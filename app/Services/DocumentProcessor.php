<?php

namespace App\Services;

use App\Models\Document;
use App\Models\ProcessingLog;
use Throwable;

class DocumentProcessor
{
    // Semua service di-inject otomatis sama Laravel (gak usah `new` manual)
    public function __construct(
        private PdfToImageService $pdf,
        private PreprocessService $preprocess,
        private OcrService $ocr,
        private TemplateDetector $detector,
        private DocumentParser $parser,
        private FilenameBuilder $builder,
    ) {}

    // Proses 1 dokumen: PDF → nama file usulan + data terstruktur.
    // $document  : record yang udah dibuat pas upload (minimal punya original_filename)
    // $pdfPath   : path file PDF yang diproses
    // $knownType : jenis dari folder upload; kalau null → dideteksi otomatis
    public function process(Document $document, string $pdfPath, ?string $knownType = null): Document
    {
        $pages = [];

        try {
            $this->log($document, 'mulai', 'info', "Memproses {$document->original_filename}");

            // 1. PDF → gambar (halaman pertama dipakai buat penamaan)
            $pages = $this->pdf->convert($pdfPath);
            $rawImage = $pages[0];
            $this->log($document, 'pdf_to_image', 'info', count($pages) . ' halaman dirender');

            // 2. Tentukan jenis dokumen
            $type = $knownType;
            if (!$type) {
                // belum tau jenis → OCR sekilas pakai profil generik, lalu tebak dari teksnya
                $quick = $this->preprocess->process($rawImage, 'threshold');
                $type  = $this->detector->detect($this->ocr->read($quick, 3));
                $this->log($document, 'deteksi_jenis', $type ? 'info' : 'warning',
                    $type ? "Terdeteksi: {$type}" : 'Jenis tidak terdeteksi');
            }

            if (!$type) {
                // gak ketebak → tandai error biar diisi manual di review page
                $document->update(['status' => 'error', 'error_message' => 'Jenis dokumen tidak dikenali']);
                return $document;
            }

            // 3. Preprocess + OCR sesuai jenis (tiap jenis profil & psm-nya beda)

            $mode       = config("doctypes.preprocess.$type.mode");   // mis. 'threshold' / 'kk_grid'
            $cleanImage = $this->preprocess->process($rawImage, $mode);
            $ocrText    = $this->ocr->readForType($cleanImage, $type);
            $this->log($document, 'ocr', 'info', 'OCR selesai (' . strlen($ocrText) . ' karakter)');

            // 4. Ekstrak field dari teks (regex Fase 3)
            $extracted = $this->parser->parse($type, $ocrText);

            // 5. Susun nama file usulan
            //    KK: nama file = "atas nama" → default kepala keluarga (bisa diganti di review)
            if ($type === 'kk') {
                $extracted['nama'] = $extracted['kepala_keluarga'] ?? null;
            }
            $newFilename = $this->builder->build($type, $extracted);
            $this->log($document, 'penamaan', 'info', "Usulan nama: {$newFilename}");

            // 6. Simpan hasil. status 'processed' = NUNGGU konfirmasi user di review page
            //    (file fisik belum di-rename di sini — itu nanti setelah user konfirmasi, di Fase 4)
            $document->update([
                'doc_type'     => $type,
                'extracted'    => $extracted,
                'ocr_text'     => $ocrText,
                'new_filename' => $newFilename,
                'status'       => 'processed',
            ]);

            $this->log($document, 'selesai', 'info', 'Dokumen siap direview');
            return $document->fresh();

        } catch (Throwable $e) {
            // apa pun yang error → catat, JANGAN bikin proses batch berhenti total
            $this->log($document, 'error', 'error', $e->getMessage());
            $document->update(['status' => 'error', 'error_message' => $e->getMessage()]);
            return $document;
        } finally {
            // Bersihkan folder temp hasil konversi PDF→PNG (sukses maupun gagal),
            // supaya storage/app/temp tidak menumpuk gambar 300 DPI.
            if (!empty($pages)) {
                \Illuminate\Support\Facades\File::deleteDirectory(dirname($pages[0]));
            }
        }
    }

    // helper: nyatet 1 langkah ke tabel processing_logs (jejak audit + debug)
    private function log(Document $document, string $step, string $level, string $message): void
    {
            ProcessingLog::create([
            'document_id' => $document->id,
            'step'        => $step,
            'level'       => $level,
            'message'     => $message,
        ]);
    }
}
