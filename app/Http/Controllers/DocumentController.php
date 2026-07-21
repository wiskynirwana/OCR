<?php
namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{

    // cuma yang sudah confirmed — yang belum masih "berjalan" di alur upload
    public function index()
    {
        $documents = Document::where('status', 'confirmed')->latest()->get();

        $groups = $documents
            ->groupBy(fn (Document $doc) => $doc->archiveFolder())
            ->sortKeys();

        $total = $documents->count();

        return view('documents.index', compact('groups', 'total'));
    }

    public function destroy(Request $request, Document $document)
    {
        if ($document->stored_path && Storage::exists($document->stored_path)) {
            Storage::delete($document->stored_path);
        }

        $document->delete();

        if ($request->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->route('documents.index')->with('ok', 'Dokumen dihapus.');
    }

    public function bulkDestroy(Request $request)
    {
        $data = $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'integer',
        ]);

        // ambil record-nya dulu biar file fisiknya ikut kehapus
        $documents = Document::whereIn('id', $data['ids'])->get();

        foreach ($documents as $document) {
            if ($document->stored_path && Storage::exists($document->stored_path)) {
                Storage::delete($document->stored_path);
            }
        }

        $deleted = Document::whereIn('id', $data['ids'])->delete();

        return redirect()->route('documents.index')
            ->with('ok', "{$deleted} dokumen dihapus.");
    }
}
