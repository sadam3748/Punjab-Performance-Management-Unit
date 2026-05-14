<?php
namespace App\Services;

use App\Models\District;
use App\Models\Division;
use App\Models\Inspection;
use App\Models\KpiCategory;
use App\Models\Tehsil;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ScorecardService
{
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
    | Common filters for scorecard pages.
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
            });
    }

    /*
    |--------------------------------------------------------------------------
    | Scorecard Summary
    |--------------------------------------------------------------------------
    | Main summary cards for scorecard page.
    */
    public function getScorecardSummary(array $filters): array
    {
        $query = Inspection::query();

        $query = $this->applyUserScope($query);
        $query = $this->applyFilters($query, $filters);

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
            'score_percentage'  => $total > 0
                ? round(($approved / $total) * 100, 2)
                : 0,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | District Ranking
    |--------------------------------------------------------------------------
    | Calculates district score based on approved inspections.
    | Formula: approved_count / total_inspections * 100
    */
    public function getDistrictRanking(array $filters)
    {
        $query = Inspection::query()
            ->select([
                'district_id',
                DB::raw('COUNT(*) as total_inspections'),
                DB::raw("SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count"),
                DB::raw("SUM(CASE WHEN status = 'reviewed' THEN 1 ELSE 0 END) as reviewed_count"),
                DB::raw("SUM(CASE WHEN status = 'submitted' THEN 1 ELSE 0 END) as submitted_count"),
                DB::raw("SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count"),
                DB::raw("
                    CASE
                        WHEN COUNT(*) > 0
                        THEN ROUND((SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END)::decimal / COUNT(*)) * 100, 2)
                        ELSE 0
                    END as score_percentage
                "),
            ])
            ->with('district:id,name,tier,division_id')
            ->groupBy('district_id');

        $query = $this->applyUserScope($query);
        $query = $this->applyFilters($query, $filters);

        return $query
            ->orderByDesc('score_percentage')
            ->orderByDesc('approved_count')
            ->paginate(20)
            ->withQueryString();
    }

    /*
    |--------------------------------------------------------------------------
    | Category Ranking
    |--------------------------------------------------------------------------
    | KPI category-wise score.
    */
    public function getCategoryRanking(array $filters)
    {
        $query = Inspection::query()
            ->select([
                'kpi_category_id',
                DB::raw('COUNT(*) as total_inspections'),
                DB::raw("SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count"),
                DB::raw("SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count"),
                DB::raw("
                    CASE
                        WHEN COUNT(*) > 0
                        THEN ROUND((SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END)::decimal / COUNT(*)) * 100, 2)
                        ELSE 0
                    END as score_percentage
                "),
            ])
            ->with('kpiCategory:id,name')
            ->groupBy('kpi_category_id');

        $query = $this->applyUserScope($query);
        $query = $this->applyFilters($query, $filters);

        return $query
            ->orderByDesc('score_percentage')
            ->orderByDesc('approved_count')
            ->limit(15)
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Tier Summary
    |--------------------------------------------------------------------------
    | Summary for Tier 1/2/3 page.
    */
    public function getTierSummary(array $filters): array
    {
        $selectedTier = $filters['tier'] ?? null;

        $query = Inspection::query();

        $query = $this->applyUserScope($query);
        $query = $this->applyFilters($query, $filters);

        $total    = (clone $query)->count();
        $approved = (clone $query)->where('status', 'approved')->count();

        $districtCountQuery = District::query()
            ->where('is_active', true)
            ->when($selectedTier, function ($q) use ($selectedTier) {
                $q->where('tier', $selectedTier);
            });

        $districtCount = $districtCountQuery->count();

        return [
            'selected_tier'     => $selectedTier ?: 'All Tiers',
            'district_count'    => $districtCount,
            'total_inspections' => $total,
            'approved'          => $approved,
            'score_percentage'  => $total > 0
                ? round(($approved / $total) * 100, 2)
                : 0,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Tier District Ranking
    |--------------------------------------------------------------------------
    | District ranking filtered by selected tier.
    */
    public function getTierDistrictRanking(array $filters)
    {
        $query = Inspection::query()
            ->select([
                'district_id',
                DB::raw('COUNT(*) as total_inspections'),
                DB::raw("SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count"),
                DB::raw("SUM(CASE WHEN status = 'reviewed' THEN 1 ELSE 0 END) as reviewed_count"),
                DB::raw("SUM(CASE WHEN status = 'submitted' THEN 1 ELSE 0 END) as submitted_count"),
                DB::raw("SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count"),
                DB::raw("
                    CASE
                        WHEN COUNT(*) > 0
                        THEN ROUND((SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END)::decimal / COUNT(*)) * 100, 2)
                        ELSE 0
                    END as score_percentage
                "),
            ])
            ->with('district:id,name,tier,division_id')
            ->groupBy('district_id');

        $query = $this->applyUserScope($query);
        $query = $this->applyFilters($query, $filters);

        return $query
            ->orderByDesc('score_percentage')
            ->orderByDesc('approved_count')
            ->paginate(20)
            ->withQueryString();
    }

    /*
    |--------------------------------------------------------------------------
    | Filter Dropdown Data
    |--------------------------------------------------------------------------
    */
    public function getFilterData(): array
    {
        return [
            'divisions'     => Division::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),

            'districts'     => District::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'division_id', 'name', 'tier']),

            'tehsils'       => Tehsil::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'district_id', 'name']),

            'kpiCategories' => KpiCategory::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
        ];
    }
}
