<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KpiFormFieldSeeder extends Seeder
{
    public function run(): void
    {
        foreach (DB::table('kpi_cards')->get() as $card) {
            $metrics = json_decode($card->metric_config ?: '[]', true);
            foreach ($metrics as $index => $metric) {
                DB::table('kpi_form_fields')->updateOrInsert(
                    ['kpi_card_id' => $card->id, 'field_name' => $metric['field']],
                    ['field_label' => $metric['label'], 'field_type' => 'number', 'options' => null, 'is_required' => true, 'sort_order' => $index + 1, 'created_at' => now(), 'updated_at' => now()]
                );
            }
            DB::table('kpi_form_fields')->updateOrInsert(
                ['kpi_card_id' => $card->id, 'field_name' => 'field_observation'],
                ['field_label' => 'Field Observation', 'field_type' => 'textarea', 'options' => null, 'is_required' => false, 'sort_order' => 99, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
