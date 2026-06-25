<?php

namespace App\Http\Controllers;

use App\Models\KpiCard;
use App\Models\KpiInspection;
use App\Services\KpiDashboardService;
use App\Services\KpiInspectionService;
use Illuminate\Http\Request;

class KpiInspectionController extends Controller
{
    public function show(Request $request, KpiCard $kpiCard, KpiInspection $inspection, KpiInspectionService $service, KpiDashboardService $dashboardService)
    {
        $user = $request->user()->loadMissing(['role', 'division', 'district', 'tehsil']);
        abort_unless($dashboardService->canAccess($user, $kpiCard), 403);

        $data = $service->getInspectionDetail($kpiCard, $inspection, $user);

        return view('kpi-inspections.show', [
            'kpiCard' => $kpiCard,
            'user' => $user,
        ] + $data);
    }

    public function approve(Request $request, KpiCard $kpiCard, KpiInspection $inspection, KpiInspectionService $service, KpiDashboardService $dashboardService)
    {
        $user = $request->user()->loadMissing(['role', 'division', 'district', 'tehsil']);
        abort_unless($dashboardService->canAccess($user, $kpiCard), 403);
        abort_if($inspection->kpi_card_id !== $kpiCard->id, 404);

        $request->merge([
            'review_remarks' => $request->input('remarks', $request->input('review_remarks')),
        ]);

        $validated = $request->validate([
            'review_remarks' => ['nullable', 'string', 'max:2000'],
        ]);

        $service->approveInspection($inspection, $user, $validated['review_remarks'] ?? null);

        return redirect()
            ->route('kpi.inspections.show', [$kpiCard, $inspection])
            ->with('success', 'Inspection approved successfully.');
    }

    public function reject(Request $request, KpiCard $kpiCard, KpiInspection $inspection, KpiInspectionService $service, KpiDashboardService $dashboardService)
    {
        $user = $request->user()->loadMissing(['role', 'division', 'district', 'tehsil']);
        abort_unless($dashboardService->canAccess($user, $kpiCard), 403);
        abort_if($inspection->kpi_card_id !== $kpiCard->id, 404);

        $request->merge([
            'rejection_reason' => $request->input('remarks', $request->input('rejection_reason')),
        ]);

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        $service->rejectInspection($inspection, $user, $validated['rejection_reason']);

        return redirect()
            ->route('kpi.inspections.show', [$kpiCard, $inspection])
            ->with('success', 'Inspection rejected and recorded.');
    }
}
