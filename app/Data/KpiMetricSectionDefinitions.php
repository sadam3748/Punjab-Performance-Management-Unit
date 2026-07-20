<?php

namespace App\Data;

class KpiMetricSectionDefinitions
{
    /**
     * @return list<array{title: string, metrics: list<array{field: string, label: string}>}>
     */
    public static function for(string $slug, ?string $role = null): array
    {
        $slug = KpiDashboardDefinitions::normalizeSlug($slug);

        if (KpiLocationSlugs::isVisitKpi($slug)) {
            return [];
        }

        $sections = match ($slug) {
            'price-of-roti' => [
                self::section('Operational Performance', [
                    ['field' => 'inspections_total_target', 'label' => 'Daily Inspection Target'],
                    ['field' => 'tandoor_inspections', 'label' => 'Tandoors Inspected'],
                    ['field' => 'operational_remaining', 'label' => 'Inspections Remaining'],
                    ['field' => 'achievement_rate', 'label' => 'Target Achievement %'],
                ]),
                self::section('Enforcement', [
                    ['field' => 'violations_found', 'label' => 'Violations Found'],
                    ['field' => 'fine_imposed', 'label' => 'Fine Deposited'],
                    ['field' => 'complaints_resolved', 'label' => 'Complaints Resolved'],
                ]),
                self::section('Compliance Findings', [
                    ['field' => 'obs_over_price', 'label' => 'Over Price'],
                    ['field' => 'obs_under_weight', 'label' => 'Under Weight'],
                    ['field' => 'obs_non_availability', 'label' => 'Non-Availability of Roti'],
                    ['field' => 'obs_fine_imposed', 'label' => 'Fine Imposed'],
                    ['field' => 'obs_complaint_action', 'label' => 'Complaints Actioned'],
                ]),
            ],
            'price-of-plain-bakery-bread' => [
                self::section('Operational Performance', [
                    ['field' => 'inspections_total_target', 'label' => 'Daily Inspection Target'],
                    ['field' => 'bread_inspections', 'label' => 'Bakeries Inspected'],
                    ['field' => 'operational_remaining', 'label' => 'Inspections Remaining'],
                    ['field' => 'achievement_rate', 'label' => 'Target Achievement %'],
                ]),
                self::section('Compliance Findings', [
                    ['field' => 'violating_entities', 'label' => 'Violating Bakeries'],
                    ['field' => 'obs_over_price', 'label' => 'Overpricing Cases'],
                    ['field' => 'obs_non_availability', 'label' => 'Plain Bread Unavailable'],
                    ['field' => 'fines_count', 'label' => 'Fines Imposed'],
                    ['field' => 'fine_deposited', 'label' => 'Fine Deposited'],
                    ['field' => 'complaints_actioned', 'label' => 'Complaints Actioned'],
                ]),
            ],
            'price-control-of-essential-commodities' => [
                self::section('Operational Performance', [
                    ['field' => 'inspections_total_target', 'label' => 'Daily Inspection Target'],
                    ['field' => 'market_inspections', 'label' => 'Sale Points Inspected'],
                    ['field' => 'operational_remaining', 'label' => 'Inspections Remaining'],
                    ['field' => 'achievement_rate', 'label' => 'Target Achievement %'],
                ]),
                self::section('Inspection and Enforcement Findings', [
                    ['field' => 'violating_entities', 'label' => 'Violating Sale Points'],
                    ['field' => 'obs_over_price', 'label' => 'Overpricing Cases'],
                    ['field' => 'obs_commodity_types', 'label' => 'Commodity Types Checked'],
                    ['field' => 'reports_actioned', 'label' => 'Reports Actioned'],
                ]),
            ],
            'repair-of-small-roads-in-both-urban-and-rural-areas' => [
                self::section('Weekly Work Progress', [
                    ['field' => 'weekly_road_target', 'label' => 'Weekly Road Target'],
                    ['field' => 'roads_selected', 'label' => 'Roads Selected / Inspected'],
                    ['field' => 'roads_work_completed', 'label' => 'Roads Completed'],
                    ['field' => 'completion_achievement', 'label' => 'Completion Achievement %'],
                ]),
                self::section('Work Details and Quality Findings', [
                    ['field' => 'roads_patched', 'label' => 'Roads Patched'],
                    ['field' => 'lane_marking_done', 'label' => 'Lane-Marking Locations Completed'],
                    ['field' => 'obs_length_covered', 'label' => 'Total Length Repaired (m)'],
                    ['field' => 'roads_in_progress', 'label' => 'Work in Progress'],
                ]),
                self::reviewSection(),
            ],
            'dysfunctional-streetlights' => [
                self::section('Weekly Inspection Coverage', [
                    ['field' => 'roads_with_streetlights', 'label' => 'Roads with Streetlights'],
                    ['field' => 'weekly_visit_target', 'label' => 'Weekly Inspection Target'],
                    ['field' => 'roads_inspected', 'label' => 'Roads Inspected'],
                    ['field' => 'inspection_coverage', 'label' => 'Inspection Coverage %'],
                ]),
                self::section('Defect and Repair Findings', [
                    ['field' => 'faulty_lights_found', 'label' => 'Faulty Lights Identified'],
                    ['field' => 'lights_repaired', 'label' => 'Lights Repaired'],
                    ['field' => 'lights_pending_repair', 'label' => 'Lights Pending Repair'],
                    ['field' => 'roads_with_faulty_lights', 'label' => 'Roads with Faulty Lights'],
                ]),
                self::reviewSection(),
            ],
            'covering-of-manholes' => [
                self::section('Target / Coverage', [
                    ['field' => 'total_ucs', 'label' => 'UCs with Manholes'],
                    ['field' => 'ucs_inspected', 'label' => 'UCs Inspected'],
                    ['field' => 'compliance_rate', 'label' => 'Coverage %'],
                ]),
                self::section('Manhole Condition and Safety Findings', [
                    ['field' => 'open_manholes_found', 'label' => 'Open Manholes Identified'],
                    ['field' => 'manholes_covered', 'label' => 'Manholes Covered'],
                    ['field' => 'obs_netting_available', 'label' => 'Safety Nets Installed'],
                    ['field' => 'manholes_pending', 'label' => 'Manholes Pending Action'],
                ]),
                self::reviewSection(),
            ],
            'functional-and-clean-water-filtration-plants' => [
                self::section('Plant Inspection', [
                    ['field' => 'total_plants', 'label' => 'Total Plants'],
                    ['field' => 'weekly_inspection_target', 'label' => 'Weekly Inspection Target'],
                    ['field' => 'plants_inspected', 'label' => 'Plants Inspected'],
                    ['field' => 'functional_plants', 'label' => 'Functional Plants'],
                    ['field' => 'clean_plants', 'label' => 'Clean Plants'],
                ]),
                self::section('Plant Functionality Findings', [
                    ['field' => 'obs_functional', 'label' => 'Functional / Non-Functional'],
                    ['field' => 'obs_cleanliness', 'label' => 'Cleanliness'],
                    ['field' => 'filter_change_compliance', 'label' => 'RO Filter Date Affixed'],
                    ['field' => 'obs_sb_visit', 'label' => 'SB Visit'],
                ]),
            ],
            'violation-of-marriage-functions-act' => [
                self::section('Weekly Inspection', [
                    ['field' => 'total_halls', 'label' => 'Total Marriage Halls'],
                    ['field' => 'weekly_inspection_target', 'label' => 'Weekly Inspection Target'],
                    ['field' => 'marriage_hall_inspections', 'label' => 'Halls Inspected'],
                    ['field' => 'pra_registered', 'label' => 'PRA Registered'],
                    ['field' => 'violations_detected', 'label' => 'Violations Found'],
                    ['field' => 'notices_fines', 'label' => 'Fine / FIR / Sealing'],
                ]),
                self::section('Compliance and Enforcement Findings', [
                    ['field' => 'obs_one_dish_rule', 'label' => 'One Dish Rule'],
                    ['field' => 'obs_timing_compliance', 'label' => 'Timing Compliance'],
                    ['field' => 'obs_pra_registration', 'label' => 'PRA Registration'],
                    ['field' => 'obs_fine_imposed', 'label' => 'Fine'],
                    ['field' => 'obs_fir_filed', 'label' => 'FIR'],
                ]),
                self::reviewSection(),
            ],
            'anti-encroachment-campaign' => [
                self::section('Daily Market Clearance', [
                    ['field' => 'daily_market_target', 'label' => 'Daily Market Target'],
                    ['field' => 'markets_cleared', 'label' => 'Markets Cleared'],
                    ['field' => 'encroachment_points', 'label' => 'Encroachment Points'],
                    ['field' => 'encroachments_removed', 'label' => 'Encroachments Removed'],
                    ['field' => 'actions_taken', 'label' => 'Enforcement Actions'],
                ]),
                self::section('Clearance Activity Findings', [
                    ['field' => 'obs_moveable', 'label' => 'Moveable Encroachment'],
                    ['field' => 'obs_immovable', 'label' => 'Immoveable Encroachment'],
                    ['field' => 'obs_market_cleared', 'label' => 'Market Cleared'],
                ]),
                self::reviewSection(),
            ],
            'regulation-of-shops-and-handcarts' => [
                self::section('Daily Market Inspection', [
                    ['field' => 'daily_market_target', 'label' => 'Daily Market Target'],
                    ['field' => 'markets_inspected', 'label' => 'Markets Inspected'],
                    ['field' => 'shop_violations', 'label' => 'Shop Violations'],
                    ['field' => 'handcart_violations', 'label' => 'Handcart Violations'],
                    ['field' => 'actions_taken', 'label' => 'Warnings / Fines'],
                ]),
                self::section('Market Compliance Findings', [
                    ['field' => 'obs_shop_line_compliance', 'label' => 'Shop Line Compliance'],
                    ['field' => 'obs_walkway_obstruction', 'label' => 'Walkway Obstruction'],
                    ['field' => 'obs_waste_debris', 'label' => 'Waste/Debris'],
                    ['field' => 'obs_unauthorized_handcarts', 'label' => 'Unauthorized Handcarts'],
                ]),
                self::reviewSection(),
            ],
            'stray-dogs' => [
                self::section('Daily UC Activity', [
                    ['field' => 'target_ucs', 'label' => 'Daily UC Target'],
                    ['field' => 'uc_activities', 'label' => 'UC Activity Conducted'],
                    ['field' => 'dogs_observed', 'label' => 'Dogs Observed'],
                    ['field' => 'dogs_culled', 'label' => 'Dogs Culled'],
                    ['field' => 'sb_complaints', 'label' => 'SB/Rescue Complaints'],
                ]),
                self::section('Field Activity Findings', [
                    ['field' => 'obs_culling_performed', 'label' => 'Culling Activity Performed'],
                    ['field' => 'obs_dogs_observed', 'label' => 'Dogs Observed'],
                    ['field' => 'obs_dogs_culled', 'label' => 'Dogs Culled'],
                    ['field' => 'obs_sb_complaint', 'label' => 'SB Complaint'],
                ]),
                self::reviewSection(),
            ],
            'removal-of-wall-chalking' => [
                self::section('Daily UC Activity', [
                    ['field' => 'target_ucs', 'label' => 'Daily UC Target'],
                    ['field' => 'ucs_inspected', 'label' => 'UC Activity Done'],
                    ['field' => 'sites_identified', 'label' => 'Spots Identified'],
                    ['field' => 'removal_done', 'label' => 'Spots Removed'],
                    ['field' => 'pending_spots', 'label' => 'Pending Spots'],
                ]),
                self::section('Removal Activity Findings', [
                    ['field' => 'obs_wall_chalking_removed', 'label' => 'Wall Chalking Removed'],
                    ['field' => 'obs_spots_removed', 'label' => 'Spots Removed'],
                    ['field' => 'obs_sb_complaint', 'label' => 'SB Complaint'],
                ]),
                self::reviewSection(),
            ],
            'graveyards' => [
                self::section('Weekly Clearance', [
                    ['field' => 'weekly_target', 'label' => 'Weekly Target'],
                    ['field' => 'graveyards_cleared', 'label' => 'Graveyards Cleared'],
                    ['field' => 'boundary_wall_issues', 'label' => 'Boundary/Demarcation OK'],
                    ['field' => 'encroachment_removed', 'label' => 'Encroachment Removed'],
                ]),
                self::section('Graveyard Condition Findings', [
                    ['field' => 'obs_encroachment_removed', 'label' => 'Encroachment Removed'],
                    ['field' => 'obs_cleanliness', 'label' => 'Cleanliness'],
                    ['field' => 'obs_boundary_wall', 'label' => 'Boundary Wall / Demarcation'],
                    ['field' => 'obs_bush_trimming', 'label' => 'Bush Trimming'],
                ]),
                self::reviewSection(),
            ],
            'zebra-crossings' => [
                self::section('School Inspection Coverage', [
                    ['field' => 'schools_to_inspect', 'label' => 'Schools in Scope'],
                    ['field' => 'weekly_inspection_target', 'label' => 'Weekly Inspection Target'],
                    ['field' => 'schools_inspected', 'label' => 'Schools Inspected'],
                    ['field' => 'inspection_coverage', 'label' => 'Inspection Coverage %'],
                ]),
                self::section('Crossing Condition and Corrective Action Findings', [
                    ['field' => 'markings_done', 'label' => 'Crossings Compliant / Visible'],
                    ['field' => 'faded_only_crossings', 'label' => 'Faded Crossings'],
                    ['field' => 'missing_crossings', 'label' => 'Missing Crossings'],
                    ['field' => 'actions_completed', 'label' => 'Actions Completed'],
                ]),
            ],
            'illegal-decanting' => [
                self::section('Weekly Inspection', [
                    ['field' => 'tier_target', 'label' => 'Weekly Target'],
                    ['field' => 'stations_inspected', 'label' => 'Sale Points Inspected'],
                    ['field' => 'violations_found', 'label' => 'Violations Found'],
                    ['field' => 'fines_imposed', 'label' => 'Fines'],
                    ['field' => 'enforcement_actions', 'label' => 'FIR / Sealed'],
                ]),
                self::section('Violation and Enforcement Findings', [
                    ['field' => 'obs_illegal_decanting', 'label' => 'Illegal Decanting Observed'],
                    ['field' => 'obs_license_missing', 'label' => 'License Missing'],
                    ['field' => 'obs_unsafe_equipment', 'label' => 'Unsafe Equipment'],
                    ['field' => 'obs_fine_imposed', 'label' => 'Fine / FIR / Sealing'],
                ]),
                self::reviewSection(),
            ],
            'suthra-punjab-campaign' => [
                self::section('Weekly UC Inspection', [
                    ['field' => 'weekly_uc_target', 'label' => 'Weekly UC Target'],
                    ['field' => 'ac_uc_inspections', 'label' => 'UCs Inspected'],
                    ['field' => 'hr_attendance', 'label' => 'HR Attendance >85%'],
                    ['field' => 'vehicles_in_field', 'label' => 'Machinery in Field >80%'],
                    ['field' => 'containers_placed', 'label' => 'Containers Placed'],
                    ['field' => 'heaps_cleared', 'label' => 'Garbage Heaps Cleared'],
                ]),
                self::section('Cleanliness Compliance Findings', [
                    ['field' => 'obs_hr_attendance', 'label' => 'HR Attendance >85%'],
                    ['field' => 'obs_machinery_field', 'label' => 'Machinery in Field >80%'],
                    ['field' => 'obs_containers', 'label' => 'Containers at Designated Points'],
                    ['field' => 'obs_door_to_door', 'label' => 'Door-to-Door Collection'],
                    ['field' => 'obs_heaps_cleared', 'label' => 'Garbage Heaps Cleared'],
                ]),
            ],
            'maintenance-of-greenbelts' => [
                self::section('Weekly Maintenance', [
                    ['field' => 'total_parks', 'label' => 'Total Parks'],
                    ['field' => 'parks_maintained', 'label' => 'Parks Maintained'],
                    ['field' => 'greenbelts_maintained', 'label' => 'Greenbelts Maintained'],
                    ['field' => 'kerb_painting_target', 'label' => 'Kerb Painting Target'],
                    ['field' => 'beautification', 'label' => 'Road Painted'],
                ]),
                self::section('Maintenance Condition Findings', [
                    ['field' => 'obs_kerb_paint', 'label' => 'Kerb Stone Paint Done'],
                    ['field' => 'obs_flowering_grass', 'label' => 'Flowering & Grass Maintained'],
                    ['field' => 'obs_greenbelt_maintained', 'label' => 'Greenbelt Fully Maintained'],
                    ['field' => 'obs_cleanliness', 'label' => 'Cleanliness'],
                ]),
                self::reviewSection(),
            ],
            'maintenance-of-drains-and-sewerage-lines' => [
                self::section('Sewerage Inspection', [
                    ['field' => 'total_ucs', 'label' => 'UCs/MCs with Sewerage Lines'],
                    ['field' => 'ucs_inspected', 'label' => 'UCs/MCs Inspected'],
                    ['field' => 'blockages_reported', 'label' => 'Blockages Found'],
                    ['field' => 'overflow_points', 'label' => 'Overflow Points'],
                    ['field' => 'stagnant_water', 'label' => 'Stagnant Water Points'],
                    ['field' => 'resolved_points', 'label' => 'Resolved Points'],
                ]),
                self::section('Sewerage Condition and Resolution Findings', [
                    ['field' => 'obs_blocked', 'label' => 'Sewerage Lines Blocked/Choked'],
                    ['field' => 'obs_overflowing', 'label' => 'Overflowing'],
                    ['field' => 'obs_stagnant_water', 'label' => 'Stagnant Water'],
                    ['field' => 'obs_remedial_status', 'label' => 'Status After Remedial Action'],
                ]),
            ],
            'bus-terminals' => [
                self::section('Terminal Inspection', [
                    ['field' => 'total_terminals', 'label' => 'Total Terminals'],
                    ['field' => 'required_visits', 'label' => 'Weekly Visit Target'],
                    ['field' => 'terminals_inspected', 'label' => 'Terminals Inspected'],
                    ['field' => 'fare_display_checked', 'label' => 'Fare Display OK'],
                    ['field' => 'water_washroom_ok', 'label' => 'Water/Washroom OK'],
                    ['field' => 'cleanliness_checked', 'label' => 'Cleanliness OK'],
                ]),
                self::section('Terminal Facility Compliance Findings', [
                    ['field' => 'obs_fare_display', 'label' => 'Fare Display'],
                    ['field' => 'obs_waiting_area', 'label' => 'Waiting Area'],
                    ['field' => 'obs_drinking_water', 'label' => 'Drinking Water'],
                    ['field' => 'obs_utilities', 'label' => 'Functional Utilities'],
                ]),
                self::reviewSection(),
            ],
            'chief-ministers-complaint-cell' => [
                self::section('Complaint Outcome Findings', [
                    ['field' => 'complaints_received', 'label' => 'Complaints Received'],
                    ['field' => 'complaints_resolved', 'label' => 'Complaints Resolved'],
                    ['field' => 'pending_complaints', 'label' => 'Complaints Pending'],
                    ['field' => 'overdue_complaints', 'label' => 'Complaints Overdue'],
                ]),
            ],
            'e-biz' => [
                self::section('Weekly Processing', [
                    ['field' => 'pending_applications', 'label' => 'Pending >7 Days at Week Start'],
                    ['field' => 'applications_completed', 'label' => 'Processed This Week'],
                    ['field' => 'disposal_rate', 'label' => 'Completion Rate'],
                    ['field' => 'help_desk_inspections', 'label' => 'Help Desks Inspected'],
                    ['field' => 'branding_available', 'label' => 'Branding Available'],
                    ['field' => 'meeting_held', 'label' => 'Meeting Held'],
                ]),
                self::section('Application and Help-Desk Findings', [
                    ['field' => 'obs_help_desk', 'label' => 'Help Desk Established'],
                    ['field' => 'obs_branding', 'label' => 'Branding with Standees/Flexes'],
                    ['field' => 'obs_pending_apps', 'label' => 'Applications Pending >7 Days'],
                    ['field' => 'obs_processed_apps', 'label' => 'Applications Processed'],
                ]),
            ],
            'land-management-services' => [
                self::section('Service Delivery Findings', [
                    ['field' => 'records_processed', 'label' => 'Records Processed'],
                    ['field' => 'services_completed', 'label' => 'Services Completed'],
                    ['field' => 'pending_cases', 'label' => 'Cases Pending'],
                    ['field' => 'service_delivery_rate', 'label' => 'Service Delivery Rate'],
                ]),
            ],
            default => [],
        };

        $sections = array_map(static function (array $section): array {
            if (str_contains($section['title'], 'Findings')) {
                $section['metrics'] = array_slice($section['metrics'], 0, 4);
            }

            return $section;
        }, $sections);

        $hasReviewSection = collect($sections)
            ->flatMap(fn (array $section): array => $section['metrics'] ?? [])
            ->contains(fn (array $metric): bool => ($metric['field'] ?? null) === 'review_target');

        if ($sections !== [] && ! $hasReviewSection) {
            $reviewTitle = match ($slug) {
                'chief-ministers-complaint-cell' => 'Resolution Verification',
                'e-biz' => 'Application & Office Validation',
                default => 'Review & Validation',
            };
            $sections[] = self::reviewSection($reviewTitle);
        }

        return $sections;
    }

    /**
     * @param  list<array{field: string, label: string}>  $metrics
     * @return array{title: string, metrics: list<array{field: string, label: string}>}
     */
    private static function section(string $title, array $metrics): array
    {
        return ['title' => $title, 'metrics' => $metrics];
    }

    /** @return array{title: string, metrics: list<array{field: string, label: string}>} */
    private static function reviewSection(string $title = 'Review & Validation'): array
    {
        return self::section($title, [
            ['field' => 'review_target', 'label' => 'Review Target'],
            ['field' => 'reviewed', 'label' => 'Reviewed'],
            ['field' => 'inspections_pending', 'label' => 'Pending Review'],
            ['field' => 'inspections_approved', 'label' => 'Approved'],
            ['field' => 'inspections_rejected', 'label' => 'Rejected'],
            ['field' => 'inspected_only', 'label' => 'Inspected Only'],
            ['field' => 'review_target_balance', 'label' => 'Reviews Remaining'],
            ['field' => 'review_completion_rate', 'label' => 'Review Target Met %'],
        ]);
    }
}
