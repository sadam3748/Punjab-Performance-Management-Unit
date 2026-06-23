@props(['card'])
@php
    $target = round((float) ($card->target ?? $card->total_marks ?? 0), 1);
    $reported = (int) ($card->reported ?? 0);
    $achieved = round((float) ($card->achieved ?? 0), 1);
    $pct = round((float) ($card->achievement_percentage ?? 0), 1);
    $statusLabel = $card->status_label ?? 'pending';
    $imageUrl = asset($card->resolvedImagePath());
    $formatValue = fn (float $value) => rtrim(rtrim(number_format($value, 1, '.', ','), '0'), '.');
    $tone = match (true) {
        $pct >= 85 => 'excellent',
        $pct >= 70 => 'good',
        $pct >= 50 => 'attention',
        default => 'critical',
    };
@endphp
<article class="ppmu-kpi-card ppmu-kpi-card--{{ $tone }}" data-kpi-card aria-labelledby="kpi-title-{{ $card->id }}">
    <span class="ppmu-kpi-status badge rounded-pill text-bg-{{ match($tone) { 'excellent' => 'success', 'good' => 'primary', 'attention' => 'warning', default => 'danger' } }}">
        {{ ucfirst($statusLabel) }}
    </span>

    <div class="ppmu-kpi-image-wrap">
        <img src="{{ $imageUrl }}" alt="{{ $card->title }}" class="ppmu-kpi-image" width="135" height="135" loading="lazy">
    </div>

    <h3 class="ppmu-kpi-title" id="kpi-title-{{ $card->id }}" title="{{ $card->title }}">{{ $card->title }}</h3>

    <div class="ppmu-kpi-achievement">{{ $pct }}% Achievement</div>

    <div class="ppmu-kpi-progress" role="progressbar" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100">
        <div class="ppmu-kpi-progress-fill" style="width: {{ min(100, $pct) }}%"></div>
    </div>

    <div class="ppmu-kpi-stats">
        <div class="ppmu-kpi-stat">
            <span>Target</span>
            <strong>{{ $formatValue($target) }}</strong>
        </div>
        <div class="ppmu-kpi-stat">
            <span>Reported</span>
            <strong>{{ number_format($reported) }}</strong>
        </div>
        <div class="ppmu-kpi-stat">
            <span>Achieved</span>
            <strong>{{ $formatValue($achieved) }}</strong>
        </div>
    </div>

    <a href="{{ route('kpi.dashboard', $card) }}" class="ppmu-kpi-action" target="_blank" rel="noopener noreferrer">
        View Dashboard <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
    </a>
</article>
