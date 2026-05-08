<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/videos', function (Request $request) {
    $files = Storage::disk('public')->allFiles('wai/videos');
    $categoryFilter = trim((string) $request->query('category', ''));

    $videosByCategory = collect($files)
        ->filter(function (string $path) {
            return preg_match('/\.(mp4|mov|avi|mkv|webm|m4v)$/i', $path) === 1;
        })
        ->when($categoryFilter !== '', function ($collection) use ($categoryFilter) {
            return $collection->filter(function (string $path) use ($categoryFilter) {
                return str_starts_with($path, 'wai/videos/'.$categoryFilter.'/');
            });
        })
        ->map(function (string $path) {
            $relativePath = preg_replace('#^wai/videos/#', '', $path);
            $segments = explode('/', (string) $relativePath);

            return [
                'name' => basename($path),
                'path' => $path,
                'category' => $segments[0] ?? 'sin-categoria',
                'url' => url('/api/videos/'.ltrim($path, '/')),
                'size' => Storage::disk('public')->size($path),
            ];
        })
        ->groupBy('category')
        ->map(function ($items, $category) {
            return [
                'category' => $category,
                'videos' => collect($items)
                    ->map(function ($item) {
                        return [
                            'name' => $item['name'],
                            'path' => $item['path'],
                            'url' => $item['url'],
                            'size' => $item['size'],
                        ];
                    })
                    ->values(),
            ];
        })
        ->values();

    return response()->json($videosByCategory);
});

Route::get('/videos/{path}', function (string $path) {
    $relativePath = ltrim($path, '/');

    if (str_contains($relativePath, '..')) {
        abort(404);
    }

    $filePath = str_starts_with($relativePath, 'wai/videos/')
        ? $relativePath
        : 'wai/videos/'.$relativePath;

    if (! Storage::disk('public')->exists($filePath)) {
        abort(404);
    }

    return response()->file(Storage::disk('public')->path($filePath));
})->where('path', '.*');
