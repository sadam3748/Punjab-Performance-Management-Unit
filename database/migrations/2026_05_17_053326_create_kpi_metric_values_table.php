<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kpi_metric_values', function (Blueprint $table) {
            $table->id();

            $table->foreignId('kpi_category_id')
                ->constrained('kpi_categories')
                ->cascadeOnDelete();

            $table->string('metric_key');
            $table->string('metric_title');

            $table->decimal('metric_value', 15, 2)->default(0);
            $table->decimal('metric_score', 8, 2)->nullable();
            $table->string('metric_unit')->nullable();

            // province, division, district, tehsil
            $table->string('area_level');

            $table->foreignId('division_id')->nullable()
                ->constrained('divisions')
                ->nullOnDelete();
            $table->foreignId('district_id')->nullable()
                ->constrained('districts')
                ->nullOnDelete();
            $table->foreignId('tehsil_id')->nullable()
                ->constrained('tehsils')
                ->nullOnDelete();

            // weekly, monthly, quarterly, yearly (graphical report currently uses weekly/monthly/yearly)
            $table->string('period_type')->default('weekly');
            $table->unsignedSmallInteger('year');
            $table->string('week_no')->nullable(); // YYYYWW
            $table->unsignedTinyInteger('month')->nullable(); // 1..12
            $table->unsignedTinyInteger('quarter')->nullable(); // 1..4
            $table->date('date_from')->nullable();
            $table->date('date_to')->nullable();

            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index('kpi_category_id');
            $table->index('metric_key');
            $table->index('area_level');
            $table->index('division_id');
            $table->index('district_id');
            $table->index('tehsil_id');
            $table->index(['period_type', 'year']);
            $table->index('week_no');
            $table->index('month');
            $table->index('is_active');
            $table->index(['date_from', 'date_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_metric_values');
    }
};

