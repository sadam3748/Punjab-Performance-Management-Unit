<?php

namespace Database\Seeders;

use App\Models\KpiCategory;
use App\Models\KpiScoringParameter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class KpiScoringParameterSeeder extends Seeder
{
    public function run(): void
    {
        $categories = KpiCategory::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        foreach ($categories as $category) {
            $name = Str::lower((string) $category->name);

            $parameters = $this->getParametersForCategory($name);

            foreach ($parameters as $index => $p) {
                KpiScoringParameter::updateOrCreate(
                    [
                        'kpi_category_id' => $category->id,
                        'parameter_name'  => $p['parameter_name'],
                    ],
                    [
                        'description'      => $p['description'] ?? null,
                        'weightage'        => $p['weightage'],
                        'unit'             => $p['unit'] ?? null,
                        'scoring_method'   => $p['scoring_method'] ?? 'percentage',
                        'target_value'     => $p['target_value'] ?? 100,
                        'higher_is_better' => $p['higher_is_better'] ?? true,
                        'sort_order'       => $p['sort_order'] ?? $index,
                        'is_active'        => true,
                    ]
                );
            }
        }
    }

    private function getParametersForCategory(string $lowerName): array
    {
        if (Str::contains($lowerName, ['water filtration', 'filtration plant'])) {
            return [
                [
                    'parameter_name' => 'Functional Plants',
                    'description' => 'Percentage of functional filtration plants reported for the period.',
                    'weightage' => 40,
                    'unit' => 'percent',
                    'scoring_method' => 'percentage',
                    'target_value' => 100,
                    'higher_is_better' => true,
                    'sort_order' => 1,
                ],
                [
                    'parameter_name' => 'Cleanliness Status',
                    'description' => 'Plants cleaned / maintained as per monitoring checklist.',
                    'weightage' => 25,
                    'unit' => 'percent',
                    'scoring_method' => 'percentage',
                    'target_value' => 100,
                    'higher_is_better' => true,
                    'sort_order' => 2,
                ],
                [
                    'parameter_name' => 'Chlorination / Testing',
                    'description' => 'Water quality testing and chlorination compliance.',
                    'weightage' => 20,
                    'unit' => 'percent',
                    'scoring_method' => 'percentage',
                    'target_value' => 100,
                    'higher_is_better' => true,
                    'sort_order' => 3,
                ],
                [
                    'parameter_name' => 'Evidence Completeness',
                    'description' => 'Geo-tagged evidence/photos and reporting completeness.',
                    'weightage' => 15,
                    'unit' => 'yes_no',
                    'scoring_method' => 'yes_no',
                    'target_value' => null,
                    'higher_is_better' => true,
                    'sort_order' => 4,
                ],
            ];
        }

        if (Str::contains($lowerName, ['manhole'])) {
            return [
                [
                    'parameter_name' => 'Manholes Covered',
                    'description' => 'Coverage of identified open manholes.',
                    'weightage' => 50,
                    'unit' => 'percent',
                    'scoring_method' => 'percentage',
                    'target_value' => 100,
                    'higher_is_better' => true,
                    'sort_order' => 1,
                ],
                [
                    'parameter_name' => 'Timely Action',
                    'description' => 'Timely completion of manhole covering actions.',
                    'weightage' => 20,
                    'unit' => 'percent',
                    'scoring_method' => 'percentage',
                    'target_value' => 100,
                    'higher_is_better' => true,
                    'sort_order' => 2,
                ],
                [
                    'parameter_name' => 'Field Verification',
                    'description' => 'Verification by field staff / supervisors.',
                    'weightage' => 20,
                    'unit' => 'percent',
                    'scoring_method' => 'percentage',
                    'target_value' => 100,
                    'higher_is_better' => true,
                    'sort_order' => 3,
                ],
                [
                    'parameter_name' => 'Evidence / Photos',
                    'description' => 'Evidence completeness for reported actions.',
                    'weightage' => 10,
                    'unit' => 'yes_no',
                    'scoring_method' => 'yes_no',
                    'target_value' => null,
                    'higher_is_better' => true,
                    'sort_order' => 4,
                ],
            ];
        }

        if (Str::contains($lowerName, ['stray dog'])) {
            return [
                [
                    'parameter_name' => 'Complaints Resolved',
                    'description' => 'Resolution rate of registered complaints and actions completed.',
                    'weightage' => 40,
                    'unit' => 'percent',
                    'scoring_method' => 'percentage',
                    'target_value' => 100,
                    'higher_is_better' => true,
                    'sort_order' => 1,
                ],
                [
                    'parameter_name' => 'Hotspot Coverage',
                    'description' => 'Hotspot coverage activities conducted as per plan.',
                    'weightage' => 25,
                    'unit' => 'percent',
                    'scoring_method' => 'percentage',
                    'target_value' => 100,
                    'higher_is_better' => true,
                    'sort_order' => 2,
                ],
                [
                    'parameter_name' => 'Response Time',
                    'description' => 'Lower response time is better (inverse scoring).',
                    'weightage' => 20,
                    'unit' => 'minutes',
                    'scoring_method' => 'inverse_percentage',
                    'target_value' => 30,
                    'higher_is_better' => false,
                    'sort_order' => 3,
                ],
                [
                    'parameter_name' => 'Evidence Completeness',
                    'description' => 'Evidence / reporting completeness for actions.',
                    'weightage' => 15,
                    'unit' => 'yes_no',
                    'scoring_method' => 'yes_no',
                    'target_value' => null,
                    'higher_is_better' => true,
                    'sort_order' => 4,
                ],
            ];
        }

        if (Str::contains($lowerName, ['marriage functions', 'marriage function', 'marriage act'])) {
            return [
                [
                    'parameter_name' => 'Inspections Conducted',
                    'description' => 'Target achievement of inspections conducted for the period.',
                    'weightage' => 30,
                    'unit' => 'percent',
                    'scoring_method' => 'percentage',
                    'target_value' => 100,
                    'higher_is_better' => true,
                    'sort_order' => 1,
                ],
                [
                    'parameter_name' => 'Violations Acted Upon',
                    'description' => 'Actions taken against violations.',
                    'weightage' => 30,
                    'unit' => 'percent',
                    'scoring_method' => 'percentage',
                    'target_value' => 100,
                    'higher_is_better' => true,
                    'sort_order' => 2,
                ],
                [
                    'parameter_name' => 'Fines / Legal Action',
                    'description' => 'Enforcement through fines or legal actions.',
                    'weightage' => 20,
                    'unit' => 'percent',
                    'scoring_method' => 'percentage',
                    'target_value' => 100,
                    'higher_is_better' => true,
                    'sort_order' => 3,
                ],
                [
                    'parameter_name' => 'Evidence Completeness',
                    'description' => 'Evidence / documentation completeness.',
                    'weightage' => 20,
                    'unit' => 'yes_no',
                    'scoring_method' => 'yes_no',
                    'target_value' => null,
                    'higher_is_better' => true,
                    'sort_order' => 4,
                ],
            ];
        }

        if (Str::contains($lowerName, ['price control', 'price of roti', 'essential commodities', 'price of plain bakery bread'])) {
            return [
                [
                    'parameter_name' => 'Market Inspections',
                    'description' => 'Target achievement of shop/tandoor inspections.',
                    'weightage' => 25,
                    'unit' => 'percent',
                    'scoring_method' => 'percentage',
                    'target_value' => 100,
                    'higher_is_better' => true,
                    'sort_order' => 1,
                ],
                [
                    'parameter_name' => 'Overcharging Cases Acted',
                    'description' => 'Actions taken on overcharging / violation cases.',
                    'weightage' => 25,
                    'unit' => 'percent',
                    'scoring_method' => 'percentage',
                    'target_value' => 100,
                    'higher_is_better' => true,
                    'sort_order' => 2,
                ],
                [
                    'parameter_name' => 'Fines Recovered',
                    'description' => 'Fines imposed and recovered against violations.',
                    'weightage' => 20,
                    'unit' => 'percent',
                    'scoring_method' => 'percentage',
                    'target_value' => 100,
                    'higher_is_better' => true,
                    'sort_order' => 3,
                ],
                [
                    'parameter_name' => 'Evidence / Reporting',
                    'description' => 'Evidence completeness and reporting timeliness.',
                    'weightage' => 30,
                    'unit' => 'percent',
                    'scoring_method' => 'percentage',
                    'target_value' => 100,
                    'higher_is_better' => true,
                    'sort_order' => 4,
                ],
            ];
        }

        return [
            [
                'parameter_name' => 'Progress Achieved',
                'description' => 'Progress achieved against assigned KPI targets.',
                'weightage' => 50,
                'unit' => 'percent',
                'scoring_method' => 'percentage',
                'target_value' => 100,
                'higher_is_better' => true,
                'sort_order' => 1,
            ],
            [
                'parameter_name' => 'Timely Reporting',
                'description' => 'Timeliness of KPI reporting for the period.',
                'weightage' => 20,
                'unit' => 'percent',
                'scoring_method' => 'percentage',
                'target_value' => 100,
                'higher_is_better' => true,
                'sort_order' => 2,
            ],
            [
                'parameter_name' => 'Field Verification',
                'description' => 'Field verification coverage for reported work.',
                'weightage' => 20,
                'unit' => 'percent',
                'scoring_method' => 'percentage',
                'target_value' => 100,
                'higher_is_better' => true,
                'sort_order' => 3,
            ],
            [
                'parameter_name' => 'Evidence Completeness',
                'description' => 'Evidence completeness (geo-tagged photos / documents).',
                'weightage' => 10,
                'unit' => 'yes_no',
                'scoring_method' => 'yes_no',
                'target_value' => null,
                'higher_is_better' => true,
                'sort_order' => 4,
            ],
        ];
    }
}

