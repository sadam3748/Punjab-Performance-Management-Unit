<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KpiMetricValue extends Model
{
    protected $fillable = [
        'kpi_category_id',
        'metric_key',
        'metric_title',
        'metric_value',
        'metric_score',
        'metric_unit',
        'area_level',
        'division_id',
        'district_id',
        'tehsil_id',
        'period_type',
        'year',
        'week_no',
        'month',
        'quarter',
        'date_from',
        'date_to',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'metric_value' => 'decimal:2',
        'metric_score' => 'decimal:2',
        'year'         => 'integer',
        'month'        => 'integer',
        'quarter'      => 'integer',
        'date_from'    => 'date',
        'date_to'      => 'date',
        'sort_order'   => 'integer',
        'is_active'    => 'boolean',
    ];

    public function kpiCategory(): BelongsTo
    {
        return $this->belongsTo(KpiCategory::class, 'kpi_category_id');
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class, 'division_id');
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class, 'district_id');
    }

    public function tehsil(): BelongsTo
    {
        return $this->belongsTo(Tehsil::class, 'tehsil_id');
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

    public function getUnitLabelAttribute(): string
    {
        if (! $this->metric_unit) {
            return 'Value';
        }

        return strtoupper(str_replace('_', ' ', (string) $this->metric_unit));
    }
}

