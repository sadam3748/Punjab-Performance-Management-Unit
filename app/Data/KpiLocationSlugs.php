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
            'price-of-roti' => 'Tandoor Inspection Coverage Map',
            'price-of-plain-bakery-bread' => 'Bakery Inspection Coverage Map',
            'price-control-of-essential-commodities' => 'Commodity Sale Point Inspection Coverage Map',
            'repair-of-small-roads-in-both-urban-and-rural-areas' => 'Road Inspection Coverage Map',
            'zebra-crossings' => 'School Zebra Crossing Inspection Coverage Map',
            'dysfunctional-streetlights' => 'Streetlight Inspection Coverage Map',
            'covering-of-manholes' => 'Manhole Inspection Coverage Map',
            'functional-and-clean-water-filtration-plants' => 'Water Filtration Plant Inspection Coverage Map',
            'violation-of-marriage-functions-act' => 'Marriage Hall Inspection Coverage Map',
            'graveyards' => 'Graveyard Inspection Coverage Map',
            'bus-terminals' => 'Bus Terminal Inspection Coverage Map',
            default => self::entityName($slug).' Inspection Coverage Map',
        };
    }

    public static function entityName(string $slug): string
    {
        return match (self::normalize($slug)) {
            'price-of-roti' => 'Tandoors',
            'price-of-plain-bakery-bread' => 'Bakeries',
            'price-control-of-essential-commodities' => 'Commodity Sale Points',
            'repair-of-small-roads-in-both-urban-and-rural-areas' => 'Roads',
            'zebra-crossings' => 'School Zebra Crossings',
            'dysfunctional-streetlights' => 'Streetlights',
            'covering-of-manholes' => 'Manholes',
            'functional-and-clean-water-filtration-plants' => 'Water Filtration Plants',
            'violation-of-marriage-functions-act' => 'Marriage Halls',
            'anti-encroachment-campaign' => 'Encroachment Locations',
            'regulation-of-shops-and-handcarts' => 'Shops and Handcarts',
            'stray-dogs' => 'Stray Dog Locations',
            'removal-of-wall-chalking' => 'Wall-Chalking Locations',
            'graveyards' => 'Graveyards',
            'illegal-decanting' => 'LPG Sale Points',
            'suthra-punjab-campaign' => 'Union Councils',
            'maintenance-of-greenbelts' => 'Parks and Greenbelts',
            'maintenance-of-drains-and-sewerage-lines' => 'Drains and Sewerage Locations',
            'bus-terminals' => 'Bus Terminals',
            'e-biz' => 'E-Biz Help Desks',
            default => 'Locations',
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
