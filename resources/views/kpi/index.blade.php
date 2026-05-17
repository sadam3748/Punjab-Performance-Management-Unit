@extends('layouts.app')

@section('title', 'KPI Categories')

@section('content')

<div class="page-title-bar">
    <div>
        <h1 class="page-title">KPI Categories</h1>
        <p class="page-subtitle">
            Manage KPI categories used for inspections, baseline data, geo tagging and reports.
        </p>
    </div>

    <div class="page-title-actions">
        <a href="{{ route('kpi.create') }}" class="btn-gov btn-gov-primary">
            <i class="bi bi-plus-circle"></i>
            Add KPI Category
        </a>

        <a href="{{ route('kpi.reporting-status') }}" class="btn-gov btn-gov-outline">
            <i class="bi bi-bar-chart-line"></i>
            Reporting Status
        </a>
    </div>
</div>

{{-- Success Message --}}
@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-1"></i>
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- Error Message --}}
@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-1"></i>
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- Summary Cards --}}
<div class="row g-3 mb-4">

    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="stat-card-ppmf">
            <div class="stat-icon-ppmf primary">
                <i class="bi bi-grid-3x3-gap"></i>
            </div>
            <div>
                <span>Total Categories</span>
                <strong>{{ number_format($summary['total_categories'] ?? ($kpiCategories->total() ?? $kpiCategories->count() ?? 0)) }}</strong>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="stat-card-ppmf">
            <div class="stat-icon-ppmf success">
                <i class="bi bi-check-circle"></i>
            </div>
            <div>
                <span>Active</span>
                <strong>{{ number_format($summary['active_categories'] ?? 0) }}</strong>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="stat-card-ppmf">
            <div class="stat-icon-ppmf warning">
                <i class="bi bi-pause-circle"></i>
            </div>
            <div>
                <span>Inactive</span>
                <strong>{{ number_format($summary['inactive_categories'] ?? 0) }}</strong>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="stat-card-ppmf">
            <div class="stat-icon-ppmf info">
                <i class="bi bi-database-check"></i>
            </div>
            <div>
                <span>Used in Reports</span>
                <strong>{{ number_format($summary['used_categories'] ?? 0) }}</strong>
            </div>
        </div>
    </div>

</div>

{{-- Filters --}}
<div class="card-ppmf mb-4">
    <div class="card-ppmf-header">
        <div class="card-ppmf-title">
            <i class="bi bi-funnel"></i>
            Filters
        </div>
    </div>

    <div class="card-ppmf-body">
        <form method="GET" action="{{ route('kpi.index') }}">
            <div class="row g-3 align-items-end">

                <div class="col-xl-4 col-lg-4 col-md-6">
                    <label class="form-label">Search</label>
                    <input
                        type="text"
                        name="search"
                        value="{{ $filters['search'] ?? request('search') }}"
                        class="form-control"
                        placeholder="Search name, code or description"
                    >
                </div>

                <div class="col-xl-3 col-lg-4 col-md-6">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="active" {{ ($filters['status'] ?? request('status')) === 'active' ? 'selected' : '' }}>
                            Active
                        </option>
                        <option value="inactive" {{ ($filters['status'] ?? request('status')) === 'inactive' ? 'selected' : '' }}>
                            Inactive
                        </option>
                    </select>
                </div>

                <div class="col-xl-5 col-lg-4 col-md-12">
                    <div class="d-flex gap-2 justify-content-end">
                        <button type="submit" class="btn-gov btn-gov-primary">
                            <i class="bi bi-search"></i>
                            Search
                        </button>

                        <a href="{{ route('kpi.index') }}" class="btn-gov btn-gov-outline">
                            <i class="bi bi-arrow-clockwise"></i>
                            Reset
                        </a>
                    </div>
                </div>

            </div>
        </form>
    </div>
</div>

{{-- KPI Category Table --}}
<div class="card-ppmf">
    <div class="card-ppmf-header">
        <div>
            <div class="card-ppmf-title">
                <i class="bi bi-list-ul"></i>
                KPI Category List
            </div>

            <p class="card-subtitle mb-0">
                Total records:
                {{ method_exists($kpiCategories, 'total') ? number_format($kpiCategories->total()) : number_format($kpiCategories->count()) }}
            </p>
        </div>
    </div>

    <div class="card-ppmf-body p-0">
        <div class="table-responsive">
            <table class="table-ppmf">
                <thead>
                    <tr>
                        <th style="width: 70px;">Sr.</th>
                        <th>KPI Category</th>
                        <th>Code</th>
                        <th>Description</th>
                        <th>Sort Order</th>
                        <th>Status</th>
                        <th>Created At</th>
                        <th class="text-center" style="width: 140px;">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($kpiCategories as $index => $category)
                        <tr>
                            <td>
                                {{ method_exists($kpiCategories, 'firstItem') ? $kpiCategories->firstItem() + $index : $index + 1 }}
                            </td>

                            <td>
                                <div class="fw-bold text-dark">
                                    {{ $category->name ?? 'N/A' }}
                                </div>
                            </td>

                            <td>
                                @if(!empty($category->code))
                                    <span class="code-chip-ppmf">
                                        {{ $category->code }}
                                    </span>
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>

                            <td>
                                <span class="text-muted">
                                    {{ \Illuminate\Support\Str::limit($category->description ?? 'No description available', 80) }}
                                </span>
                            </td>

                            <td>
                                <strong>{{ $category->sort_order ?? 0 }}</strong>
                            </td>

                            <td>
                                @if(($category->is_active ?? true) == true)
                                    <span class="badge-ppmf achieved">Active</span>
                                @else
                                    <span class="badge-ppmf critical">Inactive</span>
                                @endif
                            </td>

                            <td>
                                @if($category->created_at)
                                    {{ $category->created_at->format('d M, Y') }}
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>

                            <td class="text-center">
                                <div class="table-actions-ppmf">

                                    @if(\Illuminate\Support\Facades\Route::has('kpi.edit'))
                                        <a
                                            href="{{ route('kpi.edit', $category->id) }}"
                                            class="btn-icon-action"
                                            title="Edit"
                                        >
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                    @endif

                                    @if(\Illuminate\Support\Facades\Route::has('kpi.destroy'))
                                        <form
                                            action="{{ route('kpi.destroy', $category->id) }}"
                                            method="POST"
                                            class="d-inline"
                                            onsubmit="return confirm('Are you sure you want to delete this KPI category?');"
                                        >
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit" class="btn-icon-action danger" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @endif

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <div class="manual-box-ppmf">
                                    <i class="bi bi-grid"></i>
                                    <h5>No KPI Categories Found</h5>
                                    <p>No KPI category records are available. Please add a new category.</p>

                                    <a href="{{ route('kpi.create') }}" class="btn-gov btn-gov-primary btn-gov-sm mt-2">
                                        <i class="bi bi-plus-circle"></i>
                                        Add KPI Category
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>
    </div>

    @if (method_exists($kpiCategories, 'links'))
        <div class="card-ppmf-body border-top">
            <div class="pagination-wrapper">
                {{ $kpiCategories->links() }}
            </div>
        </div>
    @endif
</div>

@endsection

@push('styles')
<style>
    .code-chip-ppmf {
        display: inline-flex;
        align-items: center;
        padding: 5px 9px;
        border-radius: 999px;
        background: rgba(27, 107, 70, 0.10);
        color: var(--gov-green);
        font-size: 11.5px;
        font-weight: 800;
        white-space: nowrap;
    }

    .table-actions-ppmf {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
    }

    .btn-icon-action {
        width: 34px;
        height: 34px;
        border: none;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(27, 107, 70, 0.10);
        color: var(--gov-green);
        text-decoration: none;
        transition: 0.2s ease;
    }

    .btn-icon-action:hover {
        background: var(--gov-green);
        color: #fff;
    }

    .btn-icon-action.danger {
        background: rgba(220, 38, 38, 0.10);
        color: #b91c1c;
    }

    .btn-icon-action.danger:hover {
        background: #b91c1c;
        color: #fff;
    }

    .pagination-wrapper nav {
        display: flex;
        justify-content: flex-end;
    }
</style>
@endpush
