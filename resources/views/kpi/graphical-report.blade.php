@extends('layouts.app')

@section('title', 'KPI Graphical Report')

@section('content')
<div class="container-fluid">

    <div class="page-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="text-uppercase text-info fw-bold mb-0">
                District Wise KPI Graphical Report
            </h6>
        </div>

        <h2 class="text-center fw-bold text-danger mb-4">
            {{ $scopeTitle }}
        </h2>

        {{-- Filters --}}
        <div class="row g-3 mb-4">
            <div class="col-md-2">
                <select class="form-select">
                    <option>Functional And Clean</option>
                    <option>Filter Changed</option>
                    <option>Non-Functional</option>
                </select>
            </div>

            <div class="col-md-2">
                <select class="form-select">
                    <option>Weekly</option>
                    <option>Monthly</option>
                    <option>Yearly</option>
                </select>
            </div>

            <div class="col-md-2">
                <select class="form-select">
                    <option>19 Feb - 25 Feb</option>
                </select>
            </div>

            <div class="col-md-2">
                <select class="form-select">
                    <option>February</option>
                </select>
            </div>

            <div class="col-md-2">
                <select class="form-select">
                    <option>2026</option>
                </select>
            </div>

            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-info text-white">Apply</button>
                <button class="btn btn-info text-white">Report</button>
            </div>
        </div>

        {{-- Cards --}}
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="report-card">
                    <h2>5341</h2>
                    <p>Total Water Filtration Plants</p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="report-card split-card">
                    <div>
                        <h2>3997</h2>
                        <p>Inspected</p>
                    </div>
                    <div>
                        <h2>3732</h2>
                        <p>Functional</p>
                    </div>
                    <div>
                        <h2>265</h2>
                        <p>Non-Functional</p>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="report-card">
                    <h2>1138</h2>
                    <p>Not Inspected</p>
                </div>
            </div>
        </div>

        {{-- Charts placeholder --}}
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="chart-box">Total Water Filtration Plant Chart</div>
            </div>
            <div class="col-md-3">
                <div class="chart-box">Functional / Non-Functional Chart</div>
            </div>
            <div class="col-md-3">
                <div class="chart-box">Cleanliness Chart</div>
            </div>
            <div class="col-md-3">
                <div class="chart-box">Filter Change Chart</div>
            </div>
        </div>

        {{-- Table --}}
        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="table-report-head">
                    <tr>
                        <th>Sr. No.</th>
                        <th>Type</th>
                        <th>Name / Field 1</th>
                        <th>Address / Field 2</th>
                        <th>Tehsil</th>
                        <th>District</th>
                        <th>User</th>
                        <th>Date & Time</th>
                        <th>Actions</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1</td>
                        <td>Inspection of Water Filtration Plants</td>
                        <td>Basti Kandh</td>
                        <td>RO - Basti Kandh</td>
                        <td>MUZAFFARGARH</td>
                        <td>MUZAFFARGARH</td>
                        <td>zilacouncil.mgarh</td>
                        <td>02 March, 2026 12:28</td>
                        <td>0</td>
                        <td>
                            <button class="btn btn-sm btn-info text-white">
                                <i class="bi bi-search"></i>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

    </div>

</div>
@endsection
