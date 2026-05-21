<?php
namespace App\Http\Controllers;

use App\Services\BaselineDataService;
use Illuminate\Http\Request;

class BaselineDataController extends Controller
{
    protected BaselineDataService $baselineDataService;

    public function __construct(BaselineDataService $baselineDataService)
    {
        $this->baselineDataService = $baselineDataService;
    }

    public function districtBaseline(Request $request)
    {
        $filters = $request->only([
            'district_id',
            'kpi_category_id',
            'year',
            'search',
            'per_page',
        ]);

        $baselineData = $this->baselineDataService->getDistrictBaselineList($filters);
        $summary      = $this->baselineDataService->getBaselineSummary($filters);
        $filterData   = $this->baselineDataService->getFilterData();

        return view('baseline.district-baseline', [
            'baselineData'  => $baselineData,
            'summary'       => $summary,
            'districts'     => $filterData['districts'],
            'kpiCategories' => $filterData['kpiCategories'],
            'filters'       => $filters,
        ]);
    }

    public function districtBaselineData(Request $request)
    {
        $filters = $request->only([
            'district_id',
            'kpi_category_id',
            'year',
            'search',
            'per_page',
            'page',
        ]);

        $baselineData = $this->baselineDataService->getDistrictBaselineList($filters);

        $html = view('baseline.partials._baseline-table', [
            'baselineData' => $baselineData,
            'filters' => $filters,
        ])->render();

        return response()->json([
            'status' => 'success',
            'html' => $html,
            'total' => method_exists($baselineData, 'total') ? $baselineData->total() : $baselineData->count(),
        ]);
    }

    public function index(Request $request)
    {
        $filters = $request->only([
            'district_id',
            'kpi_category_id',
            'year',
            'search',
            'per_page',
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

    public function indexData(Request $request)
    {
        $filters = $request->only([
            'district_id',
            'kpi_category_id',
            'year',
            'search',
            'per_page',
            'page',
        ]);

        $baselineData = $this->baselineDataService->getDistrictBaselineList($filters);

        $html = view('baseline.partials._baseline-table', [
            'baselineData' => $baselineData,
            'filters' => $filters,
        ])->render();

        return response()->json([
            'status' => 'success',
            'html' => $html,
            'total' => method_exists($baselineData, 'total') ? $baselineData->total() : $baselineData->count(),
        ]);
    }

    public function create()
    {
        $filterData = $this->baselineDataService->getFilterData();

        return view('baseline.create', [
            'districts'     => $filterData['districts'],
            'kpiCategories' => $filterData['kpiCategories'],
        ]);
    }

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

    public function show($id)
    {
        $baseline = $this->baselineDataService->getDistrictBaselineDetail($id);

        return view('baseline.show', [
            'baseline' => $baseline,
        ]);
    }

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

    public function assetsData(Request $request)
    {
        $filters = $request->only([
            'division_id',
            'district_id',
            'tehsil_id',
            'kpi_category_id',
            'status',
            'search',
            'per_page',
            'page',
        ]);

        $assets = $this->baselineDataService->getBaselineAssetList($filters);

        $html = view('baseline.partials._assets-table', [
            'assets' => $assets,
            'filters' => $filters,
        ])->render();

        return response()->json([
            'status' => 'success',
            'html' => $html,
            'total' => method_exists($assets, 'total') ? $assets->total() : $assets->count(),
        ]);
    }

    public function showAsset($id)
    {
        $asset = $this->baselineDataService->getBaselineAssetDetail($id);

        return view('baseline.asset-show', [
            'asset' => $asset,
        ]);
    }
}
