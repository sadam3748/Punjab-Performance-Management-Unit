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
                self::section('Daily Inspection', [
                    ['field' => 'inspections_total_target', 'label' => 'Daily Target'],
                    ['field' => 'tandoor_inspections', 'label' => 'Tandoors Inspected'],
                    ['field' => 'achievement_rate', 'label' => 'Target Achievement %'],
                ]),
                self::section('Enforcement', [
                    ['field' => 'violations_found', 'label' => 'Violations Found'],
                    ['field' => 'fine_imposed', 'label' => 'Fine Deposited'],
                    ['field' => 'complaints_resolved', 'label' => 'Complaints Resolved'],
                ]),
                self::section('Observation Summary', [
                    ['field' => 'obs_over_price', 'label' => 'Over Price'],
                    ['field' => 'obs_under_weight', 'label' => 'Under Weight'],
                    ['field' => 'obs_non_availability', 'label' => 'Non-Availability of Roti'],
                    ['field' => 'obs_fine_imposed', 'label' => 'Fine Imposed'],
                    ['field' => 'obs_complaint_action', 'label' => 'Complaint Action'],
                ]),
            ],
            'price-of-plain-bakery-bread' => [
                self::section('Daily Inspection', [
                    ['field' => 'tier_target', 'label' => 'Daily Target'],
                    ['field' => 'bread_inspections', 'label' => 'Bakeries Inspected'],
                    ['field' => 'achievement_rate', 'label' => 'Target Achievement %'],
                ]),
                self::section('Enforcement', [
                    ['field' => 'violations_found', 'label' => 'Violations Found'],
                    ['field' => 'fine_imposed', 'label' => 'Fine Deposited'],
                    ['field' => 'citizen_complaint_action', 'label' => 'Complaint Action'],
                ]),
                self::section('Observation Summary', [
                    ['field' => 'obs_over_price', 'label' => 'Over Price'],
                    ['field' => 'obs_non_availability', 'label' => 'Non-Availability of Plain Bread'],
                    ['field' => 'obs_fine_imposed', 'label' => 'Fine Imposed'],
                    ['field' => 'obs_complaint_action', 'label' => 'Complaint Action'],
                ]),
            ],
            'price-control-of-essential-commodities' => [
                self::section('Daily Inspection', [
                    ['field' => 'tier_target', 'label' => 'Daily Target'],
                    ['field' => 'market_inspections', 'label' => 'Sale Points Inspected'],
                    ['field' => 'achievement_rate', 'label' => 'Target Achievement %'],
                ]),
                self::section('Enforcement', [
                    ['field' => 'sb_violations', 'label' => 'Commodity Violations'],
                    ['field' => 'fine_imposed', 'label' => 'Fine Deposited'],
                    ['field' => 'citizen_violations', 'label' => 'Citizen/SB Reports Actioned'],
                ]),
                self::section('Observation Summary', [
                    ['field' => 'obs_commodity_types', 'label' => 'Commodity Types Checked'],
                    ['field' => 'obs_over_price', 'label' => 'Over Price'],
                    ['field' => 'obs_citizen_report', 'label' => 'Citizen Report'],
                    ['field' => 'obs_sb_report', 'label' => 'Special Branch Report'],
                    ['field' => 'obs_fine_imposed', 'label' => 'Fine Imposed'],
                ]),
            ],
            'repair-of-small-roads-in-both-urban-and-rural-areas' => [
                self::section('Weekly Progress', [
                    ['field' => 'weekly_road_target', 'label' => 'Weekly Road Target'],
                    ['field' => 'repair_completed', 'label' => 'Roads Maintained'],
                    ['field' => 'achievement_rate', 'label' => 'Achievement %'],
                ]),
                self::section('Work Details', [
                    ['field' => 'lane_marking_done', 'label' => 'Lane Marking Done'],
                    ['field' => 'roads_in_progress', 'label' => 'In Progress'],
                    ['field' => 'roads_work_completed', 'label' => 'Completed'],
                ]),
                self::section('Observation Summary', [
                    ['field' => 'obs_work_type', 'label' => 'Work Type'],
                    ['field' => 'obs_work_status', 'label' => 'Work Status'],
                    ['field' => 'obs_length_covered', 'label' => 'Length Covered (m)'],
                    ['field' => 'obs_lane_marking', 'label' => 'Lane Marking Done'],
                    ['field' => 'obs_sb_complaint', 'label' => 'SB Complaint'],
                ]),
                self::reviewSection(),
            ],
            'dysfunctional-streetlights' => [
                self::section('Weekly Coverage', [
                    ['field' => 'roads_with_streetlights', 'label' => 'Roads with Streetlights'],
                    ['field' => 'weekly_visit_target', 'label' => 'Weekly Visit Target'],
                    ['field' => 'roads_inspected', 'label' => 'Roads Inspected'],
                    ['field' => 'faulty_lights_found', 'label' => 'Faulty Lights Found'],
                    ['field' => 'lights_repaired', 'label' => 'Lights Repaired'],
                    ['field' => 'repair_rate', 'label' => 'Repair Rate'],
                ]),
                self::section('Observation Summary', [
                    ['field' => 'obs_streetlight_status', 'label' => 'Streetlight Status'],
                    ['field' => 'obs_faulty_lights', 'label' => 'Faulty Lights Identified'],
                    ['field' => 'obs_lights_repaired', 'label' => 'Streetlights Repaired'],
                    ['field' => 'obs_sb_complaint', 'label' => 'SB Complaint'],
                ]),
                self::reviewSection(),
            ],
            'covering-of-manholes' => [
                self::section('Target / Coverage', [
                    ['field' => 'total_ucs', 'label' => 'UCs with Manholes'],
                    ['field' => 'ucs_inspected', 'label' => 'UCs Inspected'],
                    ['field' => 'open_manholes_found', 'label' => 'Open Manholes Found'],
                    ['field' => 'manholes_covered', 'label' => 'Manholes Covered'],
                    ['field' => 'compliance_rate', 'label' => 'Coverage %'],
                ]),
                self::section('Observation Summary', [
                    ['field' => 'obs_manhole_status', 'label' => 'Manhole Cover Status'],
                    ['field' => 'obs_open_manholes', 'label' => 'Open Manholes Count'],
                    ['field' => 'obs_manholes_covered', 'label' => 'All Manholes Covered'],
                    ['field' => 'obs_netting_available', 'label' => 'Netting Available'],
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
                    ['field' => 'filter_change_compliance', 'label' => 'RO Filter Date Affixed'],
                ]),
                self::section('Observation Summary', [
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
                self::section('Observation Summary', [
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
                self::section('Observation Summary', [
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
                self::section('Observation Summary', [
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
                self::section('Observation Summary', [
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
                self::section('Observation Summary', [
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
                    ['field' => 'obs_cleanliness', 'label' => 'Cleanliness/Bushes Done'],
                ]),
                self::section('Observation Summary', [
                    ['field' => 'obs_encroachment_removed', 'label' => 'Encroachment Removed'],
                    ['field' => 'obs_cleanliness', 'label' => 'Cleanliness'],
                    ['field' => 'obs_boundary_wall', 'label' => 'Boundary Wall / Demarcation'],
                    ['field' => 'obs_bush_trimming', 'label' => 'Bush Trimming'],
                ]),
                self::reviewSection(),
            ],
            'zebra-crossings' => [
                self::section('School Inspection', [
                    ['field' => 'schools_to_inspect', 'label' => 'Schools Requiring Zebra Crossing'],
                    ['field' => 'weekly_inspection_target', 'label' => 'Weekly Inspection Target'],
                    ['field' => 'schools_inspected', 'label' => 'Schools Inspected'],
                    ['field' => 'markings_done', 'label' => 'Visible Crossings'],
                    ['field' => 'faded_crossings', 'label' => 'Faded / Absent Crossings'],
                    ['field' => 'resolved_points', 'label' => 'Restored Crossings'],
                ]),
                self::section('Observation Summary', [
                    ['field' => 'obs_crossing_status', 'label' => 'Zebra Crossing Status'],
                    ['field' => 'obs_repainted', 'label' => 'Repainted / Restored'],
                    ['field' => 'obs_action_taken', 'label' => 'Action Taken'],
                    ['field' => 'obs_sb_complaint', 'label' => 'SB Complaint'],
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
                self::section('Observation Summary', [
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
                self::section('Observation Summary', [
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
                self::section('Observation Summary', [
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
                self::section('Observation Summary', [
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
                self::section('Observation Summary', [
                    ['field' => 'obs_fare_display', 'label' => 'Fare Display'],
                    ['field' => 'obs_waiting_area', 'label' => 'Waiting Area'],
                    ['field' => 'obs_drinking_water', 'label' => 'Drinking Water'],
                    ['field' => 'obs_utilities', 'label' => 'Functional Utilities'],
                ]),
                self::reviewSection(),
            ],
            'chief-ministers-complaint-cell' => [
                self::section('Complaint Summary', [
                    ['field' => 'complaints_received', 'label' => 'Complaints Received'],
                    ['field' => 'complaints_resolved', 'label' => 'Complaints Resolved'],
                    ['field' => 'pending_complaints', 'label' => 'Complaints Pending'],
                    ['field' => 'overdue_complaints', 'label' => 'Complaints Overdue'],
                    ['field' => 'resolution_rate', 'label' => 'Resolution Rate'],
                    ['field' => 'avg_resolution_days', 'label' => 'Average Resolution Time'],
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
                self::section('Observation Summary', [
                    ['field' => 'obs_help_desk', 'label' => 'Help Desk Established'],
                    ['field' => 'obs_branding', 'label' => 'Branding with Standees/Flexes'],
                    ['field' => 'obs_pending_apps', 'label' => 'Applications Pending >7 Days'],
                    ['field' => 'obs_processed_apps', 'label' => 'Applications Processed'],
                    ['field' => 'meeting_held', 'label' => 'DC Meeting Held'],
                ]),
            ],
            'land-management-services' => [],
            default => [],
        };

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
    private static function reviewSection(): array
    {
        return self::section('Review Status', [
            ['field' => 'review_target', 'label' => 'Review Target'],
            ['field' => 'reviewed', 'label' => 'Reviewed'],
            ['field' => 'inspections_pending', 'label' => 'Pending Review'],
            ['field' => 'inspections_approved', 'label' => 'Approved'],
            ['field' => 'inspections_rejected', 'label' => 'Rejected'],
        ]);
    }
}
