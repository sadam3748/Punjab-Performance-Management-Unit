<?php

namespace App\Http\Controllers;

use App\Models\KpiCard;
use App\Models\KpiInspection;
use App\Services\KpiDashboardService;
use App\Services\KpiGeoFilterService;
use App\Services\KpiInspectionService;
use App\Services\KpiPeriodService;
use Illuminate\Http\Request;

class KpiInspectionController extends Controller
{
    private const DEFAULT_KPI_SLUG = 'inspection-of-health-facilities';

    public function index(
        Request $request,
        KpiInspectionService $inspectionService,
        KpiPeriodService $periodService,
        KpiGeoFilterService $geoFilterService,
    ) {
        $user = $request->user()->loadMissing(['role', 'division', 'district', 'tehsil']);

        if ($request->getQueryString() === null || $request->getQueryString() === '') {
            $request = $this->applyInspectionDefaults($request, $periodService);

            return redirect()->route('inspections.index', $request->query());
        }

        $request = $this->applyInspectionDefaults($request, $periodService);

        $year = (int) ($request->input('year') ?: now()->year);
        $month = (int) ($request->input('month') ?: now()->month);

        return view('inspections.index', [
            'user' => $user,
            'inspectionRecords' => $inspectionService->getAllInspectionsList($user, $request),
            'kpiCards' => KpiCard::query()->where('is_active', true)->orderBy('title')->get(['id', 'title', 'slug']),
            'filters' => $periodService->filterOptions($year, $month),
            'period' => $periodService->state($request),
            'period_description' => $periodService->description($request),
            'geoFilters' => $geoFilterService->options($user),
            'geo' => $geoFilterService->state($request),
            'inspectionFilters' => $inspectionService->filterOptions($user),
            'selectedKpiCardId' => (int) $request->input('kpi_card_id'),
            'inspectionDefaults' => $this->inspectionDefaults($request, $periodService),
        ]);
    }

    public function data(
        Request $request,
        KpiInspectionService $inspectionService,
        KpiPeriodService $periodService,
    ) {
        $user = $request->user()->loadMissing(['role', 'division', 'district', 'tehsil']);
        $request = $this->applyInspectionDefaults($request, $periodService);

        $records = $inspectionService->getAllInspectionsList($user, $request);

        return response()->json([
            'table_html' => view('inspections.partials.list-table', ['inspectionRecords' => $records])->render(),
            'total' => $records->total(),
            'from' => $records->firstItem(),
            'to' => $records->lastItem(),
            'period_description' => $periodService->description($request),
        ]);
    }

    /** @return array<string, string> */
    private function inspectionDefaults(Request $request, KpiPeriodService $periodService): array
    {
        return [
            'period_type' => 'weekly',
            'week_no' => (string) $periodService->currentWeekNo(),
            'month' => (string) now()->month,
            'year' => (string) now()->year,
            'kpi_card_id' => (string) $request->input('kpi_card_id', $this->defaultKpiCardId() ?? ''),
            'insp_per_page' => (string) $request->input('insp_per_page', 25),
        ];
    }

    private function applyInspectionDefaults(Request $request, KpiPeriodService $periodService): Request
    {
        if (! $request->filled('period_type')) {
            $request->merge([
                'period_type' => 'weekly',
                'week_no' => $periodService->currentWeekNo(),
                'month' => (string) now()->month,
                'year' => (string) now()->year,
            ]);
        }

        if (! $request->filled('kpi_card_id')) {
            $defaultKpiId = $this->defaultKpiCardId();
            if ($defaultKpiId) {
                $request->merge(['kpi_card_id' => (string) $defaultKpiId]);
            }
        }

        if (! $request->filled('insp_per_page')) {
            $request->merge(['insp_per_page' => '25']);
        }

        return $request;
    }

    private function defaultKpiCardId(): ?int
    {
        $id = KpiCard::query()
            ->where('slug', self::DEFAULT_KPI_SLUG)
            ->where('is_active', true)
            ->value('id');

        return $id ? (int) $id : null;
    }

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
