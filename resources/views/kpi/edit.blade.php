@extends('layouts.app')

@section('title', 'Edit KPI Category')

@section('content')

<div class="page-title-bar">
    <div>
        <h1 class="page-title">Edit KPI Category</h1>
        <p class="page-subtitle">
            Update KPI category information used in inspections, baseline data and reports.
        </p>
    </div>

    <div class="page-title-actions">
        <a href="{{ route('kpi.index') }}" class="btn-gov btn-gov-outline">
            <i class="bi bi-arrow-left"></i>
            Back to KPI Categories
        </a>
    </div>
</div>

@if ($errors->any())
    <div class="alert alert-danger" role="alert">
        <div class="fw-bold mb-1">
            <i class="bi bi-exclamation-circle-fill me-1"></i>
            Please fix the following errors:
        </div>

        <ul class="mb-0 ps-3">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row g-4">

    <div class="col-xl-8 col-lg-12">
        <div class="card-ppmf">
            <div class="card-ppmf-header">
                <div class="card-ppmf-title">
                    <i class="bi bi-pencil-square"></i>
                    KPI Category Information
                </div>

                @if(($kpiCategory->is_active ?? true) == true)
                    <span class="badge-ppmf achieved">Active</span>
                @else
                    <span class="badge-ppmf critical">Inactive</span>
                @endif
            </div>

            <div class="card-ppmf-body">
                <form action="{{ route('kpi.update', $kpiCategory->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">

                        <div class="col-md-8">
                            <label for="name" class="form-label">
                                KPI Category Name <span class="text-danger">*</span>
                            </label>

                            <input
                                type="text"
                                name="name"
                                id="name"
                                value="{{ old('name', $kpiCategory->name) }}"
                                class="form-control @error('name') is-invalid @enderror"
                                placeholder="Example: Inspection of Water Filtration Plants"
                                required
                            >

                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label for="code" class="form-label">
                                Category Code
                            </label>

                            <input
                                type="text"
                                name="code"
                                id="code"
                                value="{{ old('code', $kpiCategory->code) }}"
                                class="form-control @error('code') is-invalid @enderror"
                                placeholder="Example: WFP"
                            >

                            @error('code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-12">
                            <label for="description" class="form-label">
                                Description
                            </label>

                            <textarea
                                name="description"
                                id="description"
                                rows="4"
                                class="form-control @error('description') is-invalid @enderror"
                                placeholder="Write short description about this KPI category"
                            >{{ old('description', $kpiCategory->description) }}</textarea>

                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label for="sort_order" class="form-label">
                                Sort Order
                            </label>

                            <input
                                type="number"
                                name="sort_order"
                                id="sort_order"
                                value="{{ old('sort_order', $kpiCategory->sort_order ?? 0) }}"
                                class="form-control @error('sort_order') is-invalid @enderror"
                                min="0"
                            >

                            @error('sort_order')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label for="is_active" class="form-label">
                                Status
                            </label>

                            <select
                                name="is_active"
                                id="is_active"
                                class="form-select @error('is_active') is-invalid @enderror"
                            >
                                <option value="1" {{ old('is_active', $kpiCategory->is_active) == '1' ? 'selected' : '' }}>
                                    Active
                                </option>
                                <option value="0" {{ old('is_active', $kpiCategory->is_active) == '0' ? 'selected' : '' }}>
                                    Inactive
                                </option>
                            </select>

                            @error('is_active')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label for="icon" class="form-label">
                                Icon Class
                            </label>

                            <input
                                type="text"
                                name="icon"
                                id="icon"
                                value="{{ old('icon', $kpiCategory->icon) }}"
                                class="form-control @error('icon') is-invalid @enderror"
                                placeholder="Example: bi bi-droplet"
                            >

                            @error('icon')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                    </div>

                    <div class="form-action-row mt-4">
                        <button type="submit" class="btn-gov btn-gov-primary">
                            <i class="bi bi-save"></i>
                            Update KPI Category
                        </button>

                        <a href="{{ route('kpi.index') }}" class="btn-gov btn-gov-outline">
                            <i class="bi bi-x-circle"></i>
                            Cancel
                        </a>
                    </div>

                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-lg-12">

        <div class="card-ppmf mb-4">
            <div class="card-ppmf-header">
                <div class="card-ppmf-title">
                    <i class="bi bi-info-circle"></i>
                    Current Record
                </div>
            </div>

            <div class="card-ppmf-body">
                <div class="summary-stack">

                    <div class="summary-item">
                        <span>Category ID</span>
                        <strong>#{{ $kpiCategory->id }}</strong>
                    </div>

                    <div class="summary-item">
                        <span>Name</span>
                        <strong>{{ $kpiCategory->name }}</strong>
                    </div>

                    <div class="summary-item">
                        <span>Code</span>
                        <strong>{{ $kpiCategory->code ?? 'N/A' }}</strong>
                    </div>

                    <div class="summary-item">
                        <span>Status</span>
                        <strong>{{ $kpiCategory->is_active ? 'Active' : 'Inactive' }}</strong>
                    </div>

                    <div class="summary-item">
                        <span>Created At</span>
                        <strong>
                            {{ $kpiCategory->created_at ? $kpiCategory->created_at->format('d M, Y') : 'N/A' }}
                        </strong>
                    </div>

                    <div class="summary-item">
                        <span>Updated At</span>
                        <strong>
                            {{ $kpiCategory->updated_at ? $kpiCategory->updated_at->format('d M, Y') : 'N/A' }}
                        </strong>
                    </div>

                </div>
            </div>
        </div>

        <div class="card-ppmf">
            <div class="card-ppmf-header">
                <div class="card-ppmf-title">
                    <i class="bi bi-exclamation-triangle"></i>
                    Note
                </div>
            </div>

            <div class="card-ppmf-body">
                <div class="manual-box-ppmf text-start">
                    <p class="mb-0">
                        Updating KPI category name or code may affect reports, inspections and baseline records
                        linked with this category. Keep naming consistent for reporting accuracy.
                    </p>
                </div>
            </div>
        </div>

    </div>

</div>

@endsection

@push('styles')
<style>
    .form-action-row {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
        border-top: 1px solid var(--border-light);
        padding-top: 18px;
    }
</style>
@endpush
