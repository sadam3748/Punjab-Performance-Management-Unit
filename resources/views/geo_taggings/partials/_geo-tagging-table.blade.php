<div id="geoTaggingTableContainer" class="geo-table-card">
    <div class="geo-card-header">
        <div>
            <h5 class="geo-section-title mb-1">
                <i class="bi bi-geo-alt"></i>
                Geo Tagging Records
            </h5>

            <p class="geo-section-subtitle mb-0">
                Total records:
                {{ method_exists($geoTaggings, 'total') ? number_format($geoTaggings->total()) : number_format($geoTaggings->count()) }}
            </p>
        </div>

        @if(method_exists($geoTaggings, 'links'))
            <form method="GET" action="{{ route('geo-taggings.list') }}" class="d-flex align-items-center gap-2 ms-auto geo-per-page-form">
                @foreach(request()->except(['per_page','page']) as $key => $value)
                    @if(is_array($value))
                        @foreach($value as $v)
                            <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
                        @endforeach
                    @else
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach
                <span class="geo-muted mb-0">Per page</span>
                <select name="per_page" class="form-select form-select-sm" style="width:96px">
                    @php
                        $pp = (int) ($filters['per_page'] ?? request('per_page', 20));
                    @endphp
                    @foreach([10, 20, 25, 50] as $sz)
                        <option value="{{ $sz }}" @selected($pp===$sz)>{{ $sz }}</option>
                    @endforeach
                </select>
            </form>
        @endif
    </div>

    <div class="geo-table-wrap">
        <table class="table geo-table align-middle mb-0">
                <thead>
                    <tr>
                        <th class="geo-col-sr">Sr.</th>
                        <th>Name / Detail</th>
                        <th>Type</th>
                        <th>District</th>
                        <th>Tehsil</th>
                        <th>Performed By</th>
                        <th>Date & Time</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th class="text-center geo-col-action">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($geoTaggings as $index => $geoTagging)
                        <tr>
                            <td>
                                {{ method_exists($geoTaggings, 'firstItem') ? $geoTaggings->firstItem() + $index : $index + 1 }}
                            </td>

                            <td>
                                <div class="geo-title">
                                    {{ $geoTagging->name ?? 'N/A' }}
                                </div>

                                <div class="geo-address">
                                    {{ $geoTagging->address ?? 'No address available' }}
                                </div>

                                @if(!empty($geoTagging->remarks))
                                    <div class="geo-meta">
                                        {{ \Illuminate\Support\Str::limit($geoTagging->remarks, 70) }}
                                    </div>
                                @endif
                            </td>

                            <td>
                                <span class="type-chip">
                                    {{ $geoTagging->geoTaggingType->name ?? 'N/A' }}
                                </span>
                            </td>

                            <td>{{ $geoTagging->district->name ?? 'N/A' }}</td>

                            <td>{{ $geoTagging->tehsil->name ?? 'N/A' }}</td>

                            <td>
                                <div class="fw-bold">
                                    {{ $geoTagging->performer->username ?? 'N/A' }}
                                </div>

                                <small class="text-muted">
                                    {{ $geoTagging->performer->designation ?? $geoTagging->performer->name ?? '' }}
                                </small>
                            </td>

                            <td>
                                @if ($geoTagging->tagged_at)
                                    {{ \Carbon\Carbon::parse($geoTagging->tagged_at)->format('d M, Y h:i A') }}
                                @else
                                    N/A
                                @endif
                            </td>

                            <td>
                                @if ($geoTagging->latitude && $geoTagging->longitude)
                                    <a
                                        href="https://www.google.com/maps?q={{ $geoTagging->latitude }},{{ $geoTagging->longitude }}"
                                        target="_blank"
                                        class="location-chip text-decoration-none"
                                    >
                                        <i class="bi bi-geo-alt"></i>
                                        {{ number_format($geoTagging->latitude, 5) }},
                                        {{ number_format($geoTagging->longitude, 5) }}
                                    </a>
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>

                            <td>
                                <span class="badge-ppmf
                                    @if(($geoTagging->status ?? '') === 'verified') achieved
                                    @elseif(($geoTagging->status ?? '') === 'rejected') critical
                                    @else pending
                                    @endif
                                ">
                                    {{ ucfirst(str_replace('_', ' ', $geoTagging->status ?? 'Submitted')) }}
                                </span>
                            </td>

                            <td class="text-center">
                                <a
                                    href="{{ route('geo-taggings.show', $geoTagging->id) }}"
                                    class="btn-icon-action"
                                    title="View Detail"
                                >
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-5">
                                <div class="manual-box-ppmf">
                                    <i class="bi bi-geo"></i>
                                    <h5>No Geo Tagging Records Found</h5>
                                    <p>No records are available for the selected filters.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
        </table>
    </div>

    @if (method_exists($geoTaggings, 'lastPage') && $geoTaggings->lastPage() > 1)
        @php
            $geoTaggings->appends(request()->query());

            $currentPage = $geoTaggings->currentPage();
            $lastPage = $geoTaggings->lastPage();
            $startPage = max(1, $currentPage - 2);
            $endPage = min($lastPage, $currentPage + 2);

            if ($currentPage <= 3) {
                $endPage = min($lastPage, 5);
            }

            if ($currentPage >= $lastPage - 2) {
                $startPage = max(1, $lastPage - 4);
            }
        @endphp

        <div class="geo-pagination-bar">
            <div class="geo-pagination-summary-group">
                <div class="geo-pagination-summary">
                    Showing {{ number_format($geoTaggings->firstItem()) }} to {{ number_format($geoTaggings->lastItem()) }}
                    of {{ number_format($geoTaggings->total()) }} records
                </div>
                <div class="geo-pagination-per-page">
                    {{ (int) ($filters['per_page'] ?? request('per_page', 20)) }} per page
                </div>
            </div>

            <nav class="geo-pagination-nav" aria-label="Geo tagging pagination">
                <a
                    href="{{ $geoTaggings->previousPageUrl() ?: 'javascript:void(0)' }}"
                    class="geo-page-link {{ $geoTaggings->onFirstPage() ? 'disabled' : '' }}"
                >
                    <i class="bi bi-chevron-left"></i>
                    Previous
                </a>

                @if ($startPage > 1)
                    <a href="{{ $geoTaggings->url(1) }}" class="geo-page-number">1</a>
                    @if ($startPage > 2)
                        <span class="geo-page-dots">...</span>
                    @endif
                @endif

                @for ($page = $startPage; $page <= $endPage; $page++)
                    <a
                        href="{{ $geoTaggings->url($page) }}"
                        class="geo-page-number {{ $page == $currentPage ? 'active' : '' }}"
                    >
                        {{ $page }}
                    </a>
                @endfor

                @if ($endPage < $lastPage)
                    @if ($endPage < $lastPage - 1)
                        <span class="geo-page-dots">...</span>
                    @endif
                    <a href="{{ $geoTaggings->url($lastPage) }}" class="geo-page-number">{{ $lastPage }}</a>
                @endif

                <a
                    href="{{ $geoTaggings->nextPageUrl() ?: 'javascript:void(0)' }}"
                    class="geo-page-link {{ $geoTaggings->hasMorePages() ? '' : 'disabled' }}"
                >
                    Next
                    <i class="bi bi-chevron-right"></i>
                </a>
            </nav>
        </div>
    @endif
</div>
