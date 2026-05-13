<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PpmfDummyGeoTaggingSeeder extends Seeder
{
    public function run(): void
    {
        $lahoreDistrict = DB::table('districts')->whereRaw('LOWER(name) = ?', ['lahore'])->first();
        $layyahDistrict = DB::table('districts')->whereRaw('LOWER(name) = ?', ['layyah'])->first();

        $districts = collect([$lahoreDistrict, $layyahDistrict])->filter();

        if ($districts->isEmpty()) {
            return;
        }

        $users = DB::table('users')
            ->whereIn('role_id', [6, 7])
            ->get();

        if ($users->isEmpty()) {
            return;
        }

        $typeIds = DB::table('geo_tagging_types')->pluck('id')->toArray();

        if (empty($typeIds)) {
            return;
        }

        DB::table('geo_taggings')->where('remarks', 'LIKE', 'Bulk dummy geo-tagging%')->delete();

        $records = [];

        for ($i = 1; $i <= 100; $i++) {
            $district = $i <= 65 ? $layyahDistrict : $lahoreDistrict;

            if (! $district) {
                $district = $districts->random();
            }

            $tehsil = DB::table('tehsils')
                ->where('district_id', $district->id)
                ->inRandomOrder()
                ->first();

            $user = $users->random();

            $typeId = $typeIds[array_rand($typeIds)];

            $taggedAt = now()
                ->subDays(rand(0, 45))
                ->setTime(rand(8, 18), rand(0, 59), 0);

            $records[] = [
                'geo_tagging_type_id' => $typeId,
                'division_id'         => $district->division_id,
                'district_id'         => $district->id,
                'tehsil_id'           => $tehsil?->id,
                'performed_by'        => $user->id,
                'name'                => 'Geo Tagged Asset ' . $i,
                'address'             => 'Geo Asset Address ' . $i . ', ' . $district->name,
                'latitude'            => $district->name === 'Layyah'
                    ? 30.9648 + (rand(-100, 100) / 10000)
                    : 31.5204 + (rand(-100, 100) / 10000),
                'longitude'           => $district->name === 'Layyah'
                    ? 70.9399 + (rand(-100, 100) / 10000)
                    : 74.3587 + (rand(-100, 100) / 10000),
                'tagged_at'           => $taggedAt,
                'detail_data'         => json_encode([
                    'asset_code'          => 'GEO-' . str_pad($i, 5, '0', STR_PAD_LEFT),
                    'ownership'           => collect(['Government', 'Private', 'Semi-Government'])->random(),
                    'verification_status' => collect(['Verified', 'Pending', 'Needs Review'])->random(),
                ]),
                'status'              => collect(['submitted', 'verified', 'rejected'])->random(),
                'remarks'             => 'Bulk dummy geo-tagging record for dashboard testing.',
                'created_at'          => now(),
                'updated_at'          => now(),
            ];
        }

        foreach (array_chunk($records, 50) as $chunk) {
            DB::table('geo_taggings')->insert($chunk);
        }
    }
}
