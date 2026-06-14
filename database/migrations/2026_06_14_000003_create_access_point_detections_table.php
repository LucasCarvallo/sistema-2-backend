<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('access_point_detections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scan_session_id')->constrained('scan_sessions')->onDelete('cascade');
            $table->foreignId('access_point_id')->constrained('access_points')->onDelete('cascade');
            $table->integer('rssi');
            $table->tinyInteger('channel');
            $table->timestamps();

            $table->index(['scan_session_id', 'access_point_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_point_detections');
    }
};
