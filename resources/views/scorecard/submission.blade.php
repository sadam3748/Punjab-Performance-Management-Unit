@extends('layouts.app')

@section('title', 'Submit KPI Scorecard Data')

@section('content')
<div class="page-title-bar">
    <div>
        <h1 class="page-title">Submit PPT Sub-KPI Data</h1>
        <p class="page-subtitle">{{ $district->name }} · {{ $kpiCategory->name }}</p>
    </div>
    <div class="page-title-actions">
        <a href="{{ route('scorecard.district-detail', ['district' => $district, 'kpi_category_id' => $kpiCategory->id]) }}" class="btn-gov btn-gov-outline">
            <i class="bi bi-arrow-left"></i> Back to District Scorecard
        </a>
    </div>
</div>

@if($errors->any())
    <div class="alert alert-danger">
        <strong>Please correct the submission:</strong>
        <ul class="mb-0 mt-1">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('scorecard.submission.store', [$district, $kpiCategory]) }}">
    @csrf

    <div class="card-ppmf mb-4">
        <div class="card-ppmf-header"><div class="card-ppmf-title"><i class="bi bi-calendar-week"></i> Reporting Context</div></div>
        <div class="card-ppmf-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Week Number</label>
                    <input name="week_no" class="form-control" value="{{ old('week_no', $weekNo) }}" required>
                </div>
                <input type="hidden" name="calculation_type" value="general">
                @foreach([
                    'working_days' => 'Working Days',
                    'educational_institutions' => 'Total Educational Institutions',
                    'lpg_sale_points' => 'Total LPG Sale Points',
                    'inspections_count' => 'Total Inspections for 15% Action Target',
                ] as $key => $label)
                    @if($requiredContextFields->contains($key))
                        <div class="col-md-4">
                            <label class="form-label">{{ $label }}</label>
                            <input type="number" step="0.01" min="0" name="context[{{ $key }}]" class="form-control" value="{{ old('context.'.$key, $contextDefaults[$key] ?? null) }}" required>
                            <div class="form-text">Used to calculate the PPT target automatically.</div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>

    <div class="card-ppmf">
        <div class="card-ppmf-header"><div class="card-ppmf-title"><i class="bi bi-calculator"></i> Active PPT Sub-KPIs</div></div>
        <div class="card-ppmf-body p-0">
            <div class="table-responsive">
                <table class="table-ppmf mb-0">
                    <thead>
                        <tr>
                            <th>Sub-KPI / Formula</th>
                            <th>Weightage</th>
                            <th>Actual Numerator</th>
                            <th>Denominator / Target</th>
                            <th>Calculated Result</th>
                            <th>Evidence</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($parameters as $index => $parameter)
                            @php
                                $meta = $parameterMeta->get((int) $parameter->id, []);
                                $existing = $existingDetails->get((int) $parameter->id);
                            @endphp
                            <tr>
                                <td>
                                    <input type="hidden" name="details[{{ $index }}][kpi_scoring_parameter_id]" value="{{ $parameter->id }}">
                                    <div class="fw-bold">{{ $parameter->parameter_name }}</div>
                                    <div class="text-muted small">{{ $parameter->formula_expression ?: $parameter->description }}</div>
                                    <div class="text-muted small">{{ $parameter->numerator_label }} / {{ $parameter->denominator_label ?: 'configured target' }}</div>
                                </td>
                                <td>{{ number_format((float)$parameter->weightage, 2) }}</td>
                                <td>
                                    @if($meta['is_yes_no'] ?? false)
                                        <select name="details[{{ $index }}][numerator]" class="form-select" required>
                                            <option value="">Select Yes or No</option>
                                            <option value="1" @selected((string) old('details.'.$index.'.numerator', $existing?->numerator_value) === '1')>Yes</option>
                                            <option value="0" @selected((string) old('details.'.$index.'.numerator', $existing?->numerator_value) === '0')>No</option>
                                        </select>
                                    @else
                                        <input type="number" step="0.01" min="0" name="details[{{ $index }}][numerator]" class="form-control" value="{{ old('details.'.$index.'.numerator', $existing?->numerator_value) }}" required>
                                    @endif
                                </td>
                                <td>
                                    @if($meta['needs_denominator'] ?? false)
                                        <input type="number" step="0.01" min="0.01" name="details[{{ $index }}][denominator]" class="form-control" value="{{ old('details.'.$index.'.denominator', $existing?->denominator_value) }}" placeholder="Enter target value" required>
                                    @elseif($meta['required_context'] ?? [])
                                        <span class="badge-ppmf badge-ppmf-info">Calculated from reporting context</span>
                                    @elseif($meta['is_yes_no'] ?? false)
                                        <span class="text-muted">Yes = full marks, No = 0</span>
                                    @else
                                        <span class="fw-semibold">{{ number_format((float) ($meta['target'] ?? $existing?->denominator_value ?? 0), 2) }}</span>
                                        <div class="text-muted small">Configured PPT/tier target</div>
                                    @endif
                                </td>
                                <td>
                                    @if($existing)
                                        <div class="fw-semibold">{{ number_format((float) $existing->score_obtained, 2) }} / {{ number_format((float) $existing->weightage, 2) }}</div>
                                        <div class="text-muted small">{{ number_format((float) $existing->achieved_percentage, 2) }}% achieved</div>
                                    @else
                                        <span class="text-muted">Shown after submission</span>
                                    @endif
                                </td>
                                <td><input type="text" name="details[{{ $index }}][evidence]" class="form-control" value="{{ old('details.'.$index.'.evidence', $existing?->evidence) }}"></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-3 border-top">
                <div class="text-muted small mb-2">Calculated marks cannot exceed each sub-KPI's maximum weightage.</div>
                <button class="btn-gov btn-gov-primary" type="submit"><i class="bi bi-save"></i> Calculate and Submit Scorecard</button>
            </div>
        </div>
    </div>
</form>
@endsection
