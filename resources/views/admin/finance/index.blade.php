@extends('layouts.admin')

@section('title', 'Keuangan & Kas')
@section('page_title', 'Kelola Keuangan & Pengeluaran')

@section('content')
<div class="space-y-6" x-data="{ 
    financeModalOpen: false,

    openAddFinance() {
        this.financeModalOpen = true;
    }
}">

    <!-- Financial Metric Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        
        <!-- Total Omzet Hari Ini -->
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Omzet Kasir Hari Ini</p>
            <h3 class="text-xl font-bold text-brand-500 mt-1">Rp {{ number_format($totalSalesToday, 0, ',', '.') }}</h3>
            <p class="text-xs text-slate-400 mt-1">Hasil penjualan kasir</p>
        </div>

        <!-- Total Pemasukan -->
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Pemasukan Lainnya</p>
            <h3 class="text-xl font-bold text-emerald-600 mt-1">Rp {{ number_format($totalIncome, 0, ',', '.') }}</h3>
            <p class="text-xs text-slate-400 mt-1">Total kas masuk tambahan</p>
        </div>

        <!-- Total Pengeluaran -->
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Pengeluaran</p>
            <h3 class="text-xl font-bold text-rose-600 mt-1">Rp {{ number_format($totalExpense, 0, ',', '.') }}</h3>
            <p class="text-xs text-slate-400 mt-1">Belanja & operasional</p>
        </div>

        <!-- Saldo Bersih -->
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200 bg-gradient-to-br from-white to-slate-50">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Estimated Balance</p>
            <h3 class="text-2xl font-bold text-indigo-600 mt-1">Rp {{ number_format($balance, 0, ',', '.') }}</h3>
            <p class="text-xs text-slate-400 mt-1">(Omzet + Masuk) - Keluar</p>
        </div>

    </div>

    <!-- Filter & Add Button Bar -->
    <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-4">
        <form method="GET" action="{{ route('admin.finance') }}" class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
            <div>
                <select name="shop_id" onchange="this.form.submit()"
                        class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 outline-none">
                    <option value="">Semua Cabang</option>
                    @foreach($shops as $s)
                        <option value="{{ $s->id }}" {{ request('shop_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <select name="type" onchange="this.form.submit()"
                        class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 outline-none">
                    <option value="">Semua Jenis (Masuk / Keluar)</option>
                    <option value="income" {{ request('type') == 'income' ? 'selected' : '' }}>Pemasukan (Income)</option>
                    <option value="expense" {{ request('type') == 'expense' ? 'selected' : '' }}>Pengeluaran (Expense)</option>
                </select>
            </div>
        </form>

        <button @click="openAddFinance()" class="w-full sm:w-auto px-5 py-2.5 bg-brand-500 hover:bg-brand-600 text-white font-bold rounded-xl text-xs shadow-lg shadow-brand-500/30 flex items-center justify-center space-x-2 transition">
            <i class="fa-solid fa-plus"></i>
            <span>Catat Pemasukan / Pengeluaran</span>
        </button>
    </div>

    <!-- Finance Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="text-xs font-bold uppercase bg-slate-50 text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3.5">Tanggal</th>
                        <th class="px-4 py-3.5">Cabang</th>
                        <th class="px-4 py-3.5">Pencatat</th>
                        <th class="px-4 py-3.5">Kategori</th>
                        <th class="px-4 py-3.5 text-center">Jenis</th>
                        <th class="px-4 py-3.5 text-right">Nominal (Rp)</th>
                        <th class="px-4 py-3.5">Catatan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($finances as $f)
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="px-4 py-3.5 text-xs font-semibold text-slate-600">
                                {{ $f->date ? \Carbon\Carbon::parse($f->date)->format('d MMM Y') : ($f->created_at ? $f->created_at->format('d MMM Y') : '-') }}
                            </td>
                            <td class="px-4 py-3.5 text-xs font-semibold text-slate-700">
                                {{ $f->shop->name ?? '-' }}
                            </td>
                            <td class="px-4 py-3.5 text-xs text-slate-600">
                                {{ $f->user->name ?? '-' }}
                            </td>
                            <td class="px-4 py-3.5 text-xs font-bold text-slate-800">
                                {{ $f->category }}
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                @if($f->type === 'income')
                                    <span class="px-2.5 py-1 bg-emerald-50 text-emerald-600 font-bold rounded-lg text-xs">Pemasukan</span>
                                @else
                                    <span class="px-2.5 py-1 bg-rose-50 text-rose-600 font-bold rounded-lg text-xs">Pengeluaran</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-right font-bold {{ $f->type === 'income' ? 'text-emerald-600' : 'text-rose-600' }}">
                                {{ $f->type === 'income' ? '+' : '-' }} Rp {{ number_format($f->amount, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3.5 text-xs text-slate-500">
                                {{ $f->note }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-slate-400 text-xs">
                                Belum ada catatan keuangan
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-200">
            {{ $finances->links() }}
        </div>
    </div>

    <!-- Finance Modal -->
    <div x-show="financeModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" x-transition.opacity style="display: none;">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 space-y-4 shadow-2xl" @click.away="financeModalOpen = false">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="font-bold text-slate-800 text-lg">Catat Keuangan Baru</h3>
                <button @click="financeModalOpen = false" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>

            <form action="{{ route('admin.finance.store') }}" method="POST" class="space-y-4">
                @csrf
                
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Jenis Transaksi</label>
                        <select name="type" required class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                            <option value="expense">Pengeluaran (Expense)</option>
                            <option value="income">Pemasukan (Income)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nominal (Rp)</label>
                        <input type="number" name="amount" required placeholder="50000"
                               class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Kategori</label>
                        <input type="text" name="category" required placeholder="Belanja Dimsum, Gaji, Listrik, Kebersihan"
                               class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Tanggal</label>
                        <input type="date" name="date" value="{{ date('Y-m-d') }}" required
                               class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Cabang Toko</label>
                    <select name="shop_id" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold">
                        <option value="">Pilih Cabang (Opsional)</option>
                        @foreach($shops as $shop)
                            <option value="{{ $shop->id }}">{{ $shop->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Catatan Keterangan</label>
                    <textarea name="note" required rows="2" placeholder="Detail pengeluaran..."
                              class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold outline-none focus:ring-2 focus:ring-brand-500"></textarea>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end space-x-2">
                    <button type="button" @click="financeModalOpen = false" class="px-4 py-2 bg-slate-100 text-slate-600 font-bold rounded-xl text-xs">Batal</button>
                    <button type="submit" class="px-5 py-2 bg-brand-500 hover:bg-brand-600 text-white font-bold rounded-xl text-xs shadow-md">Simpan Catatan</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
