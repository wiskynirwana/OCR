<?php

namespace App\Services;

class DocumentParser
{
    // Nama bulan Indonesia → angka, buat ngubah "1 Januari 2026" jadi "20260101"
    private array $bulan = [
        'januari' => '01', 'februari' => '02', 'maret' => '03', 'april' => '04',
        'mei' => '05', 'juni' => '06', 'juli' => '07', 'agustus' => '08',
        'september' => '09', 'oktober' => '10', 'november' => '11', 'desember' => '12',
    ];

    // Pintu masuk: arahkan ke parser sesuai jenis dokumen.
    public function parse(string $type, string $text): array
    {
      return match ($type) {
    'sk', 'spk', 'keputusan' => $this->parseSurat($type, $text),

    'ktp' => [
        'nama' => $this->namaUmum($text),
    ],

    'akte' => [
        'nama' => $this->namaAkte($text),
    ],

    'kk' => $this->parseKk($text),

    default => [],
};
    }

    // SK/SPK/KEPUTUSAN butuh: KODE, SEQ, STATUS, TANGGAL, NAMA
    private function parseSurat(string $type, string $text): array
    {
        return [
            'kode'    => $this->kode($text),
            'seq'     => $this->seq($text),
            'status'  => $this->status($text),
            // SPK pakai tanggal dalam kurung "(dd/mm/yyyy)", SK/Keputusan pakai tanggal teks
            'tanggal' => $type === 'spk' ? $this->tanggalSpk($text) : $this->tanggalKeputusan($text),
            // SPK ambil nama dari daftar "2. NAMA, lahir...", sisanya dari baris "Nama :"
            'nama'    => $type === 'spk' ? $this->namaSpk($text) : $this->namaUmum($text),
        ];
    }

    // KODE dari nomor surat. "Nomor: 1/SK.PEG.2/yys" → ambil "SK.PEG.2" → buang titik → "SKPEG2"
    private function kode(string $text): ?string
    {
        if (preg_match('/Nomor\s*:?\s*([0-9OolI]{1,3})\/([^\/]+)\/yys/i', $text, $m)) {
            // Buang titik, spasi, dan karakter non-alfanumerik lain supaya kode
            // selalu konsisten (mis. "SPK 1", "SPK.1" → "SPK1"). Ini penting
            // agar folder output tidak terpecah untuk dokumen sejenis.
            return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $m[2]));
        }
        return null;
    }

    // SEQ = nomor urut depan ("1" atau "011"). OCR kadang salah baca 0→O, 1→l/I → kita betulin, lalu pad jadi 3 digit.
    private function seq(string $text): ?string
    {
        if (preg_match('/Nomor\s*:?\s*([0-9OolI]{1,3})\//i', $text, $m)) {
            $seq = strtr($m[1], ['O' => '0', 'o' => '0', 'l' => '1', 'I' => '1']);
            return str_pad($seq, 3, '0', STR_PAD_LEFT);   // "1" → "001"
        }
        return null;
    }

    // STATUS: KONTRAK kalau ada "PEGAWAI KONTRAK", selain itu PTY kalau "PEGAWAI TETAP"
    private function status(string $text): ?string
    {
        if (stripos($text, 'PEGAWAI KONTRAK') !== false) return 'KONTRAK';
        if (stripos($text, 'PEGAWAI TETAP') !== false)   return 'PTY';
        return null;
    }

    // TANGGAL SPK: "(20/01/2026)" → "20260120"
    private function tanggalSpk(string $text): ?string
    {
        if (preg_match('/\((\d{2})\/(\d{2})\/(\d{4})\)/', $text, $m)) {
            return $m[3] . $m[2] . $m[1];   // YYYY + MM + DD
        }
        return null;
    }

    // TANGGAL KEPUTUSAN: cari semua "tgl Bulan tahun", ambil yang TERAKHIR (tanggal penetapan) → "20260101"
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

    // NAMA umum (KTP/KEPUTUSAN/AKTE): baris "Nama : XXXX"
    private function namaUmum(string $text): ?string
    {
        if (preg_match('/Nama\s*:?\s*(.+)/i', $text, $m)) {
            return trim($m[1]);
        }
        return null;
    }

    // NAMA SPK: dari daftar pihak "2. NAMA ORANG, lahir di ..."
    private function namaSpk(string $text): ?string
    {
        if (preg_match('/^\s*2\.\s*(.+?)\s*,?\s*lahir/mi', $text, $m)) {
            return trim($m[1]);
        }
        return null;
    }

private function parseKk(string $text): array
{
    // tiap baris hasil OCR tabel KK = 1 nama anggota
    $anggota = array_values(array_filter(array_map('trim', explode("\n", $text))));
    return [
        'kepala_keluarga' => $anggota[0] ?? null,  // default; "atas nama" bisa diganti di review page
        'anggota'         => $anggota,             // buat ngisi dropdown pilihan nama
    ];
}
private function namaAkte(string $text): ?string
{
    $nama = preg_replace('/[^A-Za-z]/', ' ', $text);
    $nama = preg_replace('/\s+/', ' ', trim($nama));
    $nama = strtoupper($nama);

    return $nama !== '' ? $nama : null;
}
}
