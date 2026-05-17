<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PpmfDummyBaselineSeeder extends Seeder
{
    /**
     * Dummy baseline seeder for PPMF portal.
     *
     * This seeder stores old PPMF-style baseline indicators in district_baseline_data.baseline_data JSON.
     * The detail view will then show clear field/value data such as UCs, population, schools,
     * BHUs/RHCs, roads, manholes, streetlights, water filtration plants, etc.
     */
    public function run(): void
    {
        $adminUserId = DB::table('users')->where('username', 'super.admin')->value('id')
            ?? DB::table('users')->orderBy('id')->value('id');

        if (! $adminUserId) {
            return;
        }

        $districts = DB::table('districts')
            ->when(Schema::hasColumn('districts', 'is_active'), fn ($q) => $q->where('is_active', true))
            ->orderBy('id')
            ->get();

        $kpiCategories = DB::table('kpi_categories')
            ->when(Schema::hasColumn('kpi_categories', 'is_active'), fn ($q) => $q->where('is_active', true))
            ->orderBy('id')
            ->get(['id', 'name']);

        if ($districts->isEmpty() || $kpiCategories->isEmpty()) {
            return;
        }

        $years = [2026];

        foreach ($districts as $districtIndex => $district) {
            $districtProfile = $this->districtProfile((string) $district->name, $districtIndex);

            foreach ($kpiCategories as $category) {
                foreach ($years as $year) {
                    $baseline = $this->buildBaselinePayload(
                        districtName: (string) $district->name,
                        districtProfile: $districtProfile,
                        categoryName: (string) $category->name,
                        districtIndex: $districtIndex,
                        year: $year,
                    );

                    DB::table('district_baseline_data')->updateOrInsert(
                        [
                            'district_id'     => $district->id,
                            'kpi_category_id' => $category->id,
                            'year'            => $year,
                        ],
                        [
                            'baseline_data' => json_encode($baseline, JSON_UNESCAPED_UNICODE),
                            'created_by'    => $adminUserId,
                            'updated_by'    => $adminUserId,
                            'created_at'    => now(),
                            'updated_at'    => now(),
                        ]
                    );
                }
            }
        }

        $this->seedWaterFiltrationAssets($districts, $kpiCategories, $adminUserId);
    }

    private function districtProfile(string $districtName, int $index): array
    {
        $known = [
            'lahore' => ['total_ucs' => 274, 'population_census_2023' => 13979000, 'total_tehsils' => 10, 'total_towns' => 9],
            'faisalabad' => ['total_ucs' => 353, 'population_census_2023' => 9075819, 'total_tehsils' => 6, 'total_towns' => 0],
            'rawalpindi' => ['total_ucs' => 179, 'population_census_2023' => 5745964, 'total_tehsils' => 7, 'total_towns' => 0],
            'multan' => ['total_ucs' => 187, 'population_census_2023' => 5362305, 'total_tehsils' => 4, 'total_towns' => 0],
            'gujranwala' => ['total_ucs' => 173, 'population_census_2023' => 5959750, 'total_tehsils' => 5, 'total_towns' => 0],
            'sargodha' => ['total_ucs' => 192, 'population_census_2023' => 4334448, 'total_tehsils' => 7, 'total_towns' => 0],
            'gujrat' => ['total_ucs' => 135, 'population_census_2023' => 3257767, 'total_tehsils' => 3, 'total_towns' => 0],
            'layyah' => ['total_ucs' => 53, 'population_census_2023' => 2102386, 'total_tehsils' => 3, 'total_towns' => 0],
            'bahawalpur' => ['total_ucs' => 114, 'population_census_2023' => 4284964, 'total_tehsils' => 6, 'total_towns' => 5],
            'bahawalnagar' => ['total_ucs' => 141, 'population_census_2023' => 3550000, 'total_tehsils' => 5, 'total_towns' => 3],
            'attock' => ['total_ucs' => 77, 'population_census_2023' => 2170423, 'total_tehsils' => 6, 'total_towns' => 4],
        ];

        $key = Str::lower($districtName);

        $base = $known[$key] ?? [
            'total_ucs' => 45 + (($index * 13) % 155),
            'population_census_2023' => 900000 + (($index + 1) * 185000),
            'total_tehsils' => 2 + ($index % 6),
            'total_towns' => $index % 4,
        ];

        return [
            'baseline_year' => 2026,
            'total_ucs' => (int) $base['total_ucs'],
            'population_census_2023' => (int) $base['population_census_2023'],
            'total_tehsils' => (int) $base['total_tehsils'],
            'total_towns' => (int) $base['total_towns'],
        ];
    }

    private function buildBaselinePayload(string $districtName, array $districtProfile, string $categoryName, int $districtIndex, int $year): array
    {
        $name = Str::lower($categoryName);
        $population = (int) $districtProfile['population_census_2023'];
        $ucs = (int) $districtProfile['total_ucs'];

        $common = [
            'baseline_year' => $year,
            'total_ucs' => $ucs,
            'population_census_2023' => $population,
            'total_tehsils' => (int) $districtProfile['total_tehsils'],
            'total_towns' => (int) $districtProfile['total_towns'],
        ];

        if (str_contains($name, 'education') || str_contains($name, 'school') || str_contains($name, 'institution')) {
            $primary = max(40, (int) round($ucs * 5.7));
            $middle = max(20, (int) round($ucs * 1.35));
            $high = max(25, (int) round($ucs * 1.55));
            $higher = max(5, (int) round($ucs * 0.14));
            $colleges = max(3, (int) round($ucs * 0.12));
            $special = max(1, (int) round($ucs * 0.04));

            return $common + [
                'primary_schools' => $primary,
                'middle_schools' => $middle,
                'high_schools' => $high,
                'higher_secondary_schools' => $higher,
                'degree_colleges' => $colleges,
                'special_children_institutions' => $special,
                'total_educational_institutions' => $primary + $middle + $high + $higher + $colleges + $special,
            ];
        }

        if (str_contains($name, 'health') || str_contains($name, 'bhu') || str_contains($name, 'rhc')) {
            $bhus = max(15, (int) round($ucs * 0.55));
            $rhcs = max(2, (int) round($ucs * 0.07));
            $privateFacilities = max(20, (int) round($population / 18000));
            $drugStores = max(35, (int) round($population / 9000));

            return $common + [
                'bhus' => $bhus,
                'rhcs' => $rhcs,
                'total_health_facilities' => $bhus + $rhcs,
                'registered_private_health_care_facilities' => $privateFacilities,
                'registered_drug_stores' => $drugStores,
            ];
        }

        if (str_contains($name, 'water filtration') || str_contains($name, 'filtration')) {
            $totalPlants = max(10, (int) round($ucs * 1.25));
            $functional = (int) round($totalPlants * 0.78);
            $withFilterRecord = (int) round($totalPlants * 0.68);

            return $common + [
                'total_water_filtration_plants' => $totalPlants,
                'functional_plants' => $functional,
                'non_functional_plants' => $totalPlants - $functional,
                'plants_with_filter_change_record' => $withFilterRecord,
                'plants_without_filter_change_record' => $totalPlants - $withFilterRecord,
            ];
        }

        if (str_contains($name, 'manhole') || str_contains($name, 'sewer') || str_contains($name, 'drain') || str_contains($name, 'street light')) {
            $streetLights = max(250, (int) round($population / 45));
            $functionalLights = (int) round($streetLights * 0.88);
            $manholes = max(300, (int) round($population / 32));
            $coveredManholes = (int) round($manholes * 0.84);

            return $common + [
                'street_lights_total' => $streetLights,
                'street_lights_functional' => $functionalLights,
                'street_lights_non_functional' => $streetLights - $functionalLights,
                'sewerage_lines_length_km' => round($ucs * 8.5, 2),
                'water_drains_length_km' => round($ucs * 4.3, 2),
                'manholes_total' => $manholes,
                'manholes_covered' => $coveredManholes,
                'manholes_uncovered' => $manholes - $coveredManholes,
            ];
        }

        if (str_contains($name, 'road') || str_contains($name, 'bridge') || str_contains($name, 'infrastructure')) {
            $ruralRoads = round($ucs * 18.4, 2);
            $urbanRoads = round($ucs * 4.6, 2);

            return $common + [
                'rural_roads_length_km' => $ruralRoads,
                'urban_roads_length_km' => $urbanRoads,
                'total_roads_length_km' => round($ruralRoads + $urbanRoads, 2),
                'bridges_total' => max(1, (int) round($ucs * 0.09)),
                'dilapidated_bridges' => max(0, (int) round($ucs * 0.015)),
                'zebra_crossings' => max(1, (int) round($ucs * 0.07)),
            ];
        }

        if (str_contains($name, 'marriage')) {
            $total = max(8, (int) round($population / 42000));
            return $common + [
                'total_marriage_halls' => $total,
                'registered_marriage_halls' => (int) round($total * 0.82),
                'unregistered_marriage_halls' => (int) round($total * 0.18),
                'halls_checked_for_one_dish_compliance' => (int) round($total * 0.65),
            ];
        }

        if (str_contains($name, 'fertilizer') || str_contains($name, 'pesticide')) {
            $salePoints = max(15, (int) round($ucs * 1.75));
            return $common + [
                'fertilizer_and_pesticide_sale_points' => $salePoints,
                'registered_sale_points' => (int) round($salePoints * 0.86),
                'unregistered_sale_points' => (int) round($salePoints * 0.14),
                'critical_market_points' => max(1, (int) round($ucs * 0.08)),
            ];
        }

        if (str_contains($name, 'food') || str_contains($name, 'tandoor') || str_contains($name, 'bakery')) {
            $foodBusinesses = max(80, (int) round($population / 550));
            return $common + [
                'licensed_food_businesses' => $foodBusinesses,
                'total_tandoors' => max(50, (int) round($population / 3200)),
                'bakery_brands_local_manufacturers' => max(4, (int) round($ucs * 0.22)),
            ];
        }

        if (str_contains($name, 'park') || str_contains($name, 'greenbelt') || str_contains($name, 'bus terminal')) {
            return $common + [
                'total_parks' => max(2, (int) round($ucs * 0.18)),
                'tehsils_towns_having_greenbelts' => max(1, (int) round((int) $districtProfile['total_tehsils'] * 0.7)),
                'bus_terminals' => max(1, (int) round((int) $districtProfile['total_tehsils'] * 1.4)),
                'graveyards_total' => max(10, (int) round($ucs * 1.6)),
            ];
        }

        // Generic old-PPMF-style fallback for any remaining KPI category.
        $totalUnits = max(30, (int) round($ucs * (1.2 + (($districtIndex % 5) * 0.18))));
        $compliant = (int) round($totalUnits * 0.72);

        return $common + [
            'total_baseline_units' => $totalUnits,
            'compliant_units' => $compliant,
            'non_compliant_units' => $totalUnits - $compliant,
            'critical_points_identified' => max(1, (int) round($totalUnits * 0.08)),
        ];
    }

    private function seedWaterFiltrationAssets($districts, $kpiCategories, int $adminUserId): void
    {
        $waterFiltrationCategoryId = $kpiCategories
            ->first(fn ($cat) => str_contains(Str::lower((string) $cat->name), 'water filtration'))?->id
            ?? $kpiCategories->first()?->id;

        if (! $waterFiltrationCategoryId) {
            return;
        }

        DB::table('baseline_assets')->where('detail_data->source', 'Bulk Dummy Seeder')->delete();

        $tehsilsByDistrict = DB::table('tehsils')
            ->select(['id', 'district_id', 'name'])
            ->get()
            ->groupBy('district_id');

        $records = [];
        $plantIndex = 1;

        foreach ($districts as $districtIndex => $district) {
            $districtTehsils = $tehsilsByDistrict->get($district->id, collect());
            $nameLower = Str::lower((string) $district->name);
            $assetCount = in_array($nameLower, ['lahore', 'layyah'], true) ? 80 : 20;

            for ($i = 1; $i <= $assetCount; $i++) {
                $tehsil = $districtTehsils->isNotEmpty() ? $districtTehsils->random() : null;

                $centerLat = $nameLower === 'lahore' ? 31.5204 : ($nameLower === 'layyah' ? 30.9648 : (29.5 + (($districtIndex % 14) * 0.28)));
                $centerLng = $nameLower === 'lahore' ? 74.3587 : ($nameLower === 'layyah' ? 70.9399 : (70.0 + (($districtIndex % 18) * 0.22)));

                $records[] = [
                    'kpi_category_id' => $waterFiltrationCategoryId,
                    'division_id'     => $district->division_id,
                    'district_id'     => $district->id,
                    'tehsil_id'       => $tehsil?->id,
                    'name'            => 'Water Filtration Plant ' . $plantIndex,
                    'address'         => 'Plant Address ' . $plantIndex . ', ' . $district->name,
                    'latitude'        => $centerLat + (rand(-120, 120) / 10000),
                    'longitude'       => $centerLng + (rand(-120, 120) / 10000),
                    'baseline_date'   => now()->subDays(rand(0, 180))->toDateString(),
                    'status'          => collect(['functional', 'functional', 'functional', 'non_functional'])->random(),
                    'detail_data'     => json_encode([
                        'source'                  => 'Bulk Dummy Seeder',
                        'plant_type'              => collect(['RO', 'UF', 'Community Filter Plant'])->random(),
                        'union_council'           => 'UC-' . rand(1, 180),
                        'installed_by'            => collect(['Local Government', 'Public Health Engineering', 'District Administration'])->random(),
                        'filter_change_status'    => collect(['Mentioned', 'Not Mentioned'])->random(),
                        'last_filter_change_date' => now()->subDays(rand(10, 260))->toDateString(),
                        'district'                => $district->name,
                        'tehsil'                  => $tehsil?->name,
                    ], JSON_UNESCAPED_UNICODE),
                    'created_by'      => $adminUserId,
                    'updated_by'      => $adminUserId,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ];

                $plantIndex++;
            }
        }

        foreach (array_chunk($records, 500) as $chunk) {
            DB::table('baseline_assets')->insert($chunk);
        }
    }
}
