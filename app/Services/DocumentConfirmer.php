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

        if (!$document->new_filename) {
            return false;
        }

        if (!$document->stored_path || !Storage::exists($document->stored_path)) {
            return false;
        }

        $finalName = $document->new_filename;

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

        Storage::move($document->stored_path, $targetPath);

        $document->update([
            'stored_path' => $targetPath,
            'status' => 'confirmed',
        ]);

        return true;
    }
}
