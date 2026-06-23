<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class KpiInspectionSeeder extends Seeder
{
    private const PER_KPI = 12;

    private const LAHORE = ['division_id' => 6, 'district_id' => 23, 'tehsil_id' => 81, 'lat' => 31.5204, 'lng' => 74.3587, 'area' => 'Lahore'];

    private const LAYYAH = ['division_id' => 2, 'district_id' => 7, 'tehsil_id' => 24, 'lat' => 30.9617, 'lng' => 70.9397, 'area' => 'Layyah'];

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
            $statuses = $this->statusSequence();
            $entities = $this->entitiesForSlug($card->slug, $card->title);

            foreach (range(0, self::PER_KPI - 1) as $i) {
                $side = $i < 6 ? self::LAHORE : self::LAYYAH;
                $inspector = $users->get($i % 2 === 0 ? ($side === self::LAHORE ? 'ac.lahore' : 'ac.layyah') : ($side === self::LAHORE ? 'dc.lahore' : 'dc.layyah'));
                $reviewer = $users->get($side === self::LAHORE ? 'dc.lahore' : 'dc.layyah');

                $status = $statuses[$i];
                $inspectedAt = $now->copy()->subDays(($cardIndex * 3) + $i + 2)->setTime(9 + ($i % 6), 15 * ($i % 4), 0);
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

                $detailData = [
                    'facility_category' => $entity['type'],
                    'visit_type' => $i % 2 === 0 ? 'Scheduled' : 'Follow-up',
                    'compliance_score' => 55 + (($i + $cardIndex) % 40),
                    'priority' => $status === 'rejected' ? 'High' : 'Normal',
                ];

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
                    'address' => $entity['address'].', '.$side['area'].' Tehsil',
                    'latitude' => round($side['lat'] + (($i % 5) * 0.004) + ($cardIndex * 0.0003), 7),
                    'longitude' => round($side['lng'] + (($i % 4) * 0.004) + ($cardIndex * 0.0002), 7),
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
            'pending_review', 'approved', 'approved', 'pending_review',
            'approved', 'rejected', 'pending_review', 'approved',
            'approved', 'rejected', 'pending_review', 'approved',
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
            'inspection-of-health-facilities' => [
                ['title' => 'DHQ Hospital Inspection', 'name' => 'DHQ Hospital', 'type' => 'Hospital', 'id' => 'HSP-101', 'address' => 'Hospital Road'],
                ['title' => 'RHC Field Visit', 'name' => 'Rural Health Center', 'type' => 'Health Facility', 'id' => 'HSP-118', 'address' => 'Tehsil Road'],
            ],
            'inspection-of-educational-institutions' => [
                ['title' => 'Govt High School Visit', 'name' => 'Govt High School', 'type' => 'School', 'id' => 'EDU-201', 'address' => 'Education Avenue'],
                ['title' => 'Primary School Inspection', 'name' => 'Govt Primary School', 'type' => 'School', 'id' => 'EDU-214', 'address' => 'Model Town'],
            ],
            'price-of-roti' => [
                ['title' => 'Tandoor Price Inspection', 'name' => 'Main Bazaar Tandoor', 'type' => 'Tandoor', 'id' => 'TN-301', 'address' => 'Food Street'],
                ['title' => 'Roti Price Compliance', 'name' => 'Community Tandoor', 'type' => 'Tandoor', 'id' => 'TN-309', 'address' => 'Market Lane'],
            ],
            default => [],
        };

        return array_values(array_merge($specific, $generic));
    }
}
