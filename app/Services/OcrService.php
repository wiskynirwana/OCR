<?php

namespace App\Services;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class OcrService
{
    public const PSM = [
        'sk'        => 3,
        'spk'       => 3,
        'keputusan' => 3,
        'ktp'       => 3,
        'akte'      => 8,
        'kk'        => 6,
    ];

    private string $binary;

    public function __construct(?string $binary = null)
    {
        $this->binary = $binary ?? 'tesseract';
    }

    public function read(string $imagePath, int $psm = 3, string $lang = 'ind'): string
    {
        if (!is_file($imagePath)) {
            throw new \RuntimeException("File gambar gak ditemukan: {$imagePath}");
        }

        $process = new Process([
            $this->binary,
            $imagePath,
            'stdout',
            '-l', $lang,
            '--psm', (string) $psm,
        ]);
        $process->setTimeout(120);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return trim($process->getOutput());
    }

    public function readForType(string $imagePath, string $docType, string $lang = 'ind'): string
    {
        $psm = self::PSM[strtolower($docType)] ?? 3;
        return $this->read($imagePath, $psm, $lang);
    }
}
