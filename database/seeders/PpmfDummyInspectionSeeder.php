<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PpmfDummyInspectionSeeder extends Seeder
{
    /**
     * Seed complete dummy inspection records for all districts and KPI categories.
     *
     * Purpose:
     * - Inspection list page
     * - Inspection detail page with JSON observations/actions
     * - Inspection map page with coordinates
     * - CM Governance Scorecard District Wise / Tier Wise colour testing
     */
    public function run(): void
    {
        if (! Schema::hasTable('inspections')) {
            return;
        }

        $inspectionColumns = collect(Schema::getColumnListing('inspections'));

        $districts = DB::table('districts')
            ->when(Schema::hasColumn('districts', 'is_active'), fn ($q) => $q->where('is_active', true))
            ->orderBy('id')
            ->get();

        if ($districts->isEmpty()) {
            return;
        }

        $this->ensureDistrictTiers($districts);

        $districts = DB::table('districts')
            ->when(Schema::hasColumn('districts', 'is_active'), fn ($q) => $q->where('is_active', true))
            ->orderBy('id')
            ->get();

        $kpiCategories = DB::table('kpi_categories')
            ->when(Schema::hasColumn('kpi_categories', 'is_active'), fn ($q) => $q->where('is_active', true))
            ->orderBy('id')
            ->get(['id', 'name']);

        if ($kpiCategories->isEmpty()) {
            return;
        }

        $tehsils = DB::table('tehsils')->get();
        $users   = $this->getInspectionUsers();

        if ($users->isEmpty()) {
            return;
        }

        DB::table('inspections')
            ->where(function ($query) {
                $query->where('remarks', 'LIKE', 'PPMF dummy inspection record%')
                    ->orWhere('remarks', 'LIKE', 'Bulk dummy inspection%')
                    ->orWhere('remarks', 'LIKE', 'PPMF scorecard dummy inspection%');
            })
            ->delete();

        $allowedColumnKeys = array_flip($inspectionColumns->all());
        $records     = [];
        $recordIndex = 1;
        $now         = Carbon::now();

        foreach ($districts as $districtIndex => $district) {
            $districtTehsils = $tehsils
                ->where('district_id', $district->id)
                ->values();

            $gradeProfile = $this->districtGradeProfile($districtIndex);

            foreach ($kpiCategories as $category) {
                $totalRecords   = 12;
                $approvedTarget = (int) round($totalRecords * $gradeProfile['approved_ratio']);
                $approvedTarget = max(1, min($approvedTarget, $totalRecords - 3));

                $statuses = $this->buildStatusSequence($totalRecords, $approvedTarget);

                foreach ($statuses as $status) {
                    $tehsil = $districtTehsils->isNotEmpty()
                        ? $districtTehsils->random()
                        : null;

                    $user = $users->random();

                    // Ensure recent-week data exists for graphical reports (last_week/current_week filters).
                    // Weighted: 45% last_week, 25% current_week, 30% older (up to 120 days).
                    $roll = rand(1, 100);
                    if ($roll <= 45) {
                        $inspectionDate = $now->copy()
                            ->subWeek()
                            ->startOfWeek()
                            ->addDays(rand(0, 6));
                    } elseif ($roll <= 70) {
                        $inspectionDate = $now->copy()
                            ->startOfWeek()
                            ->addDays(rand(0, 6));
                    } else {
                        $inspectionDate = $now->copy()
                            ->subDays(rand(0, 120));
                    }

                    $inspectionDate = $inspectionDate->setTime(rand(8, 18), rand(0, 59), 0);

                    [$latitude, $longitude] = $this->districtCoordinates($district, $districtIndex);

                    $referenceNo = 'PPMF-INS-' . str_pad((string) $recordIndex, 6, '0', STR_PAD_LEFT);
                    $categoryName = $category->name ?? 'General KPI';

                    $record = [
                        'kpi_category_id'     => $category->id,
                        'division_id'         => $district->division_id ?? null,
                        'district_id'         => $district->id,
                        'tehsil_id'           => $tehsil?->id,
                        'performed_by'        => $user->id,
                        'inspection_datetime' => $inspectionDate,
                        'latitude'            => $latitude,
                        'longitude'           => $longitude,
                        'main_title'          => $categoryName . ' Inspection - ' . $district->name,
                        'main_identifier'     => $referenceNo,
                        'main_address'        => 'Sample site near ' . ($tehsil->name ?? $district->name) . ', ' . $district->name,
                        'detail_data'         => json_encode($this->buildDetailData($referenceNo, $categoryName, $district, $tehsil), JSON_UNESCAPED_UNICODE),
                        'observations'        => json_encode($this->buildObservations($categoryName, $status, $gradeProfile), JSON_UNESCAPED_UNICODE),
                        'actions'             => json_encode($this->buildActions($categoryName, $status, $gradeProfile), JSON_UNESCAPED_UNICODE),
                        'status'              => $status,
                        'remarks'             => 'PPMF scorecard dummy inspection record for list, detail, map and scorecard testing.',
                        'created_at'          => now(),
                        'updated_at'          => now(),
                    ];

                    $records[] = array_intersect_key($record, $allowedColumnKeys);

                    if (count($records) >= 200) {
                        DB::table('inspections')->insert($records);
                        $records = [];
                    }

                    $recordIndex++;
                }
            }
        }

        if (! empty($records)) {
            DB::table('inspections')->insert($records);
        }
    }

    private function getInspectionUsers()
    {
        if (Schema::hasTable('roles') && Schema::hasColumn('users', 'role_id')) {
            $roleIds = DB::table('roles')
                ->where(function ($query) {
                    $query->whereIn('slug', ['ac', 'data_entry_user', 'field_user', 'dc', 'pmru_user'])
                        ->orWhereIn('name', ['Assistant Commissioner', 'Data Entry User', 'Field User', 'Deputy Commissioner', 'PMRU User']);
                })
                ->pluck('id');

            $users = DB::table('users')
                ->whereIn('role_id', $roleIds)
                ->when(Schema::hasColumn('users', 'is_active'), fn ($q) => $q->where('is_active', true))
                ->get();

            if ($users->isNotEmpty()) {
                return $users;
            }
        }

        return DB::table('users')
            ->when(Schema::hasColumn('users', 'is_active'), fn ($q) => $q->where('is_active', true))
            ->get();
    }

    private function ensureDistrictTiers($districts): void
    {
        if (! Schema::hasColumn('districts', 'tier')) {
            return;
        }

        foreach ($districts as $index => $district) {
            if (! empty($district->tier)) {
                continue;
            }

            DB::table('districts')
                ->where('id', $district->id)
                ->update(['tier' => ($index % 3) + 1]);
        }
    }

    private function districtGradeProfile(int $districtIndex): array
    {
        $profiles = [
            ['grade' => 'A+', 'approved_ratio' => 0.92, 'risk' => 'Low'],
            ['grade' => 'A',  'approved_ratio' => 0.84, 'risk' => 'Low'],
            ['grade' => 'B',  'approved_ratio' => 0.75, 'risk' => 'Medium'],
            ['grade' => 'C',  'approved_ratio' => 0.64, 'risk' => 'Medium'],
            ['grade' => 'D',  'approved_ratio' => 0.55, 'risk' => 'High'],
            ['grade' => 'E',  'approved_ratio' => 0.42, 'risk' => 'Critical'],
        ];

        return $profiles[$districtIndex % count($profiles)];
    }

    private function buildStatusSequence(int $totalRecords, int $approvedTarget): array
    {
        $remaining = $totalRecords - $approvedTarget;

        $reviewed  = max(1, (int) floor($remaining * 0.40));
        $submitted = max(1, (int) floor($remaining * 0.35));
        $rejected  = max(1, $remaining - $reviewed - $submitted);

        $statuses = array_merge(
            array_fill(0, $approvedTarget, 'approved'),
            array_fill(0, $reviewed, 'reviewed'),
            array_fill(0, $submitted, 'submitted'),
            array_fill(0, $rejected, 'rejected')
        );

        shuffle($statuses);

        return array_slice($statuses, 0, $totalRecords);
    }

    private function buildDetailData(string $referenceNo, string $categoryName, object $district, ?object $tehsil): array
    {
        $base = [
            'reference_no'      => $referenceNo,
            'inspection_source' => 'PPMF Dummy Seeder',
            'district'          => $district->name,
            'tehsil'            => $tehsil->name ?? 'N/A',
            'union_council'     => 'UC-' . rand(1, 120),
            'inspection_shift'  => collect(['Morning', 'Afternoon', 'Evening'])->random(),
            'kpi_category'      => $categoryName,
        ];

        if (Str::contains(Str::lower($categoryName), ['water', 'filtration', 'filter'])) {
            return $base + [
                'plant_code'        => 'WFP-' . rand(100, 999),
                'water_source'      => collect(['Tube Well', 'Canal Supply', 'Municipal Line'])->random(),
                'chlorination_unit' => collect(['Functional', 'Partially Functional', 'Not Functional'])->random(),
                'sample_collected'  => collect(['Yes', 'No'])->random(),
            ];
        }

        if (Str::contains(Str::lower($categoryName), ['marriage', 'hall'])) {
            return $base + [
                'hall_registration_no' => 'MH-' . rand(1000, 9999),
                'event_type'           => collect(['Wedding', 'Valima', 'Corporate Event'])->random(),
                'guest_capacity'       => rand(100, 600),
                'one_dish_checked'     => collect(['Yes', 'No'])->random(),
            ];
        }

        if (Str::contains(Str::lower($categoryName), ['dog', 'stray'])) {
            return $base + [
                'complaint_reference' => 'SD-' . rand(1000, 9999),
                'dogs_observed'       => rand(0, 18),
                'hotspot_type'        => collect(['Market', 'School Area', 'Residential Street', 'Park'])->random(),
                'team_deployed'       => collect(['Yes', 'No'])->random(),
            ];
        }

        if (Str::contains(Str::lower($categoryName), ['manhole', 'cover'])) {
            return $base + [
                'street_name'       => 'Street ' . rand(1, 80),
                'manholes_checked'  => rand(3, 30),
                'missing_covers'    => rand(0, 8),
                'repair_team_called'=> collect(['Yes', 'No'])->random(),
            ];
        }

        return $base + [
            'site_code'          => 'SITE-' . rand(1000, 9999),
            'record_verified'    => collect(['Yes', 'No'])->random(),
            'public_interaction' => collect(['Yes', 'No'])->random(),
            'service_level'      => collect(['Excellent', 'Good', 'Average', 'Poor'])->random(),
        ];
    }

    private function buildObservations(string $categoryName, string $status, array $profile): array
    {
        $common = [
            'overall_condition'      => $this->conditionByStatus($status),
            'records_verified'       => $status === 'rejected' ? 'No' : 'Yes',
            'staff_available'        => $status === 'submitted' ? 'Partially' : 'Yes',
            'public_feedback'        => collect(['Satisfactory', 'Average', 'Unsatisfactory'])->random(),
            'risk_level'             => $profile['risk'],
            'performance_grade_hint' => $profile['grade'],
        ];

        if (Str::contains(Str::lower($categoryName), ['water', 'filtration', 'filter'])) {
            return $common + [
                'plant_functional'       => $status === 'approved' ? 'Yes' : collect(['Yes', 'Partially', 'No'])->random(),
                'filter_media_condition' => collect(['Clean', 'Needs Replacement', 'Damaged'])->random(),
                'water_quality_visible'  => collect(['Clear', 'Slightly Turbid', 'Turbid'])->random(),
                'lab_test_required'      => collect(['Yes', 'No'])->random(),
            ];
        }

        if (Str::contains(Str::lower($categoryName), ['marriage', 'hall'])) {
            return $common + [
                'one_dish_compliance'    => $status === 'approved' ? 'Compliant' : collect(['Compliant', 'Violation Observed'])->random(),
                'timing_compliance'      => collect(['Compliant', 'Minor Delay', 'Violation'])->random(),
                'cleanliness_status'     => collect(['Good', 'Average', 'Poor'])->random(),
                'price_displayed'        => collect(['Yes', 'No'])->random(),
            ];
        }

        if (Str::contains(Str::lower($categoryName), ['dog', 'stray'])) {
            return $common + [
                'hotspot_verified'       => 'Yes',
                'number_of_dogs_seen'    => rand(0, 20),
                'citizen_risk_observed'  => collect(['Low', 'Medium', 'High'])->random(),
                'team_response_status'   => collect(['Responded', 'Partially Responded', 'Pending'])->random(),
            ];
        }

        if (Str::contains(Str::lower($categoryName), ['manhole', 'cover'])) {
            return $common + [
                'cover_available'        => $status === 'approved' ? 'Yes' : collect(['Yes', 'No', 'Damaged'])->random(),
                'hazard_level'           => collect(['Low', 'Medium', 'High'])->random(),
                'barricading_done'       => collect(['Yes', 'No'])->random(),
                'repair_status'          => collect(['Completed', 'In Progress', 'Pending'])->random(),
            ];
        }

        return $common + [
            'service_available'       => collect(['Yes', 'Partially', 'No'])->random(),
            'compliance_status'       => collect(['Compliant', 'Partially Compliant', 'Non-Compliant'])->random(),
            'issue_found'             => collect(['No Major Issue', 'Minor Issue', 'Major Issue'])->random(),
            'photo_evidence_verified' => collect(['Yes', 'No'])->random(),
        ];
    }

    private function buildActions(string $categoryName, string $status, array $profile): array
    {
        $needsFollowUp = in_array($status, ['submitted', 'reviewed', 'rejected'], true);

        $common = [
            'action_required'       => $needsFollowUp ? 'Yes' : 'No',
            'priority'              => $profile['risk'],
            'responsible_officer'   => collect(['Assistant Commissioner', 'Deputy Commissioner', 'Municipal Officer', 'Field Inspector'])->random(),
            'follow_up_required'    => $needsFollowUp ? 'Yes' : 'No',
            'target_completion_date'=> now()->addDays(rand(3, 15))->toDateString(),
        ];

        if (Str::contains(Str::lower($categoryName), ['water', 'filtration', 'filter'])) {
            return $common + [
                'recommended_action' => collect(['Replace filter media', 'Repair chlorination unit', 'Clean water tank', 'Collect lab sample'])->random(),
                'technical_team'     => 'Public Health Engineering Team',
                'estimated_cost'     => rand(10000, 85000),
            ];
        }

        if (Str::contains(Str::lower($categoryName), ['marriage', 'hall'])) {
            return $common + [
                'recommended_action' => collect(['Issue warning', 'Impose fine', 'Verify licence', 'Re-inspection required'])->random(),
                'fine_amount'        => $status === 'rejected' ? rand(10000, 50000) : rand(0, 10000),
                'notice_issued'      => $needsFollowUp ? 'Yes' : 'No',
            ];
        }

        if (Str::contains(Str::lower($categoryName), ['dog', 'stray'])) {
            return $common + [
                'recommended_action' => collect(['Deploy response team', 'Mark hotspot', 'Coordinate with municipal staff', 'Follow-up visit'])->random(),
                'team_required'      => 'Municipal Stray Dog Response Team',
                'public_warning'     => collect(['Yes', 'No'])->random(),
            ];
        }

        if (Str::contains(Str::lower($categoryName), ['manhole', 'cover'])) {
            return $common + [
                'recommended_action' => collect(['Install new cover', 'Repair damaged cover', 'Barricade hazard point', 'Escalate to municipal team'])->random(),
                'material_required'  => collect(['RCC Cover', 'Steel Cover', 'Warning Board', 'Barricade'])->random(),
                'safety_risk'        => collect(['Low', 'Medium', 'High'])->random(),
            ];
        }

        return $common + [
            'recommended_action' => collect(['Re-inspection', 'Issue direction', 'Resolve minor issue', 'Escalate for compliance'])->random(),
            'compliance_deadline'=> now()->addDays(rand(5, 20))->toDateString(),
            'remarks'            => 'Dummy action data generated for inspection detail testing.',
        ];
    }

    private function conditionByStatus(string $status): string
    {
        return match ($status) {
            'approved'  => collect(['Excellent', 'Good', 'Satisfactory'])->random(),
            'reviewed'  => collect(['Good', 'Average', 'Needs Follow-up'])->random(),
            'submitted' => collect(['Pending Review', 'Average', 'Needs Verification'])->random(),
            'rejected'  => collect(['Poor', 'Non-Compliant', 'Critical Issue Found'])->random(),
            default     => 'N/A',
        };
    }

    private function districtCoordinates(object $district, int $index): array
    {
        $known = [
            'lahore'     => [31.5204, 74.3587],
            'faisalabad' => [31.4504, 73.1350],
            'rawalpindi' => [33.5651, 73.0169],
            'multan'     => [30.1575, 71.5249],
            'gujranwala' => [32.1877, 74.1945],
            'sialkot'    => [32.4945, 74.5229],
            'bahawalpur' => [29.3956, 71.6836],
            'sargodha'   => [32.0836, 72.6711],
            'dg khan'    => [30.0325, 70.6402],
            'layyah'     => [30.9648, 70.9399],
            'bhakkar'    => [31.6253, 71.0657],
            'rajanpur'   => [29.1041, 70.3297],
        ];

        $name = Str::lower($district->name ?? '');
        $base = $known[$name] ?? [29.5 + (($index % 14) * 0.28), 70.0 + (($index % 18) * 0.22)];

        return [
            round($base[0] + (rand(-80, 80) / 10000), 6),
            round($base[1] + (rand(-80, 80) / 10000), 6),
        ];
    }
}
