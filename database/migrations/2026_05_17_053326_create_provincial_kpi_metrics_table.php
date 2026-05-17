<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provincial_kpi_metrics', function (Blueprint $table) {
            $table->id();

            $table->foreignId('kpi_category_id')
                ->constrained('kpi_categories')
                ->cascadeOnDelete();

            $table->string('period_type')->default('weekly');
            // current_week, last_week, last_four_weeks, custom

            $table->date('date_from')->nullable();
            $table->date('date_to')->nullable();

            $table->string('metric_title');
            $table->text('metric_description')->nullable();

            $table->decimal('metric_value', 15, 2)->default(0);
            $table->string('metric_unit')->nullable();
            // count, percent, visits, inspections, actions, fines

            $table->string('source')->nullable();
            // DC, AC, PCM, Special Branch, Citizen, System

            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index('kpi_category_id');
            $table->index('period_type');
            $table->index(['date_from', 'date_to']);
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provincial_kpi_metrics');
    }
};
