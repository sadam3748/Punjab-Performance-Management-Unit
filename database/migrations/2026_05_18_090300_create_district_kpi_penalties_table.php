<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('district_kpi_penalties', function (Blueprint $table) {
            $table->id();

            $table->foreignId('district_kpi_score_id')
                ->constrained('district_kpi_scores')
                ->cascadeOnDelete();

            $table->string('penalty_type');
            $table->decimal('penalty_score', 8, 2)->default(0);
            $table->text('remarks')->nullable();

            $table->timestamps();

            $table->index('district_kpi_score_id');
            $table->index('penalty_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('district_kpi_penalties');
    }
};

