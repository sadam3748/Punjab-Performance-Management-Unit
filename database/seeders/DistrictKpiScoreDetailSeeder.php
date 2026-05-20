<?php

namespace Database\Seeders;

use App\Models\DistrictKpiScore;
use App\Models\KpiScoringParameter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DistrictKpiScoreDetailSeeder extends Seeder
{
    public function run(): void
    {
        // Fresh seeding approach: truncate then bulk insert for speed.
        DB::table('district_kpi_score_details')->truncate();
        DB::table('district_kpi_penalties')->truncate();

        // Practical cap for local dev.
        $maxDetailRows = 2500;

        $parameterMap = KpiScoringParameter::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->groupBy('kpi_category_id');

        if ($parameterMap->isEmpty()) {
            return;
        }

        $detailBatch = [];
        $detailBatchSize = 4000;
        $nowTs = now();

        $detailRowsInserted = 0;

        $currentWeekNo = (string) (DistrictKpiScore::query()
            ->where('is_active', true)
            ->where('period_type', 'weekly')
            ->where('calculation_type', 'general')
            ->max('week_no') ?? '');

        if ($currentWeekNo === '') {
            return;
        }

        // Balanced approach: details for a subset of districts (keeps seed fast but still tests district detail page).
        $districtIdsForDetails = DistrictKpiScore::query()
            ->where('is_active', true)
            ->where('period_type', 'weekly')
            ->where('calculation_type', 'general')
            ->where('week_no', $currentWeekNo)
            ->distinct()
            ->orderBy('district_id')
            ->limit(20)
            ->pluck('district_id')
            ->all();

        if (! $districtIdsForDetails) {
            return;
        }

        DB::transaction(function () use ($parameterMap, &$detailBatch, $detailBatchSize, $nowTs, $maxDetailRows, &$detailRowsInserted, $currentWeekNo, $districtIdsForDetails) {
            DistrictKpiScore::query()
                ->where('is_active', true)
                ->where('period_type', 'weekly')
                ->where('calculation_type', 'general')
                ->where('week_no', $currentWeekNo)
                ->where('is_reported', true)
                ->whereIn('district_id', $districtIdsForDetails)
                ->orderBy('id')
                ->chunkById(400, function ($scores) use ($parameterMap, &$detailBatch, $detailBatchSize, $nowTs, $maxDetailRows, &$detailRowsInserted) {
                    foreach ($scores as $score) {
                        if ($detailRowsInserted >= $maxDetailRows) {
                            break;
                        }

                        $parameters = $parameterMap->get($score->kpi_category_id, collect());
                        if ($parameters->isEmpty()) {
                            continue;
                        }

                        $targetFinal = (float) ($score->final_score ?? 0);
                        $targetFinal = max(0, min(100, $targetFinal));

                        $paramCount = $parameters->count();
                        $sumSoFar = 0.0;

                        foreach ($parameters->values() as $i => $param) {
                            if ($detailRowsInserted >= $maxDetailRows) {
                                break;
                            }

                            $w = (float) $param->weightage;
                            $base = ($targetFinal / 100) * $w;

                            // Small deterministic jitter; last parameter absorbs remainder to keep totals close to final_score.
                            $seed = $this->stableRandInt((int) $score->district_id, (int) $score->kpi_category_id, (int) $param->id);
                            $jitter = (($seed % 80) / 100) - 0.4; // -0.40..+0.39

                            $obtained = ($i === $paramCount - 1)
                                ? max(0, min($w, $targetFinal - $sumSoFar))
                                : max(0, min($w, $base + $jitter));

                            $obtained = round($obtained, 2);
                            $sumSoFar += $obtained;

                            $achieved = $w > 0 ? round(($obtained / $w) * 100, 2) : 0.0;
                            $reportedValue = $param->target_value ? round(((float) $param->target_value) * ($achieved / 100), 2) : $achieved;

                            $detailBatch[] = [
                                'district_kpi_score_id' => $score->id,
                                'kpi_scoring_parameter_id' => $param->id,
                                'reported_value' => $reportedValue,
                                'target_value' => $param->target_value,
                                'achieved_percentage' => $achieved,
                                'weightage' => $w,
                                'score_obtained' => $obtained,
                                'evidence' => $this->buildEvidence($score, (int) $param->id),
                                'extra_data' => json_encode(['seed_key' => $this->seedKey($score, (int) $param->id)]),
                                'created_at' => $nowTs,
                                'updated_at' => $nowTs,
                            ];

                            $detailRowsInserted++;

                            if (count($detailBatch) >= $detailBatchSize) {
                                DB::table('district_kpi_score_details')->insert($detailBatch);
                                $detailBatch = [];
                            }
                        }
                    }
                });

            if ($detailBatch) {
                DB::table('district_kpi_score_details')->insert($detailBatch);
                $detailBatch = [];
            }
        });
    }

    // NOTE: We intentionally do not compute penalties/final score here; district_kpi_scores are already seeded.

    private function buildReportedValueForTarget(
        DistrictKpiScore $score,
        string $method,
        float $target,
        float $weightage,
        float $percentFactor,
        float $yesNoEarn
    ): float
    {
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
        $seed = $this->stableRandInt((int) $score->id, (int) sprintf('%u', crc32($method)), (int) $score->district_id, (int) $score->kpi_category_id);
        $jitter = (($seed % 700) / 100) - 3.5; // -3.5 .. +3.49
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
        $seed = $this->stableRandInt((int) $score->id, (int) $score->district_id, (int) $score->kpi_category_id);
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
            'penalty_type' => ($seed % 2 === 0) ? 'missing_evidence' : 'late_reporting',
            'penalty_score' => $penalty,
            'remarks' => 'Auto-seeded penalty for demonstration.',
        ]);
    }

    private function buildEvidence(DistrictKpiScore $score, int $paramId): ?string
    {
        $seed = $this->stableRandInt((int) $score->district_id, (int) $score->kpi_category_id, (int) $paramId);
        return match ($seed % 4) {
            0 => 'Verified',
            1 => 'Uploaded',
            2 => 'Pending',
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
            0 => 93, // Excellent
            1 => 82, // Good
            2 => 60, // Average
            3 => 42, // Critical
            default => 0,
        };

        $seed = $this->stableRandInt((int) $score->district_id, (int) $score->kpi_category_id, (int) sprintf('%u', crc32((string) $score->week_no)));
        $delta = (($seed % 900) / 100) - 4.5; // -4.5..+4.49

        $target = $base + $delta;

        // Keep within band ranges for cleaner distribution on UI.
        $target = match ($band) {
            0 => min(96, max(90, $target)),
            1 => min(89, max(75, $target)),
            2 => min(69, max(55, $target)),
            3 => min(49, max(30, $target)),
            default => 0,
        };

        return round($target, 2);
    }
}
