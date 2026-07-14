<?php

namespace App\Services;

use App\Data\KpiFrequencyConfig;

class KpiFrequencyService
{
    public function __construct(private readonly KpiPeriodService $periodService) {}

    public function normalizeSlug(string $slug): string
    {
        return KpiFrequencyConfig::normalize($slug);
    }

    public function isDaily(string $slug): bool
    {
        return KpiFrequencyConfig::isDaily($slug);
    }

    public function isWeekly(string $slug): bool
    {
        return KpiFrequencyConfig::isWeekly($slug);
    }

    /** @return list<string> */
    public function periodTypesFor(string $slug): array
    {
        return KpiFrequencyConfig::periodTypesFor($slug);
    }

    /** @return array<string, string> */
    public function defaultParamsFor(string $slug): array
    {
        if ($this->isDaily($slug)) {
            return [
                'period_type' => 'daily',
                'date' => now()->toDateString(),
                'week_no' => $this->periodService->currentWeekNo(),
                'month' => (string) now()->month,
                'year' => (string) now()->year,
            ];
        }

        $completedWeek = $this->periodService->latestCompletedWeekNo();
        $completedRange = $this->periodService->getWeekDateRange($completedWeek);
        $anchor = $completedRange['end'] ?? now();

        return [
            'period_type' => 'weekly',
            'week_no' => $completedWeek,
            'month' => (string) $anchor->month,
            'year' => (string) $anchor->year,
            'date' => $anchor->toDateString(),
        ];
    }
}
