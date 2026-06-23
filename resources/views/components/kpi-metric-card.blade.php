@props(['label', 'value', 'icon' => 'bi-bar-chart', 'tone' => 'green', 'unit' => null, 'hint' => null, 'variant' => 'row'])

@if($variant === 'indicator')
<article class="ppmu-pi-card tone-{{ $tone }}">
    <div class="ppmu-pi-icon"><i class="bi {{ $icon }}"></i></div>
    <div class="ppmu-pi-body">
        <span class="ppmu-pi-label">{{ $label }}</span>
        <strong class="ppmu-pi-value">
            {{ is_numeric($value) ? number_format((float) $value, is_float($value + 0) && floor($value) != $value ? 1 : 0) : $value }}@if($unit)<em>{{ $unit }}</em>@endif
        </strong>
        @if($hint)<small class="ppmu-pi-hint">{{ $hint }}</small>@endif
    </div>
</article>
@else
<div class="ppmu-metric-card tone-{{ $tone }}{{ $variant === 'tile' ? ' ppmu-metric-tile' : '' }}">
    <div class="ppmu-metric-icon"><i class="bi {{ $icon }}"></i></div>
    <div class="ppmu-metric-body">
        <strong>{{ is_numeric($value) ? number_format((float) $value, is_float($value + 0) && floor($value) != $value ? 1 : 0) : $value }}</strong>
        <span>{{ $label }}</span>
        @if($unit)<em>{{ $unit }}</em>@endif
    </div>
</div>
@endif
