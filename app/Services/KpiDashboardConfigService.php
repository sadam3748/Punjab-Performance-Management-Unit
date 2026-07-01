<?php

namespace App\Services;

use App\Data\KpiDashboardDefinitions;
use App\Data\KpiDashboardStats;

class KpiDashboardConfigService
{
    public function normalizeSlug(string $slug): string
    {
        return KpiDashboardDefinitions::normalizeSlug($slug);
    }

    /** @return array<string, mixed> */
    public function forKpi(string $slug): array
    {
        $slug = $this->normalizeSlug($slug);
        $definition = KpiDashboardDefinitions::config($slug);
        $dashboardStats = $this->dashboardStatsFor($slug);

        return [
            'slug' => $slug,
            'target_frequency' => $dashboardStats[0]['target_frequency'] ?? 'mixed',
            'filters' => $this->filters(),
            'dashboard_stats' => $dashboardStats,
            'metrics' => $dashboardStats,
            'metric_cards' => $dashboardStats,
            'charts' => $definition['charts'],
            'chart_definitions' => $definition['charts'],
            'table_columns' => $definition['table_columns'],
            'detail_fields' => $definition['detail_fields'],
            'seed_fields' => $this->seedFieldsFor($slug, $definition['table_columns'], $dashboardStats),
        ];
    }

    /** @return list<array<string, mixed>> */
    public function dashboardStatsFor(string $slug): array
    {
        return KpiDashboardStats::statsFor($this->normalizeSlug($slug));
    }

    /** @return list<array{type: string, title: string, key: string}> */
    public function chartsFor(string $slug): array
    {
        return KpiDashboardDefinitions::config($this->normalizeSlug($slug))['charts'];
    }

    /** @return list<array{label: string, field: string, from?: string}> */
    public function tableColumnsFor(string $slug): array
    {
        return KpiDashboardDefinitions::config($this->normalizeSlug($slug))['table_columns'];
    }

    /** @return list<array{label: string, field: string}> */
    public function detailFieldsFor(string $slug): array
    {
        return KpiDashboardDefinitions::config($this->normalizeSlug($slug))['detail_fields'];
    }

    /** @return list<string> */
    public function allSlugs(): array
    {
        return KpiDashboardDefinitions::slugs();
    }

    /** @return array{target: string, completed: string} */
    public function headerLabelsFor(string $slug): array
    {
        return match ($this->normalizeSlug($slug)) {
            'inspection-of-health-facilities' => [
                'target' => 'Visit Target',
                'completed' => 'Target Completed',
            ],
            'inspection-of-educational-institutions' => [
                'target' => 'Visit Target',
                'completed' => 'Visits Completed',
            ],
            'repair-of-small-roads-in-both-urban-and-rural-areas' => [
                'target' => 'Roads Target',
                'completed' => 'Roads Repaired',
            ],
            'functional-and-clean-water-filtration-plants' => [
                'target' => 'Plants Target',
                'completed' => 'Plants Inspected',
            ],
            'chief-ministers-complaint-cell' => [
                'target' => 'Complaints Received',
                'completed' => 'Complaints Resolved',
            ],
            'e-biz' => [
                'target' => 'Applications Target',
                'completed' => 'Applications Processed',
            ],
            default => [
                'target' => 'Inspection Target',
                'completed' => 'Inspections Done',
            ],
        };
    }

    /** @return array{target: string, completed: string} */
    public function operationalFieldsFor(string $slug): array
    {
        return [
            'target' => 'operational_target',
            'completed' => 'operational_completed',
        ];
    }

    /** @return array<string, mixed> */
    public function filters(): array
    {
        return [
            'geo' => ['date_range', 'division', 'district', 'tehsil'],
            'period' => ['daily', 'weekly', 'monthly', 'yearly', 'month', 'year'],
        ];
    }

    /**
     * @param  list<array{label: string, field: string, from?: string}>  $tableColumns
     * @param  list<array<string, mixed>>  $dashboardStats
     * @return list<array<string, string>>
     */
    private function seedFieldsFor(string $slug, array $tableColumns, array $dashboardStats): array
    {
        $metricFields = collect($dashboardStats)
            ->map(fn (array $metric): array => [
                'field' => $metric['field'],
                'label' => $metric['label'],
                'source' => 'submission',
            ]);

        $inspectionFields = collect($tableColumns)
            ->reject(fn (array $column): bool => in_array($column['from'] ?? 'detail_data', ['entity', 'address', 'inspector'], true))
            ->map(fn (array $column): array => [
                'field' => $column['field'],
                'label' => $column['label'],
                'source' => 'inspection',
            ]);

        return $metricFields->merge($inspectionFields)->unique('field')->values()->all();
    }
}
