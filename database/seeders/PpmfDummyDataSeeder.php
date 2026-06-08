<?php
namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PpmfDummyDataSeeder extends Seeder
{
    public function run(): void
    {
        $districts = DB::table('districts')
            ->get();

        if ($districts->isEmpty()) {
            return;
        }

        $kpiCategoryIds = DB::table('kpi_categories')
            ->where('is_active', true)
            ->pluck('id')
            ->toArray();

        if (empty($kpiCategoryIds)) {
            return;
        }

        $fieldUsers = DB::table('users')
            ->whereIn('role_id', [6, 7])
            ->get();

        if ($fieldUsers->isEmpty()) {
            return;
        }

        $tehsils = DB::table('tehsils')
            ->get();

        if ($tehsils->isEmpty()) {
            return;
        }

        DB::table('inspections')
            ->where('remarks', 'LIKE', '%PPMF dummy%')
            ->delete();

        $statusWeights = [
            'approved'  => 55,
            'reviewed'  => 20,
            'submitted' => 15,
            'rejected'  => 10,
        ];

        $statusPool = [];
        foreach ($statusWeights as $status => $weight) {
            for ($i = 0; $i < $weight; $i++) {
                $statusPool[] = $status;
            }
        }

        $requiredStatuses = ['approved', 'reviewed', 'submitted', 'rejected'];
        $records          = [];
        $recordIndex      = 1;
        $now              = Carbon::now();

        foreach ($districts as $district) {
            $districtTehsils = $tehsils
                ->where('district_id', $district->id)
                ->values();

            $performedBy    = $fieldUsers->random();
            $inspectionDate = $now->copy()
                ->subDays(rand(0, 90))
                ->setTime(rand(8, 18), rand(0, 59), 0);

            foreach ($requiredStatuses as $status) {
                $tehsil = $districtTehsils->isNotEmpty()
                    ? $districtTehsils->random()
                    : null;

                $kpiCategoryId = $kpiCategoryIds[array_rand($kpiCategoryIds)];

                $records[] = [
                    'kpi_category_id'     => $kpiCategoryId,
                    'division_id'         => $district->division_id,
                    'district_id'         => $district->id,
                    'tehsil_id'           => $tehsil?->id,
                    'performed_by'        => $performedBy->id,
                    'inspection_datetime' => $inspectionDate,
                    'latitude'            => 30 + (rand(0, 600) / 100),
                    'longitude'           => 70 + (rand(0, 600) / 100),
                    'main_title'          => ' Inspection ' . $recordIndex,
                    'main_address'        => 'Sample Location, ' . $district->name,
                    'detail_data'         => json_encode([
                        'reference_no'    => 'PPMF-DUMMY-' . str_pad($recordIndex, 5, '0', STR_PAD_LEFT),
                        'inspection_area' => 'District ' . $district->name,
                        'category'        => $kpiCategoryId,
                        'dummy_record'    => true,
                    ]),
                    'observations'        => json_encode([
                        'cleanliness'      => rand(0, 1) ? 'Yes' : 'No',
                        'records_verified' => rand(0, 1) ? 'Yes' : 'No',
                        'facility_status'  => rand(0, 1) ? 'Functional' : 'Non Functional',
                    ]),
                    'actions'             => json_encode([
                        'follow_up'      => rand(0, 1) ? 'Required' : 'Not Required',
                        'warning_issued' => rand(0, 1) ? 'Yes' : 'No',
                        'fine_amount'    => rand(0, 1) ? rand(1000, 25000) : 0,
                    ]),
                    'status'              => $status,
                    'remarks'             => 'inspection record for scorecard testing.',
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ];

                $recordIndex++;
            }

            $additionalInspections = rand(8, 16);
            for ($i = 0; $i < $additionalInspections; $i++) {
                $kpiCategoryId = $kpiCategoryIds[array_rand($kpiCategoryIds)];
                $performedBy   = $fieldUsers->random();
                $tehsil        = $districtTehsils->isNotEmpty()
                    ? $districtTehsils->random()
                    : null;

                $inspectionDate = $now->copy()
                    ->subDays(rand(0, 90))
                    ->setTime(rand(8, 18), rand(0, 59), 0);

                $status = $statusPool[array_rand($statusPool)];

                $records[] = [
                    'kpi_category_id'     => $kpiCategoryId,
                    'division_id'         => $district->division_id,
                    'district_id'         => $district->id,
                    'tehsil_id'           => $tehsil?->id,
                    'performed_by'        => $performedBy->id,
                    'inspection_datetime' => $inspectionDate,
                    'latitude'            => 30 + (rand(0, 600) / 100),
                    'longitude'           => 70 + (rand(0, 600) / 100),
                    'main_title'          => ' Inspection ' . $recordIndex,
                    'main_address'        => 'Sample Location, ' . $district->name,
                    'detail_data'         => json_encode([
                        'reference_no'    => 'PPMF-DUMMY-' . str_pad($recordIndex, 5, '0', STR_PAD_LEFT),
                        'inspection_area' => 'District ' . $district->name,
                        'category'        => $kpiCategoryId,
                        'dummy_record'    => true,
                    ]),
                    'observations'        => json_encode([
                        'cleanliness'      => rand(0, 1) ? 'Yes' : 'No',
                        'records_verified' => rand(0, 1) ? 'Yes' : 'No',
                        'facility_status'  => rand(0, 1) ? 'Functional' : 'Non Functional',
                    ]),
                    'actions'             => json_encode([
                        'follow_up'      => rand(0, 1) ? 'Required' : 'Not Required',
                        'warning_issued' => rand(0, 1) ? 'Yes' : 'No',
                        'fine_amount'    => rand(0, 1) ? rand(1000, 25000) : 0,
                    ]),
                    'status'              => $status,
                    'remarks'             => ' inspection record for scorecard testing.',
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ];

                $recordIndex++;
            }
        }

        foreach (array_chunk($records, 50) as $chunk) {
            DB::table('inspections')->insert($chunk);
        }
    }
}
