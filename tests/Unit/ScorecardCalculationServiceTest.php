<?php

namespace Tests\Unit;

use App\Models\District;
use App\Models\KpiScoringParameter;
use App\Services\ScorecardCalculationService;
use PHPUnit\Framework\TestCase;

class ScorecardCalculationServiceTest extends TestCase
{
    private ScorecardCalculationService $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = new ScorecardCalculationService();
    }

    public function test_ratio_formula_is_capped_at_parameter_weightage(): void
    {
        $result = $this->calculator->calculateParameterScore(40, 20, 4, 'resolved_ratio');

        $this->assertSame(100.0, $result['achieved_percentage']);
        $this->assertSame(4.0, $result['score_obtained']);
    }

    public function test_percentage_formula_calculates_weighted_score(): void
    {
        $result = $this->calculator->calculateParameterScore(8, 10, 5, 'percentage');

        $this->assertSame(80.0, $result['achieved_percentage']);
        $this->assertSame(4.0, $result['score_obtained']);
    }

    public function test_resolved_ratio_formula_calculates_weighted_score(): void
    {
        $result = $this->calculator->calculateParameterScore(6, 10, 3, 'resolved_ratio');

        $this->assertSame(60.0, $result['achieved_percentage']);
        $this->assertSame(1.8, $result['score_obtained']);
    }

    public function test_amount_deposit_ratio_formula_calculates_weighted_score(): void
    {
        $result = $this->calculator->calculateParameterScore(7500, 10000, 2, 'amount_deposit_ratio');

        $this->assertSame(75.0, $result['achieved_percentage']);
        $this->assertSame(1.5, $result['score_obtained']);
    }

    public function test_yes_no_formula_awards_full_or_zero_marks(): void
    {
        $yes = $this->calculator->calculateParameterScore(1, 1, 2, 'yes_no');
        $no = $this->calculator->calculateParameterScore(0, 1, 2, 'yes_no');

        $this->assertSame(2.0, $yes['score_obtained']);
        $this->assertSame(0.0, $no['score_obtained']);
    }

    public function test_ratio_formula_prevents_division_by_zero(): void
    {
        $result = $this->calculator->calculateParameterScore(10, 0, 4, 'percentage');

        $this->assertSame(0.0, $result['achieved_percentage']);
        $this->assertSame(0.0, $result['score_obtained']);
    }

    public function test_direct_score_is_capped_at_parameter_weightage(): void
    {
        $result = $this->calculator->calculateParameterScore(7, null, 5, 'direct_score');

        $this->assertSame(5.0, $result['score_obtained']);
    }

    public function test_parameter_uses_target_for_district_tier(): void
    {
        $parameter = new KpiScoringParameter([
            'formula_type' => 'percentage',
            'weightage' => 4,
            'tier_1_target' => 35,
            'tier_2_target' => 28,
            'tier_3_target' => 21,
            'higher_is_better' => true,
        ]);
        $district = new District(['tier' => 1]);

        $result = $this->calculator->calculateForParameter($parameter, $district, 20);

        $this->assertSame(35.0, $result['target_value']);
        $this->assertSame(57.14, $result['achieved_percentage']);
        $this->assertSame(2.29, $result['score_obtained']);
    }

    public function test_ppt_tier_targets_are_selected_for_each_district_tier(): void
    {
        foreach ([
            [10, 8, 6],
            [5, 4, 3],
            [35, 28, 21],
        ] as $targets) {
            foreach ([1, 2, 3] as $tier) {
                $parameter = new KpiScoringParameter([
                    'tier_1_target' => $targets[0],
                    'tier_2_target' => $targets[1],
                    'tier_3_target' => $targets[2],
                ]);

                $resolved = $this->calculator->resolveParameterTarget(
                    $parameter,
                    new District(['tier' => $tier])
                );

                $this->assertSame((float) $targets[$tier - 1], $resolved);
            }
        }
    }

    public function test_dynamic_target_uses_tehsil_count_times_two(): void
    {
        $target = $this->dynamicTarget(
            'weekly-two-visits-of-acs-in-each-tehsil-with-inspection-reports-submitted',
            ['tehsil_count' => 4]
        );

        $this->assertSame(8.0, $target);
    }

    public function test_dynamic_target_uses_tehsil_count_times_six(): void
    {
        $target = $this->dynamicTarget(
            'weekly-six-suthra-punjab-inspections-by-acs-in-each-tehsil',
            ['tehsil_count' => 4]
        );

        $this->assertSame(24.0, $target);
    }

    public function test_dynamic_target_calculates_twenty_five_percent(): void
    {
        $target = $this->dynamicTarget(
            'inspection-of-at-least-25-educational-institutions-for-zebra-crossings',
            ['educational_institutions' => 42]
        );

        $this->assertSame(11.0, $target);
    }

    public function test_dynamic_market_target_uses_tehsil_count_times_working_days(): void
    {
        $target = $this->dynamicTarget(
            'inspection-of-at-least-one-market-per-working-day-in-each-tehsil',
            ['tehsil_count' => 4, 'working_days' => 5]
        );

        $this->assertSame(20.0, $target);
    }

    public function test_dynamic_lpg_target_calculates_twenty_five_percent(): void
    {
        $target = $this->dynamicTarget(
            'inspection-of-at-least-25-sale-points-for-illegal-lpg-decanting',
            ['lpg_sale_points' => 42]
        );

        $this->assertSame(11.0, $target);
    }

    public function test_dynamic_target_calculates_fifteen_percent_action_target(): void
    {
        $target = $this->dynamicTarget(
            'action-taken-on-violations-for-at-least-15-of-inspections',
            ['inspections_count' => 41]
        );

        $this->assertSame(7.0, $target);
    }

    private function dynamicTarget(string $slug, array $context): ?float
    {
        $parameter = new KpiScoringParameter([
            'parameter_slug' => $slug,
            'formula_type' => 'percentage',
            'weightage' => 1,
            'higher_is_better' => true,
        ]);

        return $this->calculator->resolveParameterTarget(
            $parameter,
            new District(['tier' => 1]),
            [],
            $context
        );
    }
}
