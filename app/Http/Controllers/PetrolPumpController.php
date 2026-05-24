<?php
namespace App\Http\Controllers;

use App\Services\PetrolPumpService;
use Illuminate\Http\Request;

class PetrolPumpController extends Controller
{
    protected PetrolPumpService $petrolPumpService;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    | PetrolPumpService handles dashboard query and summary logic.
    */
    public function __construct(PetrolPumpService $petrolPumpService)
    {
        $this->petrolPumpService = $petrolPumpService;
    }

    /*
    |--------------------------------------------------------------------------
    | Petrol Pump Monitoring Dashboard
    |--------------------------------------------------------------------------
    | Shows petrol pump related monitoring data.
    |
    | Current approach:
    | - Uses inspections table
    | - Filters category name containing petrol / pump if exists
    | - If no petrol category exists, returns empty/safe data
    |
    | Later:
    | - If petrol pump table is created, only service logic needs update.
    */
    public function dashboard(Request $request)
    {
        $filters = $request->only([
            'division_id',
            'district_id',
            'tehsil_id',
            'status',
            'date_from',
            'date_to',
            'search',
        ]);

        $summary       = $this->petrolPumpService->getSummary($filters);
        $statusChart   = $this->petrolPumpService->getStatusChart($filters);
        $districtChart = $this->petrolPumpService->getDistrictChart($filters);
        $records       = $this->petrolPumpService->getRecords($filters);
        $filterData    = $this->petrolPumpService->getFilterData();

        return view('petrol_pump.dashboard', [
            'summary'       => $summary,
            'statusChart'   => $statusChart,
            'districtChart' => $districtChart,
            'records'       => $records,
            'divisions'     => $filterData['divisions'],
            'districts'     => $filterData['districts'],
            'tehsils'       => $filterData['tehsils'],
            'filters'       => $filters,
        ]);
    }
}
