<?php
namespace App\Http\Controllers;

use App\Services\GeoTaggingService;
use Illuminate\Http\Request;

class GeoTaggingController extends Controller
{
    protected GeoTaggingService $geoTaggingService;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    | Service is injected here so query/database logic stays outside controller.
    */
    public function __construct(GeoTaggingService $geoTaggingService)
    {
        $this->geoTaggingService = $geoTaggingService;
    }

    /*
    |--------------------------------------------------------------------------
    | Geo Tagging List Page
    |--------------------------------------------------------------------------
    | Shows geo-tagging records in table/list format with filters.
    */
    public function list(Request $request)
    {
        $filters = $request->only([
            'district_id',
            'tehsil_id',
            'geo_tagging_type_id',
            'status',
            'date_from',
            'date_to',
            'search',
        ]);

        $geoTaggings = $this->geoTaggingService->getGeoTaggingList($filters);

        $filterData = $this->geoTaggingService->getFilterData();

        return view('geo-taggings.list', [
            'geoTaggings'     => $geoTaggings,
            'districts'       => $filterData['districts'],
            'tehsils'         => $filterData['tehsils'],
            'geoTaggingTypes' => $filterData['geoTaggingTypes'],
            'filters'         => $filters,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Geo Tagging Map Page
    |--------------------------------------------------------------------------
    | Shows geo-tagging records on map using latitude and longitude.
    */
    public function map(Request $request)
    {
        $filters = $request->only([
            'district_id',
            'tehsil_id',
            'geo_tagging_type_id',
            'status',
            'date_from',
            'date_to',
        ]);

        $mapRecords = $this->geoTaggingService->getGeoTaggingMapData($filters);

        $filterData = $this->geoTaggingService->getFilterData();

        return view('geo-taggings.map', [
            'mapRecords'      => $mapRecords,
            'districts'       => $filterData['districts'],
            'tehsils'         => $filterData['tehsils'],
            'geoTaggingTypes' => $filterData['geoTaggingTypes'],
            'filters'         => $filters,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Geo Tagging Detail Page
    |--------------------------------------------------------------------------
    | Shows one geo-tagging record detail with attachments.
    */
    public function show($id)
    {
        $geoTagging = $this->geoTaggingService->getGeoTaggingDetail($id);

        return view('geo-taggings.show', [
            'geoTagging' => $geoTagging,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Existing Detail View Support
    |--------------------------------------------------------------------------
    | If your sidebar already has a static detail page, this method can return it.
    | Later it can be removed when show($id) is fully used.
    */
    public function detail()
    {
        $geoTagging = $this->geoTaggingService->getLatestGeoTaggingDetail();

        return view('geo-taggings.detail', [
            'geoTagging' => $geoTagging,
        ]);
    }
}
