<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcessingLog extends Model
{
    // kolom yang boleh diisi massal (sesuai migration processing_logs)
    protected $fillable = ['document_id', 'step', 'level', 'message'];

    // 1 log dimiliki 1 dokumen (buat relasi balik kalau perlu)
    public function document()
    {
        return $this->belongsTo(Document::class);
    }
}
