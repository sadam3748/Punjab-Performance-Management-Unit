<?php

namespace App\Services;

use App\Models\HealthFacilityBaseline;
use App\Models\KpiCard;
use App\Models\KpiInspection;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class HealthInspectionMapService
{
    private const PUNJAB_CENTER = ['lat' => 31.1704, 'lng' => 72.7097, 'zoom' => 7];

    public function __construct(
        private readonly KpiInspectionService $inspectionService,
    ) {}

    /**
     * @param  array{facilities_inspected?: int, approved?: int, pending?: int, rejected?: int}  $context
     * @return array<string, mixed>
     */
    public function forDashboard(KpiCard $card, User $user, Request $request, array $context = []): array
    {
        if ($card->slug !== 'inspection-of-health-facilities') {
            return [];
        }

        $facilitiesInspected = max(0, (int) ($context['facilities_inspected'] ?? 0));
        $approvedQuota = max(0, (int) ($context['approved'] ?? 0));
        $pendingQuota = max(0, (int) ($context['pending'] ?? 0));
        $rejectedQuota = max(0, (int) ($context['rejected'] ?? 0));

        $inspections = $this->dashboardInspections($card, $user, $request, $facilitiesInspected);
        $statusMap = $this->buildPinStatusMap($inspections, $approvedQuota, $pendingQuota, $rejectedQuota);

        $pins = $inspections
            ->map(fn (KpiInspection $inspection) => $this->inspectionPin(
                $card,
                $inspection,
                $statusMap[$inspection->id] ?? $this->inspectedStatus(),
            ))
            ->all();

        return [
            'title' => 'Health Facility Inspection Map',
            'subtitle' => 'Showing health inspection locations for the selected period. Click any pin to view inspection detail.',
            'scope_label' => $this->scopeLabel($user),
            'center' => self::PUNJAB_CENTER,
            'pins' => $pins,
            'pin_count' => count($pins),
            'inspection_ids' => $inspections->pluck('id')->all(),
            'empty_message' => 'No health inspections found for the selected period.',
        ];
    }

    /** @return Collection<int, KpiInspection> */
    private function dashboardInspections(KpiCard $card, User $user, Request $request, int $facilitiesInspected): Collection
    {
        if ($facilitiesInspected <= 0) {
            return collect();
        }

        $inspections = $this->inspectionService->healthInspectionsForMetrics($card, $user, $request);

        return $this->limitLikeObservationCards($inspections, $facilitiesInspected)
            ->map(fn (KpiInspection $inspection) => $this->resolveInspectionCoordinates($inspection))
            ->filter(fn (KpiInspection $inspection) => $this->hasCoordinates($inspection))
            ->values();
    }

    /** @return Collection<int, KpiInspection> */
    private function limitLikeObservationCards(Collection $inspections, int $limit): Collection
    {
        if ($limit <= 0) {
            return collect();
        }

        if ($inspections->count() <= $limit) {
            return $inspections->values();
        }

        return $inspections
            ->sortByDesc(fn (KpiInspection $inspection) => $inspection->inspection_datetime)
            ->take($limit)
            ->values();
    }

    private function resolveInspectionCoordinates(KpiInspection $inspection): KpiInspection
    {
        if ($this->hasCoordinates($inspection)) {
            return $inspection;
        }

        $baseline = $this->baselineForInspection($inspection);
        if ($baseline !== null) {
            $inspection->latitude = $baseline->latitude;
            $inspection->longitude = $baseline->longitude;
        }

        return $inspection;
    }

    private function baselineForInspection(KpiInspection $inspection): ?HealthFacilityBaseline
    {
        $facilityCode = $this->facilityCodeFromIdentifier((string) ($inspection->identifier ?? ''));
        if ($facilityCode === null) {
            return null;
        }

        return HealthFacilityBaseline::query()
            ->where('facility_code', $facilityCode)
            ->where('is_active', true)
            ->first();
    }

    private function facilityCodeFromIdentifier(string $identifier): ?string
    {
        $identifier = trim($identifier);
        if ($identifier === '') {
            return null;
        }

        if (preg_match('/^(HSF-[A-Z]+-\d{3})/', $identifier, $matches)) {
            return $matches[1];
        }

        return $identifier;
    }

    /**
     * Mirror dashboard review card allocation: approved, rejected, pending quotas first;
     * remaining inspected records use the Inspected (blue) status.
     *
     * @return array<int, array{key: string, label: string, color: string}>
     */
    private function buildPinStatusMap(
        Collection $inspections,
        int $approvedQuota,
        int $pendingQuota,
        int $rejectedQuota,
    ): array {
        $sorted = $inspections
            ->sortByDesc(fn (KpiInspection $inspection) => $inspection->inspection_datetime)
            ->values();

        $map = [];
        $usedIds = [];

        foreach ($sorted->filter(fn (KpiInspection $inspection) => $inspection->status === KpiInspection::STATUS_APPROVED) as $inspection) {
            if ($this->statusCount($map, 'approved') >= $approvedQuota) {
                break;
            }

            $map[$inspection->id] = $this->approvedStatus();
            $usedIds[] = $inspection->id;
        }

        foreach ($sorted->filter(
            fn (KpiInspection $inspection) => $inspection->status === KpiInspection::STATUS_REJECTED
                && ! in_array($inspection->id, $usedIds, true)
        ) as $inspection) {
            if ($this->statusCount($map, 'rejected') >= $rejectedQuota) {
                break;
            }

            $map[$inspection->id] = $this->rejectedStatus();
            $usedIds[] = $inspection->id;
        }

        foreach ($sorted->filter(
            fn (KpiInspection $inspection) => $inspection->status === KpiInspection::STATUS_PENDING
                && ! in_array($inspection->id, $usedIds, true)
        ) as $inspection) {
            if ($this->statusCount($map, 'pending_review') >= $pendingQuota) {
                break;
            }

            $map[$inspection->id] = $this->pendingReviewStatus();
            $usedIds[] = $inspection->id;
        }

        foreach ($sorted as $inspection) {
            if (! isset($map[$inspection->id])) {
                $map[$inspection->id] = $this->inspectedStatus();
            }
        }

        return $map;
    }

    /** @param  array<int, array{key: string, label: string, color: string}>  $map */
    private function statusCount(array $map, string $key): int
    {
        return collect($map)->where('key', $key)->count();
    }

    private function hasCoordinates(KpiInspection $inspection): bool
    {
        $lat = $inspection->latitude;
        $lng = $inspection->longitude;

        return $lat !== null
            && $lng !== null
            && (float) $lat !== 0.0
            && (float) $lng !== 0.0;
    }

    /**
     * @param  array{key: string, label: string, color: string}  $status
     * @return array<string, mixed>
     */
    private function inspectionPin(KpiCard $card, KpiInspection $inspection, array $status): array
    {
        return [
            'id' => $inspection->id,
            'lat' => (float) $inspection->latitude,
            'lng' => (float) $inspection->longitude,
            'color' => $status['color'],
            'status' => $status['key'],
            'status_label' => $status['label'],
            'inspection_id' => $inspection->reference_no,
            'inspection_type' => $inspection->inspection_title ?: '—',
            'facility_name' => $inspection->entity_name ?: '—',
            'inspection_date' => $inspection->inspection_datetime
                ? $inspection->inspection_datetime->format('d M Y, h:i A')
                : '—',
            'tehsil' => $inspection->tehsil?->name ?? '—',
            'address' => $this->shortAddress($inspection),
            'review_status' => $status['label'],
            'observation_issues' => $this->inspectionService->countHealthDeficiencies($inspection),
            'detail_url' => route('kpi.inspections.show', [$card, $inspection]),
        ];
    }

    private function shortAddress(KpiInspection $inspection): string
    {
        $address = trim((string) ($inspection->address ?? ''));

        if ($address !== '') {
            return $address;
        }

        $tehsil = $inspection->tehsil?->name;
        $district = $inspection->district?->name;

        if ($tehsil && $district) {
            return sprintf('%s, %s', $tehsil, $district);
        }

        return $tehsil ?: ($district ?: '—');
    }

    private function scopeLabel(User $user): string
    {
        if ($user->tehsil?->name) {
            return $user->tehsil->name.' Tehsil';
        }

        if ($user->district?->name) {
            return $user->district->name.' District';
        }

        if ($user->division?->name) {
            return $user->division->name.' Division';
        }

        return 'Punjab';
    }

    /** @return array{key: string, label: string, color: string} */
    private function inspectedStatus(): array
    {
        return ['key' => 'inspected', 'label' => 'Inspected', 'color' => 'blue'];
    }

    /** @return array{key: string, label: string, color: string} */
    private function approvedStatus(): array
    {
        return ['key' => 'approved', 'label' => 'Approved', 'color' => 'green'];
    }

    /** @return array{key: string, label: string, color: string} */
    private function pendingReviewStatus(): array
    {
        return ['key' => 'pending_review', 'label' => 'Pending Review', 'color' => 'orange'];
    }

    /** @return array{key: string, label: string, color: string} */
    private function rejectedStatus(): array
    {
        return ['key' => 'rejected', 'label' => 'Rejected', 'color' => 'red'];
    }
}
