@extends('layouts.app')

@section('title', 'Provincial KPI Wise Data | PPMF Portal')
@section('page_title', 'Provincial KPI Wise Data')

@section('content')

<div class="page-actions-ppmf">
  <div>
    <h2>Provincial KPI Wise Data</h2>
    <p>Province-level KPI data summary for current week, last week, last four weeks, or custom date range.</p>
  </div>

  <div class="d-flex gap-2">
    <button class="btn btn-outline-success">
      <i class="bi bi-file-earmark-excel"></i> Export Excel
    </button>
    <button class="btn btn-outline-danger">
      <i class="bi bi-file-earmark-pdf"></i> Export PDF
    </button>
    <button class="btn btn-success">
      <i class="bi bi-printer"></i> Print
    </button>
  </div>
</div>

{{-- Filters --}}
<div class="filter-card-ppmf mb-4">
  <div class="filter-title">
    <i class="bi bi-funnel"></i> Provincial Data Filters
  </div>

  <div class="row g-3 align-items-end">
    <div class="col-md-3">
      <label class="form-label">Data Period</label>
      <select class="form-select">
        <option selected>Current Week</option>
        <option>Last Week</option>
        <option>Last Four Weeks</option>
        <option>Custom Date</option>
      </select>
    </div>

    <div class="col-md-3">
      <label class="form-label">KPI Group</label>
      <select class="form-select">
        <option selected>All KPI Groups</option>
        <option>Price Control</option>
        <option>Municipal Services</option>
        <option>Education</option>
        <option>Health</option>
        <option>Law & Order</option>
      </select>
    </div>

    <div class="col-md-2">
      <label class="form-label">From</label>
      <input type="date" class="form-control" value="2026-04-30">
    </div>

    <div class="col-md-2">
      <label class="form-label">To</label>
      <input type="date" class="form-control" value="2026-05-06">
    </div>

    <div class="col-md-2 d-flex gap-2">
      <button class="btn btn-success flex-fill">
        <i class="bi bi-search"></i> Apply
      </button>
      <button class="btn btn-outline-secondary">
        <i class="bi bi-x-circle"></i>
      </button>
    </div>
  </div>
</div>

{{-- Summary Cards --}}
<div class="row g-3 mb-4">
  <div class="col-md-3">
    <div class="stat-card-ppmf border-success">
      <span>Total KPI Groups</span>
      <strong>21</strong>
      <small>Province-wide categories</small>
    </div>
  </div>

  <div class="col-md-3">
    <div class="stat-card-ppmf border-primary">
      <span>Total Submitted Value</span>
      <strong>5,144</strong>
      <small>Across selected period</small>
    </div>
  </div>

  <div class="col-md-3">
    <div class="stat-card-ppmf border-warning">
      <span>Active Indicators</span>
      <strong>47</strong>
      <small>KPI indicators with data</small>
    </div>
  </div>

  <div class="col-md-3">
    <div class="stat-card-ppmf border-danger">
      <span>Pending Indicators</span>
      <strong>12</strong>
      <small>Need departmental update</small>
    </div>
  </div>
</div>

{{-- KPI Data Sections --}}
<div class="provincial-kpi-wrapper">

  @php
    $kpiGroups = [
      [
        'title' => 'Price of Roti',
        'items' => [
          ['value' => '77', 'text' => 'DCs twice weekly review with all PCMs, Food Department and Special Branch about enforcement of Rate of Roti.'],
          ['value' => '0', 'text' => 'Inspections of Tandoors to be conducted by ACs/PCMs daily as per tier-wise targets.'],
          ['value' => '0', 'text' => 'Special Coverage and Mobility Index for ACs/PCMs.'],
          ['value' => '0', 'text' => 'Imposition of Fine on violations over price, weight, or non-availability of Roti.'],
          ['value' => '0', 'text' => 'Action taken on the complaints by the citizen.'],
        ]
      ],
      [
        'title' => 'Price of Plain Bakery Bread',
        'items' => [
          ['value' => '0', 'text' => 'Inspections of bread/plain bakery products to be conducted by ACs/PCMs daily as per target value.'],
          ['value' => '0', 'text' => 'Special Coverage and Mobility Index for ACs/PCMs.'],
          ['value' => '0', 'text' => 'Imposition of Fine on violations over price and non-availability of plain bread.'],
          ['value' => '0', 'text' => 'Action taken on the complaints by the citizen.'],
        ]
      ],
      [
        'title' => 'Price Control of Essential Commodities',
        'items' => [
          ['value' => '0', 'text' => 'Price violations reported by Special Branch in the district.'],
          ['value' => '6,925', 'text' => 'Action against violations reported by Special Branch.'],
          ['value' => '0', 'text' => 'Inspection of sale points to be conducted by ACs/PCMs daily as per tier-wise targets.'],
          ['value' => '0', 'text' => 'Special Coverage and Mobility Index for ACs/PCMs.'],
          ['value' => '0', 'text' => 'Action against violations reported by Citizen.'],
          ['value' => '0', 'text' => 'Imposition of fine on violations.'],
        ]
      ],
      [
        'title' => 'Repair of Small Roads in Both Urban and Rural Areas',
        'items' => [
          ['value' => '0', 'text' => 'Weekly low scale roads to be maintained by district.'],
          ['value' => '0', 'text' => 'Action on roads as reported by Special Branch in the district.'],
        ]
      ],
      [
        'title' => 'Zebra Crossings',
        'items' => [
          ['value' => '0', 'text' => 'Inspection, marking, and repair status of educational institutions by ACs.'],
          ['value' => '0', 'text' => 'Action on zebra crossings as reported by Special Branch.'],
        ]
      ],
      [
        'title' => 'Dysfunctional Streetlights',
        'items' => [
          ['value' => '0', 'text' => 'Total number of streetlights identified as dysfunctional.'],
          ['value' => '0', 'text' => 'Action on dysfunctional streetlights as reported by Special Branch.'],
        ]
      ],
      [
        'title' => 'Covering of Manholes',
        'items' => [
          ['value' => '0', 'text' => 'UC-wise uncovered manholes reported by Special Branch.'],
          ['value' => '0', 'text' => 'Action on uncovered/open manholes reported by Special Branch in district.'],
        ]
      ],
      [
        'title' => 'Functional and Clean Water Filtration Plants',
        'items' => [
          ['value' => '0', 'text' => 'Regular cleaning of filter and operating hours of functional filtration plants.'],
          ['value' => '0', 'text' => 'Inspection of water filtration plants as functional.'],
        ]
      ],
      [
        'title' => 'Inspection of Educational Institutions',
        'items' => [
          ['value' => '0', 'text' => 'Weekly inspection visits conducted in schools following cleanliness and general condition checklist.'],
          ['value' => '0', 'text' => 'Weekly low marks of AC visits in educational institutions.'],
          ['value' => '37', 'text' => 'Meetings of DEOs and schools conducted.'],
        ]
      ],
      [
        'title' => 'Inspection of Health Facilities',
        'items' => [
          ['value' => '0', 'text' => 'Weekly inspection visits conducted in health facilities following checklist.'],
          ['value' => '0', 'text' => 'Weekly low marks of AC visits in health facilities.'],
          ['value' => '37', 'text' => 'Meetings of CEOs and health facilities conducted.'],
          ['value' => '0', 'text' => 'Action on inspection made by Special Branch.'],
        ]
      ],
      [
        'title' => 'Violation of Marriage Functions Act',
        'items' => [
          ['value' => '0', 'text' => 'Number of inspections made by ACs and MOs authorized by DC.'],
          ['value' => '0', 'text' => 'Cases for one dish rule and time violations.'],
        ]
      ],
      [
        'title' => 'Anti-Encroachment Campaign',
        'items' => [
          ['value' => '0', 'text' => 'Operations of at least one market in one week.'],
          ['value' => '0', 'text' => 'Action on encroachments as reported by Special Branch.'],
        ]
      ],
      [
        'title' => 'Stray Dogs',
        'items' => [
          ['value' => '0', 'text' => 'Number of UC-wise activities of culling of stray dogs.'],
          ['value' => '0', 'text' => 'Complaints against stray dogs as reported by Special Branch.'],
        ]
      ],
      [
        'title' => 'Removal of Wall Chalking',
        'items' => [
          ['value' => '0', 'text' => 'Total number of urban UCs and rural UCs where wall chalking was removed.'],
          ['value' => '0', 'text' => 'Action against violations reported by Special Branch.'],
        ]
      ],
      [
        'title' => 'Graveyards',
        'items' => [
          ['value' => '0', 'text' => 'All local graveyards to be inspected once a week for cleanliness and maintenance.'],
          ['value' => '0', 'text' => 'Action on graveyards as reported by Special Branch.'],
        ]
      ],
      [
        'title' => 'Illegal Decanting',
        'items' => [
          ['value' => '0', 'text' => 'At least 20% of sale points inspections made by ACs.'],
          ['value' => '0', 'text' => 'Action taken on sale points for illegal decanting.'],
          ['value' => '0', 'text' => 'Action against violations reported by Special Branch in district.'],
          ['value' => '561', 'text' => 'Closure for one week in case of repeated violations.'],
        ]
      ],
      [
        'title' => 'Suthra Punjab Campaign',
        'items' => [
          ['value' => '0', 'text' => 'Weekly Tehsil inspection conducted by the DC in any of the two UCs.'],
          ['value' => '0', 'text' => 'Action on violations reported by Special Branch in district.'],
          ['value' => '0', 'text' => 'Weekly UCs inspection conducted by AC in any of the UCs.'],
        ]
      ],
      [
        'title' => 'Maintenance of Greenbelts & DCs Initiatives on Beautification',
        'items' => [
          ['value' => '0', 'text' => 'Number of parks fully maintained in a district.'],
          ['value' => '0', 'text' => 'Number of Tehsils and Towns where greenbelts are maintained.'],
          ['value' => '0', 'text' => 'Action on greenbelts as reported by Special Branch.'],
          ['value' => '0', 'text' => 'DCs initiatives of at least one beautification activity.'],
        ]
      ],
      [
        'title' => 'Maintenance of Drains and Sewerage Lines',
        'items' => [
          ['value' => '4,251', 'text' => 'Total number of urban UCs, rural UCs and MCs where sewerage line cleaning was reported.'],
          ['value' => '0', 'text' => 'Urban UCs, rural UCs and MCs with blocked/choked sewerage lines, stagnant water indicated by Special Branch.'],
        ]
      ],
      [
        'title' => 'Bus Terminals',
        'items' => [
          ['value' => '0', 'text' => 'Weekly visits of General Bus Stand, C-Class Stands and improved arrangements.'],
          ['value' => '0', 'text' => 'Action on bus terminals as reported by Special Branch.'],
        ]
      ],
      [
        'title' => "Chief Minister's Complaint Cell",
        'items' => [
          ['value' => '0', 'text' => 'Complaints received.'],
        ]
      ],
      [
        'title' => 'Regulation of Shops and Handcarts',
        'items' => [
          ['value' => '0', 'text' => 'Inspection of at least one market in one working day in each Tehsil.'],
        ]
      ],
      [
        'title' => 'E-Biz',
        'items' => [
          ['value' => '0', 'text' => 'Constitution of all mechanisms covered.'],
          ['value' => '0', 'text' => 'DCs initiatives to solve issues and liaison with relevant departments.'],
          ['value' => '19', 'text' => 'Number of requests made/received by relevant departments/organizations.'],
        ]
      ],
    ];
  @endphp

  @foreach($kpiGroups as $group)
    <div class="provincial-kpi-section">
      <div class="provincial-kpi-heading">
        <h5>{{ $group['title'] }}</h5>
        <span>{{ count($group['items']) }} Indicators</span>
      </div>

      <div class="row g-3">
        @foreach($group['items'] as $item)
          <div class="col-xl-4 col-md-6">
            <div class="provincial-kpi-card">
              <strong>{{ $item['value'] }}</strong>
              <p>{{ $item['text'] }}</p>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  @endforeach

</div>

@endsection
