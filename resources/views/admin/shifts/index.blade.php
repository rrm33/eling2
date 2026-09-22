@extends('layouts.admin')

@section('title', 'Shift Kasir')
@section('page_title', 'Riwayat Shift Kasir')

@section('content')
<div class="space-y-6">

    <!-- Header Bar -->
    <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-4">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center font-bold">
                <i class="fa-solid fa-clock-rotate-left text-lg"></i>
            </div>
            <div>
                <h3 class="font-bold text-slate-800 text-base">Riwayat Shift Kasir</h3>
                <p class="text-xs text-slate-400">Total {{ $shifts->total() }} shift tersimpan</p>
            </div>
        </div>

        <form method="GET" action="{{ route('admin.shifts') }}" class="w-full sm:w-auto">
            <select name="shop_id" onchange="this.form.submit()"
                    class="w-full sm:w-64 px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 outline-none">
                <option value="">Semua Cabang</option>
                @foreach($shops as $s)
                    <option value="{{ $s->id }}" {{ request('shop_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                @endforeach
            </select>
        </form>
    </div>

    <!-- Shifts Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="text-xs font-bold uppercase bg-slate-50 text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3.5">ID Shift</th>
                        <th class="px-4 py-3.5">Kasir</th>
                        <th class="px-4 py-3.5">Cabang</th>
                        <th class="px-4 py-3.5 text-center">Waktu Buka (Start)</th>
                        <th class="px-4 py-3.5 text-center">Waktu Tutup (End)</th>
                        <th class="px-4 py-3.5 text-right">Modal Awal</th>
                        <th class="px-4 py-3.5 text-right">Total Penjualan Shift</th>
                        <th class="px-4 py-3.5 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($shifts as $s)
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="px-4 py-3.5 text-xs font-bold text-slate-400">#{{ $s->id }}</td>
                            <td class="px-4 py-3.5 font-bold text-slate-800">
                                {{ $s->user->name ?? '-' }}
                            </td>
                            <td class="px-4 py-3.5 text-xs font-semibold text-slate-700">
                                {{ $s->shop->name ?? '-' }}
                            </td>
                            <td class="px-4 py-3.5 text-xs text-center font-semibold text-slate-600">
                                {{ $s->start_time ? \Carbon\Carbon::parse($s->start_time)->format('d MMM Y, H:i') : '-' }}
                            </td>
                            <td class="px-4 py-3.5 text-xs text-center font-semibold text-slate-600">
                                {{ $s->end_time ? \Carbon\Carbon::parse($s->end_time)->format('d MMM Y, H:i') : '-' }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-bold text-slate-800">
                                Rp {{ number_format($s->starting_cash, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-bold text-brand-500">
                                Rp {{ number_format($s->total_sales ?? 0, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                @if($s->status === 'open')
                                    <span class="px-2.5 py-1 bg-emerald-50 text-emerald-600 font-bold rounded-lg text-xs">Aktif / Open</span>
                                @else
                                    <span class="px-2.5 py-1 bg-slate-100 text-slate-500 font-bold rounded-lg text-xs">Selesai / Closed</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center text-slate-400 text-xs">
                                Belum ada data shift kasir
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-200">
            {{ $shifts->links() }}
        </div>
    </div>

</div>
@endsection
