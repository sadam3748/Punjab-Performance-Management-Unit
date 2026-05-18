<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('district_kpi_score_details', function (Blueprint $table) {
            $table->id();

            $table->foreignId('district_kpi_score_id')
                ->constrained('district_kpi_scores')
                ->cascadeOnDelete();

            $table->foreignId('kpi_scoring_parameter_id')
                ->constrained('kpi_scoring_parameters')
                ->cascadeOnDelete();

            $table->decimal('reported_value', 15, 2)->default(0);
            $table->decimal('target_value', 15, 2)->nullable();
            $table->decimal('achieved_percentage', 8, 2)->default(0);
            $table->decimal('weightage', 8, 2)->default(0);
            $table->decimal('score_obtained', 8, 2)->default(0);

            $table->text('evidence')->nullable();
            $table->jsonb('extra_data')->nullable();

            $table->timestamps();

            $table->index('district_kpi_score_id');
            $table->index('kpi_scoring_parameter_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('district_kpi_score_details');
    }
};

