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

    $token = $user->createToken('api')->plainTextToken;

    return response()->json([
        'token' => $token,
        'token_type' => 'Bearer',
        'user' => $user->only(['id', 'name', 'email']),
    ]);
});

Route::middleware('auth:sanctum')->group(function () {
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
