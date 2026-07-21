<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class Document extends Model
{
    protected $fillable = [
        'user_id',
        'batch_id',
        'original_filename',
        'stored_path',
        'file_hash',
        'doc_type',
        'new_filename',
        'periode',
        'extracted',
        'ocr_text',
        'status',
        'error_message',
    ];

    protected $casts = [
        'extracted' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (Document $document) {
            if (empty($document->user_id) && Auth::check()) {
                $document->user_id = Auth::id();
            }
        });

        // global scope: semua query otomatis dibatasi ke user login, jadi tiap akun cuma lihat miliknya
        static::addGlobalScope('owner', function (Builder $builder) {
            if (Auth::check()) {
                $builder->where('documents.user_id', Auth::id());
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // nama folder arsip, sama dengan struktur folder di output ZIP
    public function archiveFolder(): string
    {
        $data = $this->extracted ?? [];

        $tanggal = $data['tanggal'] ?? '';
        $tahun = strlen($tanggal) >= 4
            ? substr($tanggal, 0, 4)
            : ($this->created_at?->format('Y') ?? date('Y'));

        if ($this->doc_type === 'spk') {
            // normalisasi biar "SPK 1" dan "SPK1" gak terpecah jadi folder beda
            $kode = preg_replace('/[^A-Z0-9]/', '', strtoupper($data['kode'] ?? 'SPK'));
            if ($kode === '') {
                $kode = 'SPK';
            }
            $status = preg_replace('/[^A-Z0-9]/', '', strtoupper($data['status'] ?? ''));
            $folder = 'SCAN ' . trim($kode . ' ' . $status) . ' ' . $tahun;
        } else {
            $folder = 'SCAN LAINNYA ' . $tahun;
        }

        $folder = preg_replace('/[\/\\\\:*?"<>|]/', '', $folder);
        $folder = preg_replace('/\s+/', ' ', trim($folder));

        return $folder !== '' ? $folder : 'SCAN LAINNYA ' . $tahun;
    }
}
