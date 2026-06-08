<?php

namespace Database\Seeders;

use App\Models\KpiCategory;
use App\Models\KpiScoringParameter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class KpiScoringParameterSeeder extends Seeder
{
    public function run(): void
    {
        $categories = KpiCategory::query()
            ->where('is_active', true)
            ->get(['id', 'name', 'slug', 'scorecard_weightage']);

        foreach ($categories as $category) {
            $parameters = $this->parameters()[$category->slug] ?? [];
            $activeSlugs = [];

            foreach ($parameters as $index => $parameter) {
                $slug = $parameter['parameter_slug'] ?? Str::slug($parameter['parameter_name']);
                $activeSlugs[] = $slug;
                $order = $index + 1;

                KpiScoringParameter::updateOrCreate(
                    [
                        'kpi_category_id' => $category->id,
                        'parameter_slug' => $slug,
                    ],
                    [
                        'parameter_name' => $parameter['parameter_name'],
                        'description' => $parameter['formula_expression'],
                        'weightage' => $parameter['weightage'],
                        'unit' => $parameter['unit'] ?? 'ratio',
                        // Keep scoring_method populated for backward compatibility.
                        'scoring_method' => $parameter['formula_type'],
                        'formula_type' => $parameter['formula_type'],
                        'formula_expression' => $parameter['formula_expression'],
                        'numerator_label' => $parameter['numerator_label'] ?? null,
                        'denominator_label' => $parameter['denominator_label'] ?? null,
                        'target_value' => $parameter['target_value'] ?? null,
                        'tier_1_target' => $parameter['tier_1_target'] ?? null,
                        'tier_2_target' => $parameter['tier_2_target'] ?? null,
                        'tier_3_target' => $parameter['tier_3_target'] ?? null,
                        'higher_is_better' => true,
                        'sort_order' => $order,
                        'display_order' => $order,
                        'is_active' => true,
                    ]
                );
            }

            KpiScoringParameter::query()
                ->where('kpi_category_id', $category->id)
                ->where(function ($query) use ($activeSlugs) {
                    $query->whereNull('parameter_slug')
                        ->orWhereNotIn('parameter_slug', $activeSlugs);
                })
                ->update(['is_active' => false]);

            $parameterTotal = round((float) array_sum(array_column($parameters, 'weightage')), 2);
            if ($parameterTotal !== round((float) $category->scorecard_weightage, 2)) {
                Log::warning('PPT sub-KPI total does not match main KPI weightage.', [
                    'category' => $category->name,
                    'category_weightage' => (float) $category->scorecard_weightage,
                    'parameter_total' => $parameterTotal,
                ]);
            }
        }
    }

    private function ratio(
        string $name,
        float $weight,
        string $numerator,
        string $denominator,
        string $type = 'percentage',
        ?float $target = null,
        array $tiers = []
    ): array {
        $formula = $type === 'mobility_index'
            ? 'min(sum_weighted_mobility_scores / total_inspection_intervals, 1) * weightage'
            : 'min(numerator / denominator, 1) * weightage';

        return [
            'parameter_name' => $name,
            'weightage' => $weight,
            'formula_type' => $type,
            'formula_expression' => $formula,
            'numerator_label' => $numerator,
            'denominator_label' => $denominator,
            'target_value' => $target,
            'tier_1_target' => $tiers[1] ?? null,
            'tier_2_target' => $tiers[2] ?? null,
            'tier_3_target' => $tiers[3] ?? null,
        ];
    }

    private function yesNo(string $name, float $weight): array
    {
        return [
            'parameter_name' => $name,
            'weightage' => $weight,
            'formula_type' => 'yes_no',
            'formula_expression' => 'yes = full weightage; no = 0',
            'numerator_label' => 'Compliance status',
            'denominator_label' => null,
            'target_value' => 1,
            'unit' => 'yes_no',
        ];
    }

    private function parameters(): array
    {
        return [
            'price-of-roti' => [
                $this->ratio('DC twice weekly review with PCMs, Food Authority and Special Branch', 2, 'Meetings held with minutes submitted', 'Total meetings required', 'percentage', 2),
                $this->ratio('Tandoor inspections by ACs/PCMs as per tier-wise targets', 3, 'Tandoor inspections conducted', 'Tier-wise required inspections', 'percentage', null, [1 => 10, 2 => 8, 3 => 6]),
                $this->ratio('Special Coverage and Mobility Index for ACs/PCMs', 2, 'Sum of weighted mobility scores', 'Total inspection intervals', 'mobility_index'),
                $this->ratio('Fine deposited against PSID generated for violations', 2, 'Total amount deposited', 'Total amount generated through PSID', 'amount_deposit_ratio'),
                $this->ratio('Action taken on citizen complaints', 1, 'Citizen complaints resolved', 'Citizen complaints lodged', 'resolved_ratio'),
            ],
            'price-of-plain-bakery-bread' => [
                $this->ratio('Inspections of brands/local producers by ACs/PCMs as per tier-wise targets', 2, 'Producer inspections conducted', 'Tier-wise required inspections', 'percentage', null, [1 => 5, 2 => 4, 3 => 3]),
                $this->ratio('Special Coverage and Mobility Index for ACs/PCMs', 1, 'Sum of weighted mobility scores', 'Total inspection intervals', 'mobility_index'),
                $this->ratio('Fine deposited against PSID generated for violations', 1, 'Total amount deposited', 'Total amount generated through PSID', 'amount_deposit_ratio'),
                $this->ratio('Action taken on citizen complaints', 1, 'Citizen complaints resolved', 'Citizen complaints lodged', 'resolved_ratio'),
            ],
            'price-control-of-essential-commodities' => [
                $this->ratio('Inspections of sale points by ACs/PCMs as per tier-wise targets', 4, 'Sale-point inspections conducted', 'Tier-wise required inspections', 'percentage', null, [1 => 35, 2 => 28, 3 => 21]),
                $this->ratio('Special Coverage and Mobility Index for ACs/PCMs', 2, 'Sum of weighted mobility scores', 'Total inspection intervals', 'mobility_index'),
                $this->ratio('Action against violations reported by Special Branch', 1, 'Actions taken on Special Branch violations', 'Violations reported by Special Branch', 'resolved_ratio'),
                $this->ratio('Action against violations reported by citizens', 1, 'Actions taken on citizen violations', 'Violations reported by citizens', 'resolved_ratio'),
                $this->ratio('Fine deposited against PSID generated for violations', 2, 'Total amount deposited', 'Total amount generated through PSID', 'amount_deposit_ratio'),
            ],
            'repair-of-small-roads-in-both-urban-and-rural-areas' => [
                $this->ratio('Weekly maintenance of two small roads by district', 2, 'Roads maintained', 'Roads required to be maintained', 'percentage', 2),
                $this->ratio('Action on roads reported by Special Branch', 1, 'Actions taken on reported roads', 'Road points reported by Special Branch', 'resolved_ratio'),
            ],
            'dysfunctional-streetlights' => [
                $this->ratio('Inspections and repair activity on roads/streets/boulevards with streetlights', 2.5, 'Roads/streets inspected and lights repaired', 'Roads with streetlights installed'),
                $this->ratio('Action on dysfunctional streetlights reported by Special Branch', 2.5, 'Dysfunctional streetlight points resolved', 'Dysfunctional streetlight points reported', 'resolved_ratio'),
            ],
            'covering-of-manholes' => [
                $this->ratio('Inspection of UCs with manholes by AC or concerned officer', 2.5, 'UCs inspected', 'UCs with manholes'),
                $this->ratio('Action on open manholes reported by Special Branch', 2.5, 'Open manholes resolved', 'Open manholes reported by Special Branch', 'resolved_ratio'),
            ],
            'functional-and-clean-water-filtration-plants' => [
                $this->ratio('Regular filter change and date affixing at functional filtration plants', 2.5, 'Plants with filter changed and date affixed', 'Functional filtration plants'),
                $this->ratio('Inspection of all water filtration plants for functionality', 2.5, 'Filtration plants inspected', 'Total filtration plants'),
            ],
            'inspection-of-educational-institutions' => [
                $this->ratio('Weekly two visits of DC with inspection reports submitted', 2, 'DC visits with reports submitted', 'Required DC visits', 'percentage', 2),
                $this->ratio('Weekly two visits of ACs in each tehsil with inspection reports submitted', 2, 'AC visits with reports submitted', 'Required AC visits based on tehsils'),
                $this->yesNo('DC meeting on activation of School Councils with minutes submitted', 1),
            ],
            'inspection-of-health-facilities' => [
                $this->ratio('Weekly two visits of DC with health inspection reports submitted', 1.5, 'DC visits with reports submitted', 'Required DC visits', 'percentage', 2),
                $this->ratio('Weekly two visits of ACs in each tehsil with health inspection reports submitted', 1.5, 'AC visits with reports submitted', 'Required AC visits based on tehsils'),
                $this->yesNo('DC meeting on activation and fund utilization of Health Council', 1),
                $this->ratio('Action on health inspections made by Special Branch', 1, 'Special Branch health points resolved', 'Special Branch health points indicated', 'resolved_ratio'),
            ],
            'violation-of-marriage-functions-act' => [
                $this->ratio('Weekly marriage hall/function inspections by authorized teams', 2, 'Inspections made', 'Total marriage halls'),
                $this->ratio('Action against marriage-function violations reported by Special Branch', 1, 'Actions taken on violations', 'Violations reported by Special Branch', 'resolved_ratio'),
            ],
            'anti-encroachment-campaign' => [
                $this->ratio('Clearance of at least one market per working day in each tehsil', 4, 'Markets cleared from encroachment', 'Markets required to be cleared'),
                $this->ratio('Action on encroachments reported by Special Branch', 1, 'Encroachments resolved', 'Encroachments reported by Special Branch', 'resolved_ratio'),
            ],
            'regulation-of-shops-and-handcarts' => [
                $this->ratio('Inspection of at least one market per working day in each tehsil', 3, 'Markets inspected during week', 'Markets required to be inspected'),
            ],
            'stray-dogs' => [
                $this->ratio('UC coverage by designated stray-dog campaign teams', 4, 'UCs where campaign activity performed', 'Total UCs'),
                $this->ratio('Action on stray-dog points reported by Special Branch', 1, 'Reported points resolved', 'Points reported by Special Branch', 'resolved_ratio'),
            ],
            'removal-of-wall-chalking' => [
                $this->ratio('Inspections for removal of wall chalking in all UCs', 1.5, 'UCs where wall-chalking inspection performed', 'Total UCs'),
                $this->ratio('Action on wall chalking reported by Special Branch', 1.5, 'Reported wall-chalking points resolved', 'Wall-chalking points reported by Special Branch', 'resolved_ratio'),
            ],
            'graveyards' => [
                $this->ratio('Clearance of at least seven graveyards per week', 2, 'Graveyards cleared', 'Graveyards required to be cleared', 'percentage', 7),
                $this->ratio('Action on graveyards reported by Special Branch', 1, 'Reported graveyard points resolved', 'Graveyard points reported by Special Branch', 'resolved_ratio'),
            ],
            'e-biz' => [
                $this->ratio('Processing of applications more than seven days old', 2, 'Applications processed this week', 'Applications pending at start of week', 'resolved_ratio'),
                $this->ratio('DC inspections of offices for properly established help desks', 0.5, 'Offices inspected', 'Total DC/AC/MC offices'),
                $this->yesNo('DC meeting on timely disposal of applications with concerned departments', 0.5),
            ],
            'zebra-crossings' => [
                $this->ratio('Inspection of at least 25% educational institutions for zebra crossings', 1.5, 'Educational institutions inspected', 'Educational institutions required to be inspected'),
                $this->ratio('Action on zebra crossings reported by Special Branch', 0.5, 'Reported zebra-crossing points resolved', 'Zebra-crossing points reported by Special Branch', 'resolved_ratio'),
            ],
            'illegal-decanting' => [
                $this->ratio('Inspection of at least 25% sale points for illegal LPG decanting', 1.5, 'Sale points inspected', 'Sale points required to be inspected'),
                $this->ratio('Action taken on violations for at least 15% of inspections', 1, 'Actions taken', 'Required actions based on inspections'),
                $this->ratio('Action on illegal decanting violations reported by Special Branch', 0.5, 'Actions taken on reported violations', 'Violations reported by Special Branch', 'resolved_ratio'),
            ],
            'suthra-punjab-campaign' => [
                $this->ratio('Weekly two Suthra Punjab inspections by DC', 2, 'DC inspections conducted', 'Required DC inspections', 'percentage', 2),
                $this->ratio('Weekly six Suthra Punjab inspections by ACs in each tehsil', 2, 'AC inspections conducted', 'Required AC inspections based on tehsils'),
                $this->ratio('Action on Suthra Punjab violations reported by Special Branch', 1, 'Points cleared', 'Points identified by Special Branch', 'resolved_ratio'),
            ],
            'maintenance-of-greenbelts' => [
                $this->ratio('Parks fully maintained in district', 1, 'Parks fully maintained', 'Total parks'),
                $this->ratio('Tehsils and towns with fully maintained greenbelts', 1, 'Tehsils/towns with maintained greenbelts', 'Tehsils/towns having greenbelts'),
                $this->ratio('Action on greenbelts reported by Special Branch', 0.5, 'Actions taken on reported greenbelt points', 'Greenbelt points reported by Special Branch', 'resolved_ratio'),
                $this->yesNo('At least one DC beautification initiative with pictorial evidence', 0.5),
            ],
            'maintenance-of-drains-and-sewerage-lines' => [
                $this->ratio('UC-wise inspection for zero blocked/choked/overflowing sewerage and stagnant water', 2, 'UCs inspected', 'UCs with sewerage lines'),
                $this->ratio('Action on sewerage and stagnant-water points indicated by Special Branch', 1, 'Indicated points resolved', 'Points indicated by Special Branch', 'resolved_ratio'),
            ],
            'bus-terminals' => [
                $this->ratio('Weekly visits of bus terminals by authorized district officers', 2, 'Bus-terminal visits conducted', 'Total bus terminals'),
                $this->ratio('Action on bus terminals reported by Special Branch', 1, 'Reported bus-terminal points resolved', 'Bus-terminal points reported by Special Branch', 'resolved_ratio'),
            ],
            'chief-ministers-complaint-cell' => [
                $this->ratio('Complaints resolved', 3, 'Complaints resolved', 'Total complaints', 'resolved_ratio'),
            ],
        ];
    }
}
