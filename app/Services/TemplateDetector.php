<?php

namespace App\Services;

class TemplateDetector
{
    // Tebak jenis dokumen dari teks OCR. Balikin 'sk'/'spk'/'ktp'/dst, atau null kalau gak yakin.
    public function detect(string $ocrText): ?string
    {
        $text = strtoupper($ocrText);
        $best = null;
        $bestScore = 0;

        foreach (config('doctypes.templates') as $type => $keywords) {  // baca dari 'templates'
            $score = 0;
            foreach ($keywords as $kw) {
                if (str_contains($text, strtoupper($kw))) $score++;  // tiap kata kunci match nambah skor
            }
            if ($score > $bestScore) {   // jenis dengan match terbanyak yang menang
                $bestScore = $score;
                $best = $type;
            }
        }

        return $best;
    }
}
