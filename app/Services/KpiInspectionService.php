<?php

namespace App\Services;

use App\Models\KpiCard;
use App\Models\KpiInspection;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class KpiInspectionService
{
    public function __construct(private readonly KpiScopeService $scopeService) {}

    public function applyInspectionScope(Builder $query, User $user): Builder
    {
        return $this->scopeService->apply($query, $user);
    }

    public function baseQuery(KpiCard $card, User $user): Builder
    {
        return KpiInspection::query()
            ->where('kpi_card_id', $card->id)
            ->tap(fn (Builder $q) => $this->applyInspectionScope($q, $user));
    }

    public function getInspectionListForKpi(KpiCard $card, User $user, Request $request): LengthAwarePaginator
    {
        $query = $this->baseQuery($card, $user)
            ->with(['district:id,name', 'tehsil:id,name', 'inspectedBy:id,name', 'attachments'])
            ->withCount('attachments');

        $this->applyListFilters($query, $request, $user);

        $perPage = min(50, max(10, (int) $request->input('insp_per_page', 10)));

        return $query
            ->orderByDesc('inspection_datetime')
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'insp_page')
            ->withQueryString();
    }

    public function buildStatusCounts(KpiCard $card, User $user, Request $request): array
    {
        $query = $this->baseQuery($card, $user);
        $this->applyListFilters($query, $request, $user, skipStatus: true);

        $counts = (clone $query)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'total' => (int) $counts->sum(),
            'pending_review' => (int) ($counts[KpiInspection::STATUS_PENDING] ?? 0),
            'approved' => (int) ($counts[KpiInspection::STATUS_APPROVED] ?? 0),
            'rejected' => (int) ($counts[KpiInspection::STATUS_REJECTED] ?? 0),
        ];
    }

    public function filterOptions(User $user): array
    {
        $role = $user->role?->slug;

        $districts = collect();
        $tehsils = collect();

        if (in_array($role, ['super_admin', 'chief_secretary', 'pmru_user', 'viewer', 'commissioner'], true)) {
            $districts = DB::table('districts')->orderBy('name')->pluck('name', 'id');
        }

        if (in_array($role, ['super_admin', 'chief_secretary', 'pmru_user', 'viewer', 'commissioner', 'dc'], true)) {
            $tehsilQuery = DB::table('tehsils')->orderBy('name');
            if ($role === 'dc') {
                $tehsilQuery->where('district_id', $user->district_id);
            } elseif ($role === 'commissioner') {
                $tehsilQuery->whereIn('district_id', DB::table('districts')->where('division_id', $user->division_id)->pluck('id'));
            }
            $tehsils = $tehsilQuery->pluck('name', 'id');
        }

        return [
            'statuses' => [
                KpiInspection::STATUS_PENDING => 'Pending Review',
                KpiInspection::STATUS_APPROVED => 'Approved',
                KpiInspection::STATUS_REJECTED => 'Rejected',
            ],
            'districts' => $districts,
            'tehsils' => $tehsils,
            'show_district_filter' => $districts->isNotEmpty(),
            'show_tehsil_filter' => $tehsils->isNotEmpty(),
        ];
    }

    public function getInspectionDetail(KpiCard $card, KpiInspection $inspection, User $user): array
    {
        abort_if($inspection->kpi_card_id !== $card->id, 404);
        abort_unless($this->canAccessInspection($inspection, $user), 403);

        $inspection->load([
            'kpiCard',
            'district',
            'tehsil',
            'division',
            'inspectedBy',
            'reviewedBy',
            'attachments',
        ]);

        return [
            'inspection' => $inspection,
            'canReview' => $this->canReviewInspection($inspection, $user),
            'fallbackImage' => $card->resolvedImagePath(),
            'googleMapsKey' => config('services.google_maps.key'),
        ];
    }

    public function canAccessInspection(KpiInspection $inspection, User $user): bool
    {
        return $this->baseQuery($inspection->kpiCard, $user)
            ->where('kpi_inspections.id', $inspection->id)
            ->exists();
    }

    public function canReviewInspection(KpiInspection $inspection, User $user): bool
    {
        if (! $inspection->isPending()) {
            return false;
        }

        if (! $this->canAccessInspection($inspection, $user)) {
            return false;
        }

        return match ($user->role?->slug) {
            'super_admin', 'chief_secretary', 'pmru_user' => true,
            'commissioner' => (int) $inspection->division_id === (int) $user->division_id,
            'dc' => (int) $inspection->district_id === (int) $user->district_id,
            'ac', 'field_user' => (int) $inspection->tehsil_id === (int) $user->tehsil_id,
            default => false,
        };
    }

    public function approveInspection(KpiInspection $inspection, User $user, ?string $remarks = null): KpiInspection
    {
        abort_unless($this->canReviewInspection($inspection, $user), 403);

        $inspection->update([
            'status' => KpiInspection::STATUS_APPROVED,
            'reviewed_by' => $user->id,
            'reviewed_at' => now(),
            'review_remarks' => $remarks,
            'rejection_reason' => null,
        ]);

        return $inspection->fresh(['reviewedBy', 'attachments', 'district', 'tehsil', 'inspectedBy']);
    }

    public function rejectInspection(KpiInspection $inspection, User $user, string $reason): KpiInspection
    {
        abort_unless($this->canReviewInspection($inspection, $user), 403);

        $inspection->update([
            'status' => KpiInspection::STATUS_REJECTED,
            'reviewed_by' => $user->id,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
        ]);

        return $inspection->fresh(['reviewedBy', 'attachments', 'district', 'tehsil', 'inspectedBy']);
    }

    public function canReviewInspections(User $user): bool
    {
        return in_array($user->role?->slug, [
            'super_admin', 'chief_secretary', 'pmru_user',
            'commissioner', 'dc', 'ac', 'field_user',
        ], true);
    }

  private function applyListFilters(Builder $query, Request $request, User $user, bool $skipStatus = false): void
    {
        if (! $skipStatus && $request->filled('insp_status')) {
            $query->where('status', $request->string('insp_status')->toString());
        }

        if ($request->filled('insp_district') && $this->filterOptions($user)['show_district_filter']) {
            $query->where('district_id', (int) $request->input('insp_district'));
        }

        if ($request->filled('insp_tehsil') && $this->filterOptions($user)['show_tehsil_filter']) {
            $query->where('tehsil_id', (int) $request->input('insp_tehsil'));
        }

        if ($request->filled('insp_date_from')) {
            $query->whereDate('inspection_datetime', '>=', $request->input('insp_date_from'));
        }

        if ($request->filled('insp_date_to')) {
            $query->whereDate('inspection_datetime', '<=', $request->input('insp_date_to'));
        }
    }
}
