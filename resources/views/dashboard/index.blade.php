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
        <x-kpi-card :card="$card" />
    @empty
        <div class="ppmu-empty">
            <i class="bi bi-grid"></i>
            <h4>No KPI cards assigned</h4>
            <p>Contact the Super Admin to configure KPI assignments for your role.</p>
        </div>
    @endforelse
</div>

@endsection
