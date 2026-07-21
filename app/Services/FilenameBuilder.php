<?php

namespace App\Services;

class FilenameBuilder
{
    // Isi pola dari config/doctypes.php, mis. "{KODE} {STATUS} {TANGGAL} - {SEQ} {NAMA}"
    public function build(string $type, array $data): string
    {
        $pattern = config("doctypes.naming.$type");

        $name = preg_replace_callback('/\{(\w+)\}/', function ($m) use ($data) {
            return $data[strtolower($m[1])] ?? '';   // kosong kalau field gak kebaca
        }, $pattern);

      $name = preg_replace('/\s+/', ' ', trim($name));
      $name = preg_replace('/[\/\\\\:*?"<>|]/', '', $name);


      return strtoupper($name);
    }
}
