<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kpi_scoring_parameters', function (Blueprint $table) {
            $table->string('parameter_slug')->nullable()->after('parameter_name');
            $table->string('formula_type')->default('percentage')->after('scoring_method');
            $table->text('formula_expression')->nullable()->after('formula_type');
            $table->string('numerator_label')->nullable()->after('formula_expression');
            $table->string('denominator_label')->nullable()->after('numerator_label');
            $table->decimal('tier_1_target', 15, 2)->nullable()->after('target_value');
            $table->decimal('tier_2_target', 15, 2)->nullable()->after('tier_1_target');
            $table->decimal('tier_3_target', 15, 2)->nullable()->after('tier_2_target');
            $table->unsignedInteger('display_order')->default(0)->after('sort_order');

            $table->unique(['kpi_category_id', 'parameter_slug'], 'kpi_parameter_category_slug_unique');
            $table->index('formula_type');
            $table->index('display_order');
        });

        Schema::table('district_kpi_score_details', function (Blueprint $table) {
            $table->decimal('numerator_value', 15, 2)->nullable()->after('reported_value');
            $table->decimal('denominator_value', 15, 2)->nullable()->after('numerator_value');
        });
    }

    public function down(): void
    {
        Schema::table('district_kpi_score_details', function (Blueprint $table) {
            $table->dropColumn(['numerator_value', 'denominator_value']);
        });

        Schema::table('kpi_scoring_parameters', function (Blueprint $table) {
            $table->dropUnique('kpi_parameter_category_slug_unique');
            $table->dropIndex(['formula_type']);
            $table->dropIndex(['display_order']);
            $table->dropColumn([
                'parameter_slug',
                'formula_type',
                'formula_expression',
                'numerator_label',
                'denominator_label',
                'tier_1_target',
                'tier_2_target',
                'tier_3_target',
                'display_order',
            ]);
        });
    }
};
