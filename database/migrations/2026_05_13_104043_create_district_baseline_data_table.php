<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('district_baseline_data', function (Blueprint $table) {
            $table->id();

            $table->foreignId('district_id')
                ->constrained('districts')
                ->cascadeOnDelete();

            $table->foreignId('kpi_category_id')
                ->constrained('kpi_categories')
                ->cascadeOnDelete();

            $table->year('year')->nullable();

            // District/category summary baseline values
            $table->jsonb('baseline_data')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->unique(
                ['district_id', 'kpi_category_id', 'year'],
                'district_kpi_year_unique'
            );

            $table->index('district_id');
            $table->index('kpi_category_id');
            $table->index('year');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('district_baseline_data');
    }
};
