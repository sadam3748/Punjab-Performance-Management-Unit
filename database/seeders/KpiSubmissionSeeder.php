<?php

namespace Database\Seeders;

use App\Services\KpiDemoMetricFactory;
use App\Services\KpiMetricConfigService;
use App\Services\KpiPeriodService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KpiSubmissionSeeder extends Seeder
{
    private const AC_USERS = ['ac.lahore', 'ac.layyah'];

    private const OTHER_USERS = [
        'super_admin', 'cs.pmru', 'com.lahore', 'dc.lahore', 'com.dgkhan', 'dc.layyah',
    ];

    private const STATUSES = ['approved', 'approved', 'approved', 'submitted', 'pending'];

    public function run(): void
    {
        $period = app(KpiPeriodService::class);
        $metricConfig = app(KpiMetricConfigService::class);
        $factory = app(KpiDemoMetricFactory::class);

        $allUsernames = array_merge(self::OTHER_USERS, self::AC_USERS);
        $users = DB::table('users')->whereIn('username', $allUsernames)->where('is_active', true)->get()->keyBy('username');
        $cards = DB::table('kpi_cards')->where('is_active', true)->orderBy('display_order')->get()->keyBy('slug');

        foreach ($cards as $cardSlug => $card) {
            $fields = $this->ensureFields($card, $metricConfig->cardsFor($cardSlug));

            foreach (self::AC_USERS as $uIdx => $username) {
                $user = $users->get($username);
                if (! $user) {
                    continue;
                }

                $dailyCount = app()->environment('testing') ? 12 : 110;

                for ($day = 0; $day < $dailyCount; $day++) {
                    $date = Carbon::now()->subDays($day)->subHours($uIdx * 2);
                    $week = $period->weekRangeForDate($date);
                    $this->seedRecord($factory, $card, $cardSlug, $user, $fields, $date, 'daily', $week, $day, 'tehsil', $username);
                }

                for ($w = 0; $w < (app()->environment('testing') ? 3 : 10); $w++) {
                    $date = Carbon::now()->subWeeks($w)->startOfWeek(Carbon::THURSDAY)->addDays($uIdx);
                    $week = $period->weekRangeForDate($date);
                    $this->seedRecord($factory, $card, $cardSlug, $user, $fields, $date, 'weekly', $week, $w + 20, 'tehsil', $username);
                }

                for ($m = 0; $m < (app()->environment('testing') ? 2 : 8); $m++) {
                    $date = Carbon::now()->startOfMonth()->subMonths($m)->addDays(5 + $uIdx);
                    $week = $period->weekRangeForDate($date);
                    $this->seedRecord($factory, $card, $cardSlug, $user, $fields, $date, 'monthly', $week, $m + 40, 'tehsil', $username);
                }
            }

            foreach (self::OTHER_USERS as $uIdx => $username) {
                $user = $users->get($username);
                if (! $user) {
                    continue;
                }

                $count = app()->environment('testing') ? 4 : 24;
                $areaLevel = $this->areaLevel($user);

                for ($day = 0; $day < $count; $day++) {
                    $date = Carbon::now()->subDays($day + $uIdx * 2);
                    $week = $period->weekRangeForDate($date);
                    $this->seedRecord($factory, $card, $cardSlug, $user, $fields, $date, 'daily', $week, $day + 60, $areaLevel, $username);
                }

                for ($w = 0; $w < (app()->environment('testing') ? 2 : 6); $w++) {
                    $date = Carbon::now()->subWeeks($w)->startOfWeek(Carbon::THURSDAY);
                    $week = $period->weekRangeForDate($date);
                    $this->seedRecord($factory, $card, $cardSlug, $user, $fields, $date, 'weekly', $week, $w + 80, $areaLevel, $username);
                }
            }
        }

        if (! app()->environment('testing')) {
            $this->seedTehsilComparisons($cards, $users, $period, $metricConfig, $factory);
        }
    }

    private function seedRecord(
        KpiDemoMetricFactory $factory,
        object $card,
        string $cardSlug,
        object $user,
        $fields,
        Carbon $date,
        string $periodType,
        array $week,
        int $offset,
        string $areaLevel,
        string $username,
    ): void {
        $demo = $factory->build($cardSlug, $date, $username, $offset, $periodType);
        $snapshot = $demo['snapshot'];
        [$divId, $distId, $tehsilId] = $this->scopeIds($user);

        $target = (float) $card->total_marks;
        $pct = $demo['achievement_pct'];
        $achieved = round($target * $pct / 100, 2);
        $reported = $this->reportedTotal($snapshot);
        $pending = max(0, round($target - $achieved, 2));
        $achievementPct = round(min(100, $pct), 1);
        $label = $card->slug.'-u'.$user->id.'-'.$periodType.'-'.$date->format('Ymd');

        $payload = [
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
            'status' => self::STATUSES[$offset % count(self::STATUSES)],
            'score' => $achieved,
            'target_value' => $target,
            'reported_value' => $reported,
            'achieved_value' => $achieved,
            'pending_value' => $pending,
            'achievement_percentage' => $achievementPct,
            'remarks' => $demo['remarks'],
            'evidence_count' => 1 + ($offset % 5),
            'metric_snapshot' => json_encode($snapshot),
            'updated_at' => $date,
        ];

        $subId = DB::table('kpi_submissions')->where('period_label', $label)->value('id');
        if (! $subId) {
            $subId = DB::table('kpi_submissions')->insertGetId(array_merge($payload, ['created_at' => $date]));
        } else {
            DB::table('kpi_submissions')->where('id', $subId)->update($payload);
        }

        foreach ($fields as $field) {
            DB::table('kpi_submission_values')->updateOrInsert(
                ['submission_id' => $subId, 'field_id' => $field->id],
                [
                    'value' => $snapshot[$field->field_name] ?? 0,
                    'created_at' => $date,
                    'updated_at' => $date,
                ]
            );
        }
    }

    private function reportedTotal(array $snapshot): int
    {
        $sum = 0;
        foreach ($snapshot as $key => $value) {
            if (is_numeric($value) && ! str_contains($key, 'rate') && ! str_contains($key, 'index') && ! str_contains($key, 'attendance')) {
                $sum += (float) $value;
            }
        }

        return max(1, (int) round($sum));
    }

    private function ensureFields(object $card, array $metrics)
    {
        foreach ($metrics as $index => $metric) {
            DB::table('kpi_form_fields')->updateOrInsert(
                ['kpi_card_id' => $card->id, 'field_name' => $metric['field']],
                [
                    'field_label' => $metric['label'],
                    'field_type' => 'number',
                    'is_required' => false,
                    'sort_order' => $index + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        return DB::table('kpi_form_fields')->where('kpi_card_id', $card->id)->orderBy('sort_order')->get();
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

    private function seedTehsilComparisons($cards, $users, KpiPeriodService $period, KpiMetricConfigService $metricConfig, KpiDemoMetricFactory $factory): void
    {
        $dcLahore = $users->get('dc.lahore');
        $dcLayyah = $users->get('dc.layyah');
        $acLahore = $users->get('ac.lahore');
        $acLayyah = $users->get('ac.layyah');
        $districtIds = array_filter([$dcLahore?->district_id, $dcLayyah?->district_id]);
        if (empty($districtIds)) {
            return;
        }

        $tehsils = DB::table('tehsils')->whereIn('district_id', $districtIds)->get();
        foreach ($cards as $cardSlug => $card) {
            $fields = $this->ensureFields($card, $metricConfig->cardsFor($cardSlug));

            foreach ($tehsils as $tIdx => $tehsil) {
                $submitter = $tehsil->district_id == ($dcLahore?->district_id ?? 0) ? $acLahore : $acLayyah;
                if (! $submitter) {
                    continue;
                }

                $username = $submitter->username;
                for ($d = 0; $d < 35; $d++) {
                    $date = Carbon::now()->subDays($d + $tIdx);
                    $week = $period->weekRangeForDate($date);
                    $this->seedRecord($factory, $card, $cardSlug, $submitter, $fields, $date, 'daily', $week, $d + 100 + $tIdx, 'tehsil', $username);
                }
            }
        }
    }
}
