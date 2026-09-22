<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Hapus tabel lama jika ada
        Schema::dropIfExists('attendances');

        Schema::create('attendances', function (Blueprint $row) {
            $row->id();
            $row->foreignId('user_id')->constrained()->onDelete('cascade');
            $row->foreignId('shop_id')->constrained()->onDelete('cascade');
            $row->date('date'); // Tanggal absensi (Y-m-d)

            // Data Masuk
            $row->time('in_time')->nullable();
            $row->string('in_latitude')->nullable();
            $row->string('in_longitude')->nullable();
            $row->string('in_photo')->nullable();
            $row->string('in_note')->nullable();

            // Data Pulang
            $row->time('out_time')->nullable();
            $row->string('out_latitude')->nullable();
            $row->string('out_longitude')->nullable();
            $row->string('out_photo')->nullable();
            $row->string('out_note')->nullable();

            $row->timestamps();

            // Index Unik: Satu user hanya boleh punya satu record per hari
            $row->unique(['user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
