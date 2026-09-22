<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CashierShift;
use App\Models\Transaction;
use App\Models\Finance;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CashierShiftController extends Controller
{
    public function index(Request $request)
    {
        $shifts = CashierShift::with(['user', 'shop'])->orderBy('id', 'desc')->paginate(20);
        foreach ($shifts as $shift) {
            if ($shift->status == 'open') {
                $this->calculateShiftData($shift);
            }
        }
        return response()->json($shifts);
    }

    public function current(Request $request)
    {
        $shift = CashierShift::where('user_id', $request->user()->id)
            ->where('status', 'open')
            ->first();
        
        if ($shift) {
            $this->calculateShiftData($shift);
        }

        return response()->json($shift);
    }

    private function calculateShiftData(&$shift)
    {
        $shopId = $shift->shop_id;
        $userId = $shift->user_id;
        $startTime = $shift->start_time;

        // Hitung Jualan
        $shift->total_sales = (double) Transaction::where('shop_id', $shopId)
            ->where('user_id', $userId)
            ->where('status', '!=', 'void')
            ->where('created_at', '>=', $startTime)
            ->sum('total_price');
        
        // Hitung Pemasukan Manual
        $shift->total_income = (double) Finance::where('shop_id', $shopId)
            ->where('user_id', $userId)
            ->where('type', 'income')
            ->where('status', 'active')
            ->where('created_at', '>=', $startTime)
            ->sum('amount');

        // Hitung Pengeluaran Manual
        $shift->total_expense = (double) Finance::where('shop_id', $shopId)
            ->where('user_id', $userId)
            ->where('type', 'expense')
            ->where('status', 'active')
            ->where('created_at', '>=', $startTime)
            ->sum('amount');
        
        $shift->expected_balance = ((double)$shift->starting_cash + $shift->total_sales + $shift->total_income) - $shift->total_expense;
    }

    public function open(Request $request)
    {
        try {
            $request->validate(['starting_cash' => 'required|numeric']);

            $existing = CashierShift::where('user_id', $request->user()->id)->where('status', 'open')->first();
            if ($existing) return response()->json(['message' => 'Kasir sudah terbuka'], 422);

            $shift = CashierShift::create([
                'user_id' => $request->user()->id,
                'shop_id' => $request->user()->shop_id,
                'start_time' => $request->created_at ? Carbon::parse($request->created_at) : Carbon::now(),
                'starting_cash' => $request->starting_cash,
                'status' => 'open'
            ]);

            return response()->json($shift, 201);
        } catch (\Exception $e) {
            Log::error("Gagal Buka Kasir: " . $e->getMessage());
            return response()->json(['message' => 'Gagal membuka kasir: ' . $e->getMessage()], 500);
        }
    }

    public function close(Request $request)
    {
        try {
            $request->validate(['actual_cash' => 'required|numeric']);

            $shift = CashierShift::where('user_id', $request->user()->id)->where('status', 'open')->first();
            if (!$shift) return response()->json(['message' => 'Tidak ada kasir yang terbuka'], 422);

            $this->calculateShiftData($shift);

            $actualCash = (double) $request->actual_cash;
            $difference = $actualCash - $shift->expected_balance;

            $shift->update([
                'end_time' => $request->closed_at ? Carbon::parse($request->closed_at) : Carbon::now(),
                'total_sales' => $shift->total_sales,
                'total_income' => $shift->total_income,
                'total_expense' => $shift->total_expense,
                'expected_balance' => $shift->expected_balance,
                'actual_cash' => $actualCash,
                'difference' => $difference,
                'status' => 'closed',
                'note' => $request->note ?? 'Tutup via Mobile'
            ]);

            return response()->json(['message' => 'Kasir berhasil ditutup', 'data' => $shift]);
        } catch (\Exception $e) {
            Log::error("Gagal Tutup Kasir: " . $e->getMessage());
            return response()->json(['message' => 'Gagal menutup kasir: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Hapus Laporan Shift (Khusus Admin)
     */
    public function destroy(Request $request, $id)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Hanya Admin yang boleh menghapus laporan shift.'], 403);
        }

        try {
            $shift = CashierShift::findOrFail($id);
            $shift->delete();

            return response()->json(['message' => 'Laporan shift berhasil dihapus.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal menghapus: ' . $e->getMessage()], 500);
        }
    }
}
