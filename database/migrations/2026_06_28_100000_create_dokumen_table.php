<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dokumen', function (Blueprint $table) {
            $table->id();

            // nullable biar dokumen tanpa pemilik gak error; gak tampil di akun mana pun
            $table->foreignId('user_id')->nullable()
                ->constrained()->nullOnDelete();

            // pengelompokan satu sesi upload (uuid)
            $table->string('folder')->nullable()->index();

            $table->string('nama_file_asli');
            $table->string('lokasi_file')->nullable();

            // md5 isi file — dipakai buat deteksi upload duplikat
            $table->string('hash_file', 32)->nullable()->index();

            $table->string('jenis_dokumen')->nullable();   // spk, keputusan
            $table->string('nama_file_baru')->nullable();
            $table->json('hasil_ekstraksi')->nullable();
            $table->longText('teks_ocr')->nullable();

            $table->string('status')->default('pending');  // pending, processing, processed, confirmed, error
            $table->text('pesan_error')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dokumen');
    }
};
