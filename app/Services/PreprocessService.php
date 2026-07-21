<?php

namespace App\Services;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class PreprocessService
{
    // profil: threshold | blue_flatten | kk_grid
    public function process(string $imagePath, string $profile): string
    {
        $outputPath = preg_replace('/\.png$/', '_clean.png', $imagePath);

        $script = base_path('scripts/preprocess.py');

        $process = new Process(['python', $script, $imagePath, $outputPath, $profile]);
        $process->setTimeout(120);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $outputPath;
    }
}
