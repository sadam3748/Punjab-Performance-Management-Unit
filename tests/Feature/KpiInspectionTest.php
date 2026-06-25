<?php

namespace Tests\Feature;

use App\Models\KpiCard;
use App\Models\KpiInspection;
use App\Models\User;
use Database\Seeders\PpmuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KpiInspectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_inspections_sidebar_and_index_page(): void
    {
        $this->seed(PpmuSeeder::class);
        $admin = User::where('username', 'super_admin')->firstOrFail();
        $healthCard = KpiCard::where('slug', 'inspection-of-health-facilities')->firstOrFail();

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Inspections', false);

        $this->actingAs($admin)
            ->get(route('inspections.index'))
            ->assertRedirect();

        $this->actingAs($admin)
            ->get(route('inspections.index', [
                'kpi_card_id' => $healthCard->id,
                'period_type' => 'weekly',
                'week_no' => app(\App\Services\KpiPeriodService::class)->currentWeekNo(),
                'month' => (string) now()->month,
                'year' => (string) now()->year,
                'insp_per_page' => 25,
            ]))
            ->assertOk()
            ->assertSee('Inspection List')
            ->assertSee('ppmu-inspection-view-icon', false)
            ->assertSee('Inspection of Health Facilities', false);
    }

    public function test_inspections_index_defaults_redirect_and_ajax_pagination(): void
    {
        $this->seed(PpmuSeeder::class);
        $admin = User::where('username', 'super_admin')->firstOrFail();
        $healthCard = KpiCard::where('slug', 'inspection-of-health-facilities')->firstOrFail();
        $periodService = app(\App\Services\KpiPeriodService::class);

        $this->actingAs($admin)
            ->followingRedirects()
            ->get(route('inspections.index'))
            ->assertOk()
            ->assertSee('Inspection of Health Facilities', false)
            ->assertSee('Weekly', false);

        $response = $this->actingAs($admin)->getJson(route('inspections.data', [
            'kpi_card_id' => $healthCard->id,
            'period_type' => 'weekly',
            'week_no' => $periodService->currentWeekNo(),
            'month' => (string) now()->month,
            'year' => (string) now()->year,
            'insp_per_page' => 10,
            'insp_page' => 2,
        ]));

        $response->assertOk()
            ->assertJsonStructure(['table_html', 'total', 'from', 'to', 'period_description']);

        $this->assertGreaterThan(10, (int) $response->json('total'));

        $this->assertStringContainsString('pagination', $response->json('table_html'));
        $this->assertStringContainsString('ppmu-inspection-pagination', $response->json('table_html'));
    }

    public function test_kpi_detail_dashboard_does_not_duplicate_inspections_module_link(): void
    {
        $this->seed(PpmuSeeder::class);
        $slug = 'inspection-of-health-facilities';
        $admin = User::where('username', 'super_admin')->firstOrFail();

        $this->actingAs($admin)
            ->get("/kpi/{$slug}/dashboard")
            ->assertOk()
            ->assertDontSee('View Inspections')
            ->assertDontSee('ppmu-inspections-link-section', false);
    }

    public function test_inspection_detail_page_and_approve_reject_workflow(): void
    {
        $this->seed(PpmuSeeder::class);
        $card = KpiCard::where('slug', 'price-of-roti')->firstOrFail();
        $ac = User::where('username', 'ac.lahore')->firstOrFail();
        $inspection = KpiInspection::where('kpi_card_id', $card->id)
            ->where('tehsil_id', $ac->tehsil_id)
            ->where('status', KpiInspection::STATUS_PENDING)
            ->firstOrFail();

        $this->actingAs($ac)
            ->get(route('kpi.inspections.show', [$card, $inspection]))
            ->assertOk()
            ->assertSee($inspection->reference_no)
            ->assertSee('Evidence Images')
            ->assertSee('Map Location')
            ->assertSee('Approve Inspection')
            ->assertSee('Open in Maps');

        $this->actingAs($ac)
            ->post(route('kpi.inspections.approve', [$card, $inspection]), [
                'remarks' => 'Verified on site.',
            ])
            ->assertRedirect(route('kpi.inspections.show', [$card, $inspection]));

        $inspection->refresh();
        $this->assertSame(KpiInspection::STATUS_APPROVED, $inspection->status);
        $this->assertSame($ac->id, $inspection->reviewed_by);
        $this->assertSame('Verified on site.', $inspection->review_remarks);

        $rejectTarget = KpiInspection::where('kpi_card_id', $card->id)
            ->where('tehsil_id', $ac->tehsil_id)
            ->where('status', KpiInspection::STATUS_PENDING)
            ->firstOrFail();

        $this->actingAs($ac)
            ->post(route('kpi.inspections.reject', [$card, $rejectTarget]), [
                'remarks' => '',
            ])
            ->assertSessionHasErrors('rejection_reason');

        $this->actingAs($ac)
            ->post(route('kpi.inspections.reject', [$card, $rejectTarget]), [
                'remarks' => 'Incomplete photographic evidence submitted.',
            ])
            ->assertRedirect(route('kpi.inspections.show', [$card, $rejectTarget]));

        $rejectTarget->refresh();
        $this->assertSame(KpiInspection::STATUS_REJECTED, $rejectTarget->status);
        $this->assertSame('Incomplete photographic evidence submitted.', $rejectTarget->rejection_reason);
    }

    public function test_ac_scope_limits_inspections_to_tehsil(): void
    {
        $this->seed(PpmuSeeder::class);
        $card = KpiCard::where('slug', 'inspection-of-health-facilities')->firstOrFail();
        $acLahore = User::where('username', 'ac.lahore')->firstOrFail();
        $acLayyah = User::where('username', 'ac.layyah')->firstOrFail();

        $lahoreInspection = KpiInspection::where('kpi_card_id', $card->id)
            ->where('tehsil_id', $acLahore->tehsil_id)
            ->firstOrFail();

        $this->actingAs($acLahore)
            ->get(route('kpi.inspections.show', [$card, $lahoreInspection]))
            ->assertOk();

        $this->actingAs($acLayyah)
            ->get(route('kpi.inspections.show', [$card, $lahoreInspection]))
            ->assertForbidden();
    }

    public function test_each_kpi_has_seeded_inspection_records(): void
    {
        $this->seed(PpmuSeeder::class);

        KpiCard::where('is_active', true)->each(function (KpiCard $card) {
            $expected = match ($card->slug) {
                'inspection-of-educational-institutions', 'inspection-of-health-facilities' => 45,
                'price-of-roti',
                'functional-and-clean-water-filtration-plants',
                'chief-ministers-complaint-cell',
                'e-biz' => 32,
                default => 15,
            };

            $this->assertSame(
                $expected,
                KpiInspection::where('kpi_card_id', $card->id)->count(),
                "KPI {$card->slug} should have {$expected} inspections"
            );
        });

        $this->assertSame(473, KpiInspection::count());

        $total = KpiInspection::count();
        $this->assertEqualsWithDelta(.60, KpiInspection::where('status', 'approved')->count() / $total, .03);
        $this->assertEqualsWithDelta(.25, KpiInspection::where('status', 'pending_review')->count() / $total, .03);
        $this->assertEqualsWithDelta(.15, KpiInspection::where('status', 'rejected')->count() / $total, .03);
    }
}
