<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::with('shop')->where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Login gagal, email atau password salah.'], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user
        ]);
    }

    public function loginGoogle(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::with('shop')->where('email', $request->email)->first();

        if (! $user) {
            return response()->json([
                'message' => 'Email Google Anda belum didaftarkan di sistem. Hubungi Admin.'
            ], 403);
        }

        // Jika user ada, buatkan token login
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Berhasil logout.'
        ]);
    }

    public function me(Request $request)
    {
        // Paksa ambil data segar dari DB agar stok 179 terbaca, bukan 200
        $user = \App\Models\User::with('shop')->find($request->user()->id);
        
        return response()->json([
            'status' => 'success',
            'data' => $user
        ]);
    }
}
