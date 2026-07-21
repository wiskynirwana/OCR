<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\DocumentProcessor;
use App\Services\FilenameBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class DocumentUploadController extends Controller
{
    public function create()
    {
        // yang keburu 'processing' pas halaman mati gak bakal selesai sendiri, balikin ke pending
        Document::where('status', 'processing')->update(['status' => 'pending']);

        // dokumen yang belum dikonfirmasi ditampilkan lagi biar hasil OCR gak hilang pas refresh
        $unfinished = Document::whereIn('status', ['pending', 'processed', 'error'])
            ->orderBy('id')
            ->get()
            ->map(fn (Document $d) => $this->docPayload($d))
            ->values();

        return view('documents.upload', ['unfinished' => $unfinished]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'docs' => ['required', 'array', 'max:500'],
            // 20480 KB = 20 MB per file
            'docs.*' => ['required', 'file', 'mimes:pdf', 'max:20480'],
            'doc_variant' => ['nullable', 'in:spk_kontrak,spk_tetap'],
        ], [
            'docs.max' => 'Maksimal 500 file per upload.',
            'docs.*.max' => 'Ukuran tiap file maksimal 20 MB.',
        ]);

        $variant = $request->doc_variant;

        // SPK KONTRAK / SPK TETAP sama-sama diproses sebagai "spk",
        // status KONTRAK/PTY-nya nanti dibaca dari hasil OCR
        $knownType = match ($variant) {
            'spk_kontrak',
            'spk_tetap' => 'spk',

            default => null,
        };

        $batchId = \Illuminate\Support\Str::uuid()->toString();

        // OCR tidak dijalankan di sini biar respons cepat — diproses per file
        // via AJAX dari halaman upload (processOne)
        $created = [];

        foreach ($request->file('docs') as $file) {
            // hash buat deteksi file sama diupload 2x; dihitung sebelum store()
            $hash = md5_file($file->getRealPath()) ?: null;

            $created[] = Document::create([
                'batch_id' => $batchId,
                'original_filename' => $file->getClientOriginalName(),
                'stored_path' => $file->store('uploads/originals'),
                'file_hash' => $hash,
                'doc_type' => $knownType,
                'status' => 'pending',
            ]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'batch_id' => $batchId,
                'documents' => collect($created)->map(fn (Document $d) => $this->docPayload($d))->values(),
            ]);
        }

        return redirect()->route('documents.upload');
    }

    // Bentuk JSON standar 1 dokumen untuk halaman upload (progress + hasil).
    private function docPayload(Document $d): array
    {
        return [
            'id' => $d->id,
            'name' => $d->original_filename,
            'new_filename' => $d->new_filename,
            'doc_type' => $d->doc_type,
            'status' => $d->status,
            'error_message' => $d->error_message,
            'duplicate_of' => $this->duplicateNames($d),
            'process_url' => route('documents.process', $d),
            'confirm_url' => route('documents.confirm', $d),
            'delete_url' => route('documents.destroy', $d),
            'review_url' => route('documents.review', $d),
            'file_url' => route('documents.file', $d),
        ];
    }

    // nama file lain yang isinya/namanya sama — buat peringatan duplikat di halaman upload
    private function duplicateNames(Document $d): array
    {
        return Document::where('id', '!=', $d->id)
            ->where(function ($q) use ($d) {
                $q->where('original_filename', $d->original_filename);
                if ($d->file_hash) {
                    $q->orWhere('file_hash', $d->file_hash);
                }
            })
            ->orderBy('id')
            ->limit(5)
            ->pluck('original_filename')
            ->unique()
            ->values()
            ->all();
    }

    public function confirmBulk(Request $request, \App\Services\DocumentConfirmer $confirmer)
    {
        $data = $request->validate([
            'ids'   => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        // global scope 'owner' udah batasi ke dokumen milik user login
        $documents = Document::whereIn('id', $data['ids'])
            ->where('status', 'processed')
            ->get();

        $results = [];
        foreach ($documents as $document) {
            try {
                $ok = $confirmer->confirm($document);
                $results[] = ['id' => $document->id, 'ok' => $ok, 'status' => $document->status];
            } catch (\Throwable $e) {
                $document->update(['status' => 'error', 'error_message' => $e->getMessage()]);
                $results[] = ['id' => $document->id, 'ok' => false, 'status' => 'error'];
            }
        }

        return response()->json(['results' => $results]);
    }

    // OCR 1 dokumen, dipanggil satu-satu via AJAX biar progressnya kelihatan
    public function processOne(Document $document, DocumentProcessor $processor)
    {
        if ($document->status !== 'pending') {
            // udah/sedang diproses (mis. user refresh), jangan dobel
            return response()->json($this->docPayload($document));
        }

        $document->update(['status' => 'processing']);

        $document = $processor->process(
            $document,
            Storage::path($document->stored_path),
            $document->doc_type
        );

        return response()->json($this->docPayload($document));
    }

    public function review(Document $document)
    {
        return view('documents.review', compact('document'));
    }

    public function updateReview(Request $request, Document $document, FilenameBuilder $filenameBuilder)
    {
        $type = $document->doc_type;

        // ambil data OCR lama biar field yang gak tampil di form gak ikut hilang
        $data = $document->extracted ?? [];

        if ($type === 'spk') {
            $validated = $request->validate([
                'seq' => ['nullable', 'string', 'max:50'],
                'kode' => ['nullable', 'string', 'max:50'],
                'status_pegawai' => ['nullable', 'string', 'max:50'],
                'tanggal' => ['nullable', 'string', 'max:20'],
                'nama' => ['nullable', 'string', 'max:255'],
            ]);

            $data['seq'] = $validated['seq'] ?? null;
            // kode dinormalisasi biar folder output gak terpecah ("SPK 1" vs "SPK1")
            $data['kode'] = preg_replace('/[^A-Z0-9]/', '', strtoupper($validated['kode'] ?? ''));
            $data['status'] = strtoupper($validated['status_pegawai'] ?? '');
            $data['tanggal'] = $validated['tanggal'] ?? null;
            $data['nama'] = strtoupper($validated['nama'] ?? '');
        }

        $newFilename = $filenameBuilder->build($type, $data);

        $document->update([
            'extracted' => $data,
            'new_filename' => $newFilename,
        ]);

        return redirect()
            ->route('documents.review', $document)
            ->with('success', 'Koreksi berhasil disimpan dan nama file diperbarui.');
    }

    public function confirm(Request $request, Document $document, \App\Services\DocumentConfirmer $confirmer)
    {
        $error = null;
        if ($document->status !== 'processed') {
            $error = 'Dokumen belum siap dikonfirmasi.';
        } elseif (!$document->new_filename) {
            $error = 'Nama file baru masih kosong.';
        } elseif (!$document->stored_path || !Storage::exists($document->stored_path)) {
            $error = 'File asli tidak ditemukan di storage.';
        }

        if (!$error) {
            $confirmer->confirm($document);
        }

        if ($request->wantsJson()) {
            return $error
                ? response()->json(['message' => $error], 422)
                : response()->json($this->docPayload($document->fresh()));
        }

        return redirect()
            ->route('documents.review', $document)
            ->with($error ? 'error' : 'success',
                   $error ?: 'Dokumen berhasil dikonfirmasi dan dipindahkan ke folder output.');
    }

    public function downloadIndex()
    {
        $groups = Document::where('status', 'confirmed')
            ->whereNotNull('stored_path')
            ->get()
            ->groupBy(fn (Document $doc) => $doc->archiveFolder())
            ->sortKeys();

        return view('documents.download', compact('groups'));
    }

    public function downloadZip(Request $request)
    {
        $request->validate([
            'folders'   => ['nullable', 'array'],
            'folders.*' => ['string'],
        ]);

        $documents = Document::where('status', 'confirmed')
            ->whereNotNull('stored_path')
            ->get();

        // kalau gak ada folder yang dipilih, unduh semua
        $selected = $request->input('folders', []);
        if (!empty($selected)) {
            $documents = $documents->filter(
                fn (Document $doc) => in_array($doc->archiveFolder(), $selected, true)
            );
        }

        if ($documents->isEmpty()) {
            return redirect()->back()->with('error', 'Belum ada file output.');
        }

        \Illuminate\Support\Facades\Storage::makeDirectory('temp/zips');

        // nama ZIP ikut folder yang dipilih; kalau semua pakai timestamp
        if (count($selected) === 1) {
            $zipFileName = $selected[0] . '.zip';
        } elseif (count($selected) > 1) {
            $joined = implode(' + ', $selected);
            if (strlen($joined) > 100) {
                $joined = count($selected) . ' FOLDER - ' . now()->format('Ymd-His');
            }
            $zipFileName = $joined . '.zip';
        } else {
            $zipFileName = 'OUTPUT-' . now()->format('Ymd-His') . '.zip';
        }

        // folders[] datang dari input user, jangan dipakai mentah di path
        $zipFileName = preg_replace('/[\/\\\\:*?"<>|]/', '', $zipFileName);
        $zipFileName = preg_replace('/\.+(?=zip$)/', '.', trim($zipFileName));

        $zipPath = storage_path('app/temp/zips/' . $zipFileName);

        $zip = new \ZipArchive();

        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return redirect()->back()->with('error', 'Gagal membuat file ZIP.');
        }

        $added = 0;

        foreach ($documents as $document) {
            if (!\Illuminate\Support\Facades\Storage::exists($document->stored_path)) {
                continue;
            }

            $fullPath = \Illuminate\Support\Facades\Storage::path($document->stored_path);
            // pertahankan struktur folder outputs/<folder>/<file>
            $relativeName = str_replace('outputs/', '', $document->stored_path);
            $relativeName = str_replace('\\', '/', $relativeName);
            $zip->addFile($fullPath, $relativeName);
            $added++;
        }

        $zip->close();

        if ($added === 0) {
            @unlink($zipPath);
            return redirect()->back()->with('error', 'File output tidak ditemukan di storage.');
        }

        return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);
    }

    public function file(Document $document)
    {
        abort_unless(
            $document->stored_path && Storage::exists($document->stored_path),
            404
        );

        return Storage::response(
            $document->stored_path,
            $document->original_filename ?: 'dokumen.pdf',
            ['Content-Type' => 'application/pdf']
        );
    }
}
