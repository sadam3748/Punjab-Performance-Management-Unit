<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('geo_taggings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('geo_tagging_type_id')
                ->constrained('geo_tagging_types')
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

            $table->string('name')->nullable();
            $table->text('address')->nullable();

            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->dateTime('tagged_at')->nullable();

            $table->jsonb('detail_data')->nullable();

            $table->string('status')->default('submitted');
            $table->text('remarks')->nullable();

            $table->timestamps();

            $table->index('geo_tagging_type_id');
            $table->index('division_id');
            $table->index('district_id');
            $table->index('tehsil_id');
            $table->index('performed_by');
            $table->index('tagged_at');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('geo_taggings');
    }
};
