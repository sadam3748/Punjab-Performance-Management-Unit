<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;

class KpiChartService
{
    public function __construct(private readonly KpiFormulaService $formula) {}

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

        $comparisonLabel = match ($user->role?->slug) {
            'ac', 'field_user' => 'Tehsil performance trend',
            'dc' => 'Tehsil comparison',
            'commissioner' => 'District comparison',
            default => 'Division / district comparison',
        };

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
}
