<?php

namespace App\Services;

use App\Data\EducationObservationLabels;
use App\Data\HealthObservationLabels;
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
        array $healthContext = [],
        string $periodType = 'weekly',
    ): array {
        $legacy = $this->build($submissions, $user, $target, $achieved, $areaScores);
        $datasets = $this->buildDatasets($slug, $submissions, $inspections, $user, $target, $achieved, $areaScores, $legacy, $healthContext, $periodType);

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
        })->filter(function (array $definition) use ($slug): bool {
            if (in_array($slug, ['inspection-of-health-facilities', 'inspection-of-educational-institutions'], true)) {
                return true;
            }

            if (! in_array($definition['type'] ?? '', ['donut', 'pie'], true)) {
                return true;
            }

            return collect($definition['data']['values'] ?? [])->filter(fn ($value) => (float) $value > 0)->count() > 1;
        })->take(in_array($slug, ['inspection-of-health-facilities', 'inspection-of-educational-institutions'], true) ? PHP_INT_MAX : 3)->values()->all();

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
        array $healthContext = [],
        string $periodType = 'weekly',
    ): array {
        $pct = $this->formula->achievementPercentage($achieved, $target);

        $trendFormat = match ($periodType) {
            'daily' => 'H:00',
            'monthly' => '\\Wk W',
            'yearly' => 'M Y',
            default => 'D d M',
        };
        $inspectionTrend = $inspections
            ->sortBy('inspection_datetime')
            ->groupBy(fn (KpiInspection $item) => $item->inspection_datetime->format($trendFormat))
            ->map(fn ($group) => $group->count())
            ->take(14);

        if ($inspectionTrend->isEmpty()) {
            $inspectionTrend = collect([now()->format('d M') => max(1, $inspections->count())]);
        }

        $inspectionStatus = $inspections->countBy(fn (KpiInspection $item) => $item->statusLabel());
        if ($inspectionStatus->isEmpty()) {
            $inspectionStatus = collect(['Pending Review' => 1]);
        }

        $healthReviewTargetStatus = ['labels' => [], 'values' => []];
        $healthTehsilProgress = ['labels' => [], 'values' => []];
        $healthDistrictProgress = ['labels' => [], 'values' => []];
        $healthInspectionTargetAchievement = ['labels' => [], 'values' => []];
        $educationReviewTargetStatus = ['labels' => [], 'values' => []];
        $educationTehsilProgress = ['labels' => [], 'values' => []];
        $educationDistrictProgress = ['labels' => [], 'values' => []];
        $educationInspectionTargetAchievement = ['labels' => [], 'values' => []];
        $educationStudentAttendanceSummary = ['labels' => [], 'values' => []];

        $violationBreakdown = $slug === 'price-of-roti'
            ? collect([
                'Overpricing' => $this->observationValueCount($inspections, 'Over Price'),
                'Underweight' => $this->observationValueCount($inspections, 'Under Weight'),
                'Roti Unavailable' => $this->observationValueCount($inspections, 'Non-Availability'),
                'Price List Not Displayed' => $inspections->filter(fn ($item) => data_get($item->detail_data, 'price_list_displayed') === 'No')->count(),
            ])->filter(fn ($count) => $count > 0)
            : $this->detailFieldBreakdown($inspections, ['violation', 'violation_type', 'complaint_status', 'cleanliness_status', 'functional_status'])
                ->reject(fn ($count, $label) => strcasecmp((string) $label, 'Compliant') === 0);
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
        $finesCollected = $inspections->sum(function (KpiInspection $item): float {
            $detail = is_array($item->detail_data) ? $item->detail_data : [];
            $status = strtolower((string) ($detail['payment_status'] ?? ''));

            return in_array($status, ['paid', 'deposited'], true)
                ? (float) ($detail['fine'] ?? $detail['fine_amount'] ?? 0)
                : 0.0;
        });
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

        $healthObservations = $this->healthObservationAvailabilityFromInspections(
            $inspections,
            $slug === 'inspection-of-health-facilities' ? (int) max(0, round($achieved)) : $inspections->count(),
        );
        $educationObservations = $this->educationObservationAvailabilityFromInspections(
            $inspections,
            $slug === 'inspection-of-educational-institutions' ? (int) max(0, round($achieved)) : $inspections->count(),
        );
        $healthIssues = $this->healthIssueBreakdownFromInspections($inspections);

        $dcAcVisitCompletion = collect([
            'DC Visits' => $submissions->sum(fn ($i) => (float) data_get($i->metric_snapshot, 'dc_visits', data_get($i->metric_snapshot, 'dc_visit_completion', 0))),
            'AC Visits' => $submissions->sum(fn ($i) => (float) data_get($i->metric_snapshot, 'ac_visits', data_get($i->metric_snapshot, 'ac_visit_completion', 0))),
        ]);

        if ($slug === 'inspection-of-health-facilities') {
            $inspectionService = app(KpiInspectionService::class);
            $request = request();

            $tehsilProgress = $inspectionService->healthTehsilComparison($user, $request, $inspections);
            $districtProgress = $inspectionService->healthDistrictComparison($user, $request, $inspections);
            $healthTehsilProgress = $toChart($tehsilProgress);
            $healthDistrictProgress = $toChart($districtProgress);

            $achievementTarget = (int) ($healthContext['inspection_achievement_target'] ?? 0);
            $achievementCompleted = (int) ($healthContext['inspection_achievement_completed'] ?? 0);
            $achievementRemaining = (int) ($healthContext['inspection_achievement_remaining'] ?? max(0, $achievementTarget - $achievementCompleted));
            $healthInspectionTargetAchievement = $toChart(collect([
                'Target' => $achievementTarget,
                'Inspected' => $achievementCompleted,
                'Remaining' => $achievementRemaining,
            ]));

            $reviewTarget = (int) ($healthContext['review_target'] ?? 0);
            $healthReviewTargetStatus = $inspectionService->healthReviewTargetStatusChart(
                $reviewTarget,
                (int) ($healthContext['approved'] ?? 0),
                (int) ($healthContext['pending'] ?? 0),
                (int) ($healthContext['rejected'] ?? 0),
            );

            $tehsilComparison = $tehsilProgress;
            $districtComparison = $districtProgress;
        }

        if ($slug === 'inspection-of-educational-institutions') {
            $inspectionService = app(KpiInspectionService::class);
            $request = request();

            $tehsilProgress = $inspectionService->educationTehsilComparison($user, $request, $inspections);
            $districtProgress = $inspectionService->educationDistrictComparison($user, $request, $inspections);
            $educationTehsilProgress = $toChart($tehsilProgress);
            $educationDistrictProgress = $toChart($districtProgress);

            $achievementTarget = (int) ($healthContext['inspection_achievement_target'] ?? 0);
            $achievementCompleted = (int) ($healthContext['inspection_achievement_completed'] ?? 0);
            $achievementRemaining = (int) ($healthContext['inspection_achievement_remaining'] ?? max(0, $achievementTarget - $achievementCompleted));
            $educationInspectionTargetAchievement = $toChart(collect([
                'Target' => $achievementTarget,
                'Inspected' => $achievementCompleted,
                'Remaining' => $achievementRemaining,
            ]));

            $reviewTarget = (int) ($healthContext['review_target'] ?? 0);
            $educationReviewTargetStatus = $inspectionService->healthReviewTargetStatusChart(
                $reviewTarget,
                (int) ($healthContext['approved'] ?? 0),
                (int) ($healthContext['pending'] ?? 0),
                (int) ($healthContext['rejected'] ?? 0),
            );

            $educationStudentAttendanceSummary = $this->educationStudentAttendanceSummaryFromInspections(
                $inspections,
                (int) max(0, round($achieved)),
            );

            $tehsilComparison = $tehsilProgress;
            $districtComparison = $districtProgress;
        }

        return [
            'plant_inspections_trend' => $toChart($inspectionTrend),
            'inspection_trend' => $toChart($inspectionTrend),
            'functional_status_breakdown' => $toChart($this->detailFieldBreakdown($inspections, ['functional_status'])),
            'plant_status_breakdown' => $toChart($this->normalizedPlantStatusBreakdown($inspections)),
            'filter_change_compliance' => ['labels' => ['Compliance'], 'values' => [$this->roFilterComplianceGauge($inspections, $gaugeValue)]],
            'clean_unclean_breakdown' => $toChart($this->normalizedCleanlinessBreakdown($inspections)),
            'clean_vs_unclean' => $toChart($this->normalizedCleanlinessBreakdown($inspections)),
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
            'fine_recovery_complaint_resolution' => $toChart($this->fineRecoveryComplaintBreakdown($inspections)),
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
            'health_observation_availability' => $healthObservations,
            'health_review_target_status' => $healthReviewTargetStatus,
            'health_tehsil_inspection_progress' => $healthTehsilProgress,
            'health_district_inspection_progress' => $healthDistrictProgress,
            'health_inspection_target_achievement' => $healthInspectionTargetAchievement,
            'education_observation_availability' => $educationObservations,
            'education_review_target_status' => $educationReviewTargetStatus,
            'education_tehsil_inspection_progress' => $educationTehsilProgress,
            'education_district_inspection_progress' => $educationDistrictProgress,
            'education_inspection_target_achievement' => $educationInspectionTargetAchievement,
            'education_student_attendance_summary' => $educationStudentAttendanceSummary,
            'health_issue_breakdown' => $toChart($healthIssues->isNotEmpty() ? $healthIssues : collect([
                'Deep Cleaning' => $submissions->sum(fn ($i) => (float) data_get($i->metric_snapshot, 'observation_deep_cleaning_not', 0)),
                'Staff Availability' => $submissions->sum(fn ($i) => (float) data_get($i->metric_snapshot, 'observation_staff_not', 0)),
                'Medicine Availability' => $submissions->sum(fn ($i) => (float) data_get($i->metric_snapshot, 'observation_medicine_not', 0)),
                'Testing Equipment' => $submissions->sum(fn ($i) => (float) data_get($i->metric_snapshot, 'observation_equipment_not', 0)),
                'Drinking Water' => $submissions->sum(fn ($i) => (float) data_get($i->metric_snapshot, 'observation_water_not', 0)),
                'Utilities' => $submissions->sum(fn ($i) => (float) data_get($i->metric_snapshot, 'observation_utilities_not', 0)),
                'UHI Compliance' => $submissions->sum(fn ($i) => (float) data_get($i->metric_snapshot, 'observation_uhi_no', 0)),
                'Attention Required' => $submissions->sum(fn ($i) => (float) data_get($i->metric_snapshot, 'observation_attention_required', 0)),
            ])->filter(fn ($v) => $v > 0)),
            'health_council_meeting_completion' => $toChart(collect(['Held' => 0, 'Target' => 0])),
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
            'commodity_violation_breakdown' => $toChart($this->detailFieldBreakdown($inspections, ['commodity', 'violation', 'violation_type'])),
            'fine_recovery' => $toChart(collect(['Fines Imposed' => $fineTotal, 'Fines Collected' => $finesCollected])->filter(fn ($v) => $v > 0)),
            'road_repairs_trend' => $toChart($inspectionTrend),
            'repair_type_breakdown' => $toChart($this->detailFieldBreakdown($inspections, ['repair_type', 'work_type'])),
            'completion_rate' => ['labels' => ['Completion'], 'values' => [$gaugeValue]],
            'weekly_target_vs_completed' => $toChart(collect(['Target' => round($target, 0), 'Completed' => round($achieved, 0)])),
            'school_inspections_trend' => $toChart($inspectionTrend),
            'crossing_status_breakdown' => $toChart($this->normalizedCrossingStatusBreakdown($inspections)),
            'marking_compliance' => ['labels' => ['Compliance'], 'values' => [$gaugeValue]],
            'inspection_coverage_vs_target' => $toChart(collect(['Target %' => 25, 'Coverage %' => $gaugeValue])),
            'repairs_trend' => $toChart($inspectionTrend),
            'light_status_breakdown' => $toChart(collect([
                'Functional' => $inspections->sum(fn ($i) => (int) data_get($i->detail_data, 'functional_lights', 0)),
                'Faulty' => $inspections->sum(fn ($i) => (int) data_get($i->detail_data, 'dysfunctional_lights', 0)),
                'Repaired' => $inspections->sum(fn ($i) => (int) data_get($i->detail_data, 'repaired_lights', 0)),
                'Pending Repair' => max(0, $inspections->sum(fn ($i) => (int) data_get($i->detail_data, 'dysfunctional_lights', 0)) - $inspections->sum(fn ($i) => (int) data_get($i->detail_data, 'repaired_lights', 0))),
            ])->filter(fn ($v) => $v > 0)),
            'functional_rate' => ['labels' => ['Functional'], 'values' => [$gaugeValue]],
            'streetlight_repair_rate' => ['labels' => ['Repair Rate'], 'values' => [$this->formula->percentage(
                $inspections->sum(fn ($i) => (int) data_get($i->detail_data, 'repaired_lights', 0)),
                $inspections->sum(fn ($i) => (int) data_get($i->detail_data, 'dysfunctional_lights', 0)),
            )]],
            'crossing_action_completion' => ['labels' => ['Completion'], 'values' => [$this->formula->percentage(
                $inspections->filter(fn ($i) => $i->status === KpiInspection::STATUS_APPROVED && in_array(strtolower((string) data_get($i->detail_data, 'action_taken', '')), ['repainted', 'restored', 'marking restored', 'crossing repainted'], true))->count(),
                $inspections->filter(fn ($i) => in_array(data_get($i->detail_data, 'crossing_status'), ['Faded', 'Missing', 'Absent'], true))->count(),
            )]],
            'length_repaired_trend' => $toChart($inspections->sortBy('inspection_datetime')->groupBy(fn ($i) => $i->inspection_datetime->format($trendFormat))->map(fn ($group) => $group->sum(fn ($i) => (int) data_get($i->detail_data, 'length_covered_m', 0)))),
            'faulty_vs_repaired_lights' => $toChart(collect([
                'Faulty' => $inspections->sum(fn ($i) => (int) data_get($i->detail_data, 'dysfunctional_lights', 0)),
                'Repaired' => $inspections->sum(fn ($i) => (int) data_get($i->detail_data, 'repaired_lights', 0)),
            ])->filter(fn ($v) => $v > 0)),
            'manhole_coverage_trend' => $toChart($inspectionTrend),
            'manhole_status_breakdown' => $toChart(collect([
                'Open' => $inspections->sum(fn ($i) => (int) data_get($i->detail_data, 'open_manholes', 0)),
                'Covered' => $inspections->sum(fn ($i) => (int) data_get($i->detail_data, 'covered_manholes', 0)),
            ])->filter(fn ($v) => $v > 0)),
            'open_vs_covered_manholes' => $toChart(collect([
                'Open' => $inspections->sum(fn ($i) => (int) data_get($i->detail_data, 'open_manholes', 0)),
                'Covered' => $inspections->sum(fn ($i) => (int) data_get($i->detail_data, 'covered_manholes', 0)),
            ])->filter(fn ($v) => $v > 0)),
            'safety_compliance' => ['labels' => ['Compliance'], 'values' => [$gaugeValue]],
            'clean_vs_poor' => $toChart($this->detailFieldBreakdown($inspections, ['cleanliness_status', 'cleanliness'])),
            'ro_filter_compliance' => ['labels' => ['RO Filter'], 'values' => [$gaugeValue]],
            'hall_inspections_trend' => $toChart($inspectionTrend),
            'violation_trend' => $toChart($inspectionTrend),
            'enforcement_actions' => $toChart($this->detailFieldBreakdown($inspections, ['action_type', 'enforcement_action'])),
            'encroachment_clearance_trend' => $toChart($inspectionTrend),
            'encroachment_status_breakdown' => $toChart(collect([
                'Cleared' => $inspections->sum(fn ($i) => (int) data_get($i->detail_data, 'cleared_points', 0)),
                'Pending' => max(0, $inspections->sum(fn ($i) => (int) data_get($i->detail_data, 'encroachment_points', 0)) - $inspections->sum(fn ($i) => (int) data_get($i->detail_data, 'cleared_points', 0))),
            ])->filter(fn ($v) => $v > 0)),
            'encroachment_type_breakdown' => $toChart($this->detailFieldBreakdown($inspections, ['encroachment_type'])),
            'clearance_rate' => ['labels' => ['Clearance'], 'values' => [$gaugeValue]],
            'market_comparison' => $toChart($inspections->groupBy(fn ($i) => $i->entity_name ?: 'Market')->map->count()->sortDesc()->take(8)),
            'uc_activity_trend' => $toChart($inspectionTrend),
            'daily_uc_activity' => $toChart($inspectionTrend),
            'activity_type_breakdown' => $toChart($this->detailFieldBreakdown($inspections, ['activity_conducted', 'activity_type'])),
            'team_performance' => $toChart($this->detailFieldBreakdown($inspections, ['team_name'])),
            'dogs_observed_vs_culled' => $toChart(collect([
                'Observed' => $inspections->sum(fn ($i) => (int) data_get($i->detail_data, 'dogs_observed', 0)),
                'Culled' => $inspections->sum(fn ($i) => (int) data_get($i->detail_data, 'dogs_culled', 0)),
            ])->filter(fn ($v) => $v > 0)),
            'removal_activity_trend' => $toChart($inspectionTrend),
            'spot_status_breakdown' => $toChart(collect([
                'Identified' => $inspections->sum(fn ($i) => (int) data_get($i->detail_data, 'spots_identified', 0)),
                'Removed' => $inspections->sum(fn ($i) => (int) data_get($i->detail_data, 'spots_cleared', 0)),
            ])->filter(fn ($v) => $v > 0)),
            'spots_identified_vs_removed' => $toChart(collect([
                'Identified' => $inspections->sum(fn ($i) => (int) data_get($i->detail_data, 'spots_identified', 0)),
                'Removed' => $inspections->sum(fn ($i) => (int) data_get($i->detail_data, 'spots_cleared', 0)),
            ])->filter(fn ($v) => $v > 0)),
            'graveyard_maintenance_trend' => $toChart($inspectionTrend),
            'issue_type_breakdown' => $toChart($this->detailFieldBreakdown($inspections, ['violation', 'issue_type', 'maintenance_status'])),
            'weekly_target_vs_cleared' => $toChart(collect(['Target' => round($target, 0), 'Cleared' => round($achieved, 0)])),
            'pending_vs_processed_applications' => $toChart(collect([
                'Pending' => $inspections->sum(fn ($i) => (int) data_get($i->detail_data, 'pending_cases', 0)),
                'Processed' => $inspections->sum(fn ($i) => (int) data_get($i->detail_data, 'applications_reviewed', 0)),
            ])->filter(fn ($v) => $v > 0)),
            'help_desk_inspection_rate' => ['labels' => ['Rate'], 'values' => [$gaugeValue]],
            'weekly_target_vs_inspections' => $toChart(collect(['Target' => round($target, 0), 'Inspected' => round($achieved, 0)])),
            'weekly_target_vs_uc_inspections' => $toChart(collect(['Target' => round($target, 0), 'UCs Inspected' => round($achieved, 0)])),
            'compliance_criteria_breakdown' => $toChart($this->detailFieldBreakdown($inspections, ['cleanliness_status', 'hr_attendance_ok', 'machinery_in_field'])),
            'park_status' => $toChart($this->detailFieldBreakdown($inspections, ['maintenance_status', 'type'])),
            'greenbelt_maintenance' => $toChart($this->detailFieldBreakdown($inspections, ['type', 'maintenance_status'])),
            'kerb_painting_progress' => $toChart($this->detailFieldBreakdown($inspections, ['kerb_stone_paint', 'completion_status'])),
            'uc_inspection_coverage' => ['labels' => ['Coverage'], 'values' => [$gaugeValue]],
            'issue_breakdown' => $toChart($this->detailFieldBreakdown($inspections, ['issue_type', 'blockage_identified', 'cleaned_status'])),
            'resolved_vs_pending' => $toChart(collect([
                'Resolved' => $inspections->filter(fn ($i) => in_array(data_get($i->detail_data, 'cleaned_status'), ['Cleaned', 'Resolved'], true))->count(),
                'Pending' => $inspections->reject(fn ($i) => in_array(data_get($i->detail_data, 'cleaned_status'), ['Cleaned', 'Resolved'], true))->count(),
            ])->filter(fn ($v) => $v > 0)),
            'terminal_inspection_coverage' => ['labels' => ['Coverage'], 'values' => [$gaugeValue]],
            'daily_market_clearance' => $toChart($inspectionTrend),
            'daily_market_inspection' => $toChart($inspectionTrend),
            'shop_vs_handcart_violations' => $toChart(collect([
                'Shop Violations' => $inspections->sum(fn ($i) => (int) data_get($i->detail_data, 'shops_checked', 0)),
                'Handcart Violations' => $inspections->sum(fn ($i) => (int) data_get($i->detail_data, 'handcarts_checked', 0)),
            ])->filter(fn ($v) => $v > 0)),
            'action_breakdown' => $toChart($this->detailFieldBreakdown($inspections, ['action_type', 'action_taken'])),
            'daily_sale_point_inspections' => $toChart($inspectionTrend),
            'daily_bakery_inspection_trend' => $toChart($inspectionTrend),
            'cumulative_roads_maintained' => $toChart($inspectionTrend->isNotEmpty() ? $inspectionTrend : collect(['Maintained' => max(1, $inspections->count())])),
            'weekly_roads_inspected_vs_target' => $toChart(collect(['Target' => round($target, 0), 'Inspected' => round($achieved, 0)])),
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

    /** @return array{labels: list<string>, datasets: list<array{label: string, values: list<int>, color: string}>, facilities_inspected: int} */
    private function healthObservationAvailabilityFromInspections(Collection $inspections, int $facilitiesInspected): array
    {
        $categories = HealthObservationLabels::chartCategories();

        // Observation availability counts must use ALL completed inspections
        // (approved + pending-review) in the selected period and scope.
        // Do not cap based on operational targets.
        $scoped = $inspections
            ->filter(fn (KpiInspection $inspection): bool => in_array(
                $inspection->status,
                [KpiInspection::STATUS_APPROVED, KpiInspection::STATUS_PENDING],
                true
            ))
            ->values();

        $inspectedTotal = $scoped->count();

        $available = [];
        $notAvailable = [];
        $labelPairs = [];

        foreach ($categories as $label => $field) {
            $meta = HealthObservationLabels::meta($field);
            $available[$label] = 0;
            $notAvailable[$label] = 0;
            $labelPairs[] = [
                'positive' => $meta['positive'],
                'negative' => $meta['negative'],
            ];

            foreach ($scoped as $inspection) {
                $detail = is_array($inspection->detail_data)
                    ? $inspection->detail_data
                    : (json_decode($inspection->detail_data ?? '[]', true) ?: []);

                $value = $detail[$field] ?? $this->legacyHealthObservationChartValue($detail, $field);
                if (HealthObservationLabels::outcome($value) === 'positive') {
                    $available[$label]++;
                } elseif (HealthObservationLabels::outcome($value) === 'negative') {
                    $notAvailable[$label]++;
                }
            }
        }

        $labels = array_keys($categories);

        return [
            'labels' => $labels,
            'datasets' => array_values(array_filter([
                [
                    'label' => 'Positive Status',
                    'values' => array_map(fn (string $label) => $available[$label], $labels),
                    'color' => '#087443',
                ],
                [
                    'label' => 'Negative Status',
                    'values' => array_map(fn (string $label) => $notAvailable[$label], $labels),
                    'color' => '#dc2626',
                ],
            ])),
            'category_label_pairs' => $labelPairs,
            'facilities_inspected' => $inspectedTotal,
        ];
    }

    private function healthIssueBreakdownFromInspections(Collection $inspections): Collection
    {
        $counts = array_fill_keys(array_keys(HealthObservationLabels::chartCategories()), 0);
        $counts['Attention Required'] = 0;

        foreach ($inspections as $inspection) {
            $detail = is_array($inspection->detail_data)
                ? $inspection->detail_data
                : (json_decode($inspection->detail_data ?? '[]', true) ?: []);

            foreach (HealthObservationLabels::chartCategories() as $label => $field) {
                $value = strtolower((string) ($detail[$field] ?? $this->legacyHealthObservationChartValue($detail, $field)));
                if ($value === 'not_available' || $value === 'no') {
                    $counts[$label]++;
                    $counts['Attention Required']++;
                }
            }
        }

        return collect($counts)->filter(fn ($value) => $value > 0);
    }

    /** @param  array<string, mixed>  $detail */
    private function legacyHealthObservationChartValue(array $detail, string $field): string
    {
        return match ($field) {
            'deep_cleaning_available' => $this->isNegativeSignal($detail['cleanliness'] ?? null) ? 'not_available' : 'available',
            'staff_available' => (($detail['staff_present'] ?? 'Yes') === 'No') ? 'not_available' : 'available',
            'medicine_flex_available' => (($detail['medicines_ok'] ?? 'Yes') === 'No') ? 'not_available' : 'available',
            'testing_equipment_available' => in_array($detail['equipment_status'] ?? '', ['Non-Operational', 'Partial'], true)
                ? 'not_available'
                : 'available',
            'drinking_water_available' => ($detail['utilities_ok'] ?? 'Yes') === 'No' ? 'not_available' : 'available',
            'utilities_available' => ($detail['utilities_ok'] ?? 'Yes') === 'No' ? 'not_available' : 'available',
            default => 'available',
        };
    }

    private function healthDcCompletedCount(Collection $inspections, Collection $submissions, int $dcTarget): int
    {
        $fromSubmissions = (int) $submissions->sum(fn ($i) => (float) data_get($i->metric_snapshot, 'dc_visits', 0));
        if ($fromSubmissions > 0) {
            return min($dcTarget, $fromSubmissions);
        }

        $dcInspections = $inspections->filter(function (KpiInspection $item) {
            return in_array($item->inspectedBy?->role?->slug, ['dc', 'commissioner'], true);
        })->count();

        return min($dcTarget, $dcInspections);
    }

    private function isNegativeSignal(mixed $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        $text = strtolower((string) $value);

        return str_contains($text, 'poor') || str_contains($text, 'needs') || in_array($text, ['average', 'no'], true);
    }

    /** @return array{labels: list<string>, datasets: list<array{label: string, values: list<int>, color: string}>, facilities_inspected: int} */
    private function educationObservationAvailabilityFromInspections(Collection $inspections, int $institutionsInspected): array
    {
        $categories = EducationObservationLabels::chartCategories();

        // Observation availability counts must use ALL completed inspections
        // (approved + pending-review) in the selected period and scope.
        // Do not cap based on operational targets.
        $scoped = $inspections
            ->filter(fn (KpiInspection $inspection): bool => in_array(
                $inspection->status,
                [KpiInspection::STATUS_APPROVED, KpiInspection::STATUS_PENDING],
                true
            ))
            ->values();

        $inspectedTotal = $scoped->count();

        $available = [];
        $notAvailable = [];
        $labelPairs = [];

        foreach ($categories as $label => $field) {
            $meta = EducationObservationLabels::meta($field);
            $available[$label] = 0;
            $notAvailable[$label] = 0;
            $labelPairs[] = [
                'positive' => $meta['positive'],
                'negative' => $meta['negative'],
            ];

            foreach ($scoped as $inspection) {
                $detail = is_array($inspection->detail_data)
                    ? $inspection->detail_data
                    : (json_decode($inspection->detail_data ?? '[]', true) ?: []);

                $value = $detail[$field] ?? $this->legacyEducationObservationChartValue($detail, $field);
                if (EducationObservationLabels::outcome($value) === 'positive') {
                    $available[$label]++;
                } elseif (EducationObservationLabels::outcome($value) === 'negative') {
                    $notAvailable[$label]++;
                }
            }
        }

        $labels = array_keys($categories);

        return [
            'labels' => $labels,
            'datasets' => array_values(array_filter([
                [
                    'label' => 'Positive',
                    'values' => array_map(fn (string $label) => $available[$label], $labels),
                    'color' => '#087443',
                ],
                [
                    'label' => 'Negative',
                    'values' => array_map(fn (string $label) => $notAvailable[$label], $labels),
                    'color' => '#dc2626',
                ],
            ])),
            'category_label_pairs' => $labelPairs,
            'category_titles' => collect(EducationObservationLabels::definitions())->pluck('title')->values()->all(),
            'has_valid_data' => array_sum($available) + array_sum($notAvailable) > 0,
            'facilities_inspected' => $inspectedTotal,
        ];
    }

    /** @return \Illuminate\Support\Collection<string, int> */
    private function fineRecoveryComplaintBreakdown(Collection $inspections): Collection
    {
        $finePaid = 0;
        $finePending = 0;
        $complaintResolved = 0;
        $complaintPending = 0;

        foreach ($inspections as $inspection) {
            $detail = is_array($inspection->detail_data)
                ? $inspection->detail_data
                : (json_decode($inspection->detail_data ?? '[]', true) ?: []);

            $payment = strtolower((string) ($detail['payment_status'] ?? ''));
            if ($payment === 'paid') {
                $finePaid++;
            } elseif ($payment !== '') {
                $finePending++;
            }

            $complaint = strtolower((string) ($detail['complaint_action'] ?? ''));
            if ($complaint === 'resolved') {
                $complaintResolved++;
            } elseif ($complaint !== '') {
                $complaintPending++;
            }
        }

        return collect([
            'Fine Paid' => $finePaid,
            'Fine Pending' => $finePending,
            'Complaints Resolved' => $complaintResolved,
            'Complaints Pending' => $complaintPending,
        ])->filter(fn ($value) => $value > 0);
    }

    /** @return \Illuminate\Support\Collection<string, int> */
    private function normalizedPlantStatusBreakdown(Collection $inspections): Collection
    {
        $counts = ['Functional' => 0, 'Non-Functional' => 0];

        foreach ($inspections as $inspection) {
            $detail = is_array($inspection->detail_data)
                ? $inspection->detail_data
                : (json_decode($inspection->detail_data ?? '[]', true) ?: []);
            $status = strtolower((string) ($detail['functional_status'] ?? ''));

            if (in_array($status, ['functional', 'partially functional'], true)) {
                $counts['Functional']++;
            } else {
                $counts['Non-Functional']++;
            }
        }

        return collect($counts)->filter(fn ($value) => $value > 0);
    }

    /** @return \Illuminate\Support\Collection<string, int> */
    private function normalizedCleanlinessBreakdown(Collection $inspections): Collection
    {
        $counts = ['Clean' => 0, 'Unclean' => 0];

        foreach ($inspections as $inspection) {
            $detail = is_array($inspection->detail_data)
                ? $inspection->detail_data
                : (json_decode($inspection->detail_data ?? '[]', true) ?: []);
            $status = strtolower((string) ($detail['cleanliness_status'] ?? $detail['cleanliness'] ?? ''));

            if (in_array($status, ['clean', 'good'], true)) {
                $counts['Clean']++;
            } else {
                $counts['Unclean']++;
            }
        }

        return collect($counts)->filter(fn ($value) => $value > 0);
    }

    /** @return \Illuminate\Support\Collection<string, int> */
    private function normalizedCrossingStatusBreakdown(Collection $inspections): Collection
    {
        $counts = ['Visible' => 0, 'Faded' => 0, 'Missing' => 0];

        foreach ($inspections as $inspection) {
            $detail = is_array($inspection->detail_data)
                ? $inspection->detail_data
                : (json_decode($inspection->detail_data ?? '[]', true) ?: []);
            $status = strtolower((string) ($detail['crossing_status'] ?? $detail['zebra_crossing_status'] ?? ''));

            if (in_array($status, ['marked', 'visible', 'repainted', 'restored'], true)) {
                $counts['Visible']++;
            } elseif (in_array($status, ['faded'], true)) {
                $counts['Faded']++;
            } else {
                $counts['Missing']++;
            }
        }

        return collect($counts)->filter(fn ($value) => $value > 0);
    }

    private function roFilterComplianceGauge(Collection $inspections, float $fallback): float
    {
        if ($inspections->isEmpty()) {
            return $fallback;
        }

        $compliant = $inspections->filter(function ($inspection): bool {
            $detail = is_array($inspection->detail_data)
                ? $inspection->detail_data
                : (json_decode($inspection->detail_data ?? '[]', true) ?: []);

            return in_array(strtolower((string) ($detail['ro_filter_date_affixed'] ?? '')), ['yes', '1', 'true'], true)
                || ($detail['filter_change_status'] ?? '') === 'Up to Date';
        })->count();

        return round(min(100, ($compliant / max(1, $inspections->count())) * 100), 1);
    }

    /** @return array{labels: list<string>, values: list<int>} */
    private function educationStudentAttendanceSummaryFromInspections(Collection $inspections, int $institutionsInspected): array
    {
        $limit = max(0, $institutionsInspected);
        $scoped = $limit > 0 && $inspections->count() > $limit
            ? $inspections
                ->sortByDesc(fn ($inspection) => $inspection->inspection_datetime)
                ->take($limit)
                ->values()
            : $inspections->values();

        $enrolled = 0;
        $present = 0;

        foreach ($scoped as $inspection) {
            $detail = is_array($inspection->detail_data)
                ? $inspection->detail_data
                : (json_decode($inspection->detail_data ?? '[]', true) ?: []);
            $enrolled += (int) ($detail['students_enrolled'] ?? 0);
            $present += (int) ($detail['students_present'] ?? 0);
        }

        return [
            'labels' => ['Students Enrolled', 'Students Present'],
            'values' => [$enrolled, $present],
        ];
    }

    /** @param  array<string, mixed>  $detail */
    private function legacyEducationObservationChartValue(array $detail, string $field): string
    {
        return match ($field) {
            'cleanliness_available' => $this->isNegativeSignal($detail['cleanliness'] ?? null) ? 'not_available' : 'available',
            'teachers_staff_available' => in_array($detail['teachers_present'] ?? 'Yes', ['No', 'Partial'], true) ? 'not_available' : 'available',
            'books_learning_material_available' => in_array($detail['tlm_availability'] ?? '', ['Shortage', 'Partial'], true) ? 'not_available' : 'available',
            'school_facilities_utilities_available' => in_array($detail['facility_deficiency'] ?? '', ['Major', 'Minor'], true) ? 'not_available' : 'available',
            'drinking_water_available' => ($detail['drinking_water_available'] ?? 'available') === 'not_available' ? 'not_available' : 'available',
            'student_enrolment_checked' => ($detail['student_enrolment_checked'] ?? 'yes') === 'no' ? 'no' : 'yes',
            default => 'available',
        };
    }

    private function observationValueCount(Collection $inspections, string $value): int
    {
        return $inspections->filter(function (KpiInspection $inspection) use ($value): bool {
            $detail = is_array($inspection->detail_data) ? $inspection->detail_data : [];
            $types = is_array($detail['violation_types'] ?? null) ? $detail['violation_types'] : [];

            return in_array($value, $types, true) || ($detail['violation'] ?? null) === $value;
        })->count();
    }
}
