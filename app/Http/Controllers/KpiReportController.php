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
            'provincialData' => $reportData, // backward compat
            'reportData'     => $reportData,
            'summary'        => $summary,
            'kpiCategories'  => $filterData['kpiCategories'],
            'filters'        => $filters,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | District Wise KPI Score Report
    |--------------------------------------------------------------------------
    | Old PPMF-style district-wise KPI score breakdown (no inspection statuses).
    */
    public function districtWiseKpiScore(Request $request)
    {
        $filters = $request->only([
            'kpi_category_id',
            'period_type',
            'date_from',
            'date_to',
            'district_id',
            'search',
            'per_page',
        ]);

        $filterData = $this->kpiReportService->getFilterData();

        if (empty($filters['kpi_category_id'])) {
            $period = $filters['period_type'] ?? 'last_week';
            $first = \App\Models\KpiCategory::where('is_active', true)
                ->whereHas('provincialMetrics', function ($q) use ($period) {
                    $q->where('is_active', true)->where('period_type', $period);
                })
                ->orderBy('name')
                ->first(['id']);

            if ($first) {
                $filters['kpi_category_id'] = $first->id;
            } else {
                $fallback = $filterData['kpiCategories']->first();
                if ($fallback) {
                    $filters['kpi_category_id'] = $fallback->id;
                }
            }
        }

        $scoreData = $this->kpiReportService->getDistrictWiseKpiScore($filters);
        $selectedCategory = $filterData['kpiCategories']->firstWhere('id', (int) ($filters['kpi_category_id'] ?? 0));
        $summary = [
            'total_districts'     => method_exists($scoreData['paginator'], 'total') ? $scoreData['paginator']->total() : 0,
            'total_metric_cards'  => count($scoreData['metricTitles'] ?? []),
            'active_period'       => $filters['period_type'] ?? 'last_week',
        ];

        return view('kpi.district-wise-kpi-score', [
            'reportData'       => $scoreData['paginator'],
            'metricTitles'     => $scoreData['metricTitles'],
            'metricCards'      => $scoreData['metricCards'],
            'kpiCategories'    => $filterData['kpiCategories'],
            'districts'        => $filterData['districts'],
            'selectedCategory' => $selectedCategory,
            'filters'          => $filters,
            'summary'          => $summary,
        ]);
    }

    public function reportingStatus(Request $request)
    {
        $filters = $request->only([
            'district_id',
            'tehsil_id',
            'kpi_category_id',
            'date_from',
            'date_to',
            'status',
            'search',
            'per_page',
        ]);

        $reportingStatus = $this->kpiReportService->getKpiReportingStatus($filters);
        $filterData = $this->kpiReportService->getFilterData();

        return view('kpi.reporting-status', [
            'reportingStatus' => $reportingStatus,
            'districts' => $filterData['districts'],
            'tehsils' => $filterData['tehsils'],
            'kpiCategories' => $filterData['kpiCategories'],
            'filters' => $filters,
        ]);
    }

    public function graphicalReport(Request $request)
    {
        $filters = $request->only([
            'kpi_category_id',
            'period_type',
            'date_from',
            'date_to',
            'month',
            'year',
            'district_id',
            'tehsil_id',
            'per_page',
            'search',
        ]);

        if (empty($filters['period_type'])) {
            $filters['period_type'] = 'last_week';
        }

        if (empty($filters['per_page'])) {
            $filters['per_page'] = 10;
        }

        $graphFilters = $this->kpiReportService->getGraphicalFilters();

        if (empty($filters['kpi_category_id'])) {
            // Prefer a category that has seeded KPI metric cards for selected period.
            $periodType = $filters['period_type'] ?? 'last_week';
            $preferred = \App\Models\KpiCategory::where('is_active', true)
                ->where('name', 'Functional and Clean Water Filtration Plants')
                ->whereHas('provincialMetrics', fn ($q) => $q->where('is_active', true)->where('period_type', $periodType))
                ->whereHas('districtKpiMetricValues', fn ($q) => $q->where('is_active', true)->where('period_type', $periodType))
                ->first(['id']);

            $firstWithData = \App\Models\KpiCategory::where('is_active', true)
                ->whereHas('provincialMetrics', fn ($q) => $q->where('is_active', true)->where('period_type', $periodType))
                ->whereHas('districtKpiMetricValues', fn ($q) => $q->where('is_active', true)->where('period_type', $periodType))
                ->orderBy('name')
                ->first(['id']);

            if ($preferred) {
                $filters['kpi_category_id'] = $preferred->id;
            } elseif ($firstWithData) {
                $filters['kpi_category_id'] = $firstWithData->id;
            } else {
                $first = $graphFilters['kpiCategories']->first();
                if ($first) {
                    $filters['kpi_category_id'] = $first->id;
                }
            }
        }

        $scopeTitle = $this->kpiReportService->getGraphicalScopeTitle($filters);
        $summaryCards = $this->kpiReportService->getGraphicalSummaryCards($filters);
        $chartData = $this->kpiReportService->getGraphicalChartData($filters);
        $tableData = $this->kpiReportService->getGraphicalInspectionRecords($filters);

        return view('kpi.graphical-report', [
            'filters'       => $filters,
            'scopeTitle'    => $scopeTitle,
            'periodOptions' => $graphFilters['periodOptions'],
            'months'        => $graphFilters['months'],
            'years'         => $graphFilters['years'],
            'kpiCategories' => $graphFilters['kpiCategories'],
            'districts'     => $graphFilters['districts'],
            'tehsils'       => $graphFilters['tehsils'],
            'summaryCards'  => $summaryCards ?? [],
            'chartData'     => $chartData,
            'tableData'     => $tableData,
        ]);
    }
}
