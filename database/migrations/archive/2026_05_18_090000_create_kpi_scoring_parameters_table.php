<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kpi_scoring_parameters', function (Blueprint $table) {
            $table->id();

            $table->foreignId('kpi_category_id')
                ->constrained('kpi_categories')
                ->cascadeOnDelete();

            $table->string('parameter_name');
            $table->text('description')->nullable();
            $table->decimal('weightage', 8, 2)->default(0);
            $table->string('unit')->nullable();

            $table->string('scoring_method')->default('percentage');
            // percentage, direct_score, yes_no, inverse_percentage

            $table->decimal('target_value', 15, 2)->nullable();
            $table->boolean('higher_is_better')->default(true);

            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index('kpi_category_id');
            $table->index('is_active');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_scoring_parameters');
    }
};

