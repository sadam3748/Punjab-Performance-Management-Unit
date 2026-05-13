<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inspection extends Model
{
    protected $fillable = [
        'kpi_category_id',
        'division_id',
        'district_id',
        'tehsil_id',
        'performed_by',
        'inspection_datetime',
        'latitude',
        'longitude',
        'main_title',
        'main_address',
        'detail_data',
        'observations',
        'actions',
        'status',
        'remarks',
    ];

    protected $casts = [
        'inspection_datetime' => 'datetime',
        'latitude'            => 'decimal:7',
        'longitude'           => 'decimal:7',
        'detail_data'         => 'array',
        'observations'        => 'array',
        'actions'             => 'array',
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

    public function performer()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function attachments()
    {
        return $this->hasMany(InspectionAttachment::class);
    }
}
