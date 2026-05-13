<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class KpiCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['id' => 1, 'name' => 'Inspection of Marriage Halls', 'description' => 'Inspection of marriage halls for one dish, timing, fine, FIR, sealing and related actions.'],
            ['id' => 2, 'name' => 'Inspection of Stray Dogs', 'description' => 'Inspection and reporting of stray dogs seized from streets and union councils.'],
            ['id' => 3, 'name' => 'Inspection of Water Filtration Plants', 'description' => 'Inspection of water filtration plants including cleanliness, filter change date and functional status.'],
            ['id' => 4, 'name' => 'Inspection of Manholes Covers', 'description' => 'Inspection of missing or damaged manhole covers and available repair stock.'],
            ['id' => 5, 'name' => 'Inspection of Tandoors', 'description' => 'Inspection related to roti/tandoor price and compliance.'],
            ['id' => 6, 'name' => 'Inspection of Bakery Bread', 'description' => 'Inspection related to bakery bread price and compliance.'],
            ['id' => 7, 'name' => 'Inspection of Health Facilities', 'description' => 'Inspection of health facilities and related public service delivery.'],
            ['id' => 8, 'name' => 'Inspection of Registered Drug Stores', 'description' => 'Inspection of registered drug stores and compliance.'],
            ['id' => 9, 'name' => 'Inspection of Sale Points of LPG', 'description' => 'Inspection of LPG sale points and illegal filling.'],
            ['id' => 10, 'name' => 'Inspection of Bus Terminals', 'description' => 'Inspection of bus terminals and facilities.'],
            ['id' => 11, 'name' => 'Inspection of Fertilizers and Pesticides Sale Points', 'description' => 'Inspection of fertilizer and pesticide sale points.'],
            ['id' => 12, 'name' => 'Inspection of Markets', 'description' => 'Inspection of markets and public service compliance.'],
            ['id' => 13, 'name' => 'Inspection of Development Schemes', 'description' => 'Inspection of development schemes.'],
            ['id' => 14, 'name' => 'Inspection of Bridges', 'description' => 'Inspection of bridges and infrastructure condition.'],
            ['id' => 15, 'name' => 'Inspection of Roads with Streetlights', 'description' => 'Inspection of roads having streetlights.'],
            ['id' => 16, 'name' => 'Inspection of Parks', 'description' => 'Inspection of parks and green belts.'],
            ['id' => 17, 'name' => 'Inspection of Streets', 'description' => 'Inspection of streets and related public infrastructure.'],
            ['id' => 18, 'name' => 'Inspection of Educational Institutions', 'description' => 'Inspection of educational institutions.'],
        ];

        foreach ($categories as $category) {
            DB::table('kpi_categories')->updateOrInsert(
                ['id' => $category['id']],
                [
                    'name'        => $category['name'],
                    'slug'        => Str::slug($category['name']),
                    'description' => $category['description'],
                    'is_active'   => true,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]
            );
        }
    }
}
