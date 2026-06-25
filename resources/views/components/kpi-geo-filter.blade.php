@props(['geoFilters', 'kpiCard' => null, 'geo' => [], 'action' => null])

@php
    $selected = $geoFilters['selected'] ?? [];
    $showFilter = $geoFilters['show_filter'] ?? false;
    $hasGeoControls = !empty($geoFilters['show_division']) || !empty($geoFilters['show_district']) || !empty($geoFilters['show_tehsil']);
    $formAction = $action ?? ($kpiCard ? route('kpi.dashboard', $kpiCard) : route('inspections.index'));
    $resetUrl = $kpiCard ? route('kpi.dashboard', $kpiCard) : route('inspections.index');
@endphp

@if($showFilter && $hasGeoControls)
<form id="kpiGeoFilter" class="ppmu-geo-filter card-ppmf mb-3" method="GET" action="{{ $formAction }}">
    @foreach(request()->except(['geo_division', 'geo_district', 'geo_tehsil', 'geo_date_from', 'geo_date_to', 'insp_page']) as $key => $value)
        @if(is_scalar($value))
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endif
    @endforeach

    <div class="ppmu-geo-filter-row">
        @if(!empty($geoFilters['show_division']))
            <label>
                <span>Division</span>
                <select name="geo_division" class="form-select form-select-sm" data-geo-filter="geo_division">
                    <option value="">All divisions</option>
                    @foreach(($geoFilters['divisions'] ?? []) as $id => $name)
                        <option value="{{ $id }}" @selected((string) ($selected['division_id'] ?? '') === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </label>
        @endif

        @if(!empty($geoFilters['show_district']))
            <label>
                <span>District</span>
                <select name="geo_district" class="form-select form-select-sm" data-geo-filter="geo_district">
                    <option value="">All districts</option>
                    @foreach(($geoFilters['districts'] ?? []) as $id => $name)
                        <option value="{{ $id }}" @selected((string) ($selected['district_id'] ?? '') === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </label>
        @endif

        @if(!empty($geoFilters['show_tehsil']))
            <label>
                <span>Tehsil</span>
                <select name="geo_tehsil" class="form-select form-select-sm" data-geo-filter="geo_tehsil">
                    <option value="">All tehsils</option>
                    @foreach(($geoFilters['tehsils'] ?? []) as $id => $name)
                        <option value="{{ $id }}" @selected((string) ($selected['tehsil_id'] ?? '') === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </label>
        @endif

        <button type="submit" class="btn btn-sm btn-success">Apply</button>
        <a href="{{ $resetUrl }}" class="btn btn-sm btn-outline-secondary">Reset</a>
    </div>
</form>
@endif
