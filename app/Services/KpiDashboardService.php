<?php

namespace App\Services;

use App\Data\EducationObservationLabels;
use App\Data\HealthObservationLabels;
use App\Data\KpiDashboardDefinitions;
use App\Data\KpiFrequencyConfig;
use App\Data\KpiLocationSlugs;
use App\Data\KpiMetricSections;
use App\Models\KpiCard;
use App\Models\KpiInspection;
use App\Models\KpiSubmission;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

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
        private readonly HealthInspectionMapService $healthMapService,
        private readonly EducationInspectionMapService $educationMapService,
        private readonly KpiInspectionMapService $inspectionMapService,
        private readonly KpiInspectionDashboardContext $inspectionDashboardContext,
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

                return $cards->map(function (KpiCard $card) use ($user, $request) {
                    $cardRequest = $this->requestWithKpiPeriodDefaults($card, $request);
                    $submissions = $this->filteredSubmissions(
                        KpiSubmission::query()->where('kpi_card_id', $card->id),
                        $user,
                        $cardRequest
                    )->get([
                        'kpi_card_id', 'user_id', 'area_level', 'submission_date', 'metric_snapshot',
                        'reported_value', 'achieved_value', 'achievement_percentage', 'target_value',
                    ]);

                    $header = $this->resolveOperationalHeader(
                        $card,
                        $submissions,
                        $user,
                        $cardRequest,
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
        $allowFallback = ! $this->hasExplicitDashboardFilter($request);
        $request = $this->requestWithKpiPeriodDefaults($card, $request);

        [$submissions, $usedFallback] = $this->loadSubmissions($card, $user, $request, $allowFallback);

        $perPage = min(50, max(10, (int) $request->input('per_page', 15)));

        $tableSubmissions = $this->paginateSubmissions($card, $user, $request, $perPage, $usedFallback);

        $headerMetrics = $this->resolveOperationalHeader($card, $submissions, $user, $request, periodTotals: true);
        $headerLabels = $this->dashboardConfig->headerLabelsFor($card->slug);
        $areaScores = $this->areaScores($submissions, $user);

        $kpiConfig = $this->dashboardConfig->forKpi($card->slug);
        $chartDefinitions = $this->chartsForUser($kpiConfig['charts'], $user, $card->slug);
        $inspectionCollection = in_array($card->slug, [
            'inspection-of-health-facilities',
            'inspection-of-educational-institutions',
        ], true)
            ? ($card->slug === 'inspection-of-health-facilities'
                ? $this->inspectionService->healthInspectionsForMetrics($card, $user, $request)
                : $this->inspectionService->educationInspectionsForMetrics($card, $user, $request))
            : $this->inspectionService->getInspectionsCollection($card, $user, $request);
        if (! in_array($card->slug, ['inspection-of-health-facilities', 'inspection-of-educational-institutions'], true)) {
            $inspectionCollection = $this->inspectionService->synchronizeReviewSample($card, $user, $request, $inspectionCollection);
        }
        $inspectionStatusCounts = $this->inspectionService->buildStatusCounts($card, $user, $request);
        $inspectionTableColumns = $this->inspectionService->getTableColumnsForKpi($card->slug);
        $visitContext = in_array($card->slug, [
            'inspection-of-health-facilities',
            'inspection-of-educational-institutions',
        ], true)
            ? $this->visitMetricContext(
                $card,
                $user,
                $request,
                $submissions,
                $inspectionStatusCounts,
                (float) $headerMetrics['operational_target'],
                (float) $headerMetrics['completed'],
            )
            : [];

        $inspectionContext = $visitContext === [] && ! KpiLocationSlugs::isPlaceholder($card->slug)
            ? $this->inspectionDashboardContext->forKpi(
                $card,
                $user,
                $request,
                $inspectionCollection,
                $inspectionStatusCounts,
                (float) $headerMetrics['operational_target'],
                (float) $headerMetrics['completed'],
            )
            : [];

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
                $inspectionStatusCounts,
                $inspectionContext,
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
                $inspectionContext,
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
                $card->slug === 'inspection-of-health-facilities' || $card->slug === 'inspection-of-educational-institutions'
                    ? $visitContext
                    : [],
                (string) $request->input('period_type', 'weekly'),
            ),
            'filters' => $this->filterOptionsForView($card->slug, $request),
            'geo' => $this->geoFilterService->state($request),
            'period' => $this->periodState($request),
            'period_description' => $this->periodService->description($request),
            'inspectionRecords' => $this->inspectionService->getInspectionListForKpi($card, $user, $request),
            'inspectionStatusCounts' => $inspectionStatusCounts,
            'inspectionFilters' => $this->inspectionService->filterOptions($user),
            'canReviewInspections' => $this->inspectionService->canReviewInspections($user),
            ...$this->visitMapPayload($card, $user, $request, $visitContext),
            ...$this->locationMapPayload($card, $user, $request, $inspectionContext, $inspectionStatusCounts),
            'isPlaceholderDashboard' => KpiLocationSlugs::isPlaceholder($card->slug),
            'placeholderMessage' => KpiLocationSlugs::isPlaceholder($card->slug)
                ? 'Land Management Services dashboard specification pending.'
                : null,
            'hasLocationMapDashboard' => KpiLocationSlugs::hasLocationMap($card->slug),
            'hasInspectionDashboard' => ! KpiLocationSlugs::isVisitKpi($card->slug)
                && ! KpiLocationSlugs::isPlaceholder($card->slug),
        ];
    }

    /** @param  array<string, mixed>  $visitContext  @return array{healthMap: array, educationMap: array, visitMap: array} */
    private function visitMapPayload(KpiCard $card, User $user, Request $request, array $visitContext): array
    {
        $shared = [
            'approved' => (int) ($visitContext['approved'] ?? 0),
            'pending' => (int) ($visitContext['pending'] ?? 0),
            'rejected' => (int) ($visitContext['rejected'] ?? 0),
        ];
        $inspected = (int) ($visitContext['unique_facilities_inspected'] ?? 0);

        $visitMap = match ($card->slug) {
            'inspection-of-health-facilities' => $this->healthMapService->forDashboard($card, $user, $request, [
                'facilities_inspected' => $inspected,
                ...$shared,
            ]),
            'inspection-of-educational-institutions' => $this->educationMapService->forDashboard($card, $user, $request, [
                'institutions_inspected' => $inspected,
                ...$shared,
            ]),
            default => [],
        };

        return [
            'healthMap' => $card->slug === 'inspection-of-health-facilities' ? $visitMap : null,
            'educationMap' => $card->slug === 'inspection-of-educational-institutions' ? $visitMap : null,
            'visitMap' => $visitMap !== [] ? $visitMap : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $inspectionContext
     * @param  array<string, int>  $statusCounts
     * @return array{locationMap: array<string, mixed>}
     */
    private function locationMapPayload(
        KpiCard $card,
        User $user,
        Request $request,
        array $inspectionContext,
        array $statusCounts,
    ): array {
        if (! KpiLocationSlugs::hasLocationMap($card->slug)) {
            return ['locationMap' => null];
        }

        return [
            'locationMap' => $this->inspectionMapService->forDashboard($card, $user, $request, [
                'inspected' => (int) ($inspectionContext['inspected'] ?? 0),
                'approved' => (int) ($statusCounts['approved'] ?? $inspectionContext['approved'] ?? 0),
                'pending' => (int) ($statusCounts['pending_review'] ?? $inspectionContext['pending'] ?? 0),
                'rejected' => (int) ($statusCounts['rejected'] ?? $inspectionContext['rejected'] ?? 0),
            ]),
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

    public function filterOptionsForView(?string $kpiSlug = null, ?Request $request = null): array
    {
        $request ??= request();
        $year = (int) ($request->input('year') ?: now()->year);
        $month = (int) ($request->input('month') ?: now()->month);
        $options = $this->periodService->filterOptions($year, $month);

        if ($kpiSlug) {
            $options['period_types'] = $this->frequencyService->periodTypesFor($kpiSlug);
            $options['defaults'] = $this->frequencyService->defaultParamsFor($kpiSlug);
            if ($this->frequencyService->isWeekly($kpiSlug) && ! $this->frequencyService->isDaily($kpiSlug)) {
                $defaultWeek = $this->periodService->latestCompletedWeekNo();
                $options['default_week_no'] = $defaultWeek;
                if (! isset($options['weeks'][$defaultWeek])) {
                    $range = $this->periodService->getWeekDateRange($defaultWeek);
                    if (($range['start'] ?? null) && ($range['end'] ?? null)) {
                        $options['weeks'][$defaultWeek] = $this->periodService->formatWeekLabel(
                            $range['start'],
                            $range['end'],
                            null,
                            true,
                        );
                    }
                }
            }
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
    private function loadSubmissions(KpiCard $card, User $user, Request $request, bool $allowFallback = true): array
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

        if (! $allowFallback) {
            return [collect(), false];
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
    ): array {
        $fields = $this->dashboardConfig->operationalFieldsFor($card->slug);
        $marks = (float) $card->total_marks;
        $hasCalculatedVisitTarget = in_array($card->slug, [
            'inspection-of-health-facilities',
            'inspection-of-educational-institutions',
        ], true) || $this->usesInspectionOperationalAchieved($card->slug);

        $inspectionAchieved = ($hasCalculatedVisitTarget || $this->usesInspectionOperationalAchieved($card->slug)) && $periodTotals
            ? match ($card->slug) {
                'inspection-of-health-facilities' => $this->inspectionService->countHealthInspected($card, $user, $request),
                'inspection-of-educational-institutions' => $this->inspectionService->countEducationInspected($card, $user, $request),
                'repair-of-small-roads-in-both-urban-and-rural-areas' => $this->inspectionService
                    ->getInspectionsCollection($card, $user, $request)
                    ->filter(fn (KpiInspection $inspection): bool => data_get($inspection->detail_data, 'completion_status') === 'Completed')
                    ->count(),
                default => $this->inspectionService->countScopedInspections($card, $user, $request),
            }
            : null;

        if ($inspectionAchieved !== null && ! in_array($card->slug, [
            'inspection-of-health-facilities',
            'inspection-of-educational-institutions',
        ], true)) {
            $periodType = $this->periodService->resolvedParams($request)['period_type'] ?? 'weekly';
            $periodMatchesFrequency = (KpiFrequencyConfig::isDaily($card->slug) && $periodType === 'daily')
                || (KpiFrequencyConfig::isWeekly($card->slug) && $periodType === 'weekly');

            if (! $periodMatchesFrequency) {
                $inspectionAchieved = null;
            }
        }

        $activeScope = in_array($card->slug, [
            'inspection-of-health-facilities',
            'inspection-of-educational-institutions',
        ], true) && $periodTotals
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
        if (in_array($card->slug, [
            'inspection-of-health-facilities',
            'inspection-of-educational-institutions',
        ], true) && $periodTotals && $operationalTarget > 0) {
            $completed = min($completed, $operationalTarget);
        }

        // Records = submitted KPI reports only (not summed activity-volume fields).
        $records = $submissions->count();
        $inspectionsCount = $this->inspectionService->countScopedInspections($card, $user, $request);

        $pct = $this->resolveAchievementPct($submissions, $completed, $operationalTarget);
        if (in_array($card->slug, [
            'inspection-of-health-facilities',
            'inspection-of-educational-institutions',
        ], true)) {
            $pct = min(100.0, $pct);
        }
        $score = $this->formula->scoreFromWeightage($pct, $marks);

        $reviewPercentage = 0.0;
        if (in_array($card->slug, [
            'inspection-of-health-facilities',
            'inspection-of-educational-institutions',
        ], true) && $periodTotals) {
            $reviewPercentage = $card->slug === 'inspection-of-health-facilities'
                ? $this->healthReviewPercentage($card, $user, $request, $completed)
                : $this->educationReviewPercentage($card, $user, $request, $completed);
        }

        return [
            'operational_target' => $operationalTarget,
            'completed' => $completed,
            'actual_completed' => $actualCompleted,
            'records' => $records,
            'submitted_reports' => $records,
            'inspections_count' => $inspectionsCount,
            'achievement_percentage' => $pct,
            'review_percentage' => $reviewPercentage,
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

    /** @param  array<string, int>  $inspectionStatusCounts @param  array<string, mixed>  $inspectionContext */
    private function metrics(
        KpiCard $card,
        Collection $submissions,
        User $user,
        Request $request,
        float $target,
        float $achieved,
        float $pct,
        array $inspectionStatusCounts,
        array $inspectionContext = [],
    ): Collection {
        $configured = collect($this->dashboardConfig->dashboardStatsFor($card->slug));
        $allValues = $submissions->flatMap->values;
        $visitContext = $this->visitMetricContext(
            $card,
            $user,
            $request,
            $submissions,
            $inspectionStatusCounts,
            $target,
            $achieved,
        );
        $operational = ['target' => $target, 'completed' => $achieved, 'pct' => $pct];

        return $configured->map(function (array $metric) use ($allValues, $submissions, $inspectionStatusCounts, $pct, $visitContext, $inspectionContext, $user, $card, $operational) {
            $value = $this->resolveMetricValue(
                $metric['field'],
                $submissions,
                $allValues,
                $inspectionStatusCounts,
                $pct,
                $visitContext,
                $user,
                $card->slug,
                $operational,
                $inspectionContext,
            );

            return $this->decorateMetricCard($metric, $value);
        });
    }

    /** @param  array<string, int>  $inspectionStatusCounts @param  array<string, mixed>  $inspectionContext */
    private function metricSections(
        KpiCard $card,
        Collection $submissions,
        User $user,
        Request $request,
        float $pct,
        array $inspectionStatusCounts,
        float $operationalTarget = 0,
        float $operationalCompleted = 0,
        array $inspectionContext = [],
    ): array {
        $configured = collect($this->dashboardConfig->dashboardStatsFor($card->slug))->keyBy('field');
        $allValues = $submissions->flatMap->values;
        $visitContext = $this->visitMetricContext(
            $card,
            $user,
            $request,
            $submissions,
            $inspectionStatusCounts,
            $operationalTarget,
            $operationalCompleted,
        );
        $sectionDefs = KpiMetricSections::for($card->slug, $user->role?->slug);
        $operational = [
            'target' => $operationalTarget,
            'completed' => $operationalCompleted,
            'pct' => $pct,
        ];

        if ($sectionDefs === []) {
            $flat = $configured->values()->map(function (array $metric) use ($allValues, $submissions, $inspectionStatusCounts, $pct, $visitContext, $inspectionContext, $user, $card, $operational) {
                return $this->decorateMetricCard(
                    $metric,
                    $this->resolveMetricValue($metric['field'], $submissions, $allValues, $inspectionStatusCounts, $pct, $visitContext, $user, $card->slug, $operational, $inspectionContext),
                );
            })->all();

            return KpiMetricSections::groupGeneric($flat);
        }

        return collect($sectionDefs)->map(function (array $section) use ($configured, $allValues, $submissions, $inspectionStatusCounts, $pct, $visitContext, $inspectionContext, $user, $card, $operational) {
            $metrics = collect($section['metrics'])->map(function (array $item) use ($configured, $allValues, $submissions, $inspectionStatusCounts, $pct, $visitContext, $inspectionContext, $user, $card, $operational) {
                $base = $configured->get($item['field'], [
                    'field' => $item['field'],
                    'icon' => 'bi-bar-chart',
                    'tone' => 'blue',
                    'description' => '',
                    'formula_text' => '',
                ]);
                if ($item['field'] === 'review_completion_rate' && $inspectionContext !== []) {
                    $base['formula_text'] = sprintf(
                        '%s records reviewed against a minimum required target of %s.',
                        number_format((int) ($inspectionContext['reviewed'] ?? 0)),
                        number_format((int) ($inspectionContext['review_target'] ?? 0)),
                    );
                }

                return $this->decorateMetricCard(
                    array_merge($base, ['label' => $item['label']], array_filter([
                        'formula_text' => $item['formula_text'] ?? null,
                    ])),
                    $this->resolveMetricValue($item['field'], $submissions, $allValues, $inspectionStatusCounts, $pct, $visitContext, $user, $card->slug, $operational, $inspectionContext),
                );
            })->values()->all();

            return ['title' => $section['title'], 'metrics' => $metrics];
        })->all();
    }

    private function decorateMetricCard(array $metric, float|string|int|array $value): array
    {
        $field = (string) ($metric['field'] ?? '');
        $unit = $metric['unit'] ?? null;

        if ($field === 'fine_deposited') {
            $value = 'PKR '.number_format((float) $value);
            $unit = null;
        }

        if ($field === 'review_completion_rate') {
            $metric['formula_text'] = ($metric['formula_text'] ?? null) ?: 'Minimum review requirement completed for the selected reporting period.';
            $metric['description'] = $metric['formula_text'];
        }

        if ($field === 'total_health_facilities') {
            $metric['description'] = 'Total health facilities in this area';
            $metric['card_helper'] = 'Total health facilities in this area';
        }

        if ($field === 'total_institutions') {
            $metric['description'] = 'Total educational institutions in this area';
            $metric['card_helper'] = 'Total educational institutions in this area';
        }

        $observationLabels = $this->observationLabelsForField($field);

        if (is_array($value) && array_key_exists('available', $value)) {
            $labels = $observationLabels ?? HealthObservationLabels::meta($field);

            return array_merge($metric, [
                'value' => '',
                'unit' => null,
                'hint' => $this->shortCardHint($metric),
                'formula_text' => $metric['formula_text'] ?? ($metric['formula'] ?? null),
                'display_mode' => 'observation_availability',
                'observation_available' => (int) ($value['available'] ?? 0),
                'observation_not_available' => (int) ($value['not_available'] ?? 0),
                'observation_not_recorded' => (int) ($value['not_recorded'] ?? 0),
                'observation_positive_label' => $labels['positive'],
                'observation_negative_label' => $labels['negative'],
            ]);
        }

        if (is_array($value) && array_key_exists('yes', $value)) {
            $labels = $observationLabels ?? HealthObservationLabels::meta($field);

            return array_merge($metric, [
                'value' => '',
                'unit' => null,
                'hint' => $this->shortCardHint($metric),
                'formula_text' => $metric['formula_text'] ?? ($metric['formula'] ?? null),
                'display_mode' => 'observation_yesno',
                'observation_yes' => (int) ($value['yes'] ?? 0),
                'observation_no' => (int) ($value['no'] ?? 0),
                'observation_positive_label' => $labels['positive'],
                'observation_negative_label' => $labels['negative'],
            ]);
        }

        if ($field === 'observation_attention_required') {
            $count = (int) $value;
            $helper = str_starts_with($field, 'observation_') && in_array($field, EducationObservationLabels::observationMetricFields(), true)
                ? EducationObservationLabels::ATTENTION_HELPER
                : HealthObservationLabels::ATTENTION_HELPER;

            return array_merge($metric, [
                'value' => '',
                'unit' => null,
                'hint' => $this->shortCardHint($metric),
                'formula_text' => $metric['formula_text'] ?? ($metric['formula'] ?? null),
                'description' => $helper,
                'card_helper' => $helper,
                'display_mode' => 'attention',
                'attention_text' => (string) $count,
                'attention_count' => $count,
            ]);
        }

        if (is_array($value) && array_key_exists('enrolled', $value)) {
            return array_merge($metric, [
                'value' => '',
                'unit' => null,
                'hint' => $this->shortCardHint($metric),
                'formula_text' => $metric['formula_text'] ?? ($metric['formula'] ?? null),
                'display_mode' => 'student_attendance',
                'attendance_enrolled' => (int) ($value['enrolled'] ?? 0),
                'attendance_present' => (int) ($value['present'] ?? 0),
                'attendance_percent' => (float) ($value['percent'] ?? 0),
            ]);
        }

        if ($field === 'school_council_meeting' && is_string($value)) {
            return array_merge($metric, [
                'value' => $value,
                'unit' => $unit,
                'hint' => $this->shortCardHint($metric),
                'formula_text' => $metric['formula_text'] ?? ($metric['formula'] ?? null),
            ]);
        }

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
                'review_completion_rate',
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
        float $operationalTarget = 0,
        float $operationalCompleted = 0,
    ): array {
        if ($card->slug === 'inspection-of-health-facilities') {
            return $this->healthVisitMetricContext(
                $card,
                $user,
                $request,
                $submissions,
                $operationalTarget,
                $operationalCompleted,
            );
        }

        if ($card->slug === 'inspection-of-educational-institutions') {
            return $this->educationVisitMetricContext(
                $card,
                $user,
                $request,
                $submissions,
                $operationalTarget,
                $operationalCompleted,
            );
        }

        return [];
    }

    /** @return array<string, mixed> */
    private function educationVisitMetricContext(
        KpiCard $card,
        User $user,
        Request $request,
        Collection $submissions,
        float $operationalTarget,
        float $operationalCompleted,
    ): array {
        $inspections = $this->inspectionService->educationInspectionsForMetrics($card, $user, $request);
        $allInspections = $this->inspectionService->getInspectionsCollection($card, $user, $request);
        $role = $user->role?->slug ?? '';
        $institutionsInspected = (int) max(0, round($operationalCompleted));
        $totalInventory = $this->inventoryTotalForUser($user, $card->slug, $submissions, 'total_institutions');

        $reviewTarget = $this->inspectionService->educationReviewTarget($user, $inspections, $institutionsInspected);
        $reviewCounts = $this->inspectionService->educationReviewStatusCounts($inspections, $reviewTarget);
        $approved = $reviewCounts['approved'];
        $pending = $reviewCounts['pending'];
        $rejected = $reviewCounts['rejected'];
        $reviewed = $approved + $rejected;
        $reviewCompletionRate = $reviewTarget > 0
            ? min(100.0, round(($reviewed / $reviewTarget) * 100, 1))
            : 0.0;

        $displayInspections = $this->educationObservationInspections($inspections);
        $observations = $this->educationObservationCountsFromInspections($displayInspections);

        $acWeeklyTarget = 2;
        $dcWeeklyTarget = 2;
        $councilMeetingTarget = 2;
        $tehsilIds = $this->inspectionService->officialTehsilIds($user, $request);
        $districtIds = $this->inspectionService->officialDistrictIds($user, $request);
        $districtAcTarget = max($acWeeklyTarget, $tehsilIds->count() * $acWeeklyTarget);
        $districtDcTarget = max($dcWeeklyTarget, $districtIds->count() * $dcWeeklyTarget);
        $councilMeetingScopeTarget = match ($role) {
            'dc' => $councilMeetingTarget,
            'commissioner' => max($councilMeetingTarget, $districtIds->count() * $councilMeetingTarget),
            default => max($councilMeetingTarget, $districtIds->count() * $councilMeetingTarget),
        };

        $acVisitsCompleted = in_array($role, ['ac', 'field_user'], true)
            ? min($acWeeklyTarget, $institutionsInspected)
            : $this->inspectionService->educationAcVisitsCompleted($allInspections, $tehsilIds);
        $acVisitTarget = in_array($role, ['ac', 'field_user'], true)
            ? $acWeeklyTarget
            : $districtAcTarget;
        $dcOwnInspections = $this->healthDcOwnInspectionCount($allInspections, $submissions, $dcWeeklyTarget);
        $dcVisitTarget = in_array($role, ['dc'], true)
            ? $dcWeeklyTarget
            : $districtDcTarget;
        $dcVisitsCompleted = in_array($role, ['dc'], true)
            ? $dcOwnInspections
            : $this->inspectionService->educationDcVisitsCompleted($allInspections, $districtIds);
        $councilMeetingsHeld = min(
            $councilMeetingScopeTarget,
            (int) $submissions->sum(fn ($s) => (float) data_get($s->metric_snapshot, 'school_council_meeting', 0))
        );

        $inspectionAchievementTarget = match ($role) {
            'dc' => $districtAcTarget + $dcWeeklyTarget,
            default => $districtAcTarget + $districtDcTarget,
        };
        $inspectionAchievementCompleted = min(
            $inspectionAchievementTarget,
            $acVisitsCompleted + $dcVisitsCompleted
        );
        $inspectionAchievementRemaining = max(0, $inspectionAchievementTarget - $inspectionAchievementCompleted);

        $requiredInspections = in_array($role, ['ac', 'field_user'], true) ? $acWeeklyTarget : $districtAcTarget;
        $acVisitAchievement = min(
            100.0,
            round(($acVisitsCompleted / max(1, $acVisitTarget)) * 100, 1)
        );
        $districtInspections = min($institutionsInspected, $districtAcTarget + $dcWeeklyTarget);

        return [
            'total_visits' => $institutionsInspected,
            'total_inspections' => $institutionsInspected,
            'inspection_records' => $institutionsInspected,
            'visits_achieved' => $institutionsInspected,
            'unique_facilities_inspected' => $institutionsInspected,
            'approved' => $approved,
            'pending' => $pending,
            'rejected' => $rejected,
            'total_inventory' => $totalInventory,
            'not_inspected' => max(0, $totalInventory - $institutionsInspected),
            'review_target' => $reviewTarget,
            'validation_target' => $reviewTarget,
            'review_completion_rate' => $reviewCompletionRate,
            'review_percentage' => $reviewCompletionRate,
            'validated' => $reviewed,
            'dc_visits_display' => sprintf('%d / %d', $dcVisitsCompleted, $dcVisitTarget),
            'dc_own_inspections' => $dcVisitsCompleted,
            'dc_visit_target' => $dcVisitTarget,
            'ac_visits_display' => sprintf('%d / %d', $acVisitsCompleted, $acVisitTarget),
            'ac_visit_target' => $acVisitTarget,
            'district_ac_visit_target' => $districtAcTarget,
            'required_inspections' => $requiredInspections,
            'required_visits' => $requiredInspections,
            'ac_visit_achievement' => $acVisitAchievement,
            'ac_visits_completed' => $acVisitsCompleted,
            'district_inspections' => $districtInspections,
            'district_visits' => $districtInspections,
            'districts_reporting' => $submissions->pluck('district_id')->filter()->unique()->count(),
            'school_council_meeting' => $councilMeetingsHeld,
            'school_council_meeting_target' => $councilMeetingScopeTarget,
            'observations' => $observations,
            'issues' => [],
            'tehsil_ids' => $tehsilIds,
            'district_ids' => $districtIds,
            'inspection_achievement_target' => $inspectionAchievementTarget,
            'inspection_achievement_completed' => $inspectionAchievementCompleted,
            'inspection_achievement_remaining' => $inspectionAchievementRemaining,
        ];
    }

    /** @return array<string, mixed> */
    private function healthVisitMetricContext(
        KpiCard $card,
        User $user,
        Request $request,
        Collection $submissions,
        float $operationalTarget,
        float $operationalCompleted,
    ): array {
        $inspections = $this->inspectionService->healthInspectionsForMetrics($card, $user, $request);
        $allInspections = $this->inspectionService->getInspectionsCollection($card, $user, $request);
        $role = $user->role?->slug ?? '';
        $facilitiesInspected = (int) max(0, round($operationalCompleted));
        $totalInventory = $this->inventoryTotalForUser($user, $card->slug, $submissions, 'total_health_facilities');
        $notInspected = max(0, $totalInventory - $facilitiesInspected);

        $reviewTarget = $this->inspectionService->healthReviewTarget($user, $inspections, $facilitiesInspected);
        $reviewCounts = $this->inspectionService->healthReviewStatusCounts($inspections, $reviewTarget);
        $approved = $reviewCounts['approved'];
        $pending = $reviewCounts['pending'];
        $rejected = $reviewCounts['rejected'];
        $reviewed = $approved + $rejected;
        $reviewCompletionRate = $reviewTarget > 0
            ? min(100.0, round(($reviewed / $reviewTarget) * 100, 1))
            : 0.0;

        $displayInspections = $this->healthObservationInspections($inspections, $facilitiesInspected);
        $observations = $this->observationCountsFromInspections($displayInspections);

        $acWeeklyTarget = 2;
        $dcWeeklyTarget = 2;
        $councilMeetingTarget = 2;
        $tehsilIds = $this->inspectionService->officialTehsilIds($user, $request);
        $districtIds = $this->inspectionService->officialDistrictIds($user, $request);
        $districtAcTarget = max($acWeeklyTarget, $tehsilIds->count() * $acWeeklyTarget);
        $districtDcTarget = max($dcWeeklyTarget, $districtIds->count() * $dcWeeklyTarget);
        $councilMeetingScopeTarget = match ($role) {
            'dc' => $councilMeetingTarget,
            'commissioner' => max($councilMeetingTarget, $districtIds->count() * $councilMeetingTarget),
            default => max($councilMeetingTarget, $districtIds->count() * $councilMeetingTarget),
        };

        $acVisitsCompleted = in_array($role, ['ac', 'field_user'], true)
            ? min($acWeeklyTarget, $facilitiesInspected)
            : $this->inspectionService->healthAcVisitsCompleted($allInspections, $tehsilIds);
        $acVisitTarget = in_array($role, ['ac', 'field_user'], true)
            ? $acWeeklyTarget
            : $districtAcTarget;
        $dcOwnInspections = $this->healthDcOwnInspectionCount($allInspections, $submissions, $dcWeeklyTarget);
        $dcVisitTarget = in_array($role, ['dc'], true)
            ? $dcWeeklyTarget
            : $districtDcTarget;
        $dcVisitsCompleted = in_array($role, ['dc'], true)
            ? $dcOwnInspections
            : $this->inspectionService->healthDcVisitsCompleted($allInspections, $districtIds);
        $councilMeetingsHeld = min(
            $councilMeetingScopeTarget,
            (int) $submissions->sum(fn ($s) => (float) data_get($s->metric_snapshot, 'health_council_meeting', 0))
        );

        $inspectionAchievementTarget = match ($role) {
            'dc' => $districtAcTarget + $dcWeeklyTarget,
            default => $districtAcTarget + $districtDcTarget,
        };
        $inspectionAchievementCompleted = min(
            $inspectionAchievementTarget,
            $acVisitsCompleted + $dcVisitsCompleted
        );
        $inspectionAchievementRemaining = max(0, $inspectionAchievementTarget - $inspectionAchievementCompleted);

        $requiredInspections = in_array($role, ['ac', 'field_user'], true) ? $acWeeklyTarget : $districtAcTarget;
        $acVisitAchievement = min(
            100.0,
            round(($acVisitsCompleted / max(1, $acVisitTarget)) * 100, 1)
        );
        $districtInspections = min($facilitiesInspected, $districtAcTarget + $dcWeeklyTarget);

        return [
            'total_visits' => $facilitiesInspected,
            'total_inspections' => $facilitiesInspected,
            'inspection_records' => $facilitiesInspected,
            'visits_achieved' => $facilitiesInspected,
            'unique_facilities_inspected' => $facilitiesInspected,
            'approved' => $approved,
            'pending' => $pending,
            'rejected' => $rejected,
            'total_inventory' => $totalInventory,
            'not_inspected' => $notInspected,
            'review_target' => $reviewTarget,
            'validation_target' => $reviewTarget,
            'review_completion_rate' => $reviewCompletionRate,
            'review_percentage' => $reviewCompletionRate,
            'validated' => $reviewed,
            'dc_visits_display' => sprintf('%d / %d', $dcVisitsCompleted, $dcVisitTarget),
            'dc_own_inspections' => $dcVisitsCompleted,
            'dc_visit_target' => $dcVisitTarget,
            'ac_visits_display' => sprintf('%d / %d', $acVisitsCompleted, $acVisitTarget),
            'ac_visit_target' => $acVisitTarget,
            'district_ac_visit_target' => $districtAcTarget,
            'required_inspections' => $requiredInspections,
            'required_visits' => $requiredInspections,
            'ac_visit_achievement' => $acVisitAchievement,
            'ac_visits_completed' => $acVisitsCompleted,
            'district_inspections' => $districtInspections,
            'district_visits' => $districtInspections,
            'districts_reporting' => $submissions->pluck('district_id')->filter()->unique()->count(),
            'health_council_meeting' => $councilMeetingsHeld,
            'health_council_meeting_target' => $councilMeetingScopeTarget,
            'observations' => $observations,
            'issues' => [],
            'tehsil_ids' => $tehsilIds,
            'district_ids' => $districtIds,
            'inspection_achievement_target' => $inspectionAchievementTarget,
            'inspection_achievement_completed' => $inspectionAchievementCompleted,
            'inspection_achievement_remaining' => $inspectionAchievementRemaining,
        ];
    }

    private function healthReviewPercentage(
        KpiCard $card,
        User $user,
        Request $request,
        float $facilitiesInspected,
    ): float {
        if ($facilitiesInspected <= 0) {
            return 0.0;
        }

        $inspections = $this->inspectionService->healthInspectionsForMetrics($card, $user, $request);
        $reviewTarget = $this->inspectionService->healthReviewTarget($user, $inspections, (int) round($facilitiesInspected));
        if ($reviewTarget <= 0) {
            return 0.0;
        }

        $reviewCounts = $this->inspectionService->healthReviewStatusCounts($inspections, $reviewTarget);
        $reviewed = $reviewCounts['approved'] + $reviewCounts['rejected'];

        return min(100.0, round(($reviewed / $reviewTarget) * 100, 1));
    }

    private function educationReviewPercentage(
        KpiCard $card,
        User $user,
        Request $request,
        float $institutionsInspected,
    ): float {
        if ($institutionsInspected <= 0) {
            return 0.0;
        }

        $inspections = $this->inspectionService->educationInspectionsForMetrics($card, $user, $request);
        $reviewTarget = $this->inspectionService->educationReviewTarget($user, $inspections, (int) round($institutionsInspected));
        if ($reviewTarget <= 0) {
            return 0.0;
        }

        $reviewCounts = $this->inspectionService->educationReviewStatusCounts($inspections, $reviewTarget);
        $reviewed = $reviewCounts['approved'] + $reviewCounts['rejected'];

        return min(100.0, round(($reviewed / $reviewTarget) * 100, 1));
    }

    /** @return array{title: string, positive: string, negative: string, detail_field: string, mode: string, helper?: string}|null */
    private function observationLabelsForField(string $field): ?array
    {
        if (in_array($field, EducationObservationLabels::observationMetricFields(), true)) {
            return EducationObservationLabels::meta($field);
        }

        if (in_array($field, HealthObservationLabels::observationMetricFields(), true)) {
            return HealthObservationLabels::meta($field);
        }

        return null;
    }

    private function healthObservationInspections(Collection $inspections, float $facilitiesInspected): Collection
    {
        // Health observation cards include every completed facility visit in scope:
        // approved, pending-review, and inspected-only (non-draft).
        return $inspections
            ->filter(fn (KpiInspection $inspection): bool => in_array(
                $inspection->status,
                [
                    KpiInspection::STATUS_APPROVED,
                    KpiInspection::STATUS_PENDING,
                    KpiInspection::STATUS_INSPECTED,
                ],
                true
            ))
            ->values();
    }

    /**
     * Education observation cards include every completed school visit in scope:
     * approved, pending-review, and inspected-only (non-draft).
     */
    private function educationObservationInspections(Collection $inspections): Collection
    {
        return $inspections
            ->filter(fn (KpiInspection $inspection): bool => in_array(
                $inspection->status,
                [
                    KpiInspection::STATUS_APPROVED,
                    KpiInspection::STATUS_PENDING,
                    KpiInspection::STATUS_INSPECTED,
                ],
                true
            ))
            ->values();
    }

    private function healthDcOwnInspectionCount(Collection $inspections, Collection $submissions, int $dcTarget): int
    {
        $fromSubmissions = (int) $submissions->sum(fn ($s) => (float) data_get($s->metric_snapshot, 'dc_visits', 0));
        if ($fromSubmissions > 0) {
            return min($dcTarget, $fromSubmissions);
        }

        $dcInspections = $inspections->filter(function ($inspection) {
            $role = $inspection->inspectedBy?->role?->slug;

            return in_array($role, ['dc', 'commissioner'], true);
        })->count();

        return min($dcTarget, max($dcInspections, 0));
    }

    /** @return array<string, mixed> */
    private function observationCountsFromInspections(Collection $inspections): array
    {
        $definitions = HealthObservationLabels::definitions();
        $counts = collect($definitions)->map(fn () => ['available' => 0, 'not_available' => 0, 'partial' => 0, 'not_recorded' => 0, 'not_applicable' => 0])->all();

        foreach ($inspections as $inspection) {
            $detail = is_array($inspection->detail_data)
                ? $inspection->detail_data
                : (json_decode($inspection->detail_data ?? '[]', true) ?: []);

            foreach ($definitions as $metric => $definition) {
                $value = $detail[$definition['detail_field']] ?? $this->legacyHealthObservationValue($detail, $definition['detail_field']);
                $outcome = HealthObservationLabels::outcome($value);
                $bucket = match ($outcome) { 'positive' => 'available', 'negative' => 'not_available', default => $outcome };
                $counts[$metric][$bucket]++;
            }
        }

        return $counts;
    }

    /** @return array<string, mixed> */
    private function educationObservationCountsFromInspections(Collection $inspections): array
    {
        $definitions = EducationObservationLabels::definitions();
        $counts = collect($definitions)->map(fn () => ['available' => 0, 'not_available' => 0, 'partial' => 0, 'not_recorded' => 0, 'not_applicable' => 0])->all();

        foreach ($inspections as $inspection) {
            $detail = is_array($inspection->detail_data)
                ? $inspection->detail_data
                : (json_decode($inspection->detail_data ?? '[]', true) ?: []);

            foreach ($definitions as $metric => $definition) {
                $value = $detail[$definition['detail_field']] ?? $this->legacyEducationObservationValue($detail, $definition['detail_field']);
                $outcome = EducationObservationLabels::outcome($value);
                $bucket = match ($outcome) { 'positive' => 'available', 'negative' => 'not_available', default => $outcome };
                $counts[$metric][$bucket]++;
            }
        }

        return $counts;
    }

    /** @param  array<string, mixed>  $detail */
    private function legacyEducationObservationValue(array $detail, string $field): string
    {
        return match ($field) {
            'cleanliness_available' => $this->isNegativeHealthSignal($detail['cleanliness'] ?? null) ? 'not_available' : 'available',
            'teachers_staff_available' => in_array($detail['teachers_present'] ?? 'Yes', ['No', 'Partial'], true) ? 'not_available' : 'available',
            'books_learning_material_available' => in_array($detail['tlm_availability'] ?? '', ['Shortage', 'Partial'], true) ? 'not_available' : 'available',
            'school_facilities_utilities_available' => in_array($detail['facility_deficiency'] ?? '', ['Major', 'Minor'], true) ? 'not_available' : 'available',
            'drinking_water_available' => ($detail['drinking_water_available'] ?? 'available') === 'not_available' ? 'not_available' : 'available',
            default => '',
        };
    }

    /** @param  array<string, mixed>  $detail */
    private function legacyHealthObservationValue(array $detail, string $field): string
    {
        return match ($field) {
            'deep_cleaning_available' => $this->isNegativeHealthSignal($detail['cleanliness'] ?? null) ? 'not_available' : 'available',
            'staff_available' => (($detail['staff_present'] ?? 'Yes') === 'No') ? 'not_available' : 'available',
            'medicine_flex_available' => (($detail['medicines_ok'] ?? 'Yes') === 'No') ? 'not_available' : 'available',
            'testing_equipment_available' => in_array($detail['equipment_status'] ?? '', ['Non-Operational', 'Partial'], true)
                || ($detail['equipment_ok'] ?? 'Yes') === 'No'
                ? 'not_available'
                : 'available',
            'drinking_water_available' => ($detail['drinking_water_available'] ?? ($detail['utilities_ok'] ?? 'Yes')) === 'No'
                ? 'not_available'
                : 'available',
            'utilities_available' => ($detail['utilities_ok'] ?? 'Yes') === 'No' ? 'not_available' : 'available',
            default => '',
        };
    }

    private function formatAvailabilityObservation(array $counts): string
    {
        return sprintf(
            'Available %d / Not %d',
            (int) ($counts['available'] ?? 0),
            (int) ($counts['not_available'] ?? 0),
        );
    }

    private function formatYesNoObservation(array $counts): string
    {
        return sprintf(
            'Yes %d / No %d',
            (int) ($counts['yes'] ?? 0),
            (int) ($counts['no'] ?? 0),
        );
    }

    private function inventoryTotalForUser(User $user, string $slug, Collection $submissions, string $field): int
    {
        $demo = match ($user->username) {
            'ac.lahore' => $slug === 'inspection-of-health-facilities' ? 48 : 156,
            'ac.layyah' => $slug === 'inspection-of-health-facilities' ? 20 : 20,
            'ac.karor' => $slug === 'inspection-of-health-facilities' ? 28 : 20,
            'dc.layyah' => $slug === 'inspection-of-health-facilities' ? 48 : 58,
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

        if ($request->has('period_type')) {
            $query = $request->query();

            if ($this->frequencyService->isDaily($card->slug)
                && $request->get('period_type') === 'daily'
                && ! $request->filled('date')) {
                $query['date'] = now()->toDateString();

                return $request->duplicate($query);
            }

            if ($this->frequencyService->isWeekly($card->slug)
                && $request->get('period_type') === 'weekly'
                && ! $request->filled('week_no')) {
                $completedWeek = $this->periodService->latestCompletedWeekNo();
                $completedRange = $this->periodService->getWeekDateRange($completedWeek);
                $anchor = $completedRange['end'] ?? now();
                $query['week_no'] = $completedWeek;
                $query['month'] = (string) $anchor->month;
                $query['year'] = (string) $anchor->year;
                $query['date'] = $anchor->toDateString();

                return $request->duplicate($query);
            }

            return $request;
        }

        return $request->duplicate(
            array_merge($request->query(), $defaults)
        );
    }

    private function hasExplicitDashboardFilter(Request $request): bool
    {
        foreach ([
            'period_type',
            'week_no',
            'month',
            'year',
            'date',
            'geo_division',
            'geo_district',
            'geo_tehsil',
            'geo_date_from',
            'geo_date_to',
        ] as $key) {
            if ($request->filled($key)) {
                return true;
            }
        }

        return false;
    }

    /** @param  list<array{type: string, title: string, key: string}>  $definitions */
    private function chartsForUser(array $definitions, User $user, string $slug): array
    {
        $role = $user->role?->slug;
        $slug = KpiDashboardDefinitions::normalizeSlug($slug);

        if ($slug === 'inspection-of-health-facilities') {
            return match ($role) {
                'ac', 'field_user' => [
                    ['type' => 'bar', 'title' => 'Review Target Status', 'subtitle' => 'Approved, rejected, and pending reviews against AC review target.', 'key' => 'health_review_target_status'],
                    ['type' => 'grouped_bar', 'title' => 'Health Facility Observation Status by Parameter', 'subtitle' => 'Positive and negative counts from inspected health facilities.', 'key' => 'health_observation_availability'],
                ],
                'dc' => [
                    ['type' => 'bar', 'title' => 'Tehsil Inspection Progress', 'subtitle' => 'Weekly AC inspections by tehsil, capped at 2 per tehsil.', 'key' => 'health_tehsil_inspection_progress'],
                    ['type' => 'bar', 'title' => 'Inspection Target Achievement', 'subtitle' => 'Completed inspections against weekly target.', 'key' => 'health_inspection_target_achievement'],
                    ['type' => 'bar', 'title' => 'Review Target Status', 'subtitle' => 'Approved, rejected, and pending reviews against DC review target.', 'key' => 'health_review_target_status'],
                    ['type' => 'grouped_bar', 'title' => 'Health Facility Observation Status by Parameter', 'subtitle' => 'Positive and negative counts from inspected health facilities.', 'key' => 'health_observation_availability'],
                ],
                'commissioner' => [
                    ['type' => 'bar', 'title' => 'District Inspection Progress', 'subtitle' => 'Completed inspections by district, capped against district target.', 'key' => 'health_district_inspection_progress'],
                    ['type' => 'bar', 'title' => 'Inspection Target Achievement', 'subtitle' => 'Completed inspections against division weekly target.', 'key' => 'health_inspection_target_achievement'],
                    ['type' => 'bar', 'title' => 'Review Target Status', 'subtitle' => 'Approved, rejected, and pending reviews against Commissioner review target.', 'key' => 'health_review_target_status'],
                    ['type' => 'grouped_bar', 'title' => 'Health Facility Observation Status by Parameter', 'subtitle' => 'Positive and negative counts from inspected health facilities.', 'key' => 'health_observation_availability'],
                ],
                default => [
                    ['type' => 'bar', 'title' => 'District Inspection Progress', 'subtitle' => 'Completed inspections by district, capped against weekly target.', 'key' => 'health_district_inspection_progress'],
                    ['type' => 'bar', 'title' => 'Inspection Target Achievement', 'subtitle' => 'Completed inspections against Punjab weekly target.', 'key' => 'health_inspection_target_achievement'],
                    ['type' => 'bar', 'title' => 'Review Target Status', 'subtitle' => 'Approved, rejected, and pending reviews against CS review target.', 'key' => 'health_review_target_status'],
                    ['type' => 'grouped_bar', 'title' => 'Health Facility Observation Status by Parameter', 'subtitle' => 'Positive and negative counts from inspected health facilities.', 'key' => 'health_observation_availability'],
                ],
            };
        }

        if ($slug === 'inspection-of-educational-institutions') {
            return match ($role) {
                'ac', 'field_user' => [
                    ['type' => 'bar', 'title' => 'Review Target Status', 'subtitle' => 'Approved, rejected, and pending reviews against AC review target.', 'key' => 'education_review_target_status'],
                    ['type' => 'grouped_bar', 'title' => 'School Observation Summary', 'subtitle' => 'Positive and negative findings from inspected schools.', 'key' => 'education_observation_availability'],
                ],
                'dc' => [
                    ['type' => 'bar', 'title' => 'Education Inspection Progress', 'subtitle' => 'Weekly AC inspections by tehsil, capped at 2 per tehsil.', 'key' => 'education_tehsil_inspection_progress'],
                    ['type' => 'bar', 'title' => 'Inspection Target Achievement', 'subtitle' => 'Completed inspections against weekly target.', 'key' => 'education_inspection_target_achievement'],
                    ['type' => 'bar', 'title' => 'Review Target Status', 'subtitle' => 'Approved, rejected, and pending reviews against DC review target.', 'key' => 'education_review_target_status'],
                    ['type' => 'grouped_bar', 'title' => 'School Observation Summary', 'subtitle' => 'Positive and negative findings from inspected schools.', 'key' => 'education_observation_availability'],
                ],
                'commissioner' => [
                    ['type' => 'bar', 'title' => 'District Inspection Progress', 'subtitle' => 'Completed inspections by district, capped against district target.', 'key' => 'education_district_inspection_progress'],
                    ['type' => 'bar', 'title' => 'Inspection Target Achievement', 'subtitle' => 'Completed inspections against division weekly target.', 'key' => 'education_inspection_target_achievement'],
                    ['type' => 'bar', 'title' => 'Review Target Status', 'subtitle' => 'Approved, rejected, and pending reviews against Commissioner review target.', 'key' => 'education_review_target_status'],
                    ['type' => 'grouped_bar', 'title' => 'School Observation Summary', 'subtitle' => 'Positive and negative findings from inspected schools.', 'key' => 'education_observation_availability'],
                ],
                default => [
                    ['type' => 'bar', 'title' => 'District Inspection Progress', 'subtitle' => 'Completed inspections by district, capped against weekly target.', 'key' => 'education_district_inspection_progress'],
                    ['type' => 'bar', 'title' => 'Inspection Target Achievement', 'subtitle' => 'Completed inspections against Punjab weekly target.', 'key' => 'education_inspection_target_achievement'],
                    ['type' => 'bar', 'title' => 'Review Target Status', 'subtitle' => 'Approved, rejected, and pending reviews against CS review target.', 'key' => 'education_review_target_status'],
                    ['type' => 'grouped_bar', 'title' => 'School Observation Summary', 'subtitle' => 'Positive and negative findings from inspected schools.', 'key' => 'education_observation_availability'],
                ],
            };
        }

        if ($slug === 'price-of-roti') {
            return [
                ['type' => 'line', 'title' => 'Daily Inspection Trend', 'subtitle' => 'Tandoor inspections recorded for the selected day.', 'key' => 'daily_inspections_trend'],
                ['type' => 'bar', 'title' => 'Violation Type Breakdown', 'subtitle' => 'Confirmed observation categories from inspected tandoors.', 'key' => 'violation_type_breakdown'],
                ['type' => 'bar', 'title' => 'Fine Recovery', 'subtitle' => 'Fines imposed versus fines collected.', 'key' => 'fine_recovery'],
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

        return $filtered->take(3)->all();
    }

    private function usesInspectionOperationalAchieved(string $slug): bool
    {
        return KpiFrequencyConfig::isOperationalInspectionKpi($slug);
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
            return Str::limit($formula, 42);
        }

        return null;
    }

    /** @param  array<string, int>  $inspectionStatusCounts */
    /** @param  array<string, mixed>  $visitContext @param  array<string, mixed>  $inspectionContext */
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
        array $inspectionContext = [],
    ): float|string|int|array {
        if ($cardSlug === 'price-of-roti' && $operational !== []) {
            $override = match ($field) {
                'tandoor_inspections' => $operational['completed'] ?? null,
                'inspections_total_target' => $operational['target'] ?? null,
                'achievement_rate' => $operational['pct'] ?? null,
                'over_price_violations' => $inspectionContext['obs_over_price'] ?? null,
                'under_weight_violations' => $inspectionContext['obs_under_weight'] ?? null,
                'violations_found' => $inspectionContext['violations_found'] ?? null,
                default => null,
            };
            if ($override !== null) {
                return $override;
            }
        }

        if ($visitContext !== []) {
            $issues = $visitContext['issues'] ?? [];
            $observations = $visitContext['observations'] ?? [];

            return match ($field) {
                'target_completed' => (float) ($operational['completed'] ?? 0),
                'total_visits', 'inspection_records', 'total_inspections' => (int) ($visitContext['total_inspections'] ?? $visitContext['inspection_records']),
                'visits_achieved' => (int) $visitContext['visits_achieved'],
                'facilities_inspected', 'institutions_inspected' => (int) $visitContext['unique_facilities_inspected'],
                'facilities_not_inspected' => (int) $visitContext['not_inspected'],
                'institutions_not_inspected' => (int) $visitContext['not_inspected'],
                'total_health_facilities' => (int) $visitContext['total_inventory'],
                'total_institutions' => (int) $visitContext['total_inventory'],
                'inspections_pending' => (int) $visitContext['pending'],
                'inspections_approved' => (int) $visitContext['approved'],
                'inspections_rejected' => (int) $visitContext['rejected'],
                'review_target', 'validation_target' => (int) ($visitContext['review_target'] ?? $visitContext['validation_target'] ?? 0),
                'review_completion_rate' => (float) ($visitContext['review_completion_rate'] ?? 0),
                'validations_completed', 'validated_inspections' => (int) $visitContext['validated'],
                'required_inspections', 'required_visits' => (int) ($visitContext['required_inspections'] ?? $visitContext['required_visits'] ?? 2),
                'dc_own_inspections' => $visitContext['dc_visits_display'] ?? (int) ($visitContext['dc_own_inspections'] ?? 0),
                'health_council_meeting' => sprintf(
                    '%d / %d',
                    (int) ($visitContext['health_council_meeting'] ?? 0),
                    (int) ($visitContext['health_council_meeting_target'] ?? 2)
                ),
                'school_council_meeting' => sprintf(
                    '%d / %d',
                    (int) ($visitContext['school_council_meeting'] ?? 0),
                    (int) ($visitContext['school_council_meeting_target'] ?? 2)
                ),
                'district_inspections' => (int) ($visitContext['district_inspections'] ?? $visitContext['district_visits'] ?? 0),
                'dc_visits' => $visitContext['dc_visits_display'],
                'ac_visits' => $visitContext['ac_visits_display'],
                'ac_visit_target' => (int) ($visitContext['ac_visit_target'] ?? 2),
                'district_ac_visit_target' => (int) ($visitContext['district_ac_visit_target'] ?? 2),
                'ac_visit_achievement' => (float) ($visitContext['ac_visit_achievement'] ?? 0),
                'district_visits' => (int) ($visitContext['district_visits'] ?? 0),
                'districts_reporting' => (int) ($visitContext['districts_reporting'] ?? 0),
                'observation_deep_cleaning', 'observation_staff_availability', 'observation_medicines_availability',
                'observation_medicine_flex', 'observation_medicine_led', 'observation_diagnostic_services',
                'observation_uhi_compliance', 'observation_utilities', 'observation_drinking_water',
                'observation_school_premises', 'observation_classroom_cleanliness', 'observation_teachers_staff',
                'observation_teacher_dress', 'observation_learning_material', 'observation_electricity_facilities',
                'observation_toilets', 'observation_boundary_wall', 'observation_playground'
                    => $observations[$field] ?? ['available' => 0, 'not_available' => 0, 'partial' => 0, 'not_recorded' => 0, 'not_applicable' => 0],
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

        if ($inspectionContext !== [] && array_key_exists($field, $inspectionContext)) {
            $value = $inspectionContext[$field];

            if (is_array($value)) {
                return $value;
            }

            if (is_string($value) && ! is_numeric($value)) {
                return $value;
            }

            return $this->roundMetricValue((float) $value);
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
            'over_price_violations', 'under_weight_violations', 'non_availability_violations' => (int) round($this->metricFieldSum($submissions, $allValues, $field)),
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
                'review_completion_rate',
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
