<?php

namespace Database\Seeders;

use App\Services\KpiMetricConfigService;
use App\Services\KpiPeriodService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KpiSubmissionSeeder extends Seeder
{
    private const DEMO_USERNAMES = [
        'super_admin', 'cs.pmru', 'com.lahore', 'dc.lahore', 'ac.lahore',
        'com.dgkhan', 'dc.layyah', 'ac.layyah',
    ];

    private const AC_USERNAMES = ['ac.lahore', 'ac.layyah'];

    private const STATUSES = ['approved', 'approved', 'approved', 'submitted', 'pending'];

    private const KPI_PROFILES = [
        'price-of-roti' => [75, 95],
        'price-of-plain-bakery-bread' => [70, 92],
        'price-control-of-essential-commodities' => [60, 88],
        'repair-of-small-roads-in-both-urban-and-rural-areas' => [50, 80],
        'zebra-crossings' => [55, 82],
        'dysfunctional-streetlights' => [65, 90],
        'covering-of-manholes' => [70, 93],
        'functional-and-clean-water-filtration-plants' => [80, 96],
        'inspection-of-educational-institutions' => [72, 91],
        'inspection-of-health-facilities' => [68, 89],
        'violation-of-marriage-functions-act' => [60, 85],
        'anti-encroachment-campaign' => [55, 82],
        'stray-dogs' => [45, 75],
        'removal-of-wall-chalking' => [65, 88],
        'graveyards' => [70, 90],
        'illegal-decanting' => [55, 80],
        'suthra-punjab-campaign' => [75, 95],
        'maintenance-of-greenbelts' => [60, 85],
        'maintenance-of-drains-and-sewerage-lines' => [55, 80],
        'bus-terminals' => [70, 90],
        'chief-ministers-complaint-cell' => [80, 97],
        'regulation-of-shops-and-handcarts' => [65, 88],
        'e-biz' => [78, 95],
    ];

    public function run(): void
    {
        $period = app(KpiPeriodService::class);
        $metricConfig = app(KpiMetricConfigService::class);

        $users = DB::table('users')->whereIn('username', self::DEMO_USERNAMES)->where('is_active', true)->get()->keyBy('username');
        $cards = DB::table('kpi_cards')->where('is_active', true)->orderBy('display_order')->get()->keyBy('slug');

        foreach ($cards as $cardSlug => $card) {
            $fields = $this->ensureFields($card, $metricConfig->cardsFor($cardSlug));
            [$minPct, $maxPct] = self::KPI_PROFILES[$cardSlug] ?? [55, 88];

            foreach (self::DEMO_USERNAMES as $uIdx => $username) {
                $user = $users->get($username);
                if (! $user) {
                    continue;
                }

                $isAc = in_array($username, self::AC_USERNAMES, true);
                $dailyCount = app()->environment('testing') ? ($isAc ? 10 : 3) : ($isAc ? 120 : 30);

                for ($day = 0; $day < $dailyCount; $day++) {
                    $date = Carbon::now()->subDays($day + $uIdx);
                    $week = $period->weekRangeForDate($date);
                    $this->upsertSubmission($card, $user, $fields, $date, 'daily', $week, $uIdx, $day, $minPct, $maxPct, $isAc ? 'tehsil' : $this->areaLevel($user));
                }

                for ($m = 0; $m < (app()->environment('testing') ? 2 : 6); $m++) {
                    $date = Carbon::now()->startOfMonth()->subMonths($m)->addDays(($uIdx + $m) % 20);
                    $week = $period->weekRangeForDate($date);
                    $this->upsertSubmission($card, $user, $fields, $date, 'monthly', $week, $uIdx, $m, $minPct, $maxPct, $this->areaLevel($user));
                }

                for ($w = 0; $w < (app()->environment('testing') ? 2 : 8); $w++) {
                    $date = Carbon::now()->subWeeks($w)->startOfWeek(Carbon::THURSDAY);
                    $week = $period->weekRangeForDate($date);
                    $this->upsertSubmission($card, $user, $fields, $date, 'weekly', $week, $uIdx, $w, $minPct, $maxPct, $this->areaLevel($user));
                }
            }
        }

        if (! app()->environment('testing')) {
            $this->seedAreaComparisons($cards, $users, $period, $metricConfig);
        }
    }

    private function upsertSubmission(object $card, object $user, $fields, Carbon $date, string $periodType, array $week, int $uIdx, int $offset, int $minPct, int $maxPct, string $areaLevel): void
    {
        [$divId, $distId, $tehsilId] = $this->scopeIds($user);
        $target = (float) $card->total_marks;
        $pct = max($minPct, min($maxPct, ($minPct + $maxPct) / 2 + (($uIdx * 3 + $offset) % 12) - 6));
        $achieved = round($target * $pct / 100, 2);
        $reported = round($achieved + ($offset % 5), 2);
        $pending = max(0, round($target - $achieved, 2));
        $achievementPct = $target > 0 ? round(min(100, ($achieved / $target) * 100), 1) : 0;
        $label = $card->slug.'-u'.$user->id.'-'.$periodType.'-'.$date->format('Ymd');

        $snapshot = [];
        foreach ($fields as $fIdx => $field) {
            $snapshot[$field->field_name] = 5 + (($uIdx * 7 + $fIdx * 11 + $offset * 3) % 45);
        }

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
            'status' => self::STATUSES[($uIdx + $offset) % count(self::STATUSES)],
            'score' => $achieved,
            'target_value' => $target,
            'reported_value' => $reported,
            'achieved_value' => $achieved,
            'pending_value' => $pending,
            'achievement_percentage' => $achievementPct,
            'remarks' => ucfirst($periodType).' PPMF field report for demo dashboard.',
            'evidence_count' => 1 + ($offset % 4),
            'metric_snapshot' => json_encode($snapshot),
            'updated_at' => $date,
        ];

        $subId = DB::table('kpi_submissions')->where('period_label', $label)->value('id');
        if (! $subId) {
            $subId = DB::table('kpi_submissions')->insertGetId(array_merge($payload, ['created_at' => $date]));
        } else {
            DB::table('kpi_submissions')->where('id', $subId)->update($payload);
        }

        foreach ($fields as $fIdx => $field) {
            DB::table('kpi_submission_values')->updateOrInsert(
                ['submission_id' => $subId, 'field_id' => $field->id],
                ['value' => $snapshot[$field->field_name] ?? 0, 'created_at' => $date, 'updated_at' => $date]
            );
        }
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

        return DB::table('kpi_form_fields')->where('kpi_card_id', $card->id)->where('field_name', '!=', 'field_observation')->orderBy('sort_order')->get();
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

    private function seedAreaComparisons($cards, $users, KpiPeriodService $period, KpiMetricConfigService $metricConfig): void
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
            [$minPct, $maxPct] = self::KPI_PROFILES[$cardSlug] ?? [55, 88];

            foreach ($tehsils as $tIdx => $tehsil) {
                $submitter = $tehsil->district_id == ($dcLahore?->district_id ?? 0) ? $acLahore : $acLayyah;
                if (! $submitter) {
                    continue;
                }
                for ($d = 0; $d < (app()->environment('testing') ? 5 : 40); $d++) {
                    $date = Carbon::now()->subDays($d + $tIdx);
                    $week = $period->weekRangeForDate($date);
                    $this->upsertSubmission($card, $submitter, $fields, $date, 'daily', $week, $tIdx, $d, $minPct, $maxPct, 'tehsil');
                }
            }
        }
    }
}
