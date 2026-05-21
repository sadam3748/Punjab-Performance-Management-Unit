<?php
namespace App\Services;

use App\Models\District;
use App\Models\Inspection;
use App\Models\KpiCategory;
use App\Models\Tehsil;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class InspectionService
{
    /*
    |--------------------------------------------------------------------------
    | Base Query
    |--------------------------------------------------------------------------
    | Common query used for list/map/detail.
    | Includes relations to avoid repeated database queries.
    */
    private function baseQuery()
    {
        return Inspection::query()
            ->with([
                'kpiCategory:id,name,slug',
                'division:id,name',
                'district:id,name',
                'tehsil:id,name',
                'performer:id,name,username,designation',
            ])
            ->withCount('attachments');
    }

    /*
    |--------------------------------------------------------------------------
    | Role-Based Scope
    |--------------------------------------------------------------------------
    | This limits data according to logged-in user role.
    |
    | Super Admin / CS / PMRU = all Punjab
    | Commissioner = own division
    | DC = own district
    | AC / Field User = own tehsil
    */
    private function applyUserScope($query)
    {
        $user = Auth::user();

        if (! $user || ! $user->role) {
            return $query;
        }

        $roleSlug = $user->role->slug;

        if (in_array($roleSlug, ['super_admin', 'chief_secretary', 'pmru_user'])) {
            return $query;
        }

        if ($roleSlug === 'commissioner' && $user->division_id) {
            return $query->where('division_id', $user->division_id);
        }

        if ($roleSlug === 'dc' && $user->district_id) {
            return $query->where('district_id', $user->district_id);
        }

        if (in_array($roleSlug, ['ac', 'field_user']) && $user->tehsil_id) {
            return $query->where('tehsil_id', $user->tehsil_id);
        }

        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | Apply Filters
    |--------------------------------------------------------------------------
    | Applies page filters from request.
    */
    private function applyFilters($query, array $filters)
    {
        return $query
            ->when(! empty($filters['district_id']), function ($q) use ($filters) {
                $q->where('district_id', $filters['district_id']);
            })
            ->when(! empty($filters['tehsil_id']), function ($q) use ($filters) {
                $q->where('tehsil_id', $filters['tehsil_id']);
            })
            ->when(! empty($filters['kpi_category_id']), function ($q) use ($filters) {
                $q->where('kpi_category_id', $filters['kpi_category_id']);
            })
            ->when(! empty($filters['status']), function ($q) use ($filters) {
                $q->where('status', $filters['status']);
            })
            ->when(! empty($filters['date_from']), function ($q) use ($filters) {
                $q->whereDate('inspection_datetime', '>=', $filters['date_from']);
            })
            ->when(! empty($filters['date_to']), function ($q) use ($filters) {
                $q->whereDate('inspection_datetime', '<=', $filters['date_to']);
            })
            ->when(! empty($filters['search']), function ($q) use ($filters) {
                $search = $filters['search'];

                $q->where(function ($subQuery) use ($search) {
                    $subQuery->where('main_title', 'ILIKE', "%{$search}%")
                        ->orWhere('main_address', 'ILIKE', "%{$search}%")
                        ->orWhere('remarks', 'ILIKE', "%{$search}%");
                });
            });
    }

    /*
    |--------------------------------------------------------------------------
    | Inspection List
    |--------------------------------------------------------------------------
    | Paginated list for table page.
    */
    public function getInspectionList(array $filters)
    {
        $query = $this->baseQuery();

        $query = $this->applyUserScope($query);
        $query = $this->applyFilters($query, $filters);

        $perPage = (int) ($filters['per_page'] ?? 10);
        if (! in_array($perPage, [10, 20, 25, 50])) {
            $perPage = 10;
        }

        return $query
            ->latest('inspection_datetime')
            ->paginate($perPage)
            ->withQueryString();
    }

    /*
    |--------------------------------------------------------------------------
    | Inspection Map Data
    |--------------------------------------------------------------------------
    | Only records having latitude and longitude.
    */
    public function getInspectionMapData(array $filters)
    {
        $query = $this->baseQuery()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');

        $query = $this->applyUserScope($query);
        $query = $this->applyFilters($query, $filters);

        return $query
            ->latest('inspection_datetime')
            ->limit(500)
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Inspection Detail
    |--------------------------------------------------------------------------
    | Single inspection detail with attachments.
    */
    public function getInspectionDetail($id)
    {
        $query = Inspection::query()
            ->with([
                'kpiCategory:id,name',
                'division:id,name',
                'district:id,name',
                'tehsil:id,name',
                'performer:id,name,username,designation',
                'attachments',
            ]);

        $query = $this->applyUserScope($query);

        return $query->findOrFail($id);
    }

    /*
    |--------------------------------------------------------------------------
    | Filter Dropdown Data
    |--------------------------------------------------------------------------
    | Common dropdown data for list/map pages.
    */
    public function getFilterData(): array
    {
        return [
            'districts'     => District::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),

            'tehsils'       => Tehsil::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'district_id', 'name']),

            'kpiCategories' => KpiCategory::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'slug']),
        ];
    }

    public function getInspectionDisplayFields(Inspection $inspection): array
    {
        $slug = (string) ($inspection->kpiCategory?->slug ?? '');
        $detail = is_array($inspection->detail_data ?? null) ? ($inspection->detail_data ?? []) : [];

        $getFirst = function (array $keys) use ($detail, $inspection) {
            foreach ($keys as $k) {
                if (array_key_exists($k, $detail) && $detail[$k] !== null && $detail[$k] !== '') {
                    return $detail[$k];
                }
            }
            return null;
        };

        $primaryLabel = 'Primary Detail';
        $secondaryLabel = 'Secondary Detail / Address';

        if (Str::contains($slug, ['water-filtration', 'filtration-plant'])) {
            $primaryLabel = 'Filter Plant / UC';
            $secondaryLabel = 'Address';
        } elseif (Str::contains($slug, ['marriage', 'wedding', 'marriage-functions'])) {
            $primaryLabel = 'Marriage Hall Name';
            $secondaryLabel = 'Address';
        } elseif (Str::contains($slug, ['manhole'])) {
            $primaryLabel = 'Location / Road';
            $secondaryLabel = 'Nearby Landmark / Address';
        } elseif (Str::contains($slug, ['stray-dog', 'stray-dogs'])) {
            $primaryLabel = 'Area / Location';
            $secondaryLabel = 'Address / Remarks';
        }

        $primaryValue = $getFirst(['primary', 'name', 'title', 'location', 'site_name', 'plant_name']) ?? ($inspection->main_title ?? null);
        $secondaryValue = $getFirst(['secondary', 'address', 'landmark', 'remarks', 'site_address']) ?? ($inspection->main_address ?? null);

        return [
            'primary_label' => $primaryLabel,
            'primary_value' => $primaryValue ?: 'N/A',
            'secondary_label' => $secondaryLabel,
            'secondary_value' => $secondaryValue ?: 'N/A',
        ];
    }

    public function getEvidenceActionsCounts(Inspection $inspection): array
    {
        $evidenceCount = (int) ($inspection->attachments_count ?? 0);

        $actions = $inspection->actions ?? [];
        if (! is_array($actions)) {
            $actions = [];
        }

        // Count non-empty action fields/items.
        $actionsCount = 0;
        foreach ($actions as $value) {
            if (is_array($value)) {
                $actionsCount += count(array_filter($value, fn ($v) => $v !== null && $v !== ''));
                continue;
            }
            if ($value !== null && $value !== '') {
                $actionsCount++;
            }
        }

        return [
            'evidence_count' => $evidenceCount,
            'actions_count' => $actionsCount,
            'total_items' => $evidenceCount + $actionsCount,
        ];
    }
}
