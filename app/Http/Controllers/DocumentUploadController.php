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
        // Dokumen yang keburu berstatus 'processing' saat halaman mati
        // (sleep/refresh di tengah OCR) tidak akan pernah selesai sendiri —
        // kembalikan ke 'pending' supaya otomatis diproses ulang.
        Document::where('status', 'processing')->update(['status' => 'pending']);

        // Semua dokumen yang belum dikonfirmasi = "pekerjaan berjalan".
        // Dirender ulang di halaman upload agar hasil OCR tidak hilang
        // walau halaman di-refresh / laptop sleep. Baru hilang dari sini
        // setelah dikonfirmasi (masuk Riwayat) atau dihapus.
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
            // docs[] = bisa upload banyak PDF, maksimal 500 file sekaligus.
            'docs' => ['required', 'array', 'max:500'],
            // max dalam KB: 20480 KB = 20 MB per file.
            'docs.*' => ['required', 'file', 'mimes:pdf', 'max:20480'],

            // Ini pilihan dari UI, bukan tipe internal OCR.
            'doc_variant' => ['nullable', 'in:spk_kontrak,spk_tetap'],
        ], [
            'docs.max' => 'Maksimal 500 file per upload.',
            'docs.*.max' => 'Ukuran tiap file maksimal 20 MB.',
        ]);

        $variant = $request->doc_variant;

        // Mapping pilihan UI ke tipe OCR internal.
        // SPK KONTRAK / SPK TETAP tetap diproses sebagai "spk",
        // karena kode SPK dan status KONTRAK/PTY dibaca dari OCR.
        // Auto Detect (kosong) => null, biarkan pipeline yang mendeteksi.
        $knownType = match ($variant) {
            'spk_kontrak',
            'spk_tetap' => 'spk',

            default => null,
        };

        $batchId = \Illuminate\Support\Str::uuid()->toString();

        // Simpan file + buat record status 'pending' saja — OCR TIDAK dijalankan
        // di request ini supaya respons cepat dan user tidak terkunci menunggu.
        // Proses OCR dijalankan per file via AJAX dari halaman upload
        // (lihat processOne) sambil menampilkan progress ring persen.
        $created = [];

        foreach ($request->file('docs') as $file) {
            // Hash isi file — buat mendeteksi file yang sama diupload 2x.
            // Dihitung sebelum store() supaya path temp-nya masih pasti ada.
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

        // Submit AJAX dari halaman upload → balas JSON berisi daftar dokumen
        // + URL proses per file, biar progress-nya dijalankan di halaman upload.
        if ($request->wantsJson()) {
            return response()->json([
                'batch_id' => $batchId,
                'batch_url' => route('documents.batch', $batchId),
                'documents' => collect($created)->map(fn (Document $d) => $this->docPayload($d))->values(),
            ]);
        }

        return redirect()->route('documents.batch', $batchId);
    }

    // Status batch dalam JSON — dipakai halaman upload untuk melanjutkan
    // proses OCR yang tertinggal (mis. user sempat menutup halaman).
    public function batchStatus(string $batchId)
    {
        $documents = Document::where('batch_id', $batchId)->orderBy('id')->get();

        if ($documents->isEmpty()) {
            return response()->json(['message' => 'Batch tidak ditemukan.'], 404);
        }

        return response()->json([
            'batch_id' => $batchId,
            'batch_url' => route('documents.batch', $batchId),
            'documents' => $documents->map(fn (Document $d) => $this->docPayload($d))->values(),
        ]);
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

    // Nama file lain (milik user yang sama, dibatasi global scope 'owner')
    // yang isinya identik / namanya sama dengan dokumen ini — dipakai untuk
    // menampilkan peringatan duplikat di halaman upload.
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

    // Konfirmasi banyak dokumen sekaligus dari halaman upload (select all /
    // terpilih). Balas JSON berisi hasil per dokumen.
    public function confirmBulk(Request $request, \App\Services\DocumentConfirmer $confirmer)
    {
        $data = $request->validate([
            'ids'   => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        // Global scope 'owner' membatasi ke dokumen milik user login.
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

    // Proses OCR 1 dokumen (dipanggil via AJAX dari halaman batch, satu per satu,
    // supaya progress-nya kelihatan). Return JSON status terbaru dokumen.
    public function processOne(Document $document, DocumentProcessor $processor)
    {
        if ($document->status !== 'pending') {
            // Sudah/sedang diproses (mis. user refresh) — jangan proses dua kali.
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

    // Ambil data OCR lama.
    // Ini penting supaya field lain yang gak tampil tidak hilang.
    $data = $document->extracted ?? [];

    if ($type === 'spk') {
        $validated = $request->validate([
            'seq' => ['nullable', 'string', 'max:50'],
            'kode' => ['nullable', 'string', 'max:50'],
            'status_pegawai' => ['nullable', 'string', 'max:50'],
            'tanggal' => ['nullable', 'string', 'max:20'],
            'nama' => ['nullable', 'string', 'max:255'],
        ]);

        // Label UI:
        // Nomor Surat -> seq
        // Jenis -> kode
        // Status Pegawai -> status
        $data['seq'] = $validated['seq'] ?? null;
        // Normalisasi kode (buang spasi/karakter non-alfanumerik) supaya folder
        // output tidak terpecah untuk dokumen dengan jenis & tahun yang sama.
        $data['kode'] = preg_replace('/[^A-Z0-9]/', '', strtoupper($validated['kode'] ?? ''));
        $data['status'] = strtoupper($validated['status_pegawai'] ?? '');
        $data['tanggal'] = $validated['tanggal'] ?? null;
        $data['nama'] = strtoupper($validated['nama'] ?? '');
    }

    // Generate ulang nama file dari data hasil koreksi.
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

    // Dipanggil via AJAX dari halaman upload → balas JSON.
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

public function batchResult(string $batchId)
{
    $documents = \App\Models\Document::where('batch_id', $batchId)
        ->orderBy('id')
        ->get();

    if ($documents->isEmpty()) {
        return redirect()->route('documents.upload')
            ->with('error', 'Batch tidak ditemukan.');
    }

    // Peringatan duplikat per dokumen: [id => [nama file kembarannya, ...]]
    $duplicates = $documents->mapWithKeys(fn (Document $d) => [
        $d->id => $this->duplicateNames($d),
    ])->filter(fn ($names) => count($names) > 0);

    return view('documents.batch-result', compact('documents', 'batchId', 'duplicates'));
}

public function confirmAll(string $batchId, \App\Services\DocumentConfirmer $confirmer)
{
    $documents = \App\Models\Document::where('batch_id', $batchId)
        ->where('status', 'processed')
        ->get();

    if ($documents->isEmpty()) {
        return redirect()->route('documents.batch', $batchId)
            ->with('error', 'Tidak ada dokumen yang siap dikonfirmasi.');
    }

    $berhasil = 0;
    $gagal = 0;

    foreach ($documents as $document) {
        try {
            $confirmer->confirm($document) ? $berhasil++ : $gagal++;
        } catch (\Throwable $e) {
            $document->update([
                'status' => 'error',
                'error_message' => $e->getMessage(),
            ]);
            $gagal++;
        }
    }

    $pesan = "Konfirmasi selesai: {$berhasil} berhasil";
    if ($gagal > 0) $pesan .= ", {$gagal} gagal";

    return redirect()->route('documents.batch', $batchId)
        ->with('success', $pesan);
}

// Halaman pemilihan batch (folder arsip) sebelum download ZIP.
// User bisa memilih folder mana saja yang ingin diunduh.
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

    // Ambil hanya dokumen confirmed milik user yang sedang login
    // (global scope pada model Document sudah membatasi ke Auth::id()).
    $documents = Document::where('status', 'confirmed')
        ->whereNotNull('stored_path')
        ->get();

    // Jika user memilih batch tertentu, filter hanya folder terpilih.
    // Bila tidak ada yang dipilih, unduh semua folder.
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

    // Nama ZIP mengikuti folder yang dipilih:
    // - 1 folder  → "SCAN SPK1 KONTRAK 2026.zip"
    // - >1 folder → gabungan nama, dipotong bila kepanjangan
    // - semua     → "OUTPUT-<timestamp>.zip"
    if (count($selected) === 1) {
        $zipFileName = $selected[0] . '.zip';
    } elseif (count($selected) > 1) {
        $joined = implode(' + ', $selected);
        if (strlen($joined) > 100) {
            $joined = count($selected) . ' BATCH - ' . now()->format('Ymd-His');
        }
        $zipFileName = $joined . '.zip';
    } else {
        $zipFileName = 'OUTPUT-' . now()->format('Ymd-His') . '.zip';
    }

    // Amankan nama file: buang separator path & karakter ilegal
    // (folders[] datang dari input user, jangan dipakai mentah di path).
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
        // Pertahankan struktur folder outputs/<folder>/<file>
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

// Stream file PDF (asli sebelum konfirmasi / hasil rename sesudah konfirmasi)
// secara inline untuk ditampilkan di preview pane. Global scope 'owner'
// memastikan user hanya bisa membuka dokumen miliknya sendiri.
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
