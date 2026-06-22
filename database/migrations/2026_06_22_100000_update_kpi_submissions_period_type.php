<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('kpi_submissions')) {
            return;
        }

        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE kpi_submissions DROP CONSTRAINT IF EXISTS kpi_submissions_period_type_check');
            DB::statement("ALTER TABLE kpi_submissions ADD CONSTRAINT kpi_submissions_period_type_check CHECK (period_type IN ('daily', 'weekly', 'monthly', 'yearly'))");
        } elseif ($driver === 'mysql') {
            DB::statement("ALTER TABLE kpi_submissions MODIFY period_type ENUM('daily', 'weekly', 'monthly', 'yearly') NOT NULL");
        }

        DB::table('kpi_submissions')
            ->where('period_type', 'quarterly')
            ->update(['period_type' => 'monthly']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('kpi_submissions')) {
            return;
        }

        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE kpi_submissions DROP CONSTRAINT IF EXISTS kpi_submissions_period_type_check');
            DB::statement("ALTER TABLE kpi_submissions ADD CONSTRAINT kpi_submissions_period_type_check CHECK (period_type IN ('weekly', 'monthly', 'quarterly', 'yearly'))");
        } elseif ($driver === 'mysql') {
            DB::statement("ALTER TABLE kpi_submissions MODIFY period_type ENUM('weekly', 'monthly', 'quarterly', 'yearly') NOT NULL");
        }
    }
};
