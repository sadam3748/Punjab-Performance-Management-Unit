<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('districts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('division_id')
                ->constrained('divisions')
                ->cascadeOnDelete();

            $table->string('name');
            $table->string('code')->nullable()->unique();

            // For PPMF tier-wise district scorecard/reporting.
            $table->unsignedTinyInteger('tier')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('division_id');
            $table->index('name');
            $table->index('tier');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('districts');
    }
};
