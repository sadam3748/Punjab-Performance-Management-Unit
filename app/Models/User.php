<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'role_id',
        'division_id',
        'district_id',
        'tehsil_id',
        'name',
        'username',
        'email',
        'password',
        'phone',
        'designation',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at'     => 'datetime',
            'is_active'         => 'boolean',
            'password'          => 'hashed',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Role / Access Relations
    |--------------------------------------------------------------------------
    */

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function division()
    {
        return $this->belongsTo(Division::class);
    }

    public function district()
    {
        return $this->belongsTo(District::class);
    }

    public function tehsil()
    {
        return $this->belongsTo(Tehsil::class);
    }

    /*
    |--------------------------------------------------------------------------
    | PPMF Module Relations
    |--------------------------------------------------------------------------
    */

    public function inspections()
    {
        return $this->hasMany(Inspection::class, 'performed_by');
    }

    public function uploadedInspectionAttachments()
    {
        return $this->hasMany(InspectionAttachment::class, 'uploaded_by');
    }

    public function geoTaggings()
    {
        return $this->hasMany(GeoTagging::class, 'performed_by');
    }

    public function uploadedGeoTaggingAttachments()
    {
        return $this->hasMany(GeoTaggingAttachment::class, 'uploaded_by');
    }

    public function createdBaselineData()
    {
        return $this->hasMany(DistrictBaselineData::class, 'created_by');
    }

    public function updatedBaselineData()
    {
        return $this->hasMany(DistrictBaselineData::class, 'updated_by');
    }

    public function createdBaselineAssets()
    {
        return $this->hasMany(BaselineAsset::class, 'created_by');
    }

    public function updatedBaselineAssets()
    {
        return $this->hasMany(BaselineAsset::class, 'updated_by');
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    public function hasPunjabAccess(): bool
    {
        return in_array(optional($this->role)->slug, [
            'super_admin',
            'chief_secretary',
            'pmru_user',
        ]);
    }

    public function hasDivisionAccess(): bool
    {
        return optional($this->role)->scope_level === 'division' && ! empty($this->division_id);
    }

    public function hasDistrictAccess(): bool
    {
        return optional($this->role)->scope_level === 'district' && ! empty($this->district_id);
    }

    public function hasTehsilAccess(): bool
    {
        return optional($this->role)->scope_level === 'tehsil' && ! empty($this->tehsil_id);
    }

    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }
}
