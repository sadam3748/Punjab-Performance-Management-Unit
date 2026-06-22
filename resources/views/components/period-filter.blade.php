@props(['filters', 'period', 'action' => null])
<form class="ppmu-period-filter" method="GET" action="{{ $action ?? url()->current() }}">
    <div class="ppmu-period-pills">
        <button type="submit" name="period_type" value="" class="{{ empty($period['period_type']) ? 'active' : '' }}">All</button>
        @foreach($filters['period_types'] as $type)
            <button type="submit" name="period_type" value="{{ $type }}" class="{{ ($period['period_type'] ?? '') === $type ? 'active' : '' }}">{{ ucfirst($type) }}</button>
        @endforeach
    </div>
    <div class="ppmu-period-selects">
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
