<?php
namespace App\Services;

use App\Models\District;
use App\Models\DistrictKpiScore;
use App\Models\Division;
use App\Models\KpiCategory;
use App\Models\KpiScoringParameter;
use App\Models\Tehsil;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ScorecardService
{
    private static bool $scorecardWeightWarningsLogged = false;

    private const ALLOWED_CALCULATION_TYPES = [
        'general',
        'sixty_forty',
        'special_branch_negative',
        'victims_negative',
    ];

    private const VISIBLE_PPT_FORMULAS = [
        'percentage' => [
            'label' => 'Percentage Formula',
            'explanation' => 'numerator / denominator x weightage',
        ],
        'resolved_ratio' => [
            'label' => 'Resolved Ratio Formula',
            'explanation' => 'resolved / total x weightage',
        ],
        'amount_deposit_ratio' => [
            'label' => 'Fine Deposit Formula',
            'explanation' => 'amount deposited / PSID generated x weightage',
        ],
        'yes_no' => [
            'label' => 'Yes/No Formula',
            'explanation' => 'yes = full marks; no = 0',
        ],
        'mobility_index' => [
            'label' => 'Mobility Index Formula',
            'explanation' => 'weighted mobility scores / inspection intervals x weightage',
        ],
    ];

    private const DISTRICT_GEOJSON_ALIASES = [
        // New districts that are not present in the 36-district Punjab GeoJSON.
        // Map them to the legacy polygon so they remain visible on the map.
        'KOT ADDU' => 'MUZAFFARGARH',
        'JAMPUR' => 'RAJANPUR',
        'TAUNSA' => 'DERA GHAZI KHAN',
        'TALAGANG' => 'CHAKWAL',
        'MURREE' => 'RAWALPINDI',
        'WAZIRABAD' => 'GUJRANWALA',
    ];

    private function parseWeekNo(?string $weekNo): ?array
    {
        if (! $weekNo || strlen($weekNo) !== 6 || preg_match('/^\d{6}$/', $weekNo) !== 1) {
            return null;
        }

        return [
            'year' => (int) substr($weekNo, 0, 4),
            'week' => (int) substr($weekNo, 4, 2),
        ];
    }

    public function getWeekDateRange(string $weekNo): array
    {
        $parsed = $this->parseWeekNo($weekNo);
        if (! $parsed) {
            return [
                'start' => null,
                'end' => null,
                'label' => '—',
                'label_with_year' => '—',
                'table_label' => '—',
            ];
        }

        // Old PPMF weekly reporting cycle is Thursday -> Wednesday (not ISO Monday -> Sunday).
        // We keep the stored week_no as ISO-week key (YYYYWW), but map it to the Thursday start.
        $start = Carbon::now()
            ->setISODate($parsed['year'], $parsed['week'], Carbon::THURSDAY)
            ->startOfDay();
        $end = $start->copy()->addDays(6)->endOfDay();

        $label = $start->format('d M') . ' - ' . $end->format('d M');
        $labelWithYear = $start->format('d M, Y') . ' - ' . $end->format('d M, Y');
        $tableLabel = $start->format('d M, Y') . '<br>-<br>' . $end->format('d M, Y');

        return [
            'start' => $start,
            'end' => $end,
            'label' => $label,
            'label_with_year' => $labelWithYear,
            'table_label' => $tableLabel,
        ];
    }

    private function weekNoShift(string $weekNo, int $deltaWeeks): ?string
    {
        $parsed = $this->parseWeekNo($weekNo);
        if (! $parsed) {
            return null;
        }

        // Shift is anchored on PPMF week start (Thursday).
        $start = Carbon::now()
            ->setISODate($parsed['year'], $parsed['week'], Carbon::THURSDAY)
            ->startOfDay()
            ->addWeeks($deltaWeeks);

        return sprintf('%d%02d', (int) $start->isoFormat('GGGG'), (int) $start->isoWeek());
    }

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

    private function applyDivisionUserScope($query)
    {
        $user = Auth::user();
        if (! $user || ! $user->role) {
            return $query;
        }

        $roleSlug = $user->role->slug ?? null;
        if (in_array($roleSlug, ['super_admin', 'chief_secretary', 'pmru_user'], true)) {
            return $query;
        }

        if ($roleSlug === 'commissioner' && $user->division_id) {
            return $query->whereKey($user->division_id);
        }

        $districtId = $user->district_id ?? null;
        if (! $districtId && $user->tehsil_id) {
            $districtId = Tehsil::whereKey($user->tehsil_id)->value('district_id');
        }

        if ($districtId) {
            $divisionId = District::whereKey($districtId)->value('division_id');
            if ($divisionId) {
                return $query->whereKey($divisionId);
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

        $period = (string) ($filters['period_type'] ?? ($filters['period'] ?? 'weekly'));
        if ($period === 'all' || $period === 'all_time') {
            $period = 'all_time';
        }
        if (! in_array($period, ['weekly', 'monthly', 'quarterly', 'yearly', 'all_time'], true)) {
            $period = 'weekly';
        }
        $filters['period_type'] = $period;

        $calc = (string) ($filters['calculation_type'] ?? 'general');
        // Backward compatibility: older data/links may still use "negative_marking".
        if ($calc === 'negative_marking') {
            $filters['calculation_type'] = 'negative_marking';
        } elseif (! in_array($calc, self::ALLOWED_CALCULATION_TYPES, true)) {
            $filters['calculation_type'] = 'general';
        } else {
            $filters['calculation_type'] = $calc;
        }

        $filters['year'] = (int) ($filters['year'] ?? now()->year);

        $areaType = (string) ($filters['area_type'] ?? 'district');
        if (! in_array($areaType, ['district', 'division'], true)) {
            $areaType = 'district';
        }
        $filters['area_type'] = $areaType;

        // Weekly period key (YYYYWW)
        if ($filters['period_type'] === 'weekly') {
            $filters['week_no'] = $filters['week_no'] ?? null;

            if (! $filters['week_no'] && ! empty($filters['week_range'])) {
                $filters['week_no'] = $this->weekNoFromRangeLabel((string) $filters['week_range'], (int) $filters['year']);
            }

            if (! $filters['week_no']) {
                // Default to the latest *completed* PPMF reporting week (Thu->Wed),
                // not the current ISO week (which often has no data yet).
                $runningWeekNo = $this->getCurrentRunningPpmfWeekNo();
                $filters['week_no'] = $this->weekNoShift($runningWeekNo, -1) ?: $runningWeekNo;
            }

            // Persist dropdown selection: week_range now uses week_no values.
            if (empty($filters['week_range']) && ! empty($filters['week_no'])) {
                $filters['week_range'] = (string) $filters['week_no'];
            }

            // Align month/quarter dropdowns with the selected ISO week so filters look correct.
            $weekNo = (string) ($filters['week_no'] ?? '');
            $range = $this->getWeekDateRange($weekNo);
            if ($range['start']) {
                $filters['month'] = sprintf('%02d', (int) $range['start']->month);
                $filters['quarter'] = (int) $range['start']->quarter;
                // Keep year dropdown aligned too (ISO year encoded inside week_no).
                $filters['year'] = (int) substr($weekNo, 0, 4);
            }
        }

        if ($filters['period_type'] === 'monthly') {
            $filters['month'] = sprintf('%02d', (int) ($filters['month'] ?? now()->month));
        }

        if ($filters['period_type'] === 'quarterly') {
            $filters['quarter'] = (int) ($filters['quarter'] ?? now()->quarter);
        }

        return $filters;
    }

    private function applyCalculationTypeFilter($query, string $calculationType)
    {
        if ($calculationType === 'special_branch_negative') {
            return $query->whereIn('calculation_type', ['special_branch_negative', 'negative_marking']);
        }

        if ($calculationType === 'negative_marking') {
            return $query->where('calculation_type', 'negative_marking');
        }

        return $query->where('calculation_type', $calculationType);
    }

    private function weekNoFromRangeLabel(string $label, int $year): ?string
    {
        // Supports both:
        // - "202621" (week_no)
        // - "07 May - 13 May" labels (legacy)
        $label = trim($label);

        if (preg_match('/^\d{6}$/', $label) === 1) {
            return $label;
        }

        $parts = preg_split('/\s*-\s*/', $label);
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
            ->where('year', (int) $filters['year'])
            ->when($filters['period_type'] !== 'all_time', fn ($q) => $q->where('period_type', $filters['period_type']));

        $query = $this->applyCalculationTypeFilter($query, (string) $filters['calculation_type']);

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

        if ($filters['period_type'] === 'all_time') {
            // All-time scope: use year + optional date range if provided.
            if (! empty($filters['date_from'])) {
                $query->whereDate('date_from', '>=', $filters['date_from']);
            }
            if (! empty($filters['date_to'])) {
                $query->whereDate('date_to', '<=', $filters['date_to']);
            }
        }

        if (! empty($filters['kpi_category_id'])) {
            $query->where('kpi_category_id', $filters['kpi_category_id']);
        }

        return $query;
    }

    private function getKpiCategoriesForWeights(array $filters)
    {
        $filters = $this->normalizeFilters($filters);

        return KpiCategory::query()
            ->where('is_active', true)
            ->when(! empty($filters['kpi_category_id']), fn ($q) => $q->where('id', (int) $filters['kpi_category_id']))
            ->orderBy('name')
            ->get(['id', 'name', 'scorecard_weightage']);
    }

    private function weightsUnionSql(array $weights): ?string
    {
        if (! $weights) {
            return null;
        }

        $parts = [];
        foreach ($weights as $kpiCategoryId => $weightage) {
            $kpiCategoryId = (int) $kpiCategoryId;
            $weightage = (float) $weightage;
            $parts[] = "SELECT {$kpiCategoryId} as kpi_category_id, {$weightage} as weightage";
        }

        return implode(' UNION ALL ', $parts);
    }

    private function buildDistrictWeightedScoreAggQuery(array $filters): array
    {
        $filters = $this->normalizeFilters($filters);
        $this->warnIfScorecardWeightsAreInvalid();

        $kpiCategories = $this->getKpiCategoriesForWeights($filters);
        $weights = $this->getKpiDisplayWeightages($kpiCategories);

        $totalKpis = count($weights);
        $totalWeightage = array_sum($weights);

        $weightsSql = $this->weightsUnionSql($weights);
        if (! $weightsSql) {
            return [
                'query' => DB::query()->from(DB::raw('(SELECT NULL as district_id) as x'))->whereRaw('1=0'),
                'total_kpis' => 0,
                'total_weightage' => 0.0,
            ];
        }

        $districtScope = District::query()->where('is_active', true);
        $districtScope = $this->applyDistrictFilters($districtScope, $filters);
        $districtScope = $this->applyUserScope($districtScope);

        $districtIds = (clone $districtScope)->select('districts.id as district_id');

        $scoreBase = DistrictKpiScore::query();
        $scoreBase = $this->applyUserScope($scoreBase);
        $scoreBase = $this->applyScoreFilters($scoreBase, $filters);

        $kpiWeights = DB::table(DB::raw("({$weightsSql}) as kpi_weights"));

        $agg = DB::query()
            ->fromSub($districtIds, 'd')
            ->crossJoinSub($kpiWeights, 'w')
            ->leftJoinSub(
                $scoreBase->select([
                    'district_id',
                    'kpi_category_id',
                    'final_score',
                    'is_reported',
                ]),
                's',
                function ($join) {
                    $join
                        ->on('s.district_id', '=', 'd.district_id')
                        ->on('s.kpi_category_id', '=', 'w.kpi_category_id');
                }
            )
            ->select([
                'd.district_id',
                DB::raw("{$totalKpis} as total_kpis"),
                DB::raw("SUM(CASE WHEN s.is_reported = true THEN 1 ELSE 0 END) as reported_kpis"),
                DB::raw("{$totalWeightage} as total_weightage"),
                DB::raw("SUM(CASE WHEN s.is_reported = true THEN w.weightage ELSE 0 END) as covered_weightage"),
                DB::raw("SUM(CASE WHEN s.is_reported = true THEN ((COALESCE(s.final_score, 0) * w.weightage) / 100) ELSE 0 END) as weighted_score_sum"),
            ])
            ->groupBy('d.district_id');

        $agg->addSelect([
            DB::raw('SUM(CASE WHEN s.is_reported = true THEN ((COALESCE(s.final_score, 0) * w.weightage) / 100) ELSE 0 END) as score_percentage'),
        ]);

        return [
            'query' => $agg,
            'total_kpis' => $totalKpis,
            'total_weightage' => (float) $totalWeightage,
        ];
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

        $agg = $this->buildDistrictWeightedScoreAggQuery($filters);
        $distRows = (clone $agg['query'])->get([
            'district_id',
            'reported_kpis',
            'score_percentage',
        ]);

        $excellentCount = 0;
        $goodCount = 0;
        $averageCount = 0;
        $criticalCount = 0;
        $unreportedBandCount = 0;
        $sumScore = 0.0;
        $reportedCount = 0;
        $topDistrictId = null;
        $topScore = null;
        $lowDistrictId = null;
        $lowScore = null;

        foreach ($distRows as $r) {
            $reported = ((int) ($r->reported_kpis ?? 0)) > 0;
            $score = (float) ($r->score_percentage ?? 0);

            if (! $reported) {
                $unreportedBandCount++;
                continue;
            }

            $reportedCount++;
            $sumScore += $score;

            if ($topScore === null || $score > $topScore) {
                $topScore = $score;
                $topDistrictId = (int) $r->district_id;
            }

            if ($lowScore === null || $score < $lowScore) {
                $lowScore = $score;
                $lowDistrictId = (int) $r->district_id;
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

        $avgScore = $reportedCount > 0 ? ($sumScore / $reportedCount) : 0.0;

        $topDistrictName = $topDistrictId ? District::whereKey($topDistrictId)->value('name') : null;
        $lowDistrictName = $lowDistrictId ? District::whereKey($lowDistrictId)->value('name') : null;
        $unreportedCount = max(0, $totalDistricts - $reportedCount);

        return [
            'total_districts'     => $totalDistricts,
            'reported_districts'  => $reportedCount,
            'unreported_districts'=> $unreportedCount,
            'average_score'       => round($avgScore, 2),
            'top_district'        => $topDistrictName,
            'top_score'           => $topScore !== null ? round((float) $topScore, 2) : null,
            'low_district'        => $lowDistrictName,
            'low_score'           => $lowScore !== null ? round((float) $lowScore, 2) : null,
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

        $agg = $this->buildDistrictWeightedScoreAggQuery($filters);
        $scoreAgg = $agg['query'];

        $districtsQuery->leftJoinSub($scoreAgg, 'score_agg', function ($join) {
            $join->on('districts.id', '=', 'score_agg.district_id');
        });

        $districtsQuery->addSelect([
            'districts.*',
            DB::raw('COALESCE(score_agg.total_kpis, 0) as total_kpis'),
            DB::raw('COALESCE(score_agg.reported_kpis, 0) as reported_kpis'),
            DB::raw('COALESCE(score_agg.score_percentage, 0) as score_percentage'),
            DB::raw('COALESCE(score_agg.total_weightage, 0) as total_weightage'),
            DB::raw('COALESCE(score_agg.covered_weightage, 0) as covered_weightage'),
            DB::raw('COALESCE(score_agg.weighted_score_sum, 0) as weighted_score_sum'),
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
                    'total_weightage' => (float) ($district->total_weightage ?? 0),
                    'covered_weightage' => (float) ($district->covered_weightage ?? 0),
                    'weighted_score_sum' => (float) ($district->weighted_score_sum ?? 0),
                    'district' => $district,
                ];
            })
        );

        return $paginator;
    }

    public function getDistrictScoresForMap(array $filters): array
    {
        $filters = $this->normalizeFilters($filters);

        $districtsQuery = District::query()
            ->where('is_active', true);

        $districtsQuery = $this->applyDistrictFilters($districtsQuery, $filters);
        $districtsQuery = $this->applyUserScope($districtsQuery);

        $agg = $this->buildDistrictWeightedScoreAggQuery($filters);
        $scoreAgg = $agg['query'];

        $districtsQuery->leftJoinSub($scoreAgg, 'score_agg', function ($join) {
            $join->on('districts.id', '=', 'score_agg.district_id');
        });

        $districtsQuery->addSelect([
            'districts.name',
            DB::raw('COALESCE(score_agg.score_percentage, 0) as score_percentage'),
            DB::raw('COALESCE(score_agg.reported_kpis, 0) as reported_kpis'),
        ]);

        $districtsQuery = $this->applyPerformanceFilter(
            $districtsQuery,
            $filters,
            'COALESCE(score_agg.score_percentage, 0)',
            'COALESCE(score_agg.reported_kpis, 0) = 0'
        );

        return $districtsQuery
            ->orderBy('districts.name')
            ->get()
            ->mapWithKeys(fn ($row) => [(string) $row->name => round((float) ($row->score_percentage ?? 0), 2)])
            ->all();
    }

    public function getDistrictMapMeta(array $filters): array
    {
        $filters = $this->normalizeFilters($filters);

        $districtsQuery = District::query()
            ->where('is_active', true);

        $districtsQuery = $this->applyDistrictFilters($districtsQuery, $filters);
        $districtsQuery = $this->applyUserScope($districtsQuery);

        $agg = $this->buildDistrictWeightedScoreAggQuery($filters);
        $scoreAgg = $agg['query'];

        $districtsQuery->leftJoinSub($scoreAgg, 'score_agg', function ($join) {
            $join->on('districts.id', '=', 'score_agg.district_id');
        });

        $districtsQuery->addSelect([
            'districts.id',
            'districts.name',
            DB::raw('COALESCE(score_agg.score_percentage, 0) as score_percentage'),
            DB::raw('COALESCE(score_agg.reported_kpis, 0) as reported_kpis'),
        ]);

        $districtsQuery = $this->applyPerformanceFilter(
            $districtsQuery,
            $filters,
            'COALESCE(score_agg.score_percentage, 0)',
            'COALESCE(score_agg.reported_kpis, 0) = 0'
        );

        $rows = $districtsQuery
            ->orderByDesc('score_percentage')
            ->orderBy('districts.name')
            ->get();

        $scores = [];
        $ids = [];
        $ranks = [];
        $rank = 0;
        foreach ($rows as $row) {
            $name = (string) ($row->name ?? '');
            if ($name === '') {
                continue;
            }
            $rank++;
            $key = strtoupper(trim($name));
            $key = self::DISTRICT_GEOJSON_ALIASES[$key] ?? $key;

            $scores[$key] = round((float) ($row->score_percentage ?? 0), 2);
            $ids[$key] = (int) ($row->id ?? 0);

            if (! isset($ranks[$key])) {
                $ranks[$key] = $rank;
            } else {
                $ranks[$key] = min((int) $ranks[$key], $rank);
            }
        }

        return [
            'scores' => $scores,
            'ids' => $ids,
            'ranks' => $ranks,
        ];
    }

    public function getDivisionMapMeta(array $filters): array
    {
        $filters = $this->normalizeFilters($filters);

        $divisionsQuery = Division::query()
            ->where('is_active', true);
        $divisionsQuery = $this->applyDivisionUserScope($divisionsQuery);
        if (! empty($filters['division_id'])) {
            $divisionsQuery->whereKey($filters['division_id']);
        }

        $districtAgg = $this->buildDistrictWeightedScoreAggQuery($filters);

        $divisionAgg = DB::query()
            ->from('districts')
            ->leftJoinSub($districtAgg['query'], 'score_agg', fn ($j) => $j->on('districts.id', '=', 'score_agg.district_id'))
            ->where('districts.is_active', true)
            ->when(! empty($filters['division_id']), fn ($q) => $q->where('districts.division_id', (int) $filters['division_id']))
            ->select([
                'districts.division_id',
                DB::raw("AVG(CASE WHEN COALESCE(score_agg.reported_kpis, 0) > 0 THEN COALESCE(score_agg.score_percentage, 0) ELSE NULL END) as score_percentage"),
                DB::raw("SUM(CASE WHEN COALESCE(score_agg.reported_kpis, 0) > 0 THEN 1 ELSE 0 END) as reported_districts"),
            ])
            ->groupBy('districts.division_id');

        $divisionsQuery->leftJoinSub($divisionAgg, 'div_agg', fn ($j) => $j->on('divisions.id', '=', 'div_agg.division_id'));
        $divisionsQuery->addSelect([
            'divisions.id',
            'divisions.name',
            DB::raw('COALESCE(div_agg.score_percentage, 0) as score_percentage'),
            DB::raw('COALESCE(div_agg.reported_districts, 0) as reported_districts'),
        ]);

        $divisionsQuery = $this->applyPerformanceFilter(
            $divisionsQuery,
            $filters,
            'COALESCE(div_agg.score_percentage, 0)',
            'COALESCE(div_agg.reported_districts, 0) = 0'
        );

        $rows = $divisionsQuery
            ->orderByDesc('score_percentage')
            ->orderBy('divisions.name')
            ->get();

        $scores = [];
        $ids = [];
        $ranks = [];
        $rank = 0;
        foreach ($rows as $row) {
            $name = (string) ($row->name ?? '');
            if ($name === '') {
                continue;
            }
            $rank++;
            $key = strtoupper(trim($name));
            $scores[$key] = round((float) ($row->score_percentage ?? 0), 2);
            $ids[$key] = (int) ($row->id ?? 0);
            $ranks[$key] = $rank;
        }

        return [
            'scores' => $scores,
            'ids' => $ids,
            'ranks' => $ranks,
        ];
    }

    public function getDivisionRanking(array $filters)
    {
        $filters = $this->normalizeFilters($filters);

        $divisionsQuery = Division::query()
            ->where('is_active', true);
        $divisionsQuery = $this->applyDivisionUserScope($divisionsQuery);
        if (! empty($filters['division_id'])) {
            $divisionsQuery->whereKey($filters['division_id']);
        }

        $districtAgg = $this->buildDistrictWeightedScoreAggQuery($filters);

        $divisionAgg = DB::query()
            ->from('districts')
            ->leftJoinSub($districtAgg['query'], 'score_agg', fn ($j) => $j->on('districts.id', '=', 'score_agg.district_id'))
            ->where('districts.is_active', true)
            ->when(! empty($filters['division_id']), fn ($q) => $q->where('districts.division_id', (int) $filters['division_id']))
            ->select([
                'districts.division_id',
                DB::raw('COUNT(DISTINCT districts.id) as district_count'),
                DB::raw("SUM(CASE WHEN COALESCE(score_agg.reported_kpis, 0) > 0 THEN 1 ELSE 0 END) as reported_districts"),
                DB::raw("AVG(CASE WHEN COALESCE(score_agg.reported_kpis, 0) > 0 THEN COALESCE(score_agg.score_percentage, 0) ELSE NULL END) as score_percentage"),
            ])
            ->groupBy('districts.division_id');

        $divisionsQuery->leftJoinSub($divisionAgg, 'div_agg', fn ($j) => $j->on('divisions.id', '=', 'div_agg.division_id'));
        $divisionsQuery->addSelect([
            'divisions.*',
            DB::raw('COALESCE(div_agg.district_count, 0) as district_count'),
            DB::raw('COALESCE(div_agg.reported_districts, 0) as reported_districts'),
            DB::raw('COALESCE(div_agg.score_percentage, 0) as score_percentage'),
        ]);

        $divisionsQuery = $this->applyPerformanceFilter(
            $divisionsQuery,
            $filters,
            'COALESCE(div_agg.score_percentage, 0)',
            'COALESCE(div_agg.reported_districts, 0) = 0'
        );

        $paginator = $divisionsQuery
            ->orderByDesc('score_percentage')
            ->orderBy('divisions.name')
            ->paginate($filters['per_page'])
            ->withQueryString();

        $paginator->setCollection(
            $paginator->getCollection()->map(function ($division) {
                return (object) [
                    'division_id' => $division->id,
                    'score_percentage' => (float) ($division->score_percentage ?? 0),
                    'district_count' => (int) ($division->district_count ?? 0),
                    'reported_districts' => (int) ($division->reported_districts ?? 0),
                    'division' => $division,
                ];
            })
        );

        return $paginator;
    }

    public function getDivisionSummary(array $filters): array
    {
        $filters = $this->normalizeFilters($filters);

        $divisionsQuery = Division::query()->where('is_active', true);
        $divisionsQuery = $this->applyDivisionUserScope($divisionsQuery);
        if (! empty($filters['division_id'])) {
            $divisionsQuery->whereKey($filters['division_id']);
        }

        $totalDivisions = (clone $divisionsQuery)->count();

        $districtAgg = $this->buildDistrictWeightedScoreAggQuery($filters);

        $divisionAgg = DB::query()
            ->from('districts')
            ->leftJoinSub($districtAgg['query'], 'score_agg', fn ($j) => $j->on('districts.id', '=', 'score_agg.district_id'))
            ->where('districts.is_active', true)
            ->when(! empty($filters['division_id']), fn ($q) => $q->where('districts.division_id', (int) $filters['division_id']))
            ->select([
                'districts.division_id',
                DB::raw('COUNT(DISTINCT districts.id) as district_count'),
                DB::raw("SUM(CASE WHEN COALESCE(score_agg.reported_kpis, 0) > 0 THEN 1 ELSE 0 END) as reported_districts"),
                DB::raw("AVG(CASE WHEN COALESCE(score_agg.reported_kpis, 0) > 0 THEN COALESCE(score_agg.score_percentage, 0) ELSE NULL END) as score_percentage"),
            ])
            ->groupBy('districts.division_id');

        $rows = (clone $divisionAgg)->get();

        $excellentCount = 0;
        $goodCount = 0;
        $averageCount = 0;
        $criticalCount = 0;
        $unreportedCount = 0;
        $sumScore = 0.0;
        $reportedDivisions = 0;

        foreach ($rows as $r) {
            $reported = ((int) ($r->reported_districts ?? 0)) > 0;
            $score = (float) ($r->score_percentage ?? 0);
            if (! $reported) {
                $unreportedCount++;
                continue;
            }
            $reportedDivisions++;
            $sumScore += $score;

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

        $avgScore = $reportedDivisions > 0 ? ($sumScore / $reportedDivisions) : 0.0;

        return [
            'total_divisions'      => $totalDivisions,
            'reported_divisions'   => $reportedDivisions,
            'unreported_divisions' => max(0, $totalDivisions - $reportedDivisions),
            'average_score'        => round($avgScore, 2),
            'excellent_count'      => $excellentCount,
            'good_count'           => $goodCount,
            'average_count'        => $averageCount,
            'critical_count'       => $criticalCount,
            'unreported_count'     => $unreportedCount,
        ];
    }

    public function getDivisionScorecardDetail(Division $division, array $filters): array
    {
        $filters = $this->normalizeFilters($filters);

        $filters['division_id'] = $division->id;
        $filters['area_type'] = 'district';

        $currentWeekNo = (string) ($filters['week_no'] ?? '');
        $weekNos = $this->getWeeklyWindow($currentWeekNo);

        $comparisonWeeks = collect($weekNos)->map(function ($weekNo, $key) {
            $range = $weekNo ? $this->getWeekDateRange((string) $weekNo) : null;
            return [
                'key' => $key,
                'week_no' => $weekNo,
                'label' => $range ? $range['label_with_year'] : '—',
                'table_label' => $range ? $range['table_label'] : '—',
                'start' => $range['start'] ?? null,
                'end' => $range['end'] ?? null,
            ];
        })->values()->all();

        // For management readability, show the selected (current) reporting week in the heading.
        $periodHeadingLabel = $comparisonWeeks[2]['label'] ?? ($comparisonWeeks[count($comparisonWeeks) - 1]['label'] ?? '—');

        $makeFiltersForWeek = function (?string $weekNo) use ($filters): array {
            $f = $filters;
            $f['period_type'] = 'weekly';
            $f['week_no'] = $weekNo;
            $f['week_range'] = $weekNo;
            return $f;
        };

        $currentAgg = $this->buildDistrictWeightedScoreAggQuery($makeFiltersForWeek($weekNos['current']));
        $prev1Agg = $this->buildDistrictWeightedScoreAggQuery($makeFiltersForWeek($weekNos['previous_1']));
        $prev2Agg = $this->buildDistrictWeightedScoreAggQuery($makeFiltersForWeek($weekNos['previous_2']));

        $districtsQuery = District::query()
            ->where('is_active', true)
            ->where('division_id', $division->id)
            ->orderBy('name');
        $districtsQuery = $this->applyUserScope($districtsQuery);

        $districtsQuery->leftJoinSub($prev2Agg['query'], 'p2', fn ($j) => $j->on('districts.id', '=', 'p2.district_id'));
        $districtsQuery->leftJoinSub($prev1Agg['query'], 'p1', fn ($j) => $j->on('districts.id', '=', 'p1.district_id'));
        $districtsQuery->leftJoinSub($currentAgg['query'], 'c', fn ($j) => $j->on('districts.id', '=', 'c.district_id'));

        $districts = $districtsQuery->get([
            'districts.id',
            'districts.name',
            'districts.tier',
            DB::raw('COALESCE(p2.score_percentage, 0) as previous_2_score'),
            DB::raw('COALESCE(p1.score_percentage, 0) as previous_1_score'),
            DB::raw('COALESCE(c.score_percentage, 0) as current_score'),
            DB::raw('COALESCE(c.reported_kpis, 0) as current_reported_kpis'),
        ]);

        $rows = $districts->map(function ($d) {
            $p2 = (float) ($d->previous_2_score ?? 0);
            $p1 = (float) ($d->previous_1_score ?? 0);
            $cur = (float) ($d->current_score ?? 0);
            $trend = $cur > $p1 ? 'up' : ($cur < $p1 ? 'down' : 'eq');
            return (object) [
                'district_id' => (int) $d->id,
                'district_name' => (string) $d->name,
                'tier' => $d->tier,
                'previous_2_score' => $p2,
                'previous_1_score' => $p1,
                'scores' => [
                    'previous_2' => $p2,
                    'previous_1' => $p1,
                    'current' => $cur,
                ],
                'previous_score' => $p1,
                'current_score' => $cur,
                'trend' => $trend,
                'reported' => ((int) ($d->current_reported_kpis ?? 0)) > 0,
            ];
        });

        $reportedScores = $rows->filter(fn ($r) => $r->reported)->pluck('current_score')->all();
        $divisionScore = $reportedScores ? (array_sum($reportedScores) / count($reportedScores)) : 0.0;

        $divisionRank = null;
        $rankList = $this->getDivisionRanking($filters);
        $items = method_exists($rankList, 'getCollection') ? $rankList->getCollection() : collect();
        foreach ($items as $i => $row) {
            if ((int) ($row->division_id ?? 0) === (int) $division->id) {
                $divisionRank = (int) (($rankList->firstItem() ?? 1) + $i);
                break;
            }
        }

        $improvedCount = $rows->filter(fn ($r) => $r->reported && $r->trend === 'up')->count();
        $declinedCount = $rows->filter(fn ($r) => $r->reported && $r->trend === 'down')->count();

        return [
            'weekNos' => $weekNos,
            'comparisonWeeks' => $comparisonWeeks,
            'periodHeadingLabel' => $periodHeadingLabel,
            'divisionScore' => round($divisionScore, 2),
            'divisionRank' => $divisionRank,
            'districtRows' => $rows,
            'totalDistricts' => $rows->count(),
            'reportedDistricts' => $rows->filter(fn ($r) => $r->reported)->count(),
            'improvedCount' => $improvedCount,
            'declinedCount' => $declinedCount,
        ];
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
            ->where('year', (int) $filters['year'])
        ;

        $query = $this->applyCalculationTypeFilter($query, (string) $filters['calculation_type'])
            ->when($filters['period_type'] !== 'all_time', fn ($q) => $q->where('period_type', $filters['period_type']))
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

            $agg = $this->buildDistrictWeightedScoreAggQuery($tierFilters);
            $districtsQuery->leftJoinSub($agg['query'], 'score_agg', fn ($j) => $j->on('districts.id', '=', 'score_agg.district_id'));

            $districtsQuery->addSelect([
                'districts.*',
                DB::raw('COALESCE(score_agg.total_kpis, 0) as total_kpis'),
                DB::raw('COALESCE(score_agg.reported_kpis, 0) as reported_kpis'),
                DB::raw('COALESCE(score_agg.score_percentage, 0) as score_value'),
            ]);

            $districtsQuery = $this->applyPerformanceFilter(
                $districtsQuery,
                $tierFilters,
                'COALESCE(score_agg.score_percentage, 0)',
                'COALESCE(score_agg.reported_kpis, 0) = 0'
            );

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

        $agg = $this->buildDistrictWeightedScoreAggQuery($filters);
        $distRows = (clone $agg['query'])->get([
            'district_id',
            'reported_kpis',
            'score_percentage',
        ]);

        $excellentCount = 0;
        $goodCount = 0;
        $averageCount = 0;
        $criticalCount = 0;
        $unreportedBandCount = 0;
        $sumScore = 0.0;
        $reportedDistricts = 0;

        foreach ($distRows as $r) {
            $reported = ((int) ($r->reported_kpis ?? 0)) > 0;
            $score = (float) ($r->score_percentage ?? 0);

            if (! $reported) {
                $unreportedBandCount++;
                continue;
            }

            $reportedDistricts++;
            $sumScore += $score;

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

        $avgScore = $reportedDistricts > 0 ? ($sumScore / $reportedDistricts) : 0.0;

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
            $q = DistrictKpiScore::query()
                ->where('district_id', $district->id)
                ->where('period_type', 'weekly')
                ->where('is_active', true)
            ;
            $q = $this->applyCalculationTypeFilter($q, $calculationType);
            $currentWeekNo = $q->max('week_no');
        }

        $weekNos = $this->getWeeklyWindow($currentWeekNo);

        $weekHeaders = collect($weekNos)->map(function ($weekNo, $key) {
            $label = $weekNo ?: '—';
            $tableLabel = '—';
            if ($weekNo && strlen($weekNo) === 6) {
                $range = $this->getWeekDateRange((string) $weekNo);
                $label = $range['label_with_year'];
                $tableLabel = $range['table_label'];
            }
            return ['key' => $key, 'label' => $label, 'table_label' => $tableLabel, 'week_no' => $weekNo];
        })->values()->all();

        $periodLabel = ($weekHeaders[2]['label'] ?? '—');

        // KPI categories to display (limit to those seeded/active).
        $kpiCategories = KpiCategory::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'scorecard_weightage']);

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
                ->where('is_active', true)
                ->whereIn('week_no', array_values(array_filter($weekNos)))
            ;

            $scoresByWeek = $this->applyCalculationTypeFilter($scoresByWeek, $calculationType)
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

            if (array_key_exists('final_score', $p2) && $p2['final_score'] !== null) {
                $covered['previous_2'] += $weight;
            }
            if (array_key_exists('final_score', $p1) && $p1['final_score'] !== null) {
                $covered['previous_1'] += $weight;
            }
            if (array_key_exists('final_score', $cur) && $cur['final_score'] !== null) {
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

        $totals = [
            'weightage' => round($totals['weightage'], 2),
            'previous_2' => round($totals['previous_2'], 2),
            'previous_1' => round($totals['previous_1'], 2),
            'current' => round($totals['current'], 2),
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
            'reported_kpis' => collect($rows)->filter(fn ($r) => array_key_exists('final_score', $r['current']) && $r['current']['final_score'] !== null)->count(),
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

    public function getDistrictKpiSubDetail(District $district, KpiCategory $kpiCategory, array $filters): array
    {
        $filters = $this->normalizeFilters($filters);
        $calculationType = (string) ($filters['calculation_type'] ?? 'general');
        $weekNo = $filters['week_no'] ?? null;

        if (! $weekNo) {
            $latestScore = DistrictKpiScore::query()
                ->where('district_id', $district->id)
                ->where('kpi_category_id', $kpiCategory->id)
                ->where('period_type', 'weekly')
                ->where('is_active', true);
            $latestScore = $this->applyCalculationTypeFilter($latestScore, $calculationType)->latest('week_no')->first();
            $weekNo = $latestScore?->week_no;
        }

        $scoreQuery = DistrictKpiScore::query()
            ->where('district_id', $district->id)
            ->where('kpi_category_id', $kpiCategory->id)
            ->where('period_type', 'weekly')
            ->where('week_no', $weekNo)
            ->where('is_active', true)
            ->where('is_reported', true);
        $score = $this->applyCalculationTypeFilter($scoreQuery, $calculationType)->first();

        $visibleFormulaTypes = array_keys(self::VISIBLE_PPT_FORMULAS);
        $detailsQuery = $score
            ? $score->details()
                ->whereHas('scoringParameter', fn ($query) => $query
                    ->where('is_active', true)
                    ->whereIn('formula_type', $visibleFormulaTypes))
                ->with('scoringParameter')
                ->orderBy('id')
            : null;

        $totalWeightage = $detailsQuery ? (float) (clone $detailsQuery)->sum('weightage') : 0;
        $totalScore = $detailsQuery ? (float) (clone $detailsQuery)->sum('score_obtained') : 0;
        $details = $detailsQuery
            ? $detailsQuery->paginate(10)->withQueryString()
            : new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10, 1, [
                'path' => request()->url(),
                'query' => request()->query(),
            ]);

        $details->setCollection($details->getCollection()->map(function ($detail) {
            $parameter = $detail->scoringParameter;
            $formulaType = (string) ($parameter?->formula_type ?? $parameter?->scoring_method ?? 'percentage');

            return [
                'parameter_name' => $parameter?->parameter_name ?? 'Sub-KPI',
                'formula_label' => self::VISIBLE_PPT_FORMULAS[$formulaType]['label'] ?? 'PPT Formula',
                'formula_expression' => $parameter?->formula_expression ?? $parameter?->description,
                'numerator_label' => $parameter?->numerator_label,
                'denominator_label' => $parameter?->denominator_label,
                'numerator_value' => $detail->numerator_value ?? $detail->reported_value,
                'denominator_value' => $detail->denominator_value ?? $detail->target_value,
                'achieved_percentage' => $detail->achieved_percentage,
                'weightage' => $detail->weightage,
                'score_obtained' => $detail->score_obtained,
                'evidence' => $detail->evidence,
            ];
        }));

        $periodLabel = 'No reporting week selected';
        if ($weekNo) {
            $periodLabel = $this->getWeekDateRange((string) $weekNo)['label_with_year'];
        }

        return [
            'score' => $score,
            'details' => $details,
            'periodLabel' => $periodLabel,
            'weekNo' => $weekNo,
            'visibleFormulaLegend' => $this->getVisiblePptFormulaLegend(),
            'summary' => [
                'weightage' => (float) $kpiCategory->scorecard_weightage,
                'marks_obtained' => round($totalScore, 2),
                'score_percentage' => (float) ($score?->final_score ?? 0),
                'reported_percentage' => (float) ($score?->reported_score ?? 0),
                'performance' => $score?->performance_label ?? 'Unreported',
                'grade' => $score?->grade ?? '—',
                'total_weightage' => round($totalWeightage, 2),
            ],
        ];
    }

    public function getVisiblePptFormulaLegend(): array
    {
        $activeTypes = KpiScoringParameter::query()
            ->where('is_active', true)
            ->whereIn('formula_type', array_keys(self::VISIBLE_PPT_FORMULAS))
            ->distinct()
            ->pluck('formula_type')
            ->all();

        $legend = collect(self::VISIBLE_PPT_FORMULAS)
            ->only($activeTypes)
            ->all();

        $hasTierTargets = KpiScoringParameter::query()
            ->where('is_active', true)
            ->whereNotNull('tier_1_target')
            ->exists();

        if ($hasTierTargets) {
            $legend['tier_wise_target'] = [
                'label' => 'Tier-wise Target Formula',
                'explanation' => 'reported value / target for district tier x weightage',
            ];
        }

        return $legend;
    }

    private function getWeeklyWindow(?string $currentWeekNo): array
    {
        if (! $currentWeekNo || strlen($currentWeekNo) !== 6) {
            return ['previous_2' => null, 'previous_1' => null, 'current' => $currentWeekNo];
        }

        $year = (int) substr($currentWeekNo, 0, 4);
        $week = (int) substr($currentWeekNo, 4, 2);
        // Anchor to PPMF week start (Thursday) so previous weeks match Thu->Wed cycle.
        $current = Carbon::now()->setISODate($year, $week, Carbon::THURSDAY)->startOfDay();
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

        // Old PPMF scorecard uses category-level scorecard weightage (NOT parameter-level weights).
        $raw = $kpiCategories
            ->mapWithKeys(fn ($c) => [(int) $c->id => (float) ($c->scorecard_weightage ?? 0)])
            ->all();

        $sum = array_sum($raw);
        if ($sum <= 0) {
            Log::warning('Active KPI scorecard weightage total is zero; using equal fallback weights.', [
                'kpi_category_ids' => $catIds,
            ]);
            $equal = 100 / max(1, count($catIds));
            return collect($catIds)->mapWithKeys(fn ($id) => [$id => round($equal, 2)])->all();
        }

        return collect($catIds)->mapWithKeys(fn ($id) => [$id => round((float) ($raw[$id] ?? 0), 2)])->all();
    }

    private function warnIfScorecardWeightsAreInvalid(): void
    {
        if (self::$scorecardWeightWarningsLogged) {
            return;
        }

        self::$scorecardWeightWarningsLogged = true;

        $categories = KpiCategory::query()
            ->where('is_active', true)
            ->get(['id', 'name', 'scorecard_weightage']);

        $categoryTotal = round((float) $categories->sum(fn ($c) => (float) ($c->scorecard_weightage ?? 0)), 2);
        if ($categoryTotal !== 100.0) {
            Log::warning('Active KPI category scorecard weightage total is not 100.', [
                'active_category_total' => $categoryTotal,
            ]);
        }

        $parameterTotals = KpiScoringParameter::query()
            ->select('kpi_category_id', DB::raw('SUM(weightage) as parameter_total'))
            ->where('is_active', true)
            ->groupBy('kpi_category_id')
            ->pluck('parameter_total', 'kpi_category_id');

        foreach ($categories as $category) {
            $categoryWeight = round((float) ($category->scorecard_weightage ?? 0), 2);
            $parameterTotal = round((float) ($parameterTotals[$category->id] ?? 0), 2);

            if ($categoryWeight !== $parameterTotal) {
                Log::warning('Active KPI parameter total does not match category scorecard weightage.', [
                    'kpi_category_id' => $category->id,
                    'category' => $category->name,
                    'category_weightage' => $categoryWeight,
                    'parameter_total' => $parameterTotal,
                ]);
            }
        }
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

        // Old PPMF uses Thu -> Wed reporting weeks.
        // We build options for the selected month, including the overlapping week
        // that may start in the previous month (e.g., 30 Apr - 06 May).
        $startOfMonth = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $endOfMonth = Carbon::createFromDate($year, $month, 1)->endOfMonth()->endOfDay();

        $cursor = $startOfMonth->copy();
        while ($cursor->dayOfWeek !== Carbon::THURSDAY) {
            $cursor->subDay();
        }

        $weeks = [];
        while (true) {
            $weekStart = $cursor->copy()->startOfDay();
            $weekEnd = $weekStart->copy()->addDays(6)->endOfDay();

            if ($weekStart->gt($endOfMonth)) {
                break;
            }

            // Include only weeks that overlap the month window.
            if ($weekEnd->gte($startOfMonth)) {
                $weekNo = sprintf('%d%02d', (int) $weekStart->isoFormat('GGGG'), (int) $weekStart->isoWeek());
                $weeks[$weekNo] = $weekStart->format('d M') . ' - ' . $weekEnd->format('d M');
            }

            $cursor->addWeek();
        }

        return $weeks;
    }

    private function getCurrentRunningPpmfWeekNo(?Carbon $at = null): string
    {
        $at = ($at ?: now())->copy()->startOfDay();
        while ($at->dayOfWeek !== Carbon::THURSDAY) {
            $at->subDay();
        }

        return sprintf('%d%02d', (int) $at->isoFormat('GGGG'), (int) $at->isoWeek());
    }

    public function getLatestWeeklyFilters(): array
    {
        $fallbackYear = (int) now()->year;
        $fallbackWeek = (int) now()->isoWeek();
        $fallbackWeekNo = sprintf('%d%02d', $fallbackYear, $fallbackWeek);

        $runningWeekNo = $this->getCurrentRunningPpmfWeekNo();

        $q = DistrictKpiScore::query()
            ->where('is_active', true)
            ->where('period_type', 'weekly');
        $q = $this->applyUserScope($q);

        // Prefer latest *completed* PPMF reporting week:
        // - must not be the currently running Thu->Wed week
        // - must not be a future week (end date must be < today)
        $candidateWeeks = (clone $q)
            ->where('calculation_type', 'general')
            ->whereNotNull('week_no')
            ->orderByDesc('year')
            ->orderByDesc('week_no')
            ->limit(50)
            ->get(['year', 'week_no']);

        $latestGeneral = null;
        $todayStart = now()->startOfDay();
        foreach ($candidateWeeks as $c) {
            $w = (string) ($c->week_no ?? '');
            if ($w === '' || $w === $runningWeekNo) {
                continue;
            }
            $range = $this->getWeekDateRange($w);
            if ($range['end'] && $range['end']->lt($todayStart)) {
                $latestGeneral = $c;
                break;
            }
        }

        if (! $latestGeneral) {
            // Fallback (safer than "latest"): pick the latest week that is not in the future.
            // Prefer running week over a future week if completed-week data is not available.
            $now = now();
            foreach ($candidateWeeks as $c) {
                $w = (string) ($c->week_no ?? '');
                if ($w === '') {
                    continue;
                }
                $range = $this->getWeekDateRange($w);
                if ($range['start'] && $range['start']->lte($now)) {
                    $latestGeneral = $c;
                    break;
                }
            }

            // Final fallback: if everything is future or missing, use latest available general week.
            $latestGeneral = $latestGeneral ?: $candidateWeeks->first();
        }

        $latestAny = null;
        if (! $latestGeneral) {
            $latestAny = (clone $q)
                ->whereNotNull('week_no')
                ->orderByDesc('year')
                ->orderByDesc('week_no')
                ->first(['year', 'week_no', 'calculation_type']);
        }

        $year = (int) ($latestGeneral?->year ?? $latestAny?->year ?? $fallbackYear);
        $weekNo = (string) ($latestGeneral?->week_no ?? $latestAny?->week_no ?? $fallbackWeekNo);
        $calc = (string) ($latestAny?->calculation_type ?? 'general');

        $filters = $this->normalizeFilters([
            'period' => 'weekly',
            'period_type' => 'weekly',
            'year' => $year,
            'week_no' => $weekNo,
            'week_range' => $weekNo,
            'calculation_type' => $calc === '' ? 'general' : $calc,
            'per_page' => 10,
        ]);

        return $filters;
    }

    public function getPpmfWeekDateRange(int $year, int $weekNo): array
    {
        return $this->getWeekDateRange(sprintf('%d%02d', $year, $weekNo));
    }

    public function getLatestCompletedPpmfWeekFilters(): array
    {
        // Old PPMF default behavior: when opening the scorecard without filters,
        // select the latest *completed* Thu->Wed reporting week based on calendar (not future/running week),
        // even if local seed data is not yet refreshed.
        $runningWeekNo = $this->getCurrentRunningPpmfWeekNo();
        $calendarDefault = $this->weekNoShift($runningWeekNo, -1) ?: $runningWeekNo;

        $filters = $this->getLatestWeeklyFilters();

        // Force calendar-based completed week when it differs from running week (prevents selecting 21-27 etc. on May 20).
        if ($calendarDefault && $calendarDefault !== $runningWeekNo) {
            $filters['period_type'] = 'weekly';
            $filters['week_no'] = $calendarDefault;
            $filters['week_range'] = $calendarDefault;
            $filters['year'] = (int) substr($calendarDefault, 0, 4);
            // Re-normalize to align month/quarter/year dropdowns with the selected PPMF week.
            $filters = $this->normalizeFilters($filters);
        }

        $filters['area_type'] = $filters['area_type'] ?? 'district';
        if (! in_array($filters['area_type'], ['district', 'division'], true)) {
            $filters['area_type'] = 'district';
        }
        if (empty($filters['calculation_type'])) {
            $filters['calculation_type'] = 'general';
        }
        if (empty($filters['period_type'])) {
            $filters['period_type'] = 'weekly';
        }
        return $filters;
    }

    public function getPpmfWeekOptions(?int $year = null, ?int $month = null): array
    {
        return $this->getWeekRanges($year, $month);
    }

    public function getComparisonWeeks(array $filters): array
    {
        $filters = $this->normalizeFilters($filters);
        $currentWeekNo = (string) ($filters['week_no'] ?? '');
        $weekNos = $this->getWeeklyWindow($currentWeekNo);
        return [
            'previous' => $weekNos['previous_1'] ?? null,
            'current' => $weekNos['current'] ?? null,
        ];
    }

    public function getLatestWeeklyDefaultFilters(): array
    {
        // Backward-compatible naming for controller usage / readability.
        return $this->getLatestCompletedPpmfWeekFilters();
    }
}


