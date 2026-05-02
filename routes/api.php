<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/health', function () {
    return response()->json([
        'ok' => true,
        'app' => 'sistema-lucas-api',
        'version' => 'v1',
    ]);
});

Route::post('/login', function (Request $request) {
    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required', 'string', 'min:6'],
    ]);

    $user = User::query()->where('email', $credentials['email'])->first();

    if (! $user || ! Hash::check($credentials['password'], $user->password)) {
        return response()->json([
            'message' => 'Credenciales inválidas.',
        ], 401);
    }

    $token = $user->createToken('api')->plainTextToken;

    return response()->json([
        'token' => $token,
        'token_type' => 'Bearer',
        'user' => $user->only(['id', 'name', 'email']),
    ]);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/videos', function (Request $request) {
        $files = Storage::disk('public')->allFiles('videos');
        $categoryFilter = trim((string) $request->query('category', ''));

        $videos = collect($files)
            ->filter(function (string $path) {
                return preg_match('/\.(mp4|mov|avi|mkv|webm|m4v)$/i', $path) === 1;
            })
            ->when($categoryFilter !== '', function ($collection) use ($categoryFilter) {
                return $collection->filter(function (string $path) use ($categoryFilter) {
                    return str_starts_with($path, 'videos/'.$categoryFilter.'/');
                });
            })
            ->values()
            ->map(function (string $path) {
                $relativePath = preg_replace('#^videos/#', '', $path);
                $segments = explode('/', (string) $relativePath);

                return [
                    'name' => basename($path),
                    'path' => $path,
                    'category' => $segments[0] ?? 'sin-categoria',
                    'url' => '/api/videos/'.ltrim($path, '/'),
                    'size' => Storage::disk('public')->size($path),
                ];
            });

        return response()->json($videos);
    });

    Route::get('/videos/{path}', function (string $path) {
        $relativePath = ltrim($path, '/');

        if (str_contains($relativePath, '..')) {
            abort(404);
        }

        $filePath = str_starts_with($relativePath, 'videos/')
            ? $relativePath
            : 'videos/'.$relativePath;

        if (! Storage::disk('public')->exists($filePath)) {
            abort(404);
        }

        return response()->file(Storage::disk('public')->path($filePath));
    })->where('path', '.*');

    Route::get('/users', function () {
        return response()->json(
            User::query()
                ->select(['id', 'name', 'email'])
                ->orderBy('id')
                ->get(),
        );
    });

    Route::post('/logout', function (Request $request) {
        $token = $request->user()?->currentAccessToken();

        if ($token) {
            $token->delete();
        }

        return response()->json(['message' => 'Logged out successfully']);
    });

    Route::get('/me', function (Request $request) {
        return response()->json(
            $request->user()->only(['id', 'name', 'email']),
        );
    });
});
