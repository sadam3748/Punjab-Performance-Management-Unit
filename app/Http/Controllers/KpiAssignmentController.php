<?php

namespace App\Http\Controllers;

use App\Models\KpiAssignment;
use App\Models\KpiCard;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KpiAssignmentController extends Controller
{
    public function index(Request $request)
    {
        $this->admin($request);
        $cards = KpiCard::orderBy('display_order')->get();
        $roles = Role::whereIn('slug', ['chief_secretary', 'commissioner', 'dc', 'ac'])->orderBy('id')->get();
        $assigned = KpiAssignment::where('is_active', true)->whereNull('user_id')->get()->mapWithKeys(fn ($item) => [$item->kpi_card_id.'-'.$item->role_id => true]);
        return view('kpi-assignments.index', compact('cards', 'roles', 'assigned'));
    }

    public function update(Request $request)
    {
        $this->admin($request);
        $data = $request->validate(['assignments' => ['nullable', 'array'], 'assignments.*' => ['string']]);
        $selected = collect($data['assignments'] ?? []);
        $cardIds = KpiCard::pluck('id');
        $roleIds = Role::whereIn('slug', ['chief_secretary', 'commissioner', 'dc', 'ac'])->pluck('id');
        DB::transaction(function () use ($selected, $cardIds, $roleIds) {
            foreach ($cardIds as $cardId) foreach ($roleIds as $roleId) {
                $active = $selected->contains($cardId.'-'.$roleId);
                KpiAssignment::updateOrCreate(['kpi_card_id' => $cardId, 'role_id' => $roleId, 'user_id' => null, 'division_id' => null, 'district_id' => null, 'tehsil_id' => null], ['is_active' => $active]);
            }
        });
        return back()->with('success', 'KPI role assignments updated.');
    }

    private function admin(Request $request): void { abort_unless($request->user()?->role?->slug === 'super_admin', 403); }
}
