<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KpiScoringParameter extends Model
{
    protected $guarded = [];

    protected $casts = [
        'weightage'         => 'decimal:2',
        'target_value'      => 'decimal:2',
        'higher_is_better'  => 'boolean',
        'sort_order'        => 'integer',
        'is_active'         => 'boolean',
    ];

    public function kpiCategory(): BelongsTo
    {
        return $this->belongsTo(KpiCategory::class, 'kpi_category_id');
    }

    public function scoreDetails(): HasMany
    {
        return $this->hasMany(DistrictKpiScoreDetail::class, 'kpi_scoring_parameter_id');
    }
}

