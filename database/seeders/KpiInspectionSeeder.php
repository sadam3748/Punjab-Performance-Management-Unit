<?php

namespace Database\Seeders;

use App\Data\EducationObservationLabels;
use App\Data\HealthObservationLabels;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class KpiInspectionSeeder extends Seeder
{
    private const PER_KPI = 15;

    private const PRIORITY_PER_KPI = 32;

    /** @var list<string> */
    private const VISIT_KPI_SLUGS = [
        'inspection-of-educational-institutions',
        'inspection-of-health-facilities',
    ];

    /** @var list<string> */
    private const PRIORITY_SLUGS = [
        'price-of-roti',
        'inspection-of-educational-institutions',
        'inspection-of-health-facilities',
        'functional-and-clean-water-filtration-plants',
        'chief-ministers-complaint-cell',
        'e-biz',
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
    ];

    private const LAHORE_CANTT = [
        'division_id' => 6,
        'district_id' => 23,
        'tehsil_id' => 82,
        'lat' => 31.5320,
        'lng' => 74.3420,
        'area' => 'Lahore',
        'tehsil_name' => 'Lahore Cantonment',
        'district_name' => 'Lahore',
    ];

    private const LAHORE = self::LAHORE_CITY;

    private const LAYYAH = [
        'division_id' => 2,
        'district_id' => 7,
        'tehsil_id' => 24,
        'lat' => 30.9617,
        'lng' => 70.9397,
        'area' => 'Layyah',
        'tehsil_name' => 'Layyah',
        'district_name' => 'Layyah',
    ];

    public function run(): void
    {
        DB::disableQueryLog();

        $cards = DB::table('kpi_cards')->where('is_active', true)->orderBy('display_order')->get(['id', 'slug', 'title']);
        if ($cards->isEmpty()) {
            return;
        }

        $users = DB::table('users')->where('is_active', true)->get()->keyBy('username');
        $batch = 'ppmu-demo-'.now()->format('Ymd');

        $inspectionRows = [];
        $attachmentPlan = [];
        $refCounter = 1;
        $now = now();

        foreach ($cards as $cardIndex => $card) {
            if (in_array($card->slug, self::VISIT_KPI_SLUGS, true)) {
                [$visitRows, $visitAttachments] = $card->slug === 'inspection-of-health-facilities'
                    ? $this->buildHealthFacilityInspections($card, $users, $refCounter, $now, $batch)
                    : $this->buildEducationInstitutionInspections($card, $users, $refCounter, $now, $batch);
                [$visitRows, $visitAttachments] = $this->placeVisitDataInLatestCompletedWeek(
                    $visitRows,
                    $visitAttachments,
                );
                $inspectionRows = array_merge($inspectionRows, $visitRows);
                $attachmentPlan = array_merge($attachmentPlan, $visitAttachments);

                continue;
            }

            if (\Database\Seeders\Support\KpiLayyahDemoBuilders::supports($card->slug)) {
                [$demoRows, $demoAttachments] = \Database\Seeders\Support\KpiLayyahDemoBuilders::build(
                    $card->slug,
                    $card,
                    $users,
                    $refCounter,
                    $now,
                    $batch,
                );
                $inspectionRows = array_merge($inspectionRows, $demoRows);
                $attachmentPlan = array_merge($attachmentPlan, $demoAttachments);

                continue;
            }

            if ($card->slug === 'land-management-services') {
                continue;
            }

            $statuses = $this->statusSequence();
            $entities = $this->entitiesForSlug($card->slug, $card->title);
            $perKpi = in_array($card->slug, self::PRIORITY_SLUGS, true) ? self::PRIORITY_PER_KPI : self::PER_KPI;

            foreach (range(0, $perKpi - 1) as $i) {
                $side = $this->sideForIndex($i, $card->slug);
                $inspector = $users->get($i % 2 === 0 ? ($side['tehsil_id'] === self::LAYYAH['tehsil_id'] ? 'ac.layyah' : 'ac.lahore') : ($side['tehsil_id'] === self::LAYYAH['tehsil_id'] ? 'dc.layyah' : 'dc.lahore'));
                $reviewer = $users->get($side['tehsil_id'] === self::LAYYAH['tehsil_id'] ? 'dc.layyah' : 'dc.lahore');

                $status = $statuses[$i % count($statuses)];
                $inspectedAt = $this->inspectionDateForIndex($i, $perKpi, in_array($card->slug, self::PRIORITY_SLUGS, true));
                $entity = $entities[$i % count($entities)];
                $reference = sprintf('INSP-%s-%06d', $now->format('Y'), $refCounter++);

                $observations = [
                    'Field verification completed at '.$entity['name'].'.',
                    'Compliance indicators reviewed against '.$card->title.' standards.',
                    'Local conditions documented with photographic evidence.',
                ];

                $actionsRequired = $status === 'rejected'
                    ? ['Re-inspection required within 7 days.', 'Submit corrective action report to district office.']
                    : ['Continue routine monitoring during current reporting week.'];

                $actionsTaken = $status !== 'pending_review'
                    ? ['Evidence uploaded and checklist completed.', 'Location coordinates captured during visit.']
                    : ['Preliminary site visit completed.'];

                $detailData = \Database\Seeders\Support\KpiInspectionDetailFactory::forSlug($card->slug, $i);
                $location = $this->locationFor($side, $i);
                $fullAddress = $this->fullAddress($side, $entity, $location);

                $row = [
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
                    'identifier' => $entity['id'],
                    'address' => $fullAddress,
                    'latitude' => $location['lat'],
                    'longitude' => $location['lng'],
                    'inspection_datetime' => $inspectedAt,
                    'status' => $status,
                    'observations' => json_encode($observations),
                    'actions_required' => json_encode($actionsRequired),
                    'actions_taken' => json_encode($actionsTaken),
                    'detail_data' => json_encode($detailData),
                    'review_remarks' => $status === 'approved' ? 'Inspection evidence verified and accepted.' : null,
                    'rejection_reason' => $status === 'rejected' ? 'Evidence incomplete or compliance below required threshold.' : null,
                    'reviewed_at' => $status === 'pending_review' ? null : $inspectedAt->copy()->addHours(6),
                    'is_demo' => true,
                    'seed_batch' => $batch,
                    'created_at' => $inspectedAt,
                    'updated_at' => $status === 'pending_review' ? $inspectedAt : $inspectedAt->copy()->addHours(6),
                ];

                $inspectionRows[] = $row;
                $attachmentPlan[] = [
                    'reference_no' => $reference,
                    'slug' => $card->slug,
                    'count' => 1 + ($i % 3),
                    'ts' => $inspectedAt,
                ];
            }
        }

        $inspectionRows = array_map(fn (array $row): array => array_replace([
            'selected_for_review' => false,
            'selected_by' => null,
            'selected_at' => null,
            'review_level' => null,
        ], $row), $inspectionRows);

        DB::transaction(function () use ($inspectionRows, $attachmentPlan) {
            foreach (array_chunk($inspectionRows, 300) as $chunk) {
                DB::table('kpi_inspections')->insert($chunk);
            }

            $refs = array_column($attachmentPlan, 'reference_no');
            $idMap = DB::table('kpi_inspections')->whereIn('reference_no', $refs)->pluck('id', 'reference_no');

            $attachmentRows = [];
            foreach ($attachmentPlan as $plan) {
                $inspectionId = $idMap[$plan['reference_no']] ?? null;
                if (! $inspectionId) {
                    continue;
                }

                $imagePath = $this->resolveImagePath($plan['slug']);
                $attachmentCount = match ($plan['slug']) {
                    'inspection-of-health-facilities' => count($this->healthObservationAttachmentKeys()),
                    'inspection-of-educational-institutions' => count($this->educationObservationAttachmentKeys()),
                    default => $plan['count'],
                };
                for ($a = 0; $a < $attachmentCount; $a++) {
                    $observationKey = null;
                    if ($plan['slug'] === 'inspection-of-health-facilities') {
                        $keys = $this->healthObservationAttachmentKeys();
                        $observationKey = $keys[$a % count($keys)] ?? null;
                    }
                    if ($plan['slug'] === 'inspection-of-educational-institutions') {
                        $keys = $this->educationObservationAttachmentKeys();
                        $observationKey = $keys[$a % count($keys)] ?? null;
                    }
                    if ($observationKey === null) {
                        $keys = $this->operationalObservationAttachmentKeys($plan['slug']);
                        $observationKey = $keys[$a % count($keys)] ?? 'overall_finding';
                    }

                    $attachmentRows[] = [
                        'kpi_inspection_id' => $inspectionId,
                        'file_path' => $imagePath,
                        'file_name' => basename($imagePath),
                        'file_type' => 'image',
                        'mime_type' => 'image/png',
                        'caption' => ['Before inspection evidence', 'During inspection evidence', 'After corrective action evidence'][$a % 3],
                        'observation_key' => $observationKey,
                        'latitude' => $plan['lat'] ?? null,
                        'longitude' => $plan['lng'] ?? null,
                        'sort_order' => $a,
                        'is_demo' => true,
                        'created_at' => $plan['ts'],
                        'updated_at' => $plan['ts'],
                    ];
                }
            }

            foreach (array_chunk($attachmentRows, 500) as $chunk) {
                DB::table('kpi_inspection_attachments')->insert($chunk);
            }
        });
    }

    /** @return list<string> */
    private function operationalObservationAttachmentKeys(string $slug): array
    {
        return match ($slug) {
            'price-of-roti' => ['observed_price', 'observed_weight_g', 'price_list_displayed'],
            'price-of-plain-bakery-bread' => ['observed_price', 'plain_bread_available', 'price_list_displayed'],
            'price-control-of-essential-commodities' => ['commodity', 'observed_price', 'violation'],
            'dysfunctional-streetlights' => ['dysfunctional_lights', 'fault_type', 'repaired_lights'],
            'zebra-crossings' => ['crossing_status', 'action_completion_status', 'warning_sign_available'],
            'repair-of-small-roads-in-both-urban-and-rural-areas' => ['damage_type', 'length_covered_m', 'completion_status'],
            default => ['overall_finding', 'corrective_action', 'action_status'],
        };
    }

    /** @return list<string> */
    private function educationObservationAttachmentKeys(): array
    {
        return EducationObservationLabels::evidenceKeys();
    }

    /** @return list<string> */
    private function healthObservationAttachmentKeys(): array
    {
        return HealthObservationLabels::evidenceKeys();
    }

    private function resolveImagePath(string $slug): string
    {
        $demoDir = 'images/demo-inspections';
        $demoFile = $demoDir.'/'.$slug.'-1.png';
        if (is_file(public_path($demoFile))) {
            return $demoFile;
        }

        $kpiImage = 'images/kpi-images/'.$slug.'.png';

        return is_file(public_path($kpiImage))
            ? $kpiImage
            : 'images/kpi-images/default-kpi.png';
    }

    /** @return list<string> */
    private function statusSequence(): array
    {
        return [
            'approved', 'approved', 'pending_review', 'approved', 'rejected',
            'approved', 'pending_review', 'approved', 'approved', 'pending_review',
            'approved', 'rejected', 'approved', 'pending_review', 'approved',
            'approved', 'rejected', 'approved', 'pending_review', 'approved',
        ];
    }

    /** @return list<array{title:string,name:string,type:string,id:string,address:string}> */
    private function entitiesForSlug(string $slug, string $title): array
    {
        $generic = [
            ['title' => $title.' Site Visit', 'name' => 'Municipal Facility A', 'type' => 'Facility', 'id' => 'FAC-001', 'address' => 'Main Bazaar Road'],
            ['title' => $title.' Compliance Check', 'name' => 'Community Center B', 'type' => 'Public Place', 'id' => 'PUB-014', 'address' => 'Civil Lines'],
            ['title' => $title.' Field Review', 'name' => 'Ward Office C', 'type' => 'Office', 'id' => 'OFF-021', 'address' => 'Union Council Road'],
            ['title' => $title.' Monitoring Visit', 'name' => 'Service Point D', 'type' => 'Service Point', 'id' => 'SRV-033', 'address' => 'GT Road'],
        ];

        $specific = match ($slug) {
            'price-of-roti' => [
                ['title' => 'Tandoor Price Inspection', 'name' => 'Main Bazaar Tandoor', 'type' => 'Tandoor', 'id' => 'TN-301', 'address' => 'Food Street'],
                ['title' => 'Roti Price Compliance', 'name' => 'Community Tandoor', 'type' => 'Tandoor', 'id' => 'TN-309', 'address' => 'Market Lane'],
                ['title' => 'Hotel Roti Check', 'name' => 'City Hotel Kitchen', 'type' => 'Hotel', 'id' => 'HT-412', 'address' => 'Mall Road'],
                ['title' => 'Shop Inspection', 'name' => 'Anarkali Roti Shop', 'type' => 'Shop', 'id' => 'SH-518', 'address' => 'Anarkali Bazaar'],
            ],
            'inspection-of-educational-institutions' => [
                ['title' => 'Govt High School Visit', 'name' => 'Govt High School Model Town', 'type' => 'School', 'id' => 'EDU-201', 'address' => 'Education Avenue'],
                ['title' => 'Primary School Inspection', 'name' => 'Govt Primary School Gulberg', 'type' => 'School', 'id' => 'EDU-214', 'address' => 'Model Town'],
                ['title' => 'College Monitoring', 'name' => 'Govt Degree College', 'type' => 'College', 'id' => 'EDU-228', 'address' => 'College Road'],
                ['title' => 'School Council Review', 'name' => 'Govt Girls High School', 'type' => 'School', 'id' => 'EDU-241', 'address' => 'Civil Lines'],
            ],
            'inspection-of-health-facilities' => [
                ['title' => 'Health Facility Site Visit', 'name' => 'Municipal Facility A', 'type' => 'Health Facility Site Visit', 'id' => 'HSP-101', 'address' => 'Hospital Road'],
                ['title' => 'AC RHC Field Visit', 'name' => 'Rural Health Center', 'type' => 'AC RHC Field Visit', 'id' => 'HSP-118', 'address' => 'Tehsil Road'],
                ['title' => 'AC BHU Field Visit', 'name' => 'Basic Health Unit', 'type' => 'AC BHU Field Visit', 'id' => 'HSP-132', 'address' => 'Township'],
                ['title' => 'DC Health Facility Review', 'name' => 'THQ Hospital', 'type' => 'DC Health Facility Review', 'id' => 'HSP-145', 'address' => 'Health Avenue'],
                ['title' => 'Commissioner District Health Review', 'name' => 'District Health Facility', 'type' => 'Commissioner District Health Review', 'id' => 'HSP-201', 'address' => 'District Office Road'],
            ],
            default => [],
        };

        return array_values(array_merge($specific, $generic));
    }

    /** @return list<array{title:string,name:string,type:string,id:string,address:string}> */
    private function healthFacilityEntities(): array
    {
        return [
            ['title' => 'Health Facility Site Visit', 'name' => 'Municipal Facility A', 'type' => 'Health Facility Site Visit', 'id' => 'HSP-101', 'address' => 'Hospital Road'],
            ['title' => 'AC RHC Field Visit', 'name' => 'Rural Health Center', 'type' => 'AC RHC Field Visit', 'id' => 'HSP-118', 'address' => 'Tehsil Road'],
            ['title' => 'AC BHU Field Visit', 'name' => 'Basic Health Unit', 'type' => 'AC BHU Field Visit', 'id' => 'HSP-132', 'address' => 'Township'],
            ['title' => 'DC Health Facility Review', 'name' => 'THQ Hospital', 'type' => 'DC Health Facility Review', 'id' => 'HSP-145', 'address' => 'Health Avenue'],
            ['title' => 'Commissioner District Health Review', 'name' => 'District Health Facility', 'type' => 'Commissioner District Health Review', 'id' => 'HSP-201', 'address' => 'District Office Road'],
        ];
    }

    private function healthFacilityInspectionName(array $entity, string $tehsilName, int $index): string
    {
        return match ($entity['id']) {
            'HSP-132' => sprintf('Basic Health Unit — Weekly Visit #%02d', $index + 1),
            'HSP-118' => sprintf('Rural Health Center — Weekly Visit #%02d', $index + 1),
            'HSP-101' => sprintf('Municipal Facility A — %s #%02d', $tehsilName, $index + 1),
            'HSP-145' => 'THQ Hospital — Health Facility Review',
            'HSP-201' => 'District Health Facility — Review Visit',
            default => sprintf('%s — %s #%02d', $entity['name'], $tehsilName, $index + 1),
        };
    }

    /** @param  array<string, mixed>  $side */
    private function locationFor(array $side, int $i): array
    {
        $spots = $side === self::LAHORE
            ? [
                ['street' => 'Main Boulevard, Gulberg III', 'lat' => 31.5204, 'lng' => 74.3587],
                ['street' => 'Ferozepur Road, Model Town', 'lat' => 31.4834, 'lng' => 74.3250],
                ['street' => 'The Mall Road, Anarkali', 'lat' => 31.5656, 'lng' => 74.3142],
                ['street' => 'Johar Town Block H', 'lat' => 31.4697, 'lng' => 74.2728],
                ['street' => 'Canal Road, Township', 'lat' => 31.4512, 'lng' => 74.3189],
                ['street' => 'Defence Phase 5, DHA', 'lat' => 31.4673, 'lng' => 74.4095],
            ]
            : [
                ['street' => 'Kot Addu Road, Civil Lines', 'lat' => 30.9617, 'lng' => 70.9397],
                ['street' => 'Chowk Azam Road, City Center', 'lat' => 30.9700, 'lng' => 70.9450],
                ['street' => 'Karor Lal Esan Road', 'lat' => 30.9520, 'lng' => 70.9280],
                ['street' => 'Thal Hospital Road', 'lat' => 30.9685, 'lng' => 70.9510],
                ['street' => 'Railway Road, Layyah City', 'lat' => 30.9580, 'lng' => 70.9325],
                ['street' => 'College Road, Layyah', 'lat' => 30.9655, 'lng' => 70.9410],
                ['street' => 'Hospital Road, Layyah', 'lat' => 30.9638, 'lng' => 70.9362],
                ['street' => 'Fatehpur Road', 'lat' => 30.9742, 'lng' => 70.9488],
                ['street' => 'Chaubara Road', 'lat' => 30.9564, 'lng' => 70.9441],
                ['street' => 'Mandi Town Road', 'lat' => 30.9698, 'lng' => 70.9310],
                ['street' => 'Canal View Road', 'lat' => 30.9536, 'lng' => 70.9524],
                ['street' => 'Shah Jamal Road', 'lat' => 30.9771, 'lng' => 70.9375],
                ['street' => 'Chowk Sarwar Shaheed', 'lat' => 30.9602, 'lng' => 70.9268],
                ['street' => 'Tehsil Complex Road', 'lat' => 30.9669, 'lng' => 70.9493],
                ['street' => 'Bazaar Road, Layyah', 'lat' => 30.9573, 'lng' => 70.9388],
                ['street' => 'Grid Station Road', 'lat' => 30.9726, 'lng' => 70.9337],
                ['street' => 'Bypass Road, Layyah', 'lat' => 30.9549, 'lng' => 70.9466],
                ['street' => 'Model Town Layyah', 'lat' => 30.9714, 'lng' => 70.9429],
                ['street' => 'Civil Hospital Lane', 'lat' => 30.9625, 'lng' => 70.9344],
                ['street' => 'District Council Road', 'lat' => 30.9680, 'lng' => 70.9276],
            ];

        $spot = $spots[$i % count($spots)];
        $jitter = (($i % 7) - 3) * 0.00035;

        return [
            'street' => $spot['street'],
            'lat' => round($spot['lat'] + $jitter, 7),
            'lng' => round($spot['lng'] + $jitter, 7),
        ];
    }

    /**
     * @param  array<string, mixed>  $side
     * @param  array<string, string>  $entity
     * @param  array<string, mixed>  $location
     */
    private function fullAddress(array $side, array $entity, array $location): string
    {
        $plot = 10 + (crc32($entity['id']) % 180);

        return sprintf(
            'Plot No. %d, %s, Near %s, %s Tehsil, %s District, Punjab, Pakistan',
            $plot,
            $location['street'],
            $entity['name'],
            $side['tehsil_name'],
            $side['district_name']
        );
    }

    /** @return array<string, mixed> */
    private function sideForIndex(int $i, string $slug): array
    {
        if (in_array($slug, self::PRIORITY_SLUGS, true)) {
            return match ($i % 4) {
                0, 1 => self::LAHORE_CITY,
                2 => self::LAHORE_CANTT,
                default => self::LAYYAH,
            };
        }

        return $i < 6 ? self::LAHORE : self::LAYYAH;
    }

    private function inspectionDateForIndex(int $i, int $perKpi, bool $priority): Carbon
    {
        $now = now();
        if (! $priority) {
            return match (true) {
                $i === 0 => $now->copy()->setTime(9, 0),
                $i < 5 => $now->copy()->subDays($i % 7)->setTime(9 + ($i % 6), 15 * ($i % 4), 0),
                $i < 10 => $now->copy()->subDays(8 + ($i % 18))->setTime(10 + ($i % 4), 10 * ($i % 5), 0),
                default => $now->copy()->subMonths(1 + ($i % max(1, min(5, $now->month))))
                    ->subDays($i % 12)
                    ->setTime(9 + ($i % 6), 20 * ($i % 3), 0),
            };
        }

        $ratio = $perKpi > 1 ? $i / max(1, $perKpi - 1) : 0;

        if ($ratio < 0.15) {
            return $now->copy()->setTime(9 + ($i % 5), 10 * ($i % 6), 0);
        }

        if ($ratio < 0.45) {
            return $now->copy()->subDays($i % 7)->setTime(10 + ($i % 4), 12 * ($i % 5), 0);
        }

        if ($ratio < 0.75) {
            return $now->copy()->subDays(8 + ($i % 18))->setTime(11 + ($i % 3), 8 * ($i % 6), 0);
        }

        return $now->copy()->subMonths(1 + ($i % 4))->subDays($i % 12)->setTime(9 + ($i % 6), 20 * ($i % 3), 0);
    }

    /**
     * @return array{0: list<array<string, mixed>>, 1: list<array<string, mixed>>}
     */
    private function buildVisitKpiInspections(object $card, $users, int &$refCounter, Carbon $now, string $batch): array
    {
        $statuses = $this->statusSequence();
        $entities = $card->slug === 'inspection-of-health-facilities'
            ? $this->healthFacilityEntities()
            : $this->entitiesForSlug($card->slug, $card->title);
        $tehsilPlan = [
            ['tehsil_id' => 81, 'district_id' => 23, 'division_id' => 6, 'count' => 10, 'tehsil_name' => 'Lahore City', 'district_name' => 'Lahore', 'lat' => 31.5204, 'lng' => 74.3587],
            ['tehsil_id' => 82, 'district_id' => 23, 'division_id' => 6, 'count' => 8, 'tehsil_name' => 'Lahore Cantonment', 'district_name' => 'Lahore', 'lat' => 31.5320, 'lng' => 74.3420],
            ['tehsil_id' => 83, 'district_id' => 23, 'division_id' => 6, 'count' => 5, 'tehsil_name' => 'Model Town', 'district_name' => 'Lahore', 'lat' => 31.4834, 'lng' => 74.3250],
            ['tehsil_id' => 84, 'district_id' => 23, 'division_id' => 6, 'count' => 4, 'tehsil_name' => 'Raiwind', 'district_name' => 'Lahore', 'lat' => 31.2484, 'lng' => 74.2203],
            ['tehsil_id' => 85, 'district_id' => 23, 'division_id' => 6, 'count' => 4, 'tehsil_name' => 'Shalimar', 'district_name' => 'Lahore', 'lat' => 31.5870, 'lng' => 74.3805],
            ['tehsil_id' => 24, 'district_id' => 7, 'division_id' => 2, 'count' => 22, 'tehsil_name' => 'Layyah', 'district_name' => 'Layyah', 'lat' => 30.9617, 'lng' => 70.9397, 'inspector' => 'ac.layyah'],
            ['tehsil_id' => 25, 'district_id' => 7, 'division_id' => 2, 'count' => 7, 'tehsil_name' => 'Karor Lal Esan', 'district_name' => 'Layyah', 'lat' => 30.9520, 'lng' => 70.9280, 'inspector' => 'ac.karor'],
            ['tehsil_id' => 27, 'district_id' => 8, 'division_id' => 2, 'count' => 9, 'tehsil_name' => 'Muzaffargarh', 'district_name' => 'Muzaffargarh', 'lat' => 30.0703, 'lng' => 71.1933, 'inspector' => 'dc.layyah'],
        ];

        $rows = [];
        $attachments = [];
        $globalIndex = 0;

        foreach ($tehsilPlan as $plan) {
            $side = [
                'division_id' => $plan['division_id'],
                'district_id' => $plan['district_id'],
                'tehsil_id' => $plan['tehsil_id'],
                'tehsil_name' => $plan['tehsil_name'],
                'district_name' => $plan['district_name'],
                'lat' => $plan['lat'],
                'lng' => $plan['lng'],
            ];
            $inspectorUsername = $plan['inspector'] ?? ($plan['tehsil_id'] === 24 ? 'ac.layyah' : 'ac.lahore');
            $reviewerUsername = in_array($plan['tehsil_id'], [24, 25], true) ? 'dc.layyah' : 'dc.lahore';
            $inspector = $users->get($inspectorUsername);
            $reviewer = $users->get($reviewerUsername);

            for ($i = 0; $i < $plan['count']; $i++) {
                $demoStatuses = $this->demoTehsilStatusPlan($plan['tehsil_id']);
                $status = $demoStatuses[$i] ?? $statuses[$globalIndex % count($statuses)];
                if ($card->slug === 'inspection-of-health-facilities' && (int) $plan['tehsil_id'] === 24 && $i >= 2) {
                    $status = 'pending_review';
                }
                $completedDayRecordCount = $this->healthCompletedDayRecordCount($card->slug, $demoStatuses, $plan['count']);
                $isHealthCompletedDayRecord = $card->slug === 'inspection-of-health-facilities'
                    && $demoStatuses !== null
                    && $i >= 2
                    && $i < 2 + $completedDayRecordCount;
                $inspectedAt = $card->slug === 'inspection-of-health-facilities'
                    ? $this->healthInspectionDateForIndex($i, $globalIndex, $plan['count'], $completedDayRecordCount, $demoStatuses !== null, (int) $plan['tehsil_id'])
                    : ($demoStatuses !== null && isset($demoStatuses[$i])
                        ? $this->activeWeekDateForIndex($i)
                        : $this->visitInspectionDateForIndex($globalIndex, $plan['count']));
                $entity = $entities[$globalIndex % count($entities)];
                $reference = sprintf('INSP-%s-%06d', $now->format('Y'), $refCounter++);
                $detailData = \Database\Seeders\Support\KpiInspectionDetailFactory::forSlug($card->slug, $globalIndex);
                if ($card->slug === 'inspection-of-health-facilities') {
                    $detailData = $this->healthObservationTemplate($i, $plan['tehsil_id']);
                    if ($isHealthCompletedDayRecord) {
                        $detailData['inspection_list_only'] = true;
                    }
                }
                $location = $this->locationFor($side, $globalIndex);
                $fullAddress = $this->fullAddress($side, $entity, $location);
                $inspectionType = $card->slug === 'inspection-of-health-facilities'
                    ? $entity['type']
                    : $entity['title'];
                $inspectionName = $card->slug === 'inspection-of-health-facilities'
                    ? $this->healthFacilityInspectionName($entity, $plan['tehsil_name'], $i)
                    : sprintf('%s — %s #%02d', $entity['name'], $plan['tehsil_name'], $i + 1);
                $identifier = $entity['id'].'-'.$plan['tehsil_id'];

                if ($card->slug === 'inspection-of-health-facilities') {
                    $baseline = DB::table('health_facility_baselines')
                        ->where('tehsil_id', $plan['tehsil_id'])
                        ->orderBy('facility_code')
                        ->offset(min($i, 19))
                        ->limit(1)
                        ->first();

                    if ($baseline) {
                        $inspectionName = (string) $baseline->name;
                        $identifier = (string) $baseline->facility_code;
                        $fullAddress = (string) $baseline->address;
                        $location = [
                            'street' => $plan['tehsil_name'],
                            'lat' => (float) $baseline->latitude,
                            'lng' => (float) $baseline->longitude,
                        ];

                        if ($plan['tehsil_id'] === 24 && $i < 2) {
                            $location['lat'] = round($location['lat'] + ($i * 0.006), 7);
                            $location['lng'] = round($location['lng'] + ($i * 0.005), 7);
                        }

                        if ($plan['tehsil_id'] === 24 && $i >= 2) {
                            $spread = $i - 2;
                            $location['lat'] = round($location['lat'] + (($spread % 5) * 0.0025), 7);
                            $location['lng'] = round($location['lng'] + ((int) floor($spread / 5) * 0.0025), 7);
                        }
                    }
                }

                $rows[] = [
                    'uuid' => (string) Str::uuid(),
                    'reference_no' => $reference,
                    'kpi_card_id' => $card->id,
                    'kpi_submission_id' => null,
                    'division_id' => $side['division_id'],
                    'district_id' => $side['district_id'],
                    'tehsil_id' => $side['tehsil_id'],
                    'inspected_by' => $inspector?->id,
                    'reviewed_by' => in_array($status, ['pending_review', 'draft'], true) ? null : $reviewer?->id,
                    'inspection_title' => $inspectionType,
                    'entity_name' => $inspectionName,
                    'entity_type' => $entity['type'],
                    'identifier' => $identifier,
                    'address' => $fullAddress,
                    'latitude' => $location['lat'],
                    'longitude' => $location['lng'],
                    'inspection_datetime' => $inspectedAt,
                    'status' => $status,
                    'observations' => json_encode([
                        'Field verification completed at '.$entity['name'].'.',
                        'Compliance indicators reviewed against '.$card->title.' standards.',
                    ]),
                    'actions_required' => json_encode($status === 'rejected'
                        ? ['Re-inspection required within 7 days.']
                        : ['Continue routine monitoring during current reporting week.']),
                    'actions_taken' => json_encode($status !== 'pending_review'
                        ? ['Evidence uploaded and checklist completed.']
                        : ['Preliminary site visit completed.']),
                    'detail_data' => json_encode($detailData),
                    'review_remarks' => $status === 'approved' ? 'Inspection evidence verified and accepted.' : null,
                    'rejection_reason' => $status === 'rejected' ? 'Evidence incomplete or compliance below required threshold.' : null,
                    'reviewed_at' => in_array($status, ['pending_review', 'draft'], true) ? null : $inspectedAt->copy()->addHours(6),
                    'is_demo' => true,
                    'seed_batch' => $batch,
                    'created_at' => $inspectedAt,
                    'updated_at' => in_array($status, ['pending_review', 'draft'], true) ? $inspectedAt : $inspectedAt->copy()->addHours(6),
                ];

                $attachments[] = [
                    'reference_no' => $reference,
                    'slug' => $card->slug,
                    'count' => 1 + ($globalIndex % 2),
                    'ts' => $inspectedAt,
                ];

                $globalIndex++;
            }
        }

        return [$rows, $attachments];
    }

    /**
     * @return array{0: list<array<string, mixed>>, 1: list<array<string, mixed>>}
     */
    private function buildEducationInstitutionInspections(object $card, $users, int &$refCounter, Carbon $now, string $batch): array
    {
        $statuses = $this->statusSequence();
        $entities = $this->educationInstitutionEntities();
        // AC Layyah / AC Karor: exactly 2 inspected institutions in the default
        // completed week (1 approved for review target, 1 inspected-only).
        $tehsilPlan = [
            ['tehsil_id' => 81, 'district_id' => 23, 'division_id' => 6, 'count' => 2, 'tehsil_name' => 'Lahore City', 'district_name' => 'Lahore', 'lat' => 31.5204, 'lng' => 74.3587],
            ['tehsil_id' => 82, 'district_id' => 23, 'division_id' => 6, 'count' => 2, 'tehsil_name' => 'Lahore Cantonment', 'district_name' => 'Lahore', 'lat' => 31.5320, 'lng' => 74.3420],
            ['tehsil_id' => 24, 'district_id' => 7, 'division_id' => 2, 'count' => 22, 'tehsil_name' => 'Layyah', 'district_name' => 'Layyah', 'lat' => 30.9617, 'lng' => 70.9397, 'inspector' => 'ac.layyah'],
            ['tehsil_id' => 25, 'district_id' => 7, 'division_id' => 2, 'count' => 22, 'tehsil_name' => 'Karor Lal Esan', 'district_name' => 'Layyah', 'lat' => 30.9520, 'lng' => 70.9280, 'inspector' => 'ac.karor'],
            ['tehsil_id' => 26, 'district_id' => 7, 'division_id' => 2, 'count' => 2, 'tehsil_name' => 'Chaubara', 'district_name' => 'Layyah', 'lat' => 30.9005, 'lng' => 71.6512, 'inspector' => 'dc.layyah'],
        ];

        $rows = [];
        $attachments = [];
        $globalIndex = 0;

        foreach ($tehsilPlan as $plan) {
            $side = [
                'division_id' => $plan['division_id'],
                'district_id' => $plan['district_id'],
                'tehsil_id' => $plan['tehsil_id'],
                'tehsil_name' => $plan['tehsil_name'],
                'district_name' => $plan['district_name'],
                'lat' => $plan['lat'],
                'lng' => $plan['lng'],
            ];
            $inspectorUsername = $plan['inspector'] ?? ($plan['tehsil_id'] === 24 ? 'ac.layyah' : 'ac.lahore');
            $reviewerUsername = in_array($plan['tehsil_id'], [24, 25, 26], true) ? 'dc.layyah' : 'dc.lahore';
            $inspector = $users->get($inspectorUsername);
            $reviewer = $users->get($reviewerUsername);

            for ($i = 0; $i < $plan['count']; $i++) {
                $demoStatuses = $this->demoEducationTehsilStatusPlan($plan['tehsil_id']);
                $status = $demoStatuses[$i] ?? $statuses[$globalIndex % count($statuses)];
                // Layyah / Karor: indexes >= 2 feed the completed-day Inspection List
                // as pending review (yesterday until 5pm).
                $isCompletedDayListRecord = in_array((int) $plan['tehsil_id'], [24, 25], true) && $i >= 2;
                if ($isCompletedDayListRecord) {
                    $status = 'pending_review';
                }
                $inspectedAt = $isCompletedDayListRecord
                    ? $this->latestCompletedDayDateForIndex($i - 2)
                    : $this->educationInspectionDateForIndex($i, $globalIndex, $plan['count'], 0, $demoStatuses !== null, (int) $plan['tehsil_id']);
                $entity = $entities[$globalIndex % count($entities)];
                $reference = sprintf('EDU-INSP-%s-%06d', $now->format('Y'), $refCounter++);
                $detailData = $this->educationObservationTemplate($i, (int) $plan['tehsil_id']);
                if ($isCompletedDayListRecord) {
                    $detailData['inspection_list_only'] = true;
                }
                $location = $this->locationFor($side, $globalIndex);
                $fullAddress = $this->fullAddress($side, $entity, $location);
                $inspectionName = $entity['name'];
                $identifier = $entity['id'].'-'.$plan['tehsil_id'];

                $baseline = DB::table('education_institution_baselines')
                    ->where('tehsil_id', $plan['tehsil_id'])
                    ->orderBy('institution_code')
                    ->offset(min($isCompletedDayListRecord ? $i - 2 : $i, 19))
                    ->limit(1)
                    ->first();

                if ($baseline) {
                    $inspectionName = (string) $baseline->name;
                    $identifier = (string) $baseline->institution_code;
                    $fullAddress = (string) $baseline->address;
                    $location = [
                        'street' => $plan['tehsil_name'],
                        'lat' => (float) $baseline->latitude,
                        'lng' => (float) $baseline->longitude,
                    ];
                }

                $rows[] = [
                    'uuid' => (string) Str::uuid(),
                    'reference_no' => $reference,
                    'kpi_card_id' => $card->id,
                    'kpi_submission_id' => null,
                    'division_id' => $side['division_id'],
                    'district_id' => $side['district_id'],
                    'tehsil_id' => $side['tehsil_id'],
                    'inspected_by' => $inspector?->id,
                    'reviewed_by' => in_array($status, ['pending_review', 'draft', 'inspected_only'], true) ? null : $reviewer?->id,
                    'inspection_title' => 'AC School Field Visit',
                    'entity_name' => $inspectionName,
                    'entity_type' => 'AC School Field Visit',
                    'identifier' => $identifier,
                    'address' => $fullAddress,
                    'latitude' => $location['lat'],
                    'longitude' => $location['lng'],
                    'inspection_datetime' => $inspectedAt,
                    'status' => $status,
                    'observations' => json_encode(['Education institution field inspection completed.']),
                    'actions_required' => json_encode($status === 'rejected'
                        ? ['Re-inspection required within 7 days.']
                        : ['Continue routine monitoring during current reporting week.']),
                    'actions_taken' => json_encode($status !== 'pending_review'
                        ? ['Evidence uploaded and checklist completed.']
                        : ['Preliminary school visit completed.']),
                    'detail_data' => json_encode($detailData),
                    'review_remarks' => $status === 'approved' ? 'Education inspection evidence verified and accepted.' : null,
                    'rejection_reason' => $status === 'rejected' ? 'Evidence incomplete or compliance below required threshold.' : null,
                    'reviewed_at' => in_array($status, ['pending_review', 'draft', 'inspected_only'], true) ? null : $inspectedAt->copy()->addHours(6),
                    'is_demo' => true,
                    'seed_batch' => $batch,
                    'created_at' => $inspectedAt,
                    'updated_at' => in_array($status, ['pending_review', 'draft', 'inspected_only'], true) ? $inspectedAt : $inspectedAt->copy()->addHours(6),
                ];

                $attachments[] = [
                    'reference_no' => $reference,
                    'slug' => $card->slug,
                    'count' => 1 + ($globalIndex % 2),
                    'ts' => $inspectedAt,
                ];

                $globalIndex++;
            }
        }

        return [$rows, $attachments];
    }

    /** @return list<array{title:string,name:string,type:string,id:string,address:string}> */
    private function educationInstitutionEntities(): array
    {
        return [
            ['title' => 'AC School Field Visit', 'name' => 'Govt. Boys High School Karor Lal Esan', 'type' => 'AC School Field Visit', 'id' => 'EDU-KLE-001', 'address' => 'Near Main Bazar, Karor Lal Esan'],
            ['title' => 'AC School Field Visit', 'name' => 'Govt. Girls High School Karor Lal Esan', 'type' => 'AC School Field Visit', 'id' => 'EDU-KLE-002', 'address' => 'Girls School Road, Karor Lal Esan'],
            ['title' => 'AC School Field Visit', 'name' => 'Govt. Elementary School Chak No. 97/TDA', 'type' => 'AC School Field Visit', 'id' => 'EDU-LAY-003', 'address' => 'Chak No. 97/TDA, Layyah'],
            ['title' => 'AC School Field Visit', 'name' => 'Govt. Primary School Basti Gahi', 'type' => 'AC School Field Visit', 'id' => 'EDU-LAY-004', 'address' => 'Basti Gahi, Layyah'],
            ['title' => 'AC School Field Visit', 'name' => 'Govt. High School Layyah City', 'type' => 'AC School Field Visit', 'id' => 'EDU-LAY-005', 'address' => 'Layyah City'],
            ['title' => 'AC School Field Visit', 'name' => 'Govt. Girls Elementary School Fatehpur', 'type' => 'AC School Field Visit', 'id' => 'EDU-LAY-006', 'address' => 'Fatehpur, Layyah'],
            ['title' => 'AC School Field Visit', 'name' => 'Govt. Primary School Chowk Azam', 'type' => 'AC School Field Visit', 'id' => 'EDU-LAY-007', 'address' => 'Chowk Azam Road, Layyah'],
            ['title' => 'AC School Field Visit', 'name' => 'Govt. High School Chaubara', 'type' => 'AC School Field Visit', 'id' => 'EDU-CHA-008', 'address' => 'Chaubara, Layyah'],
        ];
    }

    /** @return list<string>|null */
    private function demoEducationTehsilStatusPlan(int $tehsilId): ?array
    {
        // Review Target = ceil(inspected * 0.20) = 1 when inspected = 2.
        // Only one inspection may be selected for review (approved);
        // the second remains inspected-only (blue map pin).
        return match ($tehsilId) {
            24, 25, 26, 81, 82 => ['approved', 'inspected_only'],
            default => null,
        };
    }

    private function educationCompletedDayRecordCount(int $tehsilId, int $tehsilTotal): int
    {
        return 0;
    }

    private function educationInspectionDateForIndex(
        int $index,
        int $globalIndex,
        int $tehsilTotal,
        int $completedDayRecordCount,
        bool $priorityTehsil,
        int $tehsilId = 0,
    ): Carbon {
        // Final placement is remapped to the dashboard's latest completed
        // Thu–Wed week by placeVisitDataInLatestCompletedWeek().
        return $this->activeWeekDateForIndex($index + $globalIndex);
    }

    /** @return array<string, mixed> */
    private function educationObservationTemplate(int $index, int $tehsilId): array
    {
        // AC Layyah / AC Karor demo schools: exact positive/negative pairs so
        // every observation card totals Institutions Inspected (2).
        // Indexes >= 2 are completed-day Inspection List rows only.
        if (in_array($tehsilId, [24, 25], true) && $index < 2) {
            $pattern = $index === 0
                ? [
                    'school_premises_condition' => 'satisfactory',
                    'classroom_cleanliness' => 'satisfactory',
                    'teachers_staff_present' => 'present',
                    'teacher_dress_compliance' => 'compliant',
                    'learning_material_available' => 'available',
                    'electricity_facilities_functional' => 'functional',
                    'drinking_water_available' => 'available',
                    'toilets_functional_clean' => 'functional',
                    'boundary_wall_available' => 'available',
                    'playground_maintained' => 'maintained',
                ]
                : [
                    'school_premises_condition' => 'unsatisfactory',
                    'classroom_cleanliness' => 'unsatisfactory',
                    'teachers_staff_present' => 'present',
                    'teacher_dress_compliance' => 'non_compliant',
                    'learning_material_available' => 'available',
                    'electricity_facilities_functional' => 'functional',
                    'drinking_water_available' => 'unavailable',
                    'toilets_functional_clean' => 'non_functional',
                    'boundary_wall_available' => 'available',
                    'playground_maintained' => 'not_maintained',
                ];
        } else {
            $slot = $index % 5;
            $pattern = [
                'school_premises_condition' => $slot < 4 ? 'satisfactory' : 'unsatisfactory',
                'classroom_cleanliness' => $slot < 3 ? 'satisfactory' : 'unsatisfactory',
                'teachers_staff_present' => $slot < 4 ? 'present' : 'absent',
                'teacher_dress_compliance' => ($slot < 2 ? 'compliant' : 'non_compliant'),
                'learning_material_available' => $slot < 4 ? 'available' : 'unavailable',
                'electricity_facilities_functional' => $slot < 4 ? 'functional' : 'non_functional',
                'drinking_water_available' => $slot < 3 ? 'available' : 'unavailable',
                'toilets_functional_clean' => $slot < 3 ? 'functional' : 'non_functional',
                'boundary_wall_available' => 'available',
                'playground_maintained' => $slot < 2 ? 'maintained' : 'not_maintained',
            ];
        }

        $enrolled = 140 + (($index * 23 + $tehsilId * 7) % 360);
        $attendanceRate = 0.72 + (($index + $tehsilId) % 6) * 0.04;
        $pattern['students_enrolled'] = $enrolled;
        $pattern['students_present'] = max(1, (int) round($enrolled * $attendanceRate));

        return $pattern;
    }

    /** @return list<array<string, mixed>> */
    private function educationObservationMixedPatterns(): array
    {
        return [
            [
                'cleanliness_available' => 'not_available',
                'teachers_staff_available' => 'available',
                'books_learning_material_available' => 'not_available',
                'school_facilities_utilities_available' => 'available',
                'drinking_water_available' => 'available',
                'student_enrolment_checked' => 'yes',
                'students_enrolled' => 420,
                'students_present' => 386,
            ],
            [
                'cleanliness_available' => 'available',
                'teachers_staff_available' => 'available',
                'books_learning_material_available' => 'available',
                'school_facilities_utilities_available' => 'available',
                'drinking_water_available' => 'available',
                'student_enrolment_checked' => 'yes',
                'students_enrolled' => 315,
                'students_present' => 298,
            ],
            [
                'cleanliness_available' => 'available',
                'teachers_staff_available' => 'not_available',
                'books_learning_material_available' => 'available',
                'school_facilities_utilities_available' => 'not_available',
                'drinking_water_available' => 'not_available',
                'student_enrolment_checked' => 'yes',
                'students_enrolled' => 180,
                'students_present' => 152,
            ],
            [
                'cleanliness_available' => 'not_available',
                'teachers_staff_available' => 'not_available',
                'books_learning_material_available' => 'not_available',
                'school_facilities_utilities_available' => 'not_available',
                'drinking_water_available' => 'available',
                'student_enrolment_checked' => 'no',
                'students_enrolled' => 240,
                'students_present' => 201,
            ],
            [
                'cleanliness_available' => 'available',
                'teachers_staff_available' => 'available',
                'books_learning_material_available' => 'not_available',
                'school_facilities_utilities_available' => 'available',
                'drinking_water_available' => 'not_available',
                'student_enrolment_checked' => 'yes',
                'students_enrolled' => 275,
                'students_present' => 241,
            ],
            [
                'cleanliness_available' => 'not_available',
                'teachers_staff_available' => 'available',
                'books_learning_material_available' => 'available',
                'school_facilities_utilities_available' => 'not_available',
                'drinking_water_available' => 'available',
                'student_enrolment_checked' => 'yes',
                'students_enrolled' => 198,
                'students_present' => 164,
            ],
            [
                'cleanliness_available' => 'available',
                'teachers_staff_available' => 'not_available',
                'books_learning_material_available' => 'not_available',
                'school_facilities_utilities_available' => 'available',
                'drinking_water_available' => 'available',
                'student_enrolment_checked' => 'yes',
                'students_enrolled' => 352,
                'students_present' => 318,
            ],
            [
                'cleanliness_available' => 'not_available',
                'teachers_staff_available' => 'not_available',
                'books_learning_material_available' => 'available',
                'school_facilities_utilities_available' => 'not_available',
                'drinking_water_available' => 'not_available',
                'student_enrolment_checked' => 'no',
                'students_enrolled' => 165,
                'students_present' => 128,
            ],
        ];
    }

    private function todayInspectionDateInActiveWeek(int $hour, int $minute = 0): Carbon
    {
        return now(config('app.inspection_timezone', 'Asia/Karachi'))
            ->copy()
            ->setTime($hour, $minute, 0)
            ->setTimezone(config('app.timezone', 'UTC'));
    }

    /**
     * @return array{0: list<array<string, mixed>>, 1: list<array<string, mixed>>}
     */
    private function buildHealthFacilityInspections(object $card, $users, int &$refCounter, Carbon $now, string $batch): array
    {
        $statuses = $this->statusSequence();
        $entities = $this->healthFacilityEntities();
        // AC scopes: exactly 2 inspected facilities in the default completed week
        // (1 approved for review target, 1 inspected-only / blue pin).
        // Indexes >= 2 are Inspection List-only rows for the last completed day
        // (yesterday 00:00–17:00 Asia/Karachi) and stay out of weekly dashboard remap.
        $tehsilPlan = [
            ['tehsil_id' => 81, 'district_id' => 23, 'division_id' => 6, 'count' => 22, 'tehsil_name' => 'Lahore City', 'district_name' => 'Lahore', 'lat' => 31.5204, 'lng' => 74.3587, 'inspector' => 'ac.lahore'],
            ['tehsil_id' => 24, 'district_id' => 7, 'division_id' => 2, 'count' => 22, 'tehsil_name' => 'Layyah', 'district_name' => 'Layyah', 'lat' => 30.9617, 'lng' => 70.9397, 'inspector' => 'ac.layyah'],
            ['tehsil_id' => 25, 'district_id' => 7, 'division_id' => 2, 'count' => 22, 'tehsil_name' => 'Karor Lal Esan', 'district_name' => 'Layyah', 'lat' => 30.9520, 'lng' => 70.9280, 'inspector' => 'ac.karor'],
        ];

        $rows = [];
        $attachments = [];
        $globalIndex = 0;

        foreach ($tehsilPlan as $plan) {
            $side = [
                'division_id' => $plan['division_id'],
                'district_id' => $plan['district_id'],
                'tehsil_id' => $plan['tehsil_id'],
                'tehsil_name' => $plan['tehsil_name'],
                'district_name' => $plan['district_name'],
                'lat' => $plan['lat'],
                'lng' => $plan['lng'],
            ];
            $inspector = $users->get($plan['inspector']);
            $reviewer = $users->get(in_array($plan['tehsil_id'], [24, 25], true) ? 'dc.layyah' : 'dc.lahore');

            for ($i = 0; $i < $plan['count']; $i++) {
                $demoStatuses = $this->demoTehsilStatusPlan($plan['tehsil_id']);
                $status = $demoStatuses[$i] ?? $statuses[$globalIndex % count($statuses)];
                $isCompletedDayListRecord = $i >= 2;
                if ($isCompletedDayListRecord) {
                    // Layyah: all 20 facilities pending review for the completed-day list.
                    // Lahore / Karor: mostly pending with a few approved/rejected for review colours.
                    $status = match (true) {
                        (int) $plan['tehsil_id'] === 24 => 'pending_review',
                        ($i - 2) % 10 === 8 => 'approved',
                        ($i - 2) % 10 === 9 => 'rejected',
                        default => 'pending_review',
                    };
                }
                $inspectedAt = $isCompletedDayListRecord
                    ? $this->latestCompletedDayDateForIndex($i - 2)
                    : $this->activeWeekDateForIndex($i + $globalIndex);
                $entity = $entities[$globalIndex % count($entities)];
                $reference = sprintf('INSP-%s-%06d', $now->format('Y'), $refCounter++);
                $detailData = $this->healthObservationTemplate($i, (int) $plan['tehsil_id']);
                if ($isCompletedDayListRecord) {
                    $detailData['inspection_list_only'] = true;
                }
                $location = $this->locationFor($side, $globalIndex);
                $fullAddress = $this->fullAddress($side, $entity, $location);
                $inspectionName = $this->healthFacilityInspectionName($entity, $plan['tehsil_name'], $i);
                $identifier = $entity['id'].'-'.$plan['tehsil_id'];

                $baseline = DB::table('health_facility_baselines')
                    ->where('tehsil_id', $plan['tehsil_id'])
                    ->orderBy('facility_code')
                    ->offset(min($isCompletedDayListRecord ? $i - 2 : $i, 19))
                    ->limit(1)
                    ->first();

                if ($baseline) {
                    $inspectionName = (string) $baseline->name;
                    $identifier = (string) $baseline->facility_code;
                    $fullAddress = (string) $baseline->address;
                    $location = [
                        'street' => $plan['tehsil_name'],
                        'lat' => (float) $baseline->latitude,
                        'lng' => (float) $baseline->longitude,
                    ];
                }

                $rows[] = [
                    'uuid' => (string) Str::uuid(),
                    'reference_no' => $reference,
                    'kpi_card_id' => $card->id,
                    'kpi_submission_id' => null,
                    'division_id' => $side['division_id'],
                    'district_id' => $side['district_id'],
                    'tehsil_id' => $side['tehsil_id'],
                    'inspected_by' => $inspector?->id,
                    'reviewed_by' => in_array($status, ['pending_review', 'draft', 'inspected_only'], true) ? null : $reviewer?->id,
                    'inspection_title' => $entity['type'],
                    'entity_name' => $inspectionName,
                    'entity_type' => $entity['type'],
                    'identifier' => $identifier,
                    'address' => $fullAddress,
                    'latitude' => $location['lat'],
                    'longitude' => $location['lng'],
                    'inspection_datetime' => $inspectedAt,
                    'status' => $status,
                    'observations' => json_encode([
                        'Field verification completed at '.$inspectionName.'.',
                        'Compliance indicators reviewed against '.$card->title.' standards.',
                    ]),
                    'actions_required' => json_encode($status === 'rejected'
                        ? ['Re-inspection required within 7 days.']
                        : ['Continue routine monitoring during current reporting week.']),
                    'actions_taken' => json_encode($status !== 'pending_review'
                        ? ['Evidence uploaded and checklist completed.']
                        : ['Preliminary site visit completed.']),
                    'detail_data' => json_encode($detailData),
                    'review_remarks' => $status === 'approved' ? 'Inspection evidence verified and accepted.' : null,
                    'rejection_reason' => $status === 'rejected' ? 'Evidence incomplete or compliance below required threshold.' : null,
                    'reviewed_at' => in_array($status, ['pending_review', 'draft', 'inspected_only'], true) ? null : $inspectedAt->copy()->addHours(6),
                    'is_demo' => true,
                    'seed_batch' => $batch,
                    'created_at' => $inspectedAt,
                    'updated_at' => in_array($status, ['pending_review', 'draft', 'inspected_only'], true) ? $inspectedAt : $inspectedAt->copy()->addHours(6),
                ];

                $attachments[] = [
                    'reference_no' => $reference,
                    'slug' => $card->slug,
                    'count' => 1 + ($globalIndex % 2),
                    'ts' => $inspectedAt,
                ];

                $globalIndex++;
            }
        }

        return [$rows, $attachments];
    }

    private function visitInspectionDateForIndex(int $index, int $tehsilTotal): Carbon
    {
        $now = now();
        $ratio = $tehsilTotal > 1 ? ($index % $tehsilTotal) / max(1, $tehsilTotal - 1) : 0;

        if ($ratio < 0.18) {
            return $now->copy()->setTime(9 + ($index % 4), 15 * ($index % 4), 0);
        }

        if ($ratio < 0.50) {
            return $now->copy()->subDays($index % 6)->setTime(10 + ($index % 3), 12 * ($index % 5), 0);
        }

        if ($ratio < 0.78) {
            return $now->copy()->subDays(7 + ($index % 16))->setTime(11 + ($index % 2), 8 * ($index % 6), 0);
        }

        return $now->copy()->subMonths(1 + ($index % 3))->subDays($index % 10)->setTime(9 + ($index % 5), 20 * ($index % 3), 0);
    }

    /** @param list<string>|null $demoStatuses */
    private function healthCompletedDayRecordCount(string $slug, ?array $demoStatuses, int $tehsilTotal): int
    {
        if ($slug !== 'inspection-of-health-facilities' || $demoStatuses === null) {
            return 0;
        }

        // Keep two records in the weekly dashboard sample and place the
        // remaining facilities in the completed-day Inspection List dataset.
        return max(1, $tehsilTotal - 2);
    }

    private function healthInspectionDateForIndex(
        int $index,
        int $globalIndex,
        int $tehsilTotal,
        int $completedDayRecordCount,
        bool $priorityTehsil,
        int $tehsilId = 0,
    ): Carbon {
        if ($tehsilId === 24) {
            if ($index === 0) {
                return $this->todayInspectionDateInActiveWeek(15, 30);
            }

            if ($index === 1) {
                return $this->todayInspectionDateInActiveWeek(12, 15);
            }

            return $this->latestCompletedDayDateForIndex($index - 2);
        }

        if ($completedDayRecordCount > 0 && $index >= 2 && $index < 2 + $completedDayRecordCount) {
            return $this->latestCompletedDayDateForIndex($index - 2);
        }

        $activeWeekSlots = $priorityTehsil ? 3 : max(2, (int) ceil($tehsilTotal * 0.45));
        if ($index < $completedDayRecordCount + $activeWeekSlots) {
            return $this->activeWeekDateForIndex($index + $globalIndex);
        }

        return $this->currentMonthPreviousWeekDateForIndex($globalIndex);
    }

    private function currentMonthPreviousWeekDateForIndex(int $index): Carbon
    {
        $tz = config('app.inspection_timezone', 'Asia/Karachi');
        $databaseTimezone = config('app.timezone', 'UTC');
        $now = now($tz);
        $period = app(\App\Services\KpiPeriodService::class);
        $range = $period->getWeekDateRange($period->currentWeekNo());
        $currentWeekStart = $range['start'] ?? null;
        $currentWeekStart = $currentWeekStart?->copy()->setTimezone($tz)->startOfDay();
        $monthStart = $now->copy()->startOfMonth()->startOfDay();
        $windowEnd = ($currentWeekStart ?? $now->copy()->startOfWeek())->copy()->subDay()->endOfDay();

        if ($windowEnd->lt($monthStart)) {
            $windowEnd = $now->copy()->subDay()->endOfDay();
        }

        $daysAvailable = max(1, $monthStart->diffInDays($windowEnd) + 1);
        $dayOffset = $index % $daysAvailable;

        return $windowEnd
            ->copy()
            ->subDays($dayOffset)
            ->setTime(9 + ($index % 7), 10 * ($index % 5), 0)
            ->setTimezone($databaseTimezone);
    }

    /** @return list<string>|null */
    private function demoTehsilStatusPlan(int $tehsilId): ?array
    {
        // Review Target = ceil(inspected * 0.20) = 1 when inspected = 2.
        // Only one facility may be selected for review (approved);
        // the second remains inspected-only (blue map pin).
        return match ($tehsilId) {
            24, 25, 81 => ['approved', 'inspected_only'],
            default => null,
        };
    }

    /**
     * @return array{0: list<array<string, mixed>>, 1: list<array<string, mixed>>}
     */
    private function buildRotiTehsilInspections(object $card, $users, int &$refCounter, Carbon $now, string $batch): array
    {
        $statuses = $this->statusSequence();
        $entities = $card->slug === 'inspection-of-health-facilities'
            ? $this->healthFacilityEntities()
            : $this->entitiesForSlug($card->slug, $card->title);
        $tehsilPlan = [
            ['tehsil_id' => 81, 'district_id' => 23, 'division_id' => 6, 'count' => 14, 'tehsil_name' => 'Lahore City', 'district_name' => 'Lahore', 'lat' => 31.5204, 'lng' => 74.3587, 'inspector' => 'ac.lahore'],
            ['tehsil_id' => 24, 'district_id' => 7, 'division_id' => 2, 'count' => 12, 'tehsil_name' => 'Layyah', 'district_name' => 'Layyah', 'lat' => 30.9617, 'lng' => 70.9397, 'inspector' => 'ac.layyah'],
            ['tehsil_id' => 25, 'district_id' => 7, 'division_id' => 2, 'count' => 10, 'tehsil_name' => 'Karor Lal Esan', 'district_name' => 'Layyah', 'lat' => 30.9520, 'lng' => 70.9280, 'inspector' => 'ac.karor'],
        ];

        $rows = [];
        $attachments = [];
        $globalIndex = 0;

        foreach ($tehsilPlan as $plan) {
            $side = [
                'division_id' => $plan['division_id'],
                'district_id' => $plan['district_id'],
                'tehsil_id' => $plan['tehsil_id'],
                'tehsil_name' => $plan['tehsil_name'],
                'district_name' => $plan['district_name'],
                'lat' => $plan['lat'],
                'lng' => $plan['lng'],
            ];
            $inspector = $users->get($plan['inspector']);
            $reviewer = $users->get(in_array($plan['tehsil_id'], [24, 25], true) ? 'dc.layyah' : 'dc.lahore');

            for ($i = 0; $i < $plan['count']; $i++) {
                $status = $statuses[$globalIndex % count($statuses)];
                $inspectedAt = $i < 7
                    ? ($i === 0 ? now()->copy()->setTime(10, 30) : $this->activeWeekDateForIndex($i))
                    : $this->inspectionDateForIndex($globalIndex, $plan['count'], true);
                $entity = $entities[$globalIndex % count($entities)];
                $reference = sprintf('INSP-%s-%06d', $now->format('Y'), $refCounter++);
                $detailData = \Database\Seeders\Support\KpiInspectionDetailFactory::forSlug($card->slug, $globalIndex);
                $location = $this->locationFor($side, $globalIndex);
                $fullAddress = $this->fullAddress($side, $entity, $location);

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
                    'entity_name' => $entity['name'].' #'.($i + 1),
                    'entity_type' => $entity['type'],
                    'identifier' => $entity['id'].'-'.$plan['tehsil_id'].'-'.$i,
                    'address' => $fullAddress,
                    'latitude' => $location['lat'],
                    'longitude' => $location['lng'],
                    'inspection_datetime' => $inspectedAt,
                    'status' => $status,
                    'observations' => json_encode(['Tandoor price and weight verified on site.']),
                    'actions_required' => json_encode($status === 'rejected' ? ['Re-inspection required.'] : ['Continue routine monitoring.']),
                    'actions_taken' => json_encode(['Photographic evidence captured.']),
                    'detail_data' => json_encode($detailData),
                    'review_remarks' => $status === 'approved' ? 'Inspection evidence verified and accepted.' : null,
                    'rejection_reason' => $status === 'rejected' ? 'Evidence incomplete.' : null,
                    'reviewed_at' => $status === 'pending_review' ? null : $inspectedAt->copy()->addHours(4),
                    'is_demo' => true,
                    'seed_batch' => $batch,
                    'created_at' => $inspectedAt,
                    'updated_at' => $status === 'pending_review' ? $inspectedAt : $inspectedAt->copy()->addHours(4),
                ];

                $attachments[] = [
                    'reference_no' => $reference,
                    'slug' => $card->slug,
                    'count' => 1 + ($globalIndex % 2),
                    'ts' => $inspectedAt,
                ];

                $globalIndex++;
            }
        }

        return [$rows, $attachments];
    }

    /** @return array<string, mixed> */
    private function healthObservationTemplate(int $index, int $tehsilId): array
    {
        $base = \Database\Seeders\Support\KpiInspectionDetailFactory::forSlug('inspection-of-health-facilities', $index);

        // AC Layyah / AC Karor / AC Lahore demo facilities: exact positive/negative
        // pairs so every observation card totals Facilities Inspected (2).
        // Indexes >= 2 are completed-day Inspection List rows only.
        if (in_array($tehsilId, [24, 25, 81], true) && $index < 2) {
            $pattern = $index === 0
                ? [
                    'deep_cleaning_available' => 'satisfactory',
                    'doctors_paramedics_available' => 'available',
                    'medicines_available' => 'available',
                    'medicine_flex_displayed' => 'displayed',
                    'medicine_led_functional' => 'functional',
                    'diagnostic_services_functional' => 'functional',
                    'uhi_compliance' => 'compliant',
                    'utilities_available' => 'functional',
                    'drinking_water_available' => 'available',
                ]
                : [
                    'deep_cleaning_available' => 'unsatisfactory',
                    'doctors_paramedics_available' => 'available',
                    'medicines_available' => 'unavailable',
                    'medicine_flex_displayed' => 'not_displayed',
                    'medicine_led_functional' => 'non_functional',
                    'diagnostic_services_functional' => 'functional',
                    'uhi_compliance' => 'non_compliant',
                    'utilities_available' => 'functional',
                    'drinking_water_available' => 'unavailable',
                ];

            return array_merge($base, $pattern);
        }

        $slot = $index % 5;

        return array_merge($base, [
            'deep_cleaning_available' => $slot < 4 ? 'satisfactory' : 'unsatisfactory',
            'doctors_paramedics_available' => $slot < 3 ? 'available' : 'unavailable',
            'medicines_available' => $slot < 4 ? 'available' : 'unavailable',
            'medicine_flex_displayed' => $slot < 2 ? 'displayed' : 'not_displayed',
            'medicine_led_functional' => ($slot < 3 ? 'functional' : 'non_functional'),
            'diagnostic_services_functional' => $slot < 4 ? 'functional' : 'non_functional',
            'uhi_compliance' => $slot < 3 ? 'compliant' : 'non_compliant',
            'utilities_available' => $slot < 4 ? 'functional' : 'non_functional',
            'drinking_water_available' => $slot < 4 ? 'available' : 'unavailable',
        ]);
    }

    /** @return list<array<string, string>> */
    private function healthObservationMixedPatterns(): array
    {
        return [
            [
                'deep_cleaning_available' => 'not_available',
                'staff_available' => 'available',
                'medicine_flex_available' => 'not_available',
                'testing_equipment_available' => 'available',
                'drinking_water_available' => 'available',
                'utilities_available' => 'not_available',
                'uhi_compliance' => 'no',
            ],
            [
                'deep_cleaning_available' => 'available',
                'staff_available' => 'not_available',
                'medicine_flex_available' => 'available',
                'testing_equipment_available' => 'not_available',
                'drinking_water_available' => 'available',
                'utilities_available' => 'available',
                'uhi_compliance' => 'yes',
            ],
            [
                'deep_cleaning_available' => 'available',
                'staff_available' => 'available',
                'medicine_flex_available' => 'not_available',
                'testing_equipment_available' => 'available',
                'drinking_water_available' => 'not_available',
                'utilities_available' => 'available',
                'uhi_compliance' => 'no',
            ],
            [
                'deep_cleaning_available' => 'not_available',
                'staff_available' => 'not_available',
                'medicine_flex_available' => 'available',
                'testing_equipment_available' => 'available',
                'drinking_water_available' => 'available',
                'utilities_available' => 'not_available',
                'uhi_compliance' => 'yes',
            ],
            [
                'deep_cleaning_available' => 'available',
                'staff_available' => 'available',
                'medicine_flex_available' => 'available',
                'testing_equipment_available' => 'not_available',
                'drinking_water_available' => 'not_available',
                'utilities_available' => 'not_available',
                'uhi_compliance' => 'yes',
            ],
            [
                'deep_cleaning_available' => 'not_available',
                'staff_available' => 'available',
                'medicine_flex_available' => 'available',
                'testing_equipment_available' => 'available',
                'drinking_water_available' => 'available',
                'utilities_available' => 'available',
                'uhi_compliance' => 'no',
            ],
            [
                'deep_cleaning_available' => 'available',
                'staff_available' => 'not_available',
                'medicine_flex_available' => 'not_available',
                'testing_equipment_available' => 'not_available',
                'drinking_water_available' => 'available',
                'utilities_available' => 'available',
                'uhi_compliance' => 'yes',
            ],
            [
                'deep_cleaning_available' => 'not_available',
                'staff_available' => 'available',
                'medicine_flex_available' => 'not_available',
                'testing_equipment_available' => 'available',
                'drinking_water_available' => 'not_available',
                'utilities_available' => 'not_available',
                'uhi_compliance' => 'no',
            ],
            [
                'deep_cleaning_available' => 'available',
                'staff_available' => 'available',
                'medicine_flex_available' => 'not_available',
                'testing_equipment_available' => 'available',
                'drinking_water_available' => 'available',
                'utilities_available' => 'not_available',
                'uhi_compliance' => 'yes',
            ],
            [
                'deep_cleaning_available' => 'not_available',
                'staff_available' => 'not_available',
                'medicine_flex_available' => 'not_available',
                'testing_equipment_available' => 'available',
                'drinking_water_available' => 'available',
                'utilities_available' => 'available',
                'uhi_compliance' => 'no',
            ],
            [
                'deep_cleaning_available' => 'available',
                'staff_available' => 'not_available',
                'medicine_flex_available' => 'available',
                'testing_equipment_available' => 'not_available',
                'drinking_water_available' => 'not_available',
                'utilities_available' => 'available',
                'uhi_compliance' => 'yes',
            ],
            [
                'deep_cleaning_available' => 'not_available',
                'staff_available' => 'available',
                'medicine_flex_available' => 'available',
                'testing_equipment_available' => 'not_available',
                'drinking_water_available' => 'available',
                'utilities_available' => 'not_available',
                'uhi_compliance' => 'yes',
            ],
        ];
    }

    private function activeWeekDateForIndex(int $index): Carbon
    {
        $tz = config('app.inspection_timezone', 'Asia/Karachi');
        $databaseTimezone = config('app.timezone', 'UTC');
        $period = app(\App\Services\KpiPeriodService::class);
        $range = $period->getWeekDateRange($period->currentWeekNo());
        $start = ($range['start'] ?? now($tz)->startOfDay())->copy()->setTimezone($tz);
        $now = now($tz);
        $elapsedDays = max(0, min(6, $start->copy()->startOfDay()->diffInDays($now->copy()->startOfDay())));
        $dayOffset = min($elapsedDays, $index % 6);

        $candidate = $start
            ->copy()
            ->addDays($dayOffset)
            ->startOfDay()
            ->setTime(9 + ($index % 7), 10 * ($index % 5), 0);

        if ($candidate->gt($now)) {
            $candidate = $now->copy();
        }

        return $candidate->setTimezone($databaseTimezone);
    }

    /**
     * Keep seeded visit KPI data aligned with the dashboard's default completed week.
     *
     * @param  list<array<string, mixed>>  $rows
     * @param  list<array<string, mixed>>  $attachments
     * @return array{0: list<array<string, mixed>>, 1: list<array<string, mixed>>}
     */
    private function placeVisitDataInLatestCompletedWeek(array $rows, array $attachments): array
    {
        $tz = config('app.inspection_timezone', 'Asia/Karachi');
        $databaseTimezone = config('app.timezone', 'UTC');
        $period = app(\App\Services\KpiPeriodService::class);
        // The Education and Health dashboards open on the most recent fully
        // completed Thu-Wed reporting week. Keep fresh demo inspections in that
        // exact window so charts, observation cards, maps, and inspection lists
        // all resolve the same seeded records without changing the period filter.
        $range = $period->getWeekDateRange($period->latestCompletedWeekNo());
        $start = ($range['start'] ?? now($tz)->subWeek()->startOfDay())->copy()->setTimezone($tz);
        $datesByReference = [];

        foreach ($rows as $index => &$row) {
            $detail = is_array($row['detail_data'] ?? null)
                ? $row['detail_data']
                : (json_decode((string) ($row['detail_data'] ?? '[]'), true) ?: []);
            if (($detail['inspection_list_only'] ?? false) === true) {
                $datesByReference[$row['reference_no']] = $row['inspection_datetime'];
                continue;
            }

            $inspectedAt = $start->copy()
                ->addDays($index % 7)
                ->setTime(9 + ($index % 8), 10 * ($index % 6), 0)
                ->setTimezone($databaseTimezone);
            $unreviewed = in_array($row['status'], ['pending_review', 'draft', 'inspected_only'], true);
            $reviewedAt = $unreviewed ? null : $inspectedAt->copy()->addHours(3);

            $row['inspection_datetime'] = $inspectedAt;
            $row['reviewed_at'] = $reviewedAt;
            $row['created_at'] = $inspectedAt;
            $row['updated_at'] = $reviewedAt ?? $inspectedAt;
            $datesByReference[$row['reference_no']] = $inspectedAt;
        }
        unset($row);

        foreach ($attachments as &$attachment) {
            $attachment['ts'] = $datesByReference[$attachment['reference_no']] ?? $attachment['ts'];
        }
        unset($attachment);

        return [$rows, $attachments];
    }

    private function latestCompletedDayDateForIndex(int $index): Carbon
    {
        $completedHour = min(17, 8 + ($index % 10));

        return now(config('app.inspection_timezone', 'Asia/Karachi'))
            ->subDay()
            ->startOfDay()
            ->setTime(
                $completedHour,
                $completedHour === 17 ? 0 : 10 * ($index % 6),
                0,
            )
            ->setTimezone(config('app.timezone', 'UTC'));
    }
}
