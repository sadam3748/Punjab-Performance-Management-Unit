<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeoTagging extends Model
{
    protected $fillable = [
        'geo_tagging_type_id',
        'division_id',
        'district_id',
        'tehsil_id',
        'performed_by',
        'name',
        'address',
        'latitude',
        'longitude',
        'tagged_at',
        'detail_data',
        'status',
        'remarks',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'tagged_at' => 'datetime',
        'detail_data' => 'array',
    ];

    public function geoTaggingType()
    {
        return $this->belongsTo(GeoTaggingType::class);
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
        return $this->hasMany(GeoTaggingAttachment::class);
    }
}
