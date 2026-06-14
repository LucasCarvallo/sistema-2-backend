<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccessPointDetection extends Model
{
    protected $fillable = [
        'scan_session_id',
        'access_point_id',
        'rssi',
        'channel',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function scanSession(): BelongsTo
    {
        return $this->belongsTo(ScanSession::class);
    }

    public function accessPoint(): BelongsTo
    {
        return $this->belongsTo(AccessPoint::class);
    }
}
