<?php

namespace App\Models;

use App\Data\KpiDashboardDefinitions;
use Illuminate\Database\Eloquent\Model;

class KpiCard extends Model
{
    protected $fillable = ['title', 'slug', 'category', 'description', 'icon', 'image_path', 'frequency', 'total_marks', 'is_active', 'display_order', 'metric_config'];

    protected $casts = ['is_active' => 'boolean', 'metric_config' => 'array', 'total_marks' => 'decimal:2'];

    public function getRouteKeyName(): string { return 'slug'; }

    public function resolveRouteBinding($value, $field = null)
    {
        if ($field === null || $field === $this->getRouteKeyName()) {
            $value = KpiDashboardDefinitions::normalizeSlug((string) $value);
        }

        return $this->where($field ?? $this->getRouteKeyName(), $value)->first();
    }

    public function resolvedImagePath(): string
    {
        $path = ltrim((string) $this->image_path, '/');

        return $path !== '' && is_file(public_path($path))
            ? $path
            : 'images/kpi-images/default-kpi.png';
    }
    public function formFields() { return $this->hasMany(KpiFormField::class)->orderBy('sort_order'); }
    public function assignments() { return $this->hasMany(KpiAssignment::class); }
    public function submissions() { return $this->hasMany(KpiSubmission::class); }
    public function scores() { return $this->hasMany(KpiScore::class); }
    public function inspections() { return $this->hasMany(KpiInspection::class); }
}
