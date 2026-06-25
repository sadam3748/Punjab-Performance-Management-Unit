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
                'inspection-of-educational-institutions', 'inspection-of-health-facilities' => 46,
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

        $this->assertSame(475, KpiInspection::count());

        $total = KpiInspection::count();
        $this->assertEqualsWithDelta(.60, KpiInspection::where('status', 'approved')->count() / $total, .03);
        $this->assertEqualsWithDelta(.25, KpiInspection::where('status', 'pending_review')->count() / $total, .03);
        $this->assertEqualsWithDelta(.15, KpiInspection::where('status', 'rejected')->count() / $total, .03);
    }
}
