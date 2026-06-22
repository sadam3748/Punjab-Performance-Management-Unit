@extends('layouts.app')
@section('title', 'PPMU Main KPI Dashboard')
@section('content_class', 'ppmu-dashboard-content')
@push('styles')<link rel="stylesheet" href="{{ asset('css/ppmu-kpi.css') }}?v={{ filemtime(public_path('css/ppmu-kpi.css')) }}">@endpush

@section('content')
<div class="ppmu-page-head">
    <div>
        <div class="ppmu-eyebrow">PPMU Performance Monitoring</div>
        <h1>PPMU Main KPI Dashboard</h1>
        <p>{{ $user->role?->name ?? 'User' }} · {{ $location }} · {{ $cards->count() }} KPIs</p>
    </div>
</div>

<x-period-filter :filters="$filters" :period="$period" />

<div class="ppmu-kpi-grid" id="kpiGrid">
    @forelse($cards as $card)
        <x-kpi-card :card="$card" :scope="$location" />
    @empty
        <div class="ppmu-empty">
            <i class="bi bi-grid"></i>
            <h4>No KPI cards assigned</h4>
            <p>Contact the Super Admin to configure KPI assignments for your role.</p>
        </div>
    @endforelse
</div>

@if($cards->count() > 15)
    <div class="ppmu-kpi-pager" id="kpiPager" aria-label="KPI card pages">
        <button type="button" id="kpiPrev" aria-label="Previous KPI page"><i class="bi bi-chevron-left"></i></button>
        <span id="kpiPageStatus"></span>
        <button type="button" id="kpiNext" aria-label="Next KPI page"><i class="bi bi-chevron-right"></i></button>
    </div>
@endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const cards = Array.from(document.querySelectorAll('#kpiGrid [data-kpi-card]'));
    const pager = document.getElementById('kpiPager');
    if (!pager || !cards.length) return;

    const previous = document.getElementById('kpiPrev');
    const next = document.getElementById('kpiNext');
    const status = document.getElementById('kpiPageStatus');
    let page = 1;
    const pageSize = () => window.innerWidth < 600 ? 6 : (window.innerWidth < 900 ? 10 : 15);

    function render() {
        const size = pageSize();
        const pages = Math.max(1, Math.ceil(cards.length / size));
        page = Math.min(page, pages);
        cards.forEach((card, index) => card.hidden = index < (page - 1) * size || index >= page * size);
        status.textContent = 'KPIs ' + (((page - 1) * size) + 1) + '-' + Math.min(page * size, cards.length) + ' of ' + cards.length;
        previous.disabled = page === 1;
        next.disabled = page === pages;
    }

    previous.addEventListener('click', () => { page--; render(); });
    next.addEventListener('click', () => { page++; render(); });
    window.addEventListener('resize', render);
    render();
});
</script>
@endpush
