<?php
namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            DivisionSeeder::class,
            DistrictSeeder::class,
            TehsilSeeder::class,

            RoleSeeder::class,
            KpiCategorySeeder::class,
            GeoTaggingTypeSeeder::class,

            AdminUserSeeder::class,

            PpmfDummyUserSeeder::class,
            // PpmfDummyDataSeeder::class,
            PpmfDummyGeoTaggingSeeder::class,
            PpmfDummyBaselineSeeder::class,
            PpmfDummyInspectionSeeder::class,
            KpiMetricValueSeeder::class,

            // Old PPMF-like CM Governance Scorecard (parameter/weightage based)
            KpiScoringParameterSeeder::class,
            DistrictKpiScoreSeeder::class,
            DistrictKpiScoreDetailSeeder::class,
        ]);
    }
}
