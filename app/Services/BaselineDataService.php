<?php
namespace App\Services;

use App\Models\BaselineAsset;
use App\Models\District;
use App\Models\DistrictBaselineData;
use App\Models\Division;
use App\Models\KpiCategory;
use App\Models\Tehsil;
use Illuminate\Support\Facades\Schema;

class BaselineDataService
{
    public function getFilterData(): array
    {
        $kpiQuery = KpiCategory::where('is_active', true);
        if (Schema::hasColumn('kpi_categories', 'sort_order')) {
            $kpiQuery->orderBy('sort_order');
        }

        return [
            'districts'     => District::orderBy('name')->get(),
            'kpiCategories' => $kpiQuery->orderBy('name')->get(),
        ];
    }

    public function getAssetFilterData(): array
    {
        $kpiQuery = KpiCategory::where('is_active', true);
        if (Schema::hasColumn('kpi_categories', 'sort_order')) {
            $kpiQuery->orderBy('sort_order');
        }

        return [
            'divisions'     => Division::orderBy('name')->get(),
            'districts'     => District::orderBy('name')->get(),
            'tehsils'       => Tehsil::orderBy('name')->get(),
            'kpiCategories' => $kpiQuery->orderBy('name')->get(),
        ];
    }

    public function getDistrictBaselineList(array $filters)
    {
        $perPage = (int) ($filters['per_page'] ?? request('per_page', 10));
        if (! in_array($perPage, [10, 20, 25, 50], true)) {
            $perPage = 10;
        }

        return DistrictBaselineData::with([
            'district',
            'kpiCategory',
            'creator',
            'updater',
        ])
            ->when(! empty($filters['district_id']), function ($query) use ($filters) {
                $query->where('district_id', $filters['district_id']);
            })
            ->when(! empty($filters['kpi_category_id']), function ($query) use ($filters) {
                $query->where('kpi_category_id', $filters['kpi_category_id']);
            })
            ->when(! empty($filters['year']), function ($query) use ($filters) {
                $query->where('year', $filters['year']);
            })
            ->when(! empty($filters['search']), function ($query) use ($filters) {
                $search = $filters['search'];

                $query->where(function ($q) use ($search) {
                    $q->whereHas('district', function ($districtQuery) use ($search) {
                        $districtQuery->where('name', 'ILIKE', "%{$search}%");
                    })
                        ->orWhereHas('kpiCategory', function ($categoryQuery) use ($search) {
                            $categoryQuery->where('name', 'ILIKE', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('year')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function getBaselineSummary(array $filters): array
    {
        $query = DistrictBaselineData::query();

        if (! empty($filters['district_id'])) {
            $query->where('district_id', $filters['district_id']);
        }

        if (! empty($filters['kpi_category_id'])) {
            $query->where('kpi_category_id', $filters['kpi_category_id']);
        }

        if (! empty($filters['year'])) {
            $query->where('year', $filters['year']);
        }

        return [
            'total_baseline_records' => (clone $query)->count(),
            'districts_covered'      => (clone $query)->distinct('district_id')->count('district_id'),
            'categories_covered'     => (clone $query)->distinct('kpi_category_id')->count('kpi_category_id'),
        ];
    }

    public function getDistrictBaselineDetail(int $id): DistrictBaselineData
    {
        return DistrictBaselineData::with([
            'district',
            'kpiCategory',
            'creator',
            'updater',
        ])
            ->findOrFail($id);
    }

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
                'created_by'    => auth()->id() ?? 1,
                'updated_by'    => auth()->id() ?? 1,
            ]
        );
    }

    public function updateDistrictBaseline(int $id, array $data): DistrictBaselineData
    {
        $baseline = DistrictBaselineData::findOrFail($id);

        $baseline->update([
            'district_id'     => $data['district_id'],
            'kpi_category_id' => $data['kpi_category_id'],
            'year'            => $data['year'],
            'baseline_data'   => $data['baseline_data'],
            'updated_by'      => auth()->id() ?? 1,
        ]);

        return $baseline;
    }

    public function getBaselineAssetList(array $filters)
    {
        return BaselineAsset::with([
            'division',
            'district',
            'tehsil',
            'kpiCategory',
            'creator',
            'updater',
        ])
            ->when(! empty($filters['division_id']), function ($query) use ($filters) {
                $query->where('division_id', $filters['division_id']);
            })
            ->when(! empty($filters['district_id']), function ($query) use ($filters) {
                $query->where('district_id', $filters['district_id']);
            })
            ->when(! empty($filters['tehsil_id']), function ($query) use ($filters) {
                $query->where('tehsil_id', $filters['tehsil_id']);
            })
            ->when(! empty($filters['kpi_category_id']), function ($query) use ($filters) {
                $query->where('kpi_category_id', $filters['kpi_category_id']);
            })
            ->when(! empty($filters['status']), function ($query) use ($filters) {
                $query->where('status', $filters['status']);
            })
            ->when(! empty($filters['search']), function ($query) use ($filters) {
                $search = $filters['search'];

                $query->where(function ($q) use ($search) {
                    $q->where('name', 'ILIKE', "%{$search}%")
                        ->orWhere('address', 'ILIKE', "%{$search}%")
                        ->orWhereHas('district', function ($districtQuery) use ($search) {
                            $districtQuery->where('name', 'ILIKE', "%{$search}%");
                        })
                        ->orWhereHas('tehsil', function ($tehsilQuery) use ($search) {
                            $tehsilQuery->where('name', 'ILIKE', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();
    }

    public function getBaselineAssetDetail(int $id): BaselineAsset
    {
        return BaselineAsset::with([
            'division',
            'district',
            'tehsil',
            'kpiCategory',
            'creator',
            'updater',
        ])
            ->findOrFail($id);
    }
}
