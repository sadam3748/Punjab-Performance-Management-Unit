<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InspectionAttachment extends Model
{
    protected $fillable = [
        'inspection_id',
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

    public function inspection()
    {
        return $this->belongsTo(Inspection::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
