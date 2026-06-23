<div class="table-responsive">
    <table class="table-ppmf ppmu-table">
        <thead>
            <tr>
                <th>KPI / Period</th>
                <th>Location</th>
                <th>Submitted By</th>
                <th>Date</th>
                <th>Score</th>
                <th>Status</th>
                <th>Review</th>
            </tr>
        </thead>
        <tbody>
            @forelse($submissions as $item)
                <tr>
                    <td>
                        <strong>{{ $item->kpiCard?->title }}</strong>
                        <small>{{ $item->period_label }}</small>
                    </td>
                    <td>{{ $item->tehsil?->name ?? $item->district?->name ?? 'Punjab' }}</td>
                    <td>{{ $item->user?->name }}</td>
                    <td>{{ $item->submission_date?->format('d M Y') }}</td>
                    <td>{{ number_format((float) $item->score, 1) }}</td>
                    <td><x-status-badge :status="$item->status"/></td>
                    <td>
                        <form method="POST" action="{{ route('submissions.status', $item) }}" class="d-flex gap-2">
                            @csrf
                            @method('PATCH')
                            <select name="status" class="form-select form-select-sm">
                                <option value="approved" @selected($item->status === 'approved')>Approve</option>
                                <option value="pending" @selected($item->status === 'pending')>Pending</option>
                                <option value="rejected" @selected($item->status === 'rejected')>Reject</option>
                            </select>
                            <button class="btn btn-sm btn-success">Save</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center py-5 text-muted">No submissions in your scope.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="ppmu-pagination-bar">
    <div class="ppmu-pagination-meta">
        Showing {{ $submissions->firstItem() ?? 0 }}-{{ $submissions->lastItem() ?? 0 }}
        of {{ $submissions->total() }} submissions
    </div>
    <div class="ppmu-pagination-links">
        {{ $submissions->links() }}
    </div>
</div>
