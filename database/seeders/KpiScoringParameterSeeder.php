<?php

namespace Database\Seeders;

use App\Models\KpiCategory;
use App\Models\KpiScoringParameter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class KpiScoringParameterSeeder extends Seeder
{
    public function run(): void
    {
        $categories = KpiCategory::query()
            ->where('is_active', true)
            ->get(['id', 'name', 'slug', 'scorecard_weightage']);

        foreach ($categories as $category) {
            $parameters = $this->parameters()[$category->slug] ?? [];
            if ($parameters === []) {
                Log::warning('No PPT scoring parameters configured for KPI category.', [
                    'kpi_category_id' => $category->id,
                    'slug' => $category->slug,
                ]);
                continue;
            }

            $activeNames = [];
            foreach ($parameters as $index => $p) {
                $activeNames[] = $p['parameter_name'];

                KpiScoringParameter::updateOrCreate(
                    [
                        'kpi_category_id' => $category->id,
                        'parameter_name'  => $p['parameter_name'],
                    ],
                    [
                        'description'      => $p['description'] ?? $p['parameter_name'],
                        'weightage'        => $p['weightage'],
                        'unit'             => $p['unit'] ?? 'percent',
                        'scoring_method'   => $p['scoring_method'] ?? 'percentage',
                        'target_value'     => $p['target_value'] ?? 100,
                        'higher_is_better' => $p['higher_is_better'] ?? true,
                        'sort_order'       => $p['sort_order'] ?? ($index + 1),
                        'is_active'        => true,
                    ]
                );
            }

            KpiScoringParameter::query()
                ->where('kpi_category_id', $category->id)
                ->whereNotIn('parameter_name', $activeNames)
                ->update(['is_active' => false]);

            $total = array_sum(array_column($parameters, 'weightage'));
            if (round((float) $total, 2) !== round((float) $category->scorecard_weightage, 2)) {
                Log::warning('KPI scoring parameter total does not match category weightage.', [
                    'kpi_category_id' => $category->id,
                    'category' => $category->name,
                    'category_weightage' => (float) $category->scorecard_weightage,
                    'parameter_total' => round((float) $total, 2),
                ]);
            }
        }
    }

    private function p(string $name, float $weight, string $method = 'percentage', ?float $target = 100, string $unit = 'percent', bool $higherIsBetter = true): array
    {
        return [
            'parameter_name' => $name,
            'weightage' => $weight,
            'scoring_method' => $method,
            'target_value' => $target,
            'unit' => $unit,
            'higher_is_better' => $higherIsBetter,
        ];
    }

    private function parameters(): array
    {
        return [
            'price-of-roti' => [
                $this->p('DC twice weekly review meeting', 2, 'yes_no', null, 'yes_no'),
                $this->p('Tandoor inspections as per tier target', 3),
                $this->p('Special coverage and mobility index', 2),
                $this->p('Fine deposit / PSID compliance', 2),
                $this->p('Citizen complaint action', 1),
            ],
            'price-of-plain-bakery-bread' => [
                $this->p('DC twice weekly review meeting', 1),
                $this->p('Bread producer inspections as per tier target', 1.5),
                $this->p('Fine deposit / PSID compliance', 1.5),
                $this->p('Citizen complaint action', 1),
            ],
            'price-control-of-essential-commodities' => [
                $this->p('DC twice weekly review meeting', 2),
                $this->p('Market inspections as per tier target', 3),
                $this->p('Price list display and overcharging action', 2),
                $this->p('Fine deposit / PSID compliance', 2),
                $this->p('Citizen complaint action', 1),
            ],
            'repair-of-small-roads-in-both-urban-and-rural-areas' => [
                $this->p('Identification and prioritization of small road patches', 1),
                $this->p('Repair work completed against reported roads', 1.5),
                $this->p('Geo-tagged before and after evidence', 0.5),
            ],
            'dysfunctional-streetlights' => [
                $this->p('Streetlights repaired against identified dysfunctional lights', 3),
                $this->p('Follow-up verification of repaired streetlights', 2),
            ],
            'covering-of-manholes' => [
                $this->p('Open manholes covered against identified locations', 3),
                $this->p('Geo-tagged verification and evidence', 2),
            ],
            'functional-and-clean-water-filtration-plants' => [
                $this->p('Regular change of filter and affixing dates', 2.5),
                $this->p('Inspection of all water filtration plants functional', 2.5),
            ],
            'inspection-of-educational-institutions' => [
                $this->p('Educational institution inspections as per target', 3),
                $this->p('Corrective action and follow-up compliance', 2),
            ],
            'inspection-of-health-facilities' => [
                $this->p('Health facility inspections as per target', 3),
                $this->p('Corrective action and follow-up compliance', 2),
            ],
            'violation-of-marriage-functions-act' => [
                $this->p('Marriage hall/function inspections as per target', 1),
                $this->p('Action against reported violations', 1),
                $this->p('Fine deposit / legal compliance', 1),
            ],
            'anti-encroachment-campaign' => [
                $this->p('Anti-encroachment operations conducted', 3),
                $this->p('Sustainability monitoring and re-encroachment prevention', 2),
            ],
            'regulation-of-shops-and-handcarts' => [
                $this->p('Regulation of shops and handcarts inspections', 2),
                $this->p('Action against violations and follow-up', 1),
            ],
            'stray-dogs' => [
                $this->p('Hotspot identification and area coverage', 2),
                $this->p('Complaints resolved / action completed', 2),
                $this->p('Response time against target', 1, 'inverse_percentage', 30, 'minutes', false),
            ],
            'removal-of-wall-chalking' => [
                $this->p('Wall chalking removal against identified sites', 2),
                $this->p('Geo-tagged verification and evidence', 1),
            ],
            'graveyards' => [
                $this->p('Graveyard visits / inspections conducted', 1.5),
                $this->p('Cleanliness and boundary/upkeep compliance', 1.5),
            ],
            'e-biz' => [
                $this->p('E-Biz applications processed within timeline', 2),
                $this->p('Pendency disposal and dashboard compliance', 1),
            ],
            'zebra-crossings' => [
                $this->p('Zebra crossings painted / restored', 1.5),
                $this->p('Geo-tagged verification and evidence', 0.5),
            ],
            'illegal-decanting' => [
                $this->p('LPG decanting hotspots inspected', 1),
                $this->p('Action against illegal decanting points', 1),
                $this->p('Follow-up compliance and sealing verification', 1),
            ],
            'suthra-punjab-campaign' => [
                $this->p('Cleanliness activities against campaign targets', 2),
                $this->p('Waste lifting and disposal compliance', 1),
                $this->p('Public spaces upkeep and monitoring', 1),
                $this->p('Geo-tagged evidence and reporting', 1),
            ],
            'maintenance-of-greenbelts' => [
                $this->p('Greenbelt maintenance and plantation upkeep', 2),
                $this->p('Beautification monitoring and evidence', 1),
            ],
            'maintenance-of-drains-and-sewerage-lines' => [
                $this->p('Drain and sewerage line desilting / maintenance', 2),
                $this->p('Critical choke points resolved and verified', 1),
            ],
            'bus-terminals' => [
                $this->p('Weekly visits of bus terminals', 2),
                $this->p('Action on bus terminals reported by Special Branch', 1),
            ],
            'chief-ministers-complaint-cell' => [
                $this->p('Complaints resolved', 3),
            ],
        ];
    }
}
