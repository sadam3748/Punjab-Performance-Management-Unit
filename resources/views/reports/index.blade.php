@extends('layouts.app')
@section('title', 'Reports')
@section('content_class', 'ppmu-dashboard-content')

@section('content')
<div class="ppmu-page-head">
    <div>
        <div class="ppmu-eyebrow">PPMU Reports</div>
        <h1>KPI Reports</h1>
        <p>Quick access to KPI detail dashboards for {{ $user->role?->name }} scope.</p>
    </div>
    <a href="{{ route('dashboard') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-grid-1x2 me-1"></i> Main Dashboard</a>
</div>

<div class="row g-3">
    @foreach($cards as $card)
        <div class="col-md-6 col-xl-4">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title mb-1">{{ $card->title }}</h5>
                    <p class="text-muted small mb-3">{{ $card->category }} · {{ $card->total_marks }} marks</p>
                    <a href="{{ route('kpi.dashboard', $card->slug) }}" target="_blank" rel="noopener" class="btn btn-primary btn-sm mt-auto align-self-start">
                        <i class="bi bi-box-arrow-up-right me-1"></i> View KPI Dashboard
                    </a>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection
