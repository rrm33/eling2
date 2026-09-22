<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AttendanceController extends Controller
{
    /**
     * Catat Absensi
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'type' => 'required|in:in,out',
                'latitude' => 'required',
                'longitude' => 'required',
                'photo' => 'required',
                'shop_id' => 'required|exists:shops,id',
            ]);

            $user = $request->user();
            $date = $request->created_at ? date('Y-m-d', strtotime($request->created_at)) : now()->toDateString();

            $attendance = Attendance::where('user_id', $user->id)->where('date', $date)->first();

            $shop = Shop::find($request->shop_id);
            // Jika ini sinkronisasi offline (ada created_at), kita abaikan cek jarak
            if (!$request->created_at && $shop->latitude && $shop->longitude) {
                $distance = $this->calculateDistance($request->latitude, $request->longitude, $shop->latitude, $shop->longitude);
                if ($distance > 100) {
                    return response()->json(['message' => "Di luar jangkauan (".round($distance)."m)."], 422);
                }
            }

            if ($request->hasFile('photo')) {
                // Mendukung upload file langsung (Multipart)
                $fileName = $request->file('photo')->store('attendances', 'public');
            } else {
                // Mendukung Base64 (Legacy)
                $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $request->photo));
                $fileName = 'attendances/' . uniqid() . '.png';
                Storage::disk('public')->put($fileName, $imageData);
            }

            if ($request->type === 'in') {
                if ($attendance && $attendance->in_time && !$request->force_update) {
                    return response()->json(['status' => 'exists', 'message' => 'Anda sudah absen MASUK hari ini. Update data?']);
                }
                if ($attendance && $attendance->in_photo) Storage::disk('public')->delete($attendance->in_photo);

                $attendance = Attendance::updateOrCreate(
                    ['user_id' => $user->id, 'date' => $date],
                    [
                        'shop_id' => $request->shop_id,
                        'in_time' => $request->created_at ? date('H:i:s', strtotime($request->created_at)) : now()->toTimeString(),
                        'in_latitude' => $request->latitude,
                        'in_longitude' => $request->longitude,
                        'in_photo' => $fileName,
                        'in_note' => $request->note ?? 'Masuk',
                    ]
                );

                // Set timestamp created_at jika dari client
                if ($request->created_at) {
                    $attendance->created_at = \Carbon\Carbon::parse($request->created_at);
                    $attendance->save();
                }

                $msg = 'Berhasil absen MASUK.';
            } else {
                if (!$attendance) return response()->json(['message' => 'Belum absen masuk hari ini.'], 422);
                if ($attendance->out_time && !$request->force_update) {
                    return response()->json(['status' => 'exists', 'message' => 'Anda sudah absen PULANG hari ini. Update?']);
                }
                if ($attendance->out_photo) Storage::disk('public')->delete($attendance->out_photo);

                $attendance->update([
                    'out_time' => $request->created_at ? date('H:i:s', strtotime($request->created_at)) : now()->toTimeString(),
                    'out_latitude' => $request->latitude,
                    'out_longitude' => $request->longitude,
                    'out_photo' => $fileName,
                    'out_note' => $request->note ?? 'Pulang',
                ]);

                // Set timestamp updated_at jika dari client (waktu absen pulang)
                if ($request->created_at) {
                    $attendance->updated_at = \Carbon\Carbon::parse($request->created_at);
                    $attendance->save();
                }

                $msg = 'Berhasil absen PULANG.';
            }

            return response()->json(['message' => $msg, 'data' => $attendance]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error Server: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Riwayat Absensi (Sekarang berdasarkan User agar yang belum absen tetap muncul)
     */
    public function history(Request $request)
    {
        $authUser = $request->user();
        $date = $request->query('date', now()->toDateString()); // Default hari ini

        // Query Utama: Ambil semua User
        $userQuery = User::with(['shop', 'attendances' => function($q) use ($date) {
            $q->where('date', $date);
        }]);

        if ($authUser->role !== 'admin') {
            // Jika kasir, hanya lihat diri sendiri atau rekan kerja satu toko
            $userQuery->where('shop_id', $authUser->shop_id);
        }

        $users = $userQuery->paginate(20);
        
        $users->getCollection()->transform(function($user) use ($date) {
            $attendance = $user->attendances->first();
            return [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role,
                'shop' => $user->shop,
                'date' => $date,
                'in_time' => $attendance ? $attendance->in_time : null,
                'in_photo' => $attendance ? $attendance->in_photo : null,
                'out_time' => $attendance ? $attendance->out_time : null,
                'out_photo' => $attendance ? $attendance->out_photo : null,
                'status' => $attendance ? 'Sudah Absen' : 'Belum Absen',
            ];
        });

        return response()->json($users);
    }

    /**
     * Hapus Data Absensi (Khusus Admin)
     */
    public function destroy(Request $request, $id)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Hanya Admin yang boleh menghapus data.'], 403);
        }

        try {
            $attendance = Attendance::findOrFail($id);
            
            // Hapus foto jika ada
            if ($attendance->in_photo) Storage::disk('public')->delete($attendance->in_photo);
            if ($attendance->out_photo) Storage::disk('public')->delete($attendance->out_photo);
            
            $attendance->delete();

            return response()->json(['message' => 'Data absensi berhasil dihapus.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal menghapus: ' . $e->getMessage()], 500);
        }
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000;
        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);
        $a = sin($latDelta / 2) * sin($latDelta / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lonDelta / 2) * sin($lonDelta / 2);
        return $earthRadius * (2 * atan2(sqrt($a), sqrt(1 - $a)));
    }
}
