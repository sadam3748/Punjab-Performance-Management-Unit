<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DistrictKpiScore extends Model
{
    protected $guarded = [];

    protected $casts = [
        'reported_score' => 'decimal:2',
        'verified_score' => 'decimal:2',
        'penalty_score'  => 'decimal:2',
        'final_score'    => 'decimal:2',
        'month'          => 'integer',
        'quarter'        => 'integer',
        'year'           => 'integer',
        'date_from'      => 'date',
        'date_to'        => 'date',
        'is_reported'    => 'boolean',
        'is_active'      => 'boolean',
    ];

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class, 'division_id');
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class, 'district_id');
    }

    public function kpiCategory(): BelongsTo
    {
        return $this->belongsTo(KpiCategory::class, 'kpi_category_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(DistrictKpiScoreDetail::class, 'district_kpi_score_id');
    }

    public function penalties(): HasMany
    {
        return $this->hasMany(DistrictKpiPenalty::class, 'district_kpi_score_id');
    }
}

