@props(['card'])
@php
    $target = round((float) ($card->target ?? $card->total_marks ?? 0), 1);
    $reported = (int) ($card->reported ?? 0);
    $achieved = round((float) ($card->achieved ?? 0), 1);
    $pct = round((float) ($card->achievement_percentage ?? 0), 1);
    $statusLabel = $card->status_label ?? 'pending';
    $imageUrl = asset($card->resolvedImagePath());
    $formatValue = fn (float $value) => rtrim(rtrim(number_format($value, 1, '.', ','), '0'), '.');
@endphp
<article class="kpi-png-tile" data-kpi-card aria-labelledby="kpi-title-{{ $card->id }}">
    <div class="kpi-png-icon-panel">
        <div class="kpi-png-image-wrap">
            <img src="{{ $imageUrl }}" alt="{{ $card->title }}" class="kpi-png-image" width="82" height="82" loading="lazy">
        </div>
    </div>

    <div class="kpi-png-content">
        <div class="d-flex align-items-center justify-content-between gap-2 mb-1">
            <div class="kpi-png-label">KPI Dashboard</div>
            <x-status-badge :status="$statusLabel" />
        </div>
        <h2 class="kpi-png-title" id="kpi-title-{{ $card->id }}">{{ $card->title }}</h2>

        <div class="kpi-png-values">
            <div class="kpi-png-value">
                <span>Target</span>
                <strong>{{ $formatValue($target) }}</strong>
            </div>
            <div class="kpi-png-value">
                <span>Reported</span>
                <strong>{{ number_format($reported) }}</strong>
            </div>
            <div class="kpi-png-value kpi-png-value-actual">
                <span>Achieved</span>
                <strong>{{ $formatValue($achieved) }}</strong>
            </div>
        </div>

        <div class="kpi-png-progress-wrap px-0">
            <div class="kpi-png-progress-meta">
                <span>{{ $pct }}% achievement</span>
            </div>
            <x-progress-bar :value="$pct" color="success" />
        </div>

        <a href="{{ route('kpi.dashboard', $card) }}" class="kpi-png-button" target="_blank" rel="noopener noreferrer">
            View Dashboard <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
        </a>
    </div>
</article>
