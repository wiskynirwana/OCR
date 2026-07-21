<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('processing_logs', function (Blueprint $table) {

    $table->id();
    $table->foreignId('document_id')->constrained()->cascadeOnDelete();
    $table->string('step');                    // pdf_convert, preprocess, ocr, parse, rename
    $table->string('level')->default('info');
    $table->text('message')->nullable();
    $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('processing_logs');
    }
};
