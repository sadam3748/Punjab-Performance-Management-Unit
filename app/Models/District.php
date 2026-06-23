<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class District extends Model
{
    protected $fillable = [
        'division_id',
        'name',
        'code',
        'tier',
        'is_active',
    ];

    protected $casts = [
        'tier' => 'integer',
        'is_active' => 'boolean',
    ];

    public function division()
    {
        return $this->belongsTo(Division::class);
    }

    public function tehsils()
    {
        return $this->hasMany(Tehsil::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
