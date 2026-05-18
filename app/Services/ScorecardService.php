<?php
namespace App\Services;

use App\Models\District;
use App\Models\DistrictKpiScore;
use App\Models\Division;
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

        $table = null;
        if (method_exists($query, 'getModel') && $query->getModel()) {
            $table = $query->getModel()->getTable();
        }

        if ($roleSlug === 'commissioner' && $user->division_id) {
            return $query->where('division_id', $user->division_id);
        }

        if ($roleSlug === 'dc' && $user->district_id) {
            return $query->where('district_id', $user->district_id);
        }

        if (in_array($roleSlug, ['ac', 'data_entry_user'], true)) {
            if ($user->district_id) {
                return $query->where('district_id', $user->district_id);
            }

            if ($user->tehsil_id) {
                $tehsilDistrictId = Tehsil::whereKey($user->tehsil_id)->value('district_id');
                if ($tehsilDistrictId) {
                    return $query->where('district_id', $tehsilDistrictId);
                }
            }
        }

        return $query;
    }

    public function normalizeFilters(array $filters): array
    {
        // New scorecard filters (old PPMF-style KPI scoring) + backward compatibility with existing UI params.
        $filters = array_filter($filters, fn ($v) => $v !== null && $v !== '');

        $filters['per_page'] = (int) ($filters['per_page'] ?? 10);
        if (! in_array($filters['per_page'], [10, 25, 50, 100], true)) {
            $filters['per_page'] = 10;
        }

        $filters['period_type'] = $filters['period_type'] ?? ($filters['period'] ?? 'weekly');
        if (! in_array($filters['period_type'], ['weekly', 'monthly', 'quarterly', 'yearly'], true)) {
            $filters['period_type'] = 'weekly';
        }

        $filters['calculation_type'] = $filters['calculation_type'] ?? 'general';
        if (! in_array($filters['calculation_type'], ['general', 'sixty_forty', 'negative_marking'], true)) {
            $filters['calculation_type'] = 'general';
        }

        $filters['year'] = (int) ($filters['year'] ?? now()->year);

        // Weekly period key (YYYYWW)
        if ($filters['period_type'] === 'weekly') {
            $filters['week_no'] = $filters['week_no'] ?? null;

            if (! $filters['week_no']) {
                $week = (int) now()->isoWeek();
                $filters['week_no'] = sprintf('%d%02d', (int) now()->year, $week);
            }
        }

        if ($filters['period_type'] === 'monthly') {
            $filters['month'] = (int) ($filters['month'] ?? now()->month);
        }

        if ($filters['period_type'] === 'quarterly') {
            $filters['quarter'] = (int) ($filters['quarter'] ?? now()->quarter);
        }

        return $filters;
    }

    private function applyDistrictFilters($query, array $filters)
    {
        return $query
            ->when(! empty($filters['division_id']), fn ($q) => $q->where('division_id', $filters['division_id']))
            ->when(! empty($filters['district_id']), fn ($q) => $q->where('id', $filters['district_id']))
            ->when(! empty($filters['tier']), fn ($q) => $q->where('tier', (int) $filters['tier']));
    }

    private function applyScoreFilters($query, array $filters)
    {
        $filters = $this->normalizeFilters($filters);

        $query
            ->where('is_active', true)
            ->where('period_type', $filters['period_type'])
            ->where('year', (int) $filters['year'])
            ->where('calculation_type', $filters['calculation_type']);

        if ($filters['period_type'] === 'weekly') {
            $query->where('week_no', $filters['week_no']);
        }

        if ($filters['period_type'] === 'monthly') {
            $query->where('month', (int) ($filters['month'] ?? now()->month));
        }

        if ($filters['period_type'] === 'quarterly') {
            $query->where('quarter', (int) ($filters['quarter'] ?? now()->quarter));
        }

        if ($filters['period_type'] === 'yearly') {
            // year already applied
        }

        if (! empty($filters['kpi_category_id'])) {
            $query->where('kpi_category_id', $filters['kpi_category_id']);
        }

        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | Apply Performance Score Filter (score-based)
    |--------------------------------------------------------------------------
    | Applies on a numeric score expression/column.
    */
    private function applyPerformanceFilter($query, array $filters, string $scoreExpression = '0')
    {
        $performance = $filters['performance'] ?? 'all';

        if ($performance === 'all' || $performance === '') {
            return $query;
        }

        return match ($performance) {
            'excellent' => $query->whereRaw("{$scoreExpression} >= 90"),
            'good' => $query->whereRaw("{$scoreExpression} >= 70 AND {$scoreExpression} < 90"),
            'average' => $query->whereRaw("{$scoreExpression} >= 50 AND {$scoreExpression} < 70"),
            'critical', 'bad' => $query->whereRaw("{$scoreExpression} < 50"),
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
        $filters = $this->normalizeFilters($filters);

        $districtsQuery = District::query()->where('is_active', true);
        $districtsQuery = $this->applyDistrictFilters($districtsQuery, $filters);
        $districtsQuery = $this->applyUserScope($districtsQuery);

        $totalDistricts = (clone $districtsQuery)->count();

        $scoreQuery = DistrictKpiScore::query();
        $scoreQuery = $this->applyUserScope($scoreQuery);
        $scoreQuery = $this->applyScoreFilters($scoreQuery, $filters);

        $reportedDistrictIds = (clone $scoreQuery)
            ->select('district_id')
            ->distinct()
            ->pluck('district_id')
            ->all();

        $reportedCount = count(array_unique($reportedDistrictIds));
        $unreportedCount = max(0, $totalDistricts - $reportedCount);

        $avgScore = (float) ((clone $scoreQuery)->avg('final_score') ?? 0);

        $topRow = (clone $scoreQuery)->orderByDesc('final_score')->with('district:id,name')->first();
        $lowRow = (clone $scoreQuery)->orderBy('final_score')->with('district:id,name')->first();

        return [
            'total_districts'     => $totalDistricts,
            'reported_districts'  => $reportedCount,
            'unreported_districts'=> $unreportedCount,
            'average_score'       => round($avgScore, 2),
            'top_district'        => $topRow?->district?->name,
            'top_score'           => $topRow?->final_score,
            'low_district'        => $lowRow?->district?->name,
            'low_score'           => $lowRow?->final_score,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | District Ranking
    |--------------------------------------------------------------------------
    */
    public function getDistrictRanking(array $filters)
    {
        $filters = $this->normalizeFilters($filters);

        $districtsQuery = District::query()
            ->where('is_active', true)
            ->with('division:id,name');

        $districtsQuery = $this->applyDistrictFilters($districtsQuery, $filters);
        $districtsQuery = $this->applyUserScope($districtsQuery);

        $scoreBase = DistrictKpiScore::query();
        $scoreBase = $this->applyUserScope($scoreBase);
        $scoreBase = $this->applyScoreFilters($scoreBase, $filters);
        $scoreBase
            ->when(! empty($filters['division_id']), fn ($q) => $q->where('division_id', $filters['division_id']))
            ->when(! empty($filters['district_id']), fn ($q) => $q->where('district_id', $filters['district_id']));

        $scoreAgg = (clone $scoreBase)
            ->select([
                'district_id',
                DB::raw('COUNT(*) as total_kpis'),
                DB::raw("SUM(CASE WHEN is_reported = true THEN 1 ELSE 0 END) as reported_kpis"),
                DB::raw('AVG(final_score) as score_percentage'),
            ])
            ->groupBy('district_id');

        $districtsQuery->leftJoinSub($scoreAgg, 'score_agg', function ($join) {
            $join->on('districts.id', '=', 'score_agg.district_id');
        });

        $districtsQuery->addSelect([
            'districts.*',
            DB::raw('COALESCE(score_agg.total_kpis, 0) as total_kpis'),
            DB::raw('COALESCE(score_agg.reported_kpis, 0) as reported_kpis'),
            DB::raw('COALESCE(score_agg.score_percentage, 0) as score_percentage'),
        ]);

        $districtsQuery = $this->applyPerformanceFilter($districtsQuery, $filters, 'COALESCE(score_agg.score_percentage, 0)');

        $paginator = $districtsQuery
            ->orderByDesc('score_percentage')
            ->orderBy('districts.name')
            ->paginate($filters['per_page'])
            ->withQueryString();

        // Keep existing Blade UI unchanged: it expects each row has ->district and ->score_percentage.
        $paginator->setCollection(
            $paginator->getCollection()->map(function ($district) {
                return (object) [
                    'district_id' => $district->id,
                    'score_percentage' => (float) ($district->score_percentage ?? 0),
                    'total_kpis' => (int) ($district->total_kpis ?? 0),
                    'reported_kpis' => (int) ($district->reported_kpis ?? 0),
                    'district' => $district,
                ];
            })
        );

        return $paginator;
    }

    /*
    |--------------------------------------------------------------------------
    | KPI Category Ranking
    |--------------------------------------------------------------------------
    */
    public function getCategoryRanking(array $filters)
    {
        $filters = $this->normalizeFilters($filters);

        $query = DistrictKpiScore::query()
            ->select([
                'kpi_category_id',
                DB::raw('COUNT(*) as total_rows'),
                DB::raw('COUNT(DISTINCT district_id) as districts_reported'),
                DB::raw('AVG(final_score) as avg_score'),
                DB::raw('MAX(final_score) as best_score'),
            ])
            ->with('kpiCategory:id,name')
            ->whereNotNull('kpi_category_id')
            ->where('is_active', true)
            ->where('period_type', $filters['period_type'])
            ->where('year', (int) $filters['year'])
            ->where('calculation_type', $filters['calculation_type'])
            ->when($filters['period_type'] === 'weekly', fn ($q) => $q->where('week_no', $filters['week_no']))
            ->when($filters['period_type'] === 'monthly', fn ($q) => $q->where('month', (int) ($filters['month'] ?? now()->month)))
            ->when($filters['period_type'] === 'quarterly', fn ($q) => $q->where('quarter', (int) ($filters['quarter'] ?? now()->quarter)))
            ->when(! empty($filters['division_id']), fn ($q) => $q->where('division_id', $filters['division_id']))
            ->when(! empty($filters['district_id']), fn ($q) => $q->where('district_id', $filters['district_id']))
            ->groupBy('kpi_category_id');

        $query = $this->applyUserScope($query);

        return $query
            ->orderByDesc('avg_score')
            ->orderByDesc('districts_reported')
            ->limit(15)
            ->get();
    }

    public function getTierWiseDistrictRankings(array $filters, int $limit = 15): array
    {
        $filters = $this->normalizeFilters($filters);

        $results = [];
        foreach ([1, 2, 3] as $tier) {
            $tierFilters = array_merge($filters, ['tier' => (string) $tier]);

            $districtsQuery = District::query()
                ->where('is_active', true)
                ->with('division:id,name');
            $districtsQuery = $this->applyDistrictFilters($districtsQuery, $tierFilters);
            $districtsQuery = $this->applyUserScope($districtsQuery);

            $scoreBase = DistrictKpiScore::query();
            $scoreBase = $this->applyUserScope($scoreBase);
            $scoreBase = $this->applyScoreFilters($scoreBase, $tierFilters);
            $scoreBase
                ->when(! empty($tierFilters['division_id']), fn ($q) => $q->where('division_id', $tierFilters['division_id']))
                ->when(! empty($tierFilters['district_id']), fn ($q) => $q->where('district_id', $tierFilters['district_id']));

            $scoreAgg = (clone $scoreBase)
                ->select([
                    'district_id',
                    DB::raw('COUNT(*) as total_kpis'),
                    DB::raw("SUM(CASE WHEN is_reported = true THEN 1 ELSE 0 END) as reported_kpis"),
                    DB::raw('AVG(final_score) as avg_score'),
                ])
                ->groupBy('district_id');

            $districtsQuery->leftJoinSub($scoreAgg, 'score_agg', function ($join) {
                $join->on('districts.id', '=', 'score_agg.district_id');
            });

            $districtsQuery->addSelect([
                'districts.*',
                DB::raw('COALESCE(score_agg.total_kpis, 0) as total_kpis'),
                DB::raw('COALESCE(score_agg.reported_kpis, 0) as reported_kpis'),
                DB::raw('COALESCE(score_agg.avg_score, 0) as score_value'),
            ]);

            $districtsQuery = $this->applyPerformanceFilter($districtsQuery, $tierFilters, 'COALESCE(score_agg.avg_score, 0)');

            $results[$tier] = $districtsQuery
                ->orderByDesc('score_value')
                ->orderBy('districts.name')
                ->limit($limit)
                ->get();
        }

        return $results;
    }

    /*
    |--------------------------------------------------------------------------
    | Tier Summary
    |--------------------------------------------------------------------------
    */
    public function getTierSummary(array $filters): array
    {
        $filters = $this->normalizeFilters($filters);

        $districtCountQuery = District::query()->where('is_active', true);
        $districtCountQuery = $this->applyDistrictFilters($districtCountQuery, $filters);
        $districtCountQuery = $this->applyUserScope($districtCountQuery);

        $districtCount = (clone $districtCountQuery)->count();

        $scoreQuery = DistrictKpiScore::query();
        $scoreQuery = $this->applyUserScope($scoreQuery);
        $scoreQuery = $this->applyScoreFilters($scoreQuery, $filters);

        if (! empty($filters['tier'])) {
            $scoreQuery->whereHas('district', fn ($q) => $q->where('tier', (int) $filters['tier']));
        }
        if (! empty($filters['division_id'])) {
            $scoreQuery->where('division_id', $filters['division_id']);
        }
        if (! empty($filters['district_id'])) {
            $scoreQuery->where('district_id', $filters['district_id']);
        }

        $reportedDistricts = (clone $scoreQuery)->distinct('district_id')->count('district_id');
        $avgScore = (float) ((clone $scoreQuery)->avg('final_score') ?? 0);

        return [
            'selected_tier'      => $filters['tier'] ?? 'All Tiers',
            'district_count'     => $districtCount,
            'reported_districts' => $reportedDistricts,
            'average_score'      => round($avgScore, 2),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Tier District Ranking
    |--------------------------------------------------------------------------
    */
    public function getTierDistrictRanking(array $filters)
    {
        // Tier-wise list uses same ranking query; tier filter is applied by controller.
        return $this->getDistrictRanking($filters);
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
