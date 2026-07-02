@props([
    'label',
    'value',
    'icon' => 'bi-bar-chart',
    'tone' => 'green',
    'unit' => null,
    'hint' => null,
    'description' => null,
    'formula' => null,
    'variant' => 'row',
    'displayMode' => null,
    'observationAvailable' => null,
    'observationNotAvailable' => null,
    'observationYes' => null,
    'observationNo' => null,
    'attentionText' => null,
    'cardHelper' => null,
])

@php
    $tooltip = trim((string) ($formula ?: $description ?: $hint ?: $cardHelper ?: ''));
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
<article class="ppmu-pi-card tone-{{ $tone }}{{ $displayMode ? ' ppmu-pi-card-'.$displayMode : '' }}" title="{{ $label }}">
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
        @if($displayMode === 'observation_availability')
            <div class="ppmu-observation-chips" aria-label="{{ $label }} availability counts">
                <span class="ppmu-obs-chip ppmu-obs-chip-available">Available: {{ (int) $observationAvailable }}</span>
                <span class="ppmu-obs-chip ppmu-obs-chip-unavailable">Not Available: {{ (int) $observationNotAvailable }}</span>
            </div>
        @elseif($displayMode === 'observation_yesno')
            <div class="ppmu-observation-chips" aria-label="{{ $label }} compliance counts">
                <span class="ppmu-obs-chip ppmu-obs-chip-available">Yes: {{ (int) $observationYes }}</span>
                <span class="ppmu-obs-chip ppmu-obs-chip-unavailable">No: {{ (int) $observationNo }}</span>
            </div>
        @elseif($displayMode === 'attention')
            <strong class="ppmu-pi-value">{{ $attentionText }}</strong>
            @if($cardHelper)
                <small class="ppmu-pi-card-helper">{{ $cardHelper }}</small>
            @endif
        @else
            <strong class="ppmu-pi-value">
                {{ $displayValue }}@if($unit)<em>{{ $unit }}</em>@endif
            </strong>
            @if($cardHelper)
                <small class="ppmu-pi-card-helper">{{ $cardHelper }}</small>
            @endif
        @endif
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
