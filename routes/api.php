<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

Route::get('/health', function () {
    return response()->json([
        'ok' => true,
        'app' => 'sistema-lucas-api',
        'version' => 'v1',
    ]);
});

Route::get('/users', function () {
    return response()->json(
        User::query()
            ->select(['id', 'name', 'email'])
            ->orderBy('id')
            ->get(),
    );
});

Route::post('/login', function (Request $request) {
    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required', 'string', 'min:6'],
    ]);

    $user = User::query()->where('email', $credentials['email'])->first();

    if (!$user || !Hash::check($credentials['password'], $user->password)) {
        return response()->json([
            'message' => 'Credenciales inválidas.',
        ], 401);
    }

    $token = Str::random(60);
    $user->forceFill(['remember_token' => $token])->save();

    return response()->json([
        'token' => $token,
        'user' => $user->only(['id', 'name', 'email']),
    ]);
});

Route::post('/logout', function (Request $request) {
    $token = $request->bearerToken();

    if ($token) {
        User::query()->where('remember_token', $token)->update(['remember_token' => null]);
    }

    return response()->json(['message' => 'Logged out successfully']);
});

Route::get('/me', function (Request $request) {
    $token = $request->bearerToken();

    if (!$token) {
        return response()->json(['message' => 'No autenticado.'], 401);
    }

    $user = User::query()
        ->where('remember_token', $token)
        ->first();

    if (!$user) {
        return response()->json(['message' => 'No autenticado.'], 401);
    }

    return response()->json($user->only(['id', 'name', 'email']));
});
