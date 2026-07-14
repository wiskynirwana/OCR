<?php
return [
    // Deteksi jenis dari signature judul (sisanya via ciri lain: KTP/AKTE/KK)
    'templates' => [
        'keputusan' => ['KEPUTUSAN PENGURUS'],
        'spk'       => ['SURAT PERJANJIAN KERJA'],
    ],

    // Format nama file per jenis
    'naming' => [
        'sk'        => '{KODE} {STATUS} {TANGGAL} - {SEQ} {NAMA}',
        'spk'       => '{KODE} {STATUS} {TANGGAL} - {SEQ} {NAMA}',
        'keputusan' => '{KODE} {STATUS} {TANGGAL} - {SEQ} {NAMA}',
        'ktp'       => 'KTP {NAMA}',
        'akte'      => 'AKTE {NAMA}',
        'kk'        => 'KK {NAMA}',
    ],

    // Map bulan teks → angka (buat tanggal Keputusan)
    'bulan' => [
        'januari'=>'01','februari'=>'02','maret'=>'03','april'=>'04',
        'mei'=>'05','juni'=>'06','juli'=>'07','agustus'=>'08',
        'september'=>'09','oktober'=>'10','november'=>'11','desember'=>'12',
    ],

    // Profil preprocessing per jenis (dipakai Fase 2)
    'preprocess' => [
        'sk'        => ['mode' => 'threshold',     'psm' => 3],
        'spk'       => ['mode' => 'threshold',     'psm' => 3],
        'keputusan' => ['mode' => 'threshold',     'psm' => 3],
        'ktp'       => ['mode' => 'threshold',     'psm' => 3],
        'akte'      => ['mode' => 'blue_flatten',  'psm' => 8],
        'kk'        => ['mode' => 'kk_grid',       'psm' => 6],
    ],

];
