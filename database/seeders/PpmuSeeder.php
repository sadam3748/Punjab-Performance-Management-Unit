<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PpmuSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        DB::table('kpi_inspection_attachments')->truncate();
        DB::table('kpi_inspections')->truncate();
        DB::table('kpi_scores')->truncate();
        DB::table('kpi_submission_values')->truncate();
        DB::table('kpi_submissions')->truncate();
        Schema::enableForeignKeyConstraints();

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
            KpiInspectionSeeder::class,
            KpiScoreSeeder::class,
        ]);
    }
}
