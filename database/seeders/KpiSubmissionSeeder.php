<?php

namespace Database\Seeders;

use App\Services\KpiPeriodService;
use App\Services\PpmuDemoMetricFactory;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KpiSubmissionSeeder extends Seeder
{
    private const STATUSES = ['approved', 'approved', 'approved', 'submitted', 'pending'];

    /** @var array<string, int> Per-user share; totals 56 submissions per KPI */
    private const USER_COUNTS = [
        'ac.lahore' => 18,
        'ac.layyah' => 18,
        'dc.lahore' => 6,
        'dc.layyah' => 6,
        'com.lahore' => 3,
        'com.dgkhan' => 3,
        'super_admin' => 1,
        'cs.pmru' => 1,
    ];

    public function run(): void
    {
        DB::disableQueryLog();

        $period = app(KpiPeriodService::class);
        $factory = app(PpmuDemoMetricFactory::class);
        $testing = app()->environment('testing');

        $users = DB::table('users')->where('is_active', true)->get()->keyBy('username');
        $cards = DB::table('kpi_cards')->where('is_active', true)->orderBy('display_order')->get();
        $fieldsByCard = DB::table('kpi_form_fields')
            ->orderBy('kpi_card_id')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('kpi_card_id');

        $submissionRows = [];
        $meta = [];

        foreach ($cards as $card) {
            $fields = $fieldsByCard->get($card->id, collect())->filter(
                fn ($f) => $f->field_name !== 'field_observation'
            );

            foreach (self::USER_COUNTS as $username => $count) {
                $user = $users->get($username);
                if (! $user) {
                    continue;
                }

                $records = $testing ? min(4, $count) : $count;
                [$divId, $distId, $tehsilId] = $this->scopeIds($user);
                $areaLevel = $this->areaLevel($user);

                for ($i = 0; $i < $records; $i++) {
                    $date = $this->dateForIndex($i, $records);
                    $periodType = $this->periodTypeForIndex($i);
                    $week = $period->weekRangeForDate($date);
                    $demo = $factory->build($card->slug, $date, $username, $i, $periodType);
                    $snapshot = $demo['snapshot'];
                    $target = (float) $card->total_marks;
                    $pct = max(55, min(98, $demo['achievement_pct']));
                    $achieved = round($target * $pct / 100, 2);
                    $reported = $this->reportedTotal($snapshot);
                    $pending = max(0, round($target - $achieved, 2));
                    $label = sprintf('demo-c%d-u%d-%03d', $card->id, $user->id, $i);

                    $submissionRows[] = [
                        'kpi_card_id' => $card->id,
                        'user_id' => $user->id,
                        'division_id' => $divId,
                        'district_id' => $distId,
                        'tehsil_id' => $tehsilId,
                        'area_level' => $areaLevel,
                        'period_type' => $periodType,
                        'period_label' => $label,
                        'week_no' => $week['week_no'],
                        'week_start_date' => $week['week_start'],
                        'week_end_date' => $week['week_end'],
                        'submission_date' => $date->toDateString(),
                        'status' => self::STATUSES[$i % count(self::STATUSES)],
                        'score' => $achieved,
                        'target_value' => $target,
                        'reported_value' => $reported,
                        'achieved_value' => $achieved,
                        'pending_value' => $pending,
                        'achievement_percentage' => round($pct, 1),
                        'remarks' => $demo['remarks'],
                        'evidence_count' => 1 + ($i % 4),
                        'metric_snapshot' => json_encode($snapshot),
                        'created_at' => $date,
                        'updated_at' => $date,
                    ];

                    $meta[] = ['label' => $label, 'snapshot' => $snapshot, 'fields' => $fields, 'ts' => $date];
                }
            }
        }

        DB::transaction(function () use ($submissionRows, $meta) {
            foreach (array_chunk($submissionRows, 500) as $chunk) {
                DB::table('kpi_submissions')->insert($chunk);
            }

            $labels = array_column($meta, 'label');
            $idMap = DB::table('kpi_submissions')->whereIn('period_label', $labels)->pluck('id', 'period_label');
            $valueRows = [];

            foreach ($meta as $row) {
                $submissionId = $idMap[$row['label']] ?? null;
                if (! $submissionId) {
                    continue;
                }

                foreach ($row['fields'] as $field) {
                    $valueRows[] = [
                        'submission_id' => $submissionId,
                        'field_id' => $field->id,
                        'value' => (string) ($row['snapshot'][$field->field_name] ?? 0),
                        'created_at' => $row['ts'],
                        'updated_at' => $row['ts'],
                    ];
                }
            }

            foreach (array_chunk($valueRows, 1000) as $chunk) {
                DB::table('kpi_submission_values')->insert($chunk);
            }
        });
    }

    private function dateForIndex(int $index, int $total): Carbon
    {
        $period = app(KpiPeriodService::class);
        $weekStart = Carbon::parse($period->weekRangeForDate(now())['week_start']);

        if ($index < (int) ceil($total * 0.65)) {
            $dayOffset = $index % 7;

            return $weekStart->copy()->addDays($dayOffset)->startOfDay();
        }

        if ($index < (int) ceil($total * 0.9)) {
            $weeksBack = 1 + (($index - (int) ceil($total * 0.65)) % 4);

            return $weekStart->copy()->subWeeks($weeksBack)->addDays($index % 5)->startOfDay();
        }

        return Carbon::now()->subMonths(2 + ($index % 4))->addDays($index % 20)->startOfDay();
    }

    private function periodTypeForIndex(int $index): string
    {
        return match ($index % 6) {
            0, 1 => 'daily',
            2, 3 => 'weekly',
            4 => 'monthly',
            default => 'yearly',
        };
    }

    private function reportedTotal(array $snapshot): int
    {
        $sum = 0;
        foreach ($snapshot as $key => $value) {
            if (is_numeric($value) && ! str_contains($key, 'rate') && ! str_contains($key, 'index') && ! str_contains($key, 'attendance') && ! str_contains($key, 'completion')) {
                $sum += (float) $value;
            }
        }

        return max(1, (int) round($sum));
    }

    private function scopeIds(object $user): array
    {
        return match ((int) $user->role_id) {
            1, 2 => [null, null, null],
            4 => [$user->division_id, null, null],
            5 => [$user->division_id, $user->district_id, null],
            default => [$user->division_id, $user->district_id, $user->tehsil_id],
        };
    }

    private function areaLevel(object $user): string
    {
        return match ((int) $user->role_id) {
            1, 2 => 'province',
            4 => 'division',
            5 => 'district',
            default => 'tehsil',
        };
    }
}
