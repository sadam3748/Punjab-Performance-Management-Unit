@props(['label', 'value', 'icon' => 'bi-bar-chart', 'tone' => 'green'])
<div class="ppmu-summary-card tone-{{ $tone }}">
    <div class="ppmu-summary-icon"><i class="bi {{ $icon }}"></i></div>
    <div><div class="ppmu-summary-value">{{ $value }}</div><div class="ppmu-summary-label">{{ $label }}</div></div>
</div>
