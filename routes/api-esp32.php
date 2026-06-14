<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// php artisan serve --host=0.0.0.0 --port=8000
Route::post('/wifi-scan', function (Request $request) {
    $data = $request->validate([
        'total_found' => 'required|integer',
        'visible' => 'required|integer',
        'devices' => 'required|array',
    ]);

    // Log::info('WiFi scan recibido', $data);

    return response()->json([
        'ok' => true,
        'received' => $data,
    ]);
});
