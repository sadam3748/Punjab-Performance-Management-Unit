<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeoTaggingAttachment extends Model
{
    protected $fillable = [
        'geo_tagging_id',
        'uploaded_by',
        'file_path',
        'file_name',
        'mime_type',
        'file_size',
        'caption',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    public function geoTagging()
    {
        return $this->belongsTo(GeoTagging::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
