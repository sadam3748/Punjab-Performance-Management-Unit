@props(['value' => 0])
@php $value = max(0, min(100, (float) $value)); $color = $value >= 80 ? 'success' : ($value >= 60 ? 'primary' : ($value >= 40 ? 'warning' : 'danger')); @endphp
<div class="progress ppmu-progress" role="progressbar" aria-valuenow="{{ $value }}" aria-valuemin="0" aria-valuemax="100">
    <div class="progress-bar bg-{{ $color }}" style="width: {{ $value }}%"></div>
</div>
