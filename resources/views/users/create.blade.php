@extends('layouts.app')

@section('title', 'Add User')

@section('content')

<div class="page-title-bar">
    <div>
        <h1 class="page-title">Add New User</h1>
        <p class="page-subtitle">
            Create portal user with role and administrative access scope.
        </p>
    </div>

    <div class="page-title-actions">
        <a href="{{ route('users.index') }}" class="btn-gov btn-gov-outline">
            <i class="bi bi-arrow-left"></i>
            Back to Users
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

<div class="card-ppmf">
    <div class="card-ppmf-header">
        <div class="card-ppmf-title">
            <i class="bi bi-person-plus"></i>
            User Information
        </div>
    </div>

    <div class="card-ppmf-body">
        <form action="{{ route('users.store') }}" method="POST">
            @csrf

            <div class="row g-3">

                <div class="col-md-4">
                    <label class="form-label">Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" class="form-control" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Username <span class="text-danger">*</span></label>
                    <input type="text" name="username" value="{{ old('username') }}" class="form-control" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Password <span class="text-danger">*</span></label>
                    <input type="password" name="password" class="form-control" required>
                    <small class="text-muted">Minimum 6 characters.</small>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="form-control">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone') }}" class="form-control">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Designation</label>
                    <input type="text" name="designation" value="{{ old('designation') }}" class="form-control" placeholder="AC, DC, Commissioner, PMRU User">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Role <span class="text-danger">*</span></label>
                    <select name="role_id" class="form-select" required>
                        <option value="">Select Role</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>
                                {{ $role->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Division</label>
                    <select name="division_id" class="form-select">
                        <option value="">Punjab Level / No Division</option>
                        @foreach($divisions as $division)
                            <option value="{{ $division->id }}" {{ old('division_id') == $division->id ? 'selected' : '' }}>
                                {{ $division->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">District</label>
                    <select name="district_id" class="form-select">
                        <option value="">No District</option>
                        @foreach($districts as $district)
                            <option value="{{ $district->id }}" {{ old('district_id') == $district->id ? 'selected' : '' }}>
                                {{ $district->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Tehsil</label>
                    <select name="tehsil_id" class="form-select">
                        <option value="">No Tehsil</option>
                        @foreach($tehsils as $tehsil)
                            <option value="{{ $tehsil->id }}" {{ old('tehsil_id') == $tehsil->id ? 'selected' : '' }}>
                                {{ $tehsil->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="is_active" class="form-select">
                        <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>

            </div>

            <div class="form-action-row mt-4">
                <button type="submit" class="btn-gov btn-gov-primary">
                    <i class="bi bi-save"></i>
                    Save User
                </button>

                <a href="{{ route('users.index') }}" class="btn-gov btn-gov-outline">
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
