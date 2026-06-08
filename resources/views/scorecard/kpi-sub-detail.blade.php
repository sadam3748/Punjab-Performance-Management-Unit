@extends('layouts.app')

@section('title', 'KPI Sub-Detail | PPMF Portal')
@section('page_title', 'KPI Sub-Detail')

@php
    $filters = array_merge($filters ?? [], ['week_no' => $weekNo ?? null]);
    $scorePercentage = (float) ($summary['score_percentage'] ?? 0);
    $performance = $summary['performance'] ?? 'Unreported';
    $grade = $summary['grade'] ?? '—';
    $performanceClass = $score
        ? ($scorePercentage >= 90 ? 'excellent' : ($scorePercentage >= 70 ? 'good' : ($scorePercentage >= 50 ? 'average' : 'critical')))
        : 'secondary';
@endphp

@push('styles')
<style>
    .ksd-grid{display:grid;gap:12px;margin-bottom:18px;grid-template-columns:repeat(1,minmax(0,1fr))}
    @media(min-width:576px){.ksd-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
    @media(min-width:1200px){.ksd-grid{grid-template-columns:repeat(4,minmax(0,1fr))}}
    .ksd-stat{position:relative;background:#fff;border:1px solid rgba(15,23,42,.08);border-radius:18px;padding:17px;overflow:hidden;box-shadow:0 12px 28px rgba(15,23,42,.06);min-height:116px}
    .ksd-stat:before{content:"";position:absolute;inset:0 0 auto;height:4px;background:var(--accent,#166534)}
    .ksd-stat .label{font-size:11px;font-weight:900;text-transform:uppercase;letter-spacing:.055em;color:#64748b}
    .ksd-stat .value{display:flex;align-items:center;min-height:32px;margin-top:7px;color:#0f172a;font-size:25px;font-weight:950;line-height:1}
    .ksd-stat .help{margin-top:6px;color:#64748b;font-size:12px;font-weight:700}
    .ksd-performance-badge{display:inline-flex;align-items:center;border-radius:999px;padding:8px 13px;font-size:13px;font-weight:900;line-height:1.15}
    .ksd-performance-badge.excellent{background:#dcfce7;color:#14532d;border:1px solid #86efac}
    .ksd-performance-badge.good{background:#dbeafe;color:#1e40af;border:1px solid #93c5fd}
    .ksd-performance-badge.average{background:#ffedd5;color:#9a3412;border:1px solid #fdba74}
    .ksd-performance-badge.critical{background:#fee2e2;color:#991b1b;border:1px solid #fca5a5}
    .ksd-performance-badge.secondary{background:#f1f5f9;color:#475569;border:1px solid #cbd5e1}
    .formula-help-card{background:#f8fffb;border:1px solid rgba(0,104,56,.16);border-radius:14px;padding:15px 17px;margin-bottom:18px}
    .formula-help-card .title{color:#14532d;font-size:14px;font-weight:900;margin-bottom:5px}
    .formula-help-card .formula{display:flex;flex-wrap:wrap;gap:8px 18px;margin-top:8px;color:#334155;font-size:12px;font-weight:800}
    .sub-kpi-detail-table{width:100%;min-width:1320px;table-layout:auto}
    .sub-kpi-detail-table th{white-space:normal;overflow-wrap:normal;word-break:normal;line-height:1.25;vertical-align:middle;font-size:10.5px;padding:12px 9px}
    .sub-kpi-detail-table td{white-space:normal;overflow-wrap:anywhere;vertical-align:middle;font-size:12px;padding:12px 9px}
    .sub-kpi-detail-table .parameter-cell{min-width:230px}
    .sub-kpi-detail-table .formula-type-cell{min-width:145px}
    .sub-kpi-detail-table .formula-cell{min-width:210px;font-size:11px;line-height:1.45;color:#475569}
    .sub-kpi-detail-table .numeric-cell{text-align:right;font-variant-numeric:tabular-nums;min-width:105px}
    .sub-kpi-detail-table .value-cell{min-width:145px}
    .sub-kpi-detail-table .score-cell{font-weight:900;color:#067647}
    .sub-kpi-detail-table .evidence-cell{min-width:135px}
    .sub-kpi-detail-table tfoot th{vertical-align:middle}
    .ksd-pagination{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:14px 18px;border-top:1px solid #e2e8f0}
    .ksd-pagination .pagination{margin:0;gap:6px;flex-wrap:wrap}
    .ksd-pagination .page-link{border-radius:10px;color:#14532d;font-weight:900}
    .ksd-pagination .page-item.active .page-link{background:#166534;border-color:#166534;color:#fff}
    @media(min-width:1400px){
        .sub-kpi-detail-table{min-width:100%;table-layout:fixed}
        .sub-kpi-detail-table .parameter-cell,.sub-kpi-detail-table .formula-type-cell,.sub-kpi-detail-table .formula-cell,.sub-kpi-detail-table .numeric-cell,.sub-kpi-detail-table .value-cell,.sub-kpi-detail-table .evidence-cell{min-width:0}
    }
    @media(max-width:1399px){.sub-kpi-detail-table{min-width:1260px}}
    @media(max-width:991px){.sub-kpi-detail-table{min-width:1220px}.ksd-pagination{align-items:flex-start;flex-direction:column}}
</style>
@endpush

@section('content')
<div class="page-title-bar mb-4 d-flex align-items-start justify-content-between gap-3 flex-wrap">
    <div>
        <h2 class="page-title mb-1">{{ $district->name }} - {{ $kpiCategory->name }} Sub-KPI Detail</h2>
        <p class="page-subtitle mb-0">Formula-wise calculation for selected KPI and reporting week: {{ $periodLabel }}</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('scorecard.district-detail', array_merge($filters, ['district' => $district])) }}" class="btn btn-gov btn-gov-outline">
            <i class="bi bi-arrow-left"></i> Back to District Detail
        </a>
        <a href="{{ route('scorecard.index', $filters) }}" class="btn btn-gov btn-gov-outline">
            <i class="bi bi-table"></i> District Wise
        </a>
        <a href="{{ route('scorecard.tier', $filters) }}" class="btn btn-gov btn-gov-outline">
            <i class="bi bi-layers"></i> Tier Wise
        </a>
    </div>
</div>

<div class="ksd-grid">
    <div class="ksd-stat" style="--accent:#166534">
        <div class="label">KPI Weightage</div>
        <div class="value">{{ number_format((float)($summary['weightage'] ?? 0), 2) }}</div>
        <div class="help">Maximum category marks</div>
    </div>
    <div class="ksd-stat" style="--accent:#0f766e">
        <div class="label">KPI Marks Obtained</div>
        <div class="value">{{ number_format((float)($summary['marks_obtained'] ?? 0), 2) }}</div>
        <div class="help">Sum of calculated sub-KPI marks</div>
    </div>
    <div class="ksd-stat" style="--accent:#2563eb">
        <div class="label">KPI Score %</div>
        <div class="value">{{ $score ? number_format($scorePercentage, 2).'%' : '—' }}</div>
        <div class="help">Final score for selected week</div>
    </div>
    <div class="ksd-stat" style="--accent:#f59e0b">
        <div class="label">Performance / Grade</div>
        <div class="value"><span class="ksd-performance-badge {{ $performanceClass }}">{{ $performance }} {{ $grade !== '—' ? '('.$grade.')' : '' }}</span></div>
        <div class="help">{{ ucfirst(str_replace('_', ' ', $filters['calculation_type'] ?? 'general')) }} calculation</div>
    </div>
</div>

<div class="formula-help-card">
    <div class="title"><i class="bi bi-info-circle me-1"></i> How this KPI score is calculated</div>
    <div class="text-muted small">Each row is one sub-KPI. The backend compares the actual value with its target, applies the PPT-defined weightage, and calculates marks. Score cannot exceed the sub-KPI weightage.</div>
    <div class="formula">
        <span>Sub-KPI Score = Actual &divide; Target &times; Weightage</span>
        <span>KPI Score = Sum of all Sub-KPI Scores</span>
        <span>Example: 8 &divide; 10 &times; 3 = 2.4 marks</span>
    </div>
</div>

<div class="card-ppmf">
    <div class="card-ppmf-header">
        <div>
            <div class="card-ppmf-title"><i class="bi bi-calculator"></i> Sub-KPI Formula Calculation Detail</div>
            <div class="text-muted small mt-1">Only active PPT-approved formula details are shown.</div>
        </div>
    </div>
    <div class="card-ppmf-body p-0">
        <div class="table-responsive">
            <table class="table-ppmf mb-0 sub-kpi-detail-table">
                <thead>
                    <tr>
                        <th style="width:70px">Sr. No.</th>
                        <th style="width:260px">Sub-KPI / Parameter</th>
                        <th style="width:150px">Formula Type</th>
                        <th style="width:220px">Formula</th>
                        <th class="numeric-cell" style="width:150px">Actual / Numerator</th>
                        <th class="numeric-cell" style="width:150px">Target / Denominator</th>
                        <th class="numeric-cell" style="width:110px">Achieved %</th>
                        <th class="numeric-cell" style="width:110px">Weightage</th>
                        <th class="numeric-cell" style="width:130px">Score Obtained</th>
                        <th style="width:140px">Evidence / Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($details as $detail)
                        <tr>
                            <td class="text-center fw-bold">{{ ($details->firstItem() ?? 1) + $loop->index }}</td>
                            <td class="fw-bold parameter-cell">{{ $detail['parameter_name'] }}</td>
                            <td class="formula-type-cell"><span class="badge-ppmf badge-ppmf-info">{{ $detail['formula_label'] }}</span></td>
                            <td class="formula-cell">{{ $detail['formula_expression'] ?: 'Configured PPT formula' }}</td>
                            <td class="numeric-cell value-cell">
                                <div class="fw-bold">{{ number_format((float)($detail['numerator_value'] ?? 0), 2) }}</div>
                                <small class="text-muted">{{ $detail['numerator_label'] ?: 'Reported value' }}</small>
                            </td>
                            <td class="numeric-cell value-cell">
                                <div class="fw-bold">{{ $detail['denominator_value'] === null ? '—' : number_format((float)$detail['denominator_value'], 2) }}</div>
                                <small class="text-muted">{{ $detail['denominator_label'] ?: 'Configured target' }}</small>
                            </td>
                            <td class="numeric-cell fw-bold">{{ number_format((float)$detail['achieved_percentage'], 2) }}%</td>
                            <td class="numeric-cell fw-bold">{{ number_format((float)$detail['weightage'], 2) }}</td>
                            <td class="numeric-cell score-cell">{{ number_format((float)$detail['score_obtained'], 2) }}</td>
                            <td class="evidence-cell">{{ $detail['evidence'] ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted p-4">No sub-KPI calculation detail found for this KPI and selected period.</td>
                        </tr>
                    @endforelse
                </tbody>
                @if($details->total() > 0)
                    <tfoot>
                        <tr>
                            <th colspan="6">Total / KPI Percentage</th>
                            <th class="numeric-cell">{{ number_format((float)($summary['score_percentage'] ?? 0), 2) }}%</th>
                            <th class="numeric-cell">{{ number_format((float)($summary['total_weightage'] ?? 0), 2) }}</th>
                            <th class="numeric-cell score-cell">{{ number_format((float)($summary['marks_obtained'] ?? 0), 2) }}</th>
                            <th></th>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
    @if($details->hasPages())
        <div class="card-ppmf-body ksd-pagination">
            <div class="text-muted small fw-bold">Showing {{ $details->firstItem() }} to {{ $details->lastItem() }} of {{ $details->total() }} sub-KPI(s)</div>
            <div>{{ $details->links('pagination::bootstrap-5') }}</div>
        </div>
    @endif
</div>
@endsection
