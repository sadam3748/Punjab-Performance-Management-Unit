<?php
namespace App\Services;

use App\Models\District;
use App\Models\DistrictBaselineData;
use App\Models\DistrictKpiMetricValue;
use App\Models\Inspection;
use App\Models\KpiCategory;
use App\Models\ProvincialKpiMetric;
use App\Models\Tehsil;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

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
    | Legacy Inspection-Based Provincial Summary
    |--------------------------------------------------------------------------
    | Backward compatibility alias.
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

        $perPage = $this->normalizePerPage($filters['per_page'] ?? 10);

        return $query
            ->orderByDesc('last_reported_at')
            ->paginate($perPage)
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
        // Backward compatibility: graphical report view is now based on old PPMF-style KPI metrics.
        // This method returns KPI metric cards (not inspection workflow statuses).
        return [
            'cards' => $this->getGraphicalMetricCards($filters),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | KPI Graphical Table Data
    |--------------------------------------------------------------------------
    | Table records for graphical report detail list.
    */
    public function getGraphicalTableData(array $filters)
    {
        // Bottom detail table: inspections only (no status breakdown on top).
        $perPage = $this->normalizePerPage($filters['per_page'] ?? 10);

        $dateRange = $this->resolveGraphicalDateRange($filters);
        $filters['date_from'] = $dateRange['date_from'];
        $filters['date_to'] = $dateRange['date_to'];

        $query = $this->baseInspectionQuery();
        $query = $this->applyUserScope($query);

        // Do NOT filter on workflow status for old PPMF reporting screens.
        unset($filters['status']);

        $query = $this->applyInspectionFilters($query, $filters);

        return $query
            ->latest('inspection_datetime')
            ->paginate($perPage)
            ->withQueryString();
    }

    /*
    |--------------------------------------------------------------------------
    | Graphical Report Helpers (Old PPMF-style KPI metrics)
    |--------------------------------------------------------------------------
    */
    public function getGraphicalFilters(): array
    {
        $filterData = $this->getFilterData();

        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $months[] = [
                'value' => $m,
                'label' => Carbon::createFromDate(2024, $m, 1)->format('F'),
            ];
        }

        $years = [];
        $currentYear = (int) now()->format('Y');
        for ($y = $currentYear; $y >= 2020; $y--) {
            $years[] = $y;
        }

        return [
            'kpiCategories' => $filterData['kpiCategories'],
            'districts'     => $filterData['districts'],
            'tehsils'       => $filterData['tehsils'],
            'periodOptions' => [
                'last_week'       => 'Last Week',
                'current_week'    => 'Current Week',
                'last_four_weeks' => 'Last Four Weeks',
                'custom'          => 'Custom Date',
            ],
            'months'        => $months,
            'years'         => $years,
        ];
    }

    public function getGraphicalSummaryCards(array $filters): array
    {
        return $this->getGraphicalMetricCards($filters);
    }

    public function getGraphicalChartData(array $filters): array
    {
        return $this->buildGraphicalChartData($filters);
    }

    public function getGraphicalScopeTitle(array $filters): string
    {
        if (! empty($filters['tehsil_id'])) {
            $t = Tehsil::find($filters['tehsil_id']);
            if ($t) {
                return strtoupper($t->name);
            }
        }

        if (! empty($filters['district_id'])) {
            $d = District::find($filters['district_id']);
            if ($d) {
                return strtoupper($d->name);
            }
        }

        return 'PUNJAB';
    }

    public function resolveGraphicalDateRange(array $filters): array
    {
        $periodType = $this->normalizePeriod(['period_type' => $filters['period_type'] ?? ($filters['period'] ?? 'last_week')]);

        // Month/year override (if provided)
        if (! empty($filters['month']) && ! empty($filters['year'])) {
            $month = (int) $filters['month'];
            $year = (int) $filters['year'];
            if ($month >= 1 && $month <= 12 && $year >= 2000) {
                $from = Carbon::create($year, $month, 1)->startOfMonth();
                $to = (clone $from)->endOfMonth();
                return [
                    'period_type' => 'custom',
                    'date_from'   => $from->toDateString(),
                    'date_to'     => $to->toDateString(),
                ];
            }
        }

        if ($periodType === 'current_week') {
            return [
                'period_type' => $periodType,
                'date_from'   => now()->startOfWeek()->toDateString(),
                'date_to'     => now()->endOfWeek()->toDateString(),
            ];
        }

        if ($periodType === 'last_four_weeks') {
            return [
                'period_type' => $periodType,
                'date_from'   => now()->subWeeks(4)->startOfWeek()->toDateString(),
                'date_to'     => now()->endOfWeek()->toDateString(),
            ];
        }

        if ($periodType === 'custom') {
            return [
                'period_type' => $periodType,
                'date_from'   => $filters['date_from'] ?? null,
                'date_to'     => $filters['date_to'] ?? null,
            ];
        }

        // last_week default
        return [
            'period_type' => 'last_week',
            'date_from'   => now()->subWeek()->startOfWeek()->toDateString(),
            'date_to'     => now()->subWeek()->endOfWeek()->toDateString(),
        ];
    }

    public function getGraphicalMetricCards(array $filters): array
    {
        $periodType = $this->normalizePeriod(['period_type' => $filters['period_type'] ?? ($filters['period'] ?? 'last_week')]);

        $categoryId = $filters['kpi_category_id'] ?? null;
        if (! $categoryId) {
            return [];
        }

        // If tehsil selected, treat as district scope (tehsil -> district)
        if (! empty($filters['tehsil_id']) && empty($filters['district_id'])) {
            $t = Tehsil::find($filters['tehsil_id']);
            if ($t) {
                $filters['district_id'] = $t->district_id;
            }
        }

        // Punjab / provincial scope: use provincial_kpi_metrics
        if (empty($filters['district_id'])) {
            $query = ProvincialKpiMetric::query()
                ->where('is_active', true)
                ->where('kpi_category_id', $categoryId)
                ->where('period_type', $periodType)
                ->orderBy('sort_order')
                ->orderBy('id');

            $cards = $query->get()->map(function ($m) {
                return [
                    'title'       => $m->metric_title,
                    'value'       => $m->formatted_value,
                    'raw_value'   => (float) $m->metric_value,
                    'unit'        => $m->unit_label,
                    'source'      => $m->source,
                    'description' => $m->metric_description,
                    'sort_order'  => (int) $m->sort_order,
                ];
            })->values()->all();

            return $cards;
        }

        // District scope: aggregate from district_kpi_metric_values
        $districtId = $filters['district_id'];
        $rows = DistrictKpiMetricValue::query()
            ->where('is_active', true)
            ->where('kpi_category_id', $categoryId)
            ->where('district_id', $districtId)
            ->where('period_type', $periodType)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return $rows->map(function ($r) {
            return [
                'title'       => $r->metric_title,
                'value'       => $r->formatted_value,
                'raw_value'   => (float) $r->metric_value,
                'unit'        => $r->metric_unit ? strtoupper(str_replace('_', ' ', $r->metric_unit)) : 'VALUE',
                'source'      => null,
                'description' => null,
                'sort_order'  => (int) $r->sort_order,
            ];
        })->values()->all();
    }

    public function buildGraphicalChartData(array $filters): array
    {
        $periodType = $this->normalizePeriod(['period_type' => $filters['period_type'] ?? ($filters['period'] ?? 'last_week')]);
        $categoryId = $filters['kpi_category_id'] ?? null;
        if (! $categoryId) {
            return [];
        }

        $cards = collect($this->getGraphicalMetricCards($filters));
        $getCard = function (string $title) use ($cards) {
            return $cards->firstWhere('title', $title);
        };

        // District-wise comparison charts should use district_kpi_metric_values (Punjab scope).
        $districtAgg = DistrictKpiMetricValue::query()
            ->select([
                'district_id',
                DB::raw('SUM(metric_value) as total_value'),
            ])
            ->with('district:id,name')
            ->where('is_active', true)
            ->where('kpi_category_id', $categoryId)
            ->where('period_type', $periodType)
            ->groupBy('district_id')
            ->orderByDesc('total_value')
            ->limit(12)
            ->get();

        $districtLabels = $districtAgg->map(fn ($r) => $r->district?->name ?? 'N/A')->all();
        $districtTotals = $districtAgg->map(fn ($r) => (float) $r->total_value)->all();

        $charts = [
            'coverageChart'           => null,
            'functionalChart'         => null,
            'cleanlinessChart'        => null,
            'filterChangeChart'       => null,
            'districtBarChart'        => [
                'labels'   => $districtLabels,
                'datasets' => [
                    [
                        'label' => 'District Total',
                        'data'  => $districtTotals,
                    ],
                ],
            ],
            'topDistrictsChart'       => null,
            'districtFunctionalChart' => null,
            'topIssuesChart'          => null,
            'meta'                    => [
                'donuts' => [
                    ['key' => 'coverageChart', 'id' => 'coverageChart', 'title' => 'Metric Distribution', 'icon' => 'bi-pie-chart'],
                    ['key' => 'functionalChart', 'id' => 'functionalChart', 'title' => 'Metric Distribution', 'icon' => 'bi-pie-chart'],
                    ['key' => 'cleanlinessChart', 'id' => 'cleanlinessChart', 'title' => 'Top Districts', 'icon' => 'bi-geo-alt'],
                    ['key' => 'filterChangeChart', 'id' => 'filterChangeChart', 'title' => 'Average Score', 'icon' => 'bi-speedometer2'],
                ],
                'large' => [
                    ['key' => 'districtBarChart', 'id' => 'districtBarChart', 'title' => 'District Comparison', 'icon' => 'bi-bar-chart'],
                    ['key' => 'topDistrictsChart', 'id' => 'topDistrictsChart', 'title' => 'Top Districts', 'icon' => 'bi-diagram-3'],
                ],
            ],
        ];

        $category = KpiCategory::find($categoryId);
        $hasWaterPairs =
            $cards->contains(fn ($c) => ($c['title'] ?? '') === 'Inspected') &&
            $cards->contains(fn ($c) => ($c['title'] ?? '') === 'Not Inspected') &&
            $cards->contains(fn ($c) => ($c['title'] ?? '') === 'Functional') &&
            $cards->contains(fn ($c) => ($c['title'] ?? '') === 'Non-Functional');

        $isWater = $hasWaterPairs || ($category && str_contains(strtolower($category->name), 'water filtration'));

        if ($isWater) {
            $charts['meta']['donuts'] = [
                ['key' => 'coverageChart', 'id' => 'coverageChart', 'title' => 'Inspected vs Not Inspected', 'icon' => 'bi-pie-chart'],
                ['key' => 'functionalChart', 'id' => 'functionalChart', 'title' => 'Functional vs Non-Functional', 'icon' => 'bi-check2-circle'],
                ['key' => 'cleanlinessChart', 'id' => 'cleanlinessChart', 'title' => 'Cleaned vs Un-cleaned', 'icon' => 'bi-droplet'],
                ['key' => 'filterChangeChart', 'id' => 'filterChangeChart', 'title' => 'Filter Changed vs Unchanged', 'icon' => 'bi-funnel'],
            ];
            $charts['meta']['large'] = [
                ['key' => 'districtFunctionalChart', 'id' => 'districtFunctionalChart', 'title' => 'District Functional vs Non-Functional', 'icon' => 'bi-bar-chart'],
                ['key' => 'topIssuesChart', 'id' => 'topIssuesChart', 'title' => 'Top Districts (Non-Functional)', 'icon' => 'bi-exclamation-triangle'],
            ];

            $inspected = (float) (($getCard('Inspected')['raw_value'] ?? 0));
            $notInspected = (float) (($getCard('Not Inspected')['raw_value'] ?? 0));
            $functional = (float) (($getCard('Functional')['raw_value'] ?? 0));
            $nonFunctional = (float) (($getCard('Non-Functional')['raw_value'] ?? 0));
            $cleaned = (float) (($getCard('Cleaned')['raw_value'] ?? 0));
            $uncleaned = (float) (($getCard('Un-cleaned')['raw_value'] ?? 0));
            $changed = (float) (($getCard('RO Filter Changed')['raw_value'] ?? 0));
            $unchanged = (float) (($getCard('RO Filter Unchanged')['raw_value'] ?? 0));

            $charts['coverageChart'] = [
                'labels' => ['Inspected', 'Not Inspected'],
                'data'   => [$inspected, $notInspected],
            ];
            $charts['functionalChart'] = [
                'labels' => ['Functional', 'Non-Functional'],
                'data'   => [$functional, $nonFunctional],
            ];
            $charts['cleanlinessChart'] = [
                'labels' => ['Cleaned', 'Un-cleaned'],
                'data'   => [$cleaned, $uncleaned],
            ];
            $charts['filterChangeChart'] = [
                'labels' => ['RO Filter Changed', 'RO Filter Unchanged'],
                'data'   => [$changed, $unchanged],
            ];

            // District-wise functional/non-functional
            $fnAgg = DistrictKpiMetricValue::query()
                ->select([
                    'district_id',
                    DB::raw("SUM(CASE WHEN metric_title = 'Functional' THEN metric_value ELSE 0 END) as functional"),
                    DB::raw("SUM(CASE WHEN metric_title = 'Non-Functional' THEN metric_value ELSE 0 END) as non_functional"),
                ])
                ->with('district:id,name')
                ->where('is_active', true)
                ->where('kpi_category_id', $categoryId)
                ->where('period_type', $periodType)
                ->groupBy('district_id')
                ->orderByDesc('functional')
                ->limit(12)
                ->get();

            $charts['districtFunctionalChart'] = [
                'labels' => $fnAgg->map(fn ($r) => $r->district?->name ?? 'N/A')->all(),
                'datasets' => [
                    ['label' => 'Functional', 'data' => $fnAgg->map(fn ($r) => (float) $r->functional)->all()],
                    ['label' => 'Non-Functional', 'data' => $fnAgg->map(fn ($r) => (float) $r->non_functional)->all()],
                ],
                'options' => [
                    'scales' => [
                        'x' => ['stacked' => true],
                        'y' => ['stacked' => true],
                    ],
                ],
            ];
        } else {
            // Generic donuts: use metric cards and district totals so charts don't look empty.
            $sorted = $cards->sortBy('sort_order')->values();
            $top4 = $sorted->take(4);
            $next4 = $sorted->slice(4, 4);

            if ($top4->count()) {
                $charts['coverageChart'] = [
                    'labels' => $top4->pluck('title')->all(),
                    'data'   => $top4->pluck('raw_value')->map(fn ($v) => (float) $v)->all(),
                ];
            }

            if ($next4->count()) {
                $charts['functionalChart'] = [
                    'labels' => $next4->pluck('title')->all(),
                    'data'   => $next4->pluck('raw_value')->map(fn ($v) => (float) $v)->all(),
                ];
            } elseif ($top4->count()) {
                $charts['functionalChart'] = $charts['coverageChart'];
            }

            // Donut based on top districts totals
            $topDistricts = collect($districtAgg)->take(6);
            if ($topDistricts->count()) {
                $charts['cleanlinessChart'] = [
                    'labels' => $topDistricts->map(fn ($r) => $r->district?->name ?? 'N/A')->all(),
                    'data'   => $topDistricts->map(fn ($r) => (float) $r->total_value)->all(),
                ];
            }

            // Donut based on average metric_score per metric (if any scores exist)
            $scoreAgg = DistrictKpiMetricValue::query()
                ->select([
                    'metric_title',
                    DB::raw('AVG(metric_score) as avg_score'),
                ])
                ->where('is_active', true)
                ->where('kpi_category_id', $categoryId)
                ->where('period_type', $periodType)
                ->whereNotNull('metric_score')
                ->groupBy('metric_title')
                ->orderByDesc('avg_score')
                ->limit(4)
                ->get();

            if ($scoreAgg->count()) {
                $charts['filterChangeChart'] = [
                    'labels' => $scoreAgg->pluck('metric_title')->all(),
                    'data'   => $scoreAgg->pluck('avg_score')->map(fn ($v) => round((float) $v, 2))->all(),
                ];
            } elseif ($top4->count()) {
                $charts['filterChangeChart'] = $charts['coverageChart'];
            }
        }

        // Top districts chart (used for second large chart on non-water categories)
        $top10 = DistrictKpiMetricValue::query()
            ->select([
                'district_id',
                DB::raw('SUM(metric_value) as total_value'),
            ])
            ->with('district:id,name')
            ->where('is_active', true)
            ->where('kpi_category_id', $categoryId)
            ->where('period_type', $periodType)
            ->groupBy('district_id')
            ->orderByDesc('total_value')
            ->limit(10)
            ->get();

        if ($top10->count()) {
            $charts['topDistrictsChart'] = [
                'labels'   => $top10->map(fn ($r) => $r->district?->name ?? 'N/A')->all(),
                'datasets' => [
                    [
                        'label' => 'Top Districts',
                        'data'  => $top10->map(fn ($r) => (float) $r->total_value)->all(),
                    ],
                ],
                'options' => [
                    'indexAxis' => 'y',
                ],
            ];
        }

        // Water (or any category) issue chart: Non-Functional by district if metric exists.
        $nonFunctionalAgg = DistrictKpiMetricValue::query()
            ->select([
                'district_id',
                DB::raw("SUM(CASE WHEN metric_title = 'Non-Functional' THEN metric_value ELSE 0 END) as non_functional"),
            ])
            ->with('district:id,name')
            ->where('is_active', true)
            ->where('kpi_category_id', $categoryId)
            ->where('period_type', $periodType)
            ->groupBy('district_id')
            ->orderByDesc('non_functional')
            ->limit(10)
            ->get();

        if ($nonFunctionalAgg->sum('non_functional') > 0) {
            $charts['topIssuesChart'] = [
                'labels'   => $nonFunctionalAgg->map(fn ($r) => $r->district?->name ?? 'N/A')->all(),
                'datasets' => [
                    [
                        'label' => 'Non-Functional',
                        'data'  => $nonFunctionalAgg->map(fn ($r) => (float) $r->non_functional)->all(),
                    ],
                ],
                'options' => [
                    'indexAxis' => 'y',
                ],
            ];
        }

        return $charts;
    }

    public function getGraphicalInspectionRecords(array $filters)
    {
        $perPage = $this->normalizePerPage($filters['per_page'] ?? 10);

        $dateRange = $this->resolveGraphicalDateRange($filters);
        $filters['date_from'] = $dateRange['date_from'];
        $filters['date_to'] = $dateRange['date_to'];

        $query = $this->baseInspectionQuery();
        $query = $this->applyUserScope($query);

        // Old PPMF graphical report should not filter by workflow status.
        unset($filters['status']);

        return $this->applyInspectionFilters($query, $filters)
            ->latest('inspection_datetime')
            ->paginate($perPage)
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

    /*
    |--------------------------------------------------------------------------
    | Old PPMF-Style KPI Metrics (No Inspection Statuses)
    |--------------------------------------------------------------------------
    */
    private function normalizePerPage($perPage): int
    {
        $perPage = (int) ($perPage ?? 10);
        $allowed = [10, 20, 25, 50];

        return in_array($perPage, $allowed, true) ? $perPage : 10;
    }

    private function normalizePeriod(array $filters): string
    {
        $period = (string) ($filters['period_type'] ?? 'last_week');
        $allowed = ['current_week', 'last_week', 'last_four_weeks', 'custom'];

        return in_array($period, $allowed, true) ? $period : 'last_week';
    }

    private function applyMetricPeriodFilter($query, array $filters)
    {
        $period = $this->normalizePeriod($filters);

        $query->where('period_type', $period);

        if ($period === 'custom') {
            if (! empty($filters['date_from'])) {
                $query->whereDate('date_from', '>=', $filters['date_from']);
            }
            if (! empty($filters['date_to'])) {
                $query->whereDate('date_to', '<=', $filters['date_to']);
            }
        }

        return $query;
    }

    public function getProvincialKpiMetrics(array $filters)
    {
        $perPage = $this->normalizePerPage($filters['per_page'] ?? 10);

        $categoriesQuery = KpiCategory::query()
            ->where('is_active', true)
            ->when(! empty($filters['kpi_category_id']), function ($q) use ($filters) {
                $q->where('id', $filters['kpi_category_id']);
            })
            ->whereHas('provincialMetrics', function ($q) use ($filters) {
                $q->where('is_active', true);
                $this->applyMetricPeriodFilter($q, $filters);

                if (! empty($filters['search'])) {
                    $search = trim((string) $filters['search']);
                    $q->where(function ($qq) use ($search) {
                        $qq->where('metric_title', 'ILIKE', "%{$search}%")
                            ->orWhere('metric_description', 'ILIKE', "%{$search}%")
                            ->orWhere('source', 'ILIKE', "%{$search}%")
                            ->orWhere('metric_unit', 'ILIKE', "%{$search}%");
                    });
                }
            })
            ->when(! empty($filters['search']), function ($q) use ($filters) {
                $search = trim((string) $filters['search']);
                $q->where(function ($qq) use ($search) {
                    $qq->where('name', 'ILIKE', "%{$search}%")
                        ->orWhere('description', 'ILIKE', "%{$search}%")
                        ->orWhereHas('provincialMetrics', function ($mq) use ($search) {
                            $mq->where(function ($mm) use ($search) {
                                $mm->where('metric_title', 'ILIKE', "%{$search}%")
                                    ->orWhere('metric_description', 'ILIKE', "%{$search}%")
                                    ->orWhere('source', 'ILIKE', "%{$search}%");
                            });
                        });
                });
            })
            ->with(['provincialMetrics' => function ($q) use ($filters) {
                $q->where('is_active', true);
                $this->applyMetricPeriodFilter($q, $filters);

                if (! empty($filters['search'])) {
                    $search = trim((string) $filters['search']);
                    $q->where(function ($qq) use ($search) {
                        $qq->where('metric_title', 'ILIKE', "%{$search}%")
                            ->orWhere('metric_description', 'ILIKE', "%{$search}%")
                            ->orWhere('source', 'ILIKE', "%{$search}%");
                    });
                }

                $q->orderBy('sort_order')->orderBy('id');
            }])
            ->orderBy('name');

        return $categoriesQuery
            ->paginate($perPage)
            ->withQueryString();
    }

    public function getProvincialKpiMetricSummary(array $filters): array
    {
        $period = $this->normalizePeriod($filters);

        $metricsQuery = ProvincialKpiMetric::query()
            ->where('is_active', true)
            ->when(! empty($filters['kpi_category_id']), function ($q) use ($filters) {
                $q->where('kpi_category_id', $filters['kpi_category_id']);
            });

        $this->applyMetricPeriodFilter($metricsQuery, $filters);

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $metricsQuery->where(function ($q) use ($search) {
                $q->where('metric_title', 'ILIKE', "%{$search}%")
                    ->orWhere('metric_description', 'ILIKE', "%{$search}%")
                    ->orWhere('source', 'ILIKE', "%{$search}%");
            });
        }

        $totalMetrics = (clone $metricsQuery)->count();
        $totalCategories = (clone $metricsQuery)->distinct('kpi_category_id')->count('kpi_category_id');
        $totalValue = (float) ((clone $metricsQuery)->sum('metric_value') ?? 0);

        return [
            'total_metrics'    => $totalMetrics,
            'total_categories' => $totalCategories,
            'total_value'      => $totalValue,
            'active_period'    => $period,
        ];
    }

    public function getDistrictWiseKpiMetricCards(array $filters)
    {
        $period = $this->normalizePeriod($filters);

        $query = ProvincialKpiMetric::query()
            ->where('is_active', true)
            ->where('period_type', $period)
            ->when(! empty($filters['kpi_category_id']), function ($q) use ($filters) {
                $q->where('kpi_category_id', $filters['kpi_category_id']);
            });

        if ($period === 'custom') {
            if (! empty($filters['date_from'])) {
                $query->whereDate('date_from', '>=', $filters['date_from']);
            }
            if (! empty($filters['date_to'])) {
                $query->whereDate('date_to', '<=', $filters['date_to']);
            }
        }

        return $query->orderBy('sort_order')->orderBy('id')->get();
    }

    public function getDistrictWiseKpiScore(array $filters): array
    {
        $period = $this->normalizePeriod($filters);
        $perPage = $this->normalizePerPage($filters['per_page'] ?? 10);

        $metricCards = $this->getDistrictWiseKpiMetricCards($filters);
        $metricIds = $metricCards->pluck('id')->all();
        $metricTitles = $metricCards->pluck('metric_title')->all();

        $valuesQuery = DistrictKpiMetricValue::query()
            ->where('is_active', true)
            ->where('period_type', $period)
            ->when(! empty($filters['kpi_category_id']), function ($q) use ($filters) {
                $q->where('kpi_category_id', $filters['kpi_category_id']);
            })
            ->when(! empty($metricIds), function ($q) use ($metricIds) {
                $q->whereIn('provincial_kpi_metric_id', $metricIds);
            })
            ->when(! empty($filters['district_id']), function ($q) use ($filters) {
                $q->where('district_id', $filters['district_id']);
            })
            ->when(! empty($filters['search']), function ($q) use ($filters) {
                $search = trim((string) $filters['search']);
                $q->where(function ($qq) use ($search) {
                    $qq->where('metric_title', 'ILIKE', "%{$search}%")
                        ->orWhereHas('district', function ($dq) use ($search) {
                            $dq->where('name', 'ILIKE', "%{$search}%");
                        });
                });
            })
            ->with(['district:id,name']);

        if ($period === 'custom') {
            if (! empty($filters['date_from'])) {
                $valuesQuery->whereDate('date_from', '>=', $filters['date_from']);
            }
            if (! empty($filters['date_to'])) {
                $valuesQuery->whereDate('date_to', '<=', $filters['date_to']);
            }
        }

        $values = $valuesQuery
            ->orderBy('district_id')
            ->orderBy('sort_order')
            ->get();

        $grouped = $values->groupBy('district_id');

        $rows = [];
        foreach ($grouped as $districtId => $items) {
            $districtName = optional($items->first()->district)->name ?? 'N/A';

            $metrics = [];
            foreach ($metricTitles as $title) {
                $metrics[$title] = [
                    'value'       => null,
                    'formatted'   => '-',
                    'score'       => null,
                    'badge_class' => 'secondary',
                    'evidence'    => null,
                ];
            }

            $scores = [];
            $evidenceStates = [];

            foreach ($items as $item) {
                $metrics[$item->metric_title] = [
                    'value'       => $item->metric_value,
                    'formatted'   => $item->formatted_value,
                    'score'       => $item->metric_score !== null ? (float) $item->metric_score : null,
                    'badge_class' => $item->score_badge_class,
                    'evidence'    => $item->evidence,
                ];

                if ($item->metric_score !== null) {
                    $scores[] = (float) $item->metric_score;
                }

                if ($item->evidence) {
                    $evidenceStates[] = $item->evidence;
                }
            }

            $avgScore = count($scores) ? round(array_sum($scores) / count($scores), 2) : null;

            $rows[] = [
                'district_id'        => $districtId,
                'district_name'      => $districtName,
                'metrics'            => $metrics,
                'average_score'      => $avgScore,
                'performance_label'  => $this->scoreToLabel($avgScore),
                'score_badge_class'  => $this->scoreToBadgeClass($avgScore),
                'evidence_states'    => array_values(array_unique($evidenceStates)),
            ];
        }

        usort($rows, function ($a, $b) {
            return strcmp($a['district_name'], $b['district_name']);
        });

        $page = LengthAwarePaginator::resolveCurrentPage();
        $total = count($rows);
        $itemsForPage = array_slice($rows, ($page - 1) * $perPage, $perPage);

        $paginator = new LengthAwarePaginator(
            $itemsForPage,
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return [
            'paginator'    => $paginator,
            'metricTitles' => $metricTitles,
            'metricCards'  => $metricCards,
        ];
    }

    private function scoreToLabel(?float $score): string
    {
        if ($score === null) {
            return 'N/A';
        }
        if ($score >= 90) {
            return 'Excellent';
        }
        if ($score >= 80) {
            return 'Very Good';
        }
        if ($score >= 70) {
            return 'Good';
        }
        if ($score >= 60) {
            return 'Average';
        }
        return 'Low';
    }

    private function scoreToBadgeClass(?float $score): string
    {
        if ($score === null) {
            return 'secondary';
        }
        if ($score >= 90) {
            return 'success';
        }
        if ($score >= 80) {
            return 'info';
        }
        if ($score >= 70) {
            return 'primary';
        }
        if ($score >= 60) {
            return 'warning';
        }
        return 'danger';
    }
}
