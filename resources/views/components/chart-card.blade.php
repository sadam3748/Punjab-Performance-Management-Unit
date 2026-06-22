@props(['title', 'subtitle' => null, 'canvas'])
<div class="card-ppmf ppmu-chart-card">
    <div class="card-ppmf-header"><div><div class="card-ppmf-title">{{ $title }}</div>@if($subtitle)<small class="text-muted">{{ $subtitle }}</small>@endif</div></div>
    <div class="card-ppmf-body"><canvas id="{{ $canvas }}"></canvas></div>
</div>
