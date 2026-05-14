<?php
namespace App\Services;

use App\Models\District;
use App\Models\Division;
use App\Models\Inspection;
use App\Models\KpiCategory;
use App\Models\Tehsil;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PetrolPumpService
{
    /*
    |--------------------------------------------------------------------------
    | Petrol Pump Category IDs
    |--------------------------------------------------------------------------
    | Finds KPI categories related to petrol pump monitoring.
    |
    | If later you create a dedicated category like:
    | - Inspection of Petrol Pumps
    | - Petrol Pump Monitoring
    | this method will automatically pick it.
    */
    private function getPetrolPumpCategoryIds(): array
    {
        return KpiCategory::query()
            ->where(function ($q) {
                $q->where('name', 'ILIKE', '%petrol%')
                    ->orWhere('name', 'ILIKE', '%pump%')
                    ->orWhere('slug', 'ILIKE', '%petrol%')
                    ->orWhere('slug', 'ILIKE', '%pump%');
            })
            ->pluck('id')
            ->toArray();
    }

    /*
    |--------------------------------------------------------------------------
    | Base Query
    |--------------------------------------------------------------------------
    | Common query for petrol pump monitoring.
    */
    private function baseQuery()
    {
        $categoryIds = $this->getPetrolPumpCategoryIds();

        $query = Inspection::query()
            ->with([
                'kpiCategory:id,name',
                'division:id,name',
                'district:id,name',
                'tehsil:id,name',
                'performer:id,name,username,designation',
            ]);

        /*
         * If no petrol pump category exists, return empty data safely.
         * This prevents unrelated inspection data from showing.
         */
        if (empty($categoryIds)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('kpi_category_id', $categoryIds);
    }

    /*
    |--------------------------------------------------------------------------
    | Apply User Scope
    |--------------------------------------------------------------------------
    | Super Admin / CS / PMRU = all Punjab
    | Commissioner = own division
    | DC = own district
    | AC / Field User = own tehsil
    */
    private function applyUserScope($query)
    {
        $user = Auth::user();

        if (! $user || ! $user->role) {
            return $query;
        }

        $roleSlug = $user->role->slug;

        if (in_array($roleSlug, ['super_admin', 'chief_secretary', 'pmru_user'])) {
            return $query;
        }

        if ($roleSlug === 'commissioner' && $user->division_id) {
            return $query->where('division_id', $user->division_id);
        }

        if ($roleSlug === 'dc' && $user->district_id) {
            return $query->where('district_id', $user->district_id);
        }

        if (in_array($roleSlug, ['ac', 'field_user']) && $user->tehsil_id) {
            return $query->where('tehsil_id', $user->tehsil_id);
        }

        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | Apply Filters
    |--------------------------------------------------------------------------
    | Common filters for petrol pump monitoring page.
    */
    private function applyFilters($query, array $filters)
    {
        return $query
            ->when(! empty($filters['division_id']), function ($q) use ($filters) {
                $q->where('division_id', $filters['division_id']);
            })
            ->when(! empty($filters['district_id']), function ($q) use ($filters) {
                $q->where('district_id', $filters['district_id']);
            })
            ->when(! empty($filters['tehsil_id']), function ($q) use ($filters) {
                $q->where('tehsil_id', $filters['tehsil_id']);
            })
            ->when(! empty($filters['status']), function ($q) use ($filters) {
                $q->where('status', $filters['status']);
            })
            ->when(! empty($filters['date_from']), function ($q) use ($filters) {
                $q->whereDate('inspection_datetime', '>=', $filters['date_from']);
            })
            ->when(! empty($filters['date_to']), function ($q) use ($filters) {
                $q->whereDate('inspection_datetime', '<=', $filters['date_to']);
            })
            ->when(! empty($filters['search']), function ($q) use ($filters) {
                $search = $filters['search'];

                $q->where(function ($subQuery) use ($search) {
                    $subQuery->where('main_title', 'ILIKE', "%{$search}%")
                        ->orWhere('main_address', 'ILIKE', "%{$search}%")
                        ->orWhere('remarks', 'ILIKE', "%{$search}%");
                });
            });
    }

    /*
    |--------------------------------------------------------------------------
    | Summary
    |--------------------------------------------------------------------------
    | Summary cards for petrol pump monitoring.
    */
    public function getSummary(array $filters): array
    {
        $query = $this->baseQuery();

        $query = $this->applyUserScope($query);
        $query = $this->applyFilters($query, $filters);

        $total     = (clone $query)->count();
        $approved  = (clone $query)->where('status', 'approved')->count();
        $reviewed  = (clone $query)->where('status', 'reviewed')->count();
        $submitted = (clone $query)->where('status', 'submitted')->count();
        $rejected  = (clone $query)->where('status', 'rejected')->count();

        return [
            'total_records' => $total,
            'approved'      => $approved,
            'reviewed'      => $reviewed,
            'submitted'     => $submitted,
            'rejected'      => $rejected,
            'approval_rate' => $total > 0
                ? round(($approved / $total) * 100, 2)
                : 0,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Status Chart
    |--------------------------------------------------------------------------
    | Status-wise count for chart.
    */
    public function getStatusChart(array $filters)
    {
        $query = $this->baseQuery()
            ->select([
                'status',
                DB::raw('COUNT(*) as total'),
            ])
            ->groupBy('status');

        $query = $this->applyUserScope($query);
        $query = $this->applyFilters($query, $filters);

        return $query->get();
    }

    /*
    |--------------------------------------------------------------------------
    | District Chart
    |--------------------------------------------------------------------------
    | District-wise petrol pump monitoring count.
    */
    public function getDistrictChart(array $filters)
    {
        $query = $this->baseQuery()
            ->select([
                'district_id',
                DB::raw('COUNT(*) as total'),
            ])
            ->with('district:id,name')
            ->groupBy('district_id');

        $query = $this->applyUserScope($query);
        $query = $this->applyFilters($query, $filters);

        return $query
            ->orderByDesc('total')
            ->limit(10)
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Records
    |--------------------------------------------------------------------------
    | Paginated petrol pump monitoring list.
    */
    public function getRecords(array $filters)
    {
        $query = $this->baseQuery();

        $query = $this->applyUserScope($query);
        $query = $this->applyFilters($query, $filters);

        return $query
            ->latest('inspection_datetime')
            ->paginate(20)
            ->withQueryString();
    }

    /*
    |--------------------------------------------------------------------------
    | Filter Data
    |--------------------------------------------------------------------------
    */
    public function getFilterData(): array
    {
        return [
            'divisions' => Division::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),

            'districts' => District::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'division_id', 'name']),

            'tehsils'   => Tehsil::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'district_id', 'name']),
        ];
    }
}
