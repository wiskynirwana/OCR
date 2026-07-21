<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcessingLog extends Model
{
    protected $table = 'log_proses';

    protected $fillable = ['dokumen_id', 'tahap', 'level', 'pesan'];

    public function document()
    {
        return $this->belongsTo(Document::class, 'dokumen_id');
    }
}
