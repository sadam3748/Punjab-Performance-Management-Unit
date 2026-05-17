@extends('layouts.app')

@section('title', 'User Management')

@section('content')

<div class="page-title-bar">
    <div>
        <h1 class="page-title">User Management</h1>
        <p class="page-subtitle">
            Manage PPMF portal users, roles and administrative access scope.
        </p>
    </div>

    <div class="page-title-actions">
        <a href="{{ route('users.create') }}" class="btn-gov btn-gov-primary">
            <i class="bi bi-plus-circle"></i>
            Add New User
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle-fill me-1"></i>
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card-ppmf mb-4">
    <div class="card-ppmf-header">
        <div class="card-ppmf-title">
            <i class="bi bi-funnel"></i>
            Filters
        </div>
    </div>

    <div class="card-ppmf-body">
        <form method="GET" action="{{ route('users.index') }}">
            <div class="row g-3 align-items-end">

                <div class="col-xl-2 col-lg-4 col-md-6">
                    <label class="form-label">Role</label>
                    <select name="role_id" class="form-select">
                        <option value="">All Roles</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}"
                                {{ ($filters['role_id'] ?? '') == $role->id ? 'selected' : '' }}>
                                {{ $role->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

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
                    <label class="form-label">Status</label>
                    <select name="is_active" class="form-select">
                        <option value="">All Status</option>
                        <option value="1" {{ ($filters['is_active'] ?? '') === '1' ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ ($filters['is_active'] ?? '') === '0' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>

                <div class="col-xl-2 col-lg-4 col-md-6">
                    <label class="form-label">Search</label>
                    <input
                        type="text"
                        name="search"
                        value="{{ $filters['search'] ?? '' }}"
                        class="form-control"
                        placeholder="Name, username, email"
                    >
                </div>

                <div class="col-12">
                    <div class="d-flex gap-2 justify-content-end">
                        <button type="submit" class="btn-gov btn-gov-primary">
                            <i class="bi bi-search"></i>
                            Apply Filter
                        </button>

                        <a href="{{ route('users.index') }}" class="btn-gov btn-gov-outline">
                            <i class="bi bi-arrow-clockwise"></i>
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
                <i class="bi bi-people"></i>
                User List
            </div>
            <p class="card-subtitle mb-0">
                Total records:
                {{ method_exists($users, 'total') ? number_format($users->total()) : number_format($users->count()) }}
            </p>
        </div>
    </div>

    <div class="card-ppmf-body p-0">
        <div class="table-responsive">
            <table class="table-ppmf">
                <thead>
                    <tr>
                        <th>Sr.</th>
                        <th>User Detail</th>
                        <th>Role</th>
                        <th>Designation</th>
                        <th>Access Scope</th>
                        <th>Contact</th>
                        <th>Status</th>
                        <th>Last Login</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($users as $index => $user)
                        <tr>
                            <td>
                                {{ method_exists($users, 'firstItem') ? $users->firstItem() + $index : $index + 1 }}
                            </td>

                            <td>
                                <div class="fw-bold">{{ $user->name }}</div>
                                <small class="text-muted">{{ $user->username }}</small>
                            </td>

                            <td>
                                <span class="badge-ppmf info">
                                    {{ $user->role->name ?? 'N/A' }}
                                </span>
                            </td>

                            <td>
                                {{ $user->designation ?? 'N/A' }}
                            </td>

                            <td>
                                @if($user->tehsil)
                                    <strong>{{ $user->tehsil->name }}</strong>
                                    <small class="d-block text-muted">Tehsil Level</small>
                                @elseif($user->district)
                                    <strong>{{ $user->district->name }}</strong>
                                    <small class="d-block text-muted">District Level</small>
                                @elseif($user->division)
                                    <strong>{{ $user->division->name }}</strong>
                                    <small class="d-block text-muted">Division Level</small>
                                @else
                                    <strong>Punjab</strong>
                                    <small class="d-block text-muted">Province Level</small>
                                @endif
                            </td>

                            <td>
                                <div>{{ $user->email ?? 'N/A' }}</div>
                                <small class="text-muted">{{ $user->phone ?? '' }}</small>
                            </td>

                            <td>
                                @if($user->is_active)
                                    <span class="badge-ppmf achieved">Active</span>
                                @else
                                    <span class="badge-ppmf critical">Inactive</span>
                                @endif
                            </td>

                            <td>
                                {{ $user->last_login_at ? $user->last_login_at->format('d M, Y h:i A') : 'Never' }}
                            </td>

                            <td class="text-center">
                                <div class="table-actions-ppmf">
                                    <a href="{{ route('users.edit', $user->id) }}" class="btn-icon-action" title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>

                                    <form action="{{ route('users.toggle-status', $user->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('PATCH')

                                        <button
                                            type="submit"
                                            class="btn-icon-action {{ $user->is_active ? 'danger' : 'success' }}"
                                            title="{{ $user->is_active ? 'Deactivate' : 'Activate' }}"
                                            onclick="return confirm('Are you sure you want to change this user status?')"
                                        >
                                            @if($user->is_active)
                                                <i class="bi bi-person-x"></i>
                                            @else
                                                <i class="bi bi-person-check"></i>
                                            @endif
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5">
                                <div class="manual-box-ppmf">
                                    <i class="bi bi-people"></i>
                                    <h5>No Users Found</h5>
                                    <p>No user records are available for selected filters.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>
    </div>

    @if(method_exists($users, 'links'))
        <div class="card-ppmf-body border-top">
            {{ $users->links() }}
        </div>
    @endif
</div>

@endsection

@push('styles')
<style>
    .table-actions-ppmf {
        display: inline-flex;
        gap: 7px;
        align-items: center;
        justify-content: center;
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

    .btn-icon-action.success {
        background: rgba(22, 163, 74, 0.10);
        color: #15803d;
    }

    .btn-icon-action.success:hover {
        background: #15803d;
        color: #fff;
    }
</style>
@endpush
