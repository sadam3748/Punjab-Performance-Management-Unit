<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class District extends Model
{
    protected $fillable = [
        'division_id',
        'name',
        'code',
        'tier',
        'is_active',
    ];

    protected $casts = [
        'tier' => 'integer',
        'is_active' => 'boolean',
    ];

    public function division()
    {
        return $this->belongsTo(Division::class);
    }

    public function tehsils()
    {
        return $this->hasMany(Tehsil::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function inspections()
    {
        return $this->hasMany(Inspection::class);
    }

    public function geoTaggings()
    {
        return $this->hasMany(GeoTagging::class);
    }

    public function baselineData()
    {
        return $this->hasMany(DistrictBaselineData::class);
    }

    public function baselineAssets()
    {
        return $this->hasMany(BaselineAsset::class);
    }

    public function districtKpiMetricValues()
    {
        return $this->hasMany(DistrictKpiMetricValue::class);
    }

    public function districtKpiScores()
    {
        return $this->hasMany(DistrictKpiScore::class);
    }
}
