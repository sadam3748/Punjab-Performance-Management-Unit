<?php

namespace App\Services;

use App\Data\KpiLocationSlugs;
use App\Models\KpiCard;
use App\Models\KpiInspection;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class KpiInspectionMapService
{
    private const PUNJAB_CENTER = ['lat' => 31.1704, 'lng' => 72.7097, 'zoom' => 7];

    public function __construct(
        private readonly KpiInspectionService $inspectionService,
        private readonly KpiObservationService $observationService,
    ) {}

    /**
     * @param  array{inspected?: int, approved?: int, pending?: int, rejected?: int}  $context
     * @return array<string, mixed>
     */
    public function forDashboard(KpiCard $card, User $user, Request $request, array $context = []): array
    {
        if (! KpiLocationSlugs::hasLocationMap($card->slug)) {
            return [];
        }

        $allInspections = $this->inspectionService->getInspectionsCollection($card, $user, $request)
            ->sortByDesc(fn (KpiInspection $inspection) => $inspection->inspection_datetime)
            ->values();
        $inspections = $this->dashboardInspections($allInspections);
        $statusMap = $this->buildPinStatusMap($inspections, $user);

        $pins = $inspections
            ->map(fn (KpiInspection $inspection) => $this->inspectionPin(
                $card,
                $inspection,
                $statusMap[$inspection->id] ?? $this->inspectedStatus(),
                $request,
            ))
            ->all();

        $center = $this->centerForPins($pins, $user);
        $statusCounts = collect($pins)->countBy('status');
        $entityName = KpiLocationSlugs::entityName($card->slug);
        $totalCount = $allInspections->count();

        return [
            'title' => KpiLocationSlugs::mapTitle($card->slug),
            'subtitle' => sprintf('Showing all %s in the selected area with their current inspection status.', strtolower($entityName)),
            'scope_label' => $this->scopeLabel($user),
            'count_label' => $this->entityCountLabel($user, $entityName, $totalCount),
            'entity_label' => $entityName,
            'center' => $center,
            'pins' => $pins,
            'pin_count' => count($pins),
            'mapped_count' => count($pins),
            'facility_count' => $totalCount,
            'record_count' => $totalCount,
            'unmapped_count' => max(0, $totalCount - count($pins)),
            'status_counts' => [
                'inspected' => (int) $statusCounts->get('inspected', 0),
                'pending_review' => (int) $statusCounts->get('pending_review', 0),
                'approved' => (int) $statusCounts->get('approved', 0),
                'rejected' => (int) $statusCounts->get('rejected', 0),
            ],
            'inspection_ids' => $inspections->pluck('id')->all(),
            'empty_message' => 'No inspection locations found for the selected period.',
        ];
    }

    /** @return Collection<int, KpiInspection> */
    private function dashboardInspections(Collection $inspections): Collection
    {
        return $inspections
            ->filter(fn (KpiInspection $inspection) => $this->hasCoordinates($inspection))
            ->each(function (KpiInspection $inspection): void {
                $inspection->loadMissing(['kpiCard:id,slug', 'inspectedBy:id,name', 'reviewedBy:id,name', 'attachments']);
                $inspection->loadCount('attachments');
            })
            ->values();
    }

    /**
     * Map each inspection directly to its review status colour (same clarity as Health pins).
     *
     * @return array<int, array{key: string, label: string, color: string}>
     */
    private function buildPinStatusMap(Collection $inspections, User $user): array
    {
        $map = [];

        foreach ($inspections as $inspection) {
            $map[$inspection->id] = $this->statusForPin($inspection, $user);
        }

        return $map;
    }

    /** @return array{key: string, label: string, color: string} */
    private function statusForPin(KpiInspection $inspection, User $user): array
    {
        if (! $inspection->isSelectedFor($user)) {
            return $this->inspectedStatus();
        }

        $status = (string) $inspection->status;
        $normalized = strtolower(trim(str_replace([' ', '-'], '_', $status)));

        return match ($normalized) {
            'approved', 'reviewed', 'accepted' => $this->approvedStatus(),
            'rejected', 'reject' => $this->rejectedStatus(),
            'pending', 'pending_review', 'under_review' => $this->pendingReviewStatus(),
            'inspected', 'completed', 'submitted' => $this->inspectedStatus(),
            default => $this->inspectedStatus(),
        };
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
    private function inspectionPin(KpiCard $card, KpiInspection $inspection, array $status, Request $request): array
    {
        $detail = is_array($inspection->detail_data)
            ? $inspection->detail_data
            : (json_decode($inspection->detail_data ?? '[]', true) ?: []);

        return [
            'id' => $inspection->id,
            'lat' => (float) $inspection->latitude,
            'lng' => (float) $inspection->longitude,
            'color' => $status['color'],
            'status' => $status['key'],
            'status_label' => $status['label'],
            'inspection_id' => $inspection->reference_no,
            'kpi_name' => $card->title,
            'kpi_slug' => $card->slug,
            'is_reference_kpi' => KpiLocationSlugs::isVisitKpi($card->slug),
            'inspection_type' => $inspection->inspection_title ?: $card->title,
            'location_name' => $inspection->entity_name ?: '—',
            'facility_name' => $inspection->entity_name ?: '—',
            'inspection_date' => $inspection->inspection_datetime
                ? $inspection->inspection_datetime->format('d M Y, h:i A')
                : '—',
            'tehsil' => $inspection->tehsil?->name ?? '—',
            'district' => $inspection->district?->name ?? '—',
            'address' => $this->shortAddress($inspection),
            'review_status' => $status['key'] === 'inspected' ? 'Pending Review' : $status['label'],
            'review_color' => $status['key'] === 'inspected' ? 'orange' : $status['color'],
            'rejection_reason' => $status['key'] === 'rejected' ? ($inspection->rejection_reason ?: 'â€”') : null,
            'operational_status' => $this->operationalStatus($detail, $inspection),
            'inspector' => $inspection->inspectedBy?->name ?? '—',
            'reviewer' => $status['key'] === 'inspected' ? '—' : ($inspection->reviewedBy?->name ?? '—'),
            'evidence_count' => (int) ($inspection->attachments_count ?? 0),
            'popup_details' => $this->popupDetails($card->slug, $detail),
            'issue_summary' => $this->issueSummary($detail, $inspection),
            'action_summary' => $this->issueSummary($detail, $inspection),
            'observation_issues' => $this->issueSummary($detail, $inspection),
            'important_finding' => $this->observationService->importantFindingForInspection($inspection),
            'school_name' => $card->slug === 'zebra-crossings' ? ($inspection->entity_name ?: null) : null,
            'detail_url' => route('kpi.inspections.show', [
                $card,
                $inspection,
                'return_url' => route('kpi.dashboard', [$card] + $request->only(['period_type', 'week_no', 'month', 'year', 'date'])),
            ]),
        ];
    }

    /** @param array<string, mixed> $detail @return array<string, string|int|float> */
    private function popupDetails(string $slug, array $detail): array
    {
        $fields = match ($slug) {
            'dysfunctional-streetlights' => [
                'Lights Checked' => 'total_lights', 'Faulty Lights Found' => 'dysfunctional_lights',
                'Lights Repaired' => 'repaired_lights',
            ],
            'zebra-crossings' => [
                'School Type' => 'school_type', 'Crossing Condition' => 'crossing_status',
                'Corrective Action Required' => 'action_required', 'Corrective Action Status' => 'action_taken',
            ],
            'repair-of-small-roads-in-both-urban-and-rural-areas' => [
                'Work Type' => 'repair_type', 'Length Repaired (m)' => 'length_covered_m',
                'Lane Marking Status' => 'lane_marking_done', 'Work Start Date' => 'work_start_date',
                'Completion Date' => 'completion_date',
            ],
            default => [],
        };

        $result = [];
        foreach ($fields as $label => $key) {
            $value = $detail[$key] ?? null;
            if ($value !== null && $value !== '') {
                $result[$label] = is_scalar($value) ? $value : json_encode($value);
            }
        }

        if ($slug === 'dysfunctional-streetlights') {
            $result['Lights Pending Repair'] = max(0, (int) ($detail['dysfunctional_lights'] ?? 0) - (int) ($detail['repaired_lights'] ?? 0));
        }

        return $result;
    }

    /** @param  array<string, mixed>  $detail */
    private function operationalStatus(array $detail, KpiInspection $inspection): string
    {
        foreach (['operational_status', 'completion_status', 'work_status', 'cleaned_status', 'inspection_status'] as $key) {
            $value = trim((string) ($detail[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return $inspection->status === KpiInspection::STATUS_PENDING ? 'Submitted' : 'Completed';
    }

    /** @param  array<string, mixed>  $detail */
    private function issueSummary(array $detail, KpiInspection $inspection): string
    {
        foreach (['issue_summary', 'violation', 'action_taken', 'work_status', 'completion_status', 'encroachment_type'] as $key) {
            $value = trim((string) ($detail[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        $actions = $inspection->actions_taken;
        if (is_array($actions) && $actions !== []) {
            return (string) ($actions[0] ?? 'Field inspection completed.');
        }

        return 'Field inspection completed.';
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

    private function entityCountLabel(User $user, string $entityName, int $total): string
    {
        return match ($user->role?->slug) {
            'ac', 'field_user' => sprintf('Total %s in %s Tehsil: %d', $entityName, $user->tehsil?->name ?? '—', $total),
            'dc' => sprintf('Total %s in %s District: %d', $entityName, $user->district?->name ?? '—', $total),
            'commissioner' => sprintf('Total %s in %s Division: %d', $entityName, $user->division?->name ?? '—', $total),
            default => sprintf('Total %s in Punjab: %d', $entityName, $total),
        };
    }

    private function mapSubtitle(Request $request): string
    {
        $params = app(KpiPeriodService::class)->resolvedParams($request);

        if (($params['period_type'] ?? '') === 'daily') {
            return 'Showing today\'s inspection and action locations. Pin colour reflects review status — click any pin for detail.';
        }

        return 'Showing inspection and action locations for the selected week. Pin colour reflects review status — click any pin for detail.';
    }

    /**
     * @param  list<array<string, mixed>>  $pins
     * @return array{lat: float, lng: float, zoom: int}
     */
    private function centerForPins(array $pins, User $user): array
    {
        if ($pins === []) {
            return self::PUNJAB_CENTER;
        }

        $lats = array_map(fn (array $pin) => (float) $pin['lat'], $pins);
        $lngs = array_map(fn (array $pin) => (float) $pin['lng'], $pins);

        return [
            'lat' => array_sum($lats) / count($lats),
            'lng' => array_sum($lngs) / count($lngs),
            'zoom' => $user->tehsil_id ? 14 : ($user->district_id ? 12 : self::PUNJAB_CENTER['zoom']),
        ];
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
