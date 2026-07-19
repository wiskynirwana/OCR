<?php

namespace App\Console\Commands;

use App\Models\Document;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class WipeDocuments extends Command
{
    protected $signature = 'documents:wipe {--force : Jalankan tanpa konfirmasi}';

    protected $description = 'Hapus SEMUA dokumen: record di database + seluruh file di storage (uploads, outputs, temp)';

    public function handle(): int
    {
        if (!$this->option('force') && !$this->confirm('Ini menghapus SEMUA dokumen semua user + file fisiknya. Lanjut?')) {
            $this->info('Dibatalkan.');
            return self::SUCCESS;
        }

        // Tanpa global scope 'owner' — sapu milik semua user.
        $jumlah = Document::withoutGlobalScope('owner')->count();
        Document::withoutGlobalScope('owner')->delete();

        foreach (['uploads', 'outputs', 'temp'] as $dir) {
            Storage::deleteDirectory($dir);
        }

        $this->info("Selesai: {$jumlah} record dokumen dihapus, folder uploads/outputs/temp dikosongkan.");
        return self::SUCCESS;
    }
}
