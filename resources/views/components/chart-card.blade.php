@props(['title', 'subtitle' => null, 'canvas', 'chartKey' => null])
<div class="card-ppmf ppmu-chart-card" @if($chartKey) data-chart-key="{{ $chartKey }}" @endif>
    <div class="card-ppmf-header"><div><div class="card-ppmf-title">{{ $title }}</div>@if($subtitle)<small class="text-muted">{{ $subtitle }}</small>@endif</div></div>
    <div class="card-ppmf-body"><canvas id="{{ $canvas }}"></canvas></div>
</div>
