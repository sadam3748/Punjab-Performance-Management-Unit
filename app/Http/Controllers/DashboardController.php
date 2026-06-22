<?php

namespace App\Http\Controllers;

use App\Services\KpiDashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected KpiDashboardService $dashboardService;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    | Dashboard query/calculation logic is handled inside DashboardService.
    */
    public function __construct(KpiDashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /*
    |--------------------------------------------------------------------------
    | Dashboard / Overview Page
    |--------------------------------------------------------------------------
    | Shows summary cards, charts, recent inspections and geo-tagging summary.
    */
    public function index(Request $request)
    {
        $user = $request->user()->loadMissing(['role', 'division', 'district', 'tehsil']);
        $cards = $this->dashboardService->assignedCards($user, $request);
        $location = $user->tehsil?->name ?? $user->district?->name ?? $user->division?->name ?? 'Punjab';
        $filters = $this->dashboardService->filterOptionsForView();
        $period = $this->dashboardService->periodState($request);

        return view('dashboard.index', compact('user', 'cards', 'location', 'filters', 'period'));
    }
}
