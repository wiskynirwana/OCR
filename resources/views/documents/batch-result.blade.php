<x-app-layout>
    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="mb-6">
                <h1 class="text-2xl font-semibold tracking-tight text-ink">Hasil Upload</h1>
                <p class="mt-1 text-sm text-ink-soft">
                    {{ $documents->count() }} dokumen diproses
                </p>
            </div>

            @if (session('success'))
                <div class="alert-success" role="alert">
                    <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if (session('error'))
                <div class="alert-danger" role="alert">
                    <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            @php $pendingCount = $documents->where('status', 'pending')->count(); @endphp

            {{-- Peringatan bila ada file duplikat (isi/nama sama dengan dokumen lain) --}}
            @if (isset($duplicates) && $duplicates->isNotEmpty())
                <div class="alert-danger mb-5" role="alert">
                    <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <span>
                        <strong>Perhatian:</strong> {{ $duplicates->count() }} dokumen terdeteksi duplikat
                        (file yang sama sudah pernah diupload). Periksa baris yang bertanda duplikat di bawah.
                    </span>
                </div>
            @endif

            {{-- Proses OCR berjalan dari halaman Upload; di sini cukup info + refresh manual --}}
            @if ($pendingCount > 0)
                <div class="alert-info mb-5" role="alert">
                    <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <span>
                        {{ $pendingCount }} file belum diproses OCR. Buka
                        <a href="{{ route('documents.upload') }}" class="font-medium underline underline-offset-2">halaman Upload</a>
                        untuk melanjutkan prosesnya.
                    </span>
                </div>
            @endif

            <div class="card p-6">

                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mb-5">
                    <div class="text-sm text-ink-soft">
                        Processed: <span class="text-ink font-medium">{{ $documents->where('status', 'processed')->count() }}</span>
                        <span class="mx-1.5 text-ink-faint">&middot;</span>
                        Confirmed: <span class="text-ink font-medium">{{ $documents->where('status', 'confirmed')->count() }}</span>
                        <span class="mx-1.5 text-ink-faint">&middot;</span>
                        Error: <span class="text-ink font-medium">{{ $documents->where('status', 'error')->count() }}</span>
                    </div>

                    <div class="flex items-center gap-4">
                        @if ($documents->where('status', 'processed')->count() > 0)
                            <form action="{{ route('documents.batch.confirm-all', $batchId) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn-primary"
                                    onclick="return confirm('Konfirmasi semua dokumen yang sudah processed?')">
                                    Konfirmasi Semua ({{ $documents->where('status', 'processed')->count() }})
                                </button>
                            </form>
                        @endif

                        <a href="{{ route('documents.upload') }}"
                            class="text-sm text-ink-soft underline underline-offset-2 hover:text-ink">
                            Upload Lagi
                        </a>
                    </div>
                </div>

                <div class="overflow-x-auto border border-line rounded-xl">
                    <table class="w-full text-sm text-left">
                        <thead>
                            <tr class="bg-paper/40 text-xs uppercase tracking-wider text-ink-faint border-b border-line">
                                <th class="px-3 py-3 font-medium">No</th>
                                <th class="px-3 py-3 font-medium">File Asli</th>
                                <th class="px-3 py-3 font-medium">Jenis</th>
                                <th class="px-3 py-3 font-medium">Nama File Baru</th>
                                <th class="px-3 py-3 font-medium">Status</th>
                                <th class="px-3 py-3 font-medium">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($documents as $i => $doc)
                                <tr class="bg-surface hover:bg-paper/50 transition-colors">
                                    <td class="px-3 py-2.5 text-ink-faint">{{ $i + 1 }}</td>

                                    <td class="px-3 py-2.5 text-ink max-w-xs" title="{{ $doc->original_filename }}">
                                        <div class="truncate">{{ $doc->original_filename }}</div>
                                        @if (isset($duplicates) && $duplicates->has($doc->id))
                                            <span class="badge-danger mt-1" title="Isi file sama dengan: {{ implode(', ', $duplicates[$doc->id]) }}">
                                                &#9888; Duplikat
                                            </span>
                                        @endif
                                    </td>

                                    <td class="px-3 py-2.5 text-ink-soft">
                                        {{ strtoupper($doc->doc_type ?? '-') }}
                                    </td>

                                    <td class="px-3 py-2.5 font-mono text-xs text-ink-soft break-all min-w-[16rem]">
                                        {{ $doc->new_filename ?? '-' }}
                                    </td>

                                    <td class="px-3 py-2.5">
                                        @if ($doc->status === 'pending')
                                            <span class="badge-muted">Menunggu</span>
                                        @elseif ($doc->status === 'processing')
                                            <span class="badge-info">Diproses&hellip;</span>
                                        @elseif ($doc->status === 'processed')
                                            <span class="badge-info">Processed</span>
                                        @elseif ($doc->status === 'confirmed')
                                            <span class="badge-success">Confirmed &check;</span>
                                        @elseif ($doc->status === 'error')
                                            <span class="badge-danger" title="{{ $doc->error_message }}">Error &#10007;</span>
                                        @else
                                            <span class="badge-muted">{{ $doc->status }}</span>
                                        @endif
                                    </td>

                                    <td class="px-3 py-2.5">
                                        <div class="flex items-center gap-3">
                                            @if ($doc->stored_path)
                                                <button type="button"
                                                    class="js-preview text-pine hover:underline underline-offset-2 text-sm font-medium"
                                                    data-url="{{ route('documents.file', $doc) }}"
                                                    data-title="{{ $doc->original_filename }}">
                                                    Lihat
                                                </button>
                                            @endif

                                            @if ($doc->status === 'error')
                                                <span class="text-danger text-xs block max-w-xs truncate" title="{{ $doc->error_message }}">
                                                    {{ Str::limit($doc->error_message, 40) }}
                                                </span>
                                            @elseif ($doc->status === 'processed')
                                                <a href="{{ route('documents.review', $doc) }}"
                                                    class="text-pine hover:underline underline-offset-2 text-sm font-medium">
                                                    Review
                                                </a>
                                            @elseif ($doc->status === 'confirmed')
                                                <span class="text-ink-faint text-xs">Selesai</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>

    <x-pdf-preview-panel />
</x-app-layout>
