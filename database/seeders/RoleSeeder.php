<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('roles')->upsert([
            [
                'id'          => 1,
                'name'        => 'Super Admin',
                'slug'        => 'super_admin',
                'scope_level' => 'punjab',
                'is_active'   => true,
            ],
            [
                'id'          => 2,
                'name'        => 'Chief Secretary',
                'slug'        => 'chief_secretary',
                'scope_level' => 'punjab',
                'is_active'   => true,
            ],
            [
                'id'          => 3,
                'name'        => 'PMRU User',
                'slug'        => 'pmru_user',
                'scope_level' => 'punjab',
                'is_active'   => true,
            ],
            [
                'id'          => 4,
                'name'        => 'Commissioner',
                'slug'        => 'commissioner',
                'scope_level' => 'division',
                'is_active'   => true,
            ],
            [
                'id'          => 5,
                'name'        => 'Deputy Commissioner',
                'slug'        => 'dc',
                'scope_level' => 'district',
                'is_active'   => true,
            ],
            [
                'id'          => 6,
                'name'        => 'Assistant Commissioner',
                'slug'        => 'ac',
                'scope_level' => 'tehsil',
                'is_active'   => true,
            ],
            [
                'id'          => 7,
                'name'        => 'Field User',
                'slug'        => 'field_user',
                'scope_level' => 'tehsil',
                'is_active'   => true,
            ],
            [
                'id'          => 8,
                'name'        => 'Viewer',
                'slug'        => 'viewer',
                'scope_level' => 'punjab',
                'is_active'   => true,
            ],
        ], ['id'], ['name', 'slug', 'scope_level', 'is_active']);
    }
}
