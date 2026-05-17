@extends('layouts.app')

@section('title', 'Baseline Assets')

@section('content')

<div class="page-title-bar">
    <div>
        <h1 class="page-title">Baseline Assets</h1>
        <p class="page-subtitle">
            Asset-level baseline records such as filtration plants, schools, facilities and other KPI assets.
        </p>
    </div>

    <div class="page-title-actions">
        <a href="{{ route('baseline.index') }}" class="btn-gov btn-gov-outline">
            <i class="bi bi-database"></i>
            District Baseline
        </a>
    </div>
</div>

<div class="card-ppmf mb-4">
    <div class="card-ppmf-header">
        <div class="card-ppmf-title">
            <i class="bi bi-funnel"></i>
            Filters
        </div>
    </div>

    <div class="card-ppmf-body">
        <form method="GET" action="{{ route('baseline.assets') }}">
            <div class="row g-3 align-items-end">

                <div class="col-xl-2 col-lg-4 col-md-6">
                    <label class="form-label">Division</label>
                    <select name="division_id" class="form-select">
                        <option value="">All Divisions</option>
                        @foreach($divisions as $division)
                            <option value="{{ $division->id }}"
                                {{ ($filters['division_id'] ?? '') == $division->id ? 'selected' : '' }}>
                                {{ $division->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-xl-2 col-lg-4 col-md-6">
                    <label class="form-label">District</label>
                    <select name="district_id" class="form-select">
                        <option value="">All Districts</option>
                        @foreach($districts as $district)
                            <option value="{{ $district->id }}"
                                {{ ($filters['district_id'] ?? '') == $district->id ? 'selected' : '' }}>
                                {{ $district->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-xl-2 col-lg-4 col-md-6">
                    <label class="form-label">Tehsil</label>
                    <select name="tehsil_id" class="form-select">
                        <option value="">All Tehsils</option>
                        @foreach($tehsils as $tehsil)
                            <option value="{{ $tehsil->id }}"
                                {{ ($filters['tehsil_id'] ?? '') == $tehsil->id ? 'selected' : '' }}>
                                {{ $tehsil->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-xl-2 col-lg-4 col-md-6">
                    <label class="form-label">KPI Category</label>
                    <select name="kpi_category_id" class="form-select">
                        <option value="">All Categories</option>
                        @foreach($kpiCategories as $category)
                            <option value="{{ $category->id }}"
                                {{ ($filters['kpi_category_id'] ?? '') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-xl-2 col-lg-4 col-md-6">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="functional" {{ ($filters['status'] ?? '') === 'functional' ? 'selected' : '' }}>Functional</option>
                        <option value="non_functional" {{ ($filters['status'] ?? '') === 'non_functional' ? 'selected' : '' }}>Non Functional</option>
                        <option value="active" {{ ($filters['status'] ?? '') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>

                <div class="col-xl-2 col-lg-4 col-md-6">
                    <label class="form-label">Search</label>
                    <input
                        type="text"
                        name="search"
                        value="{{ $filters['search'] ?? '' }}"
                        class="form-control"
                        placeholder="Search asset..."
                    >
                </div>

                <div class="col-12">
                    <div class="d-flex gap-2 justify-content-end">
                        <button type="submit" class="btn-gov btn-gov-primary">
                            <i class="bi bi-search"></i>
                            Apply
                        </button>

                        <a href="{{ route('baseline.assets') }}" class="btn-gov btn-gov-outline">
                            Reset
                        </a>
                    </div>
                </div>

            </div>
        </form>
    </div>
</div>

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

@endsection

@push('styles')
<style>
    .btn-icon-action {
        width: 34px;
        height: 34px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(27, 107, 70, 0.10);
        color: var(--gov-green);
        text-decoration: none;
    }

    .btn-icon-action:hover {
        background: var(--gov-green);
        color: #fff;
    }
</style>
@endpush
