<?php
namespace App\Services;

use App\Models\District;
use App\Models\GeoTagging;
use App\Models\GeoTaggingType;
use App\Models\Tehsil;
use Illuminate\Support\Facades\Auth;

class GeoTaggingService
{
    /*
    |--------------------------------------------------------------------------
    | Base Query
    |--------------------------------------------------------------------------
    | Common geo-tagging query with required relations.
    */
    private function baseQuery()
    {
        return GeoTagging::query()
            ->with([
                'geoTaggingType:id,name',
                'division:id,name',
                'district:id,name',
                'tehsil:id,name',
                'performer:id,name,username,designation',
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Role-Based Scope
    |--------------------------------------------------------------------------
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
    | Applies filters from request.
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
            ->when(! empty($filters['geo_tagging_type_id']), function ($q) use ($filters) {
                $q->where('geo_tagging_type_id', $filters['geo_tagging_type_id']);
            })
            ->when(! empty($filters['status']), function ($q) use ($filters) {
                $q->where('status', $filters['status']);
            })
            ->when(! empty($filters['date_from']), function ($q) use ($filters) {
                $q->whereDate('tagged_at', '>=', $filters['date_from']);
            })
            ->when(! empty($filters['date_to']), function ($q) use ($filters) {
                $q->whereDate('tagged_at', '<=', $filters['date_to']);
            })
            ->when(! empty($filters['search']), function ($q) use ($filters) {
                $search = $filters['search'];

                $q->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'ILIKE', "%{$search}%")
                        ->orWhere('address', 'ILIKE', "%{$search}%")
                        ->orWhere('remarks', 'ILIKE', "%{$search}%");
                });
            });
    }

    /*
    |--------------------------------------------------------------------------
    | Geo Tagging List
    |--------------------------------------------------------------------------
    | Paginated list for table page.
    */
    public function getGeoTaggingList(array $filters)
    {
        $query = $this->baseQuery();

        $query = $this->applyUserScope($query);
        $query = $this->applyFilters($query, $filters);

        $perPage = (int) ($filters['per_page'] ?? 20);
        if (! in_array($perPage, [10, 20, 25, 50], true)) {
            $perPage = 20;
        }

        return $query
            ->latest('tagged_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    /*
    |--------------------------------------------------------------------------
    | Geo Tagging Map Data
    |--------------------------------------------------------------------------
    | Only records that have latitude and longitude.
    */
    public function getGeoTaggingMapData(array $filters)
    {
        $query = $this->baseQuery()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');

        $query = $this->applyUserScope($query);
        $query = $this->applyFilters($query, $filters);

        return $query
            ->latest('tagged_at')
            ->limit(700)
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Single Geo Tagging Detail
    |--------------------------------------------------------------------------
    | Returns one detail record with attachments.
    */
    public function getGeoTaggingDetail($id)
    {
        $query = GeoTagging::query()
            ->with([
                'geoTaggingType:id,name',
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
    | Latest Geo Tagging Detail
    |--------------------------------------------------------------------------
    | Temporary method for static detail page.
    */
    public function getLatestGeoTaggingDetail()
    {
        $query = GeoTagging::query()
            ->with([
                'geoTaggingType:id,name',
                'division:id,name',
                'district:id,name',
                'tehsil:id,name',
                'performer:id,name,username,designation',
                'attachments',
            ]);

        $query = $this->applyUserScope($query);

        return $query->latest('tagged_at')->first();
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
            'districts'       => District::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),

            'tehsils'         => Tehsil::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'district_id', 'name']),

            'geoTaggingTypes' => GeoTaggingType::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
        ];
    }
}
