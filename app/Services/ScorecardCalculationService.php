<?php

namespace App\Services;

use App\Models\DistrictKpiScore;
use Illuminate\Support\Facades\DB;

class ScorecardCalculationService
{
    public function calculateParameterScore(
        float $reportedValue,
        ?float $targetValue,
        float $weightage,
        string $method = 'percentage',
        bool $higherIsBetter = true
    ): array {
        $reportedValue = max(0, $reportedValue);
        $weightage = max(0, $weightage);

        $achieved = 0.0;
        $score = 0.0;

        if ($method === 'direct_score') {
            $score = min($reportedValue, $weightage);
            $achieved = $weightage > 0 ? round(($score / $weightage) * 100, 2) : 0;
        } elseif ($method === 'yes_no') {
            $score = $reportedValue >= 1 ? $weightage : 0;
            $achieved = $reportedValue >= 1 ? 100 : 0;
        } elseif ($method === 'inverse_percentage') {
            // Lower is better (e.g., response time). If target is 0, treat as perfect only when reported is 0.
            if ($targetValue === null || (float) $targetValue <= 0) {
                $achieved = $reportedValue <= 0 ? 100 : 0;
            } else {
                // Reward values <= target as 100; beyond target decreases.
                $ratio = $reportedValue <= 0 ? 1 : ($targetValue / $reportedValue);
                $achieved = max(0, min(100, round($ratio * 100, 2)));
            }
            $score = round(($achieved / 100) * $weightage, 2);
        } else { // percentage
            if ($targetValue === null || (float) $targetValue <= 0) {
                $achieved = 0;
            } else {
                $achieved = ($reportedValue / $targetValue) * 100;
                if (! $higherIsBetter) {
                    // If lower is better in percentage method, invert.
                    $achieved = 100 - $achieved;
                }
                $achieved = max(0, min(100, round($achieved, 2)));
            }
            $score = round(($achieved / 100) * $weightage, 2);
        }

        return [
            'achieved_percentage' => round($achieved, 2),
            'score_obtained'      => round($score, 2),
        ];
    }

    public function getGradeMeta(float $score): array
    {
        if ($score >= 90) {
            return ['grade' => 'A+', 'label' => 'Excellent', 'badge_class' => 'achieved'];
        }
        if ($score >= 80) {
            return ['grade' => 'A', 'label' => 'Good', 'badge_class' => 'achieved'];
        }
        if ($score >= 70) {
            return ['grade' => 'B', 'label' => 'Good', 'badge_class' => 'info'];
        }
        if ($score >= 60) {
            return ['grade' => 'C', 'label' => 'Average', 'badge_class' => 'info'];
        }
        if ($score >= 50) {
            return ['grade' => 'D', 'label' => 'Average', 'badge_class' => 'pending'];
        }

        return ['grade' => 'E', 'label' => 'Critical', 'badge_class' => 'critical'];
    }

    public function calculateDistrictKpiFinalScore(DistrictKpiScore $score): DistrictKpiScore
    {
        $reportedScore = (float) $score->details()->sum('score_obtained');
        $penaltyScore = (float) $score->penalties()->sum('penalty_score');

        $verified = (float) ($score->verified_score ?? 0);
        $calculationType = $score->calculation_type ?? 'general';

        if ($calculationType === 'sixty_forty') {
            $final = ($reportedScore * 0.60) + ($verified * 0.40) - $penaltyScore;
        } else { // general, negative_marking
            $final = $reportedScore - $penaltyScore;
        }

        $final = max(0, min(100, round($final, 2)));
        $reportedScore = max(0, min(100, round($reportedScore, 2)));
        $penaltyScore = max(0, round($penaltyScore, 2));

        $meta = $this->getGradeMeta($final);

        $score->reported_score = $reportedScore;
        $score->penalty_score = $penaltyScore;
        $score->final_score = $final;
        $score->grade = $meta['grade'];
        $score->performance_label = $meta['label'];

        $score->save();

        return $score;
    }

    public function recalculateAllScores(): void
    {
        DistrictKpiScore::query()
            ->where('is_active', true)
            ->chunkById(200, function ($scores) {
                foreach ($scores as $score) {
                    $this->calculateDistrictKpiFinalScore($score);
                }
            });
    }
}

