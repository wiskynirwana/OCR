<?php
return [
    // Deteksi jenis dari signature judul di hasil OCR
    'templates' => [
        'keputusan' => ['KEPUTUSAN PENGURUS'],
        'spk'       => ['SURAT PERJANJIAN KERJA'],
    ],

    // Format nama file per jenis
    'naming' => [
        'spk'       => '{KODE} {STATUS} {TANGGAL} - {SEQ} {NAMA}',
        'keputusan' => '{KODE} {STATUS} {TANGGAL} - {SEQ} {NAMA}',
    ],

    // Map bulan teks → angka (buat tanggal Keputusan)
    'bulan' => [
        'januari'=>'01','februari'=>'02','maret'=>'03','april'=>'04',
        'mei'=>'05','juni'=>'06','juli'=>'07','agustus'=>'08',
        'september'=>'09','oktober'=>'10','november'=>'11','desember'=>'12',
    ],

    // Profil preprocessing per jenis
    'preprocess' => [
        'spk'       => ['mode' => 'threshold', 'psm' => 3],
        'keputusan' => ['mode' => 'threshold', 'psm' => 3],
    ],

];
