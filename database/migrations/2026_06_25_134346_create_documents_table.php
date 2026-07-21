<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {

    $table->id();
    $table->string('original_filename');
    $table->string('doc_type')->nullable();      // sk, spk, ktp, akte, kk, keputusan
    $table->string('new_filename')->nullable();
    $table->string('periode')->nullable();
    $table->json('extracted')->nullable();
    $table->longText('ocr_text')->nullable();
    $table->string('status')->default('pending'); // pending, processed, confirmed, error
    $table->text('error_message')->nullable();
    $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};


