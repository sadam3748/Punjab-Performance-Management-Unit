<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DistrictKpiMetricValue extends Model
{
    protected $fillable = [
        'district_id',
        'kpi_category_id',
        'provincial_kpi_metric_id',
        'period_type',
        'date_from',
        'date_to',
        'metric_title',
        'metric_value',
        'metric_score',
        'metric_unit',
        'evidence',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'date_from'    => 'date',
        'date_to'      => 'date',
        'metric_value' => 'decimal:2',
        'metric_score' => 'decimal:2',
        'sort_order'   => 'integer',
        'is_active'    => 'boolean',
    ];

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class, 'district_id');
    }

    public function kpiCategory(): BelongsTo
    {
        return $this->belongsTo(KpiCategory::class, 'kpi_category_id');
    }

    public function provincialKpiMetric(): BelongsTo
    {
        return $this->belongsTo(ProvincialKpiMetric::class, 'provincial_kpi_metric_id');
    }

    public function getFormattedValueAttribute(): string
    {
        $value = (float) $this->metric_value;

        if ($this->metric_unit === 'percent') {
            return number_format($value, 2) . '%';
        }

        if (floor($value) === $value) {
            return number_format($value);
        }

        return number_format($value, 2);
    }

    public function getPerformanceLabelAttribute(): string
    {
        $score = $this->metric_score;

        if ($score === null) {
            return 'N/A';
        }

        $score = (float) $score;

        if ($score >= 90) {
            return 'Excellent';
        }
        if ($score >= 80) {
            return 'Very Good';
        }
        if ($score >= 70) {
            return 'Good';
        }
        if ($score >= 60) {
            return 'Average';
        }

        return 'Low';
    }

    public function getScoreBadgeClassAttribute(): string
    {
        $score = $this->metric_score;

        if ($score === null) {
            return 'secondary';
        }

        $score = (float) $score;

        if ($score >= 90) {
            return 'success';
        }
        if ($score >= 80) {
            return 'info';
        }
        if ($score >= 70) {
            return 'primary';
        }
        if ($score >= 60) {
            return 'warning';
        }

        return 'danger';
    }
}

