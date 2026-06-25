<?php

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\KpiCard;
use App\Models\KpiInspection;
use App\Models\KpiSubmission;
use App\Models\User;
use App\Services\KpiDashboardService;
use App\Services\KpiInspectionService;
use Illuminate\Http\Request;

$slugs = [
    'price-of-roti',
    'inspection-of-educational-institutions',
    'inspection-of-health-facilities',
];

$cards = KpiCard::query()->whereIn('slug', $slugs)->get()->keyBy('slug');
$dashboard = app(KpiDashboardService::class);
$inspections = app(KpiInspectionService::class);
$period = app(\App\Services\KpiPeriodService::class);

$periods = [
    'today' => ['label' => 'Today', 'params' => ['period_type' => 'daily', 'date' => now()->toDateString()]],
    'weekly' => ['label' => 'Weekly', 'params' => ['period_type' => 'weekly', 'week_no' => $period->currentWeekNo()]],
    'monthly' => ['label' => 'Monthly', 'params' => ['period_type' => 'monthly', 'month' => (string) now()->month, 'year' => (string) now()->year]],
];

$users = ['ac.lahore', 'dc.lahore', 'com.lahore', 'cs.pmru'];

echo "=== SEEDER COUNTS ===\n";
foreach (['inspection-of-health-facilities', 'inspection-of-educational-institutions'] as $slug) {
    $card = $cards[$slug];
    echo "{$slug}: submissions=".KpiSubmission::where('kpi_card_id', $card->id)->count();
    echo ', inspections='.KpiInspection::where('kpi_card_id', $card->id)->count()."\n";
}

echo "\n=== SAMPLE VALUES ===\n";
foreach ($users as $uname) {
    $user = User::query()->where('username', $uname)->first();
    echo "\n--- {$uname} ---\n";
    foreach ($periods as $periodConfig) {
        echo "[{$periodConfig['label']}]\n";
        foreach (['inspection-of-health-facilities', 'inspection-of-educational-institutions', 'price-of-roti'] as $slug) {
            $card = $cards[$slug];
            $req = Request::create('/', 'GET', $periodConfig['params']);
            $home = $dashboard->assignedCards($user, $req)->firstWhere('slug', $slug);
            $detail = $dashboard->detail($card, $user, $req);
            $inspAchieved = in_array($slug, ['inspection-of-health-facilities', 'inspection-of-educational-institutions'], true)
                ? $inspections->countOperationalAchieved($card, $user, $req)
                : null;
            echo "  {$slug}: target={$home->target}, achieved={$home->achieved}, progress={$home->achievement_percentage}%";
            echo ", records={$detail['header']['records']}, inspections={$detail['header']['inspections_count']}";
            if ($inspAchieved !== null) {
                echo ", insp_achieved_src={$inspAchieved}";
            }
            echo "\n";
            $empty = [];
            foreach ($detail['charts']['definitions'] ?? [] as $c) {
                $d = $c['data'] ?? [];
                $vals = $d['values'] ?? ($d['datasets'][0]['data'] ?? []);
                if (empty($vals) || (is_array($vals) && array_sum(array_map('floatval', (array) $vals)) == 0)) {
                    $empty[] = $c['key'];
                }
            }
            if ($empty !== []) {
                echo '    EMPTY CHARTS: '.implode(', ', $empty)."\n";
            }
        }
    }
}
