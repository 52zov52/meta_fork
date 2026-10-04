<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
    /**
     * Аутентификация пользователя через API
     *
     * Принимает email и пароль, возвращает токен для дальнейших запросов.
     *
     * @group Public Endpoints
     *
     * @unauthenticated
     *
     * @bodyParam email string required Email пользователя. Example: admin@i.ru
     * @bodyParam password string required Пароль пользователя. Example: password
     *
     * @response 200 scenario="success" {
     *   "user_id": 1,
     *   "token": "1|nA27RBn90hFX7kV4DwyIuJ7ZZcKhKrEIY6feMPLV43be8212",
     *   "role": "student",
     *   "is_activated": true
     * }
     * @response 401 scenario="unauthorized" {
     *   "message": "Invalid credentials"
     * }
     * @response 422 scenario="validation_error" {
     *   "message": "Validation failed"
     * }
     */
    public function apiLogin(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:8',
        ]);

        if (! Auth::attempt($validated)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user = Auth::user();
        $token = $user->createToken('api-login')->plainTextToken;

        return response()->json([
            'user_id' => $user->id,
            'token' => $token,
            'role' => $user->role,
            'is_activated' => $user->is_activated,
        ], 200);
    }

    /**
     * Handle an incoming authentication request (SPA).
     */
    public function store(LoginRequest $request)
    {
        $request->authenticate();
        $request->session()->regenerate();

        return response()->json([
            'user_id' => Auth::user()->id,
            'role' => Auth::user()->role,
            'is_activated' => Auth::user()->is_activated,
        ]);
    }

    /**
     * Destroy an authenticated session (SPA).
     */
    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Пользователь успешно вышел из аккаунта']);
    }

    /**
     * Get the authenticated user (SPA).
     */
    public function user(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'user_id' => $user->id,
            'role' => $user->role,
            'is_activated' => $user->is_activated,
        ]);
    }
}
