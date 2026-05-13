<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GeoTaggingTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['id' => 1, 'name' => 'Tandoors'],
            ['id' => 2, 'name' => 'Bakery Bread'],
            ['id' => 3, 'name' => 'Water Filtration Plant'],
            ['id' => 4, 'name' => 'Educational Institution'],
            ['id' => 5, 'name' => 'Special Children Institution'],
            ['id' => 6, 'name' => 'Health Facilities'],
            ['id' => 7, 'name' => 'Registered Drug Store'],
            ['id' => 8, 'name' => 'A+ and Elite Wedding Halls, Marquees'],
            ['id' => 9, 'name' => 'Graveyards'],
            ['id' => 10, 'name' => 'Sale Points of LPG'],
            ['id' => 11, 'name' => 'Bus Terminals'],
            ['id' => 12, 'name' => 'Sale Points of Fertilizers and Pesticides'],
            ['id' => 13, 'name' => 'Markets'],
            ['id' => 14, 'name' => 'Development Schemes'],
            ['id' => 15, 'name' => 'Bridges'],
            ['id' => 16, 'name' => 'Roads having with streetlights'],
            ['id' => 17, 'name' => 'Parks'],
            ['id' => 18, 'name' => 'Manhole Covers'],
            ['id' => 19, 'name' => 'Streets'],
        ];

        foreach ($types as $type) {
            DB::table('geo_tagging_types')->updateOrInsert(
                ['id' => $type['id']],
                [
                    'name'       => $type['name'],
                    'slug'       => Str::slug($type['name']),
                    'is_active'  => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
