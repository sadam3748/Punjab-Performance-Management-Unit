<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kpi_cards', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('category')->index();
            $table->text('description')->nullable();
            $table->string('icon')->default('bi-speedometer2');
            $table->enum('frequency', ['daily', 'weekly', 'monthly', 'yearly'])->default('monthly');
            $table->decimal('total_marks', 10, 2)->default(100);
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('display_order')->default(0);
            $table->json('metric_config')->nullable();
            $table->timestamps();
        });

        Schema::create('kpi_form_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kpi_card_id')->constrained()->cascadeOnDelete();
            $table->string('field_label');
            $table->string('field_name');
            $table->enum('field_type', ['text', 'number', 'date', 'textarea', 'select', 'radio', 'checkbox'])->default('text');
            $table->json('options')->nullable();
            $table->boolean('is_required')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['kpi_card_id', 'field_name']);
        });

        Schema::create('kpi_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kpi_card_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('division_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('district_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('tehsil_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('kpi_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kpi_card_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('division_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('district_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('tehsil_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('period_type', ['daily', 'weekly', 'monthly', 'yearly']);
            $table->string('period_label');
            $table->date('submission_date');
            $table->enum('status', ['draft', 'submitted', 'pending', 'approved', 'rejected'])->default('submitted')->index();
            $table->decimal('score', 10, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('kpi_submission_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained('kpi_submissions')->cascadeOnDelete();
            $table->foreignId('field_id')->constrained('kpi_form_fields')->cascadeOnDelete();
            $table->text('value')->nullable();
            $table->timestamps();
            $table->unique(['submission_id', 'field_id']);
        });

        Schema::create('kpi_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kpi_card_id')->constrained()->cascadeOnDelete();
            $table->foreignId('submission_id')->unique()->constrained('kpi_submissions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('division_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('district_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('tehsil_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('score', 10, 2)->default(0);
            $table->decimal('percentage', 5, 2)->default(0);
            $table->string('grade', 5)->nullable();
            $table->string('performance_label')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_scores');
        Schema::dropIfExists('kpi_submission_values');
        Schema::dropIfExists('kpi_submissions');
        Schema::dropIfExists('kpi_assignments');
        Schema::dropIfExists('kpi_form_fields');
        Schema::dropIfExists('kpi_cards');
    }
};
