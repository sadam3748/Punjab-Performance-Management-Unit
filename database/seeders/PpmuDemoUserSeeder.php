<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PpmuDemoUserSeeder extends Seeder
{
    public function run(): void
    {
        $lahoreDistrict = DB::table('districts')->whereRaw('LOWER(name) = ?', ['lahore'])->first();
        $layyahDistrict = DB::table('districts')->whereRaw('LOWER(name) = ?', ['layyah'])->first();
        $lahoreTehsil = $lahoreDistrict ? DB::table('tehsils')->where('district_id', $lahoreDistrict->id)->first() : null;
        $layyahTehsil = $layyahDistrict ? DB::table('tehsils')->where('district_id', $layyahDistrict->id)->where('name', 'Layyah')->first() : null;
        $karorTehsil = $layyahDistrict ? DB::table('tehsils')->where('district_id', $layyahDistrict->id)->where('name', 'Karor Lal Esan')->first() : null;

        $users = [];

        if ($lahoreDistrict) {
            $users[] = $this->user('Commissioner Lahore', 'com.lahore', 'commissioner.lahore@ppmf.local', 4, $lahoreDistrict->division_id, null, null, 'Commissioner');
            $users[] = $this->user('DC Lahore', 'dc.lahore', 'dc.lahore@ppmf.local', 5, $lahoreDistrict->division_id, $lahoreDistrict->id, null, 'Deputy Commissioner');
            $users[] = $this->user('AC Lahore', 'ac.lahore', 'ac.lahore@ppmf.local', 6, $lahoreDistrict->division_id, $lahoreDistrict->id, $lahoreTehsil?->id, 'Assistant Commissioner');
        }

        if ($layyahDistrict) {
            $karorTehsil = DB::table('tehsils')->where('district_id', $layyahDistrict->id)->where('name', 'Karor Lal Esan')->first();
            $users[] = $this->user('Commissioner Dera Ghazi Khan', 'com.dgkhan', 'commissioner.dgk@ppmf.local', 4, $layyahDistrict->division_id, null, null, 'Commissioner');
            $users[] = $this->user('DC Layyah', 'dc.layyah', 'dc.layyah@ppmf.local', 5, $layyahDistrict->division_id, $layyahDistrict->id, null, 'Deputy Commissioner');
            $users[] = $this->user('AC Layyah', 'ac.layyah', 'ac.layyah@ppmf.local', 6, $layyahDistrict->division_id, $layyahDistrict->id, $layyahTehsil?->id, 'Assistant Commissioner');
            if ($karorTehsil) {
                $users[] = $this->user('AC Karor', 'ac.karor', 'ac.karor@ppmf.local', 6, $layyahDistrict->division_id, $layyahDistrict->id, $karorTehsil->id, 'Assistant Commissioner');
            }
        }

        foreach ($users as $user) {
            DB::table('users')->updateOrInsert(['email' => $user['email']], $user);
        }
    }

    private function user(string $name, string $username, string $email, int $roleId, ?int $divisionId, ?int $districtId, ?int $tehsilId, string $designation): array
    {
        return [
            'name' => $name,
            'username' => $username,
            'email' => $email,
            'password' => Hash::make('123456'),
            'role_id' => $roleId,
            'division_id' => $divisionId,
            'district_id' => $districtId,
            'tehsil_id' => $tehsilId,
            'phone' => null,
            'designation' => $designation,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
