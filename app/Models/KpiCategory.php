<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KpiCategory extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
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
        return $this->hasMany(ProvincialKpiMetric::class, 'kpi_category_id');
    }

    public function districtKpiMetricValues(): HasMany
    {
        return $this->hasMany(DistrictKpiMetricValue::class, 'kpi_category_id');
    }
}
