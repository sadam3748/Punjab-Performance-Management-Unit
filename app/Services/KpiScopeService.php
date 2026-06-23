<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class KpiScopeService
{
    public function areaLevel(User $user): string
    {
        return match ($user->role?->slug) {
            'ac', 'field_user' => 'tehsil',
            'dc' => 'district',
            'commissioner' => 'division',
            default => 'province',
        };
    }

    public function locationLabel(User $user): string
    {
        return $user->tehsil?->name
            ?? $user->district?->name
            ?? $user->division?->name
            ?? 'All Punjab';
    }

    public function comparisonRelation(User $user): string
    {
        return match ($user->role?->slug) {
            'commissioner' => 'district',
            'dc' => 'tehsil',
            'ac', 'field_user' => 'tehsil',
            default => 'division',
        };
    }

    public function apply(Builder $query, User $user): Builder
    {
        return match ($user->role?->slug) {
            'commissioner' => $query->where('division_id', $user->division_id),
            'dc' => $query->where('district_id', $user->district_id),
            'ac', 'field_user' => $query->where('tehsil_id', $user->tehsil_id),
            default => $query,
        };
    }

    public function scopeIds(User $user): array
    {
        return match ($user->role?->slug) {
            'chief_secretary', 'super_admin', 'pmru_user', 'viewer' => [null, null, null, 'province'],
            'commissioner' => [$user->division_id, null, null, 'division'],
            'dc' => [$user->division_id, $user->district_id, null, 'district'],
            default => [$user->division_id, $user->district_id, $user->tehsil_id, 'tehsil'],
        };
    }
}
