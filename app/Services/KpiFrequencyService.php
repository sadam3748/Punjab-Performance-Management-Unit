<?php

namespace App\Services;

class KpiFrequencyService
{
    /** @var list<string> */
    private const DAILY_SLUGS = [
        'price-of-roti',
        'price-of-plain-bakery-bread',
        'price-control-of-essential-commodities',
        'anti-encroachment-campaign',
        'regulation-of-shops-and-handcarts',
        'stray-dogs',
    ];

    /** @var list<string> */
    private const WEEKLY_SLUGS = [
        'inspection-of-health-facilities',
        'inspection-of-educational-institutions',
        'repair-of-small-roads-in-both-urban-and-rural-areas',
        'zebra-crossings',
        'dysfunctional-streetlights',
        'covering-of-manholes',
        'functional-and-clean-water-filtration-plants',
        'suthra-punjab-campaign',
        'maintenance-of-greenbelts',
        'maintenance-of-drains-and-sewerage-lines',
        'bus-terminals',
        'violation-of-marriage-functions-act',
        'graveyards',
        'illegal-decanting',
        'removal-of-wall-chalking',
        'chief-ministers-complaint-cell',
        'e-biz',
    ];

    public function __construct(private readonly KpiPeriodService $periodService) {}

    public function normalizeSlug(string $slug): string
    {
        return match ($slug) {
            'repair-of-small-roads' => 'repair-of-small-roads-in-both-urban-and-rural-areas',
            default => $slug,
        };
    }

    public function isDaily(string $slug): bool
    {
        $slug = $this->normalizeSlug($slug);

        return in_array($slug, self::DAILY_SLUGS, true);
    }

    public function isWeekly(string $slug): bool
    {
        $slug = $this->normalizeSlug($slug);

        if (in_array($slug, self::WEEKLY_SLUGS, true)) {
            return true;
        }

        return ! $this->isDaily($slug);
    }

    /** @return list<string> */
    public function periodTypesFor(string $slug): array
    {
        if ($this->isWeekly($slug) && ! $this->isDaily($slug)) {
            return ['weekly', 'monthly', 'yearly'];
        }

        return ['daily', 'weekly', 'monthly', 'yearly'];
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

        return [
            'period_type' => 'weekly',
            'week_no' => $this->periodService->currentWeekNo(),
            'month' => (string) now()->month,
            'year' => (string) now()->year,
            'date' => now()->toDateString(),
        ];
    }
}
