@props(['filters', 'period', 'action' => null, 'ajax' => false, 'periodDescription' => 'All periods · Complete available data'])

@php
    $typeLabels = ['daily' => 'Today', 'weekly' => 'Weekly', 'monthly' => 'Monthly', 'yearly' => 'Yearly'];
@endphp

@if($ajax)
<div class="ppmu-period-filter ppmu-period-filter-ajax" id="kpiPeriodFilter" data-kpi-ajax-url="{{ $action }}">
    <div class="ppmu-period-filter-top">
        <div class="ppmu-period-pills">
            <button type="button" data-period-type="" class="{{ empty($period['period_type']) ? 'active' : '' }}">All</button>
            @foreach($filters['period_types'] as $type)
                <button type="button" data-period-type="{{ $type }}" class="{{ ($period['period_type'] ?? '') === $type ? 'active' : '' }}">{{ $typeLabels[$type] ?? ucfirst($type) }}</button>
            @endforeach
        </div>
        <div class="ppmu-period-range" id="kpiPeriodRangeLabel">
            <i class="bi bi-calendar-range"></i>
            <span>{{ $periodDescription }}</span>
        </div>
    </div>
    <div class="ppmu-period-filter-controls">
        <div class="ppmu-period-selects">
            <select data-filter="week_no" class="form-select form-select-sm ppmu-filter-week" data-period-control="weekly" @if(($period['period_type'] ?? '') !== 'weekly') hidden @endif>
                <option value="">Current week (Thu – Wed)</option>
                @foreach($filters['weeks'] ?? [] as $value => $label)
                    <option value="{{ $value }}" @selected((string) ($period['week_no'] ?? '') === (string) $value)>{{ $label }}</option>
                @endforeach
            </select>
            <input type="date" data-filter="date" value="{{ $period['date'] ?? '' }}" class="form-control form-control-sm ppmu-filter-date" data-period-control="daily" @if(($period['period_type'] ?? '') !== 'daily') hidden @endif>
            <select data-filter="month" class="form-select form-select-sm ppmu-filter-month" data-period-control="monthly all" @if(in_array($period['period_type'] ?? '', ['daily', 'weekly', 'yearly'])) hidden @endif>
                <option value="">All months</option>
                @foreach($filters['months'] as $value => $label)
                    <option value="{{ $value }}" @selected((string) ($period['month'] ?? '') === (string) $value)>{{ $label }}</option>
                @endforeach
            </select>
            <select data-filter="year" class="form-select form-select-sm ppmu-filter-year" data-period-control="monthly yearly all" @if(($period['period_type'] ?? '') === 'daily') hidden @endif>
                @foreach($filters['years'] as $year)
                    <option value="{{ $year }}" @selected((string) ($period['year'] ?? now()->year) === (string) $year)>{{ $year }}</option>
                @endforeach
            </select>
            <button type="button" class="btn btn-sm btn-outline-secondary ppmu-filter-reset" data-filter-reset>Reset</button>
        </div>
        <div class="ppmu-filter-loading" hidden><span class="spinner-border spinner-border-sm text-success"></span></div>
    </div>
</div>
@else
<form class="ppmu-period-filter" method="GET" action="{{ $action ?? url()->current() }}">
    <div class="ppmu-period-pills">
        <button type="submit" name="period_type" value="" class="{{ empty($period['period_type']) ? 'active' : '' }}">All</button>
        @foreach($filters['period_types'] as $type)
            <button type="submit" name="period_type" value="{{ $type }}" class="{{ ($period['period_type'] ?? '') === $type ? 'active' : '' }}">{{ $typeLabels[$type] ?? ucfirst($type) }}</button>
        @endforeach
    </div>
    <div class="ppmu-period-selects">
        @if(($period['period_type'] ?? '') === 'weekly')
            <select name="week_no" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">Current week (Thu – Wed)</option>
                @foreach($filters['weeks'] ?? [] as $value => $label)
                    <option value="{{ $value }}" @selected((string) ($period['week_no'] ?? '') === (string) $value)>{{ $label }}</option>
                @endforeach
            </select>
        @endif
        @if(($period['period_type'] ?? '') === 'daily')
            <input type="date" name="date" value="{{ $period['date'] ?? '' }}" class="form-control form-control-sm" onchange="this.form.submit()">
        @endif
        <select name="month" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">All months</option>
            @foreach($filters['months'] as $value => $label)
                <option value="{{ $value }}" @selected((string) ($period['month'] ?? '') === (string) $value)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
            @foreach($filters['years'] as $year)
                <option value="{{ $year }}" @selected((string) ($period['year'] ?? now()->year) === (string) $year)>{{ $year }}</option>
            @endforeach
        </select>
        @if(!empty($period['period_type']))
            <input type="hidden" name="period_type" value="{{ $period['period_type'] }}">
        @endif
    </div>
</form>
@endif
