@extends('layout.user', ['role' => 'admin', 'title' => 'Sidak Stok Toko', 'default' => ['page_name' => 'Sidak Stok Toko']])

@section('content')
@include('admin.sidak.style')
@php $finished = $inspection->isFinished(); @endphp

<div class="content-wrapper">
<div class="sdk">
    <h1>{{ $finished ? 'Hasil Sidak Stok' : 'Sidak Stok Toko' }}</h1>
    <p class="sdk-sub">
        {{ $inspection->created_at->format('d/m/Y H:i') }} &middot; {{ optional($inspection->actor())->name ?? '-' }}
        @if($inspection->note) &middot; {{ $inspection->note }} @endif
        @if($inspection->counted_by) &middot; dihitung {{ $inspection->counted_by }} @endif
        @if($finished) &middot; selesai {{ $inspection->finished_at->format('H:i') }} @endif
    </p>

    @if(session('error'))
        <div class="sdk-msg bad">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="sdk-msg bad">{{ $errors->first() }}</div>
    @endif

    <div class="sdk-stats">
        <div class="sdk-stat"><b id="st-total">0</b><small>Dicek</small></div>
        <div class="sdk-stat ok"><b id="st-match">0</b><small>Cocok</small></div>
        <div class="sdk-stat bad"><b id="st-minus">0</b><small>Kurang</small></div>
        <div class="sdk-stat warn"><b id="st-plus">0</b><small>Lebih</small></div>
    </div>

    @if($finished)
        <div class="sdk-card">
            <div class="sdk-item" style="border:0;padding:0">
                <div class="sdk-item-main">
                    <div class="sdk-item-sub">Nilai barang kurang (harga beli)</div>
                    <div class="sdk-name" style="color:var(--bad)">Rp{{ number_format(abs($summary['minus_value']), 0, ',', '.') }}</div>
                </div>
                <div class="sdk-item-main" style="text-align:right">
                    <div class="sdk-item-sub">Nilai barang lebih</div>
                    <div class="sdk-name" style="color:var(--warn)">Rp{{ number_format($summary['plus_value'], 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
    @else
        <div id="sdk-msg" class="sdk-msg" role="alert"></div>

        @if(count($targets))
        {{-- 0. daftar tugas cek stok --}}
        <div class="sdk-card" id="task-card">
            <div class="sdk-head-row">
                <h2>Daftar cek stok</h2>
                <button type="button" class="sdk-btn sdk-btn-sm" id="btn-wa"><i class="fa fa-whatsapp"></i> Copy teks WA</button>
            </div>
            <div class="sdk-item-sub" id="task-progress-text"></div>
            <div class="sdk-progress"><div id="task-progress" style="width:0"></div></div>
            <div class="sdk-item-sub" style="margin-bottom:6px">
                Ketik jumlah fisik (dalam satuan yang tertulis) lalu tekan ✓ atau Enter. Ketuk nama barang untuk melihat stok sistem dan menulis catatan.
                Nomor sama dengan nomor di teks WA.
            </div>
            <div id="task-list"></div>
        </div>
        @endif

        {{-- 1. pilih barang --}}
        <div class="sdk-card" id="pick-card">
            <div class="sdk-grid2">
                <button type="button" class="sdk-btn sdk-btn-prim" id="btn-scan"><i class="fa fa-barcode"></i> Scan Barcode</button>
                <button type="button" class="sdk-btn" id="btn-random"><i class="fa fa-random"></i> Barang Acak</button>
            </div>

            <div id="scan-box" style="display:none;margin-top:12px">
                <div id="camera-row" style="display:none;margin-bottom:8px">
                    <label class="sdk-label" for="camera-select" style="margin-top:0">Kamera</label>
                    <select class="sdk-input" id="camera-select"></select>
                </div>
                <div id="sdk-reader"></div>
                <div class="sdk-item-sub" style="text-align:center;margin:8px 0">Arahkan kamera ke barcode, jarak sekitar 10–15 cm, sampai gambarnya tajam</div>
                <button type="button" class="sdk-btn sdk-btn-sm" id="btn-scan-stop" style="width:100%">Tutup kamera</button>
            </div>

            <label class="sdk-label" for="random-category">Barang acak dari kategori</label>
            <select class="sdk-input" id="random-category">
                <option value="">Semua kategori</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>

            <label class="sdk-label" for="manual">Ketik barcode atau nama barang</label>
            <div class="sdk-row">
                <input class="sdk-input" id="manual" autocomplete="off" enterkeyhint="search" placeholder="barcode / nama">
                <button type="button" class="sdk-btn" id="btn-manual" aria-label="Cari"><i class="fa fa-search"></i></button>
            </div>
            <div class="sdk-suggest" id="suggest"></div>
        </div>

        {{-- 2. hitung fisik --}}
        <div class="sdk-card" id="count-card" style="display:none">
            <div class="sdk-name" id="it-name"></div>
            <div class="sdk-meta" id="it-meta"></div>
            <div class="sdk-system">
                <div class="sdk-item-sub">Stok sistem (seharusnya)</div>
                <div class="sdk-system-num"><span id="it-system" style="color:inherit"></span> <small id="it-system-unit"></small></div>
            </div>
            <div class="sdk-msg info" id="it-checked" style="display:none;margin:10px 0 0"></div>

            <label class="sdk-label" for="real-stock">Jumlah fisik di rak (<span id="it-unit" style="color:inherit"></span>)</label>
            <input class="sdk-input sdk-qty" id="real-stock" type="number" inputmode="decimal" min="0" step="any" placeholder="0">

            <div class="sdk-live" id="it-live"></div>

            <label class="sdk-label" for="it-note">Catatan (boleh kosong)</label>
            <input class="sdk-input" id="it-note" maxlength="255" placeholder="contoh: 2 pcs rusak di gudang">

            <div class="sdk-grid2" style="margin-top:12px">
                <button type="button" class="sdk-btn" id="btn-cancel">Batal</button>
                <button type="button" class="sdk-btn sdk-btn-ok" id="btn-save"><i class="fa fa-check"></i> Simpan</button>
            </div>
        </div>

        {{-- 3. hasil barang terakhir --}}
        <div class="sdk-result" id="result" aria-live="polite">
            <div class="big" id="res-big"></div>
            <div class="det" id="res-det"></div>
        </div>
    @endif

    <div class="sdk-card">
        <h2>Barang yang sudah dicek</h2>
        <div id="list"></div>
    </div>

    @if($finished)
        @php
            $selisih = $summary['minus'] + $summary['plus'];
            $tidakDicek = array_values(array_filter($targets, fn ($t) => $t['detail'] === null));
        @endphp
        @if(count($tidakDicek))
            <div class="sdk-msg info" style="display:block">
                {{ count($tidakDicek) }} barang di daftar tidak dicek:
                {{ implode(', ', array_map(fn ($t) => $t['no'] . '. ' . $t['name'], array_slice($tidakDicek, 0, 10))) }}{{ count($tidakDicek) > 10 ? ', …' : '' }}
            </div>
        @endif
        @if($inspection->stock_opname_id)
            <div class="sdk-msg info" style="display:block">
                Selisih sidak ini sudah dikoreksi lewat
                <a href="{{ url('/admin/stock-opname/' . $inspection->stock_opname_id . '/detail') }}" style="color:#1e3a8a;font-weight:700;text-decoration:underline">Stock Opname #{{ $inspection->stock_opname_id }}</a>.
            </div>
            @if(count($kosong) && canMenu('laporan.perputaran'))
                <div class="sdk-card">
                    <h2>Barang yang tidak ada di toko</h2>
                    <p class="sdk-sub" style="margin-bottom:6px">
                        Fisiknya 0 dan stok sistemnya sekarang sudah habis. Centang yang memang tidak dijual lagi untuk di-discontinue
                        (tidak muncul lagi di Perputaran Barang &amp; Reorder; bisa diaktifkan kembali dari tab Disc.).
                    </p>
                    <form method="POST" action="{{ route('admin.reports.movement.bulk-discontinue') }}" id="disc-form">
                        {{ csrf_field() }}
                        <input type="hidden" name="reason" value="Sidak stok #{{ $inspection->id }}: barang tidak ada di toko (stok fisik 0)">
                        @foreach($kosong as $k)
                            <label class="sdk-check">
                                <input type="checkbox" name="ids[]" value="{{ $k['good_id'] }}" {{ $k['from_list'] ? 'checked' : '' }}>
                                <span>{{ $k['name'] }} @unless($k['from_list'])<span class="sdk-item-sub">(bukan dari daftar)</span>@endunless</span>
                            </label>
                        @endforeach
                        <div style="margin-top:10px">
                            <button type="submit" class="sdk-btn sdk-btn-bad"><i class="fa fa-ban"></i> Discontinue yang dicentang</button>
                        </div>
                    </form>
                </div>
            @endif
        @elseif($selisih > 0)
            <form method="POST" action="{{ route('admin.sidak.opname', $inspection->id) }}" id="opname-form" data-count="{{ $selisih }}" style="margin-bottom:10px">
                {{ csrf_field() }}
                <button type="submit" class="sdk-btn sdk-btn-prim"><i class="fa fa-search-plus"></i> Jadikan Stock Opname ({{ $selisih }} barang selisih)</button>
            </form>
            <p class="sdk-sub" style="text-align:center">Stok belum berubah. Tombol di atas mengoreksi stok {{ $selisih }} barang yang selisih dan membuat jurnalnya, sama seperti Stock Opname.</p>
        @else
            <p class="sdk-sub" style="text-align:center">Semua barang cocok, tidak ada stok yang perlu dikoreksi.</p>
        @endif
        <a class="sdk-btn" href="{{ route('admin.sidak.index') }}"><i class="fa fa-arrow-left"></i> Kembali ke daftar sidak</a>
    @else
        <form method="POST" action="{{ route('admin.sidak.finish', $inspection->id) }}" id="finish-form">
            {{ csrf_field() }}
            @if($adaDaftar)
                <label class="sdk-label" for="counted-by">Dihitung oleh (nama pegawai, boleh kosong)</label>
                <input class="sdk-input" id="counted-by" name="counted_by" maxlength="100" value="{{ $inspection->counted_by }}"
                       placeholder="kosongkan kalau Anda sendiri yang mengecek" style="margin-bottom:10px">
            @endif
            <button type="submit" class="sdk-btn sdk-btn-bad"><i class="fa fa-flag-checkered"></i> Selesai Sidak</button>
        </form>
    @endif
</div>
</div>
@endsection

@section('js-addon')
@if(! $finished)
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
@endif
<script>
(function () {
    var FINISHED = @json($finished);
    var BASE     = @json(url('/admin/sidak-stok/' . $inspection->id));
    var TOKEN    = @json(csrf_token());
    var details  = @json($details);
    var summary  = @json($summary);
    var targets  = @json($targets);
    var NOTE     = @json($inspection->note);

    function $id(id) { return document.getElementById(id); }
    function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }
    function num(n) { return Number(n).toLocaleString('id-ID', { maximumFractionDigits: 3 }); }
    function rupiah(n) { return 'Rp' + Math.round(Math.abs(n)).toLocaleString('id-ID'); }
    function signed(n) { return (n > 0 ? '+' : '') + num(n); }
    function tone(diff) { return diff === 0 ? 'ok' : (diff < 0 ? 'bad' : 'warn'); }

    function renderSummary() {
        $id('st-total').textContent = summary.total;
        $id('st-match').textContent = summary.match;
        $id('st-minus').textContent = summary.minus;
        $id('st-plus').textContent  = summary.plus;
    }

    function renderList() {
        var list = $id('list');
        if (!details.length) {
            list.innerHTML = '<div class="sdk-empty">Belum ada barang yang dicek.</div>';
            return;
        }
        list.innerHTML = details.map(function (d) {
            return '<div class="sdk-item">' +
                '<div class="sdk-item-main">' +
                    '<div class="sdk-item-name">' + esc(d.name) + '</div>' +
                    '<div class="sdk-item-sub">' + esc(d.code) + ' &middot; sistem ' + num(d.system_stock) + ', fisik ' + num(d.real_stock) + ' ' + esc(d.unit) +
                        (d.difference !== 0 ? ' &middot; ' + rupiah(d.value) : '') +
                        (d.note ? '<br>' + esc(d.note) : '') + '</div>' +
                '</div>' +
                '<span class="sdk-pill ' + tone(d.difference) + '">' + (d.difference === 0 ? 'Cocok' : signed(d.difference)) + '</span>' +
                (FINISHED ? '' : '<button type="button" class="sdk-del" data-del="' + d.id + '" aria-label="Hapus ' + esc(d.name) + '"><i class="fa fa-trash"></i></button>') +
            '</div>';
        }).join('');
    }

    // ------------------------------------------------- daftar tugas cek stok
    function targetDone(t) { return t.detail !== null; }

    function renderTargets() {
        var box = $id('task-list');
        if (!box) { return; }
        var done = targets.filter(targetDone).length;
        $id('task-progress-text').textContent = done + ' dari ' + targets.length + ' barang sudah dicek' +
            (done === targets.length ? ' — semua selesai, tekan "Selesai Sidak" di bawah.' : '');
        $id('task-progress').style.width = (targets.length ? Math.round(done / targets.length * 100) : 0) + '%';
        box.innerHTML = targets.map(function (t) {
            var d = t.detail;
            var right = d
                ? '<span class="sdk-pill ' + tone(d.difference) + '">' + (d.difference === 0 ? 'Cocok' : signed(d.difference)) + '</span>'
                : '<input class="sdk-input sdk-tqty" type="number" inputmode="decimal" min="0" step="any" placeholder="fisik" data-qty="' + t.good_id + '" aria-label="Jumlah fisik ' + esc(t.name) + '">' +
                  '<button type="button" class="sdk-btn sdk-btn-ok sdk-tsave" data-save="' + t.good_id + '" aria-label="Simpan"><i class="fa fa-check"></i></button>';
            return '<div class="sdk-titem' + (d ? ' done' : '') + '">' +
                '<div class="sdk-tno">' + t.no + '</div>' +
                '<div class="sdk-item-main">' +
                    '<button type="button" class="sdk-tname" data-open="' + t.good_id + '">' + esc(t.name) + '</button>' +
                    '<div class="sdk-item-sub">' + (t.category ? esc(t.category) + ' &middot; ' : '') + 'hitung dalam ' + esc(t.unit) +
                        (d ? ' &middot; fisik ' + num(d.real_stock) + ', sistem ' + num(d.system_stock) : '') + '</div>' +
                '</div>' + right +
            '</div>';
        }).join('');
    }

    /** Teks WA untuk pegawai: barang yang belum dicek, nomor sama dengan daftar. Stok sistem sengaja tidak ditulis. */
    function waText() {
        var sisa = targets.filter(function (t) { return !targetDone(t); });
        var list = sisa.length ? sisa : targets;
        var today = new Date().toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
        var lines = ['🔎 *CEK STOK BARANG*', '📅 ' + today];
        if (NOTE) { lines.push('📝 ' + NOTE); }
        lines.push('', 'Tolong cari dan hitung stok fisik barang ini (rak + gudang), lalu balas angkanya sesuai nomor:', '');
        list.forEach(function (t) {
            lines.push(t.no + '. ' + t.name);
            lines.push('    _' + (t.category ? t.category + ' · ' : '') + 'hitung dalam ' + t.unit + '_');
        });
        lines.push('', 'Kalau barangnya tidak ketemu, tulis 0.', 'Contoh balasan:');
        list.slice(0, 2).forEach(function (t, i) { lines.push(t.no + ' = ' + (i === 0 ? '5' : '0')); });
        return lines.join('\n');
    }

    function copyText(text, btn) {
        function done() {
            var old = btn.innerHTML;
            btn.innerHTML = '<i class="fa fa-check"></i> Tersalin';
            setTimeout(function () { btn.innerHTML = old; }, 1800);
        }
        function fallback() {
            var ta = document.createElement('textarea');
            ta.value = text; ta.style.position = 'fixed'; ta.style.opacity = '0';
            document.body.appendChild(ta); ta.select();
            try { document.execCommand('copy'); done(); } catch (e) { alert(text); }
            document.body.removeChild(ta);
        }
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(done).catch(fallback);
        } else {
            fallback();
        }
    }

    /** Simpan hasil satu barang lalu perbarui ringkasan, daftar tugas, dan daftar hasil. */
    function applySaved(data) {
        var d = data.detail;
        details = details.filter(function (x) { return x.good_id !== d.good_id; });
        details.unshift(d);
        targets.forEach(function (t) { if (t.good_id === d.good_id) { t.detail = d; } });
        summary = data.summary;
        renderSummary();
        renderList();
        renderTargets();
    }

    renderSummary();
    renderList();
    renderTargets();
    if (FINISHED) {
        var opname = $id('opname-form');
        if (opname) {
            opname.addEventListener('submit', function (e) {
                if (!confirm('Koreksi stok ' + opname.getAttribute('data-count') + ' barang yang selisih sesuai hasil sidak ini? Stok dan jurnal akan berubah, dan hanya bisa dilakukan sekali.')) {
                    e.preventDefault();
                    return;
                }
                opname.querySelector('button').disabled = true;
            });
        }
        var disc = $id('disc-form');
        if (disc) {
            disc.addEventListener('submit', function (e) {
                var n = disc.querySelectorAll('input[name="ids[]"]:checked').length;
                if (!n) { e.preventDefault(); alert('Centang dulu barang yang mau di-discontinue.'); return; }
                if (!confirm('Discontinue ' + n + ' barang yang tidak ada di toko? Barangnya tidak muncul lagi di Perputaran Barang & Reorder (bisa diaktifkan kembali).')) {
                    e.preventDefault();
                    return;
                }
                disc.querySelector('button').disabled = true;
            });
        }
        return;
    }

    // ---------------------------------------------------------------- ajax
    function api(method, path, body) {
        return fetch(BASE + path, {
            method: method,
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': TOKEN, 'X-Requested-With': 'XMLHttpRequest' },
            body: body ? JSON.stringify(body) : undefined
        }).then(function (res) {
            return res.json().catch(function () { return {}; }).then(function (data) {
                if (!res.ok) {
                    var first = data.errors ? Object.values(data.errors)[0][0] : null;
                    throw new Error(first || data.message || 'Gagal menghubungi server (' + res.status + ').');
                }
                return data;
            });
        });
    }

    function showMsg(text, kind) {
        var el = $id('sdk-msg');
        el.className = 'sdk-msg' + (text ? ' ' + (kind || 'bad') : '');
        el.textContent = text || '';
    }

    // ------------------------------------------------------------- barang
    var current = null;   // barang yang sedang dihitung
    var source  = 'scan';

    function openItem(item, from) {
        current = item;
        source  = from;
        showMsg('');
        $id('result').className = 'sdk-result';
        $id('suggest').style.display = 'none';
        $id('it-name').textContent = item.name;
        $id('it-meta').textContent = item.code + (item.category ? ' · ' + item.category : '');
        $id('it-unit').textContent = item.unit;
        $id('it-system').textContent = num(item.system_stock);
        $id('it-system-unit').textContent = item.unit;
        $id('it-live').textContent = '';
        $id('it-live').className = 'sdk-live';
        var checked = $id('it-checked');
        if (item.checked !== null) {
            checked.style.display = 'block';
            checked.textContent = 'Barang ini sudah dicek di sidak ini (fisik ' + num(item.checked) + ' ' + item.unit + '). Menyimpan lagi akan mengganti hasilnya.';
        } else {
            checked.style.display = 'none';
        }
        $id('real-stock').value = '';
        $id('it-note').value = '';
        $id('pick-card').style.display = 'none';
        $id('count-card').style.display = 'block';
        $id('real-stock').focus();
    }

    function closeItem() {
        current = null;
        $id('count-card').style.display = 'none';
        $id('pick-card').style.display = 'block';
    }

    function lookupCode(code, from) {
        showMsg('Mencari barang…', 'info');
        return api('GET', '/barcode?code=' + encodeURIComponent(code))
            .then(function (item) { openItem(item, from); })
            .catch(function (e) { showMsg(e.message); });
    }

    $id('btn-cancel').addEventListener('click', closeItem);

    $id('btn-save').addEventListener('click', function () {
        var raw = $id('real-stock').value.trim();
        if (raw === '' || isNaN(Number(raw)) || Number(raw) < 0) {
            showMsg('Isi jumlah fisik dulu (0 kalau barangnya tidak ada).');
            $id('real-stock').focus();
            return;
        }
        var btn = this;
        btn.disabled = true;
        api('POST', '/cek', { good_id: current.good_id, real_stock: Number(raw), source: source, note: $id('it-note').value.trim() || null })
            .then(function (data) {
                var d = data.detail;
                applySaved(data);
                closeItem();
                showMsg('');
                $id('result').className = 'sdk-result ' + tone(d.difference);
                $id('res-big').textContent = d.difference === 0 ? 'Cocok' : (d.difference < 0 ? 'Kurang ' : 'Lebih ') + (d.difference === 0 ? '' : num(Math.abs(d.difference)) + ' ' + d.unit);
                $id('res-det').textContent = d.name + ' — sistem ' + num(d.system_stock) + ', fisik ' + num(d.real_stock) + ' ' + d.unit;
            })
            .catch(function (e) { showMsg(e.message); })
            .then(function () { btn.disabled = false; });
    });

    // selisih langsung terlihat saat jumlah fisik diketik
    $id('real-stock').addEventListener('input', function () {
        var live = $id('it-live');
        var raw = this.value.trim();
        if (!current || raw === '' || isNaN(Number(raw))) { live.textContent = ''; live.className = 'sdk-live'; return; }
        var diff = Math.round((Number(raw) - current.system_stock) * 1000) / 1000;
        live.className = 'sdk-live ' + tone(diff);
        live.textContent = diff === 0 ? 'Cocok dengan sistem' : (diff < 0 ? 'Kurang ' : 'Lebih ') + num(Math.abs(diff)) + ' ' + current.unit + ' dari sistem';
    });

    $id('real-stock').addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { $id('btn-save').click(); }
    });

    // --------------------------------------------------------- barang acak
    $id('btn-random').addEventListener('click', function () {
        var btn = this;
        stopScan();
        btn.disabled = true;
        showMsg('Memilih barang acak…', 'info');
        var category = $id('random-category').value;
        api('GET', '/acak' + (category ? '?category=' + encodeURIComponent(category) : ''))
            .then(function (item) { openItem(item, 'acak'); })
            .catch(function (e) { showMsg(e.message); })
            .then(function () { btn.disabled = false; });
    });

    // ------------------------------------------------- ketik barcode / nama
    function manualSearch() {
        var text = $id('manual').value.trim();
        if (!text) { return; }
        stopScan();
        // angka saja = barcode; selain itu cari nama
        if (/^[0-9]{6,}$/.test(text)) {
            lookupCode(text, 'scan').then(function () { if (current) { $id('manual').value = ''; } });
            return;
        }
        showMsg('Mencari barang…', 'info');
        api('GET', '/cari?q=' + encodeURIComponent(text))
            .then(function (data) {
                var box = $id('suggest');
                if (!data.goods.length) {
                    box.style.display = 'none';
                    showMsg('Tidak ada barang dengan nama atau kode "' + text + '".');
                    return;
                }
                showMsg('');
                box.innerHTML = data.goods.map(function (g) {
                    return '<button type="button" data-good="' + g.good_id + '">' + esc(g.name) + '<small>' + esc(g.code) + '</small></button>';
                }).join('');
                box.style.display = 'block';
            })
            .catch(function (e) { showMsg(e.message); });
    }

    $id('btn-manual').addEventListener('click', manualSearch);
    $id('manual').addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); manualSearch(); } });

    $id('suggest').addEventListener('click', function (e) {
        var btn = e.target.closest('button[data-good]');
        if (!btn) { return; }
        api('GET', '/barang/' + btn.getAttribute('data-good'))
            .then(function (item) { $id('manual').value = ''; openItem(item, 'cari'); })
            .catch(function (e) { showMsg(e.message); });
    });

    // ------------------------------------------------------ hapus dari list
    $id('list').addEventListener('click', function (e) {
        var btn = e.target.closest('button[data-del]');
        if (!btn) { return; }
        var id = Number(btn.getAttribute('data-del'));
        var d = details.filter(function (x) { return x.id === id; })[0];
        if (!d || !confirm('Hapus "' + d.name + '" dari hasil sidak ini?')) { return; }
        api('DELETE', '/cek/' + id)
            .then(function (data) {
                details = details.filter(function (x) { return x.id !== id; });
                summary = data.summary;
                renderSummary();
                renderList();
            })
            .catch(function (e) { showMsg(e.message); });
    });

    $id('finish-form').addEventListener('submit', function (e) {
        var belum = targets.filter(function (t) { return !targetDone(t); }).length;
        var text = details.length
            ? 'Selesaikan sidak ini? ' + details.length + ' barang sudah dicek' +
              (belum ? ', ' + belum + ' barang di daftar BELUM dicek' : '') + '. Setelah selesai, hasil tidak bisa diubah.'
            : 'Belum ada barang yang dicek. Sidak kosong ini akan dibuang. Lanjutkan?';
        if (!confirm(text)) { e.preventDefault(); }
    });

    // ------------------------------------------------ daftar tugas: aksi
    var taskList = $id('task-list');
    if (taskList) {
        function saveRow(goodId) {
            var inp = taskList.querySelector('input[data-qty="' + goodId + '"]');
            var btn = taskList.querySelector('button[data-save="' + goodId + '"]');
            if (!inp) { return; }
            var raw = inp.value.trim();
            if (raw === '' || isNaN(Number(raw)) || Number(raw) < 0) {
                showMsg('Isi jumlah fisik dulu (0 kalau barangnya tidak ada).');
                inp.focus();
                return;
            }
            if (btn) { btn.disabled = true; }
            inp.disabled = true;
            var pos = targets.map(function (t) { return t.good_id; }).indexOf(goodId);
            api('POST', '/cek', { good_id: goodId, real_stock: Number(raw), source: 'daftar' })
                .then(function (data) {
                    showMsg('');
                    applySaved(data);
                    // lanjut ke barang berikutnya yang belum dicek
                    var next = targets.slice(pos + 1).concat(targets.slice(0, pos)).filter(function (t) { return !targetDone(t); })[0];
                    var nextInp = next ? taskList.querySelector('input[data-qty="' + next.good_id + '"]') : null;
                    if (nextInp) { nextInp.focus(); }
                })
                .catch(function (e) {
                    showMsg(e.message);
                    inp.disabled = false;
                    if (btn) { btn.disabled = false; }
                    inp.focus();
                });
        }

        taskList.addEventListener('click', function (e) {
            var save = e.target.closest('button[data-save]');
            if (save) { saveRow(Number(save.getAttribute('data-save'))); return; }
            var open = e.target.closest('button[data-open]');
            if (open) {
                stopScan();
                showMsg('Memuat barang…', 'info');
                api('GET', '/barang/' + open.getAttribute('data-open'))
                    .then(function (item) { openItem(item, 'daftar'); window.scrollTo(0, $id('count-card').offsetTop - 10); })
                    .catch(function (e) { showMsg(e.message); });
            }
        });
        taskList.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && e.target.matches('input[data-qty]')) {
                e.preventDefault();
                saveRow(Number(e.target.getAttribute('data-qty')));
            }
        });

        $id('btn-wa').addEventListener('click', function () { copyText(waText(), this); });
    }

    // -------------------------------------------------------- scan barcode
    var scanner  = null;
    var scanning = false;
    var cameras  = null;          // daftar kamera HP, diisi setelah izin diberikan
    var CAM_KEY  = 'sidak-camera';

    function savedCamera() { try { return localStorage.getItem(CAM_KEY); } catch (e) { return null; } }
    function saveCamera(id) { try { localStorage.setItem(CAM_KEY, id); } catch (e) {} }

    function isBack(label) { return /back|rear|belakang|environment/i.test(label || ''); }

    /** Kamera belakang utama: yang tersimpan, kalau tidak ada kamera belakang pertama. */
    function pickCamera(list) {
        var saved = savedCamera();
        for (var i = 0; i < list.length; i++) { if (list[i].id === saved) { return saved; } }
        var back = list.filter(function (c) { return isBack(c.label); });
        // HP dengan banyak lensa: lensa utama biasanya berlabel "camera 0" / "camera2 0"
        var main = back.filter(function (c) { return /\b0\b/.test(c.label); })[0];
        if (main) { return main.id; }
        if (back.length) { return back[0].id; }
        return list.length ? list[list.length - 1].id : null;
    }

    function fillCameraSelect(selected) {
        var sel = $id('camera-select');
        sel.innerHTML = cameras.map(function (c, i) {
            var label = c.label || ('Kamera ' + (i + 1));
            return '<option value="' + esc(c.id) + '">' + esc(label) + (isBack(c.label) ? ' (belakang)' : '') + '</option>';
        }).join('');
        sel.value = selected;
        $id('camera-row').style.display = cameras.length > 1 ? 'block' : 'none';
    }

    function stopScan() {
        $id('scan-box').style.display = 'none';
        if (scanner && scanning) {
            scanning = false;
            return scanner.stop().catch(function () {});
        }
        return Promise.resolve();
    }

    function cameraError(err) {
        $id('scan-box').style.display = 'none';
        var denied = String(err).indexOf('NotAllowed') !== -1 || String(err).indexOf('Permission') !== -1;
        showMsg(denied
            ? 'Izin kamera ditolak. Izinkan kamera untuk situs ini di pengaturan browser, atau ketik barcode secara manual.'
            : 'Kamera tidak bisa dibuka. Ketik barcode secara manual di bawah.');
    }

    /** Nyalakan kamera tertentu dengan resolusi tinggi supaya garis barcode terbaca. */
    function runCamera(cameraId) {
        if (!scanner) {
            var F = Html5QrcodeSupportedFormats;
            scanner = new Html5Qrcode('sdk-reader', {
                formatsToSupport: [F.EAN_13, F.EAN_8, F.UPC_A, F.UPC_E, F.CODE_128, F.CODE_39, F.CODE_93, F.ITF, F.CODABAR, F.QR_CODE],
                experimentalFeatures: { useBarCodeDetectorIfSupported: true },
                verbose: false
            });
        }

        var video = { width: { ideal: 1920 }, height: { ideal: 1080 } };
        if (cameraId) { video.deviceId = { exact: cameraId }; } else { video.facingMode = { ideal: 'environment' }; }

        // Tanpa qrbox: seluruh gambar kamera dipindai, jadi barcode tidak harus
        // pas di dalam kotak kecil.
        return scanner.start(
            cameraId ? cameraId : { facingMode: 'environment' },
            { fps: 15, videoConstraints: video },
            function (code) {
                if (!scanning) { return; }
                if (navigator.vibrate) { navigator.vibrate(80); }
                stopScan().then(function () { lookupCode(code, 'scan'); });
            },
            function () {}
        ).then(function () {
            scanning = true;
            // Fokus terus-menerus + sedikit zoom: barcode kecil jadi tajam
            // tanpa HP harus ditempel ke barang.
            try {
                var caps = scanner.getRunningTrackCapabilities();
                var adv = {};
                if (caps.focusMode && caps.focusMode.indexOf('continuous') !== -1) { adv.focusMode = 'continuous'; }
                if (caps.zoom && caps.zoom.max >= 2) { adv.zoom = Math.max(caps.zoom.min || 1, 2); }
                if (Object.keys(adv).length) { scanner.applyVideoConstraints({ advanced: [adv] }).catch(function () {}); }
            } catch (e) {}
        });
    }

    function startScan() {
        if (typeof Html5Qrcode === 'undefined') {
            showMsg('Pemindai barcode gagal dimuat (cek koneksi internet). Ketik barcode secara manual di bawah.');
            return;
        }
        if (!window.isSecureContext || !navigator.mediaDevices) {
            showMsg('Kamera hanya bisa dipakai lewat alamat https. Ketik barcode secara manual di bawah.');
            return;
        }
        showMsg('');
        $id('scan-box').style.display = 'block';

        var ready = cameras
            ? Promise.resolve(cameras)
            : Html5Qrcode.getCameras().then(function (list) { cameras = list || []; return cameras; });

        ready.then(function (list) {
            var id = pickCamera(list);
            if (list.length) { fillCameraSelect(id); }
            return runCamera(id);
        }).catch(cameraError);
    }

    $id('camera-select').addEventListener('change', function () {
        var id = this.value;
        saveCamera(id);
        var stop = (scanner && scanning) ? (scanning = false, scanner.stop().catch(function () {})) : Promise.resolve();
        stop.then(function () { return runCamera(id); }).catch(cameraError);
    });

    $id('btn-scan').addEventListener('click', function () {
        if (scanning) { stopScan(); } else { startScan(); }
    });
    $id('btn-scan-stop').addEventListener('click', stopScan);
})();
</script>
@endsection
