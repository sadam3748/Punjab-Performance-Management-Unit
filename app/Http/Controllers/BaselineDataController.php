<?php
namespace App\Http\Controllers;

use App\Services\BaselineDataService;
use Illuminate\Http\Request;

class BaselineDataController extends Controller
{
    protected BaselineDataService $baselineDataService;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    | BaselineDataService handles all baseline query and save logic.
    */
    public function __construct(BaselineDataService $baselineDataService)
    {
        $this->baselineDataService = $baselineDataService;
    }

    /*
    |--------------------------------------------------------------------------
    | District Baseline Data List
    |--------------------------------------------------------------------------
    | Shows district-wise baseline summary records.
    */
    public function index(Request $request)
    {
        $filters = $request->only([
            'district_id',
            'kpi_category_id',
            'year',
            'search',
        ]);

        $baselineData = $this->baselineDataService->getDistrictBaselineList($filters);
        $summary      = $this->baselineDataService->getBaselineSummary($filters);
        $filterData   = $this->baselineDataService->getFilterData();

        return view('baseline.index', [
            'baselineData'  => $baselineData,
            'summary'       => $summary,
            'districts'     => $filterData['districts'],
            'kpiCategories' => $filterData['kpiCategories'],
            'filters'       => $filters,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Create District Baseline Data
    |--------------------------------------------------------------------------
    */
    public function create()
    {
        $filterData = $this->baselineDataService->getFilterData();

        return view('baseline.create', [
            'districts'     => $filterData['districts'],
            'kpiCategories' => $filterData['kpiCategories'],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Store District Baseline Data
    |--------------------------------------------------------------------------
    | Stores baseline summary data in JSON format.
    */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'district_id'     => ['required', 'exists:districts,id'],
            'kpi_category_id' => ['required', 'exists:kpi_categories,id'],
            'year'            => ['required', 'integer', 'min:2020', 'max:2100'],
            'baseline_data'   => ['required', 'array'],
        ]);

        $this->baselineDataService->storeDistrictBaseline($validated);

        return redirect()
            ->route('baseline.index')
            ->with('success', 'District baseline data saved successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | Show District Baseline Data
    |--------------------------------------------------------------------------
    */
    public function show($id)
    {
        $baseline = $this->baselineDataService->getDistrictBaselineDetail($id);

        return view('baseline.show', [
            'baseline' => $baseline,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Edit District Baseline Data
    |--------------------------------------------------------------------------
    */
    public function edit($id)
    {
        $baseline   = $this->baselineDataService->getDistrictBaselineDetail($id);
        $filterData = $this->baselineDataService->getFilterData();

        return view('baseline.edit', [
            'baseline'      => $baseline,
            'districts'     => $filterData['districts'],
            'kpiCategories' => $filterData['kpiCategories'],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Update District Baseline Data
    |--------------------------------------------------------------------------
    */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'district_id'     => ['required', 'exists:districts,id'],
            'kpi_category_id' => ['required', 'exists:kpi_categories,id'],
            'year'            => ['required', 'integer', 'min:2020', 'max:2100'],
            'baseline_data'   => ['required', 'array'],
        ]);

        $this->baselineDataService->updateDistrictBaseline($id, $validated);

        return redirect()
            ->route('baseline.index')
            ->with('success', 'District baseline data updated successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | Baseline Asset List
    |--------------------------------------------------------------------------
    | Shows asset-level baseline records, e.g. Water Filtration Plants.
    */
    public function assets(Request $request)
    {
        $filters = $request->only([
            'division_id',
            'district_id',
            'tehsil_id',
            'kpi_category_id',
            'status',
            'search',
        ]);

        $assets     = $this->baselineDataService->getBaselineAssetList($filters);
        $filterData = $this->baselineDataService->getAssetFilterData();

        return view('baseline.assets', [
            'assets'        => $assets,
            'divisions'     => $filterData['divisions'],
            'districts'     => $filterData['districts'],
            'tehsils'       => $filterData['tehsils'],
            'kpiCategories' => $filterData['kpiCategories'],
            'filters'       => $filters,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Show Baseline Asset Detail
    |--------------------------------------------------------------------------
    */
    public function showAsset($id)
    {
        $asset = $this->baselineDataService->getBaselineAssetDetail($id);

        return view('baseline.asset-show', [
            'asset' => $asset,
        ]);
    }
}
