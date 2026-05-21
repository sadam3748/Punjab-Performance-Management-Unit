@php
    $categoryId = (int) ($filters['kpi_category_id'] ?? 0);
    $selectedCategory = ($kpiCategories ?? collect())->firstWhere('id', $categoryId);
    $periodLabel = $chartData['meta']['period_label'] ?? 'Weekly';
@endphp

<div id="graphicalReportContent" data-chart='@json($chartData ?? [])'>
    <div class="gr-card mb-4">
        <div class="gr-card-header">
            <div>
                <div class="gr-card-title"><i class="bi bi-grid-3x3-gap"></i> KPI Metric Cards</div>
                <div class="gr-card-subtitle">
                    {{ $selectedCategory->name ?? 'KPI Category' }} — {{ $periodLabel }}
                </div>
            </div>
        </div>
        <div class="gr-card-body">
            @if (count($summaryCards ?? []))
                <div class="gr-metric-grid">
                    @foreach($summaryCards as $card)
                        <div class="gr-metric-card">
                            <div class="gr-metric-value">{{ $card['value'] ?? '-' }}</div>
                            <div class="gr-metric-title">{{ $card['title'] ?? 'Metric' }}</div>
                            <div class="gr-metric-meta">
                                <span class="gr-pill"><i class="bi bi-tag"></i> {{ $card['unit'] ?? 'VALUE' }}</span>
                                @if(!empty($card['source']))
                                    <span class="gr-pill"><i class="bi bi-person-badge"></i> {{ $card['source'] }}</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="chart-empty">
                    <div>
                        <div class="fw-bold mb-1">No KPI metric cards available</div>
                        <div>Select a KPI category/week with seeded metrics.</div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="gr-card mb-4">
        <div class="gr-card-header">
            <div>
                <div class="gr-card-title"><i class="bi bi-graph-up"></i> Charts</div>
                <div class="gr-card-subtitle">Donut and district-wise comparison charts for selected KPI category.</div>
            </div>
        </div>
        <div class="gr-card-body">
            @php
                $donuts = $chartData['meta']['donuts'] ?? [];
                $largeCharts = $chartData['meta']['large'] ?? [];
            @endphp

            @if(!empty($donuts))
                <div class="chart-grid">
                    @foreach($donuts as $c)
                        <div class="chart-card">
                            <div class="chart-card-header">
                                <div class="chart-card-title"><i class="bi {{ $c['icon'] ?? 'bi-pie-chart' }}"></i> {{ $c['title'] ?? 'Chart' }}</div>
                                <div class="chart-card-subtitle">{{ $periodLabel }}</div>
                            </div>
                            <div class="chart-card-body">
                                @if(!empty(($chartData[$c['key']] ?? null)))
                                    <canvas id="{{ $c['id'] }}"></canvas>
                                @else
                                    <div class="chart-empty"><div>No data</div></div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            @if(!empty($largeCharts))
                <div class="chart-large-grid mt-3">
                    @foreach($largeCharts as $c)
                        <div class="chart-card">
                            <div class="chart-card-header">
                                <div class="chart-card-title"><i class="bi {{ $c['icon'] ?? 'bi-bar-chart' }}"></i> {{ $c['title'] ?? 'Chart' }}</div>
                                <div class="chart-card-subtitle">{{ $periodLabel }}</div>
                            </div>
                            <div class="chart-card-body">
                                @if(!empty(($chartData[$c['key']] ?? null)))
                                    <canvas id="{{ $c['id'] }}"></canvas>
                                @else
                                    <div class="chart-empty"><div>No data</div></div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="gr-card">
        <div class="gr-card-header">
            <div>
                <div class="gr-card-title"><i class="bi bi-table"></i> Inspection Records</div>
                <div class="gr-card-subtitle">Latest inspections for the selected KPI category and week.</div>
            </div>
        </div>
        <div class="gr-card-body p-0">
            <div class="table-responsive">
                <table class="table-ppmf align-middle">
                    <thead>
                        <tr>
                            <th style="width:70px">Sr. No.</th>
                            <th>Inspection Type</th>
                            <th>Primary Detail</th>
                            <th>Secondary Detail / Address</th>
                            <th>Tehsil</th>
                            <th>District</th>
                            <th>Performed By</th>
                            <th>Date & Time</th>
                            <th>Evidence / Actions</th>
                            <th class="text-center" style="width:90px">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($tableData ?? []) as $index => $row)
                            @php
                                $svc = app(\App\Services\InspectionService::class);
                                $fields = $svc->getInspectionDisplayFields($row);
                                $counts = $svc->getEvidenceActionsCounts($row);
                                $detailUrl = Route::has('inspections.show') ? route('inspections.show', $row->id) : '#';
                            @endphp
                            <tr>
                                <td>{{ method_exists($tableData, 'firstItem') ? $tableData->firstItem() + $index : $index + 1 }}</td>
                                <td class="fw-bold">{{ $row->kpiCategory->name ?? 'N/A' }}</td>
                                <td>{{ $fields['primary_value'] }}</td>
                                <td>{{ $fields['secondary_value'] }}</td>
                                <td>{{ $row->tehsil->name ?? 'N/A' }}</td>
                                <td>{{ $row->district->name ?? 'N/A' }}</td>
                                <td>{{ $row->performer->username ?? $row->performer->name ?? 'N/A' }}</td>
                                <td>
                                    @if ($row->inspection_datetime)
                                        {{ \Carbon\Carbon::parse($row->inspection_datetime)->format('d M, Y h:i A') }}
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td class="text-nowrap">
                                    Evidence: {{ $counts['evidence_count'] }} · Actions: {{ $counts['actions_count'] }}
                                </td>
                                <td class="text-center">
                                    <a href="{{ $detailUrl }}" class="btn-icon-action" title="View Detail"><i class="bi bi-eye"></i></a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="text-center py-5 text-muted">No inspection records found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if (method_exists($tableData, 'links'))
                <div class="card-ppmf-body border-top">
                    {{ $tableData->appends(request()->query())->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
