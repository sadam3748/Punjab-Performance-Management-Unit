<div class="card-ppmf">
    <div class="card-ppmf-header">
        <div>
            <div class="card-ppmf-title">
                <i class="bi bi-geo-alt"></i>
                Geo Tagging Records
            </div>

            <p class="card-subtitle mb-0">
                Total records:
                {{ method_exists($geoTaggings, 'total') ? number_format($geoTaggings->total()) : number_format($geoTaggings->count()) }}
            </p>
        </div>

        @if(method_exists($geoTaggings, 'links'))
            <form method="GET" action="{{ route('geo-taggings.list') }}" class="d-flex align-items-center gap-2 ms-auto geo-per-page-form">
                @foreach(request()->except(['per_page','page']) as $key => $value)
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endforeach
                <label class="form-label mb-0 text-nowrap">Per page</label>
                <select name="per_page" class="form-select" style="width:110px;">
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

    <div class="card-ppmf-body p-0">
        <div class="table-responsive">
            <table class="table-ppmf">
                <thead>
                    <tr>
                        <th style="width: 70px;">Sr.</th>
                        <th>Name / Detail</th>
                        <th>Type</th>
                        <th>District</th>
                        <th>Tehsil</th>
                        <th>Performed By</th>
                        <th>Date & Time</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th class="text-center" style="width: 90px;">Action</th>
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
    </div>

    @if (method_exists($geoTaggings, 'links'))
        <div class="card-ppmf-body border-top">
            <div class="pagination-wrapper">
                {{ $geoTaggings->links() }}
            </div>
        </div>
    @endif
</div>

