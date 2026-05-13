<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TehsilSeeder extends Seeder
{
    public function run()
    {
        DB::table('tehsils')->upsert([
            // Bahawalnagar District (BWN)
            ['id' => 1, 'district_id' => 1, 'name' => 'Bahawalnagar', 'code' => 'BWN-BWN', 'is_active' => true],
            ['id' => 2, 'district_id' => 1, 'name' => 'Chishtian', 'code' => 'BWN-CHS', 'is_active' => true],
            ['id' => 3, 'district_id' => 1, 'name' => 'Haroonabad', 'code' => 'BWN-HRN', 'is_active' => true],
            ['id' => 4, 'district_id' => 1, 'name' => 'Minchinabad', 'code' => 'BWN-MCD', 'is_active' => true],
            ['id' => 5, 'district_id' => 1, 'name' => 'Fort Abbas', 'code' => 'BWN-FTA', 'is_active' => true],

            // Bahawalpur District (BWP)
            ['id' => 6, 'district_id' => 2, 'name' => 'Ahmadpur East', 'code' => 'BWP-APE', 'is_active' => true],
            ['id' => 7, 'district_id' => 2, 'name' => 'Bahawalpur City', 'code' => 'BWP-BWC', 'is_active' => true],
            ['id' => 8, 'district_id' => 2, 'name' => 'Yazman', 'code' => 'BWP-YZM', 'is_active' => true],
            ['id' => 9, 'district_id' => 2, 'name' => 'Bahawalpur Saddar', 'code' => 'BWP-BWS', 'is_active' => true],
            ['id' => 10, 'district_id' => 2, 'name' => 'Hasilpur', 'code' => 'BWP-HSP', 'is_active' => true],
            ['id' => 11, 'district_id' => 2, 'name' => 'Khairpur Tamewali', 'code' => 'BWP-KPT', 'is_active' => true],

            // Rahim Yar Khan District (RYK)
            ['id' => 12, 'district_id' => 3, 'name' => 'Rahim Yar Khan', 'code' => 'RYK-RYK', 'is_active' => true],
            ['id' => 13, 'district_id' => 3, 'name' => 'Sadiqabad', 'code' => 'RYK-SDK', 'is_active' => true],
            ['id' => 14, 'district_id' => 3, 'name' => 'Liaqatpur', 'code' => 'RYK-LQP', 'is_active' => true],
            ['id' => 15, 'district_id' => 3, 'name' => 'Khanpur Katora', 'code' => 'RYK-KPK', 'is_active' => true],

            // Dera Ghazi Khan District (DGK)
            ['id' => 16, 'district_id' => 4, 'name' => 'Dera Ghazi Khan', 'code' => 'DGK-DGK', 'is_active' => true],
            ['id' => 17, 'district_id' => 4, 'name' => 'Kot Chutta', 'code' => 'DGK-KTC', 'is_active' => true],

            // Jampur District (JMP)
            ['id' => 18, 'district_id' => 5, 'name' => 'Jampur', 'code' => 'JMP-JMP', 'is_active' => true],
            ['id' => 19, 'district_id' => 5, 'name' => 'Muhammadpur', 'code' => 'JMP-MHP', 'is_active' => true],
            ['id' => 20, 'district_id' => 5, 'name' => 'Dajal', 'code' => 'JMP-DJL', 'is_active' => true],
            ['id' => 21, 'district_id' => 5, 'name' => 'Jampur Tribal Area', 'code' => 'JMP-TRB', 'is_active' => true],

            // Kot Addu District (KAD)
            ['id' => 22, 'district_id' => 6, 'name' => 'Kot Addu', 'code' => 'KAD-KAD', 'is_active' => true],
            ['id' => 23, 'district_id' => 6, 'name' => 'Chowk Sarwar Shaheed', 'code' => 'KAD-CSS', 'is_active' => true],

            // Layyah District (LYA)
            ['id' => 24, 'district_id' => 7, 'name' => 'Layyah', 'code' => 'LYA-LYA', 'is_active' => true],
            ['id' => 25, 'district_id' => 7, 'name' => 'Karor Lal Esan', 'code' => 'LYA-KLE', 'is_active' => true],
            ['id' => 26, 'district_id' => 7, 'name' => 'Chaubara', 'code' => 'LYA-CBR', 'is_active' => true],

            // Muzaffargarh District (MZG)
            ['id' => 27, 'district_id' => 8, 'name' => 'Muzaffargarh', 'code' => 'MZG-MZG', 'is_active' => true],
            ['id' => 28, 'district_id' => 8, 'name' => 'Alipur', 'code' => 'MZG-ALP', 'is_active' => true],
            ['id' => 29, 'district_id' => 8, 'name' => 'Jatoi', 'code' => 'MZG-JTI', 'is_active' => true],

            // Rajanpur District (RJP)
            ['id' => 30, 'district_id' => 9, 'name' => 'Rajanpur', 'code' => 'RJP-RJP', 'is_active' => true],
            ['id' => 31, 'district_id' => 9, 'name' => 'Rojhan', 'code' => 'RJP-RJN', 'is_active' => true],
            ['id' => 32, 'district_id' => 9, 'name' => 'De-Excluded Area Rajanpur', 'code' => 'RJP-DEX', 'is_active' => true],
            ['id' => 33, 'district_id' => 9, 'name' => 'Koh-e-Suleman', 'code' => 'RJP-KES', 'is_active' => true],

            // Taunsa District (TNS)
            ['id' => 34, 'district_id' => 10, 'name' => 'Taunsa', 'code' => 'TNS-TNS', 'is_active' => true],
            ['id' => 35, 'district_id' => 10, 'name' => 'Wahova', 'code' => 'TNS-WHV', 'is_active' => true],

            // Chiniot District (CHT)
            ['id' => 36, 'district_id' => 11, 'name' => 'Chiniot', 'code' => 'CHT-CHT', 'is_active' => true],
            ['id' => 37, 'district_id' => 11, 'name' => 'Bhowana', 'code' => 'CHT-BWN', 'is_active' => true],
            ['id' => 38, 'district_id' => 11, 'name' => 'Lalian', 'code' => 'CHT-LLN', 'is_active' => true],

            // Faisalabad District (FSD)
            ['id' => 39, 'district_id' => 12, 'name' => 'Faisalabad City', 'code' => 'FSD-CTY', 'is_active' => true],
            ['id' => 40, 'district_id' => 12, 'name' => 'Faisalabad Sadar', 'code' => 'FSD-SDR', 'is_active' => true],
            ['id' => 41, 'district_id' => 12, 'name' => 'Chak Jhumra', 'code' => 'FSD-CHJ', 'is_active' => true],
            ['id' => 42, 'district_id' => 12, 'name' => 'Jaranwala', 'code' => 'FSD-JRW', 'is_active' => true],
            ['id' => 43, 'district_id' => 12, 'name' => 'Samundri', 'code' => 'FSD-SMD', 'is_active' => true],
            ['id' => 44, 'district_id' => 12, 'name' => 'Tandlianwala', 'code' => 'FSD-TLW', 'is_active' => true],

            // Jhang District (JHG)
            ['id' => 45, 'district_id' => 13, 'name' => 'Jhang', 'code' => 'JHG-JHG', 'is_active' => true],
            ['id' => 46, 'district_id' => 13, 'name' => 'Shorkot', 'code' => 'JHG-SHK', 'is_active' => true],
            ['id' => 47, 'district_id' => 13, 'name' => 'Ahmadpur Sial', 'code' => 'JHG-APS', 'is_active' => true],
            ['id' => 48, 'district_id' => 13, 'name' => 'Athara Hazari', 'code' => 'JHG-ATH', 'is_active' => true],
            ['id' => 49, 'district_id' => 13, 'name' => 'Mandi Shah Jeewna', 'code' => 'JHG-MSJ', 'is_active' => true],

            // Toba Tek Singh District (TTS)
            ['id' => 50, 'district_id' => 14, 'name' => 'Toba Tek Singh', 'code' => 'TTS-TTS', 'is_active' => true],
            ['id' => 51, 'district_id' => 14, 'name' => 'Kamalia', 'code' => 'TTS-KML', 'is_active' => true],
            ['id' => 52, 'district_id' => 14, 'name' => 'Gojra', 'code' => 'TTS-GJR', 'is_active' => true],
            ['id' => 53, 'district_id' => 14, 'name' => 'Pirmahal', 'code' => 'TTS-PML', 'is_active' => true],

            // Gujranwala District (GRW)
            ['id' => 54, 'district_id' => 15, 'name' => 'Gujranwala City', 'code' => 'GRW-CTY', 'is_active' => true],
            ['id' => 55, 'district_id' => 15, 'name' => 'Gujranwala Saddar', 'code' => 'GRW-SDR', 'is_active' => true],
            ['id' => 56, 'district_id' => 15, 'name' => 'Kamoke', 'code' => 'GRW-KMK', 'is_active' => true],
            ['id' => 57, 'district_id' => 15, 'name' => 'Nowshera Virkan', 'code' => 'GRW-NSV', 'is_active' => true],

            // Narowal District (NRW)
            ['id' => 58, 'district_id' => 16, 'name' => 'Narowal', 'code' => 'NRW-NRW', 'is_active' => true],
            ['id' => 59, 'district_id' => 16, 'name' => 'Shakargarh', 'code' => 'NRW-SKG', 'is_active' => true],
            ['id' => 60, 'district_id' => 16, 'name' => 'Zafarwal', 'code' => 'NRW-ZFW', 'is_active' => true],

            // Sialkot District (SKT)
            ['id' => 61, 'district_id' => 17, 'name' => 'Sialkot', 'code' => 'SKT-SKT', 'is_active' => true],
            ['id' => 62, 'district_id' => 17, 'name' => 'Daska', 'code' => 'SKT-DSK', 'is_active' => true],
            ['id' => 63, 'district_id' => 17, 'name' => 'Pasrur', 'code' => 'SKT-PSR', 'is_active' => true],
            ['id' => 64, 'district_id' => 17, 'name' => 'Sambrial', 'code' => 'SKT-SMB', 'is_active' => true],

            // Gujrat District (GJT)
            ['id' => 65, 'district_id' => 18, 'name' => 'Gujrat', 'code' => 'GJT-GJT', 'is_active' => true],
            ['id' => 66, 'district_id' => 18, 'name' => 'Kharian', 'code' => 'GJT-KHN', 'is_active' => true],
            ['id' => 67, 'district_id' => 18, 'name' => 'Sarai Alamgir', 'code' => 'GJT-SAG', 'is_active' => true],
            ['id' => 68, 'district_id' => 18, 'name' => 'Jalalpur Jattan', 'code' => 'GJT-JPJ', 'is_active' => true],
            ['id' => 69, 'district_id' => 18, 'name' => 'Kunjah', 'code' => 'GJT-KJH', 'is_active' => true],

            // Hafizabad District (HFD)
            ['id' => 70, 'district_id' => 19, 'name' => 'Hafizabad', 'code' => 'HFD-HFD', 'is_active' => true],
            ['id' => 71, 'district_id' => 19, 'name' => 'Pindi Bhattian', 'code' => 'HFD-PBT', 'is_active' => true],

            // Mandi Bahauddin District (MBD)
            ['id' => 72, 'district_id' => 20, 'name' => 'Mandi Bahauddin', 'code' => 'MBD-MBD', 'is_active' => true],
            ['id' => 73, 'district_id' => 20, 'name' => 'Malakwal', 'code' => 'MBD-MLK', 'is_active' => true],
            ['id' => 74, 'district_id' => 20, 'name' => 'Phalia', 'code' => 'MBD-PHL', 'is_active' => true],

            // Wazirabad District (WZR)
            ['id' => 75, 'district_id' => 21, 'name' => 'Wazirabad', 'code' => 'WZR-WZR', 'is_active' => true],
            ['id' => 76, 'district_id' => 21, 'name' => 'Ali Pur Chatta', 'code' => 'WZR-APC', 'is_active' => true],

            // Kasur District (KSR)
            ['id' => 77, 'district_id' => 22, 'name' => 'Kasur', 'code' => 'KSR-KSR', 'is_active' => true],
            ['id' => 78, 'district_id' => 22, 'name' => 'Chunian', 'code' => 'KSR-CHN', 'is_active' => true],
            ['id' => 79, 'district_id' => 22, 'name' => 'Kot Radha Kishan', 'code' => 'KSR-KRK', 'is_active' => true],
            ['id' => 80, 'district_id' => 22, 'name' => 'Pattoki', 'code' => 'KSR-PTK', 'is_active' => true],

            // Lahore District (LHR)
            ['id' => 81, 'district_id' => 23, 'name' => 'Lahore City', 'code' => 'LHR-CTY', 'is_active' => true],
            ['id' => 82, 'district_id' => 23, 'name' => 'Lahore Cantonment', 'code' => 'LHR-CNT', 'is_active' => true],
            ['id' => 83, 'district_id' => 23, 'name' => 'Model Town', 'code' => 'LHR-MTN', 'is_active' => true],
            ['id' => 84, 'district_id' => 23, 'name' => 'Raiwind', 'code' => 'LHR-RWD', 'is_active' => true],
            ['id' => 85, 'district_id' => 23, 'name' => 'Shalimar', 'code' => 'LHR-SHL', 'is_active' => true],

            // Nankana Sahib District (NKS)
            ['id' => 86, 'district_id' => 24, 'name' => 'Nankana Sahib', 'code' => 'NKS-NKS', 'is_active' => true],
            ['id' => 87, 'district_id' => 24, 'name' => 'Sangla Hill', 'code' => 'NKS-SGH', 'is_active' => true],
            ['id' => 88, 'district_id' => 24, 'name' => 'Shah Kot', 'code' => 'NKS-SHK', 'is_active' => true],

            // Sheikhupura District (SKP)
            ['id' => 89, 'district_id' => 25, 'name' => 'Sheikhupura', 'code' => 'SKP-SKP', 'is_active' => true],
            ['id' => 90, 'district_id' => 25, 'name' => 'Muridke', 'code' => 'SKP-MRK', 'is_active' => true],
            ['id' => 91, 'district_id' => 25, 'name' => 'Ferozewala', 'code' => 'SKP-FZW', 'is_active' => true],
            ['id' => 92, 'district_id' => 25, 'name' => 'Safdarabad', 'code' => 'SKP-SFD', 'is_active' => true],
            ['id' => 93, 'district_id' => 25, 'name' => 'Sharak Pur', 'code' => 'SKP-SHP', 'is_active' => true],

            // Khanewal District (KWL)
            ['id' => 94, 'district_id' => 26, 'name' => 'Khanewal', 'code' => 'KWL-KWL', 'is_active' => true],
            ['id' => 95, 'district_id' => 26, 'name' => 'Kabirwala', 'code' => 'KWL-KBW', 'is_active' => true],
            ['id' => 96, 'district_id' => 26, 'name' => 'Mian Channu', 'code' => 'KWL-MCN', 'is_active' => true],
            ['id' => 97, 'district_id' => 26, 'name' => 'Jahanian', 'code' => 'KWL-JHN', 'is_active' => true],

            // Lodhran District (LOD)
            ['id' => 98, 'district_id' => 27, 'name' => 'Lodhran', 'code' => 'LOD-LOD', 'is_active' => true],
            ['id' => 99, 'district_id' => 27, 'name' => 'Dunyapur', 'code' => 'LOD-DNP', 'is_active' => true],
            ['id' => 100, 'district_id' => 27, 'name' => 'Kahror Pacca', 'code' => 'LOD-KPC', 'is_active' => true],

            // Multan District (MUL)
            ['id' => 101, 'district_id' => 28, 'name' => 'Multan City', 'code' => 'MUL-CTY', 'is_active' => true],
            ['id' => 102, 'district_id' => 28, 'name' => 'Multan Saddar', 'code' => 'MUL-SDR', 'is_active' => true],
            ['id' => 103, 'district_id' => 28, 'name' => 'Shujabad', 'code' => 'MUL-SJB', 'is_active' => true],
            ['id' => 104, 'district_id' => 28, 'name' => 'Jalalpur Pirwala', 'code' => 'MUL-JPP', 'is_active' => true],

            // Vehari District (VHR)
            ['id' => 105, 'district_id' => 29, 'name' => 'Vehari', 'code' => 'VHR-VHR', 'is_active' => true],
            ['id' => 106, 'district_id' => 29, 'name' => 'Burewala', 'code' => 'VHR-BRW', 'is_active' => true],
            ['id' => 107, 'district_id' => 29, 'name' => 'Mailsi', 'code' => 'VHR-MLS', 'is_active' => true],
            ['id' => 108, 'district_id' => 29, 'name' => 'Jallah Jeem', 'code' => 'VHR-JLJ', 'is_active' => true],

            // Attock District (ATK)
            ['id' => 109, 'district_id' => 30, 'name' => 'Attock', 'code' => 'ATK-ATK', 'is_active' => true],
            ['id' => 110, 'district_id' => 30, 'name' => 'Fateh Jang', 'code' => 'ATK-FTJ', 'is_active' => true],
            ['id' => 111, 'district_id' => 30, 'name' => 'Hassan Abdal', 'code' => 'ATK-HSA', 'is_active' => true],
            ['id' => 112, 'district_id' => 30, 'name' => 'Hazro', 'code' => 'ATK-HZR', 'is_active' => true],
            ['id' => 113, 'district_id' => 30, 'name' => 'Jand', 'code' => 'ATK-JND', 'is_active' => true],
            ['id' => 114, 'district_id' => 30, 'name' => 'Pindi Gheb', 'code' => 'ATK-PDG', 'is_active' => true],

            // Chakwal District (CKW)
            ['id' => 115, 'district_id' => 31, 'name' => 'Chakwal', 'code' => 'CKW-CKW', 'is_active' => true],
            ['id' => 116, 'district_id' => 31, 'name' => 'Choa Saidan Shah', 'code' => 'CKW-CSS', 'is_active' => true],
            ['id' => 117, 'district_id' => 31, 'name' => 'Kallar Kahar', 'code' => 'CKW-KKR', 'is_active' => true],

            // Jhelum District (JLM)
            ['id' => 118, 'district_id' => 32, 'name' => 'Jhelum', 'code' => 'JLM-JLM', 'is_active' => true],
            ['id' => 119, 'district_id' => 32, 'name' => 'Dina', 'code' => 'JLM-DNA', 'is_active' => true],
            ['id' => 120, 'district_id' => 32, 'name' => 'Pind Dadan Khan', 'code' => 'JLM-PDK', 'is_active' => true],
            ['id' => 121, 'district_id' => 32, 'name' => 'Sohawa', 'code' => 'JLM-SHW', 'is_active' => true],

            // Rawalpindi District (RWP)
            ['id' => 122, 'district_id' => 33, 'name' => 'Rawalpindi', 'code' => 'RWP-RWP', 'is_active' => true],
            ['id' => 123, 'district_id' => 33, 'name' => 'Gujar Khan', 'code' => 'RWP-GKH', 'is_active' => true],
            ['id' => 124, 'district_id' => 33, 'name' => 'Kahuta', 'code' => 'RWP-KHT', 'is_active' => true],
            ['id' => 125, 'district_id' => 33, 'name' => 'Murree', 'code' => 'RWP-MRE', 'is_active' => true],
            ['id' => 126, 'district_id' => 33, 'name' => 'Taxila', 'code' => 'RWP-TXL', 'is_active' => true],
            ['id' => 127, 'district_id' => 33, 'name' => 'Kotli Sattian', 'code' => 'RWP-KTS', 'is_active' => true],

            // Murree District (MRE)
            ['id' => 128, 'district_id' => 34, 'name' => 'Murree', 'code' => 'MRE-MRE', 'is_active' => true],

            // Talagang District (TLG)
            ['id' => 129, 'district_id' => 35, 'name' => 'Talagang', 'code' => 'TLG-TLG', 'is_active' => true],

            // Okara District (OKR)
            ['id' => 130, 'district_id' => 36, 'name' => 'Okara', 'code' => 'OKR-OKR', 'is_active' => true],
            ['id' => 131, 'district_id' => 36, 'name' => 'Depalpur', 'code' => 'OKR-DPL', 'is_active' => true],
            ['id' => 132, 'district_id' => 36, 'name' => 'Renala Khurd', 'code' => 'OKR-RKH', 'is_active' => true],

            // Pakpattan District (PKP)
            ['id' => 133, 'district_id' => 37, 'name' => 'Pakpattan', 'code' => 'PKP-PKP', 'is_active' => true],
            ['id' => 134, 'district_id' => 37, 'name' => 'Arifwala', 'code' => 'PKP-ARF', 'is_active' => true],

            // Sahiwal District (SWL)
            ['id' => 135, 'district_id' => 38, 'name' => 'Sahiwal', 'code' => 'SWL-SWL', 'is_active' => true],
            ['id' => 136, 'district_id' => 38, 'name' => 'Chichawatni', 'code' => 'SWL-CHC', 'is_active' => true],
            ['id' => 137, 'district_id' => 38, 'name' => 'Sulemanki', 'code' => 'SWL-SLM', 'is_active' => true],

            // Bhakkar District (BKR)
            ['id' => 138, 'district_id' => 39, 'name' => 'Bhakkar', 'code' => 'BKR-BKR', 'is_active' => true],
            ['id' => 139, 'district_id' => 39, 'name' => 'Darya Khan', 'code' => 'BKR-DYK', 'is_active' => true],
            ['id' => 140, 'district_id' => 39, 'name' => 'Mankera', 'code' => 'BKR-MKR', 'is_active' => true],
            ['id' => 141, 'district_id' => 39, 'name' => 'Kalurkot', 'code' => 'BKR-KLK', 'is_active' => true],

            // Khushab District (KSB)
            ['id' => 142, 'district_id' => 40, 'name' => 'Khushab', 'code' => 'KSB-KSB', 'is_active' => true],
            ['id' => 143, 'district_id' => 40, 'name' => 'Noorpur Thal', 'code' => 'KSB-NPT', 'is_active' => true],
            ['id' => 144, 'district_id' => 40, 'name' => 'Quaidabad', 'code' => 'KSB-QDB', 'is_active' => true],

            // Mianwali District (MWI)
            ['id' => 145, 'district_id' => 41, 'name' => 'Mianwali', 'code' => 'MWI-MWI', 'is_active' => true],
            ['id' => 146, 'district_id' => 41, 'name' => 'Piplan', 'code' => 'MWI-PPL', 'is_active' => true],
            ['id' => 147, 'district_id' => 41, 'name' => 'Isakhel', 'code' => 'MWI-ISK', 'is_active' => true],
            ['id' => 148, 'district_id' => 41, 'name' => 'Mian Khel', 'code' => 'MWI-MKH', 'is_active' => true],

            // Sargodha District (SGD)
            ['id' => 149, 'district_id' => 42, 'name' => 'Sargodha', 'code' => 'SGD-SGD', 'is_active' => true],
            ['id' => 150, 'district_id' => 42, 'name' => 'Bhera', 'code' => 'SGD-BHR', 'is_active' => true],
            ['id' => 151, 'district_id' => 42, 'name' => 'Silanwali', 'code' => 'SGD-SLW', 'is_active' => true],
            ['id' => 152, 'district_id' => 42, 'name' => 'Shahpur', 'code' => 'SGD-SHP', 'is_active' => true],
            ['id' => 153, 'district_id' => 42, 'name' => 'Kot Momin', 'code' => 'SGD-KTM', 'is_active' => true],
            ['id' => 154, 'district_id' => 42, 'name' => 'Sohawa', 'code' => 'SGD-SHW', 'is_active' => true],
            ['id' => 155, 'district_id' => 42, 'name' => 'Bhalwal', 'code' => 'SGD-BHW', 'is_active' => true],
            ['id' => 156, 'district_id' => 42, 'name' => 'Sargodha Cantt', 'code' => 'SGD-CTT', 'is_active' => true],
            ['id' => 157, 'district_id' => 42, 'name' => 'Sargodha Saddar', 'code' => 'SGD-SDR', 'is_active' => true],
        ], ['id'], ['district_id', 'name', 'code', 'is_active']);
    }
}
