<?php
namespace App\Http\Controllers;

use App\Services\KpiReportService;
use Illuminate\Http\Request;

class KpiReportController extends Controller
{
    protected KpiReportService $kpiReportService;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    | Inject service so report calculation/query logic stays outside controller.
    */
    public function __construct(KpiReportService $kpiReportService)
    {
        $this->kpiReportService = $kpiReportService;
    }

    /*
    |--------------------------------------------------------------------------
    | Provincial KPI Wise Data
    |--------------------------------------------------------------------------
    | Shows KPI/category-wise Punjab-level inspection summary.
    */
    public function provincialData(Request $request)
    {
        $filters = $request->only([
            'district_id',
            'tehsil_id',
            'kpi_category_id',
            'date_from',
            'date_to',
            'status',
            'search',
            'period',
            'year',
            'per_page',
        ]);

        $reportData = $this->kpiReportService->getProvincialKpiWiseData($filters);
        $summary    = $this->kpiReportService->getProvincialKpiSummary($filters);
        $filterData = $this->kpiReportService->getFilterData();

        return view('kpi.provincial-data', [
            'provincialData' => $reportData,
            'reportData'     => $reportData,
            'summary'        => $summary,
            'districts'      => $filterData['districts'],
            'tehsils'        => $filterData['tehsils'],
            'kpiCategories'  => $filterData['kpiCategories'],
            'filters'        => $filters,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | KPI Reporting Status
    |--------------------------------------------------------------------------
    | Shows reporting status based on inspection availability by district/category.
    | Currently calculated from inspections table.
    */
    public function reportingStatus(Request $request)
    {
        $filters = $request->only([
            'district_id',
            'kpi_category_id',
            'date_from',
            'date_to',
            'status',
        ]);

        $reportingStatus = $this->kpiReportService->getKpiReportingStatus($filters);
        $filterData      = $this->kpiReportService->getFilterData();

        return view('kpi.reporting-status', [
            'reportingStatus' => $reportingStatus,
            'districts'       => $filterData['districts'],
            'kpiCategories'   => $filterData['kpiCategories'],
            'filters'         => $filters,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | KPI Graphical Report
    |--------------------------------------------------------------------------
    | One common page. Scope/title changes based on logged-in user role.
    */
    public function graphicalReport(Request $request)
    {
        $filters = $request->only([
            'district_id',
            'tehsil_id',
            'kpi_category_id',
            'date_from',
            'date_to',
            'period',
        ]);

        $scope      = $this->kpiReportService->getUserScope();
        $summary    = $this->kpiReportService->getGraphicalSummary($filters);
        $chartData  = $this->kpiReportService->getGraphicalChartData($filters);
        $tableData  = $this->kpiReportService->getGraphicalTableData($filters);
        $filterData = $this->kpiReportService->getFilterData();

        return view('kpi.graphical-report', [
            'scope'         => $scope,
            'summary'       => $summary,
            'chartData'     => $chartData,
            'tableData'     => $tableData,
            // Backward-compatible variable names used by the view.
            'categoryChart' => $tableData,
            'districts'     => $filterData['districts'],
            'tehsils'       => $filterData['tehsils'],
            'kpiCategories' => $filterData['kpiCategories'],
            'filters'       => $filters,
        ]);
    }
}
