<?php

namespace App\Services;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class PreprocessService
{
    /**
     * Bersihin gambar sesuai profil.
     * @param  string $imagePath  Path gambar mentah
     * @param  string $profile    Nama profil: threshold | blue_flatten | kk_grid
     * @return string             Path gambar bersih hasil proses
     */
    public function process(string $imagePath, string $profile): string
    {
        // Path output: nama sama + "_clean" (contoh: page-1.png -> page-1_clean.png)
        $outputPath = preg_replace('/\.png$/', '_clean.png', $imagePath);

        // Lokasi skrip python di folder scripts/
        $script = base_path('scripts/preprocess.py');

        // Jalanin: python preprocess.py <input> <output> <profil>
        $process = new Process(['python', $script, $imagePath, $outputPath, $profile]);
        $process->setTimeout(120);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $outputPath;
    }
}
