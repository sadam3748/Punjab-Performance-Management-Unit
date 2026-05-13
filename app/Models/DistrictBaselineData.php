<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DistrictBaselineData extends Model
{
    protected $table = 'district_baseline_data';

    protected $fillable = [
        'district_id',
        'kpi_category_id',
        'year',
        'baseline_data',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'baseline_data' => 'array',
        'year' => 'integer',
    ];

    public function district()
    {
        return $this->belongsTo(District::class);
    }

    public function kpiCategory()
    {
        return $this->belongsTo(KpiCategory::class, 'kpi_category_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
