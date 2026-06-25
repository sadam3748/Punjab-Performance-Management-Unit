<?php

namespace App\Services;

use Carbon\Carbon;

/**
 * Generates realistic KPI metric snapshots for demo seeding (AC Lahore / AC Layyah).
 */
class PpmuDemoMetricFactory
{
  /** @return array{snapshot: array<string, int|float>, achievement_pct: float, remarks: string} */
  public function build(string $slug, Carbon $date, string $username, int $dayOffset, string $periodType): array
  {
    $lahore = str_contains($username, 'lahore');
    $base = $this->basePerformance($slug, $lahore);
    $trend = $this->trendFactor($date, $dayOffset, $lahore);
    $pct = $this->boundedPerformance($base + $trend, $dayOffset);

    $snapshot = match ($slug) {
      'price-of-roti' => $this->roti($pct, $dayOffset, $date, $lahore),
      'price-of-plain-bakery-bread' => $this->bread($pct, $dayOffset),
      'price-control-of-essential-commodities' => $this->priceControl($pct, $dayOffset),
      'repair-of-small-roads-in-both-urban-and-rural-areas' => $this->roads($pct, $dayOffset),
      'zebra-crossings' => $this->zebra($pct, $dayOffset),
      'dysfunctional-streetlights' => $this->streetlights($pct, $dayOffset),
      'covering-of-manholes' => $this->manholes($pct, $dayOffset),
      'functional-and-clean-water-filtration-plants' => $this->waterPlants($pct, $dayOffset),
      'inspection-of-educational-institutions' => $this->schools($pct, $dayOffset, $lahore, $date),
      'inspection-of-health-facilities' => $this->health($pct, $dayOffset, $lahore, $date),
      'violation-of-marriage-functions-act' => $this->marriage($pct, $dayOffset),
      'anti-encroachment-campaign' => $this->encroachment($pct, $dayOffset),
      'regulation-of-shops-and-handcarts' => $this->shops($pct, $dayOffset),
      'stray-dogs' => $this->strayDogs($pct, $dayOffset),
      'removal-of-wall-chalking' => $this->wallChalking($pct, $dayOffset),
      'graveyards' => $this->graveyards($pct, $dayOffset),
      'e-biz' => $this->ebiz($pct, $dayOffset, $lahore),
      'illegal-decanting' => $this->decanting($pct, $dayOffset),
      'suthra-punjab-campaign' => $this->suthra($pct, $dayOffset),
      'maintenance-of-greenbelts' => $this->greenbelts($pct, $dayOffset),
      'maintenance-of-drains-and-sewerage-lines' => $this->drains($pct, $dayOffset),
      'bus-terminals' => $this->busTerminals($pct, $dayOffset),
      'chief-ministers-complaint-cell' => $this->complaints($pct, $dayOffset, $lahore),
      default => $this->generic($pct, $dayOffset),
    };

    return [
      'snapshot' => $this->withScopeScale(
        $this->withOperationalMetrics($slug, $snapshot, $pct),
        $username
      ),
      'achievement_pct' => $pct,
      'remarks' => $this->remarks($slug, $date, $periodType, $lahore, $pct),
    ];
  }

  private function basePerformance(string $slug, bool $lahore): float
  {
    $profiles = [
      'price-of-roti' => [86, 78],
      'price-of-plain-bakery-bread' => [76, 70],
      'price-control-of-essential-commodities' => [44, 38],
      'repair-of-small-roads-in-both-urban-and-rural-areas' => [58, 52],
      'zebra-crossings' => [72, 66],
      'dysfunctional-streetlights' => [46, 40],
      'covering-of-manholes' => [62, 56],
      'functional-and-clean-water-filtration-plants' => [98, 94],
      'inspection-of-educational-institutions' => [96, 91],
      'inspection-of-health-facilities' => [79, 72],
      'violation-of-marriage-functions-act' => [70, 64],
      'anti-encroachment-campaign' => [48, 42],
      'regulation-of-shops-and-handcarts' => [75, 68],
      'stray-dogs' => [55, 47],
      'removal-of-wall-chalking' => [68, 60],
      'graveyards' => [73, 67],
      'e-biz' => [82, 74],
      'illegal-decanting' => [66, 58],
      'suthra-punjab-campaign' => [84, 76],
      'maintenance-of-greenbelts' => [77, 69],
      'maintenance-of-drains-and-sewerage-lines' => [61, 54],
      'bus-terminals' => [71, 63],
      'chief-ministers-complaint-cell' => [98, 93],
    ];

    [$good, $avg] = $profiles[$slug] ?? [72, 64];

    return $lahore ? $good : $avg;
  }

  private function trendFactor(Carbon $date, int $dayOffset, bool $lahore): float
  {
    $weekly = sin(($dayOffset % 14) / 14 * M_PI) * 6;
    $weekday = match ($date->dayOfWeek) {
      Carbon::SATURDAY, Carbon::SUNDAY => -4,
      Carbon::MONDAY => 2,
      default => 0,
    };
    $lahoreBoost = $lahore ? 2 : -1;

    return round($weekly + $weekday + $lahoreBoost + (($dayOffset % 5) - 2), 1);
  }

  private function boundedPerformance(float $raw, int $dayOffset): float
  {
    $cycle = $dayOffset % 16;

    $target = match (true) {
      $cycle === 0 => 42 + ($dayOffset % 5),
      $cycle === 5 => 58 + ($dayOffset % 7),
      $cycle === 10 => 74 + ($dayOffset % 8),
      $cycle === 15 => 88 + ($dayOffset % 7),
      default => $raw,
    };

    return round(max(32, min(98, $target)), 1);
  }

  private function performanceStatus(float $pct): string
  {
    return match (true) {
      $pct >= 85 => 'Excellent',
      $pct >= 70 => 'Good',
      $pct >= 50 => 'Needs Attention',
      default => 'Critical',
    };
  }

  private function roti(float $pct, int $d, Carbon $date, bool $lahore): array
  {
    $tiers = [10, 8, 6];
    $tier = $tiers[$d % 3];
    $inspectors = $lahore ? (6 + ($d % 3)) : (4 + ($d % 2));
    $totalTarget = $inspectors * $tier;
    $inspections = max(8, (int) round($totalTarget * max(45, $pct) / 100));
    $violations = max(2, (int) round($inspections * 0.22));
    $fines = max(2, (int) round($violations * 0.72));
    $complaints = max(3, (int) round($inspections * 0.12));
    $resolved = max(2, (int) round($complaints * 0.86));
    $validationTarget = 10 + ($d % 5);
    $validated = max(6, (int) round($validationTarget * max(70, $pct) / 100));
    $approved = max(4, (int) round($validated * 0.8));
    $rejected = max(1, (int) round($validated * 0.12));

    return [
      'dc_weekly_review' => ($date->dayOfWeek === Carbon::THURSDAY || $d % 7 === 0) ? 1 : 0,
      'tier_target' => $tier,
      'total_inspectors' => $inspectors,
      'inspections_total_target' => $totalTarget,
      'tandoor_inspections' => $inspections,
      'achievement_rate' => round(min(100, ($inspections / max(1, $totalTarget)) * 100), 1),
      'coverage_mobility_index' => round(72 + ($pct - 70) * 0.45, 1),
      'violations_found' => $violations,
      'fine_imposed' => $fines,
      'fine_imposition_rate' => round(($fines / max(1, $inspections)) * 100, 1),
      'citizen_complaints_received' => $complaints,
      'complaint_resolution_rate' => round(($resolved / max(1, $complaints)) * 100, 1),
      'validation_target' => $validationTarget,
      'validated_inspections' => $validated,
      'approved_validations' => $approved,
      'rejected_validations' => $rejected,
    ];
  }

  private function bread(float $pct, int $d): array
  {
    $inspections = 16 + ($d % 6);

    return [
      'bread_inspections' => $inspections,
      'tier_target' => 12 + ($d % 3),
      'coverage_mobility_index' => round(68 + ($pct - 65) * 0.35, 1),
      'fine_imposed' => max(1, (int) round($inspections * 0.14)),
      'citizen_complaint_action' => max(1, (int) round($inspections * 0.1)),
    ];
  }

  private function priceControl(float $pct, int $d): array
  {
    $inspections = 28 + ($d % 10);

    return [
      'market_inspections' => $inspections,
      'tier_target' => 20,
      'sb_violations' => max(2, (int) round($inspections * 0.08)),
      'citizen_violations' => max(3, (int) round($inspections * 0.12)),
      'fine_imposed' => max(4, (int) round($inspections * 0.18)),
      'compliance_rate' => round($pct, 1),
    ];
  }

  private function roads(float $pct, int $d): array
  {
    $target = 6 + ($d % 3);
    $done = max(1, (int) round($target * $pct / 100));

    return [
      'repair_completed' => $done,
      'weekly_road_target' => $target,
      'sb_points' => 4 + ($d % 3),
      'complaints_resolved' => max(1, $done - 1),
    ];
  }

  private function zebra(float $pct, int $d): array
  {
    $schools = 8 + ($d % 4);
    $inspected = max(3, (int) round($schools * $pct / 100));

    return [
      'schools_to_inspect' => $schools,
      'schools_inspected' => $inspected,
      'markings_done' => max(2, $inspected - 1),
      'sb_points' => 3 + ($d % 2),
      'resolved_points' => max(2, $inspected - 2),
      'compliance_rate' => round($pct, 1),
    ];
  }

  private function streetlights(float $pct, int $d): array
  {
    $inspected = 14 + ($d % 6);
    $reported = 22 + ($d % 8);
    $repaired = max(8, (int) round($reported * $pct / 100));
    $pending = max(0, $reported - $repaired);

    return [
      'roads_inspected' => $inspected,
      'repairs_completed' => $repaired,
      'sb_reported' => $reported,
      'resolved_points' => $repaired,
      'pending_points' => $pending,
      'functional_rate' => round($pct, 1),
    ];
  }

  private function manholes(float $pct, int $d): array
  {
    $ucs = 12;
    $open = 18 + ($d % 6);
    $covered = max(10, (int) round($open * $pct / 100));

    return [
      'ucs_inspected' => $ucs,
      'total_ucs' => 15,
      'manholes_identified' => $open,
      'covers_installed' => $covered,
      'pending_manholes' => max(0, $open - $covered),
      'compliance_rate' => round($pct, 1),
    ];
  }

  private function waterPlants(float $pct, int $d): array
  {
    $total = 24;
    $roPlants = 14;
    $ufPlants = $total - $roPlants;
    $inspect = 18 + ($d % 4);
    $inspected = max(1, (int) round($inspect * $pct / 100));
    $functional = min($total, max(14, (int) round($total * $pct / 100)));
    $blocked = $d % 3;
    $clean = max(12, $functional - 2);
    $changed = 6 + ($d % 3);
    $pending = max(0, 4 - ($d % 3));

    return [
      'total_plants' => $total,
      'total_ro_plants' => $roPlants,
      'total_uf_plants' => $ufPlants,
      'plants_to_inspect' => $inspect,
      'inspected_plants' => $inspected,
      'non_inspected_plants' => max(0, $total - $inspected),
      'plant_coverage_rate' => round(($inspected / $total) * 100, 1),
      'blocked_plants' => $blocked,
      'functional_plants' => $functional,
      'non_functional_plants' => max(0, $total - $functional),
      'clean_plants' => $clean,
      'unclean_plants' => max(0, $total - $clean),
      'ro_filter_changed' => $changed,
      'ro_filter_pending' => $pending,
      'filter_change_rate' => round(($changed / max(1, $changed + $pending)) * 100, 1),
      'approved_validations' => max(4, (int) round($inspected * 0.72)),
      'rejected_validations' => max(1, (int) round($inspected * 0.08)),
    ];
  }

  private function schools(float $pct, int $d, bool $lahore, Carbon $date): array
  {
    $total = $lahore ? 156 : 112;
    $required = $lahore ? 36 : 28;
    $dcTarget = 2;
    $dcVisits = min($dcTarget, 1 + ($d % 2));
    $acTarget = 2;
    $acVisits = min($acTarget, 1 + ($d % 2));
    $dailyVisits = max(2, (int) round(($required / 22) * max(55, $pct) / 100));
    $validationTarget = 10 + ($d % 4);
    $validated = max(5, (int) round($validationTarget * max(65, $pct) / 100));
    $approved = max(4, (int) round($validated * 0.82));
    $rejected = max(1, (int) round($validated * 0.1));
    $issuesClean = max(1, (int) round($dailyVisits * 0.18));
    $issuesTeacher = max(1, (int) round($dailyVisits * 0.12));
    $issuesTlm = max(1, (int) round($dailyVisits * 0.09));
    $issuesFacility = max(1, (int) round($dailyVisits * 0.14));
    $cumulative = min($total, (int) round($total * max(55, $pct) / 100 * ($date->day / max(1, $date->daysInMonth()))));

    return [
      'total_institutions' => $total,
      'institutions_inspected' => $dailyVisits,
      'institutions_not_inspected' => max(0, $total - $cumulative),
      'dc_visits' => $dcVisits,
      'ac_visits' => $acVisits,
      'dc_visit_target' => $dcTarget,
      'ac_visit_target' => $acTarget,
      'required_visits' => max(2, (int) round($required / 22)),
      'institution_visits' => $dailyVisits,
      'school_council_meeting' => ($d % 5 === 0) ? 1 : 0,
      'school_council_activated' => ($d % 3 !== 0) ? 1 : 0,
      'issues_cleanliness' => $issuesClean,
      'issues_teacher_absence' => $issuesTeacher,
      'issues_tlm_shortage' => $issuesTlm,
      'issues_facility_deficiency' => $issuesFacility,
      'facilities_issues' => $issuesClean + $issuesTeacher + $issuesTlm + $issuesFacility,
      'validation_target' => $validationTarget,
      'validations_completed' => $validated,
      'validated_inspections' => $validated,
      'approved_validations' => $approved,
      'rejected_validations' => $rejected,
      'achievement_rate' => round(min(100, ($dailyVisits / max(1, (int) round($required / 22))) * 100), 1),
      'compliance_rate' => round($pct, 1),
    ];
  }

  private function health(float $pct, int $d, bool $lahore, Carbon $date): array
  {
    $total = $lahore ? 48 : 34;
    $required = $lahore ? 28 : 20;
    $dailyVisits = max(2, (int) round(($required / 22) * max(55, $pct) / 100));
    $dcTarget = 2;
    $dcVisits = min($dcTarget, 1 + ($d % 2));
    $acTarget = 2;
    $acVisits = min($acTarget, 1 + ($d % 2));
    $validationTarget = 10 + ($d % 4);
    $validated = max(5, (int) round($validationTarget * max(65, $pct) / 100));
    $approved = max(4, (int) round($validated * 0.84));
    $rejected = max(1, (int) round($validated * 0.1));
    $cumulative = min($total, (int) round($total * max(55, $pct) / 100 * ($date->day / max(1, $date->daysInMonth()))));

    return [
      'total_health_facilities' => $total,
      'dc_visits' => $dcVisits,
      'ac_visits' => $acVisits,
      'dc_visit_target' => $dcTarget,
      'ac_visit_target' => $acTarget,
      'required_visits' => max(2, (int) round($required / 22)),
      'facility_visits' => $dailyVisits,
      'facilities_not_inspected' => max(0, $total - $cumulative),
      'dc_visit_completion' => round(($dcVisits / max(1, $dcTarget)) * 100, 1),
      'ac_visit_completion' => round(($acVisits / max(1, $acTarget)) * 100, 1),
      'health_council_meeting' => ($d % 6 === 0) ? 1 : 0,
      'issues_cleanliness' => max(1, (int) round($dailyVisits * 0.14)),
      'issues_staff_absence' => max(1, (int) round($dailyVisits * 0.1)),
      'issues_medicine_shortage' => max(1, (int) round($dailyVisits * 0.08)),
      'issues_equipment_utilities' => max(1, (int) round($dailyVisits * 0.11)),
      'validation_target' => $validationTarget,
      'validations_completed' => $validated,
      'validated_inspections' => $validated,
      'approved_validations' => $approved,
      'rejected_validations' => $rejected,
      'compliance_rate' => round($pct, 1),
    ];
  }

  private function marriage(float $pct, int $d): array
  {
    $halls = 14;
    $inspected = 6 + ($d % 4);
    $violations = max(1, (int) round($inspected * 0.2));

    return [
      'marriage_hall_inspections' => $inspected,
      'total_halls' => $halls,
      'violations_detected' => $violations,
      'actions_taken' => max(1, $violations - 1),
      'notices_fines' => max(1, (int) round($violations * 0.8)),
      'compliance_rate' => round($pct, 1),
    ];
  }

  private function encroachment(float $pct, int $d): array
  {
    $target = 5 + ($d % 2);
    $cleared = max(2, (int) round($target * $pct / 100));

    return [
      'encroachments_removed' => $cleared,
      'daily_market_target' => $target,
      'sb_points' => 6 + ($d % 3),
      'resolved_points' => $cleared,
      'pending_encroachments' => max(0, $target - $cleared),
      'compliance_rate' => round($pct, 1),
    ];
  }

  private function shops(float $pct, int $d): array
  {
    $markets = 8 + ($d % 3);
    $regulated = max(40, (int) round(58 * $pct / 100));

    return [
      'markets_inspected' => $markets,
      'shops_regulated' => $regulated,
      'violations_found' => max(4, (int) round($regulated * 0.08)),
      'actions_taken' => max(3, (int) round($regulated * 0.06)),
      'compliance_rate' => round($pct, 1),
    ];
  }

  private function strayDogs(float $pct, int $d): array
  {
    return [
      'uc_activities' => 6 + ($d % 3),
      'target_ucs' => 10,
      'complaints_verified' => 8 + ($d % 4),
      'sb_points' => 4 + ($d % 2),
      'actions_taken' => max(3, (int) round(10 * $pct / 100)),
      'followup_required' => max(1, 5 - ($d % 3)),
    ];
  }

  private function wallChalking(float $pct, int $d): array
  {
    $sites = 16 + ($d % 5);
    $removed = max(8, (int) round($sites * $pct / 100));

    return [
      'ucs_inspected' => 10,
      'sites_identified' => $sites,
      'removal_done' => $removed,
      'sb_points' => 5 + ($d % 2),
      'resolved_points' => $removed,
      'compliance_rate' => round($pct, 1),
    ];
  }

  private function graveyards(float $pct, int $d): array
  {
    $target = 4;
    $cleared = max(2, (int) round($target * $pct / 100));

    return [
      'graveyards_cleared' => $cleared,
      'weekly_target' => $target,
      'boundary_wall_issues' => 2 + ($d % 2),
      'encroachment_removed' => max(1, $cleared),
      'bushes_removed' => 3 + ($d % 2),
      'sb_points' => 3,
    ];
  }

  private function ebiz(float $pct, int $d, bool $lahore): array
  {
    $pending = $lahore ? 18 : 26;
    $completed = max(12, (int) round(32 * $pct / 100));

    return [
      'pending_applications' => max(4, $pending - ($d % 5)),
      'applications_completed' => $completed,
      'help_desk_inspections' => 3 + ($d % 2),
      'dc_meeting_held' => 1,
      'disposal_rate' => round($pct, 1),
    ];
  }

  private function decanting(float $pct, int $d): array
  {
    $inspected = 12 + ($d % 4);
    $violations = max(2, (int) round($inspected * 0.22));

    return [
      'stations_inspected' => $inspected,
      'violations_found' => $violations,
      'actions_taken' => max(1, $violations - 1),
      'sb_points' => 4,
      'enforcement_actions' => max(1, (int) round($violations * 0.75)),
      'compliance_rate' => round($pct, 1),
    ];
  }

  private function suthra(float $pct, int $d): array
  {
    return [
      'dc_inspections' => 2,
      'ac_uc_inspections' => 8 + ($d % 4),
      'hr_attendance' => round(82 + ($pct - 70) * 0.2, 1),
      'vehicles_in_field' => 6 + ($d % 3),
      'containers_placed' => 14 + ($d % 4),
      'heaps_cleared' => max(10, (int) round(18 * $pct / 100)),
      'sb_points' => 5 + ($d % 2),
    ];
  }

  private function greenbelts(float $pct, int $d): array
  {
    $total = 16;

    return [
      'parks_maintained' => max(8, (int) round($total * $pct / 100)),
      'total_parks' => $total,
      'greenbelts_maintained' => 5 + ($d % 3),
      'beautification' => 2 + ($d % 2),
      'sb_points' => 3,
      'compliance_rate' => round($pct, 1),
    ];
  }

  private function drains(float $pct, int $d): array
  {
    $reported = 14 + ($d % 5);
    $cleared = max(8, (int) round($reported * $pct / 100));

    return [
      'ucs_inspected' => 11,
      'total_ucs' => 14,
      'blockages_reported' => $reported,
      'blockages_cleared' => $cleared,
      'stagnant_water' => max(0, 6 - ($d % 4)),
      'compliance_rate' => round($pct, 1),
    ];
  }

  private function busTerminals(float $pct, int $d): array
  {
    $required = 6;
    $visits = max(3, (int) round($required * $pct / 100));

    return [
      'ac_visits' => $visits,
      'required_visits' => $required,
      'fare_display_checked' => $visits,
      'waiting_area_checked' => max(2, $visits - 1),
      'facilities_checked' => max(2, $visits - 1),
      'cleanliness_checked' => $visits,
      'sb_points' => 3 + ($d % 2),
    ];
  }

  private function complaints(float $pct, int $d, bool $lahore): array
  {
    $received = $lahore ? 42 : 36;
    $resolved = max(20, (int) round($received * $pct / 100));
    $pending = max(0, $received - $resolved);

    return [
      'complaints_received' => $received,
      'complaints_resolved' => $resolved,
      'pending_complaints' => $pending,
      'resolution_rate' => round($pct, 1),
      'overdue_complaints' => max(0, (int) round($pending * 0.25)),
      'followups' => 4 + ($d % 3),
    ];
  }

  private function generic(float $pct, int $d): array
  {
    return [
      'inspections' => 10 + ($d % 5),
      'actions_taken' => max(4, (int) round(12 * $pct / 100)),
      'compliance_rate' => round($pct, 1),
    ];
  }

  /** @param array<string, int|float> $snapshot */
  private function withOperationalMetrics(string $slug, array $snapshot, float $pct): array
  {
    [$target, $completed] = match ($slug) {
      'price-of-roti' => [$snapshot['inspections_total_target'], $snapshot['tandoor_inspections']],
      'price-of-plain-bakery-bread' => [$this->targetFromCompleted($snapshot['bread_inspections'], $pct), $snapshot['bread_inspections']],
      'price-control-of-essential-commodities' => [$this->targetFromCompleted($snapshot['market_inspections'], $pct), $snapshot['market_inspections']],
      'repair-of-small-roads-in-both-urban-and-rural-areas' => [$snapshot['weekly_road_target'], $snapshot['repair_completed']],
      'zebra-crossings' => [$snapshot['schools_to_inspect'], $snapshot['schools_inspected']],
      'dysfunctional-streetlights' => [$snapshot['sb_reported'], $snapshot['repairs_completed']],
      'covering-of-manholes' => [$snapshot['manholes_identified'], $snapshot['covers_installed']],
      'functional-and-clean-water-filtration-plants' => [$snapshot['plants_to_inspect'], $snapshot['inspected_plants']],
      'inspection-of-educational-institutions' => [
        $snapshot['required_visits'],
        round($snapshot['required_visits'] * $pct / 100, 1),
      ],
      'inspection-of-health-facilities' => [
        $snapshot['required_visits'],
        round($snapshot['required_visits'] * $pct / 100, 1),
      ],
      'violation-of-marriage-functions-act' => [$this->targetFromCompleted($snapshot['marriage_hall_inspections'], $pct), $snapshot['marriage_hall_inspections']],
      'anti-encroachment-campaign' => [$snapshot['daily_market_target'], $snapshot['encroachments_removed']],
      'regulation-of-shops-and-handcarts' => [$this->targetFromCompleted($snapshot['markets_inspected'], $pct), $snapshot['markets_inspected']],
      'stray-dogs' => [$snapshot['target_ucs'], $snapshot['uc_activities']],
      'removal-of-wall-chalking' => [$snapshot['sites_identified'], $snapshot['removal_done']],
      'graveyards' => [$snapshot['weekly_target'], $snapshot['graveyards_cleared']],
      'e-biz' => [$snapshot['pending_applications'] + $snapshot['applications_completed'], $snapshot['applications_completed']],
      'illegal-decanting' => [$this->targetFromCompleted($snapshot['stations_inspected'], $pct), $snapshot['stations_inspected']],
      'suthra-punjab-campaign' => [
        $this->targetFromCompleted($snapshot['dc_inspections'] + $snapshot['ac_uc_inspections'], $pct),
        $snapshot['dc_inspections'] + $snapshot['ac_uc_inspections'],
      ],
      'maintenance-of-greenbelts' => [$snapshot['total_parks'], $snapshot['parks_maintained']],
      'maintenance-of-drains-and-sewerage-lines' => [$snapshot['blockages_reported'], $snapshot['blockages_cleared']],
      'bus-terminals' => [$snapshot['required_visits'], $snapshot['ac_visits']],
      'chief-ministers-complaint-cell' => [$snapshot['complaints_received'], $snapshot['complaints_resolved']],
      default => [$this->targetFromCompleted($snapshot['inspections'], $pct), $snapshot['inspections']],
    };

    $snapshot['operational_target'] = max(1, (int) round($target));
    $snapshot['operational_completed'] = round(max(0, min(
      $snapshot['operational_target'],
      $completed
    )), 1);

    return $snapshot;
  }

  private function targetFromCompleted(int|float $completed, float $pct): int
  {
    return max(1, (int) ceil($completed / max(.45, min(.95, $pct / 100))));
  }

  /** @param array<string, int|float> $snapshot */
  private function withScopeScale(array $snapshot, string $username): array
  {
    $multiplier = match (true) {
      str_starts_with($username, 'dc.lahore') => 6,
      str_starts_with($username, 'dc.layyah') => 4,
      str_starts_with($username, 'com.lahore') => 20,
      str_starts_with($username, 'com.dgkhan') => 16,
      in_array($username, ['cs.pmru', 'super_admin'], true) => 60,
      default => 1,
    };

    $snapshot['operational_target'] *= $multiplier;
    $snapshot['operational_completed'] *= $multiplier;

    return $snapshot;
  }

  private function remarks(string $slug, Carbon $date, string $periodType, bool $lahore, float $pct): string
  {
    $area = $lahore ? 'Lahore tehsil' : 'Layyah tehsil';
    $status = $this->performanceStatus($pct);

    return sprintf(
      '%s field report (%s) for %s on %s - %s at %.1f%% achievement.',
      ucfirst($periodType),
      str_replace('-', ' ', $slug),
      $area,
      $date->format('d M Y'),
      $status,
      $pct
    );
  }
}
