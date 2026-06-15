<?php
use App\Models\AccessPoint;
use App\Models\AccessPointDetection;
use App\Models\ScanSession;
use App\Models\WifiClient;
use App\Models\WifiClientDetection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

// php artisan serve --host=0.0.0.0 --port=8000
Route::post('/wifi-scan', function (Request $request) {
    $data = $request->validate([
        'total_found' => 'required|integer|min:0',
        'visible' => 'required|integer|min:0',
        'devices' => 'required|array',
        'devices.*.ssid' => 'nullable|string',
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

    foreach ($data['devices'] as $i => $device) {
        if (($device['hidden'] ?? false) === false && empty($device['ssid'])) {
            return response()->json([
                'ok' => false,
                'message' => "SSID requerido cuando hidden=false en devices.$i",
            ], 422);
        }
    }

    // Crear sesión de escaneo
    $session = ScanSession::create([
        'device_id' => $request->ip() ?? 'unknown',
        'scan_mode' => 'managed',
        'total_found' => $data['total_found'],
        'visible' => $data['visible'],
    ]);

    // Procesar cada dispositivo detectado
    foreach ($data['devices'] as $device) {
        $ap = AccessPoint::findOrCreateByBssid($device['bssid']);
        
        // Actualizar datos si es la primera vez o si tenemos SSID
        if (!$ap->wasRecentlyCreated && $device['ssid']) {
            $ap->update([
                'ssid' => $device['ssid'],
                'hidden' => $device['hidden'],
            ]);
        } elseif ($ap->wasRecentlyCreated) {
            $ap->update([
                'ssid' => $device['ssid'],
                'hidden' => $device['hidden'],
                'first_rssi' => $device['rssi'],
                'last_channel' => $device['channel'],
            ]);
        }

        // Registrar detección
        $ap->recordDetection($device['rssi'], $device['channel'], $session);
    }

    return response()->json([
        'ok' => true,
        'session_id' => $session->id,
        'summary' => [
            'total_found' => $data['total_found'],
            'visible' => $data['visible'],
            'devices_stored' => count($data['devices']),
        ],
    ]);
});

// POST /wifi-clients - Detecciones de clientes en modo monitor
Route::post('/wifi-clients', function (Request $request) {
    $data = $request->validate([
        'device_mac' => [
            'nullable',
            'string',
            'regex:/^([0-9A-Fa-f]{2}:){5}[0-9A-Fa-f]{2}$/',
        ],
        'total_found' => 'required|integer|min:0',
        'visible' => 'required|integer|min:0',
        'clients' => 'required|array',
        'clients.*.mac' => [
            'required',
            'string',
            'regex:/^([0-9A-Fa-f]{2}:){5}[0-9A-Fa-f]{2}$/',
        ],
        'clients.*.associated_bssid' => [
            'nullable',
            'string',
            'regex:/^([0-9A-Fa-f]{2}:){5}[0-9A-Fa-f]{2}$/',
        ],
        'clients.*.rssi' => 'required|integer|between:-120,0',
        'clients.*.channel' => 'required|integer|min:1|max:14',
    ]);

    if ($data['visible'] !== count($data['clients'])) {
        return response()->json([
            'ok' => false,
            'message' => 'visible no coincide con la cantidad de clients',
        ], 422);
    }

    $deviceId = $data['device_mac'] ?? ($request->ip() ?? 'unknown');

    $session = ScanSession::create([
        'device_id' => $deviceId,
        'scan_mode' => 'monitor',
        'total_found' => $data['total_found'],
        'visible' => $data['visible'],
    ]);

    foreach ($data['clients'] as $client) {
        $wifiClient = WifiClient::findOrCreateByMac($client['mac']);

        WifiClientDetection::create([
            'scan_session_id' => $session->id,
            'wifi_client_id' => $wifiClient->id,
            'associated_bssid' => $client['associated_bssid'] ?? null,
            'rssi' => $client['rssi'],
            'channel' => $client['channel'],
            'detected_at' => now(),
        ]);

        $wifiClient->update([
            'last_seen_at' => now(),
            'detections_count' => $wifiClient->detections_count + 1,
        ]);
    }

    return response()->json([
        'ok' => true,
        'session_id' => $session->id,
        'summary' => [
            'total_found' => $data['total_found'],
            'visible' => $data['visible'],
            'clients_stored' => count($data['clients']),
        ],
    ]);
});

// PATCH /wifi-clients/{mac}/alias - Guardar alias para cliente por MAC
Route::patch('/wifi-clients/{mac}/alias', function (Request $request, string $mac) {
    $mac = trim($mac);

    if (!preg_match('/^([0-9A-Fa-f]{2}:){5}[0-9A-Fa-f]{2}$/', $mac)) {
        return response()->json([
            'ok' => false,
            'message' => 'Formato MAC invalido. Usa AA:BB:CC:DD:EE:FF',
        ], 422);
    }

    $data = $request->validate([
        'alias' => 'nullable|string|max:80',
    ]);

    $client = WifiClient::query()->whereRaw('LOWER(mac) = ?', [strtolower($mac)])->first();

    if (!$client) {
        return response()->json([
            'ok' => false,
            'message' => 'Cliente no encontrado',
        ], 404);
    }

    $alias = trim((string) ($data['alias'] ?? ''));
    $client->update([
        'alias' => $alias !== '' ? $alias : null,
    ]);

    return response()->json([
        'ok' => true,
        'client' => [
            'mac' => $client->mac,
            'alias' => $client->alias,
        ],
    ]);
});

// GET /scan-sessions - Listar todas las sesiones de escaneo
Route::get('/scan-sessions', function () {
    return response()->json(
        ScanSession::query()
            ->select(['id', 'device_id', 'scan_mode', 'total_found', 'visible', 'created_at'])
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get(),
    );
});

// GET /access-point-detections-grouped - Resumen agrupado por BSSID
// Query params opcionales:
// - mac: BSSID exacta (AA:BB:CC:DD:EE:FF)
// - limit: cantidad maxima de redes agrupadas
// - recent_seconds: solo detecciones dentro de los ultimos N segundos (1..3600)
Route::get('/access-point-detections-grouped', function (Request $request) {
    $mac = trim((string) $request->query('mac', ''));
    $limit = (int) $request->query('limit', 100);
    $recentSeconds = (int) $request->query('recent_seconds', 0);

    if ($limit < 1) {
        $limit = 1;
    }

    if ($recentSeconds < 0) {
        $recentSeconds = 0;
    }
    if ($recentSeconds > 3600) {
        $recentSeconds = 3600;
    }

    if ($mac !== '' && !preg_match('/^([0-9A-Fa-f]{2}:){5}[0-9A-Fa-f]{2}$/', $mac)) {
        return response()->json([
            'ok' => false,
            'message' => 'Formato MAC invalido. Usa AA:BB:CC:DD:EE:FF',
        ], 422);
    }

    $query = DB::table('access_point_detections as d')
        ->join('access_points as ap', 'ap.id', '=', 'd.access_point_id');

    if ($mac !== '') {
        $query->whereRaw('LOWER(ap.bssid) = ?', [strtolower($mac)]);
    }

    if ($recentSeconds > 0) {
        $query->where('d.created_at', '>=', now()->subSeconds($recentSeconds));
    }

    $rows = $query
        ->groupBy('ap.id', 'ap.bssid', 'ap.ssid', 'ap.hidden', 'ap.last_channel')
        ->selectRaw('ap.bssid')
        ->selectRaw('ap.ssid')
        ->selectRaw('ap.hidden')
        ->selectRaw('ap.last_channel as channel')
        ->selectRaw('COUNT(*) as samples')
        ->selectRaw('MAX(d.rssi) as best_rssi')
        ->selectRaw('ROUND(AVG(d.rssi), 1) as avg_rssi')
        ->selectRaw('MAX(d.created_at) as last_detected_at')
        ->selectRaw('MAX(d.scan_session_id) as last_scan_session_id')
        ->orderByDesc('best_rssi')
        ->limit($limit)
        ->get();

    return response()->json([
        'items' => $rows,
        'filters' => [
            'mac' => $mac !== '' ? strtoupper($mac) : null,
            'limit' => $limit,
            'recent_seconds' => $recentSeconds > 0 ? $recentSeconds : null,
        ],
    ]);
});

// GET /wifi-client-detections-grouped - Resumen agrupado de clientes monitor
// Query params opcionales:
// - associated_bssid: BSSID exacta (AA:BB:CC:DD:EE:FF)
// - client_mac: MAC cliente exacta (AA:BB:CC:DD:EE:FF)
// - limit: cantidad maxima de clientes
// - recent_seconds: solo detecciones dentro de los ultimos N segundos (1..86400)
Route::get('/wifi-client-detections', function (Request $request) {
    $associatedBssid = trim((string) $request->query('associated_bssid', ''));
    $clientMac = trim((string) $request->query('client_mac', ''));
    $scanSessionId = (int) $request->query('scan_session_id', 0);
    $limit = (int) $request->query('limit', 500);
    $recentSeconds = (int) $request->query('recent_seconds', 0);

    if ($limit < 1) {
        $limit = 1;
    }
    if ($limit > 2000) {
        $limit = 2000;
    }

    if ($recentSeconds < 0) {
        $recentSeconds = 0;
    }
    if ($recentSeconds > 86400) {
        $recentSeconds = 86400;
    }

    $macRegex = '/^([0-9A-Fa-f]{2}:){5}[0-9A-Fa-f]{2}$/';

    if ($associatedBssid !== '' && !preg_match($macRegex, $associatedBssid)) {
        return response()->json([
            'ok' => false,
            'message' => 'Formato associated_bssid invalido. Usa AA:BB:CC:DD:EE:FF',
        ], 422);
    }

    if ($clientMac !== '' && !preg_match($macRegex, $clientMac)) {
        return response()->json([
            'ok' => false,
            'message' => 'Formato client_mac invalido. Usa AA:BB:CC:DD:EE:FF',
        ], 422);
    }

    $query = DB::table('wifi_client_detections as d')
        ->join('wifi_clients as c', 'c.id', '=', 'd.wifi_client_id')
        ->join('scan_sessions as s', 's.id', '=', 'd.scan_session_id');

    if ($associatedBssid !== '') {
        $query->whereRaw('LOWER(d.associated_bssid) = ?', [strtolower($associatedBssid)]);
    }

    if ($clientMac !== '') {
        $query->whereRaw('LOWER(c.mac) = ?', [strtolower($clientMac)]);
    }

    if ($scanSessionId > 0) {
        $query->where('d.scan_session_id', '=', $scanSessionId);
    }

    if ($recentSeconds > 0) {
        $query->where('d.detected_at', '>=', now()->subSeconds($recentSeconds));
    }

    $rows = $query
        ->selectRaw('d.id')
        ->selectRaw('d.scan_session_id')
        ->selectRaw('s.device_id')
        ->selectRaw('s.scan_mode')
        ->selectRaw('c.mac as client_mac')
        ->selectRaw('c.alias as client_alias')
        ->selectRaw('d.associated_bssid')
        ->selectRaw('d.rssi')
        ->selectRaw('d.channel')
        ->selectRaw('d.detected_at')
        ->orderByDesc('d.detected_at')
        ->limit($limit)
        ->get();

    return response()->json([
        'items' => $rows,
        'filters' => [
            'associated_bssid' => $associatedBssid !== '' ? strtoupper($associatedBssid) : null,
            'client_mac' => $clientMac !== '' ? strtoupper($clientMac) : null,
            'scan_session_id' => $scanSessionId > 0 ? $scanSessionId : null,
            'limit' => $limit,
            'recent_seconds' => $recentSeconds > 0 ? $recentSeconds : null,
        ],
    ]);
});

Route::get('/wifi-client-detections-grouped', function (Request $request) {
    $associatedBssid = trim((string) $request->query('associated_bssid', ''));
    $clientMac = trim((string) $request->query('client_mac', ''));
    $limit = (int) $request->query('limit', 200);
    $recentSeconds = (int) $request->query('recent_seconds', 0);

    if ($limit < 1) {
        $limit = 1;
    }

    if ($recentSeconds < 0) {
        $recentSeconds = 0;
    }
    if ($recentSeconds > 86400) {
        $recentSeconds = 86400;
    }

    $macRegex = '/^([0-9A-Fa-f]{2}:){5}[0-9A-Fa-f]{2}$/';

    if ($associatedBssid !== '' && !preg_match($macRegex, $associatedBssid)) {
        return response()->json([
            'ok' => false,
            'message' => 'Formato associated_bssid invalido. Usa AA:BB:CC:DD:EE:FF',
        ], 422);
    }

    if ($clientMac !== '' && !preg_match($macRegex, $clientMac)) {
        return response()->json([
            'ok' => false,
            'message' => 'Formato client_mac invalido. Usa AA:BB:CC:DD:EE:FF',
        ], 422);
    }

    $query = DB::table('wifi_client_detections as d')
        ->join('wifi_clients as c', 'c.id', '=', 'd.wifi_client_id');

    if ($associatedBssid !== '') {
        $query->whereRaw('LOWER(d.associated_bssid) = ?', [strtolower($associatedBssid)]);
    }

    if ($clientMac !== '') {
        $query->whereRaw('LOWER(c.mac) = ?', [strtolower($clientMac)]);
    }

    if ($recentSeconds > 0) {
        $query->where('d.detected_at', '>=', now()->subSeconds($recentSeconds));
    }

    $rows = $query
        ->groupBy('c.id', 'c.mac', 'c.alias', 'd.associated_bssid')
        ->selectRaw('c.mac as client_mac')
        ->selectRaw('c.alias as client_alias')
        ->selectRaw('d.associated_bssid')
        ->selectRaw('COUNT(*) as samples')
        ->selectRaw('MAX(d.rssi) as best_rssi')
        ->selectRaw('ROUND(AVG(d.rssi), 1) as avg_rssi')
        ->selectRaw('MAX(d.detected_at) as last_detected_at')
        ->selectRaw('MAX(d.scan_session_id) as last_scan_session_id')
        ->orderByDesc('last_detected_at')
        ->limit($limit)
        ->get();

    return response()->json([
        'items' => $rows,
        'filters' => [
            'associated_bssid' => $associatedBssid !== '' ? strtoupper($associatedBssid) : null,
            'client_mac' => $clientMac !== '' ? strtoupper($clientMac) : null,
            'limit' => $limit,
            'recent_seconds' => $recentSeconds > 0 ? $recentSeconds : null,
        ],
    ]);
});

// GET /wifi-client-counts-by-bssid - Conteo de clientes unicos por BSSID
// Query params opcionales:
// - recent_seconds: solo detecciones dentro de los ultimos N segundos (1..86400)
Route::get('/wifi-client-counts-by-bssid', function (Request $request) {
    $recentSeconds = (int) $request->query('recent_seconds', 0);

    if ($recentSeconds < 0) {
        $recentSeconds = 0;
    }
    if ($recentSeconds > 86400) {
        $recentSeconds = 86400;
    }

    $query = DB::table('wifi_client_detections as d')
        ->join('wifi_clients as c', 'c.id', '=', 'd.wifi_client_id')
        ->whereNotNull('d.associated_bssid');

    if ($recentSeconds > 0) {
        $query->where('d.detected_at', '>=', now()->subSeconds($recentSeconds));
    }

    $rows = $query
        ->groupBy('d.associated_bssid')
        ->selectRaw('d.associated_bssid as bssid')
        ->selectRaw('COUNT(DISTINCT c.mac) as clients_count')
        ->selectRaw('COUNT(*) as samples')
        ->selectRaw('MAX(d.detected_at) as last_detected_at')
        ->get();

    return response()->json([
        'items' => $rows,
        'filters' => [
            'recent_seconds' => $recentSeconds > 0 ? $recentSeconds : null,
        ],
    ]);
});

// GET /scan-sessions/{id} - Detalles de una sesión con sus access points detectados
Route::get('/scan-sessions/{id}', function ($id) {
    $session = ScanSession::find($id);
    
    if (!$session) {
        return response()->json([
            'ok' => false,
            'message' => 'Sesión no encontrada',
        ], 404);
    }

    $detections = AccessPointDetection::query()
        ->where('scan_session_id', $id)
        ->with('accessPoint:id,bssid,ssid,hidden,last_rssi,last_channel,detections_count')
        ->orderBy('created_at')
        ->get();

    return response()->json([
        'session' => [
            'id' => $session->id,
            'device_id' => $session->device_id,
            'scan_mode' => $session->scan_mode,
            'total_found' => $session->total_found,
            'visible' => $session->visible,
            'created_at' => $session->created_at,
        ],
        'detections' => $detections->map(function ($detection) {
            return [
                'id' => $detection->id,
                'rssi' => $detection->rssi,
                'channel' => $detection->channel,
                'detected_at' => $detection->created_at,
                'access_point' => $detection->accessPoint,
            ];
        }),
    ]);
});
