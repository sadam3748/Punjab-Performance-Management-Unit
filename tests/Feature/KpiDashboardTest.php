<?php

namespace Tests\Feature;

use App\Models\KpiCard;
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
