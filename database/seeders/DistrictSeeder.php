<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DistrictSeeder extends Seeder
{
    // distrcits
    public function run()
    {
        DB::table('districts')->upsert([
            // Bahawalpur Division (BWP)
            ['id' => 1, 'division_id' => 1, 'name' => 'Bahawalnagar', 'code' => 'BWN', 'tier' => 2, 'is_active' => true],
            ['id' => 2, 'division_id' => 1, 'name' => 'Bahawalpur', 'code' => 'BWP', 'tier' => 2, 'is_active' => true],
            ['id' => 3, 'division_id' => 1, 'name' => 'Rahim Yar Khan', 'code' => 'RYK', 'tier' => 2, 'is_active' => true],

            // Dera Ghazi Khan Division (DGK)
            ['id' => 4, 'division_id' => 2, 'name' => 'Dera Ghazi Khan', 'code' => 'DGK', 'tier' => 2, 'is_active' => true],
            ['id' => 5, 'division_id' => 2, 'name' => 'Jampur', 'code' => 'JMP', 'tier' => 3, 'is_active' => true],
            ['id' => 6, 'division_id' => 2, 'name' => 'Kot Addu', 'code' => 'KAD', 'tier' => 3, 'is_active' => true],
            ['id' => 7, 'division_id' => 2, 'name' => 'Layyah', 'code' => 'LYA', 'tier' => 3, 'is_active' => true],
            ['id' => 8, 'division_id' => 2, 'name' => 'Muzaffargarh', 'code' => 'MZG', 'tier' => 2, 'is_active' => true],
            ['id' => 9, 'division_id' => 2, 'name' => 'Rajanpur', 'code' => 'RJP', 'tier' => 3, 'is_active' => true],
            ['id' => 10, 'division_id' => 2, 'name' => 'Taunsa', 'code' => 'TNS', 'tier' => 3, 'is_active' => true],

            // Faisalabad Division (FSD)
            ['id' => 11, 'division_id' => 3, 'name' => 'Chiniot', 'code' => 'CHT', 'tier' => 3, 'is_active' => true],
            ['id' => 12, 'division_id' => 3, 'name' => 'Faisalabad', 'code' => 'FSD', 'tier' => 1, 'is_active' => true],
            ['id' => 13, 'division_id' => 3, 'name' => 'Jhang', 'code' => 'JHG', 'tier' => 2, 'is_active' => true],
            ['id' => 14, 'division_id' => 3, 'name' => 'Toba Tek Singh', 'code' => 'TTS', 'tier' => 2, 'is_active' => true],

            // Gujranwala Division (GJWL)
            ['id' => 15, 'division_id' => 4, 'name' => 'Gujranwala', 'code' => 'GRW', 'tier' => 1, 'is_active' => true],
            ['id' => 16, 'division_id' => 4, 'name' => 'Narowal', 'code' => 'NRW', 'tier' => 3, 'is_active' => true],
            ['id' => 17, 'division_id' => 4, 'name' => 'Sialkot', 'code' => 'SKT', 'tier' => 1, 'is_active' => true],

            // Gujrat Division (GUJ)
            ['id' => 18, 'division_id' => 5, 'name' => 'Gujrat', 'code' => 'GJT', 'tier' => 2, 'is_active' => true],
            ['id' => 19, 'division_id' => 5, 'name' => 'Hafizabad', 'code' => 'HFD', 'tier' => 3, 'is_active' => true],
            ['id' => 20, 'division_id' => 5, 'name' => 'Mandi Bahauddin', 'code' => 'MBD', 'tier' => 3, 'is_active' => true],
            ['id' => 21, 'division_id' => 5, 'name' => 'Wazirabad', 'code' => 'WZR', 'tier' => 3, 'is_active' => true],

            // Lahore Division (LHR)
            ['id' => 22, 'division_id' => 6, 'name' => 'Kasur', 'code' => 'KSR', 'tier' => 2, 'is_active' => true],
            ['id' => 23, 'division_id' => 6, 'name' => 'Lahore', 'code' => 'LHR', 'tier' => 1, 'is_active' => true],
            ['id' => 24, 'division_id' => 6, 'name' => 'Nankana Sahib', 'code' => 'NKS', 'tier' => 3, 'is_active' => true],
            ['id' => 25, 'division_id' => 6, 'name' => 'Sheikhupura', 'code' => 'SKP', 'tier' => 2, 'is_active' => true],

            // Multan Division (MUL)
            ['id' => 26, 'division_id' => 7, 'name' => 'Khanewal', 'code' => 'KWL', 'tier' => 2, 'is_active' => true],
            ['id' => 27, 'division_id' => 7, 'name' => 'Lodhran', 'code' => 'LOD', 'tier' => 3, 'is_active' => true],
            ['id' => 28, 'division_id' => 7, 'name' => 'Multan', 'code' => 'MUL', 'tier' => 1, 'is_active' => true],
            ['id' => 29, 'division_id' => 7, 'name' => 'Vehari', 'code' => 'VHR', 'tier' => 2, 'is_active' => true],

            // Rawalpindi Division (RWP)
            ['id' => 30, 'division_id' => 8, 'name' => 'Attock', 'code' => 'ATK', 'tier' => 2, 'is_active' => true],
            ['id' => 31, 'division_id' => 8, 'name' => 'Chakwal', 'code' => 'CKW', 'tier' => 3, 'is_active' => true],
            ['id' => 32, 'division_id' => 8, 'name' => 'Jhelum', 'code' => 'JLM', 'tier' => 2, 'is_active' => true],
            ['id' => 33, 'division_id' => 8, 'name' => 'Rawalpindi', 'code' => 'RWP', 'tier' => 1, 'is_active' => true],
            ['id' => 34, 'division_id' => 8, 'name' => 'Murree', 'code' => 'MRE', 'tier' => 2, 'is_active' => true],
            ['id' => 35, 'division_id' => 8, 'name' => 'Talagang', 'code' => 'TLG', 'tier' => 3, 'is_active' => true],

            // Sahiwal Division (SWL)
            ['id' => 36, 'division_id' => 9, 'name' => 'Okara', 'code' => 'OKR', 'tier' => 2, 'is_active' => true],
            ['id' => 37, 'division_id' => 9, 'name' => 'Pakpattan', 'code' => 'PKP', 'tier' => 3, 'is_active' => true],
            ['id' => 38, 'division_id' => 9, 'name' => 'Sahiwal', 'code' => 'SWL', 'tier' => 2, 'is_active' => true],

            // Sargodha Division (SGD)
            ['id' => 39, 'division_id' => 10, 'name' => 'Bhakkar', 'code' => 'BKR', 'tier' => 3, 'is_active' => true],
            ['id' => 40, 'division_id' => 10, 'name' => 'Khushab', 'code' => 'KSB', 'tier' => 3, 'is_active' => true],
            ['id' => 41, 'division_id' => 10, 'name' => 'Mianwali', 'code' => 'MWI', 'tier' => 3, 'is_active' => true],
            ['id' => 42, 'division_id' => 10, 'name' => 'Sargodha', 'code' => 'SGD', 'tier' => 2, 'is_active' => true],
        ], ['id'], ['division_id', 'name', 'code', 'tier', 'is_active']);
    }
}
