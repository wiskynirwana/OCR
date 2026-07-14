<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl tracking-tight text-ink">Riwayat Dokumen</h2></x-slot>

    <div class="py-8 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <a href="{{ route('documents.upload') }}" class="btn-primary">Upload Dokumen</a>
                <span class="text-sm text-ink-soft">
                    Total semua file: <strong class="text-ink">{{ $total }}</strong>
                </span>
            </div>

            <button type="submit" form="bulk-delete-form" id="bulk-delete-btn"
                    class="btn-danger"
                    disabled
                    onclick="return confirm('Hapus dokumen yang dipilih?')">
                Hapus Terpilih (<span id="selected-count">0</span>)
            </button>
        </div>

        @if (session('ok'))
            <div class="alert-success" role="alert">
                <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span>{{ session('ok') }}</span>
            </div>
        @endif

        @if ($total > 0)
            <label class="mb-3 inline-flex items-center gap-2 text-sm text-ink-soft">
                <input type="checkbox" id="select-all" class="rounded border-line text-pine focus:ring-pine/30">
                Pilih semua ({{ $total }} file)
            </label>
        @endif

        <form id="bulk-delete-form" action="{{ route('documents.bulk-destroy') }}" method="POST">
            @csrf @method('DELETE')

            @forelse ($groups as $folder => $docs)
                <div class="batch-group mb-6 card overflow-hidden">
                    <div class="flex items-center justify-between gap-3 bg-paper/60 px-4 py-3 border-b border-line">
                        <label class="inline-flex items-center gap-2.5 cursor-pointer">
                            <input type="checkbox" class="batch-check rounded border-line text-pine focus:ring-pine/30">
                            <span class="font-medium text-ink">{{ $folder }}</span>
                        </label>
                        <div class="flex items-center gap-3">
                            <span class="text-xs text-ink-faint">{{ $docs->count() }} file</span>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead>
                                <tr class="bg-paper/40 text-xs uppercase tracking-wider text-ink-faint border-b border-line">
                                    <th class="p-3 w-8"></th>
                                    <th class="p-3 font-medium">Nama Asli</th>
                                    <th class="p-3 font-medium">Nama Baru</th>
                                    <th class="p-3 font-medium">Status</th>
                                    <th class="p-3 font-medium">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line">
                                @foreach ($docs as $doc)
                                    <tr class="bg-surface hover:bg-paper/50 transition-colors">
                                        <td class="p-3">
                                            <input type="checkbox" name="ids[]" value="{{ $doc->id }}" class="row-check rounded border-line text-pine focus:ring-pine/30">
                                        </td>
                                        <td class="p-3 text-ink"> {{$doc->original_filename}} </td>
                                        <td class="p-3 font-mono text-xs text-ink-soft"> {{$doc->new_filename ?? '-'}} </td>
                                        <td class="p-3">
                                            @if ($doc->status === 'confirmed')
                                                <span class="badge-success">{{ $doc->status }}</span>
                                            @elseif ($doc->status === 'processed')
                                                <span class="badge-info">{{ $doc->status }}</span>
                                            @elseif ($doc->status === 'error')
                                                <span class="badge-danger">{{ $doc->status }}</span>
                                            @else
                                                <span class="badge-muted">{{ $doc->status }}</span>
                                            @endif
                                        </td>
                                        <td class="p-3 space-x-3 whitespace-nowrap">
                                            @if ($doc->stored_path)
                                                <button type="button"
                                                    class="js-preview font-medium text-pine hover:underline"
                                                    data-url="{{ route('documents.file', $doc) }}"
                                                    data-title="{{ $doc->original_filename }}">Lihat</button>
                                            @else
                                                <span class="text-ink-faint text-xs">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @empty
                <p class="p-5 text-ink-soft card">Belum ada dokumen.</p>
            @endforelse
        </form>
    </div>

    <x-pdf-preview-panel />

    <script>
        (function () {
            const selectAll = document.getElementById('select-all');
            const rowChecks = Array.from(document.querySelectorAll('.row-check'));
            const groups    = Array.from(document.querySelectorAll('.batch-group'));
            const bulkBtn   = document.getElementById('bulk-delete-btn');
            const countEl   = document.getElementById('selected-count');

            // Perbarui state satu checkbox "pilih batch" berdasarkan baris di dalamnya.
            function refreshGroup(group) {
                const boxes   = Array.from(group.querySelectorAll('.row-check'));
                const batch   = group.querySelector('.batch-check');
                if (!batch) return;
                const checked = boxes.filter(c => c.checked).length;
                batch.checked = checked > 0 && checked === boxes.length;
                batch.indeterminate = checked > 0 && checked < boxes.length;
            }

            function refresh() {
                const checked = rowChecks.filter(c => c.checked).length;
                countEl.textContent = checked;
                bulkBtn.disabled = checked === 0;
                if (selectAll) {
                    selectAll.checked = checked > 0 && checked === rowChecks.length;
                    selectAll.indeterminate = checked > 0 && checked < rowChecks.length;
                }
                groups.forEach(refreshGroup);
            }

            // Pilih semua (global)
            if (selectAll) {
                selectAll.addEventListener('change', function () {
                    rowChecks.forEach(c => { c.checked = selectAll.checked; });
                    refresh();
                });
            }

            // Pilih semua dalam 1 batch
            groups.forEach(group => {
                const batch = group.querySelector('.batch-check');
                if (!batch) return;
                batch.addEventListener('change', function () {
                    group.querySelectorAll('.row-check').forEach(c => { c.checked = batch.checked; });
                    refresh();
                });
            });

            rowChecks.forEach(c => c.addEventListener('change', refresh));
            refresh();
        })();
    </script>
</x-app-layout>
