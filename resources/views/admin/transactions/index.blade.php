@extends('layouts.admin')

@section('title', 'Riwayat Transaksi')
@section('page_title', 'Riwayat & Edit Transaksi')

@section('content')
<div class="space-y-6" x-data="{ 
    editModalOpen: false, 
    editTx: null,
    editItems: [],
    allProducts: {{ json_encode($products) }},
    
    openEditModal(tx) {
        this.editTx = JSON.parse(JSON.stringify(tx));
        this.editItems = (tx.items || []).map(i => ({
            product_id: i.product_id,
            product_name: i.product ? i.product.name : (i.name || '-'),
            price: parseFloat(i.price || 0),
            quantity: parseInt(i.qty || i.quantity || 1)
        }));
        this.editModalOpen = true;
    },

    addItem(prod) {
        let existing = this.editItems.find(i => i.product_id == prod.id);
        if (existing) {
            existing.quantity++;
        } else {
            this.editItems.push({
                product_id: prod.id,
                product_name: prod.name,
                price: parseFloat(prod.price),
                quantity: 1
            });
        }
    },

    removeItem(index) {
        this.editItems.splice(index, 1);
    },

    get totalPrice() {
        return this.editItems.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    }
}">

    <!-- Filter & Header Bar -->
    <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200 space-y-4">
        <form method="GET" action="{{ route('admin.transactions') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            
            <!-- Filter Tanggal Shortcut -->
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Filter Tanggal</label>
                <select name="date_filter" onchange="this.form.submit()" 
                        class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 outline-none focus:ring-2 focus:ring-brand-500">
                    <option value="today" {{ $dateFilter == 'today' ? 'selected' : '' }}>Hari Ini</option>
                    <option value="7days" {{ $dateFilter == '7days' ? 'selected' : '' }}>7 Hari Terakhir</option>
                    <option value="month" {{ $dateFilter == 'month' ? 'selected' : '' }}>Bulan Ini</option>
                    <option value="custom" {{ $dateFilter == 'custom' || $startDate ? 'selected' : '' }}>Custom Tanggal</option>
                </select>
            </div>

            <!-- Start Date -->
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Dari Tanggal</label>
                <input type="date" name="start_date" value="{{ $startDate }}" onchange="this.form.submit()"
                       class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 outline-none focus:ring-2 focus:ring-brand-500">
            </div>

            <!-- End Date -->
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Sampai Tanggal</label>
                <input type="date" name="end_date" value="{{ $endDate }}" onchange="this.form.submit()"
                       class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 outline-none focus:ring-2 focus:ring-brand-500">
            </div>

            <!-- Shop Filter -->
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Cabang / Toko</label>
                <select name="shop_id" onchange="this.form.submit()"
                        class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 outline-none focus:ring-2 focus:ring-brand-500">
                    <option value="">Semua Cabang</option>
                    @foreach($shops as $s)
                        <option value="{{ $s->id }}" {{ request('shop_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Status Filter -->
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Status</label>
                <select name="status" onchange="this.form.submit()"
                        class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 outline-none focus:ring-2 focus:ring-brand-500">
                    <option value="">Semua Status</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="void" {{ request('status') == 'void' ? 'selected' : '' }}>Void</option>
                </select>
            </div>

        </form>

        <!-- Summary Bar -->
        <div class="pt-3 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs font-semibold">
            <div class="flex items-center space-x-4">
                <span class="text-slate-500">Total Transaksi: <strong class="text-slate-800 font-bold">{{ number_format($total_count) }}</strong></span>
                <span class="text-slate-500">Total Omzet: <strong class="text-brand-500 font-bold text-sm">Rp {{ number_format($total_sales, 0, ',', '.') }}</strong></span>
            </div>
            <a href="{{ route('admin.transactions') }}" class="text-slate-400 hover:text-slate-600 flex items-center space-x-1">
                <i class="fa-solid fa-rotate-left"></i>
                <span>Reset Filter</span>
            </a>
        </div>
    </div>

    <!-- Transactions Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="text-xs font-bold uppercase bg-slate-50 text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3.5">Invoice</th>
                        <th class="px-4 py-3.5">Tanggal / Waktu</th>
                        <th class="px-4 py-3.5">Cabang</th>
                        <th class="px-4 py-3.5">Item Produk</th>
                        <th class="px-4 py-3.5">Metode</th>
                        <th class="px-4 py-3.5 text-right">Total Nominal</th>
                        <th class="px-4 py-3.5 text-center">Status</th>
                        <th class="px-4 py-3.5 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($transactions as $t)
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="px-4 py-3.5 font-bold text-slate-800">
                                {{ $t->invoice_number }}
                                <div class="text-[10px] text-slate-400 font-normal">Kasir: {{ $t->user->name ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3.5 text-xs text-slate-600 font-semibold">
                                {{ $t->created_at ? $t->created_at->format('d MMM Y, H:i') : '-' }}
                            </td>
                            <td class="px-4 py-3.5 text-xs font-semibold text-slate-700">
                                {{ $t->shop->name ?? '-' }}
                            </td>
                            <td class="px-4 py-3.5 text-xs text-slate-600">
                                <ul class="space-y-0.5">
                                    @foreach($t->items as $item)
                                        <li>• {{ $item->product->name ?? '-' }} <span class="font-bold text-slate-800">x{{ $item->qty }}</span></li>
                                    @endforeach
                                </ul>
                            </td>
                            <td class="px-4 py-3.5 text-xs capitalize">
                                <span class="px-2.5 py-1 rounded-lg font-bold bg-slate-100 text-slate-700">
                                    {{ $t->payment_method ?? 'Cash' }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-right font-bold text-brand-500">
                                Rp {{ number_format($t->total_price, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                @if($t->status === 'void')
                                    <span class="px-2.5 py-1 bg-rose-50 text-rose-600 font-bold rounded-lg text-xs">Void</span>
                                @else
                                    <span class="px-2.5 py-1 bg-emerald-50 text-emerald-600 font-bold rounded-lg text-xs">Completed</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <div class="flex items-center justify-center space-x-1.5">
                                    
                                    <!-- Edit Button -->
                                    <button @click="openEditModal({{ json_encode($t) }})"
                                            class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-100 flex items-center justify-center transition"
                                            title="Edit Transaksi / Tanggal">
                                        <i class="fa-solid fa-pen-to-square text-xs"></i>
                                    </button>

                                    <!-- Void Button -->
                                    @if($t->status !== 'void')
                                        <form method="POST" action="{{ route('admin.transactions.void', $t->id) }}" onsubmit="return confirm('Yakin ingin membatalkan (void) transaksi ini?')">
                                            @csrf
                                            <button type="submit" class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 flex items-center justify-center transition" title="Void Transaksi">
                                                <i class="fa-solid fa-ban text-xs"></i>
                                            </button>
                                        </form>
                                    @endif

                                    <!-- Delete Button -->
                                    <form method="POST" action="{{ route('admin.transactions.delete', $t->id) }}" onsubmit="return confirm('Yakin ingin MENGHAPUS transaksi ini secara permanen?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="w-8 h-8 rounded-lg bg-slate-100 text-slate-500 hover:bg-rose-500 hover:text-white flex items-center justify-center transition" title="Hapus Permanen">
                                            <i class="fa-solid fa-trash text-xs"></i>
                                        </button>
                                    </form>

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center text-slate-400 text-xs">
                                <i class="fa-solid fa-receipt text-3xl mb-2 block text-slate-300"></i>
                                Tidak ada transaksi ditemukan
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="p-4 border-t border-slate-200">
            {{ $transactions->links() }}
        </div>
    </div>

    <!-- Edit Transaction Modal -->
    <div x-show="editModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" x-transition.opacity style="display: none;">
        <div class="bg-white rounded-3xl max-w-2xl w-full p-6 space-y-5 shadow-2xl max-h-[90vh] overflow-y-auto custom-scrollbar" @click.away="editModalOpen = false">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="font-bold text-slate-800 text-lg">Edit Transaksi <span x-text="editTx?.invoice_number" class="text-brand-500"></span></h3>
                <button @click="editModalOpen = false" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>

            <template x-if="editTx">
                <form :action="'/admin/transactions/' + editTx.id + '/update'" method="POST" class="space-y-4">
                    @csrf
                    
                    <!-- Tanggal Transaksi -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Tanggal Transaksi (Created At)</label>
                        <input type="datetime-local" name="created_at" 
                               :value="editTx.created_at ? new Date(editTx.created_at).toISOString().slice(0, 16) : ''" required
                               class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold outline-none focus:ring-2 focus:ring-brand-500">
                    </div>

                    <!-- Item Produk -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-xs font-bold text-slate-700 uppercase">Item Transaksi</label>
                            
                            <!-- Select Add Product -->
                            <select @change="if($event.target.value) { addItem(allProducts.find(p => p.id == $event.target.value)); $event.target.value=''; }"
                                    class="px-3 py-1 bg-brand-50 text-brand-500 border border-brand-200 rounded-lg text-xs font-bold outline-none">
                                <option value="">+ Tambah Produk</option>
                                <template x-for="p in allProducts" :key="p.id">
                                    <option :value="p.id" x-text="p.name + ' - Rp ' + p.price"></option>
                                </template>
                            </select>
                        </div>

                        <div class="space-y-2 max-h-48 overflow-y-auto custom-scrollbar">
                            <template x-for="(item, index) in editItems" :key="index">
                                <div class="flex items-center justify-between p-3 bg-slate-50 rounded-xl border border-slate-200 text-xs font-semibold">
                                    <div class="flex-1">
                                        <input type="hidden" :name="'items['+index+'][product_id]'" :value="item.product_id">
                                        <span x-text="item.product_name" class="text-slate-800 font-bold block"></span>
                                        <span class="text-slate-400" x-text="'Rp ' + item.price.toLocaleString() + ' / porsi'"></span>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <input type="number" :name="'items['+index+'][qty]'" x-model.number="item.quantity" min="1" required
                                               class="w-16 px-2 py-1 bg-white border border-slate-200 rounded-lg text-center font-bold text-slate-800">
                                        <button type="button" @click="removeItem(index)" class="text-rose-500 hover:text-rose-700 px-2 py-1"><i class="fa-solid fa-trash"></i></button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Pembayaran -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Metode Pembayaran</label>
                            <select name="payment_method" x-model="editTx.payment_method" required
                                    class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                                <option value="tunai">Tunai / Cash</option>
                                <option value="qris">QRIS</option>
                                <option value="lainnya">Lainnya</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nominal Bayar (Rp)</label>
                            <input type="number" name="pay_amount" x-model.number="editTx.pay_amount" required
                                   class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                        </div>
                    </div>

                    <input type="hidden" name="total_price" :value="totalPrice">

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                        <div>
                            <span class="text-xs text-slate-400 block">Total Baru:</span>
                            <span class="font-bold text-lg text-brand-500" x-text="'Rp ' + totalPrice.toLocaleString()"></span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button type="button" @click="editModalOpen = false" class="px-4 py-2 bg-slate-100 text-slate-600 font-bold rounded-xl text-xs">Batal</button>
                            <button type="submit" class="px-5 py-2 bg-brand-500 hover:bg-brand-600 text-white font-bold rounded-xl text-xs shadow-md">Simpan Perubahan</button>
                        </div>
                    </div>

                </form>
            </template>
        </div>
    </div>

</div>
@endsection
