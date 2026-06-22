<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KpiSubmission extends Model
{
    protected $fillable = ['kpi_card_id', 'user_id', 'division_id', 'district_id', 'tehsil_id', 'period_type', 'period_label', 'submission_date', 'status', 'score', 'remarks'];
    protected $casts = ['submission_date' => 'date', 'score' => 'decimal:2'];
    public function kpiCard() { return $this->belongsTo(KpiCard::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function division() { return $this->belongsTo(Division::class); }
    public function district() { return $this->belongsTo(District::class); }
    public function tehsil() { return $this->belongsTo(Tehsil::class); }
    public function values() { return $this->hasMany(KpiSubmissionValue::class, 'submission_id'); }
    public function kpiScore() { return $this->hasOne(KpiScore::class, 'submission_id'); }
}
