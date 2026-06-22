<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KpiSubmissionSeeder extends Seeder
{
    private const DEMO_USERNAMES = [
        'super_admin',
        'cs.pmru',
        'com.lahore',
        'dc.lahore',
        'ac.lahore',
        'com.dgkhan',
        'dc.layyah',
        'ac.layyah',
    ];

    private const PERIOD_TYPES = ['daily', 'weekly', 'monthly', 'yearly'];

    private const MONTH_OFFSETS = 3;

    private const STATUSES = ['approved', 'approved', 'submitted', 'pending', 'rejected'];

    public function run(): void
    {
        $users = DB::table('users')
            ->whereIn('username', self::DEMO_USERNAMES)
            ->where('is_active', true)
            ->get()
            ->keyBy('username');

        $cards = DB::table('kpi_cards')->where('is_active', true)->orderBy('display_order')->get();

        foreach ($cards as $cardIndex => $card) {
            $fields = DB::table('kpi_form_fields')
                ->where('kpi_card_id', $card->id)
                ->where('field_name', '!=', 'field_observation')
                ->orderBy('sort_order')
                ->get();

            foreach (self::DEMO_USERNAMES as $userIndex => $username) {
                $user = $users->get($username);
                if (! $user) {
                    continue;
                }

                [$divisionId, $districtId, $tehsilId] = $this->scopeIds($user);

                foreach (self::PERIOD_TYPES as $periodIndex => $periodType) {
                    for ($monthOffset = 0; $monthOffset < self::MONTH_OFFSETS; $monthOffset++) {
                        $date = Carbon::now()->startOfMonth()->subMonths($monthOffset)->addDays(($userIndex + $cardIndex + $periodIndex) % 20);
                        $periodLabel = $card->slug.'-'.$user->id.'-'.$periodType.'-'.$date->format('Ym');

                        $score = min(
                            (float) $card->total_marks,
                            40 + (($userIndex * 7 + $monthOffset * 5 + $cardIndex * 3 + $periodIndex * 2) % 55)
                        );

                        $status = self::STATUSES[($userIndex + $monthOffset + $cardIndex + $periodIndex) % count(self::STATUSES)];

                        $submissionId = DB::table('kpi_submissions')->where('period_label', $periodLabel)->value('id');

                        if (! $submissionId) {
                            $submissionId = DB::table('kpi_submissions')->insertGetId([
                                'kpi_card_id' => $card->id,
                                'user_id' => $user->id,
                                'division_id' => $divisionId,
                                'district_id' => $districtId,
                                'tehsil_id' => $tehsilId,
                                'period_type' => $periodType,
                                'period_label' => $periodLabel,
                                'submission_date' => $date,
                                'status' => $status,
                                'score' => $score,
                                'remarks' => 'Seeded '.$periodType.' performance report for demo dashboard.',
                                'created_at' => $date,
                                'updated_at' => $date,
                            ]);
                        } else {
                            DB::table('kpi_submissions')->where('id', $submissionId)->update([
                                'division_id' => $divisionId,
                                'district_id' => $districtId,
                                'tehsil_id' => $tehsilId,
                                'period_type' => $periodType,
                                'status' => $status,
                                'score' => $score,
                                'updated_at' => $date,
                            ]);
                        }

                        foreach ($fields as $fieldIndex => $field) {
                            $value = $field->field_type === 'number'
                                ? 12 + (($userIndex * 11 + $fieldIndex * 17 + $monthOffset * 3 + $cardIndex) % 88)
                                : 'Verified during field review.';

                            DB::table('kpi_submission_values')->updateOrInsert(
                                ['submission_id' => $submissionId, 'field_id' => $field->id],
                                ['value' => $value, 'created_at' => $date, 'updated_at' => $date]
                            );
                        }
                    }
                }
            }
        }

        $this->seedChildAreaSubmissions($cards, $users);
    }

    private function scopeIds(object $user): array
    {
        $roleId = (int) $user->role_id;

        return match ($roleId) {
            1, 2 => [null, null, null],
            4 => [$user->division_id, null, null],
            5 => [$user->division_id, $user->district_id, null],
            6 => [$user->division_id, $user->district_id, $user->tehsil_id],
            default => [$user->division_id, $user->district_id, $user->tehsil_id],
        };
    }

    private function seedChildAreaSubmissions($cards, $users): void
    {
        $lahoreDc = $users->get('dc.lahore');
        $layyahDc = $users->get('dc.layyah');
        $acLahore = $users->get('ac.lahore');
        $acLayyah = $users->get('ac.layyah');

        if (! $lahoreDc || ! $layyahDc) {
            return;
        }

        $tehsils = DB::table('tehsils')
            ->whereIn('district_id', [$lahoreDc->district_id, $layyahDc->district_id])
            ->get();

        foreach ($cards as $cardIndex => $card) {
            $fields = DB::table('kpi_form_fields')
                ->where('kpi_card_id', $card->id)
                ->where('field_name', '!=', 'field_observation')
                ->orderBy('sort_order')
                ->get();

            foreach ($tehsils as $tehsilIndex => $tehsil) {
                $submitter = $tehsil->district_id == $lahoreDc->district_id ? $acLahore : $acLayyah;
                if (! $submitter) {
                    continue;
                }

                for ($monthOffset = 0; $monthOffset < 2; $monthOffset++) {
                    $date = Carbon::now()->startOfMonth()->subMonths($monthOffset)->addDays($tehsilIndex % 15);
                    $periodLabel = $card->slug.'-tehsil-'.$tehsil->id.'-'.$date->format('Ym');
                    $score = min((float) $card->total_marks, 50 + (($tehsilIndex * 5 + $cardIndex * 2 + $monthOffset * 3) % 45));

                    $submissionId = DB::table('kpi_submissions')->where('period_label', $periodLabel)->value('id');

                    if (! $submissionId) {
                        $submissionId = DB::table('kpi_submissions')->insertGetId([
                            'kpi_card_id' => $card->id,
                            'user_id' => $submitter->id,
                            'division_id' => $tehsil->division_id ?? $submitter->division_id,
                            'district_id' => $tehsil->district_id,
                            'tehsil_id' => $tehsil->id,
                            'period_type' => 'monthly',
                            'period_label' => $periodLabel,
                            'submission_date' => $date,
                            'status' => 'approved',
                            'score' => $score,
                            'remarks' => 'Seeded tehsil-level data for area comparison charts.',
                            'created_at' => $date,
                            'updated_at' => $date,
                        ]);
                    }

                    foreach ($fields as $fieldIndex => $field) {
                        $value = 15 + (($tehsilIndex * 9 + $fieldIndex * 13 + $monthOffset * 4 + $cardIndex) % 75);

                        DB::table('kpi_submission_values')->updateOrInsert(
                            ['submission_id' => $submissionId, 'field_id' => $field->id],
                            ['value' => $value, 'created_at' => $date, 'updated_at' => $date]
                        );
                    }
                }
            }
        }
    }
}
