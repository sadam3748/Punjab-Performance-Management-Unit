<?php
namespace Database\Seeders;

use App\Services\ScorecardService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PpmfDummyInspectionSeeder extends Seeder
{
    /**
     * Seed realistic inspection records for all districts and KPI categories.
     *
     * Purpose:
     * - Inspection list page
     * - Inspection detail page with JSON observations/actions
     * - Inspection map page with coordinates
     * - CM Governance Scorecard District Wise / Tier Wise testing
     */
    public function run(): void
    {
        if (! Schema::hasTable('inspections')) {
            return;
        }

        $inspectionColumns = collect(Schema::getColumnListing('inspections'));

        $districts = DB::table('districts')
            ->when(Schema::hasColumn('districts', 'is_active'), fn($q) => $q->where('is_active', true))
            ->orderBy('id')
            ->get();

        if ($districts->isEmpty()) {
            return;
        }

        $this->ensureDistrictTiers($districts);

        $districts = DB::table('districts')
            ->when(Schema::hasColumn('districts', 'is_active'), fn($q) => $q->where('is_active', true))
            ->orderBy('id')
            ->get();

        $kpiCategories = DB::table('kpi_categories')
            ->when(Schema::hasColumn('kpi_categories', 'is_active'), fn($q) => $q->where('is_active', true))
            ->orderBy('id')
            ->get(['id', 'name', 'slug']);

        if ($kpiCategories->isEmpty()) {
            return;
        }

        $tehsils = DB::table('tehsils')->get();
        $users   = $this->getInspectionUsers();

        if ($users->isEmpty()) {
            return;
        }

        /*
         * Remove only old/generated inspection seed rows.
         * Do not delete by generic words like "Inspection", because valid realistic
         * records also use titles such as "Govt School Inspection".
         */
        $deleteQuery = DB::table('inspections');

        $deleteQuery->where(function ($query) {
            if (Schema::hasColumn('inspections', 'main_identifier')) {
                $query->where('main_identifier', 'LIKE', 'PPMF-INS-%')
                    ->orWhere('main_identifier', 'LIKE', 'PPMF-DUMMY-%')
                    ->orWhere('main_identifier', 'LIKE', 'INS-' . now()->format('Y') . '-%');
            }

            if (Schema::hasColumn('inspections', 'main_title')) {
                $query->orWhere('main_title', 'LIKE', 'PPMF Dummy Inspection%')
                    ->orWhere('main_title', 'LIKE', ' Inspection %');
            }

            if (Schema::hasColumn('inspections', 'main_address')) {
                $query->orWhere('main_address', 'LIKE', 'Sample Location%');
            }

            if (Schema::hasColumn('inspections', 'remarks')) {
                $query->orWhere('remarks', 'LIKE', '%PPMF dummy%')
                    ->orWhere('remarks', 'LIKE', '%Routine field inspection conducted for weekly monitoring%');
            }

            if (Schema::hasColumn('inspections', 'detail_data')) {
                $query->orWhere('detail_data', 'LIKE', '%PPMF-DUMMY%')
                    ->orWhere('detail_data', 'LIKE', '%PPMF Seeder%')
                    ->orWhere('detail_data', 'LIKE', '%seeded_realistic_inspection%')
                    ->orWhere('detail_data', 'LIKE', '%dummy_record%')
                    ->orWhere('detail_data', 'LIKE', '%Sample Location%');
            }
        });

        $deleteQuery->delete();

        $allowedColumnKeys = array_flip($inspectionColumns->all());
        $records           = [];
        $recordIndex       = 1;
        $now               = Carbon::now();

        /*
         * Old PPMF week logic:
         * Keep seeded dates inside last completed week/current week also,
         * so week filters and graphical reports show records.
         */
        $scorecard     = app(ScorecardService::class);
        $lastCompleted = $scorecard->getLatestCompletedPpmfWeekFilters();
        $lastWeekRange = ! empty($lastCompleted['week_no'])
            ? $scorecard->getWeekDateRange((string) $lastCompleted['week_no'])
            : null;

        $lastWeekStart = $lastWeekRange['start'] ?? $now->copy()->subWeek()->startOfDay();
        $lastWeekEnd   = $lastWeekRange['end'] ?? $lastWeekStart->copy()->addDays(6)->endOfDay();

        $currentWeekStart = $lastWeekStart->copy()->addWeek();
        $currentWeekEnd   = $lastWeekEnd->copy()->addWeek();

        /*
         * Keep seed volume reasonable:
         * - all districts
         * - rotating 8 KPI categories per district
         * - 2 inspections per selected category
         */
        $categoriesPerDistrict  = 8;
        $inspectionsPerCategory = 2;

        $categoryList  = $kpiCategories->values();
        $categoryCount = max(1, $categoryList->count());

        foreach ($districts as $districtIndex => $district) {
            $districtTehsils = $tehsils
                ->where('district_id', $district->id)
                ->values();

            $gradeProfile = $this->districtGradeProfile($districtIndex);

            $start              = $districtIndex % $categoryCount;
            $selectedCategories = collect(range(0, $categoriesPerDistrict - 1))
                ->map(fn($i) => $categoryList[($start + $i) % $categoryCount])
                ->values();

            foreach ($selectedCategories as $category) {
                $statuses = $this->buildStatusSequence(
                    $inspectionsPerCategory,
                    (int) max(1, round($inspectionsPerCategory * $gradeProfile['approved_ratio']))
                );

                foreach ($statuses as $status) {
                    $tehsil = $districtTehsils->isNotEmpty()
                        ? $districtTehsils->random()
                        : null;

                    $user = $users->random();

                    $roll = rand(1, 100);

                    if ($roll <= 45) {
                        $inspectionDate = Carbon::createFromTimestamp(rand($lastWeekStart->timestamp, $lastWeekEnd->timestamp));
                    } elseif ($roll <= 70) {
                        $inspectionDate = Carbon::createFromTimestamp(rand($currentWeekStart->timestamp, $currentWeekEnd->timestamp));
                    } else {
                        $inspectionDate = $now->copy()->subDays(rand(0, 120));
                    }

                    $inspectionDate = $inspectionDate->setTime(rand(8, 18), rand(0, 59), 0);

                    [$latitude, $longitude] = $this->districtCoordinates($district, $districtIndex);

                    $referenceNo  = 'INS-' . now()->format('Y') . '-' . str_pad((string) $recordIndex, 6, '0', STR_PAD_LEFT);
                    $categoryName = $category->name ?? 'General KPI';
                    $categorySlug = $category->slug ?? Str::slug($categoryName);

                    $mainTitle   = $this->buildMainTitle($categorySlug, $categoryName, $district, $tehsil);
                    $mainAddress = $this->buildMainAddress($categorySlug, $categoryName, $district, $tehsil);

                    $record = [
                        'kpi_category_id'     => $category->id,
                        'division_id'         => $district->division_id ?? null,
                        'district_id'         => $district->id,
                        'tehsil_id'           => $tehsil?->id,
                        'performed_by'        => $user->id,
                        'inspection_datetime' => $inspectionDate,
                        'latitude'            => $latitude,
                        'longitude'           => $longitude,
                        'main_title'          => $mainTitle,
                        'main_identifier'     => $referenceNo,
                        'main_address'        => $mainAddress,
                        'detail_data'         => json_encode(
                            $this->buildDetailData($referenceNo, $categorySlug, $categoryName, $district, $tehsil, $mainTitle, $mainAddress),
                            JSON_UNESCAPED_UNICODE
                        ),
                        'observations'        => json_encode(
                            $this->buildObservations($categorySlug, $categoryName, $status, $gradeProfile),
                            JSON_UNESCAPED_UNICODE
                        ),
                        'actions'             => json_encode(
                            $this->buildActions($categorySlug, $categoryName, $status, $gradeProfile),
                            JSON_UNESCAPED_UNICODE
                        ),
                        'status'              => $status,
                        'remarks'             => 'Routine field inspection conducted for weekly monitoring and compliance review.',
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
                        ->orWhereIn('name', [
                            'Assistant Commissioner',
                            'Data Entry User',
                            'Field User',
                            'Deputy Commissioner',
                            'PMRU User',
                        ]);
                })
                ->pluck('id');

            $users = DB::table('users')
                ->whereIn('role_id', $roleIds)
                ->when(Schema::hasColumn('users', 'is_active'), fn($q) => $q->where('is_active', true))
                ->get();

            if ($users->isNotEmpty()) {
                return $users;
            }
        }

        return DB::table('users')
            ->when(Schema::hasColumn('users', 'is_active'), fn($q) => $q->where('is_active', true))
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
            ['grade' => 'A', 'approved_ratio' => 0.84, 'risk' => 'Low'],
            ['grade' => 'B', 'approved_ratio' => 0.75, 'risk' => 'Medium'],
            ['grade' => 'C', 'approved_ratio' => 0.64, 'risk' => 'Medium'],
            ['grade' => 'D', 'approved_ratio' => 0.55, 'risk' => 'High'],
            ['grade' => 'E', 'approved_ratio' => 0.42, 'risk' => 'Critical'],
        ];

        return $profiles[$districtIndex % count($profiles)];
    }

    private function buildStatusSequence(int $totalRecords, int $approvedTarget): array
    {
        $remaining = max(0, $totalRecords - $approvedTarget);

        $reviewed  = $remaining > 0 ? max(1, (int) floor($remaining * 0.40)) : 0;
        $submitted = $remaining > 0 ? max(1, (int) floor($remaining * 0.35)) : 0;
        $rejected  = $remaining > 0 ? max(1, $remaining - $reviewed - $submitted) : 0;

        $statuses = array_merge(
            array_fill(0, $approvedTarget, 'approved'),
            array_fill(0, $reviewed, 'reviewed'),
            array_fill(0, $submitted, 'submitted'),
            array_fill(0, $rejected, 'rejected')
        );

        shuffle($statuses);

        return array_slice($statuses, 0, $totalRecords);
    }

    private function buildDetailData(
        string $referenceNo,
        string $categorySlug,
        string $categoryName,
        object $district,
        ?object $tehsil,
        string $mainTitle,
        string $mainAddress
    ): array {
        $base = [
            'reference_no'      => $referenceNo,
            'inspection_source' => 'Field Monitoring Record',
            'source_key'        => 'seeded_realistic_inspection',
            'primary_detail'    => $mainTitle,
            'secondary_detail'  => $mainAddress,
            'address'           => $mainAddress,
            'district'          => $district->name,
            'tehsil'            => $tehsil->name ?? 'N/A',
            'union_council'     => 'UC-' . rand(1, 120),
            'inspection_shift'  => collect(['Morning', 'Afternoon', 'Evening'])->random(),
            'kpi_category'      => $categoryName,
        ];

        return match ($categorySlug) {
            'price-of-roti'                                       => $base + [
                'tandoor_name'         => collect(['Al-Madina Tandoor', 'City Roti Point', 'Punjab Tandoor', 'Model Tandoor'])->random(),
                'approved_roti_price'  => 'Rs. 14',
                'observed_roti_price'  => collect(['Rs. 14', 'Rs. 15', 'Rs. 16'])->random(),
                'price_list_displayed' => collect(['Yes', 'No'])->random(),
                'fine_imposed'         => collect(['Yes', 'No'])->random(),
            ],

            'price-of-plain-bakery-bread'                         => $base + [
                'bakery_name'          => collect(['Punjab Bakery', 'City Bakers', 'Al-Noor Bakery', 'Fresh Bread House'])->random(),
                'bread_weight_checked' => collect(['Yes', 'No'])->random(),
                'approved_price'       => 'Rs. 100',
                'observed_price'       => collect(['Rs. 100', 'Rs. 105', 'Rs. 110'])->random(),
                'producer_verified'    => collect(['Yes', 'No'])->random(),
            ],

            'price-control-of-essential-commodities'              => $base + [
                'market_name'         => collect(['Main Bazaar', 'Model Market', 'Sabzi Mandi', 'General Market'])->random(),
                'shops_checked'       => rand(8, 35),
                'price_lists_checked' => rand(5, 30),
                'overcharging_cases'  => rand(0, 8),
                'fine_amount'         => rand(0, 50000),
            ],

            'repair-of-small-roads-in-both-urban-and-rural-areas' => $base + [
                'road_name'          => collect(['Link Road', 'School Road', 'Rural Access Road', 'Main Street Road'])->random(),
                'patches_identified' => rand(1, 12),
                'patches_repaired'   => rand(0, 10),
                'before_photo_taken' => collect(['Yes', 'No'])->random(),
                'after_photo_taken'  => collect(['Yes', 'No'])->random(),
            ],

            'zebra-crossings'                                     => $base + [
                'road_point'         => collect(['School Gate', 'Hospital Road', 'Main Chowk', 'Bus Stand Road'])->random(),
                'crossing_condition' => collect(['Freshly Painted', 'Faded', 'Missing', 'Partially Visible'])->random(),
                'traffic_signage'    => collect(['Available', 'Not Available'])->random(),
                'geo_tag_verified'   => collect(['Yes', 'No'])->random(),
            ],

            'dysfunctional-streetlights'                          => $base + [
                'road_or_street'       => collect(['Main Bazaar Road', 'College Road', 'Street No. 12', 'Railway Road'])->random(),
                'lights_checked'       => rand(5, 40),
                'faulty_lights_found'  => rand(0, 15),
                'lights_repaired'      => rand(0, 15),
                'electrician_deployed' => collect(['Yes', 'No'])->random(),
            ],

            'covering-of-manholes'                                => $base + [
                'street_name'         => collect(['Main Bazaar Road', 'Circular Road', 'College Road', 'Street No. 8'])->random(),
                'manholes_checked'    => rand(3, 30),
                'open_manholes_found' => rand(0, 8),
                'covers_installed'    => rand(0, 8),
                'hazard_marking_done' => collect(['Yes', 'No'])->random(),
            ],

            'functional-and-clean-water-filtration-plants'        => $base + [
                'plant_code'          => 'WFP-' . rand(100, 999),
                'water_source'        => collect(['Tube Well', 'Municipal Line', 'Canal Supply'])->random(),
                'plant_functional'    => collect(['Yes', 'Partially', 'No'])->random(),
                'filter_change_date'  => now()->subDays(rand(1, 45))->toDateString(),
                'chlorination_status' => collect(['Functional', 'Partially Functional', 'Not Functional'])->random(),
            ],

            'inspection-of-educational-institutions'              => $base + [
                'institution_name'   => collect(['Govt High School', 'Govt Girls High School', 'Primary School', 'Elementary School'])->random(),
                'students_present'   => rand(80, 900),
                'teachers_present'   => rand(5, 45),
                'cleanliness_status' => collect(['Good', 'Average', 'Poor'])->random(),
                'missing_facilities' => collect(['None', 'Drinking Water', 'Boundary Wall', 'Furniture', 'Toilets'])->random(),
            ],

            'inspection-of-health-facilities'                     => $base + [
                'facility_name'      => collect(['Basic Health Unit', 'Rural Health Centre', 'THQ Hospital', 'Dispensary'])->random(),
                'doctor_available'   => collect(['Yes', 'No'])->random(),
                'medicine_available' => collect(['Yes', 'Partially', 'No'])->random(),
                'patients_present'   => rand(10, 250),
                'cleanliness_status' => collect(['Good', 'Average', 'Poor'])->random(),
            ],

            'violation-of-marriage-functions-act'                 => $base + [
                'marriage_hall_name' => collect(['Royal Palace Marriage Hall', 'Al-Noor Marriage Hall', 'City Banquet Hall', 'Mehfil Marriage Hall'])->random(),
                'event_type'         => collect(['Wedding', 'Valima', 'Private Function'])->random(),
                'one_dish_checked'   => collect(['Yes', 'No'])->random(),
                'timing_checked'     => collect(['Yes', 'No'])->random(),
                'violation_found'    => collect(['Yes', 'No'])->random(),
            ],

            'anti-encroachment-campaign'                          => $base + [
                'operation_area'        => collect(['Main Bazaar', 'Bus Stand Road', 'Fruit Market', 'Commercial Area'])->random(),
                'encroachments_removed' => rand(0, 35),
                'shops_warned'          => rand(0, 25),
                'items_confiscated'     => rand(0, 20),
                'follow_up_required'    => collect(['Yes', 'No'])->random(),
            ],

            'stray-dogs'                                          => $base + [
                'hotspot_area'       => collect(['School Area', 'Market Street', 'Residential Street', 'Park Side'])->random(),
                'dogs_observed'      => rand(0, 18),
                'citizen_complaints' => rand(0, 10),
                'team_deployed'      => collect(['Yes', 'No'])->random(),
                'risk_category'      => collect(['Low', 'Medium', 'High'])->random(),
            ],

            'removal-of-wall-chalking'                            => $base + [
                'site_name'           => collect(['Boundary Wall', 'Main Chowk Wall', 'School Wall', 'Underpass Wall'])->random(),
                'wall_chalking_found' => collect(['Yes', 'No'])->random(),
                'area_cleaned_sqft'   => rand(50, 800),
                'before_photo_taken'  => collect(['Yes', 'No'])->random(),
                'after_photo_taken'   => collect(['Yes', 'No'])->random(),
            ],

            'graveyards'                                          => $base + [
                'graveyard_name'       => collect(['Main Graveyard', 'Old City Graveyard', 'Model Town Graveyard', 'Village Graveyard'])->random(),
                'cleanliness_status'   => collect(['Good', 'Average', 'Poor'])->random(),
                'boundary_wall_status' => collect(['Complete', 'Damaged', 'Missing'])->random(),
                'lighting_available'   => collect(['Yes', 'No'])->random(),
                'paths_cleared'        => collect(['Yes', 'No'])->random(),
            ],

            'illegal-decanting'                                   => $base + [
                'decanting_point'   => collect(['LPG Shop', 'Roadside Cylinder Point', 'Workshop Area', 'Commercial Shop'])->random(),
                'cylinders_checked' => rand(2, 30),
                'illegal_activity'  => collect(['Yes', 'No'])->random(),
                'shop_sealed'       => collect(['Yes', 'No'])->random(),
                'case_registered'   => collect(['Yes', 'No'])->random(),
            ],

            'suthra-punjab-campaign'                              => $base + [
                'cleanliness_site'     => collect(['Main Market', 'Bus Stand', 'Park', 'Drain Side', 'Residential Street'])->random(),
                'waste_lifted'         => collect(['Yes', 'Partially', 'No'])->random(),
                'sanitary_staff_seen'  => collect(['Yes', 'No'])->random(),
                'bins_available'       => collect(['Yes', 'No'])->random(),
                'public_place_cleaned' => collect(['Yes', 'No'])->random(),
            ],

            'maintenance-of-greenbelts'                           => $base + [
                'greenbelt_location'    => collect(['Main Road Greenbelt', 'Canal Road Median', 'Park Boundary', 'City Entrance Greenbelt'])->random(),
                'grass_cutting_done'    => collect(['Yes', 'No'])->random(),
                'plants_condition'      => collect(['Healthy', 'Needs Watering', 'Damaged'])->random(),
                'watering_done'         => collect(['Yes', 'No'])->random(),
                'beautification_status' => collect(['Good', 'Average', 'Poor'])->random(),
            ],

            'maintenance-of-drains-and-sewerage-lines'            => $base + [
                'drain_location'       => collect(['Main Drain', 'Street Drain', 'Commercial Area Drain', 'Sewerage Choke Point'])->random(),
                'desilting_done'       => collect(['Yes', 'Partially', 'No'])->random(),
                'choke_points_found'   => rand(0, 10),
                'choke_points_cleared' => rand(0, 10),
                'machinery_used'       => collect(['Yes', 'No'])->random(),
            ],

            'bus-terminals'                                       => $base + [
                'terminal_name'         => collect(['General Bus Stand', 'City Bus Terminal', 'Wagon Stand', 'Intercity Terminal'])->random(),
                'cleanliness_status'    => collect(['Good', 'Average', 'Poor'])->random(),
                'fare_list_displayed'   => collect(['Yes', 'No'])->random(),
                'waiting_area_status'   => collect(['Good', 'Average', 'Poor'])->random(),
                'illegal_parking_found' => collect(['Yes', 'No'])->random(),
            ],

            'chief-ministers-complaint-cell'                      => $base + [
                'complaint_tracking_no' => 'CMCC-' . rand(10000, 99999),
                'complaint_type'        => collect(['Service Delivery', 'Cleanliness', 'Price Control', 'Municipal Issue'])->random(),
                'complaint_status'      => collect(['Resolved', 'In Progress', 'Pending Verification'])->random(),
                'citizen_contacted'     => collect(['Yes', 'No'])->random(),
                'satisfaction_checked'  => collect(['Yes', 'No'])->random(),
            ],

            'regulation-of-shops-and-handcarts'                   => $base + [
                'market_area'       => collect(['Main Bazaar', 'Fruit Market', 'Vegetable Market', 'Bus Stand Market'])->random(),
                'shops_checked'     => rand(5, 40),
                'handcarts_checked' => rand(3, 30),
                'violations_found'  => rand(0, 12),
                'warnings_issued'   => rand(0, 10),
            ],

            'e-biz'                                               => $base + [
                'application_no'        => 'EBIZ-' . rand(10000, 99999),
                'service_type'          => collect(['NOC', 'Registration', 'Licence', 'Approval'])->random(),
                'applications_reviewed' => rand(3, 30),
                'pending_cases'         => rand(0, 12),
                'timeline_compliance'   => collect(['Yes', 'Partially', 'No'])->random(),
            ],

            default                                               => $base + [
                'site_code'          => 'SITE-' . rand(1000, 9999),
                'record_verified'    => collect(['Yes', 'No'])->random(),
                'public_interaction' => collect(['Yes', 'No'])->random(),
                'service_level'      => collect(['Excellent', 'Good', 'Average', 'Poor'])->random(),
            ],
        };
    }

    private function buildObservations(string $categorySlug, string $categoryName, string $status, array $profile): array
    {
        $common = [
            'overall_condition'      => $this->conditionByStatus($status),
            'records_verified'       => $status === 'rejected' ? 'No' : 'Yes',
            'staff_available'        => $status === 'submitted' ? 'Partially' : 'Yes',
            'public_feedback'        => collect(['Satisfactory', 'Average', 'Unsatisfactory'])->random(),
            'risk_level'             => $profile['risk'],
            'performance_grade_hint' => $profile['grade'],
        ];

        return match ($categorySlug) {
            'price-of-roti',
            'price-of-plain-bakery-bread',
            'price-control-of-essential-commodities'       => $common + [
                'price_list_displayed'  => collect(['Yes', 'No'])->random(),
                'overcharging_observed' => $status === 'approved' ? 'No' : collect(['Yes', 'No'])->random(),
                'fine_deposit_status'   => collect(['Deposited', 'Pending', 'Not Applicable'])->random(),
            ],

            'functional-and-clean-water-filtration-plants' => $common + [
                'plant_functional'       => $status === 'approved' ? 'Yes' : collect(['Yes', 'Partially', 'No'])->random(),
                'filter_media_condition' => collect(['Clean', 'Needs Replacement', 'Damaged'])->random(),
                'water_quality_visible'  => collect(['Clear', 'Slightly Turbid', 'Turbid'])->random(),
                'lab_test_required'      => collect(['Yes', 'No'])->random(),
            ],

            'violation-of-marriage-functions-act'          => $common + [
                'one_dish_compliance' => $status === 'approved' ? 'Compliant' : collect(['Compliant', 'Violation Observed'])->random(),
                'timing_compliance'   => collect(['Compliant', 'Minor Delay', 'Violation'])->random(),
                'notice_required'     => collect(['Yes', 'No'])->random(),
            ],

            'stray-dogs'                                   => $common + [
                'hotspot_verified'      => 'Yes',
                'number_of_dogs_seen'   => rand(0, 20),
                'citizen_risk_observed' => collect(['Low', 'Medium', 'High'])->random(),
                'team_response_status'  => collect(['Responded', 'Partially Responded', 'Pending'])->random(),
            ],

            'covering-of-manholes'                         => $common + [
                'cover_available'  => $status === 'approved' ? 'Yes' : collect(['Yes', 'No', 'Damaged'])->random(),
                'hazard_level'     => collect(['Low', 'Medium', 'High'])->random(),
                'barricading_done' => collect(['Yes', 'No'])->random(),
                'repair_status'    => collect(['Completed', 'In Progress', 'Pending'])->random(),
            ],

            'inspection-of-educational-institutions',
            'inspection-of-health-facilities'              => $common + [
                'facility_open'       => collect(['Yes', 'No'])->random(),
                'attendance_verified' => collect(['Yes', 'No'])->random(),
                'cleanliness_status'  => collect(['Good', 'Average', 'Poor'])->random(),
                'corrective_action'   => collect(['Required', 'Not Required'])->random(),
            ],

            default                                        => $common + [
                'service_available'       => collect(['Yes', 'Partially', 'No'])->random(),
                'compliance_status'       => collect(['Compliant', 'Partially Compliant', 'Non-Compliant'])->random(),
                'issue_found'             => collect(['No Major Issue', 'Minor Issue', 'Major Issue'])->random(),
                'photo_evidence_verified' => collect(['Yes', 'No'])->random(),
            ],
        };
    }

    private function buildActions(string $categorySlug, string $categoryName, string $status, array $profile): array
    {
        $needsFollowUp = in_array($status, ['submitted', 'reviewed', 'rejected'], true);

        $common = [
            'action_required'        => $needsFollowUp ? 'Yes' : 'No',
            'priority'               => $profile['risk'],
            'responsible_officer'    => collect(['Assistant Commissioner', 'Deputy Commissioner', 'Municipal Officer', 'Field Inspector'])->random(),
            'follow_up_required'     => $needsFollowUp ? 'Yes' : 'No',
            'target_completion_date' => now()->addDays(rand(3, 15))->toDateString(),
        ];

        return match ($categorySlug) {
            'price-of-roti',
            'price-of-plain-bakery-bread',
            'price-control-of-essential-commodities'       => $common + [
                'recommended_action' => collect(['Issue warning', 'Impose fine', 'Verify PSID deposit', 'Re-inspection required'])->random(),
                'fine_amount'        => $status === 'rejected' ? rand(10000, 50000) : rand(0, 10000),
                'notice_issued'      => $needsFollowUp ? 'Yes' : 'No',
            ],

            'functional-and-clean-water-filtration-plants' => $common + [
                'recommended_action' => collect(['Replace filter media', 'Repair chlorination unit', 'Clean water tank', 'Collect lab sample'])->random(),
                'technical_team'     => 'Public Health Engineering Team',
                'estimated_cost'     => rand(10000, 85000),
            ],

            'violation-of-marriage-functions-act'          => $common + [
                'recommended_action' => collect(['Issue warning', 'Impose fine', 'Verify licence', 'Re-inspection required'])->random(),
                'fine_amount'        => $status === 'rejected' ? rand(10000, 50000) : rand(0, 10000),
                'notice_issued'      => $needsFollowUp ? 'Yes' : 'No',
            ],

            'stray-dogs'                                   => $common + [
                'recommended_action' => collect(['Deploy response team', 'Mark hotspot', 'Coordinate with municipal staff', 'Follow-up visit'])->random(),
                'team_required'      => 'Municipal Stray Dog Response Team',
                'public_warning'     => collect(['Yes', 'No'])->random(),
            ],

            'covering-of-manholes'                         => $common + [
                'recommended_action' => collect(['Install new cover', 'Repair damaged cover', 'Barricade hazard point', 'Escalate to municipal team'])->random(),
                'material_required'  => collect(['RCC Cover', 'Steel Cover', 'Warning Board', 'Barricade'])->random(),
                'safety_risk'        => collect(['Low', 'Medium', 'High'])->random(),
            ],

            'dysfunctional-streetlights'                   => $common + [
                'recommended_action' => collect(['Repair faulty light', 'Replace bulb', 'Fix wiring issue', 'Verify night-time functionality'])->random(),
                'technical_team'     => 'Municipal Electrical Team',
                'verification_visit' => collect(['Required', 'Not Required'])->random(),
            ],

            'suthra-punjab-campaign',
            'maintenance-of-greenbelts',
            'maintenance-of-drains-and-sewerage-lines'     => $common + [
                'recommended_action'  => collect(['Deploy sanitary staff', 'Complete cleaning activity', 'Remove waste', 'Verify site again'])->random(),
                'municipal_team'      => 'Municipal Committee Field Team',
                'before_after_photos' => collect(['Required', 'Available'])->random(),
            ],

            default                                        => $common + [
                'recommended_action'  => collect(['Re-inspection', 'Issue direction', 'Resolve minor issue', 'Escalate for compliance'])->random(),
                'compliance_deadline' => now()->addDays(rand(5, 20))->toDateString(),
                'remarks'             => 'Actions recorded for follow-up and compliance tracking.',
            ],
        };
    }

    private function buildMainTitle(string $categorySlug, string $categoryName, object $district, ?object $tehsil): string
    {
        $t  = $tehsil->name ?? ($district->name ?? 'Tehsil');
        $uc = 'UC-' . rand(1, 40);

        return match ($categorySlug) {
            'price-of-roti'                                       => collect([
                'Al-Madina Tandoor Price Inspection',
                'Local Tandoor Roti Rate Verification',
                'City Tandoor Price Control Check',
                'Model Tandoor Rate Inspection',
            ])->random(),

            'price-of-plain-bakery-bread'                         => collect([
                'Plain Bread Price Verification',
                'Bakery Bread Weight and Rate Inspection',
                'Bread Producer Price Check',
                'Local Bakery Rate Inspection',
            ])->random(),

            'price-control-of-essential-commodities'              => collect([
                'Essential Commodities Price Inspection',
                'Market Price List Verification',
                'General Store Price Control Check',
                'Sabzi Mandi Rate Inspection',
            ])->random(),

            'repair-of-small-roads-in-both-urban-and-rural-areas' => collect([
                'Small Road Patch Repair Verification',
                'Urban Link Road Repair Inspection',
                'Rural Road Maintenance Check',
                'Damaged Road Patch Inspection',
            ])->random(),

            'zebra-crossings'                                     => collect([
                'Zebra Crossing Restoration Check',
                'School Zone Zebra Crossing Inspection',
                'Main Chowk Road Marking Verification',
                'Pedestrian Crossing Paint Inspection',
            ])->random(),

            'dysfunctional-streetlights'                          => collect([
                'Streetlight Repair Verification',
                'Dysfunctional Streetlight Inspection',
                'Night Lighting Functionality Check',
                'Municipal Streetlight Complaint Verification',
            ])->random(),

            'covering-of-manholes'                                => collect([
                'Open Manhole Covering Verification',
                'Damaged Manhole Cover Inspection',
                'Manhole Safety Hazard Check',
                'Municipal Manhole Cover Inspection',
            ])->random(),

            'functional-and-clean-water-filtration-plants'        => "Govt Water Filtration Plant, {$uc}",

            'inspection-of-educational-institutions'   => collect([
                'Govt School Inspection',
                'Educational Institution Monitoring Visit',
                'School Cleanliness and Attendance Check',
                'Public School Facility Inspection',
            ])->random(),

            'inspection-of-health-facilities'          => collect([
                'Basic Health Unit Inspection',
                'Health Facility Monitoring Visit',
                'THQ/RHC Service Delivery Check',
                'Public Health Facility Inspection',
            ])->random(),

            'violation-of-marriage-functions-act'      => collect([
                'Marriage Hall Act Compliance Check',
                'Wedding Function Timing Inspection',
                'One Dish Compliance Verification',
                'Marriage Function Violation Inspection',
            ])->random(),

            'anti-encroachment-campaign'               => collect([
                'Anti-Encroachment Operation Verification',
                'Market Encroachment Removal Check',
                'Commercial Area Encroachment Inspection',
                'Temporary Encroachment Clearance Visit',
            ])->random(),

            'stray-dogs'                               => 'Stray Dogs Hotspot Verification - Ward ' . str_pad((string) rand(1, 25), 2, '0', STR_PAD_LEFT),

            'removal-of-wall-chalking'                 => collect([
                'Wall Chalking Removal Verification',
                'Public Wall Cleanliness Inspection',
                'Political Wall Chalking Removal Check',
                'Main Road Wall Chalking Inspection',
            ])->random(),

            'graveyards'                               => collect([
                'Graveyard Cleanliness Inspection',
                'Graveyard Boundary and Upkeep Check',
                'Public Graveyard Maintenance Visit',
                'Graveyard Pathway Clearance Inspection',
            ])->random(),

            'illegal-decanting'                        => collect([
                'Illegal LPG Decanting Inspection',
                'Cylinder Decanting Point Verification',
                'LPG Safety Violation Check',
                'Illegal Decanting Complaint Inspection',
            ])->random(),

            'suthra-punjab-campaign'                   => collect([
                'Suthra Punjab Cleanliness Activity Check',
                'Waste Lifting Verification Visit',
                'Public Place Cleanliness Inspection',
                'Sanitation Campaign Monitoring Visit',
            ])->random(),

            'maintenance-of-greenbelts'                => collect([
                'Greenbelt Maintenance Inspection',
                'Plantation Upkeep Verification',
                'Roadside Greenbelt Monitoring Visit',
                'Beautification and Greenbelt Check',
            ])->random(),

            'maintenance-of-drains-and-sewerage-lines' => collect([
                'Drain Desilting Verification',
                'Sewerage Line Maintenance Inspection',
                'Critical Choke Point Clearance Check',
                'Municipal Drain Cleaning Visit',
            ])->random(),

            'bus-terminals'                            => collect([
                'Bus Terminal Cleanliness Inspection',
                'General Bus Stand Monitoring Visit',
                'Passenger Facility Check',
                'Bus Terminal Compliance Inspection',
            ])->random(),

            'chief-ministers-complaint-cell'           => collect([
                'CM Complaint Resolution Verification',
                'Citizen Complaint Follow-up Visit',
                'Complaint Cell Field Verification',
                'CMCC Complaint Satisfaction Check',
            ])->random(),

            'regulation-of-shops-and-handcarts'        => collect([
                'Shops and Handcarts Regulation Check',
                'Market Handcart Management Inspection',
                'Commercial Area Regulation Visit',
                'Shopfront Compliance Inspection',
            ])->random(),

            'e-biz'                                    => collect([
                'E-Biz Application Pendency Review',
                'Business Facilitation Case Verification',
                'E-Biz Timeline Compliance Check',
                'Online Application Processing Review',
            ])->random(),

            default                                    => $categoryName . ' Field Inspection - ' . $t,
        };
    }

    private function buildMainAddress(string $categorySlug, string $categoryName, object $district, ?object $tehsil): string
    {
        $d = $district->name ?? 'District';
        $t = $tehsil->name ?? $d;

        $areas = [
            'Main Market',
            'Model Town',
            'Civil Lines',
            'B-Block',
            'Railway Colony',
            'Bus Stand Area',
            'Near THQ Hospital',
            'Old City Area',
            'Commercial Market',
        ];

        $landmarks = [
            'Union Council Office',
            'Civil Hospital',
            'Govt High School',
            'Municipal Office',
            'Police Station',
            'Post Office',
            'Main Chowk',
        ];

        return match ($categorySlug) {
            'functional-and-clean-water-filtration-plants' =>
            'Near ' . collect($landmarks)->random() . ', ' . collect($areas)->random() . ", {$t}, {$d}",

            'violation-of-marriage-functions-act' =>
            'Main Road, ' . collect($areas)->random() . ", {$t}, {$d}",

            'covering-of-manholes',
            'dysfunctional-streetlights',
            'zebra-crossings',
            'repair-of-small-roads-in-both-urban-and-rural-areas' =>
            collect(['Main Bazaar Road', 'Circular Road', 'Railway Road', 'College Road', 'Hospital Road'])->random()
            . ' near ' . collect($landmarks)->random() . ", {$t}, {$d}",

            'stray-dogs' =>
            'Ward area near ' . collect(['Government Primary School', 'Park', 'Bus Stop', 'Marketplace'])->random() . ", {$t}, {$d}",

            'price-of-roti',
            'price-of-plain-bakery-bread',
            'price-control-of-essential-commodities',
            'regulation-of-shops-and-handcarts' =>
            collect($areas)->random() . ", {$t}, {$d}",

            'inspection-of-educational-institutions' =>
            collect(['Govt High School', 'Govt Primary School', 'Govt Girls School', 'Elementary School'])->random()
            . ', ' . collect($areas)->random() . ", {$t}, {$d}",

            'inspection-of-health-facilities' =>
            collect(['Basic Health Unit', 'Rural Health Centre', 'THQ Hospital', 'Govt Dispensary'])->random()
            . ', ' . collect($areas)->random() . ", {$t}, {$d}",

            default =>
            collect($areas)->random() . ', near ' . collect($landmarks)->random() . ", {$t}, {$d}",
        };
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
            'lahore'          => [31.5204, 74.3587],
            'faisalabad'      => [31.4504, 73.1350],
            'rawalpindi'      => [33.5651, 73.0169],
            'multan'          => [30.1575, 71.5249],
            'gujranwala'      => [32.1877, 74.1945],
            'sialkot'         => [32.4945, 74.5229],
            'bahawalpur'      => [29.3956, 71.6836],
            'sargodha'        => [32.0836, 72.6711],
            'dg khan'         => [30.0325, 70.6402],
            'dera ghazi khan' => [30.0325, 70.6402],
            'layyah'          => [30.9648, 70.9399],
            'bhakkar'         => [31.6253, 71.0657],
            'rajanpur'        => [29.1041, 70.3297],
            'bahawalnagar'    => [29.9983, 73.2527],
            'jhelum'          => [32.9331, 73.7264],
            'khushab'         => [32.2967, 72.3525],
            'lodhran'         => [29.5405, 71.6336],
            'nankana sahib'   => [31.4501, 73.7065],
        ];

        $name = Str::lower($district->name ?? '');
        $base = $known[$name] ?? [
            29.5 + (($index % 14) * 0.28),
            70.0 + (($index % 18) * 0.22),
        ];

        return [
            round($base[0] + (rand(-80, 80) / 10000), 6),
            round($base[1] + (rand(-80, 80) / 10000), 6),
        ];
    }
}
