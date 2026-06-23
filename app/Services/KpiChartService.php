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
            ->groupBy(fn ($item) => $item->submission_date->format('d M Y'))
            ->map(fn ($group) => round($group->avg(fn ($item) => (float) ($item->achievement_percentage ?? $item->kpiScore?->percentage ?? $item->score)), 1));

        if ($trend->isEmpty()) {
            $trend = collect([now()->format('d M Y') => $pct]);
        }

        $donut = $this->formula->donutSplit(
            $totalAchieved ?: $achieved,
            $totalPending ?: max(0, $target - $achieved)
        );

        return [
            'status' => $statusCounts->isNotEmpty() ? $statusCounts : collect(['approved' => $submissions->count() ?: 1]),
            'donut' => collect($donut),
            'areas' => $areaScores->isNotEmpty() ? $areaScores->sortDesc()->take(12) : collect(['No area data' => $pct]),
            'trend' => $trend,
            'target_achieved' => collect([
                'Target' => round($target, 1),
                'Achieved' => round($achieved, 1),
            ]),
        ];
    }
}
