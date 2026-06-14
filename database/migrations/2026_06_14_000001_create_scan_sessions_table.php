<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scan_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('device_id')->index();
            $table->enum('scan_mode', ['managed', 'monitor'])->default('managed');
            $table->integer('total_found')->default(0);
            $table->integer('visible')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scan_sessions');
    }
};
