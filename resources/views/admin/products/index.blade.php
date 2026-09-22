@extends('layouts.admin')

@section('title', 'Produk & Stok')
@section('page_title', 'Kelola Produk & Stok')

@section('content')
<div class="space-y-6" x-data="{ 
    activeTab: 'products',
    productModalOpen: false,
    categoryModalOpen: false,
    editProduct: null,
    editCategory: null,

    openAddProduct() {
        this.editProduct = null;
        this.productModalOpen = true;
    },

    openEditProduct(p) {
        this.editProduct = p;
        this.productModalOpen = true;
    },

    openAddCategory() {
        this.editCategory = null;
        this.categoryModalOpen = true;
    },

    openEditCategory(c) {
        this.editCategory = c;
        this.categoryModalOpen = true;
    }
}">

    <!-- Top Action & Navigation Tabs -->
    <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-4">
        
        <!-- Tabs -->
        <div class="flex items-center space-x-2 bg-slate-100 p-1.5 rounded-xl text-xs font-bold w-full sm:w-auto">
            <button @click="activeTab = 'products'" 
                    :class="activeTab === 'products' ? 'bg-white text-brand-500 shadow-sm' : 'text-slate-500 hover:text-slate-800'"
                    class="px-5 py-2.5 rounded-lg transition">
                <i class="fa-solid fa-box-open mr-1.5"></i> Daftar Produk ({{ $products->count() }})
            </button>
            <button @click="activeTab = 'categories'" 
                    :class="activeTab === 'categories' ? 'bg-white text-brand-500 shadow-sm' : 'text-slate-500 hover:text-slate-800'"
                    class="px-5 py-2.5 rounded-lg transition">
                <i class="fa-solid fa-tags mr-1.5"></i> Kategori ({{ $categories->count() }})
            </button>
        </div>

        <!-- Add Button -->
        <div>
            <template x-if="activeTab === 'products'">
                <button @click="openAddProduct()" class="w-full sm:w-auto px-5 py-2.5 bg-brand-500 hover:bg-brand-600 text-white font-bold rounded-xl text-xs shadow-lg shadow-brand-500/30 flex items-center justify-center space-x-2 transition">
                    <i class="fa-solid fa-plus"></i>
                    <span>Tambah Produk Baru</span>
                </button>
            </template>
            <template x-if="activeTab === 'categories'">
                <button @click="openAddCategory()" class="w-full sm:w-auto px-5 py-2.5 bg-brand-500 hover:bg-brand-600 text-white font-bold rounded-xl text-xs shadow-lg shadow-brand-500/30 flex items-center justify-center space-x-2 transition">
                    <i class="fa-solid fa-plus"></i>
                    <span>Tambah Kategori Baru</span>
                </button>
            </template>
        </div>

    </div>

    <!-- PRODUCTS TAB CONTENT -->
    <div x-show="activeTab === 'products'" class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="text-xs font-bold uppercase bg-slate-50 text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3.5">Gambar</th>
                        <th class="px-4 py-3.5">Nama Produk</th>
                        <th class="px-4 py-3.5">Kategori</th>
                        <th class="px-4 py-3.5 text-right">Harga (Rp)</th>
                        <th class="px-4 py-3.5 text-center">Bundle Qty (Butir)</th>
                        <th class="px-4 py-3.5 text-center">Status</th>
                        <th class="px-4 py-3.5 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($products as $p)
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="px-4 py-3.5">
                                @if($p->image)
                                    <img src="{{ $p->image_url }}" class="w-12 h-12 rounded-xl object-cover border border-slate-200" onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode($p->name) }}&background=D9383A&color=ffffff'">
                                @else
                                    <div class="w-12 h-12 rounded-xl bg-brand-50 text-brand-500 flex items-center justify-center font-bold text-base">
                                        <i class="fa-solid fa-cookie-bite"></i>
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 font-bold text-slate-800">
                                {{ $p->name }}
                                @if($p->description)
                                    <p class="text-xs font-normal text-slate-400 truncate max-w-xs">{{ $p->description }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-xs">
                                <span class="px-2.5 py-1 bg-slate-100 text-slate-700 font-bold rounded-lg">
                                    {{ $p->category->name ?? 'Uncategorized' }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-right font-bold text-brand-500">
                                Rp {{ number_format($p->price, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                @if($p->bundle_qty > 0)
                                    <span class="px-2.5 py-1 bg-amber-50 text-amber-700 font-bold rounded-lg text-xs">{{ $p->bundle_qty }} butir / porsi</span>
                                @else
                                    <span class="text-slate-400 text-xs">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                @if($p->status === 'active')
                                    <span class="px-2.5 py-1 bg-emerald-50 text-emerald-600 font-bold rounded-lg text-xs">Aktif</span>
                                @else
                                    <span class="px-2.5 py-1 bg-slate-100 text-slate-500 font-bold rounded-lg text-xs">Nonaktif</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <div class="flex items-center justify-center space-x-1.5">
                                    <button @click="openEditProduct({{ json_encode($p) }})"
                                            class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-100 flex items-center justify-center transition" title="Edit Produk">
                                        <i class="fa-solid fa-pen-to-square text-xs"></i>
                                    </button>
                                    <form method="POST" action="{{ route('admin.products.delete', $p->id) }}" onsubmit="return confirm('Yakin ingin menghapus produk ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 flex items-center justify-center transition" title="Hapus Produk">
                                            <i class="fa-solid fa-trash text-xs"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-slate-400 text-xs">
                                Belum ada produk ditambahkan
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- CATEGORIES TAB CONTENT -->
    <div x-show="activeTab === 'categories'" class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden" style="display: none;">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="text-xs font-bold uppercase bg-slate-50 text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3.5">ID</th>
                        <th class="px-4 py-3.5">Nama Kategori</th>
                        <th class="px-4 py-3.5 text-center">Jumlah Produk</th>
                        <th class="px-4 py-3.5 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($categories as $c)
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="px-4 py-3.5 text-xs font-bold text-slate-400">#{{ $c->id }}</td>
                            <td class="px-4 py-3.5 font-bold text-slate-800">{{ $c->name }}</td>
                            <td class="px-4 py-3.5 text-center">
                                <span class="px-2.5 py-1 bg-slate-100 text-slate-700 font-bold rounded-lg text-xs">{{ $c->products_count ?? 0 }} produk</span>
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <div class="flex items-center justify-center space-x-1.5">
                                    <button @click="openEditCategory({{ json_encode($c) }})"
                                            class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-100 flex items-center justify-center transition">
                                        <i class="fa-solid fa-pen-to-square text-xs"></i>
                                    </button>
                                    <form method="POST" action="{{ route('admin.categories.delete', $c->id) }}" onsubmit="return confirm('Yakin ingin menghapus kategori ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 flex items-center justify-center transition">
                                            <i class="fa-solid fa-trash text-xs"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-12 text-center text-slate-400 text-xs">
                                Belum ada kategori
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Product Modal (Add/Edit) -->
    <div x-show="productModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" x-transition.opacity style="display: none;">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 space-y-4 shadow-2xl" @click.away="productModalOpen = false">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="font-bold text-slate-800 text-lg" x-text="editProduct ? 'Edit Produk' : 'Tambah Produk Baru'"></h3>
                <button @click="productModalOpen = false" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>

            <form :action="editProduct ? '/admin/products/' + editProduct.id + '/update' : '{{ route('admin.products.store') }}'" 
                  method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nama Produk</label>
                    <input type="text" name="name" :value="editProduct ? editProduct.name : ''" required
                           placeholder="Contoh: Dimsum Ayam Porsi Big"
                           class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold outline-none focus:ring-2 focus:ring-brand-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Kategori</label>
                        <select name="category_id" required class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold">
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" :selected="editProduct && editProduct.category_id == {{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Harga Jual (Rp)</label>
                        <input type="number" name="price" :value="editProduct ? editProduct.price : ''" required
                               placeholder="15000"
                               class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Bundle Qty (Butir per porsi)</label>
                        <input type="number" name="bundle_qty" :value="editProduct ? editProduct.bundle_qty : '0'"
                               placeholder="4 (isi 4 butir per porsi)"
                               class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Status</label>
                        <select name="status" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold">
                            <option value="active" :selected="!editProduct || editProduct.status == 'active'">Aktif</option>
                            <option value="inactive" :selected="editProduct && editProduct.status == 'inactive'">Nonaktif</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Foto Produk</label>
                    <input type="file" name="image" accept="image/*" class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-brand-50 file:text-brand-500 hover:file:bg-brand-100">
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end space-x-2">
                    <button type="button" @click="productModalOpen = false" class="px-4 py-2 bg-slate-100 text-slate-600 font-bold rounded-xl text-xs">Batal</button>
                    <button type="submit" class="px-5 py-2 bg-brand-500 hover:bg-brand-600 text-white font-bold rounded-xl text-xs shadow-md">Simpan Produk</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Category Modal (Add/Edit) -->
    <div x-show="categoryModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" x-transition.opacity style="display: none;">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 space-y-4 shadow-2xl" @click.away="categoryModalOpen = false">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="font-bold text-slate-800 text-lg" x-text="editCategory ? 'Edit Kategori' : 'Tambah Kategori Baru'"></h3>
                <button @click="categoryModalOpen = false" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>

            <form :action="editCategory ? '/admin/categories/' + editCategory.id + '/update' : '{{ route('admin.categories.store') }}'" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nama Kategori</label>
                    <input type="text" name="name" :value="editCategory ? editCategory.name : ''" required
                           placeholder="Contoh: Paket Dimsum, Minuman, Saus"
                           class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold outline-none focus:ring-2 focus:ring-brand-500">
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end space-x-2">
                    <button type="button" @click="categoryModalOpen = false" class="px-4 py-2 bg-slate-100 text-slate-600 font-bold rounded-xl text-xs">Batal</button>
                    <button type="submit" class="px-5 py-2 bg-brand-500 hover:bg-brand-600 text-white font-bold rounded-xl text-xs shadow-md">Simpan Kategori</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
