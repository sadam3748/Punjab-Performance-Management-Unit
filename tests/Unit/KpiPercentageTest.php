<?php

namespace Tests\Unit;

use App\Services\KpiFormulaService;
use App\Services\PpmuDemoMetricFactory;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class KpiPercentageTest extends TestCase
{
    public function test_percentage_display_is_always_between_zero_and_one_hundred(): void
    {
        $formula = new KpiFormulaService;

        $this->assertSame(100.0, $formula->percentage(150, 100));
        $this->assertSame(0.0, $formula->percentage(50, 0));
        $this->assertSame(0.0, $formula->displayPercentage(-12));
        $this->assertSame(100.0, $formula->displayPercentage(171.4));
    }

    public function test_roti_demo_snapshot_respects_targets_and_ratio_constraints(): void
    {
        $factory = new PpmuDemoMetricFactory;
        $snapshot = $factory->build(
            'price-of-roti',
            Carbon::parse('2026-06-25'),
            'ac.lahore',
            0,
            'daily'
        )['snapshot'];

        $this->assertLessThanOrEqual($snapshot['inspections_total_target'], $snapshot['tandoor_inspections']);
        $this->assertLessThanOrEqual($snapshot['citizen_complaints_received'], $snapshot['complaints_resolved']);
        $this->assertLessThanOrEqual($snapshot['fine_generated'], $snapshot['fine_deposited']);
        $this->assertLessThanOrEqual($snapshot['validation_target'], $snapshot['validated_inspections']);
        $this->assertLessThanOrEqual(
            $snapshot['validated_inspections'],
            $snapshot['approved_validations'] + $snapshot['rejected_validations']
        );

        foreach ([
            'achievement_rate',
            'fine_imposition_rate',
            'complaint_resolution_rate',
            'validation_rate',
        ] as $field) {
            $this->assertGreaterThanOrEqual(0, $snapshot[$field]);
            $this->assertLessThanOrEqual(100, $snapshot[$field]);
        }
    }

    public function test_demo_snapshots_keep_complaint_fine_and_validation_counts_realistic(): void
    {
        $factory = new PpmuDemoMetricFactory;

        foreach ([
            'price-of-roti',
            'inspection-of-educational-institutions',
            'inspection-of-health-facilities',
            'chief-ministers-complaint-cell',
        ] as $slug) {
            $snapshot = $factory->build(
                $slug,
                Carbon::parse('2026-06-25'),
                'ac.layyah',
                3,
                'weekly'
            )['snapshot'];

            $complaints = $snapshot['complaints_received']
                ?? $snapshot['citizen_complaints_received']
                ?? null;
            if ($complaints !== null && isset($snapshot['complaints_resolved'])) {
                $this->assertLessThanOrEqual($complaints, $snapshot['complaints_resolved'], $slug);
            }

            if (isset($snapshot['fine_generated'], $snapshot['fine_deposited'])) {
                $this->assertLessThanOrEqual($snapshot['fine_generated'], $snapshot['fine_deposited'], $slug);
            }

            $validated = $snapshot['validated_inspections']
                ?? $snapshot['validations_completed']
                ?? null;
            if (isset($snapshot['validation_target']) && $validated !== null) {
                $this->assertLessThanOrEqual($snapshot['validation_target'], $validated, $slug);

                $approved = (int) ($snapshot['approved_validations'] ?? 0);
                $rejected = (int) ($snapshot['rejected_validations'] ?? 0);
                $this->assertLessThanOrEqual($validated, $approved + $rejected, $slug);
            }
        }
    }
}
