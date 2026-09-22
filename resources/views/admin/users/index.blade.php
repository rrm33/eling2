@extends('layouts.admin')

@section('title', 'Kelola Pengguna')
@section('page_title', 'Kelola Pengguna & Karyawan')

@section('content')
<div class="space-y-6" x-data="{ 
    userModalOpen: false,
    editUser: null,

    openAddUser() {
        this.editUser = null;
        this.userModalOpen = true;
    },

    openEditUser(u) {
        this.editUser = u;
        this.userModalOpen = true;
    }
}">

    <!-- Top Action Bar -->
    <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-200 flex items-center justify-between">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold">
                <i class="fa-solid fa-users-gear text-lg"></i>
            </div>
            <div>
                <h3 class="font-bold text-slate-800 text-base">Daftar Pengguna & Karyawan</h3>
                <p class="text-xs text-slate-400">Total {{ $users->count() }} pengguna terdaftar</p>
            </div>
        </div>

        <button @click="openAddUser()" class="px-5 py-2.5 bg-brand-500 hover:bg-brand-600 text-white font-bold rounded-xl text-xs shadow-lg shadow-brand-500/30 flex items-center space-x-2 transition">
            <i class="fa-solid fa-user-plus"></i>
            <span>Tambah User Baru</span>
        </button>
    </div>

    <!-- Users Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="text-xs font-bold uppercase bg-slate-50 text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3.5">Nama Karyawan</th>
                        <th class="px-4 py-3.5">Email / Username</th>
                        <th class="px-4 py-3.5 text-center">Role / Peran</th>
                        <th class="px-4 py-3.5">Cabang Penugasan</th>
                        <th class="px-4 py-3.5 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($users as $u)
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="px-4 py-3.5 font-bold text-slate-800 flex items-center space-x-3">
                                <div class="w-9 h-9 rounded-full bg-brand-50 text-brand-500 font-bold flex items-center justify-center text-xs">
                                    {{ strtoupper(substr($u->name, 0, 1)) }}
                                </div>
                                <span>{{ $u->name }}</span>
                            </td>
                            <td class="px-4 py-3.5 text-xs font-semibold text-slate-600">
                                {{ $u->email }}
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                @if($u->role === 'super_admin')
                                    <span class="px-2.5 py-1 bg-rose-100 text-rose-800 font-bold rounded-lg text-xs">Super Admin System</span>
                                @elseif($u->role === 'admin')
                                    <span class="px-2.5 py-1 bg-purple-50 text-purple-700 font-bold rounded-lg text-xs">Admin System</span>
                                @elseif($u->role === 'cashier')
                                    <span class="px-2.5 py-1 bg-emerald-50 text-emerald-700 font-bold rounded-lg text-xs">Kasir Cabang</span>
                                @elseif($u->role === 'courier')
                                    <span class="px-2.5 py-1 bg-amber-50 text-amber-700 font-bold rounded-lg text-xs">Kurir Stok</span>
                                @else
                                    <span class="px-2.5 py-1 bg-slate-100 text-slate-700 font-bold rounded-lg text-xs capitalize">{{ $u->role }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-xs font-semibold text-slate-700">
                                {{ $u->shop->name ?? 'Semua Cabang (Admin)' }}
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <div class="flex items-center justify-center space-x-1.5">
                                    <button @click="openEditUser({{ json_encode($u) }})"
                                            class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-100 flex items-center justify-center transition" title="Edit User">
                                        <i class="fa-solid fa-pen-to-square text-xs"></i>
                                    </button>
                                    @if($u->id !== Auth::id())
                                        <form method="POST" action="{{ route('admin.users.delete', $u->id) }}" onsubmit="return confirm('Yakin ingin menghapus user ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 flex items-center justify-center transition" title="Hapus User">
                                                <i class="fa-solid fa-trash text-xs"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-12 text-center text-slate-400 text-xs">
                                Belum ada user terdaftar
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- User Modal (Add/Edit) -->
    <div x-show="userModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" x-transition.opacity style="display: none;">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 space-y-4 shadow-2xl" @click.away="userModalOpen = false">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="font-bold text-slate-800 text-lg" x-text="editUser ? 'Edit Pengguna' : 'Tambah Pengguna Baru'"></h3>
                <button @click="userModalOpen = false" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>

            <form :action="editUser ? '/admin/users/' + editUser.id + '/update' : '{{ route('admin.users.store') }}'" method="POST" class="space-y-4">
                @csrf
                
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nama Karyawan / User</label>
                    <input type="text" name="name" :value="editUser ? editUser.name : ''" required
                           placeholder="Contoh: Budi Kasir"
                           class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold outline-none focus:ring-2 focus:ring-brand-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Email / Username Login</label>
                    <input type="email" name="email" :value="editUser ? editUser.email : ''" required
                           placeholder="kasir1@gmail.com"
                           class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold outline-none focus:ring-2 focus:ring-brand-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">
                        <span x-text="editUser ? 'Password Baru (Kosongkan jika tidak diubah)' : 'Password'"></span>
                    </label>
                    <input type="password" name="password" :required="!editUser"
                           placeholder="••••••••"
                           class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold outline-none focus:ring-2 focus:ring-brand-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Role / Peran</label>
                        <select name="role" required class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                            @if(Auth::user()->role === 'super_admin')
                                <option value="super_admin" :selected="editUser && editUser.role == 'super_admin'">Super Admin System</option>
                            @endif
                            <option value="admin" :selected="editUser && editUser.role == 'admin'">Admin System</option>
                            <option value="cashier" :selected="!editUser || editUser.role == 'cashier'">Kasir Cabang</option>
                            <option value="courier" :selected="editUser && editUser.role == 'courier'">Kurir Stok</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Cabang Penugasan</label>
                        <select name="shop_id" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold">
                            <option value="">Semua Cabang (Admin)</option>
                            @foreach($shops as $shop)
                                <option value="{{ $shop->id }}" :selected="editUser && editUser.shop_id == {{ $shop->id }}">{{ $shop->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end space-x-2">
                    <button type="button" @click="userModalOpen = false" class="px-4 py-2 bg-slate-100 text-slate-600 font-bold rounded-xl text-xs">Batal</button>
                    <button type="submit" class="px-5 py-2 bg-brand-500 hover:bg-brand-600 text-white font-bold rounded-xl text-xs shadow-md">Simpan User</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
