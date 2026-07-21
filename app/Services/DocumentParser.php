<?php

namespace App\Services;

class DocumentParser
{
    // buat konversi "1 Januari 2026" → "20260101"
    private array $bulan = [
        'januari' => '01', 'februari' => '02', 'maret' => '03', 'april' => '04',
        'mei' => '05', 'juni' => '06', 'juli' => '07', 'agustus' => '08',
        'september' => '09', 'oktober' => '10', 'november' => '11', 'desember' => '12',
    ];

    public function parse(string $type, string $text): array
    {
        return match ($type) {
            'spk', 'keputusan' => $this->parseSurat($type, $text),
            default => [],
        };
    }

    private function parseSurat(string $type, string $text): array
    {
        return [
            'kode'    => $this->kode($text),
            'seq'     => $this->seq($text),
            'status'  => $this->status($text),
            // SPK tanggalnya format "(dd/mm/yyyy)", Keputusan pakai tanggal teks
            'tanggal' => $type === 'spk' ? $this->tanggalSpk($text) : $this->tanggalKeputusan($text),
            // nama di SPK adanya di daftar pihak "2. NAMA, lahir...", bukan baris "Nama :"
            'nama'    => $type === 'spk' ? $this->namaSpk($text) : $this->namaUmum($text),
        ];
    }

    // "Nomor: 1/SK.PEG.2/yys" → ambil bagian tengah → "SKPEG2"
    private function kode(string $text): ?string
    {
        if (preg_match('/Nomor\s*:?\s*([0-9OolI]{1,3})\/([^\/]+)\/yys/i', $text, $m)) {
            // buang titik/spasi biar "SPK 1" dan "SPK.1" jadi folder yang sama
            return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $m[2]));
        }
        return null;
    }

    // OCR suka salah baca 0→O, 1→l/I di nomor urut, jadi dibetulin dulu
    private function seq(string $text): ?string
    {
        if (preg_match('/Nomor\s*:?\s*([0-9OolI]{1,3})\//i', $text, $m)) {
            $seq = strtr($m[1], ['O' => '0', 'o' => '0', 'l' => '1', 'I' => '1']);
            return str_pad($seq, 3, '0', STR_PAD_LEFT);
        }
        return null;
    }

    private function status(string $text): ?string
    {
        if (stripos($text, 'PEGAWAI KONTRAK') !== false) return 'KONTRAK';
        if (stripos($text, 'PEGAWAI TETAP') !== false)   return 'PTY';
        return null;
    }

    private function tanggalSpk(string $text): ?string
    {
        if (preg_match('/\((\d{2})\/(\d{2})\/(\d{4})\)/', $text, $m)) {
            return $m[3] . $m[2] . $m[1];
        }
        return null;
    }

    // ambil tanggal TERAKHIR di dokumen (= tanggal penetapan)
    private function tanggalKeputusan(string $text): ?string
    {
        $pola = '/(\d{1,2})\s+(Januari|Februari|Maret|April|Mei|Juni|Juli|Agustus|September|Oktober|November|Desember)\s+(\d{4})/i';
        if (preg_match_all($pola, $text, $all, PREG_SET_ORDER)) {
            $m = end($all);
            $hari  = str_pad($m[1], 2, '0', STR_PAD_LEFT);
            $bulan = $this->bulan[strtolower($m[2])];
            return $m[3] . $bulan . $hari;
        }
        return null;
    }

    private function namaUmum(string $text): ?string
    {
        if (preg_match('/Nama\s*:?\s*(.+)/i', $text, $m)) {
            return trim($m[1]);
        }
        return null;
    }

    private function namaSpk(string $text): ?string
    {
        if (preg_match('/^\s*2\.\s*(.+?)\s*,?\s*lahir/mi', $text, $m)) {
            return trim($m[1]);
        }
        return null;
    }
}
