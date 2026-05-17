<?php
namespace App\Services;

use App\Models\District;
use App\Models\DistrictBaselineData;
use App\Models\Inspection;
use App\Models\KpiCategory;
use App\Models\ProvincialKpiMetric;
use App\Models\Tehsil;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class KpiReportService
{
    /*
    |--------------------------------------------------------------------------
    | User Scope
    |--------------------------------------------------------------------------
    | Returns current user's reporting scope.
    |
    | Super Admin / CS / PMRU = Punjab
    | Commissioner = Division
    | DC = District
    | AC / Field User = Tehsil
    */
    public function getUserScope(): array
    {
        $user = Auth::user();

        if (! $user || ! $user->role) {
            return [
                'type'  => 'punjab',
                'title' => 'PUNJAB',
            ];
        }

        $roleSlug = $user->role->slug;

        if (in_array($roleSlug, ['super_admin', 'chief_secretary', 'pmru_user'])) {
            return [
                'type'  => 'punjab',
                'title' => 'PUNJAB',
            ];
        }

        if ($roleSlug === 'commissioner' && $user->division) {
            return [
                'type'        => 'division',
                'title'       => strtoupper($user->division->name . ' DIVISION'),
                'division_id' => $user->division_id,
            ];
        }

        if ($roleSlug === 'dc' && $user->district) {
            return [
                'type'        => 'district',
                'title'       => strtoupper($user->district->name),
                'district_id' => $user->district_id,
            ];
        }

        if (in_array($roleSlug, ['ac', 'field_user']) && $user->tehsil) {
            return [
                'type'      => 'tehsil',
                'title'     => strtoupper($user->tehsil->name),
                'tehsil_id' => $user->tehsil_id,
            ];
        }

        return [
            'type'  => 'punjab',
            'title' => 'PUNJAB',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Base Inspection Query
    |--------------------------------------------------------------------------
    | Common inspection query for KPI reports.
    */
    private function baseInspectionQuery()
    {
        return Inspection::query()
            ->with([
                'kpiCategory:id,name',
                'division:id,name',
                'district:id,name',
                'tehsil:id,name',
                'performer:id,name,username',
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Apply Role-Based Scope
    |--------------------------------------------------------------------------
    | Limits data according to logged-in user access.
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
    | Apply Report Filters
    |--------------------------------------------------------------------------
    | Common filters used by KPI report pages.
    */
    private function applyInspectionFilters($query, array $filters)
    {
        return $query
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
    | Provincial KPI Wise Data
    |--------------------------------------------------------------------------
    | Old PPMF-style category-wise metric cards.
    | Data source: provincial_kpi_metrics table.
    */
    public function getProvincialKpiMetrics(array $filters = [])
    {
        $perPage = (int) ($filters['per_page'] ?? 10);

        if (! in_array($perPage, [10, 20, 25, 50], true)) {
            $perPage = 10;
        }

        $metricFilter = function ($query) use ($filters) {
            $query->where('is_active', true);

            if (! empty($filters['period_type']) && ($filters['period_type'] !== 'custom')) {
                $query->where('period_type', $filters['period_type']);
            }

            if (! empty($filters['date_from'])) {
                $query->whereDate('date_from', '>=', $filters['date_from']);
            }

            if (! empty($filters['date_to'])) {
                $query->whereDate('date_to', '<=', $filters['date_to']);
            }

            if (! empty($filters['search'])) {
                $search = trim($filters['search']);

                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('metric_title', 'ILIKE', "%{$search}%")
                        ->orWhere('metric_description', 'ILIKE', "%{$search}%")
                        ->orWhere('source', 'ILIKE', "%{$search}%");
                });
            }
        };

        $query = KpiCategory::query()
            ->where('is_active', true)
            ->with(['provincialMetrics' => function ($query) use ($metricFilter) {
                $metricFilter($query);
                $query->orderBy('sort_order')->orderBy('metric_title');
            }])
            ->whereHas('provincialMetrics', $metricFilter);

        if (! empty($filters['kpi_category_id'])) {
            $query->where('id', $filters['kpi_category_id']);
        }

        if (! empty($filters['search'])) {
            $search = trim($filters['search']);

            $query->where(function ($categoryQuery) use ($search, $metricFilter) {
                $categoryQuery->where('name', 'ILIKE', "%{$search}%")
                    ->orWhere('description', 'ILIKE', "%{$search}%")
                    ->orWhereHas('provincialMetrics', $metricFilter);
            });
        }

        return $query
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function getProvincialKpiMetricSummary(array $filters = []): array
    {
        $query = ProvincialKpiMetric::query()
            ->where('is_active', true);

        if (! empty($filters['kpi_category_id'])) {
            $query->where('kpi_category_id', $filters['kpi_category_id']);
        }

        if (! empty($filters['period_type']) && ($filters['period_type'] !== 'custom')) {
            $query->where('period_type', $filters['period_type']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('date_from', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('date_to', '<=', $filters['date_to']);
        }

        if (! empty($filters['search'])) {
            $search = trim($filters['search']);

            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('metric_title', 'ILIKE', "%{$search}%")
                    ->orWhere('metric_description', 'ILIKE', "%{$search}%")
                    ->orWhere('source', 'ILIKE', "%{$search}%")
                    ->orWhereHas('kpiCategory', function ($categoryQuery) use ($search) {
                        $categoryQuery->where('name', 'ILIKE', "%{$search}%");
                    });
            });
        }

        $totalMetrics = (clone $query)->count();
        $totalCategories = (clone $query)->distinct('kpi_category_id')->count('kpi_category_id');
        $totalValue = (clone $query)->sum('metric_value');
        $lastUpdated = (clone $query)->latest('updated_at')->value('updated_at');

        return [
            'total_metrics' => $totalMetrics,
            'total_categories' => $totalCategories,
            'total_value' => $totalValue,
            'active_period' => $filters['period_type'] ?? 'last_week',
            'last_updated' => $lastUpdated,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Legacy Inspection-Based Provincial Summary
    |--------------------------------------------------------------------------
    | Kept for backward compatibility if another view/report still uses it.
    */
    public function getProvincialKpiWiseData(array $filters)
    {
        return $this->getProvincialKpiMetrics($filters);
    }

    /*
    |--------------------------------------------------------------------------
    | KPI Reporting Status
    |--------------------------------------------------------------------------
    | Shows which district/category has reported data.
    */
    public function getKpiReportingStatus(array $filters)
    {
        $query = Inspection::query()
            ->select([
                'district_id',
                'kpi_category_id',
                DB::raw('COUNT(*) as total_records'),
                DB::raw('MAX(inspection_datetime) as last_reported_at'),
            ])
            ->with([
                'district:id,name',
                'kpiCategory:id,name',
            ])
            ->groupBy('district_id', 'kpi_category_id');

        $query = $this->applyUserScope($query);
        $query = $this->applyInspectionFilters($query, $filters);

        return $query
            ->orderByDesc('last_reported_at')
            ->paginate(20)
            ->withQueryString();
    }

    /*
    |--------------------------------------------------------------------------
    | District Baseline Data
    |--------------------------------------------------------------------------
    | Baseline summary records for district/category/year.
    */
    public function getDistrictBaselineData(array $filters)
    {
        $query = DistrictBaselineData::query()
            ->with([
                'district:id,name',
                'kpiCategory:id,name',
                'creator:id,name,username',
                'updater:id,name,username',
            ]);

        $query = $this->applyBaselineUserScope($query);
        $query = $this->applyBaselineFilters($query, $filters);

        return $query
            ->latest()
            ->paginate(20)
            ->withQueryString();
    }

    /*
    |--------------------------------------------------------------------------
    | Apply User Scope For Baseline
    |--------------------------------------------------------------------------
    | Baseline table has district_id, so scope is applied through district.
    */
    private function applyBaselineUserScope($query)
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
    | Apply Baseline Filters
    |--------------------------------------------------------------------------
    */
    private function applyBaselineFilters($query, array $filters)
    {
        return $query
            ->when(! empty($filters['district_id']), function ($q) use ($filters) {
                $q->where('district_id', $filters['district_id']);
            })
            ->when(! empty($filters['kpi_category_id']), function ($q) use ($filters) {
                $q->where('kpi_category_id', $filters['kpi_category_id']);
            })
            ->when(! empty($filters['year']), function ($q) use ($filters) {
                $q->where('year', $filters['year']);
            });
    }

    /*
    |--------------------------------------------------------------------------
    | KPI Graphical Summary
    |--------------------------------------------------------------------------
    | Summary cards for graphical report page.
    */
    public function getGraphicalSummary(array $filters): array
    {
        $query = $this->baseInspectionQuery();

        $query = $this->applyUserScope($query);
        $query = $this->applyInspectionFilters($query, $filters);

        $total     = (clone $query)->count();
        $approved  = (clone $query)->where('status', 'approved')->count();
        $reviewed  = (clone $query)->where('status', 'reviewed')->count();
        $submitted = (clone $query)->where('status', 'submitted')->count();
        $rejected  = (clone $query)->where('status', 'rejected')->count();

        return [
            'total_inspections' => $total,
            'approved'          => $approved,
            'reviewed'          => $reviewed,
            'submitted'         => $submitted,
            'rejected'          => $rejected,
            'approval_rate'     => $total > 0 ? round(($approved / $total) * 100, 2) : 0,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | KPI Graphical Chart Data
    |--------------------------------------------------------------------------
    | Data grouped by KPI category for charts.
    */
    public function getGraphicalChartData(array $filters)
    {
        $query = Inspection::query()
            ->select([
                'kpi_category_id',
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved"),
                DB::raw("SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected"),
            ])
            ->with('kpiCategory:id,name')
            ->groupBy('kpi_category_id');

        $query = $this->applyUserScope($query);
        $query = $this->applyInspectionFilters($query, $filters);

        return $query->get();
    }

    /*
    |--------------------------------------------------------------------------
    | KPI Graphical Table Data
    |--------------------------------------------------------------------------
    | Table records for graphical report detail list.
    */
    public function getGraphicalTableData(array $filters)
    {
        $query = $this->baseInspectionQuery();

        $query = $this->applyUserScope($query);
        $query = $this->applyInspectionFilters($query, $filters);

        return $query
            ->latest('inspection_datetime')
            ->paginate(15)
            ->withQueryString();
    }

    /*
    |--------------------------------------------------------------------------
    | Filter Dropdown Data
    |--------------------------------------------------------------------------
    | Common dropdowns for KPI report pages.
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
}
