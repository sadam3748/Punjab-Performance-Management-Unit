@php
    /** @var \Illuminate\Pagination\LengthAwarePaginator|\Illuminate\Support\Collection $inspections */
    $svc = app(\App\Services\InspectionService::class);
@endphp

<div class="inspection-table-card">
    <div class="inspection-card-header">
        <div>
            <h5 class="inspection-section-title mb-1">
                <i class="bi bi-list-check"></i>
                Inspection Records
            </h5>
            <p class="inspection-section-subtitle mb-0">
                Latest inspections based on selected filters.
            </p>
        </div>

        <form method="GET" action="{{ route('inspections.list') }}" class="d-flex align-items-center gap-2 inspection-per-page-form">
            @foreach(request()->except(['per_page','page']) as $key => $value)
                @if(is_array($value))
                    @foreach($value as $v)
                        <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
                    @endforeach
                @else
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endif
            @endforeach
            <span class="sc-muted mb-0" style="font-weight:800">Per page</span>
            <select name="per_page" class="form-select form-select-sm" style="width:96px">
                @foreach([10, 20, 25, 50] as $perPageOption)
                    <option value="{{ $perPageOption }}"
                        {{ (int) (request('per_page', 10)) === (int) $perPageOption ? 'selected' : '' }}>
                        {{ $perPageOption }}
                    </option>
                @endforeach
            </select>
        </form>
    </div>

    <div class="inspection-table-wrap" id="inspectionTableContainer">
        <table class="table inspection-table align-middle mb-0">
            <thead>
                <tr>
                    <th class="inspection-col-sr">Sr. No.</th>
                    <th>Inspection Type</th>
                    <th>Primary Detail</th>
                    <th>Secondary Detail / Address</th>
                    <th>Tehsil</th>
                    <th>District</th>
                    <th>Performed By</th>
                    <th>Date & Time</th>
                    <th>Evidence / Actions</th>
                    <th class="text-center inspection-col-action">Action</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($inspections as $index => $inspection)
                    @php
                        $fields = $svc->getInspectionDisplayFields($inspection);
                        $counts = $svc->getEvidenceActionsCounts($inspection);
                    @endphp
                    <tr>
                        <td class="fw-bold text-muted">
                            {{ method_exists($inspections, 'firstItem') ? $inspections->firstItem() + $index : $index + 1 }}
                        </td>

                        <td>
                            <div class="inspection-type-cell">
                                <span class="inspection-type-icon">
                                    <i class="bi bi-clipboard-check"></i>
                                </span>
                                <span>{{ $inspection->kpiCategory->name ?? 'N/A' }}</span>
                            </div>
                        </td>

                        <td>
                            <div class="inspection-title">{{ $fields['primary_value'] }}</div>
                            <div class="inspection-meta">{{ $fields['primary_label'] }}</div>
                        </td>

                        <td>
                            <div class="inspection-address">{{ $fields['secondary_value'] }}</div>
                            <div class="inspection-meta">{{ $fields['secondary_label'] }}</div>
                        </td>

                        <td>
                            <span class="area-chip tehsil-chip">
                                {{ $inspection->tehsil->name ?? 'N/A' }}
                            </span>
                        </td>

                        <td>
                            <span class="area-chip district-chip">
                                {{ $inspection->district->name ?? 'N/A' }}
                            </span>
                        </td>

                        <td>
                            <div class="inspection-user">
                                <div class="inspection-user-avatar">
                                    {{ strtoupper(substr($inspection->performer->username ?? $inspection->performer->name ?? 'U', 0, 1)) }}
                                </div>
                                <div>
                                    <div class="inspection-user-name">
                                        {{ $inspection->performer->username ?? 'N/A' }}
                                    </div>
                                    <small class="inspection-user-role">
                                        {{ $inspection->performer->designation ?? $inspection->performer->name ?? '' }}
                                    </small>
                                </div>
                            </div>
                        </td>

                        <td>
                            @if ($inspection->inspection_datetime)
                                <div class="inspection-date">
                                    {{ \Carbon\Carbon::parse($inspection->inspection_datetime)->format('d M, Y') }}
                                </div>
                                <div class="inspection-time">
                                    {{ \Carbon\Carbon::parse($inspection->inspection_datetime)->format('h:i A') }}
                                </div>
                            @else
                                <span class="text-muted">N/A</span>
                            @endif
                        </td>

                        <td class="text-nowrap">
                            <span class="inspection-meta">Evidence: {{ $counts['evidence_count'] }}</span><br>
                            <span class="inspection-meta">Actions: {{ $counts['actions_count'] }}</span>
                        </td>

                        <td class="text-center">
                            <a href="{{ route('inspections.show', $inspection->id) }}"
                               class="inspection-view-btn"
                               title="View Detail">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center py-5">
                            <div class="inspection-empty-state">
                                <i class="bi bi-inbox"></i>
                                <h6>No inspection records found</h6>
                                <p>Try changing filters or add inspection dummy data.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if (method_exists($inspections, 'lastPage') && $inspections->lastPage() > 1)
        @php
            $inspections->appends(request()->query());

            $currentPage = $inspections->currentPage();
            $lastPage = $inspections->lastPage();
            $startPage = max(1, $currentPage - 2);
            $endPage = min($lastPage, $currentPage + 2);

            if ($currentPage <= 3) {
                $endPage = min($lastPage, 5);
            }

            if ($currentPage >= $lastPage - 2) {
                $startPage = max(1, $lastPage - 4);
            }
        @endphp

        <div class="inspection-pagination-bar">
            <div class="inspection-pagination-summary-group">
                <div class="inspection-pagination-summary">
                    Showing {{ number_format($inspections->firstItem()) }} to {{ number_format($inspections->lastItem()) }}
                    of {{ number_format($inspections->total()) }} records
                </div>
                <div class="inspection-pagination-per-page">
                    {{ (int) (request('per_page', 10)) }} per page
                </div>
            </div>

            <nav class="inspection-pagination-nav" aria-label="Inspection pagination">
                <a
                    href="{{ $inspections->previousPageUrl() ?: 'javascript:void(0)' }}"
                    class="inspection-page-link {{ $inspections->onFirstPage() ? 'disabled' : '' }}"
                >
                    <i class="bi bi-chevron-left"></i>
                    Previous
                </a>

                @if ($startPage > 1)
                    <a href="{{ $inspections->url(1) }}" class="inspection-page-number">1</a>
                    @if ($startPage > 2)
                        <span class="inspection-page-dots">...</span>
                    @endif
                @endif

                @for ($page = $startPage; $page <= $endPage; $page++)
                    <a
                        href="{{ $inspections->url($page) }}"
                        class="inspection-page-number {{ $page == $currentPage ? 'active' : '' }}"
                    >
                        {{ $page }}
                    </a>
                @endfor

                @if ($endPage < $lastPage)
                    @if ($endPage < $lastPage - 1)
                        <span class="inspection-page-dots">...</span>
                    @endif
                    <a href="{{ $inspections->url($lastPage) }}" class="inspection-page-number">{{ $lastPage }}</a>
                @endif

                <a
                    href="{{ $inspections->nextPageUrl() ?: 'javascript:void(0)' }}"
                    class="inspection-page-link {{ $inspections->hasMorePages() ? '' : 'disabled' }}"
                >
                    Next
                    <i class="bi bi-chevron-right"></i>
                </a>
            </nav>
        </div>
    @endif
</div>

