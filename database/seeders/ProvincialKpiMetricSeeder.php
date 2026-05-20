<?php
namespace Database\Seeders;

use App\Models\KpiCategory;
use App\Models\ProvincialKpiMetric;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProvincialKpiMetricSeeder extends Seeder
{
    public function run(): void
    {
        // PostgreSQL safety: if sequences get out of sync (common after manual inserts/imports),
        // new inserts can fail with duplicate primary key errors.
        if (DB::getDriverName() === 'pgsql') {
            $maxId = (int) (DB::table('kpi_categories')->max('id') ?? 0);
            $sequenceValue = max($maxId, 1);

            if ($maxId > 0) {
                DB::statement("SELECT setval(pg_get_serial_sequence('kpi_categories','id'), ?, true)", [$sequenceValue]);
            } else {
                DB::statement("SELECT setval(pg_get_serial_sequence('kpi_categories','id'), 1, false)");
            }
        }

        $categories = [
            'Price of Roti'                                => [
                [
                    'value'       => 77,
                    'title'       => 'DCs Review',
                    'description' => 'DCs twice weekly review with PCMs, Food Department and Special Branch regarding enforcement of Roti rate.',
                    'unit'        => 'count',
                    'source'      => 'DC',
                ],
                [
                    'value'       => 56,
                    'title'       => 'Tandoor Inspections',
                    'description' => 'Inspections of tandoors conducted by ACs/PCMs as per tier-wise targets.',
                    'unit'        => 'inspections',
                    'source'      => 'AC/PCM',
                ],
                [
                    'value'       => 107,
                    'title'       => 'Fine Imposed',
                    'description' => 'Fine imposed on violations including overpricing, weight issue, or non-availability of roti.',
                    'unit'        => 'actions',
                    'source'      => 'Field Teams',
                ],
                [
                    'value'       => 39,
                    'title'       => 'Citizen Complaint Actions',
                    'description' => 'Actions taken on citizen complaints related to roti price and availability.',
                    'unit'        => 'actions',
                    'source'      => 'Citizen',
                ],
            ],

            'Price of Plain Bakery Bread'                  => [
                [
                    'value'       => 37,
                    'title'       => 'Bakery Producer Inspections',
                    'description' => 'Inspections of brands and local producers conducted by ACs/PCMs.',
                    'unit'        => 'inspections',
                    'source'      => 'AC/PCM',
                ],
                [
                    'value'       => 22,
                    'title'       => 'Mobility Index',
                    'description' => 'Special coverage and mobility index for ACs/PCMs.',
                    'unit'        => 'count',
                    'source'      => 'System',
                ],
                [
                    'value'       => 195,
                    'title'       => 'Fine Imposed',
                    'description' => 'Fine imposed on violations including overpricing or non-availability of plain bread.',
                    'unit'        => 'actions',
                    'source'      => 'Field Teams',
                ],
                [
                    'value'       => 41,
                    'title'       => 'Citizen Complaint Actions',
                    'description' => 'Actions taken on citizen complaints.',
                    'unit'        => 'actions',
                    'source'      => 'Citizen',
                ],
            ],

            'Price Control of Essential Commodities'       => [
                [
                    'value'       => 1506,
                    'title'       => 'Violations Reported',
                    'description' => 'Total violations reported by Special Branch in the district.',
                    'unit'        => 'count',
                    'source'      => 'Special Branch',
                ],
                [
                    'value'       => 7518,
                    'title'       => 'Actions Against Violations',
                    'description' => 'Actions taken against violations reported by Special Branch.',
                    'unit'        => 'actions',
                    'source'      => 'Special Branch',
                ],
                [
                    'value'       => 56,
                    'title'       => 'Sale Point Inspections',
                    'description' => 'Inspections of sale points conducted by ACs/PCMs as per tier-wise targets.',
                    'unit'        => 'inspections',
                    'source'      => 'AC/PCM',
                ],
                [
                    'value'       => 93,
                    'title'       => 'Fine Imposed',
                    'description' => 'Fine imposed on overpricing violations.',
                    'unit'        => 'actions',
                    'source'      => 'Field Teams',
                ],
            ],

            'Dysfunctional Streetlights'                   => [
                [
                    'value'       => 7553,
                    'title'       => 'Zero Dysfunctional Streetlights',
                    'description' => 'Total roads, streets, and boulevards with zero dysfunctional streetlights now.',
                    'unit'        => 'count',
                    'source'      => 'District',
                ],
                [
                    'value'       => 32,
                    'title'       => 'Actions Reported',
                    'description' => 'Actions on dysfunctional streetlights as reported by Special Branch.',
                    'unit'        => 'actions',
                    'source'      => 'Special Branch',
                ],
            ],

            'Covering of Manholes'                         => [
                [
                    'value'       => 5629,
                    'title'       => 'Zero Open Manholes',
                    'description' => 'Total urban UCs, rural UCs, and MCs with zero open manholes now.',
                    'unit'        => 'count',
                    'source'      => 'District',
                ],
                [
                    'value'       => 37,
                    'title'       => 'Actions Reported',
                    'description' => 'Actions on covering open manholes reported by Special Branch.',
                    'unit'        => 'actions',
                    'source'      => 'Special Branch',
                ],
            ],

            'Functional and Clean Water Filtration Plants' => [
                [
                    'value'       => 5341,
                    'title'       => 'Total Water Filtration Plants',
                    'description' => 'Total number of water filtration plants in the province.',
                    'unit'        => 'count',
                    'source'      => 'District',
                ],
                [
                    'value'       => 3997,
                    'title'       => 'Inspected',
                    'description' => 'Total plants inspected during the selected period.',
                    'unit'        => 'count',
                    'source'      => 'Field Teams',
                ],
                [
                    'value'       => 3732,
                    'title'       => 'Functional',
                    'description' => 'Plants found functional during inspection.',
                    'unit'        => 'count',
                    'source'      => 'Field Teams',
                ],
                [
                    'value'       => 265,
                    'title'       => 'Non-Functional',
                    'description' => 'Plants found non-functional during inspection.',
                    'unit'        => 'count',
                    'source'      => 'Field Teams',
                ],
                [
                    'value'       => 1138,
                    'title'       => 'Not Inspected',
                    'description' => 'Plants not inspected during the selected period.',
                    'unit'        => 'count',
                    'source'      => 'District',
                ],
                [
                    'value'       => 3100,
                    'title'       => 'RO / UF Plants',
                    'description' => 'RO/UF plants reported in the province.',
                    'unit'        => 'count',
                    'source'      => 'District',
                ],
                [
                    'value'       => 300,
                    'title'       => 'RO Filter Changed',
                    'description' => 'RO filter change recorded during the selected period.',
                    'unit'        => 'count',
                    'source'      => 'District',
                ],
                [
                    'value'       => 345,
                    'title'       => 'RO Filter Unchanged',
                    'description' => 'RO filter unchanged during the selected period.',
                    'unit'        => 'count',
                    'source'      => 'District',
                ],
                [
                    'value'       => 3532,
                    'title'       => 'Cleaned',
                    'description' => 'Plants cleaned during the selected period.',
                    'unit'        => 'count',
                    'source'      => 'District',
                ],
                [
                    'value'       => 465,
                    'title'       => 'Un-cleaned',
                    'description' => 'Plants found un-cleaned during the selected period.',
                    'unit'        => 'count',
                    'source'      => 'District',
                ],
            ],

            'Inspection of Educational Institutions'       => [
                [
                    'value'       => 76,
                    'title'       => 'DC School Visits',
                    'description' => 'Weekly two visits of DC in district to inspect cleanliness, facilities, classrooms, staff availability, boundary wall, drinking water, toilets, and learning material.',
                    'unit'        => 'visits',
                    'source'      => 'DC',
                ],
                [
                    'value'       => 306,
                    'title'       => 'AC School Visits',
                    'description' => 'Weekly two visits of ACs in each tehsil to inspect schools and facilities.',
                    'unit'        => 'visits',
                    'source'      => 'AC',
                ],
                [
                    'value'       => 37,
                    'title'       => 'School Council Meetings',
                    'description' => 'Meetings of DCs on activation of School Councils.',
                    'unit'        => 'meetings',
                    'source'      => 'DC',
                ],
            ],

            'Inspection of Health Facilities'              => [
                [
                    'value'       => 76,
                    'title'       => 'DC Health Facility Visits',
                    'description' => 'Weekly two visits of DC to inspect health facility cleanliness, medicines, equipment, staff availability, ultrasound, wheelchairs, and UHI compliance.',
                    'unit'        => 'visits',
                    'source'      => 'DC',
                ],
                [
                    'value'       => 306,
                    'title'       => 'AC Health Facility Visits',
                    'description' => 'Weekly two visits of ACs in each tehsil to inspect health facilities.',
                    'unit'        => 'visits',
                    'source'      => 'AC',
                ],
                [
                    'value'       => 37,
                    'title'       => 'Health Council Meetings',
                    'description' => 'Meetings of DCs on activation and fund utilization of Health Council.',
                    'unit'        => 'meetings',
                    'source'      => 'DC',
                ],
                [
                    'value'       => 14,
                    'title'       => 'Special Branch Actions',
                    'description' => 'Actions on inspections made by Special Branch.',
                    'unit'        => 'actions',
                    'source'      => 'Special Branch',
                ],
            ],

            'Stray Dogs'                                   => [
                [
                    'value'       => 13045,
                    'title'       => 'UC-Level Stray Dog Activities',
                    'description' => 'Number of UC activities for culling of stray dogs completed by the district designated teams.',
                    'unit'        => 'count',
                    'source'      => 'District',
                ],
                [
                    'value'       => 38,
                    'title'       => 'Campaign Actions',
                    'description' => 'Campaign actions against stray dogs as reported by Special Branch.',
                    'unit'        => 'actions',
                    'source'      => 'Special Branch',
                ],
            ],

            'Maintenance of Drains and Sewerage Lines'     => [
                [
                    'value'       => 5821,
                    'title'       => 'Zero Blocked Sewerage Lines',
                    'description' => 'Total urban UCs, rural UCs and MCs with zero blocked, choked, or overflowing sewerage lines.',
                    'unit'        => 'count',
                    'source'      => 'District',
                ],
                [
                    'value'       => 39,
                    'title'       => 'Special Branch Actions',
                    'description' => 'Actions on blocked or overflowing sewerage lines as reported by Special Branch.',
                    'unit'        => 'actions',
                    'source'      => 'Special Branch',
                ],
            ],

            'Bus Terminals'                                => [
                [
                    'value'       => 568,
                    'title'       => 'Bus Terminal Visits',
                    'description' => 'Weekly visits of bus terminals by AC, Secretary RTA, or authorized officer for waiting area, washrooms, drinking water, and cleanliness arrangements.',
                    'unit'        => 'visits',
                    'source'      => 'District',
                ],
                [
                    'value'       => 18,
                    'title'       => 'Special Branch Actions',
                    'description' => 'Actions on bus terminals as reported by Special Branch.',
                    'unit'        => 'actions',
                    'source'      => 'Special Branch',
                ],
            ],

            'E-Biz'                                        => [
                [
                    'value'       => 41,
                    'title'       => 'Applications Completed',
                    'description' => 'Completion of all applications received.',
                    'unit'        => 'count',
                    'source'      => 'System',
                ],
                [
                    'value'       => 545,
                    'title'       => 'Help Desk Inspections',
                    'description' => 'DC inspections in offices to check proper establishment of help desks.',
                    'unit'        => 'inspections',
                    'source'      => 'DC',
                ],
                [
                    'value'       => 19,
                    'title'       => 'Disposal Meetings',
                    'description' => 'Meetings of DCs on timely disposal of applications with concerned departments or organizations.',
                    'unit'        => 'meetings',
                    'source'      => 'DC',
                ],
            ],
        ];

        $periods = ['last_week', 'current_week', 'last_four_weeks'];

        foreach ($categories as $categoryName => $metrics) {
            // Ensure each KPI category has enough metric cards for a meaningful district-wise table (>= 5).
            // This keeps the UI dense and avoids very small/empty-looking tables.
            if (count($metrics) < 5) {
                $fillers = [
                    [
                        'value'       => mt_rand(20, 180),
                        'title'       => 'Field Visits',
                        'description' => 'Field visits conducted by district teams for monitoring and follow-up.',
                        'unit'        => 'visits',
                        'source'      => 'District',
                    ],
                    [
                        'value'       => mt_rand(20, 220),
                        'title'       => 'Follow-up Checks',
                        'description' => 'Follow-up checks completed after initial observations to ensure compliance.',
                        'unit'        => 'checks',
                        'source'      => 'District',
                    ],
                    [
                        'value'       => mt_rand(10, 160),
                        'title'       => 'Compliance Actions',
                        'description' => 'Compliance actions taken (warnings, fines, closures, etc.) based on field findings.',
                        'unit'        => 'actions',
                        'source'      => 'District',
                    ],
                    [
                        'value'       => mt_rand(10, 140),
                        'title'       => 'Citizen Complaints Resolved',
                        'description' => 'Citizen complaints resolved through field action and monitoring.',
                        'unit'        => 'actions',
                        'source'      => 'Citizen',
                    ],
                    [
                        'value'       => mt_rand(20, 260),
                        'title'       => 'Coverage Index',
                        'description' => 'Coverage index showing breadth of field monitoring and reporting.',
                        'unit'        => 'count',
                        'source'      => 'System',
                    ],
                ];

                $needed = 5 - count($metrics);
                $metrics = array_merge($metrics, array_slice($fillers, 0, $needed));
            }

            $slug = (string) str($categoryName)->slug();
            $category = KpiCategory::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $categoryName,
                    'description' => $categoryName,
                    'is_active' => true,
                ]
            );

            foreach ($periods as $periodType) {
                $rangeFrom = null;
                $rangeTo = null;
                $valueFactor = 1.0;

                if ($periodType === 'current_week') {
                    $rangeFrom = now()->startOfWeek()->toDateString();
                    $rangeTo = now()->endOfWeek()->toDateString();
                    $valueFactor = 0.92;
                } elseif ($periodType === 'last_four_weeks') {
                    $rangeFrom = now()->subWeeks(4)->startOfWeek()->toDateString();
                    $rangeTo = now()->endOfWeek()->toDateString();
                    $valueFactor = 3.6;
                } else { // last_week
                    $rangeFrom = now()->subWeek()->startOfWeek()->toDateString();
                    $rangeTo = now()->subWeek()->endOfWeek()->toDateString();
                    $valueFactor = 1.0;
                }

                foreach ($metrics as $index => $metric) {
                    $baseValue = (float) ($metric['value'] ?? 0);
                    $finalValue = round(max(0, $baseValue * $valueFactor), 2);

                    ProvincialKpiMetric::updateOrCreate(
                        [
                            'kpi_category_id' => $category->id,
                            'period_type'     => $periodType,
                            'metric_title'    => $metric['title'],
                        ],
                        [
                            'date_from'          => $rangeFrom,
                            'date_to'            => $rangeTo,
                            'metric_description' => $metric['description'] ?? null,
                            'metric_value'       => $finalValue,
                            'metric_unit'        => $metric['unit'] ?? null,
                            'source'             => $metric['source'] ?? null,
                            'sort_order'         => $index + 1,
                            'is_active'          => true,
                        ]
                    );
                }
            }
        }
    }
}
