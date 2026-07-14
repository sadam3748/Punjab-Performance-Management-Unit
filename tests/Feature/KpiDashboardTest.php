<?php

namespace Tests\Feature;

use App\Models\KpiCard;
use App\Models\KpiInspection;
use App\Models\KpiSubmission;
use App\Models\User;
use App\Services\HealthInspectionMapService;
use App\Services\KpiDashboardService;
use App\Services\KpiInspectionService;
use App\Services\KpiPeriodService;
use Database\Seeders\PpmuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
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

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertOk()
            ->assertDontSee('<h1>Home</h1>', false)
            ->assertDontSee('Search KPIs, locations, reports...')
            ->assertDontSee('header-search', false)
            ->assertDontSee('ppmu-dashboard-summary', false)
            ->assertSee('ppmu-header-kpi-count', false)
            ->assertSee('24 KPIs');
        $this->actingAs($admin)->get('/dashboard')->assertDontSee('ppmu-kpi-tile-stats', false);
        $this->actingAs($admin)->get('/dashboard')->assertDontSee('ppmu-kpi-percent-badge', false);
        $this->actingAs($admin)->get('/dashboard')->assertDontSee('ppmu-kpi-tile-status', false);
        $this->actingAs($admin)->get('/dashboard')->assertSee('ppmu-main-dashboard', false);
        $this->actingAs($admin)
            ->get("/kpi/{$slug}/dashboard")
            ->assertOk()
            ->assertSee('Water Filtration')
            ->assertDontSee('Search KPIs, locations, reports...')
            ->assertDontSee('header-search', false);
        $this->actingAs($admin)->get('/manage-kpis')->assertOk()->assertSee('Manage KPI Cards');

        $ac = User::whereHas('role', fn ($query) => $query->where('slug', 'ac'))->whereNotNull('tehsil_id')->firstOrFail();
        $this->actingAs($ac)->get('/dashboard')->assertOk()->assertSee($card->title);
        $this->actingAs($ac)->get("/submit-kpi/{$slug}")->assertOk();
        $this->actingAs($ac)->get('/manage-kpis')->assertForbidden();
    }

    public function test_all_demo_users_see_23_kpi_cards_and_detail_dashboard(): void
    {
        $this->seed(PpmuSeeder::class);

        $this->assertSame(24, KpiCard::where('is_active', true)->count());

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
            $response
                ->assertSee('24 KPIs')
                ->assertSee('ppmu-header-kpi-count', false)
                ->assertDontSee('ppmu-dashboard-summary', false);

            $cardCount = substr_count($response->getContent(), 'data-kpi-card');
            $this->assertSame(24, $cardCount, "User {$login} should see 24 KPI cards");
            $response->assertSee('View Dashboard')->assertDontSee('ppmu-kpi-tile-stats', false)->assertDontSee('ppmu-kpi-percent-badge', false)->assertDontSee('Reported')->assertDontSee('ppmu-kpi-tile-status', false)->assertDontSee('Performance</span>', false);

            $this->get('/kpi/functional-and-clean-water-filtration-plants/dashboard')
                ->assertOk()
                ->assertSee('KPI Performance Cards')
                ->assertSee('KPI Detail Dashboard')
                ->assertSee('kpiChart_0', false)
                ->assertSee('RO Filter Compliance');
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
            ->assertSee('Week 1', false)
            ->assertSee('Today', false)
            ->assertDontSee('PPMF Week', false)
            ->assertDontSee('W26', false);

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
        $period = app(KpiPeriodService::class);
        $dashboard = app(KpiDashboardService::class);

        $daily = $dashboard->assignedCards($user, Request::create('/', 'GET', [
            'period_type' => 'daily',
            'date' => now()->toDateString(),
        ]));
        $weekly = $dashboard->assignedCards($user, Request::create('/', 'GET', [
            'period_type' => 'weekly',
            'week_no' => $period->currentWeekNo(),
        ]));
        $monthly = $dashboard->assignedCards($user, Request::create('/', 'GET', [
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

        $dashboard = app(KpiDashboardService::class);
        $inspections = app(KpiInspectionService::class);
        $period = app(KpiPeriodService::class);
        $card = KpiCard::where('slug', 'inspection-of-health-facilities')->firstOrFail();
        $weeklyRequest = Request::create('/', 'GET', [
            'period_type' => 'weekly',
            'week_no' => $period->currentWeekNo(),
        ]);
        $monthlyRequest = Request::create('/', 'GET', [
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

        foreach ($users->keys() as $username) {
            $user = $users[$username];
            $tehsilIds = $inspections->officialTehsilIds($user, $weeklyRequest);
            $districtIds = $inspections->officialDistrictIds($user, $weeklyRequest);
            $expectedWeeklyTarget = match ($user->role?->slug) {
                'ac', 'field_user' => 2.0,
                'dc' => (float) (($tehsilIds->count() * 2) + 2),
                default => (float) (($tehsilIds->count() * 2) + ($districtIds->count() * 2)),
            };
            $this->assertSame($expectedWeeklyTarget, (float) $weekly[$username]->target, $username.' weekly target');
        }

        $this->assertGreaterThanOrEqual((float) $weekly['ac.lahore']->target, (float) $weekly['dc.lahore']->target);
        $this->assertGreaterThanOrEqual((float) $weekly['com.lahore']->target, (float) $weekly['cs.pmru']->target);

        $weeksInMonth = (int) ceil(now()->daysInMonth / 7);
        foreach ($users->keys() as $username) {
            $user = $users[$username];
            $this->assertSame(
                (float) $weekly[$username]->target * $weeksInMonth,
                (float) $monthly[$username]->target
            );
            $expectedAchieved = $card->slug === 'inspection-of-health-facilities'
                ? (float) $inspections->countHealthInspected($card, $user, $weeklyRequest)
                : (float) $inspections->countOperationalAchieved($card, $user, $weeklyRequest);
            $expectedDisplay = min($expectedAchieved, (float) $weekly[$username]->target);
            $this->assertSame($expectedDisplay, (float) $weekly[$username]->achieved, $username);
            $this->assertLessThanOrEqual((float) $weekly[$username]->target, (float) $weekly[$username]->achieved, $username);
            if ($username === 'ac.lahore') {
                $this->assertGreaterThanOrEqual((float) $weekly['ac.lahore']->achieved, $expectedAchieved);
            }
            $this->assertGreaterThan((float) $weekly['ac.lahore']->achieved, (float) $weekly['dc.lahore']->achieved);
        }
    }

    public function test_health_and_education_achieved_matches_detail_header_and_inspection_rows(): void
    {
        $this->seed(PpmuSeeder::class);

        $dashboard = app(KpiDashboardService::class);
        $inspections = app(KpiInspectionService::class);
        $period = app(KpiPeriodService::class);
        $user = User::where('username', 'ac.lahore')->firstOrFail();
        $request = Request::create('/', 'GET', [
            'period_type' => 'weekly',
            'week_no' => $period->currentWeekNo(),
        ]);

        foreach (['inspection-of-health-facilities', 'inspection-of-educational-institutions'] as $slug) {
            $card = KpiCard::where('slug', $slug)->firstOrFail();
            $home = $dashboard->assignedCards($user, $request)->firstWhere('slug', $slug);
            $detail = $dashboard->detail($card, $user, $request);
            $rawAchieved = in_array($slug, [
                'inspection-of-health-facilities',
                'inspection-of-educational-institutions',
            ], true)
                ? (float) ($slug === 'inspection-of-health-facilities'
                    ? $inspections->countHealthInspected($card, $user, $request)
                    : $inspections->countEducationInspected($card, $user, $request))
                : (float) $inspections->countOperationalAchieved($card, $user, $request);
            $expected = in_array($slug, [
                'inspection-of-health-facilities',
                'inspection-of-educational-institutions',
            ], true)
                ? min($rawAchieved, (float) $home->target)
                : $rawAchieved;

            $this->assertSame($expected, (float) $home->achieved, $slug.' home achieved');
            $this->assertSame((float) $home->target, (float) $detail['header']['operational_target'], $slug.' target parity');
            $this->assertSame((float) $home->achieved, (float) $detail['header']['completed'], $slug.' achieved parity');
            if (in_array($slug, ['inspection-of-health-facilities', 'inspection-of-educational-institutions'], true)) {
                $this->assertSame($rawAchieved, (float) $detail['header']['actual_completed']);
                $this->assertLessThanOrEqual(100.0, (float) $detail['header']['achievement_percentage']);
            }
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
                'period_filters',
            ]);
    }

    public function test_health_detail_defaults_to_weekly_and_hides_today_period_type(): void
    {
        $this->seed(PpmuSeeder::class);
        $user = User::where('username', 'ac.lahore')->firstOrFail();

        $this->actingAs($user)
            ->get('/kpi/inspection-of-health-facilities/dashboard')
            ->assertOk()
            ->assertSee('Weekly', false)
            ->assertDontSee('data-period-type="daily"', false);

        $detail = app(KpiDashboardService::class)->detail(
            KpiCard::where('slug', 'inspection-of-health-facilities')->firstOrFail(),
            $user,
            Request::create('/kpi/inspection-of-health-facilities/dashboard', 'GET')
        );

        $this->assertSame('weekly', $detail['period']['period_type']);
    }

    public function test_roti_detail_defaults_to_today(): void
    {
        $this->seed(PpmuSeeder::class);
        $user = User::where('username', 'ac.lahore')->firstOrFail();

        $detail = app(KpiDashboardService::class)->detail(
            KpiCard::where('slug', 'price-of-roti')->firstOrFail(),
            $user,
            Request::create('/kpi/price-of-roti/dashboard', 'GET')
        );

        $this->assertSame('daily', $detail['period']['period_type']);
        $this->assertSame(now()->toDateString(), $detail['period']['date']);
    }

    public function test_kpi_score_is_hidden_from_detail_header(): void
    {
        $this->seed(PpmuSeeder::class);
        $user = User::where('username', 'ac.lahore')->firstOrFail();

        $this->actingAs($user)
            ->get('/kpi/inspection-of-health-facilities/dashboard')
            ->assertOk()
            ->assertDontSee('data-stat="score"', false)
            ->assertDontSee('KPI Score');
    }

    public function test_ac_karor_only_sees_karor_scoped_data(): void
    {
        $this->seed(PpmuSeeder::class);
        $card = KpiCard::where('slug', 'inspection-of-health-facilities')->firstOrFail();
        $karor = User::where('username', 'ac.karor')->firstOrFail();
        $layyah = User::where('username', 'ac.layyah')->firstOrFail();
        $period = app(KpiPeriodService::class);
        $request = Request::create('/', 'GET', [
            'period_type' => 'weekly',
            'week_no' => $period->currentWeekNo(),
        ]);

        $karorInspection = KpiInspection::where('kpi_card_id', $card->id)
            ->where('tehsil_id', $karor->tehsil_id)
            ->firstOrFail();
        $layyahInspection = KpiInspection::where('kpi_card_id', $card->id)
            ->where('tehsil_id', $layyah->tehsil_id)
            ->firstOrFail();

        $this->actingAs($karor)
            ->get(route('kpi.inspections.show', [$card, $karorInspection]))
            ->assertOk();

        $this->actingAs($karor)
            ->get(route('kpi.inspections.show', [$card, $layyahInspection]))
            ->assertForbidden();
    }

    public function test_dc_layyah_health_dashboard_has_tehsil_inspection_progress_chart(): void
    {
        $this->seed(PpmuSeeder::class);
        $user = User::where('username', 'dc.layyah')->firstOrFail();
        $card = KpiCard::where('slug', 'inspection-of-health-facilities')->firstOrFail();
        $period = app(KpiPeriodService::class);
        $detail = app(KpiDashboardService::class)->detail(
            $card,
            $user,
            Request::create('/', 'GET', [
                'period_type' => 'weekly',
                'week_no' => $period->currentWeekNo(),
            ])
        );

        $keys = collect($detail['chartDefinitions'])->pluck('key');
        $this->assertTrue($keys->contains('health_tehsil_inspection_progress'));
        $this->assertTrue($keys->contains('health_inspection_target_achievement'));
        $this->assertTrue($keys->contains('health_review_target_status'));
        $this->assertFalse($keys->contains('dc_ac_visit_completion'));
        $comparison = collect($detail['charts']['definitions'])->firstWhere('key', 'health_tehsil_inspection_progress');
        $this->assertStringContainsString('Tehsil Inspection Progress', (string) ($comparison['title'] ?? ''));
        $this->assertStringContainsString('capped at 2 per tehsil', strtolower((string) ($comparison['subtitle'] ?? '')));
        $this->assertNotEmpty($comparison['data']['labels'] ?? []);
        $layyah = collect($comparison['data']['labels'] ?? [])->first(fn ($l) => str_contains((string) $l, 'Layyah'));
        $karor = collect($comparison['data']['labels'] ?? [])->first(fn ($l) => str_contains((string) $l, 'Karor'));
        $chaubara = collect($comparison['data']['labels'] ?? [])->first(fn ($l) => str_contains((string) $l, 'Chaubara'));
        $this->assertNotNull($layyah);
        $this->assertNotNull($karor);
        $this->assertNotNull($chaubara);
    }

    public function test_inspection_list_section_has_no_duplicate_summary_cards(): void
    {
        $this->seed(PpmuSeeder::class);
        $user = User::where('username', 'ac.lahore')->firstOrFail();

        $this->actingAs($user)
            ->get('/kpi/inspection-of-health-facilities/dashboard')
            ->assertOk()
            ->assertDontSee('kpiInspectionFilter', false)
            ->assertDontSee('ppmu-inspection-count-grid', false)
            ->assertDontSee('ppmu-inspections-link-section', false);
    }

    public function test_kpi_detail_hides_all_period_tab(): void
    {
        $this->seed(PpmuSeeder::class);
        $user = User::where('username', 'ac.lahore')->firstOrFail();

        $this->actingAs($user)
            ->get('/kpi/inspection-of-health-facilities/dashboard')
            ->assertOk()
            ->assertDontSee('data-period-type=""', false)
            ->assertSee('Weekly', false)
            ->assertSee('Monthly', false);
    }

    public function test_weekly_dropdown_uses_month_wise_labels_not_w26(): void
    {
        $this->seed(PpmuSeeder::class);

        $period = app(KpiPeriodService::class);
        $filters = $period->filterOptions((int) now()->year, (int) now()->month);
        $labels = array_values($filters['weeks'] ?? []);

        $this->assertNotEmpty($labels);
        $this->assertStringStartsWith('Week 1', $labels[0]);
        $this->assertStringNotContainsString('W26', $labels[0]);
        $this->assertStringContainsString('Thu', $labels[0]);
        $this->assertStringContainsString('Wed', $labels[0]);

        $weekNo = array_key_first($filters['weeks'] ?? []);
        $range = $period->getWeekDateRange((string) $weekNo);
        $this->assertStringContainsString($range['start']->format('d M'), $labels[0]);
        $this->assertStringContainsString($range['end']->format('d M'), $labels[0]);

        $user = User::where('username', 'ac.lahore')->firstOrFail();
        $this->actingAs($user)
            ->get('/kpi/inspection-of-health-facilities/dashboard')
            ->assertOk()
            ->assertDontSee('W26', false);
    }

    public function test_dc_sees_only_tehsil_geo_filter(): void
    {
        $this->seed(PpmuSeeder::class);
        $user = User::where('username', 'dc.layyah')->firstOrFail();

        $this->actingAs($user)
            ->get('/kpi/inspection-of-health-facilities/dashboard')
            ->assertOk()
            ->assertSee('kpiGeoFilter', false)
            ->assertSee('All tehsils', false)
            ->assertDontSee('All divisions', false)
            ->assertDontSee('All districts', false);
    }

    public function test_submission_reports_section_on_detail_dashboard(): void
    {
        $this->seed(PpmuSeeder::class);
        $user = User::where('username', 'ac.lahore')->firstOrFail();

        $this->actingAs($user)
            ->get('/kpi/price-of-roti/dashboard')
            ->assertOk()
            ->assertSee('Submission Reports')
            ->assertSee('KPI summary rows from submissions', false);
    }

    public function test_health_detail_hides_submission_reports_section(): void
    {
        $this->seed(PpmuSeeder::class);
        $user = User::where('username', 'ac.lahore')->firstOrFail();

        $this->actingAs($user)
            ->get('/kpi/inspection-of-health-facilities/dashboard')
            ->assertOk()
            ->assertDontSee('Submission Reports')
            ->assertDontSee('kpiDetailRecords', false);
    }

    public function test_health_metric_sections_are_grouped(): void
    {
        $this->seed(PpmuSeeder::class);
        $user = User::where('username', 'dc.lahore')->firstOrFail();
        $card = KpiCard::where('slug', 'inspection-of-health-facilities')->firstOrFail();
        $detail = app(KpiDashboardService::class)->detail(
            $card,
            $user,
            Request::create('/', 'GET', ['period_type' => 'weekly'])
        );

        $titles = collect($detail['metricSections'])->pluck('title');
        $this->assertTrue($titles->contains('Inspection Coverage'));
        $this->assertTrue($titles->contains('Observations'));
    }

    public function test_health_achieved_counts_all_inspection_statuses_for_header(): void
    {
        $this->seed(PpmuSeeder::class);

        $card = KpiCard::where('slug', 'inspection-of-health-facilities')->firstOrFail();
        $user = User::where('username', 'ac.karor')->firstOrFail();
        $inspections = app(KpiInspectionService::class);
        $request = Request::create('/', 'GET', [
            'period_type' => 'weekly',
            'week_no' => app(KpiPeriodService::class)->currentWeekNo(),
            'month' => (string) now()->month,
            'year' => (string) now()->year,
        ]);

        $scoped = $inspections->healthInspectionsForMetrics($card, $user, $request);
        $expected = $scoped->count();
        $detail = app(KpiDashboardService::class)->detail($card, $user, $request);
        $target = (float) $detail['header']['operational_target'];

        $this->assertGreaterThan(0, $expected);
        $this->assertSame($expected, $inspections->countHealthInspected($card, $user, $request));
        $this->assertSame((float) $expected, (float) ($detail['header']['actual_completed'] ?? $detail['header']['completed']));
        $this->assertSame(min($expected, (int) $target), (int) $detail['header']['completed']);
        if ($expected >= $target && $target > 0) {
            $this->assertSame(100.0, (float) $detail['header']['achievement_percentage']);
        }
    }

    public function test_weekly_dropdown_week_no_controls_detail_period_range(): void
    {
        $this->seed(PpmuSeeder::class);

        $period = app(KpiPeriodService::class);
        $weekNo = $period->currentWeekNo();
        $range = $period->getWeekDateRange($weekNo);
        $filters = $period->filterOptions((int) now()->year, (int) now()->month);

        $this->assertArrayHasKey($weekNo, $filters['weeks']);
        $this->assertStringStartsWith('Week ', $filters['weeks'][$weekNo]);

        $detail = app(KpiDashboardService::class)->detail(
            KpiCard::where('slug', 'inspection-of-health-facilities')->firstOrFail(),
            User::where('username', 'dc.layyah')->firstOrFail(),
            Request::create('/', 'GET', [
                'period_type' => 'weekly',
                'week_no' => $weekNo,
                'month' => (string) now()->month,
                'year' => (string) now()->year,
            ])
        );

        $this->assertSame($weekNo, $detail['period']['week_no']);
        $this->assertSame($filters['weeks'][$weekNo], $detail['period_description']);
        $this->assertNotNull($range['start']);
        $this->assertNotNull($range['end']);
    }

    public function test_roti_detail_respects_explicit_weekly_period(): void
    {
        $this->seed(PpmuSeeder::class);
        $user = User::where('username', 'ac.layyah')->firstOrFail();
        $period = app(KpiPeriodService::class);

        $detail = app(KpiDashboardService::class)->detail(
            KpiCard::where('slug', 'price-of-roti')->firstOrFail(),
            $user,
            Request::create('/kpi/price-of-roti/dashboard', 'GET', [
                'period_type' => 'weekly',
                'week_no' => $period->currentWeekNo(),
            ])
        );

        $this->assertSame('weekly', $detail['period']['period_type']);
        $this->assertSame($period->currentWeekNo(), $detail['period']['week_no']);
        $this->assertCount(3, $detail['chartDefinitions']);
        $this->assertGreaterThanOrEqual(6, count($detail['charts']['definitions'][0]['data']['labels']));
    }

    public function test_ac_health_dashboard_excludes_dc_ac_visit_chart(): void
    {
        $this->seed(PpmuSeeder::class);
        $user = User::where('username', 'ac.layyah')->firstOrFail();
        $detail = app(KpiDashboardService::class)->detail(
            KpiCard::where('slug', 'inspection-of-health-facilities')->firstOrFail(),
            $user,
            Request::create('/', 'GET', ['period_type' => 'weekly'])
        );

        $keys = collect($detail['chartDefinitions'])->pluck('key');
        $this->assertFalse($keys->contains('dc_ac_visit_completion'));
        $this->assertFalse($keys->contains('tehsil_comparison'));
        $this->assertFalse($keys->contains('district_comparison'));
        $this->assertTrue($keys->contains('health_review_target_status'));
        $this->assertTrue($keys->contains('health_observation_availability'));
    }

    public function test_ac_user_does_not_see_geo_location_filters(): void
    {
        $this->seed(PpmuSeeder::class);
        $user = User::where('username', 'ac.layyah')->firstOrFail();

        $this->actingAs($user)
            ->get('/kpi/inspection-of-health-facilities/dashboard')
            ->assertOk()
            ->assertDontSee('kpiGeoFilter', false)
            ->assertDontSee('All tehsils', false);
    }

    public function test_health_visit_counts_obey_status_invariant(): void
    {
        $this->seed(PpmuSeeder::class);
        $user = User::where('username', 'ac.layyah')->firstOrFail();
        $card = KpiCard::where('slug', 'inspection-of-health-facilities')->firstOrFail();
        $period = app(KpiPeriodService::class);
        $request = Request::create('/', 'GET', [
            'period_type' => 'weekly',
            'week_no' => $period->currentWeekNo(),
        ]);

        $detail = app(KpiDashboardService::class)->detail($card, $user, $request);
        $coverage = collect($detail['metricSections'])->firstWhere('title', 'Inspection Coverage');
        $values = collect($coverage['metrics'])->mapWithKeys(fn ($m) => [$m['label'] => $m['value']]);

        $approved = (int) $values['Approved'];
        $pending = (int) $values['Pending Review'];
        $rejected = (int) $values['Rejected'];
        $facilitiesInspected = (int) $values['Facilities Inspected'];
        $reviewTarget = (int) $values['Review Target'];
        $totalFacilities = (int) $values['Total Health Facilities'];
        $records = $approved + $pending + $rejected;

        $this->assertFalse($values->has('Inspection Records'));
        $this->assertFalse($values->has('Facilities Not Inspected'));
        $this->assertFalse($values->has('Review Completion %'));
        $this->assertCount(6, $coverage['metrics']);
        $this->assertSame($reviewTarget, $records);
        $this->assertLessThanOrEqual($totalFacilities, $facilitiesInspected);
        $this->assertFalse($values->has('Total Visits'));
        $this->assertFalse($values->has('Achieved'));
        $this->assertFalse(collect($detail['metricSections'])->pluck('title')->contains('Visits & Meetings'));
    }

    public function test_health_ac_header_caps_completed_to_weekly_target(): void
    {
        $this->seed(PpmuSeeder::class);

        $card = KpiCard::where('slug', 'inspection-of-health-facilities')->firstOrFail();
        $user = User::where('username', 'ac.layyah')->firstOrFail();
        $period = app(KpiPeriodService::class);
        $inspections = app(KpiInspectionService::class);
        $request = Request::create('/', 'GET', [
            'period_type' => 'weekly',
            'week_no' => $period->currentWeekNo(),
            'month' => (string) now()->month,
            'year' => (string) now()->year,
        ]);

        $actualCompleted = $inspections->countHealthInspected($card, $user, $request);
        $detail = app(KpiDashboardService::class)->detail($card, $user, $request);

        $this->assertGreaterThanOrEqual(2, $actualCompleted);
        $this->assertSame(2.0, (float) $detail['header']['operational_target']);
        $this->assertSame(2.0, (float) $detail['header']['completed']);
        $this->assertSame((float) $actualCompleted, (float) $detail['header']['actual_completed']);
        $this->assertSame(100.0, (float) $detail['header']['achievement_percentage']);
    }

    public function test_health_ac_weekly_card_counts_match_seeded_demo(): void
    {
        $this->seed(PpmuSeeder::class);

        $card = KpiCard::where('slug', 'inspection-of-health-facilities')->firstOrFail();
        $period = app(KpiPeriodService::class);
        $request = Request::create('/', 'GET', [
            'period_type' => 'weekly',
            'week_no' => $period->currentWeekNo(),
            'month' => (string) now()->month,
            'year' => (string) now()->year,
        ]);
        $dashboard = app(KpiDashboardService::class);

        $cases = [
            'ac.layyah' => ['total' => 20, 'facilities' => 2, 'approved' => 1, 'pending' => 0, 'rejected' => 0, 'review_target' => 1],
            'ac.karor' => ['total' => 28, 'facilities' => 2, 'approved' => 1, 'pending' => 0, 'rejected' => 0, 'review_target' => 1],
            'ac.lahore' => ['total' => 48, 'facilities' => 2, 'approved' => 1, 'pending' => 0, 'rejected' => 0, 'review_target' => 1],
        ];

        foreach ($cases as $username => $expected) {
            $user = User::where('username', $username)->firstOrFail();
            $detail = $dashboard->detail($card, $user, $request);
            $coverage = collect($detail['metricSections'])->firstWhere('title', 'Inspection Coverage');
            $values = collect($coverage['metrics'])->mapWithKeys(fn ($m) => [$m['label'] => $m['value']]);

            $this->assertSame($expected['total'], (int) $values['Total Health Facilities'], $username.' total');
            $this->assertSame($expected['facilities'], (int) $values['Facilities Inspected'], $username.' facilities');
            $this->assertFalse($values->has('Inspection Records'), $username.' hides inspection records card');
            $this->assertFalse($values->has('Facilities Not Inspected'), $username.' hides not inspected card');
            $this->assertFalse($values->has('Review Completion %'), $username.' hides review completion card');
            $this->assertSame($expected['approved'], (int) $values['Approved'], $username.' approved');
            $this->assertSame($expected['pending'], (int) $values['Pending Review'], $username.' pending');
            $this->assertSame($expected['rejected'], (int) $values['Rejected'], $username.' rejected');
            $this->assertSame(
                $expected['approved'] + $expected['pending'] + $expected['rejected'],
                $expected['review_target'],
                $username.' review status totals match review target'
            );
            $this->assertSame($expected['review_target'], (int) $values['Review Target'], $username.' review target');
            $this->assertSame(2.0, (float) $detail['header']['completed'], $username.' header completed');
            $this->assertLessThanOrEqual(2.0, (float) $detail['header']['completed'], $username.' header cap');
            $this->assertLessThanOrEqual(100.0, (float) $detail['header']['achievement_percentage'], $username.' progress cap');
            $this->assertFalse(collect($detail['metricSections'])->pluck('title')->contains('Visits & Meetings'), $username.' hides visits section');
        }
    }

    public function test_health_role_scope_isolates_tehsil_and_district_data(): void
    {
        $this->seed(PpmuSeeder::class);

        $card = KpiCard::where('slug', 'inspection-of-health-facilities')->firstOrFail();
        $period = app(KpiPeriodService::class);
        $request = Request::create('/', 'GET', [
            'period_type' => 'weekly',
            'week_no' => $period->currentWeekNo(),
        ]);
        $inspections = app(KpiInspectionService::class);

        $layyah = $inspections->healthInspectionsForMetrics($card, User::where('username', 'ac.layyah')->firstOrFail(), $request);
        $karor = $inspections->healthInspectionsForMetrics($card, User::where('username', 'ac.karor')->firstOrFail(), $request);
        $dcLayyah = $inspections->healthInspectionsForMetrics($card, User::where('username', 'dc.layyah')->firstOrFail(), $request);

        $this->assertGreaterThanOrEqual(2, $layyah->count());
        $this->assertGreaterThanOrEqual(2, $karor->count());
        $this->assertGreaterThanOrEqual($layyah->count() + $karor->count(), $dcLayyah->count());
        $this->assertTrue($layyah->pluck('tehsil_id')->every(fn ($id) => (int) $id === 24));
        $this->assertTrue($karor->pluck('tehsil_id')->every(fn ($id) => (int) $id === 25));
        $this->assertTrue($dcLayyah->pluck('district_id')->every(fn ($id) => (int) $id === 7));
    }

    public function test_health_chart_subtitles_are_management_friendly(): void
    {
        $this->seed(PpmuSeeder::class);
        $card = KpiCard::where('slug', 'inspection-of-health-facilities')->firstOrFail();
        $period = app(KpiPeriodService::class);
        $request = Request::create('/', 'GET', [
            'period_type' => 'weekly',
            'week_no' => $period->currentWeekNo(),
        ]);
        $dashboard = app(KpiDashboardService::class);

        $ac = $dashboard->detail($card, User::where('username', 'ac.lahore')->firstOrFail(), $request);
        $dc = $dashboard->detail($card, User::where('username', 'dc.layyah')->firstOrFail(), $request);
        $cs = $dashboard->detail($card, User::where('username', 'cs.pmru')->firstOrFail(), $request);

        foreach ($ac['chartDefinitions'] as $chart) {
            $this->assertNotSame('Bar chart', $chart['subtitle'] ?? null);
            $this->assertNotSame('Donut chart', $chart['subtitle'] ?? null);
        }

        $dcComparison = collect($dc['chartDefinitions'])->firstWhere('key', 'health_tehsil_inspection_progress');
        $this->assertStringContainsString('Tehsil Inspection Progress', (string) ($dcComparison['title'] ?? ''));

        $csComparison = collect($cs['chartDefinitions'])->firstWhere('key', 'health_district_inspection_progress');
        $this->assertStringContainsString('District Inspection Progress', (string) ($csComparison['title'] ?? ''));
        $this->assertFalse(collect($cs['chartDefinitions'])->pluck('key')->contains('division_comparison'));
    }

    public function test_health_header_shows_inspection_target_inspected_and_review_percent(): void
    {
        $this->seed(PpmuSeeder::class);
        $user = User::where('username', 'ac.layyah')->firstOrFail();

        $this->actingAs($user)
            ->get('/kpi/inspection-of-health-facilities/dashboard')
            ->assertOk()
            ->assertSee('Inspection Target', false)
            ->assertSee('Inspected', false)
            ->assertSee('Review %', false)
            ->assertDontSee('Visit Target', false)
            ->assertDontSee('Target Completed', false);
    }

    public function test_ac_health_dashboard_hides_visits_section_and_shows_six_coverage_cards(): void
    {
        $this->seed(PpmuSeeder::class);
        $user = User::where('username', 'ac.layyah')->firstOrFail();

        $this->actingAs($user)
            ->get('/kpi/inspection-of-health-facilities/dashboard')
            ->assertOk()
            ->assertDontSee('Visits &amp; Meetings', false)
            ->assertDontSee('Required Inspections', false)
            ->assertDontSee('Completed Inspections', false)
            ->assertDontSee('Target Achievement', false)
            ->assertDontSee('Facilities Not Inspected', false)
            ->assertDontSee('Review Completion %', false);
    }

    public function test_health_coverage_shows_six_cards_for_all_roles(): void
    {
        $this->seed(PpmuSeeder::class);
        $card = KpiCard::where('slug', 'inspection-of-health-facilities')->firstOrFail();
        $period = app(KpiPeriodService::class);
        $dashboard = app(KpiDashboardService::class);
        $request = Request::create('/', 'GET', [
            'period_type' => 'weekly',
            'week_no' => $period->currentWeekNo(),
        ]);

        foreach (['dc.layyah', 'ac.layyah', 'cs.pmru'] as $username) {
            $detail = $dashboard->detail($card, User::where('username', $username)->firstOrFail(), $request);
            $coverage = collect($detail['metricSections'])->firstWhere('title', 'Inspection Coverage');
            $labels = collect($coverage['metrics'])->pluck('label')->all();

            $this->assertCount(6, $labels, $username);
            $this->assertSame(
                ['Total Health Facilities', 'Facilities Inspected', 'Review Target', 'Pending Review', 'Approved', 'Rejected'],
                $labels,
                $username
            );
            $this->assertNotContains('Facilities Not Inspected', $labels, $username);
            $this->assertNotContains('Review Completion %', $labels, $username);
        }
    }

    public function test_health_review_target_formula_by_role(): void
    {
        $this->seed(PpmuSeeder::class);
        $card = KpiCard::where('slug', 'inspection-of-health-facilities')->firstOrFail();
        $period = app(KpiPeriodService::class);
        $request = Request::create('/', 'GET', [
            'period_type' => 'weekly',
            'week_no' => $period->currentWeekNo(),
        ]);
        $dashboard = app(KpiDashboardService::class);

        $ac = $dashboard->detail($card, User::where('username', 'ac.layyah')->firstOrFail(), $request);
        $dc = $dashboard->detail($card, User::where('username', 'dc.layyah')->firstOrFail(), $request);

        $acCoverage = collect($ac['metricSections'])->firstWhere('title', 'Inspection Coverage');
        $dcCoverage = collect($dc['metricSections'])->firstWhere('title', 'Inspection Coverage');
        $acValues = collect($acCoverage['metrics'])->mapWithKeys(fn ($m) => [$m['label'] => $m['value']]);
        $dcValues = collect($dcCoverage['metrics'])->mapWithKeys(fn ($m) => [$m['label'] => $m['value']]);

        $this->assertSame(1, (int) $acValues['Review Target']);
        $this->assertFalse($acValues->has('Review Completion %'));
        $this->assertSame(1, (int) $acValues['Approved'] + (int) $acValues['Pending Review'] + (int) $acValues['Rejected']);
        $this->assertGreaterThanOrEqual(1, (int) $dcValues['Review Target']);
        $this->assertFalse($dcValues->has('Review Completion %'));
        $this->assertSame(
            (int) $dcValues['Review Target'],
            (int) $dcValues['Approved'] + (int) $dcValues['Pending Review'] + (int) $dcValues['Rejected']
        );
    }

    public function test_health_observations_section_has_eight_cards(): void
    {
        $this->seed(PpmuSeeder::class);
        $card = KpiCard::where('slug', 'inspection-of-health-facilities')->firstOrFail();
        $detail = app(KpiDashboardService::class)->detail(
            $card,
            User::where('username', 'ac.layyah')->firstOrFail(),
            Request::create('/', 'GET', [
                'period_type' => 'weekly',
                'week_no' => app(KpiPeriodService::class)->currentWeekNo(),
            ])
        );

        $observations = collect($detail['metricSections'])->firstWhere('title', 'Observations');
        $labels = collect($observations['metrics'])->pluck('label')->all();

        $this->assertCount(8, $labels);
        $this->assertContains('Observation Issues', $labels);
        $deepCleaning = collect($observations['metrics'])->firstWhere('label', 'Deep Cleaning');
        $this->assertSame('observation_availability', $deepCleaning['display_mode'] ?? null);
        $this->assertSame('Satisfactory', $deepCleaning['observation_positive_label'] ?? null);
        $this->assertSame('Unsatisfactory', $deepCleaning['observation_negative_label'] ?? null);
        $this->assertGreaterThanOrEqual(0, (int) ($deepCleaning['observation_available'] ?? -1));
        $this->assertGreaterThanOrEqual(0, (int) ($deepCleaning['observation_not_available'] ?? -1));
        $this->assertGreaterThan(
            0,
            (int) ($deepCleaning['observation_available'] ?? 0) + (int) ($deepCleaning['observation_not_available'] ?? 0)
        );

        $staffAvailability = collect($observations['metrics'])->firstWhere('label', 'Staff Availability');
        $this->assertSame('Present', $staffAvailability['observation_positive_label'] ?? null);
        $this->assertSame('Absent', $staffAvailability['observation_negative_label'] ?? null);

        $utilities = collect($observations['metrics'])->firstWhere('label', 'Utilities');
        $this->assertSame('Functional', $utilities['observation_positive_label'] ?? null);
        $this->assertSame('Non-Functional', $utilities['observation_negative_label'] ?? null);

        $uhiCompliance = collect($observations['metrics'])->firstWhere('label', 'UHI Compliance');
        $this->assertSame('observation_yesno', $uhiCompliance['display_mode'] ?? null);
        $this->assertSame('Compliant', $uhiCompliance['observation_positive_label'] ?? null);
        $this->assertSame('Non-Compliant', $uhiCompliance['observation_negative_label'] ?? null);

        $medicineAvailability = collect($observations['metrics'])->firstWhere('label', 'Medicine Availability');
        $this->assertNotNull($medicineAvailability);
    }

    public function test_health_total_facilities_card_has_inventory_helper_text(): void
    {
        $this->seed(PpmuSeeder::class);
        $card = KpiCard::where('slug', 'inspection-of-health-facilities')->firstOrFail();
        $detail = app(KpiDashboardService::class)->detail(
            $card,
            User::where('username', 'ac.layyah')->firstOrFail(),
            Request::create('/', 'GET', [
                'period_type' => 'weekly',
                'week_no' => app(KpiPeriodService::class)->currentWeekNo(),
            ])
        );

        $coverage = collect($detail['metricSections'])->firstWhere('title', 'Inspection Coverage');
        $totalFacilities = collect($coverage['metrics'])->firstWhere('label', 'Total Health Facilities');

        $this->assertSame('Total health facilities in this area', $totalFacilities['card_helper'] ?? null);
        $this->assertSame('Total health facilities in this area', $totalFacilities['description'] ?? null);
    }

    public function test_health_observation_chart_is_observation_availability_stacked_bar(): void
    {
        $this->seed(PpmuSeeder::class);
        $card = KpiCard::where('slug', 'inspection-of-health-facilities')->firstOrFail();
        $detail = app(KpiDashboardService::class)->detail(
            $card,
            User::where('username', 'ac.layyah')->firstOrFail(),
            Request::create('/', 'GET', [
                'period_type' => 'weekly',
                'week_no' => app(KpiPeriodService::class)->currentWeekNo(),
            ])
        );

        $chart = collect($detail['charts']['definitions'])->firstWhere('key', 'health_observation_availability');
        $this->assertNotNull($chart);
        $this->assertSame('Observation Availability', $chart['title']);
        $this->assertSame('stacked_bar', $chart['type']);
        $this->assertStringContainsString('Observation outcomes from inspected health facilities', (string) ($chart['subtitle'] ?? ''));
        $this->assertCount(2, $chart['data']['datasets'] ?? []);
        $this->assertSame('Positive outcome', $chart['data']['datasets'][0]['label'] ?? null);
        $this->assertSame('Negative outcome', $chart['data']['datasets'][1]['label'] ?? null);
        $this->assertSame(2, (int) ($chart['data']['facilities_inspected'] ?? 0));

        $deepCleaningAvailable = (int) ($chart['data']['datasets'][0]['values'][0] ?? 0);
        $deepCleaningNotAvailable = (int) ($chart['data']['datasets'][1]['values'][0] ?? 0);
        $this->assertSame(2, $deepCleaningAvailable + $deepCleaningNotAvailable);
    }

    public function test_health_observation_cards_do_not_use_combined_value_text(): void
    {
        $this->seed(PpmuSeeder::class);
        $card = KpiCard::where('slug', 'inspection-of-health-facilities')->firstOrFail();
        $detail = app(KpiDashboardService::class)->detail(
            $card,
            User::where('username', 'ac.layyah')->firstOrFail(),
            Request::create('/', 'GET', [
                'period_type' => 'weekly',
                'week_no' => app(KpiPeriodService::class)->currentWeekNo(),
            ])
        );

        $observations = collect($detail['metricSections'])->firstWhere('title', 'Observations');
        foreach ($observations['metrics'] as $metric) {
            if (($metric['label'] ?? '') === 'Observation Issues') {
                $this->assertSame('attention', $metric['display_mode'] ?? null);
                $this->assertSame('Deficiencies Found', $metric['card_helper'] ?? null);

                continue;
            }

            $this->assertStringNotContainsString(' / Not ', (string) ($metric['value'] ?? ''));
        }
    }

    public function test_dc_layyah_ac_visit_target_matches_official_tehsils(): void
    {
        $this->seed(PpmuSeeder::class);
        $card = KpiCard::where('slug', 'inspection-of-health-facilities')->firstOrFail();
        $user = User::where('username', 'dc.layyah')->firstOrFail();
        $period = app(KpiPeriodService::class);
        $request = Request::create('/', 'GET', [
            'period_type' => 'weekly',
            'week_no' => $period->currentWeekNo(),
        ]);

        $detail = app(KpiDashboardService::class)->detail($card, $user, $request);
        $visits = collect($detail['metricSections'])->firstWhere('title', 'Visits & Meetings');
        $visitValues = collect($visits['metrics'])->mapWithKeys(fn ($m) => [$m['label'] => $m['value']]);
        $comparison = collect($detail['charts']['definitions'])->firstWhere('key', 'health_tehsil_inspection_progress');
        $officialTehsils = app(KpiInspectionService::class)->officialTehsilIds($user, $request)->count();
        $achievement = collect($detail['charts']['definitions'])->firstWhere('key', 'health_inspection_target_achievement');

        $this->assertSame($officialTehsils, count($comparison['data']['labels'] ?? []));
        $this->assertSame($officialTehsils * 2, (int) explode(' / ', (string) $visitValues['ACs Visits'])[1]);
        $this->assertContains('Target', $achievement['data']['labels'] ?? []);
        $this->assertContains('Inspected', $achievement['data']['labels'] ?? []);
        $this->assertContains('Remaining', $achievement['data']['labels'] ?? []);
        $this->assertFalse(collect($detail['chartDefinitions'])->pluck('key')->contains('dc_ac_visit_completion'));
    }

    public function test_cs_health_demo_progress_is_not_critical(): void
    {
        $this->seed(PpmuSeeder::class);
        $card = KpiCard::where('slug', 'inspection-of-health-facilities')->firstOrFail();
        $user = User::where('username', 'cs.pmru')->firstOrFail();
        $period = app(KpiPeriodService::class);
        $detail = app(KpiDashboardService::class)->detail(
            $card,
            $user,
            Request::create('/', 'GET', [
                'period_type' => 'weekly',
                'week_no' => $period->currentWeekNo(),
            ])
        );

        $this->assertGreaterThan(0, (float) $detail['header']['operational_target']);
        $comDetail = app(KpiDashboardService::class)->detail(
            $card,
            User::where('username', 'com.lahore')->firstOrFail(),
            Request::create('/', 'GET', [
                'period_type' => 'weekly',
                'week_no' => $period->currentWeekNo(),
            ])
        );
        $this->assertGreaterThan(
            (float) $comDetail['header']['operational_target'],
            (float) $detail['header']['operational_target']
        );
        $this->assertGreaterThan(0.0, (float) $detail['header']['achievement_percentage']);
        $this->assertNotSame('Critical', $detail['header']['status_label']);
    }

    public function test_health_dashboard_renders_inspection_map_section(): void
    {
        $this->seed(PpmuSeeder::class);
        $card = KpiCard::where('slug', 'inspection-of-health-facilities')->firstOrFail();
        $user = User::where('username', 'ac.layyah')->firstOrFail();

        $response = $this->actingAs($user)
            ->get(route('kpi.dashboard', $card));

        $response->assertOk()
            ->assertSee('Health Facility Inspection Map')
            ->assertSee('Showing health inspection locations for the selected period')
            ->assertSee('id="ppmuHealthDashboardMap"', false)
            ->assertSee('KPI Performance Cards')
            ->assertSee('KPI Charts')
            ->assertDontSee('Field Inspections')
            ->assertDontSee('Not Inspected', false)
            ->assertDontSee('Deficiency Found', false);

        $content = $response->getContent();
        $metricsPos = strpos($content, 'id="kpiDetailMetrics"');
        $mapPos = strpos($content, 'id="kpiDetailHealthMap"');
        $chartsPos = strpos($content, 'id="kpiDetailCharts"');

        $this->assertNotFalse($metricsPos);
        $this->assertNotFalse($mapPos);
        $this->assertNotFalse($chartsPos);
        $this->assertGreaterThan($metricsPos, $mapPos);
        $this->assertLessThan($chartsPos, $mapPos);
    }

    public function test_health_map_matches_dashboard_card_counts_for_weekly_period(): void
    {
        $this->seed(PpmuSeeder::class);
        $card = KpiCard::where('slug', 'inspection-of-health-facilities')->firstOrFail();
        $user = User::where('username', 'ac.layyah')->firstOrFail();
        $period = app(KpiPeriodService::class);
        $request = Request::create('/', 'GET', [
            'period_type' => 'weekly',
            'week_no' => $period->currentWeekNo(),
        ]);

        $dashboard = app(KpiDashboardService::class);
        $detail = $dashboard->detail($card, $user, $request);
        $coverage = collect($detail['metricSections'])->firstWhere('title', 'Inspection Coverage');
        $values = collect($coverage['metrics'])->mapWithKeys(fn ($m) => [$m['label'] => $m['value']]);
        $map = $detail['healthMap'];

        $facilitiesInspected = (int) $values['Facilities Inspected'];
        $approved = (int) $values['Approved'];
        $pending = (int) $values['Pending Review'];
        $rejected = (int) $values['Rejected'];

        $this->assertSame(20, (int) $values['Total Health Facilities']);
        $this->assertSame(2, $facilitiesInspected);
        $this->assertSame(1, (int) $values['Review Target']);
        $this->assertSame(1, $approved);
        $this->assertSame(0, $pending);
        $this->assertSame(0, $rejected);

        $this->assertSame($facilitiesInspected, $map['pin_count']);
        $this->assertCount($facilitiesInspected, $map['pins']);

        $statusCounts = collect($map['pins'])->countBy('color');
        $this->assertSame($approved, $statusCounts->get('green', 0));
        $this->assertSame($pending, $statusCounts->get('orange', 0));
        $this->assertSame($rejected, $statusCounts->get('red', 0));
        $this->assertSame(
            $facilitiesInspected - $approved - $pending - $rejected,
            $statusCounts->get('blue', 0)
        );

        foreach ($map['pins'] as $pin) {
            $this->assertNotEmpty($pin['lat']);
            $this->assertNotEmpty($pin['lng']);
            $this->assertContains($pin['color'], ['green', 'orange', 'blue', 'red']);
            $this->assertArrayHasKey('inspection_id', $pin);
            $this->assertArrayHasKey('observation_issues', $pin);
            $this->assertNotNull($pin['detail_url']);
            $this->assertStringContainsString('/inspections/', $pin['detail_url']);
            $this->assertContains($pin['review_status'], ['Inspected', 'Pending Review', 'Approved', 'Rejected']);
        }

        if ($facilitiesInspected >= 2) {
            $coordinates = collect($map['pins'])->map(fn (array $pin) => round((float) $pin['lat'], 5).':'.round((float) $pin['lng'], 5));
            $this->assertSame($facilitiesInspected, $coordinates->unique()->count());
        }
    }

    public function test_education_dashboard_renders_inspection_map_section(): void
    {
        $this->seed(PpmuSeeder::class);
        $card = KpiCard::where('slug', 'inspection-of-educational-institutions')->firstOrFail();
        $user = User::where('username', 'ac.karor')->firstOrFail();

        $response = $this->actingAs($user)
            ->get(route('kpi.dashboard', $card));

        $response->assertOk()
            ->assertSee('Educational Institution Inspection Map')
            ->assertSee('Showing education inspection locations for the selected period')
            ->assertSee('id="ppmuHealthDashboardMap"', false)
            ->assertDontSee('Health Facility Inspection Map')
            ->assertDontSee('Not Inspected', false);

        $content = $response->getContent();
        $metricsPos = strpos($content, 'id="kpiDetailMetrics"');
        $mapPos = strpos($content, 'id="kpiDetailHealthMap"');
        $chartsPos = strpos($content, 'id="kpiDetailCharts"');

        $this->assertGreaterThan($metricsPos, $mapPos);
        $this->assertLessThan($chartsPos, $mapPos);
    }

    public function test_ac_karor_education_weekly_card_counts_match_seeded_demo(): void
    {
        $this->seed(PpmuSeeder::class);
        $card = KpiCard::where('slug', 'inspection-of-educational-institutions')->firstOrFail();
        $user = User::where('username', 'ac.karor')->firstOrFail();
        $period = app(KpiPeriodService::class);
        $request = Request::create('/', 'GET', [
            'period_type' => 'weekly',
            'week_no' => $period->currentWeekNo(),
        ]);

        $detail = app(KpiDashboardService::class)->detail($card, $user, $request);
        $coverage = collect($detail['metricSections'])->firstWhere('title', 'Inspection Coverage');
        $values = collect($coverage['metrics'])->mapWithKeys(fn ($m) => [$m['label'] => $m['value']]);
        $map = $detail['educationMap'];

        $this->assertSame(20, (int) $values['Total Educational Institutions']);
        $this->assertSame(2, (int) $values['Institutions Inspected']);
        $this->assertSame(1, (int) $values['Review Target']);
        $this->assertSame(1, (int) $values['Approved']);
        $this->assertSame(0, (int) $values['Pending Review']);
        $this->assertSame(0, (int) $values['Rejected']);
        $this->assertSame(2, $map['pin_count']);

        $statusCounts = collect($map['pins'])->countBy('color');
        $this->assertSame(1, $statusCounts->get('green', 0));
        $this->assertSame(1, $statusCounts->get('blue', 0));
    }

    public function test_education_observations_section_has_eight_cards(): void
    {
        $this->seed(PpmuSeeder::class);
        $card = KpiCard::where('slug', 'inspection-of-educational-institutions')->firstOrFail();
        $detail = app(KpiDashboardService::class)->detail(
            $card,
            User::where('username', 'ac.karor')->firstOrFail(),
            Request::create('/', 'GET', [
                'period_type' => 'weekly',
                'week_no' => app(KpiPeriodService::class)->currentWeekNo(),
            ])
        );

        $observations = collect($detail['metricSections'])->firstWhere('title', 'Observations');
        $labels = collect($observations['metrics'])->pluck('label')->all();

        $this->assertCount(8, $labels);
        $this->assertContains('Observation Issues', $labels);
        $this->assertContains('Student Attendance', $labels);
    }

    public function test_ac_education_dashboard_shows_two_charts_only(): void
    {
        $this->seed(PpmuSeeder::class);
        $user = User::where('username', 'ac.karor')->firstOrFail();
        $detail = app(KpiDashboardService::class)->detail(
            KpiCard::where('slug', 'inspection-of-educational-institutions')->firstOrFail(),
            $user,
            Request::create('/', 'GET', ['period_type' => 'weekly'])
        );

        $keys = collect($detail['chartDefinitions'])->pluck('key');
        $this->assertCount(2, $detail['chartDefinitions']);
        $this->assertTrue($keys->contains('education_review_target_status'));
        $this->assertTrue($keys->contains('education_observation_availability'));
        $this->assertFalse($keys->contains('education_student_attendance_summary'));
    }

    public function test_operational_kpi_map_shows_pins_for_default_period(): void
    {
        $this->seed(PpmuSeeder::class);
        $card = KpiCard::where('slug', 'zebra-crossings')->firstOrFail();
        $user = User::where('username', 'ac.layyah')->firstOrFail();

        $detail = app(KpiDashboardService::class)->detail(
            $card,
            $user,
            Request::create('/kpi/zebra-crossings/dashboard', 'GET'),
        );

        $map = $detail['locationMap'];
        $inspected = (int) collect($detail['metricSections'])
            ->flatMap(fn (array $section) => $section['metrics'])
            ->firstWhere('label', 'Schools Inspected')['value'] ?? 0;

        $this->assertGreaterThan(0, $inspected, 'Expected seeded zebra inspections for AC Layyah.');
        $this->assertSame($inspected, $map['pin_count']);
        $this->assertCount($inspected, $map['pins']);

        foreach ($map['pins'] as $pin) {
            $this->assertNotEmpty($pin['lat']);
            $this->assertNotEmpty($pin['lng']);
            $this->assertContains($pin['color'], ['green', 'orange', 'blue', 'red']);
            $this->assertArrayHasKey('detail_url', $pin);
            $this->assertStringContainsString('/inspections/', $pin['detail_url']);
            $this->assertNotSame('—', $pin['address']);
        }

        $this->actingAs($user)
            ->get(route('kpi.dashboard', $card))
            ->assertOk()
            ->assertSee('School Zebra Crossing Map')
            ->assertSee('5 inspections mapped')
            ->assertSee('locationMap:', false)
            ->assertSee('"pin_count":5', false)
            ->assertDontSee('Complaint Location Map');

        $ajax = $this->actingAs($user)
            ->getJson(route('kpi.dashboard.data', $card).'?'.http_build_query($detail['period']))
            ->assertOk()
            ->json();

        $this->assertSame(5, (int) ($ajax['location_map']['pin_count'] ?? 0));
        $this->assertCount(5, $ajax['location_map']['pins'] ?? []);
        $this->assertNull($ajax['visit_map']);
        $this->assertNull($ajax['health_map']);
    }

    public function test_daily_kpi_map_shows_pins_for_today(): void
    {
        $this->seed(PpmuSeeder::class);
        $card = KpiCard::where('slug', 'price-of-roti')->firstOrFail();
        $user = User::where('username', 'ac.layyah')->firstOrFail();

        $detail = app(KpiDashboardService::class)->detail(
            $card,
            $user,
            Request::create('/kpi/price-of-roti/dashboard', 'GET'),
        );

        $map = $detail['locationMap'];

        $this->assertSame(6, $map['pin_count']);
        $this->assertCount(6, $map['pins']);
        $this->assertSame('daily', $detail['period']['period_type']);
    }
}
