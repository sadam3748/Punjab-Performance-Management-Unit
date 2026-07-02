<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kpi_inspection_attachments', function (Blueprint $table) {
            $table->string('observation_key', 64)->nullable()->after('caption');
        });
    }

    public function down(): void
    {
        Schema::table('kpi_inspection_attachments', function (Blueprint $table) {
            $table->dropColumn('observation_key');
        });
    }
};
