<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('users')->updateOrInsert(
            ['email' => 'admin@ppmf.local'],
            [
                'role_id'       => 1,
                'division_id'   => null,
                'district_id'   => null,
                'tehsil_id'     => null,
                'name'          => 'PPMF Super Admin',
                'username'      => 'super.admin',
                'email'         => 'admin@ppmf.local',
                'password'      => Hash::make('123456'),
                'phone'         => null,
                'designation'   => 'Super Admin',
                'is_active'     => true,
                'last_login_at' => null,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]
        );

        DB::table('users')->updateOrInsert(
            ['email' => 'cs.pmru@ppmf.local'],
            [
                'role_id'       => 2,
                'division_id'   => null,
                'district_id'   => null,
                'tehsil_id'     => null,
                'name'          => 'Chief Secretary',
                'username'      => 'cs.pmru',
                'email'         => 'cs.pmru@ppmf.local',
                'password'      => Hash::make('123456'),
                'phone'         => null,
                'designation'   => 'Chief Secretary',
                'is_active'     => true,
                'last_login_at' => null,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]
        );
    }
}
