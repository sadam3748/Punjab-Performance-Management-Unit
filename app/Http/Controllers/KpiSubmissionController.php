<?php

namespace App\Http\Controllers;

use App\Models\KpiCard;
use App\Models\KpiScore;
use App\Models\KpiSubmission;
use App\Services\KpiDashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KpiSubmissionController extends Controller
{
    public function create(Request $request, KpiCard $kpiCard, KpiDashboardService $service)
    {
        abort_unless($service->canAccess($request->user(), $kpiCard), 403);
        $kpiCard->load('formFields');
        return view('submissions.create', compact('kpiCard'));
    }

    public function store(Request $request, KpiCard $kpiCard, KpiDashboardService $service)
    {
        abort_unless($service->canAccess($request->user(), $kpiCard), 403);
        $kpiCard->load('formFields');
        $rules = ['period_type' => ['required', 'in:daily,weekly,monthly,yearly'], 'period_label' => ['required', 'string', 'max:100'], 'submission_date' => ['required', 'date'], 'remarks' => ['nullable', 'string']];
        foreach ($kpiCard->formFields as $field) {
            $rules['fields.'.$field->id] = array_filter([$field->is_required ? 'required' : 'nullable', $field->field_type === 'number' ? 'numeric' : 'string']);
        }
        $data = $request->validate($rules);
        $user = $request->user();

        DB::transaction(function () use ($data, $user, $kpiCard) {
            $submission = KpiSubmission::create([
                'kpi_card_id' => $kpiCard->id, 'user_id' => $user->id,
                'division_id' => $user->division_id, 'district_id' => $user->district_id, 'tehsil_id' => $user->tehsil_id,
                'period_type' => $data['period_type'], 'period_label' => $data['period_label'], 'submission_date' => $data['submission_date'],
                'status' => 'submitted', 'score' => 0, 'remarks' => $data['remarks'] ?? null,
            ]);
            foreach ($kpiCard->formFields as $field) {
                $submission->values()->create(['field_id' => $field->id, 'value' => $data['fields'][$field->id] ?? null]);
            }
            $numeric = $kpiCard->formFields->where('field_type', 'number')->sum(fn ($field) => (float) ($data['fields'][$field->id] ?? 0));
            $percentage = min(100, round(($numeric / max(1, (float) $kpiCard->total_marks)) * 100, 2));
            KpiScore::create(['kpi_card_id' => $kpiCard->id, 'submission_id' => $submission->id, 'user_id' => $user->id, 'division_id' => $user->division_id, 'district_id' => $user->district_id, 'tehsil_id' => $user->tehsil_id, 'score' => $numeric, 'percentage' => $percentage, 'grade' => $this->grade($percentage), 'performance_label' => $this->label($percentage)]);
            $submission->update(['score' => $numeric]);
        });
        return redirect()->route('kpi.dashboard', $kpiCard)->with('success', 'KPI data submitted successfully.');
    }

    public function review(Request $request, KpiDashboardService $service)
    {
        abort_if(in_array($request->user()->role?->slug, ['ac', 'field_user']), 403);

        $validated = $request->validate([
            'period_type' => ['nullable', 'in:daily,weekly,monthly,yearly'],
            'date' => ['nullable', 'date'],
            'week_no' => ['nullable', 'string', 'max:10'],
            'month' => ['nullable', 'integer', 'between:1,12'],
            'year' => ['nullable', 'integer', 'between:2020,2100'],
            'per_page' => ['nullable', 'integer', 'in:10,20,50,100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $query = $service->scope(KpiSubmission::with(['kpiCard', 'user', 'district', 'tehsil']), $request->user());

        if (! empty($validated['period_type'])) {
            $query->where('period_type', $validated['period_type']);
        }

        $query = $service->applyPeriodFilters($query, $request);
        $perPage = (int) ($validated['per_page'] ?? 20);
        $submissions = $query->latest('submission_date')->paginate($perPage)->withQueryString();
        $filters = $service->filterOptionsForView();
        $period = $service->periodState($request);
        $perPageOptions = [10, 20, 50, 100];

        if ($request->ajax()) {
            return response()->json([
                'html' => view('submissions.partials.review-table', compact('submissions'))->render(),
            ]);
        }

        return view('submissions.review', compact('submissions', 'filters', 'period', 'perPage', 'perPageOptions'));
    }

    public function updateStatus(Request $request, KpiSubmission $submission, KpiDashboardService $service)
    {
        abort_if(in_array($request->user()->role?->slug, ['ac', 'field_user']), 403);
        abort_unless($service->scope(KpiSubmission::whereKey($submission->id), $request->user())->exists(), 403);
        $data = $request->validate(['status' => ['required', 'in:pending,approved,rejected'], 'remarks' => ['nullable', 'string']]);
        $submission->update($data);
        return back()->with('success', 'Submission status updated.');
    }

    private function grade(float $score): string { return match (true) { $score >= 90 => 'A+', $score >= 80 => 'A', $score >= 70 => 'B', $score >= 60 => 'C', default => 'D' }; }
    private function label(float $score): string { return match (true) { $score >= 85 => 'Excellent', $score >= 70 => 'Good', $score >= 50 => 'Needs Attention', default => 'Critical' }; }
}
