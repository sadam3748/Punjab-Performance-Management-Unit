<?php

namespace Database\Seeders;

use App\Services\KpiMetricConfigService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KpiCardSeeder extends Seeder
{
    public function run(): void
    {
        $metricConfig = app(KpiMetricConfigService::class);

        $cards = [
            ['Price of Roti', 'price-of-roti', 'Price Control', 10, 'monthly'],
            ['Price of Plain Bakery Bread', 'price-of-plain-bakery-bread', 'Price Control', 5, 'monthly'],
            ['Price Control of Essential Commodities', 'price-control-of-essential-commodities', 'Price Control', 10, 'monthly'],
            ['Repair of Small Roads in both Urban and Rural Areas', 'repair-of-small-roads-in-both-urban-and-rural-areas', 'Infrastructure', 3, 'monthly'],
            ['Zebra Crossings', 'zebra-crossings', 'Infrastructure', 2, 'monthly'],
            ['Dysfunctional Streetlights', 'dysfunctional-streetlights', 'Infrastructure', 5, 'monthly'],
            ['Covering of Manholes', 'covering-of-manholes', 'Infrastructure', 5, 'monthly'],
            ['Functional And Clean Water Filtration Plants', 'functional-and-clean-water-filtration-plants', 'Public Services', 5, 'weekly'],
            ['Inspection of Educational Institutions', 'inspection-of-educational-institutions', 'Social Sector', 5, 'monthly'],
            ['Inspection of Health Facilities', 'inspection-of-health-facilities', 'Social Sector', 5, 'monthly'],
            ['Violation of Marriage Functions Act', 'violation-of-marriage-functions-act', 'Governance', 3, 'monthly'],
            ['Anti-Encroachment Campaign', 'anti-encroachment-campaign', 'Governance', 5, 'weekly'],
            ['Stray Dogs', 'stray-dogs', 'Governance', 5, 'monthly'],
            ['Removal of Wall Chalking', 'removal-of-wall-chalking', 'Governance', 3, 'monthly'],
            ['Graveyards', 'graveyards', 'Governance', 3, 'monthly'],
            ['Illegal Decanting', 'illegal-decanting', 'Governance', 3, 'monthly'],
            ['Suthra Punjab Campaign', 'suthra-punjab-campaign', 'Municipal Services', 5, 'weekly'],
            ['Maintenance of Greenbelts', 'maintenance-of-greenbelts', 'Municipal Services', 3, 'monthly'],
            ['Maintenance of Drains and Sewerage Lines', 'maintenance-of-drains-and-sewerage-lines', 'Municipal Services', 3, 'monthly'],
            ['Bus Terminals', 'bus-terminals', 'Infrastructure', 3, 'monthly'],
            ["Chief Minister's Complaint Cell", 'chief-ministers-complaint-cell', 'Citizen Services', 3, 'monthly'],
            ['Regulation of Shops and Handcarts', 'regulation-of-shops-and-handcarts', 'Governance', 3, 'monthly'],
            ['E-Biz', 'e-biz', 'Citizen Services', 3, 'monthly'],
        ];

        $activeSlugs = [];

        foreach ($cards as $index => [$title, $slug, $category, $marks, $frequency]) {
            $activeSlugs[] = $slug;
            $metrics = $metricConfig->cardsFor($slug);

            DB::table('kpi_cards')->updateOrInsert(
                ['display_order' => $index + 1],
                [
                    'title' => $title,
                    'slug' => $slug,
                    'category' => $category,
                    'description' => $title.' — performance, reporting and compliance monitoring across Punjab.',
                    'icon' => $slug.'.svg',
                    'image_path' => 'images/kpi-images/'.$slug.'.png',
                    'frequency' => $frequency,
                    'total_marks' => $marks,
                    'is_active' => true,
                    'display_order' => $index + 1,
                    'metric_config' => json_encode($metrics),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        DB::table('kpi_cards')
            ->whereNotIn('slug', $activeSlugs)
            ->update(['is_active' => false, 'updated_at' => now()]);
    }
}
