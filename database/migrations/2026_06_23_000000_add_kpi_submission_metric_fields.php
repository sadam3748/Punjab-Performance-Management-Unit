<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kpi_submissions', function (Blueprint $table) {
            $table->decimal('target_value', 10, 2)->default(0)->after('score');
            $table->decimal('reported_value', 10, 2)->default(0)->after('target_value');
            $table->decimal('achieved_value', 10, 2)->default(0)->after('reported_value');
            $table->decimal('pending_value', 10, 2)->default(0)->after('achieved_value');
            $table->decimal('achievement_percentage', 5, 2)->default(0)->after('pending_value');
            $table->string('week_no', 10)->nullable()->after('period_label');
            $table->date('week_start_date')->nullable()->after('week_no');
            $table->date('week_end_date')->nullable()->after('week_start_date');
            $table->string('area_level', 20)->nullable()->after('tehsil_id');
            $table->unsignedSmallInteger('evidence_count')->default(0)->after('remarks');
            $table->json('metric_snapshot')->nullable()->after('evidence_count');
        });
    }

    public function down(): void
    {
        Schema::table('kpi_submissions', function (Blueprint $table) {
            $table->dropColumn([
                'target_value', 'reported_value', 'achieved_value', 'pending_value',
                'achievement_percentage', 'week_no', 'week_start_date', 'week_end_date',
                'area_level', 'evidence_count', 'metric_snapshot',
            ]);
        });
    }
};
