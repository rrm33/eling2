@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard Admin')

@section('content')
<div class="space-y-6">

    <!-- Filter Cabang Bar -->
    <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-4">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-xl bg-slate-100 text-brand-500 flex items-center justify-center font-bold">
                <i class="fa-solid fa-store text-lg"></i>
            </div>
            <div>
                <h3 class="font-bold text-slate-800 text-base">Filter Cabang / Toko</h3>
                <p class="text-xs text-slate-400">Tampilkan data omzet & penjualan per cabang</p>
            </div>
        </div>

        <form method="GET" action="{{ route('admin.dashboard') }}" class="w-full sm:w-auto">
            <select name="shop_id" onchange="this.form.submit()" 
                    class="w-full sm:w-64 px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-700 outline-none focus:ring-2 focus:ring-brand-500">
                <option value="">Semua Cabang Toko</option>
                @foreach($shops as $s)
                    <option value="{{ $s->id }}" {{ $shopId == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                @endforeach
            </select>
        </form>
    </div>

    <!-- Metric Cards Grid (Hari Ini) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        
        <!-- Total Omzet Hari Ini -->
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200 relative overflow-hidden">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Omzet Hari Ini</p>
                    <h3 class="text-2xl font-bold text-brand-500 mt-1">Rp {{ number_format($total_sales, 0, ',', '.') }}</h3>
                    <p class="text-xs text-slate-500 mt-1"><i class="fa-solid fa-receipt text-brand-500 mr-1"></i> {{ $total_transactions }} Transaksi hari ini</p>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-brand-50 text-brand-500 flex items-center justify-center text-xl shadow-inner">
                    <i class="fa-solid fa-money-bill-wave"></i>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 grid grid-cols-3 gap-1 text-center text-xs">
                <div class="bg-slate-50 p-1.5 rounded-lg">
                    <span class="text-slate-400 block text-[10px]">Tunai</span>
                    <span class="font-bold text-slate-700">Rp {{ number_format($total_sales_cash, 0, ',', '.') }}</span>
                </div>
                <div class="bg-slate-50 p-1.5 rounded-lg">
                    <span class="text-slate-400 block text-[10px]">QRIS</span>
                    <span class="font-bold text-slate-700">Rp {{ number_format($total_sales_qris, 0, ',', '.') }}</span>
                </div>
                <div class="bg-slate-50 p-1.5 rounded-lg">
                    <span class="text-slate-400 block text-[10px]">Lainnya</span>
                    <span class="font-bold text-slate-700">Rp {{ number_format($total_sales_lainnya, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        <!-- Dimsum Terjual (Butir) -->
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200 flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Dimsum Keluar</p>
                    <h3 class="text-2xl font-bold text-slate-800 mt-1">{{ number_format($total_grains) }} <span class="text-sm font-normal text-slate-500">butir</span></h3>
                    <p class="text-xs text-slate-500 mt-1">Total porsi × bundle</p>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-500 flex items-center justify-center text-xl">
                    <i class="fa-solid fa-cookie-bite"></i>
                </div>
            </div>
            <div class="mt-4 text-xs font-medium text-amber-600 bg-amber-50 px-3 py-1.5 rounded-xl text-center">
                <i class="fa-solid fa-fire mr-1"></i> Raw Material Usage Hari Ini
            </div>
        </div>

        <!-- Saus Bangkok Terjual -->
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200 flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Saus Bangkok</p>
                    <h3 class="text-2xl font-bold text-slate-800 mt-1">{{ number_format($total_saus) }} <span class="text-sm font-normal text-slate-500">porsi</span></h3>
                    <p class="text-xs text-slate-500 mt-1">Saus Bangkok Terjual</p>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-500 flex items-center justify-center text-xl">
                    <i class="fa-solid fa-bottle-droplet"></i>
                </div>
            </div>
            <div class="mt-4 text-xs font-medium text-rose-600 bg-rose-50 px-3 py-1.5 rounded-xl text-center">
                <i class="fa-solid fa-pepper-hot mr-1"></i> Penggunaan Saus
            </div>
        </div>

        <!-- Total Cabang Aktif -->
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200 flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Cabang</p>
                    <h3 class="text-2xl font-bold text-slate-800 mt-1">{{ $shops->count() }} <span class="text-sm font-normal text-slate-500">toko</span></h3>
                    <p class="text-xs text-slate-500 mt-1">Toko Aktif Berjalan</p>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-500 flex items-center justify-center text-xl">
                    <i class="fa-solid fa-shop"></i>
                </div>
            </div>
            <a href="{{ route('admin.shops') }}" class="mt-4 text-xs font-semibold text-indigo-600 bg-indigo-50 hover:bg-indigo-100 px-3 py-1.5 rounded-xl text-center transition">
                Kelola Cabang Toko <i class="fa-solid fa-arrow-right ml-1"></i>
            </a>
        </div>

    </div>

    <!-- Quick Shortcuts Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-3">
        <a href="{{ route('admin.transactions') }}" class="bg-white p-3.5 rounded-2xl border border-slate-200 text-center hover:border-brand-500 hover:shadow-md transition group">
            <div class="w-10 h-10 rounded-xl bg-rose-50 text-brand-500 flex items-center justify-center mx-auto mb-2 group-hover:scale-110 transition">
                <i class="fa-solid fa-receipt"></i>
            </div>
            <span class="text-xs font-bold text-slate-700 block">Riwayat Tx</span>
        </a>

        <a href="{{ route('admin.products') }}" class="bg-white p-3.5 rounded-2xl border border-slate-200 text-center hover:border-brand-500 hover:shadow-md transition group">
            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-500 flex items-center justify-center mx-auto mb-2 group-hover:scale-110 transition">
                <i class="fa-solid fa-box-open"></i>
            </div>
            <span class="text-xs font-bold text-slate-700 block">Produk/Stok</span>
        </a>

        <a href="{{ route('admin.shops') }}" class="bg-white p-3.5 rounded-2xl border border-slate-200 text-center hover:border-brand-500 hover:shadow-md transition group">
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-500 flex items-center justify-center mx-auto mb-2 group-hover:scale-110 transition">
                <i class="fa-solid fa-store"></i>
            </div>
            <span class="text-xs font-bold text-slate-700 block">Cabang</span>
        </a>

        <a href="{{ route('admin.users') }}" class="bg-white p-3.5 rounded-2xl border border-slate-200 text-center hover:border-brand-500 hover:shadow-md transition group">
            <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-500 flex items-center justify-center mx-auto mb-2 group-hover:scale-110 transition">
                <i class="fa-solid fa-users-gear"></i>
            </div>
            <span class="text-xs font-bold text-slate-700 block">Pengguna</span>
        </a>

        <a href="{{ route('admin.finance') }}" class="bg-white p-3.5 rounded-2xl border border-slate-200 text-center hover:border-brand-500 hover:shadow-md transition group">
            <div class="w-10 h-10 rounded-xl bg-cyan-50 text-cyan-500 flex items-center justify-center mx-auto mb-2 group-hover:scale-110 transition">
                <i class="fa-solid fa-wallet"></i>
            </div>
            <span class="text-xs font-bold text-slate-700 block">Keuangan</span>
        </a>

        <a href="{{ route('admin.shifts') }}" class="bg-white p-3.5 rounded-2xl border border-slate-200 text-center hover:border-brand-500 hover:shadow-md transition group">
            <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-500 flex items-center justify-center mx-auto mb-2 group-hover:scale-110 transition">
                <i class="fa-solid fa-clock-rotate-left"></i>
            </div>
            <span class="text-xs font-bold text-slate-700 block">Shift Kasir</span>
        </a>

        <a href="{{ route('admin.attendance') }}" class="bg-white p-3.5 rounded-2xl border border-slate-200 text-center hover:border-brand-500 hover:shadow-md transition group">
            <div class="w-10 h-10 rounded-xl bg-teal-50 text-teal-500 flex items-center justify-center mx-auto mb-2 group-hover:scale-110 transition">
                <i class="fa-solid fa-id-card-clip"></i>
            </div>
            <span class="text-xs font-bold text-slate-700 block">Absensi</span>
        </a>

        <a href="{{ route('admin.chat') }}" class="bg-white p-3.5 rounded-2xl border border-slate-200 text-center hover:border-brand-500 hover:shadow-md transition group">
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-500 flex items-center justify-center mx-auto mb-2 group-hover:scale-110 transition">
                <i class="fa-solid fa-comments"></i>
            </div>
            <span class="text-xs font-bold text-slate-700 block">Chat</span>
        </a>
    </div>

    <!-- Main Section: Top Products & Shop Performance Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Rincian Produk Terjual (Top Sales) -->
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="font-bold text-slate-800 text-base">Top Produk Terjual Hari Ini</h3>
                    <p class="text-xs text-slate-400">Peringkat porsi terjual & total omzet per produk</p>
                </div>
                <span class="text-xs font-semibold text-brand-500 bg-brand-50 px-2.5 py-1 rounded-lg">Hari Ini</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="text-xs font-bold uppercase bg-slate-50 text-slate-500 border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-3">Produk</th>
                            <th class="px-4 py-3 text-center">Jumlah Qty</th>
                            <th class="px-4 py-3 text-right">Total Omzet</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium">
                        @forelse($product_details as $p)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="px-4 py-3 flex items-center space-x-3">
                                    @if($p->image)
                                        @php
                                            $imgSrc = str_starts_with($p->image, 'http') ? $p->image : (str_starts_with($p->image, 'products/') ? asset('storage/' . $p->image) : asset('storage/products/' . basename($p->image)));
                                        @endphp
                                        <img src="{{ $imgSrc }}" class="w-9 h-9 rounded-xl object-cover border border-slate-200" onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode($p->name) }}&background=D9383A&color=ffffff'">
                                    @else
                                        <div class="w-9 h-9 rounded-xl bg-brand-50 text-brand-500 flex items-center justify-center font-bold text-xs">
                                            <i class="fa-solid fa-cookie"></i>
                                        </div>
                                    @endif
                                    <span class="font-semibold text-slate-800">{{ $p->name }}</span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="px-2.5 py-1 bg-amber-50 text-amber-700 font-bold rounded-lg text-xs">{{ $p->total_qty }} porsi</span>
                                </td>
                                <td class="px-4 py-3 text-right font-bold text-slate-800">
                                    Rp {{ number_format($p->total_omset, 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-8 text-center text-slate-400 text-xs">
                                    <i class="fa-solid fa-box-open text-2xl mb-1 block"></i>
                                    Belum ada produk terjual hari ini
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Omzet Per Cabang -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="font-bold text-slate-800 text-base">Omzet Per Cabang</h3>
                    <p class="text-xs text-slate-400">Total omzet toko hari ini</p>
                </div>
            </div>

            <div class="space-y-3">
                @forelse($shop_stats as $ss)
                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="w-9 h-9 rounded-xl bg-white text-indigo-600 flex items-center justify-center font-bold shadow-sm">
                                <i class="fa-solid fa-store text-sm"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-sm text-slate-800">{{ $ss->shop_name }}</h4>
                                <p class="text-xs text-slate-400">{{ $ss->total_tx }} transaksi</p>
                            </div>
                        </div>
                        <span class="font-bold text-sm text-brand-500">Rp {{ number_format($ss->total_sales, 0, ',', '.') }}</span>
                    </div>
                @empty
                    <div class="py-8 text-center text-slate-400 text-xs">
                        Belum ada penjualan per cabang hari ini
                    </div>
                @endforelse
            </div>
        </div>

    </div>

    <!-- Transaksi Terbaru Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="font-bold text-slate-800 text-base">Transaksi Terbaru</h3>
                <p class="text-xs text-slate-400">10 transaksi paling baru tersimpan di sistem</p>
            </div>
            <a href="{{ route('admin.transactions') }}" class="text-xs font-bold text-brand-500 hover:text-brand-600">
                Lihat Semua <i class="fa-solid fa-arrow-right ml-1"></i>
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="text-xs font-bold uppercase bg-slate-50 text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3">Invoice</th>
                        <th class="px-4 py-3">Waktu</th>
                        <th class="px-4 py-3">Cabang</th>
                        <th class="px-4 py-3">Metode</th>
                        <th class="px-4 py-3 text-right">Total</th>
                        <th class="px-4 py-3 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($latest_transactions as $t)
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="px-4 py-3 font-bold text-slate-800">
                                {{ $t->invoice_number }}
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-500">
                                {{ $t->created_at ? $t->created_at->format('d M Y, H:i') : '-' }}
                            </td>
                            <td class="px-4 py-3 text-xs font-semibold text-slate-700">
                                {{ $t->shop->name ?? '-' }}
                            </td>
                            <td class="px-4 py-3 text-xs capitalize">
                                <span class="px-2 py-0.5 rounded-md font-semibold bg-slate-100 text-slate-700">
                                    {{ $t->payment_method ?? 'Cash' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right font-bold text-brand-500">
                                Rp {{ number_format($t->total_price, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($t->status === 'void')
                                    <span class="px-2.5 py-1 bg-rose-50 text-rose-600 font-bold rounded-lg text-xs">Void</span>
                                @else
                                    <span class="px-2.5 py-1 bg-emerald-50 text-emerald-600 font-bold rounded-lg text-xs">Completed</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-slate-400 text-xs">
                                Belum ada transaksi tersimpan
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
