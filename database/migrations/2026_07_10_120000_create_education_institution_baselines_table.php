<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('education_institution_baselines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('division_id')->nullable()->constrained('divisions')->nullOnDelete();
            $table->foreignId('district_id')->nullable()->constrained('districts')->nullOnDelete();
            $table->foreignId('tehsil_id')->nullable()->constrained('tehsils')->nullOnDelete();
            $table->string('institution_code')->unique();
            $table->string('name');
            $table->string('institution_type')->nullable();
            $table->text('address')->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['tehsil_id', 'is_active']);
            $table->index(['district_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('education_institution_baselines');
    }
};
