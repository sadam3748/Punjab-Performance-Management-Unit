<?php

namespace App\Data;

class KpiMetricSections
{
    /**
     * @return list<array{title: string, metrics: list<array{field: string, label: string}>}>
     */
    public static function for(string $slug, ?string $role = null): array
    {
        $slug = KpiDashboardDefinitions::normalizeSlug($slug);

        return match ($slug) {
            'inspection-of-health-facilities' => self::healthSections($role),
            'inspection-of-educational-institutions' => self::educationSections($role),
            'price-of-roti' => self::rotiSections($role),
            default => KpiMetricSectionDefinitions::for($slug, $role),
        };
    }

    /**
     * @return list<array{title: string, metrics: list<array{field: string, label: string}>}>
     */
    private static function healthSections(?string $role): array
    {
        $coverage = [
            ['field' => 'total_health_facilities', 'label' => 'Total Health Facilities'],
            ['field' => 'facilities_inspected', 'label' => 'Facilities Inspected'],
            ['field' => 'review_target', 'label' => 'Review Target'],
            ['field' => 'inspections_pending', 'label' => 'Pending Review'],
            ['field' => 'inspections_approved', 'label' => 'Approved'],
            ['field' => 'inspections_rejected', 'label' => 'Rejected'],
        ];

        $sections = [
            ['title' => 'Inspection Coverage', 'metrics' => $coverage],
        ];

        if (! in_array($role, ['ac', 'field_user'], true)) {
            $visits = match ($role) {
                'dc' => [
                    ['field' => 'ac_visits', 'label' => 'ACs Visits'],
                    ['field' => 'dc_own_inspections', 'label' => 'DC Visits'],
                    ['field' => 'health_council_meeting', 'label' => 'Council Meetings'],
                ],
                'commissioner' => [
                    ['field' => 'ac_visits', 'label' => 'ACs Visits'],
                    ['field' => 'dc_own_inspections', 'label' => 'DC Visits'],
                    ['field' => 'health_council_meeting', 'label' => 'Council Meetings'],
                ],
                'chief_secretary', 'super_admin', 'pmru_user', 'viewer' => [
                    ['field' => 'ac_visits', 'label' => 'ACs Visits'],
                    ['field' => 'dc_own_inspections', 'label' => 'DC Visits'],
                    ['field' => 'health_council_meeting', 'label' => 'Council Meetings'],
                ],
                default => [
                    ['field' => 'ac_visits', 'label' => 'AC Inspections'],
                    ['field' => 'dc_own_inspections', 'label' => 'DC Inspections'],
                    ['field' => 'health_council_meeting', 'label' => 'Health Council Meeting'],
                ],
            };

            $sections[] = ['title' => 'Visits & Meetings', 'metrics' => $visits];
        }

        // Tests and dashboard spec expect this section to be labeled "Observations".
        $sections[] = ['title' => 'Observations', 'metrics' => collect(HealthObservationLabels::definitions())
            ->map(fn (array $definition, string $field) => ['field' => $field, 'label' => $definition['title']])->values()->all()];

        return $sections;
    }

    /**
     * @return list<array{title: string, metrics: list<array{field: string, label: string}>}>
     */
    private static function educationSections(?string $role): array
    {
        $coverage = [
            ['field' => 'total_institutions', 'label' => 'Total Educational Institutions'],
            ['field' => 'institutions_inspected', 'label' => 'Institutions Inspected'],
            ['field' => 'review_target', 'label' => 'Review Target'],
            ['field' => 'inspections_pending', 'label' => 'Pending Review'],
            ['field' => 'inspections_approved', 'label' => 'Approved'],
            ['field' => 'inspections_rejected', 'label' => 'Rejected'],
        ];

        $sections = [
            ['title' => 'Inspection Coverage', 'metrics' => $coverage],
        ];

        if (! in_array($role, ['ac', 'field_user'], true)) {
            $visits = match ($role) {
                'dc' => [
                    ['field' => 'ac_visits', 'label' => 'ACs Visits'],
                    ['field' => 'dc_own_inspections', 'label' => 'DC Visits'],
                    ['field' => 'school_council_meeting', 'label' => 'Council Meetings'],
                ],
                'commissioner' => [
                    ['field' => 'ac_visits', 'label' => 'ACs Visits'],
                    ['field' => 'dc_own_inspections', 'label' => 'DC Visits'],
                    ['field' => 'school_council_meeting', 'label' => 'Council Meetings'],
                ],
                'chief_secretary', 'super_admin', 'pmru_user', 'viewer' => [
                    ['field' => 'ac_visits', 'label' => 'ACs Visits'],
                    ['field' => 'dc_own_inspections', 'label' => 'DC Visits'],
                    ['field' => 'school_council_meeting', 'label' => 'Council Meetings'],
                ],
                default => [
                    ['field' => 'ac_visits', 'label' => 'AC Inspections'],
                    ['field' => 'dc_own_inspections', 'label' => 'DC Inspections'],
                    ['field' => 'school_council_meeting', 'label' => 'School Council Meeting'],
                ],
            };

            $sections[] = ['title' => 'Visits & Meetings', 'metrics' => $visits];
        }

        $sections[] = ['title' => 'School Observation Findings', 'metrics' => collect(EducationObservationLabels::definitions())
            ->map(fn (array $definition, string $field) => ['field' => $field, 'label' => $definition['title']])->values()->all()];

        return $sections;
    }

    /**
     * @return list<array{title: string, metrics: list<array{field: string, label: string}>}>
     */
    private static function rotiSections(?string $role): array
    {
        return [
            ['title' => 'Operational Performance', 'metrics' => [
                ['field' => 'inspections_total_target', 'label' => 'Daily Inspection Target'],
                ['field' => 'tandoor_inspections', 'label' => 'Tandoors Inspected'],
                ['field' => 'operational_remaining', 'label' => 'Inspections Remaining'],
                ['field' => 'achievement_rate', 'label' => 'Target Achievement %'],
            ]],
            ['title' => 'Compliance Findings', 'metrics' => [
                ['field' => 'violating_entities', 'label' => 'Violating Tandoors'],
                ['field' => 'obs_over_price', 'label' => 'Overpricing Cases'],
                ['field' => 'obs_under_weight', 'label' => 'Underweight Roti Cases'],
                ['field' => 'obs_non_availability', 'label' => 'Roti Unavailable Cases'],
            ]],
            ['title' => 'Complaint Outcome', 'metrics' => [
                ['field' => 'complaints_received', 'label' => 'Complaints Received'],
                ['field' => 'complaints_resolved', 'label' => 'Complaints Resolved'],
                ['field' => 'complaint_resolution_rate', 'label' => 'Complaint Resolution %'],
            ]],
            ['title' => 'Review and Validation', 'metrics' => [
                ['field' => 'review_target', 'label' => 'Review Target'],
                ['field' => 'reviewed', 'label' => 'Reviewed'],
                ['field' => 'inspections_pending', 'label' => 'Pending Review'],
                ['field' => 'inspections_approved', 'label' => 'Approved'],
                ['field' => 'inspections_rejected', 'label' => 'Rejected'],
                ['field' => 'inspected_only', 'label' => 'Inspected Only'],
                ['field' => 'review_target_balance', 'label' => 'Reviews Remaining'],
                ['field' => 'review_completion_rate', 'label' => 'Review Target Met %'],
            ]],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $configuredMetrics
     * @return list<array{title: string, metrics: list<array<string, mixed>>}>
     */
    public static function groupGeneric(array $configuredMetrics): array
    {
        $buckets = [
            'Coverage / Target' => [],
            'Compliance / Enforcement' => [],
            'Issues / Observations' => [],
            'Validation' => [],
        ];

        foreach ($configuredMetrics as $metric) {
            $label = strtolower((string) ($metric['label'] ?? ''));
            $field = (string) ($metric['field'] ?? '');

            if (str_contains($label, 'validation') || str_contains($field, 'validation')) {
                $buckets['Validation'][] = $metric;
            } elseif (str_contains($label, 'issue') || str_contains($label, 'violation') || str_contains($label, 'deficien')) {
                $buckets['Issues / Observations'][] = $metric;
            } elseif (str_contains($label, 'rate') || str_contains($label, 'fine') || str_contains($label, 'compliance') || str_contains($label, 'enforcement')) {
                $buckets['Compliance / Enforcement'][] = $metric;
            } else {
                $buckets['Coverage / Target'][] = $metric;
            }
        }

        return collect($buckets)
            ->filter(fn (array $items) => $items !== [])
            ->map(fn (array $items, string $title) => ['title' => $title, 'metrics' => $items])
            ->values()
            ->all();
    }
}
