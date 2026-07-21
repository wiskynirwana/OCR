<?php

namespace App\Services;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Illuminate\Support\Str;

class PdfToImageService
{
    // 300 DPI paling pas buat OCR
    protected int $dpi = 300;

    public function convert(string $pdfPath): array
    {
        if (!file_exists($pdfPath)) {
            throw new \RuntimeException("File PDF gak ketemu: {$pdfPath}");
        }

        // folder temp unik per file biar gak tabrakan antar proses
        $outputDir = storage_path('app/temp/' . Str::uuid());
        mkdir($outputDir, 0755, true);

        // pdftoppm otomatis nambah -1, -2, dst per halaman
        $prefix = $outputDir . DIRECTORY_SEPARATOR . 'page';

        $process = new Process([
            'pdftoppm',
            '-png',
            '-r', (string) $this->dpi,
            $pdfPath,
            $prefix,
        ]);
        $process->setTimeout(120);      // biar gak nyangkut selamanya
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $images = glob($outputDir . DIRECTORY_SEPARATOR . 'page*.png');
        sort($images);

        return $images;
    }
}
