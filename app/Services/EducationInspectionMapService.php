<?php

namespace App\Services;

use App\Models\EducationInstitutionBaseline;
use App\Models\KpiCard;
use App\Models\KpiInspection;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class EducationInspectionMapService
{
    private const PUNJAB_CENTER = ['lat' => 31.1704, 'lng' => 72.7097, 'zoom' => 7];

    public function __construct(
        private readonly KpiInspectionService $inspectionService,
    ) {}

    /**
     * @param  array{institutions_inspected?: int, approved?: int, pending?: int, rejected?: int}  $context
     * @return array<string, mixed>
     */
    public function forDashboard(KpiCard $card, User $user, Request $request, array $context = []): array
    {
        if ($card->slug !== 'inspection-of-educational-institutions') {
            return [];
        }

        $institutionsInspected = max(0, (int) ($context['institutions_inspected'] ?? 0));
        $statusQuotas = [
            KpiInspection::STATUS_APPROVED => max(0, (int) ($context['approved'] ?? 0)),
            KpiInspection::STATUS_PENDING => max(0, (int) ($context['pending'] ?? 0)),
            KpiInspection::STATUS_REJECTED => max(0, (int) ($context['rejected'] ?? 0)),
        ];
        $inspections = $this->dashboardInspections($card, $user, $request, $institutionsInspected, $statusQuotas);
        $reviewedPinIds = $this->reviewedPinIds($inspections, $statusQuotas);

        $pins = $inspections
            ->map(fn (KpiInspection $inspection) => $this->inspectionPin(
                $card,
                $inspection,
                in_array($inspection->id, $reviewedPinIds, true)
                    ? $this->statusForInspection($inspection)
                    : $this->inspectedStatus(),
            ))
            ->all();

        return [
            'title' => 'Educational Institution Inspection Map',
            'subtitle' => 'Showing education inspection locations for the selected period. Click any pin to view inspection detail.',
            'scope_label' => $this->scopeLabel($user),
            'center' => self::PUNJAB_CENTER,
            'pins' => $pins,
            'pin_count' => count($pins),
            'inspection_ids' => $inspections->pluck('id')->all(),
            'empty_message' => 'No education inspections found for the selected period.',
            'entity_label' => 'Institution',
        ];
    }

    /** @return Collection<int, KpiInspection> */
    private function dashboardInspections(KpiCard $card, User $user, Request $request, int $institutionsInspected, array $statusQuotas): Collection
    {
        if ($institutionsInspected <= 0) {
            return collect();
        }

        $inspections = $this->inspectionService->educationInspectionsForMetrics($card, $user, $request);

        return $this->limitLikeObservationCards($inspections, $institutionsInspected, $statusQuotas)
            ->map(fn (KpiInspection $inspection) => $this->resolveInspectionCoordinates($inspection))
            ->filter(fn (KpiInspection $inspection) => $this->hasCoordinates($inspection))
            ->values();
    }

    /** @return Collection<int, KpiInspection> */
    private function limitLikeObservationCards(Collection $inspections, int $limit, array $statusQuotas): Collection
    {
        if ($limit <= 0) {
            return collect();
        }

        $sorted = $inspections->sortByDesc(fn (KpiInspection $inspection) => $inspection->inspection_datetime)->values();
        $selected = collect();
        foreach ($statusQuotas as $status => $quota) {
            $selected = $selected->concat($sorted->where('status', $status)->take($quota));
        }

        return $selected
            ->concat($sorted->whereNotIn('id', $selected->pluck('id'))->take(max(0, $limit - $selected->count())))
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

    private function baselineForInspection(KpiInspection $inspection): ?EducationInstitutionBaseline
    {
        $institutionCode = $this->institutionCodeFromIdentifier((string) ($inspection->identifier ?? ''));
        if ($institutionCode === null) {
            return null;
        }

        return EducationInstitutionBaseline::query()
            ->where('institution_code', $institutionCode)
            ->where('is_active', true)
            ->first();
    }

    private function institutionCodeFromIdentifier(string $identifier): ?string
    {
        $identifier = trim($identifier);
        if ($identifier === '') {
            return null;
        }

        if (preg_match('/^(EDU-[A-Z]+-\d{3})/', $identifier, $matches)) {
            return $matches[1];
        }

        return $identifier;
    }

    /** @return array{key: string, label: string, color: string} */
    private function statusForInspection(KpiInspection $inspection): array
    {
        return match ($inspection->status) {
            KpiInspection::STATUS_APPROVED => $this->approvedStatus(),
            KpiInspection::STATUS_PENDING => $this->pendingReviewStatus(),
            KpiInspection::STATUS_REJECTED => $this->rejectedStatus(),
            default => $this->inspectedStatus(),
        };
    }

    /** @return list<int> */
    private function reviewedPinIds(Collection $inspections, array $statusQuotas): array
    {
        return collect($statusQuotas)
            ->flatMap(fn (int $quota, string $status) => $inspections->where('status', $status)->take($quota)->pluck('id'))
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
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
            'inspection_type' => $inspection->inspection_title ?: '—',
            'facility_name' => $inspection->entity_name ?: '—',
            'institution_name' => $inspection->entity_name ?: '—',
            'inspection_date' => $inspection->inspection_datetime
                ? $inspection->inspection_datetime->format('d M Y, h:i A')
                : '—',
            'tehsil' => $inspection->tehsil?->name ?? '—',
            'address' => $this->shortAddress($inspection),
            'review_status' => $status['label'],
            'observation_issues' => $this->inspectionService->countEducationDeficiencies($inspection),
            'students_enrolled' => $detail['students_enrolled'] ?? '—',
            'students_present' => $detail['students_present'] ?? '—',
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
