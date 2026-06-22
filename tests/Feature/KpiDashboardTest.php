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
        $card = KpiCard::where('slug', 'water-filtration')->firstOrFail();
        $admin = User::where('username', 'super_admin')->firstOrFail();

        $this->actingAs($admin)->get('/dashboard')->assertOk()->assertSee('PPMU Main KPI Dashboard');
        $this->actingAs($admin)->get('/dashboard')->assertSeeInOrder(['Performance', 'Open Dashboard']);
        $this->actingAs($admin)->get('/kpi/water-filtration/dashboard')->assertOk()->assertSee('Water Filtration');
        $this->actingAs($admin)->get('/manage-kpis')->assertOk()->assertSee('Manage KPI Cards');

        $ac = User::whereHas('role', fn ($query) => $query->where('slug', 'ac'))->whereNotNull('tehsil_id')->firstOrFail();
        $this->actingAs($ac)->get('/dashboard')->assertOk()->assertSee($card->title);
        $this->actingAs($ac)->get('/submit-kpi/water-filtration')->assertOk();
        $this->actingAs($ac)->get('/manage-kpis')->assertForbidden();
    }

    public function test_all_demo_users_see_23_kpi_cards_and_detail_dashboard(): void
    {
        $this->seed(PpmuSeeder::class);

        $this->assertSame(23, KpiCard::where('is_active', true)->count());

        foreach (['super_admin', 'cs.pmru', 'com.lahore', 'dc.lahore', 'ac.lahore', 'com.dgkhan', 'dc.layyah', 'ac.layyah'] as $login) {
            auth()->logout();
            $this->post('/login', ['login' => $login, 'password' => '123456'])
                ->assertRedirect(route('dashboard'));

            $response = $this->get('/dashboard');
            $response->assertOk()->assertSee('Water Filtration')->assertSee('Price of Roti');

            $cardCount = substr_count($response->getContent(), 'data-kpi-card');
            $this->assertSame(23, $cardCount, "User {$login} should see 23 KPI cards");

            $this->get('/kpi/water-filtration/dashboard')
                ->assertOk()
                ->assertSee('KPI Metrics')
                ->assertSee('statusChart')
                ->assertSee('targetChart');
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
}
