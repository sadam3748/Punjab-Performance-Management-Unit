@props(['label', 'value', 'icon' => 'bi-bar-chart', 'tone' => 'green'])
<div class="ppmu-metric-card tone-{{ $tone }}">
    <div class="ppmu-metric-icon"><i class="bi {{ $icon }}"></i></div>
    <div class="ppmu-metric-body">
        <strong>{{ is_numeric($value) ? number_format((float) $value, is_float($value + 0) && floor($value) != $value ? 1 : 0) : $value }}</strong>
        <span>{{ $label }}</span>
    </div>
</div>
