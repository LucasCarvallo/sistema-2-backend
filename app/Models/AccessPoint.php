<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccessPoint extends Model
{
    protected $fillable = [
        'bssid',
        'ssid',
        'hidden',
        'first_rssi',
        'last_rssi',
        'last_channel',
        'detections_count',
        'first_seen_at',
        'last_seen_at',
    ];

    protected $casts = [
        'hidden' => 'boolean',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function detections(): HasMany
    {
        return $this->hasMany(AccessPointDetection::class);
    }

    public static function findOrCreateByBssid(string $bssid): self
    {
        return self::firstOrCreate(
            ['bssid' => $bssid],
            ['first_seen_at' => now()]
        );
    }

    public function recordDetection(int $rssi, int $channel, ScanSession $session): void
    {
        $this->detections()->create([
            'scan_session_id' => $session->id,
            'rssi' => $rssi,
            'channel' => $channel,
        ]);

        $this->update([
            'last_rssi' => $rssi,
            'last_channel' => $channel,
            'last_seen_at' => now(),
            'detections_count' => $this->detections_count + 1,
        ]);
    }
}
