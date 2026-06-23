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
        $datasets = $this->buildDatasets($submissions, $inspections, $user, $target, $achieved, $areaScores, $legacy);

        $configured = collect($definitions)->map(function (array $definition) use ($datasets, $user): array {
            $key = $definition['key'];
            $comparisonKeys = ['tehsil_comparison', 'district_comparison', 'division_comparison'];

            if (in_array($key, $comparisonKeys, true)) {
                $definition['comparison_label'] = $this->comparisonLabel($user, $key);
            }

            return array_merge($definition, [
                'data' => $datasets[$key] ?? ['labels' => [], 'values' => []],
            ]);
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
            ->map(fn ($group) => round($group->avg(fn ($item) => (float) ($item->achievement_percentage ?? $item->kpiScore?->percentage ?? $item->score)), 1))
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
            : round(($inspections->where('status', KpiInspection::STATUS_APPROVED)->count() / max(1, $inspections->count())) * 100, 1);

        $fineTotal = $inspections->sum(fn (KpiInspection $item) => (float) data_get($item->detail_data, 'fine', 0));
        $fineRatio = $inspections->isEmpty() ? 0 : round(min(100, ($fineTotal / max(1, $inspections->count())) / 100), 1);

        $toChart = static fn (Collection $data): array => [
            'labels' => $data->keys()->values()->all(),
            'values' => $data->values()->map(fn ($v) => is_numeric($v) ? (float) $v : 0)->values()->all(),
        ];

        return [
            'plant_inspections_trend' => $toChart($inspectionTrend),
            'inspection_trend' => $toChart($inspectionTrend),
            'functional_status_breakdown' => $toChart($this->detailFieldBreakdown($inspections, ['functional_status'])),
            'plant_status_breakdown' => $toChart($this->detailFieldBreakdown($inspections, ['functional_status'])),
            'filter_change_compliance' => ['labels' => ['Compliance'], 'values' => [$gaugeValue]],
            'clean_unclean_breakdown' => $toChart($this->detailFieldBreakdown($inspections, ['cleanliness_status', 'cleanliness'])),
            'clean_vs_unclean' => $toChart($this->detailFieldBreakdown($inspections, ['cleanliness_status', 'cleanliness'])),
            'daily_inspections_trend' => $toChart($inspectionTrend),
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
            'target_achieved' => $toChart($legacy['target_achieved']),
            'performance_trend' => $toChart($legacy['trend']),
            'dc_ac_inspection_comparison' => $toChart($this->detailFieldBreakdown($inspections, ['dc_inspected', 'ac_inspected'])),
            'dc_ac_visit_completion' => $toChart(collect([
                'DC Visits' => $submissions->sum(fn ($i) => (float) data_get($i->metric_snapshot, 'dc_visits', data_get($i->metric_snapshot, 'dc_visit_completion', 0))),
                'AC Visits' => $submissions->sum(fn ($i) => (float) data_get($i->metric_snapshot, 'ac_visits', data_get($i->metric_snapshot, 'ac_visit_completion', 0))),
            ])),
            'health_issue_breakdown' => $toChart(collect([
                'Cleanliness' => $submissions->sum(fn ($i) => (float) data_get($i->metric_snapshot, 'issues_cleanliness', 0)),
                'Staff Absence' => $submissions->sum(fn ($i) => (float) data_get($i->metric_snapshot, 'issues_staff_absence', 0)),
                'Medicine Shortage' => $submissions->sum(fn ($i) => (float) data_get($i->metric_snapshot, 'issues_medicine_shortage', 0)),
                'Equipment / Utilities' => $submissions->sum(fn ($i) => (float) data_get($i->metric_snapshot, 'issues_equipment_utilities', 0)),
            ])),
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
}
