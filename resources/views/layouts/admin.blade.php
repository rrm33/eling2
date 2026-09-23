<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin Dashboard') - Dimsum POS</title>
    <!-- Outfit Font & Tailwind CSS & FontAwesome -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Alpine.js & Chart.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#fff1f2',
                            100: '#ffe4e6',
                            500: '#D9383A',
                            600: '#C0292B',
                            700: '#A11E20',
                            800: '#831A1C',
                        },
                        darkbg: '#1A1C23',
                        cardbg: '#242632',
                    },
                    fontFamily: {
                        sans: ['Outfit', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Outfit', sans-serif; background-color: #F4F6F8; }
        .custom-scrollbar::-webkit-scrollbar { width: 5px; height: 5px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 10px; }
    </style>
</head>
<body class="text-slate-800 antialiased" x-data="{ sidebarOpen: false }">

    <div class="min-h-screen flex flex-col md:flex-row">
        
        <!-- Mobile Sidebar Overlay -->
        <div x-show="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 z-40 bg-black/50 md:hidden" x-transition.opacity></div>

        <!-- Sidebar Navigation -->
        <aside class="fixed inset-y-0 left-0 z-50 w-64 bg-darkbg text-white flex flex-col transition-transform duration-300 transform md:translate-x-0 md:static md:z-auto"
               :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
            
            <!-- Sidebar Header / Logo -->
            <div class="h-20 flex items-center px-6 border-b border-slate-800">
                <div class="w-10 h-10 rounded-xl bg-brand-500 flex items-center justify-center text-white shadow-lg shadow-brand-500/30 mr-3">
                    <i class="fa-solid fa-utensils text-lg"></i>
                </div>
                <div>
                    <h1 class="font-bold text-base leading-tight tracking-wide">DIMSUM POS</h1>
                    <p class="text-xs text-slate-400">Admin Web Dashboard</p>
                </div>
            </div>

            <!-- Nav Links -->
            <nav class="flex-1 px-4 py-6 space-y-1.5 overflow-y-auto custom-scrollbar">
                <a href="{{ route('admin.dashboard') }}" 
                   class="flex items-center px-4 py-3 text-sm font-semibold rounded-xl transition-all duration-200 {{ request()->routeIs('admin.dashboard') ? 'bg-brand-500 text-white shadow-lg shadow-brand-500/30' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                    <i class="fa-solid fa-chart-pie w-6 text-base"></i>
                    <span>Dashboard</span>
                </a>

                <a href="{{ route('admin.reports') }}" 
                   class="flex items-center px-4 py-3 text-sm font-semibold rounded-xl transition-all duration-200 {{ request()->routeIs('admin.reports') ? 'bg-brand-500 text-white shadow-lg shadow-brand-500/30' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                    <i class="fa-solid fa-chart-line w-6 text-base"></i>
                    <span>Laporan Penjualan</span>
                </a>

                <a href="{{ route('admin.transactions') }}" 
                   class="flex items-center px-4 py-3 text-sm font-semibold rounded-xl transition-all duration-200 {{ request()->routeIs('admin.transactions') ? 'bg-brand-500 text-white shadow-lg shadow-brand-500/30' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                    <i class="fa-solid fa-receipt w-6 text-base"></i>
                    <span>Riwayat Transaksi</span>
                </a>

                <a href="{{ route('admin.products') }}" 
                   class="flex items-center px-4 py-3 text-sm font-semibold rounded-xl transition-all duration-200 {{ request()->routeIs('admin.products') ? 'bg-brand-500 text-white shadow-lg shadow-brand-500/30' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                    <i class="fa-solid fa-box-open w-6 text-base"></i>
                    <span>Produk & Stok</span>
                </a>

                <a href="{{ route('admin.shops') }}" 
                   class="flex items-center px-4 py-3 text-sm font-semibold rounded-xl transition-all duration-200 {{ request()->routeIs('admin.shops') ? 'bg-brand-500 text-white shadow-lg shadow-brand-500/30' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                    <i class="fa-solid fa-store w-6 text-base"></i>
                    <span>Cabang / Toko</span>
                </a>

                <a href="{{ route('admin.users') }}" 
                   class="flex items-center px-4 py-3 text-sm font-semibold rounded-xl transition-all duration-200 {{ request()->routeIs('admin.users') ? 'bg-brand-500 text-white shadow-lg shadow-brand-500/30' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                    <i class="fa-solid fa-users-gear w-6 text-base"></i>
                    <span>Kelola Pengguna</span>
                </a>

                <a href="{{ route('admin.finance') }}" 
                   class="flex items-center px-4 py-3 text-sm font-semibold rounded-xl transition-all duration-200 {{ request()->routeIs('admin.finance') ? 'bg-brand-500 text-white shadow-lg shadow-brand-500/30' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                    <i class="fa-solid fa-wallet w-6 text-base"></i>
                    <span>Keuangan & Kas</span>
                </a>

                <a href="{{ route('admin.shifts') }}" 
                   class="flex items-center px-4 py-3 text-sm font-semibold rounded-xl transition-all duration-200 {{ request()->routeIs('admin.shifts') ? 'bg-brand-500 text-white shadow-lg shadow-brand-500/30' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                    <i class="fa-solid fa-clock-rotate-left w-6 text-base"></i>
                    <span>Shift Kasir</span>
                </a>

                <a href="{{ route('admin.attendance') }}" 
                   class="flex items-center px-4 py-3 text-sm font-semibold rounded-xl transition-all duration-200 {{ request()->routeIs('admin.attendance') ? 'bg-brand-500 text-white shadow-lg shadow-brand-500/30' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                    <i class="fa-solid fa-id-card-clip w-6 text-base"></i>
                    <span>Absensi Karyawan</span>
                </a>

                <a href="{{ route('admin.chat') }}" 
                   class="flex items-center px-4 py-3 text-sm font-semibold rounded-xl transition-all duration-200 {{ request()->routeIs('admin.chat') ? 'bg-brand-500 text-white shadow-lg shadow-brand-500/30' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                    <i class="fa-solid fa-comments w-6 text-base"></i>
                    <span>Chat Cabang</span>
                </a>

                <a href="{{ route('admin.billing') }}" 
                   class="flex items-center px-4 py-3 text-sm font-semibold rounded-xl transition-all duration-200 {{ request()->routeIs('admin.billing*') ? 'bg-brand-500 text-white shadow-lg shadow-brand-500/30' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                    <i class="fa-solid fa-file-invoice-dollar w-6 text-base"></i>
                    <span>Tagihan Aplikasi</span>
                </a>
            </nav>

            <!-- User Info / Logout Footer -->
            <div class="p-4 border-t border-slate-800 bg-slate-900/50">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3 overflow-hidden">
                        <div class="w-9 h-9 rounded-full bg-brand-500/20 text-brand-500 flex items-center justify-center font-bold text-sm flex-shrink-0">
                            {{ strtoupper(substr(Auth::user()->name ?? 'A', 0, 1)) }}
                        </div>
                        <div class="truncate">
                            <p class="text-sm font-semibold text-white truncate">{{ Auth::user()->name ?? 'Admin' }}</p>
                            <p class="text-xs text-slate-400 capitalize">{{ Auth::user()->role ?? 'Admin' }}</p>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" title="Logout" class="w-8 h-8 rounded-lg bg-slate-800 text-slate-400 hover:text-rose-500 hover:bg-slate-700 flex items-center justify-center transition">
                            <i class="fa-solid fa-right-from-bracket text-xs"></i>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0">
            
            <!-- Top Header Bar -->
            <header class="h-20 bg-white border-b border-slate-200 px-6 flex items-center justify-between sticky top-0 z-30 shadow-sm">
                <div class="flex items-center space-x-4">
                    <button @click="sidebarOpen = !sidebarOpen" class="md:hidden text-slate-600 hover:text-brand-500 focus:outline-none">
                        <i class="fa-solid fa-bars text-xl"></i>
                    </button>
                    <h2 class="text-xl font-bold text-slate-800">@yield('page_title', 'Dashboard')</h2>
                </div>

                <div class="flex items-center space-x-4">
                    <!-- Refresh Button -->
                    <button onclick="window.location.reload()" class="w-10 h-10 rounded-xl bg-slate-100 text-slate-600 hover:bg-slate-200 flex items-center justify-center transition" title="Refresh Data">
                        <i class="fa-solid fa-rotate-right text-sm"></i>
                    </button>

                    <!-- User Profile Dropdown -->
                    <div class="flex items-center space-x-3 bg-slate-50 px-3 py-1.5 rounded-xl border border-slate-200">
                        <div class="w-8 h-8 rounded-lg bg-brand-500 text-white flex items-center justify-center font-bold text-xs">
                            {{ strtoupper(substr(Auth::user()->name ?? 'A', 0, 1)) }}
                        </div>
                        <span class="text-sm font-semibold text-slate-700 hidden sm:inline">{{ Auth::user()->name ?? 'Admin' }}</span>
                    </div>
                </div>
            </header>

            <!-- Page Body -->
            <main class="flex-1 p-6 overflow-y-auto">
                <!-- Session Alerts -->
                @if(session('success'))
                    <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3.5 rounded-xl flex items-center space-x-3 shadow-sm">
                        <i class="fa-solid fa-circle-check text-emerald-500 text-lg"></i>
                        <span class="text-sm font-medium">{{ session('success') }}</span>
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-6 bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3.5 rounded-xl flex items-center space-x-3 shadow-sm">
                        <i class="fa-solid fa-triangle-exclamation text-rose-500 text-lg"></i>
                        <span class="text-sm font-medium">{{ session('error') }}</span>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-6 bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3.5 rounded-xl space-y-1 shadow-sm">
                        <div class="flex items-center space-x-3">
                            <i class="fa-solid fa-triangle-exclamation text-rose-500 text-lg"></i>
                            <span class="text-sm font-bold">Harap periksa kembali input Anda:</span>
                        </div>
                        <ul class="list-disc list-inside text-xs pl-8 space-y-0.5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </main>

        </div>
    </div>

    @yield('scripts')
</body>
</html>
