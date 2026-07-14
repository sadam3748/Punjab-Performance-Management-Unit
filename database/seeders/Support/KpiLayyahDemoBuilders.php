<?php

namespace Database\Seeders\Support;

use App\Services\KpiPeriodService;
use Carbon\Carbon;
use Illuminate\Support\Str;

class KpiLayyahDemoBuilders
{
    private const KAROR = [
        'division_id' => 2,
        'district_id' => 7,
        'tehsil_id' => 25,
        'lat' => 30.9520,
        'lng' => 70.9280,
        'area' => 'Karor Lal Esan',
        'tehsil_name' => 'Karor Lal Esan',
        'district_name' => 'Layyah',
        'inspector' => 'ac.karor',
        'reviewer' => 'dc.layyah',
        'primary' => false,
    ];

    private const LAYYAH = [
        'division_id' => 2,
        'district_id' => 7,
        'tehsil_id' => 24,
        'lat' => 30.9617,
        'lng' => 70.9397,
        'area' => 'Layyah',
        'tehsil_name' => 'Layyah',
        'district_name' => 'Layyah',
        'inspector' => 'ac.layyah',
        'reviewer' => 'dc.layyah',
        'primary' => true,
    ];

    private const CHAUBARA = [
        'division_id' => 2,
        'district_id' => 7,
        'tehsil_id' => 26,
        'lat' => 30.9005,
        'lng' => 71.6512,
        'area' => 'Chaubara',
        'tehsil_name' => 'Chaubara',
        'district_name' => 'Layyah',
        'inspector' => 'dc.layyah',
        'reviewer' => 'dc.layyah',
        'primary' => false,
    ];

    private const LAHORE_CITY = [
        'division_id' => 6,
        'district_id' => 23,
        'tehsil_id' => 81,
        'lat' => 31.5204,
        'lng' => 74.3587,
        'area' => 'Lahore',
        'tehsil_name' => 'Lahore City',
        'district_name' => 'Lahore',
        'inspector' => 'ac.lahore',
        'reviewer' => 'dc.lahore',
        'primary' => false,
    ];

    /** @var list<string> */
    private const EXCLUDED_SLUGS = [
        'inspection-of-health-facilities',
        'inspection-of-educational-institutions',
    ];

    /**
     * @var array<string, array{frequency: string, karor: int, layyah: int, chaubara: int, lahore: int}>
     */
    private const SLUG_PLANS = [
        'price-of-roti' => ['frequency' => 'daily', 'layyah' => 6, 'karor' => 6, 'chaubara' => 1, 'lahore' => 2],
        'price-of-plain-bakery-bread' => ['frequency' => 'daily', 'layyah' => 3, 'karor' => 3, 'chaubara' => 1, 'lahore' => 2],
        'price-control-of-essential-commodities' => ['frequency' => 'daily', 'layyah' => 21, 'karor' => 21, 'chaubara' => 2, 'lahore' => 3],
        'repair-of-small-roads-in-both-urban-and-rural-areas' => ['frequency' => 'weekly', 'layyah' => 1, 'karor' => 1, 'chaubara' => 0, 'lahore' => 2],
        'dysfunctional-streetlights' => ['frequency' => 'weekly', 'layyah' => 2, 'karor' => 2, 'chaubara' => 1, 'lahore' => 2],
        'covering-of-manholes' => ['frequency' => 'weekly', 'layyah' => 3, 'karor' => 3, 'chaubara' => 1, 'lahore' => 2],
        'functional-and-clean-water-filtration-plants' => ['frequency' => 'weekly', 'layyah' => 2, 'karor' => 2, 'chaubara' => 1, 'lahore' => 2],
        'violation-of-marriage-functions-act' => ['frequency' => 'weekly', 'layyah' => 3, 'karor' => 3, 'chaubara' => 1, 'lahore' => 2],
        'anti-encroachment-campaign' => ['frequency' => 'daily', 'layyah' => 1, 'karor' => 1, 'chaubara' => 1, 'lahore' => 2],
        'regulation-of-shops-and-handcarts' => ['frequency' => 'daily', 'layyah' => 1, 'karor' => 1, 'chaubara' => 0, 'lahore' => 2],
        'stray-dogs' => ['frequency' => 'daily', 'layyah' => 1, 'karor' => 1, 'chaubara' => 1, 'lahore' => 2],
        'removal-of-wall-chalking' => ['frequency' => 'daily', 'layyah' => 1, 'karor' => 1, 'chaubara' => 0, 'lahore' => 2],
        'graveyards' => ['frequency' => 'weekly', 'layyah' => 2, 'karor' => 2, 'chaubara' => 1, 'lahore' => 2],
        'zebra-crossings' => ['frequency' => 'weekly', 'layyah' => 5, 'karor' => 5, 'chaubara' => 1, 'lahore' => 3],
        'illegal-decanting' => ['frequency' => 'weekly', 'layyah' => 15, 'karor' => 15, 'chaubara' => 2, 'lahore' => 3],
        'suthra-punjab-campaign' => ['frequency' => 'weekly', 'layyah' => 4, 'karor' => 4, 'chaubara' => 1, 'lahore' => 2],
        'maintenance-of-greenbelts' => ['frequency' => 'weekly', 'layyah' => 3, 'karor' => 3, 'chaubara' => 1, 'lahore' => 2],
        'maintenance-of-drains-and-sewerage-lines' => ['frequency' => 'weekly', 'layyah' => 4, 'karor' => 4, 'chaubara' => 1, 'lahore' => 2],
        'bus-terminals' => ['frequency' => 'weekly', 'layyah' => 2, 'karor' => 2, 'chaubara' => 1, 'lahore' => 2],
        'chief-ministers-complaint-cell' => ['frequency' => 'daily', 'layyah' => 12, 'karor' => 12, 'chaubara' => 2, 'lahore' => 3],
        'e-biz' => ['frequency' => 'weekly', 'layyah' => 6, 'karor' => 6, 'chaubara' => 1, 'lahore' => 2],
    ];

    /** @return list<string> */
    public static function supportedSlugs(): array
    {
        return array_keys(self::SLUG_PLANS);
    }

    public static function supports(string $slug): bool
    {
        return isset(self::SLUG_PLANS[$slug]) && ! in_array($slug, self::EXCLUDED_SLUGS, true);
    }

    /**
     * @return array{0: list<array<string,mixed>>, 1: list<array<string,mixed>>}|null
     */
    public static function build(string $slug, object $card, $users, int &$refCounter, Carbon $now, string $batch): ?array
    {
        if (! self::supports($slug)) {
            return null;
        }

        $plan = self::SLUG_PLANS[$slug];
        $statuses = self::statusSequence();
        $entities = self::entitiesForSlug($slug, (string) $card->title);
        $rows = [];
        $attachments = [];
        $globalIndex = 0;

        foreach (self::tehsilEntriesForPlan($plan) as $entry) {
            $side = $entry['side'];
            $count = $entry['count'];
            $isPrimaryLayyah = $side['tehsil_id'] === self::LAYYAH['tehsil_id'];
            $inspector = $users->get($side['inspector']);
            $reviewer = $users->get($side['reviewer']);

            for ($i = 0; $i < $count; $i++) {
                $status = $statuses[($globalIndex + $i) % count($statuses)];
                if ($slug === 'price-of-roti' && $isPrimaryLayyah) {
                    $status = match ($i) {
                        0, 1, 2 => 'pending_review',
                        3 => 'approved',
                        4 => 'rejected',
                        default => 'approved',
                    };
                }
                $inspectedAt = self::inspectionDateForRecord(
                    $plan['frequency'],
                    $isPrimaryLayyah,
                    $i,
                    $globalIndex,
                );
                $entity = $entities[($globalIndex + $i) % count($entities)];
                $reference = sprintf('INSP-%s-%06d', $now->format('Y'), $refCounter++);
                $detailData = self::enhancedDetailData($slug, $globalIndex + $i, $entity);
                $location = self::locationFor($side, $globalIndex + $i);
                $fullAddress = self::fullAddress($side, $entity, $location);

                $rows[] = [
                    'uuid' => (string) Str::uuid(),
                    'reference_no' => $reference,
                    'kpi_card_id' => $card->id,
                    'kpi_submission_id' => null,
                    'division_id' => $side['division_id'],
                    'district_id' => $side['district_id'],
                    'tehsil_id' => $side['tehsil_id'],
                    'inspected_by' => $inspector?->id,
                    'reviewed_by' => $status === 'pending_review' ? null : $reviewer?->id,
                    'inspection_title' => $entity['title'],
                    'entity_name' => $entity['name'],
                    'entity_type' => $entity['type'],
                    'identifier' => $entity['id'].'-'.$side['tehsil_id'].'-'.$i,
                    'address' => $fullAddress,
                    'latitude' => $location['lat'],
                    'longitude' => $location['lng'],
                    'inspection_datetime' => $inspectedAt,
                    'status' => $status,
                    'observations' => json_encode(self::observationsFor($slug, $entity, $card->title)),
                    'actions_required' => json_encode($status === 'rejected'
                      ? ['Re-inspection required within 7 days.', 'Submit corrective action report to district office.']
                      : ['Continue routine monitoring during current reporting week.']),
                    'actions_taken' => json_encode($status !== 'pending_review'
                      ? ['Evidence uploaded and checklist completed.', 'Location coordinates captured during visit.']
                      : ['Preliminary site visit completed.']),
                    'detail_data' => json_encode($detailData),
                    'review_remarks' => $status === 'approved' ? 'Inspection evidence verified and accepted.' : null,
                    'rejection_reason' => $status === 'rejected' ? 'Evidence incomplete or compliance below required threshold.' : null,
                    'reviewed_at' => $status === 'pending_review' ? null : $inspectedAt->copy()->addHours(6),
                    'is_demo' => true,
                    'seed_batch' => $batch,
                    'created_at' => $inspectedAt,
                    'updated_at' => $status === 'pending_review' ? $inspectedAt : $inspectedAt->copy()->addHours(6),
                ];

                $attachments[] = [
                    'reference_no' => $reference,
                    'slug' => $slug,
                    'count' => 1 + (($globalIndex + $i) % 3),
                    'ts' => $inspectedAt,
                ];
            }

            $globalIndex += $count;
        }

        return [$rows, $attachments];
    }

    /**
     * @param  array{frequency: string, karor: int, layyah: int, chaubara: int, lahore: int}  $plan
     * @return list<array{side: array<string, mixed>, count: int}>
     */
    private static function tehsilEntriesForPlan(array $plan): array
    {
        $entries = [];

        if ($plan['layyah'] > 0) {
            $entries[] = ['side' => self::LAYYAH, 'count' => $plan['layyah']];
        }
        if ($plan['karor'] > 0) {
            $entries[] = ['side' => self::KAROR, 'count' => $plan['karor']];
        }
        if ($plan['chaubara'] > 0) {
            $entries[] = ['side' => self::CHAUBARA, 'count' => $plan['chaubara']];
        }
        if ($plan['lahore'] > 0) {
            $entries[] = ['side' => self::LAHORE_CITY, 'count' => $plan['lahore']];
        }

        return $entries;
    }

    private static function inspectionDateForRecord(
        string $frequency,
        bool $isPrimaryLayyah,
        int $localIndex,
        int $globalIndex,
    ): Carbon {
        unset($isPrimaryLayyah);

        if ($frequency === 'daily') {
            $slot = $globalIndex + $localIndex;
            $hour = 9 + ($slot % 8);
            $minute = 15 * ($slot % 4);

            return self::todayInspectionDateInActiveWeek(min(16, $hour), $minute);
        }

        return self::latestCompletedWeekDateForIndex($globalIndex + $localIndex);
    }

    /** @return list<string> */
    private static function statusSequence(): array
    {
        return [
            'approved', 'approved', 'pending_review', 'approved', 'rejected',
            'approved', 'approved', 'approved', 'approved', 'pending_review',
            'approved', 'approved', 'pending_review', 'approved', 'approved',
            'approved', 'approved', 'rejected', 'approved', 'pending_review',
        ];
    }

    /** @return list<string> */
    private static function observationsFor(string $slug, array $entity, string $cardTitle): array
    {
        $base = 'Field verification completed at '.$entity['name'].'.';

        return match ($slug) {
            'price-of-roti' => [$base, 'Tandoor price and roti weight verified on site.'],
            'price-of-plain-bakery-bread' => [$base, 'Bakery bread price and availability checked.'],
            'price-control-of-essential-commodities' => [$base, 'Essential commodity rates compared with notified prices.'],
            'chief-ministers-complaint-cell' => [$base, 'Citizen complaint progress reviewed against CMCC timeline.'],
            'illegal-decanting' => [$base, 'LPG decanting safety and licensing compliance verified.'],
            'anti-encroachment-campaign' => [$base, 'Encroachment clearance progress documented with photographs.'],
            default => [
                $base,
                'Compliance indicators reviewed against '.$cardTitle.' standards.',
                'Local conditions documented with photographic evidence.',
            ],
        };
    }

    /**
     * @return array<string, mixed>
     */
    private static function enhancedDetailData(string $slug, int $index, array $entity): array
    {
        $detail = KpiInspectionDetailFactory::forSlug($slug, $index);

        if ($slug === 'price-control-of-essential-commodities') {
            $commodities = ['Flour', 'Sugar', 'Ghee', 'Pulses', 'Vegetables', 'Milk', 'Flour', 'Sugar', 'Ghee'];
            $detail['commodity'] = $commodities[$index % count($commodities)];
        }

        if ($slug === 'maintenance-of-greenbelts') {
            $types = ['Park', 'Greenbelt', 'Beautification Initiative', 'Park', 'Greenbelt', 'Road'];
            $detail['type'] = $types[$index % count($types)];
        }

        $detail['issue_summary'] = self::issueSummaryFor($slug, $detail, $entity);

        return $detail;
    }

    /**
     * @param  array<string, mixed>  $detail
     */
    private static function issueSummaryFor(string $slug, array $detail, array $entity): string
    {
        return match ($slug) {
            'price-of-roti' => sprintf(
                '%s at %s — fine PKR %s (%s).',
                $detail['violation'] ?? 'Compliance check',
                $entity['name'],
                number_format((int) ($detail['fine'] ?? 0)),
                $detail['payment_status'] ?? 'Pending',
            ),
            'price-of-plain-bakery-bread' => sprintf(
                'Bakery bread %s noted at %s; fine PKR %s.',
                $detail['violation'] ?? 'violation',
                $entity['name'],
                number_format((int) ($detail['fine'] ?? 0)),
            ),
            'price-control-of-essential-commodities' => sprintf(
                '%s %s at %s; fine PKR %s.',
                $detail['commodity'] ?? 'Commodity',
                $detail['violation'] ?? 'violation',
                $entity['name'],
                number_format((int) ($detail['fine'] ?? 0)),
            ),
            'dysfunctional-streetlights' => sprintf(
                '%d of %d streetlights dysfunctional; %d repaired during visit.',
                (int) ($detail['dysfunctional_lights'] ?? 0),
                (int) ($detail['total_lights'] ?? 0),
                (int) ($detail['repaired_lights'] ?? 0),
            ),
            'covering-of-manholes' => sprintf(
                '%d open manholes identified; %d covered during inspection.',
                (int) ($detail['open_manholes'] ?? 0),
                (int) ($detail['covered_manholes'] ?? 0),
            ),
            'functional-and-clean-water-filtration-plants' => sprintf(
                '%s plant at %s — %s, cleanliness %s.',
                $detail['plant_type'] ?? 'RO',
                $entity['name'],
                $detail['functional_status'] ?? 'Unknown',
                $detail['cleanliness_status'] ?? 'Unknown',
            ),
            'illegal-decanting' => sprintf(
                '%s at %s — %s imposed, fine PKR %s.',
                $detail['violation'] ?? 'Violation',
                $entity['name'],
                $detail['action_type'] ?? 'Fine',
                number_format((int) ($detail['fine'] ?? 0)),
            ),
            'chief-ministers-complaint-cell' => sprintf(
                'Complaint %s — status %s (%s).',
                $entity['name'],
                $detail['complaint_status'] ?? 'Pending',
                $detail['overdue_status'] ?? 'On Time',
            ),
            'e-biz' => sprintf(
                '%s application %s — %s timeline (%d pending).',
                $detail['service_type'] ?? 'Service',
                $detail['application_no'] ?? 'N/A',
                $detail['timeline_compliance'] ?? 'Compliant',
                (int) ($detail['pending_cases'] ?? 0),
            ),
            'zebra-crossings' => sprintf(
                'Zebra crossing %s at %s — %s.',
                strtolower((string) ($detail['crossing_status'] ?? 'marked')),
                $entity['name'],
                match (strtolower((string) ($detail['crossing_status'] ?? ''))) {
                    'marked', 'repainted', 'restored', 'visible' => 'Compliant / restored',
                    'faded' => 'Needs repaint',
                    'missing', 'absent' => 'Action required',
                    default => ($detail['inspection_status'] ?? 'Inspected'),
                },
            ),
            'repair-of-small-roads-in-both-urban-and-rural-areas' => sprintf(
                '%s on %s — %s.',
                $detail['repair_type'] ?? 'Patching',
                $entity['name'],
                $detail['completion_status'] ?? 'Pending',
            ),
            default => sprintf('Routine inspection at %s with field evidence captured.', $entity['name']),
        };
    }

    /**
     * @return list<array{title: string, name: string, type: string, id: string, address: string}>
     */
    private static function entitiesForSlug(string $slug, string $title): array
    {
        return match ($slug) {
            'price-of-roti' => [
                ['title' => 'Tandoor Price Inspection', 'name' => 'Hussaini Tandoor Fatehpur Road', 'type' => 'Tandoor', 'id' => 'TN-LAY-201', 'address' => 'Fatehpur Road, Layyah'],
                ['title' => 'Roti Price Compliance', 'name' => 'Madina Nan Shop Chowk Azam', 'type' => 'Tandoor', 'id' => 'TN-LAY-202', 'address' => 'Chowk Azam Road, Layyah'],
                ['title' => 'Community Tandoor Check', 'name' => 'City Tandoor Railway Road', 'type' => 'Tandoor', 'id' => 'TN-LAY-203', 'address' => 'Railway Road, Layyah'],
                ['title' => 'Hotel Roti Check', 'name' => 'Thal Hotel Kitchen Layyah', 'type' => 'Hotel', 'id' => 'HT-LAY-301', 'address' => 'College Road, Layyah'],
                ['title' => 'Tandoor Price Inspection', 'name' => 'Al-Madina Tandoor Karor Bazar', 'type' => 'Tandoor', 'id' => 'TN-KLE-101', 'address' => 'Main Bazar, Karor Lal Esan'],
                ['title' => 'Roti Price Compliance', 'name' => 'Bismillah Nan Shop Kot Addu Road', 'type' => 'Tandoor', 'id' => 'TN-KLE-102', 'address' => 'Kot Addu Road, Karor'],
                ['title' => 'Tandoor Inspection', 'name' => 'Chaubara Community Tandoor', 'type' => 'Tandoor', 'id' => 'TN-CBR-401', 'address' => 'Chaubara Bazar'],
            ],
            'price-of-plain-bakery-bread' => [
                ['title' => 'Bakery Bread Price Check', 'name' => 'Karor City Bakery', 'type' => 'Bakery', 'id' => 'BK-KLE-101', 'address' => 'Civil Lines, Karor'],
                ['title' => 'Plain Bread Compliance', 'name' => 'Layyah Sweets & Bakery', 'type' => 'Bakery', 'id' => 'BK-LAY-201', 'address' => 'Chowk Azam Road, Layyah'],
                ['title' => 'Bakery Inspection', 'name' => 'Millat Bakery Chaubara', 'type' => 'Bakery', 'id' => 'BK-CBR-301', 'address' => 'Chaubara Main Road'],
            ],
            'price-control-of-essential-commodities' => [
                ['title' => 'Utility Store Inspection', 'name' => 'Utility Store Karor Lal Esan', 'type' => 'Utility Store', 'id' => 'US-KLE-101', 'address' => 'Tehsil Complex Road, Karor'],
                ['title' => 'General Store Check', 'name' => 'Al-Fatah General Store', 'type' => 'General Store', 'id' => 'GS-KLE-102', 'address' => 'Main Bazar, Karor'],
                ['title' => 'Flour Dealer Inspection', 'name' => 'Thal Flour Mills Dealer', 'type' => 'Dealer', 'id' => 'DL-LAY-201', 'address' => 'Industrial Area, Layyah'],
                ['title' => 'Grocery Market Visit', 'name' => 'Layyah Sabzi Mandi', 'type' => 'Market', 'id' => 'MK-LAY-202', 'address' => 'Sabzi Mandi, Layyah'],
                ['title' => 'Commodity Price Check', 'name' => 'Chaubara Wholesale Market', 'type' => 'Wholesale', 'id' => 'WS-CBR-301', 'address' => 'Chaubara Bazar'],
                ['title' => 'Milk Shop Inspection', 'name' => 'Dairy Point Karor', 'type' => 'Dairy', 'id' => 'DY-KLE-103', 'address' => 'Hospital Road, Karor'],
                ['title' => 'Sugar Dealer Visit', 'name' => 'Layyah Sugar & Ghee Store', 'type' => 'Dealer', 'id' => 'DL-LAY-203', 'address' => 'Bazaar Road, Layyah'],
            ],
            'repair-of-small-roads-in-both-urban-and-rural-areas' => [
                ['title' => 'Rural Lane Repair', 'name' => 'Chak 112/TDA Access Lane', 'type' => 'Rural Road', 'id' => 'RD-LAY-201', 'address' => 'Chak 112/TDA, Layyah'],
                ['title' => 'Road Patchwork Inspection', 'name' => 'Karor Bazar Link Road', 'type' => 'Urban Road', 'id' => 'RD-KLE-101', 'address' => 'Karor Bazar to Tehsil Road'],
                ['title' => 'Lane Marking Visit', 'name' => 'Layyah College Road Shoulder', 'type' => 'Urban Road', 'id' => 'RD-LAY-202', 'address' => 'College Road, Layyah'],
            ],
            'dysfunctional-streetlights' => [
                ['title' => 'Streetlight Survey', 'name' => 'Karor Hospital Road Pole Line', 'type' => 'Street Lighting', 'id' => 'SL-KLE-101', 'address' => 'Hospital Road, Karor'],
                ['title' => 'Streetlight Maintenance', 'name' => 'Layyah Civil Lines Avenue', 'type' => 'Street Lighting', 'id' => 'SL-LAY-201', 'address' => 'Civil Lines, Layyah'],
                ['title' => 'Pole Inspection', 'name' => 'Chaubara Main Road Lights', 'type' => 'Street Lighting', 'id' => 'SL-CBR-301', 'address' => 'Main Road, Chaubara'],
            ],
            'covering-of-manholes' => [
                ['title' => 'Open Manhole Inspection', 'name' => 'Layyah Model Town Drains UC-1', 'type' => 'Sewerage', 'id' => 'MH-LAY-201', 'address' => 'Model Town, Layyah'],
                ['title' => 'Manhole Covering Drive', 'name' => 'Layyah Bazaar Road UC-2', 'type' => 'Sewerage', 'id' => 'MH-LAY-202', 'address' => 'Bazaar Road, Layyah'],
                ['title' => 'Manhole Safety Check', 'name' => 'Karor Bazar Sewer Line', 'type' => 'Sewerage', 'id' => 'MH-KLE-101', 'address' => 'Main Bazar, Karor'],
                ['title' => 'Manhole Coverage Visit', 'name' => 'Chaubara UC Drainage', 'type' => 'Sewerage', 'id' => 'MH-CBR-301', 'address' => 'UC Office Road, Chaubara'],
            ],
            'functional-and-clean-water-filtration-plants' => [
                ['title' => 'RO Plant Inspection', 'name' => 'Layyah City Filtration Plant', 'type' => 'Filtration Plant', 'id' => 'WF-LAY-201', 'address' => 'Canal View Road, Layyah'],
                ['title' => 'UF Plant Visit', 'name' => 'Model Town RO Plant Layyah', 'type' => 'Filtration Plant', 'id' => 'WF-LAY-202', 'address' => 'Model Town, Layyah'],
                ['title' => 'Water Plant Check', 'name' => 'Karor UC Water Filtration Plant', 'type' => 'Filtration Plant', 'id' => 'WF-KLE-101', 'address' => 'UC Karor, Layyah'],
                ['title' => 'RO Plant Inspection', 'name' => 'Chaubara Community RO Plant', 'type' => 'Filtration Plant', 'id' => 'WF-CBR-301', 'address' => 'Chaubara'],
            ],
            'violation-of-marriage-functions-act' => [
                ['title' => 'Marriage Hall Inspection', 'name' => 'Al-Saeed Marriage Hall Karor', 'type' => 'Marriage Hall', 'id' => 'MH-KLE-201', 'address' => 'Fatehpur Road, Karor'],
                ['title' => 'Function Compliance Check', 'name' => 'Layyah Royal Banquet', 'type' => 'Banquet', 'id' => 'BN-LAY-201', 'address' => 'Bypass Road, Layyah'],
                ['title' => 'Wedding Venue Visit', 'name' => 'Chaubara Community Hall', 'type' => 'Community Hall', 'id' => 'CH-CBR-301', 'address' => 'Chaubara'],
            ],
            'anti-encroachment-campaign' => [
                ['title' => 'Encroachment Clearance', 'name' => 'Karor Main Bazar Footpath', 'type' => 'Public Space', 'id' => 'AE-KLE-101', 'address' => 'Main Bazar, Karor'],
                ['title' => 'Anti-Encroachment Drive', 'name' => 'Layyah Chowk Azam Market', 'type' => 'Market', 'id' => 'AE-LAY-201', 'address' => 'Chowk Azam Road, Layyah'],
            ],
            'regulation-of-shops-and-handcarts' => [
                ['title' => 'Rehri Bazar Inspection', 'name' => 'Karor Rehri Bazar Zone', 'type' => 'Rehri Bazar', 'id' => 'RB-KLE-101', 'address' => 'Bazaar Road, Karor'],
                ['title' => 'Handcart Regulation', 'name' => 'Layyah Cart Bazar Layyah City', 'type' => 'Cart Bazar', 'id' => 'CB-LAY-201', 'address' => 'Railway Road, Layyah'],
            ],
            'stray-dogs' => [
                ['title' => 'Stray Dog Control Drive', 'name' => 'Karor UC Catching Zone A', 'type' => 'Field Operation', 'id' => 'SD-KLE-101', 'address' => 'UC Karor periphery'],
                ['title' => 'Dog Vaccination Campaign', 'name' => 'Layyah City Ward 3', 'type' => 'Field Operation', 'id' => 'SD-LAY-201', 'address' => 'Ward 3, Layyah'],
            ],
            'removal-of-wall-chalking' => [
                ['title' => 'Wall Chalking Removal', 'name' => 'Karor Tehsil Complex Walls', 'type' => 'Public Building', 'id' => 'WC-KLE-101', 'address' => 'Tehsil Complex, Karor'],
                ['title' => 'Banner Removal Drive', 'name' => 'Layyah Main Chowk Walls', 'type' => 'Public Space', 'id' => 'WC-LAY-201', 'address' => 'Main Chowk, Layyah'],
            ],
            'graveyards' => [
                ['title' => 'Graveyard Standards Visit', 'name' => 'Karor Lal Esan Graveyard', 'type' => 'Graveyard', 'id' => 'GY-KLE-101', 'address' => 'Karor outskirts'],
                ['title' => 'Graveyard Maintenance', 'name' => 'Layyah City Graveyard', 'type' => 'Graveyard', 'id' => 'GY-LAY-201', 'address' => 'Fatehpur Road, Layyah'],
                ['title' => 'Graveyard Inspection', 'name' => 'Chaubara Community Graveyard', 'type' => 'Graveyard', 'id' => 'GY-CBR-301', 'address' => 'Chaubara'],
            ],
            'zebra-crossings' => [
                ['title' => 'School Zebra Crossing Visit', 'name' => 'Govt. Boys High School Layyah', 'type' => 'School Crossing', 'id' => 'ZC-LAY-101', 'address' => 'College Road, Layyah'],
                ['title' => 'School Zebra Crossing Visit', 'name' => 'Govt. Girls High School Layyah', 'type' => 'School Crossing', 'id' => 'ZC-LAY-102', 'address' => 'Civil Lines, Layyah'],
                ['title' => 'School Zebra Crossing Visit', 'name' => 'Govt. Primary School Model Town Layyah', 'type' => 'School Crossing', 'id' => 'ZC-LAY-103', 'address' => 'Model Town, Layyah'],
                ['title' => 'School Zebra Crossing Visit', 'name' => 'Govt. High School Karor Lal Esan', 'type' => 'School Crossing', 'id' => 'ZC-KLE-101', 'address' => 'School Road, Karor'],
                ['title' => 'School Zebra Crossing Visit', 'name' => 'Govt. Girls School Karor Front', 'type' => 'School Crossing', 'id' => 'ZC-KLE-102', 'address' => 'Hospital Road, Karor'],
                ['title' => 'School Zebra Crossing Visit', 'name' => 'Govt. Primary School Chaubara', 'type' => 'School Crossing', 'id' => 'ZC-CBR-101', 'address' => 'Chaubara'],
            ],
            'illegal-decanting' => [
                ['title' => 'LPG Decanting Inspection', 'name' => 'Karor Gas Filling Point', 'type' => 'LPG Outlet', 'id' => 'LP-KLE-101', 'address' => 'Bypass Road, Karor'],
                ['title' => 'Illegal Decanting Check', 'name' => 'Layyah LPG Dealer Shop 1', 'type' => 'LPG Dealer', 'id' => 'LP-LAY-201', 'address' => 'Industrial Area, Layyah'],
                ['title' => 'Safety Compliance Visit', 'name' => 'Layyah LPG Dealer Shop 2', 'type' => 'LPG Dealer', 'id' => 'LP-LAY-202', 'address' => 'Grid Station Road, Layyah'],
                ['title' => 'Decanting Raid', 'name' => 'Chaubara Gas Agency', 'type' => 'LPG Agency', 'id' => 'LP-CBR-301', 'address' => 'Chaubara Bazar'],
                ['title' => 'Unlicensed Storage Check', 'name' => 'Karor Roadside LPG Setup', 'type' => 'LPG Outlet', 'id' => 'LP-KLE-102', 'address' => 'Kot Addu Road, Karor'],
            ],
            'suthra-punjab-campaign' => [
                ['title' => 'Suthra Punjab Visit', 'name' => 'Karor UC Cleanliness Zone', 'type' => 'Cleanliness Zone', 'id' => 'SP-KLE-101', 'address' => 'UC Karor, Layyah'],
                ['title' => 'Cleanliness Campaign', 'name' => 'Layyah City Market Strip', 'type' => 'Market', 'id' => 'SP-LAY-201', 'address' => 'Bazaar Road, Layyah'],
                ['title' => 'Suthra Drive', 'name' => 'Chaubara Bazar Cleanup', 'type' => 'Bazar', 'id' => 'SP-CBR-301', 'address' => 'Chaubara'],
            ],
            'maintenance-of-greenbelts' => [
                ['title' => 'Park Maintenance Visit', 'name' => 'Karor Family Park', 'type' => 'Park', 'id' => 'GB-KLE-101', 'address' => 'Canal View, Karor'],
                ['title' => 'Greenbelt Inspection', 'name' => 'Layyah Thal Greenbelt', 'type' => 'Greenbelt', 'id' => 'GB-LAY-201', 'address' => 'Bypass Road, Layyah'],
                ['title' => 'Road Beautification Check', 'name' => 'Chaubara Main Road Median', 'type' => 'Road', 'id' => 'GB-CBR-301', 'address' => 'Main Road, Chaubara'],
            ],
            'maintenance-of-drains-and-sewerage-lines' => [
                ['title' => 'Drain Cleaning Inspection', 'name' => 'Karor Bazar Drain Line', 'type' => 'Drain', 'id' => 'DR-KLE-101', 'address' => 'Main Bazar, Karor'],
                ['title' => 'Sewerage Maintenance', 'name' => 'Layyah Model Town Sewer', 'type' => 'Sewerage', 'id' => 'DR-LAY-201', 'address' => 'Model Town, Layyah'],
                ['title' => 'Stagnant Water Check', 'name' => 'Chaubara UC Drain', 'type' => 'Drain', 'id' => 'DR-CBR-301', 'address' => 'UC Chaubara'],
            ],
            'bus-terminals' => [
                ['title' => 'Bus Terminal Inspection', 'name' => 'Karor Adda Bus Terminal', 'type' => 'Bus Terminal', 'id' => 'BT-KLE-101', 'address' => 'Adda Karor, Layyah'],
                ['title' => 'Terminal Standards Visit', 'name' => 'Layyah City Bus Stand', 'type' => 'Bus Terminal', 'id' => 'BT-LAY-201', 'address' => 'Railway Road, Layyah'],
                ['title' => 'Bus Adda Check', 'name' => 'Chaubara Bus Stop', 'type' => 'Bus Terminal', 'id' => 'BT-CBR-301', 'address' => 'Chaubara'],
            ],
            'chief-ministers-complaint-cell' => [
                ['title' => 'CMCC Complaint Review', 'name' => 'CMCC-LYA-2026-0142 Water Supply', 'type' => 'Complaint', 'id' => 'CM-KLE-101', 'address' => 'Mohalla Islamia, Karor'],
                ['title' => 'CMCC Complaint Follow-up', 'name' => 'CMCC-LYA-2026-0158 Street Damage', 'type' => 'Complaint', 'id' => 'CM-KLE-102', 'address' => 'Hospital Road, Karor'],
                ['title' => 'CMCC Case Inspection', 'name' => 'CMCC-LYA-2026-0171 Sanitation', 'type' => 'Complaint', 'id' => 'CM-LAY-201', 'address' => 'Model Town, Layyah'],
                ['title' => 'CMCC Complaint Visit', 'name' => 'CMCC-LYA-2026-0189 Encroachment', 'type' => 'Complaint', 'id' => 'CM-LAY-202', 'address' => 'Chowk Azam, Layyah'],
                ['title' => 'CMCC Resolution Check', 'name' => 'CMCC-LYA-2026-0203 Electricity', 'type' => 'Complaint', 'id' => 'CM-CBR-301', 'address' => 'Chaubara Bazar'],
                ['title' => 'CMCC Complaint Monitoring', 'name' => 'CMCC-LYA-2026-0217 Drainage', 'type' => 'Complaint', 'id' => 'CM-KLE-103', 'address' => 'Fatehpur Road, Karor'],
            ],
            'e-biz' => [
                ['title' => 'E-Biz District Office Review', 'name' => 'Karor Lal Esan Assistant Commissioner Office', 'type' => 'District Office', 'id' => 'EB-KLE-101', 'address' => 'Tehsil Complex, Karor'],
                ['title' => 'E-Biz Service Desk Visit', 'name' => 'Layyah Deputy Commissioner Office', 'type' => 'District Office', 'id' => 'EB-LAY-201', 'address' => 'DC Office, Layyah'],
                ['title' => 'E-Biz Application Review', 'name' => 'Chaubara Tehsil E-Khidmat Center', 'type' => 'Service Center', 'id' => 'EB-CBR-301', 'address' => 'Tehsil Office, Chaubara'],
                ['title' => 'E-Biz Compliance Check', 'name' => 'Layyah Business Registration Cell', 'type' => 'District Office', 'id' => 'EB-LAY-202', 'address' => 'Civil Lines, Layyah'],
            ],
            default => [
                ['title' => $title.' Site Visit', 'name' => 'Municipal Facility Karor', 'type' => 'Facility', 'id' => 'FAC-KLE-001', 'address' => 'Tehsil Complex Road, Karor'],
                ['title' => $title.' Compliance Check', 'name' => 'Layyah Community Center', 'type' => 'Public Place', 'id' => 'PUB-LAY-014', 'address' => 'Civil Lines, Layyah'],
                ['title' => $title.' Field Review', 'name' => 'Chaubara Ward Office', 'type' => 'Office', 'id' => 'OFF-CBR-021', 'address' => 'UC Office Road, Chaubara'],
            ],
        };
    }

    /**
     * @param  array<string, mixed>  $side
     * @return array{street: string, lat: float, lng: float}
     */
    private static function locationFor(array $side, int $i): array
    {
        $isLahore = $side['tehsil_id'] === self::LAHORE_CITY['tehsil_id'];

        $spots = $isLahore
          ? [
              ['street' => 'Main Boulevard, Gulberg III', 'lat' => 31.5204, 'lng' => 74.3587],
              ['street' => 'Ferozepur Road, Model Town', 'lat' => 31.4834, 'lng' => 74.3250],
              ['street' => 'The Mall Road, Anarkali', 'lat' => 31.5656, 'lng' => 74.3142],
          ]
          : match ($side['tehsil_id']) {
              self::KAROR['tehsil_id'] => [
                  ['street' => 'Main Bazar, Karor Lal Esan', 'lat' => 30.9520, 'lng' => 70.9280],
                  ['street' => 'Kot Addu Road, Karor', 'lat' => 30.9545, 'lng' => 70.9310],
                  ['street' => 'Hospital Road, Karor', 'lat' => 30.9508, 'lng' => 70.9255],
                  ['street' => 'Tehsil Complex Road, Karor', 'lat' => 30.9532, 'lng' => 70.9298],
                  ['street' => 'Fatehpur Road, Karor', 'lat' => 30.9560, 'lng' => 70.9340],
              ],
              self::CHAUBARA['tehsil_id'] => [
                  ['street' => 'Chaubara Main Road', 'lat' => 30.9005, 'lng' => 71.6512],
                  ['street' => 'Chaubara Bazar', 'lat' => 30.9018, 'lng' => 71.6530],
                  ['street' => 'UC Office Road, Chaubara', 'lat' => 30.8992, 'lng' => 71.6495],
              ],
              default => [
                  ['street' => 'Kot Addu Road, Civil Lines', 'lat' => 30.9617, 'lng' => 70.9397],
                  ['street' => 'Chowk Azam Road, City Center', 'lat' => 30.9700, 'lng' => 70.9450],
                  ['street' => 'Railway Road, Layyah City', 'lat' => 30.9580, 'lng' => 70.9325],
                  ['street' => 'College Road, Layyah', 'lat' => 30.9655, 'lng' => 70.9410],
                  ['street' => 'Hospital Road, Layyah', 'lat' => 30.9638, 'lng' => 70.9362],
                  ['street' => 'Bazaar Road, Layyah', 'lat' => 30.9573, 'lng' => 70.9388],
                  ['street' => 'Bypass Road, Layyah', 'lat' => 30.9549, 'lng' => 70.9466],
                  ['street' => 'Model Town Layyah', 'lat' => 30.9714, 'lng' => 70.9429],
              ],
          };

        $spot = $spots[$i % count($spots)];
        $ring = intdiv($i, count($spots));
        $jitterLat = (($i % 7) - 3) * 0.00055 + ($ring * 0.00035);
        $jitterLng = ((($i + 2) % 7) - 3) * 0.00055 + ($ring * 0.00025);

        return [
            'street' => $spot['street'],
            'lat' => round($spot['lat'] + $jitterLat, 7),
            'lng' => round($spot['lng'] + $jitterLng, 7),
        ];
    }

    /**
     * @param  array<string, mixed>  $side
     * @param  array<string, string>  $entity
     * @param  array<string, mixed>  $location
     */
    private static function fullAddress(array $side, array $entity, array $location): string
    {
        $plot = 10 + (crc32($entity['id']) % 180);

        return sprintf(
            'Plot No. %d, %s, Near %s, %s Tehsil, %s District, Punjab, Pakistan',
            $plot,
            $location['street'],
            $entity['name'],
            $side['tehsil_name'],
            $side['district_name'],
        );
    }

    private static function todayInspectionDateInActiveWeek(int $hour, int $minute = 0): Carbon
    {
        return now(config('app.inspection_timezone', 'Asia/Karachi'))
            ->copy()
            ->setTime($hour, $minute, 0)
            ->setTimezone(config('app.timezone', 'UTC'));
    }

    private static function latestCompletedWeekDateForIndex(int $index): Carbon
    {
        $tz = config('app.inspection_timezone', 'Asia/Karachi');
        $databaseTimezone = config('app.timezone', 'UTC');
        $period = app(KpiPeriodService::class);
        $range = $period->getWeekDateRange($period->latestCompletedWeekNo());
        $start = ($range['start'] ?? now($tz)->startOfDay())->copy()->setTimezone($tz);
        $dayOffset = min(6, $index % 7);

        return $start
            ->copy()
            ->addDays($dayOffset)
            ->setTime(9 + ($index % 7), 10 * ($index % 5), 0)
            ->setTimezone($databaseTimezone);
    }
}
