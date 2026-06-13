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
