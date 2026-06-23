@extends('layouts.app')

@section('title', 'Review KPI Submissions')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/ppmu-kpi.css') }}?v={{ filemtime(public_path('css/ppmu-kpi.css')) }}">
@endpush

@section('content')
<div class="ppmu-page-head">
    <div>
        <div class="ppmu-eyebrow">Verification Workflow</div>
        <h1>Review Submissions</h1>
        <p>Review KPI reports within your assigned administrative scope.</p>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<form id="submissionReviewFilters" class="ppmu-review-toolbar" method="GET" action="{{ route('kpi-submissions.index') }}">
    <div class="ppmu-period-pills">
        <button type="submit" name="period_type" value="" class="{{ empty($period['period_type']) ? 'active' : '' }}">All</button>
        @foreach($filters['period_types'] as $type)
            <button type="submit" name="period_type" value="{{ $type }}" class="{{ ($period['period_type'] ?? '') === $type ? 'active' : '' }}">
                {{ ucfirst($type) }}
            </button>
        @endforeach
    </div>

    <div class="ppmu-review-controls">
        <select name="week_no" class="form-select form-select-sm" data-period-control="weekly" @disabled(($period['period_type'] ?? '') !== 'weekly')>
            <option value="">Current week (Thu – Wed)</option>
            @foreach($filters['weeks'] ?? [] as $value => $label)
                <option value="{{ $value }}" @selected((string) ($period['week_no'] ?? '') === (string) $value)>{{ $label }}</option>
            @endforeach
        </select>

        <input type="date" name="date" value="{{ $period['date'] ?? '' }}" class="form-control form-control-sm" data-period-control="daily" @disabled(($period['period_type'] ?? '') !== 'daily')>

        <select name="month" class="form-select form-select-sm">
            <option value="">All months</option>
            @foreach($filters['months'] as $value => $label)
                <option value="{{ $value }}" @selected((string) ($period['month'] ?? '') === (string) $value)>{{ $label }}</option>
            @endforeach
        </select>

        <select name="year" class="form-select form-select-sm">
            @foreach($filters['years'] as $year)
                <option value="{{ $year }}" @selected((string) ($period['year'] ?? now()->year) === (string) $year)>{{ $year }}</option>
            @endforeach
        </select>

        <label class="ppmu-per-page">
            <span>Show</span>
            <select name="per_page" class="form-select form-select-sm">
                @foreach($perPageOptions as $option)
                    <option value="{{ $option }}" @selected($perPage === $option)>{{ $option }}</option>
                @endforeach
            </select>
        </label>
    </div>
</form>

<div class="card-ppmf ppmu-review-card">
    <div id="submissionReviewTable" data-review-table>
        @include('submissions.partials.review-table', ['submissions' => $submissions])
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('submissionReviewFilters');
    const table = document.querySelector('[data-review-table]');
    if (!form || !table) return;

    const setLoading = function (loading) {
        table.classList.toggle('is-loading', loading);
        form.querySelectorAll('button, select, input').forEach(function (control) {
            if (!control.matches('[data-period-control]')) {
                control.disabled = loading;
            }
        });
        syncPeriodControls();
    };

    const currentPeriod = function () {
        return form.querySelector('button[name="period_type"].active')?.value || '';
    };

    const syncPeriodControls = function () {
        const period = currentPeriod();
        form.querySelectorAll('[data-period-control]').forEach(function (control) {
            control.disabled = control.dataset.periodControl !== period;
        });
    };

    const buildUrl = function (pageUrl) {
        const params = new URLSearchParams(new FormData(form));
        const period = currentPeriod();
        if (period) {
            params.set('period_type', period);
        } else {
            params.delete('period_type');
        }

        if (currentPeriod() !== 'daily') params.delete('date');
        if (currentPeriod() !== 'weekly') params.delete('week_no');
        if (!period && !params.get('month')) params.delete('year');

        const url = new URL(pageUrl || form.action, window.location.origin);
        const page = pageUrl ? url.searchParams.get('page') : null;
        params.forEach(function (value, key) {
            if (value !== '') url.searchParams.set(key, value);
        });
        if (page) {
            url.searchParams.set('page', page);
        } else {
            url.searchParams.delete('page');
        }
        return url;
    };

    const load = function (pageUrl) {
        const url = buildUrl(pageUrl);
        setLoading(true);

        fetch(url.toString(), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
        })
            .then(function (response) {
                if (!response.ok) throw new Error('Request failed');
                return response.json();
            })
            .then(function (payload) {
                table.innerHTML = payload.html;
                window.history.replaceState({}, '', url.toString());
            })
            .catch(function () {
                window.location.href = url.toString();
            })
            .finally(function () {
                setLoading(false);
            });
    };

    form.addEventListener('click', function (event) {
        const button = event.target.closest('button[name="period_type"]');
        if (!button) return;
        event.preventDefault();
        form.querySelectorAll('button[name="period_type"]').forEach(function (item) {
            item.classList.toggle('active', item === button);
        });
        syncPeriodControls();
        load();
    });

    form.addEventListener('change', function () {
        load();
    });

    table.addEventListener('click', function (event) {
        const link = event.target.closest('.pagination a');
        if (!link) return;
        event.preventDefault();
        load(link.href);
    });

    syncPeriodControls();
});
</script>
@endpush
