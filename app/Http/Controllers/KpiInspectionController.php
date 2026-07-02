<?php

namespace App\Http\Controllers;

use App\Models\KpiCard;
use App\Models\KpiInspection;
use App\Services\KpiDashboardService;
use App\Services\KpiGeoFilterService;
use App\Services\KpiInspectionService;
use Illuminate\Http\Request;

class KpiInspectionController extends Controller
{
    private const DEFAULT_KPI_SLUG = 'inspection-of-health-facilities';

    public function index(
        Request $request,
        KpiInspectionService $inspectionService,
        KpiGeoFilterService $geoFilterService,
    ) {
        $user = $request->user()->loadMissing(['role', 'division', 'district', 'tehsil']);
        $request = $this->applyInspectionDefaults($request);

        return view('inspections.index', [
            'user' => $user,
            'inspectionRecords' => $inspectionService->getAllInspectionsList($user, $request),
            'kpiCards' => KpiCard::query()->where('is_active', true)->orderBy('title')->get(['id', 'title', 'slug']),
            'geoFilters' => $geoFilterService->options($user),
            'geo' => $geoFilterService->state($request),
            'inspectionFilters' => $inspectionService->filterOptions($user),
            'selectedKpiCardId' => (int) $request->input('kpi_card_id'),
            'inspectionDateRange' => $inspectionService->completedDayDateRange(),
        ]);
    }

    public function data(
        Request $request,
        KpiInspectionService $inspectionService,
    ) {
        $user = $request->user()->loadMissing(['role', 'division', 'district', 'tehsil']);
        $request = $this->applyInspectionDefaults($request);

        $records = $inspectionService->getAllInspectionsList($user, $request);

        return response()->json([
            'table_html' => view('inspections.partials.list-table', ['inspectionRecords' => $records])->render(),
            'total' => $records->total(),
            'from' => $records->firstItem(),
            'to' => $records->lastItem(),
        ]);
    }

    private function applyInspectionDefaults(Request $request): Request
    {
        if (! $request->has('kpi_card_id')) {
            $defaultKpiId = $this->defaultKpiCardId();
            if ($defaultKpiId) {
                $request->merge(['kpi_card_id' => (string) $defaultKpiId]);
            }
        }

        if (! $request->filled('insp_per_page')) {
            $request->merge(['insp_per_page' => '10']);
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
            'backUrl' => $this->inspectionListBackUrl($request),
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
            ->route('kpi.inspections.show', $this->detailRouteParameters($request, $kpiCard, $inspection))
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
            'rejection_reason' => ['nullable', 'string', 'max:2000'],
        ]);

        $service->rejectInspection($inspection, $user, $validated['rejection_reason'] ?? null);

        return redirect()
            ->route('kpi.inspections.show', $this->detailRouteParameters($request, $kpiCard, $inspection))
            ->with('success', 'Inspection rejected and recorded.');
    }

    private function inspectionListBackUrl(Request $request): string
    {
        $fallback = route('inspections.index');
        $candidate = $request->string('return_url')->toString();

        if ($candidate === '') {
            return $fallback;
        }

        $path = parse_url($candidate, PHP_URL_PATH);
        $expectedPath = parse_url($fallback, PHP_URL_PATH);
        $host = parse_url($candidate, PHP_URL_HOST);

        return $path === $expectedPath && ($host === null || $host === $request->getHost())
            ? $candidate
            : $fallback;
    }

    /** @return array<int|string, mixed> */
    private function detailRouteParameters(Request $request, KpiCard $kpiCard, KpiInspection $inspection): array
    {
        $parameters = [$kpiCard, $inspection];

        if ($request->filled('return_url')) {
            $parameters['return_url'] = $this->inspectionListBackUrl($request);
        }

        return $parameters;
    }
}
