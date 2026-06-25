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
        $this->actingAs($admin)->get('/dashboard')->assertDontSee('ppmu-kpi-tile-stats', false);
        $this->actingAs($admin)->get('/dashboard')->assertDontSee('ppmu-kpi-percent-badge', false);
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
            $response->assertSee('View Dashboard')->assertDontSee('ppmu-kpi-tile-stats', false)->assertDontSee('ppmu-kpi-percent-badge', false)->assertDontSee('Reported')->assertDontSee('ppmu-kpi-tile-status', false)->assertDontSee('Performance</span>', false);

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

        $detail = app(\App\Services\KpiDashboardService::class)->detail(
            KpiCard::where('slug', 'inspection-of-health-facilities')->firstOrFail(),
            $user,
            \Illuminate\Http\Request::create('/kpi/inspection-of-health-facilities/dashboard', 'GET')
        );

        $this->assertSame('weekly', $detail['period']['period_type']);
    }

    public function test_roti_detail_defaults_to_today(): void
    {
        $this->seed(PpmuSeeder::class);
        $user = User::where('username', 'ac.lahore')->firstOrFail();

        $detail = app(\App\Services\KpiDashboardService::class)->detail(
            KpiCard::where('slug', 'price-of-roti')->firstOrFail(),
            $user,
            \Illuminate\Http\Request::create('/kpi/price-of-roti/dashboard', 'GET')
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
        $period = app(\App\Services\KpiPeriodService::class);
        $request = \Illuminate\Http\Request::create('/', 'GET', [
            'period_type' => 'weekly',
            'week_no' => $period->currentWeekNo(),
        ]);

        $karorInspection = \App\Models\KpiInspection::where('kpi_card_id', $card->id)
            ->where('tehsil_id', $karor->tehsil_id)
            ->firstOrFail();
        $layyahInspection = \App\Models\KpiInspection::where('kpi_card_id', $card->id)
            ->where('tehsil_id', $layyah->tehsil_id)
            ->firstOrFail();

        $this->actingAs($karor)
            ->get(route('kpi.inspections.show', [$card, $karorInspection]))
            ->assertOk();

        $this->actingAs($karor)
            ->get(route('kpi.inspections.show', [$card, $layyahInspection]))
            ->assertForbidden();
    }

    public function test_dc_layyah_health_dashboard_has_tehsil_comparison_chart(): void
    {
        $this->seed(PpmuSeeder::class);
        $user = User::where('username', 'dc.layyah')->firstOrFail();
        $card = KpiCard::where('slug', 'inspection-of-health-facilities')->firstOrFail();
        $period = app(\App\Services\KpiPeriodService::class);
        $detail = app(\App\Services\KpiDashboardService::class)->detail(
            $card,
            $user,
            \Illuminate\Http\Request::create('/', 'GET', [
                'period_type' => 'weekly',
                'week_no' => $period->currentWeekNo(),
            ])
        );

        $keys = collect($detail['charts']['definitions'])->pluck('key');
        $this->assertTrue($keys->contains('tehsil_comparison'));
        $comparison = collect($detail['charts']['definitions'])->firstWhere('key', 'tehsil_comparison');
        $this->assertNotEmpty($comparison['data']['labels'] ?? []);
    }

    public function test_inspection_list_section_has_no_duplicate_summary_cards(): void
    {
        $this->seed(PpmuSeeder::class);
        $user = User::where('username', 'ac.lahore')->firstOrFail();

        $this->actingAs($user)
            ->get('/kpi/inspection-of-health-facilities/dashboard')
            ->assertOk()
            ->assertSee('View Inspections')
            ->assertDontSee('kpiInspectionFilter', false)
            ->assertDontSee('ppmu-inspection-count-grid', false);
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

        $period = app(\App\Services\KpiPeriodService::class);
        $filters = $period->filterOptions((int) now()->year, (int) now()->month);
        $labels = array_values($filters['weeks'] ?? []);

        $this->assertNotEmpty($labels);
        $this->assertStringStartsWith('Week 1', $labels[0]);
        $this->assertStringNotContainsString('W26', $labels[0]);

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
            ->get('/kpi/inspection-of-health-facilities/dashboard')
            ->assertOk()
            ->assertSee('Submission Reports')
            ->assertSee('KPI summary rows from submissions', false);
    }

    public function test_health_metric_sections_are_grouped(): void
    {
        $this->seed(PpmuSeeder::class);
        $user = User::where('username', 'dc.lahore')->firstOrFail();
        $card = KpiCard::where('slug', 'inspection-of-health-facilities')->firstOrFail();
        $detail = app(\App\Services\KpiDashboardService::class)->detail(
            $card,
            $user,
            \Illuminate\Http\Request::create('/', 'GET', ['period_type' => 'weekly'])
        );

        $titles = collect($detail['metricSections'])->pluck('title');
        $this->assertTrue($titles->contains('Inspection Coverage'));
        $this->assertTrue($titles->contains('Issues Found'));
    }

    public function test_health_achieved_counts_approved_and_pending_excludes_rejected(): void
    {
        $this->seed(PpmuSeeder::class);

        $card = KpiCard::where('slug', 'inspection-of-health-facilities')->firstOrFail();
        $user = User::where('username', 'ac.lahore')->firstOrFail();
        $inspections = app(\App\Services\KpiInspectionService::class);
        $request = \Illuminate\Http\Request::create('/', 'GET', [
            'period_type' => 'monthly',
            'month' => (string) now()->month,
            'year' => (string) now()->year,
        ]);

        $scoped = $inspections->getInspectionsCollection($card, $user, $request);
        $expected = $scoped->whereIn('status', [
            \App\Models\KpiInspection::STATUS_APPROVED,
            \App\Models\KpiInspection::STATUS_PENDING,
        ])->count();
        $rejected = $scoped->where('status', \App\Models\KpiInspection::STATUS_REJECTED)->count();
        $achieved = $inspections->countOperationalAchieved($card, $user, $request);

        $this->assertSame($expected, $achieved);
        $this->assertGreaterThan(0, $expected);
        $this->assertGreaterThan(0, $rejected);
        $this->assertLessThan($scoped->count(), $achieved);
    }

    public function test_weekly_dropdown_week_no_controls_detail_period_range(): void
    {
        $this->seed(PpmuSeeder::class);

        $period = app(\App\Services\KpiPeriodService::class);
        $weekNo = $period->currentWeekNo();
        $range = $period->getWeekDateRange($weekNo);
        $filters = $period->filterOptions((int) now()->year, (int) now()->month);

        $this->assertArrayHasKey($weekNo, $filters['weeks']);
        $this->assertStringStartsWith('Week ', $filters['weeks'][$weekNo]);

        $detail = app(\App\Services\KpiDashboardService::class)->detail(
            KpiCard::where('slug', 'inspection-of-health-facilities')->firstOrFail(),
            User::where('username', 'dc.layyah')->firstOrFail(),
            \Illuminate\Http\Request::create('/', 'GET', [
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
        $period = app(\App\Services\KpiPeriodService::class);

        $detail = app(\App\Services\KpiDashboardService::class)->detail(
            KpiCard::where('slug', 'price-of-roti')->firstOrFail(),
            $user,
            \Illuminate\Http\Request::create('/kpi/price-of-roti/dashboard', 'GET', [
                'period_type' => 'weekly',
                'week_no' => $period->currentWeekNo(),
            ])
        );

        $this->assertSame('weekly', $detail['period']['period_type']);
        $this->assertSame($period->currentWeekNo(), $detail['period']['week_no']);
        $this->assertCount(2, $detail['chartDefinitions']);
        $this->assertCount(7, $detail['charts']['definitions'][0]['data']['labels']);
    }

    public function test_ac_health_dashboard_excludes_dc_ac_visit_chart(): void
    {
        $this->seed(PpmuSeeder::class);
        $user = User::where('username', 'ac.layyah')->firstOrFail();
        $detail = app(\App\Services\KpiDashboardService::class)->detail(
            KpiCard::where('slug', 'inspection-of-health-facilities')->firstOrFail(),
            $user,
            \Illuminate\Http\Request::create('/', 'GET', ['period_type' => 'weekly'])
        );

        $keys = collect($detail['chartDefinitions'])->pluck('key');
        $this->assertFalse($keys->contains('dc_ac_visit_completion'));
        $this->assertFalse($keys->contains('tehsil_comparison'));
        $this->assertTrue($keys->contains('inspection_status_breakdown'));
        $this->assertTrue($keys->contains('health_issue_breakdown'));
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
        $period = app(\App\Services\KpiPeriodService::class);
        $request = \Illuminate\Http\Request::create('/', 'GET', [
            'period_type' => 'weekly',
            'week_no' => $period->currentWeekNo(),
        ]);

        $detail = app(\App\Services\KpiDashboardService::class)->detail($card, $user, $request);
        $coverage = collect($detail['metricSections'])->firstWhere('title', 'Inspection Coverage');
        $values = collect($coverage['metrics'])->mapWithKeys(fn ($m) => [$m['label'] => $m['value']]);

        $total = (int) $values['Inspected'];
        $approved = (int) $values['Approved'];
        $pending = (int) $values['Pending'];
        $rejected = (int) $values['Rejected'];

        $this->assertSame($approved + $pending + $rejected, $total);
        $this->assertFalse($values->has('Total Visits'));
        $this->assertFalse($values->has('Achieved'));
    }
}
