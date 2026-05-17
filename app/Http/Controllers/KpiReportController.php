<?php

namespace App\Http\Controllers;

use App\Services\KpiReportService;
use Illuminate\Http\Request;

class KpiReportController extends Controller
{
    protected KpiReportService $kpiReportService;

    public function __construct(KpiReportService $kpiReportService)
    {
        $this->kpiReportService = $kpiReportService;
    }

    /*
    |--------------------------------------------------------------------------
    | Provincial KPI Wise Data
    |--------------------------------------------------------------------------
    | Old PPMF-style category-wise metric cards.
    | This does NOT use submitted/reviewed/approved/rejected inspection statuses.
    */
    public function provincialData(Request $request)
    {
        $filters = $request->only([
            'period_type',
            'kpi_category_id',
            'date_from',
            'date_to',
            'search',
            'per_page',
        ]);

        $reportData = $this->kpiReportService->getProvincialKpiMetrics($filters);
        $summary = $this->kpiReportService->getProvincialKpiMetricSummary($filters);
        $filterData = $this->kpiReportService->getFilterData();

        return view('kpi.provincial-data', [
            'provincialData' => $reportData,
            'reportData' => $reportData,
            'summary' => $summary,
            'districts' => $filterData['districts'],
            'tehsils' => $filterData['tehsils'],
            'kpiCategories' => $filterData['kpiCategories'],
            'filters' => $filters,
        ]);
    }

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
        $filterData = $this->kpiReportService->getFilterData();

        return view('kpi.reporting-status', [
            'reportingStatus' => $reportingStatus,
            'districts' => $filterData['districts'],
            'kpiCategories' => $filterData['kpiCategories'],
            'filters' => $filters,
        ]);
    }

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

        $scope = $this->kpiReportService->getUserScope();
        $summary = $this->kpiReportService->getGraphicalSummary($filters);
        $chartData = $this->kpiReportService->getGraphicalChartData($filters);
        $tableData = $this->kpiReportService->getGraphicalTableData($filters);
        $filterData = $this->kpiReportService->getFilterData();

        return view('kpi.graphical-report', [
            'scope' => $scope,
            'summary' => $summary,
            'chartData' => $chartData,
            'tableData' => $tableData,
            'categoryChart' => $tableData,
            'districts' => $filterData['districts'],
            'tehsils' => $filterData['tehsils'],
            'kpiCategories' => $filterData['kpiCategories'],
            'filters' => $filters,
        ]);
    }
}
