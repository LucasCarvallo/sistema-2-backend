<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WifiClient extends Model
{
    protected $fillable = [
        'mac',
        'first_seen_at',
        'last_seen_at',
        'detections_count',
    ];

    protected $casts = [
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function detections(): HasMany
    {
        return $this->hasMany(WifiClientDetection::class);
    }

    public static function findOrCreateByMac(string $mac): self
    {
        return self::firstOrCreate(
            ['mac' => strtoupper($mac)],
            ['first_seen_at' => now()]
        );
    }
}
