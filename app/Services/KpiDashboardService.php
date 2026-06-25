<?php

namespace App\Services;

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
        [$submissions, $usedFallback] = $this->loadSubmissions($card, $user, $request);

        $perPage = min(50, max(10, (int) $request->input('per_page', 15)));

        $tableSubmissions = $this->paginateSubmissions($card, $user, $request, $perPage, $usedFallback);

        $headerMetrics = $this->resolveOperationalHeader($card, $submissions, $user, $request, periodTotals: true);
        $headerLabels = $this->dashboardConfig->headerLabelsFor($card->slug);
        $areaScores = $this->areaScores($submissions, $user);

        $kpiConfig = $this->dashboardConfig->forKpi($card->slug);
        $chartDefinitions = $kpiConfig['charts'];
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
                $headerMetrics['operational_target'],
                $headerMetrics['completed'],
                $headerMetrics['achievement_percentage'],
                $inspectionStatusCounts
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
            'filters' => $this->filterOptionsForView(),
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

    public function filterOptionsForView(): array
    {
        return $this->periodService->filterOptions();
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

        if ($periodTotals) {
            $operational = $this->operationalService->totals(
                $card,
                $submissions,
                $user,
                $request,
                $fields,
                $inspectionAchieved,
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

        // Records = submitted KPI reports only (not summed activity-volume fields).
        $records = $submissions->count();
        $inspectionsCount = $this->inspectionService->countScopedInspections($card, $user, $request);

        $pct = $this->resolveAchievementPct($submissions, $completed, $operationalTarget);
        $score = $this->formula->scoreFromWeightage($pct, $marks);

        return [
            'operational_target' => $operationalTarget,
            'completed' => $completed,
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
    private function metrics(KpiCard $card, Collection $submissions, float $target, float $achieved, float $pct, array $inspectionStatusCounts): Collection
    {
        $configured = collect($this->dashboardConfig->dashboardStatsFor($card->slug));
        $allValues = $submissions->flatMap->values;

        return $configured->map(function (array $metric) use ($allValues, $submissions, $inspectionStatusCounts, $pct) {
            $value = $this->resolveMetricValue($metric['field'], $submissions, $allValues, $inspectionStatusCounts, $pct);

            return array_merge($metric, [
                'value' => $value,
                'hint' => $this->shortCardHint($metric),
            ]);
        });
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
    private function resolveMetricValue(string $field, Collection $submissions, Collection $allValues, array $inspectionStatusCounts, float $pct): float|string|int
    {
        return match ($field) {
            'achievement_rate' => round($this->metricFieldSum($submissions, $allValues, $field) ?: $pct, 1),
            'complaint_resolution_rate', 'plant_coverage_rate', 'filter_change_rate',
            'resolution_rate', 'compliance_rate', 'fine_imposition_rate', 'dc_visit_completion', 'ac_visit_completion'
                => round($this->metricFieldSum($submissions, $allValues, $field), 1),
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
            ->map(fn ($group) => round($group->avg(fn ($item) => (float) ($item->achievement_percentage ?? $item->kpiScore?->percentage ?? $item->score)), 1));

        if ($scores->isNotEmpty()) {
            return $scores;
        }

        return $submissions
            ->groupBy(fn ($item) => $item->submission_date?->format('d M') ?? 'Period')
            ->map(fn ($group) => round($group->avg(fn ($item) => (float) ($item->achievement_percentage ?? 0)), 1));
    }
}
