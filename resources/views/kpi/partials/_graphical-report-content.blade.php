@php
    $activePeriod = $filters['period_type'] ?? 'last_week';
    $selectedCategory = collect($kpiCategories ?? [])->firstWhere('id', (int) ($filters['kpi_category_id'] ?? 0));
    $periodLabel = $periodOptions[$activePeriod] ?? ucwords(str_replace('_', ' ', $activePeriod));
    $metricIcons = ['bi-stack', 'bi-clipboard-check', 'bi-check-circle', 'bi-exclamation-triangle', 'bi-check2-circle', 'bi-graph-up', 'bi-geo-alt'];
    $metricAccents = [
        ['color' => '#18b979', 'soft' => '#e0f7ef'],
        ['color' => '#2f80ed', 'soft' => '#eff6ff'],
        ['color' => '#0d9488', 'soft' => '#e0f4f3'],
        ['color' => '#dc5a5a', 'soft' => '#fff1f2'],
        ['color' => '#f59e0b', 'soft' => '#fffbeb'],
        ['color' => '#7c3aed', 'soft' => '#f3e8ff'],
    ];

    $donuts = $chartData['meta']['donuts'] ?? [
        ['key' => 'coverageChart', 'id' => 'coverageChart', 'title' => 'Metric Distribution', 'subtitle' => 'Selected KPI value split', 'icon' => 'bi-pie-chart'],
        ['key' => 'functionalChart', 'id' => 'functionalChart', 'title' => 'Compliance Status', 'subtitle' => 'Compliant vs non-compliant status', 'icon' => 'bi-check2-circle'],
    ];

    $largeCharts = $chartData['meta']['large'] ?? [
        ['key' => 'districtBarChart', 'id' => 'districtBarChart', 'title' => 'District Comparison', 'subtitle' => 'District-wise KPI comparison', 'icon' => 'bi-bar-chart'],
        ['key' => 'topDistrictsChart', 'id' => 'topDistrictsChart', 'title' => 'Top Performing Districts', 'subtitle' => 'Highest ranked districts by selected metric', 'icon' => 'bi-trophy'],
        ['key' => 'trendLineChart', 'id' => 'trendLineChart', 'title' => 'KPI Trend Line', 'subtitle' => 'Period-wise trend where available', 'icon' => 'bi-graph-up-arrow', 'type' => 'line'],
    ];

    $metricCount = count($summaryCards ?? []);
    $metricGridClass = $metricCount > 9 ? 'metric-count-many' : 'metric-count-' . max(1, $metricCount);
    $donutCount = count($donuts ?? []);
    $donutGridClass = $donutCount === 1 ? 'donut-count-1' : ($donutCount === 2 ? 'donut-count-2' : 'donut-count-many');
@endphp

<div id="graphicalReportContent" data-chart='@json($chartData ?? [])'>
    <div class="gr-section-label">
        <i class="bi bi-grid-3x3-gap"></i>
        KPI Metric Cards
        <span>· {{ $selectedCategory->name ?? 'Selected KPI Category' }} — {{ $periodLabel }}</span>
    </div>

    @if (count($summaryCards ?? []))
        <div class="gr-metric-grid {{ $metricGridClass }}">
            @foreach($summaryCards as $index => $card)
                @php
                    $accent = $metricAccents[$index % count($metricAccents)];
                    $icon = $card['icon'] ?? $metricIcons[$index % count($metricIcons)];
                @endphp
                <div class="gr-metric-card" style="--accent: {{ $accent['color'] }}; --accent-soft: {{ $accent['soft'] }};">
                    <div class="gr-metric-icon">
                        <i class="bi {{ $icon }}"></i>
                    </div>
                    <h3 class="gr-metric-value">{{ $card['value'] ?? '-' }}</h3>
                    <div class="gr-metric-title">{{ $card['title'] ?? 'Metric' }}</div>
                    <div class="gr-metric-meta">
                        <span class="gr-mini-pill"><i class="bi bi-tag"></i> {{ strtoupper($card['unit'] ?? 'VALUE') }}</span>
                        @if(!empty($card['source']))
                            <span class="gr-mini-pill"><i class="bi bi-person-badge"></i> {{ $card['source'] }}</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="gr-empty mb-3">
            <div>
                <div class="fw-bold mb-1">No KPI metric cards available</div>
                <div>Select a KPI category and period with available metric data.</div>
            </div>
        </div>
    @endif

    <div class="gr-section-label">
        <i class="bi bi-pie-chart"></i>
        Donut Charts
        <span>· Compact distribution view for selected category</span>
    </div>

    <div class="gr-donut-row {{ $donutGridClass }}">
        @foreach($donuts as $chart)
            <div class="gr-chart-card">
                <div class="gr-chart-head">
                    <div class="gr-chart-title"><i class="bi {{ $chart['icon'] ?? 'bi-pie-chart' }}"></i> {{ $chart['title'] ?? 'Metric Chart' }}</div>
                    <div class="gr-chart-sub">{{ $chart['subtitle'] ?? 'Metric breakdown' }}</div>
                </div>
                <div class="gr-chart-body">
                    @if(!empty($chartData[$chart['key']] ?? null))
                        <canvas id="{{ $chart['id'] }}"></canvas>
                    @else
                        <div class="gr-empty">No chart data available.</div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <div class="gr-section-label">
        <i class="bi bi-bar-chart"></i>
        Graphical Comparison
        <span>· District ranking, bar graph, and trend/line chart where available</span>
    </div>

    @if(count($largeCharts))
        <div class="gr-graph-row">
            @foreach($largeCharts as $chart)
                <div class="gr-chart-card is-graph">
                    <div class="gr-chart-head">
                        <div class="gr-chart-title"><i class="bi {{ $chart['icon'] ?? 'bi-bar-chart' }}"></i> {{ $chart['title'] ?? 'District Chart' }}</div>
                        <div class="gr-chart-sub">{{ $chart['subtitle'] ?? 'Selected KPI district comparison' }}</div>
                    </div>
                    <div class="gr-chart-body">
                        @if(!empty($chartData[$chart['key']] ?? null))
                            <canvas id="{{ $chart['id'] }}"></canvas>
                        @else
                            <div class="gr-empty">No comparison data available.</div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <div class="gr-section-label">
        <i class="bi bi-list-details"></i>
        Inspection Records
        <span>· Latest records for selected KPI category</span>
    </div>

    <div class="gr-table-card">
        <div class="gr-table-top">
            <div class="gr-table-title">
                <i class="bi bi-table"></i>
                KPI Detail Records
                <span class="gr-live-badge">Live</span>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <span class="gr-mini-pill"><i class="bi bi-funnel"></i> {{ $selectedCategory->name ?? 'All Categories' }}</span>
                <span class="gr-mini-pill"><i class="bi bi-calendar3"></i> {{ $periodLabel }}</span>
            </div>
        </div>

        <div class="gr-table-wrap">
            <table class="gr-table">
                <colgroup>
                    <col style="width:5%;">
                    <col style="width:15%;">
                    <col style="width:19%;">
                    <col style="width:18%;">
                    <col style="width:11%;">
                    <col style="width:16%;">
                    <col style="width:11%;">
                    <col style="width:5%;">
                </colgroup>
                <thead>
                    <tr>
                        <th style="width:58px;">Sr.</th>
                        <th>KPI Type</th>
                        <th>Main Detail</th>
                        <th>Location</th>
                        <th>District</th>
                        <th>Performed By</th>
                        <th>Date &amp; Time</th>
                        <th class="text-center" style="width:80px;">Detail</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tableData as $index => $row)
                        @php
                            $detailUrl = Route::has('inspections.show') ? route('inspections.show', $row->id) : '#';
                            $serialNo = method_exists($tableData, 'firstItem') ? (($tableData->firstItem() ?? 1) + $index) : ($index + 1);
                            $performerName = $row->performer->username ?? $row->performer->name ?? 'N/A';
                            $performerInitial = strtoupper(substr($performerName ?: 'U', 0, 1));
                        @endphp
                        <tr>
                            <td>
                                <span class="text-muted fw-bold">{{ str_pad($serialNo, 2, '0', STR_PAD_LEFT) }}</span>
                            </td>
                            <td>
                                <span class="gr-type-badge">
                                    <i class="bi bi-clipboard-data"></i>
                                    {{ $row->kpiCategory->name ?? 'N/A' }}
                                </span>
                            </td>
                            <td>
                                <div class="gr-row-title">{{ $row->main_title ?? 'N/A' }}</div>
                                <div class="gr-row-sub">{{ $row->tehsil->name ?? 'N/A' }}</div>
                            </td>
                            <td>
                                <div class="gr-row-sub">{{ $row->main_address ?? 'N/A' }}</div>
                            </td>
                            <td>
                                <span class="gr-district-badge">{{ $row->district->name ?? 'N/A' }}</span>
                            </td>
                            <td>
                                <div class="gr-user">
                                    <div class="gr-user-avatar">{{ $performerInitial }}</div>
                                    <div>
                                        <div class="gr-user-name">{{ $performerName }}</div>
                                        <div class="gr-user-role">{{ $row->performer->designation ?? 'User' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($row->inspection_datetime)
                                    <div class="gr-date">{{ \Carbon\Carbon::parse($row->inspection_datetime)->format('d M, Y') }}</div>
                                    <div class="gr-time">{{ \Carbon\Carbon::parse($row->inspection_datetime)->format('h:i A') }}</div>
                                @else
                                    <span class="text-muted fw-bold">N/A</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ $detailUrl }}" class="gr-view-btn" title="View Detail">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="gr-empty my-3">
                                    <div>
                                        <div class="fw-bold mb-1">No KPI detail records</div>
                                        <div>No inspection records found for selected filters.</div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if (method_exists($tableData, 'lastPage') && $tableData->lastPage() > 1)
            @php
                $tableData->appends(request()->query());

                $currentPage = $tableData->currentPage();
                $lastPage = $tableData->lastPage();
                $startPage = max(1, $currentPage - 2);
                $endPage = min($lastPage, $currentPage + 2);

                if ($currentPage <= 3) {
                    $endPage = min($lastPage, 5);
                }

                if ($currentPage >= $lastPage - 2) {
                    $startPage = max(1, $lastPage - 4);
                }

                $currentPerPage = (int) ($filters['per_page'] ?? request('per_page', 10));
            @endphp

            <div class="inspection-pagination-bar">
                <div class="inspection-pagination-summary-group">
                    <div class="inspection-pagination-summary">
                        Showing {{ number_format($tableData->firstItem() ?? 0) }} to {{ number_format($tableData->lastItem() ?? 0) }}
                        of {{ number_format($tableData->total() ?? 0) }} records
                    </div>
                    <div class="inspection-pagination-per-page">
                        {{ $currentPerPage }} per page
                    </div>
                </div>

                <nav class="inspection-pagination-nav" aria-label="Graphical report pagination">
                    <a
                        href="{{ $tableData->previousPageUrl() ?: 'javascript:void(0)' }}"
                        class="inspection-page-link {{ $tableData->onFirstPage() ? 'disabled' : '' }}"
                    >
                        <i class="bi bi-chevron-left"></i>
                        Previous
                    </a>

                    @if ($startPage > 1)
                        <a href="{{ $tableData->url(1) }}" class="inspection-page-number">1</a>
                        @if ($startPage > 2)
                            <span class="inspection-page-dots">...</span>
                        @endif
                    @endif

                    @for ($page = $startPage; $page <= $endPage; $page++)
                        <a
                            href="{{ $tableData->url($page) }}"
                            class="inspection-page-number {{ $page == $currentPage ? 'active' : '' }}"
                        >
                            {{ $page }}
                        </a>
                    @endfor

                    @if ($endPage < $lastPage)
                        @if ($endPage < $lastPage - 1)
                            <span class="inspection-page-dots">...</span>
                        @endif
                        <a href="{{ $tableData->url($lastPage) }}" class="inspection-page-number">{{ $lastPage }}</a>
                    @endif

                    <a
                        href="{{ $tableData->nextPageUrl() ?: 'javascript:void(0)' }}"
                        class="inspection-page-link {{ $tableData->hasMorePages() ? '' : 'disabled' }}"
                    >
                        Next
                        <i class="bi bi-chevron-right"></i>
                    </a>
                </nav>
            </div>
        @endif
    </div>
</div>
