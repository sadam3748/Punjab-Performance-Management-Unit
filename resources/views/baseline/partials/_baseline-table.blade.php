<div class="card-ppmf">
    <div class="card-ppmf-header">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 w-100">
            <div>
                <div class="card-ppmf-title">
                    <i class="bi bi-table"></i>
                    District Baseline Records
                </div>
                <p class="card-subtitle mb-0">
                    Total records:
                    {{ method_exists($baselineData, 'total') ? number_format($baselineData->total()) : number_format($baselineData->count()) }}
                </p>
            </div>

            @if(method_exists($baselineData, 'links'))
                <form method="GET" action="{{ route('baseline.index') }}" class="d-flex align-items-center gap-2 ms-auto baseline-per-page-form">
                    <input type="hidden" name="district_id" value="{{ $filters['district_id'] ?? request('district_id', '') }}">
                    <input type="hidden" name="kpi_category_id" value="{{ $filters['kpi_category_id'] ?? request('kpi_category_id', '') }}">
                    <input type="hidden" name="year" value="{{ $filters['year'] ?? request('year', '') }}">
                    <input type="hidden" name="search" value="{{ $filters['search'] ?? request('search', '') }}">

                    <label class="form-label mb-0 text-nowrap" for="baselinePerPageSelect">Per Page</label>
                    <select
                        id="baselinePerPageSelect"
                        name="per_page"
                        class="form-select"
                        style="width: 110px;"
                    >
                        @php
                            $currentPerPage = (int) ($filters['per_page'] ?? request('per_page', 10));
                        @endphp
                        @foreach([10, 20, 25, 50] as $size)
                            <option value="{{ $size }}" {{ $currentPerPage === (int) $size ? 'selected' : '' }}>
                                {{ $size }}
                            </option>
                        @endforeach
                    </select>
                </form>
            @endif
        </div>
    </div>

    <div class="card-ppmf-body p-0">
        <div class="table-responsive">
            <table class="table-ppmf">
                <thead>
                    <tr>
                        <th>Sr.</th>
                        <th>District</th>
                        <th>KPI Category</th>
                        <th>Year</th>
                        <th>Baseline Summary</th>
                        <th>Created By</th>
                        <th>Updated By</th>
                        <th>Updated At</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($baselineData as $index => $baseline)
                        @php
                            $data = $baseline->baseline_data ?? [];
                        @endphp

                        <tr>
                            <td>
                                {{ method_exists($baselineData, 'firstItem') ? $baselineData->firstItem() + $index : $index + 1 }}
                            </td>

                            <td>
                                <strong>{{ $baseline->district->name ?? 'N/A' }}</strong>
                            </td>

                            <td>
                                <span class="badge-ppmf info">
                                    {{ $baseline->kpiCategory->name ?? 'N/A' }}
                                </span>
                            </td>

                            <td>
                                <strong>{{ $baseline->year ?? 'N/A' }}</strong>
                            </td>

                            <td>
                                @if(is_array($data) && count($data) > 0)
                                    <div class="baseline-preview">
                                        @foreach(array_slice($data, 0, 3) as $key => $value)
                                            <div>
                                                <span>{{ ucwords(str_replace('_', ' ', $key)) }}:</span>
                                                <strong>
                                                    @if(is_array($value))
                                                        {{ implode(', ', array_slice($value, 0, 2)) }}
                                                    @elseif(is_bool($value))
                                                        {{ $value ? 'Yes' : 'No' }}
                                                    @else
                                                        {{ \Illuminate\Support\Str::limit((string) $value, 35) }}
                                                    @endif
                                                </strong>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-muted">No JSON data</span>
                                @endif
                            </td>

                            <td>{{ $baseline->creator->username ?? $baseline->creator->name ?? 'N/A' }}</td>

                            <td>{{ $baseline->updater->username ?? $baseline->updater->name ?? 'N/A' }}</td>

                            <td>
                                {{ $baseline->updated_at ? $baseline->updated_at->format('d M, Y h:i A') : 'N/A' }}
                            </td>

                            <td class="text-center">
                                <a href="{{ route('baseline.show', $baseline->id) }}" class="btn-icon-action" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>

                                <a href="{{ route('baseline.edit', $baseline->id) }}" class="btn-icon-action" title="Edit">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5">
                                <div class="manual-box-ppmf">
                                    <i class="bi bi-database-x"></i>
                                    <h5>No Baseline Data Found</h5>
                                    <p>No district baseline data is available for selected filters.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>
    </div>

    @if(method_exists($baselineData, 'links'))
        <div class="card-ppmf-body border-top">
            {{ $baselineData->appends(request()->query())->links() }}
        </div>
    @endif
</div>

