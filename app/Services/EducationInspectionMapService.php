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

        $inspectionByCode = $inspections
            ->groupBy(fn (KpiInspection $inspection) => $this->institutionCodeFromIdentifier((string) $inspection->identifier))
            ->map(fn (Collection $records) => $records->sortByDesc(fn (KpiInspection $record) => $this->statusPriority($record))->first());
        $baselines = $this->assignedInstitutions($user);
        $pins = $baselines->map(function (EducationInstitutionBaseline $institution) use ($card, $request, $inspectionByCode, $reviewedPinIds) {
            $inspection = $inspectionByCode->get($institution->institution_code);
            if (! $inspection || $inspection->status === KpiInspection::STATUS_DRAFT) return $this->uninspectedPin($card, $institution, $request);
            $status = in_array($inspection->id, $reviewedPinIds, true) ? $this->statusForInspection($inspection) : $this->inspectedStatus();
            return $this->inspectionPin($card, $inspection, $status, $request);
        })->all();
        $statusCounts = collect($pins)->countBy('status')->all();
        $institutionCount = $baselines->count();
        $mappedCount = $baselines->filter(fn (EducationInstitutionBaseline $institution): bool => $this->institutionHasCoordinates($institution))->count();
        $unmappedCount = max(0, $institutionCount - $mappedCount);

        return [
            'title' => 'Educational Institution Inspection Coverage Map',
            'subtitle' => 'Showing all educational institutions in the selected area with their current inspection status.',
            'scope_label' => $this->scopeLabel($user),
            'count_label' => $this->institutionCountLabel($user, $institutionCount),
            'center' => self::PUNJAB_CENTER,
            'pins' => $pins,
            'pin_count' => count($pins),
            'mapped_count' => $mappedCount,
            'facility_count' => $institutionCount,
            'unmapped_count' => $unmappedCount,
            'status_counts' => array_merge(['not_inspected' => 0, 'inspected' => 0, 'pending_review' => 0, 'approved' => 0, 'rejected' => 0], $statusCounts),
            'inspection_ids' => $inspections->pluck('id')->all(),
            'empty_message' => 'No education inspections found for the selected period.',
            'entity_label' => 'Educational Institutions',
        ];
    }

    private function assignedInstitutions(User $user): Collection
    {
        $query = EducationInstitutionBaseline::query()->with(['tehsil', 'district'])->where('is_active', true);
        if ($user->tehsil_id) $query->where('tehsil_id', $user->tehsil_id);
        elseif ($user->district_id) $query->where('district_id', $user->district_id);
        elseif ($user->division_id) $query->where('division_id', $user->division_id);
        return $query->orderBy('institution_code')->get();
    }

    private function uninspectedPin(KpiCard $card, EducationInstitutionBaseline $institution, Request $request): array
    {
        return [
            'id' => 'education-'.$institution->id, 'lat' => (float) $institution->latitude, 'lng' => (float) $institution->longitude,
            'color' => 'grey', 'status' => 'not_inspected', 'status_label' => 'Not Inspected',
            'inspection_id' => 'Not assigned', 'inspection_type' => $institution->institution_type ?: 'School',
            'facility_name' => $institution->name, 'institution_name' => $institution->name,
            'inspection_date' => 'Not yet inspected', 'operational_status' => 'Not Inspected', 'tehsil' => $institution->tehsil?->name ?? '—',
            'district' => $institution->district?->name ?? '—', 'address' => $institution->address ?: '—',
            'review_status' => 'Not Applicable', 'observation_issues' => 0, 'important_finding' => 'Inspection pending',
            'students_enrolled' => '—', 'students_present' => '—', 'action_label' => 'View Details',
            'detail_url' => route('kpi.entities.show', [$card, 'education', $institution->id] + $request->only(['period_type', 'week_no', 'month', 'year', 'date'])),
        ];
    }

    /** @return Collection<int, KpiInspection> */
    private function dashboardInspections(KpiCard $card, User $user, Request $request, int $institutionsInspected, array $statusQuotas): Collection
    {
        $inspections = $this->inspectionService->getInspectionsCollection($card, $user, $request);
        $drafts = $inspections->where('status', KpiInspection::STATUS_DRAFT);
        $completed = $inspections->where('status', '!=', KpiInspection::STATUS_DRAFT);

        return $this->limitLikeObservationCards($completed, $institutionsInspected, $statusQuotas)->concat($drafts)
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

    private function statusPriority(KpiInspection $inspection): int
    {
        return match ($inspection->status) {
            KpiInspection::STATUS_REJECTED => 60,
            KpiInspection::STATUS_APPROVED => 50,
            KpiInspection::STATUS_PENDING => 40,
            KpiInspection::STATUS_INSPECTED => 30,
            KpiInspection::STATUS_DRAFT => 20,
            default => 10,
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
            'inspection_type' => $inspection->inspection_title ?: '—',
            'facility_name' => $inspection->entity_name ?: '—',
            'institution_name' => $inspection->entity_name ?: '—',
            'inspection_date' => $inspection->inspection_datetime
                ? $inspection->inspection_datetime->format('d M Y, h:i A')
                : '—',
            'tehsil' => $inspection->tehsil?->name ?? '—',
            'address' => $this->shortAddress($inspection),
            'district' => $inspection->district?->name ?? '—',
            'review_status' => $status['key'] === 'inspected' ? 'Pending Review' : $status['label'],
            'review_color' => $status['key'] === 'inspected' ? 'amber' : $status['color'],
            'operational_status' => 'Completed',
            'action_label' => 'View Details',
            'observation_issues' => $this->inspectionService->countEducationDeficiencies($inspection),
            'important_finding' => $this->inspectionService->educationDeficiencySummary($inspection),
            'students_enrolled' => $detail['students_enrolled'] ?? '—',
            'students_present' => $detail['students_present'] ?? '—',
            'detail_url' => route('kpi.inspections.show', [
                $card,
                $inspection,
                'return_url' => route('kpi.dashboard', [$card] + $request->only(['period_type', 'week_no', 'month', 'year', 'date'])),
            ]),
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

    private function institutionCountLabel(User $user, int $count): string
    {
        return match ($user->role?->slug) {
            'ac', 'field_user' => sprintf('Total Educational Institutions in %s Tehsil: %d', $user->tehsil?->name ?? '—', $count),
            'dc' => sprintf('Total Educational Institutions in %s District: %d', $user->district?->name ?? '—', $count),
            'commissioner' => sprintf('Total Educational Institutions in %s Division: %d', $user->division?->name ?? '—', $count),
            default => sprintf('Total Educational Institutions in Punjab: %d', $count),
        };
    }

    private function institutionHasCoordinates(EducationInstitutionBaseline $institution): bool
    {
        return $institution->latitude !== null
            && $institution->longitude !== null
            && (float) $institution->latitude !== 0.0
            && (float) $institution->longitude !== 0.0;
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
