<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KpiFormField extends Model
{
    protected $fillable = ['kpi_card_id', 'field_label', 'field_name', 'field_type', 'options', 'is_required', 'sort_order'];
    protected $casts = ['options' => 'array', 'is_required' => 'boolean'];
    public function kpiCard() { return $this->belongsTo(KpiCard::class); }
    public function values() { return $this->hasMany(KpiSubmissionValue::class, 'field_id'); }
}
