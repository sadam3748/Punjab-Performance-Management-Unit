<?php
namespace App\Services;

use App\Models\District;
use App\Models\Division;
use App\Models\Inspection;
use App\Models\KpiCategory;
use App\Models\Tehsil;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ScorecardService
{
    /*
    |--------------------------------------------------------------------------
    | Apply Logged-in User Scope
    |--------------------------------------------------------------------------
    */
    private function applyUserScope($query)
    {
        $user = Auth::user();

        if (! $user || ! $user->role) {
            return $query;
        }

        $roleSlug = $user->role->slug ?? null;

        if (in_array($roleSlug, ['super_admin', 'chief_secretary', 'pmru_user'])) {
            return $query;
        }

        if ($roleSlug === 'commissioner' && $user->division_id) {
            return $query->where('division_id', $user->division_id);
        }

        if ($roleSlug === 'dc' && $user->district_id) {
            return $query->where('district_id', $user->district_id);
        }

        if (in_array($roleSlug, ['ac', 'data_entry_user']) && $user->tehsil_id) {
            return $query->where('tehsil_id', $user->tehsil_id);
        }

        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | Normalize Scorecard Filters
    |--------------------------------------------------------------------------
    | Converts old PPMF style filters into actual date_from/date_to filters.
    */
    public function normalizeFilters(array $filters): array
    {
        $filters = array_filter($filters, function ($value) {
            return $value !== null && $value !== '';
        });

        $filters['per_page'] = (int) ($filters['per_page'] ?? 10);
        if (! in_array($filters['per_page'], [10, 25, 50, 100])) {
            $filters['per_page'] = 10;
        }

        $filters['scope']     = $filters['scope'] ?? 'all';
        $filters['period']    = $filters['period'] ?? 'weekly';
        $filters['area_type'] = $filters['area_type'] ?? 'district';

        $year  = (int) ($filters['year'] ?? now()->year);
        $month = (int) ($filters['month'] ?? now()->month);

        /*
        |--------------------------------------------------------------------------
        | If direct date range is selected, keep it.
        |--------------------------------------------------------------------------
        */
        if (! empty($filters['date_from']) || ! empty($filters['date_to'])) {
            return $filters;
        }

        /*
        |--------------------------------------------------------------------------
        | Weekly
        | Example week_range: 07 May - 13 May
        |--------------------------------------------------------------------------
        */
        if (($filters['period'] ?? null) === 'weekly') {
            if (! empty($filters['week_range'])) {
                try {
                    [$startText, $endText] = array_map('trim', explode('-', $filters['week_range']));

                    $startDate = Carbon::parse($startText . ' ' . $year);
                    $endDate   = Carbon::parse($endText . ' ' . $year);

                    if ($endDate->lt($startDate)) {
                        $endDate->addMonth();
                    }

                    $filters['date_from'] = $startDate->format('Y-m-d');
                    $filters['date_to']   = $endDate->format('Y-m-d');
                } catch (\Throwable $e) {
                    $filters['date_from'] = now()->startOfWeek()->format('Y-m-d');
                    $filters['date_to']   = now()->endOfWeek()->format('Y-m-d');
                }
            }

            return $filters;
        }

        /*
        |--------------------------------------------------------------------------
        | Monthly
        |--------------------------------------------------------------------------
        */
        if (($filters['period'] ?? null) === 'monthly') {
            $date = Carbon::createFromDate($year, $month, 1);

            $filters['date_from'] = $date->copy()->startOfMonth()->format('Y-m-d');
            $filters['date_to']   = $date->copy()->endOfMonth()->format('Y-m-d');

            return $filters;
        }

        /*
        |--------------------------------------------------------------------------
        | Quarterly
        |--------------------------------------------------------------------------
        */
        if (($filters['period'] ?? null) === 'quarterly') {
            $date = Carbon::createFromDate($year, $month, 1);

            $filters['date_from'] = $date->copy()->firstOfQuarter()->format('Y-m-d');
            $filters['date_to']   = $date->copy()->lastOfQuarter()->format('Y-m-d');

            return $filters;
        }

        /*
        |--------------------------------------------------------------------------
        | Yearly
        |--------------------------------------------------------------------------
        */
        if (($filters['period'] ?? null) === 'yearly') {
            $date = Carbon::createFromDate($year, 1, 1);

            $filters['date_from'] = $date->copy()->startOfYear()->format('Y-m-d');
            $filters['date_to']   = $date->copy()->endOfYear()->format('Y-m-d');

            return $filters;
        }

        /*
        |--------------------------------------------------------------------------
        | All Time
        |--------------------------------------------------------------------------
        */
        if (($filters['period'] ?? null) === 'all') {
            unset($filters['date_from'], $filters['date_to']);
        }

        return $filters;
    }

    /*
    |--------------------------------------------------------------------------
    | Apply Common Filters
    |--------------------------------------------------------------------------
    */
    private function applyFilters($query, array $filters)
    {
        $filters = $this->normalizeFilters($filters);

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
    | Apply Performance Score Filter
    |--------------------------------------------------------------------------
    | Works on grouped ranking queries. Do not use alias in HAVING because
    | PostgreSQL does not allow SELECT aliases inside HAVING.
    */
    private function applyPerformanceFilter($query, array $filters)
    {
        $performance = $filters['performance'] ?? 'all';

        if ($performance === 'all' || $performance === '') {
            return $query;
        }

        $scoreExpression = "
            CASE
                WHEN COUNT(*) > 0
                THEN ROUND((SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END)::decimal / COUNT(*)) * 100, 2)
                ELSE 0
            END
        ";

        return match ($performance) {
            'excellent' => $query->havingRaw("{$scoreExpression} >= 90"),
            'good' => $query->havingRaw("{$scoreExpression} >= 70 AND {$scoreExpression} < 90"),
            'average' => $query->havingRaw("{$scoreExpression} >= 50 AND {$scoreExpression} < 70"),
            'critical', 'bad' => $query->havingRaw("{$scoreExpression} < 50"),
            default => $query,
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Scorecard Summary
    |--------------------------------------------------------------------------
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
            ->whereNotNull('district_id')
            ->groupBy('district_id');

        $query = $this->applyUserScope($query);
        $query = $this->applyFilters($query, $filters);
        $query = $this->applyPerformanceFilter($query, $filters);

        return $query
            ->orderByDesc('score_percentage')
            ->orderByDesc('approved_count')
            ->paginate($filters['per_page'] ?? 10)
            ->withQueryString();
    }

    /*
    |--------------------------------------------------------------------------
    | KPI Category Ranking
    |--------------------------------------------------------------------------
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
            ->whereNotNull('kpi_category_id')
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
    */
    public function getTierSummary(array $filters): array
    {
        $filters = $this->normalizeFilters($filters);

        $query = Inspection::query();

        $query = $this->applyUserScope($query);
        $query = $this->applyFilters($query, $filters);

        $total    = (clone $query)->count();
        $approved = (clone $query)->where('status', 'approved')->count();

        $districtCountQuery = District::query()
            ->where('is_active', true)
            ->when(! empty($filters['tier']), function ($q) use ($filters) {
                $q->where('tier', $filters['tier']);
            })
            ->when(! empty($filters['division_id']), function ($q) use ($filters) {
                $q->where('division_id', $filters['division_id']);
            })
            ->when(! empty($filters['district_id']), function ($q) use ($filters) {
                $q->where('id', $filters['district_id']);
            });

        return [
            'selected_tier'     => $filters['tier'] ?? 'All Tiers',
            'district_count'    => $districtCountQuery->count(),
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
            ->whereNotNull('district_id')
            ->groupBy('district_id');

        $query = $this->applyUserScope($query);
        $query = $this->applyFilters($query, $filters);
        $query = $this->applyPerformanceFilter($query, $filters);

        return $query
            ->orderByDesc('score_percentage')
            ->orderByDesc('approved_count')
            ->paginate($filters['per_page'] ?? 10)
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

    /*
    |--------------------------------------------------------------------------
    | Week Range Options
    |--------------------------------------------------------------------------
    */
    public function getWeekRanges(?int $year = null, ?int $month = null): array
    {
        $year  = $year ?: now()->year;
        $month = $month ?: now()->month;

        $start = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $end   = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        $weeks  = [];
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            $weekStart = $cursor->copy();
            $weekEnd   = $cursor->copy()->addDays(6);

            if ($weekEnd->gt($end)) {
                $weekEnd = $end->copy();
            }

            $label         = $weekStart->format('d M') . ' - ' . $weekEnd->format('d M');
            $weeks[$label] = $label;

            $cursor = $weekEnd->copy()->addDay();
        }

        return $weeks;
    }
}
