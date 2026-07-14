<?php

namespace App\Data;

class KpiLocationSlugs
{
    /** @var list<string> */
    public const VISIT_KPIS = [
        'inspection-of-health-facilities',
        'inspection-of-educational-institutions',
    ];

    /** @var list<string> */
    public const LOCATION_KPIS = [
        'price-of-roti',
        'price-of-plain-bakery-bread',
        'price-control-of-essential-commodities',
        'repair-of-small-roads-in-both-urban-and-rural-areas',
        'zebra-crossings',
        'dysfunctional-streetlights',
        'covering-of-manholes',
        'functional-and-clean-water-filtration-plants',
        'violation-of-marriage-functions-act',
        'anti-encroachment-campaign',
        'regulation-of-shops-and-handcarts',
        'stray-dogs',
        'removal-of-wall-chalking',
        'graveyards',
        'illegal-decanting',
        'suthra-punjab-campaign',
        'maintenance-of-greenbelts',
        'maintenance-of-drains-and-sewerage-lines',
        'bus-terminals',
        'e-biz',
    ];

    public static function normalize(string $slug): string
    {
        return KpiDashboardDefinitions::normalizeSlug($slug);
    }

    public static function hasLocationMap(string $slug): bool
    {
        return in_array(self::normalize($slug), self::LOCATION_KPIS, true);
    }

    public static function isVisitKpi(string $slug): bool
    {
        return in_array(self::normalize($slug), self::VISIT_KPIS, true);
    }

    public static function isPlaceholder(string $slug): bool
    {
        return self::normalize($slug) === 'land-management-services';
    }

    public static function mapTitle(string $slug): string
    {
        return match (self::normalize($slug)) {
            'price-of-roti' => 'Tandoor Inspection Map',
            'price-of-plain-bakery-bread' => 'Bakery Inspection Map',
            'price-control-of-essential-commodities' => 'Sale Point Inspection Map',
            'repair-of-small-roads-in-both-urban-and-rural-areas' => 'Road Maintenance Map',
            'zebra-crossings' => 'School Zebra Crossing Map',
            'dysfunctional-streetlights' => 'Streetlight Inspection Map',
            'covering-of-manholes' => 'Manhole Coverage Map',
            'functional-and-clean-water-filtration-plants' => 'Water Filtration Plant Map',
            'violation-of-marriage-functions-act' => 'Marriage Hall Inspection Map',
            'anti-encroachment-campaign' => 'Anti-Encroachment Action Map',
            'regulation-of-shops-and-handcarts' => 'Market Inspection Map',
            'stray-dogs' => 'Stray Dog Activity Map',
            'removal-of-wall-chalking' => 'Wall-Chalking Removal Map',
            'graveyards' => 'Graveyard Standards Map',
            'illegal-decanting' => 'LPG Sale Point Map',
            'suthra-punjab-campaign' => 'Suthra Punjab UC Inspection Map',
            'maintenance-of-greenbelts' => 'Parks & Greenbelt Map',
            'maintenance-of-drains-and-sewerage-lines' => 'Drain & Sewerage Map',
            'bus-terminals' => 'Bus Terminal Inspection Map',
            'e-biz' => 'E-Biz Help Desk Map',
            default => 'Inspection Location Map',
        };
    }

    public static function listSectionTitle(string $slug): string
    {
        return match (self::normalize($slug)) {
            'price-of-roti' => 'Recent Roti Inspections',
            'price-of-plain-bakery-bread' => 'Recent Bakery Inspections',
            'price-control-of-essential-commodities' => 'Recent Sale Point Inspections',
            'repair-of-small-roads-in-both-urban-and-rural-areas' => 'Road Maintenance Records',
            'dysfunctional-streetlights' => 'Streetlight Inspection Records',
            'covering-of-manholes' => 'Manhole Coverage Records',
            'functional-and-clean-water-filtration-plants' => 'Water Filtration Plant Records',
            'violation-of-marriage-functions-act' => 'Marriage Hall Inspection Records',
            'anti-encroachment-campaign' => 'Market Clearance Records',
            'regulation-of-shops-and-handcarts' => 'Market Inspection Records',
            'stray-dogs' => 'Stray Dog Activity Records',
            'removal-of-wall-chalking' => 'Wall-Chalking Removal Records',
            'graveyards' => 'Graveyard Maintenance Records',
            'zebra-crossings' => 'School Zebra Crossing Records',
            'illegal-decanting' => 'LPG Sale Point Inspection Records',
            'suthra-punjab-campaign' => 'Suthra Punjab UC Records',
            'maintenance-of-greenbelts' => 'Parks & Greenbelt Records',
            'maintenance-of-drains-and-sewerage-lines' => 'Drain & Sewerage Records',
            'bus-terminals' => 'Bus Terminal Inspection Records',
            'chief-ministers-complaint-cell' => 'CM Complaint Records',
            'e-biz' => 'E-Biz Help Desk Records',
            default => 'Inspection Records',
        };
    }

    public static function listSectionDescription(string $slug, int $total): string
    {
        $label = self::listSectionTitle($slug);

        return $total === 1
            ? "1 record in the selected period. Open the list to review evidence and details."
            : sprintf('%s records in the selected period. Open the list to review evidence and details.', number_format($total));
    }
}
