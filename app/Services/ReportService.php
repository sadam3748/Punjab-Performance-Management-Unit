<?php
namespace App\Services;

use App\Models\District;
use App\Models\Inspection;
use App\Models\KpiCategory;
use App\Models\Tehsil;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /*
    |--------------------------------------------------------------------------
    | Base Query
    |--------------------------------------------------------------------------
    | Common inspection query for report pages.
    */
    private function baseInspectionQuery()
    {
        return Inspection::query()
            ->with([
                'kpiCategory:id,name',
                'division:id,name',
                'district:id,name,tier,division_id',
                'tehsil:id,name',
                'performer:id,name,username',
            ]);
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
    | Apply Common Filters
    |--------------------------------------------------------------------------
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
            ->when(! empty($filters['kpi_category_id']), function ($q) use ($filters) {
                $q->where('kpi_category_id', $filters['kpi_category_id']);
            })
            ->when(! empty($filters['status']), function ($q) use ($filters) {
                $q->where('status', $filters['status']);
            })
            ->when(! empty($filters['tier']), function ($q) use ($filters) {
                $q->whereHas('district', function ($districtQuery) use ($filters) {
                    $districtQuery->where('tier', $filters['tier']);
                });
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
    | Report Summary
    |--------------------------------------------------------------------------
    | General report landing summary.
    */
    public function getReportSummary(array $filters): array
    {
        $query = $this->baseInspectionQuery();

        $query = $this->applyUserScope($query);
        $query = $this->applyFilters($query, $filters);

        $total     = (clone $query)->count();
        $approved  = (clone $query)->where('status', 'approved')->count();
        $reviewed  = (clone $query)->where('status', 'reviewed')->count();
        $submitted = (clone $query)->where('status', 'submitted')->count();
        $rejected  = (clone $query)->where('status', 'rejected')->count();

        return [
            'total'         => $total,
            'approved'      => $approved,
            'reviewed'      => $reviewed,
            'submitted'     => $submitted,
            'rejected'      => $rejected,
            'approval_rate' => $total > 0 ? round(($approved / $total) * 100, 2) : 0,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Category Wise District Score
    |--------------------------------------------------------------------------
    | District + KPI category grouped report.
    */
    public function getCategoryWiseDistrictScore(array $filters)
    {
        $query = Inspection::query()
            ->select([
                'district_id',
                'kpi_category_id',
                DB::raw('COUNT(*) as total_inspections'),
                DB::raw("SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count"),
                DB::raw("SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count"),
            ])
            ->with([
                'district:id,name,tier',
                'kpiCategory:id,name',
            ])
            ->groupBy('district_id', 'kpi_category_id');

        $query = $this->applyUserScope($query);
        $query = $this->applyFilters($query, $filters);

        return $query
            ->orderByDesc('total_inspections')
            ->paginate(25)
            ->withQueryString();
    }

    /*
    |--------------------------------------------------------------------------
    | District Tier Report
    |--------------------------------------------------------------------------
    | District-wise report grouped with tier information.
    */
    public function getDistrictTierReport(array $filters)
    {
        $query = Inspection::query()
            ->select([
                'district_id',
                DB::raw('COUNT(*) as total_inspections'),
                DB::raw("SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count"),
                DB::raw("SUM(CASE WHEN status = 'reviewed' THEN 1 ELSE 0 END) as reviewed_count"),
                DB::raw("SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count"),
            ])
            ->with('district:id,name,tier,division_id')
            ->groupBy('district_id');

        $query = $this->applyUserScope($query);
        $query = $this->applyFilters($query, $filters);

        return $query
            ->orderByDesc('total_inspections')
            ->paginate(25)
            ->withQueryString();
    }

    /*
    |--------------------------------------------------------------------------
    | District Comparison
    |--------------------------------------------------------------------------
    | Compares districts by total and approved inspections.
    */
    public function getDistrictComparison(array $filters)
    {
        $query = Inspection::query()
            ->select([
                'district_id',
                DB::raw('COUNT(*) as total_inspections'),
                DB::raw("SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count"),
                DB::raw("SUM(CASE WHEN status = 'submitted' THEN 1 ELSE 0 END) as submitted_count"),
                DB::raw("SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count"),
            ])
            ->with('district:id,name,tier')
            ->groupBy('district_id');

        $query = $this->applyUserScope($query);
        $query = $this->applyFilters($query, $filters);

        return $query
            ->orderByDesc('approved_count')
            ->paginate(25)
            ->withQueryString();
    }

    /*
    |--------------------------------------------------------------------------
    | Division Score
    |--------------------------------------------------------------------------
    | Division-wise score report.
    */
    public function getDivisionScore(array $filters)
    {
        $query = Inspection::query()
            ->select([
                'division_id',
                DB::raw('COUNT(*) as total_inspections'),
                DB::raw("SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count"),
                DB::raw("SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count"),
            ])
            ->with('division:id,name')
            ->groupBy('division_id');

        $query = $this->applyUserScope($query);
        $query = $this->applyFilters($query, $filters);

        return $query
            ->orderByDesc('approved_count')
            ->paginate(20)
            ->withQueryString();
    }

    /*
    |--------------------------------------------------------------------------
    | District Accumulative
    |--------------------------------------------------------------------------
    | Accumulative district-level report.
    */
    public function getDistrictAccumulative(array $filters)
    {
        $query = Inspection::query()
            ->select([
                'district_id',
                DB::raw('COUNT(*) as total_inspections'),
                DB::raw("SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count"),
                DB::raw("SUM(CASE WHEN status = 'reviewed' THEN 1 ELSE 0 END) as reviewed_count"),
                DB::raw("SUM(CASE WHEN status = 'submitted' THEN 1 ELSE 0 END) as submitted_count"),
                DB::raw("SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count"),
            ])
            ->with('district:id,name,tier')
            ->groupBy('district_id');

        $query = $this->applyUserScope($query);
        $query = $this->applyFilters($query, $filters);

        return $query
            ->orderByDesc('total_inspections')
            ->paginate(25)
            ->withQueryString();
    }

    /*
    |--------------------------------------------------------------------------
    | Division KPI Ranking
    |--------------------------------------------------------------------------
    | Ranking by division and KPI category.
    */
    public function getDivisionKpiRanking(array $filters)
    {
        $query = Inspection::query()
            ->select([
                'division_id',
                'kpi_category_id',
                DB::raw('COUNT(*) as total_inspections'),
                DB::raw("SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count"),
            ])
            ->with([
                'division:id,name',
                'kpiCategory:id,name',
            ])
            ->groupBy('division_id', 'kpi_category_id');

        $query = $this->applyUserScope($query);
        $query = $this->applyFilters($query, $filters);

        return $query
            ->orderByDesc('approved_count')
            ->paginate(25)
            ->withQueryString();
    }

    /*
    |--------------------------------------------------------------------------
    | District Weekly KPI Inspection
    |--------------------------------------------------------------------------
    | Weekly district/KPI inspection count.
    */
    public function getDistrictWeeklyKpiInspection(array $filters)
    {
        $query = Inspection::query()
            ->select([
                'district_id',
                'kpi_category_id',
                DB::raw("DATE_TRUNC('week', inspection_datetime) as week_start"),
                DB::raw('COUNT(*) as total_inspections'),
                DB::raw("SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count"),
            ])
            ->with([
                'district:id,name,tier',
                'kpiCategory:id,name',
            ])
            ->groupBy('district_id', 'kpi_category_id', DB::raw("DATE_TRUNC('week', inspection_datetime)"));

        $query = $this->applyUserScope($query);
        $query = $this->applyFilters($query, $filters);

        return $query
            ->orderByDesc('week_start')
            ->paginate(25)
            ->withQueryString();
    }

    /*
    |--------------------------------------------------------------------------
    | District Week Rank Changelog
    |--------------------------------------------------------------------------
    | Simple weekly ranking based on approved inspections.
    */
    public function getDistrictWeekRankChangelog(array $filters)
    {
        $query = Inspection::query()
            ->select([
                'district_id',
                DB::raw("DATE_TRUNC('week', inspection_datetime) as week_start"),
                DB::raw('COUNT(*) as total_inspections'),
                DB::raw("SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count"),
            ])
            ->with('district:id,name,tier')
            ->groupBy('district_id', DB::raw("DATE_TRUNC('week', inspection_datetime)"));

        $query = $this->applyUserScope($query);
        $query = $this->applyFilters($query, $filters);

        return $query
            ->orderByDesc('week_start')
            ->orderByDesc('approved_count')
            ->paginate(25)
            ->withQueryString();
    }

    /*
    |--------------------------------------------------------------------------
    | District Wise KPI Score
    |--------------------------------------------------------------------------
    | Score formula: approved / total * 100.
    */
    public function getDistrictWiseKpiScore(array $filters)
    {
        $query = Inspection::query()
            ->select([
                'district_id',
                'kpi_category_id',
                DB::raw('COUNT(*) as total_inspections'),
                DB::raw("SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count"),
                DB::raw("
                    CASE
                        WHEN COUNT(*) > 0
                        THEN ROUND((SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END)::decimal / COUNT(*)) * 100, 2)
                        ELSE 0
                    END as score_percentage
                "),
            ])
            ->with([
                'district:id,name,tier',
                'kpiCategory:id,name',
            ])
            ->groupBy('district_id', 'kpi_category_id');

        $query = $this->applyUserScope($query);
        $query = $this->applyFilters($query, $filters);

        return $query
            ->orderByDesc('score_percentage')
            ->paginate(25)
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
            'districts'     => District::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'tier']),

            'tehsils'       => Tehsil::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'district_id', 'name']),

            'kpiCategories' => KpiCategory::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
        ];
    }
}
