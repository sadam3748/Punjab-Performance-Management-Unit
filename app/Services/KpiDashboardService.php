<?php

namespace App\Services;

use App\Models\District;
use App\Models\Division;
use App\Models\KpiCard;
use App\Models\KpiSubmission;
use App\Models\Tehsil;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class KpiDashboardService
{
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
                'submissions as total_count' => fn (Builder $q) => $this->applyPeriodFilters($this->scope($q, $user), $request),
                'submissions as submitted_count' => fn (Builder $q) => $this->applyPeriodFilters($this->scope($q, $user), $request)->where('status', 'submitted'),
                'submissions as pending_count' => fn (Builder $q) => $this->applyPeriodFilters($this->scope($q, $user), $request)->whereIn('status', ['draft', 'pending']),
                'submissions as approved_count' => fn (Builder $q) => $this->applyPeriodFilters($this->scope($q, $user), $request)->where('status', 'approved'),
            ])
            ->withAvg(['scores as performance' => function (Builder $q) use ($user, $request) {
                $this->scope($q, $user);
                $q->whereHas('submission', fn (Builder $sub) => $this->applyPeriodFilters($sub, $request));
            }], 'percentage')
            ->withMax(['submissions as last_updated_at' => fn (Builder $q) => $this->applyPeriodFilters($this->scope($q, $user), $request)], 'updated_at')
            ->withAvg(['submissions as achieved_avg' => fn (Builder $q) => $this->applyPeriodFilters($this->scope($q, $user), $request)], 'score')
            ->orderBy('display_order')
            ->get()
            ->map(function (KpiCard $card) {
                $target = (float) $card->total_marks;
                $achieved = round((float) ($card->achieved_avg ?? 0), 1);
                $achievementPercentage = $target > 0 ? round(min(100, ($achieved / $target) * 100), 1) : 0;

                $card->target = $target;
                $card->achieved = $achieved;
                $card->achievement_percentage = $achievementPercentage;
                $card->status_label = $this->performanceLabel($achievementPercentage);

                return $card;
            });
    }

    public function canAccess(User $user, KpiCard $card): bool
    {
        return $user->role?->slug === 'super_admin' || $this->assignedCards($user)->contains('id', $card->id);
    }

    public function detail(KpiCard $card, User $user, Request $request): array
    {
        $query = $this->scope(KpiSubmission::query()->where('kpi_card_id', $card->id), $user);
        $this->applyPeriodFilters($query, $request);

        $submissions = $query
            ->with(['user:id,name', 'division:id,name', 'district:id,name', 'tehsil:id,name', 'values.field', 'kpiScore'])
            ->latest('submission_date')
            ->get();

        $scores = $submissions->pluck('kpiScore')->filter();
        $statusCounts = $submissions->countBy('status');
        $areaScores = $this->areaScores($submissions, $user);

        $target = (float) $card->total_marks;
        $achieved = round((float) $submissions->avg('score'), 1);
        $achievementPercentage = $target > 0 ? round(min(100, ($achieved / $target) * 100), 1) : 0;

        $trend = $submissions
            ->filter(fn ($item) => $item->kpiScore)
            ->groupBy(fn ($item) => $item->submission_date->format('M Y'))
            ->map(fn ($group) => round($group->avg(fn ($item) => (float) ($item->kpiScore?->percentage ?? $item->score)), 1));

        return [
            'submissions' => $submissions,
            'summary' => [
                'total' => $submissions->count(),
                'submitted' => $statusCounts->get('submitted', 0),
                'pending' => $statusCounts->get('pending', 0) + $statusCounts->get('draft', 0),
                'approved' => $statusCounts->get('approved', 0),
                'rejected' => $statusCounts->get('rejected', 0),
                'score' => round((float) $scores->avg('percentage'), 1) ?: $achievementPercentage,
                'target' => $target,
                'achieved' => $achieved,
                'achievement_percentage' => $achievementPercentage,
                'status_label' => $this->performanceLabel($achievementPercentage),
                'best_area' => $areaScores->sortDesc()->keys()->first() ?: 'No data',
                'weak_area' => $areaScores->sort()->keys()->first() ?: 'No data',
            ],
            'header' => [
                'target' => $target,
                'achieved' => $achieved,
                'achievement_percentage' => $achievementPercentage,
                'status_label' => $this->performanceLabel($achievementPercentage),
                'period_label' => $this->periodLabel($request),
            ],
            'metrics' => $this->metrics($card, $submissions, $target, $achieved, $achievementPercentage),
            'charts' => [
                'status' => $statusCounts->isNotEmpty() ? $statusCounts : collect(['approved' => 1]),
                'areas' => $areaScores->isNotEmpty() ? $areaScores->sortDesc()->take(12) : collect(['No data' => $achievementPercentage]),
                'trend' => $trend->isNotEmpty() ? $trend : collect([now()->format('M Y') => $achievementPercentage]),
                'target_achieved' => collect([
                    'Target' => $target,
                    'Achieved' => $achieved,
                ]),
            ],
            'filters' => $this->filterOptionsForView(),
            'period' => $this->periodState($request),
        ];
    }

    public function scope(Builder $query, User $user): Builder
    {
        return match ($user->role?->slug) {
            'commissioner' => $query->where('division_id', $user->division_id),
            'dc' => $query->where('district_id', $user->district_id),
            'ac', 'field_user' => $query->where('tehsil_id', $user->tehsil_id),
            default => $query,
        };
    }

    public function applyPeriodFilters(Builder $query, Request $request): Builder
    {
        $query->when($request->filled('period_type'), fn ($q) => $q->where('period_type', $request->period_type));

        if ($request->filled('year')) {
            $year = (int) $request->year;
            $query->whereYear('submission_date', $year);

            if ($request->filled('month')) {
                $query->whereMonth('submission_date', (int) $request->month);
            }
        } elseif ($request->filled('month')) {
            $query->whereMonth('submission_date', (int) $request->month)
                ->whereYear('submission_date', (int) ($request->year ?: now()->year));
        }

        return $query;
    }

    public function filterOptionsForView(): array
    {
        return [
            'months' => collect(range(1, 12))->mapWithKeys(fn ($m) => [$m => Carbon::create(null, $m)->format('F')]),
            'years' => collect(range(now()->year - 2, now()->year))->reverse()->values(),
            'period_types' => ['daily', 'weekly', 'monthly', 'yearly'],
        ];
    }

    public function periodState(Request $request): array
    {
        return [
            'period_type' => $request->get('period_type', ''),
            'month' => $request->get('month', ''),
            'year' => $request->get('year', now()->year),
        ];
    }

    private function filterOptions(): array
    {
        return $this->filterOptionsForView();
    }

    private function periodLabel(Request $request): string
    {
        $parts = [];

        if ($request->filled('period_type')) {
            $parts[] = ucfirst($request->period_type);
        }

        if ($request->filled('month')) {
            $parts[] = Carbon::create(null, (int) $request->month)->format('F');
        }

        if ($request->filled('year')) {
            $parts[] = $request->year;
        }

        return $parts ? implode(' · ', $parts) : 'All periods';
    }

    private function performanceLabel(float $percentage): string
    {
        return match (true) {
            $percentage >= 85 => 'excellent',
            $percentage >= 70 => 'good',
            $percentage >= 50 => 'needs attention',
            default => 'critical',
        };
    }

    private function metrics(KpiCard $card, Collection $submissions, float $target, float $achieved, float $achievementPercentage): Collection
    {
        $configured = collect($card->metric_config ?: []);

        $metrics = $configured->map(function ($metric) use ($submissions) {
            $values = $submissions->flatMap->values->filter(fn ($value) => $value->field?->field_name === $metric['field']);

            return $metric + ['value' => round($values->sum(fn ($value) => (float) $value->value), 1)];
        });

        $metrics->push(
            ['label' => 'Target', 'field' => 'target', 'icon' => 'bi-bullseye', 'value' => $target],
            ['label' => 'Achieved', 'field' => 'achieved', 'icon' => 'bi-graph-up-arrow', 'value' => $achieved],
            ['label' => 'Achievement %', 'field' => 'achievement_percentage', 'icon' => 'bi-percent', 'value' => $achievementPercentage],
        );

        return $metrics;
    }

    private function areaScores(Collection $submissions, User $user): Collection
    {
        $relation = match ($user->role?->slug) {
            'commissioner' => 'district',
            'dc' => 'tehsil',
            'ac', 'field_user' => 'tehsil',
            default => 'division',
        };

        return $submissions
            ->filter(fn ($item) => $item->{$relation})
            ->groupBy(fn ($item) => $item->{$relation}->name)
            ->map(fn ($group) => round($group->avg(fn ($item) => (float) ($item->kpiScore?->percentage ?? $item->score)), 1));
    }
}
