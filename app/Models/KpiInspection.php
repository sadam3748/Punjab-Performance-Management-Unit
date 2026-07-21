<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KpiInspection extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_INSPECTED = 'inspected_only';

    public const STATUS_PENDING = 'pending_review';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'uuid', 'reference_no', 'kpi_card_id', 'kpi_submission_id',
        'division_id', 'district_id', 'tehsil_id', 'inspected_by', 'reviewed_by',
        'inspection_title', 'entity_name', 'entity_type', 'identifier', 'address',
        'latitude', 'longitude', 'inspection_datetime', 'status', 'selected_for_review',
        'selected_by', 'selected_at', 'review_level',
        'observations', 'actions_required', 'actions_taken', 'detail_data',
        'review_remarks', 'rejection_reason', 'reviewed_at', 'is_demo', 'seed_batch',
    ];

    protected $casts = [
        'inspection_datetime' => 'datetime',
        'reviewed_at' => 'datetime',
        'selected_at' => 'datetime',
        'selected_for_review' => 'boolean',
        'observations' => 'array',
        'actions_required' => 'array',
        'actions_taken' => 'array',
        'detail_data' => 'array',
        'is_demo' => 'boolean',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    public function kpiCard(): BelongsTo
    {
        return $this->belongsTo(KpiCard::class);
    }

    public function kpiSubmission(): BelongsTo
    {
        return $this->belongsTo(KpiSubmission::class);
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function tehsil(): BelongsTo
    {
        return $this->belongsTo(Tehsil::class);
    }

    public function inspectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspected_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function selectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'selected_by');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(KpiInspectionAttachment::class)->orderBy('sort_order');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function statusLabel(): string
    {
        if ($this->usesSampleReviewModel()
            && ! $this->selected_for_review
            && $this->status === self::STATUS_INSPECTED) {
            return 'Inspected Only';
        }

        return match ($this->status) {
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            default => 'Pending Review',
        };
    }

    public function displayStatusFor(?User $user): string
    {
        if (! $this->usesSampleReviewModel()) {
            return $this->statusLabel();
        }

        if (! $this->isSelectedFor($user)) {
            return 'Inspected Only';
        }

        return match ($this->status) {
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            default => 'Pending Review',
        };
    }

    public function displayStatusKeyFor(?User $user): string
    {
        if (! $this->usesSampleReviewModel()) {
            return $this->status;
        }

        if (! $this->isSelectedFor($user)) {
            return self::STATUS_INSPECTED;
        }

        return $this->status;
    }

    public function displayStatusClassFor(?User $user): string
    {
        return match ($this->displayStatusKeyFor($user)) {
            self::STATUS_APPROVED => 'success',
            self::STATUS_REJECTED => 'danger',
            self::STATUS_PENDING => 'warning',
            default => 'primary',
        };
    }

    public function detailStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            default => 'Pending Review',
        };
    }

    public function detailStatusClass(): string
    {
        return match ($this->status) {
            self::STATUS_APPROVED => 'success',
            self::STATUS_REJECTED => 'danger',
            default => 'warning',
        };
    }

    public function isSelectedFor(?User $user): bool
    {
        if (! $this->usesSampleReviewModel()) {
            return true;
        }

        return $this->selected_for_review
            && $user !== null
            && $this->review_level === ($user->role?->slug ?? '');
    }

    private function usesSampleReviewModel(): bool
    {
        return ! in_array($this->kpiCard?->slug, [
            'inspection-of-health-facilities',
            'inspection-of-educational-institutions',
        ], true);
    }

    public function statusClass(): string
    {
        if ($this->usesSampleReviewModel()
            && ! $this->selected_for_review
            && $this->status === self::STATUS_INSPECTED) {
            return 'primary';
        }

        return match ($this->status) {
            self::STATUS_APPROVED => 'success',
            self::STATUS_REJECTED => 'danger',
            default => 'warning',
        };
    }

    public function googleMapsUrl(): ?string
    {
        if ($this->latitude === null || $this->longitude === null) {
            return null;
        }

        return sprintf('https://www.google.com/maps?q=%s,%s', $this->latitude, $this->longitude);
    }

    public function primaryImage(): ?KpiInspectionAttachment
    {
        return $this->relationLoaded('attachments')
            ? $this->attachments->first()
            : $this->attachments()->orderBy('sort_order')->first();
    }

    public function locationLabel(): string
    {
        return $this->entity_name
            ?: $this->inspection_title
            ?: 'Inspection location';
    }
}
