<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KpiScore extends Model
{
    protected $fillable = ['kpi_card_id', 'submission_id', 'user_id', 'division_id', 'district_id', 'tehsil_id', 'score', 'percentage', 'grade', 'performance_label'];
    protected $casts = ['score' => 'decimal:2', 'percentage' => 'decimal:2'];
    public function kpiCard() { return $this->belongsTo(KpiCard::class); }
    public function submission() { return $this->belongsTo(KpiSubmission::class); }
}
