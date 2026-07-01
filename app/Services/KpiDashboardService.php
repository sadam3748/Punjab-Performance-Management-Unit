<?php

namespace App\Services;

use App\Data\KpiMetricSections;
use App\Models\KpiCard;
use App\Models\KpiSubmission;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class KpiDashboardService
{
    public function __construct(
        private readonly KpiScopeService $scopeService,
        private readonly KpiPeriodService $periodService,
        private readonly KpiFormulaService $formula,
        private readonly KpiChartService $chartService,
        private readonly KpiInspectionService $inspectionService,
        private readonly KpiDashboardConfigService $dashboardConfig,
        private readonly KpiGeoFilterService $geoFilterService,
        private readonly KpiOperationalService $operationalService,
        private readonly KpiFrequencyService $frequencyService,
    ) {}

    public function assignedCards(User $user, ?Request $request = null): Collection
    {
        $request ??= request();

        return KpiCard::query()
            ->where('is_active', true)
            ->when($user->role?->slug !== 'super_admin', function (Builder $query) use ($user) {
                $query->whereHas('assignments', function (Builder $assignment) use ($user) {
                    $assignment->where('is_active', true)
                        ->where(function (Builder $match) use ($user) {
                            $match->where('user_id', $user->id)
                                ->orWhere(function (Builder $roleMatch) use ($user) {
                                    $roleMatch->where('role_id', $user->role_id)
                                        ->where(fn (Builder $q) => $q->whereNull('division_id')->orWhere('division_id', $user->division_id))
                                        ->where(fn (Builder $q) => $q->whereNull('district_id')->orWhere('district_id', $user->district_id))
                                        ->where(fn (Builder $q) => $q->whereNull('tehsil_id')->orWhere('tehsil_id', $user->tehsil_id));
                                });
                        });
                });
            })
            ->orderBy('display_order')
            ->get()
            ->pipe(function (Collection $cards) use ($user, $request) {
                if ($cards->isEmpty()) {
                    return $cards;
                }

                $submissionsByCard = $this->filteredSubmissions(
                    KpiSubmission::query()->whereIn('kpi_card_id', $cards->pluck('id')),
                    $user,
                    $request
                )
                    ->get([
                        'kpi_card_id', 'user_id', 'area_level', 'submission_date', 'metric_snapshot',
                        'reported_value', 'achieved_value', 'achievement_percentage', 'target_value',
                    ])
                    ->groupBy('kpi_card_id');

                return $cards->map(function (KpiCard $card) use ($submissionsByCard, $user, $request) {
                    $header = $this->resolveOperationalHeader(
                        $card,
                        $submissionsByCard->get($card->id, collect()),
                        $user,
                        $request,
                        periodTotals: true,
                    );

                    $card->target = $header['operational_target'];
                    $card->achieved = $header['completed'];
                    $card->achievement_percentage = $header['achievement_percentage'];
                    $card->status_label = $header['status_label'];

                    return $card;
                });
            });
    }

    public function canAccess(User $user, KpiCard $card): bool
    {
        return $user->role?->slug === 'super_admin' || $this->assignedCards($user)->contains('id', $card->id);
    }

    public function detail(KpiCard $card, User $user, Request $request): array
    {
        $request = $this->requestWithKpiPeriodDefaults($card, $request);

        [$submissions, $usedFallback] = $this->loadSubmissions($card, $user, $request);

        $perPage = min(50, max(10, (int) $request->input('per_page', 15)));

        $tableSubmissions = $this->paginateSubmissions($card, $user, $request, $perPage, $usedFallback);

        $headerMetrics = $this->resolveOperationalHeader($card, $submissions, $user, $request, periodTotals: true);
        $headerLabels = $this->dashboardConfig->headerLabelsFor($card->slug);
        $areaScores = $this->areaScores($submissions, $user);

        $kpiConfig = $this->dashboardConfig->forKpi($card->slug);
        $chartDefinitions = $this->chartsForUser($kpiConfig['charts'], $user, $card->slug);
        $inspectionCollection = $this->inspectionService->getInspectionsCollection($card, $user, $request);
        $inspectionStatusCounts = $this->inspectionService->buildStatusCounts($card, $user, $request);
        $inspectionTableColumns = $this->inspectionService->getTableColumnsForKpi($card->slug);

        return [
            'kpiConfig' => $kpiConfig,
            'chartDefinitions' => $chartDefinitions,
            'inspectionTableColumns' => $inspectionTableColumns,
            'geoFilters' => $this->geoFilterService->options($user),
            'submissions' => $submissions,
            'tableSubmissions' => $tableSubmissions,
            'data_fallback' => $usedFallback,
            'summary' => [
                'total' => $submissions->count(),
                'approved' => $submissions->where('status', 'approved')->count(),
                'submitted' => $submissions->where('status', 'submitted')->count(),
                'reported' => $headerMetrics['records'],
                'pending' => $headerMetrics['pending'],
                'rejected' => $submissions->where('status', 'rejected')->count(),
                'target' => $headerMetrics['operational_target'],
                'achieved' => $headerMetrics['completed'],
                'achievement_percentage' => $headerMetrics['achievement_percentage'],
                'score' => $headerMetrics['score'],
                'status_label' => $headerMetrics['status_label'],
                'best_area' => $areaScores->sortDesc()->keys()->first() ?: '—',
                'weak_area' => $areaScores->sort()->keys()->first() ?: '—',
            ],
            'header' => array_merge($headerMetrics, [
                'labels' => $headerLabels,
                'period_label' => $this->periodService->label($request),
                'area_level' => $this->scopeService->areaLevel($user),
                'scope_label' => $this->scopeService->locationLabel($user),
            ]),
            'metrics' => $this->metrics(
                $card,
                $submissions,
                $user,
                $request,
                $headerMetrics['operational_target'],
                $headerMetrics['completed'],
                $headerMetrics['achievement_percentage'],
                $inspectionStatusCounts
            ),
            'metricSections' => $this->metricSections(
                $card,
                $submissions,
                $user,
                $request,
                $headerMetrics['achievement_percentage'],
                $inspectionStatusCounts,
                (float) $headerMetrics['operational_target'],
                (float) $headerMetrics['completed'],
            ),
            'charts' => $this->chartService->buildForKpi(
                $card->slug,
                $submissions,
                $inspectionCollection,
                $user,
                $headerMetrics['operational_target'],
                $headerMetrics['completed'],
                $areaScores,
                $chartDefinitions,
            ),
            'filters' => $this->filterOptionsForView($card->slug),
            'geo' => $this->geoFilterService->state($request),
            'period' => $this->periodState($request),
            'period_description' => $this->periodService->description($request),
            'inspectionRecords' => $this->inspectionService->getInspectionListForKpi($card, $user, $request),
            'inspectionStatusCounts' => $inspectionStatusCounts,
            'inspectionFilters' => $this->inspectionService->filterOptions($user),
            'canReviewInspections' => $this->inspectionService->canReviewInspections($user),
        ];
    }

    public function scope(Builder $query, User $user): Builder
    {
        return $this->scopeService->apply($query, $user);
    }

    public function applyPeriodFilters(Builder $query, Request $request): Builder
    {
        return $this->periodService->applyToQuery($query, $request);
    }

    public function filterOptionsForView(?string $kpiSlug = null): array
    {
        $year = (int) (request('year') ?: now()->year);
        $month = (int) (request('month') ?: now()->month);
        $options = $this->periodService->filterOptions($year, $month);

        if ($kpiSlug) {
            $options['period_types'] = $this->frequencyService->periodTypesFor($kpiSlug);
            $options['defaults'] = $this->frequencyService->defaultParamsFor($kpiSlug);
        }

        return $options;
    }

    public function periodState(Request $request): array
    {
        return $this->periodService->state($request);
    }

    public function periodDescription(Request $request): string
    {
        return $this->periodService->description($request);
    }

    public function periodQueryString(Request $request): string
    {
        return $this->periodService->queryString($request);
    }

    private function filteredSubmissions(Builder $query, User $user, Request $request): Builder
    {
        $query = $this->periodService->applyToQuery($this->scopeService->apply($query, $user), $request);

        return $this->geoFilterService->apply($query, $request, $user);
    }

    /** @return array{0: Collection<int, KpiSubmission>, 1: bool} */
    private function loadSubmissions(KpiCard $card, User $user, Request $request): array
    {
        $query = $this->filteredSubmissions(
            KpiSubmission::query()->where('kpi_card_id', $card->id),
            $user,
            $request
        );

        $submissions = (clone $query)
            ->with(['user:id,name', 'division:id,name', 'district:id,name', 'tehsil:id,name', 'values.field', 'kpiScore'])
            ->latest('submission_date')
            ->get();

        if ($submissions->isNotEmpty()) {
            return [$submissions, false];
        }

        $fallback = $this->scopedSubmissions($card, $user, $request)
            ->whereYear('submission_date', (int) ($request->input('year') ?: now()->year))
            ->whereMonth('submission_date', (int) ($request->input('month') ?: now()->month))
            ->with(['user:id,name', 'division:id,name', 'district:id,name', 'tehsil:id,name', 'values.field', 'kpiScore'])
            ->latest('submission_date')
            ->limit(120)
            ->get();

        if ($fallback->isNotEmpty()) {
            return [$fallback, true];
        }

        $recent = $this->scopedSubmissions($card, $user, $request)
            ->with(['user:id,name', 'division:id,name', 'district:id,name', 'tehsil:id,name', 'values.field', 'kpiScore'])
            ->latest('submission_date')
            ->limit(80)
            ->get();

        return [$recent, $recent->isNotEmpty()];
    }

    private function scopedSubmissions(KpiCard $card, User $user, Request $request): Builder
    {
        return $this->geoFilterService->apply(
            $this->scopeService->apply(
                KpiSubmission::query()->where('kpi_card_id', $card->id),
                $user
            ),
            $request,
            $user
        );
    }

    private function paginateSubmissions(KpiCard $card, User $user, Request $request, int $perPage, bool $usedFallback)
    {
        if ($usedFallback) {
            return $this->scopedSubmissions($card, $user, $request)
                ->with(['user:id,name', 'division:id,name', 'district:id,name', 'tehsil:id,name', 'values.field', 'kpiScore'])
                ->latest('submission_date')
                ->paginate($perPage)
                ->withQueryString();
        }

        return $this->filteredSubmissions(
            KpiSubmission::query()->where('kpi_card_id', $card->id),
            $user,
            $request
        )
            ->with(['user:id,name', 'division:id,name', 'district:id,name', 'tehsil:id,name', 'values.field', 'kpiScore'])
            ->latest('submission_date')
            ->paginate($perPage)
            ->withQueryString();
    }

    private function resolveAchieved(Collection $submissions, float $target): float
    {
        if ($submissions->isEmpty()) {
            return 0.0;
        }

        $avg = round((float) $submissions->avg(fn ($s) => (float) ($s->achieved_value ?? $s->score)), 1);
        if ($avg > 0) {
            return $avg;
        }

        $pctAvg = round((float) $submissions->avg(fn ($s) => (float) ($s->achievement_percentage ?? 0)), 1);
        if ($pctAvg > 0 && $target > 0) {
            return round($target * $pctAvg / 100, 1);
        }

        return $avg;
    }

    private function resolveAchievementPct(Collection $submissions, float $achieved, float $target): float
    {
        if ($submissions->isEmpty()) {
            return 0.0;
        }

        if ($target <= 0) {
            return 0.0;
        }

        if ($target > 0) {
            return $this->formula->achievementPercentage($achieved, $target);
        }

        $pct = round((float) $submissions->avg(fn ($s) => (float) ($s->achievement_percentage ?? $s->kpiScore?->percentage ?? 0)), 1);

        return $pct > 0 ? $pct : 0.0;
    }

    private function resolveAchievedSum(Collection $submissions): float
    {
        if ($submissions->isEmpty()) {
            return 0.0;
        }

        $sum = round((float) $submissions->sum(fn ($s) => (float) ($s->achieved_value ?? $s->score ?? 0)), 1);
        if ($sum > 0) {
            return $sum;
        }

        $targetSum = round((float) $submissions->sum('target_value'), 1);
        $pctAvg = round((float) $submissions->avg(fn ($s) => (float) ($s->achievement_percentage ?? 0)), 1);
        if ($pctAvg > 0 && $targetSum > 0) {
            return round($targetSum * $pctAvg / 100, 1);
        }

        return $sum;
    }

    /** @return array<string, float|int|string> */
    private function resolveOperationalHeader(
        KpiCard $card,
        Collection $submissions,
        User $user,
        Request $request,
        bool $periodTotals = false,
    ): array
    {
        $fields = $this->dashboardConfig->operationalFieldsFor($card->slug);
        $marks = (float) $card->total_marks;
        $hasCalculatedVisitTarget = in_array($card->slug, [
            'inspection-of-health-facilities',
            'inspection-of-educational-institutions',
        ], true);

        $inspectionAchieved = $hasCalculatedVisitTarget && $periodTotals
            ? $this->inspectionService->countOperationalAchieved($card, $user, $request)
            : null;

        $activeScope = $card->slug === 'inspection-of-health-facilities' && $periodTotals
            ? $this->inspectionService->activeScopeCounts($card, $user, $request)
            : null;

        if ($periodTotals) {
            $operational = $this->operationalService->totals(
                $card,
                $submissions,
                $user,
                $request,
                $fields,
                $inspectionAchieved,
                $activeScope,
            );
            $operationalTarget = $operational['target'];
            $completed = $operational['completed'];
        } else {
            $operationalTarget = $this->snapshotAverage($submissions, $fields['target']);
            $completed = $this->snapshotAverage($submissions, $fields['completed']);
        }

        if ($operationalTarget <= 0 && ! $hasCalculatedVisitTarget) {
            $operationalTarget = $periodTotals
                ? round((float) $submissions->sum('target_value'), 1)
                : round((float) $submissions->avg('target_value'), 1);
        }
        if ($completed <= 0 && ! $hasCalculatedVisitTarget) {
            $completed = $periodTotals
                ? $this->resolveAchievedSum($submissions)
                : $this->resolveAchieved($submissions, max($operationalTarget, $marks));
        }

        $actualCompleted = $completed;
        if ($card->slug === 'inspection-of-health-facilities' && $periodTotals && $operationalTarget > 0) {
            $completed = min($completed, $operationalTarget);
        }

        // Records = submitted KPI reports only (not summed activity-volume fields).
        $records = $submissions->count();
        $inspectionsCount = $this->inspectionService->countScopedInspections($card, $user, $request);

        $pct = $this->resolveAchievementPct($submissions, $completed, $operationalTarget);
        if ($card->slug === 'inspection-of-health-facilities') {
            $pct = min(100.0, $pct);
        }
        $score = $this->formula->scoreFromWeightage($pct, $marks);

        return [
            'operational_target' => $operationalTarget,
            'completed' => $completed,
            'actual_completed' => $actualCompleted,
            'records' => $records,
            'submitted_reports' => $records,
            'inspections_count' => $inspectionsCount,
            'achievement_percentage' => $pct,
            'score' => $score,
            'total_marks' => $marks,
            'status_label' => $this->formula->performanceLabel($pct),
            'target' => $operationalTarget,
            'achieved' => $completed,
            'reported' => $records,
            'pending' => max(0, round($operationalTarget - $completed, 1)),
        ];
    }

    private function snapshotAverage(Collection $submissions, string $field): float
    {
        if ($submissions->isEmpty()) {
            return 0.0;
        }

        if ($field === 'applications_target') {
            return round((float) $submissions->avg(function ($submission) {
                $snapshot = $submission->metric_snapshot ?? [];

                return (float) ($snapshot['pending_applications'] ?? 0)
                    + (float) ($snapshot['applications_completed'] ?? 0);
            }), 1);
        }

        return round((float) $submissions->avg(
            fn ($submission) => (float) data_get($submission->metric_snapshot, $field, 0)
        ), 1);
    }

    private function snapshotSum(Collection $submissions, string $field): float
    {
        if ($submissions->isEmpty()) {
            return 0.0;
        }

        if ($field === 'applications_target') {
            return round((float) $submissions->sum(function ($submission) {
                $snapshot = $submission->metric_snapshot ?? [];

                return (float) ($snapshot['pending_applications'] ?? 0)
                    + (float) ($snapshot['applications_completed'] ?? 0);
            }), 1);
        }

        return round((float) $submissions->sum(
            fn ($submission) => (float) data_get($submission->metric_snapshot, $field, 0)
        ), 1);
    }

    /** @param  array<string, int>  $inspectionStatusCounts */
    private function metrics(
        KpiCard $card,
        Collection $submissions,
        User $user,
        Request $request,
        float $target,
        float $achieved,
        float $pct,
        array $inspectionStatusCounts,
    ): Collection {
        $configured = collect($this->dashboardConfig->dashboardStatsFor($card->slug));
        $allValues = $submissions->flatMap->values;
        $visitContext = $this->visitMetricContext($card, $user, $request, $submissions, $inspectionStatusCounts);

        return $configured->map(function (array $metric) use ($allValues, $submissions, $inspectionStatusCounts, $pct, $visitContext, $user) {
            $value = $this->resolveMetricValue(
                $metric['field'],
                $submissions,
                $allValues,
                $inspectionStatusCounts,
                $pct,
                $visitContext,
                $user,
            );

            return array_merge($metric, [
                'value' => $value,
                'hint' => $this->shortCardHint($metric),
            ]);
        });
    }

    /** @param  array<string, int>  $inspectionStatusCounts */
    private function metricSections(
        KpiCard $card,
        Collection $submissions,
        User $user,
        Request $request,
        float $pct,
        array $inspectionStatusCounts,
        float $operationalTarget = 0,
        float $operationalCompleted = 0,
    ): array {
        $configured = collect($this->dashboardConfig->dashboardStatsFor($card->slug))->keyBy('field');
        $allValues = $submissions->flatMap->values;
        $visitContext = $this->visitMetricContext($card, $user, $request, $submissions, $inspectionStatusCounts);
        $sectionDefs = KpiMetricSections::for($card->slug, $user->role?->slug);
        $operational = [
            'target' => $operationalTarget,
            'completed' => $operationalCompleted,
            'pct' => $pct,
        ];

        if ($sectionDefs === []) {
            $flat = $configured->values()->map(function (array $metric) use ($allValues, $submissions, $inspectionStatusCounts, $pct, $visitContext, $user, $card, $operational) {
                return $this->decorateMetricCard(
                    $metric,
                    $this->resolveMetricValue($metric['field'], $submissions, $allValues, $inspectionStatusCounts, $pct, $visitContext, $user, $card->slug, $operational),
                );
            })->all();

            return KpiMetricSections::groupGeneric($flat);
        }

        return collect($sectionDefs)->map(function (array $section) use ($configured, $allValues, $submissions, $inspectionStatusCounts, $pct, $visitContext, $user, $card, $operational) {
            $metrics = collect($section['metrics'])->map(function (array $item) use ($configured, $allValues, $submissions, $inspectionStatusCounts, $pct, $visitContext, $user, $card, $operational) {
                $base = $configured->get($item['field'], [
                    'field' => $item['field'],
                    'icon' => 'bi-bar-chart',
                    'tone' => 'blue',
                    'description' => '',
                    'formula_text' => '',
                ]);

                return $this->decorateMetricCard(
                    array_merge($base, ['label' => $item['label']], array_filter([
                        'formula_text' => $item['formula_text'] ?? null,
                    ])),
                    $this->resolveMetricValue($item['field'], $submissions, $allValues, $inspectionStatusCounts, $pct, $visitContext, $user, $card->slug, $operational),
                );
            })->values()->all();

            return ['title' => $section['title'], 'metrics' => $metrics];
        })->all();
    }

    private function decorateMetricCard(array $metric, float|string|int $value): array
    {
        $field = (string) ($metric['field'] ?? '');
        $unit = $metric['unit'] ?? null;

        if ($unit === null && is_numeric($value) && $this->isPercentMetricField($field, $metric)) {
            $unit = '%';
        }

        return array_merge($metric, [
            'value' => $value,
            'unit' => $unit,
            'hint' => $this->shortCardHint($metric),
            'formula_text' => $metric['formula_text'] ?? ($metric['formula'] ?? null),
        ]);
    }

    private function isPercentMetricField(string $field, array $metric): bool
    {
        $label = strtolower((string) ($metric['label'] ?? ''));

        if (str_contains($label, '%') || str_contains($label, 'rate') || str_contains($label, 'achievement')) {
            return true;
        }

        return str_ends_with($field, '_rate')
            || in_array($field, [
                'achievement_rate',
                'fine_imposition_rate',
                'complaint_resolution_rate',
                'dc_visit_completion',
                'ac_visit_completion',
                'ac_visit_achievement',
                'compliance_rate',
                'resolution_rate',
                'plant_coverage_rate',
                'filter_change_rate',
            ], true);
    }

    /** @param  array<string, int>  $inspectionStatusCounts */
    private function visitMetricContext(
        KpiCard $card,
        User $user,
        Request $request,
        Collection $submissions,
        array $inspectionStatusCounts,
    ): array {
        if (! in_array($card->slug, [
            'inspection-of-health-facilities',
            'inspection-of-educational-institutions',
        ], true)) {
            return [];
        }

        $inspections = $this->inspectionService->getInspectionsCollection($card, $user, $request);
        $approved = $inspections->where('status', \App\Models\KpiInspection::STATUS_APPROVED)->count();
        $pending = $inspections->where('status', \App\Models\KpiInspection::STATUS_PENDING)->count();
        $rejected = $inspections->where('status', \App\Models\KpiInspection::STATUS_REJECTED)->count();
        $totalVisits = $approved + $pending + $rejected;
        $achieved = $approved + $pending;

        $totalField = $card->slug === 'inspection-of-health-facilities'
            ? 'total_health_facilities'
            : 'total_institutions';
        $totalInventory = $this->inventoryTotalForUser($user, $card->slug, $submissions, $totalField);
        $uniqueInspected = $inspections->pluck('entity_name')->filter()->unique()->count();
        $notInspected = max(0, $totalInventory - $uniqueInspected);

        $validationTarget = $achieved > 0
            ? max(1, (int) ceil($achieved * $this->validationRateForRole($user)))
            : 0;

        $issues = $this->issueCountsFromInspections($inspections, $card->slug);
        $acTarget = 2;
        $dcTarget = 2;
        $role = $user->role?->slug ?? '';
        $districtAcTarget = $this->districtAcVisitTarget($inspections);

        if ($card->slug === 'inspection-of-health-facilities' && in_array($role, ['ac', 'field_user'], true)) {
            $acVisitsCapped = min($achieved, $acTarget);
            $acVisitsDisplay = sprintf('%d / %d', $acVisitsCapped, $acTarget);
            $acVisitAchievement = min(100.0, round(($acVisitsCapped / max(1, $acTarget)) * 100, 1));
            $dcVisitsDisplay = sprintf('%d / %d', 0, $dcTarget);
        } else {
            $acVisits = (int) $submissions->sum(fn ($s) => (float) data_get($s->metric_snapshot, 'ac_visits', 0));
            $dcVisits = (int) $submissions->sum(fn ($s) => (float) data_get($s->metric_snapshot, 'dc_visits', 0));
            $acVisits = min($districtAcTarget ?: $acTarget, max($acVisits, min($achieved, $acTarget)));
            $dcVisits = min($dcTarget, max($dcVisits, 0));
            $acVisitsDisplay = sprintf('%d / %d', $acVisits, $districtAcTarget ?: $acTarget);
            $dcVisitsDisplay = sprintf('%d / %d', $dcVisits, $dcTarget);
            $acVisitAchievement = round(min(100, ($acVisits / max(1, $districtAcTarget ?: $acTarget)) * 100), 1);
        }

        return [
            'total_visits' => $totalVisits,
            'inspection_records' => $totalVisits,
            'visits_achieved' => $achieved,
            'unique_facilities_inspected' => $uniqueInspected,
            'approved' => $approved,
            'pending' => $pending,
            'rejected' => $rejected,
            'total_inventory' => $totalInventory,
            'not_inspected' => $notInspected,
            'validation_target' => $validationTarget,
            'validated' => $approved + $rejected,
            'dc_visits_display' => $dcVisitsDisplay,
            'ac_visits_display' => $acVisitsDisplay,
            'ac_visit_target' => $acTarget,
            'district_ac_visit_target' => $districtAcTarget ?: $acTarget,
            'required_visits' => $acTarget,
            'ac_visit_achievement' => $acVisitAchievement,
            'district_visits' => min($achieved, $acTarget + $dcTarget),
            'districts_reporting' => $submissions->pluck('district_id')->filter()->unique()->count(),
            'issues' => $issues,
        ];
    }

    private function inventoryTotalForUser(User $user, string $slug, Collection $submissions, string $field): int
    {
        $demo = match ($user->username) {
            'ac.lahore' => $slug === 'inspection-of-health-facilities' ? 48 : 156,
            'ac.layyah' => $slug === 'inspection-of-health-facilities' ? 34 : 112,
            'ac.karor' => $slug === 'inspection-of-health-facilities' ? 28 : 98,
            'dc.layyah' => $slug === 'inspection-of-health-facilities' ? 62 : 210,
            'com.dgkhan', 'com.lahore' => $slug === 'inspection-of-health-facilities' ? 120 : 380,
            'cs.pmru', 'super_admin' => $slug === 'inspection-of-health-facilities' ? 186 : 620,
            default => 0,
        };

        if ($demo > 0) {
            return $demo;
        }

        return (int) round($this->metricFieldSum($submissions, $submissions->flatMap->values, $field));
    }

    /** @return array<string, int> */
    private function issueCountsFromInspections(Collection $inspections, string $slug): array
    {
        $isHealth = $slug === 'inspection-of-health-facilities';
        $keys = $isHealth
            ? ['cleanliness', 'staff_absence', 'medicine_shortage', 'equipment_utilities']
            : ['cleanliness', 'teacher_absence', 'tlm_shortage', 'facility_deficiency'];

        $counts = [];
        foreach ($keys as $key) {
            $counts[$key] = 0;
        }

        foreach ($inspections as $inspection) {
            $detail = is_array($inspection->detail_data)
                ? $inspection->detail_data
                : (json_decode($inspection->detail_data ?? '[]', true) ?: []);

            if ($isHealth) {
                if ($this->isNegativeHealthSignal($detail['cleanliness'] ?? null)) {
                    $counts['cleanliness']++;
                }
                if (($detail['staff_present'] ?? 'Yes') === 'No' || ($detail['staff_absence'] ?? false)) {
                    $counts['staff_absence']++;
                }
                if (($detail['medicines_ok'] ?? 'Yes') === 'No' || ($detail['medicine_shortage'] ?? false)) {
                    $counts['medicine_shortage']++;
                }
            if (($detail['equipment_status'] ?? '') === 'Non-Operational'
                || ($detail['equipment_ok'] ?? 'Yes') === 'No'
                || ($detail['utilities_ok'] ?? 'Yes') === 'No') {
                    $counts['equipment_utilities']++;
                }
            } else {
                if ($this->isNegativeHealthSignal($detail['cleanliness'] ?? $detail['cleanliness_status'] ?? null)) {
                    $counts['cleanliness']++;
                }
                if (($detail['teachers_present'] ?? 'Yes') === 'No') {
                    $counts['teacher_absence']++;
                }
                if (($detail['tlm_shortage'] ?? false) || ($detail['tlm_ok'] ?? 'Yes') === 'No') {
                    $counts['tlm_shortage']++;
                }
                if (! empty($detail['facility_deficiency']) && $detail['facility_deficiency'] !== 'None') {
                    $counts['facility_deficiency']++;
                }
            }
        }

        return $counts;
    }

    private function isNegativeHealthSignal(mixed $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        $text = strtolower((string) $value);

        return in_array($text, ['poor', 'average', 'needs cleaning', 'needs improvement', 'no', 'false', '1'], true)
            || str_contains($text, 'poor')
            || str_contains($text, 'needs');
    }

    private function districtAcVisitTarget(Collection $inspections): int
    {
        $activeTehsils = $inspections->pluck('tehsil_id')->filter()->unique()->count();

        return max(2, $activeTehsils * 2);
    }

    private function validationRateForRole(User $user): float
    {
        return match ($user->role?->slug) {
            'ac', 'field_user' => 0.20,
            'dc', 'commissioner' => 0.05,
            default => 0.05,
        };
    }

    private function requestWithKpiPeriodDefaults(KpiCard $card, Request $request): Request
    {
        $defaults = $this->frequencyService->defaultParamsFor($card->slug);
        $shouldApply = ! $request->has('period_type');

        if (! $shouldApply) {
            return $request;
        }

        return $request->duplicate(
            array_merge($request->query(), $defaults)
        );
    }

    /** @param  list<array{type: string, title: string, key: string}>  $definitions */
    private function chartsForUser(array $definitions, User $user, string $slug): array
    {
        $role = $user->role?->slug;
        $slug = \App\Data\KpiDashboardDefinitions::normalizeSlug($slug);

        if ($slug === 'inspection-of-health-facilities') {
            return match ($role) {
                'ac', 'field_user' => [
                    ['type' => 'bar', 'title' => 'Inspection Status', 'subtitle' => 'Approved, pending, and rejected inspection records.', 'key' => 'inspection_status_breakdown'],
                    ['type' => 'donut', 'title' => 'Issues Found', 'subtitle' => 'Issues found during health inspections.', 'key' => 'health_issue_breakdown'],
                ],
                'dc' => [
                    ['type' => 'bar', 'title' => 'Visit Target Completion', 'subtitle' => 'AC and DC health visit target completion.', 'key' => 'dc_ac_visit_completion'],
                    ['type' => 'bar', 'title' => 'Tehsil Comparison — Inspection Records', 'subtitle' => 'Shows health inspection records by tehsil in selected period.', 'key' => 'tehsil_comparison'],
                    ['type' => 'donut', 'title' => 'Issues Found', 'subtitle' => 'Issues found during selected period.', 'key' => 'health_issue_breakdown'],
                    ['type' => 'bar', 'title' => 'Inspection Status', 'subtitle' => 'Approved, pending, and rejected inspection records.', 'key' => 'inspection_status_breakdown'],
                ],
                'commissioner' => [
                    ['type' => 'bar', 'title' => 'District Comparison — Inspection Records', 'subtitle' => 'Shows health inspection records by district in selected period.', 'key' => 'district_comparison'],
                    ['type' => 'donut', 'title' => 'Issues Found', 'subtitle' => 'Issues found during selected period.', 'key' => 'health_issue_breakdown'],
                    ['type' => 'bar', 'title' => 'Visit Target Completion', 'subtitle' => 'AC and DC health visit target completion.', 'key' => 'dc_ac_visit_completion'],
                    ['type' => 'bar', 'title' => 'Inspection Status', 'subtitle' => 'Approved, pending, and rejected inspection records.', 'key' => 'inspection_status_breakdown'],
                ],
                default => [
                    ['type' => 'bar', 'title' => 'District Comparison — Inspection Records', 'subtitle' => 'Shows health inspection records across Punjab in selected period.', 'key' => 'district_comparison'],
                    ['type' => 'donut', 'title' => 'Issues Found', 'subtitle' => 'Issues found during selected period.', 'key' => 'health_issue_breakdown'],
                    ['type' => 'bar', 'title' => 'Inspection Status', 'subtitle' => 'Approved, pending, and rejected inspection records.', 'key' => 'inspection_status_breakdown'],
                    ['type' => 'bar', 'title' => 'Visit Target Completion', 'subtitle' => 'AC and DC health visit target completion.', 'key' => 'dc_ac_visit_completion'],
                ],
            };
        }

        if ($slug === 'inspection-of-educational-institutions') {
            return match ($role) {
                'ac', 'field_user' => [
                    ['type' => 'gauge', 'title' => 'Visit Completion', 'key' => 'ac_visit_completion_gauge'],
                    ['type' => 'donut', 'title' => 'Issue Category Breakdown', 'key' => 'issue_category_breakdown'],
                    ['type' => 'donut', 'title' => 'Inspection Status', 'key' => 'status_donut'],
                ],
                'dc' => [
                    ['type' => 'bar', 'title' => 'Tehsil Comparison', 'key' => 'tehsil_comparison'],
                    ['type' => 'donut', 'title' => 'Issue Category Breakdown', 'key' => 'issue_category_breakdown'],
                    ['type' => 'donut', 'title' => 'Inspection Status', 'key' => 'status_donut'],
                    ['type' => 'gauge', 'title' => 'School Council Activation', 'key' => 'school_council_activation'],
                ],
                'commissioner' => [
                    ['type' => 'bar', 'title' => 'District Comparison', 'key' => 'district_comparison'],
                    ['type' => 'donut', 'title' => 'Issue Category Breakdown', 'key' => 'issue_category_breakdown'],
                    ['type' => 'donut', 'title' => 'Inspection Status', 'key' => 'status_donut'],
                ],
                default => [
                    ['type' => 'bar', 'title' => 'District Comparison', 'key' => 'district_comparison'],
                    ['type' => 'donut', 'title' => 'Issue Category Breakdown', 'key' => 'issue_category_breakdown'],
                    ['type' => 'donut', 'title' => 'Inspection Status', 'key' => 'status_donut'],
                ],
            };
        }

        if ($slug === 'price-of-roti') {
            return [
                ['type' => 'line', 'title' => 'Daily Inspections Trend', 'key' => 'daily_inspections_trend'],
                ['type' => 'donut', 'title' => 'Violation Type Breakdown', 'key' => 'violation_type_breakdown'],
            ];
        }

        $filtered = collect($definitions)->filter(function (array $definition) use ($role) {
            $key = $definition['key'];

            if (in_array($role, ['ac', 'field_user'], true)) {
                return ! in_array($key, [
                    'tehsil_comparison', 'district_comparison', 'division_comparison',
                    'dc_ac_visit_completion', 'dc_ac_inspection_comparison',
                ], true);
            }

            if ($role === 'dc') {
                return ! in_array($key, ['district_comparison', 'division_comparison'], true);
            }

            if ($role === 'commissioner') {
                return $key !== 'division_comparison';
            }

            if (in_array($role, ['chief_secretary', 'super_admin', 'pmru_user', 'viewer'], true)) {
                return $key !== 'tehsil_comparison';
            }

            return true;
        })->values();

        return $filtered->all();
    }

    private function shortCardHint(array $metric): ?string
    {
        $formula = trim((string) ($metric['formula_text'] ?? ''));
        $label = strtolower((string) ($metric['label'] ?? ''));

        if ($formula === '') {
            return null;
        }

        if (str_contains($label, 'rate')
            || str_contains($label, '%')
            || str_contains($label, 'achievement')
            || str_contains($label, 'compliance')
            || str_contains($label, 'resolution')) {
            return \Illuminate\Support\Str::limit($formula, 42);
        }

        return null;
    }

    /** @param  array<string, int>  $inspectionStatusCounts */
    /** @param  array<string, mixed>  $visitContext */
    private function resolveMetricValue(
        string $field,
        Collection $submissions,
        Collection $allValues,
        array $inspectionStatusCounts,
        float $pct,
        array $visitContext = [],
        ?User $user = null,
        ?string $cardSlug = null,
        array $operational = [],
    ): float|string|int {
        if ($cardSlug === 'price-of-roti' && $operational !== []) {
            $override = match ($field) {
                'tandoor_inspections' => $operational['completed'] ?? null,
                'inspections_total_target' => $operational['target'] ?? null,
                'achievement_rate' => $operational['pct'] ?? null,
                default => null,
            };
            if ($override !== null) {
                return $override;
            }
        }

        if ($visitContext !== []) {
            $issues = $visitContext['issues'] ?? [];

            return match ($field) {
                'target_completed' => (float) ($operational['completed'] ?? 0),
                'total_visits', 'inspection_records' => (int) $visitContext['inspection_records'],
                'visits_achieved' => (int) $visitContext['visits_achieved'],
                'facilities_inspected', 'institutions_inspected' => (int) $visitContext['unique_facilities_inspected'],
                'facilities_not_inspected' => (int) $visitContext['not_inspected'],
                'institutions_not_inspected' => (int) $visitContext['not_inspected'],
                'total_health_facilities' => (int) $visitContext['total_inventory'],
                'total_institutions' => (int) $visitContext['total_inventory'],
                'inspections_pending' => (int) $visitContext['pending'],
                'inspections_approved' => (int) $visitContext['approved'],
                'inspections_rejected' => (int) $visitContext['rejected'],
                'validation_target' => (int) $visitContext['validation_target'],
                'validations_completed', 'validated_inspections' => (int) $visitContext['validated'],
                'dc_visits' => $visitContext['dc_visits_display'],
                'ac_visits' => $visitContext['ac_visits_display'],
                'ac_visit_target' => (int) ($visitContext['ac_visit_target'] ?? 2),
                'district_ac_visit_target' => (int) ($visitContext['district_ac_visit_target'] ?? 2),
                'required_visits' => (int) ($visitContext['required_visits'] ?? 2),
                'ac_visit_achievement' => (float) ($visitContext['ac_visit_achievement'] ?? 0),
                'district_visits' => (int) ($visitContext['district_visits'] ?? 0),
                'districts_reporting' => (int) ($visitContext['districts_reporting'] ?? 0),
                'issues_cleanliness' => (int) ($issues['cleanliness'] ?? 0),
                'issues_staff_absence' => (int) ($issues['staff_absence'] ?? 0),
                'issues_medicine_shortage' => (int) ($issues['medicine_shortage'] ?? 0),
                'issues_equipment_utilities' => (int) ($issues['equipment_utilities'] ?? 0),
                'issues_teacher_absence' => (int) ($issues['teacher_absence'] ?? 0),
                'issues_tlm_shortage' => (int) ($issues['tlm_shortage'] ?? 0),
                'issues_facility_deficiency' => (int) ($issues['facility_deficiency'] ?? 0),
                default => null,
            } ?? $this->resolveMetricValueCore($field, $submissions, $allValues, $inspectionStatusCounts, $pct);
        }

        return $this->resolveMetricValueCore($field, $submissions, $allValues, $inspectionStatusCounts, $pct);
    }

    /** @param  array<string, int>  $inspectionStatusCounts */
    private function resolveMetricValueCore(
        string $field,
        Collection $submissions,
        Collection $allValues,
        array $inspectionStatusCounts,
        float $pct,
    ): float|string|int {
        if ($this->isPercentageMetric($field)) {
            $value = $this->percentageMetricValue($submissions, $allValues, $field);

            return $this->formula->displayPercentage(
                $field === 'achievement_rate' && $value <= 0 ? $pct : $value
            );
        }

        return match ($field) {
            'complaints_resolved' => (int) round($this->metricFieldSum($submissions, $allValues, $field)),
            'over_price_violations', 'under_weight_violations', 'non_availability_violations'
                => (int) round($this->metricFieldSum($submissions, $allValues, $field)),
            'approved_validations' => (int) ($inspectionStatusCounts['approved'] ?? 0),
            'rejected_validations' => (int) ($inspectionStatusCounts['rejected'] ?? 0),
            'validated_inspections' => (int) (($inspectionStatusCounts['approved'] ?? 0) + ($inspectionStatusCounts['rejected'] ?? 0)),
            'approved_rejected_validations' => sprintf(
                '%d / %d',
                (int) ($inspectionStatusCounts['approved'] ?? 0),
                (int) ($inspectionStatusCounts['rejected'] ?? 0)
            ),
            default => $this->roundMetricValue($this->metricFieldSum($submissions, $allValues, $field)),
        };
    }

    private function roundMetricValue(float $value): float
    {
        return round($value, fmod(abs($value), 1.0) > 0.001 ? 1 : 0);
    }

    private function metricFieldSum(Collection $submissions, Collection $allValues, string $field): float
    {
        if (in_array($field, ['total_health_facilities', 'total_institutions', 'total_institutions_count'], true)) {
            return (float) $submissions->max(
                fn ($submission) => (float) data_get($submission->metric_snapshot, $field, 0)
            );
        }

        if (in_array($field, ['tier_target', 'total_inspectors', 'inspections_total_target'], true)) {
            return (float) $submissions->max(
                fn ($submission) => (float) data_get($submission->metric_snapshot, $field, 0)
            );
        }

        if ($field === 'facilities_not_inspected') {
            $total = (float) $submissions->max(fn ($s) => (float) data_get($s->metric_snapshot, 'total_health_facilities', 0));
            $visited = (float) $submissions->sum(fn ($s) => (float) data_get($s->metric_snapshot, 'facility_visits', 0));

            return max(0, $total - min($total, $visited));
        }

        if ($field === 'institutions_not_inspected') {
            $total = (float) $submissions->max(fn ($s) => (float) data_get($s->metric_snapshot, 'total_institutions', 0));
            $visited = (float) $submissions->sum(fn ($s) => (float) data_get($s->metric_snapshot, 'institution_visits', 0));

            return max(0, $total - min($total, $visited));
        }

        $fromValues = (float) $allValues
            ->filter(fn ($v) => $v->field?->field_name === $field)
            ->sum(fn ($v) => (float) $v->value);

        if ($fromValues > 0) {
            return $fromValues;
        }

        return (float) $submissions->sum(function ($submission) use ($field) {
            $snapshot = $submission->metric_snapshot;
            if (is_string($snapshot)) {
                $snapshot = json_decode($snapshot, true) ?: [];
            }

            return (float) data_get($snapshot, $field, 0);
        });
    }

    private function percentageMetricValue(Collection $submissions, Collection $allValues, string $field): float
    {
        if ($field === 'fine_imposition_rate') {
            $generated = (float) $submissions->sum(
                fn ($submission) => (float) data_get($submission->metric_snapshot, 'fine_generated', 0)
            );
            $deposited = (float) $submissions->sum(
                fn ($submission) => (float) data_get($submission->metric_snapshot, 'fine_deposited', 0)
            );

            if ($generated > 0) {
                return round(($deposited / $generated) * 100, 1);
            }
        }

        if ($field === 'complaint_resolution_rate') {
            $received = (float) $submissions->sum(
                fn ($submission) => (float) data_get($submission->metric_snapshot, 'citizen_complaints_received', 0)
            );
            $resolved = (float) $submissions->sum(
                fn ($submission) => (float) data_get($submission->metric_snapshot, 'complaints_resolved', 0)
            );

            if ($received > 0) {
                return round(($resolved / $received) * 100, 1);
            }
        }

        $snapshotValues = $submissions
            ->map(fn ($submission) => data_get($submission->metric_snapshot, $field))
            ->filter(fn ($value) => is_numeric($value));

        if ($snapshotValues->isNotEmpty()) {
            return (float) $snapshotValues->avg();
        }

        $formValues = $allValues
            ->filter(fn ($value) => $value->field?->field_name === $field && is_numeric($value->value))
            ->map(fn ($value) => (float) $value->value);

        return $formValues->isNotEmpty() ? (float) $formValues->avg() : 0.0;
    }

    private function isPercentageMetric(string $field): bool
    {
        return str_ends_with($field, '_rate')
            || str_ends_with($field, '_percentage')
            || str_ends_with($field, '_completion')
            || str_ends_with($field, '_achievement')
            || str_ends_with($field, '_compliance')
            || in_array($field, [
                'hr_attendance',
                'coverage_mobility_index',
                'disposal_rate',
                'school_council_activation',
            ], true);
    }

    private function metricHint(string $label): string
    {
        return '';
    }

    private function areaScores(Collection $submissions, User $user): Collection
    {
        $relation = $this->scopeService->comparisonRelation($user);

        $scores = $submissions
            ->filter(fn ($item) => $item->{$relation})
            ->groupBy(fn ($item) => $item->{$relation}->name)
            ->map(fn ($group) => $this->formula->displayPercentage(
                (float) $group->avg(fn ($item) => (float) ($item->achievement_percentage ?? $item->kpiScore?->percentage ?? $item->score))
            ));

        if ($scores->isNotEmpty()) {
            return $scores;
        }

        return $submissions
            ->groupBy(fn ($item) => $item->submission_date?->format('d M') ?? 'Period')
            ->map(fn ($group) => $this->formula->displayPercentage(
                (float) $group->avg(fn ($item) => (float) ($item->achievement_percentage ?? 0))
            ));
    }
}
