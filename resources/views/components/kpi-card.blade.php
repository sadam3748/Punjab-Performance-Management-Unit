@props(['card', 'periodQuery' => ''])
@php
    $target = round((float) ($card->target ?? $card->total_marks ?? 0), 1);
    $achieved = round((float) ($card->achieved ?? 0), 1);
    $imageUrl = asset($card->resolvedImagePath());
    $formatValue = fn (float $value) => rtrim(rtrim(number_format($value, 1, '.', ','), '0'), '.');
    $detailUrl = route('kpi.dashboard', $card).($periodQuery ? '?'.$periodQuery : '');
@endphp
<article class="ppmu-kpi-tile" data-kpi-card aria-labelledby="kpi-title-{{ $card->id }}">
    <div class="ppmu-kpi-tile-image">
        <img src="{{ $imageUrl }}" alt="{{ $card->title }}" width="140" height="140" loading="lazy">
    </div>

    <h3 class="ppmu-kpi-tile-title" id="kpi-title-{{ $card->id }}" title="{{ $card->title }}">{{ $card->title }}</h3>

    <div class="ppmu-kpi-tile-values">
        <div class="ppmu-kpi-tile-value">
            <span>Target</span>
            <strong>{{ $formatValue($target) }}</strong>
        </div>
        <div class="ppmu-kpi-tile-value ppmu-kpi-tile-value-accent">
            <span>Actual</span>
            <strong>{{ $formatValue($achieved) }}</strong>
        </div>
    </div>

    <a href="{{ $detailUrl }}" class="ppmu-kpi-tile-btn" target="_blank" rel="noopener noreferrer" data-kpi-detail-link>
        View Dashboard
    </a>
</article>
