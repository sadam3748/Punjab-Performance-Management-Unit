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
        private readonly KpiMetricConfigService $metricConfig,
        private readonly KpiFormulaService $formula,
        private readonly KpiChartService $chartService,
        private readonly KpiInspectionService $inspectionService,
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
            ->withCount([
                'submissions as total_count' => fn (Builder $q) => $this->filteredSubmissions($q, $user, $request),
                'submissions as submitted_count' => fn (Builder $q) => $this->filteredSubmissions($q, $user, $request)->where('status', 'submitted'),
                'submissions as pending_count' => fn (Builder $q) => $this->filteredSubmissions($q, $user, $request)->whereIn('status', ['draft', 'pending']),
            ])
            ->withAvg(['submissions as achieved_avg' => fn (Builder $q) => $this->filteredSubmissions($q, $user, $request)], 'achieved_value')
            ->withAvg(['submissions as pct_avg' => fn (Builder $q) => $this->filteredSubmissions($q, $user, $request)], 'achievement_percentage')
            ->withSum(['submissions as reported_sum' => fn (Builder $q) => $this->filteredSubmissions($q, $user, $request)], 'reported_value')
            ->orderBy('display_order')
            ->get()
            ->map(function (KpiCard $card) {
                $target = (float) $card->total_marks;
                $achieved = round((float) ($card->achieved_avg ?? 0), 1);
                $reported = (int) ($card->reported_sum ?? 0);
                $pct = round((float) ($card->pct_avg ?? 0), 1);
                if ($pct <= 0 && $target > 0 && $achieved > 0) {
                    $pct = $this->formula->achievementPercentage($achieved, $target);
                }

                $card->target = $target;
                $card->reported = $reported;
                $card->achieved = $achieved;
                $card->achievement_percentage = $pct;
                $card->status_label = $this->formula->performanceLabel($pct);

                return $card;
            });
    }

    public function canAccess(User $user, KpiCard $card): bool
    {
        return $user->role?->slug === 'super_admin' || $this->assignedCards($user)->contains('id', $card->id);
    }

    public function detail(KpiCard $card, User $user, Request $request): array
    {
        $baseQuery = $this->filteredSubmissions(
            KpiSubmission::query()->where('kpi_card_id', $card->id),
            $user,
            $request
        );

        $submissions = (clone $baseQuery)
            ->with(['user:id,name', 'division:id,name', 'district:id,name', 'tehsil:id,name', 'values.field', 'kpiScore'])
            ->latest('submission_date')
            ->get();

        $perPage = min(50, max(10, (int) $request->input('per_page', 15)));

        $tableSubmissions = (clone $baseQuery)
            ->with(['user:id,name', 'division:id,name', 'district:id,name', 'tehsil:id,name', 'values.field', 'kpiScore'])
            ->latest('submission_date')
            ->paginate($perPage)
            ->withQueryString();

        $target = (float) $card->total_marks;
        $reported = (int) $submissions->sum(fn ($s) => (float) ($s->reported_value ?? 1));
        $achieved = round((float) $submissions->avg(fn ($s) => (float) ($s->achieved_value ?? $s->score)), 1);
        $pending = round((float) $submissions->sum(fn ($s) => (float) ($s->pending_value ?? 0)), 1);
        $pct = $this->formula->achievementPercentage($achieved, $target);
        $score = $this->formula->scoreFromWeightage($pct, (float) $card->total_marks);
        $areaScores = $this->areaScores($submissions, $user);

        return [
            'submissions' => $submissions,
            'tableSubmissions' => $tableSubmissions,
            'summary' => [
                'total' => $submissions->count(),
                'approved' => $submissions->where('status', 'approved')->count(),
                'submitted' => $submissions->where('status', 'submitted')->count(),
                'reported' => $reported,
                'pending' => $pending,
                'rejected' => $submissions->where('status', 'rejected')->count(),
                'target' => $target,
                'achieved' => $achieved,
                'achievement_percentage' => $pct,
                'score' => $score,
                'status_label' => $this->formula->performanceLabel($pct),
                'best_area' => $areaScores->sortDesc()->keys()->first() ?: '—',
                'weak_area' => $areaScores->sort()->keys()->first() ?: '—',
            ],
            'header' => [
                'target' => $target,
                'reported' => $reported,
                'achieved' => $achieved,
                'pending' => $pending,
                'score' => $score,
                'achievement_percentage' => $pct,
                'status_label' => $this->formula->performanceLabel($pct),
                'period_label' => $this->periodService->label($request),
                'area_level' => $this->scopeService->areaLevel($user),
                'scope_label' => $this->scopeService->locationLabel($user),
            ],
            'metrics' => $this->metrics($card, $submissions, $target, $achieved, $pct),
            'charts' => $this->chartService->build($submissions, $user, $target, $achieved, $areaScores),
            'filters' => $this->filterOptionsForView(),
            'period' => $this->periodState($request),
            'period_description' => $this->periodService->description($request),
            'inspectionRecords' => $this->inspectionService->getInspectionListForKpi($card, $user, $request),
            'inspectionStatusCounts' => $this->inspectionService->buildStatusCounts($card, $user, $request),
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
        return $this->periodService->applyToQuery($this->scopeService->apply($query, $user), $request);
    }

    private function metrics(KpiCard $card, Collection $submissions, float $target, float $achieved, float $pct): Collection
    {
        $configured = collect($this->metricConfig->cardsFor($card->slug) ?: ($card->metric_config ?? []));
        $allValues = $submissions->flatMap->values;

        $metrics = $configured->map(function ($metric) use ($allValues) {
            $fieldValues = $allValues->filter(fn ($v) => $v->field?->field_name === $metric['field']);

            return array_merge($metric, [
                'value' => round($fieldValues->sum(fn ($v) => (float) $v->value), 1),
                'hint' => $metric['hint'] ?? $this->metricHint($metric['label']),
            ]);
        });

        $metrics->push(
            ['label' => 'Target', 'field' => 'target', 'icon' => 'bi-bullseye', 'tone' => 'purple', 'value' => $target, 'hint' => 'Period performance goal'],
            ['label' => 'Achieved', 'field' => 'achieved', 'icon' => 'bi-graph-up-arrow', 'tone' => 'green', 'value' => $achieved, 'hint' => 'Reported achievement total'],
            ['label' => 'Score', 'field' => 'score', 'icon' => 'bi-award', 'tone' => 'blue', 'value' => $this->formula->scoreFromWeightage($pct, $target), 'hint' => 'Weighted KPI score'],
        );

        return $metrics;
    }

    private function metricHint(string $label): string
    {
        $key = strtolower($label);

        return match (true) {
            str_contains($key, 'compliance') || str_contains($key, '%') => 'Compliance percentage',
            str_contains($key, 'target') => 'Assigned period target',
            str_contains($key, 'achieved') || str_contains($key, 'resolved') || str_contains($key, 'completed') => 'Completed in selected period',
            str_contains($key, 'pending') => 'Outstanding items',
            str_contains($key, 'visit') => 'Official field visits',
            str_contains($key, 'inspection') || str_contains($key, 'inspected') => 'Inspection activity count',
            str_contains($key, 'report') => 'Reports submitted',
            str_contains($key, 'fine') || str_contains($key, 'violation') => 'Enforcement actions',
            str_contains($key, 'functional') || str_contains($key, 'clean') => 'Operational status count',
            str_contains($key, 'non-functional') || str_contains($key, 'open') => 'Issues requiring action',
            str_contains($key, 'total') || str_contains($key, 'plants') || str_contains($key, 'roads') => 'Baseline operational count',
            str_contains($key, 'meeting') || str_contains($key, 'council') => 'Governance meetings held',
            str_contains($key, 'mobility') || str_contains($key, 'index') => 'Coverage mobility index',
            str_contains($key, 'complaint') => 'Citizen complaint actions',
            str_contains($key, 'tier') => 'Tier-wise target value',
            default => 'Operational indicator value',
        };
    }

    private function areaScores(Collection $submissions, User $user): Collection
    {
        $relation = $this->scopeService->comparisonRelation($user);

        return $submissions
            ->filter(fn ($item) => $item->{$relation})
            ->groupBy(fn ($item) => $item->{$relation}->name)
            ->map(fn ($group) => round($group->avg(fn ($item) => (float) ($item->achievement_percentage ?? $item->kpiScore?->percentage ?? $item->score)), 1));
    }
}
