<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kpi_inspections', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->nullable()->unique();
            $table->string('reference_no')->unique();
            $table->foreignId('kpi_card_id')->constrained('kpi_cards')->cascadeOnDelete();
            $table->foreignId('kpi_submission_id')->nullable()->constrained('kpi_submissions')->nullOnDelete();
            $table->foreignId('division_id')->nullable()->constrained('divisions')->nullOnDelete();
            $table->foreignId('district_id')->nullable()->constrained('districts')->nullOnDelete();
            $table->foreignId('tehsil_id')->nullable()->constrained('tehsils')->nullOnDelete();
            $table->foreignId('inspected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('inspection_title');
            $table->string('entity_name')->nullable();
            $table->string('entity_type')->nullable();
            $table->string('identifier')->nullable();
            $table->text('address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->dateTime('inspection_datetime');
            $table->string('status')->default('pending_review');
            $table->json('observations')->nullable();
            $table->json('actions_required')->nullable();
            $table->json('actions_taken')->nullable();
            $table->json('detail_data')->nullable();
            $table->text('review_remarks')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->boolean('is_demo')->default(false);
            $table->string('seed_batch')->nullable();
            $table->timestamps();

            $table->index(['kpi_card_id', 'status']);
            $table->index(['district_id', 'tehsil_id']);
            $table->index('inspection_datetime');
        });

        Schema::create('kpi_inspection_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kpi_inspection_id')->constrained('kpi_inspections')->cascadeOnDelete();
            $table->string('file_path');
            $table->string('file_name')->nullable();
            $table->string('file_type')->nullable();
            $table->string('mime_type')->nullable();
            $table->string('caption')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_inspection_attachments');
        Schema::dropIfExists('kpi_inspections');
    }
};
