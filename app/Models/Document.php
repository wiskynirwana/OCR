<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class Document extends Model
{
    protected $table = 'dokumen';

    protected $fillable = [
        'user_id',
        'folder',
        'nama_file_asli',
        'lokasi_file',
        'hash_file',
        'jenis_dokumen',
        'nama_file_baru',
        'hasil_ekstraksi',
        'teks_ocr',
        'status',
        'pesan_error',
    ];

    protected $casts = [
        'hasil_ekstraksi' => 'array',
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
                $builder->where('dokumen.user_id', Auth::id());
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
        $data = $this->hasil_ekstraksi ?? [];

        $tanggal = $data['tanggal'] ?? '';
        $tahun = strlen($tanggal) >= 4
            ? substr($tanggal, 0, 4)
            : ($this->created_at?->format('Y') ?? date('Y'));

        if ($this->jenis_dokumen === 'spk') {
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
