<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {

    $table->id();
    $table->string('original_filename');
    $table->string('doc_type')->nullable();      // sk, spk, ktp, akte, kk, keputusan
    $table->string('new_filename')->nullable();  // hasil rename
    $table->string('periode')->nullable();        // contoh: 2026-01 (buat folder)
    $table->json('extracted')->nullable();        // field hasil parse (KODE/STATUS/TGL/SEQ/NAMA, atau KK: {kepala_keluarga, anggota[]})
    $table->longText('ocr_text')->nullable();     // teks mentah OCR (buat debug)
    $table->string('status')->default('pending'); // pending, processed, confirmed, error
    $table->text('error_message')->nullable();
    $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};


