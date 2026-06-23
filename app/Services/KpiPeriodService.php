<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class KpiPeriodService
{
    public function weekRangeForDate(Carbon $date): array
    {
        $start = $this->thursdayWeekStart($date);
        $end = $start->copy()->addDays(6)->endOfDay();
        $weekNo = sprintf('%d%02d', (int) $start->isoFormat('GGGG'), (int) $start->isoWeek());
        $weekNum = (int) $start->isoWeek();

        return [
            'week_no' => $weekNo,
            'week_num' => $weekNum,
            'week_start' => $start->toDateString(),
            'week_end' => $end->toDateString(),
            'week_label' => sprintf(
                'W%02d · Thu %s – Wed %s',
                $weekNum,
                $start->format('d M'),
                $end->format('d M Y')
            ),
        ];
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
            'label_with_year' => $start->format('d M, Y').' - '.$end->format('d M, Y'),
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

    public function filterOptions(): array
    {
        $weeks = [];
        $cursor = $this->thursdayWeekStart(now());

        for ($i = 0; $i < 12; $i++) {
            $range = $this->weekRangeForDate($cursor->copy()->subWeeks($i));
            $weeks[$range['week_no']] = $range['week_label'];
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
            $weekNo = (string) ($params['week_no'] ?: $this->currentWeekNo());
            $range = $this->getWeekDateRange($weekNo);
            $weekNum = $this->weekNumberFromCode($weekNo);

            return sprintf('Week W%02d · %s', $weekNum, $range['label_with_year']);
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
