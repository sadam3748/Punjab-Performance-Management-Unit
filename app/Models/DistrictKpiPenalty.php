<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DistrictKpiPenalty extends Model
{
    protected $guarded = [];

    protected $casts = [
        'penalty_score' => 'decimal:2',
    ];

    public function districtKpiScore(): BelongsTo
    {
        return $this->belongsTo(DistrictKpiScore::class, 'district_kpi_score_id');
    }
}

