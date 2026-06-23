<?php

namespace App\Services;

class KpiMetricConfigService
{
    /** @return array<int, array{label:string,field:string,icon:string,tone:string}> */
    public function cardsFor(string $slug): array
    {
        $m = fn (string $label, string $field, string $icon = 'bi-bar-chart', string $tone = 'blue') => compact('label', 'field', 'icon', 'tone');

        return match ($slug) {
            'price-of-roti' => [
                $m('DC Weekly Reviews Held', 'dc_weekly_review', 'bi-person-check', 'blue'),
                $m('Tandoor Inspections', 'tandoor_inspections', 'bi-shop', 'blue'),
                $m('Tier Target', 'tier_target', 'bi-bullseye', 'purple'),
                $m('Mobility Index', 'coverage_mobility_index', 'bi-speedometer2', 'blue'),
                $m('Violations / Fines', 'fine_imposed', 'bi-currency-rupee', 'yellow'),
                $m('Complaints Resolved', 'citizen_complaint_action', 'bi-check-circle', 'green'),
            ],
            'price-of-plain-bakery-bread' => [
                $m('Bakery / Brand Inspections', 'bread_inspections', 'bi-shop', 'blue'),
                $m('Tier Target', 'tier_target', 'bi-bullseye', 'purple'),
                $m('Mobility Index', 'coverage_mobility_index', 'bi-speedometer2', 'blue'),
                $m('Fine Actions', 'fine_imposed', 'bi-gavel', 'yellow'),
                $m('Complaints Resolved', 'citizen_complaint_action', 'bi-check-circle', 'green'),
            ],
            'price-control-of-essential-commodities' => [
                $m('Sale Point Inspections', 'market_inspections', 'bi-clipboard2-check', 'blue'),
                $m('Tier Target', 'tier_target', 'bi-bullseye', 'purple'),
                $m('Special Branch Violations', 'sb_violations', 'bi-exclamation-triangle', 'red'),
                $m('Citizen Violations', 'citizen_violations', 'bi-person-exclamation', 'yellow'),
                $m('Fines Imposed', 'fine_imposed', 'bi-currency-rupee', 'yellow'),
                $m('Compliance %', 'compliance_rate', 'bi-percent', 'green'),
            ],
            'repair-of-small-roads-in-both-urban-and-rural-areas' => [
                $m('Roads Maintained', 'repair_completed', 'bi-tools', 'green'),
                $m('Weekly Road Target', 'weekly_road_target', 'bi-bullseye', 'purple'),
                $m('Special Branch Points', 'sb_points', 'bi-flag', 'blue'),
                $m('Resolved Points', 'complaints_resolved', 'bi-check-circle', 'green'),
            ],
            'dysfunctional-streetlights' => [
                $m('Roads / Streets Inspected', 'roads_inspected', 'bi-signpost', 'blue'),
                $m('Lights Repaired', 'repairs_completed', 'bi-lightbulb-fill', 'green'),
                $m('Special Branch Reported', 'sb_reported', 'bi-flag', 'blue'),
                $m('Resolved', 'resolved_points', 'bi-check-circle', 'green'),
                $m('Pending', 'pending_points', 'bi-hourglass-split', 'yellow'),
                $m('Functional %', 'functional_rate', 'bi-percent', 'green'),
            ],
            'covering-of-manholes' => [
                $m('UCs Inspected', 'ucs_inspected', 'bi-geo-alt', 'blue'),
                $m('Total UCs with Manholes', 'total_ucs', 'bi-grid', 'blue'),
                $m('Open Manholes Reported', 'manholes_identified', 'bi-exclamation-octagon', 'red'),
                $m('Covered Manholes', 'covers_installed', 'bi-check-circle', 'green'),
                $m('Pending Manholes', 'pending_manholes', 'bi-hourglass-split', 'yellow'),
                $m('Safety Compliance %', 'compliance_rate', 'bi-percent', 'green'),
            ],
            'functional-and-clean-water-filtration-plants' => [
                $m('Total Plants', 'total_plants', 'bi-droplet', 'blue'),
                $m('Plants to Inspect', 'plants_to_inspect', 'bi-list-check', 'blue'),
                $m('Inspected Plants', 'inspected_plants', 'bi-clipboard2-check', 'blue'),
                $m('Functional Plants', 'functional_plants', 'bi-check-circle', 'green'),
                $m('Non-Functional Plants', 'non_functional_plants', 'bi-x-circle', 'red'),
                $m('RO Filter Changed', 'ro_filter_changed', 'bi-arrow-repeat', 'green'),
                $m('RO Filter Pending', 'ro_filter_pending', 'bi-hourglass-split', 'yellow'),
                $m('Clean Plants', 'clean_plants', 'bi-stars', 'green'),
            ],
            'inspection-of-educational-institutions' => [
                $m('DC Visits', 'dc_visits', 'bi-person-badge', 'blue'),
                $m('AC Visits', 'ac_visits', 'bi-person-check', 'blue'),
                $m('Total Required Visits', 'required_visits', 'bi-bullseye', 'purple'),
                $m('Inspection Reports Submitted', 'institution_visits', 'bi-file-earmark-text', 'green'),
                $m('School Council Meeting', 'school_council_meeting', 'bi-people', 'blue'),
                $m('Facilities Issues', 'facilities_issues', 'bi-exclamation-triangle', 'yellow'),
                $m('Compliance %', 'compliance_rate', 'bi-percent', 'green'),
            ],
            'inspection-of-health-facilities' => [
                $m('DC Visits', 'dc_visits', 'bi-person-badge', 'blue'),
                $m('AC Visits', 'ac_visits', 'bi-person-check', 'blue'),
                $m('Total Required Visits', 'required_visits', 'bi-bullseye', 'purple'),
                $m('Inspection Reports Submitted', 'facility_visits', 'bi-file-earmark-text', 'green'),
                $m('Health Council Meeting', 'health_council_meeting', 'bi-people', 'blue'),
                $m('Special Branch Points', 'sb_points', 'bi-flag', 'blue'),
                $m('Resolved Points', 'issues_resolved', 'bi-check-circle', 'green'),
                $m('Compliance %', 'compliance_rate', 'bi-percent', 'green'),
            ],
            'violation-of-marriage-functions-act' => [
                $m('Marriage Hall Inspections', 'marriage_hall_inspections', 'bi-building', 'blue'),
                $m('Total Halls', 'total_halls', 'bi-grid', 'blue'),
                $m('Violations Reported', 'violations_detected', 'bi-exclamation-triangle', 'red'),
                $m('Actions Taken', 'actions_taken', 'bi-gavel', 'yellow'),
                $m('Fines / Notices', 'notices_fines', 'bi-currency-rupee', 'yellow'),
                $m('Compliance %', 'compliance_rate', 'bi-percent', 'green'),
            ],
            'anti-encroachment-campaign' => [
                $m('Markets Cleared', 'encroachments_removed', 'bi-shop', 'green'),
                $m('Daily Market Target', 'daily_market_target', 'bi-bullseye', 'purple'),
                $m('Special Branch Points', 'sb_points', 'bi-flag', 'blue'),
                $m('Resolved Points', 'resolved_points', 'bi-check-circle', 'green'),
                $m('Pending Encroachments', 'pending_encroachments', 'bi-hourglass-split', 'yellow'),
                $m('Compliance %', 'compliance_rate', 'bi-percent', 'green'),
            ],
            'regulation-of-shops-and-handcarts' => [
                $m('Markets Inspected', 'markets_inspected', 'bi-shop', 'blue'),
                $m('Shops / Handcarts Regulated', 'shops_regulated', 'bi-check-circle', 'green'),
                $m('Violations Found', 'violations_found', 'bi-exclamation-triangle', 'red'),
                $m('Actions Taken', 'actions_taken', 'bi-gavel', 'yellow'),
                $m('Compliance %', 'compliance_rate', 'bi-percent', 'green'),
            ],
            'stray-dogs' => [
                $m('UC Activities', 'uc_activities', 'bi-geo-alt', 'blue'),
                $m('Target UCs', 'target_ucs', 'bi-bullseye', 'purple'),
                $m('Complaints Verified', 'complaints_verified', 'bi-check2-square', 'blue'),
                $m('Special Branch Points', 'sb_points', 'bi-flag', 'blue'),
                $m('Actions Completed', 'actions_taken', 'bi-check-circle', 'green'),
                $m('Follow-up Required', 'followup_required', 'bi-hourglass-split', 'yellow'),
            ],
            'removal-of-wall-chalking' => [
                $m('UCs Inspected', 'ucs_inspected', 'bi-geo-alt', 'blue'),
                $m('Sites Identified', 'sites_identified', 'bi-search', 'blue'),
                $m('Removal Completed', 'removal_done', 'bi-brush', 'green'),
                $m('Special Branch Points', 'sb_points', 'bi-flag', 'blue'),
                $m('Resolved Points', 'resolved_points', 'bi-check-circle', 'green'),
                $m('Compliance %', 'compliance_rate', 'bi-percent', 'green'),
            ],
            'graveyards' => [
                $m('Graveyards Cleared', 'graveyards_cleared', 'bi-check-circle', 'green'),
                $m('Weekly Target', 'weekly_target', 'bi-bullseye', 'purple'),
                $m('Boundary Wall Issues', 'boundary_wall_issues', 'bi-bricks', 'yellow'),
                $m('Encroachment Removed', 'encroachment_removed', 'bi-sign-stop', 'green'),
                $m('Bushes Removed', 'bushes_removed', 'bi-tree', 'green'),
                $m('Special Branch Points', 'sb_points', 'bi-flag', 'blue'),
            ],
            'e-biz' => [
                $m('Applications Pending', 'pending_applications', 'bi-hourglass-split', 'yellow'),
                $m('Applications Completed', 'applications_completed', 'bi-check-circle', 'green'),
                $m('Help Desk Inspections', 'help_desk_inspections', 'bi-headset', 'blue'),
                $m('DC Meeting Held', 'dc_meeting_held', 'bi-people', 'blue'),
                $m('Disposal %', 'disposal_rate', 'bi-percent', 'green'),
            ],
            'zebra-crossings' => [
                $m('Schools to Inspect', 'schools_to_inspect', 'bi-mortarboard', 'blue'),
                $m('Schools Inspected', 'schools_inspected', 'bi-clipboard2-check', 'blue'),
                $m('Zebra Crossings Marked', 'markings_done', 'bi-brush', 'green'),
                $m('Special Branch Points', 'sb_points', 'bi-flag', 'blue'),
                $m('Resolved Points', 'resolved_points', 'bi-check-circle', 'green'),
                $m('Compliance %', 'compliance_rate', 'bi-percent', 'green'),
            ],
            'illegal-decanting' => [
                $m('Sale Points Inspected', 'stations_inspected', 'bi-fuel-pump', 'blue'),
                $m('Violations Found', 'violations_found', 'bi-exclamation-triangle', 'red'),
                $m('Actions Taken', 'actions_taken', 'bi-gavel', 'yellow'),
                $m('Special Branch Points', 'sb_points', 'bi-flag', 'blue'),
                $m('Fines / FIR / Sealed', 'enforcement_actions', 'bi-shield-exclamation', 'red'),
                $m('Compliance %', 'compliance_rate', 'bi-percent', 'green'),
            ],
            'suthra-punjab-campaign' => [
                $m('DC Inspections', 'dc_inspections', 'bi-person-badge', 'blue'),
                $m('AC UC Inspections', 'ac_uc_inspections', 'bi-geo-alt', 'blue'),
                $m('HR Attendance %', 'hr_attendance', 'bi-people', 'blue'),
                $m('Vehicles / Machinery in Field', 'vehicles_in_field', 'bi-truck', 'blue'),
                $m('Containers Placed', 'containers_placed', 'bi-box', 'green'),
                $m('Heaps Cleared', 'heaps_cleared', 'bi-stars', 'green'),
                $m('Special Branch Points', 'sb_points', 'bi-flag', 'blue'),
            ],
            'maintenance-of-greenbelts' => [
                $m('Parks Maintained', 'parks_maintained', 'bi-tree', 'green'),
                $m('Total Parks', 'total_parks', 'bi-grid', 'blue'),
                $m('Greenbelts Maintained', 'greenbelts_maintained', 'bi-flower1', 'green'),
                $m('Beautification Initiative', 'beautification', 'bi-palette', 'blue'),
                $m('Special Branch Points', 'sb_points', 'bi-flag', 'blue'),
                $m('Compliance %', 'compliance_rate', 'bi-percent', 'green'),
            ],
            'maintenance-of-drains-and-sewerage-lines' => [
                $m('UCs Inspected', 'ucs_inspected', 'bi-geo-alt', 'blue'),
                $m('Total UCs with Sewerage', 'total_ucs', 'bi-grid', 'blue'),
                $m('Blockages Reported', 'blockages_reported', 'bi-exclamation-triangle', 'red'),
                $m('Blockages Cleared', 'blockages_cleared', 'bi-check-circle', 'green'),
                $m('Stagnant Water Points', 'stagnant_water', 'bi-droplet', 'yellow'),
                $m('Compliance %', 'compliance_rate', 'bi-percent', 'green'),
            ],
            'bus-terminals' => [
                $m('AC Visits', 'ac_visits', 'bi-person-check', 'blue'),
                $m('Required Visits', 'required_visits', 'bi-bullseye', 'purple'),
                $m('Fare Display Checked', 'fare_display_checked', 'bi-cash', 'blue'),
                $m('Waiting Area Checked', 'waiting_area_checked', 'bi-bench', 'blue'),
                $m('Water / Washroom Checked', 'facilities_checked', 'bi-droplet', 'blue'),
                $m('Cleanliness Checked', 'cleanliness_checked', 'bi-stars', 'green'),
                $m('Special Branch Points', 'sb_points', 'bi-flag', 'blue'),
            ],
            'chief-ministers-complaint-cell' => [
                $m('Complaints Received', 'complaints_received', 'bi-inbox', 'blue'),
                $m('Complaints Resolved', 'complaints_resolved', 'bi-check-circle', 'green'),
                $m('Pending Complaints', 'pending_complaints', 'bi-hourglass-split', 'yellow'),
                $m('Resolution %', 'resolution_rate', 'bi-percent', 'green'),
                $m('Overdue Complaints', 'overdue_complaints', 'bi-exclamation-triangle', 'red'),
                $m('Follow-ups', 'followups', 'bi-arrow-repeat', 'blue'),
            ],
            default => [],
        };
    }

    public function syncToCardMetricConfig(string $slug): array
    {
        return $this->cardsFor($slug);
    }
}
