<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcessingLog extends Model
{
    protected $fillable = ['document_id', 'step', 'level', 'message'];

    public function document()
    {
        return $this->belongsTo(Document::class);
    }
}
