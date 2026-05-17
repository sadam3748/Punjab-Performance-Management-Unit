@extends('layouts.app')

@section('title', 'Add KPI Category')

@section('content')

<div class="page-title-bar">
    <div>
        <h1 class="page-title">Add KPI Category</h1>
        <p class="page-subtitle">
            Create a new KPI category for inspection, baseline and reporting modules.
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
                    <i class="bi bi-plus-circle"></i>
                    KPI Category Information
                </div>
            </div>

            <div class="card-ppmf-body">
                <form action="{{ route('kpi.store') }}" method="POST">
                    @csrf

                    <div class="row g-3">

                        <div class="col-md-8">
                            <label for="name" class="form-label">
                                KPI Category Name <span class="text-danger">*</span>
                            </label>

                            <input
                                type="text"
                                name="name"
                                id="name"
                                value="{{ old('name') }}"
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
                                value="{{ old('code') }}"
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
                            >{{ old('description') }}</textarea>

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
                                value="{{ old('sort_order', 0) }}"
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
                                <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>
                                    Active
                                </option>
                                <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>
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
                                value="{{ old('icon') }}"
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
                            Save KPI Category
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
                    Usage Information
                </div>
            </div>

            <div class="card-ppmf-body">
                <div class="summary-stack">

                    <div class="summary-item">
                        <span>Used In</span>
                        <strong>Inspections</strong>
                    </div>

                    <div class="summary-item">
                        <span>Used In</span>
                        <strong>Baseline Data</strong>
                    </div>

                    <div class="summary-item">
                        <span>Used In</span>
                        <strong>KPI Reports</strong>
                    </div>

                    <div class="summary-item">
                        <span>Used In</span>
                        <strong>Dashboard Charts</strong>
                    </div>

                </div>
            </div>
        </div>

        <div class="card-ppmf">
            <div class="card-ppmf-header">
                <div class="card-ppmf-title">
                    <i class="bi bi-lightbulb"></i>
                    Example Categories
                </div>
            </div>

            <div class="card-ppmf-body">
                <div class="example-list-ppmf">
                    <span>Inspection of Marriage Halls</span>
                    <span>Inspection of Water Filtration Plants</span>
                    <span>Inspection of Manhole Covers</span>
                    <span>Inspection of Stray Dogs</span>
                    <span>Inspection of Petrol Pumps</span>
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

    .example-list-ppmf {
        display: grid;
        gap: 8px;
    }

    .example-list-ppmf span {
        display: block;
        padding: 9px 11px;
        border-radius: var(--radius-sm);
        background: var(--bg);
        border: 1px solid var(--border-light);
        color: var(--text-secondary);
        font-size: 12.5px;
        font-weight: 700;
    }
</style>
@endpush
