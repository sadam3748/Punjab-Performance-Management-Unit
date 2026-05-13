<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PpmfDummyBaselineSeeder extends Seeder
{
    public function run(): void
    {
        $adminUserId = DB::table('users')->where('username', 'super.admin')->value('id');

        $waterFiltrationCategoryId = DB::table('kpi_categories')
            ->whereRaw('LOWER(name) LIKE ?', ['%water filtration%'])
            ->value('id');

        if (! $waterFiltrationCategoryId) {
            $waterFiltrationCategoryId = DB::table('kpi_categories')->value('id');
        }

        if (! $waterFiltrationCategoryId) {
            return;
        }

        $lahoreDistrict = DB::table('districts')->whereRaw('LOWER(name) = ?', ['lahore'])->first();
        $layyahDistrict = DB::table('districts')->whereRaw('LOWER(name) = ?', ['layyah'])->first();

        $districts = collect([$lahoreDistrict, $layyahDistrict])->filter();

        if ($districts->isEmpty()) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | District Summary Baseline Data
        |--------------------------------------------------------------------------
        */

        foreach ($districts as $district) {
            DB::table('district_baseline_data')->updateOrInsert(
                [
                    'district_id'     => $district->id,
                    'kpi_category_id' => $waterFiltrationCategoryId,
                    'year'            => 2026,
                ],
                [
                    'baseline_data' => json_encode([
                        'total_water_filtration_plants'       => $district->name === 'Layyah' ? 75 : 120,
                        'functional_plants'                   => $district->name === 'Layyah' ? 58 : 96,
                        'non_functional_plants'               => $district->name === 'Layyah' ? 17 : 24,
                        'plants_with_filter_change_record'    => $district->name === 'Layyah' ? 49 : 82,
                        'plants_without_filter_change_record' => $district->name === 'Layyah' ? 26 : 38,
                    ]),
                    'created_by'    => $adminUserId,
                    'updated_by'    => $adminUserId,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Asset-Level Baseline Data
        |--------------------------------------------------------------------------
        */

        DB::table('baseline_assets')->where('detail_data->source', 'Bulk Dummy Seeder')->delete();

        $records = [];

        for ($i = 1; $i <= 120; $i++) {
            $district = $i <= 80 ? $layyahDistrict : $lahoreDistrict;

            if (! $district) {
                $district = $districts->random();
            }

            $tehsil = DB::table('tehsils')
                ->where('district_id', $district->id)
                ->inRandomOrder()
                ->first();

            $records[] = [
                'kpi_category_id' => $waterFiltrationCategoryId,
                'division_id'     => $district->division_id,
                'district_id'     => $district->id,
                'tehsil_id'       => $tehsil?->id,
                'name'            => 'Water Filtration Plant ' . $i,
                'address'         => 'Plant Address ' . $i . ', ' . $district->name,
                'latitude'        => $district->name === 'Layyah'
                    ? 30.9648 + (rand(-100, 100) / 10000)
                    : 31.5204 + (rand(-100, 100) / 10000),
                'longitude'       => $district->name === 'Layyah'
                    ? 70.9399 + (rand(-100, 100) / 10000)
                    : 74.3587 + (rand(-100, 100) / 10000),
                'baseline_date'   => now()->subDays(rand(0, 60))->toDateString(),
                'status'          => collect(['functional', 'functional', 'functional', 'non_functional'])->random(),
                'detail_data'     => json_encode([
                    'source'                  => 'Bulk Dummy Seeder',
                    'plant_type'              => collect(['RO', 'UF', 'Community Filter Plant'])->random(),
                    'union_council'           => 'UC-' . rand(1, 120),
                    'installed_by'            => collect(['Local Government', 'Public Health Engineering', 'District Administration'])->random(),
                    'filter_change_status'    => collect(['Mentioned', 'Not Mentioned'])->random(),
                    'last_filter_change_date' => now()->subDays(rand(10, 180))->toDateString(),
                ]),
                'created_by'      => $adminUserId,
                'updated_by'      => $adminUserId,
                'created_at'      => now(),
                'updated_at'      => now(),
            ];
        }

        foreach (array_chunk($records, 50) as $chunk) {
            DB::table('baseline_assets')->insert($chunk);
        }
    }
}
