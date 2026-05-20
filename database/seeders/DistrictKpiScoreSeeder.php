<?php

namespace Database\Seeders;

use App\Models\District;
use App\Models\KpiCategory;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DistrictKpiScoreSeeder extends Seeder
{
    public function run(): void
    {
        // Seed realistic scorecard data for testing:
        // - general: all districts × all categories (latest week + previous week)
        // - other calculation types: sample districts only (latest week)
        DB::table('district_kpi_scores')->truncate();

        $districts = District::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get(['id', 'division_id', 'tier', 'name']);

        $categories = KpiCategory::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get(['id', 'name']);

        if ($districts->isEmpty() || $categories->isEmpty()) {
            return;
        }

        $now = now();
        $periods = $this->weeklyPeriods($now, 2); // current + previous for trend testing
        $currentPeriod = $periods[0];
        $previousPeriod = $periods[1] ?? null;

        $extraCalcTypes = ['sixty_forty', 'special_branch_negative', 'victims_negative'];
        $extraCalcDistrictIds = $districts->take(12)->pluck('id')->all();

        $nowTs = now();
        $batch = [];
        $batchSize = 2500;

        foreach ($districts->values() as $idx => $district) {
            $band = $this->districtBand((int) $idx);

            foreach ($categories as $category) {
                // Missing/unreported test: skip a few category records entirely (missing row => unreported).
                if ($this->shouldSkipRecord((int) $district->id, (int) $category->id)) {
                    continue;
                }

                $currentScore = $this->makeKpiScore((int) $district->id, (int) $category->id, $band, 0);
                $currentReported = $this->shouldBeUnreported((int) $district->id, (int) $category->id, 'general', true) ? false : true;

                $batch[] = $this->makeRowWithScore($district, (int) $category->id, $currentPeriod, 'general', $currentReported, $currentScore, $nowTs);

                if ($previousPeriod) {
                    $prevScore = $this->makeKpiScore((int) $district->id, (int) $category->id, $band, -1, $currentScore);
                    $prevReported = $this->shouldBeUnreported((int) $district->id, (int) $category->id, 'general', false) ? false : true;
                    $batch[] = $this->makeRowWithScore($district, (int) $category->id, $previousPeriod, 'general', $prevReported, $prevScore, $nowTs);
                }

                // Sample data for other calculation types (latest week only).
                if (in_array($district->id, $extraCalcDistrictIds, true)) {
                    foreach ($extraCalcTypes as $calcType) {
                        if ($this->shouldSkipRecord((int) $district->id, (int) $category->id, $calcType)) {
                            continue;
                        }

                        $score = $this->makeKpiScore((int) $district->id, (int) $category->id, $band, 0);
                        $reported = $this->shouldBeUnreported((int) $district->id, (int) $category->id, $calcType, true) ? false : true;
                        $batch[] = $this->makeRowWithScore($district, (int) $category->id, $currentPeriod, $calcType, $reported, $score, $nowTs);
                    }
                }

                if (count($batch) >= $batchSize) {
                    DB::table('district_kpi_scores')->insert($batch);
                    $batch = [];
                }
            }
        }

        if ($batch) {
            DB::table('district_kpi_scores')->insert($batch);
        }
    }

    private function makeRowWithScore($district, int $kpiCategoryId, array $period, string $calculationType, bool $reported, float $finalScore, $nowTs): array
    {
        $finalScore = round(max(0, min(100, $finalScore)), 2);

        $label = $finalScore >= 80 ? 'Excellent' : ($finalScore >= 60 ? 'Good' : ($finalScore >= 40 ? 'Average' : 'Critical'));
        $grade = $finalScore >= 90 ? 'A+' : ($finalScore >= 80 ? 'A' : ($finalScore >= 70 ? 'B' : ($finalScore >= 60 ? 'C' : ($finalScore >= 50 ? 'D' : 'E'))));

        return [
            'division_id' => $district->division_id,
            'district_id' => $district->id,
            'kpi_category_id' => $kpiCategoryId,
            'period_type' => $period['period_type'],
            'week_no' => $period['week_no'],
            'month' => $period['month'],
            'quarter' => $period['quarter'],
            'year' => $period['year'],
            'date_from' => $period['date_from'],
            'date_to' => $period['date_to'],
            'calculation_type' => $calculationType,
            'reported_score' => $reported ? $finalScore : 0,
            'verified_score' => $reported ? $finalScore : 0,
            'penalty_score' => 0,
            'final_score' => $reported ? $finalScore : 0,
            'grade' => $reported ? $grade : null,
            'performance_label' => $reported ? $label : null,
            'is_reported' => $reported ? true : false,
            'is_active' => true,
            'created_at' => $nowTs,
            'updated_at' => $nowTs,
        ];
    }

    private function districtBand(int $index): string
    {
        return match ($index % 4) {
            0 => 'excellent',
            1 => 'good',
            2 => 'average',
            default => 'critical',
        };
    }

    private function makeKpiScore(int $districtId, int $categoryId, string $band, int $weekOffset = 0, ?float $currentScore = null): float
    {
        $seed = $this->stableRandInt($districtId, $categoryId, $weekOffset);
        $jitter = (($seed % 900) / 100) - 4.5; // -4.5..+4.49

        if ($weekOffset < 0 && $currentScore !== null) {
            $delta = 2 + (($seed % 7)); // 2..8
            return (float) max(0, min(100, round($currentScore - $delta, 2)));
        }

        $base = match ($band) {
            'excellent' => 92,
            'good' => 75,
            'average' => 55,
            default => 28,
        };

        $score = $base + $jitter;

        // Band clamps
        $score = match ($band) {
            'excellent' => min(100, max(85, $score)),
            'good' => min(84, max(65, $score)),
            'average' => min(64, max(45, $score)),
            default => min(39, max(10, $score)),
        };

        // Add a few true-zero scores for critical band to validate 0-score handling.
        if ($band === 'critical' && (($seed % 23) === 0)) {
            $score = 0;
        }

        return (float) round($score, 2);
    }

    private function shouldSkipRecord(int $districtId, int $categoryId, ?string $calcType = null): bool
    {
        // Missing record => unreported (tests covered-weight scaling).
        $seed = $this->stableRandInt($districtId, $categoryId, (int) sprintf('%u', crc32((string) $calcType)));
        return ($seed % 97) === 0; // ~1%
    }

    private function shouldBeUnreported(int $districtId, int $categoryId, string $calcType, bool $isCurrentWeek): bool
    {
        if (! $isCurrentWeek) {
            return false;
        }

        $seed = $this->stableRandInt($districtId, $categoryId, (int) sprintf('%u', crc32($calcType)), 991);
        return ($seed % 53) === 0; // small % flagged as unreported rows
    }

    private function stableRandInt(int ...$parts): int
    {
        return (int) sprintf('%u', crc32(implode(':', $parts)));
    }

    private function weeklyPeriods(Carbon $now, int $weeksBack): array
    {
        $periods = [];

        // Old PPMF weekly cycle is Thursday -> Wednesday.
        // For local testing we seed the *latest completed* week as "current" (so defaults match old PPMF),
        // and then seed previous week for comparisons.
        $cursor = $now->copy()->startOfDay();
        while ($cursor->dayOfWeek !== Carbon::THURSDAY) {
            $cursor->subDay();
        }

        // Move to latest completed reporting week start (Thursday).
        $cursor->subWeek();

        for ($i = 0; $i < $weeksBack; $i++) {
            $start = $cursor->copy()->startOfDay();
            $end = $start->copy()->addDays(6)->endOfDay(); // Wed end

            $year = (int) $start->isoFormat('GGGG'); // ISO week year
            $week = (int) $start->isoWeek();
            $weekNo = sprintf('%d%02d', $year, $week);

            $periods[] = [
                'period_key' => "weekly:{$weekNo}",
                'period_type' => 'weekly',
                'week_no' => $weekNo,
                'month' => sprintf('%02d', (int) $start->month),
                'quarter' => (int) $start->quarter,
                'year' => $year,
                'date_from' => $start->toDateString(),
                'date_to' => $end->toDateString(),
            ];

            $cursor->subWeek();
        }

        return $periods;
    }

}
