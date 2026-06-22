<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KpiCardSeeder extends Seeder
{
    public function run(): void
    {
        $cards = [
            ['Price of Roti', 'price-of-roti', 'Price Control', 'price-of-roti.svg', 10, 'monthly', [
                ['Shops Inspected', 'shops_inspected'],
                ['Compliant Shops', 'compliant_shops'],
                ['Violations Found', 'violations_found'],
                ['Fines Imposed', 'fines_imposed'],
            ]],
            ['Price of Plain Bakery Bread', 'price-of-bakery-bread', 'Price Control', 'price-of-bakery-bread.svg', 5, 'monthly', [
                ['Bakeries Inspected', 'bakeries_inspected'],
                ['Compliant Bakeries', 'compliant_bakeries'],
                ['Price Violations', 'price_violations'],
                ['Actions Taken', 'actions_taken'],
            ]],
            ['Price Control of Essential Commodities', 'price-control', 'Price Control', 'price-control.svg', 10, 'monthly', [
                ['Total Inspections', 'total_inspections'],
                ['Fines Imposed', 'fines_imposed'],
                ['FIRs Registered', 'firs_registered'],
                ['Shops Sealed', 'shops_sealed'],
            ]],
            ['Repair of Small Roads', 'road-repair', 'Infrastructure', 'road-repair.svg', 3, 'monthly', [
                ['Roads Identified', 'roads_identified'],
                ['Roads Repaired', 'roads_repaired'],
                ['Pending Repairs', 'pending_repairs'],
                ['Funds Utilized (M)', 'funds_utilized'],
            ]],
            ['Zebra Crossings', 'zebra-crossings', 'Infrastructure', 'zebra-crossings.svg', 2, 'monthly', [
                ['Crossings Required', 'crossings_required'],
                ['Crossings Painted', 'crossings_painted'],
                ['Pending Crossings', 'pending_crossings'],
                ['Compliance Rate', 'compliance_rate'],
            ]],
            ['Dysfunctional Streetlights', 'streetlights', 'Infrastructure', 'streetlights.svg', 5, 'monthly', [
                ['Total Streetlights', 'total_streetlights'],
                ['Functional Lights', 'functional_lights'],
                ['Non-functional Lights', 'non_functional_lights'],
                ['Repaired This Period', 'repaired'],
            ]],
            ['Covering of Manholes', 'manholes', 'Infrastructure', 'manholes.svg', 5, 'monthly', [
                ['Manholes Identified', 'manholes_identified'],
                ['Manholes Covered', 'manholes_covered'],
                ['Open Manholes', 'open_manholes'],
                ['Compliance Rate', 'compliance_rate'],
            ]],
            ['Functional And Clean Water Filtration Plants', 'water-filtration', 'Public Services', 'water-filtration.svg', 5, 'weekly', [
                ['Total Plants', 'total_plants'],
                ['Functional Plants', 'functional_plants'],
                ['Non-functional Plants', 'non_functional_plants'],
                ['Inspected Plants', 'inspected_plants'],
                ['Clean Plants', 'clean_plants'],
                ['Unclean Plants', 'unclean_plants'],
            ]],
            ['Inspection of Educational Institutions', 'education', 'Social Sector', 'education.svg', 5, 'monthly', [
                ['Total Schools', 'total_schools'],
                ['Inspected Schools', 'inspected_schools'],
                ['Attendance Percentage', 'attendance_percentage'],
                ['Missing Facilities', 'missing_facilities'],
                ['Compliance Percentage', 'compliance_percentage'],
            ]],
            ['Inspection of Health Facilities', 'health', 'Social Sector', 'health.svg', 5, 'monthly', [
                ['Total Facilities', 'total_facilities'],
                ['Inspected Facilities', 'inspected_facilities'],
                ['Functional Facilities', 'functional_facilities'],
                ['Non-functional Facilities', 'non_functional_facilities'],
                ['Missing Facilities', 'missing_facilities'],
            ]],
            ['Violation of Marriage Functions Act', 'marriage-act', 'Governance', 'marriage-act.svg', 3, 'monthly', [
                ['Functions Monitored', 'functions_monitored'],
                ['Violations Detected', 'violations_detected'],
                ['Actions Taken', 'actions_taken'],
                ['Pending Cases', 'pending_cases'],
            ]],
            ['Anti-Encroachment Campaign', 'anti-encroachment', 'Governance', 'anti-encroachment.svg', 5, 'weekly', [
                ['Encroachments Identified', 'identified'],
                ['Encroachments Removed', 'removed'],
                ['Area Cleared (acres)', 'area_cleared'],
                ['Pending Cases', 'pending_cases'],
            ]],
            ['Stray Dogs', 'stray-dogs', 'Governance', 'stray-dogs.svg', 5, 'monthly', [
                ['Dogs Reported', 'dogs_reported'],
                ['Dogs Vaccinated', 'dogs_vaccinated'],
                ['Dogs Relocated', 'dogs_relocated'],
                ['Pending Cases', 'pending_cases'],
            ]],
            ['Removal of Wall Chalking', 'wall-chalking', 'Governance', 'wall-chalking.svg', 3, 'monthly', [
                ['Walls Identified', 'walls_identified'],
                ['Walls Cleaned', 'walls_cleaned'],
                ['Pending Walls', 'pending_walls'],
                ['Compliance Rate', 'compliance_rate'],
            ]],
            ['Graveyards', 'graveyards', 'Governance', 'graveyards.svg', 3, 'monthly', [
                ['Graveyards Inspected', 'graveyards_inspected'],
                ['Clean Graveyards', 'clean_graveyards'],
                ['Unclean Graveyards', 'unclean_graveyards'],
                ['Improvements Made', 'improvements_made'],
            ]],
            ['Illegal Decanting', 'illegal-decanting', 'Governance', 'illegal-decanting.svg', 3, 'monthly', [
                ['Stations Inspected', 'stations_inspected'],
                ['Violations Found', 'violations_found'],
                ['Sealed Stations', 'sealed_stations'],
                ['Pending Cases', 'pending_cases'],
            ]],
            ['Suthra Punjab Campaign', 'cleanliness', 'Municipal Services', 'cleanliness.svg', 5, 'weekly', [
                ['Total Inspections', 'total_inspections'],
                ['Clean Areas', 'clean_areas'],
                ['Unclean Areas', 'unclean_areas'],
                ['Waste Points Cleared', 'waste_points_cleared'],
                ['Pending Points', 'pending_points'],
            ]],
            ['Maintenance of Greenbelts', 'greenbelts', 'Municipal Services', 'greenbelts.svg', 3, 'monthly', [
                ['Greenbelts Identified', 'greenbelts_identified'],
                ['Maintained Greenbelts', 'maintained_greenbelts'],
                ['Neglected Greenbelts', 'neglected_greenbelts'],
                ['Plants Planted', 'plants_planted'],
            ]],
            ['Maintenance of Drains and Sewerage Lines', 'drains-sewerage', 'Municipal Services', 'drains-sewerage.svg', 3, 'monthly', [
                ['Drains Identified', 'drains_identified'],
                ['Drains Cleared', 'drains_cleared'],
                ['Blocked Drains', 'blocked_drains'],
                ['Compliance Rate', 'compliance_rate'],
            ]],
            ['Bus Terminals', 'bus-terminals', 'Infrastructure', 'bus-terminals.svg', 3, 'monthly', [
                ['Terminals Inspected', 'terminals_inspected'],
                ['Clean Terminals', 'clean_terminals'],
                ['Unclean Terminals', 'unclean_terminals'],
                ['Improvements Made', 'improvements_made'],
            ]],
            ['Chief Minister\'s Complaint Cell', 'complaint-management', 'Citizen Services', 'complaint-management.svg', 3, 'monthly', [
                ['Total Complaints', 'total_complaints'],
                ['Resolved Complaints', 'resolved'],
                ['Pending Complaints', 'pending'],
                ['Overdue Complaints', 'overdue'],
            ]],
            ['Regulation of Shops and Handcarts', 'shops-handcarts', 'Governance', 'shops-handcarts.svg', 3, 'monthly', [
                ['Shops Inspected', 'shops_inspected'],
                ['Compliant Shops', 'compliant_shops'],
                ['Violations Found', 'violations_found'],
                ['Actions Taken', 'actions_taken'],
            ]],
            ['E-Biz', 'e-biz', 'Citizen Services', 'e-biz.svg', 3, 'monthly', [
                ['Services Online', 'services_online'],
                ['Applications Received', 'applications_received'],
                ['Applications Processed', 'applications_processed'],
                ['Pending Applications', 'pending_applications'],
            ]],
        ];

        $activeSlugs = [];

        foreach ($cards as $index => [$title, $slug, $category, $icon, $marks, $frequency, $metrics]) {
            $activeSlugs[] = $slug;

            DB::table('kpi_cards')->updateOrInsert(['slug' => $slug], [
                'title' => $title,
                'category' => $category,
                'description' => $title.' performance, reporting and compliance monitoring across Punjab.',
                'icon' => $icon,
                'frequency' => $frequency,
                'total_marks' => $marks,
                'is_active' => true,
                'display_order' => $index + 1,
                'metric_config' => json_encode(array_map(fn ($metric) => [
                    'label' => $metric[0],
                    'field' => $metric[1],
                    'icon' => 'bi-bar-chart',
                ], $metrics)),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('kpi_cards')
            ->whereNotIn('slug', $activeSlugs)
            ->update(['is_active' => false, 'updated_at' => now()]);
    }
}
