<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
    $credentials = $request->validate([
        'email'    => ['required', 'email'],
        'password' => ['required'],
    ]);

    $user = User::where('email', $credentials['email'])->first();

    if (! $user || ! Hash::check($credentials['password'], $user->password)) {
        return response()->json(['message' => 'Kredensial tidak cocok'], 401);
    }

    return response()->json([
        'access_token' => $user->createToken('auth_token')->plainTextToken,
        'token_type'   => 'Bearer',
    ]);
    }

    public function logout(Request $request)
    {
    $request->user()->currentAccessToken()->delete();

    return response()->json(['message' => 'Logout berhasil']);
    }

    public function register(Request $request)
    {
    $validated = $request->validate([
        'name'     => ['required', 'string', 'max:255'],
        'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
        'password' => ['required', 'string', 'min:8', 'confirmed'],
    ]);

    $user = User::create([
        'name'     => $validated['name'],
        'email'    => $validated['email'],
        'password' => Hash::make($validated['password']),
    ]);

    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
        'message'      => 'Registrasi berhasil',
        'access_token' => $token,
        'token_type'   => 'Bearer',
    ], 201);
    }
}