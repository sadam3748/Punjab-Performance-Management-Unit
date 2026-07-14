<?php

namespace Database\Seeders\Support;

class KpiInspectionDetailFactory
{
    /** @return array<string, mixed> */
    public static function forSlug(string $slug, int $index): array
    {
        $slug = match ($slug) {
            'repair-of-small-roads' => 'repair-of-small-roads-in-both-urban-and-rural-areas',
            default => $slug,
        };

        $pick = static fn (array $values): string => $values[$index % count($values)];
        $num = static fn (int $min, int $max): int => $min + ($index % ($max - $min + 1));

        return match ($slug) {
            'price-of-roti' => [
                'violation' => $pick(['Over Price', 'Under Weight', 'Non-Availability', 'Compliant', 'Over Price', 'Under Weight']),
                'fine' => $index % 6 === 3 ? 0 : $num(500, 5000),
                'payment_status' => $pick(['Paid', 'Pending', 'PSID Generated', 'Paid']),
                'psid' => 'PSID-'.str_pad((string) (10000 + $index), 6, '0', STR_PAD_LEFT),
                'complaint_action' => $pick(['Resolved', 'Pending', 'Referred', 'Resolved']),
            ],
            'price-of-plain-bakery-bread' => [
                'violation' => $pick(['Over Price', 'Non-Availability']),
                'fine' => $num(300, 3000),
                'payment_status' => $pick(['Paid', 'Pending']),
            ],
            'price-control-of-essential-commodities' => [
                'commodity' => $pick(['Flour', 'Sugar', 'Ghee', 'Pulses', 'Others']),
                'violation' => $pick(['Over Price', 'Hoarding', 'Non-Availability']),
                'fine' => $num(1000, 10000),
            ],
            'repair-of-small-roads-in-both-urban-and-rural-areas' => [
                'repair_type' => $pick(['Patching', 'Resurfacing', 'Shoulder Repair', 'Lane Marking']),
                'work_type' => $pick(['Patching', 'Resurfacing', 'Lane Marking']),
                'completion_status' => $pick(['Completed', 'In Progress', 'Completed']),
                'work_status' => $pick(['Completed', 'In Progress', 'Completed']),
                'lane_marking_done' => $pick(['yes', 'no', 'yes']),
                'length_covered_m' => $num(120, 480),
                'sb_complaint' => $pick(['no', 'yes', 'no']),
            ],
            'zebra-crossings' => [
                'crossing_status' => $pick(['Visible', 'Faded', 'Missing', 'Repainted', 'Visible']),
                'inspection_status' => $pick(['Compliant', 'Needs Repaint', 'Non-Compliant', 'Compliant']),
                'action_taken' => $pick(['Marking restored', 'Repaint ordered', 'Warning issued', 'Crossing repainted']),
                'sb_complaint' => $pick(['no', 'yes', 'no', 'no']),
            ],
            'dysfunctional-streetlights' => [
                'total_lights' => $num(20, 120),
                'dysfunctional_lights' => $num(2, 25),
                'repaired_lights' => $num(1, 20),
                'streetlight_status' => $pick(['Functional', 'Partially Functional', 'Non-Functional']),
                'sb_complaint' => $pick(['yes', 'no']),
                'action_taken' => $pick(['Repaired on site', 'Work order issued', 'Pending contractor']),
            ],
            'covering-of-manholes' => [
                'open_manholes' => $num(1, 8),
                'covered_manholes' => $num(4, 16),
                'manhole_cover_status' => $pick(['Covered', 'Open', 'Partially Covered', 'Covered']),
                'netting_available' => $pick(['yes', 'no', 'yes']),
                'all_manholes_covered' => $pick(['yes', 'no', 'yes']),
                'sb_complaint' => $pick(['no', 'yes', 'no']),
            ],
            'functional-and-clean-water-filtration-plants' => [
                'plant_type' => $pick(['RO', 'UF']),
                'inspected' => 'Yes',
                'functional_status' => $pick(['Functional', 'Non-Functional', 'Functional']),
                'cleanliness_status' => $pick(['Clean', 'Unclean', 'Clean']),
                'filter_change_status' => $pick(['Up to Date', 'Due', 'Up to Date']),
                'ro_filter_date_affixed' => $pick(['yes', 'no', 'yes']),
                'sb_visit' => $pick(['yes', 'no', 'yes']),
            ],
            'inspection-of-educational-institutions' => [
                'cleanliness_available' => $pick(['available', 'not_available']),
                'teachers_staff_available' => $pick(['available', 'not_available']),
                'books_learning_material_available' => $pick(['available', 'not_available']),
                'school_facilities_utilities_available' => $pick(['available', 'not_available']),
                'drinking_water_available' => $pick(['available', 'not_available']),
                'student_enrolment_checked' => $pick(['yes', 'no']),
                'students_enrolled' => $num(120, 520),
                'students_present' => $num(100, 480),
            ],
            'inspection-of-health-facilities' => [
                'facility_type' => $pick(['Hospital', 'BHU', 'RHC', 'Dispensary']),
                'deep_cleaning_available' => $pick(['available', 'not_available']),
                'staff_available' => $pick(['available', 'not_available']),
                'medicine_flex_available' => $pick(['available', 'not_available']),
                'testing_equipment_available' => $pick(['available', 'not_available']),
                'drinking_water_available' => $pick(['available', 'not_available']),
                'utilities_available' => $pick(['available', 'not_available']),
                'uhi_compliance' => $pick(['yes', 'no']),
                'cleanliness' => $pick(['Good', 'Average', 'Poor']),
                'staff_present' => $pick(['Yes', 'Partial', 'No']),
                'medicines_ok' => $pick(['Yes', 'Partial', 'No']),
                'equipment_status' => $pick(['Operational', 'Partial', 'Non-Operational']),
            ],
            'violation-of-marriage-functions-act' => [
                'violation' => $pick(['Over Capacity', 'Late Hours', 'Noise Violation', 'One Dish Violation']),
                'fine' => $num(5000, 50000),
                'one_dish_rule' => $pick(['yes', 'no']),
                'timing_compliance' => $pick(['yes', 'no']),
                'pra_registration' => $pick(['yes', 'no']),
                'fir_filed' => $pick(['yes', 'no']),
                'sealing' => $pick(['yes', 'no']),
            ],
            'anti-encroachment-campaign' => [
                'encroachment_points' => $num(5, 40),
                'cleared_points' => $num(2, 35),
                'encroachment_type' => $pick(['Moveable', 'Immoveable', 'Mixed']),
                'moveable_encroachment' => $pick(['yes', 'no']),
                'immovable_encroachment' => $pick(['yes', 'no']),
                'traffic_congestion' => $pick(['yes', 'no']),
                'market_cleared' => $pick(['yes', 'no']),
                'fir_filed' => $pick(['yes', 'no']),
                'action_taken' => $pick(['Fine imposed', 'Confiscation', 'FIR registered']),
            ],
            'stray-dogs' => [
                'activity_conducted' => $pick(['Catching Drive', 'Vaccination', 'Awareness']),
                'team_name' => $pick(['MC Team A', 'Rescue Squad B', 'Field Unit C']),
                'dogs_observed' => $num(3, 28),
                'dogs_culled' => $num(1, 12),
                'culling_activity' => $pick(['yes', 'no']),
                'sb_complaint' => $pick(['yes', 'no']),
                'rescue_1122_complaint' => $pick(['yes', 'no']),
            ],
            'removal-of-wall-chalking' => [
                'spots_identified' => $num(10, 80),
                'spots_cleared' => $num(5, 70),
                'banners_removed' => $num(1, 15),
                'wall_chalking_removed' => $pick(['yes', 'no']),
                'sb_complaint' => $pick(['yes', 'no']),
            ],
            'graveyards' => [
                'demarcated' => $pick(['yes', 'no']),
                'encroachment_removed' => $pick(['yes', 'no']),
                'cleaned' => $pick(['yes', 'no']),
                'bush_trimming' => $pick(['yes', 'no']),
                'soiling_leveling' => $pick(['yes', 'no']),
                'sb_visit' => $pick(['yes', 'no']),
            ],
            'illegal-decanting' => [
                'violation' => $pick(['Illegal Decanting', 'Unlicensed Storage', 'Safety Breach']),
                'action_type' => $pick(['Fine', 'FIR', 'Sealed']),
                'fine' => $num(10000, 100000),
            ],
            'suthra-punjab-campaign' => [
                'dc_inspected' => $pick(['yes', 'no']),
                'ac_inspected' => $pick(['yes', 'no']),
                'cleanliness_status' => $pick(['Satisfactory', 'Needs Improvement', 'Poor']),
                'hr_attendance_ok' => $pick(['yes', 'no']),
                'machinery_in_field' => $pick(['yes', 'no']),
                'containers_placed' => $pick(['yes', 'no']),
                'door_to_door_collection' => $pick(['yes', 'no']),
                'garbage_heaps_cleared' => $pick(['yes', 'no']),
                'sb_complaint' => $pick(['yes', 'no']),
            ],
            'maintenance-of-greenbelts' => [
                'type' => $pick(['Park', 'Greenbelt', 'Road']),
                'site_type' => $pick(['park', 'greenbelt', 'road']),
                'maintenance_status' => $pick(['Maintained', 'Partial', 'Neglected']),
                'dc_initiative' => $pick(['yes', 'no']),
                'kerb_stone_paint' => $pick(['yes', 'no']),
                'cleanliness_ok' => $pick(['yes', 'no']),
                'sb_complaint' => $pick(['yes', 'no']),
            ],
            'maintenance-of-drains-and-sewerage-lines' => [
                'blockage_identified' => $num(1, 10),
                'blockages_found' => $num(1, 10),
                'stagnant_water' => $pick(['yes', 'no']),
                'overflowing' => $pick(['yes', 'no']),
                'cleaned_status' => $pick(['Cleaned', 'In Progress', 'Pending']),
                'issue_type' => $pick(['Blockage', 'Overflow', 'Stagnant Water']),
                'sb_complaint' => $pick(['yes', 'no']),
            ],
            'bus-terminals' => [
                'fare_display' => $pick(['Visible', 'Missing', 'Incorrect']),
                'waiting_area' => $pick(['Available', 'Limited', 'Unavailable']),
                'drinking_water' => $pick(['Available', 'Unavailable']),
                'washroom' => $pick(['Clean', 'Needs Repair', 'Unavailable']),
                'cleanliness' => $pick(['Good', 'Average', 'Poor']),
                'electricity' => $pick(['Available', 'Partial', 'Unavailable']),
                'overcharging' => $pick(['yes', 'no']),
                'sb_report' => $pick(['yes', 'no']),
            ],
            'chief-ministers-complaint-cell' => [
                'complaint_status' => $pick(['Resolved', 'In Progress', 'Pending']),
                'resolution_days' => $num(1, 30),
                'overdue_status' => $pick(['On Time', 'Overdue']),
            ],
            'regulation-of-shops-and-handcarts' => [
                'shops_checked' => $num(10, 60),
                'handcarts_checked' => $num(5, 40),
                'violations_found' => $num(0, 15),
                'shop_line_compliance' => $pick(['yes', 'no']),
                'walkway_obstruction' => $pick(['yes', 'no']),
                'waste_debris' => $pick(['yes', 'no']),
                'operating_hours_compliance' => $pick(['yes', 'no']),
                'action_taken' => $pick(['Warning', 'Fine', 'Confiscation']),
            ],
            'e-biz' => [
                'application_no' => 'EBIZ-'.str_pad((string) (1000 + $index), 5, '0', STR_PAD_LEFT),
                'service_type' => $pick(['Business Registration', 'License Renewal', 'NOC']),
                'applications_reviewed' => $num(5, 40),
                'pending_cases' => $num(0, 12),
                'timeline_compliance' => $pick(['Compliant', 'Delayed', 'Overdue']),
                'help_desk_established' => $pick(['yes', 'no']),
                'branding_available' => $pick(['yes', 'no']),
                'dc_meeting_held' => $pick(['yes', 'no']),
            ],
            default => [
                'compliance_score' => $num(55, 95),
                'visit_type' => $pick(['Scheduled', 'Follow-up']),
            ],
        };
    }
}
