@extends('layout.user', ['role' => 'admin', 'title' => 'Rekomendasi Order ke Distributor'])

@section('content')
@php
    $groups        = $groups        ?? collect();
    $summary       = $summary       ?? [];
    $distributors  = $distributors  ?? collect();
    $categories    = $categories    ?? collect();
    $startDate     = $startDate     ?? date('Y-m-d', strtotime('-90 days'));
    $endDate       = $endDate       ?? date('Y-m-d');
    $distributorId = $distributorId ?? null;
    $kategori      = $kategori      ?? null;
    $onlyNeeded    = $onlyNeeded    ?? true;
    $s = array_merge([
        'total_item'=>0,'total_distributor'=>0,'total_biaya'=>0,
        'urgent_count'=>0,'soon_count'=>0,'days'=>0,
    ], $summary);
@endphp

<style>
.ro-wrap { padding: 1.25rem 1.5rem 2.5rem; font-family: 'Segoe UI', system-ui, sans-serif;
           font-size: 16px; max-width: 1440px; margin: 0 auto; }
.ro-wrap *, .ro-wrap *::before, .ro-wrap *::after { box-sizing: border-box; }

.ro-wrap {
    --ink:    #0f172a; --ink2: #475569; --ink3: #94a3b8;
    --surf:   #f8fafc; --card: #fff;   --bdr:  #e2e8f0; --bdr2: #f1f5f9;
    --urgent: #dc2626; --urgent-bg:#fef2f2; --urgent-b:#fecaca;
    --soon:   #d97706; --soon-bg:#fffbeb;   --soon-b:#fde68a;
    --safe:   #16a34a; --safe-bg:#f0fdf4;   --safe-b:#bbf7d0;
    --blue:   #2563eb; --blue-bg:#eff6ff;   --blue-b:#bfdbfe;
}

.ro-head { display:flex; justify-content:space-between; align-items:flex-start;
           flex-wrap:wrap; gap:1rem; margin-bottom:1.5rem; }
.ro-head h1 { font-size:1.85rem; font-weight:800; color:var(--ink); margin:0; line-height:1.25; }
.ro-head p  { font-size:1.05rem; color:var(--ink2); margin:.35rem 0 0; }
.ro-badge   { background:var(--blue-bg); border:1px solid var(--blue-b); color:var(--blue);
              font-size:.95rem; font-weight:600; padding:.5rem 1.1rem;
              border-radius:9999px; white-space:nowrap; align-self:flex-start; }

.ro-info { background:var(--blue-bg); border:1px solid var(--blue-b); color:#1e3a8a;
           border-radius:.6rem; padding:1rem 1.25rem; margin-bottom:1.25rem;
           font-size:1.02rem; line-height:1.65; }
.ro-info strong { color:#1e3a8a; }

.ro-filter { background:var(--card); border:1px solid var(--bdr); border-radius:.75rem;
             padding:1.1rem 1.25rem; margin-bottom:1.25rem;
             display:flex; align-items:flex-end; gap:1rem; flex-wrap:wrap; }
.ro-fg { display:flex; flex-direction:column; gap:.35rem; }
.ro-fg label { font-size:.8rem; font-weight:700; color:var(--ink3);
               text-transform:uppercase; letter-spacing:.07em; }
.ro-fg input, .ro-fg select { padding:.6rem .85rem; border:1px solid var(--bdr);
    border-radius:.5rem; font-size:1rem; color:var(--ink); background:var(--surf);
    min-width:170px; line-height:1.4; }
.ro-fg input:focus, .ro-fg select:focus { outline:none; border-color:var(--blue); }
.ro-fg-chk { display:flex; align-items:center; gap:.5rem; padding-bottom:.6rem; }
.ro-fg-chk input[type="checkbox"] { width:1.15rem; height:1.15rem; cursor:pointer; }
.ro-fg-chk label { text-transform:none; font-size:1rem; font-weight:500; color:var(--ink2); cursor:pointer; }
.ro-btn-prim { padding:.65rem 1.4rem; background:var(--blue); color:#fff; border:none;
               border-radius:.5rem; font-size:1rem; font-weight:600; cursor:pointer;
               align-self:flex-end; white-space:nowrap; }
.ro-btn-prim:hover { background:#1d4ed8; }
.ro-btn-reset { padding:.65rem 1.1rem; background:var(--surf); color:var(--ink2);
                border:1px solid var(--bdr); border-radius:.5rem; font-size:1rem;
                cursor:pointer; align-self:flex-end; text-decoration:none;
                display:inline-block; white-space:nowrap; }
.ro-btn-reset:hover { background:#eef2f7; }

.ro-kpi-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(190px,1fr));
               gap:1rem; margin-bottom:1.5rem; }
.ro-kpi { background:var(--card); border:1px solid var(--bdr);
          border-radius:.75rem; padding:1.1rem 1.25rem; }
.ro-kpi .kl { font-size:.82rem; font-weight:700; color:var(--ink3);
              text-transform:uppercase; letter-spacing:.06em; margin-bottom:.45rem; }
.ro-kpi .kv { font-size:2rem; font-weight:800; color:var(--ink); line-height:1; }
.ro-kpi .ks { font-size:.88rem; color:var(--ink3); margin-top:.35rem; }
.ro-kpi.k-urgent { border-left:5px solid var(--urgent); } .ro-kpi.k-urgent .kv{color:var(--urgent);}
.ro-kpi.k-soon   { border-left:5px solid var(--soon); }   .ro-kpi.k-soon .kv{color:var(--soon);}
.ro-kpi.k-blue   { border-left:5px solid var(--blue); }   .ro-kpi.k-blue .kv{color:var(--blue);}

.ro-group { background:var(--card); border:1px solid var(--bdr); border-radius:.75rem;
            margin-bottom:1.25rem; overflow:hidden; }
.ro-group-hd { padding:1.1rem 1.25rem; background:var(--surf); border-bottom:1px solid var(--bdr);
               display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:.6rem;
               cursor:pointer; user-select:none; transition:background .12s; }
.ro-group-hd:hover { background:#eef2f7; }
.ro-group.collapsed .ro-group-hd { border-bottom-color:transparent; }
.ro-group-title { display:flex; align-items:center; gap:.65rem; min-width:0; }
.ro-group-chevron { display:inline-flex; align-items:center; justify-content:center;
    width:1.6rem; height:1.6rem; border-radius:9999px; background:#fff; border:1px solid var(--bdr);
    color:var(--ink2); font-size:.85rem; flex-shrink:0; transition:transform .18s ease; }
.ro-group.collapsed .ro-group-chevron { transform:rotate(-90deg); }
.ro-group-hd h2 { font-size:1.25rem; font-weight:700; color:var(--ink); margin:0;
                  display:flex; align-items:center; gap:.65rem; min-width:0; }
.ro-group-body { max-height:none; overflow:hidden; }
.ro-group.collapsed .ro-group-body { display:none; }
.ro-group-meta { font-size:.98rem; color:var(--ink2); display:flex; gap:1.25rem; flex-wrap:wrap; }
.ro-group-meta b { color:var(--ink); }
.ro-urgent-pill { background:var(--urgent-bg); color:var(--urgent); border:1px solid var(--urgent-b);
                  font-size:.85rem; font-weight:700; padding:.25rem .75rem; border-radius:9999px; }

.ro-tbl-wrap { overflow-x:auto; }
.ro-tbl-wrap table { width:100%; border-collapse:collapse; font-size:1rem; min-width:1000px; }
.ro-tbl-wrap thead th { background:#fff; padding:.85rem 1rem; text-align:left;
    font-weight:700; color:var(--ink2); border-bottom:2px solid var(--bdr);
    white-space:nowrap; font-size:.85rem; text-transform:uppercase; letter-spacing:.02em; }
.ro-tbl-wrap tbody td { padding:.95rem 1rem; border-bottom:1px solid var(--bdr2);
                        color:var(--ink); vertical-align:middle; font-size:1rem; }
.ro-tbl-wrap tbody tr:last-child td { border-bottom:none; }
.ro-tbl-wrap tbody tr:hover td { background:#f8fafc; }
.ro-tbl-wrap tbody tr.row-urgent td { background:#fff9f9; }
.ro-tbl-wrap tbody tr.row-urgent:hover td { background:#fee2e2; }
.tr { text-align:right; } .tc { text-align:center; }

.ro-badge-tag { display:inline-flex; align-items:center; gap:.3rem; padding:.3rem .7rem;
                border-radius:9999px; font-size:.88rem; font-weight:700; white-space:nowrap; }
.b-urgent { background:var(--urgent-bg); color:var(--urgent); border:1px solid var(--urgent-b); }
.b-soon   { background:var(--soon-bg);   color:var(--soon);   border:1px solid var(--soon-b); }
.b-safe   { background:var(--safe-bg);   color:var(--safe);   border:1px solid var(--safe-b); }

.ro-stock-line { font-size:.85rem; color:var(--ink3); margin-top:.25rem; }
.ro-item-name { font-weight:600; font-size:1.05rem; }

.ro-empty { text-align:center; padding:4rem 1.5rem; color:var(--ink3); }
.ro-empty .ei { font-size:3rem; margin-bottom:.75rem; }
.ro-empty h3 { color:var(--ink2); font-size:1.25rem; margin:0 0 .35rem; }
.ro-empty p { font-size:1rem; }

[data-ro-tip] { position:relative; cursor:help; border-bottom:1px dashed var(--ink3); }
[data-ro-tip]:hover::after { content:attr(data-ro-tip); position:absolute; bottom:135%;
    left:0; background:var(--ink); color:#fff; font-size:.82rem; padding:.45rem .7rem;
    border-radius:.4rem; white-space:normal; width:250px; z-index:999; pointer-events:none;
    line-height:1.5; }

/* ── Desktop besar: beri sedikit lagi keleluasaan ───────────────────────── */
@media (min-width: 1400px) {
    .ro-wrap { font-size: 17px; }
    .ro-head h1 { font-size: 2rem; }
    .ro-kpi .kv { font-size: 2.15rem; }
}

/* ── Tablet ──────────────────────────────────────────────────────────────── */
@media (max-width: 1024px) {
    .ro-kpi-grid { grid-template-columns:repeat(2,1fr); }
}

/* ── Mobile ──────────────────────────────────────────────────────────────── */
@media (max-width: 768px) {
    .ro-wrap     { padding: 1rem .85rem 2rem; font-size: 15px; }
    .ro-head h1  { font-size: 1.4rem; }
    .ro-head p   { font-size: .92rem; }
    .ro-filter   { flex-direction:column; align-items:stretch; }
    .ro-fg input, .ro-fg select { min-width: 0; width: 100%; }
    .ro-btn-prim, .ro-btn-reset { width: 100%; text-align:center; }
    .ro-kpi-grid { grid-template-columns:repeat(2,1fr); gap:.75rem; }
    .ro-kpi .kv  { font-size: 1.6rem; }
    .ro-group-hd { flex-direction:column; align-items:flex-start; }
}

@media (max-width: 480px) {
    .ro-kpi-grid { grid-template-columns:1fr; }
}

.ro-export-all { display:inline-flex; align-items:center; gap:.5rem; padding:.65rem 1.25rem;
              background:#16a34a; color:#fff; border:none; border-radius:.5rem;
              font-size:1rem; font-weight:600; cursor:pointer; text-decoration:none;
              white-space:nowrap; }
.ro-export-all:hover { background:#15803d; color:#fff; }
.ro-export-group { display:inline-flex; align-items:center; gap:.4rem; padding:.45rem .9rem;
              background:#fff; color:#16a34a; border:1px solid #bbf7d0; border-radius:.5rem;
              font-size:.92rem; font-weight:600; cursor:pointer; text-decoration:none;
              white-space:nowrap; }
.ro-export-group:hover { background:#f0fdf4; }
.ro-export-group:disabled, .ro-export-group.disabled {
              opacity:.5; cursor:not-allowed; pointer-events:none; }

/* Checkbox kolom pilih-export */
.ro-chk-col { width:2.75rem; text-align:center; }
.ro-row-chk { width:1.2rem; height:1.2rem; cursor:pointer; accent-color:var(--blue); }

/* Nama barang sebagai link ke halaman detail */
.ro-item-link { color:var(--ink); text-decoration:none; }
.ro-item-link:hover .ro-item-name { color:var(--blue); text-decoration:underline; }

/* Tombol discontinue di kolom aksi */
.ro-act-col { white-space:nowrap; }
.ro-btn-disc { display:inline-flex; align-items:center; gap:.3rem; padding:.35rem .75rem;
               background:var(--urgent-bg); color:var(--urgent); border:1px solid var(--urgent-b);
               border-radius:.4rem; font-size:.85rem; font-weight:600; cursor:pointer;
               white-space:nowrap; }
.ro-btn-disc:hover { background:var(--urgent); color:#fff; }

/* Modal discontinue (pola sama dengan halaman good-movement) */
.ro-modal-overlay { position:fixed; inset:0; background:rgba(15,23,42,.55);
    display:flex; align-items:center; justify-content:center; z-index:1000;
    opacity:0; pointer-events:none; transition:opacity .15s; }
.ro-modal-overlay.open { opacity:1; pointer-events:auto; }
.ro-modal { background:#fff; border-radius:.75rem; width:min(440px, 92vw);
    padding:1.5rem; box-shadow:0 20px 50px rgba(0,0,0,.25); }
.ro-modal-hd { display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; }
.ro-modal-hd h3 { margin:0; font-size:1.15rem; color:var(--ink); }
.ro-modal-close { background:none; border:none; font-size:1.1rem; cursor:pointer; color:var(--ink3); }
.ro-modal-name { font-weight:700; font-size:1.05rem; color:var(--ink); margin-bottom:.2rem; }
.ro-modal-meta { font-size:.92rem; color:var(--ink2); margin-bottom:.85rem; }
.ro-modal-warn { background:var(--soon-bg); border:1px solid var(--soon-b); color:#92400e;
    border-radius:.5rem; padding:.7rem .9rem; font-size:.88rem; line-height:1.55; margin-bottom:1rem; }
.ro-modal-lbl { display:block; font-size:.88rem; font-weight:600; color:var(--ink2); margin-bottom:.4rem; }
.ro-modal-ta { width:100%; min-height:90px; border:1px solid var(--bdr); border-radius:.5rem;
    padding:.6rem .75rem; font-size:.95rem; font-family:inherit; resize:vertical; }
.ro-modal-ta:focus { outline:none; border-color:var(--blue); }
.ro-modal-err { color:var(--urgent); font-size:.82rem; margin-top:.35rem; display:none; }
.ro-modal-ft { display:flex; justify-content:flex-end; gap:.6rem; margin-top:1.25rem; }
.ro-btn-cancel { padding:.55rem 1.1rem; background:var(--surf); color:var(--ink2);
    border:1px solid var(--bdr); border-radius:.5rem; font-size:.95rem; cursor:pointer; }
.ro-btn-ok { padding:.55rem 1.1rem; background:var(--urgent); color:#fff; border:none;
    border-radius:.5rem; font-size:.95rem; font-weight:600; cursor:pointer; }
.ro-btn-ok:hover { background:#b91c1c; }
</style>

<div class="content-wrapper">
<section class="content">
<div class="ro-wrap">

{{-- Header --}}
<div class="ro-head">
    <div>
        <h1>📦 Rekomendasi Order ke Distributor</h1>
        <p>Barang yang perlu di-reorder, dikelompokkan per distributor</p>
    </div>
    <div style="display:flex; align-items:center; gap:.75rem; flex-wrap:wrap;">
        <span class="ro-badge">
            {{ \Carbon\Carbon::parse($startDate)->isoFormat('D MMM Y') }} →
            {{ \Carbon\Carbon::parse($endDate)->isoFormat('D MMM Y') }}
            · {{ $s['days'] }} hari
        </span>
        <a href="#" id="roExportAllBtn" class="ro-export-all">⬇️ Export Semua (CSV)</a>
    </div>
</div>

{{-- Info perhitungan --}}
<div class="ro-info">
    ℹ️ <strong>Cara baca:</strong> <em>Min. Stok</em> adalah titik di mana barang harus segera dipesan
    (dihitung dari rata-rata penjualan harian × estimasi 5 hari pengiriman + stok pengaman).
    <em>Qty Order</em> adalah jumlah yang disarankan dipesan agar stok cukup untuk ±14 hari ke depan,
    sudah dibulatkan ke satuan kemasan (dus/pak) jika tersedia. Lead time pengiriman masih berupa
    <strong>asumsi 5 hari</strong> — angka ini akan lebih akurat bila dicatat per distributor.
</div>

{{-- Filter --}}
<form method="GET" action="{{ route('admin.reports.reorder') }}" id="roFilterForm">
    <div class="ro-filter">
        <div class="ro-fg">
            <label>Dari</label>
            <input type="date" name="start_date" value="{{ $startDate }}">
        </div>
        <div class="ro-fg">
            <label>Sampai</label>
            <input type="date" name="end_date" value="{{ $endDate }}">
        </div>
        <div class="ro-fg">
            <label>Distributor</label>
            <select name="distributor">
                <option value="">Semua Distributor</option>
                @foreach($distributors as $d)
                    <option value="{{ $d->id }}" {{ $distributorId == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="ro-fg">
            <label>Kategori</label>
            <select name="kategori">
                <option value="">Semua Kategori</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ $kategori == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="ro-fg-chk">
            <input type="checkbox" id="roOnlyNeeded" name="only_needed" value="1" {{ $onlyNeeded ? 'checked' : '' }}>
            <label for="roOnlyNeeded">Hanya yang perlu order</label>
        </div>
        <button type="submit" class="ro-btn-prim">🔍 Terapkan</button>
        <a href="{{ route('admin.reports.reorder') }}" class="ro-btn-reset">Reset</a>
    </div>
</form>

{{-- KPI --}}
<div class="ro-kpi-grid">
    <div class="ro-kpi k-urgent">
        <div class="kl">Segera Order</div>
        <div class="kv">{{ number_format($s['urgent_count']) }}</div>
        <div class="ks">stok di bawah pengaman</div>
    </div>
    <div class="ro-kpi k-soon">
        <div class="kl">Mendekati Batas</div>
        <div class="kv">{{ number_format($s['soon_count']) }}</div>
        <div class="ks">perlu dipantau</div>
    </div>
    <div class="ro-kpi">
        <div class="kl">Total Barang</div>
        <div class="kv">{{ number_format($s['total_item']) }}</div>
        <div class="ks">perlu di-order</div>
    </div>
    <div class="ro-kpi">
        <div class="kl">Distributor</div>
        <div class="kv">{{ number_format($s['total_distributor']) }}</div>
        <div class="ks">tujuan order</div>
    </div>
    <div class="ro-kpi k-blue">
        <div class="kl">Estimasi Biaya</div>
        <div class="kv" style="font-size:1.05rem">Rp {{ number_format($s['total_biaya']/1000000, 1) }}jt</div>
        <div class="ks">total semua order</div>
    </div>
</div>

{{-- Grup per distributor --}}
@forelse($groups as $group)
<div class="ro-group collapsed" id="roGroup{{ $loop->index }}">
    <div class="ro-group-hd" onclick="roToggleGroup({{ $loop->index }})">
        <div class="ro-group-title">
            <span class="ro-group-chevron">▾</span>
            <h2>
                🏢 {{ $group->distributor_nama }}
                @if($group->urgent_count > 0)
                    <span class="ro-urgent-pill">{{ $group->urgent_count }} segera</span>
                @endif
            </h2>
        </div>
        <div class="ro-group-meta">
            <span><b>{{ $group->jumlah_item }}</b> item</span>
            <span>Estimasi: <b>Rp {{ number_format($group->total_biaya, 0, ',', '.') }}</b></span>
            <button type="button" class="ro-export-group"
                onclick="event.stopPropagation()"
                data-group-idx="{{ $loop->index }}"
                data-distributor-id="{{ $group->distributor_id }}"
                data-distributor-nama="{{ $group->distributor_nama }}">
                ⬇️ Export CSV
            </button>
        </div>
    </div>
    <div class="ro-group-body">
    <div class="ro-tbl-wrap">
    <table data-group-idx="{{ $loop->index }}">
        <thead>
            <tr>
                <th class="ro-chk-col">
                    <input type="checkbox" class="ro-row-chk ro-chk-all" title="Pilih semua di distributor ini">
                </th>
                <th>Barang</th>
                <th>Kategori</th>
                <th class="tr">Stok Saat Ini</th>
                <th class="tr" data-ro-tip="Titik pemicu order: stok pengaman + kebutuhan selama lead time">Min. Stok</th>
                <th class="tr" data-ro-tip="Stok ideal setelah barang baru tiba">Target Stok</th>
                <th class="tr">Qty Order</th>
                <th class="tr">Estimasi Biaya</th>
                <th>Urgensi</th>
                <th class="ro-act-col">Aksi</th>
            </tr>
        </thead>
        <tbody>
        @foreach($group->items as $it)
        @php
            $badgeClass = $it->urgensi === 1 ? 'b-urgent' : ($it->urgensi === 2 ? 'b-soon' : 'b-safe');
            $rowClass   = $it->urgensi === 1 ? 'row-urgent' : '';
        @endphp
        <tr class="{{ $rowClass }}">
            <td class="ro-chk-col">
                <input type="checkbox" class="ro-row-chk ro-item-chk" checked
                    data-good-id="{{ $it->good_id }}">
            </td>
            <td>
                <a href="{{ url('admin/good/' . $it->good_id . '/detail') }}" class="ro-item-link">
                    <div class="ro-item-name">{{ $it->nama }}</div>
                </a>
                <div class="ro-stock-line">
                    @if($it->kode){{ $it->kode }} · @endif{{ $it->satuan }}@if($it->merk && $it->merk !== '-') · {{ $it->merk }}@endif
                </div>
            </td>
            <td>{{ $it->kategori }}</td>
            <td class="tr">
                {{ number_format($it->stok_sekarang, 0, ',', '.') }}
                <div class="ro-stock-line">{{ number_format($it->avg_qty_per_day, 1) }}/hari</div>
            </td>
            <td class="tr">{{ number_format($it->min_stock, 1, ',', '.') }}</td>
            <td class="tr">{{ number_format($it->target_stock, 1, ',', '.') }}</td>
            <td class="tr">
                <div style="font-weight:700">{{ $it->reorder_paket }}</div>
            </td>
            <td class="tr">Rp {{ number_format($it->estimasi_biaya, 0, ',', '.') }}</td>
            <td>
                <span class="ro-badge-tag {{ $badgeClass }}">
                    {{ $it->urgensi === 1 ? '🚨' : ($it->urgensi === 2 ? '⏳' : '✅') }}
                    {{ $it->urgensi_label }}
                </span>
            </td>
            <td class="ro-act-col">
                <button type="button" class="ro-btn-disc"
                    onclick="roOpenDiscModal({{ $it->good_id }}, '{{ addslashes($it->nama) }}', '{{ addslashes($it->kategori) }}')">
                    🗑️ Disc.
                </button>
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
    </div>
    </div>{{-- end ro-group-body --}}
</div>
@empty
<div class="ro-group" style="cursor:default">
    <div class="ro-empty">
        <div class="ei">🎉</div>
        <h3>Tidak ada barang yang perlu di-order</h3>
        <p>Semua stok masih aman sesuai kriteria saat ini.</p>
    </div>
</div>
@endforelse

</div>{{-- end ro-wrap --}}
</section>
</div>{{-- end content-wrapper --}}

{{-- ═══════════════════════════════════════════════════════════════════ --}}
{{-- MODAL DISCONTINUE — di luar content-wrapper agar z-index benar     --}}
{{-- ═══════════════════════════════════════════════════════════════════ --}}
<div class="ro-modal-overlay" id="roDiscModal">
    <div class="ro-modal">
        <div class="ro-modal-hd">
            <h3>🗑️ Konfirmasi Discontinue</h3>
            <button class="ro-modal-close" type="button" onclick="roCloseDiscModal()">✕</button>
        </div>
        <div class="ro-modal-name" id="roModalName">—</div>
        <div class="ro-modal-meta" id="roModalMeta">—</div>
        <div class="ro-modal-warn">
            ⚠️ Barang yang di-discontinue <strong>tidak akan muncul lagi</strong> dalam
            laporan pergerakan maupun rekomendasi order. Riwayat transaksi tetap tersimpan
            dan barang dapat diaktifkan kembali kapan saja dari halaman Pergerakan Barang.
        </div>
        <label class="ro-modal-lbl" for="roDiscReason">
            Alasan Discontinue <span style="color:var(--urgent)">*</span>
        </label>
        <textarea class="ro-modal-ta" id="roDiscReason"
            placeholder="Contoh: Tidak laku selama 6 bulan, digantikan produk baru...">Sudah lama tidak order</textarea>
        <div class="ro-modal-err" id="roDiscErr">Alasan wajib diisi minimal 5 karakter.</div>
        <div class="ro-modal-ft">
            <button type="button" class="ro-btn-cancel" onclick="roCloseDiscModal()">Batal</button>
            <button type="button" class="ro-btn-ok" onclick="roSubmitDisc()">🗑️ Ya, Discontinue</button>
        </div>
    </div>
</div>

{{-- Form ini TIDAK disubmit langsung — hanya dipakai sebagai sumber token
     CSRF untuk request AJAX di roSubmitDisc() (lihat <script> di bawah),
     supaya discontinue tidak memindahkan halaman, cukup reload setelah selesai. --}}
<form method="POST" id="roDiscForm" style="display:none">
    @csrf
</form>

{{-- Form tersembunyi untuk export (GET, agar good_id[] terkirim sebagai query array) --}}
<form method="GET" id="roExportForm" action="{{ route('admin.reports.reorder.export') }}" style="display:none">
    <input type="hidden" name="start_date" value="{{ $startDate }}">
    <input type="hidden" name="end_date" value="{{ $endDate }}">
    <input type="hidden" name="kategori" value="{{ $kategori }}">
    <input type="hidden" name="only_needed" value="{{ $onlyNeeded ? '1' : '0' }}">
    <input type="hidden" name="distributor" id="roExportDistributorId" value="">
    <input type="hidden" name="distributor_nama" id="roExportDistributorNama" value="">
    <div id="roExportGoodIds"></div>
</form>

<script>
(function () {
    /* ── Konstanta ────────────────────────────────────────────────── */
    var _discBaseUrl = '{{ url("admin/reports/movement") }}';
    var _goodId       = null;

    /* ── Accordion: expand/collapse tabel per distributor ────────────── */
    window.roToggleGroup = function (idx) {
        var el = document.getElementById('roGroup' + idx);
        if (el) el.classList.toggle('collapsed');
    };

    /* ── Select-all per tabel distributor ───────────────────────────── */
    document.querySelectorAll('.ro-tbl-wrap table').forEach(function (table) {
        var allChk  = table.querySelector('.ro-chk-all');
        var rowChks = table.querySelectorAll('.ro-item-chk');
        if (!allChk) return;

        allChk.checked = true; // default semua terpilih

        allChk.addEventListener('change', function () {
            rowChks.forEach(function (c) { c.checked = allChk.checked; });
        });

        rowChks.forEach(function (c) {
            c.addEventListener('change', function () {
                var checkedCount = table.querySelectorAll('.ro-item-chk:checked').length;
                allChk.checked       = checkedCount === rowChks.length;
                allChk.indeterminate = checkedCount > 0 && checkedCount < rowChks.length;
            });
        });
    });

    /* ── Export per-distributor (hanya barang yang dicentang) ───────── */
    document.querySelectorAll('.ro-export-group').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var idx       = btn.getAttribute('data-group-idx');
            var table     = document.querySelector('.ro-tbl-wrap table[data-group-idx="' + idx + '"]');
            var checked   = table ? table.querySelectorAll('.ro-item-chk:checked') : [];

            if (checked.length === 0) {
                alert('Pilih minimal 1 barang untuk di-export.');
                return;
            }

            var distId   = btn.getAttribute('data-distributor-id');
            var distNama = btn.getAttribute('data-distributor-nama');

            document.getElementById('roExportDistributorId').value   = distId || '';
            document.getElementById('roExportDistributorNama').value = distId ? '' : distNama;

            roFillGoodIds(checked);
            document.getElementById('roExportForm').submit();
        });
    });

    /* ── Export semua (hanya barang yang dicentang, lintas distributor) ── */
    var exportAllBtn = document.getElementById('roExportAllBtn');
    if (exportAllBtn) {
        exportAllBtn.addEventListener('click', function (e) {
            e.preventDefault();

            var allChecked = document.querySelectorAll('.ro-item-chk:checked');
            if (allChecked.length === 0) {
                alert('Pilih minimal 1 barang untuk di-export.');
                return;
            }

            document.getElementById('roExportDistributorId').value   = '';
            document.getElementById('roExportDistributorNama').value = '';

            roFillGoodIds(allChecked);
            document.getElementById('roExportForm').submit();
        });
    }

    function roFillGoodIds(checkedNodeList) {
        var holder = document.getElementById('roExportGoodIds');
        holder.innerHTML = '';
        checkedNodeList.forEach(function (chk) {
            var input = document.createElement('input');
            input.type  = 'hidden';
            input.name  = 'good_ids[]';
            input.value = chk.getAttribute('data-good-id');
            holder.appendChild(input);
        });
    }

    /* ── Modal discontinue ──────────────────────────────────────────── */
    var ROD_DEFAULT_REASON = 'Sudah lama tidak order';

    window.roOpenDiscModal = function (goodId, name, kat) {
        _goodId = goodId;
        document.getElementById('roModalName').textContent = name;
        document.getElementById('roModalMeta').textContent = 'Kategori: ' + kat;
        document.getElementById('roDiscReason').value = ROD_DEFAULT_REASON;
        document.getElementById('roDiscErr').style.display = 'none';
        document.getElementById('roDiscModal').classList.add('open');
        setTimeout(function () {
            var ta = document.getElementById('roDiscReason');
            ta.focus();
            ta.select(); // teks default langsung terseleksi, mudah diganti kalau perlu
        }, 120);
    };

    window.roCloseDiscModal = function () {
        document.getElementById('roDiscModal').classList.remove('open');
        _goodId = null;
    };

    window.roSubmitDisc = function () {
        var reason = document.getElementById('roDiscReason').value.trim();
        if (reason.length < 5) {
            document.getElementById('roDiscErr').style.display = 'block';
            document.getElementById('roDiscReason').focus();
            return;
        }
        document.getElementById('roDiscErr').style.display = 'none';

        var okBtn      = document.querySelector('.ro-btn-ok');
        var cancelBtn  = document.querySelector('.ro-btn-cancel');
        var origLabel  = okBtn.textContent;
        okBtn.disabled = true;
        cancelBtn.disabled = true;
        okBtn.textContent = '⏳ Memproses...';

        var tokenEl = document.querySelector('#roDiscForm input[name="_token"]');
        var token   = tokenEl ? tokenEl.value : '';

        fetch(_discBaseUrl + '/' + _goodId + '/discontinue', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN':    token,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept':          'application/json, text/html',
            },
            body: new URLSearchParams({ reason: reason, _token: token }),
        })
        .then(function () {
            // Berhasil diproses (atau setidaknya request selesai tanpa error
            // jaringan) -> reload halaman supaya data daftar reorder ter-update,
            // barang yang baru di-discontinue otomatis hilang dari daftar.
            window.location.reload();
        })
        .catch(function () {
            okBtn.disabled = false;
            cancelBtn.disabled = false;
            okBtn.textContent = origLabel;
            alert('Gagal menghubungi server. Periksa koneksi lalu coba lagi.');
        });
    };

    document.getElementById('roDiscModal').addEventListener('click', function (e) {
        if (e.target === this) roCloseDiscModal();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') roCloseDiscModal();
    });
}());
</script>
@endsection