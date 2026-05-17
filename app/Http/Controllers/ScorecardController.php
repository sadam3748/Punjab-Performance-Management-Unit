<?php
namespace App\Http\Controllers;

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
        $filters = $this->scorecardService->normalizeFilters(
            $request->only([
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
            ])
        );

        $filterData = $this->scorecardService->getFilterData();

        return view('scorecard.index', [
            'summary'         => $this->scorecardService->getScorecardSummary($filters),
            'districtRanking' => $this->scorecardService->getDistrictRanking($filters),
            'categoryRanking' => $this->scorecardService->getCategoryRanking($filters),

            'divisions'       => $filterData['divisions'],
            'districts'       => $filterData['districts'],
            'tehsils'         => $filterData['tehsils'],
            'kpiCategories'   => $filterData['kpiCategories'],

            'weekOptions'     => $this->scorecardService->getWeekRanges(
                $request->integer('year') ?: now()->year,
                $request->integer('month') ?: now()->month
            ),

            'filters'         => $filters,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Chief Minister Governance Scorecard Tier Wise
    |--------------------------------------------------------------------------
    */
    public function tierWise(Request $request)
    {
        $filters = $this->scorecardService->normalizeFilters(
            $request->only([
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
            ])
        );

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
                $request->integer('year') ?: now()->year,
                $request->integer('month') ?: now()->month
            ),

            'filters'       => $filters,
        ]);
    }
}
