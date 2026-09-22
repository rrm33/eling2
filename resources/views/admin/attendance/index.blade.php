@extends('layouts.admin')

@section('title', 'Absensi Karyawan')
@section('page_title', 'Riwayat Absensi Karyawan')

@section('content')
<div class="space-y-6">

    <!-- Filter Bar -->
    <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-4">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center font-bold">
                <i class="fa-solid fa-id-card-clip text-lg"></i>
            </div>
            <div>
                <h3 class="font-bold text-slate-800 text-base">Riwayat Absensi Karyawan</h3>
                <p class="text-xs text-slate-400">Total {{ $attendances->total() }} log absensi tersimpan</p>
            </div>
        </div>

        <form method="GET" action="{{ route('admin.attendance') }}" class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
            <div>
                <input type="date" name="date" value="{{ request('date') }}" onchange="this.form.submit()"
                       class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 outline-none">
            </div>
            <div>
                <select name="shop_id" onchange="this.form.submit()"
                        class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 outline-none">
                    <option value="">Semua Cabang</option>
                    @foreach($shops as $s)
                        <option value="{{ $s->id }}" {{ request('shop_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>

    <!-- Attendance Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="text-xs font-bold uppercase bg-slate-50 text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3.5">Foto Absen</th>
                        <th class="px-4 py-3.5">Karyawan</th>
                        <th class="px-4 py-3.5">Cabang</th>
                        <th class="px-4 py-3.5">Tanggal</th>
                        <th class="px-4 py-3.5 text-center">Jam Masuk (In)</th>
                        <th class="px-4 py-3.5 text-center">Jam Pulang (Out)</th>
                        <th class="px-4 py-3.5">Lokasi GPS</th>
                        <th class="px-4 py-3.5">Catatan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($attendances as $a)
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="px-4 py-3.5">
                                @if($a->photo)
                                    <a href="{{ asset('storage/' . $a->photo) }}" target="_blank">
                                        <img src="{{ asset('storage/' . $a->photo) }}" class="w-12 h-12 rounded-xl object-cover border border-slate-200 hover:opacity-80 transition">
                                    </a>
                                @else
                                    <div class="w-12 h-12 rounded-xl bg-slate-100 text-slate-400 flex items-center justify-center font-bold text-xs">
                                        <i class="fa-solid fa-camera"></i>
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 font-bold text-slate-800">
                                {{ $a->user->name ?? '-' }}
                                <div class="text-[10px] text-slate-400 capitalize">{{ $a->user->role ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3.5 text-xs font-semibold text-slate-700">
                                {{ $a->shop->name ?? '-' }}
                            </td>
                            <td class="px-4 py-3.5 text-xs font-semibold text-slate-600">
                                {{ $a->created_at ? $a->created_at->format('d MMM Y') : '-' }}
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                @if($a->in_time)
                                    <span class="px-2.5 py-1 bg-emerald-50 text-emerald-700 font-bold rounded-lg text-xs">
                                        <i class="fa-solid fa-right-to-bracket mr-1"></i> {{ $a->in_time }}
                                    </span>
                                @else
                                    <span class="text-slate-400 text-xs">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                @if($a->out_time)
                                    <span class="px-2.5 py-1 bg-rose-50 text-rose-700 font-bold rounded-lg text-xs">
                                        <i class="fa-solid fa-right-from-bracket mr-1"></i> {{ $a->out_time }}
                                    </span>
                                @else
                                    <span class="text-slate-400 text-xs">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-xs">
                                @if($a->latitude && $a->longitude)
                                    <a href="https://www.google.com/maps?q={{ $a->latitude }},{{ $a->longitude }}" target="_blank" class="text-indigo-600 hover:text-indigo-800 font-bold inline-flex items-center space-x-1">
                                        <i class="fa-solid fa-location-dot"></i>
                                        <span>Maps Link</span>
                                    </a>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-xs text-slate-500">
                                {{ $a->note ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center text-slate-400 text-xs">
                                Belum ada data absensi
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-200">
            {{ $attendances->links() }}
        </div>
    </div>

</div>
@endsection
