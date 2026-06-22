<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KpiAssignment extends Model
{
    protected $fillable = ['kpi_card_id', 'role_id', 'division_id', 'district_id', 'tehsil_id', 'user_id', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];
    public function kpiCard() { return $this->belongsTo(KpiCard::class); }
    public function role() { return $this->belongsTo(Role::class); }
    public function user() { return $this->belongsTo(User::class); }
}
