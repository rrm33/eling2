@extends('layouts.admin')

@section('title', 'Cabang / Toko')
@section('page_title', 'Kelola Cabang & Toko')

@section('content')
<div class="space-y-6" x-data="{ 
    shopModalOpen: false,
    editShop: null,

    openAddShop() {
        this.editShop = null;
        this.shopModalOpen = true;
    },

    openEditShop(s) {
        this.editShop = s;
        this.shopModalOpen = true;
    }
}">

    <!-- Top Action Bar -->
    <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-200 flex items-center justify-between">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold">
                <i class="fa-solid fa-store text-lg"></i>
            </div>
            <div>
                <h3 class="font-bold text-slate-800 text-base">Daftar Cabang Toko</h3>
                <p class="text-xs text-slate-400">Total {{ $shops->count() }} cabang terdaftar</p>
            </div>
        </div>

        <button @click="openAddShop()" class="px-5 py-2.5 bg-brand-500 hover:bg-brand-600 text-white font-bold rounded-xl text-xs shadow-lg shadow-brand-500/30 flex items-center space-x-2 transition">
            <i class="fa-solid fa-plus"></i>
            <span>Tambah Cabang Baru</span>
        </button>
    </div>

    <!-- Shops Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="text-xs font-bold uppercase bg-slate-50 text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3.5">Nama Cabang</th>
                        <th class="px-4 py-3.5">Alamat</th>
                        <th class="px-4 py-3.5">Telepon</th>
                        <th class="px-4 py-3.5 text-center">Stok Dimsum (Butir)</th>
                        <th class="px-4 py-3.5 text-center">Jumlah Kasir</th>
                        <th class="px-4 py-3.5 text-center">Status</th>
                        <th class="px-4 py-3.5 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($shops as $s)
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="px-4 py-3.5 font-bold text-slate-800">
                                {{ $s->name }}
                                @if($s->latitude && $s->longitude)
                                    <div class="text-[10px] text-indigo-600 font-medium mt-0.5">
                                        <i class="fa-solid fa-location-dot"></i> {{ $s->latitude }}, {{ $s->longitude }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-xs text-slate-600">
                                {{ $s->address }}
                            </td>
                            <td class="px-4 py-3.5 text-xs font-semibold text-slate-700">
                                {{ $s->phone ?? '-' }}
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <span class="px-3 py-1 bg-amber-50 text-amber-700 font-bold rounded-lg text-xs">
                                    {{ number_format($s->stock) }} butir
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <span class="px-2.5 py-1 bg-slate-100 text-slate-700 font-bold rounded-lg text-xs">
                                    {{ $s->users_count }} karyawan
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                @if($s->status === 'active')
                                    <span class="px-2.5 py-1 bg-emerald-50 text-emerald-600 font-bold rounded-lg text-xs">Aktif</span>
                                @else
                                    <span class="px-2.5 py-1 bg-slate-100 text-slate-500 font-bold rounded-lg text-xs">Nonaktif</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <div class="flex items-center justify-center space-x-1.5">
                                    <button @click="openEditShop({{ json_encode($s) }})"
                                            class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-100 flex items-center justify-center transition" title="Edit Cabang">
                                        <i class="fa-solid fa-pen-to-square text-xs"></i>
                                    </button>
                                    <form method="POST" action="{{ route('admin.shops.delete', $s->id) }}" onsubmit="return confirm('Yakin ingin menghapus cabang ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 flex items-center justify-center transition" title="Hapus Cabang">
                                            <i class="fa-solid fa-trash text-xs"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-slate-400 text-xs">
                                Belum ada cabang toko terdaftar
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Shop Modal (Add/Edit) -->
    <div x-show="shopModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" x-transition.opacity style="display: none;">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 space-y-4 shadow-2xl" @click.away="shopModalOpen = false">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="font-bold text-slate-800 text-lg" x-text="editShop ? 'Edit Cabang Toko' : 'Tambah Cabang Baru'"></h3>
                <button @click="shopModalOpen = false" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>

            <form :action="editShop ? '/admin/shops/' + editShop.id + '/update' : '{{ route('admin.shops.store') }}'" method="POST" class="space-y-4">
                @csrf
                
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nama Cabang / Toko</label>
                    <input type="text" name="name" :value="editShop ? editShop.name : ''" required
                           placeholder="Contoh: Dimsum Cabang Kampus"
                           class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold outline-none focus:ring-2 focus:ring-brand-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Alamat Lengkap</label>
                    <textarea name="address" :value="editShop ? editShop.address : ''" required rows="2"
                              placeholder="Jl. Kalimantan No. 12..."
                              class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold outline-none focus:ring-2 focus:ring-brand-500"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">No. Telepon / WA</label>
                        <input type="text" name="phone" :value="editShop ? editShop.phone : ''"
                               placeholder="081234567890"
                               class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Stok Dimsum (Butir)</label>
                        <input type="number" name="stock" :value="editShop ? editShop.stock : '0'"
                               placeholder="500"
                               class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-amber-600">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Latitude GPS</label>
                        <input type="text" name="latitude" :value="editShop ? editShop.latitude : ''"
                               placeholder="-8.1553"
                               class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Longitude GPS</label>
                        <input type="text" name="longitude" :value="editShop ? editShop.longitude : ''"
                               placeholder="113.435"
                               class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold">
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end space-x-2">
                    <button type="button" @click="shopModalOpen = false" class="px-4 py-2 bg-slate-100 text-slate-600 font-bold rounded-xl text-xs">Batal</button>
                    <button type="submit" class="px-5 py-2 bg-brand-500 hover:bg-brand-600 text-white font-bold rounded-xl text-xs shadow-md">Simpan Cabang</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
