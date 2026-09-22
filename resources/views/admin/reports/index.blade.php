@extends('layouts.admin')

@section('title', 'Laporan Penjualan')
@section('page_title', 'Laporan Penjualan & Performa Toko')

@section('content')
<div class="space-y-6">

    <!-- Filter Bar Card -->
    <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
        <form method="GET" action="{{ route('admin.reports') }}" class="space-y-4">
            
            <!-- Top Controls Row -->
            <div class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-4">
                
                <!-- Filter Period Pills (Default is Bulan Ini) -->
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('admin.reports', array_merge(request()->query(), ['date_filter' => 'month', 'start_date' => null, 'end_date' => null])) }}"
                       class="px-4 py-2 rounded-xl text-xs font-bold transition {{ $dateFilter === 'month' ? 'bg-brand-500 text-white shadow-md shadow-brand-500/20' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                        Bulan Ini
                    </a>
                    <a href="{{ route('admin.reports', array_merge(request()->query(), ['date_filter' => 'today', 'start_date' => null, 'end_date' => null])) }}"
                       class="px-4 py-2 rounded-xl text-xs font-bold transition {{ $dateFilter === 'today' && !request('start_date') ? 'bg-brand-500 text-white shadow-md shadow-brand-500/20' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                        Hari Ini
                    </a>
                    <a href="{{ route('admin.reports', array_merge(request()->query(), ['date_filter' => '7days', 'start_date' => null, 'end_date' => null])) }}"
                       class="px-4 py-2 rounded-xl text-xs font-bold transition {{ $dateFilter === '7days' ? 'bg-brand-500 text-white shadow-md shadow-brand-500/20' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                        7 Hari Terakhir
                    </a>
                    <a href="{{ route('admin.reports', array_merge(request()->query(), ['date_filter' => 'year', 'start_date' => null, 'end_date' => null])) }}"
                       class="px-4 py-2 rounded-xl text-xs font-bold transition {{ $dateFilter === 'year' ? 'bg-brand-500 text-white shadow-md shadow-brand-500/20' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                        Per Tahun
                    </a>
                    <a href="{{ route('admin.reports', array_merge(request()->query(), ['date_filter' => 'all', 'start_date' => null, 'end_date' => null])) }}"
                       class="px-4 py-2 rounded-xl text-xs font-bold transition {{ $dateFilter === 'all' ? 'bg-brand-500 text-white shadow-md shadow-brand-500/20' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                        Semua Waktu
                    </a>
                </div>

                <!-- Cabang Filter Dropdown -->
                <div class="w-full lg:w-64">
                    <select name="shop_id" onchange="this.form.submit()" class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 outline-none focus:ring-2 focus:ring-brand-500">
                        <option value="">Semua Cabang Toko</option>
                        @foreach($shops as $s)
                            <option value="{{ $s->id }}" {{ $shopId == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Conditional Filters (Month/Year Picker or Custom Date Range) -->
            @if($dateFilter === 'month')
                <div class="pt-3 border-t border-slate-100 flex items-center space-x-3">
                    <input type="hidden" name="date_filter" value="month">
                    <select name="month" class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700">
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ $selectedMonth == $m ? 'selected' : '' }}>
                                {{ Carbon\Carbon::createFromDate(2026, $m, 1)->locale('id')->isoFormat('MMMM') }}
                            </option>
                        @endfor
                    </select>
                    <select name="year" class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700">
                        @for($y = Carbon\Carbon::now()->year; $y >= 2024; $y--)
                            <option value="{{ $y }}" {{ $selectedYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                    <button type="submit" class="px-4 py-2 bg-brand-500 text-white rounded-xl text-xs font-bold shadow-sm">Terapkan Bulan</button>
                </div>
            @elseif($dateFilter === 'year')
                <div class="pt-3 border-t border-slate-100 flex items-center space-x-3">
                    <input type="hidden" name="date_filter" value="year">
                    <select name="year" class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700">
                        @for($y = Carbon\Carbon::now()->year; $y >= 2024; $y--)
                            <option value="{{ $y }}" {{ $selectedYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                    <button type="submit" class="px-4 py-2 bg-brand-500 text-white rounded-xl text-xs font-bold shadow-sm">Terapkan Tahun</button>
                </div>
            @endif

            <!-- Custom Date Range Picker -->
            <div class="pt-3 border-t border-slate-100 flex flex-wrap items-center gap-3">
                <span class="text-xs font-bold text-slate-500">Custom Tanggal:</span>
                <input type="date" name="start_date" value="{{ $startDate }}" class="px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700">
                <span class="text-xs text-slate-400">s/d</span>
                <input type="date" name="end_date" value="{{ $endDate }}" class="px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700">
                <button type="submit" class="px-4 py-1.5 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-bold transition shadow-sm">Filter Tanggal</button>
            </div>

        </form>
    </div>

    <!-- MAIN OMZET SUMMARY BANNER CARD -->
    <div class="bg-gradient-to-br from-brand-600 to-brand-500 rounded-3xl p-6 sm:p-8 text-white shadow-xl shadow-brand-500/20 relative overflow-hidden">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-bold uppercase tracking-wider text-white/80">Total Omzet Penjualan ({{ $filterLabel }})</span>
            <span class="px-3 py-1 bg-white/20 backdrop-blur-md rounded-xl text-[11px] font-bold">
                <i class="fa-solid fa-clock-rotate-left mr-1"></i> Real-time Sync
            </span>
        </div>
        <h2 class="text-3xl sm:text-4xl font-black tracking-tight mb-4">
            Rp {{ number_format($total_sales, 0, ',', '.') }}
        </h2>

        <!-- Payment Badges Row -->
        <div class="flex flex-wrap items-center gap-3 mb-6">
            <div class="flex items-center space-x-2 bg-emerald-500/20 backdrop-blur-md border border-emerald-400/30 px-3 py-1.5 rounded-xl text-xs font-bold">
                <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                <span>Tunai: Rp {{ number_format($total_sales_cash, 0, ',', '.') }}</span>
            </div>
            <div class="flex items-center space-x-2 bg-purple-500/20 backdrop-blur-md border border-purple-400/30 px-3 py-1.5 rounded-xl text-xs font-bold">
                <span class="w-2 h-2 rounded-full bg-purple-400"></span>
                <span>QRIS: Rp {{ number_format($total_sales_qris, 0, ',', '.') }}</span>
            </div>
            @if($total_sales_lainnya > 0)
                <div class="flex items-center space-x-2 bg-amber-500/20 backdrop-blur-md border border-amber-400/30 px-3 py-1.5 rounded-xl text-xs font-bold">
                    <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                    <span>Lainnya: Rp {{ number_format($total_sales_lainnya, 0, ',', '.') }}</span>
                </div>
            @endif
        </div>

        <div class="border-t border-white/20 pt-4 grid grid-cols-3 gap-4 text-center sm:text-left">
            <div>
                <span class="text-[11px] text-white/70 block">Jml Transaksi</span>
                <strong class="text-base sm:text-lg font-bold">{{ number_format($total_transactions) }} Tx</strong>
            </div>
            <div>
                <span class="text-[11px] text-white/70 block">Dimsum Terjual</span>
                <strong class="text-base sm:text-lg font-bold">{{ number_format($total_grains) }} Butir</strong>
            </div>
            <div>
                <span class="text-[11px] text-white/70 block">Saus Terjual</span>
                <strong class="text-base sm:text-lg font-bold">{{ number_format($total_saus) }} Pcs</strong>
            </div>
        </div>
    </div>

    <!-- GRID ROW: BAHAN BAKU & ARUS KAS -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        
        <!-- Pemakaian Bahan Baku Card -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 space-y-4">
            <div class="flex items-center space-x-3 border-b border-slate-100 pb-3">
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
                    <i class="fa-solid fa-boxes-packing text-lg"></i>
                </div>
                <div>
                    <h3 class="font-bold text-slate-800 text-base">Pemakaian Bahan Baku</h3>
                    <p class="text-xs text-slate-400">Akumulasi butiran dimsum & saus terjual</p>
                </div>
            </div>

            <div class="space-y-3">
                <div class="flex items-center justify-between p-3.5 bg-slate-50 rounded-xl border border-slate-100">
                    <div>
                        <span class="text-xs font-bold text-slate-700 block">Total Dimsum Terjual</span>
                        <span class="text-[10px] text-slate-400">Dari {{ number_format($total_transactions) }} Transaksi</span>
                    </div>
                    <span class="text-lg font-black text-blue-600">{{ number_format($total_grains) }} Butir</span>
                </div>

                <div class="flex items-center justify-between p-3.5 bg-slate-50 rounded-xl border border-slate-100">
                    <div>
                        <span class="text-xs font-bold text-slate-700 block">Total Saus Bangkok</span>
                        <span class="text-[10px] text-slate-400">Porsi saus terjual</span>
                    </div>
                    <span class="text-lg font-black text-rose-600">{{ number_format($total_saus) }} pcs</span>
                </div>
            </div>
        </div>

        <!-- Arus Kas & Laba Bersih Card -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 space-y-4">
            <div class="flex items-center space-x-3 border-b border-slate-100 pb-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
                    <i class="fa-solid fa-wallet text-lg"></i>
                </div>
                <div>
                    <h3 class="font-bold text-slate-800 text-base">Arus Kas & Estimasi Laba</h3>
                    <p class="text-xs text-slate-400">Omzet + Pemasukan - Pengeluaran</p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="p-3.5 bg-emerald-50/50 border border-emerald-100 rounded-xl">
                    <span class="text-[10px] font-bold uppercase text-emerald-700 block">Pemasukan Manual</span>
                    <strong class="text-sm font-bold text-emerald-800">Rp {{ number_format($total_income, 0, ',', '.') }}</strong>
                </div>

                <div class="p-3.5 bg-rose-50/50 border border-rose-100 rounded-xl">
                    <span class="text-[10px] font-bold uppercase text-rose-700 block">Pengeluaran Manual</span>
                    <strong class="text-sm font-bold text-rose-800">Rp {{ number_format($total_expense, 0, ',', '.') }}</strong>
                </div>
            </div>

            <div class="p-4 bg-slate-900 rounded-xl text-white flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-bold uppercase text-slate-400 block">Laba Bersih Estimasi</span>
                    <span class="text-xs text-slate-400">Hasil Penjualan Bersih</span>
                </div>
                <strong class="text-xl font-black text-emerald-400">Rp {{ number_format($net_profit, 0, ',', '.') }}</strong>
            </div>
        </div>

    </div>

    <!-- SALES CHART CARD (Default Current Month) -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center font-bold">
                    <i class="fa-solid fa-chart-line text-lg"></i>
                </div>
                <div>
                    <h3 class="font-bold text-slate-800 text-base">Grafik Tren Penjualan ({{ $filterLabel }})</h3>
                    <p class="text-xs text-slate-400">Grafik omzet harian berdasarkan periode aktif</p>
                </div>
            </div>
        </div>

        <div class="h-64 relative">
            <canvas id="salesTrendChart"></canvas>
        </div>
    </div>

    <!-- GRID ROW: TOP SHOPS & PRODUCT BREAKDOWN -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Peringkat Cabang Terlaris -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold">
                        <i class="fa-solid fa-trophy text-lg"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-800 text-base">Peringkat Cabang</h3>
                        <p class="text-xs text-slate-400">Urutan omzet terbanyak</p>
                    </div>
                </div>
            </div>

            <div class="space-y-3">
                @forelse($shop_stats as $idx => $shop)
                    <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200 flex items-center justify-between gap-2">
                        <div class="flex items-center space-x-3 overflow-hidden">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center font-black text-xs flex-shrink-0
                                {{ $idx === 0 ? 'bg-amber-100 text-amber-800 border border-amber-300' : ($idx === 1 ? 'bg-slate-200 text-slate-700' : ($idx === 2 ? 'bg-orange-100 text-orange-800' : 'bg-slate-100 text-slate-500')) }}">
                                #{{ $idx + 1 }}
                            </div>
                            <div class="truncate">
                                <h4 class="font-bold text-slate-800 text-xs truncate">{{ $shop->shop_name }}</h4>
                                <p class="text-[10px] text-slate-400 truncate">{{ number_format($shop->total_dimsum) }} dimsum • {{ number_format($shop->total_saus) }} saus</p>
                            </div>
                        </div>

                        <div class="flex items-center space-x-2 flex-shrink-0">
                            <span class="font-bold text-xs text-brand-600 block text-right">Rp {{ number_format($shop->total_sales, 0, ',', '.') }}</span>
                            <button onclick="openShopChartModal({{ $shop->shop_id }}, '{{ addslashes($shop->shop_name) }}')"
                                    class="w-7 h-7 rounded-lg bg-purple-50 hover:bg-purple-100 text-purple-700 font-bold flex items-center justify-center transition" title="Lihat Grafik Omzet Cabang">
                                <i class="fa-solid fa-chart-line text-xs"></i>
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-slate-400 text-xs py-8">
                        Belum ada data penjualan cabang
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Detail Produk Terjual Table -->
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex items-center space-x-3">
                <div class="w-10 h-10 rounded-xl bg-brand-50 text-brand-500 flex items-center justify-center font-bold">
                    <i class="fa-solid fa-list-check text-lg"></i>
                </div>
                <div>
                    <h3 class="font-bold text-slate-800 text-base">Detail Produk Terjual</h3>
                    <p class="text-xs text-slate-400">Rincian kuantitas & omzet per item produk</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="text-xs font-bold uppercase bg-slate-50 text-slate-500 border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-3.5">Produk</th>
                            <th class="px-4 py-3.5 text-center">Terjual (Qty)</th>
                            <th class="px-4 py-3.5 text-right">Total Omzet (Rp)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium">
                        @forelse($product_details as $p)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="px-4 py-3.5 font-bold text-slate-800 flex items-center space-x-3">
                                    <div class="w-9 h-9 rounded-xl bg-slate-100 flex items-center justify-center text-slate-500 font-bold overflow-hidden flex-shrink-0">
                                        @if($p->image)
                                            @php
                                                $imgUrl = str_starts_with($p->image, 'http') ? $p->image : (str_starts_with($p->image, 'products/') ? asset('storage/' . $p->image) : asset('storage/products/' . basename($p->image)));
                                            @endphp
                                            <img src="{{ $imgUrl }}" class="w-full h-full object-cover" onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode($p->name) }}&background=D9383A&color=ffffff'">
                                        @else
                                            <i class="fa-solid fa-box text-xs"></i>
                                        @endif
                                    </div>
                                    <span>{{ $p->name }}</span>
                                </td>
                                <td class="px-4 py-3.5 text-center font-bold text-slate-700">
                                    {{ number_format($p->total_qty) }} pcs
                                </td>
                                <td class="px-4 py-3.5 text-right font-black text-slate-900">
                                    Rp {{ number_format($p->total_omset, 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-12 text-center text-slate-400 text-xs">
                                    Belum ada produk terjual pada periode ini
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>

<!-- MODAL GRAFIK OMZET PER CABANG -->
<div id="shopChartModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs hidden">
    <div class="bg-white rounded-3xl max-w-2xl w-full p-6 space-y-4 shadow-2xl relative" onclick="event.stopPropagation()">
        <!-- Header -->
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center font-bold">
                    <i class="fa-solid fa-store text-lg"></i>
                </div>
                <div>
                    <h3 id="modalShopTitle" class="font-bold text-slate-800 text-lg">Grafik Penjualan Cabang</h3>
                    <p id="modalShopSubtitle" class="text-xs text-slate-400">Tren omzet harian per bulan</p>
                </div>
            </div>
            <button onclick="closeShopChartModal()" class="text-slate-400 hover:text-slate-600 p-2"><i class="fa-solid fa-xmark text-xl"></i></button>
        </div>

        <!-- Filter Row Inside Modal -->
        <div class="flex flex-wrap items-center justify-between gap-3 bg-slate-50 p-3.5 rounded-2xl border border-slate-200">
            <div class="flex items-center space-x-2">
                <span class="text-xs font-bold text-slate-600">Pilih Bulan:</span>
                <select id="modalMonthSelect" onchange="loadShopChartData()" class="px-3 py-1.5 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-700 outline-none cursor-pointer">
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ Carbon\Carbon::now()->month == $m ? 'selected' : '' }}>
                            {{ Carbon\Carbon::createFromDate(2026, $m, 1)->locale('id')->isoFormat('MMMM') }}
                        </option>
                    @endfor
                </select>
                <select id="modalYearSelect" onchange="loadShopChartData()" class="px-3 py-1.5 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-700 outline-none cursor-pointer">
                    @for($y = Carbon\Carbon::now()->year; $y >= 2024; $y--)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endfor
                </select>
            </div>

            <div class="text-right">
                <span class="text-[10px] font-bold text-slate-400 uppercase block">Total Omzet Bulan Ini</span>
                <strong id="modalShopTotalSales" class="text-base font-black text-purple-700">Rp 0</strong>
            </div>
        </div>

        <!-- Canvas Area -->
        <div class="h-64 relative pt-2">
            <canvas id="modalShopChartCanvas"></canvas>
        </div>
    </div>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Main Sales Chart Initialization
        const ctx = document.getElementById('salesTrendChart').getContext('2d');
        const labels = {!! json_encode($chartData->pluck('date_group')->map(fn($d) => Carbon\Carbon::parse($d)->format('d M'))) !!};
        const salesData = {!! json_encode($chartData->pluck('daily_sales')) !!};

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Omzet Penjualan (Rp)',
                    data: salesData,
                    borderColor: '#D9383A',
                    backgroundColor: 'rgba(217, 56, 58, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.3,
                    pointBackgroundColor: '#D9383A',
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) { return 'Rp ' + value.toLocaleString('id-ID'); }
                        }
                    }
                }
            }
        });
    });

    // Modal Shop Chart Functions
    let currentModalShopId = null;
    let modalShopChartInstance = null;

    function openShopChartModal(shopId, shopName) {
        currentModalShopId = shopId;
        document.getElementById('modalShopTitle').innerText = 'Grafik Penjualan ' + shopName;
        document.getElementById('shopChartModal').classList.remove('hidden');
        loadShopChartData();
    }

    function closeShopChartModal() {
        document.getElementById('shopChartModal').classList.add('hidden');
    }

    function loadShopChartData() {
        if (!currentModalShopId) return;

        const month = document.getElementById('modalMonthSelect').value;
        const year = document.getElementById('modalYearSelect').value;

        fetch(`/admin/reports/shop-chart?shop_id=${currentModalShopId}&year=${year}&month=${month}`)
            .then(res => res.json())
            .then(res => {
                if (!res.success) return;

                document.getElementById('modalShopSubtitle').innerText = 'Tren omzet harian - ' + res.month_label;
                document.getElementById('modalShopTotalSales').innerText = res.total_sales_formatted;

                const ctx = document.getElementById('modalShopChartCanvas').getContext('2d');
                if (modalShopChartInstance) {
                    modalShopChartInstance.destroy();
                }

                modalShopChartInstance = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: res.labels,
                        datasets: [{
                            label: 'Omzet Cabang (Rp)',
                            data: res.sales_data,
                            borderColor: '#9333EA',
                            backgroundColor: 'rgba(147, 51, 234, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.3,
                            pointBackgroundColor: '#9333EA',
                            pointRadius: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(val) { return 'Rp ' + val.toLocaleString('id-ID'); }
                                }
                            }
                        }
                    }
                });
            })
            .catch(err => console.error("Error loading shop chart data:", err));
    }
</script>
@endsection
