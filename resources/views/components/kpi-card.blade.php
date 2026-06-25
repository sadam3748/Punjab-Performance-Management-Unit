@props(['card', 'periodQuery' => ''])
@php
    $status = strtolower((string) ($card->status_label ?? 'critical'));
    $imageUrl = asset($card->resolvedImagePath());
    $detailUrl = route('kpi.dashboard', $card).($periodQuery ? '?'.$periodQuery : '');
@endphp
<article class="ppmu-kpi-tile ppmu-kpi-tile-{{ $status }}" data-kpi-card aria-labelledby="kpi-title-{{ $card->id }}">
    <div class="ppmu-kpi-tile-hero">
        <img class="ppmu-kpi-tile-png" src="{{ $imageUrl }}" alt="{{ $card->title }}" loading="lazy" decoding="async">
    </div>

    <div class="ppmu-kpi-tile-foot">
        <h3 class="ppmu-kpi-tile-title" id="kpi-title-{{ $card->id }}" title="{{ $card->title }}">{{ $card->title }}</h3>

        <a href="{{ $detailUrl }}" class="ppmu-kpi-tile-btn" target="_blank" rel="noopener noreferrer" data-kpi-detail-link title="Open KPI dashboard">
            <i class="bi bi-eye"></i> View Dashboard
        </a>
    </div>
</article>
