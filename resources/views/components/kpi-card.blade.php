@props(['card', 'periodQuery' => ''])
@php
    $target = round((float) ($card->target ?? $card->total_marks ?? 0), 1);
    $reported = (int) ($card->reported ?? 0);
    $achieved = round((float) ($card->achieved ?? 0), 1);
    $pct = max(0, min(100, (float) ($card->achievement_percentage ?? 0)));
    $status = strtolower((string) ($card->status_label ?? 'critical'));
    $statusText = match ($status) {
        'excellent' => 'Excellent',
        'good' => 'Good',
        'attention' => 'Attention',
        default => 'Critical',
    };
    $imageUrl = asset($card->resolvedImagePath());
    $formatValue = fn (float $value) => rtrim(rtrim(number_format($value, 1, '.', ','), '0'), '.');
    $detailUrl = route('kpi.dashboard', $card).($periodQuery ? '?'.$periodQuery : '');
@endphp
<article class="ppmu-kpi-tile" data-kpi-card aria-labelledby="kpi-title-{{ $card->id }}">
    <span class="ppmu-kpi-tile-status ppmu-kpi-status-{{ $status }}">{{ $statusText }}</span>

    <div class="ppmu-kpi-tile-image">
        <img src="{{ $imageUrl }}" alt="{{ $card->title }}" width="140" height="140" loading="lazy">
    </div>

    <h3 class="ppmu-kpi-tile-title" id="kpi-title-{{ $card->id }}" title="{{ $card->title }}">{{ $card->title }}</h3>

    <div class="ppmu-kpi-tile-achievement">
        <span class="ppmu-kpi-tile-pct ppmu-kpi-pct-{{ $status }}">{{ rtrim(rtrim(number_format($pct, 1), '0'), '.') }}% Achievement</span>
        <div class="ppmu-kpi-tile-progress" role="progressbar" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100" aria-label="Achievement progress">
            <span class="ppmu-kpi-tile-progress-fill ppmu-kpi-progress-{{ $status }}" style="width: {{ $pct }}%"></span>
        </div>
    </div>

    <div class="ppmu-kpi-tile-values ppmu-kpi-tile-values-3">
        <div class="ppmu-kpi-tile-value">
            <span>Target</span>
            <strong>{{ $formatValue($target) }}</strong>
        </div>
        <div class="ppmu-kpi-tile-value">
            <span>Reported</span>
            <strong>{{ number_format($reported) }}</strong>
        </div>
        <div class="ppmu-kpi-tile-value ppmu-kpi-tile-value-accent">
            <span>Achieved</span>
            <strong>{{ $formatValue($achieved) }}</strong>
        </div>
    </div>

    <a href="{{ $detailUrl }}" class="ppmu-kpi-tile-btn" target="_blank" rel="noopener noreferrer" data-kpi-detail-link>
        <i class="bi bi-box-arrow-up-right"></i> View Dashboard
    </a>
</article>
