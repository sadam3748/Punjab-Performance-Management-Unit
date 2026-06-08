<?php
namespace Database\Seeders;

use App\Models\DistrictKpiPenalty;
use App\Models\DistrictKpiScore;
use App\Models\KpiScoringParameter;
use App\Services\ScorecardCalculationService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DistrictKpiScoreDetailSeeder extends Seeder
{
    public function run(): void
    {
        // Fresh seeding approach: truncate then bulk insert for speed.
        DB::table('district_kpi_score_details')->truncate();
        DB::table('district_kpi_penalties')->truncate();

        $parameterMap = KpiScoringParameter::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->groupBy('kpi_category_id');

        if ($parameterMap->isEmpty()) {
            return;
        }

        $detailBatch     = [];
        $detailBatchSize = 4000;
        $nowTs           = now();

        $detailRowsInserted = 0;
        $scoreIdsToRecalculate = [];
        $calculator = app(ScorecardCalculationService::class);

        DB::transaction(function () use ($parameterMap, &$detailBatch, $detailBatchSize, $nowTs, &$detailRowsInserted, &$scoreIdsToRecalculate, $calculator) {
            DistrictKpiScore::query()
                ->with('district')
                ->where('is_active', true)
                ->where('period_type', 'weekly')
                ->where('calculation_type', 'general')
                ->where('is_reported', true)
                ->orderBy('id')
                ->chunkById(400, function ($scores) use ($parameterMap, &$detailBatch, $detailBatchSize, $nowTs, &$detailRowsInserted, &$scoreIdsToRecalculate, $calculator) {
                    foreach ($scores as $score) {
                        $parameters = $parameterMap->get($score->kpi_category_id, collect());
                        if ($parameters->isEmpty()) {
                            continue;
                        }

                        $context = $this->demoContext($score);

                        foreach ($parameters->values() as $param) {
                            $formulaType = (string) ($param->formula_type ?: $param->scoring_method ?: 'percentage');
                            $denominator = $calculator->resolveParameterTarget($param, $score->district, [], $context)
                                ?? $this->demoDenominator($param, $score);
                            $desiredAchievement = $this->demoAchievement($score, (int) $param->id);
                            $numerator = $formulaType === 'yes_no'
                                ? ($desiredAchievement >= 50 ? 1.0 : 0.0)
                                : round($denominator * ($desiredAchievement / 100), 2);

                            $result = $calculator->calculateForParameter(
                                $param,
                                $score->district,
                                $numerator,
                                $denominator,
                                null,
                                $context
                            );

                            $detailBatch[] = [
                                'district_kpi_score_id'    => $score->id,
                                'kpi_scoring_parameter_id' => $param->id,
                                'reported_value'           => $numerator,
                                'numerator_value'          => $numerator,
                                'denominator_value'        => $denominator,
                                'target_value'             => $result['target_value'],
                                'achieved_percentage'      => $result['achieved_percentage'],
                                'weightage'                => $param->weightage,
                                'score_obtained'           => $result['score_obtained'],
                                'evidence'                 => $this->buildEvidence($score, (int) $param->id),
                                'extra_data'               => json_encode([
                                    'seed_key' => $this->seedKey($score, (int) $param->id),
                                    'formula_type' => $result['formula_type'],
                                    'submission_context' => $context,
                                ]),
                                'created_at'               => $nowTs,
                                'updated_at'               => $nowTs,
                            ];

                            $detailRowsInserted++;

                            if (count($detailBatch) >= $detailBatchSize) {
                                DB::table('district_kpi_score_details')->insert($detailBatch);
                                $detailBatch = [];
                            }
                        }

                        $scoreIdsToRecalculate[] = $score->id;
                    }
                });

            if ($detailBatch) {
                DB::table('district_kpi_score_details')->insert($detailBatch);
                $detailBatch = [];
            }
        });

        DistrictKpiScore::query()
            ->with('kpiCategory')
            ->whereIn('id', array_values(array_unique($scoreIdsToRecalculate)))
            ->each(fn (DistrictKpiScore $score) => $calculator->calculateDistrictKpiFinalScore($score));
    }

    private function demoContext(DistrictKpiScore $score): array
    {
        return [
            'tehsil_count' => max(1, $score->district->tehsils()->where('is_active', true)->count()),
            'working_days' => 5,
            'educational_institutions' => 40 + ((int) $score->district_id % 25),
            'lpg_sale_points' => 20 + ((int) $score->district_id % 15),
            'inspections_count' => 30 + ((int) $score->district_id % 20),
        ];
    }

    private function demoDenominator(KpiScoringParameter $parameter, DistrictKpiScore $score): float
    {
        $seed = $this->stableRandInt((int) $score->district_id, (int) $parameter->id, 707);

        return match ((string) $parameter->formula_type) {
            'amount_deposit_ratio' => (float) (10000 + ($seed % 40000)),
            'mobility_index' => (float) (20 + ($seed % 25)),
            default => (float) (10 + ($seed % 90)),
        };
    }

    private function demoAchievement(DistrictKpiScore $score, int $parameterId): float
    {
        $base = match ((int) $score->district_id % 4) {
            0 => 92,
            1 => 76,
            2 => 58,
            default => 34,
        };
        $seed = $this->stableRandInt((int) $score->district_id, (int) $score->kpi_category_id, $parameterId, (int) sprintf('%u', crc32((string) $score->week_no)));
        $jitter = (($seed % 1000) / 100) - 5;

        return round(max(0, min(100, $base + $jitter)), 2);
    }

    private function buildReportedValueForTarget(
        DistrictKpiScore $score,
        string $method,
        float $target,
        float $weightage,
        float $percentFactor,
        float $yesNoEarn
    ): float {
        if ($method === 'yes_no') {
            // Earn yes/no score for higher bands, partial for average band.
            if ($yesNoEarn <= 0) {
                return 0;
            }
            if ($yesNoEarn < $weightage) {
                // ~50% chance to keep some yes/no points
                return ($this->stableRandInt((int) $score->id, 221) % 2 === 0) ? 1 : 0;
            }
            return 1;
        }

        if ($target <= 0) {
            $target = 100;
        }

        // Percentage-like methods: keep values close to target factor with small deterministic variation.
        $seed             = $this->stableRandInt((int) $score->id, (int) sprintf('%u', crc32($method)), (int) $score->district_id, (int) $score->kpi_category_id);
        $jitter           = (($seed % 700) / 100) - 3.5; // -3.5 .. +3.49
        $effectivePercent = min(100, max(0, $percentFactor + $jitter));

        if ($method === 'inverse_percentage') {
            // Lower is better: percentFactor represents achievement; convert to a value around target.
            $ratio = max(0.05, 1 - ($effectivePercent / 100));
            return round($target * $ratio, 2);
        }

        // percentage / direct_score fallback
        return round($target * ($effectivePercent / 100), 2);
    }

    private function buildVerifiedScore(DistrictKpiScore $score, float $reportedScore): float
    {
        $seed  = $this->stableRandInt((int) $score->id, (int) $score->district_id, (int) $score->kpi_category_id);
        $delta = (($seed % 900) / 100) - 4.5; // -4.5 .. +4.49

        return (float) round(max(0, min(100, $reportedScore + $delta)), 2);
    }

    private function maybeSeedPenalty(DistrictKpiScore $score): void
    {
        // Keep penalties rare for small demo dataset.
        $seed = $this->stableRandInt((int) $score->id, (int) $score->district_id, (int) $score->kpi_category_id, 991);
        if (($seed % 9) !== 0) {
            return;
        }

        $penalty = round((($seed % 800) / 100), 2); // 0..7.99
        if ($penalty <= 0) {
            return;
        }

        DistrictKpiPenalty::create([
            'district_kpi_score_id' => $score->id,
            'penalty_type'          => ($seed % 2 === 0) ? 'missing_evidence' : 'late_reporting',
            'penalty_score'         => $penalty,
            'remarks'               => 'Auto-seeded penalty for demonstration.',
        ]);
    }

    private function buildEvidence(DistrictKpiScore $score, int $paramId): ?string
    {
        $seed = $this->stableRandInt((int) $score->district_id, (int) $score->kpi_category_id, (int) $paramId);
        return match ($seed % 4) {
            0       => 'Verified',
            1       => 'Uploaded',
            2       => 'Pending',
            default => 'N/A',
        };
    }

    private function seedKey(DistrictKpiScore $score, int $paramId): string
    {
        return implode(':', [
            $score->district_id,
            $score->kpi_category_id,
            $score->period_type,
            $score->week_no ?? $score->month ?? $score->quarter ?? $score->year,
            $score->calculation_type,
            $paramId,
        ]);
    }

    private function stableRandInt(int ...$parts): int
    {
        return (int) sprintf('%u', crc32(implode(':', $parts)));
    }

    private function targetFinalScore(DistrictKpiScore $score): float
    {
        $band = (int) ($score->district_id % 5);

        $base = match ($band) {
            0       => 93, // Excellent
            1       => 82, // Good
            2       => 60, // Average
            3       => 42, // Critical
            default => 0,
        };

        $seed  = $this->stableRandInt((int) $score->district_id, (int) $score->kpi_category_id, (int) sprintf('%u', crc32((string) $score->week_no)));
        $delta = (($seed % 900) / 100) - 4.5; // -4.5..+4.49

        $target = $base + $delta;

        // Keep within band ranges for cleaner distribution on UI.
        $target = match ($band) {
            0       => min(96, max(90, $target)),
            1       => min(89, max(75, $target)),
            2       => min(69, max(55, $target)),
            3       => min(49, max(30, $target)),
            default => 0,
        };

        return round($target, 2);
    }
}
