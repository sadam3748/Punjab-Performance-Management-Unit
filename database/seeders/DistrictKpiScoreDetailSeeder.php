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

                $reportedScore = 0.0;

                foreach ($parameters as $param) {
                    $reportedValue = $this->buildReportedValue($score, $param->scoring_method, (float) ($param->target_value ?? 0));

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

    private function buildReportedValue(DistrictKpiScore $score, string $method, float $target): float
    {
        $seed = $this->stableRandInt(
            (int) $score->district_id,
            (int) $score->kpi_category_id,
            (int) sprintf('%u', crc32((string) $score->period_type)),
            (int) sprintf('%u', crc32((string) ($score->week_no ?? $score->month ?? $score->quarter ?? $score->year))),
            (int) sprintf('%u', crc32((string) $method))
        );

        $r = ($seed % 10000) / 10000; // 0..0.9999

        if ($method === 'yes_no') {
            return ($seed % 7 === 0) ? 0 : 1;
        }

        if ($method === 'direct_score') {
            // Direct score is already a score (0..weightage), we still store a value.
            return round(40 + ($r * 60), 2);
        }

        if ($method === 'inverse_percentage') {
            // Lower is better. Keep values around the target with some variation.
            if ($target <= 0) {
                return round(10 + ($r * 60), 2);
            }
            return round(max(0, $target * (0.6 + ($r * 1.2))), 2);
        }

        // percentage
        if ($target <= 0) {
            $target = 100;
        }

        return round(max(0, $target * (0.55 + ($r * 0.6))), 2); // 55%..115%
    }

    private function buildVerifiedScore(DistrictKpiScore $score, float $reportedScore): float
    {
        $seed = $this->stableRandInt((int) $score->id, (int) $score->district_id, (int) $score->kpi_category_id);
        $delta = (($seed % 900) / 100) - 4.5; // -4.5 .. +4.49

        return (float) round(max(0, min(100, $reportedScore + $delta)), 2);
    }

    private function maybeSeedPenalty(DistrictKpiScore $score): void
    {
        $seed = $this->stableRandInt((int) $score->id, (int) $score->district_id, (int) $score->kpi_category_id, 991);
        if (($seed % 5) !== 0) {
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
}

