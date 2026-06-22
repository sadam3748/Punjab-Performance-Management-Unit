@extends('layouts.app')
@section('title', 'Settings')
@section('page_title', 'Settings')

@section('content')
<div class="panel-card">
    <div class="panel-header">
        <div><h5>Settings</h5><p>Portal configuration and profile settings</p></div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-success btn-sm">Export</button>
            <button class="btn btn-success btn-sm">Add New</button>
        </div>
    </div>

    <div class="filter-card compact mb-3">
        <div class="row g-3">
            <div class="col-md-3"><input class="form-control" placeholder="Search"></div>
            <div class="col-md-3"><select class="form-select"><option>All Divisions</option><option>Lahore</option><option>Rawalpindi</option></select></div>
            <div class="col-md-3"><select class="form-select"><option>All Status</option><option>Excellent</option><option>Average</option><option>Low</option></select></div>
            <div class="col-md-3"><button class="btn btn-success w-100">Apply Filter</button></div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table ppmf-table align-middle">
            <thead>
                <tr><th>Name</th><th>Division/Department</th><th>Total KPIs</th><th>Achieved</th><th>Score</th><th>Status</th><th>Action</th></tr>
            </thead>
            <tbody>
                <tr><td>Lahore</td><td>Lahore</td><td>46</td><td>42</td><td>91%</td><td><span class="badge bg-success-subtle text-success">Excellent</span></td><td><button class="btn btn-sm btn-outline-success">View</button></td></tr>
                <tr><td>Faisalabad</td><td>Faisalabad</td><td>45</td><td>39</td><td>86%</td><td><span class="badge bg-success-subtle text-success">Good</span></td><td><button class="btn btn-sm btn-outline-success">View</button></td></tr>
                <tr><td>DG Khan</td><td>DG Khan</td><td>40</td><td>19</td><td>48%</td><td><span class="badge bg-danger-subtle text-danger">Low</span></td><td><button class="btn btn-sm btn-outline-success">View</button></td></tr>
            </tbody>
        </table>
    </div>
</div>
@endsection
