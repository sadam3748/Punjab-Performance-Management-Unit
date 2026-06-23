<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('baseline_assets', function (Blueprint $table) {
            $table->id();

            $table->foreignId('kpi_category_id')
                ->constrained('kpi_categories')
                ->cascadeOnDelete();

            $table->foreignId('division_id')
                ->nullable()
                ->constrained('divisions')
                ->nullOnDelete();

            $table->foreignId('district_id')
                ->constrained('districts')
                ->cascadeOnDelete();

            $table->foreignId('tehsil_id')
                ->nullable()
                ->constrained('tehsils')
                ->nullOnDelete();

            // Common asset fields from baseline Excel files
            $table->string('name')->nullable();
            $table->text('address')->nullable();

            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->date('baseline_date')->nullable();

            // Example: functional, non_functional, active, inactive, verified
            $table->string('status')->nullable();

            // Category-specific extra fields from Excel
            $table->jsonb('detail_data')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index('kpi_category_id');
            $table->index('division_id');
            $table->index('district_id');
            $table->index('tehsil_id');
            $table->index('status');
            $table->index('baseline_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('baseline_assets');
    }
};
