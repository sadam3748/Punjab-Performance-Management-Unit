<?php

namespace App\Services;

use App\Data\KpiFrequencyConfig;
use App\Models\KpiCard;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class KpiOperationalService
{
    private const WEEKLY_VISIT_KPIS = [
        'inspection-of-health-facilities',
        'inspection-of-educational-institutions',
    ];

    public function __construct(private readonly KpiPeriodService $periodService) {}

    /** @return array{target: float, completed: float} */
    public function totals(
        KpiCard $card,
        Collection $submissions,
        User $user,
        Request $request,
        array $fields,
        ?int $inspectionAchieved = null,
        ?array $activeScope = null,
    ): array {
        $operationalSubmissions = $this->operationalSubmissions($submissions, $user, $request);

        if (in_array($card->slug, self::WEEKLY_VISIT_KPIS, true)) {
            $target = $this->visitTarget($user, $request, $operationalSubmissions, $card, $activeScope);
            // Achieved is supplied from kpi_inspections (approved + pending_review) for scope/period.
            $completed = (float) max(0, $inspectionAchieved ?? 0);

            return ['target' => $target, 'completed' => $completed];
        }

        if (KpiFrequencyConfig::isDaily($card->slug)) {
            return $this->dailyActivityTotals($card, $user, $operationalSubmissions, $request, $fields, $inspectionAchieved);
        }

        if (KpiFrequencyConfig::isWeekly($card->slug) && KpiFrequencyConfig::isOperationalInspectionKpi($card->slug)) {
            return $this->weeklyInspectionTotals($card, $user, $request, $inspectionAchieved);
        }

        $target = $this->snapshotSum($operationalSubmissions, $fields['target']);
        $completed = $inspectionAchieved !== null
            ? (float) max(0, $inspectionAchieved)
            : $this->snapshotSum($operationalSubmissions, $fields['completed']);

        if ($inspectionAchieved !== null && $target <= 0) {
            $target = $completed;
        }

        return [
            'target' => $target,
            'completed' => $completed,
        ];
    }

    /** @return array{target: float, completed: float} */
    private function dailyActivityTotals(KpiCard $card, User $user, Collection $submissions, Request $request, array $fields, ?int $inspectionAchieved = null): array
    {
        $params = $this->periodService->resolvedParams($request);
        $completed = $inspectionAchieved !== null
            ? (float) max(0, $inspectionAchieved)
            : $this->snapshotSum($submissions, $fields['completed']);
        $dailyTarget = (float) $submissions->max(
            fn ($submission) => (float) data_get($submission->metric_snapshot, $fields['target'], 0)
        );

        if ($dailyTarget <= 0) {
            $dailyTarget = (float) $submissions->avg(
                fn ($submission) => (float) data_get($submission->metric_snapshot, $fields['target'], 0)
            );
        }

        if ($dailyTarget <= 0 && $inspectionAchieved !== null) {
            $dailyTarget = (float) max(1, $inspectionAchieved);
        }

        if ($inspectionAchieved !== null && $params['period_type'] === 'daily') {
            $dailyTarget = (float) $this->dailyInspectionTargetForScope($card->slug, $user, $request, $dailyTarget);
        }

        $days = match ($params['period_type']) {
            'daily' => 1,
            'weekly' => 7,
            'monthly' => (int) Carbon::create(
                (int) ($params['year'] ?: now()->year),
                (int) ($params['month'] ?: now()->month),
                1
            )->daysInMonth,
            'yearly' => 365,
            default => max(1, $submissions->count()),
        };

        $target = round($dailyTarget * $days, 1);

        return [
            'target' => $target,
            'completed' => round(min($completed, $target > 0 ? $target : $completed), 1),
        ];
    }

    /** @return array{target: float, completed: float} */
    private function weeklyInspectionTotals(KpiCard $card, User $user, Request $request, ?int $inspectionAchieved = null): array
    {
        $params = $this->periodService->resolvedParams($request);
        $weeklyTarget = (float) $this->weeklyInspectionTargetForSlug($card->slug, $user, $request);
        $target = match ($params['period_type']) {
            'weekly' => $weeklyTarget,
            'monthly' => $weeklyTarget * $this->weeksInMonth($params),
            'yearly' => $weeklyTarget * 52,
            default => $weeklyTarget,
        };
        $completed = (float) max(0, $inspectionAchieved ?? 0);

        return [
            'target' => round($target, 1),
            'completed' => round(min($completed, $target > 0 ? $target : $completed), 1),
        ];
    }

    private function weeklyInspectionTargetForSlug(string $slug, User $user, Request $request): int
    {
        $slug = KpiFrequencyConfig::normalize($slug);
        $tehsils = max(1, $this->dailyTargetTehsilMultiplier($user, $request));

        $perTehsil = match ($slug) {
            'repair-of-small-roads-in-both-urban-and-rural-areas' => 1,
            'dysfunctional-streetlights' => 2,
            'covering-of-manholes' => 3,
            'functional-and-clean-water-filtration-plants' => 2,
            'violation-of-marriage-functions-act' => 3,
            'zebra-crossings' => 5,
            'illegal-decanting' => 15,
            'suthra-punjab-campaign' => in_array($user->role?->slug, ['dc'], true) ? 2 : 4,
            'maintenance-of-greenbelts' => 3,
            'maintenance-of-drains-and-sewerage-lines' => 4,
            'graveyards' => 2,
            'bus-terminals' => 2,
            'e-biz' => 6,
            default => 1,
        };

        if (in_array($user->role?->slug, ['ac', 'field_user'], true) || $request->filled('geo_tehsil')) {
            return $perTehsil;
        }

        return $perTehsil * $tehsils;
    }

    private function dailyInspectionTargetForScope(string $slug, User $user, Request $request, float $fallback): int
    {
        $base = match ($slug) {
            'price-of-roti' => 6,
            'price-of-plain-bakery-bread' => 3,
            'price-control-of-essential-commodities' => 21,
            'anti-encroachment-campaign' => 1,
            'regulation-of-shops-and-handcarts' => 1,
            'stray-dogs' => 1,
            'removal-of-wall-chalking' => 1,
            'chief-ministers-complaint-cell' => 12,
            default => max(1, (int) round($fallback)),
        };

        return $base * max(1, $this->dailyTargetTehsilMultiplier($user, $request));
    }

    private function dailyTargetTehsilMultiplier(User $user, Request $request): int
    {
        if ($request->filled('geo_tehsil') || in_array($user->role?->slug, ['ac', 'field_user'], true)) {
            return 1;
        }

        if ($request->filled('geo_district')) {
            return $this->activeTehsilCountForDistrict((int) $request->input('geo_district'));
        }

        if ($request->filled('geo_division')) {
            return $this->activeTehsilCountForDivision((int) $request->input('geo_division'));
        }

        return match ($user->role?->slug) {
            'dc' => $this->activeTehsilCountForDistrict((int) $user->district_id),
            'commissioner' => $this->activeTehsilCountForDivision((int) $user->division_id),
            default => max(1, DB::table('tehsils')->where('is_active', true)->count()),
        };
    }

    private function activeTehsilCountForDistrict(int $districtId): int
    {
        return max(1, DB::table('tehsils')
            ->where('district_id', $districtId)
            ->where('is_active', true)
            ->count());
    }

    private function activeTehsilCountForDivision(int $divisionId): int
    {
        $districtIds = DB::table('districts')
            ->where('division_id', $divisionId)
            ->where('is_active', true)
            ->pluck('id');

        return max(1, DB::table('tehsils')
            ->whereIn('district_id', $districtIds)
            ->where('is_active', true)
            ->count());
    }

    private function operationalSubmissions(Collection $submissions, User $user, Request $request): Collection
    {
        $level = match (true) {
            $request->filled('geo_tehsil') => 'tehsil',
            $request->filled('geo_district') => 'district',
            $request->filled('geo_division') => 'division',
            default => match ($user->role?->slug) {
                'ac', 'field_user' => 'tehsil',
                'dc' => 'district',
                'commissioner' => 'division',
                default => 'province',
            },
        };

        $atLevel = $submissions->where('area_level', $level);
        $userRows = $atLevel->where('user_id', $user->id);

        if (! $request->filled('geo_tehsil')
            && ! $request->filled('geo_district')
            && ! $request->filled('geo_division')
            && $userRows->isNotEmpty()) {
            return $userRows->values();
        }

        return $atLevel->isNotEmpty() ? $atLevel->values() : $submissions;
    }

    private function visitTarget(User $user, Request $request, Collection $submissions, ?KpiCard $card = null, ?array $activeScope = null): float
    {
        $weeklyTarget = $this->weeklyVisitTargetForScope($user, $request, $card, $activeScope);
        $params = $this->periodService->resolvedParams($request);

        return match ($params['period_type']) {
            'daily' => $this->dailyVisitTarget($weeklyTarget, $params['date']),
            'weekly' => $weeklyTarget,
            'monthly' => $weeklyTarget * $this->weeksInMonth($params),
            'yearly' => $weeklyTarget * 52,
            default => $weeklyTarget * $this->weeksCoveredBy($submissions),
        };
    }

    private function weeklyVisitTargetForScope(User $user, Request $request, ?KpiCard $card = null, ?array $activeScope = null): int
    {
        if ($request->filled('geo_tehsil')) {
            return 2;
        }

        if ($card?->slug === 'inspection-of-health-facilities') {
            if ($request->filled('geo_district')) {
                return $this->districtWeeklyTarget((int) $request->input('geo_district'));
            }

            if ($request->filled('geo_division')) {
                return $this->divisionWeeklyTarget((int) $request->input('geo_division'));
            }

            return match ($user->role?->slug) {
                'ac', 'field_user' => 2,
                'dc' => $this->districtWeeklyTarget((int) $user->district_id),
                'commissioner' => $this->divisionWeeklyTarget((int) $user->division_id),
                default => $this->provinceWeeklyTarget(),
            };
        }

        if ($request->filled('geo_district')) {
            return $this->districtWeeklyTarget((int) $request->input('geo_district'));
        }

        if ($request->filled('geo_division')) {
            return $this->divisionWeeklyTarget((int) $request->input('geo_division'));
        }

        return match ($user->role?->slug) {
            'ac', 'field_user' => 2,
            'dc' => $this->districtWeeklyTarget((int) $user->district_id),
            'commissioner' => $this->divisionWeeklyTarget((int) $user->division_id),
            default => $this->provinceWeeklyTarget(),
        };
    }

    private function districtWeeklyTarget(int $districtId): int
    {
        $tehsils = DB::table('tehsils')
            ->where('district_id', $districtId)
            ->where('is_active', true)
            ->count();

        return ($tehsils * 2) + 2;
    }

    private function divisionWeeklyTarget(int $divisionId): int
    {
        $districtIds = DB::table('districts')
            ->where('division_id', $divisionId)
            ->where('is_active', true)
            ->pluck('id');

        $tehsils = DB::table('tehsils')
            ->whereIn('district_id', $districtIds)
            ->where('is_active', true)
            ->count();

        return ($tehsils * 2) + ($districtIds->count() * 2);
    }

    private function provinceWeeklyTarget(): int
    {
        $districts = DB::table('districts')->where('is_active', true)->count();
        $tehsils = DB::table('tehsils')->where('is_active', true)->count();

        return ($tehsils * 2) + ($districts * 2);
    }

    private function dailyVisitTarget(int $weeklyTarget, mixed $date): int
    {
        $day = $date ? Carbon::parse($date) : now();

        return in_array($day->dayOfWeek, [Carbon::MONDAY, Carbon::THURSDAY], true)
            ? (int) ceil($weeklyTarget / 2)
            : 0;
    }

    private function weeksInMonth(array $params): int
    {
        $year = (int) ($params['year'] ?: now()->year);
        $month = (int) ($params['month'] ?: now()->month);

        return (int) ceil(Carbon::create($year, $month, 1)->daysInMonth / 7);
    }

    private function weeksCoveredBy(Collection $submissions): int
    {
        $dates = $submissions->pluck('submission_date')->filter();
        if ($dates->isEmpty()) {
            return 1;
        }

        $start = Carbon::parse($dates->min())->startOfDay();
        $end = Carbon::parse($dates->max())->endOfDay();

        return max(1, (int) ceil(($start->diffInDays($end) + 1) / 7));
    }

    private function snapshotSum(Collection $submissions, string $field): float
    {
        return round((float) $submissions->sum(
            fn ($submission) => (float) data_get($submission->metric_snapshot, $field, 0)
        ), 1);
    }
}
