@extends('layouts.admin')

@section('title', 'Chat Cabang')
@section('page_title', 'Chat & Komunikasi Cabang')

@section('content')
<div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden h-[calc(100vh-140px)] flex flex-col md:flex-row">

    <!-- Shop Conversations List (Left Sidebar) -->
    <div class="w-full md:w-80 border-b md:border-b-0 md:border-r border-slate-200 flex flex-col bg-slate-50">
        <div class="p-4 border-b border-slate-200">
            <h3 class="font-bold text-slate-800 text-base">Pilih Cabang Toko</h3>
            <p class="text-xs text-slate-400">Pesan langsung dengan kasir cabang</p>
        </div>

        <div class="flex-1 overflow-y-auto custom-scrollbar divide-y divide-slate-100">
            @foreach($shops as $shop)
                <a href="{{ route('admin.chat', ['shop_id' => $shop->id]) }}" 
                   class="p-4 flex items-center justify-between hover:bg-white transition cursor-pointer {{ $selectedShopId == $shop->id ? 'bg-white border-l-4 border-brand-500 shadow-sm' : '' }}">
                    <div class="flex items-center space-x-3 overflow-hidden">
                        <div class="w-10 h-10 rounded-xl bg-brand-50 text-brand-500 font-bold flex items-center justify-center text-sm flex-shrink-0">
                            <i class="fa-solid fa-store"></i>
                        </div>
                        <div class="truncate">
                            <h4 class="font-bold text-sm text-slate-800 truncate">{{ $shop->name }}</h4>
                            <p class="text-xs text-slate-400 truncate">{{ $shop->address }}</p>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>

    <!-- Chat Messages Window (Right Pane) -->
    <div class="flex-1 flex flex-col min-w-0 bg-white">
        
        @if($selectedShopId)
            @php $currentShop = $shops->find($selectedShopId); @endphp
            
            <!-- Chat Header -->
            <div class="p-4 border-b border-slate-200 flex items-center justify-between bg-slate-50">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-xl bg-brand-500 text-white font-bold flex items-center justify-center text-sm shadow-md shadow-brand-500/20">
                        <i class="fa-solid fa-store"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-800 text-base">{{ $currentShop->name ?? 'Cabang' }}</h3>
                        <p class="text-xs text-slate-400">{{ $currentShop->address ?? '' }}</p>
                    </div>
                </div>
            </div>

            <!-- Messages Stream -->
            <div class="flex-1 p-6 overflow-y-auto custom-scrollbar space-y-4 bg-slate-50/50">
                @forelse($messages as $m)
                    @php $isMe = ($m->user_id === Auth::id()); @endphp
                    <div class="flex flex-col {{ $isMe ? 'items-end' : 'items-start' }}">
                        <div class="flex items-center space-x-2 text-[10px] text-slate-400 mb-1 px-1">
                            <span class="font-bold text-slate-600">{{ $m->user->name ?? 'User' }}</span>
                            <span>• {{ $m->created_at ? $m->created_at->format('H:i, d MMM') : '' }}</span>
                        </div>
                        <div class="max-w-md px-4 py-3 rounded-2xl text-sm font-medium shadow-sm {{ $isMe ? 'bg-brand-500 text-white rounded-tr-none' : 'bg-white text-slate-800 border border-slate-200 rounded-tl-none' }}">
                            {{ $m->message }}
                        </div>
                    </div>
                @empty
                    <div class="h-full flex flex-col items-center justify-center text-slate-400 text-xs py-12">
                        <i class="fa-solid fa-comments text-4xl mb-2 text-slate-300"></i>
                        <span>Belum ada percakapan dengan cabang ini</span>
                    </div>
                @endforelse
            </div>

            <!-- Send Message Input Form -->
            <div class="p-4 border-t border-slate-200 bg-white">
                <form action="{{ route('admin.chat.send') }}" method="POST" class="flex items-center space-x-3">
                    @csrf
                    <input type="hidden" name="shop_id" value="{{ $selectedShopId }}">
                    <input type="text" name="message" required placeholder="Tulis pesan ke cabang ini..."
                           class="flex-1 px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold outline-none focus:ring-2 focus:ring-brand-500">
                    <button type="submit" class="px-6 py-3 bg-brand-500 hover:bg-brand-600 text-white font-bold rounded-xl text-sm shadow-md shadow-brand-500/20 flex items-center space-x-2 transition">
                        <span>Kirim</span>
                        <i class="fa-solid fa-paper-plane text-xs"></i>
                    </button>
                </form>
            </div>
        @else
            <div class="h-full flex flex-col items-center justify-center text-slate-400 text-xs">
                <i class="fa-solid fa-comments text-5xl mb-3 text-slate-300"></i>
                <p class="font-bold text-slate-600 text-sm">Pilih Cabang Toko</p>
                <p>Klik salah satu cabang di sebelah kiri untuk melihat pesan</p>
            </div>
        @endif

    </div>

</div>
@endsection
