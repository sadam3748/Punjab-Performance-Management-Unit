<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PpmfDummyUserSeeder extends Seeder
{
    public function run(): void
    {
        $lahoreDistrict = DB::table('districts')
            ->whereRaw('LOWER(name) = ?', ['lahore'])
            ->first();

        $layyahDistrict = DB::table('districts')
            ->whereRaw('LOWER(name) = ?', ['layyah'])
            ->first();

        $lahoreTehsil = $lahoreDistrict
            ? DB::table('tehsils')->where('district_id', $lahoreDistrict->id)->first()
            : null;

        $layyahTehsil = $layyahDistrict
            ? DB::table('tehsils')->where('district_id', $layyahDistrict->id)->first()
            : null;

        $users = [];

        if ($lahoreDistrict) {
            $users[] = [
                'name'        => 'Commissioner Lahore',
                'username'    => 'commissioner.lahore',
                'email'       => 'commissioner.lahore@ppmf.local',
                'password'    => Hash::make('123456'),
                'role_id'     => 4,
                'division_id' => $lahoreDistrict->division_id,
                'district_id' => null,
                'tehsil_id'   => null,
                'phone'       => '03000000004',
                'designation' => 'Commissioner',
                'is_active'   => true,
            ];

            $users[] = [
                'name'        => 'DC Lahore',
                'username'    => 'dc.lahore',
                'email'       => 'dc.lahore@ppmf.local',
                'password'    => Hash::make('123456'),
                'role_id'     => 5,
                'division_id' => $lahoreDistrict->division_id,
                'district_id' => $lahoreDistrict->id,
                'tehsil_id'   => null,
                'phone'       => '03000000005',
                'designation' => 'Deputy Commissioner',
                'is_active'   => true,
            ];

            $users[] = [
                'name'        => 'AC Lahore',
                'username'    => 'ac.lahore',
                'email'       => 'ac.lahore@ppmf.local',
                'password'    => Hash::make('123456'),
                'role_id'     => 6,
                'division_id' => $lahoreDistrict->division_id,
                'district_id' => $lahoreDistrict->id,
                'tehsil_id'   => $lahoreTehsil?->id,
                'phone'       => '03000000006',
                'designation' => 'Assistant Commissioner',
                'is_active'   => true,
            ];
        }

        if ($layyahDistrict) {
            $users[] = [
                'name'        => 'Commissioner Dera Ghazi Khan',
                'username'    => 'commissioner.dgk',
                'email'       => 'commissioner.dgk@ppmf.local',
                'password'    => Hash::make('123456'),
                'role_id'     => 4,
                'division_id' => $layyahDistrict->division_id,
                'district_id' => null,
                'tehsil_id'   => null,
                'phone'       => '03000000007',
                'designation' => 'Commissioner',
                'is_active'   => true,
            ];

            $users[] = [
                'name'        => 'DC Layyah',
                'username'    => 'dc.layyah',
                'email'       => 'dc.layyah@ppmf.local',
                'password'    => Hash::make('123456'),
                'role_id'     => 5,
                'division_id' => $layyahDistrict->division_id,
                'district_id' => $layyahDistrict->id,
                'tehsil_id'   => null,
                'phone'       => '03000000008',
                'designation' => 'Deputy Commissioner',
                'is_active'   => true,
            ];

            $users[] = [
                'name'        => 'AC Layyah',
                'username'    => 'ac.layyah',
                'email'       => 'ac.layyah@ppmf.local',
                'password'    => Hash::make('123456'),
                'role_id'     => 6,
                'division_id' => $layyahDistrict->division_id,
                'district_id' => $layyahDistrict->id,
                'tehsil_id'   => $layyahTehsil?->id,
                'phone'       => '03000000009',
                'designation' => 'Assistant Commissioner',
                'is_active'   => true,
            ];
        }

        for ($i = 1; $i <= 40; $i++) {
            $district = $i <= 25 ? $layyahDistrict : $lahoreDistrict;

            if (! $district) {
                continue;
            }

            $tehsil = DB::table('tehsils')
                ->where('district_id', $district->id)
                ->inRandomOrder()
                ->first();

            $districtSlug = strtolower(str_replace(' ', '.', $district->name));

            $users[] = [
                'name'        => 'Field User ' . $district->name . ' ' . $i,
                'username'    => 'field.' . $districtSlug . '.' . $i,
                'email'       => 'field.' . $districtSlug . '.' . $i . '@ppmf.local',
                'password'    => Hash::make('123456'),
                'role_id'     => 7,
                'division_id' => $district->division_id,
                'district_id' => $district->id,
                'tehsil_id'   => $tehsil?->id,
                'phone'       => '0310000' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'designation' => 'Field Officer',
                'is_active'   => true,
            ];
        }

        foreach ($users as $user) {
            DB::table('users')->updateOrInsert(
                ['email' => $user['email']],
                array_merge($user, [
                    'updated_at' => now(),
                    'created_at' => now(),
                ])
            );
        }
    }
}
