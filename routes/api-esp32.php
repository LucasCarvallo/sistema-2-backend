<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// php artisan serve --host=0.0.0.0 --port=8000
Route::post('/wifi-scan', function (Request $request) {
    $data = $request->validate([
        'total_found' => 'required|integer|min:0',
        'visible' => 'required|integer|min:0',
        'devices' => 'required|array',
        'devices.*.ssid' => 'required|string',
        'devices.*.bssid' => [
            'required',
            'string',
            'regex:/^([0-9A-Fa-f]{2}:){5}[0-9A-Fa-f]{2}$/',
        ],
        'devices.*.rssi' => 'required|integer|between:-120,0',
        'devices.*.channel' => 'required|integer|min:1|max:14',
        'devices.*.hidden' => 'required|boolean',
    ]);

    if ($data['visible'] !== count($data['devices'])) {
        return response()->json([
            'ok' => false,
            'message' => 'visible no coincide con la cantidad de devices',
        ], 422);
    }

    return response()->json([
        'ok' => true,
        'summary' => [
            'total_found' => $data['total_found'],
            'visible' => $data['visible'],
            'first_bssid' => $data['devices'][0]['bssid'] ?? null,
        ],
    ]);
});
