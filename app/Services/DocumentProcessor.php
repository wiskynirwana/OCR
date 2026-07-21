<?php

namespace App\Services;

use App\Models\Document;
use App\Models\ProcessingLog;
use Throwable;

class DocumentProcessor
{
    public function __construct(
        private PdfToImageService $pdf,
        private PreprocessService $preprocess,
        private OcrService $ocr,
        private TemplateDetector $detector,
        private DocumentParser $parser,
        private FilenameBuilder $builder,
    ) {}

    // $knownType dari folder upload; kalau null dideteksi otomatis dari teksnya
    public function process(Document $document, string $pdfPath, ?string $knownType = null): Document
    {
        $pages = [];

        try {
            $this->log($document, 'mulai', 'info', "Memproses {$document->original_filename}");

            // halaman pertama yang dipakai buat penamaan
            $pages = $this->pdf->convert($pdfPath);
            $rawImage = $pages[0];
            $this->log($document, 'pdf_to_image', 'info', count($pages) . ' halaman dirender');

            $type = $knownType;
            if (!$type) {
                // OCR sekilas pakai profil generik, terus ditebak dari teksnya
                $quick = $this->preprocess->process($rawImage, 'threshold');
                $type  = $this->detector->detect($this->ocr->read($quick, 3));
                $this->log($document, 'deteksi_jenis', $type ? 'info' : 'warning',
                    $type ? "Terdeteksi: {$type}" : 'Jenis tidak terdeteksi');
            }

            if (!$type) {
                // gak ketebak → user isi manual di review page
                $document->update(['status' => 'error', 'error_message' => 'Jenis dokumen tidak dikenali']);
                return $document;
            }

            $mode       = config("doctypes.preprocess.$type.mode");
            $cleanImage = $this->preprocess->process($rawImage, $mode);
            $ocrText    = $this->ocr->readForType($cleanImage, $type);
            $this->log($document, 'ocr', 'info', 'OCR selesai (' . strlen($ocrText) . ' karakter)');

            $extracted = $this->parser->parse($type, $ocrText);

            // KK: "atas nama" default kepala keluarga, bisa diganti di review
            if ($type === 'kk') {
                $extracted['nama'] = $extracted['kepala_keluarga'] ?? null;
            }
            $newFilename = $this->builder->build($type, $extracted);
            $this->log($document, 'penamaan', 'info', "Usulan nama: {$newFilename}");

            // status 'processed' = masih nunggu konfirmasi user, file belum di-rename
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
            // satu file error jangan sampai matiin proses batch
            $this->log($document, 'error', 'error', $e->getMessage());
            $document->update(['status' => 'error', 'error_message' => $e->getMessage()]);
            return $document;
        } finally {
            // hapus temp hasil konversi biar storage/app/temp gak numpuk gambar 300 DPI
            if (!empty($pages)) {
                \Illuminate\Support\Facades\File::deleteDirectory(dirname($pages[0]));
            }
        }
    }

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
