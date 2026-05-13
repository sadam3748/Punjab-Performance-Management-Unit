<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DivisionSeeder extends Seeder
{
    public function run()
    {
        DB::table('divisions')->upsert([
            ['id' => 1, 'name' => 'Bahawalpur', 'code' => 'BWP', 'is_active' => true],
            ['id' => 2, 'name' => 'Dera Ghazi Khan ', 'code' => 'DGK', 'is_active' => true],
            ['id' => 3, 'name' => 'Faisalabad', 'code' => 'FSD', 'is_active' => true],
            ['id' => 4, 'name' => 'Gujranwala', 'code' => 'GJWL', 'is_active' => true],
            ['id' => 5, 'name' => 'Gujrat', 'code' => 'GUJ', 'is_active' => true],
            ['id' => 6, 'name' => 'Lahore', 'code' => 'LHR', 'is_active' => true],
            ['id' => 7, 'name' => 'Multan', 'code' => 'MUL', 'is_active' => true],
            ['id' => 8, 'name' => 'Rawalpindi ', 'code' => 'RWP', 'is_active' => true],
            ['id' => 9, 'name' => 'Sahiwal', 'code' => 'SWL', 'is_active' => true],
            ['id' => 10, 'name' => 'Sargodha', 'code' => 'SGD', 'is_active' => true],
        ], ['id'], ['name', 'code', 'is_active']);
    }
}
