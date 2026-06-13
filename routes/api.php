<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'ok' => true,
        'app' => 'sistema-lucas-api',
        'version' => 'v1',
    ]);
});

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

require __DIR__.'/api-videos.php';
require __DIR__.'/api-auth.php';

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/users', function () {
        return response()->json(
            User::query()
                ->select(['id', 'name', 'email'])
                ->orderBy('id')
                ->get(),
        );
    });
});
