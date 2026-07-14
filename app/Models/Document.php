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
        // Isi otomatis pemilik dokumen saat dibuat lewat request web.
        static::creating(function (Document $document) {
            if (empty($document->user_id) && Auth::check()) {
                $document->user_id = Auth::id();
            }
        });

        // Global scope: setiap query dokumen otomatis dibatasi ke user
        // yang sedang login, sehingga tiap akun hanya melihat miliknya.
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

    /**
     * Nama folder arsip untuk dokumen ini — sama dengan struktur folder
     * pada output ZIP (per jenis + status + tahun). Dipakai untuk
     * mengelompokkan Riwayat Dokumen dan menentukan folder output.
     */
    public function archiveFolder(): string
    {
        $data = $this->extracted ?? [];

        $tanggal = $data['tanggal'] ?? '';
        $tahun = strlen($tanggal) >= 4
            ? substr($tanggal, 0, 4)
            : ($this->created_at?->format('Y') ?? date('Y'));

        if ($this->doc_type === 'spk') {
            // Normalisasi kode & status: buang spasi/karakter non-alfanumerik
            // supaya "SPK 1" dan "SPK1" dianggap sama — dokumen dengan jenis
            // & tahun yang sama tidak terpecah ke folder yang berbeda.
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
