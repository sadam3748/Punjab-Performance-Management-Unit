<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KpiSubmission extends Model
{
    protected $fillable = [
        'kpi_card_id', 'user_id', 'division_id', 'district_id', 'tehsil_id', 'area_level',
        'period_type', 'period_label', 'week_no', 'week_start_date', 'week_end_date',
        'submission_date', 'status', 'score', 'target_value', 'reported_value', 'achieved_value',
        'pending_value', 'achievement_percentage', 'remarks', 'evidence_count', 'metric_snapshot',
    ];

    protected $casts = [
        'submission_date' => 'date',
        'week_start_date' => 'date',
        'week_end_date' => 'date',
        'score' => 'decimal:2',
        'target_value' => 'decimal:2',
        'reported_value' => 'decimal:2',
        'achieved_value' => 'decimal:2',
        'pending_value' => 'decimal:2',
        'achievement_percentage' => 'decimal:2',
        'metric_snapshot' => 'array',
    ];
    public function kpiCard() { return $this->belongsTo(KpiCard::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function division() { return $this->belongsTo(Division::class); }
    public function district() { return $this->belongsTo(District::class); }
    public function tehsil() { return $this->belongsTo(Tehsil::class); }
    public function values() { return $this->hasMany(KpiSubmissionValue::class, 'submission_id'); }
    public function kpiScore() { return $this->hasOne(KpiScore::class, 'submission_id'); }
}
