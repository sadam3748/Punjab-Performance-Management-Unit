<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PpmuSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DivisionSeeder::class,
            DistrictSeeder::class,
            TehsilSeeder::class,
            RoleSeeder::class,
            AdminUserSeeder::class,
            PpmuDemoUserSeeder::class,
            KpiCardSeeder::class,
            KpiFormFieldSeeder::class,
            KpiAssignmentSeeder::class,
            KpiSubmissionSeeder::class,
            KpiScoreSeeder::class,
        ]);
    }
}
