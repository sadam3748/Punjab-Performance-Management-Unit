<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kpi_inspections', function (Blueprint $table): void {
            $table->boolean('selected_for_review')->default(false)->after('status');
            $table->foreignId('selected_by')->nullable()->after('selected_for_review')->constrained('users')->nullOnDelete();
            $table->timestamp('selected_at')->nullable()->after('selected_by');
            $table->string('review_level', 40)->nullable()->after('selected_at');
            $table->index(['kpi_card_id', 'review_level', 'selected_for_review'], 'kpi_inspection_review_sample_idx');
        });
    }

    public function down(): void
    {
        Schema::table('kpi_inspections', function (Blueprint $table): void {
            $table->dropIndex('kpi_inspection_review_sample_idx');
            $table->dropConstrainedForeignId('selected_by');
            $table->dropColumn(['selected_for_review', 'selected_at', 'review_level']);
        });
    }
};
