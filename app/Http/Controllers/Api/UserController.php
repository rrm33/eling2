<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Tampilkan daftar user
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Admin lihat semua, Kasir lihat yang satu toko saja
        if ($user->role === 'admin') {
            $users = User::with('shop')->latest()->get();
        } else {
            $users = User::where('shop_id', $user->shop_id)->latest()->get();
        }

        return response()->json($users);
    }

    /**
     * Simpan user baru
     */
    public function store(Request $request)
    {
        $authUser = $request->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|in:admin,cashier,kurir',
            'shop_id' => [
                'nullable',
                Rule::requiredIf($request->role === 'cashier'),
                'exists:shops,id'
            ],
        ]);

        // Pencegahan: Karyawan biasa tidak boleh buat Admin
        if ($authUser->role !== 'admin' && $request->role === 'admin') {
            return response()->json(['message' => 'Anda tidak memiliki akses membuat Admin.'], 403);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'shop_id' => in_array($request->role, ['admin', 'kurir']) ? null : ($request->shop_id ?? $authUser->shop_id),
        ]);

        return response()->json(['message' => 'User berhasil dibuat.', 'data' => $user], 201);
    }

    /**
     * Update data user
     */
    public function update(Request $request, User $user)
    {
        $authUser = $request->user();

        // Otorisasi: Pastikan Kasir hanya bisa edit temannya di toko yang sama
        if ($authUser->role !== 'admin' && $authUser->shop_id !== $user->shop_id) {
            return response()->json(['message' => 'Anda tidak memiliki akses ke user ini.'], 403);
        }

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8',
            'role' => 'sometimes|required|in:admin,cashier,kurir',
            'shop_id' => 'nullable|exists:shops,id',
        ]);

        // Update data
        if ($request->has('name')) $user->name = $request->name;
        if ($request->has('email')) $user->email = $request->email;
        if ($request->has('password')) $user->password = Hash::make($request->password);
        
        // Hanya Admin yang bisa ganti Role/Shop user lain
        if ($authUser->role === 'admin') {
            if ($request->has('role')) $user->role = $request->role;
            if ($request->has('shop_id')) $user->shop_id = in_array($user->role, ['admin', 'kurir']) ? null : $request->shop_id;
        }

        $user->save();

        return response()->json(['message' => 'User berhasil diperbarui.', 'data' => $user]);
    }

    /**
     * Hapus user
     */
    public function destroy(Request $request, User $user)
    {
        $authUser = $request->user();

        // Tidak boleh hapus diri sendiri
        if ($authUser->id === $user->id) {
            return response()->json(['message' => 'Anda tidak bisa menghapus akun Anda sendiri.'], 422);
        }

        // Otorisasi penghapusan
        if ($authUser->role !== 'admin' && ($authUser->shop_id !== $user->shop_id || $user->role === 'admin')) {
            return response()->json(['message' => 'Anda tidak memiliki izin menghapus user ini.'], 403);
        }

        $user->delete();

        return response()->json(['message' => 'User berhasil dihapus.']);
    }

    /**
     * Update FCM Token
     */
    public function updateFcmToken(Request $request)
    {
        $request->validate([
            'fcm_token' => 'nullable|string'
        ]);

        $user = $request->user();
        $user->fcm_token = $request->fcm_token;
        $user->save();

        return response()->json(['message' => 'FCM Token updated']);
    }
}
