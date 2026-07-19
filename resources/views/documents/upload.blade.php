<x-app-layout>
    <div class="py-10">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="mb-6">
                <h1 class="text-2xl font-semibold tracking-tight text-ink">Upload Dokumen</h1>
                <p class="mt-1 text-sm text-ink-soft">Pilih jenis dokumen, lalu unggah file PDF.</p>
            </div>

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

            {{-- Kartu form upload --}}
            <div id="upload-card" class="card p-6">
                <form id="upload-form" action="{{ route('documents.upload.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-5">
                        <label class="field-label">Jenis Dokumen</label>
                        <select name="doc_variant" class="field">
                            <option value="">Auto Detect</option>
                            <option value="spk_kontrak">SPK KONTRAK</option>
                            <option value="spk_tetap">SPK TETAP</option>
                        </select>
                        <p class="field-hint">Kosongkan untuk deteksi otomatis.</p>
                    </div>

                    <div class="mb-5">
                        <label class="field-label">File PDF</label>

                        {{-- Dropzone: klik atau seret file ke sini --}}
                        <div id="dropzone"
                             class="group flex flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed border-line bg-paper/40 px-6 py-8 text-center cursor-pointer transition hover:border-pine/50 hover:bg-pine-soft/40">
                            <svg class="w-8 h-8 text-ink-faint group-hover:text-pine transition" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                            </svg>
                            <div class="text-sm text-ink">
                                <span class="font-medium text-pine">Klik untuk memilih</span> atau seret file ke sini
                            </div>
                            <div class="text-xs text-ink-faint">PDF &middot; maks 500 file &middot; maks 20 MB tiap file</div>
                        </div>

                        {{-- Input asli (tersembunyi). files-nya = sumber data untuk submit. --}}
                        <input id="file-input" type="file" name="docs[]" multiple accept="application/pdf" class="hidden">
                    </div>

                    {{-- Daftar file terpilih + preview --}}
                    <div id="file-list-wrap" class="mb-5 hidden">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-ink">File terpilih (<span id="file-count">0</span> / 500)</span>
                            <button type="button" id="clear-all" class="text-xs font-medium text-danger hover:text-danger-dark">Kosongkan semua</button>
                        </div>
                        <ul id="file-list" class="divide-y divide-line rounded-xl border border-line overflow-hidden"></ul>
                    </div>

                    <button type="submit" id="submit-btn" class="btn-primary w-full" disabled>
                        Proses OCR &rarr;
                    </button>
                </form>
            </div>

            {{-- ===== Hasil OCR: muncul di bawah form saat proses jalan & sesudahnya ===== --}}
            <div id="result-card" class="card overflow-hidden mt-6 hidden">
                <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 border-b border-line bg-paper/60">
                    <div>
                        <h2 class="text-base font-semibold text-ink">Hasil OCR</h2>
                        <p id="result-summary" class="text-xs text-ink-faint mt-0.5">Memproses&hellip;</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <label id="select-all-wrap" class="hidden items-center gap-2 text-sm text-ink-soft cursor-pointer">
                            <input type="checkbox" id="result-select-all" class="rounded border-line text-pine focus:ring-pine/30">
                            Pilih semua
                        </label>
                        <button type="button" id="confirm-selected-btn" class="btn-primary hidden">
                            Konfirmasi Terpilih (<span id="confirm-count">0</span>)
                        </button>
                    </div>
                </div>

                <ul id="result-list" class="divide-y divide-line"></ul>
            </div>

        </div>
    </div>

    <x-pdf-preview-panel />

    <script>
        (function () {
            const form       = document.getElementById('upload-form');
            const input      = document.getElementById('file-input');
            const dropzone   = document.getElementById('dropzone');
            const listWrap   = document.getElementById('file-list-wrap');
            const listEl     = document.getElementById('file-list');
            const countEl    = document.getElementById('file-count');
            const submitBtn  = document.getElementById('submit-btn');
            const clearAll   = document.getElementById('clear-all');
            const csrf       = document.querySelector('meta[name="csrf-token"]').content;

            // Elemen hasil OCR
            const resultCard   = document.getElementById('result-card');
            const resultList   = document.getElementById('result-list');
            const summaryEl    = document.getElementById('result-summary');
            const selAllWrap   = document.getElementById('select-all-wrap');
            const selAll       = document.getElementById('result-select-all');
            const confirmBtn   = document.getElementById('confirm-selected-btn');
            const confirmCount = document.getElementById('confirm-count');

            const BATCH_KEY = 'ocr_active_batch'; // legacy; dibersihkan saja

            // Pekerjaan berjalan dari server (belum dikonfirmasi) — sumber
            // kebenaran, tahan refresh/sleep. Dirender saat halaman dibuka.
            const UNFINISHED = @json($unfinished);

            // Kunci anti spam-click: sekali proses jalan, submit diabaikan.
            let busy = false;

            // Akumulator: sumber kebenaran untuk file yang akan di-submit.
            let store = [];
            const MAX_FILES = 500;

            function keyOf(f) { return f.name + '|' + f.size + '|' + f.lastModified; }

            function humanSize(bytes) {
                if (bytes < 1024) return bytes + ' B';
                if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
                return (bytes / 1048576).toFixed(1) + ' MB';
            }

            // Sinkronkan store -> input.files (yang benar-benar dikirim ke server).
            function syncInput() {
                const dt = new DataTransfer();
                store.forEach(f => dt.items.add(f));
                input.files = dt.files;
            }

            function addFiles(fileList) {
                const existing = new Set(store.map(keyOf));
                let penuh = false;
                const dobel = [];
                Array.from(fileList).forEach(f => {
                    const isPdf = f.type === 'application/pdf' || /\.pdf$/i.test(f.name);
                    if (!isPdf) return;
                    if (existing.has(keyOf(f))) { dobel.push(f.name); return; }   // duplikat: jangan ditambah 2x
                    if (store.length >= MAX_FILES) { penuh = true; return; }
                    existing.add(keyOf(f));
                    store.push(f);
                });
                syncInput();
                render();
                if (dobel.length > 0) {
                    alert('⚠ File duplikat dilewati (sudah ada di daftar):\n- ' + dobel.slice(0, 10).join('\n- ') + (dobel.length > 10 ? '\n…dan ' + (dobel.length - 10) + ' lainnya' : ''));
                }
                if (penuh) alert('Maksimal ' + MAX_FILES + ' file per upload.');
            }

            function removeAt(i) {
                store.splice(i, 1);
                syncInput();
                render();
            }

            function render() {
                listEl.innerHTML = '';
                if (store.length === 0) {
                    listWrap.classList.add('hidden');
                    submitBtn.disabled = true;
                    countEl.textContent = '0';
                    return;
                }
                listWrap.classList.remove('hidden');
                submitBtn.disabled = false;
                countEl.textContent = store.length;

                store.forEach((f, i) => {
                    const li = document.createElement('li');
                    li.className = 'flex items-center gap-3 bg-surface px-3.5 py-2.5';
                    li.innerHTML =
                        '<span class="grid h-9 w-9 flex-shrink-0 place-items-center rounded-lg bg-danger-soft text-[10px] font-semibold text-danger-dark">PDF</span>' +
                        '<div class="min-w-0 flex-1">' +
                            '<div class="truncate text-sm text-ink">' + escapeHtml(f.name) + '</div>' +
                            '<div class="text-xs text-ink-faint">' + humanSize(f.size) + '</div>' +
                        '</div>' +
                        '<button type="button" data-act="preview" data-i="' + i + '" class="rounded-lg px-2.5 py-1 text-xs font-medium text-pine hover:bg-pine-soft transition">Preview</button>' +
                        '<button type="button" data-act="remove" data-i="' + i + '" class="rounded-lg px-2 py-1 text-ink-faint hover:text-danger hover:bg-danger-soft transition" title="Hapus">&times;</button>';
                    listEl.appendChild(li);
                });
            }

            function escapeHtml(s) {
                const d = document.createElement('div');
                d.textContent = s || '';
                return d.innerHTML;
            }

            // ================= Hasil OCR (list per file di bawah form) =================

            const SPINNER =
                '<svg class="w-5 h-5 animate-spin text-pine" fill="none" viewBox="0 0 24 24">' +
                '<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>' +
                '<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>';
            const ICON_WAIT =
                '<svg class="w-5 h-5 text-ink-faint" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">' +
                '<path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
            const ICON_OK =
                '<svg class="w-5 h-5 text-pine" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">' +
                '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
            const ICON_ERR =
                '<svg class="w-5 h-5 text-danger" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">' +
                '<path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';

            // state per dokumen: {id, name, status, ...payload}
            let docsState = [];

            function rowEl(id) { return resultList.querySelector('li[data-id="' + id + '"]'); }

            function makeRow(doc) {
                const li = document.createElement('li');
                li.dataset.id = doc.id;
                li.className = 'px-4 py-3 bg-surface';
                li.innerHTML =
                    '<div class="flex items-center gap-3">' +
                        '<span class="row-check-wrap hidden flex-shrink-0"><input type="checkbox" class="row-check rounded border-line text-pine focus:ring-pine/30"></span>' +
                        '<span class="row-icon flex-shrink-0">' + ICON_WAIT + '</span>' +
                        '<div class="min-w-0 flex-1">' +
                            '<div class="truncate text-sm text-ink" title="' + escapeHtml(doc.name) + '">' + escapeHtml(doc.name) + '</div>' +
                            {{-- Nama file hasil OCR di baris sendiri + break-all supaya kebaca penuh, tidak kepotong --}}
                            '<div class="mt-1 flex items-start gap-1.5">' +
                                '<svg class="w-3.5 h-3.5 mt-0.5 flex-shrink-0 text-ink-faint" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>' +
                                '<span class="row-newname font-mono text-xs text-ink break-all">menunggu&hellip;</span>' +
                            '</div>' +
                            '<div class="row-dup hidden mt-1"></div>' +
                            '<div class="row-note text-xs text-ink-faint mt-0.5">Menunggu giliran</div>' +
                        '</div>' +
                        '<div class="row-actions flex flex-shrink-0 items-center gap-2.5"></div>' +
                    '</div>';
                return li;
            }

            function setRow(doc) {
                const row = rowEl(doc.id);
                if (!row) return;
                const icon    = row.querySelector('.row-icon');
                const newname = row.querySelector('.row-newname');
                const note    = row.querySelector('.row-note');
                const dupEl   = row.querySelector('.row-dup');
                const actions = row.querySelector('.row-actions');
                const chkWrap = row.querySelector('.row-check-wrap');

                actions.innerHTML = '';
                chkWrap.classList.add('hidden');
                dupEl.classList.add('hidden');
                dupEl.innerHTML = '';

                if (doc.status === 'pending') {
                    icon.innerHTML = ICON_WAIT;
                    newname.textContent = 'menunggu…';
                    note.textContent = 'Menunggu giliran';
                } else if (doc.status === 'processing') {
                    icon.innerHTML = SPINNER;
                    newname.textContent = 'Proses…';
                    note.textContent = 'OCR sedang berjalan';
                } else if (doc.status === 'processed') {
                    icon.innerHTML = ICON_OK;
                    newname.textContent = doc.new_filename || '(nama belum terbaca)';
                    newname.title = doc.new_filename || '';
                    note.textContent ='siap dikonfirmasi';
                    chkWrap.classList.remove('hidden');
                    actions.innerHTML =
                        '<button type="button" class="js-preview text-xs font-medium text-pine hover:underline" data-url="' + doc.file_url + '" data-title="' + escapeHtml(doc.name) + '">Preview</button>' +
                        '<button type="button" data-row-act="confirm" class="text-xs font-medium text-pine hover:underline">Konfirmasi</button>' +
                        '<a href="' + doc.review_url + '" class="text-xs font-medium text-ink-soft hover:text-ink hover:underline">Edit</a>' +
                        '<button type="button" data-row-act="delete" class="text-xs font-medium text-danger hover:underline">Hapus</button>';
                } else if (doc.status === 'confirmed') {
                    icon.innerHTML = ICON_OK;
                    newname.textContent = doc.new_filename || '';
                    note.textContent = 'Dikonfirmasi · masuk Riwayat & folder output';
                    actions.innerHTML =
                        '<button type="button" class="js-preview text-xs font-medium text-pine hover:underline" data-url="' + doc.file_url + '" data-title="' + escapeHtml(doc.new_filename || doc.name) + '">Preview</button>' +
                        '<span class="badge-success">Selesai</span>';
                } else if (doc.status === 'error') {
                    icon.innerHTML = ICON_ERR;
                    newname.textContent = '(gagal)';
                    note.textContent = doc.error_message || 'Gagal diproses';
                    note.classList.add('text-danger');
                    actions.innerHTML =
                        '<button type="button" class="js-preview text-xs font-medium text-pine hover:underline" data-url="' + doc.file_url + '" data-title="' + escapeHtml(doc.name) + '">Preview</button>' +
                        '<a href="' + doc.review_url + '" class="text-xs font-medium text-ink-soft hover:text-ink hover:underline">Edit</a>' +
                        '<button type="button" data-row-act="delete" class="text-xs font-medium text-danger hover:underline">Hapus</button>';
                }

                // Peringatan duplikat: file dengan isi yang sama sudah pernah diupload.
                if (doc.duplicate_of && doc.duplicate_of.length > 0) {
                    dupEl.classList.remove('hidden');
                    dupEl.innerHTML =
                        '<span class="inline-flex items-start gap-1.5 rounded-lg bg-danger-soft px-2 py-1 text-xs text-danger-dark">' +
                            '<svg class="w-3.5 h-3.5 mt-px flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>' +
                            '<span><strong>Duplikat!</strong> Isi file sama dengan: ' + escapeHtml(doc.duplicate_of.join(', ')) + '</span>' +
                        '</span>';
                }
                refreshBulkUi();
            }

            function refreshSummary() {
                const total     = docsState.length;
                const processed = docsState.filter(d => d.status === 'processed').length;
                const confirmed = docsState.filter(d => d.status === 'confirmed').length;
                const error     = docsState.filter(d => d.status === 'error').length;
                const sisa      = docsState.filter(d => d.status === 'pending' || d.status === 'processing').length;
                const dobel     = docsState.filter(d => d.duplicate_of && d.duplicate_of.length > 0).length;

                let txt = total + ' file';
                if (sisa > 0)      txt += ' · ' + sisa + ' diproses…';
                if (processed > 0) txt += ' · ' + processed + ' siap konfirmasi';
                if (confirmed > 0) txt += ' · ' + confirmed + ' selesai';
                if (error > 0)     txt += ' · ' + error + ' gagal';
                if (dobel > 0)     txt += ' · ⚠ ' + dobel + ' duplikat';
                summaryEl.textContent = txt;
            }

            function refreshBulkUi() {
                refreshSummary();
                const checks  = Array.from(resultList.querySelectorAll('.row-check'));
                const checked = checks.filter(c => c.checked);

                // Tampilkan kontrol bulk hanya kalau ada yang bisa dikonfirmasi.
                if (checks.length > 0) {
                    selAllWrap.classList.remove('hidden');
                    selAllWrap.classList.add('inline-flex');
                    confirmBtn.classList.remove('hidden');
                } else {
                    selAllWrap.classList.add('hidden');
                    selAllWrap.classList.remove('inline-flex');
                    confirmBtn.classList.add('hidden');
                }
                confirmCount.textContent = checked.length;
                confirmBtn.disabled = checked.length === 0;
                selAll.checked = checks.length > 0 && checked.length === checks.length;
                selAll.indeterminate = checked.length > 0 && checked.length < checks.length;
            }

            function findDoc(id) { return docsState.find(d => String(d.id) === String(id)); }

            // ================= Antrian proses OCR (per file) =================

            // Render daftar dokumen ke kartu hasil, lalu proses yang masih pending.
            async function runQueue(docs) {
                // Gabungkan dengan yang sudah tampil (upload susulan tidak menimpa).
                docs.forEach(d => {
                    if (!findDoc(d.id)) {
                        docsState.push(d);
                        resultList.appendChild(makeRow(d));
                        setRow(d);
                    }
                });
                if (docsState.length > 0) resultCard.classList.remove('hidden');
                refreshSummary();

                const antrian = docsState.filter(d => d.status === 'pending');
                if (antrian.length > 0) busy = true;

                for (const doc of antrian) {
                    doc.status = 'processing';
                    setRow(doc);
                    try {
                        const res  = await fetch(doc.process_url, {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                        });
                        const data = await res.json();
                        Object.assign(doc, data);
                    } catch (e) {
                        doc.status = 'error';
                        doc.error_message = 'Koneksi terputus saat memproses.';
                    }
                    setRow(doc);
                }

                busy = false;
                refreshSummary();
            }

            // Peringatkan kalau mau menutup halaman saat OCR masih jalan.
            window.addEventListener('beforeunload', function (e) {
                if (!busy) return;
                e.preventDefault();
                e.returnValue = '';
            });

            // ================= Aksi per baris & bulk =================

            resultList.addEventListener('click', async function (e) {
                const btn = e.target.closest('button[data-row-act]');
                if (!btn) return;
                const row = btn.closest('li[data-id]');
                const doc = findDoc(row.dataset.id);
                if (!doc) return;

                if (btn.dataset.rowAct === 'confirm') {
                    // Jangan proses dua kali: baris yang sudah/sedang dikonfirmasi diabaikan.
                    if (doc.status !== 'processed' || doc._confirming) return;
                    doc._confirming = true;
                    btn.disabled = true;
                    btn.textContent = 'Memproses…';
                    try {
                        const res = await fetch(doc.confirm_url, {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                        });
                        if (res.ok) {
                            Object.assign(doc, await res.json());
                        } else {
                            const err = await res.json().catch(() => null);
                            alert(err && err.message ? err.message : 'Gagal konfirmasi.');
                        }
                    } catch (e2) { alert('Gagal konfirmasi. Periksa koneksi.'); }
                    doc._confirming = false;
                    setRow(doc);
                }

                if (btn.dataset.rowAct === 'delete') {
                    if (!confirm('Hapus dokumen ini? File dan datanya ikut terhapus.')) return;
                    btn.disabled = true;
                    try {
                        const res = await fetch(doc.delete_url, {
                            method: 'DELETE',
                            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                        });
                        if (res.ok) {
                            docsState = docsState.filter(d => d !== doc);
                            row.remove();
                            refreshBulkUi();
                        } else {
                            alert('Gagal menghapus.');
                            btn.disabled = false;
                        }
                    } catch (e2) { alert('Gagal menghapus. Periksa koneksi.'); btn.disabled = false; }
                }
            });

            resultList.addEventListener('change', function (e) {
                if (e.target.classList.contains('row-check')) refreshBulkUi();
            });

            selAll.addEventListener('change', function () {
                resultList.querySelectorAll('.row-check').forEach(c => { c.checked = selAll.checked; });
                refreshBulkUi();
            });

            confirmBtn.addEventListener('click', async function () {
                if (confirmBtn.disabled) return;
                // Hanya dokumen berstatus processed yang dikirim — yang sudah
                // terlanjur confirmed (mis. dari tombol per baris) dilewati.
                const ids = Array.from(resultList.querySelectorAll('.row-check:checked'))
                    .map(c => c.closest('li[data-id]').dataset.id)
                    .filter(id => { const d = findDoc(id); return d && d.status === 'processed'; });
                if (ids.length === 0) return;
                if (!confirm('Konfirmasi ' + ids.length + ' dokumen terpilih?')) return;

                confirmBtn.disabled = true;
                try {
                    const res = await fetch('{{ route('documents.confirm-bulk') }}', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                        body: JSON.stringify({ ids: ids }),
                    });
                    const data = await res.json();
                    (data.results || []).forEach(r => {
                        const doc = findDoc(r.id);
                        if (doc) { doc.status = r.status; setRow(doc); }
                    });
                } catch (e2) { alert('Gagal konfirmasi. Periksa koneksi.'); }
                confirmBtn.disabled = false;
                refreshBulkUi();
            });

            // ================= Submit upload (AJAX) =================

            form.addEventListener('submit', async function (e) {
                e.preventDefault();
                if (store.length === 0) return;
                if (busy) return;          // anti dobel-submit / spam click
                busy = true;

                submitBtn.disabled = true;
                submitBtn.textContent = 'Mengunggah file…';

                try {
                    const res = await fetch(form.action, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                        body: new FormData(form),
                    });

                    if (!res.ok) {
                        const err = await res.json().catch(() => null);
                        alert(err && err.message ? err.message : 'Upload gagal. Coba lagi.');
                        busy = false;
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = 'Proses OCR &rarr;';
                        return;
                    }

                    const batch = await res.json();

                    // Kosongkan pilihan file & aktifkan lagi form untuk upload berikutnya.
                    store = [];
                    syncInput();
                    render();
                    submitBtn.innerHTML = 'Proses OCR &rarr;';

                    runQueue(batch.documents);
                } catch (e2) {
                    alert('Upload gagal. Periksa koneksi lalu coba lagi.');
                    busy = false;
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Proses OCR &rarr;';
                }
            });

            // Tampilkan pekerjaan berjalan dari server (tahan refresh/sleep):
            // yang 'pending' otomatis dilanjut, yang 'processed' menunggu
            // konfirmasi di sini — tidak hilang sebelum dikonfirmasi/dihapus.
            localStorage.removeItem(BATCH_KEY);
            if (UNFINISHED.length > 0) {
                runQueue(UNFINISHED);
            }

            // ================= Interaksi pemilihan file =================

            // Klik dropzone -> buka dialog file
            dropzone.addEventListener('click', () => input.click());

            // Pilih file lewat dialog: gabungkan (bukan timpa)
            input.addEventListener('change', function () {
                addFiles(input.files);
            });

            // Drag & drop
            ['dragenter', 'dragover'].forEach(ev => dropzone.addEventListener(ev, e => {
                e.preventDefault();
                dropzone.classList.add('border-pine', 'bg-pine-soft/50');
            }));
            ['dragleave', 'drop'].forEach(ev => dropzone.addEventListener(ev, e => {
                e.preventDefault();
                dropzone.classList.remove('border-pine', 'bg-pine-soft/50');
            }));
            dropzone.addEventListener('drop', e => {
                if (e.dataTransfer && e.dataTransfer.files) addFiles(e.dataTransfer.files);
            });

            // Aksi di daftar (preview / remove) via event delegation
            listEl.addEventListener('click', function (e) {
                const btn = e.target.closest('button[data-act]');
                if (!btn) return;
                const i = parseInt(btn.dataset.i, 10);
                if (btn.dataset.act === 'remove') {
                    removeAt(i);
                } else if (btn.dataset.act === 'preview') {
                    const url = URL.createObjectURL(store[i]);
                    openPdfPreview(url, store[i].name);
                }
            });

            clearAll.addEventListener('click', function () {
                store = [];
                syncInput();
                render();
            });
        })();
    </script>
</x-app-layout>
