<?php

namespace App\Services;

class TemplateDetector
{
    // Tebak jenis dokumen dari teks OCR; null kalau gak ada yang match.
    public function detect(string $ocrText): ?string
    {
        $text = strtoupper($ocrText);
        $best = null;
        $bestScore = 0;

        foreach (config('doctypes.templates') as $type => $keywords) {
            $score = 0;
            foreach ($keywords as $kw) {
                if (str_contains($text, strtoupper($kw))) $score++;
            }
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $type;
            }
        }

        return $best;
    }
}
