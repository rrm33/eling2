@extends('layouts.admin')

@section('title', 'Tagihan & Versi Aplikasi')
@section('page_title', 'Tagihan & Pengaturan Versi Aplikasi')

@section('content')
<div class="space-y-6">

    <!-- Header Card -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 flex flex-col md:flex-row items-center justify-between gap-6">
        <div class="flex items-center space-x-4">
            <div class="w-14 h-14 rounded-2xl bg-brand-50 text-brand-500 flex items-center justify-center font-bold text-2xl shadow-inner">
                <i class="fa-solid fa-file-invoice-dollar"></i>
            </div>
            <div>
                <h3 class="font-bold text-slate-800 text-lg">Langganan Sistem DIMSUM & GOSAM POS</h3>
                <p class="text-xs text-slate-500 mt-0.5">Biaya pemeliharaan & lisensi sistem POS sebesar <strong class="text-slate-800">Rp 100.000 / bulan</strong> (jatuh tempo di akhir bulan).</p>
            </div>
        </div>

        <div class="flex items-center space-x-3 bg-slate-50 p-3 rounded-xl border border-slate-200">
            <div class="text-right">
                <span class="text-[10px] font-bold uppercase text-slate-400 block">Status Layanan</span>
                <span class="text-xs font-bold text-emerald-600 inline-flex items-center">
                    <i class="fa-solid fa-circle-check mr-1 text-[10px]"></i> Aktif Berjalan
                </span>
            </div>
        </div>
    </div>

    <!-- SUPER ADMIN ONLY: Flutter App Version & Download URL Settings -->
    @if(Auth::user()->role === 'super_admin')
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
            <div class="flex items-center space-x-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center font-bold">
                    <i class="fa-solid fa-mobile-screen-button text-lg"></i>
                </div>
                <div>
                    <h3 class="font-bold text-slate-800 text-base">Pengaturan & Upload Versi APK Aplikasi Flutter (Super Admin)</h3>
                    <p class="text-xs text-slate-400">Unggah file .apk baru secara langsung atau perbarui URL unduhan aplikasi tanpa perlu edit file di server</p>
                </div>
            </div>

            <form action="{{ route('admin.settings.app-version') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-start">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Versi Acuan Aplikasi (Version)</label>
                        <input type="text" name="flutter_app_version" value="{{ $appVersion }}" required placeholder="1.0.2"
                               class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold outline-none focus:ring-2 focus:ring-purple-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Upload File APK Baru (.apk)</label>
                        <input type="file" name="apk_file" accept=".apk"
                               class="w-full text-xs text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100 cursor-pointer">
                        <span class="text-[10px] text-slate-400 block mt-1">*Pilih file .apk dari komputer Anda untuk langsung dipublikasikan.</span>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Atau Link URL Unduhan Manual</label>
                        <input type="text" name="flutter_apk_url" value="{{ $apkUrl }}" placeholder="https://..."
                               class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold outline-none focus:ring-2 focus:ring-purple-500">
                    </div>
                </div>

                <div class="pt-2 flex items-center justify-between">
                    <div class="text-xs text-slate-500">
                        <span>URL Unduhan Aktif: <strong class="text-purple-700 font-mono select-all">{{ $apkUrl }}</strong></span>
                    </div>
                    
                    <div class="flex items-center space-x-2">
                        <a href="{{ $apkUrl }}" target="_blank" download class="px-4 py-2.5 bg-purple-50 hover:bg-purple-100 text-purple-700 font-bold rounded-xl text-xs flex items-center space-x-1.5 transition">
                            <i class="fa-solid fa-cloud-arrow-down"></i>
                            <span>Tes Unduh APK</span>
                        </a>

                        <button type="submit" class="px-5 py-2.5 bg-purple-600 hover:bg-purple-700 text-white font-bold rounded-xl text-xs shadow-md transition flex items-center space-x-2">
                            <i class="fa-solid fa-upload"></i>
                            <span>Unggah & Simpan Versi APK</span>
                        </button>
                    </div>
                </div>

            </form>
        </div>
    @endif

    <!-- Monthly Invoices List Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-5 border-b border-slate-200 flex items-center justify-between">
            <div>
                <h3 class="font-bold text-slate-800 text-base">Daftar Tagihan Bulanan</h3>
                <p class="text-xs text-slate-400">Tagihan otomatis diterbitkan setiap akhir bulan</p>
            </div>
            <span class="text-xs font-semibold text-slate-500 bg-slate-100 px-3 py-1.5 rounded-xl">
                Rp 100.000 / Bulan
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="text-xs font-bold uppercase bg-slate-50 text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3.5">Periode Bulan</th>
                        <th class="px-4 py-3.5">No. Invoice</th>
                        <th class="px-4 py-3.5">Tanggal Tagihan</th>
                        <th class="px-4 py-3.5 text-right">Nominal (Rp)</th>
                        <th class="px-4 py-3.5 text-center">Status</th>
                        <th class="px-4 py-3.5 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($invoices as $inv)
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="px-4 py-3.5 font-bold text-slate-800 flex items-center space-x-3">
                                <div class="w-9 h-9 rounded-xl bg-brand-50 text-brand-500 font-bold flex items-center justify-center text-xs">
                                    <i class="fa-solid fa-calendar-check"></i>
                                </div>
                                <span class="capitalize">{{ $inv['month_name'] }}</span>
                            </td>
                            <td class="px-4 py-3.5 text-xs font-semibold text-slate-600">
                                {{ $inv['invoice_number'] }}
                            </td>
                            <td class="px-4 py-3.5 text-xs text-slate-600">
                                {{ $inv['invoice_date'] }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-bold text-slate-800">
                                Rp {{ number_format($inv['amount'], 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                @if($inv['status'] === 'Lunas')
                                    <span class="px-2.5 py-1 bg-emerald-50 text-emerald-600 font-bold rounded-lg text-xs">
                                        <i class="fa-solid fa-circle-check mr-1"></i> Lunas
                                    </span>
                                @elseif($inv['status'] === 'Belum Jatuh Tempo')
                                    <span class="px-2.5 py-1 bg-blue-50 text-blue-600 font-bold rounded-lg text-xs">
                                        <i class="fa-solid fa-clock mr-1"></i> Periode Berjalan (Belum Jatuh Tempo)
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 bg-rose-50 text-rose-600 font-bold rounded-lg text-xs">
                                        <i class="fa-solid fa-triangle-exclamation mr-1"></i> {{ $inv['status'] }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <div class="flex items-center justify-center space-x-2">
                                    
                                    <a href="{{ route('admin.billing.invoice', ['year' => $inv['year'], 'month' => $inv['month']]) }}" target="_blank"
                                       class="px-3.5 py-1.5 bg-slate-100 hover:bg-brand-500 hover:text-white text-slate-700 font-bold rounded-xl text-xs inline-flex items-center space-x-1.5 transition">
                                        <i class="fa-solid fa-file-invoice"></i>
                                        <span>Lihat Invoice</span>
                                    </a>

                                    <!-- SUPER ADMIN: Form Ubah Status Tagihan -->
                                    @if(Auth::user()->role === 'super_admin')
                                        <form method="POST" action="{{ route('admin.billing.status') }}" class="inline-flex items-center space-x-1">
                                            @csrf
                                            <input type="hidden" name="year" value="{{ $inv['year'] }}">
                                            <input type="hidden" name="month" value="{{ $inv['month'] }}">
                                            <select name="status" onchange="this.form.submit()"
                                                    class="px-2 py-1 bg-purple-50 text-purple-700 border border-purple-200 rounded-lg text-xs font-bold outline-none cursor-pointer">
                                                <option value="" disabled selected>Ubah Status</option>
                                                <option value="Lunas">Set LUNAS</option>
                                                <option value="Belum Dibayar">Set Belum Dibayar</option>
                                                <option value="Belum Jatuh Tempo">Set Belum Jatuh Tempo</option>
                                                <option value="Jatuh Tempo">Set Jatuh Tempo</option>
                                            </select>
                                        </form>
                                    @endif

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-slate-400 text-xs">
                                Belum ada tagihan terbit
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
