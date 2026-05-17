<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProvincialKpiMetric extends Model
{
    protected $fillable = [
        'kpi_category_id',
        'period_type',
        'date_from',
        'date_to',
        'metric_title',
        'metric_description',
        'metric_value',
        'metric_unit',
        'source',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'date_from'    => 'date',
        'date_to'      => 'date',
        'metric_value' => 'decimal:2',
        'sort_order'   => 'integer',
        'is_active'    => 'boolean',
    ];

    public function kpiCategory(): BelongsTo
    {
        return $this->belongsTo(KpiCategory::class, 'kpi_category_id');
    }

    public function districtMetricValues(): HasMany
    {
        return $this->hasMany(DistrictKpiMetricValue::class, 'provincial_kpi_metric_id');
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
