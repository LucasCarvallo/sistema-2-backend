<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScanSession extends Model
{
    protected $fillable = [
        'device_id',
        'scan_mode',
        'total_found',
        'visible',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function detections(): HasMany
    {
        return $this->hasMany(AccessPointDetection::class);
    }

    public function accessPoints()
    {
        return $this->belongsToMany(AccessPoint::class, 'access_point_detections')
            ->withPivot('rssi', 'channel')
            ->withTimestamps();
    }
}
