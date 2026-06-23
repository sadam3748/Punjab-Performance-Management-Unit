@props(['card', 'periodQuery' => ''])
@php
    $target = round((float) ($card->target ?? $card->total_marks ?? 0), 1);
    $achieved = round((float) ($card->achieved ?? 0), 1);
    $pct = max(0, min(100, (float) ($card->achievement_percentage ?? 0)));
    $status = strtolower((string) ($card->status_label ?? 'critical'));
    $imageUrl = asset($card->resolvedImagePath());
    $formatValue = fn (float $value) => rtrim(rtrim(number_format($value, 1, '.', ','), '0'), '.');
    $formatPct = fn (float $value) => rtrim(rtrim(number_format($value, 1), '0'), '.');
    $detailUrl = route('kpi.dashboard', $card).($periodQuery ? '?'.$periodQuery : '');
@endphp
<article class="ppmu-kpi-tile ppmu-kpi-tile-{{ $status }}" data-kpi-card aria-labelledby="kpi-title-{{ $card->id }}">
    <div class="ppmu-kpi-tile-hero">
        <img class="ppmu-kpi-tile-png" src="{{ $imageUrl }}" alt="{{ $card->title }}" loading="lazy" decoding="async">
    </div>

    <div class="ppmu-kpi-tile-foot">
        <h3 class="ppmu-kpi-tile-title" id="kpi-title-{{ $card->id }}" title="{{ $card->title }}">{{ $card->title }}</h3>

        <div class="ppmu-kpi-tile-stats">
            <div class="ppmu-kpi-stat ppmu-kpi-stat-target" title="Required operational work for the selected period">
                <strong>{{ $formatValue($target) }}</strong>
                <span>Target</span>
            </div>
            <div class="ppmu-kpi-stat ppmu-kpi-stat-accent" title="Completed operational work for the selected period">
                <strong>{{ $formatValue($achieved) }}</strong>
                <span>Achieved</span>
            </div>
            <div class="ppmu-kpi-percent-badge ppmu-kpi-pct-{{ $status }}" title="Progress = Achieved ÷ Target × 100">
                <strong>{{ $formatPct($pct) }}%</strong>
                <span>Progress</span>
            </div>
        </div>

        <a href="{{ $detailUrl }}" class="ppmu-kpi-tile-btn" target="_blank" rel="noopener noreferrer" data-kpi-detail-link title="Open KPI dashboard">
            <i class="bi bi-eye"></i> View Dashboard
        </a>
    </div>
</article>
