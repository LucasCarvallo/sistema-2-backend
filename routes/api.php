<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

$mockUsers = [
    [
        'id' => 1,
        'name' => 'Lucas Admin',
        'email' => 'lucas@example.com',
    ],
    [
        'id' => 2,
        'name' => 'Ana Dev',
        'email' => 'ana@example.com',
    ],
    [
        'id' => 3,
        'name' => 'Mario QA',
        'email' => 'mario@example.com',
    ],
];

Route::get('/health', function () {
    return response()->json([
        'ok' => true,
        'app' => 'sistema-lucas-api',
        'version' => 'v1',
    ]);
});

Route::get('/users', function () use ($mockUsers) {
    return response()->json($mockUsers);
});

Route::post('/login', function (Request $request) use ($mockUsers) {
    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required', 'string', 'min:6'],
    ]);

    $user = collect($mockUsers)
        ->firstWhere('email', $credentials['email'])
        ?? [
            'id' => 999,
            'name' => 'Mock User',
            'email' => $credentials['email'],
        ];

    return response()->json([
        'token' => 'fake-token-'.Str::random(40),
        'user' => $user,
    ]);
});

Route::post('/logout', function () {
    return response()->json(['message' => 'Logged out successfully']);
});

Route::get('/me', function (Request $request) use ($mockUsers) {
    // Simulate authenticated user (for demo purposes)
    $user = $mockUsers[0]; // Always return the first user

    return response()->json($user);
});
