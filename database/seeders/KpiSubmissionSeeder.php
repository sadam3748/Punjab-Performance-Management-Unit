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

    /** @var list<string> */
    private const PRIORITY_SLUGS = [
        'price-of-roti',
        'inspection-of-educational-institutions',
        'inspection-of-health-facilities',
        'functional-and-clean-water-filtration-plants',
        'chief-ministers-complaint-cell',
        'e-biz',
    ];

    /** @var array<string, int> Per-user share; totals 56 submissions per KPI */
    private const USER_COUNTS = [
        'ac.lahore' => 18,
        'ac.layyah' => 18,
        'ac.karor' => 18,
        'dc.lahore' => 6,
        'dc.layyah' => 6,
        'com.lahore' => 3,
        'com.dgkhan' => 3,
        'super_admin' => 1,
        'cs.pmru' => 1,
    ];

    /** @var array<string, int> Rich demo data for priority KPIs (~105 per KPI) */
    private const PRIORITY_USER_COUNTS = [
        'ac.lahore' => 28,
        'ac.layyah' => 28,
        'ac.karor' => 28,
        'dc.lahore' => 12,
        'dc.layyah' => 12,
        'com.lahore' => 8,
        'com.dgkhan' => 8,
        'cs.pmru' => 14,
        'super_admin' => 14,
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

            $isPriority = in_array($card->slug, self::PRIORITY_SLUGS, true);
            $userCounts = $isPriority ? self::PRIORITY_USER_COUNTS : self::USER_COUNTS;

            foreach ($userCounts as $username => $count) {
                $user = $users->get($username);
                if (! $user) {
                    continue;
                }

                $records = $testing
                    ? ($isPriority ? min(14, $count) : min(4, $count))
                    : $count;
                [$divId, $distId, $tehsilId] = $this->scopeIds($user);
                $areaLevel = $this->areaLevel($user);

                for ($i = 0; $i < $records; $i++) {
                    $date = $isPriority
                        ? $this->priorityDateForIndex($i, $records)
                        : $this->dateForIndex($i, $records);
                    $periodType = $this->periodTypeForIndex($i, $date);
                    $week = $period->weekRangeForDate($date);
                    $demo = $factory->build($card->slug, $date, $username, $i, $periodType);
                    $snapshot = $demo['snapshot'];
                    $target = (float) ($snapshot['operational_target'] ?? $card->total_marks);
                    $achieved = (float) ($snapshot['operational_completed'] ?? 0);
                    $pct = app(\App\Services\KpiFormulaService::class)->percentage($achieved, $target);
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
        $today = now()->startOfDay();
        $weekStart = Carbon::parse($period->weekRangeForDate(now())['week_start']);

        return match ($index % 4) {
            0 => $today->copy(),
            1 => $weekStart->copy()->addDays(($index + 1) % 7)->startOfDay(),
            2 => $today->copy()->startOfMonth()->addDays(min($today->day - 1, 7 + ($index % 14)))->startOfDay(),
            default => $today->copy()->startOfYear()->addMonths(max(0, $today->month - 2))->addDays($index % 20)->startOfDay(),
        };
    }

    private function periodTypeForIndex(int $index, Carbon $date): string
    {
        if ($date->isToday()) {
            return 'daily';
        }

        $period = app(KpiPeriodService::class);
        $weekStart = Carbon::parse($period->weekRangeForDate(now())['week_start']);
        $weekEnd = Carbon::parse($period->weekRangeForDate(now())['week_end']);

        if ($date->between($weekStart, $weekEnd)) {
            return 'weekly';
        }

        if ($date->isSameMonth(now())) {
            return 'monthly';
        }

        return 'yearly';
    }

    private function priorityDateForIndex(int $index, int $total): Carbon
    {
        $period = app(KpiPeriodService::class);
        $today = now()->startOfDay();
        $weekStart = Carbon::parse($period->weekRangeForDate($today)['week_start']);
        $monthStart = $today->copy()->startOfMonth();
        $yearStart = $today->copy()->startOfYear();

        if ($index <= 6) {
            return $weekStart->copy()->addDays($index)->startOfDay();
        }

        $monthRecords = max(3, (int) floor($total * .4));
        if ($index < 7 + $monthRecords) {
            $monthDay = min(max(0, $today->day - 8), $index - 7);

            return $monthStart->copy()->addDays($monthDay)->startOfDay();
        }

        if ($index < $total - 2) {
            $monthsBack = 1 + ($index % max(1, (int) $today->month - 1));

            return $yearStart->copy()->addMonths($monthsBack - 1)->addDays(($index % 20) + 1)->startOfDay();
        }

        return $today->copy()->subMonths(2 + ($index % 3))->subDays($index % 15)->startOfDay();
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
