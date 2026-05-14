<?php
namespace App\Http\Controllers;

use App\Services\ScorecardService;
use Illuminate\Http\Request;

class ScorecardController extends Controller
{
    protected ScorecardService $scorecardService;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    | ScorecardService handles ranking/score calculation logic.
    */
    public function __construct(ScorecardService $scorecardService)
    {
        $this->scorecardService = $scorecardService;
    }

    /*
    |--------------------------------------------------------------------------
    | CM Governance Scorecard
    |--------------------------------------------------------------------------
    | Main scorecard page. Shows district/category score based on inspections.
    */
    public function index(Request $request)
    {
        $filters = $request->only([
            'division_id',
            'district_id',
            'tehsil_id',
            'kpi_category_id',
            'tier',
            'date_from',
            'date_to',
            'period',
        ]);

        $summary         = $this->scorecardService->getScorecardSummary($filters);
        $districtRanking = $this->scorecardService->getDistrictRanking($filters);
        $categoryRanking = $this->scorecardService->getCategoryRanking($filters);
        $filterData      = $this->scorecardService->getFilterData();

        return view('scorecard.index', [
            'summary'         => $summary,
            'districtRanking' => $districtRanking,
            'categoryRanking' => $categoryRanking,
            'divisions'       => $filterData['divisions'],
            'districts'       => $filterData['districts'],
            'tehsils'         => $filterData['tehsils'],
            'kpiCategories'   => $filterData['kpiCategories'],
            'filters'         => $filters,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Tier Wise Scorecard
    |--------------------------------------------------------------------------
    | Shows district ranking by Tier 1, Tier 2, Tier 3.
    */
    public function tierWise(Request $request)
    {
        $filters = $request->only([
            'tier',
            'division_id',
            'district_id',
            'kpi_category_id',
            'date_from',
            'date_to',
            'period',
        ]);

        $tierSummary = $this->scorecardService->getTierSummary($filters);
        $tierRanking = $this->scorecardService->getTierDistrictRanking($filters);
        $filterData  = $this->scorecardService->getFilterData();

        return view('scorecard.tier-wise', [
            'tierSummary'   => $tierSummary,
            'tierRanking'   => $tierRanking,
            'divisions'     => $filterData['divisions'],
            'districts'     => $filterData['districts'],
            'kpiCategories' => $filterData['kpiCategories'],
            'filters'       => $filters,
        ]);
    }
}
