<div class="card-ppmf">
    <div class="card-ppmf-header">
        <div>
            <div class="card-ppmf-title">
                <i class="bi bi-box-seam"></i>
                Baseline Asset Records
            </div>
            <p class="card-subtitle mb-0">
                Total records:
                {{ method_exists($assets, 'total') ? number_format($assets->total()) : number_format($assets->count()) }}
            </p>
        </div>
    </div>

    <div class="card-ppmf-body p-0">
        <div class="table-responsive">
            <table class="table-ppmf">
                <thead>
                    <tr>
                        <th>Sr.</th>
                        <th>Asset Detail</th>
                        <th>KPI Category</th>
                        <th>Division</th>
                        <th>District</th>
                        <th>Tehsil</th>
                        <th>Status</th>
                        <th>Baseline Date</th>
                        <th>Location</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($assets as $index => $asset)
                        <tr>
                            <td>
                                {{ method_exists($assets, 'firstItem') ? $assets->firstItem() + $index : $index + 1 }}
                            </td>

                            <td>
                                <div class="fw-bold">{{ $asset->name ?? 'N/A' }}</div>
                                <small class="text-muted">{{ $asset->address ?? 'No address' }}</small>
                            </td>

                            <td>
                                <span class="badge-ppmf info">
                                    {{ $asset->kpiCategory->name ?? 'N/A' }}
                                </span>
                            </td>

                            <td>{{ $asset->division->name ?? 'N/A' }}</td>
                            <td>{{ $asset->district->name ?? 'N/A' }}</td>
                            <td>{{ $asset->tehsil->name ?? 'N/A' }}</td>

                            <td>
                                <span class="badge-ppmf
                                    @if(in_array($asset->status, ['functional', 'active'])) achieved
                                    @elseif(in_array($asset->status, ['non_functional', 'inactive'])) critical
                                    @else pending
                                    @endif
                                ">
                                    {{ ucfirst(str_replace('_', ' ', $asset->status ?? 'N/A')) }}
                                </span>
                            </td>

                            <td>
                                {{ $asset->baseline_date ? \Carbon\Carbon::parse($asset->baseline_date)->format('d M, Y') : 'N/A' }}
                            </td>

                            <td>
                                @if($asset->latitude && $asset->longitude)
                                    <a href="https://www.google.com/maps?q={{ $asset->latitude }},{{ $asset->longitude }}"
                                       target="_blank"
                                       class="text-decoration-none">
                                        <i class="bi bi-geo-alt"></i>
                                        View Map
                                    </a>
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>

                            <td class="text-center">
                                <a href="{{ route('baseline.assets.show', $asset->id) }}" class="btn-icon-action">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-5">
                                <div class="manual-box-ppmf">
                                    <i class="bi bi-box"></i>
                                    <h5>No Baseline Assets Found</h5>
                                    <p>No asset-level baseline records are available for selected filters.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>
    </div>

    @if(method_exists($assets, 'links'))
        <div class="card-ppmf-body border-top">
            {{ $assets->links() }}
        </div>
    @endif
</div>

