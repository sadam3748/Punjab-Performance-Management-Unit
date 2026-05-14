<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected DashboardService $dashboardService;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    | Dashboard query/calculation logic is handled inside DashboardService.
    */
    public function __construct(DashboardService $dashboardService)
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
        $filters = $request->only([
            'district_id',
            'tehsil_id',
            'kpi_category_id',
            'date_from',
            'date_to',
            'period',
        ]);

        $summary = $this->dashboardService->getSummaryCards($filters);
        $statusChart = $this->dashboardService->getInspectionStatusChart($filters);
        $categoryChart = $this->dashboardService->getCategoryWiseChart($filters);
        $districtChart = $this->dashboardService->getDistrictWiseChart($filters);
        $geoTaggingSummary = $this->dashboardService->getGeoTaggingSummary($filters);
        $baselineSummary = $this->dashboardService->getBaselineSummary($filters);
        $recentInspections = $this->dashboardService->getRecentInspections($filters);
        $filterData = $this->dashboardService->getFilterData();

        return view('dashboard.index', [
            'summary' => $summary,
            'statusChart' => $statusChart,
            'categoryChart' => $categoryChart,
            'districtChart' => $districtChart,
            'geoTaggingSummary' => $geoTaggingSummary,
            'baselineSummary' => $baselineSummary,
            'recentInspections' => $recentInspections,
            'districts' => $filterData['districts'],
            'tehsils' => $filterData['tehsils'],
            'kpiCategories' => $filterData['kpiCategories'],
            'filters' => $filters,
        ]);
    }
}
