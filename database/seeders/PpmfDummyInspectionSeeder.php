<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PpmfDummyInspectionSeeder extends Seeder
{
    public function run(): void
    {
        $lahoreDistrict = DB::table('districts')->whereRaw('LOWER(name) = ?', ['lahore'])->first();
        $layyahDistrict = DB::table('districts')->whereRaw('LOWER(name) = ?', ['layyah'])->first();

        $districts = collect([$lahoreDistrict, $layyahDistrict])->filter();

        if ($districts->isEmpty()) {
            return;
        }

        $fieldUsers = DB::table('users')
            ->whereIn('role_id', [6, 7])
            ->get();

        if ($fieldUsers->isEmpty()) {
            return;
        }

        $categoryIds = DB::table('kpi_categories')->pluck('id')->toArray();

        if (empty($categoryIds)) {
            return;
        }

        DB::table('inspections')->where('remarks', 'LIKE', 'Bulk dummy inspection%')->delete();

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

            $user = $fieldUsers->random();

            $kpiCategoryId = $categoryIds[array_rand($categoryIds)];

            $inspectionDate = now()
                ->subDays(rand(0, 45))
                ->setTime(rand(8, 18), rand(0, 59), 0);

            $mainTitle   = 'PPMF Inspection Record ' . $i;
            $mainAddress = 'Sample Address ' . $i . ', ' . $district->name;

            $detailData = [
                'reference_no'       => 'PPMF-INS-' . str_pad($i, 5, '0', STR_PAD_LEFT),
                'union_council'      => 'UC-' . rand(1, 120),
                'inspection_source'  => 'Dummy Seeder',
                'sample_field_value' => 'Test value ' . $i,
            ];

            $observations = [
                'cleanliness_satisfactory' => rand(0, 1) ? 'Yes' : 'No',
                'record_available'         => rand(0, 1) ? 'Yes' : 'No',
                'status_functional'        => rand(0, 1) ? 'Yes' : 'No',
            ];

            $actions = [
                'warning_issued'     => rand(0, 1) ? 'Yes' : 'No',
                'fine'               => rand(0, 1) ? 'Yes' : 'No',
                'fine_amount'        => rand(0, 1) ? rand(1000, 20000) : 0,
                'follow_up_required' => rand(0, 1) ? 'Yes' : 'No',
            ];

            $records[] = [
                'kpi_category_id'     => $kpiCategoryId,
                'division_id'         => $district->division_id,
                'district_id'         => $district->id,
                'tehsil_id'           => $tehsil?->id,
                'performed_by'        => $user->id,
                'inspection_datetime' => $inspectionDate,
                'latitude'            => $district->name === 'Layyah'
                    ? 30.9648 + (rand(-100, 100) / 10000)
                    : 31.5204 + (rand(-100, 100) / 10000),
                'longitude'           => $district->name === 'Layyah'
                    ? 70.9399 + (rand(-100, 100) / 10000)
                    : 74.3587 + (rand(-100, 100) / 10000),
                'main_title'          => $mainTitle,
                'main_address'        => $mainAddress,
                'detail_data'         => json_encode($detailData),
                'observations'        => json_encode($observations),
                'actions'             => json_encode($actions),
                'status'              => collect(['submitted', 'reviewed', 'approved', 'rejected'])->random(),
                'remarks'             => 'Bulk dummy inspection record for dashboard testing.',
                'created_at'          => now(),
                'updated_at'          => now(),
            ];
        }

        foreach (array_chunk($records, 50) as $chunk) {
            DB::table('inspections')->insert($chunk);
        }
    }
}
