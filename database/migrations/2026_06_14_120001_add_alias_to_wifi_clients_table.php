<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wifi_clients', function (Blueprint $table) {
            $table->string('alias', 80)->nullable()->after('mac');
        });
    }

    public function down(): void
    {
        Schema::table('wifi_clients', function (Blueprint $table) {
            $table->dropColumn('alias');
        });
    }
};
