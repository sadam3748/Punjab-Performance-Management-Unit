<?php

namespace App\Services;

class KpiFormulaService
{
    public function achievementPercentage(float $achieved, float $target): float
    {
        if ($target <= 0) {
            return 0;
        }

        return round(min(100, ($achieved / $target) * 100), 1);
    }

    public function scoreFromWeightage(float $achievedPercentage, float $weightage): float
    {
        return round(($achievedPercentage / 100) * $weightage, 2);
    }

    public function pending(float $target, float $reported, float $achieved): float
    {
        return max(0, round($target - $achieved, 1));
    }

    public function performanceLabel(float $percentage): string
    {
        return match (true) {
            $percentage >= 85 => 'excellent',
            $percentage >= 70 => 'good',
            $percentage >= 50 => 'attention',
            default => 'critical',
        };
    }

    public function donutSplit(float $achieved, float $pending): array
    {
        return [
            'Achieved' => round(max(0, $achieved), 1),
            'Pending' => round(max(0, $pending), 1),
        ];
    }
}
