{{--
    Preview pane (slide-over dari kanan) untuk menampilkan file PDF inline.
    Include SEKALI per halaman: <x-pdf-preview-panel />

    Pemicu preview: tombol/elemen apa pun dengan class "js-preview" dan
    atribut data-url (URL file) + data-title (judul). Contoh:

        <button type="button" class="js-preview"
                data-url="{{ route('documents.file', $doc) }}"
                data-title="{{ $doc->original_filename }}">Lihat</button>

    Atau panggil langsung dari JS: openPdfPreview(url, title)
--}}
<div id="pdf-preview-overlay" class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-ink/40 backdrop-blur-[1px]" onclick="closePdfPreview()"></div>

    <div class="absolute right-0 top-0 flex h-full w-full max-w-2xl flex-col bg-surface shadow-soft">
        <div class="flex items-center justify-between border-b border-line px-5 py-4">
            <h3 id="pdf-preview-title" class="truncate pr-4 text-sm font-semibold text-ink">Preview</h3>
            <div class="flex items-center gap-4">
                <a id="pdf-preview-open" href="#" target="_blank" rel="noopener"
                   class="text-xs font-medium text-pine underline underline-offset-2 hover:text-pine-dark">Buka tab baru</a>
                <button type="button" onclick="closePdfPreview()"
                        class="text-2xl leading-none text-ink-faint hover:text-ink">&times;</button>
            </div>
        </div>
        <div class="flex-1 bg-paper">
            <iframe id="pdf-preview-frame" src="" class="h-full w-full" title="Preview PDF"></iframe>
        </div>
    </div>
</div>

<script>
    function openPdfPreview(url, title) {
        var overlay = document.getElementById('pdf-preview-overlay');
        var frame   = document.getElementById('pdf-preview-frame');
        var titleEl = document.getElementById('pdf-preview-title');
        var openEl  = document.getElementById('pdf-preview-open');
        frame.src = url;
        titleEl.textContent = title || 'Preview';
        openEl.href = url;
        overlay.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closePdfPreview() {
        var overlay = document.getElementById('pdf-preview-overlay');
        var frame   = document.getElementById('pdf-preview-frame');
        overlay.classList.add('hidden');
        frame.src = '';
        document.body.style.overflow = '';
    }

    // Delegasi: semua elemen .js-preview memicu preview.
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.js-preview');
        if (!btn) return;
        e.preventDefault();
        openPdfPreview(btn.dataset.url, btn.dataset.title);
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closePdfPreview();
    });
</script>
