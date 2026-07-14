<?php

namespace App\Services;

use App\Models\KpiCard;
use App\Models\KpiInspection;
use App\Models\User;
use App\Services\KpiInspectionService as InspectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class KpiInspectionDashboardContext
{
    public function __construct(
        private readonly InspectionService $inspectionService,
    ) {}

    /**
     * @param  array<string, int>  $statusCounts
     * @return array<string, mixed>
     */
    public function forKpi(
        KpiCard $card,
        User $user,
        Request $request,
        Collection $inspections,
        array $statusCounts,
        float $operationalTarget = 0,
        float $operationalCompleted = 0,
    ): array {
        $slug = $card->slug;
        $count = $inspections->count();
        $approved = (int) ($statusCounts['approved'] ?? 0);
        $pending = (int) ($statusCounts['pending_review'] ?? 0);
        $rejected = (int) ($statusCounts['rejected'] ?? 0);
        $reviewTarget = $this->inspectionService->reviewTargetFor($card, $user, $request, $count);
        $reviewCounts = $this->inspectionService->healthReviewStatusCounts($inspections, $reviewTarget);
        $approved = (int) $reviewCounts['approved'];
        $pending = (int) $reviewCounts['pending'];
        $rejected = (int) $reviewCounts['rejected'];

        $violations = $inspections->filter(function (KpiInspection $inspection): bool {
            $detail = $this->detail($inspection);
            $violation = trim((string) ($detail['violation'] ?? $detail['commodity_violation'] ?? ''));

            if ($violation === '' || strcasecmp($violation, 'Compliant') === 0) {
                return ($detail['illegal_decanting_observed'] ?? '') === 'yes';
            }

            return true;
        })->count();

        $finesTotal = (int) $inspections->sum(function (KpiInspection $inspection): int {
            $detail = $this->detail($inspection);

            return (int) ($detail['fine'] ?? $detail['fine_amount'] ?? 0);
        });

        $achievement = $operationalTarget > 0
            ? round(min(100, ($operationalCompleted / $operationalTarget) * 100), 1)
            : ($count > 0 ? 100.0 : 0.0);

        $base = [
            'inspected' => $count,
            'inspections_conducted' => $count,
            'violations_found' => $violations,
            'fine_imposed' => $finesTotal,
            'fine_deposited' => $finesTotal,
            'achievement_rate' => $achievement,
            'target_achievement' => $achievement,
            'approved' => $approved,
            'pending' => $pending,
            'rejected' => $rejected,
            'inspections_approved' => $approved,
            'inspections_pending' => $pending,
            'inspections_rejected' => $rejected,
            'review_target' => $reviewTarget,
            'validation_target' => $reviewTarget,
            'reviewed' => $approved + $rejected,
            'operational_target' => $operationalTarget,
            'operational_completed' => $operationalCompleted,
        ];

        return array_merge($base, $this->slugSpecific($slug, $inspections, $base));
    }

    /** @return array<string, mixed> */
    private function slugSpecific(string $slug, Collection $inspections, array $base): array
    {
        $count = (int) $base['inspected'];
        $target = (int) max(1, round((float) ($base['operational_target'] ?? 0)));
        $yes = fn (string $field) => $inspections->filter(fn ($i) => in_array(strtolower((string) ($this->detail($i)[$field] ?? '')), ['yes', '1', 'true', 'available', 'visible', 'clean', 'functional', 'marked', 'repainted', 'completed', 'paid'], true))->count();
        $sum = fn (string $field) => (int) $inspections->sum(fn ($i) => (int) ($this->detail($i)[$field] ?? 0));
        $violationIs = fn (string $value) => $inspections->filter(fn ($i) => ($this->detail($i)['violation'] ?? $this->detail($i)['commodity_violation'] ?? '') === $value)->count();
        $fined = $inspections->filter(fn ($i) => (int) ($this->detail($i)['fine'] ?? $this->detail($i)['fine_amount'] ?? 0) > 0)->count();
        $priceObs = [
            'obs_over_price' => $violationIs('Over Price'),
            'obs_under_weight' => $violationIs('Under Weight'),
            'obs_non_availability' => $violationIs('Non-Availability'),
            'obs_fine_imposed' => $fined,
            'obs_complaint_action' => max(0, (int) round($count * 0.25)),
        ];

        return match ($slug) {
            'price-of-roti' => array_merge([
                'tier_target' => $target > 0 ? $target : 6,
                'inspections_total_target' => $target > 0 ? $target : 6,
                'tandoor_inspections' => $count,
                'complaints_resolved' => max(1, (int) round($count * 0.35)),
            ], $priceObs),
            'price-of-plain-bakery-bread' => array_merge([
                'tier_target' => $target > 0 ? $target : 3,
                'inspections_total_target' => $target > 0 ? $target : 3,
                'bread_inspections' => $count,
                'citizen_complaint_action' => max(0, (int) round($count * 0.25)),
            ], array_diff_key($priceObs, ['obs_under_weight' => true])),
            'price-control-of-essential-commodities' => array_merge([
                'tier_target' => $target > 0 ? $target : 21,
                'inspections_total_target' => $target > 0 ? $target : 21,
                'market_inspections' => $count,
                'commodity_violations' => $base['violations_found'],
                'sb_violations' => $base['violations_found'],
                'citizen_violations' => max(1, (int) round($count * 0.2)),
                'obs_commodity_types' => $inspections->pluck('detail_data')->map(fn ($d) => is_array($d) ? ($d['commodity'] ?? 'General') : 'General')->unique()->count(),
                'obs_citizen_report' => max(0, (int) round($count * 0.15)),
                'obs_sb_report' => max(0, (int) round($count * 0.1)),
            ], array_intersect_key($priceObs, array_flip(['obs_over_price', 'obs_fine_imposed']))),
            'repair-of-small-roads-in-both-urban-and-rural-areas' => [
                'weekly_road_target' => max(1, $target),
                'repair_completed' => $count,
                'lane_marking_done' => $yes('lane_marking_done'),
                'roads_in_progress' => $inspections->filter(fn ($i) => in_array($this->detail($i)['completion_status'] ?? '', ['In Progress', 'Pending'], true))->count(),
                'roads_work_completed' => $inspections->filter(fn ($i) => ($this->detail($i)['completion_status'] ?? '') === 'Completed')->count(),
                'complaints_resolved' => $inspections->filter(fn ($i) => ($this->detail($i)['completion_status'] ?? '') === 'In Progress')->count(),
                'obs_work_type' => $inspections->pluck('detail_data')->map(fn ($d) => is_array($d) ? ($d['repair_type'] ?? $d['work_type'] ?? 'Patching') : 'Patching')->countBy()->keys()->first() ?? 'Patching',
                'obs_work_status' => $inspections->filter(fn ($i) => ($this->detail($i)['completion_status'] ?? '') === 'Completed')->count(),
                'obs_length_covered' => $sum('length_covered_m') + $sum('length_meters'),
                'obs_lane_marking' => $yes('lane_marking_done'),
                'obs_sb_complaint' => $yes('sb_complaint'),
            ],
            'dysfunctional-streetlights' => [
                'roads_with_streetlights' => max($count, 8),
                'weekly_visit_target' => max(2, (int) ceil(max($count, 8) * 0.25)),
                'roads_inspected' => $count,
                'faulty_lights_found' => $sum('dysfunctional_lights'),
                'lights_repaired' => $sum('repaired_lights'),
                'repair_rate' => $sum('dysfunctional_lights') > 0
                    ? round(min(100, ($sum('repaired_lights') / max(1, $sum('dysfunctional_lights'))) * 100), 1)
                    : 0.0,
                'obs_streetlight_status' => $count,
                'obs_faulty_lights' => $sum('dysfunctional_lights'),
                'obs_lights_repaired' => $sum('repaired_lights'),
                'obs_sb_complaint' => $yes('sb_complaint'),
            ],
            'covering-of-manholes' => [
                'total_ucs' => max($count, 5),
                'ucs_inspected' => $count,
                'open_manholes_found' => $sum('open_manholes'),
                'manholes_covered' => $sum('covered_manholes'),
                'compliance_rate' => ($sum('open_manholes') + $sum('covered_manholes')) > 0
                    ? round(min(100, ($sum('covered_manholes') / max(1, $sum('open_manholes') + $sum('covered_manholes'))) * 100), 1)
                    : 0.0,
                'obs_manhole_status' => $count,
                'obs_open_manholes' => $sum('open_manholes'),
                'obs_manholes_covered' => $yes('all_manholes_covered'),
                'obs_netting_available' => $yes('netting_available'),
            ],
            'functional-and-clean-water-filtration-plants' => [
                'total_plants' => max($count, 8),
                'weekly_inspection_target' => max(2, (int) ceil(max($count, 8) * 0.25)),
                'plants_inspected' => $count,
                'functional_plants' => $inspections->filter(fn ($i) => in_array($this->detail($i)['functional_status'] ?? '', ['Functional', 'Partially Functional'], true))->count(),
                'clean_plants' => $inspections->filter(fn ($i) => in_array($this->detail($i)['cleanliness_status'] ?? '', ['Clean', 'Good'], true))->count(),
                'filter_change_compliance' => $yes('ro_filter_date_affixed') ?: $inspections->filter(
                    fn ($i) => ($this->detail($i)['filter_change_status'] ?? '') === 'Up to Date'
                )->count(),
                'obs_functional' => $inspections->filter(fn ($i) => ($this->detail($i)['functional_status'] ?? '') === 'Functional')->count(),
                'obs_cleanliness' => $inspections->filter(fn ($i) => in_array($this->detail($i)['cleanliness_status'] ?? '', ['Clean', 'Good'], true))->count(),
                'obs_sb_visit' => $yes('sb_visit'),
            ],
            'violation-of-marriage-functions-act' => [
                'total_halls' => max($count, 20),
                'weekly_inspection_target' => max(3, (int) ceil(max($count, 20) * 0.15)),
                'marriage_hall_inspections' => $count,
                'violations_detected' => $base['violations_found'],
                'notices_fines' => $base['violations_found'],
                'pra_registered' => $yes('pra_registration'),
                'obs_one_dish_rule' => $yes('one_dish_rule'),
                'obs_timing_compliance' => $yes('timing_compliance'),
                'obs_pra_registration' => $yes('pra_registration'),
                'obs_fine_imposed' => $fined,
                'obs_fir_filed' => $yes('fir_filed'),
            ],
            'anti-encroachment-campaign' => [
                'daily_market_target' => max(1, $target),
                'markets_cleared' => $count,
                'encroachments_removed' => $sum('cleared_points'),
                'encroachment_points' => $sum('encroachment_points'),
                'actions_taken' => $base['violations_found'] + $yes('fir_filed'),
                'obs_moveable' => $yes('moveable_encroachment'),
                'obs_immovable' => $yes('immovable_encroachment'),
                'obs_market_cleared' => $yes('market_cleared'),
            ],
            'regulation-of-shops-and-handcarts' => [
                'daily_market_target' => max(1, $target),
                'markets_inspected' => $count,
                'violations_found' => $sum('violations_found') ?: $base['violations_found'],
                'shop_violations' => $sum('shops_checked') ?: max(1, (int) round($count * 0.6)),
                'handcart_violations' => $sum('handcarts_checked') ?: max(0, (int) round($count * 0.4)),
                'actions_taken' => $sum('violations_found'),
                'obs_shop_line_compliance' => $yes('shop_line_compliance'),
                'obs_walkway_obstruction' => $yes('walkway_obstruction'),
                'obs_waste_debris' => $yes('waste_debris'),
                'obs_unauthorized_handcarts' => $yes('unauthorized_handcarts'),
            ],
            'stray-dogs' => [
                'target_ucs' => max(1, $target),
                'uc_activities' => $count,
                'dogs_observed' => $sum('dogs_observed'),
                'dogs_culled' => $sum('dogs_culled'),
                'sb_complaints' => $yes('sb_complaint'),
                'obs_culling_performed' => $yes('culling_activity'),
                'obs_dogs_observed' => $sum('dogs_observed'),
                'obs_dogs_culled' => $sum('dogs_culled'),
                'obs_sb_complaint' => $yes('sb_complaint'),
            ],
            'removal-of-wall-chalking' => [
                'target_ucs' => max(1, $target),
                'ucs_inspected' => $count,
                'sites_identified' => $sum('spots_identified'),
                'removal_done' => $sum('spots_cleared'),
                'pending_spots' => max(0, $sum('spots_identified') - $sum('spots_cleared')),
                'obs_wall_chalking_removed' => $yes('wall_chalking_removed'),
                'obs_spots_removed' => $sum('spots_cleared'),
                'obs_sb_complaint' => $yes('sb_complaint'),
            ],
            'graveyards' => [
                'weekly_target' => max(2, $target),
                'graveyards_cleared' => $count,
                'boundary_wall_issues' => $yes('demarcated'),
                'encroachment_removed' => $yes('encroachment_removed'),
                'obs_encroachment_removed' => $yes('encroachment_removed'),
                'obs_cleanliness' => $yes('cleaned'),
                'obs_boundary_wall' => $yes('demarcated'),
                'obs_bush_trimming' => $yes('bush_trimming'),
            ],
            'zebra-crossings' => [
                'schools_to_inspect' => max($count, 20),
                'schools_inspected' => $count,
                'weekly_inspection_target' => max(5, (int) ceil(max($count, 20) * 0.25)),
                'markings_done' => $inspections->filter(fn ($i) => in_array($this->detail($i)['crossing_status'] ?? '', ['Marked', 'Repainted', 'Visible', 'Restored'], true))->count(),
                'resolved_points' => $inspections->filter(fn ($i) => in_array($this->detail($i)['crossing_status'] ?? '', ['Repainted', 'Restored', 'Visible'], true)
                    || in_array(strtolower((string) ($this->detail($i)['action_taken'] ?? '')), ['repainted', 'restored', 'marking restored'], true))->count(),
                'faded_crossings' => $inspections->filter(fn ($i) => in_array($this->detail($i)['crossing_status'] ?? '', ['Faded', 'Missing', 'Absent'], true))->count(),
                'obs_crossing_status' => $count,
                'obs_repainted' => $inspections->filter(fn ($i) => in_array($this->detail($i)['crossing_status'] ?? '', ['Repainted', 'Restored'], true))->count(),
                'obs_action_taken' => $inspections->filter(fn ($i) => filled($this->detail($i)['action_taken'] ?? null))->count(),
                'obs_sb_complaint' => $yes('sb_complaint'),
            ],
            'illegal-decanting' => [
                'tier_target' => max(15, $target),
                'stations_inspected' => $count,
                'violations_found' => $base['violations_found'],
                'enforcement_actions' => $inspections->filter(fn ($i) => filled($this->detail($i)['action_type'] ?? null))->count(),
                'fines_imposed' => $base['fine_imposed'],
                'obs_illegal_decanting' => $inspections->filter(fn ($i) => ($this->detail($i)['illegal_decanting_observed'] ?? '') === 'yes')->count(),
                'obs_license_missing' => $yes('license_missing'),
                'obs_unsafe_equipment' => $yes('unsafe_equipment'),
                'obs_fine_imposed' => $fined,
            ],
            'suthra-punjab-campaign' => [
                'weekly_uc_target' => max(4, $target),
                'ac_uc_inspections' => $count,
                'hr_attendance' => $yes('hr_attendance_ok'),
                'vehicles_in_field' => $yes('machinery_in_field'),
                'containers_placed' => $yes('containers_placed'),
                'heaps_cleared' => $yes('garbage_heaps_cleared'),
                'obs_hr_attendance' => $yes('hr_attendance_ok'),
                'obs_machinery_field' => $yes('machinery_in_field'),
                'obs_containers' => $yes('containers_placed'),
                'obs_door_to_door' => $yes('door_to_door_collection'),
                'obs_heaps_cleared' => $yes('garbage_heaps_cleared'),
            ],
            'maintenance-of-greenbelts' => [
                'total_parks' => max($count, 6),
                'parks_maintained' => $inspections->filter(fn ($i) => in_array(strtolower((string) ($this->detail($i)['type'] ?? $this->detail($i)['site_type'] ?? '')), ['park', 'family park'], true))->count(),
                'greenbelts_maintained' => $inspections->filter(fn ($i) => str_contains(strtolower((string) ($this->detail($i)['type'] ?? $this->detail($i)['site_type'] ?? '')), 'greenbelt'))->count(),
                'beautification' => $yes('kerb_stone_paint'),
                'kerb_painting_target' => 1,
                'obs_kerb_paint' => $yes('kerb_stone_paint'),
                'obs_flowering_grass' => $yes('flowering_grass'),
                'obs_greenbelt_maintained' => $yes('greenbelt_maintained'),
                'obs_cleanliness' => $yes('cleanliness_ok'),
            ],
            'maintenance-of-drains-and-sewerage-lines' => [
                'total_ucs' => max($count, 4),
                'ucs_inspected' => $count,
                'blockages_reported' => $sum('blockage_identified'),
                'stagnant_water' => $yes('stagnant_water'),
                'resolved_points' => $inspections->filter(fn ($i) => ($this->detail($i)['cleaned_status'] ?? '') === 'Cleaned')->count(),
                'overflow_points' => $yes('overflowing'),
                'obs_blocked' => $yes('blockage_identified'),
                'obs_overflowing' => $yes('overflowing'),
                'obs_stagnant_water' => $yes('stagnant_water'),
                'obs_remedial_status' => $inspections->filter(fn ($i) => ($this->detail($i)['cleaned_status'] ?? '') === 'Cleaned')->count(),
            ],
            'bus-terminals' => [
                'total_terminals' => max($count, 2),
                'required_visits' => max($count, 2),
                'terminals_inspected' => $count,
                'fare_display_checked' => $yes('fare_display'),
                'water_washroom_ok' => $yes('drinking_water') + $yes('washroom'),
                'cleanliness_checked' => $yes('cleanliness'),
                'obs_fare_display' => $yes('fare_display'),
                'obs_waiting_area' => $yes('waiting_area'),
                'obs_drinking_water' => $yes('drinking_water'),
                'obs_utilities' => $yes('electricity'),
            ],
            'chief-ministers-complaint-cell' => [
                'complaints_received' => $count,
                'complaints_resolved' => $base['approved'],
                'pending_complaints' => $base['pending'],
                'overdue_complaints' => $inspections->filter(fn ($i) => ($this->detail($i)['overdue_status'] ?? '') === 'Overdue')->count(),
                'resolution_rate' => $count > 0 ? round(($base['approved'] / $count) * 100, 1) : 0,
                'avg_resolution_days' => $count > 0 ? round($sum('resolution_days') / $count, 1) : 0,
            ],
            'e-biz' => [
                'pending_applications' => $sum('pending_cases'),
                'applications_completed' => $sum('applications_reviewed') ?: $base['approved'],
                'disposal_rate' => $count > 0 ? round((($sum('applications_reviewed') ?: $base['approved']) / max(1, $sum('pending_cases') + $sum('applications_reviewed'))) * 100, 1) : 0,
                'help_desk_inspections' => $count,
                'branding_available' => $yes('branding_available'),
                'meeting_held' => $yes('dc_meeting_held'),
                'obs_help_desk' => $yes('help_desk_established'),
                'obs_branding' => $yes('branding_available'),
                'obs_pending_apps' => $sum('pending_cases'),
                'obs_processed_apps' => $sum('applications_reviewed') ?: $base['approved'],
            ],
            default => [],
        };
    }

    /** @return array<string, mixed> */
    private function detail(KpiInspection $inspection): array
    {
        $detail = $inspection->detail_data;

        return is_array($detail) ? $detail : (json_decode($detail ?? '[]', true) ?: []);
    }
}
