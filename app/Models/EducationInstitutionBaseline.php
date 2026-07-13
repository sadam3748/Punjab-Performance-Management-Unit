<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EducationInstitutionBaseline extends Model
{
    protected $fillable = [
        'division_id',
        'district_id',
        'tehsil_id',
        'institution_code',
        'name',
        'institution_type',
        'address',
        'latitude',
        'longitude',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'is_active' => 'boolean',
        ];
    }

    public function tehsil(): BelongsTo
    {
        return $this->belongsTo(Tehsil::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }
}
