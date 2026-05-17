@extends('layouts.app')

@section('title', 'Edit Baseline Data')

@section('content')

<div class="page-title-bar">
    <div>
        <h1 class="page-title">Edit Baseline Data</h1>
        <p class="page-subtitle">
            Update district-wise baseline summary for KPI category.
        </p>
    </div>

    <div class="page-title-actions">
        <a href="{{ route('baseline.index') }}" class="btn-gov btn-gov-outline">
            <i class="bi bi-arrow-left"></i>
            Back to Baseline Data
        </a>
    </div>
</div>

@if ($errors->any())
    <div class="alert alert-danger">
        <strong>Please fix the following errors:</strong>
        <ul class="mb-0 mt-2">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@php
    $data = $baseline->baseline_data ?? [];
@endphp

<div class="card-ppmf">
    <div class="card-ppmf-header">
        <div class="card-ppmf-title">
            <i class="bi bi-pencil-square"></i>
            Baseline Information
        </div>
    </div>

    <div class="card-ppmf-body">
        <form action="{{ route('baseline.update', $baseline->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row g-3">

                <div class="col-md-4">
                    <label class="form-label">District <span class="text-danger">*</span></label>
                    <select name="district_id" class="form-select" required>
                        <option value="">Select District</option>
                        @foreach($districts as $district)
                            <option value="{{ $district->id }}"
                                {{ old('district_id', $baseline->district_id) == $district->id ? 'selected' : '' }}>
                                {{ $district->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">KPI Category <span class="text-danger">*</span></label>
                    <select name="kpi_category_id" class="form-select" required>
                        <option value="">Select KPI Category</option>
                        @foreach($kpiCategories as $category)
                            <option value="{{ $category->id }}"
                                {{ old('kpi_category_id', $baseline->kpi_category_id) == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Year <span class="text-danger">*</span></label>
                    <input
                        type="number"
                        name="year"
                        value="{{ old('year', $baseline->year) }}"
                        class="form-control"
                        min="2020"
                        max="2100"
                        required
                    >
                </div>

                <div class="col-md-4">
                    <label class="form-label">Total Assets</label>
                    <input
                        type="number"
                        name="baseline_data[total_assets]"
                        value="{{ old('baseline_data.total_assets', $data['total_assets'] ?? '') }}"
                        class="form-control"
                        min="0"
                    >
                </div>

                <div class="col-md-4">
                    <label class="form-label">Functional Assets</label>
                    <input
                        type="number"
                        name="baseline_data[functional_assets]"
                        value="{{ old('baseline_data.functional_assets', $data['functional_assets'] ?? '') }}"
                        class="form-control"
                        min="0"
                    >
                </div>

                <div class="col-md-4">
                    <label class="form-label">Non-Functional Assets</label>
                    <input
                        type="number"
                        name="baseline_data[non_functional_assets]"
                        value="{{ old('baseline_data.non_functional_assets', $data['non_functional_assets'] ?? '') }}"
                        class="form-control"
                        min="0"
                    >
                </div>

                <div class="col-md-6">
                    <label class="form-label">Remarks</label>
                    <textarea
                        name="baseline_data[remarks]"
                        class="form-control"
                        rows="3"
                    >{{ old('baseline_data.remarks', $data['remarks'] ?? '') }}</textarea>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Source / Reference</label>
                    <textarea
                        name="baseline_data[source]"
                        class="form-control"
                        rows="3"
                    >{{ old('baseline_data.source', $data['source'] ?? '') }}</textarea>
                </div>

            </div>

            <div class="form-action-row mt-4">
                <button type="submit" class="btn-gov btn-gov-primary">
                    <i class="bi bi-save"></i>
                    Update Baseline Data
                </button>

                <a href="{{ route('baseline.index') }}" class="btn-gov btn-gov-outline">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

@endsection

@push('styles')
<style>
    .form-action-row {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        border-top: 1px solid var(--border-light);
        padding-top: 18px;
    }
</style>
@endpush
