<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('log_proses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dokumen_id')
                ->constrained('dokumen')->cascadeOnDelete();
            $table->string('tahap');                   // mulai, pdf_to_image, deteksi_jenis, ocr, penamaan, selesai, error
            $table->string('level')->default('info');  // info, warning, error
            $table->text('pesan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_proses');
    }
};
