<?php
namespace App\Http\Controllers;

use App\Models\District;
use App\Models\Division;
use App\Services\ScorecardService;
use Illuminate\Http\Request;

class ScorecardController extends Controller
{
    protected ScorecardService $scorecardService;

    public function __construct(ScorecardService $scorecardService)
    {
        $this->scorecardService = $scorecardService;
    }

    /*
    |--------------------------------------------------------------------------
    | Chief Minister Governance Scorecard
    |--------------------------------------------------------------------------
    */
    public function index(Request $request)
    {
        $input = $request->only([
                'scope',
                'period',
                'week_range',
                'month',
                'year',
                'area_type',
                'division_id',
                'district_id',
                'tehsil_id',
                'kpi_category_id',
                'tier',
                'date_from',
                'date_to',
                'performance',
                'per_page',
                'calculation_type',
        ]);

        $hasAnyFilter = collect($input)->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty();
        $filters = $hasAnyFilter
            ? $this->scorecardService->normalizeFilters($input)
            : $this->scorecardService->getLatestCompletedPpmfWeekFilters();

        $filterData = $this->scorecardService->getFilterData();

        $areaType = (string) ($filters['area_type'] ?? 'district');
        $divisionRanking = $areaType === 'division' ? $this->scorecardService->getDivisionRanking($filters) : null;
        $summary = $areaType === 'division'
            ? $this->scorecardService->getDivisionSummary($filters)
            : $this->scorecardService->getScorecardSummary($filters);

        return view('scorecard.index', [
            'summary'         => $summary,
            'districtRanking' => $this->scorecardService->getDistrictRanking($filters),
            'divisionRanking' => $divisionRanking,
            'categoryRanking' => $this->scorecardService->getCategoryRanking($filters),

            'divisions'       => $filterData['divisions'],
            'districts'       => $filterData['districts'],
            'tehsils'         => $filterData['tehsils'],
            'kpiCategories'   => $filterData['kpiCategories'],

            'weekOptions'     => $this->scorecardService->getWeekRanges(
                (int) ($filters['year'] ?? now()->year),
                (int) ($filters['month'] ?? now()->month)
            ),

            'filters'         => $filters,
        ]);
    }

    public function data(Request $request)
    {
        $input = $request->only([
            'scope',
            'period',
            'week_range',
            'month',
            'year',
            'area_type',
            'division_id',
            'district_id',
            'tehsil_id',
            'kpi_category_id',
            'tier',
            'date_from',
            'date_to',
            'performance',
            'per_page',
            'calculation_type',
        ]);

        $hasAnyFilter = collect($input)->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty();
        $filters = $hasAnyFilter
            ? $this->scorecardService->normalizeFilters($input)
            : $this->scorecardService->getLatestCompletedPpmfWeekFilters();

        $filterData = $this->scorecardService->getFilterData();

        $areaType = (string) ($filters['area_type'] ?? 'district');
        $summary = $areaType === 'division'
            ? $this->scorecardService->getDivisionSummary($filters)
            : $this->scorecardService->getScorecardSummary($filters);
        $districtRanking = $areaType === 'district' ? $this->scorecardService->getDistrictRanking($filters) : null;
        $divisionRanking = $areaType === 'division' ? $this->scorecardService->getDivisionRanking($filters) : null;
        $categoryRanking = $this->scorecardService->getCategoryRanking($filters);

        $weekOptions = $this->scorecardService->getWeekRanges(
            (int) ($filters['year'] ?? now()->year),
            (int) ($filters['month'] ?? now()->month)
        );

        return response()->json([
            'status' => 'success',
            'filters' => $filters,
            'html' => [
                'results' => view('scorecard.partials.scorecard-results', compact('summary', 'districtRanking', 'divisionRanking', 'categoryRanking', 'filters'))->render(),
                'week_options' => view('scorecard.partials.week-options', ['weekOptions' => $weekOptions, 'selectedWeekRange' => (string) ($filters['week_range'] ?? '')])->render(),
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Chief Minister Governance Scorecard Tier Wise
    |--------------------------------------------------------------------------
    */
    public function tierWise(Request $request)
    {
        $input = $request->only([
                'scope',
                'period',
                'week_range',
                'month',
                'year',
                'area_type',
                'division_id',
                'district_id',
                'tehsil_id',
                'kpi_category_id',
                'tier',
                'date_from',
                'date_to',
                'performance',
                'per_page',
                'calculation_type',
        ]);

        $hasAnyFilter = collect($input)->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty();
        $filters = $hasAnyFilter
            ? $this->scorecardService->normalizeFilters($input)
            : $this->scorecardService->getLatestCompletedPpmfWeekFilters();

        // Tier-wise page should open Tier 1 by default and keep selected tier in all filters.
        $filters['tier'] = $filters['tier'] ?? '1';

        $filterData = $this->scorecardService->getFilterData();

        return view('scorecard.tier-wise', [
            'tierSummary'   => $this->scorecardService->getTierSummary($filters),
            'tierRanking'   => $this->scorecardService->getTierDistrictRanking($filters),

            'divisions'     => $filterData['divisions'],
            'districts'     => $filterData['districts'],
            'tehsils'       => $filterData['tehsils'],
            'kpiCategories' => $filterData['kpiCategories'],

            'weekOptions'   => $this->scorecardService->getWeekRanges(
                (int) ($filters['year'] ?? now()->year),
                (int) ($filters['month'] ?? now()->month)
            ),

            'filters'       => $filters,
        ]);
    }

    public function tierWiseData(Request $request)
    {
        $input = $request->only([
            'scope',
            'period',
            'week_range',
            'month',
            'year',
            'area_type',
            'division_id',
            'district_id',
            'tehsil_id',
            'kpi_category_id',
            'tier',
            'date_from',
            'date_to',
            'performance',
            'per_page',
            'calculation_type',
        ]);

        $hasAnyFilter = collect($input)->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty();
        $filters = $hasAnyFilter
            ? $this->scorecardService->normalizeFilters($input)
            : $this->scorecardService->getLatestCompletedPpmfWeekFilters();

        $filters['tier'] = $filters['tier'] ?? '1';

        $tierSummary = $this->scorecardService->getTierSummary($filters);
        $tierRanking = $this->scorecardService->getTierDistrictRanking($filters);

        $weekOptions = $this->scorecardService->getWeekRanges(
            (int) ($filters['year'] ?? now()->year),
            (int) ($filters['month'] ?? now()->month)
        );

        return response()->json([
            'status' => 'success',
            'filters' => $filters,
            'html' => [
                'results' => view('scorecard.partials.tier-results', compact('tierSummary', 'tierRanking', 'filters'))->render(),
                'week_options' => view('scorecard.partials.week-options', ['weekOptions' => $weekOptions, 'selectedWeekRange' => (string) ($filters['week_range'] ?? '')])->render(),
            ],
        ]);
    }

    public function divisionDetail(Request $request, Division $division)
    {
        $input = $request->only([
            'scope',
            'period',
            'week_range',
            'month',
            'year',
            'area_type',
            'division_id',
            'district_id',
            'tehsil_id',
            'kpi_category_id',
            'tier',
            'date_from',
            'date_to',
            'performance',
            'per_page',
            'calculation_type',
        ]);

        $hasAnyFilter = collect($input)->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty();
        $filters = $hasAnyFilter
            ? $this->scorecardService->normalizeFilters($input)
            : $this->scorecardService->getLatestCompletedPpmfWeekFilters();

        $filters['division_id'] = $division->id;
        $filters['area_type'] = 'district';

        $detail = $this->scorecardService->getDivisionScorecardDetail($division, $filters);

        return view('scorecard.division-detail', [
            'division' => $division,
            'filters' => $filters,
            ...$detail,
        ]);
    }

    public function districtDetail(Request $request, District $district)
    {
        $input = $request->only([
            'scope',
            'period',
            'week_range',
            'month',
            'year',
            'area_type',
            'division_id',
            'district_id',
            'tehsil_id',
            'kpi_category_id',
            'tier',
            'date_from',
            'date_to',
            'performance',
            'per_page',
            'calculation_type',
        ]);

        $hasAnyFilter = collect($input)->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty();
        $filters = $hasAnyFilter
            ? $this->scorecardService->normalizeFilters($input)
            : $this->scorecardService->getLatestCompletedPpmfWeekFilters();

        $detail = $this->scorecardService->getDistrictScorecardDetail($district, $filters);

        return view('scorecard.district-detail', [
            'district' => $district,
            'filters' => $filters,
            ...$detail,
        ]);
    }
}
