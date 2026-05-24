<?php
namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    protected ReportService $reportService;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    | Report query/calculation logic stays inside ReportService.
    */
    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /*
    |--------------------------------------------------------------------------
    | Reports Main Page
    |--------------------------------------------------------------------------
    | General reports landing page.
    */
    public function index(Request $request)
    {
        $filters = $this->getCommonFilters($request);

        $summary    = $this->reportService->getReportSummary($filters);
        $filterData = $this->reportService->getFilterData();

        return view('reports.index', [
            'summary'       => $summary,
            'districts'     => $filterData['districts'],
            'tehsils'       => $filterData['tehsils'],
            'kpiCategories' => $filterData['kpiCategories'],
            'filters'       => $filters,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Category Wise District Score Report
    |--------------------------------------------------------------------------
    | Shows district performance by KPI category.
    */
    public function categoryWiseDistrictScore(Request $request)
    {
        $filters = $this->getCommonFilters($request);

        $reportData = $this->reportService->getCategoryWiseDistrictScore($filters);
        $filterData = $this->reportService->getFilterData();

        return view('reports.category-wise-district-score', [
            'reportData'    => $reportData,
            'districts'     => $filterData['districts'],
            'tehsils'       => $filterData['tehsils'],
            'kpiCategories' => $filterData['kpiCategories'],
            'filters'       => $filters,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | District SFN Victim Tier Report
    |--------------------------------------------------------------------------
    | Placeholder report method. Uses district tier and inspection counts.
    */
    public function districtSfnVictimTier(Request $request)
    {
        $filters = $this->getCommonFilters($request);

        $reportData = $this->reportService->getDistrictTierReport($filters);
        $filterData = $this->reportService->getFilterData();

        return view('reports.district-sfn-victim-tier', [
            'reportData'    => $reportData,
            'districts'     => $filterData['districts'],
            'tehsils'       => $filterData['tehsils'],
            'kpiCategories' => $filterData['kpiCategories'],
            'filters'       => $filters,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | District SFN Comparison Report
    |--------------------------------------------------------------------------
    */
    public function districtSfnComparison(Request $request)
    {
        $filters = $this->getCommonFilters($request);

        $reportData = $this->reportService->getDistrictComparison($filters);
        $filterData = $this->reportService->getFilterData();

        return view('reports.district-sfn-comparison', [
            'reportData'    => $reportData,
            'districts'     => $filterData['districts'],
            'tehsils'       => $filterData['tehsils'],
            'kpiCategories' => $filterData['kpiCategories'],
            'filters'       => $filters,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Division Score Report
    |--------------------------------------------------------------------------
    */
    public function divisionScore(Request $request)
    {
        $filters = $this->getCommonFilters($request);

        $reportData = $this->reportService->getDivisionScore($filters);
        $filterData = $this->reportService->getFilterData();

        return view('reports.division-score', [
            'reportData'    => $reportData,
            'districts'     => $filterData['districts'],
            'kpiCategories' => $filterData['kpiCategories'],
            'filters'       => $filters,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | District Comparison Report
    |--------------------------------------------------------------------------
    */
    public function districtComparison(Request $request)
    {
        $filters = $this->getCommonFilters($request);

        $reportData = $this->reportService->getDistrictComparison($filters);
        $filterData = $this->reportService->getFilterData();

        return view('reports.district-comparison', [
            'reportData'    => $reportData,
            'districts'     => $filterData['districts'],
            'kpiCategories' => $filterData['kpiCategories'],
            'filters'       => $filters,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | District Accumulative Report
    |--------------------------------------------------------------------------
    */
    public function districtAccumulative(Request $request)
    {
        $filters = $this->getCommonFilters($request);

        $reportData = $this->reportService->getDistrictAccumulative($filters);
        $filterData = $this->reportService->getFilterData();

        return view('reports.district-accumulative', [
            'reportData'    => $reportData,
            'districts'     => $filterData['districts'],
            'kpiCategories' => $filterData['kpiCategories'],
            'filters'       => $filters,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Division KPI Ranking Report
    |--------------------------------------------------------------------------
    */
    public function divisionKpiRanking(Request $request)
    {
        $filters = $this->getCommonFilters($request);

        $reportData = $this->reportService->getDivisionKpiRanking($filters);
        $filterData = $this->reportService->getFilterData();

        return view('reports.division-kpi-ranking', [
            'reportData'    => $reportData,
            'districts'     => $filterData['districts'],
            'kpiCategories' => $filterData['kpiCategories'],
            'filters'       => $filters,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | District Weekly KPI Inspection Report
    |--------------------------------------------------------------------------
    */
    public function districtWeeklyKpiInspection(Request $request)
    {
        $filters = $this->getCommonFilters($request);

        $reportData = $this->reportService->getDistrictWeeklyKpiInspection($filters);
        $filterData = $this->reportService->getFilterData();

        return view('reports.district-weekly-kpi-inspection', [
            'reportData'    => $reportData,
            'districts'     => $filterData['districts'],
            'kpiCategories' => $filterData['kpiCategories'],
            'filters'       => $filters,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | District Week Rank Changelog Report
    |--------------------------------------------------------------------------
    */
    public function districtWeekRankChangelog(Request $request)
    {
        $filters = $this->getCommonFilters($request);

        $reportData = $this->reportService->getDistrictWeekRankChangelog($filters);
        $filterData = $this->reportService->getFilterData();

        return view('reports.district-week-rank-changelog', [
            'reportData'    => $reportData,
            'districts'     => $filterData['districts'],
            'tehsils'       => $filterData['tehsils'],
            'kpiCategories' => $filterData['kpiCategories'],
            'filters'       => $filters,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | District Wise KPI Score Report
    |--------------------------------------------------------------------------
    */
    public function districtWiseKpiScore(Request $request)
    {
        $filters = $this->getCommonFilters($request);

        $reportData = $this->reportService->getDistrictWiseKpiScore($filters);
        $filterData = $this->reportService->getFilterData();

        return view('reports.district-wise-kpi-score', [
            'reportData'    => $reportData,
            'districts'     => $filterData['districts'],
            'tehsils'       => $filterData['tehsils'],
            'kpiCategories' => $filterData['kpiCategories'],
            'filters'       => $filters,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Common Filter Helper
    |--------------------------------------------------------------------------
    | Keeps controller methods clean.
    */
    private function getCommonFilters(Request $request): array
    {
        return $request->only([
            'division_id',
            'district_id',
            'tehsil_id',
            'kpi_category_id',
            'status',
            'tier',
            'date_from',
            'date_to',
            'year',
            'search',
        ]);
    }
}
