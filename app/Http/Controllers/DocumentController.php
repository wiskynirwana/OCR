<?php
namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;

class DocumentController extends Controller
{

    // READ — tampilkan daftar dokumen, dikelompokkan per jenis + tahun
    // (mengikuti struktur folder output ZIP), plus total semua file.
    // Hanya dokumen yang SUDAH dikonfirmasi — yang belum konfirmasi masih
    // "berjalan" di alur upload (review & konfirmasi di halaman hasil OCR).
    public function index()
    {
        $documents = Document::where('status', 'confirmed')->latest()->get();

        $groups = $documents
            ->groupBy(fn (Document $doc) => $doc->archiveFolder())
            ->sortKeys();

        $total = $documents->count();

        return view('documents.index', compact('groups', 'total'));
    }

    // READ — tampilkan detail 1 dokumen
    public function show(Document $document)
    {
        return view('documents.show', compact('document'));
    }

    // DELETE — hapus dokumen
    public function destroy(Request $request, Document $document)
    {
        $document->delete();

        // Dipanggil via AJAX dari halaman upload → balas JSON.
        if ($request->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->route('documents.index')->with('ok', 'Dokumen dihapus.');
    }

    // DELETE — hapus banyak dokumen sekaligus (select all)
    public function bulkDestroy(Request $request)
    {
        $data = $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'integer',
        ]);

        // Global scope 'owner' memastikan hanya dokumen milik user yang terhapus.
        $deleted = Document::whereIn('id', $data['ids'])->delete();

        return redirect()->route('documents.index')
            ->with('ok', "{$deleted} dokumen dihapus.");
    }
}
