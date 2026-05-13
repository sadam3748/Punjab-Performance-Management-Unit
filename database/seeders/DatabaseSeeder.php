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
            PpmfDummyInspectionSeeder::class,
            PpmfDummyGeoTaggingSeeder::class,
            PpmfDummyBaselineSeeder::class,
        ]);
    }
}
