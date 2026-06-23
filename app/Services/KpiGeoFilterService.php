<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class KpiGeoFilterService
{
    public function __construct(private readonly KpiScopeService $scopeService) {}

  /** @return array<string, mixed> */
    public function options(User $user): array
    {
        $role = $user->role?->slug;

        $divisions = collect();
        $districts = collect();
        $tehsils = collect();

        if (in_array($role, ['super_admin', 'chief_secretary', 'pmru_user', 'viewer'], true)) {
            $divisions = DB::table('divisions')->orderBy('name')->pluck('name', 'id');
            $districts = DB::table('districts')->orderBy('name')->pluck('name', 'id');
            $tehsils = DB::table('tehsils')->orderBy('name')->pluck('name', 'id');
        } elseif ($role === 'commissioner') {
            $districts = DB::table('districts')
                ->where('division_id', $user->division_id)
                ->orderBy('name')
                ->pluck('name', 'id');
            $tehsils = DB::table('tehsils')
                ->whereIn('district_id', $districts->keys())
                ->orderBy('name')
                ->pluck('name', 'id');
        } elseif ($role === 'dc') {
            $districts = DB::table('districts')
                ->where('id', $user->district_id)
                ->pluck('name', 'id');
            $tehsils = DB::table('tehsils')
                ->where('district_id', $user->district_id)
                ->orderBy('name')
                ->pluck('name', 'id');
        }

        return [
            'divisions' => $divisions,
            'districts' => $districts,
            'tehsils' => $tehsils,
            'show_division' => $divisions->isNotEmpty(),
            'show_district' => $districts->isNotEmpty() && $role !== 'ac' && $role !== 'field_user',
            'show_tehsil' => $tehsils->isNotEmpty() && ! in_array($role, ['ac', 'field_user'], true),
            'selected' => [
                'division_id' => request('geo_division'),
                'district_id' => request('geo_district'),
                'tehsil_id' => request('geo_tehsil'),
                'date_from' => request('geo_date_from'),
                'date_to' => request('geo_date_to'),
            ],
        ];
    }

    public function apply(Builder $query, Request $request, User $user): Builder
    {
        $options = $this->options($user);

        if ($request->filled('geo_division') && $options['show_division']) {
            $divisionId = (int) $request->input('geo_division');
            if ($options['divisions']->has($divisionId)) {
                $query->where('division_id', $divisionId);
            }
        }

        if ($request->filled('geo_district') && $options['show_district']) {
            $districtId = (int) $request->input('geo_district');
            if ($options['districts']->has($districtId)) {
                $query->where('district_id', $districtId);
            }
        }

        if ($request->filled('geo_tehsil') && $options['show_tehsil']) {
            $tehsilId = (int) $request->input('geo_tehsil');
            if ($options['tehsils']->has($tehsilId)) {
                $query->where('tehsil_id', $tehsilId);
            }
        }

        if ($request->filled('geo_date_from')) {
            $dateColumn = $this->dateColumn($query);
            $query->whereDate($dateColumn, '>=', $request->input('geo_date_from'));
        }

        if ($request->filled('geo_date_to')) {
            $dateColumn = $this->dateColumn($query);
            $query->whereDate($dateColumn, '<=', $request->input('geo_date_to'));
        }

        return $query;
    }

    public function state(Request $request): array
    {
        return [
            'geo_division' => $request->input('geo_division'),
            'geo_district' => $request->input('geo_district'),
            'geo_tehsil' => $request->input('geo_tehsil'),
            'geo_date_from' => $request->input('geo_date_from'),
            'geo_date_to' => $request->input('geo_date_to'),
        ];
    }

    private function dateColumn(Builder $query): string
    {
        $table = $query->getModel()->getTable();

        return match ($table) {
            'kpi_inspections' => 'inspection_datetime',
            default => 'submission_date',
        };
    }
}
