<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EducationInstitutionBaselineSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('education_institution_baselines')->truncate();

        $schools = [
            'Govt. Boys High School Karor Lal Esan',
            'Govt. Girls High School Karor Lal Esan',
            'Govt. Elementary School Chak No. 97/TDA',
            'Govt. Primary School Basti Gahi',
            'Govt. High School Layyah City',
            'Govt. Girls Elementary School Fatehpur',
            'Govt. Primary School Chowk Azam',
            'Govt. High School Chaubara',
        ];

        $rows = array_merge(
            $this->institutionsForTehsil(
                divisionId: 2,
                districtId: 7,
                tehsilId: 24,
                tehsilName: 'Layyah',
                districtName: 'Layyah',
                codePrefix: 'EDU-LAY',
                count: 20,
                baseLat: 30.9617,
                baseLng: 70.9397,
                schoolNames: $schools,
            ),
            $this->institutionsForTehsil(
                divisionId: 2,
                districtId: 7,
                tehsilId: 25,
                tehsilName: 'Karor Lal Esan',
                districtName: 'Layyah',
                codePrefix: 'EDU-KLE',
                count: 20,
                baseLat: 30.9520,
                baseLng: 70.9280,
                schoolNames: $schools,
            ),
            $this->institutionsForTehsil(
                divisionId: 2,
                districtId: 7,
                tehsilId: 26,
                tehsilName: 'Chaubara',
                districtName: 'Layyah',
                codePrefix: 'EDU-CHA',
                count: 18,
                baseLat: 30.9005,
                baseLng: 71.6512,
                schoolNames: $schools,
            ),
        );

        DB::table('education_institution_baselines')->insert($rows);
    }

    /**
     * @param  list<string>  $schoolNames
     * @return list<array<string, mixed>>
     */
    private function institutionsForTehsil(
        int $divisionId,
        int $districtId,
        int $tehsilId,
        string $tehsilName,
        string $districtName,
        string $codePrefix,
        int $count,
        float $baseLat,
        float $baseLng,
        array $schoolNames,
    ): array {
        $types = ['Primary School', 'Elementary School', 'High School', 'Girls High School'];
        $rows = [];
        $now = now();

        for ($i = 0; $i < $count; $i++) {
            $type = $types[$i % count($types)];
            $number = str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT);
            $code = sprintf('%s-%s', $codePrefix, $number);
            $name = $schoolNames[$i % count($schoolNames)];
            if ($i >= count($schoolNames)) {
                $name = sprintf('%s %s — %s', $type, $tehsilName, $number);
            }
            $angle = ($i / max(1, $count)) * (M_PI * 2);
            $radius = 0.012 + (($i % 5) * 0.0025);
            $lat = round($baseLat + cos($angle) * $radius, 7);
            $lng = round($baseLng + sin($angle) * $radius, 7);
            $street = sprintf('School Road %d, Near Main Bazar', $i + 1);

            $rows[] = [
                'division_id' => $divisionId,
                'district_id' => $districtId,
                'tehsil_id' => $tehsilId,
                'institution_code' => $code,
                'name' => $name,
                'institution_type' => $type,
                'address' => sprintf(
                    '%s, %s, %s Tehsil, %s District, Punjab, Pakistan',
                    $street,
                    $tehsilName,
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
