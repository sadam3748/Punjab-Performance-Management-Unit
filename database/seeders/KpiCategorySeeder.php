<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KpiCategorySeeder extends Seeder
{
    public function run(): void
    {
        // Latest PPMF / CM Governance Scorecard categories as per PPT dated 29-04-2026.
        // Total initiatives = 23, total marks = 100.
        $categories = [
            ['id' => 1, 'name' => 'Price of Roti', 'slug' => 'price-of-roti', 'scorecard_weightage' => 10, 'is_active' => true],
            ['id' => 2, 'name' => 'Price of Plain Bakery Bread', 'slug' => 'price-of-plain-bakery-bread', 'scorecard_weightage' => 5, 'is_active' => true],
            ['id' => 3, 'name' => 'Price Control of Essential Commodities', 'slug' => 'price-control-of-essential-commodities', 'scorecard_weightage' => 10, 'is_active' => true],
            ['id' => 4, 'name' => 'Repair of Small Roads in both Urban and Rural Areas', 'slug' => 'repair-of-small-roads-in-both-urban-and-rural-areas', 'scorecard_weightage' => 3, 'is_active' => true],
            ['id' => 5, 'name' => 'Zebra Crossings', 'slug' => 'zebra-crossings', 'scorecard_weightage' => 2, 'is_active' => true],
            ['id' => 6, 'name' => 'Dysfunctional Streetlights', 'slug' => 'dysfunctional-streetlights', 'scorecard_weightage' => 5, 'is_active' => true],
            ['id' => 7, 'name' => 'Covering of Manholes', 'slug' => 'covering-of-manholes', 'scorecard_weightage' => 5, 'is_active' => true],
            ['id' => 8, 'name' => 'Functional And Clean Water Filtration Plants', 'slug' => 'functional-and-clean-water-filtration-plants', 'scorecard_weightage' => 5, 'is_active' => true],
            ['id' => 9, 'name' => 'Inspection of Educational Institutions', 'slug' => 'inspection-of-educational-institutions', 'scorecard_weightage' => 5, 'is_active' => true],
            ['id' => 10, 'name' => 'Inspection of Health Facilities', 'slug' => 'inspection-of-health-facilities', 'scorecard_weightage' => 5, 'is_active' => true],
            ['id' => 11, 'name' => 'Violation of Marriage Functions Act', 'slug' => 'violation-of-marriage-functions-act', 'scorecard_weightage' => 3, 'is_active' => true],
            ['id' => 12, 'name' => 'Anti-Encroachment Campaign', 'slug' => 'anti-encroachment-campaign', 'scorecard_weightage' => 5, 'is_active' => true],
            ['id' => 13, 'name' => 'Stray Dogs', 'slug' => 'stray-dogs', 'scorecard_weightage' => 5, 'is_active' => true],
            ['id' => 14, 'name' => 'Removal of Wall Chalking', 'slug' => 'removal-of-wall-chalking', 'scorecard_weightage' => 3, 'is_active' => true],
            ['id' => 15, 'name' => 'Graveyards', 'slug' => 'graveyards', 'scorecard_weightage' => 3, 'is_active' => true],
            ['id' => 16, 'name' => 'Illegal Decanting', 'slug' => 'illegal-decanting', 'scorecard_weightage' => 3, 'is_active' => true],
            ['id' => 17, 'name' => 'Suthra Punjab Campaign', 'slug' => 'suthra-punjab-campaign', 'scorecard_weightage' => 5, 'is_active' => true],
            ['id' => 18, 'name' => 'Maintenance of Greenbelts', 'slug' => 'maintenance-of-greenbelts', 'scorecard_weightage' => 3, 'is_active' => true],
            ['id' => 19, 'name' => 'Maintenance of Drains and Sewerage Lines', 'slug' => 'maintenance-of-drains-and-sewerage-lines', 'scorecard_weightage' => 3, 'is_active' => true],
            ['id' => 20, 'name' => 'Bus Terminals', 'slug' => 'bus-terminals', 'scorecard_weightage' => 3, 'is_active' => true],
            ['id' => 21, 'name' => 'Chief Minister\'s Complaint Cell', 'slug' => 'chief-ministers-complaint-cell', 'scorecard_weightage' => 3, 'is_active' => true],
            ['id' => 22, 'name' => 'Regulation of Shops and Handcarts', 'slug' => 'regulation-of-shops-and-handcarts', 'scorecard_weightage' => 3, 'is_active' => true],
            ['id' => 23, 'name' => 'E-Biz', 'slug' => 'e-biz', 'scorecard_weightage' => 3, 'is_active' => true],
        ];

        $nowTs = now();

        DB::table('kpi_categories')->upsert(
            array_map(function (array $c) use ($nowTs) {
                return [
                    'id' => $c['id'],
                    'name' => $c['name'],
                    'slug' => $c['slug'],
                    'description' => $c['name'],
                    'scorecard_weightage' => (float) $c['scorecard_weightage'],
                    'is_active' => (bool) $c['is_active'],
                    'created_at' => $nowTs,
                    'updated_at' => $nowTs,
                ];
            }, $categories),
            ['id'],
            ['name', 'slug', 'description', 'scorecard_weightage', 'is_active', 'updated_at']
        );

        $desiredIds = array_map(fn ($c) => (int) $c['id'], $categories);
        DB::table('kpi_categories')
            ->whereNotIn('id', $desiredIds)
            ->update(['is_active' => false, 'updated_at' => $nowTs]);
    }
}
