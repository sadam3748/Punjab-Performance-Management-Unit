<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KpiScoreSeeder extends Seeder
{
    public function run(): void
    {
        foreach (DB::table('kpi_submissions')->get() as $submission) {
            $percentage = min(100, (float) $submission->score);
            $grade = $percentage >= 90 ? 'A+' : ($percentage >= 80 ? 'A' : ($percentage >= 70 ? 'B' : ($percentage >= 60 ? 'C' : 'D')));
            $label = $percentage >= 85 ? 'Excellent' : ($percentage >= 70 ? 'Good' : ($percentage >= 50 ? 'Needs Attention' : 'Critical'));
            DB::table('kpi_scores')->updateOrInsert(['submission_id' => $submission->id], [
                'kpi_card_id' => $submission->kpi_card_id, 'user_id' => $submission->user_id, 'division_id' => $submission->division_id,
                'district_id' => $submission->district_id, 'tehsil_id' => $submission->tehsil_id, 'score' => $submission->score,
                'percentage' => $percentage, 'grade' => $grade, 'performance_label' => $label,
                'created_at' => $submission->created_at, 'updated_at' => $submission->updated_at,
            ]);
        }
    }
}
