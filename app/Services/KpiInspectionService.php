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
  /** @var list<string> */
    private const OPERATIONAL_COUNT_STATUSES = [
        KpiInspection::STATUS_APPROVED,
        KpiInspection::STATUS_PENDING,
    ];

    public function __construct(
        private readonly KpiScopeService $scopeService,
        private readonly KpiGeoFilterService $geoFilterService,
        private readonly KpiDashboardConfigService $dashboardConfig,
        private readonly KpiPeriodService $periodService,
    ) {}

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

    public function getInspectionsCollection(KpiCard $card, User $user, Request $request): Collection
    {
        $query = $this->baseQuery($card, $user);
        $this->applyListFilters($query, $request, $user);

        return $query->with(['district:id,name', 'tehsil:id,name'])->get();
    }

    /**
     * Operational achieved count for Health/Education header cards.
     * Counts field inspections that count as completed work: approved + pending_review.
     * Rejected inspections are excluded — they are not accepted completed visits.
     */
    public function countOperationalAchieved(KpiCard $card, User $user, Request $request): int
    {
        $query = $this->baseQuery($card, $user);
        $this->applyListFilters($query, $request, $user);

        return (int) $query
            ->whereIn('status', self::OPERATIONAL_COUNT_STATUSES)
            ->count();
    }

    /** @return array{tehsils: int, districts: int, divisions: int} */
    public function activeScopeCounts(KpiCard $card, User $user, Request $request): array
    {
        $inspections = $this->getInspectionsCollection($card, $user, $request);

        return [
            'tehsils' => $inspections->pluck('tehsil_id')->filter()->unique()->count(),
            'districts' => $inspections->pluck('district_id')->filter()->unique()->count(),
            'divisions' => $inspections->pluck('division_id')->filter()->unique()->count(),
        ];
    }

    /** Total scoped inspection rows in the selected period (all statuses). */
    public function countScopedInspections(KpiCard $card, User $user, Request $request): int
    {
        $query = $this->baseQuery($card, $user);
        $this->applyListFilters($query, $request, $user);

        return (int) $query->count();
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

    /** @return list<array<string, mixed>> */
    public function getTableColumnsForKpi(string $slug): array
    {
        $slug = $this->dashboardConfig->normalizeSlug($slug);
        $definitionColumns = $this->dashboardConfig->tableColumnsFor($slug);

        $columns = [
            ['key' => 'reference_no', 'label' => 'Ref.', 'type' => 'base'],
        ];

        $entityColumns = collect($definitionColumns)->filter(
            fn (array $column): bool => ($column['from'] ?? 'detail_data') === 'entity'
        );
        $addressColumns = collect($definitionColumns)->filter(
            fn (array $column): bool => ($column['from'] ?? 'detail_data') === 'address' || $column['field'] === 'address'
        );
        $kpiColumns = collect($definitionColumns)->reject(
            fn (array $column): bool => in_array($column['from'] ?? 'detail_data', ['entity', 'address', 'inspector'], true)
        );

        foreach ($entityColumns as $column) {
            $columns[] = $this->mapTableColumn($column, 'base');
        }

        foreach ($addressColumns as $column) {
            $columns[] = $this->mapTableColumn($column, 'base');
        }

        $columns[] = ['key' => 'tehsil', 'label' => 'Tehsil', 'type' => 'base'];

        foreach ($kpiColumns as $column) {
            $columns[] = $this->mapTableColumn($column, 'kpi');
        }

        $columns[] = ['key' => 'inspected_by', 'label' => 'Insp.', 'type' => 'base'];
        $columns[] = ['key' => 'inspection_date', 'label' => 'Date', 'type' => 'base'];

        if ($slug === 'functional-and-clean-water-filtration-plants') {
            $columns[] = ['key' => 'inspection_link', 'label' => 'Link', 'type' => 'link'];
        }

        $columns[] = ['key' => 'status', 'label' => 'Status', 'type' => 'base'];
        $columns[] = ['key' => 'action', 'label' => '', 'type' => 'base'];

        return $columns;
    }

    /** @param  array{label: string, field: string, from?: string}  $column */
    private function mapTableColumn(array $column, string $type): array
    {
        return [
            'key' => $column['field'],
            'label' => $column['label'],
            'field' => $column['field'],
            'from' => $column['from'] ?? 'detail_data',
            'type' => $type,
        ];
    }

    /** @param  list<array<string, mixed>>  $columns */
    public function buildDynamicTableRows(Collection $inspections, array $columns): Collection
    {
        return $inspections->map(fn (KpiInspection $inspection): array => [
            'inspection' => $inspection,
            'cells' => collect($columns)
                ->reject(fn (array $column): bool => in_array($column['key'], ['action', 'status'], true))
                ->mapWithKeys(fn (array $column): array => [
                    $column['key'] => $this->cellValue($inspection, $column),
                ])
                ->all(),
        ]);
    }

    /** @param  array<string, mixed>  $column */
    public function cellValue(KpiInspection $inspection, array $column): string
    {
        $key = $column['key'] ?? $column['field'] ?? '';

        return match ($key) {
            'reference_no' => (string) $inspection->reference_no,
            'inspection_date' => $inspection->inspection_datetime->format('d M Y'),
            'district' => (string) ($inspection->district?->name ?? '—'),
            'tehsil' => (string) ($inspection->tehsil?->name ?? '—'),
            'inspected_by' => (string) ($inspection->inspectedBy?->name ?? '—'),
            'entity_name' => (string) ($inspection->entity_name ?? '—'),
            'address' => (string) ($inspection->address ?? '—'),
            'identifier' => (string) ($inspection->identifier ?? '—'),
            default => $this->resolveFieldValue($inspection, $column),
        };
    }

    public function filterOptions(User $user): array
    {
        return [
            'statuses' => [
                KpiInspection::STATUS_PENDING => 'Pending Review',
                KpiInspection::STATUS_APPROVED => 'Approved',
                KpiInspection::STATUS_REJECTED => 'Rejected',
            ],
        ];
    }

    /** @return \Illuminate\Support\Collection<int, KpiCard> */
    public function accessibleKpiCards(User $user): Collection
    {
        return KpiCard::query()
            ->where('is_active', true)
            ->orderBy('title')
            ->get(['id', 'title', 'slug']);
    }

    public function getAllInspectionsList(User $user, Request $request): LengthAwarePaginator
    {
        $query = KpiInspection::query()
            ->with(['kpiCard:id,title,slug,image_path', 'district:id,name', 'tehsil:id,name', 'inspectedBy:id,name'])
            ->withCount('attachments')
            ->tap(fn (Builder $q) => $this->applyInspectionScope($q, $user));

        if ($request->filled('kpi_card_id')) {
            $query->where('kpi_card_id', (int) $request->input('kpi_card_id'));
        }

        $this->applyListFilters($query, $request, $user);

        $perPage = min(50, max(10, (int) $request->input('insp_per_page', 25)));

        return $query
            ->orderByDesc('inspection_datetime')
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'insp_page')
            ->withQueryString();
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
            'detailFields' => $this->dashboardConfig->detailFieldsFor($card->slug),
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
        $this->geoFilterService->apply($query, $request, $user);

        if ($request->filled('period_type')) {
            $this->periodService->applyToQuery($query, $request, 'inspection_datetime');
        }

        if (! $skipStatus && $request->filled('insp_status')) {
            $query->where('status', $request->string('insp_status')->toString());
        }

        if ($request->filled('insp_date_from')) {
            $query->whereDate('inspection_datetime', '>=', $request->input('insp_date_from'));
        }

        if ($request->filled('insp_date_to')) {
            $query->whereDate('inspection_datetime', '<=', $request->input('insp_date_to'));
        }
    }

    /** @param  array<string, mixed>  $column */
    private function resolveFieldValue(KpiInspection $inspection, array $column): string
    {
        $from = $column['from'] ?? 'detail_data';
        $field = $column['field'] ?? $column['key'] ?? '';

        $value = match ($from) {
            'entity' => $inspection->{$field} ?? null,
            'address' => $inspection->address,
            'inspector' => $inspection->inspectedBy?->name,
            default => data_get($inspection->detail_data, $field),
        };

        if (is_array($value)) {
            return json_encode($value);
        }

        return $value === null || $value === '' ? '—' : (string) $value;
    }
}
