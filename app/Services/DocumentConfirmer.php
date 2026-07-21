<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Support\Facades\Storage;

class DocumentConfirmer
{
    // Rename + pindah file ke folder output final. Return false kalau dokumen belum siap.
    public function confirm(Document $document): bool
    {
        if ($document->status !== 'processed') {
            return false;
        }

        if (!$document->nama_file_baru) {
            return false;
        }

        if (!$document->lokasi_file || !Storage::exists($document->lokasi_file)) {
            return false;
        }

        $finalName = $document->nama_file_baru;

        if (!str_ends_with(strtolower($finalName), '.pdf')) {
            $finalName .= '.pdf';
        }

        $folder = $document->archiveFolder();
        $targetPath = "outputs/{$folder}/{$finalName}";

        if (Storage::exists($targetPath)) {
            $nameOnly = pathinfo($finalName, PATHINFO_FILENAME);
            $extension = pathinfo($finalName, PATHINFO_EXTENSION);

            $targetPath = "outputs/{$folder}/{$nameOnly} - " . now()->format('YmdHis') . ".{$extension}";
        }

        Storage::move($document->lokasi_file, $targetPath);

        $document->update([
            'lokasi_file' => $targetPath,
            'status' => 'confirmed',
        ]);

        return true;
    }
}
