<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KpiSubmissionValue extends Model
{
    protected $fillable = ['submission_id', 'field_id', 'value'];
    public function submission() { return $this->belongsTo(KpiSubmission::class); }
    public function field() { return $this->belongsTo(KpiFormField::class, 'field_id'); }
}
