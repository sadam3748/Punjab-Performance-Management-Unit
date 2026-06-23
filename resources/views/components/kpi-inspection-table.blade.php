@props([
    'kpiCard',
    'inspectionRecords',
    'fallbackImage' => null,
])

@php
    $fallbackImage = $fallbackImage ?? $kpiCard->resolvedImagePath();
@endphp

<div class="ppmu-inspection-table-wrap">
    <table class="table-ppmf ppmu-table inspection-table inspection-table-compact ppmu-inspection-table">
        <thead>
            <tr>
                <th>Inspection</th>
                <th>Location</th>
                <th>Evidence / Facility</th>
                <th>Officer</th>
                <th>Status</th>
                <th class="ppmu-th-action">View</th>
            </tr>
        </thead>
        <tbody>
            @forelse($inspectionRecords as $inspection)
                @php
                    $primary = $inspection->attachments->first();
                    $thumbPath = $primary?->file_path;
                    $thumbUrl = $primary
                        ? (is_file(public_path(ltrim($thumbPath, '/'))) ? asset($thumbPath) : asset($fallbackImage))
                        : null;
                    $photoCount = (int) ($inspection->attachments_count ?? $inspection->attachments->count());
                @endphp
                <tr>
                    <td>
                        <div class="ppmu-inspection-ref">{{ $inspection->reference_no }}</div>
                        <div class="ppmu-inspection-muted">
                            {{ $inspection->inspection_datetime->format('d M Y') }}
                            <span>{{ $inspection->inspection_datetime->format('h:i A') }}</span>
                        </div>
                    </td>
                    <td>
                        <div class="ppmu-inspection-main">{{ $inspection->district?->name ?? 'N/A' }}</div>
                        <div class="ppmu-inspection-muted">{{ $inspection->tehsil?->name ?? 'N/A' }}</div>
                    </td>
                    <td class="ppmu-inspection-evidence-cell">
                        <div class="ppmu-inspection-evidence">
                            @if($thumbUrl)
                                <img src="{{ $thumbUrl }}" alt="Evidence" class="ppmu-inspection-thumb" loading="lazy">
                            @else
                                <span class="ppmu-inspection-thumb ppmu-inspection-thumb-empty"><i class="bi bi-image"></i></span>
                            @endif
                            <div>
                                <div class="ppmu-inspection-main" title="{{ $inspection->entity_name ?? $inspection->inspection_title }}">
                                    {{ \Illuminate\Support\Str::limit($inspection->entity_name ?? $inspection->inspection_title, 34) }}
                                </div>
                                <div class="ppmu-inspection-muted">{{ $photoCount }} {{ $photoCount === 1 ? 'photo' : 'photos' }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="ppmu-inspection-main">{{ $inspection->inspectedBy?->name ?? 'N/A' }}</div>
                        <div class="ppmu-inspection-muted">Updated {{ $inspection->updated_at->format('d M Y') }}</div>
                    </td>
                    <td>
                        <span class="ppmu-inspection-status ppmu-inspection-status-{{ $inspection->status }}">{{ $inspection->statusLabel() }}</span>
                    </td>
                    <td class="text-center">
                        <a href="{{ route('kpi.inspections.show', [$kpiCard, $inspection]) }}" class="ppmu-icon-action ppmu-inspection-view-btn" title="View inspection detail" aria-label="View inspection detail">
                            <i class="bi bi-eye-fill"></i>
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">
                        <div class="ppmu-empty-state py-4">
                            <i class="bi bi-clipboard2-check"></i>
                            <h5>No inspection records</h5>
                            <p>No field inspections found for this KPI in your scope.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($inspectionRecords->hasPages())
    <div class="ppmu-pagination-wrap">
        {{ $inspectionRecords->withQueryString()->links('pagination::bootstrap-5') }}
    </div>
@endif
