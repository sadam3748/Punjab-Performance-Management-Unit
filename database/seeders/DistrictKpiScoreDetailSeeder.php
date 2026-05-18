<?php

namespace Database\Seeders;

use App\Models\DistrictKpiPenalty;
use App\Models\DistrictKpiScore;
use App\Models\DistrictKpiScoreDetail;
use App\Models\KpiScoringParameter;
use App\Services\ScorecardCalculationService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DistrictKpiScoreDetailSeeder extends Seeder
{
    public function __construct(private readonly ScorecardCalculationService $calculator)
    {
    }

    public function run(): void
    {
        $scores = DistrictKpiScore::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        if ($scores->isEmpty()) {
            return;
        }

        $parameterMap = KpiScoringParameter::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->groupBy('kpi_category_id');

        DB::transaction(function () use ($scores, $parameterMap) {
            foreach ($scores as $score) {
                $parameters = $parameterMap->get($score->kpi_category_id, collect());
                if ($parameters->isEmpty()) {
                    continue;
                }

                // Re-seeding safety: clear existing details/penalties then recreate.
                DistrictKpiScoreDetail::where('district_kpi_score_id', $score->id)->delete();
                DistrictKpiPenalty::where('district_kpi_score_id', $score->id)->delete();

                if (! $score->is_reported) {
                    $score->reported_score = 0;
                    $score->verified_score = 0;
                    $score->penalty_score = 0;
                    $score->final_score = 0;
                    $score->save();
                    continue;
                }

                $reportedScore = 0.0;

                $targetFinal = $this->targetFinalScore($score);

                $yesNoWeight = (float) $parameters
                    ->where('scoring_method', 'yes_no')
                    ->sum('weightage');

                $yesNoEarn = ($targetFinal >= 70) ? $yesNoWeight : (($targetFinal >= 50) ? ($yesNoWeight * 0.5) : 0);
                $remainingTarget = max(0, $targetFinal - $yesNoEarn);

                $percentWeight = (float) $parameters
                    ->where('scoring_method', '!=', 'yes_no')
                    ->sum('weightage');
                $percentFactor = $percentWeight > 0 ? min(100, max(0, ($remainingTarget / $percentWeight) * 100)) : 0;

                foreach ($parameters as $param) {
                    $reportedValue = $this->buildReportedValueForTarget(
                        $score,
                        (string) $param->scoring_method,
                        (float) ($param->target_value ?? 0),
                        (float) $param->weightage,
                        $percentFactor,
                        $yesNoEarn
                    );

                    $calc = $this->calculator->calculateParameterScore(
                        $reportedValue,
                        $param->target_value,
                        (float) $param->weightage,
                        (string) $param->scoring_method,
                        (bool) $param->higher_is_better
                    );

                    $reportedScore += (float) $calc['score_obtained'];

                    DistrictKpiScoreDetail::create([
                        'district_kpi_score_id'      => $score->id,
                        'kpi_scoring_parameter_id'   => $param->id,
                        'reported_value'             => $reportedValue,
                        'target_value'               => $param->target_value,
                        'achieved_percentage'        => (float) $calc['achieved_percentage'],
                        'weightage'                  => (float) $param->weightage,
                        'score_obtained'             => (float) $calc['score_obtained'],
                        'evidence'                   => $this->buildEvidence($score, $param->id),
                        'extra_data'                 => [
                            'seed_key' => $this->seedKey($score, $param->id),
                        ],
                    ]);
                }

                $reportedScore = round($reportedScore, 2);
                $verifiedScore = $this->buildVerifiedScore($score, $reportedScore);

                $score->reported_score = $reportedScore;
                $score->verified_score = $verifiedScore;
                $score->penalty_score = 0;
                $score->final_score = 0;
                $score->save();

                $this->maybeSeedPenalty($score);
                $this->calculator->calculateDistrictKpiFinalScore($score->refresh());
            }
        });
    }

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
