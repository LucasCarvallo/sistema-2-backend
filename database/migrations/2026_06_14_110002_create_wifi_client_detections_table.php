<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wifi_client_detections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scan_session_id')->constrained('scan_sessions')->onDelete('cascade');
            $table->foreignId('wifi_client_id')->constrained('wifi_clients')->onDelete('cascade');
            $table->string('associated_bssid', 17)->nullable()->index();
            $table->integer('rssi');
            $table->tinyInteger('channel')->nullable();
            $table->timestamp('detected_at')->nullable();
            $table->timestamps();

            $table->index(['scan_session_id', 'wifi_client_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wifi_client_detections');
    }
};
