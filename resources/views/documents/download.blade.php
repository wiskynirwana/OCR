<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl tracking-tight text-ink">Download ZIP</h2></x-slot>

    <div class="py-8 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

        @if (session('error'))
            <div class="alert-danger mb-5" role="alert">
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <div class="mb-5">
        </div>

        @if ($groups->isEmpty())
            <p class="p-5 text-ink-soft card">Belum ada file output yang siap diunduh.</p>
        @else
            <form action="{{ route('outputs.download.zip') }}" method="POST">
                @csrf

                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <label class="inline-flex items-center gap-2 text-sm text-ink-soft">
                        <input type="checkbox" id="select-all" class="rounded border-line text-pine focus:ring-pine/30">
                        Pilih semua ({{ $groups->count() }} folder)
                    </label>

                    <button type="submit" id="download-btn" class="btn-primary" disabled>
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Download ZIP
                    </button>
                </div>

                <div class="card divide-y divide-line overflow-hidden">
                    @foreach ($groups as $folder => $docs)
                        <div x-data="{ open: false }">
                            <div class="flex items-center justify-between gap-3 px-4 py-3 hover:bg-paper/50 transition-colors">
                                <span class="inline-flex items-center gap-2.5">
                                    {{-- checkbox di luar tombol toggle biar klik centang tidak ikut buka/tutup --}}
                                    <input type="checkbox" name="folders[]" value="{{ $folder }}"
                                           class="folder-check rounded border-line text-pine focus:ring-pine/30">
                                    <button type="button" @click="open = !open"
                                            class="inline-flex items-center gap-1.5 font-medium text-ink hover:text-pine transition">
                                        {{ $folder }}
                                        <svg class="w-4 h-4 text-ink-faint transition-transform" :class="open ? 'rotate-180' : ''"
                                             fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </button>
                                </span>
                                <span class="text-xs text-ink-faint">{{ $docs->count() }} file</span>
                            </div>

                            <ul x-show="open" x-cloak class="border-t border-line bg-paper/30 divide-y divide-line/60">
                                @foreach ($docs as $doc)
                                    <li class="flex items-center justify-between gap-3 ps-11 pe-4 py-2">
                                        <span class="min-w-0 flex-1 truncate font-mono text-xs text-ink-soft"
                                              title="{{ $doc->nama_file_baru }}">
                                            {{ $doc->nama_file_baru ?? $doc->nama_file_asli }}
                                        </span>
                                        @if ($doc->lokasi_file)
                                            <button type="button"
                                                class="js-preview flex-shrink-0 text-xs font-medium text-pine hover:underline"
                                                data-url="{{ route('documents.file', $doc) }}"
                                                data-title="{{ $doc->nama_file_baru ?? $doc->nama_file_asli }}">
                                                Preview
                                            </button>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            </form>
        @endif
    </div>

    <x-pdf-preview-panel />

    <script>
        (function () {
            const selectAll = document.getElementById('select-all');
            const checks = Array.from(document.querySelectorAll('.folder-check'));
            const downloadBtn = document.getElementById('download-btn');

            function refresh() {
                if (!selectAll) return;
                const checked = checks.filter(c => c.checked).length;
                selectAll.checked = checked > 0 && checked === checks.length;
                selectAll.indeterminate = checked > 0 && checked < checks.length;
                if (downloadBtn) {
                    downloadBtn.disabled = checked === 0;
                    downloadBtn.classList.toggle('opacity-50', checked === 0);
                    downloadBtn.classList.toggle('cursor-not-allowed', checked === 0);
                }
            }

            if (selectAll) {
                selectAll.addEventListener('change', function () {
                    checks.forEach(c => { c.checked = selectAll.checked; });
                    refresh();
                });
            }
            checks.forEach(c => c.addEventListener('change', refresh));
            refresh();
        })();
    </script>
</x-app-layout>
