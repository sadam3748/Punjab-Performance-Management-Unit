<?php

namespace App\Services;

class KpiFormulaService
{
    public function displayPercentage(float $rawPercentage): float
    {
        return round(max(0, min(100, $rawPercentage)), 1);
    }

    public function percentage(float $numerator, float $denominator): float
    {
        if ($denominator <= 0) {
            return 0.0;
        }

        return $this->displayPercentage(($numerator / $denominator) * 100);
    }

    public function achievementPercentage(float $achieved, float $target): float
    {
        return $this->percentage($achieved, $target);
    }

    public function scoreFromWeightage(float $achievedPercentage, float $weightage): float
    {
        return round(($this->displayPercentage($achievedPercentage) / 100) * $weightage, 2);
    }

    public function pending(float $target, float $reported, float $achieved): float
    {
        return max(0, round($target - $achieved, 1));
    }

    public function performanceLabel(float $percentage): string
    {
        $percentage = $this->displayPercentage($percentage);

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
