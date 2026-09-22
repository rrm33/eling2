<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Finance;
use App\Models\Transaction;
use Illuminate\Http\Request;

class FinanceController extends Controller
{
    /**
     * Ringkasan Keuangan untuk Dashboard
     */
    public function summary(Request $request)
    {
        $user = $request->user();
        $shopId = $user->role === 'admin' ? $request->shop_id : $user->shop_id;

        // 1. Ambil Modal Awal (Starting Cash) dari Shift Terakhir yang sedang buka
        $startingCash = 0;
        $startTime = null;
        if ($shopId) {
            $lastShift = \App\Models\CashierShift::where('shop_id', $shopId)
                ->where('status', 'open')
                ->latest()
                ->first();
            if ($lastShift) {
                $startingCash = $lastShift->starting_cash;
                $startTime = $lastShift->start_time ?? $lastShift->created_at;
            }
        }

        // 2. Hitung Total Penjualan
        $salesQuery = Transaction::where('transactions.status', '!=', 'void');
        if ($shopId) $salesQuery->where('transactions.shop_id', $shopId);
        
        if ($request->start_date) {
            $salesQuery->whereDate('transactions.created_at', '>=', $request->start_date);
        } elseif ($startTime) {
            $salesQuery->where('transactions.created_at', '>=', $startTime);
        }

        if ($request->end_date) {
            $salesQuery->whereDate('transactions.created_at', '<=', $request->end_date);
        }

        $totalSales = $salesQuery->sum('total_price');

        // 3. Hitung Pemasukan Lainnya
        $incomeQuery = Finance::where('type', 'income')->where('status', 'active');
        if ($shopId) $incomeQuery->where('shop_id', $shopId);
        
        if ($request->start_date) {
            $incomeQuery->whereDate('date', '>=', $request->start_date);
        } elseif ($startTime) {
            $incomeQuery->where('date', '>=', $startTime);
        }

        if ($request->end_date) {
            $incomeQuery->whereDate('date', '<=', $request->end_date);
        }

        $totalIncome = $incomeQuery->sum('amount');

        // 4. Hitung Pengeluaran
        $expenseQuery = Finance::where('type', 'expense')->where('status', 'active');
        if ($shopId) $expenseQuery->where('shop_id', $shopId);
        
        if ($request->start_date) {
            $expenseQuery->whereDate('date', '>=', $request->start_date);
        } elseif ($startTime) {
            $expenseQuery->where('date', '>=', $startTime);
        }

        if ($request->end_date) {
            $expenseQuery->whereDate('date', '<=', $request->end_date);
        }

        $totalExpense = $expenseQuery->sum('amount');

        $balance = ($startingCash + $totalSales + $totalIncome) - $totalExpense;

        return response()->json([
            'starting_cash' => $startingCash,
            'total_sales' => $totalSales,
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'balance' => $balance
        ]);
    }

    /**
     * List Pemasukan/Pengeluaran
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Finance::with(['user', 'shop'])->latest();

        if ($user->role !== 'admin') {
            $query->where('shop_id', $user->shop_id);
        }

        if ($request->type) {
            $query->where('type', $request->type);
        }

        return response()->json([
            'data' => $query->paginate(20)
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:income,expense',
            'amount' => 'required|numeric',
            'category' => 'required|string',
            'note' => 'required|string',
        ]);

        $finance = Finance::create([
            'shop_id' => $request->user()->shop_id,
            'user_id' => $request->user()->id,
            'type' => $request->type,
            'amount' => $request->amount,
            'category' => $request->category,
            'note' => $request->note,
            'date' => $request->date ?? now()->toDateString(),
            'status' => $request->status ?? 'active',
            'void_by' => $request->void_by,
        ]);

        return response()->json(['message' => 'Data keuangan berhasil disimpan.', 'data' => $finance], 201);
    }

    public function update(Request $request, $id)
    {
        $finance = Finance::findOrFail($id);
        
        $finance->update([
            'status' => $request->status ?? $finance->status,
            'void_by' => $request->void_by ?? $finance->void_by,
        ]);

        return response()->json(['message' => 'Data keuangan berhasil diperbarui.', 'data' => $finance]);
    }

    /**
     * Data Grafik Keuangan
     */
    public function chart(Request $request)
    {
        $user = $request->user();
        $shopId = $user->role === 'admin' ? $request->shop_id : $user->shop_id;

        // Validasi input tanggal
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        // Query transactions
        $query = Transaction::where('status', '!=', 'void')
            ->whereDate('created_at', '>=', $request->start_date)
            ->whereDate('created_at', '<=', $request->end_date);

        if ($shopId) {
            $query->where('shop_id', $shopId);
        }

        // Group by Date using SQL
        $dailySales = $query->select(
            \DB::raw('DATE(created_at) as date'),
            \DB::raw('SUM(total_price) as total_sales')
        )
        ->groupBy('date')
        ->orderBy('date', 'asc')
        ->get();

        // Siapkan array dengan semua tanggal di rentang waktu
        $start = \Carbon\Carbon::parse($request->start_date);
        $end = \Carbon\Carbon::parse($request->end_date);
        $labels = [];
        $data = [];
        
        $salesMap = $dailySales->pluck('total_sales', 'date')->toArray();

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $dateString = $date->format('Y-m-d');
            $labels[] = $date->format('d M'); // e.g., '01 Aug'
            $data[] = (double)($salesMap[$dateString] ?? 0);
        }

        return response()->json([
            'status' => 'success',
            'chart' => [
                'labels' => $labels,
                'data' => $data,
            ]
        ]);
    }
}

