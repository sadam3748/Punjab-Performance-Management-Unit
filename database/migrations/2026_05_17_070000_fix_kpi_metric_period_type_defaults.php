<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fix legacy default "weekly" to "last_week" for old PPMF-style KPI reporting.
        // (Keeps app period_type values consistent: current_week, last_week, last_four_weeks, custom)
        if (Schema::hasTable('provincial_kpi_metrics')) {
            DB::table('provincial_kpi_metrics')
                ->where('period_type', 'weekly')
                ->update(['period_type' => 'last_week']);

            if (DB::getDriverName() === 'pgsql') {
                DB::statement("ALTER TABLE provincial_kpi_metrics ALTER COLUMN period_type SET DEFAULT 'last_week'");
            } else {
                DB::statement("ALTER TABLE provincial_kpi_metrics ALTER period_type SET DEFAULT 'last_week'");
            }
        }

        if (Schema::hasTable('district_kpi_metric_values')) {
            DB::table('district_kpi_metric_values')
                ->where('period_type', 'weekly')
                ->update(['period_type' => 'last_week']);

            if (DB::getDriverName() === 'pgsql') {
                DB::statement("ALTER TABLE district_kpi_metric_values ALTER COLUMN period_type SET DEFAULT 'last_week'");
            } else {
                DB::statement("ALTER TABLE district_kpi_metric_values ALTER period_type SET DEFAULT 'last_week'");
            }
        }
    }

    public function down(): void
    {
        // No-op: keep last_week default.
    }
};

