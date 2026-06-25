@props(['label', 'value', 'icon' => 'bi-bar-chart', 'tone' => 'green', 'unit' => null, 'hint' => null, 'description' => null, 'formula' => null, 'variant' => 'row'])

@php
    $tooltip = trim((string) ($formula ?: $description ?: $hint ?: ''));
    $percentageMetric = str_contains(strtolower($label), '%')
        || str_contains(strtolower($label), 'rate')
        || str_contains(strtolower($label), 'percentage')
        || str_contains(strtolower($label), 'completion')
        || str_contains(strtolower($label), 'compliance')
        || str_contains(strtolower($label), 'achievement');
    $safeValue = is_numeric($value) && $percentageMetric
        ? max(0, min(100, (float) $value))
        : $value;
    $displayValue = is_numeric($safeValue)
        ? number_format((float) $safeValue, is_float($safeValue + 0) && floor($safeValue) != $safeValue ? 1 : 0)
        : $value;
@endphp

@if($variant === 'indicator')
<article class="ppmu-pi-card tone-{{ $tone }}" title="{{ $label }}">
    <div class="ppmu-pi-card-head">
        <div class="ppmu-pi-icon" aria-hidden="true"><i class="bi {{ $icon }}"></i></div>
        <h4 class="ppmu-pi-title">{{ $label }}</h4>
        @if($tooltip !== '')
            <span class="ppmu-pi-info" title="{{ $tooltip }}" aria-label="More information about {{ $label }}">
                <i class="bi bi-info-circle"></i>
            </span>
        @endif
    </div>
    <div class="ppmu-pi-card-foot">
        <strong class="ppmu-pi-value">
            {{ $displayValue }}@if($unit)<em>{{ $unit }}</em>@endif
        </strong>
    </div>
</article>
@else
<div class="ppmu-metric-card tone-{{ $tone }}{{ $variant === 'tile' ? ' ppmu-metric-tile' : '' }}">
    <div class="ppmu-metric-icon"><i class="bi {{ $icon }}"></i></div>
    <div class="ppmu-metric-body">
        <strong>{{ $displayValue }}</strong>
        <span>{{ $label }}</span>
        @if($unit)<em>{{ $unit }}</em>@endif
    </div>
</div>
@endif
