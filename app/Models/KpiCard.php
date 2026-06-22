<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KpiCard extends Model
{
    protected $fillable = ['title', 'slug', 'category', 'description', 'icon', 'frequency', 'total_marks', 'is_active', 'display_order', 'metric_config'];

    protected $casts = ['is_active' => 'boolean', 'metric_config' => 'array', 'total_marks' => 'decimal:2'];

    public function getRouteKeyName(): string { return 'slug'; }
    public function formFields() { return $this->hasMany(KpiFormField::class)->orderBy('sort_order'); }
    public function assignments() { return $this->hasMany(KpiAssignment::class); }
    public function submissions() { return $this->hasMany(KpiSubmission::class); }
    public function scores() { return $this->hasMany(KpiScore::class); }
}
