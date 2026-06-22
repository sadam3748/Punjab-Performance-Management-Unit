@extends('layouts.app')
@section('title','Manage KPI Cards')
@push('styles')<link rel="stylesheet" href="{{ asset('css/ppmu-kpi.css') }}">@endpush
@section('content')
<div class="ppmu-page-head"><div><div class="ppmu-eyebrow">Administration</div><h1>Manage KPI Cards</h1><p>Create, configure and activate KPI cards for the PPMU dashboard.</p></div><a href="{{ route('manage-kpis.create') }}" class="btn btn-success"><i class="bi bi-plus-circle me-2"></i>Create KPI Card</a></div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="card-ppmf"><div class="table-responsive"><table class="table-ppmf ppmu-table"><thead><tr><th>Order</th><th>KPI</th><th>Category</th><th>Frequency</th><th>Marks</th><th>Fields</th><th>Assignments</th><th>Submissions</th><th>Status</th><th></th></tr></thead><tbody>@foreach($cards as $card)<tr><td>{{ $card->display_order }}</td><td><strong><i class="bi {{ $card->icon }} me-2 text-success"></i>{{ $card->title }}</strong><small>{{ $card->slug }}</small></td><td>{{ $card->category }}</td><td>{{ ucfirst($card->frequency) }}</td><td>{{ number_format((float)$card->total_marks) }}</td><td>{{ $card->form_fields_count }}</td><td>{{ $card->assignments_count }}</td><td>{{ $card->submissions_count }}</td><td><x-status-badge :status="$card->is_active ? 'active' : 'inactive'"/></td><td><a class="btn btn-sm btn-outline-success" href="{{ route('manage-kpis.edit',$card) }}">Edit</a></td></tr>@endforeach</tbody></table></div><div class="p-3">{{ $cards->links() }}</div></div>
@endsection
