<?php

namespace App\Http\Controllers;

use App\Models\KpiCard;
use App\Services\KpiDashboardService;
use Illuminate\Http\Request;

class KpiDashboardController extends Controller
{
    public function show(Request $request, KpiCard $kpiCard, KpiDashboardService $service)
    {
        $user = $request->user()->loadMissing(['role', 'division', 'district', 'tehsil']);
        abort_unless($service->canAccess($user, $kpiCard), 403);
        $data = $service->detail($kpiCard, $user, $request);
        $location = $user->tehsil?->name ?? $user->district?->name ?? $user->division?->name ?? 'Punjab';

        return view('dashboard.kpi-detail', compact('kpiCard', 'user', 'location') + $data);
    }

    public function data(Request $request, KpiCard $kpiCard, KpiDashboardService $service)
    {
        $user = $request->user()->loadMissing(['role', 'division', 'district', 'tehsil']);
        abort_unless($service->canAccess($user, $kpiCard), 403);

        $data = $service->detail($kpiCard, $user, $request);
        $imageUrl = asset($kpiCard->resolvedImagePath());

        $areaChartColors = $data['charts']['areas']->values()->map(fn ($v) =>
            $v >= 85 ? '#087443' : ($v >= 70 ? '#2563eb' : ($v >= 50 ? '#e07b00' : '#dc2626'))
        );

        $isHealth = $kpiCard->slug === 'inspection-of-health-facilities';

        return response()->json([
            'header' => $data['header'],
            'metrics_html' => view('dashboard.partials.kpi-detail-metrics', [
                'metrics' => $data['metrics'],
                'metricSections' => $data['metricSections'],
            ])->render(),
            'records_html' => $isHealth ? '' : view('dashboard.partials.kpi-detail-records', [
                'kpiCard' => $kpiCard,
                'summary' => $data['summary'],
                'tableSubmissions' => $data['tableSubmissions'],
                'imageUrl' => $imageUrl,
                'periodDescription' => $data['period_description'],
            ])->render(),
            'inspections_html' => view('dashboard.partials.kpi-detail-inspections-link', [
                'kpiCard' => $kpiCard,
                'inspectionRecords' => $data['inspectionRecords'],
                'period' => $data['period'],
                'geo' => $data['geo'] ?? [],
            ])->render(),
            'charts' => [
                'definitions' => $data['charts']['definitions'] ?? [],
                'status_donut' => $data['charts']['status_donut'],
                'target_achieved' => $data['charts']['target_achieved'],
                'trend' => $data['charts']['trend'],
                'areas' => $data['charts']['areas'],
                'comparison_label' => $data['charts']['comparison_label'],
            ],
            'area_chart_colors' => $areaChartColors,
            'records_total' => $data['tableSubmissions']->total(),
            'inspections_total' => $data['inspectionRecords']->total(),
            'period_description' => $data['period_description'],
            'period_query' => $service->periodQueryString($request),
            'period_filters' => $service->filterOptionsForView($kpiCard->slug),
        ]);
    }
}
