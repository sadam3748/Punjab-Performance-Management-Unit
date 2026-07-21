<?php

namespace Tests\Unit;

use App\Data\EducationObservationLabels;
use App\Data\HealthObservationLabels;
use PHPUnit\Framework\TestCase;

class ObservationLabelDefinitionsTest extends TestCase
{
    public function test_education_exposes_ten_distinct_observation_parameters(): void
    {
        $definitions = EducationObservationLabels::definitions();

        $this->assertCount(10, $definitions);
        $this->assertSame(range(1, 10), array_column($definitions, 'evidence'));
        $this->assertCount(10, EducationObservationLabels::chartCategories());
        $this->assertSame([
            'Premises', 'Classrooms', 'Staff', 'Dress/Gown', 'Learning Material',
            'Electricity', 'Drinking Water', 'Toilets', 'Boundary Wall', 'Playground',
        ], array_keys(EducationObservationLabels::chartCategories()));
    }

    public function test_health_exposes_nine_distinct_observation_parameters(): void
    {
        $definitions = HealthObservationLabels::definitions();

        $this->assertCount(9, $definitions);
        $this->assertSame(range(1, 9), array_column($definitions, 'evidence'));
        $this->assertCount(9, HealthObservationLabels::chartCategories());
    }

    public function test_not_applicable_is_not_classified_as_positive_or_negative(): void
    {
        $this->assertSame('not_applicable', EducationObservationLabels::outcome('not_applicable'));
        $this->assertSame('not_applicable', HealthObservationLabels::outcome('N/A'));
        $this->assertSame('not_recorded', EducationObservationLabels::outcome(null));
        $this->assertSame('Not Recorded', HealthObservationLabels::displayValue('medicines_available', null));
    }
}
