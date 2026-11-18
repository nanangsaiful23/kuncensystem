@extends('layout.user', ['role' => 'admin', 'title' => 'Petakan Jenis', 'default' => ['page_name' => 'Petakan Jenis']])

@section('content')
@include('admin.sidak.style')
<style>
.sdk { max-width: 1000px; }
.pj-tabs { display: flex; gap: 6px; margin-bottom: 10px; flex-wrap: wrap; }
.pj-tabs a { padding: 8px 14px; border-radius: 8px; border: 1px solid var(--bdr); color: var(--ink); background: var(--card); font-weight: 600; }
.pj-tabs a.on { background: var(--blue); border-color: var(--blue); color: #fff; }
.pj-filter { display: grid; grid-template-columns: 2fr 1.4fr auto auto; gap: 8px; align-items: center; }
.pj-bar { position: sticky; top: 0; z-index: 5; background: var(--blue-bg); border: 1px solid var(--blue);
          border-radius: 10px; padding: 10px; margin: 10px 0; display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
.pj-bar .sdk-input { flex: 1; min-width: 200px; margin: 0; }
.pj-table { width: 100%; border-collapse: collapse; font-size: 14px; background: var(--card); }
.pj-table td, .pj-table th { padding: 6px 8px; border-top: 1px solid var(--bdr); vertical-align: middle; }
.pj-table th { font-size: 12px; color: var(--ink2); text-align: left; }
.pj-table tr.on { background: var(--ok-bg); }
.pj-table input[type=checkbox] { width: 20px; height: 20px; }
.pj-table .sdk-input { margin: 0; width: 100%; min-width: 160px; }
.pj-table .salah { border-color: var(--bad); background: var(--bad-bg); }
.pj-small { font-size: 12px; color: var(--ink2); }
.pj-pages { display: flex; gap: 6px; flex-wrap: wrap; margin: 10px 0; }
.pj-pages a { padding: 4px 10px; border: 1px solid var(--bdr); border-radius: 6px; }
.pj-pages a.on { background: var(--blue); color: #fff; border-color: var(--blue); }
@media (max-width: 700px) { .pj-filter { grid-template-columns: 1fr 1fr; } }
</style>

<div class="content-wrapper">
<div class="sdk">
    <h1>Petakan Jenis</h1>
    <p class="sdk-sub">Cari kata (mis. <b>ember</b>), centang barangnya, pilih jenis, lalu Terapkan. Kategori barang otomatis ikut kategori induk jenis.
        Sebelum klik pertama setiap hari, data nama/jenis/kategori semua barang dibackup otomatis
        (bisa dipulihkan di <a href="{{ route('admin.penataan-kategori.index') }}">Penataan Kategori</a>).</p>

    @if($errors->any())
        <div class="sdk-msg bad">{{ $errors->first() }}</div>
    @endif
    @if($hasil = session('hasil'))
        <div class="sdk-msg {{ count($hasil['dilewati']) ? 'bad' : 'info' }}">
            {{ $hasil['diubah'] }} barang dipetakan, {{ $hasil['nama_diubah'] }} nama dirapikan.
            Backup: {{ $hasil['backup'] }}.
            @if($hasil['dilewati'])
                {{ count($hasil['dilewati']) }} dilewati: {{ implode('; ', array_slice($hasil['dilewati'], 0, 10)) }}
            @endif
        </div>
    @endif

    @php $q = fn ($extra) => route('admin.penataan-kategori.klik', array_merge(
            ['mode' => $mode, 'kata' => $kata, 'kategori' => $kategori, 'berstok' => $berstok ? 1 : null], $extra)); @endphp

    <div class="pj-tabs">
        <a class="{{ $mode === 'tidak' ? 'on' : '' }}" href="{{ $q(['mode' => 'tidak', 'hal' => 1]) }}">Belum ada jenis ({{ number_format($jumlahTidak, 0, ',', '.') }})</a>
        <a class="{{ $mode === 'usulan' ? 'on' : '' }}" href="{{ $q(['mode' => 'usulan', 'hal' => 1]) }}">Punya usulan, belum diterapkan ({{ number_format($jumlahUsulan, 0, ',', '.') }})</a>
    </div>

    <form method="GET" action="{{ route('admin.penataan-kategori.klik') }}" class="sdk-card pj-filter">
        <input type="hidden" name="mode" value="{{ $mode }}">
        <input class="sdk-input" type="search" name="kata" value="{{ $kata }}" placeholder="Cari kata di nama barang" autofocus>
        <select class="sdk-input" name="kategori">
            <option value="">Semua kategori lama</option>
            @foreach($kategoriLama as $id => $nama)
                <option value="{{ $id }}" {{ (string) $id === $kategori ? 'selected' : '' }}>{{ $nama }}</option>
            @endforeach
        </select>
        <label class="pj-small"><input type="checkbox" name="berstok" value="1" {{ $berstok ? 'checked' : '' }}> Berstok saja</label>
        <button class="sdk-btn sdk-btn-prim" type="submit"><i class="fa fa-search"></i> Cari</button>
    </form>

    <datalist id="daftar-jenis">
        @foreach($jenis as $j)
            <option value="{{ $j['name'] }}">{{ $j['kategori'] }}</option>
        @endforeach
    </datalist>

    <form method="POST" action="{{ route('admin.penataan-kategori.klik.simpan') }}" id="form-klik">
        {{ csrf_field() }}
        <input type="hidden" name="data" id="data">

        <div class="pj-bar">
            <input class="sdk-input" list="daftar-jenis" id="jenis-massal" placeholder="Ketik / pilih jenis untuk yang dicentang">
            <button type="button" class="sdk-btn sdk-btn-sm" id="isi-massal">Isi ke yang dicentang</button>
            <label class="pj-small"><input type="checkbox" name="buang_awalan" value="1" checked> Rapikan nama (buang kata jenis di awal)</label>
            <button type="submit" class="sdk-btn sdk-btn-ok"><i class="fa fa-check"></i> Terapkan <span id="jml">0</span> barang</button>
        </div>

        <div class="pj-small" style="margin-bottom:6px">{{ number_format($total, 0, ',', '.') }} barang ditemukan
            @if($jumlahHal > 1) &middot; halaman {{ $hal }} dari {{ $jumlahHal }} @endif</div>

        @if($rows->isEmpty())
            <div class="sdk-empty">Tidak ada barang.</div>
        @else
        <div style="overflow-x:auto">
        <table class="pj-table">
            <tr>
                <th><input type="checkbox" id="semua" title="Centang semua di halaman ini"></th>
                <th>Barang</th>
                <th>Jenis lama</th>
                <th style="width:35%">Jenis baru</th>
            </tr>
            @foreach($rows as $r)
                @php
                    $awal = $mode === 'usulan' ? $r['jenis baru'] : '';
                @endphp
                <tr data-id="{{ $r['id'] }}" data-nama="{{ $r['nama'] }}" data-kat="{{ $r['kategori_lama_id'] }}"
                    data-jenis-lama-id="{{ $r['jenis_lama_id'] }}" data-jenis-lama="{{ $r['jenis lama'] }}">
                    <td><input type="checkbox" class="cek"></td>
                    <td>
                        {{ $r['nama'] }}
                        <div class="pj-small">stok {{ $r['stok'] }} &middot; {{ $r['kategori lama'] }}
                            @if($mode === 'tidak' && $r['usulan jenis baru']) &middot; usulan jenis belum dibuat: <b>{{ $r['usulan jenis baru'] }}</b>@endif
                            @if($mode === 'usulan') &middot; {{ $r['alasan'] }}@endif
                        </div>
                    </td>
                    <td class="pj-small">{{ $r['jenis lama'] ?: '-' }}</td>
                    <td><input class="sdk-input jenis" list="daftar-jenis" value="{{ $awal }}" placeholder="pilih jenis"></td>
                </tr>
            @endforeach
        </table>
        </div>
        @endif

        @if($jumlahHal > 1)
            <div class="pj-pages">
                @for($i = 1; $i <= $jumlahHal; $i++)
                    <a class="{{ $i === $hal ? 'on' : '' }}" href="{{ $q(['hal' => $i]) }}">{{ $i }}</a>
                @endfor
            </div>
        @endif
    </form>
</div>
</div>

<script>
(function () {
    var valid = {};
    document.querySelectorAll('#daftar-jenis option').forEach(function (o) { valid[o.value.toLowerCase()] = o.value; });
    var form = document.getElementById('form-klik');
    var rows = Array.prototype.slice.call(form.querySelectorAll('tr[data-id]'));

    function hitung() {
        var n = 0;
        rows.forEach(function (tr) {
            var on = tr.querySelector('.cek').checked;
            tr.classList.toggle('on', on);
            if (on) n++;
        });
        document.getElementById('jml').textContent = n;
    }
    function cekJenis(inp) {
        var v = inp.value.trim();
        inp.classList.toggle('salah', v !== '' && !valid[v.toLowerCase()]);
    }

    rows.forEach(function (tr) {
        var inp = tr.querySelector('.jenis');
        tr.querySelector('.cek').addEventListener('change', hitung);
        inp.addEventListener('input', function () {
            cekJenis(inp);
            if (inp.value.trim() !== '') tr.querySelector('.cek').checked = true;
            hitung();
        });
        cekJenis(inp);
    });

    var semua = document.getElementById('semua');
    if (semua) semua.addEventListener('change', function () {
        rows.forEach(function (tr) { tr.querySelector('.cek').checked = semua.checked; });
        hitung();
    });

    document.getElementById('isi-massal').addEventListener('click', function () {
        var v = document.getElementById('jenis-massal').value.trim();
        if (!valid[v.toLowerCase()]) { alert('Jenis "' + v + '" tidak ada di daftar.'); return; }
        rows.forEach(function (tr) {
            if (tr.querySelector('.cek').checked) {
                var inp = tr.querySelector('.jenis');
                inp.value = valid[v.toLowerCase()];
                cekJenis(inp);
            }
        });
    });

    form.addEventListener('submit', function (e) {
        var data = [], kosong = 0, salah = 0;
        rows.forEach(function (tr) {
            if (!tr.querySelector('.cek').checked) return;
            var v = tr.querySelector('.jenis').value.trim();
            if (v === '') { kosong++; return; }
            if (!valid[v.toLowerCase()]) { salah++; return; }
            data.push({ id: tr.dataset.id, nama: tr.dataset.nama, jenis: valid[v.toLowerCase()],
                        jenis_lama: tr.dataset.jenisLama, kategori_lama_id: tr.dataset.kat, jenis_lama_id: tr.dataset.jenisLamaId });
        });
        if (salah) { e.preventDefault(); alert(salah + ' barang dicentang dengan jenis yang tidak ada (kotak merah).'); return; }
        if (!data.length) { e.preventDefault(); alert('Centang barang dan isi jenisnya dulu.'); return; }
        var pesan = 'Terapkan jenis ke ' + data.length + ' barang?' + (kosong ? '\n(' + kosong + ' barang dicentang tanpa jenis akan dilewati)' : '');
        if (!confirm(pesan)) { e.preventDefault(); return; }
        document.getElementById('data').value = JSON.stringify(data);
    });
    hitung();
})();
</script>
@endsection
