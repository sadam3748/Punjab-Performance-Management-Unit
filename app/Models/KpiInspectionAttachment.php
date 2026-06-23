<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KpiInspectionAttachment extends Model
{
    protected $fillable = [
        'kpi_inspection_id', 'file_path', 'file_name', 'file_type', 'mime_type',
        'caption', 'latitude', 'longitude', 'sort_order', 'is_demo',
    ];

    protected $casts = [
        'is_demo' => 'boolean',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(KpiInspection::class, 'kpi_inspection_id');
    }

    public function resolvedUrl(?string $fallbackPath = null): string
    {
        $path = ltrim((string) $this->file_path, '/');

        if ($path !== '' && is_file(public_path($path))) {
            return asset($path);
        }

        if ($fallbackPath && is_file(public_path($fallbackPath))) {
            return asset($fallbackPath);
        }

        return asset('images/kpi-images/default-kpi.png');
    }
}
