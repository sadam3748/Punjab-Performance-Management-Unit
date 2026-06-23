<?php

namespace App\Http\Controllers;

use App\Services\KpiDashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private readonly KpiDashboardService $dashboardService) {}

    public function index(Request $request)
    {
        $user = $request->user()->loadMissing(['role', 'division', 'district', 'tehsil']);
        $cards = $this->dashboardService->assignedCards($user, $request);
        $location = $user->tehsil?->name ?? $user->district?->name ?? $user->division?->name ?? 'Punjab';
        $filters = $this->dashboardService->filterOptionsForView();
        $period = $this->dashboardService->periodState($request);
        $period_description = $this->dashboardService->periodDescription($request);
        $periodQuery = $this->dashboardService->periodQueryString($request);

        return view('dashboard.index', compact('user', 'cards', 'location', 'filters', 'period', 'period_description', 'periodQuery'));
    }

    public function data(Request $request)
    {
        $user = $request->user()->loadMissing(['role', 'division', 'district', 'tehsil']);
        $cards = $this->dashboardService->assignedCards($user, $request);
        $periodQuery = $this->dashboardService->periodQueryString($request);

        return response()->json([
            'cards_html' => view('dashboard.partials.kpi-grid', [
                'cards' => $cards,
                'periodQuery' => $periodQuery,
            ])->render(),
            'cards_count' => $cards->count(),
            'period_description' => $this->dashboardService->periodDescription($request),
            'period' => $this->dashboardService->periodState($request),
            'period_query' => $periodQuery,
        ]);
    }
}
