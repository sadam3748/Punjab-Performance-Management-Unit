<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KpiAssignmentSeeder extends Seeder
{
    public function run(): void
    {
        $roleIds = DB::table('roles')->whereIn('slug', ['chief_secretary', 'commissioner', 'dc', 'ac'])->pluck('id');
        foreach (DB::table('kpi_cards')->pluck('id') as $cardId) {
            foreach ($roleIds as $roleId) {
                $exists = DB::table('kpi_assignments')->where(['kpi_card_id' => $cardId, 'role_id' => $roleId])->whereNull('user_id')->exists();
                if (! $exists) DB::table('kpi_assignments')->insert(['kpi_card_id' => $cardId, 'role_id' => $roleId, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }
}
