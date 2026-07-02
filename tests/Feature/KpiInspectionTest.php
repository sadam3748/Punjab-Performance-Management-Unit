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
            ->assertOk()
            ->assertSee('Inspection List')
            ->assertSee('Review Status')
            ->assertSee('Field inspections with status and quick view access.')
            ->assertSee('Action')
            ->assertSee('Last completed day:')
            ->assertSee(
                app(\App\Services\KpiInspectionService::class)->completedDayDateRange()['start']->format('d M Y')
            )
            ->assertSee('12:00 AM')
            ->assertSee('11:59 PM')
            ->assertDontSee('(Asia/Karachi)')
            ->assertDontSee('Main Dashboard')
            ->assertDontSee('Weekly')
            ->assertDontSee('Monthly')
            ->assertDontSee('Yearly')
            ->assertDontSee('>Apply<', false)
            ->assertDontSee('ppmu-inspection-meta', false)
            ->assertSee('ppmu-inspections.js', false)
            ->assertSee('ppmu-inspection-view-icon', false)
            ->assertSee('Inspection of Health Facilities', false);
    }

    public function test_inspections_index_defaults_redirect_and_ajax_pagination(): void
    {
        $this->seed(PpmuSeeder::class);
        $admin = User::where('username', 'super_admin')->firstOrFail();
        $healthCard = KpiCard::where('slug', 'inspection-of-health-facilities')->firstOrFail();
        $this->actingAs($admin)
            ->get(route('inspections.index'))
            ->assertOk()
            ->assertSee('Inspection of Health Facilities', false)
            ->assertViewHas('inspectionRecords', fn ($records) => $records->perPage() === 10);

        $response = $this->actingAs($admin)->getJson(route('inspections.data', [
            'kpi_card_id' => $healthCard->id,
            'insp_per_page' => 10,
            'insp_page' => 2,
        ]));

        $response->assertOk()
            ->assertJsonStructure(['table_html', 'total', 'from', 'to']);

        $this->assertGreaterThan(10, (int) $response->json('total'));

        $this->assertStringContainsString('pagination', $response->json('table_html'));
        $this->assertStringContainsString('ppmu-inspection-pagination', $response->json('table_html'));
    }

    public function test_inspection_list_uses_last_completed_day_range_and_keeps_role_scope(): void
    {
        $this->seed(PpmuSeeder::class);
        $card = KpiCard::where('slug', 'inspection-of-health-facilities')->firstOrFail();
        $admin = User::where('username', 'super_admin')->firstOrFail();
        $acLahore = User::where('username', 'ac.lahore')->firstOrFail();
        $acLayyah = User::where('username', 'ac.layyah')->firstOrFail();

        $range = app(\App\Services\KpiInspectionService::class)->completedDayDatabaseRange();
        KpiInspection::where('kpi_card_id', $card->id)
            ->update(['inspection_datetime' => $range['start']->copy()->subDay()]);

        $lahoreInspection = KpiInspection::where('kpi_card_id', $card->id)
            ->where('tehsil_id', $acLahore->tehsil_id)
            ->firstOrFail();
        $layyahInspection = KpiInspection::where('kpi_card_id', $card->id)
            ->where('tehsil_id', $acLayyah->tehsil_id)
            ->firstOrFail();
        $olderInspection = KpiInspection::where('kpi_card_id', $card->id)
            ->whereNotIn('id', [$lahoreInspection->id, $layyahInspection->id])
            ->firstOrFail();
        $currentInspection = KpiInspection::where('kpi_card_id', $card->id)
            ->whereNotIn('id', [$lahoreInspection->id, $layyahInspection->id, $olderInspection->id])
            ->firstOrFail();

        $lahoreInspection->update(['inspection_datetime' => $range['start']->copy()]);
        $layyahInspection->update(['inspection_datetime' => $range['end']->copy()]);
        $currentInspection->update(['inspection_datetime' => now()]);

        $this->actingAs($admin)
            ->get(route('inspections.index'))
            ->assertOk()
            ->assertSee($lahoreInspection->reference_no)
            ->assertSee($layyahInspection->reference_no)
            ->assertDontSee($olderInspection->reference_no)
            ->assertDontSee($currentInspection->reference_no)
            ->assertViewHas('inspectionRecords', fn ($records) => $records->total() === 2);

        $this->actingAs($acLahore)
            ->get(route('inspections.index'))
            ->assertOk()
            ->assertSee($lahoreInspection->reference_no)
            ->assertDontSee($layyahInspection->reference_no);
    }

    public function test_health_seeder_always_provides_multiple_completed_day_inspections_for_live_scopes(): void
    {
        $this->seed(PpmuSeeder::class);
        $card = KpiCard::where('slug', 'inspection-of-health-facilities')->firstOrFail();
        $service = app(\App\Services\KpiInspectionService::class);
        $request = \Illuminate\Http\Request::create('/inspections', 'GET', [
            'kpi_card_id' => $card->id,
            'insp_per_page' => 10,
        ]);

        $range = $service->completedDayDatabaseRange();
        $completedDayCount = KpiInspection::where('kpi_card_id', $card->id)
            ->whereBetween('inspection_datetime', [$range['start'], $range['end']])
            ->count();

        $this->assertGreaterThanOrEqual(15, $completedDayCount);

        foreach (['ac.lahore', 'ac.layyah', 'ac.karor', 'dc.layyah', 'com.dgkhan', 'cs.pmru'] as $username) {
            $user = User::where('username', $username)->firstOrFail()->loadMissing('role');
            $this->assertGreaterThan(
                1,
                $service->getAllInspectionsList($user, $request)->total(),
                "{$username} should have multiple Health inspections for the last completed day."
            );
        }
    }

    public function test_health_inspection_detail_shows_observation_values_and_evidence_actions(): void
    {
        $this->seed(PpmuSeeder::class);
        $card = KpiCard::where('slug', 'inspection-of-health-facilities')->firstOrFail();
        $admin = User::where('username', 'super_admin')->firstOrFail();
        $inspection = KpiInspection::where('kpi_card_id', $card->id)
            ->where('status', KpiInspection::STATUS_PENDING)
            ->whereHas('attachments')
            ->firstOrFail();
        $returnUrl = route('inspections.index', [
            'kpi_card_id' => $card->id,
            'insp_status' => KpiInspection::STATUS_PENDING,
            'insp_page' => 2,
        ]);

        $this->actingAs($admin)
            ->get(route('kpi.inspections.show', [$card, $inspection, 'return_url' => $returnUrl]))
            ->assertOk()
            ->assertSee($returnUrl)
            ->assertSee('Deep Cleaning')
            ->assertSee('Staff Availability')
            ->assertSee('Medicine Flex')
            ->assertSee('Testing Equipment')
            ->assertSee('Drinking Water')
            ->assertSee('Utilities')
            ->assertSee('UHI Compliance')
            ->assertSee('Overall Attention')
            ->assertSee('View Evidence')
            ->assertSee('id="ppmuObservationEvidenceModal"', false)
            ->assertSee('Evidence Images')
            ->assertSee('Field evidence photo', false)
            ->assertDontSee('Deep Cleaning evidence', false)
            ->assertDontSee('Social Sector')
            ->assertDontSee('ppmu-inspection-summary-grid', false);
    }

    public function test_health_inspection_detail_observation_evidence_mapping(): void
    {
        $this->seed(PpmuSeeder::class);
        $card = KpiCard::where('slug', 'inspection-of-health-facilities')->firstOrFail();
        $admin = User::where('username', 'super_admin')->firstOrFail();
        $inspection = KpiInspection::where('kpi_card_id', $card->id)
            ->whereHas('attachments', fn ($q) => $q->where('observation_key', 'deep_cleaning'))
            ->firstOrFail();

        $this->actingAs($admin)
            ->get(route('kpi.inspections.show', [$card, $inspection]))
            ->assertOk()
            ->assertSee('id="ppmuObservationEvidenceModal"', false)
            ->assertSee('data-observation-key="deep_cleaning"', false)
            ->assertSee('data-evidence-url', false);
    }

    public function test_health_inspection_review_uses_single_remarks_box(): void
    {
        $this->seed(PpmuSeeder::class);
        $card = KpiCard::where('slug', 'inspection-of-health-facilities')->firstOrFail();
        $admin = User::where('username', 'super_admin')->firstOrFail();
        $inspection = KpiInspection::where('kpi_card_id', $card->id)
            ->where('status', KpiInspection::STATUS_PENDING)
            ->firstOrFail();

        $this->actingAs($admin)
            ->get(route('kpi.inspections.show', [$card, $inspection]))
            ->assertOk()
            ->assertSee('id="review-remarks"', false)
            ->assertSee('Approve Inspection')
            ->assertSee('Reject Inspection');

        $this->actingAs($admin)
            ->post(route('kpi.inspections.approve', [$card, $inspection]), [
                'review_remarks' => 'Health inspection verified.',
            ])
            ->assertRedirect(route('kpi.inspections.show', [$card, $inspection]));

        $inspection->refresh();
        $this->assertSame(KpiInspection::STATUS_APPROVED, $inspection->status);
        $this->assertSame('Health inspection verified.', $inspection->review_remarks);
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
            ->assertSee('</i> Back', false)
            ->assertDontSee('Back to KPI Dashboard')
            ->assertDontSee('Social Sector')
            ->assertDontSee('ppmu-inspection-summary-grid', false)
            ->assertSee('Observations')
            ->assertDontSee('KPI-Specific Details')
            ->assertDontSee('Actions Taken / Required')
            ->assertSee('Evidence Images')
            ->assertSee('Map Location')
            ->assertSee('Remarks')
            ->assertSee('Approve Inspection')
            ->assertSee('Reject Inspection')
            ->assertDontSee('Submit Approval')
            ->assertDontSee('Submit Rejection')
            ->assertDontSee('Approval Remarks')
            ->assertDontSee('Rejection Remarks')
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
            ->assertRedirect(route('kpi.inspections.show', [$card, $rejectTarget]));

        $rejectTarget->refresh();
        $this->assertSame(KpiInspection::STATUS_REJECTED, $rejectTarget->status);
        $this->assertNull($rejectTarget->review_remarks);
        $this->assertNull($rejectTarget->rejection_reason);

        $rejectWithRemarks = KpiInspection::where('kpi_card_id', $card->id)
            ->where('tehsil_id', $ac->tehsil_id)
            ->where('status', KpiInspection::STATUS_PENDING)
            ->firstOrFail();

        $this->actingAs($ac)
            ->post(route('kpi.inspections.reject', [$card, $rejectWithRemarks]), [
                'remarks' => 'Incomplete photographic evidence submitted.',
            ])
            ->assertRedirect(route('kpi.inspections.show', [$card, $rejectWithRemarks]));

        $rejectWithRemarks->refresh();
        $this->assertSame(KpiInspection::STATUS_REJECTED, $rejectWithRemarks->status);
        $this->assertSame('Incomplete photographic evidence submitted.', $rejectWithRemarks->review_remarks);
        $this->assertSame('Incomplete photographic evidence submitted.', $rejectWithRemarks->rejection_reason);
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
                'inspection-of-health-facilities' => 60,
                'inspection-of-educational-institutions' => 58,
                'price-of-roti' => 36,
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

        $this->assertSame(505, KpiInspection::count());

        $total = KpiInspection::count();
        $this->assertEqualsWithDelta(.60, KpiInspection::where('status', 'approved')->count() / $total, .03);
        $this->assertEqualsWithDelta(.25, KpiInspection::where('status', 'pending_review')->count() / $total, .03);
        $this->assertEqualsWithDelta(.15, KpiInspection::where('status', 'rejected')->count() / $total, .03);
    }
}
