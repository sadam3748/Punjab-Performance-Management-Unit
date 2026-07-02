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
                'violation' => $pick(['Over Price', 'Under Weight', 'Non-Availability']),
                'fine' => $num(500, 5000),
                'payment_status' => $pick(['Paid', 'Pending', 'PSID Generated']),
                'psid' => 'PSID-'.str_pad((string) (10000 + $index), 6, '0', STR_PAD_LEFT),
            ],
            'price-of-plain-bakery-bread' => [
                'violation' => $pick(['Over Price', 'Non-Availability']),
                'fine' => $num(300, 3000),
            ],
            'price-control-of-essential-commodities' => [
                'commodity' => $pick(['Flour', 'Sugar', 'Ghee', 'Pulses', 'Others']),
                'violation' => $pick(['Over Price', 'Hoarding', 'Non-Availability']),
                'fine' => $num(1000, 10000),
            ],
            'repair-of-small-roads-in-both-urban-and-rural-areas' => [
                'repair_type' => $pick(['Patching', 'Resurfacing', 'Shoulder Repair']),
                'completion_status' => $pick(['Completed', 'In Progress', 'Pending']),
            ],
            'zebra-crossings' => [
                'crossing_status' => $pick(['Marked', 'Faded', 'Missing']),
                'inspection_status' => $pick(['Compliant', 'Needs Repaint', 'Non-Compliant']),
            ],
            'dysfunctional-streetlights' => [
                'total_lights' => $num(20, 120),
                'dysfunctional_lights' => $num(2, 25),
                'repaired_lights' => $num(1, 20),
            ],
            'covering-of-manholes' => [
                'open_manholes' => $num(1, 12),
                'covered_manholes' => $num(3, 15),
            ],
            'functional-and-clean-water-filtration-plants' => [
                'plant_type' => $pick(['RO', 'UF']),
                'inspected' => $pick(['Yes', 'No']),
                'functional_status' => $pick(['Functional', 'Partially Functional', 'Non-Functional']),
                'cleanliness_status' => $pick(['Clean', 'Needs Cleaning', 'Poor']),
                'filter_change_status' => $pick(['Up to Date', 'Due', 'Overdue']),
            ],
            'inspection-of-educational-institutions' => [
                'cleanliness' => $pick(['Good', 'Average', 'Poor']),
                'teachers_present' => $pick(['Yes', 'Partial', 'No']),
                'school_council_activated' => $pick(['Yes', 'No']),
                'tlm_availability' => $pick(['Adequate', 'Partial', 'Shortage']),
                'facility_deficiency' => $pick(['None', 'Minor', 'Major']),
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
                'violation' => $pick(['Over Capacity', 'Late Hours', 'Noise Violation']),
                'fine' => $num(5000, 50000),
            ],
            'anti-encroachment-campaign' => [
                'encroachment_points' => $num(5, 40),
                'cleared_points' => $num(2, 35),
            ],
            'stray-dogs' => [
                'activity_conducted' => $pick(['Catching Drive', 'Vaccination', 'Awareness']),
                'team_name' => $pick(['MC Team A', 'Rescue Squad B', 'Field Unit C']),
            ],
            'removal-of-wall-chalking' => [
                'spots_identified' => $num(10, 80),
                'spots_cleared' => $num(5, 70),
                'banners_removed' => $num(1, 15),
            ],
            'graveyards' => [
                'demarcated' => $pick(['Yes', 'Partial', 'No']),
                'encroachment_removed' => $pick(['Yes', 'Partial', 'No']),
                'cleaned' => $pick(['Yes', 'Partial', 'No']),
            ],
            'illegal-decanting' => [
                'violation' => $pick(['Illegal Decanting', 'Unlicensed Storage', 'Safety Breach']),
                'action_type' => $pick(['Fine', 'FIR', 'Sealed']),
                'fine' => $num(10000, 100000),
            ],
            'suthra-punjab-campaign' => [
                'dc_inspected' => $pick(['Yes', 'No']),
                'ac_inspected' => $pick(['Yes', 'No']),
                'cleanliness_status' => $pick(['Satisfactory', 'Needs Improvement', 'Poor']),
            ],
            'maintenance-of-greenbelts' => [
                'type' => $pick(['Park', 'Greenbelt', 'Beautification Initiative']),
                'maintenance_status' => $pick(['Maintained', 'Partial', 'Neglected']),
                'dc_initiative' => $pick(['Yes', 'No']),
            ],
            'maintenance-of-drains-and-sewerage-lines' => [
                'blockage_identified' => $num(1, 10),
                'stagnant_water' => $pick(['Yes', 'No']),
                'cleaned_status' => $pick(['Cleaned', 'In Progress', 'Pending']),
            ],
            'bus-terminals' => [
                'fare_display' => $pick(['Visible', 'Missing', 'Incorrect']),
                'waiting_area' => $pick(['Available', 'Limited', 'Unavailable']),
                'drinking_water' => $pick(['Available', 'Unavailable']),
                'washroom' => $pick(['Clean', 'Needs Repair', 'Unavailable']),
                'cleanliness' => $pick(['Good', 'Average', 'Poor']),
                'electricity' => $pick(['Available', 'Partial', 'Unavailable']),
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
            ],
            'e-biz' => [
                'application_no' => 'EBIZ-'.str_pad((string) (1000 + $index), 5, '0', STR_PAD_LEFT),
                'service_type' => $pick(['Business Registration', 'License Renewal', 'NOC']),
                'applications_reviewed' => $num(5, 40),
                'pending_cases' => $num(0, 12),
                'timeline_compliance' => $pick(['Compliant', 'Delayed', 'Overdue']),
            ],
            default => [
                'compliance_score' => $num(55, 95),
                'visit_type' => $pick(['Scheduled', 'Follow-up']),
            ],
        };
    }
}
