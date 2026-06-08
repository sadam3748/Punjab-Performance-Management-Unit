<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\Division;
use App\Models\KpiCategory;
use App\Models\KpiScoringParameter;
use App\Models\Role;
use App\Models\User;
use App\Services\ScorecardCalculationService;
use App\Services\ScorecardService;
use App\Services\ScorecardSubmissionService;
use Database\Seeders\KpiCategorySeeder;
use Database\Seeders\KpiScoringParameterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScorecardSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_submission_calculates_parent_kpi_from_child_scores(): void
    {
        $division = Division::create(['name' => 'Test Division', 'code' => 'TD', 'is_active' => true]);
        $district = District::create([
            'division_id' => $division->id,
            'name' => 'Test District',
            'code' => 'TST',
            'tier' => 1,
            'is_active' => true,
        ]);
        $category = KpiCategory::create([
            'name' => 'Test KPI',
            'slug' => 'test-kpi',
            'scorecard_weightage' => 10,
            'is_active' => true,
        ]);

        $first = $this->parameter($category, 'first', 4);
        $second = $this->parameter($category, 'second', 6);

        $score = app(ScorecardSubmissionService::class)->submit($district, $category, [
            'week_no' => '202623',
            'calculation_type' => 'general',
            'details' => [
                ['kpi_scoring_parameter_id' => $first->id, 'numerator' => 5, 'denominator' => 10],
                ['kpi_scoring_parameter_id' => $second->id, 'numerator' => 10, 'denominator' => 10],
            ],
        ]);

        $this->assertSame(2, $score->details()->count());
        $this->assertSame(8.0, (float) $score->details()->sum('score_obtained'));
        $this->assertSame(80.0, (float) $score->reported_score);
        $this->assertSame(80.0, (float) $score->final_score);

        $role = Role::create(['name' => 'Super Admin', 'slug' => 'super_admin', 'scope_level' => 'province', 'is_active' => true]);
        $user = User::create([
            'role_id' => $role->id,
            'name' => 'Scorecard Tester',
            'username' => 'scorecard.tester',
            'email' => 'scorecard.tester@example.test',
            'password' => 'password',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('scorecard.district-detail', ['district' => $district, 'week_no' => '202623']))
            ->assertOk()
            ->assertSee('View sub-KPI formula detail')
            ->assertSee('How this score is calculated')
            ->assertDontSee('Current Week Sub-KPI Formula Detail');

        $this->actingAs($user)
            ->get(route('scorecard.district.kpi-details', [
                'district' => $district,
                'kpiCategory' => $category,
                'week_no' => '202623',
                'calculation_type' => 'general',
            ]))
            ->assertOk()
            ->assertSee('Sub-KPI Formula Calculation Detail')
            ->assertSee('How this KPI score is calculated')
            ->assertSee('sub-kpi-detail-table')
            ->assertSee('First')
            ->assertSee('Second');
    }

    public function test_requested_ppt_kpis_recalculate_children_parents_and_district_weighted_score(): void
    {
        $this->seed([KpiCategorySeeder::class, KpiScoringParameterSeeder::class]);

        $division = Division::create(['name' => 'PPT Division', 'code' => 'PPTD', 'is_active' => true]);
        $district = District::create([
            'division_id' => $division->id,
            'name' => 'PPT District',
            'code' => 'PPT',
            'tier' => 1,
            'is_active' => true,
        ]);
        $context = [
            'tehsil_count' => 4,
            'working_days' => 5,
            'educational_institutions' => 40,
            'lpg_sale_points' => 40,
            'inspections_count' => 40,
        ];
        $categories = KpiCategory::query()->whereIn('slug', [
            'price-of-roti',
            'price-control-of-essential-commodities',
            'inspection-of-educational-institutions',
            'suthra-punjab-campaign',
            'chief-ministers-complaint-cell',
        ])->get();

        foreach ($categories as $category) {
            $details = $category->scoringParameters()->where('is_active', true)->get()
                ->map(function (KpiScoringParameter $parameter) use ($district, $context) {
                    $target = app(ScorecardCalculationService::class)
                        ->resolveParameterTarget($parameter, $district, [], $context);

                    return [
                        'kpi_scoring_parameter_id' => $parameter->id,
                        'numerator' => $parameter->formula_type === 'yes_no' ? 1 : ($target ?? 10),
                        'denominator' => $target === null && $parameter->formula_type !== 'yes_no' ? 10 : null,
                    ];
                })->all();

            $score = app(ScorecardSubmissionService::class)->submit($district, $category, [
                'week_no' => '202623',
                'calculation_type' => 'general',
                'context' => $context,
                'details' => $details,
            ]);

            $this->assertSame($category->scoringParameters()->where('is_active', true)->count(), $score->details()->count());
            $this->assertSame(100.0, (float) $score->reported_score);
            $this->assertSame(100.0, (float) $score->final_score);
            $this->assertFalse($score->details->contains(
                fn ($detail) => (float) $detail->score_obtained > (float) $detail->weightage
            ));
        }

        $roti = $categories->firstWhere('slug', 'price-of-roti');
        $subDetail = app(ScorecardService::class)->getDistrictKpiSubDetail($district, $roti, [
            'period_type' => 'weekly',
            'week_no' => '202623',
            'year' => 2026,
            'calculation_type' => 'general',
        ]);

        $this->assertSame($roti->scoringParameters()->where('is_active', true)->count(), $subDetail['details']->total());
        $this->assertSame((float) $roti->scorecard_weightage, (float) $subDetail['summary']['marks_obtained']);
        $this->assertSame(100.0, (float) $subDetail['summary']['score_percentage']);

        $ranking = app(ScorecardService::class)->getDistrictRanking([
            'period' => 'weekly',
            'week_range' => '202623',
            'year' => 2026,
            'calculation_type' => 'general',
            'per_page' => 100,
        ]);
        $row = $ranking->getCollection()->firstWhere('district_id', $district->id);

        $this->assertNotNull($row);
        $this->assertSame(33.0, (float) $row->weighted_score_sum);
        $this->assertSame(5, (int) $row->reported_kpis);
    }

    private function parameter(KpiCategory $category, string $slug, float $weight): KpiScoringParameter
    {
        return KpiScoringParameter::create([
            'kpi_category_id' => $category->id,
            'parameter_name' => ucfirst($slug),
            'parameter_slug' => $slug,
            'weightage' => $weight,
            'formula_type' => 'percentage',
            'scoring_method' => 'percentage',
            'higher_is_better' => true,
            'display_order' => 1,
            'sort_order' => 1,
            'is_active' => true,
        ]);
    }
}
