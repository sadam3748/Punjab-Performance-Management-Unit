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
            default => [],
        };
    }

    /**
     * @return list<array{title: string, metrics: list<array{field: string, label: string}>}>
     */
    private static function healthSections(?string $role): array
    {
        $coverage = [
            ['field' => 'total_health_facilities', 'label' => 'Total Facilities'],
            ['field' => 'facilities_inspected', 'label' => 'Facilities Inspected'],
            ['field' => 'facilities_not_inspected', 'label' => 'Facilities Not Inspected'],
            ['field' => 'validation_target', 'label' => 'Validation Target'],
            ['field' => 'validations_completed', 'label' => 'Validated'],
            ['field' => 'inspections_pending', 'label' => 'Pending'],
            ['field' => 'inspections_approved', 'label' => 'Approved'],
            ['field' => 'inspections_rejected', 'label' => 'Rejected'],
        ];

        $visits = match ($role) {
            'ac', 'field_user' => [
                ['field' => 'required_visits', 'label' => 'Required Visits'],
                ['field' => 'target_completed', 'label' => 'Completed Visits'],
                ['field' => 'ac_visit_achievement', 'label' => 'Target Achievement'],
            ],
            'dc' => [
                ['field' => 'district_ac_visit_target', 'label' => 'AC Visit Target'],
                ['field' => 'dc_visits', 'label' => 'DC Own Visits'],
                ['field' => 'health_council_meeting', 'label' => 'Council Meetings'],
            ],
            'commissioner' => [
                ['field' => 'district_visits', 'label' => 'District Visits'],
                ['field' => 'dc_visits', 'label' => 'DC Visits'],
                ['field' => 'health_council_meeting', 'label' => 'Meetings Held'],
            ],
            'chief_secretary', 'super_admin', 'pmru_user', 'viewer' => [
                ['field' => 'districts_reporting', 'label' => 'Districts Reporting'],
                ['field' => 'total_visits', 'label' => 'Total Visits'],
                ['field' => 'health_council_meeting', 'label' => 'Meetings Held'],
                ['field' => 'achievement_rate', 'label' => 'Achievement %'],
            ],
            default => [
                ['field' => 'ac_visits', 'label' => 'AC Visits'],
                ['field' => 'dc_visits', 'label' => 'DC Visits'],
                ['field' => 'health_council_meeting', 'label' => 'Health Council Meeting'],
            ],
        };

        return [
            ['title' => 'Inspection Coverage', 'metrics' => $coverage],
            ['title' => 'Visits & Meetings', 'metrics' => $visits],
            ['title' => 'Issues Found', 'metrics' => [
                ['field' => 'issues_cleanliness', 'label' => 'Cleanliness'],
                ['field' => 'issues_staff_absence', 'label' => 'Staff Absence'],
                ['field' => 'issues_medicine_shortage', 'label' => 'Medicine Shortage'],
                ['field' => 'issues_equipment_utilities', 'label' => 'Equipment'],
            ]],
        ];
    }

    /**
     * @return list<array{title: string, metrics: list<array{field: string, label: string}>}>
     */
    private static function educationSections(?string $role): array
    {
        $coverage = [
            ['field' => 'total_institutions', 'label' => 'Total Institutions'],
            ['field' => 'institutions_inspected', 'label' => 'Inspected'],
            ['field' => 'institutions_not_inspected', 'label' => 'Not Inspected'],
            ['field' => 'validation_target', 'label' => 'Validation Target'],
            ['field' => 'validations_completed', 'label' => 'Validated'],
            ['field' => 'inspections_pending', 'label' => 'Pending'],
            ['field' => 'inspections_approved', 'label' => 'Approved'],
            ['field' => 'inspections_rejected', 'label' => 'Rejected'],
        ];

        $visits = match ($role) {
            'ac', 'field_user' => [
                ['field' => 'ac_visits', 'label' => 'AC Visits'],
                ['field' => 'ac_visit_target', 'label' => 'Visit Target'],
                ['field' => 'ac_visit_achievement', 'label' => 'Visit Achievement'],
            ],
            'dc' => [
                ['field' => 'ac_visits', 'label' => 'AC Visits'],
                ['field' => 'dc_visits', 'label' => 'DC Visits'],
                ['field' => 'school_council_meeting', 'label' => 'School Council Meeting'],
            ],
            'commissioner' => [
                ['field' => 'district_visits', 'label' => 'District Visits'],
                ['field' => 'dc_visits', 'label' => 'DC Visits'],
                ['field' => 'school_council_meeting', 'label' => 'Meetings Held'],
            ],
            'chief_secretary', 'super_admin', 'pmru_user', 'viewer' => [
                ['field' => 'districts_reporting', 'label' => 'Districts Reporting'],
                ['field' => 'total_visits', 'label' => 'Total Visits'],
                ['field' => 'school_council_meeting', 'label' => 'Meetings Held'],
                ['field' => 'achievement_rate', 'label' => 'Achievement %'],
            ],
            default => [
                ['field' => 'ac_visits', 'label' => 'AC Visits'],
                ['field' => 'dc_visits', 'label' => 'DC Visits'],
                ['field' => 'school_council_meeting', 'label' => 'School Council Meeting'],
            ],
        };

        return [
            ['title' => 'Institution Coverage', 'metrics' => $coverage],
            ['title' => 'Visits & Meetings', 'metrics' => $visits],
            ['title' => 'Issues Found', 'metrics' => [
                ['field' => 'issues_cleanliness', 'label' => 'Cleanliness'],
                ['field' => 'issues_teacher_absence', 'label' => 'Teacher Absence'],
                ['field' => 'issues_tlm_shortage', 'label' => 'TLM Shortage'],
                ['field' => 'issues_facility_deficiency', 'label' => 'Facility Deficiency'],
            ]],
        ];
    }

    /**
     * @return list<array{title: string, metrics: list<array{field: string, label: string}>}>
     */
    private static function rotiSections(?string $role): array
    {
        $target = [
            ['field' => 'total_inspectors', 'label' => 'Inspectors'],
            ['field' => 'tier_target', 'label' => 'Target / Inspector'],
            ['field' => 'inspections_total_target', 'label' => 'Total Target'],
            ['field' => 'tandoor_inspections', 'label' => 'Conducted'],
        ];

        $compliance = [
            ['field' => 'violations_found', 'label' => 'Violations'],
            ['field' => 'over_price_violations', 'label' => 'Over Price'],
            ['field' => 'under_weight_violations', 'label' => 'Under Weight'],
        ];

        $complaints = [
            ['field' => 'citizen_complaints_received', 'label' => 'Complaints'],
            ['field' => 'complaints_resolved', 'label' => 'Resolved'],
            ['field' => 'complaint_resolution_rate', 'label' => 'Resolution %'],
        ];

        $validation = [
            ['field' => 'validation_target', 'label' => 'Validation Target'],
            ['field' => 'validated_inspections', 'label' => 'Validated'],
            ['field' => 'approved_validations', 'label' => 'Approved'],
            ['field' => 'rejected_validations', 'label' => 'Rejected'],
        ];

        $sections = [
            ['title' => 'Inspection Target', 'metrics' => $target],
            ['title' => 'Compliance / Enforcement', 'metrics' => $compliance],
            ['title' => 'Complaints', 'metrics' => $complaints],
            ['title' => 'Validation', 'metrics' => $validation],
        ];

        if (! in_array($role, ['dc', 'commissioner', 'chief_secretary', 'super_admin', 'pmru_user'], true)) {
            return $sections;
        }

        array_splice($sections, 1, 0, [[
            'title' => 'Review Meetings',
            'metrics' => [['field' => 'dc_weekly_review', 'label' => 'DC Weekly Review Meetings']],
        ]]);

        return $sections;
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
