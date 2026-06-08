<?php

namespace App\Services;

use App\Models\District;
use App\Models\DistrictKpiScore;
use App\Models\DistrictKpiScoreDetail;
use App\Models\KpiScoringParameter;
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

        // Direct and inverse remain internal/future-safe; no active PPT parameter uses them.
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
        } else { // percentage, mobility_index, amount_deposit_ratio, resolved_ratio
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

    /**
     * Main KPI weight comes from kpi_categories; sub-KPI weight and formula come
     * from kpi_scoring_parameters. Actual numerator/denominator values are saved
     * in district_kpi_score_details and are always scored through this service.
     */
    public function calculateForParameter(
        KpiScoringParameter $parameter,
        District $district,
        float $numerator,
        ?float $denominator = null,
        ?float $reportedScore = null,
        array $context = []
    ): array {
        $formulaType = (string) ($parameter->formula_type ?: $parameter->scoring_method ?: 'percentage');
        $target = $this->resolveParameterTarget(
            $parameter,
            $district,
            ['denominator' => $denominator],
            $context
        );
        $reportedValue = $formulaType === 'direct_score'
            ? (float) ($reportedScore ?? $numerator)
            : $numerator;

        return [
            ...$this->calculateParameterScore(
                $reportedValue,
                $target,
                (float) $parameter->weightage,
                $formulaType,
                (bool) $parameter->higher_is_better
            ),
            'target_value' => $target,
            'formula_type' => $formulaType,
        ];
    }

    public function resolveTargetForDistrict(
        KpiScoringParameter $parameter,
        District $district,
        ?float $denominator = null
    ): ?float {
        return $this->resolveParameterTarget($parameter, $district, ['denominator' => $denominator]);
    }

    public function resolveParameterTarget(
        KpiScoringParameter $parameter,
        District $district,
        array $input = [],
        array $context = []
    ): ?float {
        if (array_key_exists('denominator', $input) && $input['denominator'] !== null) {
            return max(0, (float) $input['denominator']);
        }

        $tier = (int) ($district->tier ?? 0);
        $tierTarget = match ($tier) {
            1 => $parameter->tier_1_target,
            2 => $parameter->tier_2_target,
            3 => $parameter->tier_3_target,
            default => null,
        };

        // PPT tier targets override generic targets for the district's assigned tier.
        if ($tierTarget !== null) {
            return max(0, (float) $tierTarget);
        }

        // Dynamic denominators implement PPT rules such as tehsil x visits and baseline percentages.
        $dynamicTarget = $this->resolveDynamicTarget($parameter, $district, $context);
        if ($dynamicTarget !== null) {
            return max(0, $dynamicTarget);
        }

        return $parameter->target_value !== null
            ? max(0, (float) $parameter->target_value)
            : null;
    }

    private function resolveDynamicTarget(
        KpiScoringParameter $parameter,
        District $district,
        array $context
    ): ?float {
        $slug = (string) $parameter->parameter_slug;

        return match ($slug) {
            'weekly-two-visits-of-acs-in-each-tehsil-with-inspection-reports-submitted',
            'weekly-two-visits-of-acs-in-each-tehsil-with-health-inspection-reports-submitted'
                => $this->resolveTehsilCount($district, $context) * 2,

            'weekly-six-suthra-punjab-inspections-by-acs-in-each-tehsil'
                => $this->resolveTehsilCount($district, $context) * 6,

            'clearance-of-at-least-one-market-per-working-day-in-each-tehsil',
            'inspection-of-at-least-one-market-per-working-day-in-each-tehsil'
                => $this->resolveTehsilCount($district, $context) * max(0, (float) ($context['working_days'] ?? 5)),

            'inspection-of-at-least-25-educational-institutions-for-zebra-crossings'
                => $this->percentageTarget($context['educational_institutions'] ?? null, 0.25),

            'inspection-of-at-least-25-sale-points-for-illegal-lpg-decanting'
                => $this->percentageTarget($context['lpg_sale_points'] ?? null, 0.25),

            'action-taken-on-violations-for-at-least-15-of-inspections'
                => $this->percentageTarget($context['inspections_count'] ?? null, 0.15),

            default => null,
        };
    }

    private function resolveTehsilCount(District $district, array $context): float
    {
        if (isset($context['tehsil_count'])) {
            return max(0, (float) $context['tehsil_count']);
        }

        if (! $district->exists) {
            return 0;
        }

        return (float) $district->tehsils()->where('is_active', true)->count();
    }

    private function percentageTarget(mixed $baseValue, float $rate): ?float
    {
        if ($baseValue === null) {
            return null;
        }

        return (float) ceil(max(0, (float) $baseValue) * $rate);
    }

    /**
     * Entry point for imports/submissions: calculate and persist one sub-KPI,
     * then refresh the parent KPI percentage from the sum of sub-KPI marks.
     */
    public function saveParameterResult(
        DistrictKpiScore $score,
        KpiScoringParameter $parameter,
        float $numerator,
        ?float $denominator = null,
        ?float $reportedScore = null,
        ?string $evidence = null,
        array $extraData = [],
        array $context = []
    ): DistrictKpiScoreDetail {
        return DB::transaction(function () use ($score, $parameter, $numerator, $denominator, $reportedScore, $evidence, $extraData, $context) {
            $score->loadMissing(['district', 'kpiCategory']);
            $result = $this->calculateForParameter(
                $parameter,
                $score->district,
                $numerator,
                $denominator,
                $reportedScore,
                $context
            );

            $detail = DistrictKpiScoreDetail::updateOrCreate(
                [
                    'district_kpi_score_id' => $score->id,
                    'kpi_scoring_parameter_id' => $parameter->id,
                ],
                [
                    'reported_value' => $reportedScore ?? $numerator,
                    'numerator_value' => $numerator,
                    'denominator_value' => $result['target_value'],
                    'target_value' => $result['target_value'],
                    'achieved_percentage' => $result['achieved_percentage'],
                    'weightage' => $parameter->weightage,
                    'score_obtained' => $result['score_obtained'],
                    'evidence' => $evidence,
                    'extra_data' => [
                        ...$extraData,
                        'formula_type' => $result['formula_type'],
                    ],
                ]
            );

            $this->calculateDistrictKpiFinalScore($score);

            return $detail;
        });
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
        $categoryWeightage = (float) ($score->kpiCategory?->scorecard_weightage ?? 0);
        if ($categoryWeightage <= 0) {
            $categoryWeightage = (float) $score->details()->sum('weightage');
        }
        $categoryWeightage = max(0.01, $categoryWeightage);

        // Final KPI marks are the sum of its calculated sub-KPI scores.
        $reportedMarks = (float) $score->details()->sum('score_obtained');
        $penaltyMarks = (float) $score->penalties()->sum('penalty_score');

        $verified = (float) ($score->verified_score ?? 0);
        $calculationType = $score->calculation_type ?? 'general';
        $verifiedMarks = $verified > $categoryWeightage
            ? ($verified / 100) * $categoryWeightage
            : $verified;

        if ($calculationType === 'sixty_forty') {
            $finalMarks = ($reportedMarks * 0.60) + ($verifiedMarks * 0.40) - $penaltyMarks;
        } else { // general, special_branch_negative, victims_negative, negative_marking
            $finalMarks = $reportedMarks - $penaltyMarks;
        }

        $finalMarks = max(0, min($categoryWeightage, round($finalMarks, 2)));
        $reportedMarks = max(0, min($categoryWeightage, round($reportedMarks, 2)));
        $penaltyMarks = max(0, round($penaltyMarks, 2));
        $reportedPercentage = round(($reportedMarks / $categoryWeightage) * 100, 2);
        $finalPercentage = round(($finalMarks / $categoryWeightage) * 100, 2);

        $meta = $this->getGradeMeta($finalPercentage);

        $score->reported_score = $reportedPercentage;
        $score->penalty_score = $penaltyMarks;
        $score->final_score = $finalPercentage;
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
