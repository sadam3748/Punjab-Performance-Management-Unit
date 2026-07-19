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
            ['Price of Roti', 'price-of-roti', 'Price Control', 10, 'monthly', 'Price of Roti.png'],
            ['Price of Bakery Bread', 'price-of-plain-bakery-bread', 'Price Control', 5, 'monthly', 'Price of Bakery Bread.png'],
            ['Price Control of Essential Commodities', 'price-control-of-essential-commodities', 'Price Control', 10, 'monthly'],
            ['Repair/Patchwork of Small Roads/Lane Marking', 'repair-of-small-roads-in-both-urban-and-rural-areas', 'Infrastructure', 3, 'monthly', 'Patchwork of Small Roads.png'],
            ['Zebra Crossing in Front of Schools', 'zebra-crossings', 'Infrastructure', 2, 'monthly'],
            ['Dysfunctional Streetlights', 'dysfunctional-streetlights', 'Infrastructure', 5, 'monthly'],
            ['Covering of Manholes', 'covering-of-manholes', 'Infrastructure', 5, 'monthly'],
            ['Functional and Clean Water Filtration Plants', 'functional-and-clean-water-filtration-plants', 'Public Services', 5, 'weekly'],
            ['Inspection of Educational Institutions', 'inspection-of-educational-institutions', 'Social Sector', 5, 'monthly'],
            ['Inspection of Health Facilities', 'inspection-of-health-facilities', 'Social Sector', 5, 'monthly'],
            ['Marriage Functions Act Compliance', 'violation-of-marriage-functions-act', 'Governance', 3, 'monthly'],
            ['Anti-Encroachment Campaign', 'anti-encroachment-campaign', 'Governance', 5, 'weekly'],
            ['Stray Dogs', 'stray-dogs', 'Governance', 5, 'monthly'],
            ['Removal of Wall-Chalking', 'removal-of-wall-chalking', 'Governance', 3, 'monthly'],
            ['Graveyards Standards', 'graveyards', 'Governance', 3, 'monthly'],
            ['Illegal Decanting', 'illegal-decanting', 'Governance', 3, 'monthly'],
            ['Suthra Punjab Campaign', 'suthra-punjab-campaign', 'Municipal Services', 5, 'weekly'],
            ['Maintenance of Greenbelts and Family Parks', 'maintenance-of-greenbelts', 'Municipal Services', 3, 'monthly'],
            ['Maintenance of Drains and Sewerage Lines', 'maintenance-of-drains-and-sewerage-lines', 'Municipal Services', 3, 'monthly'],
            ['Bus Terminals', 'bus-terminals', 'Infrastructure', 3, 'monthly'],
            ["Chief Minister's Complaint Cell", 'chief-ministers-complaint-cell', 'Citizen Services', 3, 'monthly'],
            ['Management of Rehri Bazar and Cart Bazar', 'regulation-of-shops-and-handcarts', 'Governance', 3, 'monthly'],
            ['E-Biz', 'e-biz', 'Citizen Services', 3, 'monthly'],
            ['Land Management Services', 'land-management-services', 'Citizen Services', 3, 'monthly'],
        ];

        $activeSlugs = [];

        foreach ($cards as $index => $card) {
            [$title, $slug, $category, $marks, $frequency] = $card;
            $imageFile = $card[5] ?? $title.'.png';
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
                    'image_path' => 'images/kpi-images/'.$imageFile,
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
