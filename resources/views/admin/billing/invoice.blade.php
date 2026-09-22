<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoiceNumber }} - Tagihan {{ $monthName }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- html2pdf Library for direct PDF Download -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        body { font-family: 'Outfit', sans-serif; background-color: #F8FAFC; }
        @media print {
            .no-print { display: none !important; }
            body { background-color: #ffffff !important; }
            .print-shadow-none { shadow: none !important; border: none !important; }
        }
    </style>
</head>
<body class="p-4 sm:p-8">

    <!-- Action Buttons (No Print) -->
    <div class="max-w-3xl mx-auto mb-6 flex items-center justify-between no-print">
        <a href="{{ route('admin.billing') }}" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold rounded-xl text-xs flex items-center space-x-2 transition">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Kembali ke Tagihan</span>
        </a>

        <div class="flex items-center space-x-2">
            <button onclick="downloadPDF()" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs shadow-lg shadow-emerald-600/30 flex items-center space-x-2 transition">
                <i class="fa-solid fa-file-pdf"></i>
                <span>Download File PDF</span>
            </button>
            <button onclick="window.print()" class="px-4 py-2.5 bg-slate-800 hover:bg-slate-900 text-white font-bold rounded-xl text-xs flex items-center space-x-2 transition">
                <i class="fa-solid fa-print"></i>
                <span>Cetak</span>
            </button>
        </div>
    </div>

    <!-- Invoice Card Document -->
    <div id="invoice-document" class="max-w-3xl mx-auto bg-white rounded-3xl shadow-xl border border-slate-200 p-8 sm:p-12 relative overflow-hidden print-shadow-none">
        
        <!-- Status Stamp -->
        <div class="absolute top-8 right-8 z-10">
            @if($status === 'Lunas')
                <div class="border-4 border-emerald-500 text-emerald-600 font-black text-xl tracking-widest uppercase px-4 py-1.5 rounded-2xl transform rotate-12 opacity-80 select-none">
                    LUNAS
                </div>
            @elseif($status === 'Belum Jatuh Tempo')
                <div class="border-4 border-blue-500 text-blue-600 font-black text-sm tracking-wider uppercase px-4 py-1.5 rounded-2xl transform rotate-12 opacity-80 select-none">
                    PERIODE BERJALAN
                </div>
            @else
                <div class="border-4 border-rose-500 text-rose-600 font-black text-xl tracking-widest uppercase px-4 py-1.5 rounded-2xl transform rotate-12 opacity-80 select-none">
                    BELUM DIBAYAR
                </div>
            @endif
        </div>

        <!-- Header -->
        <div class="flex flex-col sm:flex-row items-start justify-between border-b border-slate-100 pb-8 mb-8 gap-6">
            <div class="flex items-center space-x-4">
                <div class="w-14 h-14 rounded-2xl text-white flex items-center justify-center text-2xl shadow-lg" style="background-color: #D9383A;">
                    <i class="fa-solid fa-utensils"></i>
                </div>
                <div>
                    <h1 class="font-bold text-2xl text-slate-800 tracking-tight">DIMSUM & GOSAM POS</h1>
                    <p class="text-xs text-slate-400">Layanan Pemeliharaan System & Software POS</p>
                </div>
            </div>

            <div class="text-left sm:text-right space-y-1">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">INVOICE TAGIHAN</span>
                <h2 class="text-lg font-bold text-slate-800">{{ $invoiceNumber }}</h2>
                <p class="text-xs text-slate-500">Tanggal: <strong class="text-slate-700">{{ $lastDayOfMonth }}</strong></p>
            </div>
        </div>

        <!-- Bill To / Bill From Info Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-8 mb-8 text-xs">
            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100 space-y-1">
                <span class="font-bold uppercase text-slate-400 block mb-2 text-[10px]">Ditagihkan Kepada:</span>
                <h3 class="font-bold text-sm text-slate-800">{{ Auth::user()->name ?? 'Owner' }}</h3>
            </div>

            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100 space-y-1">
                <span class="font-bold uppercase text-slate-400 block mb-2 text-[10px]">Penyedia Layanan:</span>
                <h3 class="font-bold text-sm text-slate-800">Tim Pengembang DIMSUM & GOSAM POS</h3>
                <p class="text-slate-600">Sistem Kasir POS & Manajemen Stok Cloud</p>
                <p class="text-slate-500">Dukungan Server & Pemeliharaan Bulanan</p>
            </div>
        </div>

        <!-- Invoice Items Table -->
        <div class="border border-slate-200 rounded-2xl overflow-hidden mb-8">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs font-bold uppercase text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="px-5 py-3.5">Deskripsi Layanan</th>
                        <th class="px-5 py-3.5 text-center">Periode</th>
                        <th class="px-5 py-3.5 text-right">Jumlah (Rp)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    <tr>
                        <td class="px-5 py-4">
                            <span class="font-bold text-slate-800 block">Biaya Pemeliharaan & Langganan Aplikasi DIMSUM & GOSAM POS</span>
                            <span class="text-xs text-slate-400">Termasuk fitur Kasir POS, Manajemen Stok, Multi Cabang, & Server Backup</span>
                        </td>
                        <td class="px-5 py-4 text-center text-xs font-semibold text-slate-600">
                            {{ $monthName }}
                        </td>
                        <td class="px-5 py-4 text-right font-bold text-slate-800">
                            Rp {{ number_format($monthlyFee, 0, ',', '.') }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Total Calculation -->
        <div class="flex flex-col sm:flex-row items-center justify-between bg-slate-50 p-6 rounded-2xl border border-slate-200 gap-4 mb-8">
            <div>
                <span class="text-xs font-bold text-slate-500 block">Metode Pembayaran:</span>
                <span class="text-xs font-semibold text-slate-700">Transfer Bank / QRIS</span>
            </div>
            <div class="text-right">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Total Tagihan</span>
                <h3 class="text-2xl font-black text-slate-800" style="color: #D9383A;">Rp {{ number_format($monthlyFee, 0, ',', '.') }}</h3>
            </div>
        </div>

        <!-- Payment Transfer Instructions -->
        <div class="pt-6 border-t border-slate-100 text-xs text-slate-500 space-y-2">
            <h4 class="font-bold text-slate-700 uppercase text-[11px]">Petunjuk Pembayaran Transfer:</h4>
            <p>Silakan melakukan pembayaran tagihan langganan bulanan ke rekening resmi pengembang.</p>
            <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200 font-semibold text-slate-800 flex flex-col sm:flex-row items-center justify-between gap-2">
                <span>Nominal Tagihan: <strong class="text-slate-900">Rp {{ number_format($monthlyFee, 0, ',', '.') }}</strong></span>
            </div>
            <p class="text-[10px] text-slate-400 italic mt-2">* Invoice ini adalah bukti sah tagihan langganan bulanan sistem DIMSUM & GOSAM POS.</p>
        </div>

    </div>

    <script>
        function downloadPDF() {
            const element = document.getElementById('invoice-document');
            const opt = {
                margin:       0.2,
                filename:     'Invoice-{{ str_replace("/", "-", $invoiceNumber) }}.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2, useCORS: true },
                jsPDF:        { unit: 'in', format: 'a4', orientation: 'portrait' }
            };
            html2pdf().set(opt).from(element).save();
        }
    </script>

</body>
</html>
