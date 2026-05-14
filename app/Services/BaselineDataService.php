<?php
namespace App\Services;

use App\Models\BaselineAsset;
use App\Models\District;
use App\Models\DistrictBaselineData;
use App\Models\Division;
use App\Models\KpiCategory;
use App\Models\Tehsil;
use Illuminate\Support\Facades\Auth;

class BaselineDataService
{
    /*
    |--------------------------------------------------------------------------
    | Apply Baseline Scope
    |--------------------------------------------------------------------------
    | District baseline table has district_id only.
    |
    | Super Admin / CS / PMRU = all Punjab
    | Commissioner = own division districts
    | DC = own district
    | AC / Field User = own district baseline only
    */
    private function applyDistrictBaselineScope($query)
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
            return $query->whereHas('district', function ($q) use ($user) {
                $q->where('division_id', $user->division_id);
            });
        }

        if (in_array($roleSlug, ['dc', 'ac', 'field_user']) && $user->district_id) {
            return $query->where('district_id', $user->district_id);
        }

        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | Apply Asset Scope
    |--------------------------------------------------------------------------
    | Baseline assets have division_id, district_id and tehsil_id.
    */
    private function applyAssetScope($query)
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
    | District Baseline List
    |--------------------------------------------------------------------------
    */
    public function getDistrictBaselineList(array $filters)
    {
        $query = DistrictBaselineData::query()
            ->with([
                'district:id,name,division_id',
                'kpiCategory:id,name',
                'creator:id,name,username',
                'updater:id,name,username',
            ]);

        $query = $this->applyDistrictBaselineScope($query);

        $query
            ->when(! empty($filters['district_id']), function ($q) use ($filters) {
                $q->where('district_id', $filters['district_id']);
            })
            ->when(! empty($filters['kpi_category_id']), function ($q) use ($filters) {
                $q->where('kpi_category_id', $filters['kpi_category_id']);
            })
            ->when(! empty($filters['year']), function ($q) use ($filters) {
                $q->where('year', $filters['year']);
            })
            ->when(! empty($filters['search']), function ($q) use ($filters) {
                $search = $filters['search'];

                $q->whereHas('district', function ($districtQuery) use ($search) {
                    $districtQuery->where('name', 'ILIKE', "%{$search}%");
                });
            });

        return $query
            ->latest()
            ->paginate(20)
            ->withQueryString();
    }

    /*
    |--------------------------------------------------------------------------
    | Baseline Summary
    |--------------------------------------------------------------------------
    */
    public function getBaselineSummary(array $filters): array
    {
        $query = DistrictBaselineData::query()->with('district:id,division_id');

        $query = $this->applyDistrictBaselineScope($query);

        $query
            ->when(! empty($filters['district_id']), function ($q) use ($filters) {
                $q->where('district_id', $filters['district_id']);
            })
            ->when(! empty($filters['kpi_category_id']), function ($q) use ($filters) {
                $q->where('kpi_category_id', $filters['kpi_category_id']);
            })
            ->when(! empty($filters['year']), function ($q) use ($filters) {
                $q->where('year', $filters['year']);
            });

        return [
            'total_baseline_records'   => (clone $query)->count(),
            'total_districts_covered'  => (clone $query)->distinct('district_id')->count('district_id'),
            'total_categories_covered' => (clone $query)->distinct('kpi_category_id')->count('kpi_category_id'),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Store District Baseline
    |--------------------------------------------------------------------------
    | Uses updateOrCreate to avoid duplicate district/category/year record.
    */
    public function storeDistrictBaseline(array $data): DistrictBaselineData
    {
        return DistrictBaselineData::updateOrCreate(
            [
                'district_id'     => $data['district_id'],
                'kpi_category_id' => $data['kpi_category_id'],
                'year'            => $data['year'],
            ],
            [
                'baseline_data' => $data['baseline_data'],
                'created_by'    => Auth::id(),
                'updated_by'    => Auth::id(),
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | District Baseline Detail
    |--------------------------------------------------------------------------
    */
    public function getDistrictBaselineDetail($id): DistrictBaselineData
    {
        $query = DistrictBaselineData::query()
            ->with([
                'district:id,name',
                'kpiCategory:id,name',
                'creator:id,name,username',
                'updater:id,name,username',
            ]);

        $query = $this->applyDistrictBaselineScope($query);

        return $query->findOrFail($id);
    }

    /*
    |--------------------------------------------------------------------------
    | Update District Baseline
    |--------------------------------------------------------------------------
    */
    public function updateDistrictBaseline($id, array $data): DistrictBaselineData
    {
        $baseline = $this->getDistrictBaselineDetail($id);

        $baseline->update([
            'district_id'     => $data['district_id'],
            'kpi_category_id' => $data['kpi_category_id'],
            'year'            => $data['year'],
            'baseline_data'   => $data['baseline_data'],
            'updated_by'      => Auth::id(),
        ]);

        return $baseline;
    }

    /*
    |--------------------------------------------------------------------------
    | Baseline Asset List
    |--------------------------------------------------------------------------
    */
    public function getBaselineAssetList(array $filters)
    {
        $query = BaselineAsset::query()
            ->with([
                'kpiCategory:id,name',
                'division:id,name',
                'district:id,name',
                'tehsil:id,name',
                'creator:id,name,username',
                'updater:id,name,username',
            ]);

        $query = $this->applyAssetScope($query);

        $query
            ->when(! empty($filters['division_id']), function ($q) use ($filters) {
                $q->where('division_id', $filters['division_id']);
            })
            ->when(! empty($filters['district_id']), function ($q) use ($filters) {
                $q->where('district_id', $filters['district_id']);
            })
            ->when(! empty($filters['tehsil_id']), function ($q) use ($filters) {
                $q->where('tehsil_id', $filters['tehsil_id']);
            })
            ->when(! empty($filters['kpi_category_id']), function ($q) use ($filters) {
                $q->where('kpi_category_id', $filters['kpi_category_id']);
            })
            ->when(! empty($filters['status']), function ($q) use ($filters) {
                $q->where('status', $filters['status']);
            })
            ->when(! empty($filters['search']), function ($q) use ($filters) {
                $search = $filters['search'];

                $q->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'ILIKE', "%{$search}%")
                        ->orWhere('address', 'ILIKE', "%{$search}%");
                });
            });

        return $query
            ->latest('baseline_date')
            ->paginate(20)
            ->withQueryString();
    }

    /*
    |--------------------------------------------------------------------------
    | Baseline Asset Detail
    |--------------------------------------------------------------------------
    */
    public function getBaselineAssetDetail($id): BaselineAsset
    {
        $query = BaselineAsset::query()
            ->with([
                'kpiCategory:id,name',
                'division:id,name',
                'district:id,name',
                'tehsil:id,name',
                'creator:id,name,username',
                'updater:id,name,username',
            ]);

        $query = $this->applyAssetScope($query);

        return $query->findOrFail($id);
    }

    /*
    |--------------------------------------------------------------------------
    | Filter Data For District Baseline
    |--------------------------------------------------------------------------
    */
    public function getFilterData(): array
    {
        return [
            'districts'     => District::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),

            'kpiCategories' => KpiCategory::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Filter Data For Baseline Assets
    |--------------------------------------------------------------------------
    */
    public function getAssetFilterData(): array
    {
        return [
            'divisions'     => Division::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),

            'districts'     => District::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'division_id', 'name']),

            'tehsils'       => Tehsil::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'district_id', 'name']),

            'kpiCategories' => KpiCategory::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
        ];
    }
}
