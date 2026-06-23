<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('district_kpi_scores', function (Blueprint $table) {
            $table->id();

            $table->foreignId('division_id')
                ->nullable()
                ->constrained('divisions')
                ->nullOnDelete();

            $table->foreignId('district_id')
                ->constrained('districts')
                ->cascadeOnDelete();

            $table->foreignId('kpi_category_id')
                ->nullable()
                ->constrained('kpi_categories')
                ->nullOnDelete();

            $table->string('period_type')->default('weekly');
            // weekly, monthly, quarterly, yearly

            $table->string('week_no')->nullable(); // e.g. 202619
            $table->unsignedTinyInteger('month')->nullable();
            $table->unsignedTinyInteger('quarter')->nullable();
            $table->unsignedSmallInteger('year');
            $table->date('date_from')->nullable();
            $table->date('date_to')->nullable();

            $table->string('calculation_type')->default('general');
            // general, sixty_forty, negative_marking

            $table->decimal('reported_score', 8, 2)->default(0);
            $table->decimal('verified_score', 8, 2)->default(0);
            $table->decimal('penalty_score', 8, 2)->default(0);
            $table->decimal('final_score', 8, 2)->default(0);

            $table->string('grade')->nullable();
            $table->string('performance_label')->nullable();

            $table->boolean('is_reported')->default(true);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['district_id', 'kpi_category_id']);
            $table->index(['period_type', 'year']);
            $table->index('calculation_type');
            $table->index('final_score');
        });

        // Practical unique constraint for PostgreSQL with nullable columns.
        // Uses COALESCE to avoid duplicate "NULL" combinations slipping through.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("
                CREATE UNIQUE INDEX district_kpi_scores_unique_period
                ON district_kpi_scores (
                    district_id,
                    COALESCE(kpi_category_id, 0),
                    period_type,
                    COALESCE(week_no, ''),
                    COALESCE(month, 0),
                    COALESCE(quarter, 0),
                    year,
                    calculation_type
                )
            ");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS district_kpi_scores_unique_period');
        }

        Schema::dropIfExists('district_kpi_scores');
    }
};

