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

    public function test_kpi_detail_dashboard_shows_inspection_records_section(): void
    {
        $this->seed(PpmuSeeder::class);
        $slug = 'inspection-of-health-facilities';
        $admin = User::where('username', 'super_admin')->firstOrFail();

        $this->actingAs($admin)
            ->get("/kpi/{$slug}/dashboard")
            ->assertOk()
            ->assertSee('Inspection List')
            ->assertSee('kpiInspectionFilter', false)
            ->assertSee('ppmu-inspection-view-icon', false)
            ->assertSee('bi-eye', false)
            ->assertSee('Pending Review', false);
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
                'review_remarks' => 'Verified on site.',
            ])
            ->assertRedirect(route('kpi.inspections.show', [$card, $inspection]));

        $inspection->refresh();
        $this->assertSame(KpiInspection::STATUS_APPROVED, $inspection->status);
        $this->assertSame($ac->id, $inspection->reviewed_by);

        $rejectTarget = KpiInspection::where('kpi_card_id', $card->id)
            ->where('tehsil_id', $ac->tehsil_id)
            ->where('status', KpiInspection::STATUS_PENDING)
            ->firstOrFail();

        $this->actingAs($ac)
            ->post(route('kpi.inspections.reject', [$card, $rejectTarget]), [
                'rejection_reason' => '',
            ])
            ->assertSessionHasErrors('rejection_reason');

        $this->actingAs($ac)
            ->post(route('kpi.inspections.reject', [$card, $rejectTarget]), [
                'rejection_reason' => 'Incomplete photographic evidence submitted.',
            ])
            ->assertRedirect(route('kpi.inspections.show', [$card, $rejectTarget]));

        $rejectTarget->refresh();
        $this->assertSame(KpiInspection::STATUS_REJECTED, $rejectTarget->status);
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
            $this->assertSame(
                15,
                KpiInspection::where('kpi_card_id', $card->id)->count(),
                "KPI {$card->slug} should have 15 inspections"
            );
        });

        $this->assertSame(345, KpiInspection::count());
    }
}
