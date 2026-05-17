<?php

namespace Database\Seeders;

use App\Models\District;
use App\Models\DistrictKpiMetricValue;
use App\Models\ProvincialKpiMetric;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class DistrictKpiMetricValueSeeder extends Seeder
{
    public function run(): void
    {
        $districts = District::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        $periods = [
            'last_week'       => [
                'from'   => now()->subWeek()->startOfWeek()->toDateString(),
                'to'     => now()->subWeek()->endOfWeek()->toDateString(),
                'factor' => 1.0,
            ],
            'current_week'    => [
                'from'   => now()->startOfWeek()->toDateString(),
                'to'     => now()->endOfWeek()->toDateString(),
                'factor' => 0.92,
            ],
            'last_four_weeks' => [
                'from'   => now()->subWeeks(4)->startOfWeek()->toDateString(),
                'to'     => now()->endOfWeek()->toDateString(),
                'factor' => 3.6,
            ],
        ];

        $evidenceOptions = [
            'Verified',
            'Uploaded',
            'Pending evidence',
            'N/A',
        ];

        foreach ($periods as $periodType => $meta) {
            $dateFrom = $meta['from'];
            $dateTo = $meta['to'];
            $periodFactor = (float) $meta['factor'];

            $metrics = ProvincialKpiMetric::where('is_active', true)
                ->where('period_type', $periodType)
                ->with('kpiCategory:id,name')
                ->orderBy('kpi_category_id')
                ->orderBy('sort_order')
                ->get();

            foreach ($metrics as $metric) {
                foreach ($districts as $district) {
                    $base = (float) $metric->metric_value;

                    // Generate a realistic district variance around provincial value.
                    $varianceFactor = mt_rand(70, 130) / 100; // 0.70 .. 1.30
                    $districtValue = max(0, round(($base * $varianceFactor), 2));

                    // Score loosely based on closeness to provincial baseline (higher is better).
                    $ratio = $base > 0 ? min(1.2, $districtValue / $base) : 0;
                    $score = $base > 0 ? round(min(100, max(35, ($ratio * 85) + mt_rand(-10, 10))), 2) : null;

                    // Extra realism for water filtration plants: keep some logical relations.
                    if (str_contains(strtolower((string) $metric->kpiCategory?->name), 'water filtration')) {
                        $title = strtolower((string) $metric->metric_title);

                        if (str_contains($title, 'total water filtration plants')) {
                            $districtValue = max(40, round($base * (mt_rand(50, 160) / 100), 0));
                            $score = round(min(100, max(50, mt_rand(60, 98))), 2);
                        } elseif (str_contains($title, 'inspected')) {
                            $districtValue = max(0, round($base * (mt_rand(60, 140) / 100), 0));
                        } elseif (str_contains($title, 'not inspected')) {
                            $districtValue = max(0, round($base * (mt_rand(50, 140) / 100), 0));
                        } elseif (str_contains($title, 'functional')) {
                            $districtValue = max(0, round($base * (mt_rand(60, 140) / 100), 0));
                        } elseif (str_contains($title, 'non-functional')) {
                            $districtValue = max(0, round($base * (mt_rand(40, 160) / 100), 0));
                        }
                    }

                    DistrictKpiMetricValue::updateOrCreate(
                        [
                            'district_id'             => $district->id,
                            'kpi_category_id'          => $metric->kpi_category_id,
                            'provincial_kpi_metric_id' => $metric->id,
                            'period_type'              => $periodType,
                            'metric_title'             => $metric->metric_title,
                        ],
                        [
                            'date_from'    => $dateFrom,
                            'date_to'      => $dateTo,
                            'metric_value' => $districtValue,
                            'metric_score' => $score,
                            'metric_unit'  => $metric->metric_unit,
                            'evidence'     => Arr::random($evidenceOptions),
                            'sort_order'   => $metric->sort_order,
                            'is_active'    => true,
                        ]
                    );
                }
            }
        }
    }
}
