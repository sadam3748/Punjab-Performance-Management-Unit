<?php

namespace App\Data;

class KpiDashboardStats
{
    /**
     * @return list<array{
     *     label: string,
     *     field: string,
     *     icon: string,
     *     tone: 'green'|'blue'|'purple'|'yellow'|'orange'|'red',
     *     description: string,
     *     formula_text: string,
     *     target_frequency: 'daily'|'weekly'|'monthly'|'yearly'|'mixed'
     * }>
     */
    public static function statsFor(string $slug): array
    {
        $slug = KpiDashboardDefinitions::normalizeSlug($slug);

        return match ($slug) {
            'price-of-roti' => self::priceOfRotiCards(),
            'price-of-plain-bakery-bread' => self::priceOfPlainBakeryBreadCards(),
            'price-control-of-essential-commodities' => self::priceControlEssentialCommoditiesCards(),
            'repair-of-small-roads-in-both-urban-and-rural-areas' => self::repairSmallRoadsCards(),
            'zebra-crossings' => self::zebraCrossingsCards(),
            'dysfunctional-streetlights' => self::dysfunctionalStreetlightsCards(),
            'covering-of-manholes' => self::coveringOfManholesCards(),
            'functional-and-clean-water-filtration-plants' => self::waterFiltrationPlantsCards(),
            'inspection-of-educational-institutions' => self::educationalInstitutionsCards(),
            'inspection-of-health-facilities' => self::healthFacilitiesCards(),
            'violation-of-marriage-functions-act' => self::marriageFunctionsActCards(),
            'anti-encroachment-campaign' => self::antiEncroachmentCards(),
            'stray-dogs' => self::strayDogsCards(),
            'removal-of-wall-chalking' => self::wallChalkingCards(),
            'graveyards' => self::graveyardsCards(),
            'illegal-decanting' => self::illegalDecantingCards(),
            'suthra-punjab-campaign' => self::suthraPunjabCards(),
            'maintenance-of-greenbelts' => self::greenbeltsCards(),
            'maintenance-of-drains-and-sewerage-lines' => self::drainsSewerageCards(),
            'bus-terminals' => self::busTerminalsCards(),
            'chief-ministers-complaint-cell' => self::chiefMinistersComplaintCellCards(),
            'regulation-of-shops-and-handcarts' => self::shopsAndHandcartsCards(),
            'e-biz' => self::eBizCards(),
            default => [],
        };
    }

    /** @return list<array<string, string>> */
    private static function priceOfRotiCards(): array
    {
        $tierDesc = 'Daily inspection target per AC/PCM by district tier: T1 = 10, T2 = 8, T3 = 6 inspections per day.';

        return [
            self::card(
                'DC Weekly Review Meetings Held vs. Target',
                'dc_weekly_review',
                'bi-people-fill',
                'blue',
                'DC must hold one weekly review meeting on price of roti enforcement.',
                'Meetings held ÷ weekly target × 100',
                'weekly'
            ),
            self::card(
                'Inspections Target per AC/PCM',
                'tier_target',
                'bi-bullseye',
                'purple',
                $tierDesc,
                'Tier-based daily inspections per AC/PCM',
                'daily'
            ),
            self::card(
                'Total Inspectors (AC/PCM)',
                'total_inspectors',
                'bi-person-badge',
                'blue',
                'Count of AC and Price Control Magistrate inspectors deployed for roti price monitoring.',
                '',
                'mixed'
            ),
            self::card(
                'Inspections Total Target',
                'inspections_total_target',
                'bi-list-check',
                'purple',
                'District-wide daily inspection target derived from tier targets and inspector count.',
                'Inspectors × tier target per AC/PCM',
                'daily'
            ),
            self::card(
                'Total Inspections Conducted',
                'tandoor_inspections',
                'bi-shop',
                'blue',
                'Tandoors, shops, hotels and other roti sale points physically inspected during the period.',
                '',
                'daily'
            ),
            self::card(
                'Inspection Target Achievement Rate',
                'achievement_rate',
                'bi-percent',
                'green',
                'Share of inspection target achieved in the reporting period.',
                'Total inspections conducted ÷ inspections total target × 100',
                'daily'
            ),
            self::card(
                'AC/PCM Coverage & Mobility Index Score',
                'coverage_mobility_index',
                'bi-speedometer2',
                'blue',
                'Composite score reflecting geographic coverage and field mobility of enforcement teams.',
                'Weighted coverage and mobility index (0–100)',
                'weekly'
            ),
            self::card(
                'Violations Found (Over Price / Under Weight / Non-Availability)',
                'violations_found',
                'bi-exclamation-triangle',
                'red',
                'Violations detected for overcharging, under-weight roti, or non-availability.',
                '',
                'daily'
            ),
            self::card(
                'Fine Imposition',
                'fine_imposed',
                'bi-currency-rupee',
                'yellow',
                'Number of fines imposed on violators during inspections.',
                '',
                'daily'
            ),
            self::card(
                'Fine Imposition Rate',
                'fine_imposition_rate',
                'bi-graph-up',
                'orange',
                'Proportion of inspections resulting in a fine.',
                'Fines imposed ÷ total inspections conducted × 100',
                'daily'
            ),
            self::card(
                'Citizen Complaints Received',
                'citizen_complaints_received',
                'bi-inbox',
                'blue',
                'Citizen complaints received about roti price, weight or availability.',
                '',
                'daily'
            ),
            self::card(
                'Citizen Complaint Resolution Rate',
                'complaint_resolution_rate',
                'bi-check-circle',
                'green',
                'Share of citizen complaints resolved within the stipulated timeline.',
                'Complaints resolved ÷ complaints received × 100',
                'daily'
            ),
            self::card(
                'Inspections Validation Target',
                'validation_target',
                'bi-bullseye',
                'purple',
                'Target number of roti inspections to be validated by supervisory officers.',
                '',
                'weekly'
            ),
            self::card(
                'Inspections Validated',
                'validated_inspections',
                'bi-clipboard2-check',
                'blue',
                'Inspection reports reviewed and validated by AC/DC or designated validators.',
                '',
                'weekly'
            ),
            self::card(
                'Approved / Rejected Validations',
                'approved_rejected_validations',
                'bi-patch-check',
                'green',
                'Validated inspections approved or sent back for correction.',
                'Approved validations + rejected validations',
                'weekly'
            ),
        ];
    }

    /** @return list<array<string, string>> */
    private static function priceOfPlainBakeryBreadCards(): array
    {
        $tierDesc = 'Daily inspection target per AC/PCM by district tier: T1 = 5, T2 = 4, T3 = 3 inspections per day.';

        return [
            self::card(
                'DC Weekly Review Meetings Held vs. Target',
                'dc_weekly_review',
                'bi-people-fill',
                'blue',
                'DC must hold one weekly review meeting on bakery bread price enforcement.',
                'Meetings held ÷ weekly target × 100',
                'weekly'
            ),
            self::card(
                'Inspections Target per AC/PCM',
                'tier_target',
                'bi-bullseye',
                'purple',
                $tierDesc,
                'Tier-based daily inspections per AC/PCM',
                'daily'
            ),
            self::card(
                'Total Inspectors (AC/PCM)',
                'total_inspectors',
                'bi-person-badge',
                'blue',
                'Count of AC and PCM inspectors deployed for bakery bread price monitoring.',
                '',
                'mixed'
            ),
            self::card(
                'Inspections Total Target',
                'inspections_total_target',
                'bi-list-check',
                'purple',
                'District-wide daily inspection target for bakeries, brands and retail outlets.',
                'Inspectors × tier target per AC/PCM',
                'daily'
            ),
            self::card(
                'Total Inspections Conducted',
                'bread_inspections',
                'bi-shop',
                'blue',
                'Bakeries, brands and retail outlets inspected for plain bakery bread pricing.',
                '',
                'daily'
            ),
            self::card(
                'Inspection Target Achievement Rate',
                'achievement_rate',
                'bi-percent',
                'green',
                'Share of bakery bread inspection target achieved in the reporting period.',
                'Total inspections conducted ÷ inspections total target × 100',
                'daily'
            ),
            self::card(
                'AC/PCM Coverage & Mobility Index Score',
                'coverage_mobility_index',
                'bi-speedometer2',
                'blue',
                'Composite score reflecting geographic coverage and field mobility of bakery enforcement teams.',
                'Weighted coverage and mobility index (0–100)',
                'weekly'
            ),
            self::card(
                'Violations Found (Over Price / Under Weight / Non-Availability)',
                'violations_found',
                'bi-exclamation-triangle',
                'red',
                'Violations detected for overcharging, under-weight bread, or non-availability.',
                '',
                'daily'
            ),
            self::card(
                'Fine Imposition',
                'fine_imposed',
                'bi-gavel',
                'yellow',
                'Number of fines imposed on bakery bread violators during inspections.',
                '',
                'daily'
            ),
            self::card(
                'Fine Imposition Rate',
                'fine_imposition_rate',
                'bi-graph-up',
                'orange',
                'Proportion of bakery inspections resulting in a fine.',
                'Fines imposed ÷ total inspections conducted × 100',
                'daily'
            ),
            self::card(
                'Citizen Complaints Received',
                'citizen_complaints_received',
                'bi-inbox',
                'blue',
                'Citizen complaints received about bakery bread price, weight or availability.',
                '',
                'daily'
            ),
            self::card(
                'Citizen Complaint Resolution Rate',
                'complaint_resolution_rate',
                'bi-check-circle',
                'green',
                'Share of bakery bread complaints resolved within the stipulated timeline.',
                'Complaints resolved ÷ complaints received × 100',
                'daily'
            ),
            self::card(
                'Inspections Validation Target',
                'validation_target',
                'bi-bullseye',
                'purple',
                'Target number of bakery bread inspections to be validated by supervisory officers.',
                '',
                'weekly'
            ),
            self::card(
                'Inspections Validated',
                'validated_inspections',
                'bi-clipboard2-check',
                'blue',
                'Bakery inspection reports reviewed and validated by AC/DC or designated validators.',
                '',
                'weekly'
            ),
            self::card(
                'Approved / Rejected Validations',
                'approved_rejected_validations',
                'bi-patch-check',
                'green',
                'Validated bakery inspections approved or sent back for correction.',
                'Approved validations + rejected validations',
                'weekly'
            ),
        ];
    }

    /** @return list<array<string, string>> */
    private static function priceControlEssentialCommoditiesCards(): array
    {
        $tierDesc = 'Daily inspection target per AC/PCM by district tier: T1 = 35, T2 = 28, T3 = 21 inspections per day.';

        return [
            self::card(
                'DC Weekly Review Meetings Held vs. Target',
                'dc_weekly_review',
                'bi-people-fill',
                'blue',
                'DC must hold one weekly review meeting on essential commodity price control.',
                'Meetings held ÷ weekly target × 100',
                'weekly'
            ),
            self::card(
                'Inspections Target per AC/PCM',
                'tier_target',
                'bi-bullseye',
                'purple',
                $tierDesc,
                'Tier-based daily inspections per AC/PCM',
                'daily'
            ),
            self::card(
                'Total Inspectors (AC/PCM)',
                'total_inspectors',
                'bi-person-badge',
                'blue',
                'Count of AC and PCM inspectors deployed for essential commodity monitoring.',
                '',
                'mixed'
            ),
            self::card(
                'Inspections Total Target',
                'inspections_total_target',
                'bi-list-check',
                'purple',
                'District-wide daily inspection target for shops and sale points.',
                'Inspectors × tier target per AC/PCM',
                'daily'
            ),
            self::card(
                'Total Inspections Conducted',
                'market_inspections',
                'bi-clipboard2-check',
                'blue',
                'Shops and sale points inspected for essential commodity pricing and availability.',
                '',
                'daily'
            ),
            self::card(
                'Inspection Target Achievement Rate',
                'achievement_rate',
                'bi-percent',
                'green',
                'Share of essential commodity inspection target achieved in the reporting period.',
                'Total inspections conducted ÷ inspections total target × 100',
                'daily'
            ),
            self::card(
                'AC/PCM Coverage & Mobility Index Score',
                'coverage_mobility_index',
                'bi-speedometer2',
                'blue',
                'Composite score reflecting geographic coverage and field mobility of price control teams.',
                'Weighted coverage and mobility index (0–100)',
                'weekly'
            ),
            self::card(
                'Violations Found (Over Price / Hoarding / Non-Availability)',
                'violations_found',
                'bi-exclamation-triangle',
                'red',
                'Violations including overpricing, hoarding, and non-availability of essential commodities.',
                'Special Branch violations + citizen-reported violations',
                'daily'
            ),
            self::card(
                'Fine Imposition',
                'fine_imposed',
                'bi-currency-rupee',
                'yellow',
                'Number of fines imposed on essential commodity violators.',
                '',
                'daily'
            ),
            self::card(
                'Fine Imposition Rate',
                'fine_imposition_rate',
                'bi-graph-up',
                'orange',
                'Proportion of commodity inspections resulting in a fine.',
                'Fines imposed ÷ total inspections conducted × 100',
                'daily'
            ),
            self::card(
                'Citizen Complaints Received',
                'citizen_complaints_received',
                'bi-inbox',
                'blue',
                'Citizen complaints received about essential commodity pricing or availability.',
                '',
                'daily'
            ),
            self::card(
                'Citizen Complaint Resolution Rate',
                'complaint_resolution_rate',
                'bi-check-circle',
                'green',
                'Share of essential commodity complaints resolved within the stipulated timeline.',
                'Complaints resolved ÷ complaints received × 100',
                'daily'
            ),
            self::card(
                'Inspections Validation Target',
                'validation_target',
                'bi-bullseye',
                'purple',
                'Target number of commodity inspections to be validated by supervisory officers.',
                '',
                'weekly'
            ),
            self::card(
                'Inspections Validated',
                'validated_inspections',
                'bi-clipboard2-check',
                'blue',
                'Commodity inspection reports reviewed and validated by AC/DC or designated validators.',
                '',
                'weekly'
            ),
            self::card(
                'Approved / Rejected Validations',
                'approved_rejected_validations',
                'bi-patch-check',
                'green',
                'Validated commodity inspections approved or sent back for correction.',
                'Approved validations + rejected validations',
                'weekly'
            ),
        ];
    }

    /** @return list<array<string, string>> */
    private static function healthFacilitiesCards(): array
    {
        return [
            self::card(
                'Total Health Facilities',
                'total_health_facilities',
                'bi-hospital',
                'blue',
                'Total BHUs, RHCs and other health facilities in the district baseline.',
                '',
                'yearly'
            ),
            self::card(
                'Total Facilities Inspected',
                'facility_visits',
                'bi-clipboard2-check',
                'blue',
                'Health facilities physically inspected during the reporting period.',
                '',
                'monthly'
            ),
            self::card(
                'Total Facilities Not Inspected',
                'facilities_not_inspected',
                'bi-hourglass-split',
                'yellow',
                'Health facilities not yet inspected against the monthly visit target.',
                'Total facilities − facilities inspected',
                'monthly'
            ),
            self::card(
                'DC Visits Completed vs Target',
                'dc_visit_completion',
                'bi-person-badge',
                'blue',
                'DC visits to health facilities compared to the monthly DC visit target.',
                'DC visits completed ÷ DC visit target × 100',
                'monthly'
            ),
            self::card(
                'AC Visits Completed vs Target',
                'ac_visit_completion',
                'bi-person-check',
                'blue',
                'AC visits to health facilities compared to the monthly AC visit target.',
                'AC visits completed ÷ AC visit target × 100',
                'monthly'
            ),
            self::card(
                'DC Meeting on Health Council Held',
                'health_council_meeting',
                'bi-people',
                'blue',
                'District health council review meetings chaired or attended by the DC.',
                '',
                'monthly'
            ),
            self::card(
                'Issues Found - Cleanliness',
                'issues_cleanliness',
                'bi-stars',
                'yellow',
                'Cleanliness deficiencies identified during health facility inspections.',
                '',
                'monthly'
            ),
            self::card(
                'Issues Found - Staff Absence',
                'issues_staff_absence',
                'bi-person-x',
                'red',
                'Staff absence or attendance issues found during facility inspections.',
                '',
                'monthly'
            ),
            self::card(
                'Issues Found - Medicine Shortage',
                'issues_medicine_shortage',
                'bi-capsule',
                'red',
                'Medicine stock shortages identified during health facility inspections.',
                '',
                'monthly'
            ),
            self::card(
                'Issues Found - Equipment / Utilities',
                'issues_equipment_utilities',
                'bi-tools',
                'yellow',
                'Equipment, power or utility issues found during health facility inspections.',
                '',
                'monthly'
            ),
            self::card(
                'Inspections Validation Target',
                'validation_target',
                'bi-bullseye',
                'purple',
                'Target number of health facility inspection reports to be validated.',
                '',
                'monthly'
            ),
            self::card(
                'Inspections Validated',
                'validations_completed',
                'bi-patch-check',
                'green',
                'Health facility inspection reports reviewed and validated by supervisory officers.',
                '',
                'monthly'
            ),
            self::card(
                'Approved Validations',
                'approved_validations',
                'bi-check-circle',
                'green',
                'Validated health facility inspections approved for scorecard reporting.',
                '',
                'monthly'
            ),
            self::card(
                'Rejected Validations',
                'rejected_validations',
                'bi-x-circle',
                'red',
                'Validated health facility inspections rejected and sent back for correction.',
                '',
                'monthly'
            ),
        ];
    }

    /** @return list<array<string, string>> */
    private static function waterFiltrationPlantsCards(): array
    {
        return [
            self::card(
                'Total Water Filtration Plants',
                'total_plants',
                'bi-droplet',
                'blue',
                'Total government water filtration plants in the district baseline.',
                '',
                'yearly'
            ),
            self::card(
                'Total RO Plants',
                'total_ro_plants',
                'bi-droplet-half',
                'blue',
                'Reverse-osmosis (RO) filtration plants in the district.',
                '',
                'yearly'
            ),
            self::card(
                'Total UF Plants',
                'total_uf_plants',
                'bi-droplet-fill',
                'blue',
                'Ultrafiltration (UF) plants in the district.',
                '',
                'yearly'
            ),
            self::card(
                'Water Filtration Plants Inspection Target',
                'plants_to_inspect',
                'bi-list-check',
                'purple',
                'Weekly target for inspecting all functional filtration plants in the district.',
                '',
                'weekly'
            ),
            self::card(
                'Total Inspected Plants',
                'inspected_plants',
                'bi-clipboard2-check',
                'blue',
                'Filtration plants physically inspected during the reporting period.',
                '',
                'weekly'
            ),
            self::card(
                'Total Non-Inspected Plants',
                'non_inspected_plants',
                'bi-hourglass-split',
                'yellow',
                'Filtration plants not yet inspected against the weekly target.',
                'Inspection target − inspected plants',
                'weekly'
            ),
            self::card(
                'Plant Inspection Coverage Rate',
                'plant_coverage_rate',
                'bi-percent',
                'green',
                'Share of filtration plants inspected against the weekly target.',
                'Inspected plants ÷ inspection target × 100',
                'weekly'
            ),
            self::card(
                'Total Blocked Plants',
                'blocked_plants',
                'bi-slash-circle',
                'red',
                'Filtration plants found blocked or non-operational due to obstruction.',
                '',
                'weekly'
            ),
            self::card(
                'Functional Plants',
                'functional_plants',
                'bi-check-circle',
                'green',
                'Plants verified as functional and supplying filtered water.',
                '',
                'weekly'
            ),
            self::card(
                'Non-Functional Plants',
                'non_functional_plants',
                'bi-x-circle',
                'red',
                'Plants found non-functional during inspection.',
                '',
                'weekly'
            ),
            self::card(
                'Clean Plants',
                'clean_plants',
                'bi-stars',
                'green',
                'Plants meeting cleanliness standards during inspection.',
                '',
                'weekly'
            ),
            self::card(
                'Unclean Plants',
                'unclean_plants',
                'bi-exclamation-triangle',
                'red',
                'Plants failing cleanliness standards during inspection.',
                '',
                'weekly'
            ),
            self::card(
                'RO Plants Due for Filter Change',
                'ro_filter_pending',
                'bi-hourglass-split',
                'yellow',
                'RO plants where filter change is overdue per maintenance schedule.',
                '',
                'weekly'
            ),
            self::card(
                'RO Plants with Filter Changed',
                'ro_filter_changed',
                'bi-arrow-repeat',
                'green',
                'RO plants where filter was changed and change date was affixed.',
                '',
                'weekly'
            ),
            self::card(
                'Filter Change Compliance Rate',
                'filter_change_rate',
                'bi-percent',
                'green',
                'Share of due RO plants where filter change was completed on time.',
                'Filters changed ÷ filters due × 100',
                'weekly'
            ),
            self::card(
                'Approved / Rejected Validations',
                'approved_rejected_validations',
                'bi-patch-check',
                'green',
                'Plant inspection validations approved or rejected by supervisory officers.',
                'Approved validations + rejected validations',
                'weekly'
            ),
        ];
    }

    /** @return list<array<string, string>> */
    private static function chiefMinistersComplaintCellCards(): array
    {
        return [
            self::card(
                'Complaints Received',
                'complaints_received',
                'bi-inbox',
                'blue',
                'Total complaints received through the Chief Minister Complaint Cell for the district.',
                '',
                'daily'
            ),
            self::card(
                'Complaints Resolved',
                'complaints_resolved',
                'bi-check-circle',
                'green',
                'Complaints fully resolved and closed within the reporting period.',
                '',
                'daily'
            ),
            self::card(
                'Pending Complaints',
                'pending_complaints',
                'bi-hourglass-split',
                'yellow',
                'Complaints still under process and not yet resolved.',
                'Complaints received − complaints resolved',
                'daily'
            ),
            self::card(
                'Resolution Rate',
                'resolution_rate',
                'bi-percent',
                'green',
                'Share of received complaints resolved within the stipulated timeline.',
                'Complaints resolved ÷ complaints received × 100',
                'daily'
            ),
            self::card(
                'Overdue Complaints',
                'overdue_complaints',
                'bi-exclamation-triangle',
                'red',
                'Complaints exceeding the prescribed resolution timeline.',
                '',
                'daily'
            ),
            self::card(
                'Average Resolution Days',
                'average_resolution_days',
                'bi-calendar-check',
                'blue',
                'Average number of days taken to resolve complaints in the period.',
                'Sum of resolution days ÷ complaints resolved',
                'daily'
            ),
            self::card(
                'Escalated Complaints',
                'escalated_complaints',
                'bi-arrow-up-circle',
                'orange',
                'Complaints escalated to DC or higher authority due to delay or complexity.',
                '',
                'weekly'
            ),
            self::card(
                'Follow-ups Conducted',
                'followups',
                'bi-arrow-repeat',
                'blue',
                'Follow-up actions taken on pending or overdue complaints.',
                '',
                'weekly'
            ),
        ];
    }

    /** @return list<array<string, string>> */
    private static function repairSmallRoadsCards(): array
    {
        return [
            self::card(
                'Total Small Roads in Baseline',
                'total_roads',
                'bi-signpost-2',
                'blue',
                'Small urban and rural roads recorded in the district baseline inventory.',
                '',
                'yearly'
            ),
            self::card(
                'Roads Identified for Repair',
                'roads_identified',
                'bi-search',
                'blue',
                'Road segments identified as requiring repair during field surveys.',
                '',
                'weekly'
            ),
            self::card(
                'Weekly Road Repair Target',
                'weekly_road_target',
                'bi-bullseye',
                'purple',
                'Target number of small road repair points to be completed each week.',
                '',
                'weekly'
            ),
            self::card(
                'Road Repairs Completed',
                'repair_completed',
                'bi-tools',
                'green',
                'Road repair works completed and verified during the reporting period.',
                '',
                'weekly'
            ),
            self::card(
                'Repair Target Achievement Rate',
                'achievement_rate',
                'bi-percent',
                'green',
                'Share of weekly road repair target achieved.',
                'Repairs completed ÷ weekly road target × 100',
                'weekly'
            ),
            self::card(
                'Special Branch Points Reported',
                'sb_points',
                'bi-flag',
                'blue',
                'Road damage points reported through Special Branch channels.',
                '',
                'weekly'
            ),
            self::card(
                'Citizen Complaints Resolved',
                'complaints_resolved',
                'bi-check-circle',
                'green',
                'Citizen complaints about small road repairs resolved in the period.',
                '',
                'weekly'
            ),
            self::card(
                'Inspections Validation Target',
                'validation_target',
                'bi-bullseye',
                'purple',
                'Target number of road repair verifications to be validated.',
                '',
                'weekly'
            ),
            self::card(
                'Inspections Validated',
                'validations_completed',
                'bi-clipboard2-check',
                'blue',
                'Road repair completion reports validated by supervisory officers.',
                '',
                'weekly'
            ),
            self::card(
                'Approved / Rejected Validations',
                'approved_rejected_validations',
                'bi-patch-check',
                'green',
                'Road repair validations approved or sent back for correction.',
                'Approved validations + rejected validations',
                'weekly'
            ),
        ];
    }

    /** @return list<array<string, string>> */
    private static function zebraCrossingsCards(): array
    {
        return [
            self::card(
                'Schools in Baseline',
                'schools_to_inspect',
                'bi-mortarboard',
                'blue',
                'Educational institutions with zebra crossing requirements in the district.',
                '',
                'yearly'
            ),
            self::card(
                'Schools Inspected',
                'schools_inspected',
                'bi-clipboard2-check',
                'blue',
                'Schools inspected for zebra crossing markings and safety compliance.',
                '',
                'monthly'
            ),
            self::card(
                'Zebra Crossings Marked / Refreshed',
                'markings_done',
                'bi-brush',
                'green',
                'Zebra crossings newly marked or refreshed at school approaches.',
                '',
                'monthly'
            ),
            self::card(
                'Inspection Target Achievement Rate',
                'achievement_rate',
                'bi-percent',
                'green',
                'Share of school inspection target achieved in the period.',
                'Schools inspected ÷ schools to inspect × 100',
                'monthly'
            ),
            self::card(
                'Special Branch Points Reported',
                'sb_points',
                'bi-flag',
                'blue',
                'Zebra crossing deficiencies reported through Special Branch.',
                '',
                'monthly'
            ),
            self::card(
                'Issues Resolved',
                'resolved_points',
                'bi-check-circle',
                'green',
                'Zebra crossing issues resolved after inspection or complaint.',
                '',
                'monthly'
            ),
            self::card(
                'Inspections Validation Target',
                'validation_target',
                'bi-bullseye',
                'purple',
                'Target number of school zebra crossing inspections to validate.',
                '',
                'monthly'
            ),
            self::card(
                'Inspections Validated',
                'validations_completed',
                'bi-clipboard2-check',
                'blue',
                'School zebra crossing inspection reports validated by AC/DC.',
                '',
                'monthly'
            ),
            self::card(
                'Approved / Rejected Validations',
                'approved_rejected_validations',
                'bi-patch-check',
                'green',
                'Zebra crossing inspection validations approved or rejected.',
                'Approved validations + rejected validations',
                'monthly'
            ),
        ];
    }

    /** @return list<array<string, string>> */
    private static function dysfunctionalStreetlightsCards(): array
    {
        return [
            self::card(
                'Roads / Streets Inspected',
                'roads_inspected',
                'bi-signpost',
                'blue',
                'Roads and streets surveyed for dysfunctional streetlights.',
                '',
                'weekly'
            ),
            self::card(
                'Dysfunctional Lights Reported',
                'sb_reported',
                'bi-lightbulb',
                'red',
                'Streetlights reported as dysfunctional by field teams or Special Branch.',
                '',
                'weekly'
            ),
            self::card(
                'Lights Repaired',
                'repairs_completed',
                'bi-lightbulb-fill',
                'green',
                'Dysfunctional streetlights repaired and restored to working order.',
                '',
                'weekly'
            ),
            self::card(
                'Pending Repairs',
                'pending_points',
                'bi-hourglass-split',
                'yellow',
                'Reported dysfunctional lights not yet repaired.',
                'Lights reported − lights repaired',
                'weekly'
            ),
            self::card(
                'Repair Target Achievement Rate',
                'achievement_rate',
                'bi-percent',
                'green',
                'Share of reported dysfunctional lights repaired in the period.',
                'Lights repaired ÷ lights reported × 100',
                'weekly'
            ),
            self::card(
                'Functional Streetlight Rate',
                'functional_rate',
                'bi-percent',
                'blue',
                'Overall functional streetlight rate across inspected roads.',
                'Functional lights ÷ total lights × 100',
                'weekly'
            ),
            self::card(
                'Special Branch Points Resolved',
                'resolved_points',
                'bi-check-circle',
                'green',
                'Special Branch streetlight complaints resolved in the period.',
                '',
                'weekly'
            ),
            self::card(
                'Inspections Validation Target',
                'validation_target',
                'bi-bullseye',
                'purple',
                'Target number of streetlight repair verifications to validate.',
                '',
                'weekly'
            ),
            self::card(
                'Inspections Validated',
                'validations_completed',
                'bi-clipboard2-check',
                'blue',
                'Streetlight repair reports validated by supervisory officers.',
                '',
                'weekly'
            ),
            self::card(
                'Approved / Rejected Validations',
                'approved_rejected_validations',
                'bi-patch-check',
                'green',
                'Streetlight repair validations approved or rejected.',
                'Approved validations + rejected validations',
                'weekly'
            ),
        ];
    }

    /** @return list<array<string, string>> */
    private static function coveringOfManholesCards(): array
    {
        return [
            self::card(
                'UCs Inspected',
                'ucs_inspected',
                'bi-geo-alt',
                'blue',
                'Union councils inspected for open manhole coverage compliance.',
                '',
                'weekly'
            ),
            self::card(
                'Total UCs with Manholes',
                'total_ucs',
                'bi-grid',
                'blue',
                'Union councils with manhole infrastructure in the district baseline.',
                '',
                'yearly'
            ),
            self::card(
                'Open Manholes Identified',
                'manholes_identified',
                'bi-exclamation-octagon',
                'red',
                'Open or uncovered manholes identified during inspections.',
                '',
                'weekly'
            ),
            self::card(
                'Manholes Covered',
                'covers_installed',
                'bi-check-circle',
                'green',
                'Manholes covered or secured after identification.',
                '',
                'weekly'
            ),
            self::card(
                'Pending Open Manholes',
                'pending_manholes',
                'bi-hourglass-split',
                'yellow',
                'Identified open manholes not yet covered.',
                'Open manholes identified − manholes covered',
                'weekly'
            ),
            self::card(
                'Safety Compliance Rate',
                'compliance_rate',
                'bi-percent',
                'green',
                'Share of identified open manholes that have been covered.',
                'Manholes covered ÷ open manholes identified × 100',
                'weekly'
            ),
            self::card(
                'Special Branch Points Reported',
                'sb_points',
                'bi-flag',
                'blue',
                'Open manhole hazards reported through Special Branch.',
                '',
                'weekly'
            ),
            self::card(
                'Inspections Validation Target',
                'validation_target',
                'bi-bullseye',
                'purple',
                'Target number of manhole coverage inspections to validate.',
                '',
                'weekly'
            ),
            self::card(
                'Inspections Validated',
                'validations_completed',
                'bi-clipboard2-check',
                'blue',
                'Manhole coverage inspection reports validated by AC/DC.',
                '',
                'weekly'
            ),
            self::card(
                'Approved / Rejected Validations',
                'approved_rejected_validations',
                'bi-patch-check',
                'green',
                'Manhole coverage validations approved or rejected.',
                'Approved validations + rejected validations',
                'weekly'
            ),
        ];
    }

    /** @return list<array<string, string>> */
    private static function educationalInstitutionsCards(): array
    {
        return [
            self::card(
                'Total Educational Institutions',
                'total_institutions',
                'bi-mortarboard',
                'blue',
                'Schools and colleges in the district baseline for institutional inspection.',
                '',
                'yearly'
            ),
            self::card(
                'DC Visits Completed',
                'dc_visits',
                'bi-person-badge',
                'blue',
                'Deputy Commissioner visits to educational institutions in the period.',
                '',
                'monthly'
            ),
            self::card(
                'AC Visits Completed',
                'ac_visits',
                'bi-person-check',
                'blue',
                'Assistant Commissioner visits to educational institutions in the period.',
                '',
                'monthly'
            ),
            self::card(
                'Total Required Visits',
                'required_visits',
                'bi-bullseye',
                'purple',
                'Monthly target for institutional inspections across the district.',
                '',
                'monthly'
            ),
            self::card(
                'Inspection Reports Submitted',
                'institution_visits',
                'bi-file-earmark-text',
                'green',
                'Completed institutional inspection reports submitted on the portal.',
                '',
                'monthly'
            ),
            self::card(
                'Visit Target Achievement Rate',
                'achievement_rate',
                'bi-percent',
                'green',
                'Share of required institutional visits completed.',
                'Reports submitted ÷ required visits × 100',
                'monthly'
            ),
            self::card(
                'School Council Meetings Held',
                'school_council_meeting',
                'bi-people',
                'blue',
                'School council meetings convened as per inspection findings.',
                '',
                'monthly'
            ),
            self::card(
                'Facility Deficiencies Found',
                'facilities_issues',
                'bi-exclamation-triangle',
                'yellow',
                'Cleanliness, staffing or facility gaps identified during visits.',
                '',
                'monthly'
            ),
            self::card(
                'Inspections Validation Target',
                'validation_target',
                'bi-bullseye',
                'purple',
                'Target number of school inspection reports to validate.',
                '',
                'monthly'
            ),
            self::card(
                'Inspections Validated',
                'validations_completed',
                'bi-clipboard2-check',
                'blue',
                'Educational institution inspection reports validated by supervisory officers.',
                '',
                'monthly'
            ),
            self::card(
                'Approved / Rejected Validations',
                'approved_rejected_validations',
                'bi-patch-check',
                'green',
                'School inspection validations approved or rejected.',
                'Approved validations + rejected validations',
                'monthly'
            ),
        ];
    }

    /** @return list<array<string, string>> */
    private static function marriageFunctionsActCards(): array
    {
        return [
            self::card(
                'Total Marriage Halls',
                'total_halls',
                'bi-building',
                'blue',
                'Registered marriage halls in the district baseline.',
                '',
                'yearly'
            ),
            self::card(
                'Marriage Hall Inspections Conducted',
                'marriage_hall_inspections',
                'bi-clipboard2-check',
                'blue',
                'Marriage halls inspected for compliance with the Marriage Functions Act.',
                '',
                'monthly'
            ),
            self::card(
                'Violations Detected',
                'violations_detected',
                'bi-exclamation-triangle',
                'red',
                'Violations of timing, capacity or other act provisions found during inspections.',
                '',
                'monthly'
            ),
            self::card(
                'Enforcement Actions Taken',
                'actions_taken',
                'bi-gavel',
                'yellow',
                'Warnings, notices or other enforcement actions on violating halls.',
                '',
                'monthly'
            ),
            self::card(
                'Fines / Notices Issued',
                'notices_fines',
                'bi-currency-rupee',
                'orange',
                'Monetary fines or formal notices issued to non-compliant marriage halls.',
                '',
                'monthly'
            ),
            self::card(
                'Compliance Rate',
                'compliance_rate',
                'bi-percent',
                'green',
                'Share of inspected halls found compliant with the act.',
                'Compliant halls ÷ halls inspected × 100',
                'monthly'
            ),
            self::card(
                'Inspections Validation Target',
                'validation_target',
                'bi-bullseye',
                'purple',
                'Target number of marriage hall inspections to validate.',
                '',
                'monthly'
            ),
            self::card(
                'Inspections Validated',
                'validations_completed',
                'bi-clipboard2-check',
                'blue',
                'Marriage hall inspection reports validated by AC/DC.',
                '',
                'monthly'
            ),
            self::card(
                'Approved / Rejected Validations',
                'approved_rejected_validations',
                'bi-patch-check',
                'green',
                'Marriage hall inspection validations approved or rejected.',
                'Approved validations + rejected validations',
                'monthly'
            ),
        ];
    }

    /** @return list<array<string, string>> */
    private static function antiEncroachmentCards(): array
    {
        return [
            self::card(
                'Markets / Locations Surveyed',
                'markets_surveyed',
                'bi-shop',
                'blue',
                'Markets and public spaces surveyed for encroachment during the campaign.',
                '',
                'daily'
            ),
            self::card(
                'Daily Market Clearance Target',
                'daily_market_target',
                'bi-bullseye',
                'purple',
                'Daily target for encroachment clearance points across the district.',
                '',
                'daily'
            ),
            self::card(
                'Encroachment Points Cleared',
                'encroachments_removed',
                'bi-check-circle',
                'green',
                'Encroachment points cleared during anti-encroachment operations.',
                '',
                'daily'
            ),
            self::card(
                'Clearance Target Achievement Rate',
                'achievement_rate',
                'bi-percent',
                'green',
                'Share of daily encroachment clearance target achieved.',
                'Points cleared ÷ daily target × 100',
                'daily'
            ),
            self::card(
                'Pending Encroachments',
                'pending_encroachments',
                'bi-hourglass-split',
                'yellow',
                'Identified encroachment points not yet cleared.',
                '',
                'daily'
            ),
            self::card(
                'Special Branch Points Reported',
                'sb_points',
                'bi-flag',
                'blue',
                'Encroachment complaints reported through Special Branch channels.',
                '',
                'daily'
            ),
            self::card(
                'Issues Resolved',
                'resolved_points',
                'bi-check-circle',
                'green',
                'Encroachment-related complaints resolved in the reporting period.',
                '',
                'daily'
            ),
            self::card(
                'Inspections Validation Target',
                'validation_target',
                'bi-bullseye',
                'purple',
                'Target number of encroachment clearance actions to validate.',
                '',
                'weekly'
            ),
            self::card(
                'Inspections Validated',
                'validations_completed',
                'bi-clipboard2-check',
                'blue',
                'Encroachment clearance reports validated by supervisory officers.',
                '',
                'weekly'
            ),
            self::card(
                'Approved / Rejected Validations',
                'approved_rejected_validations',
                'bi-patch-check',
                'green',
                'Encroachment clearance validations approved or rejected.',
                'Approved validations + rejected validations',
                'weekly'
            ),
        ];
    }

    /** @return list<array<string, string>> */
    private static function strayDogsCards(): array
    {
        return [
            self::card(
                'Target Union Councils',
                'target_ucs',
                'bi-bullseye',
                'purple',
                'Union councils targeted for stray dog control activities each month.',
                '',
                'monthly'
            ),
            self::card(
                'UC Activities Conducted',
                'uc_activities',
                'bi-geo-alt',
                'blue',
                'Stray dog control activities completed in union councils.',
                '',
                'monthly'
            ),
            self::card(
                'Activity Target Achievement Rate',
                'achievement_rate',
                'bi-percent',
                'green',
                'Share of target UCs where stray dog activities were conducted.',
                'UC activities ÷ target UCs × 100',
                'monthly'
            ),
            self::card(
                'Citizen Complaints Verified',
                'complaints_verified',
                'bi-check2-square',
                'blue',
                'Citizen complaints about stray dogs verified on ground.',
                '',
                'monthly'
            ),
            self::card(
                'Control Actions Completed',
                'actions_taken',
                'bi-shield-check',
                'green',
                'Vaccination, neutering or relocation actions completed.',
                '',
                'monthly'
            ),
            self::card(
                'Follow-up Required',
                'followup_required',
                'bi-hourglass-split',
                'yellow',
                'UCs or complaint sites requiring follow-up stray dog control action.',
                '',
                'monthly'
            ),
            self::card(
                'Special Branch Points Reported',
                'sb_points',
                'bi-flag',
                'blue',
                'Stray dog incidents reported through Special Branch.',
                '',
                'monthly'
            ),
            self::card(
                'Inspections Validation Target',
                'validation_target',
                'bi-bullseye',
                'purple',
                'Target number of stray dog activity reports to validate.',
                '',
                'monthly'
            ),
            self::card(
                'Inspections Validated',
                'validations_completed',
                'bi-clipboard2-check',
                'blue',
                'Stray dog control activity reports validated by AC/DC.',
                '',
                'monthly'
            ),
            self::card(
                'Approved / Rejected Validations',
                'approved_rejected_validations',
                'bi-patch-check',
                'green',
                'Stray dog activity validations approved or rejected.',
                'Approved validations + rejected validations',
                'monthly'
            ),
        ];
    }

    /** @return list<array<string, string>> */
    private static function wallChalkingCards(): array
    {
        return [
            self::card(
                'UCs Inspected',
                'ucs_inspected',
                'bi-geo-alt',
                'blue',
                'Union councils inspected for wall chalking and illegal banners.',
                '',
                'weekly'
            ),
            self::card(
                'Wall Chalking Spots Identified',
                'spots_identified',
                'bi-search',
                'blue',
                'Sites with wall chalking or unauthorized publicity identified.',
                '',
                'weekly'
            ),
            self::card(
                'Wall Chalking Removed',
                'removal_done',
                'bi-brush',
                'green',
                'Wall chalking spots cleared during removal drives.',
                '',
                'weekly'
            ),
            self::card(
                'Banners / Posters Removed',
                'banners_removed',
                'bi-image',
                'green',
                'Illegal banners and posters removed from public spaces.',
                '',
                'weekly'
            ),
            self::card(
                'Removal Target Achievement Rate',
                'achievement_rate',
                'bi-percent',
                'green',
                'Share of identified chalking spots cleared in the period.',
                'Spots cleared ÷ spots identified × 100',
                'weekly'
            ),
            self::card(
                'Special Branch Points Reported',
                'sb_points',
                'bi-flag',
                'blue',
                'Wall chalking complaints reported through Special Branch.',
                '',
                'weekly'
            ),
            self::card(
                'Issues Resolved',
                'resolved_points',
                'bi-check-circle',
                'green',
                'Wall chalking complaints resolved after removal action.',
                '',
                'weekly'
            ),
            self::card(
                'Inspections Validation Target',
                'validation_target',
                'bi-bullseye',
                'purple',
                'Target number of wall chalking removal actions to validate.',
                '',
                'weekly'
            ),
            self::card(
                'Inspections Validated',
                'validations_completed',
                'bi-clipboard2-check',
                'blue',
                'Wall chalking removal reports validated by supervisory officers.',
                '',
                'weekly'
            ),
            self::card(
                'Approved / Rejected Validations',
                'approved_rejected_validations',
                'bi-patch-check',
                'green',
                'Wall chalking removal validations approved or rejected.',
                'Approved validations + rejected validations',
                'weekly'
            ),
        ];
    }

    /** @return list<array<string, string>> */
    private static function graveyardsCards(): array
    {
        return [
            self::card(
                'Total Graveyards in Baseline',
                'total_graveyards',
                'bi-grid',
                'blue',
                'Graveyards recorded in the district baseline inventory.',
                '',
                'yearly'
            ),
            self::card(
                'Weekly Graveyard Maintenance Target',
                'weekly_target',
                'bi-bullseye',
                'purple',
                'Target number of graveyards to be maintained each week.',
                '',
                'weekly'
            ),
            self::card(
                'Graveyards Maintained / Cleared',
                'graveyards_cleared',
                'bi-check-circle',
                'green',
                'Graveyards cleaned, demarcated or encroachment-free after maintenance.',
                '',
                'weekly'
            ),
            self::card(
                'Boundary Wall Issues Found',
                'boundary_wall_issues',
                'bi-bricks',
                'yellow',
                'Graveyards with damaged or missing boundary walls identified.',
                '',
                'weekly'
            ),
            self::card(
                'Encroachment Removed',
                'encroachment_removed',
                'bi-sign-stop',
                'green',
                'Encroachments cleared from graveyard premises.',
                '',
                'weekly'
            ),
            self::card(
                'Overgrowth / Bushes Removed',
                'bushes_removed',
                'bi-tree',
                'green',
                'Vegetation and bush clearance completed at graveyards.',
                '',
                'weekly'
            ),
            self::card(
                'Special Branch Points Reported',
                'sb_points',
                'bi-flag',
                'blue',
                'Graveyard maintenance issues reported through Special Branch.',
                '',
                'weekly'
            ),
            self::card(
                'Inspections Validation Target',
                'validation_target',
                'bi-bullseye',
                'purple',
                'Target number of graveyard maintenance reports to validate.',
                '',
                'weekly'
            ),
            self::card(
                'Inspections Validated',
                'validations_completed',
                'bi-clipboard2-check',
                'blue',
                'Graveyard maintenance reports validated by AC/DC.',
                '',
                'weekly'
            ),
            self::card(
                'Approved / Rejected Validations',
                'approved_rejected_validations',
                'bi-patch-check',
                'green',
                'Graveyard maintenance validations approved or rejected.',
                'Approved validations + rejected validations',
                'weekly'
            ),
        ];
    }

    /** @return list<array<string, string>> */
    private static function illegalDecantingCards(): array
    {
        return [
            self::card(
                'LPG Sale Points Inspected',
                'stations_inspected',
                'bi-fuel-pump',
                'blue',
                'Petrol pumps, shops and outlets inspected for illegal LPG decanting.',
                '',
                'weekly'
            ),
            self::card(
                'Weekly Inspection Target',
                'inspection_target',
                'bi-bullseye',
                'purple',
                'Weekly target for illegal decanting enforcement inspections.',
                '',
                'weekly'
            ),
            self::card(
                'Violations Found',
                'violations_found',
                'bi-exclamation-triangle',
                'red',
                'Illegal decanting or unsafe LPG handling violations detected.',
                '',
                'weekly'
            ),
            self::card(
                'Enforcement Actions Taken',
                'actions_taken',
                'bi-gavel',
                'yellow',
                'Warnings, seizures or other enforcement actions on violators.',
                '',
                'weekly'
            ),
            self::card(
                'Fines / FIR / Sealed Outlets',
                'enforcement_actions',
                'bi-shield-exclamation',
                'red',
                'Outlets fined, FIR registered or sealed for illegal decanting.',
                '',
                'weekly'
            ),
            self::card(
                'Compliance Rate',
                'compliance_rate',
                'bi-percent',
                'green',
                'Share of inspected outlets found compliant with LPG safety rules.',
                'Compliant outlets ÷ outlets inspected × 100',
                'weekly'
            ),
            self::card(
                'Special Branch Points Reported',
                'sb_points',
                'bi-flag',
                'blue',
                'Illegal decanting leads reported through Special Branch.',
                '',
                'weekly'
            ),
            self::card(
                'Inspections Validation Target',
                'validation_target',
                'bi-bullseye',
                'purple',
                'Target number of decanting inspection reports to validate.',
                '',
                'weekly'
            ),
            self::card(
                'Inspections Validated',
                'validations_completed',
                'bi-clipboard2-check',
                'blue',
                'Illegal decanting inspection reports validated by AC/DC.',
                '',
                'weekly'
            ),
            self::card(
                'Approved / Rejected Validations',
                'approved_rejected_validations',
                'bi-patch-check',
                'green',
                'Decanting inspection validations approved or rejected.',
                'Approved validations + rejected validations',
                'weekly'
            ),
        ];
    }

    /** @return list<array<string, string>> */
    private static function suthraPunjabCards(): array
    {
        return [
            self::card(
                'DC Field Inspections',
                'dc_inspections',
                'bi-person-badge',
                'blue',
                'Deputy Commissioner-led Suthra Punjab field inspections.',
                '',
                'weekly'
            ),
            self::card(
                'AC / UC Inspections',
                'ac_uc_inspections',
                'bi-geo-alt',
                'blue',
                'Assistant Commissioner and UC-level cleanliness inspections.',
                '',
                'weekly'
            ),
            self::card(
                'Sanitation Staff Attendance Rate',
                'hr_attendance',
                'bi-people',
                'blue',
                'Attendance of sanitation workers during campaign monitoring.',
                'Present staff ÷ rostered staff × 100',
                'weekly'
            ),
            self::card(
                'Vehicles / Machinery Deployed',
                'vehicles_in_field',
                'bi-truck',
                'blue',
                'Waste collection vehicles and machinery operational in the field.',
                '',
                'weekly'
            ),
            self::card(
                'Waste Containers Placed',
                'containers_placed',
                'bi-box',
                'green',
                'New or replacement waste containers placed in public areas.',
                '',
                'weekly'
            ),
            self::card(
                'Garbage Heaps Cleared',
                'heaps_cleared',
                'bi-stars',
                'green',
                'Open garbage heaps cleared during Suthra Punjab drives.',
                '',
                'weekly'
            ),
            self::card(
                'Campaign Compliance Rate',
                'campaign_compliance',
                'bi-percent',
                'green',
                'Overall Suthra Punjab campaign compliance score for the district.',
                'Weighted cleanliness compliance index',
                'weekly'
            ),
            self::card(
                'Special Branch Points Reported',
                'sb_points',
                'bi-flag',
                'blue',
                'Cleanliness issues reported through Special Branch during the campaign.',
                '',
                'weekly'
            ),
            self::card(
                'Inspections Validation Target',
                'validation_target',
                'bi-bullseye',
                'purple',
                'Target number of Suthra Punjab inspection reports to validate.',
                '',
                'weekly'
            ),
            self::card(
                'Inspections Validated',
                'validations_completed',
                'bi-clipboard2-check',
                'blue',
                'Suthra Punjab inspection reports validated by supervisory officers.',
                '',
                'weekly'
            ),
            self::card(
                'Approved / Rejected Validations',
                'approved_rejected_validations',
                'bi-patch-check',
                'green',
                'Suthra Punjab inspection validations approved or rejected.',
                'Approved validations + rejected validations',
                'weekly'
            ),
        ];
    }

    /** @return list<array<string, string>> */
    private static function greenbeltsCards(): array
    {
        return [
            self::card(
                'Total Parks / Greenbelts',
                'total_parks',
                'bi-grid',
                'blue',
                'Parks and greenbelts in the district baseline inventory.',
                '',
                'yearly'
            ),
            self::card(
                'Parks Maintained',
                'parks_maintained',
                'bi-tree',
                'green',
                'Parks where scheduled maintenance was completed.',
                '',
                'monthly'
            ),
            self::card(
                'Greenbelts Maintained',
                'greenbelts_maintained',
                'bi-flower1',
                'green',
                'Roadside greenbelts trimmed, irrigated or otherwise maintained.',
                '',
                'monthly'
            ),
            self::card(
                'DC Beautification Initiatives',
                'beautification',
                'bi-palette',
                'blue',
                'DC-led beautification or plantation initiatives completed.',
                '',
                'monthly'
            ),
            self::card(
                'Maintenance Compliance Rate',
                'compliance_rate',
                'bi-percent',
                'green',
                'Share of parks and greenbelts meeting maintenance standards.',
                'Maintained assets ÷ total assets × 100',
                'monthly'
            ),
            self::card(
                'Special Branch Points Reported',
                'sb_points',
                'bi-flag',
                'blue',
                'Greenbelt maintenance issues reported through Special Branch.',
                '',
                'monthly'
            ),
            self::card(
                'Inspections Validation Target',
                'validation_target',
                'bi-bullseye',
                'purple',
                'Target number of greenbelt maintenance reports to validate.',
                '',
                'monthly'
            ),
            self::card(
                'Inspections Validated',
                'validations_completed',
                'bi-clipboard2-check',
                'blue',
                'Greenbelt maintenance reports validated by AC/DC.',
                '',
                'monthly'
            ),
            self::card(
                'Approved / Rejected Validations',
                'approved_rejected_validations',
                'bi-patch-check',
                'green',
                'Greenbelt maintenance validations approved or rejected.',
                'Approved validations + rejected validations',
                'monthly'
            ),
        ];
    }

    /** @return list<array<string, string>> */
    private static function drainsSewerageCards(): array
    {
        return [
            self::card(
                'UCs Inspected',
                'ucs_inspected',
                'bi-geo-alt',
                'blue',
                'Union councils inspected for drain and sewerage maintenance.',
                '',
                'weekly'
            ),
            self::card(
                'Total UCs with Sewerage Network',
                'total_ucs',
                'bi-grid',
                'blue',
                'Union councils with drains or sewerage lines in the baseline.',
                '',
                'yearly'
            ),
            self::card(
                'Drain Blockages Reported',
                'blockages_reported',
                'bi-exclamation-triangle',
                'red',
                'Blocked drains or sewerage lines identified during inspections.',
                '',
                'weekly'
            ),
            self::card(
                'Blockages Cleared',
                'blockages_cleared',
                'bi-check-circle',
                'green',
                'Reported drain blockages cleared during maintenance operations.',
                '',
                'weekly'
            ),
            self::card(
                'Stagnant Water Points',
                'stagnant_water',
                'bi-droplet',
                'yellow',
                'Locations with stagnant water due to poor drainage identified.',
                '',
                'weekly'
            ),
            self::card(
                'Maintenance Compliance Rate',
                'compliance_rate',
                'bi-percent',
                'green',
                'Share of reported blockages cleared within the target timeline.',
                'Blockages cleared ÷ blockages reported × 100',
                'weekly'
            ),
            self::card(
                'Special Branch Points Reported',
                'sb_points',
                'bi-flag',
                'blue',
                'Drain and sewerage issues reported through Special Branch.',
                '',
                'weekly'
            ),
            self::card(
                'Inspections Validation Target',
                'validation_target',
                'bi-bullseye',
                'purple',
                'Target number of drain maintenance reports to validate.',
                '',
                'weekly'
            ),
            self::card(
                'Inspections Validated',
                'validations_completed',
                'bi-clipboard2-check',
                'blue',
                'Drain maintenance reports validated by supervisory officers.',
                '',
                'weekly'
            ),
            self::card(
                'Approved / Rejected Validations',
                'approved_rejected_validations',
                'bi-patch-check',
                'green',
                'Drain maintenance validations approved or rejected.',
                'Approved validations + rejected validations',
                'weekly'
            ),
        ];
    }

    /** @return list<array<string, string>> */
    private static function busTerminalsCards(): array
    {
        return [
            self::card(
                'Total Bus Terminals',
                'total_terminals',
                'bi-bus-front',
                'blue',
                'Bus terminals in the district baseline for facility inspection.',
                '',
                'yearly'
            ),
            self::card(
                'AC Visits Completed',
                'ac_visits',
                'bi-person-check',
                'blue',
                'Assistant Commissioner visits to bus terminals in the period.',
                '',
                'monthly'
            ),
            self::card(
                'Required Terminal Visits',
                'required_visits',
                'bi-bullseye',
                'purple',
                'Monthly target for bus terminal facility inspections.',
                '',
                'monthly'
            ),
            self::card(
                'Fare Display Checked',
                'fare_display_checked',
                'bi-cash',
                'blue',
                'Terminals where fare boards were verified as displayed and accurate.',
                '',
                'monthly'
            ),
            self::card(
                'Waiting Area Checked',
                'waiting_area_checked',
                'bi-bench',
                'blue',
                'Terminals where passenger waiting areas were inspected.',
                '',
                'monthly'
            ),
            self::card(
                'Drinking Water / Washroom Checked',
                'facilities_checked',
                'bi-droplet',
                'blue',
                'Terminals where drinking water and washroom facilities were inspected.',
                '',
                'monthly'
            ),
            self::card(
                'Cleanliness Checked',
                'cleanliness_checked',
                'bi-stars',
                'green',
                'Terminals where cleanliness standards were verified.',
                '',
                'monthly'
            ),
            self::card(
                'Terminal Compliance Rate',
                'compliance_rate',
                'bi-percent',
                'green',
                'Share of terminal facility checks meeting minimum standards.',
                'Compliant checks ÷ total checks × 100',
                'monthly'
            ),
            self::card(
                'Special Branch Points Reported',
                'sb_points',
                'bi-flag',
                'blue',
                'Bus terminal facility complaints reported through Special Branch.',
                '',
                'monthly'
            ),
            self::card(
                'Inspections Validation Target',
                'validation_target',
                'bi-bullseye',
                'purple',
                'Target number of bus terminal inspection reports to validate.',
                '',
                'monthly'
            ),
            self::card(
                'Inspections Validated',
                'validations_completed',
                'bi-clipboard2-check',
                'blue',
                'Bus terminal inspection reports validated by AC/DC.',
                '',
                'monthly'
            ),
            self::card(
                'Approved / Rejected Validations',
                'approved_rejected_validations',
                'bi-patch-check',
                'green',
                'Bus terminal inspection validations approved or rejected.',
                'Approved validations + rejected validations',
                'monthly'
            ),
        ];
    }

    /** @return list<array<string, string>> */
    private static function shopsAndHandcartsCards(): array
    {
        return [
            self::card(
                'Markets Inspected',
                'markets_inspected',
                'bi-shop',
                'blue',
                'Markets inspected for shop and handcart regulation compliance.',
                '',
                'weekly'
            ),
            self::card(
                'Weekly Market Inspection Target',
                'inspection_target',
                'bi-bullseye',
                'purple',
                'Weekly target for market regulation inspections across the district.',
                '',
                'weekly'
            ),
            self::card(
                'Shops Checked',
                'shops_checked',
                'bi-building',
                'blue',
                'Fixed shops checked for licensing, pricing and placement rules.',
                '',
                'weekly'
            ),
            self::card(
                'Handcarts Checked',
                'handcarts_checked',
                'bi-cart',
                'blue',
                'Mobile handcarts and vendors checked for regulation compliance.',
                '',
                'weekly'
            ),
            self::card(
                'Violations Found',
                'violations_found',
                'bi-exclamation-triangle',
                'red',
                'Regulatory violations found during shop and handcart inspections.',
                '',
                'weekly'
            ),
            self::card(
                'Enforcement Actions Taken',
                'actions_taken',
                'bi-gavel',
                'yellow',
                'Warnings, removals or other actions on violating shops and handcarts.',
                '',
                'weekly'
            ),
            self::card(
                'Compliance Rate',
                'compliance_rate',
                'bi-percent',
                'green',
                'Share of checked shops and handcarts found compliant.',
                'Compliant vendors ÷ vendors checked × 100',
                'weekly'
            ),
            self::card(
                'Inspections Validation Target',
                'validation_target',
                'bi-bullseye',
                'purple',
                'Target number of market regulation inspections to validate.',
                '',
                'weekly'
            ),
            self::card(
                'Inspections Validated',
                'validations_completed',
                'bi-clipboard2-check',
                'blue',
                'Market regulation inspection reports validated by AC/DC.',
                '',
                'weekly'
            ),
            self::card(
                'Approved / Rejected Validations',
                'approved_rejected_validations',
                'bi-patch-check',
                'green',
                'Market regulation validations approved or rejected.',
                'Approved validations + rejected validations',
                'weekly'
            ),
        ];
    }

    /** @return list<array<string, string>> */
    private static function eBizCards(): array
    {
        return [
            self::card(
                'e-Biz Applications Received',
                'applications_received',
                'bi-inbox',
                'blue',
                'Business registration and licensing applications received via e-Biz.',
                '',
                'daily'
            ),
            self::card(
                'Applications Pending',
                'pending_applications',
                'bi-hourglass-split',
                'yellow',
                'e-Biz applications awaiting review or approval.',
                '',
                'daily'
            ),
            self::card(
                'Applications Completed',
                'applications_completed',
                'bi-check-circle',
                'green',
                'e-Biz applications processed and disposed within the period.',
                '',
                'daily'
            ),
            self::card(
                'Help Desk Inspections',
                'help_desk_inspections',
                'bi-headset',
                'blue',
                'Inspections of e-Biz help desks and citizen service counters.',
                '',
                'monthly'
            ),
            self::card(
                'DC Review Meeting Held',
                'dc_meeting_held',
                'bi-people',
                'blue',
                'DC-led review meeting on e-Biz service delivery held in the period.',
                '',
                'monthly'
            ),
            self::card(
                'Timeline Compliance Rate',
                'disposal_rate',
                'bi-percent',
                'green',
                'Share of applications disposed within the prescribed e-Biz timeline.',
                'On-time disposals ÷ applications completed × 100',
                'daily'
            ),
            self::card(
                'Service Type Breakdown Tracked',
                'service_types_tracked',
                'bi-diagram-3',
                'blue',
                'Distinct e-Biz service types with activity reported in the period.',
                '',
                'mixed'
            ),
            self::card(
                'Inspections Validation Target',
                'validation_target',
                'bi-bullseye',
                'purple',
                'Target number of e-Biz help desk inspections to validate.',
                '',
                'monthly'
            ),
            self::card(
                'Inspections Validated',
                'validations_completed',
                'bi-clipboard2-check',
                'blue',
                'e-Biz help desk inspection reports validated by supervisory officers.',
                '',
                'monthly'
            ),
            self::card(
                'Approved / Rejected Validations',
                'approved_rejected_validations',
                'bi-patch-check',
                'green',
                'e-Biz inspection validations approved or rejected.',
                'Approved validations + rejected validations',
                'monthly'
            ),
        ];
    }

    /**
     * @return array{
     *     label: string,
     *     field: string,
     *     icon: string,
     *     tone: string,
     *     description: string,
     *     formula_text: string,
     *     target_frequency: string
     * }
     */
    private static function card(
        string $label,
        string $field,
        string $icon,
        string $tone,
        string $description = '',
        string $formulaText = '',
        string $targetFrequency = 'mixed'
    ): array {
        return [
            'label' => $label,
            'field' => $field,
            'icon' => $icon,
            'tone' => $tone,
            'description' => $description,
            'formula_text' => $formulaText,
            'target_frequency' => $targetFrequency,
        ];
    }
}
