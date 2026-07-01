<?php

namespace Tests\Feature;

use App\Data\KpiDashboardDefinitions;
use App\Models\KpiCard;
use App\Services\KpiDashboardConfigService;
use Database\Seeders\PpmuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KpiDashboardConfigTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_kpi_slugs_have_dashboard_configuration(): void
    {
        $this->seed(PpmuSeeder::class);
        $service = app(KpiDashboardConfigService::class);

        foreach (KpiDashboardDefinitions::slugs() as $slug) {
            $config = $service->forKpi($slug);

            $this->assertNotEmpty($config['metrics'], "Missing metrics for {$slug}");
            $this->assertNotEmpty($config['charts'], "Missing charts for {$slug}");
            $this->assertNotEmpty($config['table_columns'], "Missing table columns for {$slug}");
            $this->assertNotEmpty($config['detail_fields'], "Missing detail fields for {$slug}");
        }

        $this->assertSame(23, KpiCard::where('is_active', true)->count());
    }

    public function test_health_facilities_dashboard_shows_kpi_specific_columns(): void
    {
        $this->seed(PpmuSeeder::class);
        $slug = 'inspection-of-health-facilities';

        $this->actingAs(\App\Models\User::where('username', 'super_admin')->firstOrFail())
            ->get("/kpi/{$slug}/dashboard")
            ->assertOk()
            ->assertSee('KPI Performance Cards')
            ->assertSee('Total Facilities')
            ->assertSee('Facilities Inspected')
            ->assertDontSee('ppmu-pi-title">Inspection Records', false)
            ->assertSee('Cleanliness')
            ->assertDontSee('Submission Reports')
            ->assertSee('KPI Detail Dashboard')
            ->assertSee('District Comparison — Inspection Records');
    }

    public function test_health_facilities_uses_visit_header_labels(): void
    {
        $labels = app(KpiDashboardConfigService::class)
            ->headerLabelsFor('inspection-of-health-facilities');

        $this->assertSame('Visit Target', $labels['target']);
        $this->assertSame('Target Completed', $labels['completed']);
    }

    public function test_price_of_roti_uses_two_management_charts(): void
    {
        $charts = KpiDashboardDefinitions::config('price-of-roti')['charts'];

        $this->assertCount(2, $charts);
        $this->assertSame(
            ['daily_inspections_trend', 'violation_type_breakdown'],
            array_column($charts, 'key')
        );
    }

    public function test_short_road_slug_alias_opens_existing_kpi_dashboard(): void
    {
        $this->seed(PpmuSeeder::class);

        $this->actingAs(\App\Models\User::where('username', 'super_admin')->firstOrFail())
            ->get('/kpi/repair-of-small-roads/dashboard')
            ->assertOk()
            ->assertSee('Repair', false);
    }
}
