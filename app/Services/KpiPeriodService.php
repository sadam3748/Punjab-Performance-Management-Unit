<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class KpiPeriodService
{
    public function weekRangeForDate(Carbon $date, ?int $monthIndex = null): array
    {
        $start = $this->thursdayWeekStart($date);
        $end = $start->copy()->addDays(6)->endOfDay();
        $weekNo = sprintf('%d%02d', (int) $start->isoFormat('GGGG'), (int) $start->isoWeek());

        return [
            'week_no' => $weekNo,
            'week_num' => (int) $start->isoWeek(),
            'week_start' => $start->toDateString(),
            'week_end' => $end->toDateString(),
            'week_label' => $this->formatWeekLabel($start, $end, $monthIndex),
        ];
    }

    public function formatWeekLabel(Carbon $start, Carbon $end, ?int $monthIndex = null, bool $withWeekdays = false): string
    {
        $prefix = $monthIndex !== null ? sprintf('Week %d · ', $monthIndex) : 'Week · ';

        if ($withWeekdays) {
            return $prefix.sprintf(
                '%s %s – %s %s %s',
                $start->format('D'),
                $start->format('d M'),
                $end->format('D'),
                $end->format('d M'),
                $end->format('Y')
            );
        }

        return $prefix.sprintf(
            '%s – %s',
            $start->format('d M'),
            $end->format('d M Y')
        );
    }

    public function getWeekDateRange(string $weekNo): array
    {
        if (! preg_match('/^(\d{4})(\d{2})$/', $weekNo, $m)) {
            return ['start' => null, 'end' => null, 'label_with_year' => '—'];
        }

        $start = Carbon::now()->setISODate((int) $m[1], (int) $m[2], Carbon::THURSDAY)->startOfDay();
        $end = $start->copy()->addDays(6)->endOfDay();

        return [
            'start' => $start,
            'end' => $end,
            'label_with_year' => $this->formatWeekLabel($start, $end, null, true),
        ];
    }

    public function currentWeekNo(): string
    {
        return $this->weekRangeForDate(now())['week_no'];
    }

    public function defaultParams(): array
    {
        return [
            'period_type' => 'weekly',
            'week_no' => $this->currentWeekNo(),
            'month' => (string) now()->month,
            'year' => (string) now()->year,
            'date' => now()->toDateString(),
        ];
    }

    public function usesDefaults(Request $request): bool
    {
        return ! $request->has('period_type');
    }

    public function resolvedParams(Request $request): array
    {
        if ($this->usesDefaults($request)) {
            return $this->defaultParams();
        }

        return [
            'period_type' => (string) $request->get('period_type', ''),
            'month' => $request->get('month', ''),
            'year' => (string) ($request->get('year') ?: now()->year),
            'week_no' => $request->get('week_no', ''),
            'date' => $request->get('date', ''),
        ];
    }

    public function applyToQuery(Builder $query, Request $request, string $dateColumn = 'submission_date'): Builder
    {
        $params = $this->resolvedParams($request);
        $periodType = $params['period_type'];

        if ($periodType === 'daily' && ! empty($params['date'])) {
            return $query->whereDate($dateColumn, Carbon::parse($params['date']));
        }

        if ($periodType === 'weekly') {
            $weekNo = $params['week_no'] ?: $this->currentWeekNo();
            $range = $this->getWeekDateRange((string) $weekNo);
            if ($range['start'] && $range['end']) {
                return $query->whereBetween($dateColumn, [$range['start']->toDateString(), $range['end']->toDateString()]);
            }
        }

        if ($periodType === 'monthly' || (! $periodType && ! empty($params['month']))) {
            $year = (int) ($params['year'] ?: now()->year);
            $query->whereYear($dateColumn, $year);
            if (! empty($params['month'])) {
                $query->whereMonth($dateColumn, (int) $params['month']);
            }

            return $query;
        }

        if ($periodType === 'yearly' || (! $periodType && ! empty($params['year']))) {
            return $query->whereYear($dateColumn, (int) ($params['year'] ?: now()->year));
        }

        return $query;
    }

    public function filterOptions(?int $year = null, ?int $month = null): array
    {
        $year = $year ?: (int) now()->year;
        $month = $month ?: (int) now()->month;
        $weeks = $this->weeksForMonth($year, $month);

        if ($weeks === []) {
            $cursor = $this->thursdayWeekStart(now());
            for ($i = 0; $i < 12; $i++) {
                $range = $this->weekRangeForDate($cursor->copy()->subWeeks($i));
                $weeks[$range['week_no']] = $range['week_label'];
            }
        }

        return [
            'months' => collect(range(1, 12))->mapWithKeys(fn ($m) => [$m => Carbon::create(null, $m)->format('F')]),
            'years' => collect(range(now()->year - 2, now()->year))->reverse()->values(),
            'period_types' => ['daily', 'weekly', 'monthly', 'yearly'],
            'weeks' => $weeks,
            'default_week_no' => $this->currentWeekNo(),
            'defaults' => $this->defaultParams(),
        ];
    }

    /** @return array<string, string> */
    public function weeksForMonth(int $year, int $month): array
    {
        $weeks = [];
        $monthStart = Carbon::create($year, $month, 1)->startOfDay();
        $monthEnd = $monthStart->copy()->endOfMonth();
        $cursor = $this->thursdayWeekStart($monthStart->copy());
        $weekIndex = 1;

        while ($cursor->lte($monthEnd->copy()->addDays(7))) {
            $range = $this->weekRangeForDate($cursor);
            $weekStart = Carbon::parse($range['week_start']);
            $weekEnd = Carbon::parse($range['week_end']);

            if ($weekEnd->gte($monthStart) && $weekStart->lte($monthEnd)) {
                $weeks[$range['week_no']] = $this->formatWeekLabel($weekStart, $weekEnd, $weekIndex, true);
                $weekIndex++;
            }

            $cursor->addWeek();
            if ($cursor->gt($monthEnd->copy()->addMonth())) {
                break;
            }
        }

        return $weeks;
    }

    public function weekDisplayLabel(string $weekNo, ?int $year = null, ?int $month = null): string
    {
        $year = $year ?: (int) now()->year;
        $month = $month ?: (int) now()->month;
        $weeks = $this->weeksForMonth($year, $month);

        if (isset($weeks[$weekNo])) {
            return $weeks[$weekNo];
        }

        $range = $this->getWeekDateRange($weekNo);

        if ($range['start'] && $range['end']) {
            return $this->formatWeekLabel($range['start'], $range['end'], null, true);
        }

        return 'Week · —';
    }

    public function state(Request $request): array
    {
        return $this->resolvedParams($request);
    }

    public function label(Request $request): string
    {
        return $this->description($request);
    }

    public function typeLabel(string $type): string
    {
        return match ($type) {
            'daily' => 'Today',
            'weekly' => 'Weekly',
            'monthly' => 'Monthly',
            'yearly' => 'Yearly',
            default => ucfirst($type),
        };
    }

    public function description(Request $request): string
    {
        $params = $this->resolvedParams($request);
        $type = $params['period_type'];

        if ($type === 'daily') {
            $date = ! empty($params['date']) ? Carbon::parse($params['date']) : now();

            return 'Today · '.$date->format('l, d M Y');
        }

        if ($type === 'weekly') {
            $year = (int) ($params['year'] ?: now()->year);
            $month = ! empty($params['month']) ? (int) $params['month'] : now()->month;
            $weekNo = (string) ($params['week_no'] ?: $this->currentWeekNo());

            return $this->weekDisplayLabel($weekNo, $year, $month);
        }

        if ($type === 'monthly') {
            $year = (int) ($params['year'] ?: now()->year);
            $month = ! empty($params['month']) ? (int) $params['month'] : now()->month;
            $start = Carbon::create($year, $month, 1);
            $end = $start->copy()->endOfMonth();

            return sprintf(
                'Monthly · %s (%s – %s)',
                $start->format('F Y'),
                $start->format('d M Y'),
                $end->format('d M Y')
            );
        }

        if ($type === 'yearly') {
            $year = (int) ($params['year'] ?: now()->year);

            return 'Yearly · 1 Jan '.$year.' – 31 Dec '.$year;
        }

        return 'All periods · Complete available data';
    }

    public function queryString(Request $request): string
    {
        return http_build_query(array_filter(
            $this->resolvedParams($request),
            fn ($value) => $value !== '' && $value !== null
        ));
    }

    private function weekNumberFromCode(string $weekNo): int
    {
        if (preg_match('/\d{4}(\d{2})$/', $weekNo, $matches)) {
            return (int) $matches[1];
        }

        return 0;
    }

    private function thursdayWeekStart(Carbon $date): Carbon
    {
        $cursor = $date->copy()->startOfDay();
        while ($cursor->dayOfWeek !== Carbon::THURSDAY) {
            $cursor->subDay();
        }

        return $cursor;
    }
}
