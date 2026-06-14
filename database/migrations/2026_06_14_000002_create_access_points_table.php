<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('access_points', function (Blueprint $table) {
            $table->id();
            $table->string('bssid', 17)->unique()->index();
            $table->string('ssid')->nullable()->index();
            $table->boolean('hidden')->default(false);
            $table->integer('first_rssi')->nullable();
            $table->integer('last_rssi')->nullable();
            $table->tinyInteger('last_channel')->nullable();
            $table->bigInteger('detections_count')->default(0);
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_points');
    }
};
