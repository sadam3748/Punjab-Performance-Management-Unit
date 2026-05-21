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
            'week_no',
            'month',
            'year',
            'per_page',
        ]);

        if (empty($filters['period_type'])) {
            $filters['period_type'] = 'weekly';
        }

        if (empty($filters['per_page'])) {
            $filters['per_page'] = 10;
        }

        $graphFilters = $this->kpiReportService->getGraphicalFilters();

        if (empty($filters['kpi_category_id'])) {
            $first = $graphFilters['kpiCategories']->first();
            if ($first) $filters['kpi_category_id'] = $first->id;
        }

        // Old PPMF default: weekly + latest completed Thu->Wed week (week_no is ISO week key YYYYWW).
        $scorecardService = app(\App\Services\ScorecardService::class);
        if (($filters['period_type'] ?? '') === 'weekly' && empty($filters['week_no'])) {
            $default = $scorecardService->getLatestCompletedPpmfWeekFilters();
            $filters['week_no'] = $default['week_no'] ?? null;
            $filters['year'] = $default['year'] ?? null;
            $filters['month'] = $default['month'] ?? null;
        }

        if (($filters['period_type'] ?? '') === 'weekly' && ! empty($filters['week_no']) && (empty($filters['year']) || empty($filters['month']))) {
            $range = $scorecardService->getWeekDateRange((string) $filters['week_no']);
            if (! empty($range['start'])) {
                $filters['year'] = (int) $range['start']->format('Y');
                $filters['month'] = (int) $range['start']->format('n');
            }
        }

        $weekOptions = [];
        if (($filters['period_type'] ?? '') === 'weekly') {
            $y = ! empty($filters['year']) ? (int) $filters['year'] : (int) now()->format('Y');
            for ($m = 1; $m <= 12; $m++) {
                $weekOptions = array_replace($weekOptions, $scorecardService->getWeekRanges($y, $m));
            }
            ksort($weekOptions);
        }

        $scopeTitle = 'PUNJAB';
        $summaryCards = $this->kpiReportService->getGraphicalSummaryCards($filters);
        $chartData = $this->kpiReportService->getGraphicalChartData($filters);
        $tableData = $this->kpiReportService->getGraphicalInspectionRecords($filters);

        return view('kpi.graphical-report', [
            'filters'       => $filters,
            'scopeTitle'    => $scopeTitle,
            'periodOptions' => $graphFilters['periodOptions'],
            'weekOptions'   => $weekOptions,
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

    public function graphicalReportData(Request $request)
    {
        $filters = $request->only([
            'kpi_category_id',
            'period_type',
            'week_no',
            'month',
            'year',
            'per_page',
            'page',
        ]);

        if (empty($filters['period_type'])) {
            $filters['period_type'] = 'weekly';
        }
        if (empty($filters['per_page'])) {
            $filters['per_page'] = 10;
        }

        $graphFilters = $this->kpiReportService->getGraphicalFilters();
        if (empty($filters['kpi_category_id'])) {
            $first = $graphFilters['kpiCategories']->first();
            if ($first) $filters['kpi_category_id'] = $first->id;
        }

        $scorecardService = app(\App\Services\ScorecardService::class);
        if (($filters['period_type'] ?? '') === 'weekly' && empty($filters['week_no'])) {
            $default = $scorecardService->getLatestCompletedPpmfWeekFilters();
            $filters['week_no'] = $default['week_no'] ?? null;
            $filters['year'] = $default['year'] ?? null;
            $filters['month'] = $default['month'] ?? null;
        }

        if (($filters['period_type'] ?? '') === 'weekly' && ! empty($filters['week_no']) && (empty($filters['year']) || empty($filters['month']))) {
            $range = $scorecardService->getWeekDateRange((string) $filters['week_no']);
            if (! empty($range['start'])) {
                $filters['year'] = (int) $range['start']->format('Y');
                $filters['month'] = (int) $range['start']->format('n');
            }
        }

        $summaryCards = $this->kpiReportService->getGraphicalSummaryCards($filters);
        $chartData = $this->kpiReportService->getGraphicalChartData($filters);
        $tableData = $this->kpiReportService->getGraphicalInspectionRecords($filters);

        $html = view('kpi.partials._graphical-report-content', [
            'filters' => $filters,
            'scopeTitle' => 'PUNJAB',
            'summaryCards' => $summaryCards ?? [],
            'chartData' => $chartData,
            'tableData' => $tableData,
            'kpiCategories' => $graphFilters['kpiCategories'],
        ])->render();

        return response()->json([
            'status' => 'success',
            'html' => $html,
            'filters' => $filters,
            'chartData' => $chartData,
        ]);
    }
}
