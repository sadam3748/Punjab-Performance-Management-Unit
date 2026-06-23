<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inspections', function (Blueprint $table) {
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
                ->constrained('tehsils')
                ->cascadeOnDelete();

            $table->foreignId('performed_by')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->dateTime('inspection_datetime');

            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->string('main_title')->nullable();
            $table->text('main_address')->nullable();

            $table->jsonb('detail_data')->nullable();
            $table->jsonb('observations')->nullable();
            $table->jsonb('actions')->nullable();

            $table->string('status')->default('submitted');
            $table->text('remarks')->nullable();

            $table->timestamps();

            $table->index('kpi_category_id');
            $table->index('division_id');
            $table->index('district_id');
            $table->index('tehsil_id');
            $table->index('performed_by');
            $table->index('inspection_datetime');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspections');
    }
};
