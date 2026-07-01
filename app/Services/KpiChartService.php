<?php

namespace App\Services;

use App\Models\KpiInspection;
use App\Models\User;
use Illuminate\Support\Collection;

class KpiChartService
{
    public function __construct(private readonly KpiFormulaService $formula) {}

    /**
     * @param  list<array{type: string, title: string, key: string}>  $definitions
     * @return array<string, mixed>
     */
    public function buildForKpi(
        string $slug,
        Collection $submissions,
        Collection $inspections,
        User $user,
        float $target,
        float $achieved,
        Collection $areaScores,
        array $definitions,
    ): array {
        $legacy = $this->build($submissions, $user, $target, $achieved, $areaScores);
        $datasets = $this->buildDatasets($slug, $submissions, $inspections, $user, $target, $achieved, $areaScores, $legacy);

        $configured = collect($definitions)->map(function (array $definition) use ($datasets, $user): array {
            $key = $definition['key'];
            $comparisonKeys = ['tehsil_comparison', 'district_comparison', 'division_comparison'];

            if (in_array($key, $comparisonKeys, true)) {
                $definition['comparison_label'] = $this->comparisonLabel($user, $key);
            }

            $data = $datasets[$key] ?? ['labels' => [], 'values' => []];
            if (($definition['type'] ?? null) === 'gauge') {
                $data['values'] = collect($data['values'] ?? [])
                    ->map(fn ($value) => $this->formula->displayPercentage((float) $value))
                    ->values()
                    ->all();
            }

            return array_merge($definition, ['data' => $data]);
        })->values()->all();

        return array_merge($legacy, [
            'definitions' => $configured,
            'datasets' => $datasets,
        ]);
    }

    public function build(Collection $submissions, User $user, float $target, float $achieved, Collection $areaScores): array
    {
        $statusCounts = $submissions->countBy('status');
        $totalAchieved = $submissions->sum(fn ($s) => (float) ($s->achieved_value ?? $s->score));
        $totalPending = $submissions->sum(fn ($s) => (float) ($s->pending_value ?? 0));
        $pct = $this->formula->achievementPercentage($achieved, $target);

        $trend = $submissions
            ->sortBy('submission_date')
            ->groupBy(fn ($item) => $item->submission_date->format('d M'))
            ->map(fn ($group) => $this->formula->displayPercentage(
                (float) $group->avg(fn ($item) => (float) ($item->achievement_percentage ?? $item->kpiScore?->percentage ?? $item->score))
            ))
            ->take(14);

        if ($trend->isEmpty()) {
            $trend = collect([now()->format('d M') => $pct]);
        }

        $achievedPending = collect($this->formula->donutSplit(
            $totalAchieved ?: $achieved,
            $totalPending ?: max(0, $target - $achieved)
        ));

        $statusDonut = collect([
            'Approved' => (int) ($statusCounts->get('approved', 0)),
            'Submitted' => (int) ($statusCounts->get('submitted', 0)),
            'Pending' => (int) ($statusCounts->get('pending', 0) + $statusCounts->get('draft', 0)),
            'Rejected' => (int) ($statusCounts->get('rejected', 0)),
        ])->filter(fn ($v) => $v > 0);

        if ($statusDonut->isEmpty()) {
            $statusDonut = collect(['Approved' => $submissions->count() ?: 1]);
        }

        $comparisonLabel = $this->comparisonLabel($user);

        return [
            'status' => $statusCounts->isNotEmpty() ? $statusCounts : collect(['approved' => $submissions->count() ?: 1]),
            'donut' => $achievedPending,
            'status_donut' => $statusDonut,
            'areas' => $areaScores->isNotEmpty() ? $areaScores->sortDesc()->take(10) : collect(['No area data' => $pct]),
            'trend' => $trend,
            'target_achieved' => collect([
                'Target' => round($target, 1),
                'Achieved' => round($achieved, 1),
            ]),
            'comparison_label' => $comparisonLabel,
        ];
    }

    /** @return array<string, array{labels: list<string>, values: list<float|int>}> */
    private function buildDatasets(
        string $slug,
        Collection $submissions,
        Collection $inspections,
        User $user,
        float $target,
        float $achieved,
        Collection $areaScores,
        array $legacy,
    ): array {
        $pct = $this->formula->achievementPercentage($achieved, $target);

        $inspectionTrend = $inspections
            ->sortBy('inspection_datetime')
            ->groupBy(fn (KpiInspection $item) => $item->inspection_datetime->format('d M'))
            ->map(fn ($group) => $group->count())
            ->take(14);

        if ($inspectionTrend->isEmpty()) {
            $inspectionTrend = collect([now()->format('d M') => max(1, $inspections->count())]);
        }

        $inspectionStatus = $inspections->countBy(fn (KpiInspection $item) => $item->statusLabel());
        if ($inspectionStatus->isEmpty()) {
            $inspectionStatus = collect(['Pending Review' => 1]);
        }

        $violationBreakdown = $this->detailFieldBreakdown($inspections, ['violation', 'violation_type', 'complaint_status', 'cleanliness_status', 'functional_status']);
        $typeBreakdown = $this->detailFieldBreakdown($inspections, ['plant_type', 'facility_type', 'service_type', 'type', 'commodity', 'action_type']);

        $tehsilComparison = $inspections
            ->groupBy(fn (KpiInspection $item) => $item->tehsil?->name ?? 'Unknown')
            ->map(fn ($group) => $group->count())
            ->sortDesc()
            ->take(10);

        $districtComparison = $inspections
            ->groupBy(fn (KpiInspection $item) => $item->district?->name ?? 'Unknown')
            ->map(fn ($group) => $group->count())
            ->sortDesc()
            ->take(10);

        $gaugeValue = $inspections->isEmpty()
            ? $pct
            : $this->formula->percentage(
                $inspections->where('status', KpiInspection::STATUS_APPROVED)->count(),
                $inspections->count()
            );

        $fineTotal = $inspections->sum(fn (KpiInspection $item) => (float) data_get($item->detail_data, 'fine', 0));
        $fineRatio = $inspections->isEmpty() ? 0 : round(min(100, ($fineTotal / max(1, $inspections->count())) / 100), 1);

        $toChart = static fn (Collection $data): array => [
            'labels' => $data->keys()->values()->all(),
            'values' => $data->values()->map(fn ($v) => is_numeric($v) ? (float) $v : 0)->values()->all(),
        ];

        $submissionVisitTrend = $submissions
            ->sortBy('submission_date')
            ->groupBy(fn ($item) => $item->submission_date->format('d M'))
            ->map(fn ($group) => (float) $group->sum(fn ($item) => (float) (
                data_get($item->metric_snapshot, 'institution_visits')
                ?? data_get($item->metric_snapshot, 'tandoor_inspections')
                ?? data_get($item->metric_snapshot, 'facility_visits')
                ?? 0
            )))
            ->take(14);

        if ($submissionVisitTrend->isEmpty()) {
            $submissionVisitTrend = $inspectionTrend;
        }

        $schoolCouncilActivated = $inspections->filter(
            fn (KpiInspection $item) => in_array(strtolower((string) data_get($item->detail_data, 'school_council_activated', '')), ['yes', '1', 'true'], true)
        )->count();
        $schoolCouncilFromSubmissions = $this->formula->displayPercentage(
            (float) $submissions->avg(fn ($s) => (float) data_get($s->metric_snapshot, 'school_council_activated', 0)) * 100,
        );
        $schoolCouncilGauge = $inspections->isEmpty()
            ? $schoolCouncilFromSubmissions
            : $this->formula->percentage($schoolCouncilActivated, $inspections->count());
        if ($schoolCouncilGauge <= 0 && $schoolCouncilFromSubmissions > 0) {
            $schoolCouncilGauge = $schoolCouncilFromSubmissions;
        }

        $acVisitTarget = max(1, (int) $submissions->max(fn ($s) => (float) data_get($s->metric_snapshot, 'ac_visit_target', 2)));
        $acVisitsDone = (int) $submissions->sum(fn ($s) => (float) data_get($s->metric_snapshot, 'ac_visits', 0));
        $acVisitGauge = $this->formula->percentage($acVisitsDone, $acVisitTarget);
        if ($acVisitsDone === 0 && $target > 0) {
            $acVisitGauge = $this->formula->percentage($achieved, $target);
        }

        $healthIssues = $this->healthIssueBreakdownFromInspections($inspections);

        $dcAcVisitCompletion = collect([
            'DC Visits' => $submissions->sum(fn ($i) => (float) data_get($i->metric_snapshot, 'dc_visits', data_get($i->metric_snapshot, 'dc_visit_completion', 0))),
            'AC Visits' => $submissions->sum(fn ($i) => (float) data_get($i->metric_snapshot, 'ac_visits', data_get($i->metric_snapshot, 'ac_visit_completion', 0))),
        ]);

        if ($slug === 'inspection-of-health-facilities') {
            $activeTehsils = max(1, $inspections->pluck('tehsil_id')->filter()->unique()->count());
            $acTarget = max(2, $activeTehsils * 2);
            $dcTarget = 2;
            $acCompleted = (int) $inspections->whereIn('status', [KpiInspection::STATUS_APPROVED, KpiInspection::STATUS_PENDING])->count();
            $dcCompleted = (int) $submissions->sum(fn ($i) => (float) data_get($i->metric_snapshot, 'dc_visits', 0));

            if (in_array($user->role?->slug, ['ac', 'field_user'], true)) {
                $acTarget = 2;
            }

            $dcAcVisitCompletion = collect([
                'AC Visits %' => min(100.0, $this->formula->percentage(min($acCompleted, $acTarget), $acTarget)),
                'DC Visits %' => min(100.0, $this->formula->percentage(min($dcCompleted, $dcTarget), $dcTarget)),
            ]);
        }

        return [
            'plant_inspections_trend' => $toChart($inspectionTrend),
            'inspection_trend' => $toChart($inspectionTrend),
            'functional_status_breakdown' => $toChart($this->detailFieldBreakdown($inspections, ['functional_status'])),
            'plant_status_breakdown' => $toChart($this->detailFieldBreakdown($inspections, ['functional_status'])),
            'filter_change_compliance' => ['labels' => ['Compliance'], 'values' => [$gaugeValue]],
            'clean_unclean_breakdown' => $toChart($this->detailFieldBreakdown($inspections, ['cleanliness_status', 'cleanliness'])),
            'clean_vs_unclean' => $toChart($this->detailFieldBreakdown($inspections, ['cleanliness_status', 'cleanliness'])),
            'daily_inspections_trend' => $toChart($submissionVisitTrend->isNotEmpty() ? $submissionVisitTrend : $inspectionTrend),
            'institution_visits_trend' => $toChart($submissionVisitTrend),
            'inspection_activity_trend' => $toChart($inspectionTrend),
            'terminal_inspections_trend' => $toChart($inspectionTrend),
            'market_inspections_trend' => $toChart($inspectionTrend),
            'maintenance_activity_trend' => $toChart($inspectionTrend),
            'drain_cleaning_trend' => $toChart($inspectionTrend),
            'complaint_resolution_trend' => $toChart($inspectionTrend),
            'received_resolved_trend' => $toChart(collect([
                'Received' => $submissions->sum(fn ($i) => (float) data_get($i->metric_snapshot, 'complaints_received', data_get($i->metric_snapshot, 'citizen_complaints_received', 0))),
                'Resolved' => $submissions->sum(fn ($i) => (float) data_get($i->metric_snapshot, 'complaints_resolved', data_get($i->metric_snapshot, 'complaints_resolved_count', 0))),
            ])),
            'application_processing_trend' => $toChart($inspectionTrend),
            'violation_type_breakdown' => $toChart($violationBreakdown),
            'violation_breakdown' => $toChart($violationBreakdown),
            'cleanliness_status_breakdown' => $toChart($this->detailFieldBreakdown($inspections, ['cleanliness_status', 'cleanliness'])),
            'complaint_status_breakdown' => $toChart($this->detailFieldBreakdown($inspections, ['complaint_status'])),
            'service_type_breakdown' => $toChart($typeBreakdown),
            'facility_compliance_breakdown' => $toChart($this->detailFieldBreakdown($inspections, ['fare_display', 'waiting_area', 'washroom'])),
            'maintenance_status_breakdown' => $toChart($this->detailFieldBreakdown($inspections, ['maintenance_status', 'completion_status'])),
            'blockage_status_breakdown' => $toChart($this->detailFieldBreakdown($inspections, ['cleaned_status', 'blockage_identified'])),
            'greenbelt_type_breakdown' => $toChart($typeBreakdown),
            'action_type_breakdown' => $toChart($this->detailFieldBreakdown($inspections, ['action_type'])),
            'tehsil_comparison' => $toChart($tehsilComparison->isNotEmpty() ? $tehsilComparison : $legacy['areas']),
            'district_comparison' => $toChart($districtComparison->isNotEmpty() ? $districtComparison : $legacy['areas']),
            'division_comparison' => $toChart($legacy['areas']),
            'fine_to_inspection_ratio' => ['labels' => ['Ratio'], 'values' => [$fineRatio]],
            'enforcement_rate' => ['labels' => ['Rate'], 'values' => [$gaugeValue]],
            'campaign_compliance' => ['labels' => ['Compliance'], 'values' => [$gaugeValue]],
            'maintenance_compliance' => ['labels' => ['Compliance'], 'values' => [$gaugeValue]],
            'demarcation_compliance' => ['labels' => ['Compliance'], 'values' => [$gaugeValue]],
            'compliance_rate' => ['labels' => ['Compliance'], 'values' => [$gaugeValue]],
            'resolution_rate' => ['labels' => ['Resolution'], 'values' => [$gaugeValue]],
            'timeline_compliance_rate' => ['labels' => ['Timeline'], 'values' => [$gaugeValue]],
            'overall_terminal_score' => ['labels' => ['Score'], 'values' => [$gaugeValue]],
            'cleaned_status_rate' => ['labels' => ['Cleaned'], 'values' => [$gaugeValue]],
            'status_donut' => $toChart($inspectionStatus),
            'inspection_status_breakdown' => $toChart($inspectionStatus),
            'ac_visit_completion_gauge' => ['labels' => ['Completion'], 'values' => [$acVisitGauge]],
            'target_achieved' => $toChart($legacy['target_achieved']),
            'performance_trend' => $toChart($legacy['trend']),
            'dc_ac_inspection_comparison' => $toChart($this->detailFieldBreakdown($inspections, ['dc_inspected', 'ac_inspected'])),
            'dc_ac_visit_completion' => $toChart($dcAcVisitCompletion),
            'facility_deficiency_breakdown' => $toChart($this->detailFieldBreakdown($inspections, ['facility_deficiency'])),
            'school_council_activation' => ['labels' => ['Activation'], 'values' => [$schoolCouncilGauge]],
            'issue_category_breakdown' => $toChart(collect([
                'Cleanliness' => $submissions->sum(fn ($i) => (float) data_get($i->metric_snapshot, 'issues_cleanliness', 0)),
                'Teacher Absence' => $submissions->sum(fn ($i) => (float) data_get($i->metric_snapshot, 'issues_teacher_absence', 0)),
                'TLM Shortage' => $submissions->sum(fn ($i) => (float) data_get($i->metric_snapshot, 'issues_tlm_shortage', 0)),
                'Facility Deficiency' => $submissions->sum(fn ($i) => (float) data_get($i->metric_snapshot, 'issues_facility_deficiency', 0)),
            ])->filter(fn ($v) => $v > 0)),
            'health_issue_breakdown' => $toChart($healthIssues->isNotEmpty() ? $healthIssues : collect([
                'Cleanliness' => $submissions->sum(fn ($i) => (float) data_get($i->metric_snapshot, 'issues_cleanliness', 0)),
                'Staff Absence' => $submissions->sum(fn ($i) => (float) data_get($i->metric_snapshot, 'issues_staff_absence', 0)),
                'Medicine Shortage' => $submissions->sum(fn ($i) => (float) data_get($i->metric_snapshot, 'issues_medicine_shortage', 0)),
                'Equipment / Utilities' => $submissions->sum(fn ($i) => (float) data_get($i->metric_snapshot, 'issues_equipment_utilities', 0)),
            ])->filter(fn ($v) => $v > 0)),
            'shops_handcarts_comparison' => $toChart(collect([
                'Shops' => $inspections->avg(fn ($i) => (float) data_get($i->detail_data, 'shops_checked', 0)) ?: 0,
                'Handcarts' => $inspections->avg(fn ($i) => (float) data_get($i->detail_data, 'handcarts_checked', 0)) ?: 0,
            ])),
            'pending_reviewed_comparison' => $toChart(collect([
                'Reviewed' => $inspections->sum(fn ($i) => (float) data_get($i->detail_data, 'applications_reviewed', 0)),
                'Pending' => $inspections->sum(fn ($i) => (float) data_get($i->detail_data, 'pending_cases', 0)),
            ])),
            'facility_check_comparison' => $toChart($this->detailFieldBreakdown($inspections, ['fare_display', 'drinking_water', 'electricity'])),
            'stagnant_water_points' => $toChart($this->detailFieldBreakdown($inspections, ['stagnant_water'])),
            'overdue_complaints' => $toChart($this->detailFieldBreakdown($inspections, ['overdue_status'])),
            'overdue_complaints_age' => $toChart($this->detailFieldBreakdown($inspections, ['overdue_status', 'resolution_days'])),
            'district_complaint_load' => $toChart($districtComparison->isNotEmpty() ? $districtComparison : $legacy['areas']),
            'dc_initiative_impact' => $toChart($this->detailFieldBreakdown($inspections, ['dc_initiative'])),
        ];
    }

    /** @param  list<string>  $fields */
    private function detailFieldBreakdown(Collection $inspections, array $fields): Collection
    {
        $counts = collect();

        foreach ($inspections as $inspection) {
            foreach ($fields as $field) {
                $value = data_get($inspection->detail_data, $field);
                if ($value !== null && $value !== '') {
                    $label = is_numeric($value) ? $field.' '.$value : (string) $value;
                    $counts[$label] = ($counts[$label] ?? 0) + 1;
                    break;
                }
            }
        }

        return $counts->isEmpty() ? collect(['No data' => 1]) : $counts;
    }

    private function comparisonLabel(User $user, ?string $key = null): string
    {
        if ($key === 'district_comparison') {
            return 'District comparison';
        }

        if ($key === 'division_comparison') {
            return 'Division comparison';
        }

        return match ($user->role?->slug) {
            'ac', 'field_user' => 'Tehsil performance trend',
            'dc' => 'Tehsil comparison',
            'commissioner' => 'District comparison',
            default => 'Division / district comparison',
        };
    }

    private function healthIssueBreakdownFromInspections(Collection $inspections): Collection
    {
        $counts = [
            'Cleanliness' => 0,
            'Staff Absence' => 0,
            'Medicine Shortage' => 0,
            'Equipment / Utilities' => 0,
        ];

        foreach ($inspections as $inspection) {
            $detail = is_array($inspection->detail_data)
                ? $inspection->detail_data
                : (json_decode($inspection->detail_data ?? '[]', true) ?: []);

            if ($this->isNegativeSignal($detail['cleanliness'] ?? null)) {
                $counts['Cleanliness']++;
            }
            if (($detail['staff_present'] ?? 'Yes') === 'No') {
                $counts['Staff Absence']++;
            }
            if (($detail['medicines_ok'] ?? 'Yes') === 'No') {
                $counts['Medicine Shortage']++;
            }
            if (($detail['equipment_status'] ?? '') === 'Non-Operational'
                || ($detail['equipment_ok'] ?? 'Yes') === 'No'
                || ($detail['utilities_ok'] ?? 'Yes') === 'No') {
                $counts['Equipment / Utilities']++;
            }
        }

        return collect($counts)->filter(fn ($v) => $v > 0);
    }

    private function isNegativeSignal(mixed $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        $text = strtolower((string) $value);

        return str_contains($text, 'poor') || str_contains($text, 'needs') || in_array($text, ['average', 'no'], true);
    }
}
