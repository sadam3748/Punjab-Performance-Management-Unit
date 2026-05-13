<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BaselineAsset extends Model
{
    protected $table = 'baseline_assets';

    protected $fillable = [
        'kpi_category_id',
        'division_id',
        'district_id',
        'tehsil_id',
        'name',
        'address',
        'latitude',
        'longitude',
        'baseline_date',
        'status',
        'detail_data',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'baseline_date' => 'date',
        'detail_data' => 'array',
    ];

    public function kpiCategory()
    {
        return $this->belongsTo(KpiCategory::class, 'kpi_category_id');
    }

    public function division()
    {
        return $this->belongsTo(Division::class);
    }

    public function district()
    {
        return $this->belongsTo(District::class);
    }

    public function tehsil()
    {
        return $this->belongsTo(Tehsil::class);
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
