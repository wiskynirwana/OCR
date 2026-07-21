<x-app-layout>
    <div class="min-h-screen py-8 px-4 bg-paper">
        <div class="max-w-5xl mx-auto">

            <div class="mb-8">
                <h1 class="text-2xl font-semibold tracking-tight text-ink">
                    Selamat datang, {{ auth()->user()->name }}
                </h1>
                <p class="text-sm mt-1 text-ink-soft">
                    Sistem Rename Otomatis Arsip Digital
                </p>
            </div>

            {{-- Kartu Statistik --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">

                <div class="card p-5">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3 bg-pine-soft text-pine">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
                        </svg>
                    </div>
                    <p class="text-2xl font-semibold text-ink">{{ $stats['total'] }}</p>
                    <p class="text-sm mt-0.5 text-ink-soft">Total Dokumen</p>
                </div>

                <div class="card p-5">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3 bg-clay-soft text-clay-dark">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <p class="text-2xl font-semibold text-clay-dark">{{ $stats['processed'] }}</p>
                    <p class="text-sm mt-0.5 text-ink-soft">Menunggu Review</p>
                </div>

                <div class="card p-5">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3 bg-pine-soft text-pine">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <p class="text-2xl font-semibold text-pine">{{ $stats['confirmed'] }}</p>
                    <p class="text-sm mt-0.5 text-ink-soft">Selesai</p>
                </div>

                <div class="card p-5">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3 bg-danger-soft text-danger">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <p class="text-2xl font-semibold text-danger">{{ $stats['error'] }}</p>
                    <p class="text-sm mt-0.5 text-ink-soft">Gagal</p>
                </div>

            </div>

            <div class="grid md:grid-cols-3 gap-6 mb-8">

                <div class="md:col-span-2 card p-6">
                    <h2 class="font-semibold text-base mb-3 text-ink">Tentang Sistem Ini</h2>
                    <p class="text-sm leading-relaxed mb-3 text-ink-soft">
                        Sistem ini membantu melakukan penamaan ulang file dokumen surat kerja
                        secara otomatis menggunakan teknologi OCR (Optical Character Recognition).
                    </p>
                    <p class="text-sm leading-relaxed mb-3 text-ink-soft">
                        Upload file PDF dokumen SPK, sistem akan membaca isi dokumen,
                        mengekstrak data penting seperti nomor surat, nama pegawai, dan tanggal,
                        lalu menyusun nama file sesuai standar arsip yayasan secara otomatis.
                    </p>
                    <p class="text-sm leading-relaxed text-ink-soft">
                        Seluruh proses berjalan otomatis sehingga penamaan file menjadi konsisten,
                        rapi, dan sesuai standar arsip tanpa perlu mengetik ulang nama file secara manual.
                    </p>
                </div>

                <div class="card p-6">
                    <h2 class="font-semibold text-base mb-4 text-ink">Upload</h2>

                    <a href="{{ route('documents.upload') }}" class="btn-primary w-full">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                        </svg>
                        Upload Dokumen
                    </a>
                </div>

            </div>

            @if ($recentDocs->isNotEmpty())
            <div class="card overflow-hidden">
                <div class="px-6 py-4 border-b border-line">
                    <h2 class="font-semibold text-base text-ink">Aktivitas Terbaru</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-paper/40 border-b border-line">
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-ink-faint">File Asli</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-ink-faint">Nama File Baru</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-ink-faint">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-ink-faint">Waktu</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-ink-faint">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($recentDocs as $i => $doc)
                            <tr class="hover:bg-paper/50 transition-colors">
                                <td class="px-4 py-3 text-ink">
                                    {{ \Illuminate\Support\Str::limit($doc->original_filename, 35) }}
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-ink-soft">
                                    {{ $doc->new_filename ?? '-' }}
                                </td>
                                <td class="px-4 py-3">
                                    @if ($doc->status === 'confirmed')
                                        <span class="badge-success">Selesai</span>
                                    @else
                                        <span class="badge-info">Menunggu</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs text-ink-faint">
                                    {{ $doc->updated_at->diffForHumans() }}
                                </td>
                                <td class="px-4 py-3 text-xs">
                                    @if ($doc->stored_path)
                                        <button type="button"
                                            class="js-preview font-medium text-pine hover:underline"
                                            data-url="{{ route('documents.file', $doc) }}"
                                            data-title="{{ $doc->original_filename }}">Lihat</button>
                                    @else
                                        <span class="text-ink-faint">-</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

        </div>
    </div>

    <x-pdf-preview-panel />
</x-app-layout>
