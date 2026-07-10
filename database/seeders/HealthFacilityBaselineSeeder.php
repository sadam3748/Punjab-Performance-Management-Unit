<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HealthFacilityBaselineSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('health_facility_baselines')->truncate();

        $rows = array_merge(
            $this->facilitiesForTehsil(
                divisionId: 2,
                districtId: 7,
                tehsilId: 24,
                tehsilName: 'Layyah',
                districtName: 'Layyah',
                codePrefix: 'HSF-LAY',
                count: 20,
                baseLat: 30.9617,
                baseLng: 70.9397,
            ),
            $this->facilitiesForTehsil(
                divisionId: 2,
                districtId: 7,
                tehsilId: 25,
                tehsilName: 'Karor Lal Esan',
                districtName: 'Layyah',
                codePrefix: 'HSF-KLE',
                count: 28,
                baseLat: 30.9520,
                baseLng: 70.9280,
            ),
        );

        DB::table('health_facility_baselines')->insert($rows);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function facilitiesForTehsil(
        int $divisionId,
        int $districtId,
        int $tehsilId,
        string $tehsilName,
        string $districtName,
        string $codePrefix,
        int $count,
        float $baseLat,
        float $baseLng,
    ): array {
        $types = ['BHU', 'RHC', 'THQ Hospital', 'DHQ Hospital', 'Dispensary'];
        $rows = [];
        $now = now();

        for ($i = 0; $i < $count; $i++) {
            $type = $types[$i % count($types)];
            $number = str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT);
            $code = sprintf('%s-%s', $codePrefix, $number);
            $name = sprintf('%s %s — %s', $type, $tehsilName, $number);
            $angle = ($i / max(1, $count)) * (M_PI * 2);
            $radius = 0.012 + (($i % 5) * 0.0025);
            $lat = round($baseLat + cos($angle) * $radius, 7);
            $lng = round($baseLng + sin($angle) * $radius, 7);
            $street = sprintf('Health Facility Road %d', $i + 1);

            $rows[] = [
                'division_id' => $divisionId,
                'district_id' => $districtId,
                'tehsil_id' => $tehsilId,
                'facility_code' => $code,
                'name' => $name,
                'facility_type' => $type,
                'address' => sprintf(
                    'Plot %d, %s, %s Tehsil, %s District, Punjab, Pakistan',
                    10 + $i,
                    $street,
                    $tehsilName,
                    $districtName,
                ),
                'latitude' => $lat,
                'longitude' => $lng,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        return $rows;
    }
}
