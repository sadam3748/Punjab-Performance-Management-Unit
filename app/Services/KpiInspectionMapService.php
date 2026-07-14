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

        $inspections = $this->dashboardInspections($card, $user, $request);
        $statusMap = $this->buildPinStatusMap($inspections);

        $pins = $inspections
            ->map(fn (KpiInspection $inspection) => $this->inspectionPin(
                $card,
                $inspection,
                $statusMap[$inspection->id] ?? $this->inspectedStatus(),
            ))
            ->all();

        $center = $this->centerForPins($pins, $user);

        return [
            'title' => KpiLocationSlugs::mapTitle($card->slug),
            'subtitle' => $this->mapSubtitle($request),
            'scope_label' => $this->scopeLabel($user),
            'center' => $center,
            'pins' => $pins,
            'pin_count' => count($pins),
            'inspection_ids' => $inspections->pluck('id')->all(),
            'empty_message' => 'No inspection locations found for the selected period.',
        ];
    }

    /** @return Collection<int, KpiInspection> */
    private function dashboardInspections(KpiCard $card, User $user, Request $request): Collection
    {
        return $this->inspectionService->getInspectionsCollection($card, $user, $request)
            ->map(fn (KpiInspection $inspection) => $this->resolveInspectionCoordinates($inspection))
            ->filter(fn (KpiInspection $inspection) => $this->hasCoordinates($inspection))
            ->sortByDesc(fn (KpiInspection $inspection) => $inspection->inspection_datetime)
            ->values();
    }

    private function resolveInspectionCoordinates(KpiInspection $inspection): KpiInspection
    {
        if ($this->hasCoordinates($inspection)) {
            return $inspection;
        }

        $center = $this->tehsilCenter((int) $inspection->tehsil_id);
        $seed = (int) ($inspection->id ?: crc32((string) $inspection->reference_no));
        $inspection->latitude = round($center['lat'] + ((($seed % 17) - 8) * 0.00115), 7);
        $inspection->longitude = round($center['lng'] + (((($seed + 3) % 13) - 6) * 0.00115), 7);

        return $inspection;
    }

    /** @return array{lat: float, lng: float} */
    private function tehsilCenter(int $tehsilId): array
    {
        return match ($tehsilId) {
            24 => ['lat' => 30.9617, 'lng' => 70.9397], // Layyah
            25 => ['lat' => 30.9520, 'lng' => 70.9280], // Karor Lal Esan
            26 => ['lat' => 30.9005, 'lng' => 71.6512], // Chaubara
            81 => ['lat' => 31.5204, 'lng' => 74.3587], // Lahore City
            82 => ['lat' => 31.5320, 'lng' => 74.3420], // Lahore Cantt
            default => self::PUNJAB_CENTER,
        };
    }

    /**
     * Map each inspection directly to its review status colour (same clarity as Health pins).
     *
     * @return array<int, array{key: string, label: string, color: string}>
     */
    private function buildPinStatusMap(Collection $inspections): array
    {
        $map = [];

        foreach ($inspections as $inspection) {
            $map[$inspection->id] = $this->statusForPin((string) $inspection->status);
        }

        return $map;
    }

    /** @return array{key: string, label: string, color: string} */
    private function statusForPin(string $status): array
    {
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
    private function inspectionPin(KpiCard $card, KpiInspection $inspection, array $status): array
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
            'inspection_type' => $inspection->inspection_title ?: $card->title,
            'location_name' => $inspection->entity_name ?: '—',
            'facility_name' => $inspection->entity_name ?: '—',
            'inspection_date' => $inspection->inspection_datetime
                ? $inspection->inspection_datetime->format('d M Y, h:i A')
                : '—',
            'tehsil' => $inspection->tehsil?->name ?? '—',
            'district' => $inspection->district?->name ?? '—',
            'address' => $this->shortAddress($inspection),
            'review_status' => $status['label'],
            'issue_summary' => $this->issueSummary($detail, $inspection),
            'action_summary' => $this->issueSummary($detail, $inspection),
            'observation_issues' => $this->issueSummary($detail, $inspection),
            'school_name' => $card->slug === 'zebra-crossings' ? ($inspection->entity_name ?: null) : null,
            'detail_url' => route('kpi.inspections.show', [$card, $inspection]),
        ];
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
