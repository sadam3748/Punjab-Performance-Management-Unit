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
    <div class="ppmu-kpi-tile-hero">
        <span class="ppmu-kpi-tile-status ppmu-kpi-status-{{ $status }}">{{ $statusText }}</span>
        <img class="ppmu-kpi-tile-png" src="{{ $imageUrl }}" alt="{{ $card->title }}" width="72" height="72" loading="lazy">
    </div>

    <div class="ppmu-kpi-tile-foot">
        <h3 class="ppmu-kpi-tile-title" id="kpi-title-{{ $card->id }}" title="{{ $card->title }}">{{ $card->title }}</h3>

        <div class="ppmu-kpi-tile-achievement">
            <div class="ppmu-kpi-tile-ach-row">
                <span class="ppmu-kpi-tile-pct ppmu-kpi-pct-{{ $status }}" title="Achievement">{{ rtrim(rtrim(number_format($pct, 1), '0'), '.') }}%</span>
                <div class="ppmu-kpi-tile-progress" role="progressbar" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100" aria-label="Achievement {{ $pct }} percent">
                    <span class="ppmu-kpi-tile-progress-fill ppmu-kpi-progress-{{ $status }}" style="width: {{ $pct }}%"></span>
                </div>
            </div>
        </div>

        <div class="ppmu-kpi-tile-stats">
            <div class="ppmu-kpi-stat">
                <strong>{{ $formatValue($target) }}</strong>
                <span>Target</span>
            </div>
            <div class="ppmu-kpi-stat">
                <strong>{{ number_format($reported) }}</strong>
                <span>Reported</span>
            </div>
            <div class="ppmu-kpi-stat ppmu-kpi-stat-accent">
                <strong>{{ $formatValue($achieved) }}</strong>
                <span>Achieved</span>
            </div>
        </div>

        <a href="{{ $detailUrl }}" class="ppmu-kpi-tile-btn" target="_blank" rel="noopener noreferrer" data-kpi-detail-link title="Open KPI dashboard">
            <i class="bi bi-box-arrow-up-right"></i> View
        </a>
    </div>
</article>
