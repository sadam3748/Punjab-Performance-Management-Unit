<?php

namespace App\Data;

class KpiDashboardDefinitions
{
    private const SLUG_ALIASES = [
        'repair-of-small-roads' => 'repair-of-small-roads-in-both-urban-and-rural-areas',
    ];

    public static function normalizeSlug(string $slug): string
    {
        return self::SLUG_ALIASES[$slug] ?? $slug;
    }

    /** @return list<string> */
    public static function slugs(): array
    {
        return [
            'price-of-roti',
            'price-of-plain-bakery-bread',
            'price-control-of-essential-commodities',
            'repair-of-small-roads-in-both-urban-and-rural-areas',
            'zebra-crossings',
            'dysfunctional-streetlights',
            'covering-of-manholes',
            'functional-and-clean-water-filtration-plants',
            'inspection-of-educational-institutions',
            'inspection-of-health-facilities',
            'violation-of-marriage-functions-act',
            'anti-encroachment-campaign',
            'stray-dogs',
            'removal-of-wall-chalking',
            'graveyards',
            'illegal-decanting',
            'suthra-punjab-campaign',
            'maintenance-of-greenbelts',
            'maintenance-of-drains-and-sewerage-lines',
            'bus-terminals',
            'chief-ministers-complaint-cell',
            'regulation-of-shops-and-handcarts',
            'e-biz',
        ];
    }

    /**
     * @return array{
     *     table_columns: list<array{label: string, field: string, from: 'entity'|'detail_data'|'address'|'inspector'}>,
     *     charts: list<array{type: 'line'|'bar'|'donut'|'pie'|'gauge', title: string, key: string}>,
     *     detail_fields: list<array{label: string, field: string}>
     * }
     */
    public static function config(string $slug): array
    {
        $slug = self::normalizeSlug($slug);

        $tableColumns = match ($slug) {
            'price-of-roti' => [
                self::column('Tandoor / Shop / Hotel Name', 'entity_name', 'entity'),
                self::column('Address', 'address', 'address'),
                self::column('Violation', 'violation'),
                self::column('Fine', 'fine'),
            ],
            'price-of-plain-bakery-bread' => [
                self::column('Outlet/Store/Shop/Bakery Name', 'entity_name', 'entity'),
                self::column('Address', 'address', 'address'),
                self::column('Violation', 'violation'),
                self::column('Fine', 'fine'),
            ],
            'price-control-of-essential-commodities' => [
                self::column('Shop/Store Name', 'entity_name', 'entity'),
                self::column('Commodity', 'commodity'),
                self::column('Violation', 'violation'),
                self::column('Fine', 'fine'),
            ],
            'repair-of-small-roads-in-both-urban-and-rural-areas' => [
                self::column('Road Name/ID', 'identifier', 'entity'),
                self::column('Location', 'address', 'address'),
                self::column('Repair Type', 'repair_type'),
                self::column('Completion Status', 'completion_status'),
            ],
            'zebra-crossings' => [
                self::column('Institution Name', 'entity_name', 'entity'),
                self::column('Crossing Status', 'crossing_status'),
                self::column('Inspection Status', 'inspection_status'),
            ],
            'dysfunctional-streetlights' => [
                self::column('Road Name', 'entity_name', 'entity'),
                self::column('Total Lights', 'total_lights'),
                self::column('Dysfunctional Lights', 'dysfunctional_lights'),
                self::column('Repaired Lights', 'repaired_lights'),
            ],
            'covering-of-manholes' => [
                self::column('UC/MC Name', 'entity_name', 'entity'),
                self::column('Open Manholes', 'open_manholes'),
                self::column('Covered Manholes', 'covered_manholes'),
            ],
            'functional-and-clean-water-filtration-plants' => [
                self::column('Plant Name', 'entity_name', 'entity'),
                self::column('Type', 'entity_type', 'entity'),
                self::column('Inspected', 'inspected'),
            ],
            'inspection-of-educational-institutions' => [
                self::column('Institution Name', 'entity_name', 'entity'),
                self::column('Cleanliness', 'cleanliness'),
                self::column('Teachers Present', 'teachers_present'),
                self::column('School Council Activated', 'school_council_activated'),
                self::column('Facility Deficiency', 'facility_deficiency'),
            ],
            'inspection-of-health-facilities' => [
                self::column('Facility Name', 'entity_name', 'entity'),
                self::column('Type', 'entity_type', 'entity'),
                self::column('Cleanliness', 'cleanliness'),
                self::column('Staff Present', 'staff_present'),
                self::column('Medicines OK', 'medicines_ok'),
            ],
            'violation-of-marriage-functions-act' => [
                self::column('Hall Name', 'entity_name', 'entity'),
                self::column('Violation', 'violation'),
                self::column('Fine', 'fine'),
            ],
            'anti-encroachment-campaign' => [
                self::column('Market/Location', 'entity_name', 'entity'),
                self::column('Encroachment Points', 'encroachment_points'),
                self::column('Cleared Points', 'cleared_points'),
            ],
            'stray-dogs' => [
                self::column('UC Name', 'entity_name', 'entity'),
                self::column('Activity Conducted', 'activity_conducted'),
                self::column('Team Name', 'team_name'),
            ],
            'removal-of-wall-chalking' => [
                self::column('UC/MC Name', 'entity_name', 'entity'),
                self::column('Spots Identified', 'spots_identified'),
                self::column('Spots Cleared', 'spots_cleared'),
                self::column('Banners Removed', 'banners_removed'),
            ],
            'graveyards' => [
                self::column('Graveyard Name', 'entity_name', 'entity'),
                self::column('Demarcated', 'demarcated'),
                self::column('Encroachment Removed', 'encroachment_removed'),
                self::column('Cleaned', 'cleaned'),
            ],
            'illegal-decanting' => [
                self::column('Outlet/Shop Name', 'entity_name', 'entity'),
                self::column('Violation', 'violation'),
                self::column('Action Type', 'action_type'),
                self::column('Fine', 'fine'),
            ],
            'suthra-punjab-campaign' => [
                self::column('UC Name', 'entity_name', 'entity'),
                self::column('DC Inspected', 'dc_inspected'),
                self::column('AC Inspected', 'ac_inspected'),
                self::column('Cleanliness Status', 'cleanliness_status'),
            ],
            'maintenance-of-greenbelts' => [
                self::column('Park/Greenbelt Name', 'entity_name', 'entity'),
                self::column('Type', 'entity_type', 'entity'),
                self::column('Maintenance Status', 'maintenance_status'),
                self::column('DC Initiative', 'dc_initiative'),
            ],
            'maintenance-of-drains-and-sewerage-lines' => [
                self::column('UC/MC Name', 'entity_name', 'entity'),
                self::column('Blockage Identified', 'blockage_identified'),
                self::column('Stagnant Water', 'stagnant_water'),
                self::column('Cleaned Status', 'cleaned_status'),
            ],
            'bus-terminals' => [
                self::column('Terminal Name', 'entity_name', 'entity'),
            ],
            'chief-ministers-complaint-cell' => [
                self::column('Complaint Reference', 'identifier', 'entity'),
                self::column('Complaint Status', 'complaint_status'),
                self::column('Resolution Days', 'resolution_days'),
                self::column('Overdue Status', 'overdue_status'),
            ],
            'regulation-of-shops-and-handcarts' => [
                self::column('Market Name', 'entity_name', 'entity'),
                self::column('Shops Checked', 'shops_checked'),
                self::column('Handcarts Checked', 'handcarts_checked'),
                self::column('Violations Found', 'violations_found'),
            ],
            'e-biz' => [
                self::column('Office/Help Desk', 'entity_name', 'entity'),
                self::column('Application No', 'identifier', 'entity'),
                self::column('Service Type', 'service_type'),
                self::column('Applications Reviewed', 'applications_reviewed'),
                self::column('Pending Cases', 'pending_cases'),
                self::column('Timeline Compliance', 'timeline_compliance'),
            ],
            default => [],
        };

        $charts = match ($slug) {
            'price-of-roti' => [
                self::chart('line', 'Daily Inspections Trend', 'daily_inspections_trend'),
                self::chart('donut', 'Violation Type Breakdown', 'violation_type_breakdown'),
                self::chart('gauge', 'Fine-to-Inspection Ratio', 'fine_to_inspection_ratio'),
                self::chart('bar', 'Tehsil Comparison', 'tehsil_comparison'),
                self::chart('bar', 'District Comparison', 'district_comparison'),
            ],
            'price-of-plain-bakery-bread' => [
                self::chart('line', 'Daily Inspections Trend', 'daily_inspections_trend'),
                self::chart('donut', 'Violation Type Breakdown', 'violation_type_breakdown'),
                self::chart('gauge', 'Fine-to-Inspection Ratio', 'fine_to_inspection_ratio'),
                self::chart('bar', 'Tehsil Comparison', 'tehsil_comparison'),
                self::chart('bar', 'District Comparison', 'district_comparison'),
            ],
            'price-control-of-essential-commodities' => [
                self::chart('line', 'Inspection Activity Trend', 'inspection_activity_trend'),
                self::chart('donut', 'Commodity Violation Breakdown', 'commodity_violation_breakdown'),
                self::chart('gauge', 'Compliance Rate', 'compliance_rate'),
                self::chart('bar', 'Tehsil Comparison', 'tehsil_comparison'),
                self::chart('bar', 'District Comparison', 'district_comparison'),
            ],
            'repair-of-small-roads-in-both-urban-and-rural-areas' => [
                self::chart('line', 'Road Repairs Trend', 'road_repairs_trend'),
                self::chart('donut', 'Repair Type Breakdown', 'repair_type_breakdown'),
                self::chart('gauge', 'Completion Rate', 'completion_rate'),
                self::chart('bar', 'Tehsil Comparison', 'tehsil_comparison'),
                self::chart('bar', 'District Comparison', 'district_comparison'),
            ],
            'zebra-crossings' => [
                self::chart('line', 'School Inspections Trend', 'school_inspections_trend'),
                self::chart('donut', 'Crossing Status Breakdown', 'crossing_status_breakdown'),
                self::chart('gauge', 'Marking Compliance', 'marking_compliance'),
                self::chart('bar', 'Tehsil Comparison', 'tehsil_comparison'),
            ],
            'dysfunctional-streetlights' => [
                self::chart('line', 'Repairs Trend', 'repairs_trend'),
                self::chart('donut', 'Light Status Breakdown', 'light_status_breakdown'),
                self::chart('gauge', 'Functional Rate', 'functional_rate'),
                self::chart('bar', 'Tehsil Comparison', 'tehsil_comparison'),
                self::chart('bar', 'District Comparison', 'district_comparison'),
            ],
            'covering-of-manholes' => [
                self::chart('line', 'Manhole Coverage Trend', 'manhole_coverage_trend'),
                self::chart('donut', 'Open vs Covered Manholes', 'manhole_status_breakdown'),
                self::chart('gauge', 'Safety Compliance', 'safety_compliance'),
                self::chart('bar', 'Tehsil Comparison', 'tehsil_comparison'),
            ],
            'functional-and-clean-water-filtration-plants' => [
                self::chart('donut', 'Plant Status — Functional / Non-Functional / Blocked', 'plant_status_breakdown'),
                self::chart('gauge', 'RO Filter Change Compliance', 'filter_change_compliance'),
                self::chart('bar', 'Clean vs. Unclean', 'clean_vs_unclean'),
            ],
            'inspection-of-educational-institutions' => [
                self::chart('line', 'Institution Visits Trend', 'institution_visits_trend'),
                self::chart('donut', 'Cleanliness Status Breakdown', 'cleanliness_status_breakdown'),
                self::chart('gauge', 'School Council Activation', 'school_council_activation'),
                self::chart('bar', 'Tehsil Comparison', 'tehsil_comparison'),
                self::chart('bar', 'Facility Deficiency Breakdown', 'facility_deficiency_breakdown'),
            ],
            'inspection-of-health-facilities' => [
                self::chart('bar', 'DC vs AC Visit Completion', 'dc_ac_visit_completion'),
                self::chart('donut', 'Issue Category Breakdown', 'health_issue_breakdown'),
                self::chart('bar', 'Tehsil Comparison', 'tehsil_comparison'),
                self::chart('bar', 'District Comparison', 'district_comparison'),
            ],
            'violation-of-marriage-functions-act' => [
                self::chart('line', 'Hall Inspections Trend', 'hall_inspections_trend'),
                self::chart('donut', 'Violation Type Breakdown', 'violation_type_breakdown'),
                self::chart('gauge', 'Compliance Rate', 'compliance_rate'),
                self::chart('bar', 'Tehsil Comparison', 'tehsil_comparison'),
            ],
            'anti-encroachment-campaign' => [
                self::chart('line', 'Encroachment Clearance Trend', 'encroachment_clearance_trend'),
                self::chart('donut', 'Cleared vs Pending Points', 'encroachment_status_breakdown'),
                self::chart('gauge', 'Clearance Rate', 'clearance_rate'),
                self::chart('bar', 'Market Comparison', 'market_comparison'),
                self::chart('bar', 'Tehsil Comparison', 'tehsil_comparison'),
            ],
            'stray-dogs' => [
                self::chart('line', 'UC Activity Trend', 'uc_activity_trend'),
                self::chart('donut', 'Activity Type Breakdown', 'activity_type_breakdown'),
                self::chart('bar', 'Team Performance', 'team_performance'),
                self::chart('bar', 'Tehsil Comparison', 'tehsil_comparison'),
            ],
            'removal-of-wall-chalking' => [
                self::chart('line', 'Removal Activity Trend', 'removal_activity_trend'),
                self::chart('donut', 'Spots Cleared vs Identified', 'spot_status_breakdown'),
                self::chart('gauge', 'Clearance Rate', 'clearance_rate'),
                self::chart('bar', 'Tehsil Comparison', 'tehsil_comparison'),
            ],
            'graveyards' => [
                self::chart('line', 'Graveyard Maintenance Trend', 'graveyard_maintenance_trend'),
                self::chart('donut', 'Maintenance Status Breakdown', 'maintenance_status_breakdown'),
                self::chart('gauge', 'Demarcation Compliance', 'demarcation_compliance'),
                self::chart('bar', 'Tehsil Comparison', 'tehsil_comparison'),
            ],
            'illegal-decanting' => [
                self::chart('line', 'Inspection Activity Trend', 'inspection_activity_trend'),
                self::chart('donut', 'Violation Type Breakdown', 'violation_type_breakdown'),
                self::chart('gauge', 'Enforcement Rate', 'enforcement_rate'),
                self::chart('bar', 'Action Type Breakdown', 'action_type_breakdown'),
                self::chart('bar', 'Tehsil Comparison', 'tehsil_comparison'),
            ],
            'suthra-punjab-campaign' => [
                self::chart('line', 'Inspection Activity Trend', 'inspection_activity_trend'),
                self::chart('donut', 'Cleanliness Status Breakdown', 'cleanliness_status_breakdown'),
                self::chart('bar', 'DC vs AC Inspections', 'dc_ac_inspection_comparison'),
                self::chart('gauge', 'Campaign Compliance', 'campaign_compliance'),
                self::chart('bar', 'Tehsil Comparison', 'tehsil_comparison'),
            ],
            'maintenance-of-greenbelts' => [
                self::chart('line', 'Maintenance Activity Trend', 'maintenance_activity_trend'),
                self::chart('donut', 'Greenbelt Type Breakdown', 'greenbelt_type_breakdown'),
                self::chart('gauge', 'Maintenance Compliance', 'maintenance_compliance'),
                self::chart('bar', 'DC Initiative Impact', 'dc_initiative_impact'),
            ],
            'maintenance-of-drains-and-sewerage-lines' => [
                self::chart('line', 'Drain Cleaning Trend', 'drain_cleaning_trend'),
                self::chart('donut', 'Blockage Status Breakdown', 'blockage_status_breakdown'),
                self::chart('gauge', 'Cleaned Status Rate', 'cleaned_status_rate'),
                self::chart('bar', 'Stagnant Water Points', 'stagnant_water_points'),
                self::chart('bar', 'Tehsil Comparison', 'tehsil_comparison'),
            ],
            'bus-terminals' => [
                self::chart('line', 'Terminal Inspections Trend', 'terminal_inspections_trend'),
                self::chart('donut', 'Facility Compliance Breakdown', 'facility_compliance_breakdown'),
                self::chart('gauge', 'Overall Terminal Score', 'overall_terminal_score'),
                self::chart('bar', 'Facility Check Comparison', 'facility_check_comparison'),
            ],
            'chief-ministers-complaint-cell' => [
                self::chart('line', 'Received vs. Resolved Trend', 'received_resolved_trend'),
                self::chart('gauge', 'Resolution Rate Gauge', 'resolution_rate'),
                self::chart('bar', 'District-wise Complaint Load', 'district_complaint_load'),
                self::chart('bar', 'Overdue Complaints by Age Bucket', 'overdue_complaints_age'),
            ],
            'regulation-of-shops-and-handcarts' => [
                self::chart('line', 'Market Inspections Trend', 'market_inspections_trend'),
                self::chart('donut', 'Violation Breakdown', 'violation_breakdown'),
                self::chart('bar', 'Shops vs Handcarts Checked', 'shops_handcarts_comparison'),
                self::chart('gauge', 'Compliance Rate', 'compliance_rate'),
            ],
            'e-biz' => [
                self::chart('line', 'Application Processing Trend', 'application_processing_trend'),
                self::chart('donut', 'Service Type Breakdown', 'service_type_breakdown'),
                self::chart('gauge', 'Timeline Compliance', 'timeline_compliance_rate'),
                self::chart('bar', 'Pending vs Reviewed', 'pending_reviewed_comparison'),
                self::chart('bar', 'District Comparison', 'district_comparison'),
            ],
            default => [],
        };

        $detailFields = self::detailFieldsFromColumns($tableColumns);

        $detailFields = match ($slug) {
            'functional-and-clean-water-filtration-plants' => array_merge($detailFields, [
                ['label' => 'Functional / Non-Functional', 'field' => 'functional_status'],
                ['label' => 'Clean / Unclean', 'field' => 'cleanliness_status'],
                ['label' => 'Filter Change Status', 'field' => 'filter_change_status'],
            ]),
            'inspection-of-health-facilities' => array_merge($detailFields, [
                ['label' => 'Equipment / Utilities', 'field' => 'equipment_status'],
            ]),
            'bus-terminals' => array_merge($detailFields, [
                ['label' => 'Fare Display', 'field' => 'fare_display'],
                ['label' => 'Waiting Area', 'field' => 'waiting_area'],
                ['label' => 'Drinking Water', 'field' => 'drinking_water'],
                ['label' => 'Washroom', 'field' => 'washroom'],
                ['label' => 'Cleanliness', 'field' => 'cleanliness'],
                ['label' => 'Electricity', 'field' => 'electricity'],
            ]),
            default => $detailFields,
        };

        return [
            'table_columns' => $tableColumns,
            'charts' => $charts,
            'detail_fields' => $detailFields,
        ];
    }

    /**
     * @return array{label: string, field: string, from: 'entity'|'detail_data'|'address'|'inspector'}
     */
    private static function column(string $label, string $field, string $from = 'detail_data'): array
    {
        return [
            'label' => $label,
            'field' => $field,
            'from' => $from,
        ];
    }

    /**
     * @return array{type: 'line'|'bar'|'donut'|'pie'|'gauge', title: string, key: string}
     */
    private static function chart(string $type, string $title, string $key): array
    {
        return [
            'type' => $type,
            'title' => $title,
            'key' => $key,
        ];
    }

    /**
     * @param  list<array{label: string, field: string, from: string}>  $columns
     * @return list<array{label: string, field: string}>
     */
    private static function detailFieldsFromColumns(array $columns): array
    {
        return array_map(
            static fn (array $column): array => [
                'label' => $column['label'],
                'field' => $column['field'],
            ],
            $columns
        );
    }
}
