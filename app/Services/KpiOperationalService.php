<?php

namespace App\Services;

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

    /** @var list<string> */
    private const DAILY_ACTIVITY_KPIS = [
        'price-of-roti',
        'price-of-plain-bakery-bread',
        'price-control-of-essential-commodities',
        'anti-encroachment-campaign',
        'stray-dogs',
        'regulation-of-shops-and-handcarts',
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
    ): array {
        $operationalSubmissions = $this->operationalSubmissions($submissions, $user, $request);

        if (in_array($card->slug, self::WEEKLY_VISIT_KPIS, true)) {
            $target = $this->visitTarget($user, $request, $operationalSubmissions);
            // Achieved is supplied from kpi_inspections (approved + pending_review) for scope/period.
            $completed = (float) max(0, $inspectionAchieved ?? 0);

            return ['target' => $target, 'completed' => $completed];
        }

        if (in_array($card->slug, self::DAILY_ACTIVITY_KPIS, true)) {
            return $this->dailyActivityTotals($operationalSubmissions, $request, $fields);
        }

        return [
            'target' => $this->snapshotSum($operationalSubmissions, $fields['target']),
            'completed' => $this->snapshotSum($operationalSubmissions, $fields['completed']),
        ];
    }

    /** @return array{target: float, completed: float} */
    private function dailyActivityTotals(Collection $submissions, Request $request, array $fields): array
    {
        $params = $this->periodService->resolvedParams($request);
        $completed = $this->snapshotSum($submissions, $fields['completed']);
        $dailyTarget = (float) $submissions->max(
            fn ($submission) => (float) data_get($submission->metric_snapshot, $fields['target'], 0)
        );

        if ($dailyTarget <= 0) {
            $dailyTarget = (float) $submissions->avg(
                fn ($submission) => (float) data_get($submission->metric_snapshot, $fields['target'], 0)
            );
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

        return [
            'target' => round($dailyTarget * $days, 1),
            'completed' => round($completed, 1),
        ];
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

    private function visitTarget(User $user, Request $request, Collection $submissions): float
    {
        $weeklyTarget = $this->weeklyVisitTargetForScope($user, $request);
        $params = $this->periodService->resolvedParams($request);

        return match ($params['period_type']) {
            'daily' => $this->dailyVisitTarget($weeklyTarget, $params['date']),
            'weekly' => $weeklyTarget,
            'monthly' => $weeklyTarget * $this->weeksInMonth($params),
            'yearly' => $weeklyTarget * 52,
            default => $weeklyTarget * $this->weeksCoveredBy($submissions),
        };
    }

    private function weeklyVisitTargetForScope(User $user, Request $request): int
    {
        if ($request->filled('geo_tehsil')) {
            return 2;
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
