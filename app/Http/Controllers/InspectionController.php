<?php
namespace App\Http\Controllers;

use App\Services\InspectionService;
use Illuminate\Http\Request;

class InspectionController extends Controller
{
    protected InspectionService $inspectionService;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    | Inject InspectionService so controller stays clean.
    */
    public function __construct(InspectionService $inspectionService)
    {
        $this->inspectionService = $inspectionService;
    }

    /*
    |--------------------------------------------------------------------------
    | Inspection List Page
    |--------------------------------------------------------------------------
    | Shows inspection records with filters.
    */
    public function list(Request $request)
    {
        $filters = $request->only([
            'district_id',
            'tehsil_id',
            'kpi_category_id',
            'status',
            'date_from',
            'date_to',
            'search',
        ]);

        $inspections = $this->inspectionService->getInspectionList($filters);

        $filterData = $this->inspectionService->getFilterData();

        return view('inspections.list', [
            'inspections'   => $inspections,
            'districts'     => $filterData['districts'],
            'tehsils'       => $filterData['tehsils'],
            'kpiCategories' => $filterData['kpiCategories'],
            'filters'       => $filters,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Inspection Map Page
    |--------------------------------------------------------------------------
    | Shows inspections having latitude/longitude.
    */
    public function map(Request $request)
    {
        $filters = $request->only([
            'district_id',
            'tehsil_id',
            'kpi_category_id',
            'status',
            'date_from',
            'date_to',
        ]);

        $mapRecords = $this->inspectionService->getInspectionMapData($filters);

        $filterData = $this->inspectionService->getFilterData();

        return view('inspections.map', [
            'mapRecords'    => $mapRecords,
            'districts'     => $filterData['districts'],
            'tehsils'       => $filterData['tehsils'],
            'kpiCategories' => $filterData['kpiCategories'],
            'filters'       => $filters,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Inspection Detail Page / Modal Data
    |--------------------------------------------------------------------------
    | Later this can be used for detail page or AJAX modal.
    */
    public function show($id)
    {
        $inspection = $this->inspectionService->getInspectionDetail($id);

        return view('inspections.show', [
            'inspection' => $inspection,
        ]);
    }
}
