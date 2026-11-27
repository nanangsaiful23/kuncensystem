@extends('layout.user', ['role' => 'admin', 'title' => 'Atur Musim'])

@section('content')
@include('admin.season._style')

<div class="content-wrapper">
<section class="content">
<div class="ms-wrap">

<div class="ms-head">
    <div>
        <a href="{{ route('admin.reports.movement') }}" class="ms-back">← Perputaran Barang</a>
        <h1>🗓️ Atur Musim</h1>
        <p>Barang musiman di luar musimnya tidak dicap Slow/Dead dan tidak disarankan order.</p>
    </div>
</div>

@if(session('success'))<div class="ms-alert ms-ok">✅ {{ session('success') }}</div>@endif
@if(session('error'))<div class="ms-alert ms-err">❌ {{ session('error') }}</div>@endif
@if($errors->any())<div class="ms-alert ms-err">❌ {{ $errors->first() }}</div>@endif

@if(!$tabelAda)
<div class="ms-warn">⚠️ Tabel musim belum bisa dibuat otomatis. Jalankan <code>docs/sql/2026-11-27-obral-dan-musim.sql</code> di phpMyAdmin, lalu buka ulang halaman ini.</div>
@else

<div class="ms-info">
    <strong>Cara kerja:</strong>
    <br>1. Buat musim beserta tanggalnya. Musim yang tanggalnya tetap tiap tahun (mis. Tahun Ajaran Baru, Natal & Tahun Baru,
    Musim Hujan) cukup diisi sekali lalu centang <em>berulang tiap tahun</em>. Musim yang bergeser (Ramadan & Lebaran, Idul Adha, Imlek)
    diisi tanggalnya setiap tahun — isi juga tanggal tahun lalu supaya sistem tahu penjualan musim lalu.
    <br>2. Pilih <strong>jenis barang</strong> yang termasuk musim itu (semua barang di jenis itu ikut). Pengecualian per barang
    bisa diatur dari menu Perputaran Barang (klik <em>🗓️ atur musim</em> di nama barang).
    <br>3. <em>Di luar musim</em> → status "Musiman", saran "Simpan, jangan order". <em>Menjelang musim</em> (sesuai hari persiapan)
    → saran "Siapkan Stok Musim" sebanyak penjualan musim lalu, juga di Rekomendasi Reorder.
</div>

@if($jenisTanpaMusim->isNotEmpty())
<div class="ms-warn">
    📌 <strong>{{ $jenisTanpaMusim->count() }} jenis</strong> ditandai "musiman" di Penataan Kategori tapi belum dihubungkan ke musim:
    {{ $jenisTanpaMusim->take(15)->map(fn ($t) => $t->name)->implode(', ') }}{{ $jenisTanpaMusim->count() > 15 ? ', …' : '' }}.
    Buka musimnya lalu centang jenis-jenis ini.
</div>
@endif

<div class="ms-card">
    <div class="ms-card-hd">📋 Daftar Musim ({{ $seasons->count() }})</div>
    <div class="ms-tbl-wrap">
    <table class="ms-tbl">
        <thead><tr>
            <th>Musim</th><th>Tanggal</th><th>Status hari ini</th><th class="tr">Jenis</th><th class="tr">Barang aktif</th><th></th>
        </tr></thead>
        <tbody>
        @forelse($seasons as $season)
        @php $st = $status[$season->id]; @endphp
        <tr>
            <td>
                <div style="font-weight:700">{{ $season->name }}</div>
                <div class="ms-muted">{{ $season->repeats_yearly ? 'Berulang tiap tahun' : 'Tanggal diisi per tahun' }} · persiapan {{ $season->prep_days }} hari</div>
            </td>
            <td>
                @forelse($season->periods as $p)
                    <div>{{ $p->start_date->isoFormat('D MMM Y') }} – {{ $p->end_date->isoFormat('D MMM Y') }}</div>
                @empty
                    <span class="ms-muted">belum ada</span>
                @endforelse
            </td>
            <td><span class="ms-badge ms-b-{{ $st['status'] }}">{{ $st['label'] }}</span></td>
            <td class="tr">{{ number_format($jumlahJenis[$season->id] ?? 0) }}</td>
            <td class="tr">{{ number_format($jumlahBarang[$season->id] ?? 0) }}</td>
            <td><a href="{{ route('admin.seasons.edit', $season->id) }}" class="ms-btn ms-btn-sm">Atur</a></td>
        </tr>
        @empty
        <tr><td colspan="6" class="ms-muted" style="text-align:center;padding:1.5rem">Belum ada musim. Tambahkan di bawah.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
</div>

<div class="ms-card">
    <div class="ms-card-hd">➕ Tambah Musim</div>
    <div class="ms-card-bd">
        <form method="POST" action="{{ route('admin.seasons.store') }}" class="ms-form">
            @csrf
            <div class="ms-fg">
                <label for="msName">Nama musim</label>
                <input type="text" id="msName" name="name" list="msSaran" value="{{ old('name') }}" required maxlength="100" placeholder="mis. Ramadan & Lebaran">
                <datalist id="msSaran">@foreach($saran as $nm)<option value="{{ $nm }}">@endforeach</datalist>
            </div>
            <div class="ms-fg">
                <label for="msStart">Mulai</label>
                <input type="date" id="msStart" name="start_date" value="{{ old('start_date') }}" required>
            </div>
            <div class="ms-fg">
                <label for="msEnd">Selesai</label>
                <input type="date" id="msEnd" name="end_date" value="{{ old('end_date') }}" required>
            </div>
            <div class="ms-fg">
                <label for="msPrep">Persiapan (hari sebelum)</label>
                <input type="number" id="msPrep" name="prep_days" value="{{ old('prep_days', 30) }}" min="0" max="180" style="width:110px">
            </div>
            <div class="ms-fg">
                <label>&nbsp;</label>
                <label class="ms-chk"><input type="checkbox" name="repeats_yearly" value="1" {{ old('repeats_yearly') ? 'checked' : '' }}> Berulang tiap tahun (tanggal tetap)</label>
            </div>
            <div class="ms-fg">
                <label for="msNote">Catatan (opsional)</label>
                <textarea id="msNote" name="note" maxlength="500">{{ old('note') }}</textarea>
            </div>
            <button type="submit" class="ms-btn">Simpan & pilih jenis →</button>
        </form>
    </div>
</div>
@endif

</div>
</section>
</div>
@endsection
