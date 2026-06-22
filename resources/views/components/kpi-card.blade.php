@props(['card', 'scope'])
@php
    $percentage = round((float) ($card->achievement_percentage ?? $card->performance ?? 0), 1);
    $iconPath = str_contains($card->icon ?? '', '.') ? asset('assets/images/kpi-icons/'.$card->icon) : null;
@endphp
<article class="ppmu-kpi-card" data-kpi-card>
    <div class="ppmu-kpi-card-head">
        <span class="ppmu-kpi-icon">
            @if($iconPath)
                <img src="{{ $iconPath }}" alt="{{ $card->title }}" width="32" height="32">
            @else
                <i class="bi {{ $card->icon }}"></i>
            @endif
        </span>
        <div class="ppmu-kpi-heading">
            <h2>{{ $card->title }}</h2>
        </div>
        <x-status-badge :status="$card->status_label ?? ($percentage >= 80 ? 'excellent' : ($percentage >= 60 ? 'good' : 'needs attention'))" />
    </div>

    <div class="ppmu-kpi-performance">
        <div class="ppmu-kpi-score"><span>Performance</span><strong>{{ $percentage }}%</strong></div>
        <x-progress-bar :value="$percentage" />
    </div>

    <div class="ppmu-kpi-counts">
        <div class="metric-submitted"><b>{{ $card->submitted_count ?? 0 }}</b><span>Submitted</span></div>
        <div class="metric-pending"><b>{{ $card->pending_count ?? 0 }}</b><span>Pending</span></div>
    </div>

    <div class="ppmu-kpi-card-foot">
        <a href="{{ route('kpi.dashboard', $card) }}" class="ppmu-kpi-action">Open Dashboard <i class="bi bi-arrow-right"></i></a>
    </div>
</article>
