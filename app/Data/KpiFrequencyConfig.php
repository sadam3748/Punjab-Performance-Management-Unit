<?php

namespace App\Data;

/**
 * Central KPI frequency configuration — single source for daily vs weekly defaults.
 */
class KpiFrequencyConfig
{
    /** @var list<string> */
    public const DAILY_KPIS = [
        'price-of-roti',
        'price-of-plain-bakery-bread',
        'price-control-of-essential-commodities',
        'anti-encroachment-campaign',
        'regulation-of-shops-and-handcarts',
        'stray-dogs',
        'removal-of-wall-chalking',
        'chief-ministers-complaint-cell',
    ];

    /** @var list<string> */
    public const WEEKLY_KPIS = [
        'repair-of-small-roads-in-both-urban-and-rural-areas',
        'dysfunctional-streetlights',
        'covering-of-manholes',
        'functional-and-clean-water-filtration-plants',
        'violation-of-marriage-functions-act',
        'zebra-crossings',
        'illegal-decanting',
        'suthra-punjab-campaign',
        'maintenance-of-greenbelts',
        'maintenance-of-drains-and-sewerage-lines',
        'graveyards',
        'bus-terminals',
        'e-biz',
    ];

    /** @var list<string> */
    public const VISIT_KPIS = [
        'inspection-of-health-facilities',
        'inspection-of-educational-institutions',
    ];

    public static function normalize(string $slug): string
    {
        return KpiDashboardDefinitions::normalizeSlug($slug);
    }

    public static function isDaily(string $slug): bool
    {
        return in_array(self::normalize($slug), self::DAILY_KPIS, true);
    }

    public static function isWeekly(string $slug): bool
    {
        $slug = self::normalize($slug);

        if (in_array($slug, self::WEEKLY_KPIS, true) || in_array($slug, self::VISIT_KPIS, true)) {
            return true;
        }

        return ! self::isDaily($slug);
    }

    public static function isOperationalInspectionKpi(string $slug): bool
    {
        $slug = self::normalize($slug);

        return ! in_array($slug, self::VISIT_KPIS, true)
            && $slug !== 'land-management-services';
    }

    /** @return list<string> */
    public static function periodTypesFor(string $slug): array
    {
        if (self::isWeekly($slug) && ! self::isDaily($slug)) {
            return ['weekly', 'monthly', 'yearly'];
        }

        return ['daily', 'weekly', 'monthly', 'yearly'];
    }
}
