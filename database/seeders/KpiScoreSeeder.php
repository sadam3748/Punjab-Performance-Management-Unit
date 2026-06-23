<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KpiScoreSeeder extends Seeder
{
    public function run(): void
    {
        DB::disableQueryLog();

        $submissions = DB::table('kpi_submissions')
            ->select([
                'id as submission_id',
                'kpi_card_id',
                'user_id',
                'division_id',
                'district_id',
                'tehsil_id',
                'score',
                'achievement_percentage',
                'created_at',
                'updated_at',
            ])
            ->orderBy('id')
            ->get();

        if ($submissions->isEmpty()) {
            return;
        }

        $rows = [];
        foreach ($submissions as $submission) {
            $percentage = min(100, (float) ($submission->achievement_percentage ?? $submission->score));
            $rows[] = [
                'kpi_card_id' => $submission->kpi_card_id,
                'submission_id' => $submission->submission_id,
                'user_id' => $submission->user_id,
                'division_id' => $submission->division_id,
                'district_id' => $submission->district_id,
                'tehsil_id' => $submission->tehsil_id,
                'score' => $submission->score,
                'percentage' => $percentage,
                'grade' => $this->grade($percentage),
                'performance_label' => $this->performanceLabel($percentage),
                'created_at' => $submission->created_at,
                'updated_at' => $submission->updated_at,
            ];
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('kpi_scores')->insert($chunk);
        }
    }

    private function grade(float $percentage): string
    {
        return match (true) {
            $percentage >= 90 => 'A+',
            $percentage >= 80 => 'A',
            $percentage >= 70 => 'B',
            $percentage >= 60 => 'C',
            default => 'D',
        };
    }

    private function performanceLabel(float $percentage): string
    {
        return match (true) {
            $percentage >= 85 => 'Excellent',
            $percentage >= 70 => 'Good',
            $percentage >= 50 => 'Needs Attention',
            default => 'Critical',
        };
    }
}
