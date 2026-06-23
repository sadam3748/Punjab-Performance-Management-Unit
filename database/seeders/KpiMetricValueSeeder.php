<?php

namespace Database\Seeders;

use App\Models\District;
use App\Models\Division;
use App\Models\KpiCategory;
use App\Models\KpiMetricValue;
use App\Services\KpiPeriodService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KpiMetricValueSeeder extends Seeder
{
    public function run(): void
    {
        // Keep seeding fast: truncate and insert controlled rows.
        DB::table('kpi_metric_values')->truncate();

        $categories = KpiCategory::where('is_active', true)->orderBy('id')->get(['id', 'name', 'slug']);
        $divisions = Division::where('is_active', true)->orderBy('id')->get(['id', 'name']);
        $districts = District::where('is_active', true)->orderBy('id')->get(['id', 'name', 'division_id']);

        $period = app(KpiPeriodService::class);

        $latestWeek = $period->weekRangeForDate(now()->subWeek());
        $latestWeekNo = (string) $latestWeek['week_no'];
        $latestRange = $period->getWeekDateRange($latestWeekNo);
        $weekStart = $latestRange['start'] ?? now()->copy()->subWeek()->startOfDay();

        // Seed 6 completed weekly snapshots (Thu->Wed) for province + district charts.
        $weeklyStarts = [];
        for ($i = 0; $i < 6; $i++) {
            $weeklyStarts[] = Carbon::parse($period->weekRangeForDate($weekStart->copy()->subWeeks($i))['week_start']);
        }

        $rows = [];

        foreach ($weeklyStarts as $weekCursor) {
            $week = $period->weekRangeForDate($weekCursor);
            $dateFrom = Carbon::parse($week['week_start'])->startOfDay();
            $dateTo = Carbon::parse($week['week_end'])->endOfDay();
            $year = (int) $dateFrom->format('Y');
            $month = (int) $dateFrom->format('n');
            $quarter = (int) ceil($month / 3);
            $weekNo = $week['week_no'];

            foreach ($categories as $category) {
                $defs = $this->metricDefinitionsForGraphicalReport($category->slug, $category->name);

                // Province-level cards (all metric keys for the category)
                $sort = 1;
                $baseScale = mt_rand(92, 110) / 100;
                $provinceValues = [];
                foreach ($defs as $def) {
                    $val = $this->valueForProvinceMetric($category->slug, $def['key'], $baseScale);
                    $provinceValues[$def['key']] = $val;

                    $rows[] = [
                        'kpi_category_id' => $category->id,
                        'metric_key'      => $def['key'],
                        'metric_title'    => $def['title'],
                        'metric_value'    => $val,
                        'metric_score'    => null,
                        'metric_unit'     => $def['unit'] ?? null,
                        'area_level'      => 'province',
                        'division_id'     => null,
                        'district_id'     => null,
                        'tehsil_id'       => null,
                        'period_type'     => 'weekly',
                        'year'            => $year,
                        'week_no'         => $weekNo,
                        'month'           => $month,
                        'quarter'         => $quarter,
                        'date_from'       => $dateFrom->toDateString(),
                        'date_to'         => $dateTo->toDateString(),
                        'sort_order'      => $sort++,
                        'is_active'       => true,
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ];
                }

                // District-level chart values:
                // - For all categories: seed one "primary" metric (keeps total rows reasonable).
                // - For Water Filtration: also seed functional/non_functional so special charts work.
                $districtMetricKeys = [$defs[0]['key']]; // first metric is primary
                if ($this->isWaterCategory($category->slug, $category->name)) {
                    $extra = ['functional', 'non_functional', 'ro_filter_changed', 'ro_filter_unchanged'];
                    foreach ($extra as $k) {
                        if (in_array($k, array_column($defs, 'key'), true)) {
                            $districtMetricKeys[] = $k;
                        }
                    }
                }
                $districtMetricKeys = array_values(array_unique($districtMetricKeys));

                foreach ($districts as $district) {
                    foreach ($districtMetricKeys as $metricKey) {
                        $pBase = (float) ($provinceValues[$metricKey] ?? 0);
                        $val = $this->valueForDistrictMetric($category->slug, $metricKey, $pBase, (int) $district->id);
                        $score = $this->scoreFromValue($pBase, $val);

                        $rows[] = [
                            'kpi_category_id' => $category->id,
                            'metric_key'      => $metricKey,
                            'metric_title'    => $this->titleForKey($defs, $metricKey),
                            'metric_value'    => $val,
                            'metric_score'    => $score,
                            'metric_unit'     => $this->unitForKey($defs, $metricKey),
                            'area_level'      => 'district',
                            'division_id'     => $district->division_id,
                            'district_id'     => $district->id,
                            'tehsil_id'       => null,
                            'period_type'     => 'weekly',
                            'year'            => $year,
                            'week_no'         => $weekNo,
                            'month'           => $month,
                            'quarter'         => $quarter,
                            'date_from'       => $dateFrom->toDateString(),
                            'date_to'         => $dateTo->toDateString(),
                            'sort_order'      => 1,
                            'is_active'       => true,
                            'created_at'      => now(),
                            'updated_at'      => now(),
                        ];
                    }
                }
            }
        }

        // Division-level summary for latest week only (keeps volume small)
        if (! empty($weeklyStarts)) {
            $week = $period->weekRangeForDate($weeklyStarts[0]);
            $dateFrom = Carbon::parse($week['week_start'])->startOfDay();
            $dateTo = Carbon::parse($week['week_end'])->endOfDay();
            $year = (int) $dateFrom->format('Y');
            $month = (int) $dateFrom->format('n');
            $quarter = (int) ceil($month / 3);
            $weekNo = $week['week_no'];

            foreach ($categories as $category) {
                $defs = $this->metricDefinitionsForCategory($category->slug, $category->name);
                $primaryKey = $defs[0]['key'];

                foreach ($divisions as $division) {
                    $districtIds = $districts->where('division_id', $division->id)->pluck('id')->all();
                    if (! count($districtIds)) {
                        continue;
                    }

                    $divisionTotal = KpiMetricValue::query()
                        ->where('area_level', 'district')
                        ->where('kpi_category_id', $category->id)
                        ->where('period_type', 'weekly')
                        ->where('week_no', $weekNo)
                        ->where('metric_key', $primaryKey)
                        ->whereIn('district_id', $districtIds)
                        ->sum('metric_value');

                    $rows[] = [
                        'kpi_category_id' => $category->id,
                        'metric_key'      => $primaryKey,
                        'metric_title'    => $defs[0]['title'],
                        'metric_value'    => round((float) $divisionTotal, 2),
                        'metric_score'    => null,
                        'metric_unit'     => $defs[0]['unit'] ?? null,
                        'area_level'      => 'division',
                        'division_id'     => $division->id,
                        'district_id'     => null,
                        'tehsil_id'       => null,
                        'period_type'     => 'weekly',
                        'year'            => $year,
                        'week_no'         => $weekNo,
                        'month'           => $month,
                        'quarter'         => $quarter,
                        'date_from'       => $dateFrom->toDateString(),
                        'date_to'         => $dateTo->toDateString(),
                        'sort_order'      => 1,
                        'is_active'       => true,
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ];
                }
            }
        }

        // Old PPMF KPI reports use period presets (current_week/last_week/last_four_weeks).
        // Seed a compact, report-friendly dataset for those presets so provincial + district-wise KPI score pages
        // can show metric cards + dynamic district columns like old PPMF.
        $legacyPeriods = [
            'last_week' => $period->weekRangeForDate($weekStart->copy()),
            'current_week' => $period->weekRangeForDate($weekStart->copy()->addWeek()),
            'last_four_weeks' => [
                'week_start' => $period->weekRangeForDate($weekStart->copy()->subWeeks(3))['week_start'],
                'week_end' => $period->weekRangeForDate($weekStart)['week_end'],
            ],
        ];

        foreach ($legacyPeriods as $periodType => $range) {
            if ($periodType === 'last_four_weeks') {
                $dateFrom = Carbon::parse($range['week_start'])->startOfDay();
                $dateTo = Carbon::parse($range['week_end'])->endOfDay();
                $weekNo = $period->weekRangeForDate($dateFrom)['week_no'];
            } else {
                $dateFrom = Carbon::parse($range['week_start'])->startOfDay();
                $dateTo = Carbon::parse($range['week_end'])->endOfDay();
                $weekNo = $range['week_no'];
            }

            $year = (int) $dateFrom->format('Y');
            $month = (int) $dateFrom->format('n');
            $quarter = (int) ceil($month / 3);

            foreach ($categories as $category) {
                $defs = $this->metricDefinitionsForLegacyReport($category->slug, $category->name);

                $sort = 1;
                $baseScale = mt_rand(92, 110) / 100;
                $provinceValues = [];
                foreach ($defs as $def) {
                    $val = $this->valueForProvinceMetric($category->slug, $def['key'], $baseScale);
                    $provinceValues[$def['key']] = $val;

                    $rows[] = [
                        'kpi_category_id' => $category->id,
                        'metric_key'      => $def['key'],
                        'metric_title'    => $def['title'],
                        'metric_value'    => $val,
                        'metric_score'    => null,
                        'metric_unit'     => $def['unit'] ?? null,
                        'area_level'      => 'province',
                        'division_id'     => null,
                        'district_id'     => null,
                        'tehsil_id'       => null,
                        'period_type'     => $periodType,
                        'year'            => $year,
                        'week_no'         => $weekNo,
                        'month'           => $month,
                        'quarter'         => $quarter,
                        'date_from'       => $dateFrom->toDateString(),
                        'date_to'         => $dateTo->toDateString(),
                        'sort_order'      => $sort++,
                        'is_active'       => true,
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ];
                }

                // District rows: seed ALL metric keys so district-wise KPI score table can render dynamic columns.
                $districtMetricKeys = array_column($defs, 'key');
                foreach ($districts as $district) {
                    foreach ($districtMetricKeys as $metricKey) {
                        $pBase = (float) ($provinceValues[$metricKey] ?? 0);
                        $val = $this->valueForDistrictMetric($category->slug, $metricKey, $pBase, (int) $district->id);
                        $score = $this->scoreFromValue($pBase, $val);

                        $rows[] = [
                            'kpi_category_id' => $category->id,
                            'metric_key'      => $metricKey,
                            'metric_title'    => $this->titleForKey($defs, $metricKey),
                            'metric_value'    => $val,
                            'metric_score'    => $score,
                            'metric_unit'     => $this->unitForKey($defs, $metricKey),
                            'area_level'      => 'district',
                            'division_id'     => $district->division_id,
                            'district_id'     => $district->id,
                            'tehsil_id'       => null,
                            'period_type'     => $periodType,
                            'year'            => $year,
                            'week_no'         => $weekNo,
                            'month'           => $month,
                            'quarter'         => $quarter,
                            'date_from'       => $dateFrom->toDateString(),
                            'date_to'         => $dateTo->toDateString(),
                            'sort_order'      => 1,
                            'is_active'       => true,
                            'created_at'      => now(),
                            'updated_at'      => now(),
                        ];
                    }
                }
            }
        }

        foreach (array_chunk($rows, 1500) as $chunk) {
            DB::table('kpi_metric_values')->insert($chunk);
        }
    }

    private function isWaterCategory(?string $slug, string $name): bool
    {
        $slug = (string) $slug;
        return str_contains($slug, 'water') || str_contains(strtolower($name), 'water filtration');
    }

    private function metricDefinitionsForCategory(?string $slug, string $name): array
    {
        // Deprecated: kept for backward compatibility in older commits.
        // Use metricDefinitionsForGraphicalReport() or metricDefinitionsForLegacyReport().
        return $this->metricDefinitionsForLegacyReport($slug, $name);
    }

    private function metricDefinitionsForLegacyReport(?string $slug, string $name): array
    {
        $slug = (string) $slug;

        return match ($slug) {
            'price-of-roti' => [
                ['key' => 'dc_weekly_review', 'title' => 'DCs twice Weekly Review with all PCMs, Food Department and special branch about enforcement of Rate of Roti.', 'unit' => 'count'],
                ['key' => 'tandoor_inspections', 'title' => 'Inspections of Tandoors to be conducted by ACs/PCMs daily as per tier wise targets.', 'unit' => 'inspections'],
                ['key' => 'coverage_mobility_index', 'title' => 'Special Coverage and Mobility Index for ACs/PCMs.', 'unit' => 'index'],
                ['key' => 'fine_imposed', 'title' => 'Imposition of Fine on violations (Over Price, Weight, Non availability of Roti) at least 15% of visits.', 'unit' => 'actions'],
                ['key' => 'citizen_complaint_action', 'title' => 'Action taken on the complaints by the citizen.', 'unit' => 'actions'],
            ],

            'price-of-plain-bakery-bread' => [
                ['key' => 'bread_inspections', 'title' => 'Inspections of Brands/local producers to be conducted by ACs/PCMs daily as per tier wise targets.', 'unit' => 'inspections'],
                ['key' => 'coverage_mobility_index', 'title' => 'Special Coverage and Mobility Index.', 'unit' => 'index'],
                ['key' => 'fine_imposed', 'title' => 'Imposition of Fine on violations (Over Price, Non availability of Plain Bread) at least 15% of visits.', 'unit' => 'actions'],
                ['key' => 'citizen_complaint_action', 'title' => 'Action taken on the complaints by the citizen.', 'unit' => 'actions'],
            ],

            'price-control-of-essential-commodities' => [
                ['key' => 'market_inspections', 'title' => 'Inspections of Markets/shops to ensure price list display and enforcement.', 'unit' => 'inspections'],
                ['key' => 'price_list_display', 'title' => 'Price List Displayed at sale points as per notified rates.', 'unit' => 'count'],
                ['key' => 'overcharging_actions', 'title' => 'Action taken on overcharging / profiteering cases.', 'unit' => 'actions'],
                ['key' => 'fine_imposed', 'title' => 'Imposition of Fine on violations at least 15% of visits.', 'unit' => 'actions'],
                ['key' => 'complaints_action', 'title' => 'Action taken on citizen complaints related to price control.', 'unit' => 'actions'],
            ],

            'repair-of-small-roads-in-both-urban-and-rural-areas' => [
                ['key' => 'road_sites_identified', 'title' => 'Identification of damaged road sites for repair (urban & rural).', 'unit' => 'count'],
                ['key' => 'repair_completed', 'title' => 'Repair/patch work completed as per approved plan.', 'unit' => 'count'],
                ['key' => 'quality_checks', 'title' => 'Quality/verification checks conducted by administration.', 'unit' => 'checks'],
                ['key' => 'complaints_resolved', 'title' => 'Public complaints resolved regarding road repair.', 'unit' => 'actions'],
            ],

            'zebra-crossings' => [
                ['key' => 'locations_identified', 'title' => 'Zebra crossing locations identified near schools/hospitals/markets.', 'unit' => 'count'],
                ['key' => 'markings_done', 'title' => 'Zebra crossings marked/painted as per plan.', 'unit' => 'count'],
                ['key' => 'visibility_maintenance', 'title' => 'Maintenance of visibility (repainting/cleaning) carried out.', 'unit' => 'count'],
            ],

            'dysfunctional-streetlights' => [
                ['key' => 'faults_reported', 'title' => 'Dysfunctional streetlights reported/identified.', 'unit' => 'count'],
                ['key' => 'repairs_completed', 'title' => 'Streetlights repaired/restored to functional status.', 'unit' => 'count'],
                ['key' => 'followups', 'title' => 'Follow-up inspections conducted for repaired streetlights.', 'unit' => 'inspections'],
            ],

            'covering-of-manholes' => [
                ['key' => 'manholes_identified', 'title' => 'Open/damaged manholes identified.', 'unit' => 'count'],
                ['key' => 'covers_installed', 'title' => 'Manhole covers installed/replaced.', 'unit' => 'count'],
                ['key' => 'hazards_resolved', 'title' => 'Hazardous manholes resolved/secured.', 'unit' => 'count'],
            ],

            'functional-and-clean-water-filtration-plants' => [
                ['key' => 'filter_change_logged', 'title' => 'Regular change of filter and affixing the dates thereof of all functional filtration plants.', 'unit' => 'count'],
                ['key' => 'plants_functional_verified', 'title' => 'Inspection/verification that water filtration plants are functional.', 'unit' => 'inspections'],
            ],

            'inspection-of-educational-institutions' => [
                ['key' => 'institution_visits', 'title' => 'Inspections of educational institutions conducted as per targets.', 'unit' => 'inspections'],
                ['key' => 'cleanliness_compliance', 'title' => 'Cleanliness & basic facilities compliance verified.', 'unit' => 'count'],
                ['key' => 'issues_resolved', 'title' => 'Issues identified and resolved through follow-up actions.', 'unit' => 'actions'],
            ],

            'inspection-of-health-facilities' => [
                ['key' => 'facility_visits', 'title' => 'Inspections of health facilities conducted as per targets.', 'unit' => 'inspections'],
                ['key' => 'medicine_availability', 'title' => 'Availability of essential medicines verified.', 'unit' => 'count'],
                ['key' => 'issues_resolved', 'title' => 'Issues identified and resolved through follow-up actions.', 'unit' => 'actions'],
            ],

            'violation-of-marriage-functions-act' => [
                ['key' => 'marriage_hall_inspections', 'title' => 'Inspections of marriage halls/functions conducted as per targets.', 'unit' => 'inspections'],
                ['key' => 'violations_detected', 'title' => 'Violations detected as per Marriage Functions Act.', 'unit' => 'count'],
                ['key' => 'notices_fines', 'title' => 'Notices/fines imposed on violations.', 'unit' => 'actions'],
            ],

            'anti-encroachment-campaign' => [
                ['key' => 'encroachments_identified', 'title' => 'Encroachments identified during campaigns.', 'unit' => 'count'],
                ['key' => 'encroachments_removed', 'title' => 'Encroachments removed and roads/footpaths cleared.', 'unit' => 'count'],
                ['key' => 'operations_conducted', 'title' => 'Anti-encroachment operations conducted (drives).', 'unit' => 'operations'],
            ],

            'stray-dogs' => [
                ['key' => 'areas_visited', 'title' => 'Areas visited/inspected for stray dog situation.', 'unit' => 'inspections'],
                ['key' => 'complaints_verified', 'title' => 'Citizen complaints verified and logged.', 'unit' => 'count'],
                ['key' => 'actions_taken', 'title' => 'Actions taken by local authorities/veterinary teams.', 'unit' => 'actions'],
            ],

            'removal-of-wall-chalking' => [
                ['key' => 'sites_identified', 'title' => 'Wall chalking sites identified.', 'unit' => 'count'],
                ['key' => 'removal_done', 'title' => 'Removal/cleaning performed (as per plan).', 'unit' => 'count'],
                ['key' => 'repeat_prevented', 'title' => 'Preventive measures / repeat monitoring carried out.', 'unit' => 'count'],
            ],

            'graveyards' => [
                ['key' => 'graveyard_inspections', 'title' => 'Graveyard inspections/visits conducted.', 'unit' => 'inspections'],
                ['key' => 'cleanliness_actions', 'title' => 'Cleanliness/maintenance actions completed.', 'unit' => 'actions'],
                ['key' => 'issues_resolved', 'title' => 'Issues resolved through coordination with departments.', 'unit' => 'actions'],
            ],

            'illegal-decanting' => [
                ['key' => 'lpg_points_inspected', 'title' => 'Inspections of LPG sale points / decanting hotspots.', 'unit' => 'inspections'],
                ['key' => 'violations_detected', 'title' => 'Illegal decanting violations detected.', 'unit' => 'count'],
                ['key' => 'actions_taken', 'title' => 'Sealing/fines/action taken on violations.', 'unit' => 'actions'],
            ],

            'suthra-punjab-campaign' => [
                ['key' => 'cleanliness_drives', 'title' => 'Cleanliness drives conducted under Suthra Punjab Campaign.', 'unit' => 'drives'],
                ['key' => 'waste_removed', 'title' => 'Waste removed / sites cleaned (reported).', 'unit' => 'count'],
                ['key' => 'monitoring_visits', 'title' => 'Monitoring visits for sustainability & compliance.', 'unit' => 'inspections'],
            ],

            'maintenance-of-greenbelts-dcs-initiatives-on-beautification' => [
                ['key' => 'greenbelt_maintenance', 'title' => 'Maintenance of greenbelts and beautification initiatives completed.', 'unit' => 'count'],
                ['key' => 'plantation_activities', 'title' => 'Plantation/landscaping activities conducted.', 'unit' => 'activities'],
                ['key' => 'public_spaces_upkeep', 'title' => 'Upkeep of public spaces and cleanliness monitoring.', 'unit' => 'inspections'],
            ],

            'maintenance-of-drains-and-sewerage-lines' => [
                ['key' => 'drain_cleaning', 'title' => 'Drain/sewerage line cleaning operations completed.', 'unit' => 'operations'],
                ['key' => 'blockages_cleared', 'title' => 'Blockages cleared and flow restored.', 'unit' => 'count'],
                ['key' => 'complaints_resolved', 'title' => 'Public complaints resolved regarding sewerage/drainage.', 'unit' => 'actions'],
            ],

            'bus-terminals' => [
                ['key' => 'terminal_inspections', 'title' => 'Bus terminal inspections conducted.', 'unit' => 'inspections'],
                ['key' => 'cleanliness_compliance', 'title' => 'Cleanliness & facilities compliance verified.', 'unit' => 'count'],
                ['key' => 'issues_resolved', 'title' => 'Issues resolved through follow-up actions.', 'unit' => 'actions'],
            ],

            'chief-ministers-complaint-cell' => [
                ['key' => 'complaints_received', 'title' => 'Complaints received on Chief Minister’s Complaint Cell.', 'unit' => 'count'],
                ['key' => 'complaints_resolved', 'title' => 'Complaints resolved within timelines.', 'unit' => 'count'],
                ['key' => 'pending_followups', 'title' => 'Pending complaints followed up with concerned departments.', 'unit' => 'actions'],
            ],

            'regulation-of-shops-and-handcarts' => [
                ['key' => 'market_visits', 'title' => 'Market visits for regulation of shops and handcarts.', 'unit' => 'inspections'],
                ['key' => 'violations_identified', 'title' => 'Violations/obstructions identified.', 'unit' => 'count'],
                ['key' => 'actions_taken', 'title' => 'Actions taken (warnings/removals/fines).', 'unit' => 'actions'],
            ],

            'e-biz' => [
                ['key' => 'applications_completed', 'title' => 'Completion of all applications received.', 'unit' => 'applications'],
                ['key' => 'help_desk_inspection', 'title' => 'DCs Inspection in the offices (DC, ACs and MCs) to check the proper establishment of Help Desks.', 'unit' => 'inspections'],
                ['key' => 'timely_disposal_meeting', 'title' => 'Meeting of DCs on timely Disposal of Applications with the officers of Concerned Departments/Organizations.', 'unit' => 'meetings'],
            ],

            default => [
                // Safe fallback: minimal, non-generic metrics (prevents Total Records/Compliant spam).
                ['key' => 'monitoring_visits', 'title' => 'Monitoring/field visits conducted for KPI compliance.', 'unit' => 'visits'],
                ['key' => 'actions_taken', 'title' => 'Corrective actions taken as per observations.', 'unit' => 'actions'],
                ['key' => 'issues_resolved', 'title' => 'Issues resolved and verified through follow-up.', 'unit' => 'actions'],
            ],
        };
    }

    private function metricDefinitionsForGraphicalReport(?string $slug, string $name): array
    {
        $slug = (string) $slug;

        if ($this->isWaterCategory($slug, $name)) {
            return [
                ['key' => 'total_plants', 'title' => 'Total Water Filtration Plants', 'unit' => 'count'],
                ['key' => 'inspected', 'title' => 'Inspected', 'unit' => 'count'],
                ['key' => 'not_inspected', 'title' => 'Not Inspected', 'unit' => 'count'],
                ['key' => 'functional', 'title' => 'Functional', 'unit' => 'count'],
                ['key' => 'non_functional', 'title' => 'Non-Functional', 'unit' => 'count'],
                ['key' => 'cleaned', 'title' => 'Cleaned', 'unit' => 'count'],
                ['key' => 'uncleaned', 'title' => 'Un-cleaned', 'unit' => 'count'],
                ['key' => 'ro_filter_changed', 'title' => 'RO Filter Changed', 'unit' => 'count'],
                ['key' => 'ro_filter_unchanged', 'title' => 'RO Filter Unchanged', 'unit' => 'count'],
            ];
        }

        if (str_contains($slug, 'marriage')) {
            return [
                ['key' => 'total_inspections', 'title' => 'Total Inspections', 'unit' => 'inspections'],
                ['key' => 'compliant', 'title' => 'Compliant', 'unit' => 'count'],
                ['key' => 'violations_found', 'title' => 'Violations Found', 'unit' => 'count'],
                ['key' => 'notices_issued', 'title' => 'Notices Issued', 'unit' => 'actions'],
                ['key' => 'fines_imposed', 'title' => 'Fines Imposed', 'unit' => 'actions'],
            ];
        }

        if (str_contains($slug, 'essential') || str_contains($slug, 'price-control') || str_contains($slug, 'price-control-of-essential')) {
            return [
                ['key' => 'shops_inspected', 'title' => 'Shops Inspected', 'unit' => 'inspections'],
                ['key' => 'price_list_displayed', 'title' => 'Price List Displayed', 'unit' => 'count'],
                ['key' => 'overcharging_found', 'title' => 'Overcharging Found', 'unit' => 'count'],
                ['key' => 'fines_imposed', 'title' => 'Fines Imposed', 'unit' => 'actions'],
                ['key' => 'compliant_shops', 'title' => 'Compliant Shops', 'unit' => 'count'],
            ];
        }

        if (str_contains($slug, 'manhole')) {
            return [
                ['key' => 'total_locations', 'title' => 'Total Locations', 'unit' => 'count'],
                ['key' => 'covered', 'title' => 'Covered', 'unit' => 'count'],
                ['key' => 'uncovered', 'title' => 'Uncovered', 'unit' => 'count'],
                ['key' => 'high_risk', 'title' => 'High Risk', 'unit' => 'count'],
                ['key' => 'resolved', 'title' => 'Resolved', 'unit' => 'count'],
            ];
        }

        if (str_contains($slug, 'stray-dog')) {
            return [
                ['key' => 'areas_inspected', 'title' => 'Areas Inspected', 'unit' => 'inspections'],
                ['key' => 'complaints_verified', 'title' => 'Complaints Verified', 'unit' => 'count'],
                ['key' => 'dogs_spotted', 'title' => 'Dogs Spotted', 'unit' => 'count'],
                ['key' => 'response_completed', 'title' => 'Response Completed', 'unit' => 'count'],
                ['key' => 'follow_up_required', 'title' => 'Follow-up Required', 'unit' => 'count'],
            ];
        }

        // Keep graphical report seeded for all categories; avoid repeating "Total Records" etc.
        // Use a small generic set that reads like management KPIs.
        return [
            ['key' => 'monitoring_visits', 'title' => 'Monitoring Visits', 'unit' => 'visits'],
            ['key' => 'actions_taken', 'title' => 'Actions Taken', 'unit' => 'actions'],
            ['key' => 'issues_resolved', 'title' => 'Issues Resolved', 'unit' => 'actions'],
        ];
    }

    private function titleForKey(array $defs, string $key): string
    {
        foreach ($defs as $d) {
            if (($d['key'] ?? '') === $key) return (string) ($d['title'] ?? $key);
        }
        return $key;
    }

    private function unitForKey(array $defs, string $key): ?string
    {
        foreach ($defs as $d) {
            if (($d['key'] ?? '') === $key) return $d['unit'] ?? null;
        }
        return null;
    }

    private function valueForProvinceMetric(string $categorySlug, string $metricKey, float $scale): float
    {
        // Simple realistic ranges by key; scaled weekly.
        $base = match ($metricKey) {
            // Old PPMF KPI report-style metrics (action-based)
            'dc_weekly_review' => mt_rand(10, 80),
            'tandoor_inspections' => mt_rand(200, 1800),
            'bread_inspections' => mt_rand(150, 1400),
            'coverage_mobility_index' => mt_rand(60, 100),
            'fine_imposed' => mt_rand(5, 450),
            'citizen_complaint_action' => mt_rand(20, 900),
            'complaints_action' => mt_rand(20, 900),
            'market_inspections' => mt_rand(200, 2200),
            'price_list_display' => mt_rand(150, 2000),
            'overcharging_actions' => mt_rand(10, 420),

            'road_sites_identified' => mt_rand(50, 600),
            'repair_completed' => mt_rand(30, 420),
            'quality_checks' => mt_rand(30, 320),
            'complaints_resolved' => mt_rand(20, 550),

            'locations_identified' => mt_rand(50, 500),
            'markings_done' => mt_rand(30, 420),
            'visibility_maintenance' => mt_rand(20, 380),

            'faults_reported' => mt_rand(120, 1800),
            'repairs_completed' => mt_rand(80, 1500),
            'followups' => mt_rand(60, 1200),

            'manholes_identified' => mt_rand(80, 1200),
            'covers_installed' => mt_rand(60, 1100),
            'hazards_resolved' => mt_rand(40, 900),

            'filter_change_logged' => mt_rand(80, 900),
            'plants_functional_verified' => mt_rand(120, 1500),

            'institution_visits' => mt_rand(100, 1400),
            'cleanliness_compliance' => mt_rand(80, 1200),
            'facility_visits' => mt_rand(80, 1200),
            'medicine_availability' => mt_rand(60, 1100),

            'marriage_hall_inspections' => mt_rand(60, 520),
            'violations_detected' => mt_rand(5, 260),
            'notices_fines' => mt_rand(5, 240),

            'encroachments_identified' => mt_rand(60, 900),
            'encroachments_removed' => mt_rand(40, 850),
            'operations_conducted' => mt_rand(10, 220),

            'areas_visited' => mt_rand(30, 520),
            'actions_taken' => mt_rand(10, 520),

            'sites_identified' => mt_rand(60, 900),
            'removal_done' => mt_rand(40, 800),
            'repeat_prevented' => mt_rand(30, 700),

            'graveyard_inspections' => mt_rand(20, 260),
            'cleanliness_actions' => mt_rand(10, 220),

            'lpg_points_inspected' => mt_rand(20, 240),

            'cleanliness_drives' => mt_rand(10, 160),
            'waste_removed' => mt_rand(40, 900),
            'monitoring_visits' => mt_rand(60, 1200),

            'greenbelt_maintenance' => mt_rand(20, 260),
            'plantation_activities' => mt_rand(20, 220),
            'public_spaces_upkeep' => mt_rand(30, 520),

            'drain_cleaning' => mt_rand(20, 260),
            'blockages_cleared' => mt_rand(20, 360),

            'terminal_inspections' => mt_rand(20, 260),

            'complaints_received' => mt_rand(200, 5000),
            'pending_followups' => mt_rand(40, 900),

            'market_visits' => mt_rand(40, 900),
            'violations_identified' => mt_rand(10, 520),

            'applications_completed' => mt_rand(5000, 55000),
            'help_desk_inspection' => mt_rand(60, 900),
            'timely_disposal_meeting' => mt_rand(10, 180),

            'total_plants' => mt_rand(4200, 6500),
            'inspected' => mt_rand(2800, 5200),
            'not_inspected' => mt_rand(400, 1800),
            'functional' => mt_rand(2500, 5000),
            'non_functional' => mt_rand(80, 450),
            'cleaned' => mt_rand(1800, 4500),
            'uncleaned' => mt_rand(100, 900),
            'ro_filter_changed' => mt_rand(120, 900),
            'ro_filter_unchanged' => mt_rand(200, 1400),

            'total_inspections' => mt_rand(80, 520),
            'compliant' => mt_rand(40, 420),
            'violations_found' => mt_rand(10, 240),
            'notices_issued' => mt_rand(5, 220),
            'fines_imposed' => mt_rand(0, 180),

            'shops_inspected' => mt_rand(120, 1200),
            'price_list_displayed' => mt_rand(80, 1000),
            'overcharging_found' => mt_rand(10, 260),
            'compliant_shops' => mt_rand(80, 1050),

            'total_locations' => mt_rand(200, 2200),
            'covered' => mt_rand(150, 2000),
            'uncovered' => mt_rand(20, 450),
            'high_risk' => mt_rand(10, 180),
            'resolved' => mt_rand(80, 1800),

            'areas_inspected' => mt_rand(40, 520),
            'complaints_verified' => mt_rand(20, 380),
            'dogs_spotted' => mt_rand(10, 320),
            'response_completed' => mt_rand(10, 320),
            'follow_up_required' => mt_rand(0, 220),

            default => mt_rand(60, 1200),
        };

        $val = round($base * $scale, 2);

        // Keep some logical relations for water category cards.
        if ($this->isWaterCategory($categorySlug, $categorySlug)) {
            // no-op here; relations handled via district generation mostly.
        }

        return max(0, $val);
    }

    private function valueForDistrictMetric(string $categorySlug, string $metricKey, float $provinceBase, int $districtId): float
    {
        $variance = (mt_rand(70, 130) / 100);
        $val = $provinceBase > 0 ? ($provinceBase / 41) * $variance * 12 : mt_rand(5, 60); // scaled per district

        // Add deterministic-ish skew so charts look varied per district.
        $skew = (($districtId % 7) - 3) * 0.06; // -0.18..+0.18
        $val = $val * (1 + $skew);

        // For non_functional keep smaller.
        if (in_array($metricKey, ['non_functional', 'ro_filter_changed', 'uncovered', 'high_risk', 'follow_up_required', 'violations_found', 'overcharging_found'], true)) {
            $val = $val * (mt_rand(20, 60) / 100);
        }

        return round(max(0, $val), 2);
    }

    private function scoreFromValue(float $provinceBase, float $districtValue): ?float
    {
        if ($provinceBase <= 0) {
            return null;
        }

        $ratio = min(1.25, max(0, $districtValue / ($provinceBase / 41)));
        $score = ($ratio * 70) + mt_rand(10, 30);
        return round(min(100, max(0, $score)), 2);
    }
}
