<?php

namespace Tests\Feature;

use App\Models\KpiCard;
use App\Models\KpiSubmission;
use App\Models\User;
use Database\Seeders\PpmuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KpiDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_based_kpi_dashboards_and_management_pages_render(): void
    {
        $this->seed(PpmuSeeder::class);
        $slug = 'functional-and-clean-water-filtration-plants';
        $card = KpiCard::where('slug', $slug)->firstOrFail();
        $admin = User::where('username', 'super_admin')->firstOrFail();

        $this->actingAs($admin)->get('/dashboard')->assertOk()->assertSee('Home');
        $this->actingAs($admin)->get('/dashboard')->assertSeeInOrder(['Target', 'Achieved', 'Progress']);
        $this->actingAs($admin)->get('/dashboard')->assertSee('ppmu-kpi-percent-badge', false);
        $this->actingAs($admin)->get('/dashboard')->assertDontSee('ppmu-kpi-tile-status', false);
        $this->actingAs($admin)->get('/dashboard')->assertSee('ppmu-main-dashboard', false);
        $this->actingAs($admin)->get("/kpi/{$slug}/dashboard")->assertOk()->assertSee('Water Filtration');
        $this->actingAs($admin)->get('/manage-kpis')->assertOk()->assertSee('Manage KPI Cards');

        $ac = User::whereHas('role', fn ($query) => $query->where('slug', 'ac'))->whereNotNull('tehsil_id')->firstOrFail();
        $this->actingAs($ac)->get('/dashboard')->assertOk()->assertSee($card->title);
        $this->actingAs($ac)->get("/submit-kpi/{$slug}")->assertOk();
        $this->actingAs($ac)->get('/manage-kpis')->assertForbidden();
    }

    public function test_all_demo_users_see_23_kpi_cards_and_detail_dashboard(): void
    {
        $this->seed(PpmuSeeder::class);

        $this->assertSame(23, KpiCard::where('is_active', true)->count());

        KpiCard::where('is_active', true)->each(function (KpiCard $card) {
            $this->assertStringStartsWith('images/kpi-images/', $card->image_path);
            $this->assertFileExists(public_path($card->resolvedImagePath()), "Missing KPI image for {$card->slug}");
        });

        foreach (['super_admin', 'cs.pmru', 'com.lahore', 'dc.lahore', 'ac.lahore', 'com.dgkhan', 'dc.layyah', 'ac.layyah'] as $login) {
            auth()->logout();
            $this->post('/login', ['login' => $login, 'password' => '123456'])
                ->assertRedirect(route('dashboard'));

            $response = $this->get('/dashboard');
            $response->assertOk()->assertSee('Water Filtration')->assertSee('Price of Roti')->assertSee('images/kpi-images/', false);

            $cardCount = substr_count($response->getContent(), 'data-kpi-card');
            $this->assertSame(23, $cardCount, "User {$login} should see 23 KPI cards");
            $response->assertSee('View Dashboard')->assertSee('Achieved')->assertSee('Progress')->assertSee('ppmu-kpi-percent-badge', false)->assertDontSee('Reported')->assertDontSee('ppmu-kpi-tile-status', false)->assertDontSee('Performance</span>', false);

            $this->get('/kpi/functional-and-clean-water-filtration-plants/dashboard')
                ->assertOk()
                ->assertSee('KPI Performance Cards')
                ->assertSee('KPI Detail Dashboard')
                ->assertSee('kpiChart_0', false)
                ->assertSee('RO Filter Change Compliance');
        }
    }

    public function test_period_filter_on_dashboard(): void
    {
        $this->seed(PpmuSeeder::class);
        $admin = User::where('username', 'super_admin')->firstOrFail();

        $this->actingAs($admin)
            ->get('/dashboard?period_type=monthly&month=1&year='.now()->year)
            ->assertOk()
            ->assertSee('Monthly');
    }

    public function test_dashboard_defaults_to_active_ppmf_week(): void
    {
        $this->seed(PpmuSeeder::class);
        $admin = User::where('username', 'super_admin')->firstOrFail();

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Week W', false)
            ->assertSee('Today', false)
            ->assertDontSee('PPMF Week', false);

        $this->actingAs($admin)
            ->getJson('/dashboard/data')
            ->assertOk()
            ->assertJsonStructure(['cards_html', 'cards_count', 'period_description', 'period', 'period_query'])
            ->assertJsonPath('period.period_type', 'weekly');
    }

    public function test_main_dashboard_uses_period_totals_for_target_and_achieved(): void
    {
        $this->seed(PpmuSeeder::class);

        $user = User::where('username', 'ac.lahore')->firstOrFail();
        $period = app(\App\Services\KpiPeriodService::class);
        $dashboard = app(\App\Services\KpiDashboardService::class);

        $daily = $dashboard->assignedCards($user, \Illuminate\Http\Request::create('/', 'GET', [
            'period_type' => 'daily',
            'date' => now()->toDateString(),
        ]));
        $weekly = $dashboard->assignedCards($user, \Illuminate\Http\Request::create('/', 'GET', [
            'period_type' => 'weekly',
            'week_no' => $period->currentWeekNo(),
        ]));
        $monthly = $dashboard->assignedCards($user, \Illuminate\Http\Request::create('/', 'GET', [
            'period_type' => 'monthly',
            'month' => (string) now()->month,
            'year' => (string) now()->year,
        ]));

        $dailyRoti = $daily->firstWhere('slug', 'price-of-roti');
        $weeklyRoti = $weekly->firstWhere('slug', 'price-of-roti');
        $monthlyRoti = $monthly->firstWhere('slug', 'price-of-roti');

        $this->assertNotNull($dailyRoti);
        $this->assertNotNull($weeklyRoti);
        $this->assertNotNull($monthlyRoti);

        $this->assertGreaterThan((float) $dailyRoti->target, (float) $weeklyRoti->target);
        $this->assertGreaterThan((float) $dailyRoti->achieved, (float) $weeklyRoti->achieved);
        $this->assertGreaterThanOrEqual((float) $weeklyRoti->target, (float) $monthlyRoti->target);

        $expectedWeeklyProgress = round(((float) $weeklyRoti->achieved / (float) $weeklyRoti->target) * 100, 1);
        $this->assertEquals($expectedWeeklyProgress, (float) $weeklyRoti->achievement_percentage);
    }

    public function test_health_targets_follow_role_scope_and_period_formula(): void
    {
        $this->seed(PpmuSeeder::class);

        $dashboard = app(\App\Services\KpiDashboardService::class);
        $inspections = app(\App\Services\KpiInspectionService::class);
        $period = app(\App\Services\KpiPeriodService::class);
        $card = KpiCard::where('slug', 'inspection-of-health-facilities')->firstOrFail();
        $weeklyRequest = \Illuminate\Http\Request::create('/', 'GET', [
            'period_type' => 'weekly',
            'week_no' => $period->currentWeekNo(),
        ]);
        $monthlyRequest = \Illuminate\Http\Request::create('/', 'GET', [
            'period_type' => 'monthly',
            'month' => (string) now()->month,
            'year' => (string) now()->year,
        ]);

        $users = collect(['ac.lahore', 'dc.lahore', 'com.lahore', 'cs.pmru'])
            ->mapWithKeys(fn (string $username) => [
                $username => User::where('username', $username)->firstOrFail(),
            ]);

        $weekly = $users->map(fn (User $user) => $dashboard
            ->assignedCards($user, $weeklyRequest)
            ->firstWhere('slug', 'inspection-of-health-facilities'));
        $monthly = $users->map(fn (User $user) => $dashboard
            ->assignedCards($user, $monthlyRequest)
            ->firstWhere('slug', 'inspection-of-health-facilities'));

        $this->assertSame(2.0, (float) $weekly['ac.lahore']->target);
        $this->assertSame(12.0, (float) $weekly['dc.lahore']->target);
        $this->assertGreaterThan((float) $weekly['dc.lahore']->target, (float) $weekly['com.lahore']->target);
        $this->assertGreaterThan((float) $weekly['com.lahore']->target, (float) $weekly['cs.pmru']->target);

        $weeksInMonth = (int) ceil(now()->daysInMonth / 7);
        foreach ($users->keys() as $username) {
            $user = $users[$username];
            $this->assertSame(
                (float) $weekly[$username]->target * $weeksInMonth,
                (float) $monthly[$username]->target
            );
            $expectedAchieved = (float) $inspections->countOperationalAchieved($card, $user, $weeklyRequest);
            $this->assertSame($expectedAchieved, (float) $weekly[$username]->achieved, $username);
            $this->assertGreaterThan((float) $weekly['ac.lahore']->achieved, (float) $weekly['dc.lahore']->achieved);
        }
    }

    public function test_health_and_education_achieved_matches_detail_header_and_inspection_rows(): void
    {
        $this->seed(PpmuSeeder::class);

        $dashboard = app(\App\Services\KpiDashboardService::class);
        $inspections = app(\App\Services\KpiInspectionService::class);
        $period = app(\App\Services\KpiPeriodService::class);
        $user = User::where('username', 'ac.lahore')->firstOrFail();
        $request = \Illuminate\Http\Request::create('/', 'GET', [
            'period_type' => 'weekly',
            'week_no' => $period->currentWeekNo(),
        ]);

        foreach (['inspection-of-health-facilities', 'inspection-of-educational-institutions'] as $slug) {
            $card = KpiCard::where('slug', $slug)->firstOrFail();
            $home = $dashboard->assignedCards($user, $request)->firstWhere('slug', $slug);
            $detail = $dashboard->detail($card, $user, $request);
            $expected = (float) $inspections->countOperationalAchieved($card, $user, $request);

            $this->assertSame($expected, (float) $home->achieved, $slug.' home achieved');
            $this->assertSame((float) $home->target, (float) $detail['header']['operational_target'], $slug.' target parity');
            $this->assertSame((float) $home->achieved, (float) $detail['header']['completed'], $slug.' achieved parity');
            $this->assertSame($detail['summary']['total'], $detail['header']['records'], $slug.' records are submission count');
        }
    }

    public function test_every_seeded_kpi_has_operational_target_and_completed_values(): void
    {
        $this->seed(PpmuSeeder::class);

        KpiCard::where('is_active', true)->each(function (KpiCard $card) {
            $submission = KpiSubmission::where('kpi_card_id', $card->id)->firstOrFail();
            $snapshot = $submission->metric_snapshot;

            $this->assertArrayHasKey('operational_target', $snapshot, $card->slug);
            $this->assertArrayHasKey('operational_completed', $snapshot, $card->slug);
            $this->assertGreaterThan(0, $snapshot['operational_target'], $card->slug);
            $this->assertGreaterThanOrEqual(0, $snapshot['operational_completed'], $card->slug);
            $this->assertLessThanOrEqual(
                $snapshot['operational_target'],
                $snapshot['operational_completed'],
                $card->slug
            );
        });
    }

    public function test_kpi_detail_dashboard_ajax_filter_returns_json(): void
    {
        $this->seed(PpmuSeeder::class);
        $slug = 'functional-and-clean-water-filtration-plants';
        $admin = User::where('username', 'super_admin')->firstOrFail();

        $this->actingAs($admin)
            ->getJson("/kpi/{$slug}/dashboard/data?period_type=monthly&month=1&year=".now()->year)
            ->assertOk()
            ->assertJsonStructure([
                'header',
                'metrics_html',
                'records_html',
                'inspections_html',
                'charts' => ['definitions', 'status_donut', 'target_achieved', 'trend', 'areas'],
                'records_total',
                'inspections_total',
                'period_description',
            ]);
    }
}
