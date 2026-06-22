<?php

namespace App\Http\Controllers;

use App\Models\KpiCard;
use App\Services\KpiDashboardService;
use Illuminate\Http\Request;

class KpiDashboardController extends Controller
{
    public function show(Request $request, KpiCard $kpiCard, KpiDashboardService $service)
    {
        $user = $request->user()->loadMissing(['role', 'division', 'district', 'tehsil']);
        abort_unless($service->canAccess($user, $kpiCard), 403);
        $data = $service->detail($kpiCard, $user, $request);
        $location = $user->tehsil?->name ?? $user->district?->name ?? $user->division?->name ?? 'Punjab';
        return view('dashboard.kpi-detail', compact('kpiCard', 'user', 'location') + $data);
    }
}
