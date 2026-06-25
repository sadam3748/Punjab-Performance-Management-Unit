<?php

namespace Tests\Feature;

use App\Models\KpiCard;
use App\Models\User;
use App\Services\KpiDashboardService;
use Database\Seeders\PpmuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class KpiDetailDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_facilities_dashboard_shows_non_zero_metrics(): void
    {
        $this->seed(PpmuSeeder::class);
        $slug = 'inspection-of-health-facilities';
        $card = KpiCard::where('slug', $slug)->firstOrFail();
        $admin = User::where('username', 'super_admin')->firstOrFail();

        $data = app(KpiDashboardService::class)->detail($card, $admin, Request::create('/'));

        $this->assertGreaterThan(0, $data['header']['completed']);
        $this->assertGreaterThan(0, $data['header']['operational_target']);
        $this->assertGreaterThan(0, $data['header']['achievement_percentage']);
        $this->assertGreaterThan(
            0,
            $data['metrics']->firstWhere('field', 'total_health_facilities')['value'] ?? 0
        );

        $this->actingAs($admin)
            ->get("/kpi/{$slug}/dashboard")
            ->assertOk()
            ->assertSee('KPI Performance Cards')
            ->assertSee('Visit Target')
            ->assertSee('Visits Completed')
            ->assertDontSee('data-stat="reported"', false)
            ->assertSee('KPI Score')
            ->assertSee('Progress')
            ->assertSee('Total Health Facilities')
            ->assertSee('Inspection List');
    }

    public function test_seeded_submission_volume_per_kpi(): void
    {
        $this->seed(PpmuSeeder::class);

        KpiCard::where('is_active', true)->each(function (KpiCard $card) {
            $count = $card->submissions()->count();
            if (app()->environment('testing')) {
                $this->assertGreaterThanOrEqual(20, $count, "KPI {$card->slug} should have test submissions");
                $priority = in_array($card->slug, [
                    'price-of-roti',
                    'inspection-of-educational-institutions',
                    'inspection-of-health-facilities',
                    'functional-and-clean-water-filtration-plants',
                    'chief-ministers-complaint-cell',
                    'e-biz',
                ], true);
                $this->assertLessThanOrEqual(
                    $priority ? 100 : 40,
                    $count,
                    "KPI {$card->slug} should not be over-seeded in tests"
                );

                return;
            }

            $this->assertGreaterThanOrEqual(50, $count, "KPI {$card->slug} should have enough submissions");
            $this->assertLessThanOrEqual(130, $count, "KPI {$card->slug} should not be over-seeded");
        });
    }

    public function test_priority_kpi_chart_definitions_have_populated_data(): void
    {
        $this->seed(PpmuSeeder::class);
        $admin = User::where('username', 'super_admin')->firstOrFail();
        $dashboard = app(KpiDashboardService::class);

        foreach ([
            'price-of-roti',
            'inspection-of-health-facilities',
            'inspection-of-educational-institutions',
            'functional-and-clean-water-filtration-plants',
            'chief-ministers-complaint-cell',
            'e-biz',
        ] as $slug) {
            $card = KpiCard::where('slug', $slug)->firstOrFail();
            $data = $dashboard->detail($card, $admin, Request::create('/'));

            foreach ($data['charts']['definitions'] as $definition) {
                $this->assertNotEmpty($definition['data']['labels'], "{$slug}: {$definition['key']} labels");
                $this->assertNotEmpty($definition['data']['values'], "{$slug}: {$definition['key']} values");
            }
        }
    }
}
