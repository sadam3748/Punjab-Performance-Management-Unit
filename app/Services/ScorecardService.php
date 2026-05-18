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

            if (! $filters['week_no'] && ! empty($filters['week_range'])) {
                $filters['week_no'] = $this->weekNoFromRangeLabel((string) $filters['week_range'], (int) $filters['year']);
            }

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

    private function weekNoFromRangeLabel(string $label, int $year): ?string
    {
        // Expected label format: "07 May - 13 May" (from getWeekRanges()).
        $parts = preg_split('/\s*-\s*/', trim($label));
        if (! $parts || count($parts) < 1) {
            return null;
        }

        try {
            $start = Carbon::createFromFormat('d M Y', trim($parts[0]) . ' ' . $year)->startOfDay();
        } catch (\Throwable) {
            return null;
        }

        $isoYear = (int) $start->isoFormat('GGGG');
        $isoWeek = (int) $start->isoWeek();

        return sprintf('%d%02d', $isoYear, $isoWeek);
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
    private function applyPerformanceFilter($query, array $filters, string $scoreExpression = '0', ?string $unreportedExpression = null)
    {
        $performance = $filters['performance'] ?? 'all';

        if ($performance === 'all' || $performance === '') {
            return $query;
        }

        $reportedExpr = $unreportedExpression ? "NOT ({$unreportedExpression})" : null;

        return match ($performance) {
            'excellent' => $query->whereRaw("{$scoreExpression} >= 90" . ($reportedExpr ? " AND {$reportedExpr}" : '')),
            'good' => $query->whereRaw("{$scoreExpression} >= 70 AND {$scoreExpression} < 90" . ($reportedExpr ? " AND {$reportedExpr}" : '')),
            'average' => $query->whereRaw("{$scoreExpression} >= 50 AND {$scoreExpression} < 70" . ($reportedExpr ? " AND {$reportedExpr}" : '')),
            'critical', 'bad' => $query->whereRaw("{$scoreExpression} < 50" . ($reportedExpr ? " AND {$reportedExpr}" : '')),
            'unreported' => $unreportedExpression ? $query->whereRaw("{$unreportedExpression}") : $query->whereRaw("{$scoreExpression} = 0"),
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
            ->where('is_reported', true)
            ->select('district_id')
            ->distinct()
            ->pluck('district_id')
            ->all();

        $reportedCount = count(array_unique($reportedDistrictIds));
        $unreportedCount = max(0, $totalDistricts - $reportedCount);

        $avgScore = (float) ((clone $scoreQuery)->avg('final_score') ?? 0);

        $topRow = (clone $scoreQuery)->orderByDesc('final_score')->with('district:id,name')->first();
        $lowRow = (clone $scoreQuery)->orderBy('final_score')->with('district:id,name')->first();

        // Performance distribution for cards (ignore performance filter; use same period/KPI/tier scope).
        $scoreAgg = (clone $scoreQuery)
            ->select([
                'district_id',
                DB::raw("AVG(CASE WHEN is_reported = true THEN final_score ELSE NULL END) as score_percentage"),
                DB::raw('COUNT(*) as total_kpis'),
                DB::raw("SUM(CASE WHEN is_reported = true THEN 1 ELSE 0 END) as reported_kpis"),
            ])
            ->groupBy('district_id');

        $distRows = (clone $districtsQuery)
            ->leftJoinSub($scoreAgg, 'score_agg', fn ($j) => $j->on('districts.id', '=', 'score_agg.district_id'))
            ->get([
                'districts.id',
                DB::raw('COALESCE(score_agg.total_kpis, 0) as total_kpis'),
                DB::raw('COALESCE(score_agg.reported_kpis, 0) as reported_kpis'),
                DB::raw('COALESCE(score_agg.score_percentage, 0) as score_percentage'),
            ]);

        $excellentCount = 0;
        $goodCount = 0;
        $averageCount = 0;
        $criticalCount = 0;
        $unreportedBandCount = 0;

        foreach ($distRows as $r) {
            $reported = ((int) ($r->reported_kpis ?? 0)) > 0;
            $score = (float) ($r->score_percentage ?? 0);

            if (! $reported) {
                $unreportedBandCount++;
                continue;
            }

            if ($score >= 90) {
                $excellentCount++;
            } elseif ($score >= 70) {
                $goodCount++;
            } elseif ($score >= 50) {
                $averageCount++;
            } else {
                $criticalCount++;
            }
        }

        return [
            'total_districts'     => $totalDistricts,
            'reported_districts'  => $reportedCount,
            'unreported_districts'=> $unreportedCount,
            'average_score'       => round($avgScore, 2),
            'top_district'        => $topRow?->district?->name,
            'top_score'           => $topRow?->final_score,
            'low_district'        => $lowRow?->district?->name,
            'low_score'           => $lowRow?->final_score,
            'excellent_count'     => $excellentCount,
            'good_count'          => $goodCount,
            'average_count'       => $averageCount,
            'critical_count'      => $criticalCount,
            'unreported_count'    => $unreportedBandCount,
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
                DB::raw("AVG(CASE WHEN is_reported = true THEN final_score ELSE NULL END) as score_percentage"),
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

        $districtsQuery = $this->applyPerformanceFilter(
            $districtsQuery,
            $filters,
            'COALESCE(score_agg.score_percentage, 0)',
            'COALESCE(score_agg.reported_kpis, 0) = 0'
        );

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

        $reportedDistricts = (clone $scoreQuery)
            ->where('is_reported', true)
            ->distinct('district_id')
            ->count('district_id');
        $avgScore = (float) ((clone $scoreQuery)->avg('final_score') ?? 0);

        $scoreAgg = (clone $scoreQuery)
            ->select([
                'district_id',
                DB::raw("AVG(CASE WHEN is_reported = true THEN final_score ELSE NULL END) as score_percentage"),
                DB::raw('COUNT(*) as total_kpis'),
                DB::raw("SUM(CASE WHEN is_reported = true THEN 1 ELSE 0 END) as reported_kpis"),
            ])
            ->groupBy('district_id');

        $distRows = (clone $districtCountQuery)
            ->leftJoinSub($scoreAgg, 'score_agg', fn ($j) => $j->on('districts.id', '=', 'score_agg.district_id'))
            ->get([
                'districts.id',
                DB::raw('COALESCE(score_agg.total_kpis, 0) as total_kpis'),
                DB::raw('COALESCE(score_agg.reported_kpis, 0) as reported_kpis'),
                DB::raw('COALESCE(score_agg.score_percentage, 0) as score_percentage'),
            ]);

        $excellentCount = 0;
        $goodCount = 0;
        $averageCount = 0;
        $criticalCount = 0;
        $unreportedBandCount = 0;

        foreach ($distRows as $r) {
            $reported = ((int) ($r->reported_kpis ?? 0)) > 0;
            $score = (float) ($r->score_percentage ?? 0);

            if (! $reported) {
                $unreportedBandCount++;
                continue;
            }

            if ($score >= 90) {
                $excellentCount++;
            } elseif ($score >= 70) {
                $goodCount++;
            } elseif ($score >= 50) {
                $averageCount++;
            } else {
                $criticalCount++;
            }
        }

        return [
            'selected_tier'      => $filters['tier'] ?? 'All Tiers',
            'district_count'     => $districtCount,
            'reported_districts' => $reportedDistricts,
            'average_score'      => round($avgScore, 2),
            'excellent_count'    => $excellentCount,
            'good_count'         => $goodCount,
            'average_count'      => $averageCount,
            'critical_count'     => $criticalCount,
            'unreported_count'   => $unreportedBandCount,
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

    public function getDistrictScorecardDetail(District $district, array $filters): array
    {
        $filters = $this->normalizeFilters($filters);

        $calculationType = $filters['calculation_type'] ?? 'general';

        $detailPerPage = (int) (request('detail_per_page', 10));
        if (! in_array($detailPerPage, [10, 20, 25, 50], true)) {
            $detailPerPage = 10;
        }

        // Determine the 3 week buckets (previous_2, previous_1, current) for weekly.
        $currentWeekNo = $filters['week_no'] ?? null;
        if (! $currentWeekNo) {
            $currentWeekNo = DistrictKpiScore::query()
                ->where('district_id', $district->id)
                ->where('period_type', 'weekly')
                ->where('calculation_type', $calculationType)
                ->where('is_active', true)
                ->max('week_no');
        }

        $weekNos = $this->getWeeklyWindow($currentWeekNo);

        $weekHeaders = collect($weekNos)->map(function ($weekNo, $key) {
            $label = $weekNo ?: '—';
            if ($weekNo && strlen($weekNo) === 6) {
                $year = (int) substr($weekNo, 0, 4);
                $week = (int) substr($weekNo, 4, 2);
                $start = Carbon::now()->setISODate($year, $week)->startOfWeek(Carbon::MONDAY);
                $end = $start->copy()->endOfWeek(Carbon::SUNDAY);
                $label = $start->format('d M, Y') . ' - ' . $end->format('d M, Y');
            }
            return ['key' => $key, 'label' => $label, 'week_no' => $weekNo];
        })->values()->all();

        $periodLabel = ($weekHeaders[2]['label'] ?? '—');

        // KPI categories to display (limit to those seeded/active).
        $kpiCategories = KpiCategory::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name']);

        $selectedKpiCategory = null;
        if (! empty($filters['kpi_category_id'])) {
            $selectedKpiCategory = $kpiCategories->firstWhere('id', (int) $filters['kpi_category_id']);
            $kpiCategories = $selectedKpiCategory ? collect([$selectedKpiCategory]) : $kpiCategories;
        }

        $displayWeights = $this->getKpiDisplayWeightages($kpiCategories);

        $rows = [];
        $totals = [
            'weightage' => 0.0,
            'previous_2' => 0.0,
            'previous_1' => 0.0,
            'current' => 0.0,
        ];
        $covered = [
            'previous_2' => 0.0,
            'previous_1' => 0.0,
            'current' => 0.0,
        ];

        foreach ($kpiCategories as $cat) {
            $weight = (float) ($displayWeights[$cat->id] ?? 0);

            $scoresByWeek = DistrictKpiScore::query()
                ->where('district_id', $district->id)
                ->where('kpi_category_id', $cat->id)
                ->where('period_type', 'weekly')
                ->where('calculation_type', $calculationType)
                ->where('is_active', true)
                ->whereIn('week_no', array_values(array_filter($weekNos)))
                ->get(['week_no', 'final_score', 'is_reported'])
                ->keyBy('week_no');

            $cell = function (?string $weekNo) use ($scoresByWeek, $weight): array {
                if (! $weekNo) {
                    return ['final_score' => null, 'weighted_score' => null];
                }
                $row = $scoresByWeek->get($weekNo);
                if (! $row || ! $row->is_reported) {
                    return ['final_score' => null, 'weighted_score' => null];
                }
                $final = (float) $row->final_score;
                return [
                    'final_score' => $final,
                    'weighted_score' => round(($final / 100) * $weight, 2),
                ];
            };

            $p2 = $cell($weekNos['previous_2']);
            $p1 = $cell($weekNos['previous_1']);
            $cur = $cell($weekNos['current']);

            $rows[] = [
                'kpi_category_id' => $cat->id,
                'kpi_name' => $cat->name,
                'weightage' => round($weight, 2),
                'previous_2' => $p2,
                'previous_1' => $p1,
                'current' => $cur,
            ];

            $totals['weightage'] += $weight;
            $totals['previous_2'] += (float) ($p2['weighted_score'] ?? 0);
            $totals['previous_1'] += (float) ($p1['weighted_score'] ?? 0);
            $totals['current'] += (float) ($cur['weighted_score'] ?? 0);

            if (! empty($p2['final_score'])) {
                $covered['previous_2'] += $weight;
            }
            if (! empty($p1['final_score'])) {
                $covered['previous_1'] += $weight;
            }
            if (! empty($cur['final_score'])) {
                $covered['current'] += $weight;
            }
        }

        $rowsCollection = collect($rows);
        $currentPage = max(1, (int) request('page', 1));
        $pagedItems = $rowsCollection->slice(($currentPage - 1) * $detailPerPage, $detailPerPage)->values();

        $rowsPaginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $pagedItems,
            $rowsCollection->count(),
            $detailPerPage,
            $currentPage,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );

        // Totals shown on detail should match main scorecard behavior:
        // exclude missing/unreported KPIs from denominator (weight covered).
        $scaled = [
            'previous_2' => $covered['previous_2'] > 0 ? (($totals['previous_2'] / $covered['previous_2']) * 100) : 0,
            'previous_1' => $covered['previous_1'] > 0 ? (($totals['previous_1'] / $covered['previous_1']) * 100) : 0,
            'current' => $covered['current'] > 0 ? (($totals['current'] / $covered['current']) * 100) : 0,
        ];

        $totals = [
            'weightage' => 100.0,
            'previous_2' => round($scaled['previous_2'], 2),
            'previous_1' => round($scaled['previous_1'], 2),
            'current' => round($scaled['current'], 2),
            'covered_weight_previous_2' => round($covered['previous_2'], 2),
            'covered_weight_previous_1' => round($covered['previous_1'], 2),
            'covered_weight_current' => round($covered['current'], 2),
        ];

        // Rank and top summary: use current-week overall score_percentage among active districts.
        $rank = $this->getDistrictRankForWeek($district, $filters, $weekNos['current']);

        $summary = [
            'rank' => $rank,
            'score' => $totals['current'],
            'tier' => $district->tier ?? null,
            'reported_kpis' => collect($rows)->filter(fn ($r) => ! empty($r['current']['final_score']))->count(),
            'total_kpis' => count($rows),
            'calculation_type' => $calculationType,
        ];

        return [
            'periodLabel' => $periodLabel,
            'summary' => $summary,
            'rows' => $rowsPaginator,
            'totals' => $totals,
            'weekHeaders' => $weekHeaders,
            'kpiCategories' => $kpiCategories,
            'selectedKpiCategory' => $selectedKpiCategory,
            'calculationType' => $calculationType,
            'detailPerPage' => $detailPerPage,
        ];
    }

    private function getWeeklyWindow(?string $currentWeekNo): array
    {
        if (! $currentWeekNo || strlen($currentWeekNo) !== 6) {
            return ['previous_2' => null, 'previous_1' => null, 'current' => $currentWeekNo];
        }

        $year = (int) substr($currentWeekNo, 0, 4);
        $week = (int) substr($currentWeekNo, 4, 2);
        $current = Carbon::now()->setISODate($year, $week)->startOfWeek(Carbon::MONDAY);
        $p1 = $current->copy()->subWeek();
        $p2 = $current->copy()->subWeeks(2);

        $make = fn (Carbon $dt) => sprintf('%d%02d', (int) $dt->isoFormat('GGGG'), (int) $dt->isoWeek());

        return [
            'previous_2' => $make($p2),
            'previous_1' => $make($p1),
            'current' => $currentWeekNo,
        ];
    }

    private function getKpiDisplayWeightages($kpiCategories): array
    {
        $catIds = $kpiCategories->pluck('id')->all();
        if (! $catIds) {
            return [];
        }

        $raw = DB::table('kpi_scoring_parameters')
            ->select('kpi_category_id', DB::raw('SUM(weightage) as w'))
            ->whereIn('kpi_category_id', $catIds)
            ->where('is_active', true)
            ->groupBy('kpi_category_id')
            ->pluck('w', 'kpi_category_id')
            ->map(fn ($v) => (float) $v)
            ->all();

        $total = array_sum($raw);
        if ($total <= 0) {
            // fallback equal distribution
            $equal = 100 / max(1, count($catIds));
            return collect($catIds)->mapWithKeys(fn ($id) => [$id => round($equal, 2)])->all();
        }

        return collect($catIds)->mapWithKeys(function ($id) use ($raw, $total) {
            $w = (float) ($raw[$id] ?? 0);
            return [$id => round(($w / $total) * 100, 2)];
        })->all();
    }

    private function getDistrictRankForWeek(District $district, array $filters, ?string $weekNo): ?int
    {
        if (! $weekNo) {
            return null;
        }

        $rankFilters = $filters;
        $rankFilters['period_type'] = 'weekly';
        $rankFilters['week_no'] = $weekNo;

        $ranking = $this->getDistrictRanking($rankFilters);
        $items = method_exists($ranking, 'getCollection') ? $ranking->getCollection() : collect();

        foreach ($items as $i => $row) {
            if ((int) ($row->district_id ?? 0) === (int) $district->id) {
                return (int) (($ranking->firstItem() ?? 1) + $i);
            }
        }

        return null;
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
