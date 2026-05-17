<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('district_kpi_metric_values', function (Blueprint $table) {
            $table->id();

            $table->foreignId('district_id')
                ->constrained('districts')
                ->cascadeOnDelete();

            $table->foreignId('kpi_category_id')
                ->constrained('kpi_categories')
                ->cascadeOnDelete();

            $table->foreignId('provincial_kpi_metric_id')
                ->nullable()
                ->constrained('provincial_kpi_metrics')
                ->nullOnDelete();

            $table->string('period_type')->default('last_week');
            // current_week, last_week, last_four_weeks, custom

            $table->date('date_from')->nullable();
            $table->date('date_to')->nullable();

            $table->string('metric_title');
            $table->decimal('metric_value', 15, 2)->default(0);
            $table->decimal('metric_score', 8, 2)->nullable();
            $table->string('metric_unit')->nullable();
            $table->text('evidence')->nullable();

            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['district_id', 'kpi_category_id']);
            $table->index('period_type');
            $table->index(['date_from', 'date_to']);
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('district_kpi_metric_values');
    }
};

