<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DistrictKpiScoreDetail extends Model
{
    protected $guarded = [];

    protected $casts = [
        'reported_value'       => 'decimal:2',
        'numerator_value'      => 'decimal:2',
        'denominator_value'    => 'decimal:2',
        'target_value'         => 'decimal:2',
        'achieved_percentage'  => 'decimal:2',
        'weightage'            => 'decimal:2',
        'score_obtained'       => 'decimal:2',
        'extra_data'           => 'array',
    ];

    public function districtKpiScore(): BelongsTo
    {
        return $this->belongsTo(DistrictKpiScore::class, 'district_kpi_score_id');
    }

    public function scoringParameter(): BelongsTo
    {
        return $this->belongsTo(KpiScoringParameter::class, 'kpi_scoring_parameter_id');
    }
}
