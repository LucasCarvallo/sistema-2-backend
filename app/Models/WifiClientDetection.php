<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WifiClientDetection extends Model
{
    protected $fillable = [
        'scan_session_id',
        'wifi_client_id',
        'associated_bssid',
        'rssi',
        'channel',
        'detected_at',
    ];

    protected $casts = [
        'detected_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function scanSession(): BelongsTo
    {
        return $this->belongsTo(ScanSession::class);
    }

    public function wifiClient(): BelongsTo
    {
        return $this->belongsTo(WifiClient::class);
    }
}
