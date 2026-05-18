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
            ->get(['id', 'name']);

        $now = now();
        $periods = array_merge(
            $this->weeklyPeriods($now, 4),
            $this->monthlyPeriods($now),
            $this->quarterlyPeriods($now),
            $this->yearlyPeriods($now)
        );

        $calculationTypes = ['general', 'sixty_forty'];

        foreach ($districts as $district) {
            foreach ($categories as $category) {
                foreach ($periods as $period) {
                    foreach ($calculationTypes as $calcType) {
                        // Intentionally keep a few missing rows to simulate "Unreported"
                        $skipKey = $this->stableRandInt($district->id, $category->id, crc32($period['period_key']), crc32($calcType)) % 23;
                        if ($skipKey === 0) {
                            continue;
                        }

                        DistrictKpiScore::updateOrCreate(
                            [
                                'district_id'      => $district->id,
                                'kpi_category_id'  => $category->id,
                                'period_type'      => $period['period_type'],
                                'week_no'          => $period['week_no'],
                                'month'            => $period['month'],
                                'quarter'          => $period['quarter'],
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
                                'is_reported'       => true,
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

    private function monthlyPeriods(Carbon $now): array
    {
        $periods = [];
        foreach ([0, 1] as $back) {
            $start = $now->copy()->subMonths($back)->startOfMonth();
            $end = $start->copy()->endOfMonth();

            $periods[] = [
                'period_key' => 'monthly:' . $start->format('Y-m'),
                'period_type' => 'monthly',
                'week_no' => null,
                'month' => (int) $start->month,
                'quarter' => null,
                'year' => (int) $start->year,
                'date_from' => $start->toDateString(),
                'date_to' => $end->toDateString(),
            ];
        }

        return $periods;
    }

    private function quarterlyPeriods(Carbon $now): array
    {
        $start = $now->copy()->firstOfQuarter()->startOfDay();
        $end = $now->copy()->lastOfQuarter()->endOfDay();

        return [[
            'period_key' => 'quarterly:' . $now->year . '-Q' . $now->quarter,
            'period_type' => 'quarterly',
            'week_no' => null,
            'month' => null,
            'quarter' => (int) $now->quarter,
            'year' => (int) $now->year,
            'date_from' => $start->toDateString(),
            'date_to' => $end->toDateString(),
        ]];
    }

    private function yearlyPeriods(Carbon $now): array
    {
        $start = $now->copy()->startOfYear();
        $end = $now->copy()->endOfYear();

        return [[
            'period_key' => 'yearly:' . $now->year,
            'period_type' => 'yearly',
            'week_no' => null,
            'month' => null,
            'quarter' => null,
            'year' => (int) $now->year,
            'date_from' => $start->toDateString(),
            'date_to' => $end->toDateString(),
        ]];
    }

    private function stableRandInt(int ...$parts): int
    {
        return (int) sprintf('%u', crc32(implode(':', $parts)));
    }
}

