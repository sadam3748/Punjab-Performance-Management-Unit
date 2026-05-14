<?php
namespace App\Services;

use App\Models\BaselineAsset;
use App\Models\District;
use App\Models\DistrictBaselineData;
use App\Models\GeoTagging;
use App\Models\Inspection;
use App\Models\KpiCategory;
use App\Models\Tehsil;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /*
    |--------------------------------------------------------------------------
    | Base Inspection Query
    |--------------------------------------------------------------------------
    | Common inspection query used by dashboard cards, charts and lists.
    */
    private function baseInspectionQuery()
    {
        return Inspection::query()
            ->with([
                'kpiCategory:id,name',
                'division:id,name',
                'district:id,name',
                'tehsil:id,name',
                'performer:id,name,username,designation',
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Base Geo Tagging Query
    |--------------------------------------------------------------------------
    | Common geo-tagging query used by dashboard summary.
    */
    private function baseGeoTaggingQuery()
    {
        return GeoTagging::query()
            ->with([
                'geoTaggingType:id,name',
                'division:id,name',
                'district:id,name',
                'tehsil:id,name',
                'performer:id,name,username,designation',
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Role-Based Scope
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
    | Role-Based Scope for District Baseline
    |--------------------------------------------------------------------------
    | District baseline table has district_id only, so division-level scope uses
    | district relationship.
    */
    private function applyBaselineScope($query)
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

        if ($roleSlug === 'dc' && $user->district_id) {
            return $query->where('district_id', $user->district_id);
        }

        if (in_array($roleSlug, ['ac', 'field_user']) && $user->district_id) {
            return $query->where('district_id', $user->district_id);
        }

        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | Apply Common Filters
    |--------------------------------------------------------------------------
    | Filters inspection/geo-tagging data.
    */
    private function applyCommonFilters($query, array $filters, string $dateColumn)
    {
        return $query
            ->when(! empty($filters['district_id']), function ($q) use ($filters) {
                $q->where('district_id', $filters['district_id']);
            })
            ->when(! empty($filters['tehsil_id']), function ($q) use ($filters) {
                $q->where('tehsil_id', $filters['tehsil_id']);
            })
            ->when(! empty($filters['kpi_category_id']) && $dateColumn === 'inspection_datetime', function ($q) use ($filters) {
                $q->where('kpi_category_id', $filters['kpi_category_id']);
            })
            ->when(! empty($filters['date_from']), function ($q) use ($filters, $dateColumn) {
                $q->whereDate($dateColumn, '>=', $filters['date_from']);
            })
            ->when(! empty($filters['date_to']), function ($q) use ($filters, $dateColumn) {
                $q->whereDate($dateColumn, '<=', $filters['date_to']);
            });
    }

    /*
    |--------------------------------------------------------------------------
    | Summary Cards
    |--------------------------------------------------------------------------
    | Main dashboard totals.
    */
    public function getSummaryCards(array $filters): array
    {
        $inspectionQuery = $this->baseInspectionQuery();
        $inspectionQuery = $this->applyUserScope($inspectionQuery);
        $inspectionQuery = $this->applyCommonFilters($inspectionQuery, $filters, 'inspection_datetime');

        $geoQuery = $this->baseGeoTaggingQuery();
        $geoQuery = $this->applyUserScope($geoQuery);
        $geoQuery = $this->applyCommonFilters($geoQuery, $filters, 'tagged_at');

        $totalInspections = (clone $inspectionQuery)->count();
        $approved         = (clone $inspectionQuery)->where('status', 'approved')->count();
        $reviewed         = (clone $inspectionQuery)->where('status', 'reviewed')->count();
        $submitted        = (clone $inspectionQuery)->where('status', 'submitted')->count();
        $rejected         = (clone $inspectionQuery)->where('status', 'rejected')->count();

        return [
            'total_inspections'     => $totalInspections,
            'approved_inspections'  => $approved,
            'reviewed_inspections'  => $reviewed,
            'submitted_inspections' => $submitted,
            'rejected_inspections'  => $rejected,
            'total_geo_taggings'    => (clone $geoQuery)->count(),
            'approval_rate'         => $totalInspections > 0
                ? round(($approved / $totalInspections) * 100, 2)
                : 0,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Inspection Status Chart
    |--------------------------------------------------------------------------
    | Data for status breakdown chart.
    */
    public function getInspectionStatusChart(array $filters)
    {
        $query = Inspection::query()
            ->select([
                'status',
                DB::raw('COUNT(*) as total'),
            ])
            ->groupBy('status');

        $query = $this->applyUserScope($query);
        $query = $this->applyCommonFilters($query, $filters, 'inspection_datetime');

        return $query->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Category Wise Chart
    |--------------------------------------------------------------------------
    | KPI category-wise inspection count.
    */
    public function getCategoryWiseChart(array $filters)
    {
        $query = Inspection::query()
            ->select([
                'kpi_category_id',
                DB::raw('COUNT(*) as total'),
            ])
            ->with('kpiCategory:id,name')
            ->groupBy('kpi_category_id');

        $query = $this->applyUserScope($query);
        $query = $this->applyCommonFilters($query, $filters, 'inspection_datetime');

        return $query
            ->orderByDesc('total')
            ->limit(10)
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | District Wise Chart
    |--------------------------------------------------------------------------
    | District-wise inspection count.
    */
    public function getDistrictWiseChart(array $filters)
    {
        $query = Inspection::query()
            ->select([
                'district_id',
                DB::raw('COUNT(*) as total'),
            ])
            ->with('district:id,name')
            ->groupBy('district_id');

        $query = $this->applyUserScope($query);
        $query = $this->applyCommonFilters($query, $filters, 'inspection_datetime');

        return $query
            ->orderByDesc('total')
            ->limit(10)
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Geo Tagging Summary
    |--------------------------------------------------------------------------
    | Small summary for geo-tagging module.
    */
    public function getGeoTaggingSummary(array $filters): array
    {
        $query = $this->baseGeoTaggingQuery();

        $query = $this->applyUserScope($query);
        $query = $this->applyCommonFilters($query, $filters, 'tagged_at');

        $total     = (clone $query)->count();
        $verified  = (clone $query)->where('status', 'verified')->count();
        $submitted = (clone $query)->where('status', 'submitted')->count();
        $rejected  = (clone $query)->where('status', 'rejected')->count();

        return [
            'total_geo_taggings' => $total,
            'verified'           => $verified,
            'submitted'          => $submitted,
            'rejected'           => $rejected,
            'verified_rate'      => $total > 0 ? round(($verified / $total) * 100, 2) : 0,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Baseline Summary
    |--------------------------------------------------------------------------
    | Summary of district baseline and asset-level baseline records.
    */
    public function getBaselineSummary(array $filters): array
    {
        $baselineQuery = DistrictBaselineData::query()
            ->with('district:id,name');

        $baselineQuery = $this->applyBaselineScope($baselineQuery);

        $assetQuery = BaselineAsset::query();

        $assetQuery = $this->applyUserScope($assetQuery);

        $assetQuery
            ->when(! empty($filters['district_id']), function ($q) use ($filters) {
                $q->where('district_id', $filters['district_id']);
            })
            ->when(! empty($filters['tehsil_id']), function ($q) use ($filters) {
                $q->where('tehsil_id', $filters['tehsil_id']);
            })
            ->when(! empty($filters['kpi_category_id']), function ($q) use ($filters) {
                $q->where('kpi_category_id', $filters['kpi_category_id']);
            });

        return [
            'district_baseline_records' => $baselineQuery->count(),
            'baseline_assets'           => $assetQuery->count(),
            'functional_assets'         => (clone $assetQuery)->where('status', 'functional')->count(),
            'non_functional_assets'     => (clone $assetQuery)->where('status', 'non_functional')->count(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Recent Inspections
    |--------------------------------------------------------------------------
    | Latest inspection records for dashboard table.
    */
    public function getRecentInspections(array $filters)
    {
        $query = $this->baseInspectionQuery();

        $query = $this->applyUserScope($query);
        $query = $this->applyCommonFilters($query, $filters, 'inspection_datetime');

        return $query
            ->latest('inspection_datetime')
            ->limit(10)
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Filter Dropdown Data
    |--------------------------------------------------------------------------
    | Common dropdowns for dashboard filters.
    */
    public function getFilterData(): array
    {
        return [
            'districts'     => District::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),

            'tehsils'       => Tehsil::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'district_id', 'name']),

            'kpiCategories' => KpiCategory::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | User Summary
    |--------------------------------------------------------------------------
    | Optional helper for later admin dashboard/user cards.
    */
    public function getUserSummary(): array
    {
        return [
            'total_users'    => User::count(),
            'active_users'   => User::where('is_active', true)->count(),
            'inactive_users' => User::where('is_active', false)->count(),
        ];
    }
}
