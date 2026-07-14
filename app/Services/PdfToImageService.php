<?php

namespace App\Services;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Illuminate\Support\Str;

class PdfToImageService
{
    // Resolusi gambar hasil convert. 300 DPI = tajam, paling bagus buat OCR.
    protected int $dpi = 300;

    /**
     * Ubah PDF jadi gambar PNG.
     * @param  string $pdfPath  Path lengkap ke file PDF
     * @return array<string>    Daftar path PNG hasil convert
     */
    public function convert(string $pdfPath): array
    {
        // 1. Pastikan file PDF-nya beneran ada
        if (!file_exists($pdfPath)) {
            throw new \RuntimeException("File PDF gak ketemu: {$pdfPath}");
        }

        // 2. Bikin folder sementara yang UNIK (pakai uuid) biar gak tabrakan antar file
        $outputDir = storage_path('app/temp/' . Str::uuid());
        mkdir($outputDir, 0755, true);

        // 3. Prefix nama output. pdftoppm otomatis nambah -1, -2, dst per halaman
        $prefix = $outputDir . DIRECTORY_SEPARATOR . 'page';

        // 4. Susun perintah: pdftoppm -png -r 300 <input.pdf> <prefix>
        $process = new Process([
            'pdftoppm',
            '-png',                     // output format PNG
            '-r', (string) $this->dpi,  // resolusi 300 DPI
            $pdfPath,                   // file masuk
            $prefix,                    // prefix file keluar
        ]);
        $process->setTimeout(120);      // batas 2 menit, biar gak nyangkut selamanya
        $process->run();

        // 5. Kalo pdftoppm gagal, lempar error biar ketahuan (jangan diem-diem)
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        // 6. Kumpulin semua PNG hasil convert, urutkan per halaman
        $images = glob($outputDir . DIRECTORY_SEPARATOR . 'page*.png');
        sort($images);

        return $images;
    }
}
