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

        return [
            'week_no' => $weekNo,
            'week_start' => $start->toDateString(),
            'week_end' => $end->toDateString(),
            'week_label' => $start->format('d M, Y').' - '.$end->format('d M, Y'),
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

    public function applyToQuery(Builder $query, Request $request, string $dateColumn = 'submission_date'): Builder
    {
        $periodType = $request->get('period_type');

        if ($periodType === 'daily' && $request->filled('date')) {
            return $query->whereDate($dateColumn, $request->date('date'));
        }

        if ($periodType === 'weekly') {
            $weekNo = $request->get('week_no') ?: $this->latestCompletedWeekNo();
            $range = $this->getWeekDateRange((string) $weekNo);
            if ($range['start'] && $range['end']) {
                return $query->whereBetween($dateColumn, [$range['start']->toDateString(), $range['end']->toDateString()]);
            }
        }

        if ($periodType === 'monthly' || $request->filled('month')) {
            $year = (int) ($request->year ?: now()->year);
            $query->whereYear($dateColumn, $year);
            if ($request->filled('month')) {
                $query->whereMonth($dateColumn, (int) $request->month);
            }

            return $query;
        }

        if ($periodType === 'yearly' || $request->filled('year')) {
            return $query->whereYear($dateColumn, (int) ($request->year ?: now()->year));
        }

        if ($request->filled('year')) {
            $query->whereYear($dateColumn, (int) $request->year);
            if ($request->filled('month')) {
                $query->whereMonth($dateColumn, (int) $request->month);
            }
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
            'default_week_no' => $this->latestCompletedWeekNo(),
        ];
    }

    public function state(Request $request): array
    {
        return [
            'period_type' => $request->get('period_type', ''),
            'month' => $request->get('month', ''),
            'year' => $request->get('year', now()->year),
            'week_no' => $request->get('week_no', ''),
            'date' => $request->get('date', ''),
        ];
    }

    public function label(Request $request): string
    {
        $parts = [];

        if ($request->filled('period_type')) {
            $parts[] = ucfirst($request->period_type);
        }

        if ($request->get('period_type') === 'weekly') {
            $weekNo = $request->get('week_no') ?: $this->latestCompletedWeekNo();
            $parts[] = $this->getWeekDateRange((string) $weekNo)['label_with_year'] ?? 'Week '.$weekNo;
        }

        if ($request->filled('date')) {
            $parts[] = Carbon::parse($request->date)->format('d M Y');
        }

        if ($request->filled('month')) {
            $parts[] = Carbon::create(null, (int) $request->month)->format('F');
        }

        if ($request->filled('year')) {
            $parts[] = $request->year;
        }

        return $parts ? implode(' · ', $parts) : 'All periods';
    }

    private function thursdayWeekStart(Carbon $date): Carbon
    {
        $cursor = $date->copy()->startOfDay();
        while ($cursor->dayOfWeek !== Carbon::THURSDAY) {
            $cursor->subDay();
        }

        return $cursor;
    }

    private function latestCompletedWeekNo(): string
    {
        return $this->weekRangeForDate(now()->subWeek())['week_no'];
    }
}
