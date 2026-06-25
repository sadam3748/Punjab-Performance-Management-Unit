<?php

namespace Database\Seeders;

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
                [$visitRows, $visitAttachments] = $this->buildVisitKpiInspections(
                    $card,
                    $users,
                    $refCounter,
                    $now,
                    $batch
                );
                $inspectionRows = array_merge($inspectionRows, $visitRows);
                $attachmentPlan = array_merge($attachmentPlan, $visitAttachments);

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
                for ($a = 0; $a < $plan['count']; $a++) {
                    $attachmentRows[] = [
                        'kpi_inspection_id' => $inspectionId,
                        'file_path' => $imagePath,
                        'file_name' => basename($imagePath),
                        'file_type' => 'image',
                        'mime_type' => 'image/png',
                        'caption' => 'Field evidence photo '.($a + 1),
                        'latitude' => null,
                        'longitude' => null,
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
                ['title' => 'DHQ Hospital Inspection', 'name' => 'DHQ Hospital', 'type' => 'Hospital', 'id' => 'HSP-101', 'address' => 'Hospital Road'],
                ['title' => 'RHC Field Visit', 'name' => 'Rural Health Center', 'type' => 'RHC', 'id' => 'HSP-118', 'address' => 'Tehsil Road'],
                ['title' => 'BHU Inspection', 'name' => 'Basic Health Unit Township', 'type' => 'BHU', 'id' => 'HSP-132', 'address' => 'Township'],
                ['title' => 'Dispensary Check', 'name' => 'Govt Dispensary', 'type' => 'Dispensary', 'id' => 'HSP-145', 'address' => 'Health Avenue'],
            ],
            default => [],
        };

        return array_values(array_merge($specific, $generic));
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
        $entities = $this->entitiesForSlug($card->slug, $card->title);
        $tehsilPlan = [
            ['tehsil_id' => 81, 'district_id' => 23, 'division_id' => 6, 'count' => 14, 'tehsil_name' => 'Lahore City', 'district_name' => 'Lahore', 'lat' => 31.5204, 'lng' => 74.3587],
            ['tehsil_id' => 82, 'district_id' => 23, 'division_id' => 6, 'count' => 8, 'tehsil_name' => 'Lahore Cantonment', 'district_name' => 'Lahore', 'lat' => 31.5320, 'lng' => 74.3420],
            ['tehsil_id' => 83, 'district_id' => 23, 'division_id' => 6, 'count' => 5, 'tehsil_name' => 'Model Town', 'district_name' => 'Lahore', 'lat' => 31.4834, 'lng' => 74.3250],
            ['tehsil_id' => 84, 'district_id' => 23, 'division_id' => 6, 'count' => 4, 'tehsil_name' => 'Raiwind', 'district_name' => 'Lahore', 'lat' => 31.2484, 'lng' => 74.2203],
            ['tehsil_id' => 85, 'district_id' => 23, 'division_id' => 6, 'count' => 4, 'tehsil_name' => 'Shalimar', 'district_name' => 'Lahore', 'lat' => 31.5870, 'lng' => 74.3805],
            ['tehsil_id' => 24, 'district_id' => 7, 'division_id' => 2, 'count' => 11, 'tehsil_name' => 'Layyah', 'district_name' => 'Layyah', 'lat' => 30.9617, 'lng' => 70.9397],
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
            $inspectorUsername = $plan['tehsil_id'] === 24 ? 'ac.layyah' : 'ac.lahore';
            $reviewerUsername = $plan['tehsil_id'] === 24 ? 'dc.layyah' : 'dc.lahore';
            $inspector = $users->get($inspectorUsername);
            $reviewer = $users->get($reviewerUsername);

            for ($i = 0; $i < $plan['count']; $i++) {
                $status = $statuses[$globalIndex % count($statuses)];
                $inspectedAt = $this->visitInspectionDateForIndex($globalIndex, $plan['count']);
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
                    'entity_name' => $entity['name'],
                    'entity_type' => $entity['type'],
                    'identifier' => $entity['id'].'-'.$plan['tehsil_id'],
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
                    'reviewed_at' => $status === 'pending_review' ? null : $inspectedAt->copy()->addHours(6),
                    'is_demo' => true,
                    'seed_batch' => $batch,
                    'created_at' => $inspectedAt,
                    'updated_at' => $status === 'pending_review' ? $inspectedAt : $inspectedAt->copy()->addHours(6),
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
}
