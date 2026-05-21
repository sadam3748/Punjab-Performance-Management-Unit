<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KpiCategory extends Model
{
    protected $fillable = [
        'name',
        'scorecard_weightage',
        'slug',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'scorecard_weightage' => 'decimal:2',
    ];

    public function inspections()
    {
        return $this->hasMany(Inspection::class);
    }

    public function baselineData()
    {
        return $this->hasMany(DistrictBaselineData::class);
    }

    public function baselineAssets()
    {
        return $this->hasMany(BaselineAsset::class);
    }

    public function provincialMetrics(): HasMany
    {
        // Backward-compatible alias (old name) for unified KPI metric values.
        return $this->hasMany(KpiMetricValue::class, 'kpi_category_id')
            ->where('area_level', 'province');
    }

    public function districtKpiMetricValues(): HasMany
    {
        // Backward-compatible alias (old name) for unified KPI metric values.
        return $this->hasMany(KpiMetricValue::class, 'kpi_category_id')
            ->where('area_level', 'district');
    }

    public function metricValues(): HasMany
    {
        return $this->hasMany(KpiMetricValue::class, 'kpi_category_id');
    }

    public function scoringParameters(): HasMany
    {
        return $this->hasMany(KpiScoringParameter::class, 'kpi_category_id');
    }

    public function districtKpiScores(): HasMany
    {
        return $this->hasMany(DistrictKpiScore::class, 'kpi_category_id');
    }
}
