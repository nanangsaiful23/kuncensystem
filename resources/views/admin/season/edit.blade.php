@extends('layout.user', ['role' => 'admin', 'title' => 'Atur Musim: ' . $season->name])

@section('content')
@include('admin.season._style')

<div class="content-wrapper">
<section class="content">
<div class="ms-wrap">

<div class="ms-head">
    <div>
        <a href="{{ route('admin.seasons.index') }}" class="ms-back">← Daftar Musim</a>
        <h1>🗓️ {{ $season->name }}</h1>
        <p><span class="ms-badge ms-b-{{ $status['status'] }}">{{ $status['label'] }}</span></p>
    </div>
    <form method="POST" action="{{ route('admin.seasons.destroy', $season->id) }}"
          onsubmit="return confirm('Hapus musim {{ addslashes($season->name) }}? Jenis & barangnya tidak lagi dianggap musiman.')">
        @csrf
        <button type="submit" class="ms-btn ms-btn-danger">🗑️ Hapus musim</button>
    </form>
</div>

@if(session('success'))<div class="ms-alert ms-ok">✅ {{ session('success') }}</div>@endif
@if(session('error'))<div class="ms-alert ms-err">❌ {{ session('error') }}</div>@endif
@if($errors->any())<div class="ms-alert ms-err">❌ {{ $errors->first() }}</div>@endif

{{-- ── Tanggal musim ───────────────────────────────────────────── --}}
<div class="ms-card">
    <div class="ms-card-hd">📅 Tanggal Musim</div>
    <div class="ms-card-bd">
        @if($season->repeats_yearly)
        <p class="ms-muted" style="margin-top:0">Berulang tiap tahun: tanggal & bulannya dipakai setiap tahun (tahunnya tidak berpengaruh).</p>
        @else
        <p class="ms-muted" style="margin-top:0">Tanggal diisi per tahun. Isi juga musim tahun lalu supaya saran "Siapkan Stok Musim" tahu penjualan musim lalu.</p>
        @endif
        @forelse($season->periods as $p)
        <div class="ms-period">
            <strong>{{ $p->start_date->isoFormat('dddd, D MMM Y') }} – {{ $p->end_date->isoFormat('dddd, D MMM Y') }}</strong>
            <span class="ms-muted">({{ (int) round($p->start_date->diffInDays($p->end_date)) + 1 }} hari)</span>
            <form method="POST" action="{{ route('admin.seasons.period.delete', $p->id) }}" onsubmit="return confirm('Hapus tanggal ini?')" style="display:inline">
                @csrf
                <button type="submit" class="ms-btn ms-btn-sm ms-btn-danger">Hapus</button>
            </form>
        </div>
        @empty
        <p class="ms-muted">Belum ada tanggal — musim ini belum berpengaruh ke laporan.</p>
        @endforelse

        <form method="POST" action="{{ route('admin.seasons.period.add', $season->id) }}" class="ms-form" style="margin-top:.75rem">
            @csrf
            <div class="ms-fg"><label>Mulai</label><input type="date" name="start_date" required></div>
            <div class="ms-fg"><label>Selesai</label><input type="date" name="end_date" required></div>
            <button type="submit" class="ms-btn ms-btn-ghost">➕ Tambah tanggal</button>
        </form>
    </div>
</div>

{{-- ── Data musim + jenis barang ───────────────────────────────── --}}
<form method="POST" action="{{ route('admin.seasons.update', $season->id) }}">
@csrf
<div class="ms-card">
    <div class="ms-card-hd">⚙️ Pengaturan</div>
    <div class="ms-card-bd">
        <div class="ms-form">
            <div class="ms-fg">
                <label>Nama musim</label>
                <input type="text" name="name" value="{{ old('name', $season->name) }}" required maxlength="100">
            </div>
            <div class="ms-fg">
                <label>Persiapan (hari sebelum)</label>
                <input type="number" name="prep_days" value="{{ old('prep_days', $season->prep_days) }}" min="0" max="180" style="width:110px">
            </div>
            <div class="ms-fg">
                <label>&nbsp;</label>
                <label class="ms-chk"><input type="checkbox" name="repeats_yearly" value="1" {{ $season->repeats_yearly ? 'checked' : '' }}> Berulang tiap tahun (tanggal tetap)</label>
            </div>
            <div class="ms-fg">
                <label>Catatan</label>
                <textarea name="note" maxlength="500">{{ old('note', $season->note) }}</textarea>
            </div>
        </div>
    </div>
</div>

<div class="ms-card">
    <div class="ms-card-hd">
        <span>🏷️ Jenis Barang di Musim Ini (<span id="msTypeCount">0</span> dipilih)</span>
        <span class="ms-muted">Jenis bertanda <span class="ms-tag-musiman">musiman</span> = ditandai musiman di Penataan Kategori</span>
    </div>
    <div class="ms-card-bd">
        <input type="text" class="ms-search" id="msTypeSearch" placeholder="Cari jenis atau kategori…" oninput="msFilterTypes(this.value)">
        <div class="ms-types">
            @foreach($types as $t)
            @php $mine = (int) $t->season_id === $season->id; @endphp
            <label class="ms-type-row{{ $mine ? ' is-mine' : '' }}" data-search="{{ strtolower($t->name . ' ' . $t->kategori) }}">
                <input type="checkbox" name="type_ids[]" value="{{ $t->id }}" {{ $mine ? 'checked' : '' }} onchange="msCountTypes()">
                <span>
                    <span class="ms-type-name">{{ $t->name }}</span>
                    @if($t->tipe_kontrol === 'musiman')<span class="ms-tag-musiman">musiman</span>@endif
                    @if(!$mine && $t->musim_lain)<span class="ms-tag-other" title="Mencentang akan memindahkan jenis ini ke musim {{ $season->name }}">sekarang: {{ $t->musim_lain }}</span>@endif
                    <br><span class="ms-muted">{{ $t->kategori ?? 'Tanpa kategori' }} · {{ number_format($jumlahBarangJenis[$t->id] ?? 0) }} barang aktif</span>
                </span>
            </label>
            @endforeach
        </div>
    </div>
    <div class="ms-sticky-save">
        <button type="submit" class="ms-btn">💾 Simpan pengaturan & jenis</button>
        <span class="ms-muted">Satu jenis hanya bisa masuk satu musim.</span>
    </div>
</div>
</form>

{{-- ── Pengecualian per barang ─────────────────────────────────── --}}
<div class="ms-card">
    <div class="ms-card-hd">🎯 Diatur Per Barang</div>
    <div class="ms-card-bd">
        <p class="ms-muted" style="margin-top:0">Atur dari menu Perputaran Barang: klik <em>🗓️ atur musim</em> di nama barang, pilih "Barang ini saja".</p>
        <strong>Masuk musim ini (walau jenisnya tidak):</strong>
        @forelse($barangLangsung as $b)
        <div class="ms-period">
            {{ \App\Models\Good::formatName($b->tipe, $b->name) }} <span class="ms-muted">{{ $b->code }}</span>
            <form method="POST" action="{{ route('admin.seasons.good.reset', $b->id) }}" style="display:inline">@csrf
                <button type="submit" class="ms-btn ms-btn-sm ms-btn-ghost">Ikut jenis lagi</button></form>
        </div>
        @empty
        <div class="ms-muted">— tidak ada —</div>
        @endforelse

        <div style="margin-top:.75rem"><strong>Dikecualikan (bukan musiman, walau jenisnya di musim ini):</strong></div>
        @forelse($barangDikecualikan as $b)
        <div class="ms-period">
            {{ \App\Models\Good::formatName($b->tipe, $b->name) }} <span class="ms-muted">{{ $b->code }}</span>
            <form method="POST" action="{{ route('admin.seasons.good.reset', $b->id) }}" style="display:inline">@csrf
                <button type="submit" class="ms-btn ms-btn-sm ms-btn-ghost">Ikut jenis lagi</button></form>
        </div>
        @empty
        <div class="ms-muted">— tidak ada —</div>
        @endforelse
    </div>
</div>

</div>
</section>
</div>

<script>
function msFilterTypes(q) {
    q = (q || '').toLowerCase().trim();
    document.querySelectorAll('.ms-type-row').forEach(function (r) {
        r.style.display = !q || r.getAttribute('data-search').indexOf(q) !== -1 ? '' : 'none';
    });
}
function msCountTypes() {
    document.getElementById('msTypeCount').textContent = document.querySelectorAll('.ms-type-row input:checked').length;
}
document.addEventListener('DOMContentLoaded', msCountTypes);
</script>
@endsection
