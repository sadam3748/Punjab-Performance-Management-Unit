<?php

namespace Database\Seeders;

use App\Models\District;
use App\Models\DistrictKpiScore;
use App\Models\KpiCategory;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DistrictKpiScoreSeeder extends Seeder
{
    public function run(): void
    {
        $districts = District::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get(['id', 'division_id', 'tier', 'name']);

        $categories = KpiCategory::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->limit(15)
            ->get(['id', 'name']);

        $now = now();
        // Keep seed data small: only latest week + previous two weeks (detail page needs 3).
        $periods = $this->weeklyPeriods($now, 3);

        foreach ($districts as $district) {
            foreach ($categories as $category) {
                foreach ($periods as $period) {
                    $calcType = 'general';

                    // Unreported demo: for some districts, mark a few KPIs as not reported for the latest week.
                    $isLatestWeek = ($period['week_no'] ?? null) === ($periods[0]['week_no'] ?? null);
                    $band = $district->id % 5; // deterministic distribution
                    $shouldBeUnreported = $isLatestWeek && $band === 4 && (($category->id + $district->id) % 2 === 0);

                    DistrictKpiScore::updateOrCreate(
                        [
                            'district_id'      => $district->id,
                            'kpi_category_id'  => $category->id,
                            'period_type'      => $period['period_type'],
                            'week_no'          => $period['week_no'],
                            'month'            => null,
                            'quarter'          => null,
                            'year'             => $period['year'],
                            'calculation_type' => $calcType,
                        ],
                        [
                            'division_id'       => $district->division_id,
                            'date_from'         => $period['date_from'],
                            'date_to'           => $period['date_to'],
                            'reported_score'    => 0,
                            'verified_score'    => 0,
                            'penalty_score'     => 0,
                            'final_score'       => 0,
                            'grade'             => null,
                            'performance_label' => null,
                            'is_reported'       => $shouldBeUnreported ? false : true,
                            'is_active'         => true,
                        ]
                    );

                    // Add a small sample of sixty_forty rows (not for every record).
                    $addSixtyForty = $isLatestWeek && ($district->id % 7 === 0) && (($category->id % 3) === 0);
                    if ($addSixtyForty) {
                        DistrictKpiScore::updateOrCreate(
                            [
                                'district_id'      => $district->id,
                                'kpi_category_id'  => $category->id,
                                'period_type'      => $period['period_type'],
                                'week_no'          => $period['week_no'],
                                'month'            => null,
                                'quarter'          => null,
                                'year'             => $period['year'],
                                'calculation_type' => 'sixty_forty',
                            ],
                            [
                                'division_id'       => $district->division_id,
                                'date_from'         => $period['date_from'],
                                'date_to'           => $period['date_to'],
                                'reported_score'    => 0,
                                'verified_score'    => 0,
                                'penalty_score'     => 0,
                                'final_score'       => 0,
                                'grade'             => null,
                                'performance_label' => null,
                                'is_reported'       => $shouldBeUnreported ? false : true,
                                'is_active'         => true,
                            ]
                        );
                    }
                }
            }
        }
    }

    private function weeklyPeriods(Carbon $now, int $weeksBack): array
    {
        $periods = [];
        $cursor = $now->copy();

        for ($i = 0; $i < $weeksBack; $i++) {
            $start = $cursor->copy()->startOfWeek(Carbon::MONDAY);
            $end = $start->copy()->endOfWeek(Carbon::SUNDAY);

            $year = (int) $start->isoFormat('GGGG'); // ISO week year
            $week = (int) $start->isoWeek();
            $weekNo = sprintf('%d%02d', $year, $week);

            $periods[] = [
                'period_key' => "weekly:{$weekNo}",
                'period_type' => 'weekly',
                'week_no' => $weekNo,
                'month' => null,
                'quarter' => null,
                'year' => $year,
                'date_from' => $start->toDateString(),
                'date_to' => $end->toDateString(),
            ];

            $cursor = $cursor->subWeek();
        }

        return $periods;
    }
}
