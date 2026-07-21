<x-app-layout>
    <div class="py-10">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="mb-6">
                <h1 class="text-2xl font-semibold tracking-tight text-ink">Review Dokumen</h1>
                <p class="mt-1 text-sm text-ink-soft">Periksa dan koreksi hasil OCR sebelum dikonfirmasi.</p>
            </div>

            @if (session('success'))
                <div class="alert-success" role="alert">
                    <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert-danger" role="alert">
                    <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <ul class="list-disc ms-4 space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @php
                $data = $document->hasil_ekstraksi ?? [];
                $type = $document->jenis_dokumen;
            @endphp

            <div class="md:grid md:grid-cols-3 md:gap-6">

                {{-- Kolom kiri: info dokumen --}}
                <div class="md:col-span-1 mb-6 md:mb-0">
                    <div class="card p-5 space-y-4">

                        <div>
                            <div class="text-xs uppercase tracking-wider text-ink-faint">File Asli</div>
                            <div class="mt-0.5 text-ink break-words">{{ $document->nama_file_asli }}</div>
                        </div>

                        <div>
                            <div class="text-xs uppercase tracking-wider text-ink-faint">Kategori Dokumen</div>
                            <div class="mt-0.5 text-ink">{{ strtoupper($document->jenis_dokumen ?? '-') }}</div>
                        </div>

                        <div>
                            <div class="text-xs uppercase tracking-wider text-ink-faint">Status Proses</div>
                            <div class="mt-1">
                                @if ($document->status === 'processed')
                                    <span class="badge-info">Processed</span>
                                @elseif ($document->status === 'confirmed')
                                    <span class="badge-success">Confirmed &check;</span>
                                @elseif ($document->status === 'error')
                                    <span class="badge-danger">Error &#10007;</span>
                                @else
                                    <span class="badge-muted">{{ $document->status ?? '-' }}</span>
                                @endif
                            </div>
                        </div>

                        @if ($document->status === 'error')
                            <div class="border-l-4 border-danger bg-danger-soft text-danger-dark p-3 rounded-r-lg text-sm">
                                <strong>Error:</strong><br>
                                {{ $document->pesan_error }}
                            </div>
                        @endif

                        <div>
                            <div class="text-xs uppercase tracking-wider text-ink-faint">Nama File Baru</div>
                            <div class="mt-1 p-3 bg-paper/60 border border-line rounded-lg font-mono text-sm text-ink break-all">
                                {{ $document->nama_file_baru ?? '-' }}
                            </div>
                        </div>

                        @if ($document->lokasi_file)
                            <div>
                                <button type="button"
                                    class="js-preview btn-ghost w-full"
                                    data-url="{{ route('documents.file', $document) }}"
                                    data-title="{{ $document->nama_file_asli }}">
                                    Lihat Dokumen
                                </button>
                            </div>
                        @endif

                        <div class="pt-1">
                            @if ($document->status === 'processed')
                                <form action="{{ route('documents.confirm', $document) }}" method="POST"
                                    onsubmit="const b=this.querySelector('button'); b.disabled=true; b.textContent='Memproses…';">
                                    @csrf
                                    <button type="submit" class="btn-primary w-full">
                                        Konfirmasi Rename
                                    </button>
                                </form>
                            @elseif ($document->status === 'confirmed')
                                <span class="text-pine text-sm font-medium">Sudah Dikonfirmasi &check;</span>
                            @endif
                        </div>

                    </div>
                </div>

                {{-- Kolom kanan: form edit --}}
                <div class="md:col-span-2">
                    <div class="card p-6">
                        <form action="{{ route('documents.review.update', $document) }}" method="POST">
                            @csrf
                            @method('PATCH')

                            @if ($type === 'spk')
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                                    <div>
                                        <label class="field-label">Nomor Surat</label>
                                        <input type="text" name="seq" placeholder="001"
                                            value="{{ old('seq', $data['seq'] ?? '') }}" class="field">
                                    </div>

                                    <div>
                                        <label class="field-label">Jenis</label>
                                        <input type="text" name="kode"
                                            value="{{ old('kode', $data['kode'] ?? '') }}" class="field">
                                    </div>

                                    <div>
                                        <label class="field-label">Status Pegawai</label>
                                        @php $statusValue = old('status_pegawai', $data['status'] ?? ''); @endphp
                                        <select name="status_pegawai" class="field">
                                            <option value="">- Pilih Status -</option>
                                            <option value="KONTRAK" {{ $statusValue === 'KONTRAK' ? 'selected' : '' }}>KONTRAK</option>
                                            <option value="PTY" {{ $statusValue === 'PTY' ? 'selected' : '' }}>PTY</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="field-label">Tanggal</label>
                                        <input type="text" name="tanggal" placeholder="YYYYMMDD"
                                            value="{{ old('tanggal', $data['tanggal'] ?? '') }}" class="field">
                                        <p class="field-hint">Contoh: 20260101.</p>
                                    </div>

                                    <div class="md:col-span-2">
                                        <label class="field-label">Nama</label>
                                        <input type="text" name="nama" placeholder="Nama lengkap pegawai"
                                            value="{{ old('nama', $data['nama'] ?? '') }}" class="field">
                                    </div>

                                </div>
                            @else
                                <div class="alert-info" role="alert">
                                    <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                    <span>Jenis dokumen belum dikenali. Coba koreksi dari upload ulang atau pilih jenis dokumen secara manual.</span>
                                </div>
                            @endif

                            <div class="mt-6 flex items-center gap-3">
                                <button type="submit" class="btn-primary">Simpan Koreksi</button>

                                <a href="{{ route('documents.upload') }}" class="btn-ghost">
                                    Kembali ke Upload
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <x-pdf-preview-panel />
</x-app-layout>
