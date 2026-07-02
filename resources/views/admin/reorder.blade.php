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
.ro-last-loading-date { color:#64748b; }

.ro-qty-input-group { display:inline-flex; align-items:center; gap:.4rem; justify-content:flex-end; }
.ro-qty-input { width:5.5rem; padding:.4rem .55rem; border:1px solid var(--bdr);
    border-radius:.4rem; font-size:1rem; font-weight:700; text-align:right;
    color:var(--ink); background:#fff; }
.ro-qty-input:focus { outline:none; border-color:var(--blue); box-shadow:0 0 0 3px rgba(37,99,235,.12); }
.ro-qty-input.ro-qty-changed { border-color:var(--soon); background:var(--soon-bg); }
.ro-qty-unit { font-size:.85rem; font-weight:600; color:var(--ink2); white-space:nowrap; min-width:2.2rem; }

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

/* Dropdown pilihan format export per distributor */
.ro-export-dd { position:relative; display:inline-block; }
.ro-export-dd-menu { position:absolute; top:calc(100% + .4rem); right:0; background:#fff;
    border:1px solid var(--bdr); border-radius:.6rem; box-shadow:0 10px 30px rgba(0,0,0,.12);
    min-width:260px; z-index:50; display:none; overflow:hidden; }
.ro-export-dd.open .ro-export-dd-menu { display:block; }
.ro-export-dd-item { display:flex; align-items:flex-start; gap:.6rem; width:100%; text-align:left;
    padding:.7rem .9rem; background:none; border:none; cursor:pointer; font-family:inherit; }
.ro-export-dd-item:hover { background:var(--surf); }
.ro-export-dd-item + .ro-export-dd-item { border-top:1px solid var(--bdr2); }
.ro-export-dd-item .dd-ico { font-size:1.1rem; line-height:1.3; flex-shrink:0; }
.ro-export-dd-item .dd-title { font-size:.9rem; font-weight:700; color:var(--ink); }
.ro-export-dd-item .dd-desc { font-size:.78rem; color:var(--ink2); margin-top:.1rem; line-height:1.4; }
.ro-dd-divider { height:1px; background:var(--bdr); margin:.2rem 0; }
.ro-dd-wa .dd-title { color:#128c7e; }
.ro-dd-wa:hover { background:#f0fdf9; }

/* Toast notifikasi "Berhasil disalin" */
.ro-toast { position:fixed; bottom:1.75rem; left:50%; transform:translateX(-50%) translateY(4rem);
    background:#1e293b; color:#fff; font-size:.95rem; font-weight:500;
    padding:.65rem 1.35rem; border-radius:9999px; z-index:2000;
    box-shadow:0 8px 24px rgba(0,0,0,.25); white-space:nowrap;
    transition:transform .22s ease, opacity .22s ease; opacity:0; pointer-events:none; }
.ro-toast.show { transform:translateX(-50%) translateY(0); opacity:1; }
.ro-export-group:disabled, .ro-export-group.disabled {
              opacity:.5; cursor:not-allowed; pointer-events:none; }

/* Checkbox kolom pilih-export */
.ro-chk-col { width:3.5rem; text-align:center; }
.ro-chk-col .ro-chk-label { font-size:.62rem; font-weight:700; color:var(--ink3);
    text-transform:uppercase; letter-spacing:.03em; margin-top:.25rem; white-space:nowrap; }
.ro-row-chk { width:1.2rem; height:1.2rem; cursor:pointer; }
.ro-row-chk.ro-chk-export-all, .ro-export-chk { accent-color:var(--blue); }
.ro-row-chk.ro-chk-disc-all, .ro-disc-chk { accent-color:var(--urgent); }

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

.ro-btn-disc-bulk { display:inline-flex; align-items:center; gap:.4rem; padding:.45rem .9rem;
               background:#fff; color:var(--urgent); border:1px solid var(--urgent-b);
               border-radius:.5rem; font-size:.92rem; font-weight:600; cursor:pointer;
               white-space:nowrap; transition:background .12s; }
.ro-btn-disc-bulk:hover:not(:disabled) { background:var(--urgent); color:#fff; }
.ro-btn-disc-bulk:disabled { opacity:.45; cursor:not-allowed; }

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
.ro-modal-list { list-style:none; margin:0 0 .85rem; padding:0; max-height:170px;
    overflow-y:auto; border:1px solid var(--bdr); border-radius:.5rem; }
.ro-modal-list li { padding:.5rem .75rem; font-size:.9rem; color:var(--ink);
    border-bottom:1px solid var(--bdr2); display:flex; align-items:center; gap:.5rem; }
.ro-modal-list li:last-child { border-bottom:none; }
.ro-modal-list li .num { color:var(--ink3); font-size:.8rem; min-width:1.4rem; }
.ro-modal-progress { font-size:.85rem; color:var(--ink2); margin-top:.6rem; text-align:center; }
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
            <button type="button" class="ro-btn-disc-bulk" disabled
                onclick="event.stopPropagation(); roOpenBulkDiscModal({{ $loop->index }})"
                data-group-idx="{{ $loop->index }}">
                🗑️ Discontinue Terpilih (<span class="ro-bulk-count">0</span>)
            </button>
            <div class="ro-export-dd" id="roExportDd{{ $loop->index }}"
                data-group-idx="{{ $loop->index }}"
                data-distributor-id="{{ $group->distributor_id }}"
                data-distributor-nama="{{ $group->distributor_nama }}">
                <button type="button" class="ro-export-group"
                    onclick="event.stopPropagation(); roToggleExportDd({{ $loop->index }})">
                    ⬇️ Export CSV ▾
                </button>
                <div class="ro-export-dd-menu" onclick="event.stopPropagation()">
                    <button type="button" class="ro-export-dd-item"
                        onclick="roDoExport({{ $loop->index }}, 'lengkap')">
                        <span class="dd-ico">📋</span>
                        <span>
                            <span class="dd-title">Export Lengkap (Internal)</span>
                            <span class="dd-desc">Semua data: stok, min. stok, target, urgensi, harga — untuk arsip toko.</span>
                        </span>
                    </button>
                    <button type="button" class="ro-export-dd-item"
                        onclick="roDoExport({{ $loop->index }}, 'ringkas')">
                        <span class="dd-ico">📤</span>
                        <span>
                            <span class="dd-title">Export untuk Distributor (CSV)</span>
                            <span class="dd-desc">Hanya nama barang & jumlah order — siap kirim WhatsApp/email.</span>
                        </span>
                    </button>
                    <div class="ro-dd-divider"></div>
                    <button type="button" class="ro-export-dd-item ro-dd-wa"
                        onclick="roCopyWa({{ $loop->index }})">
                        <span class="dd-ico">💬</span>
                        <span>
                            <span class="dd-title">Copy Teks untuk WA</span>
                            <span class="dd-desc">Salin daftar pesanan siap paste ke chat WhatsApp distributor.</span>
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="ro-group-body">
    <div class="ro-tbl-wrap">
    <table data-group-idx="{{ $loop->index }}">
        <thead>
            <tr>
                <th class="ro-chk-col" data-ro-tip="Pilih barang yang ikut di-export ke CSV">
                    <input type="checkbox" class="ro-row-chk ro-chk-export-all" title="Pilih semua untuk export">
                    <div class="ro-chk-label">Export</div>
                </th>
                <th>Barang</th>
                <th>Kategori</th>
                <th class="tr">Stok Saat Ini</th>
                <th class="tr" data-ro-tip="Titik pemicu order: stok pengaman + kebutuhan selama lead time">Min. Stok</th>
                <th class="tr" data-ro-tip="Stok ideal setelah barang baru tiba">Target Stok</th>
                <th class="tr" data-ro-tip="Jumlah pada loading/restock terakhir, sebagai pembanding">Loading Terakhir</th>
                <th class="tr" data-ro-tip="Bisa diubah manual sebelum export sesuai kebutuhan">Qty Order</th>
                <th class="tr">Estimasi Biaya</th>
                <th>Urgensi</th>
                <th class="ro-chk-col" data-ro-tip="Pilih barang yang akan di-discontinue sekaligus">
                    <input type="checkbox" class="ro-row-chk ro-chk-disc-all" title="Pilih semua untuk discontinue">
                    <div class="ro-chk-label">Disc.</div>
                </th>
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
                <input type="checkbox" class="ro-row-chk ro-export-chk" checked
                    data-good-id="{{ $it->good_id }}">
            </td>
            <td>
                <a href="{{ url('admin/good/' . $it->good_id . '/detail') }}" class="ro-item-link">
                    <div class="ro-item-name">{{ $it->nama }}</div>
                </a>
                <div class="ro-stock-line">
                    @if($it->kode){{ $it->kode }} · @endif{{ $it->satuan }}@if($it->merk && $it->merk !== '-') · {{ $it->merk }}@endif
                </div>
                <div class="ro-stock-line ro-last-loading-date">
                    📅 Last loading: {{ $it->last_loading_date ?? 'belum pernah' }}
                </div>
            </td>
            <td>{{ $it->kategori }}</td>
            <td class="tr">
                {{ number_format($it->stok_sekarang, 0, ',', '.') }}
                <div class="ro-stock-line">{{ number_format($it->avg_qty_per_day, 1) }}/hari</div>
            </td>
            <td class="tr">{{ number_format($it->min_stock, 0, ',', '.') }}</td>
            <td class="tr">{{ number_format($it->target_stock, 0, ',', '.') }}</td>
            <td class="tr">
                @if($it->last_loading_qty !== null)
                    {{ number_format($it->last_loading_qty, 0, ',', '.') }} {{ $it->last_loading_unit }}
                @else
                    <span class="ro-stock-line">—</span>
                @endif
            </td>
            <td class="tr">
                <div class="ro-qty-input-group">
                    <input type="number"
                        class="ro-qty-input"
                        id="roQty{{ $it->good_id }}"
                        data-good-id="{{ $it->good_id }}"
                        data-harga-per-unit="{{ $it->harga_beli_per_unit }}"
                        value="{{ $it->reorder_qty }}"
                        min="0" step="1">
                    <span class="ro-qty-unit">{{ $it->reorder_unit }}</span>
                </div>
            </td>
            <td class="tr">
                <span id="roBiaya{{ $it->good_id }}">Rp {{ number_format($it->estimasi_biaya, 0, ',', '.') }}</span>
            </td>
            <td>
                <span class="ro-badge-tag {{ $badgeClass }}">
                    {{ $it->urgensi === 1 ? '🚨' : ($it->urgensi === 2 ? '⏳' : '✅') }}
                    {{ $it->urgensi_label }}
                </span>
            </td>
            <td class="ro-chk-col">
                <input type="checkbox" class="ro-row-chk ro-disc-chk"
                    data-good-id="{{ $it->good_id }}">
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

{{-- Toast notifikasi "Teks berhasil disalin" --}}
<div class="ro-toast" id="roToast"></div>

{{-- ═══════════════════════════════════════════════════════════════════ --}}
{{-- MODAL DISCONTINUE — di luar content-wrapper agar z-index benar     --}}
{{-- ═══════════════════════════════════════════════════════════════════ --}}
<div class="ro-modal-overlay" id="roDiscModal">
    <div class="ro-modal">
        <div class="ro-modal-hd">
            <h3 id="roModalTitle">🗑️ Konfirmasi Discontinue</h3>
            <button class="ro-modal-close" type="button" onclick="roCloseDiscModal()">✕</button>
        </div>
        <div class="ro-modal-name" id="roModalName">—</div>
        <div class="ro-modal-meta" id="roModalMeta">—</div>
        <ul class="ro-modal-list" id="roModalList" style="display:none"></ul>
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
        <div class="ro-modal-progress" id="roModalProgress" style="display:none"></div>
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
    <input type="hidden" name="format" id="roExportFormat" value="lengkap">
    <div id="roExportGoodIds"></div>
    <div id="roExportQtyOverrides"></div>
</form>

<script>
(function () {
    /* ── Konstanta ────────────────────────────────────────────────── */
    var _discBaseUrl = '{{ url("admin/reports/movement") }}';
    var _discTargets = []; // array of {id, name} -- 1 item = mode single, banyak = mode bulk

    /* ── Accordion: expand/collapse tabel per distributor ────────────── */
    window.roToggleGroup = function (idx) {
        var el = document.getElementById('roGroup' + idx);
        if (el) el.classList.toggle('collapsed');
    };

    /* ── Select-all per tabel distributor (2 kelompok independen) ───── */
    document.querySelectorAll('.ro-tbl-wrap table').forEach(function (table) {
        roSetupSelectAll(table, '.ro-chk-export-all', '.ro-export-chk', null);
        roSetupSelectAll(table, '.ro-chk-disc-all',   '.ro-disc-chk',   roUpdateBulkBtn);
        roUpdateBulkBtn(table); // set angka awal saat halaman dimuat
    });

    /**
     * Pasang logika "pilih semua" yang independen untuk 1 kelompok checkbox
     * dalam 1 tabel. onAnyChange (opsional) dipanggil setiap kali ada
     * perubahan, dipakai discontinue untuk update tombol bulk.
     */
    function roSetupSelectAll(table, allSelector, rowSelector, onAnyChange) {
        var allChk  = table.querySelector(allSelector);
        var rowChks = table.querySelectorAll(rowSelector);
        if (!allChk) return;

        allChk.addEventListener('change', function () {
            rowChks.forEach(function (c) { c.checked = allChk.checked; });
            if (onAnyChange) onAnyChange(table);
        });

        rowChks.forEach(function (c) {
            c.addEventListener('change', function () {
                var checkedCount = table.querySelectorAll(rowSelector + ':checked').length;
                allChk.checked       = checkedCount === rowChks.length;
                allChk.indeterminate = checkedCount > 0 && checkedCount < rowChks.length;
                if (onAnyChange) onAnyChange(table);
            });
        });
    }

    /* ── Update tombol "Discontinue Terpilih (N)" sesuai checkbox aktif ── */
    function roUpdateBulkBtn(table) {
        var idx = table.getAttribute('data-group-idx');
        var btn = document.querySelector('.ro-btn-disc-bulk[data-group-idx="' + idx + '"]');
        if (!btn) return;
        var count = table.querySelectorAll('.ro-disc-chk:checked').length;
        btn.querySelector('.ro-bulk-count').textContent = count;
        btn.disabled = count === 0;
    }

    /* ── Qty Order editable: update estimasi biaya secara live saat diketik,
       dan tandai visual input yang sudah diubah dari nilai rekomendasi awal.
       Nilai SELALU bulat (integer) karena pemesanan dalam satuan kemasan utuh. ── */
    document.querySelectorAll('.ro-qty-input').forEach(function (input) {
        var initialValue = input.value;

        input.addEventListener('input', function () {
            var qty       = parseInt(input.value, 10) || 0;
            var hargaUnit = parseFloat(input.getAttribute('data-harga-per-unit')) || 0;
            var goodId    = input.getAttribute('data-good-id');
            var biayaEl   = document.getElementById('roBiaya' + goodId);

            if (biayaEl) {
                var biaya = Math.round(qty * hargaUnit);
                biayaEl.textContent = 'Rp ' + biaya.toLocaleString('id-ID');
            }

            input.classList.toggle('ro-qty-changed', input.value !== initialValue);
        });

        // Paksa nilai akhir selalu bulat & tidak negatif begitu input ditinggalkan
        // (mis. browser tertentu masih mengizinkan ketik koma walau step="1")
        input.addEventListener('blur', function () {
            var qty = parseInt(input.value, 10);
            if (isNaN(qty) || qty < 0) qty = 0;
            input.value = qty;
            input.dispatchEvent(new Event('input'));
        });
    });

    /* ── Dropdown pilihan format export per-distributor ──────────────── */
    window.roToggleExportDd = function (idx) {
        var dd = document.getElementById('roExportDd' + idx);
        if (!dd) return;
        var isOpen = dd.classList.contains('open');
        // Tutup semua dropdown lain dulu sebelum buka yang baru diklik
        document.querySelectorAll('.ro-export-dd.open').forEach(function (el) {
            el.classList.remove('open');
        });
        if (!isOpen) dd.classList.add('open');
    };

    // Klik di luar dropdown manapun -> tutup semua dropdown yang terbuka
    document.addEventListener('click', function () {
        document.querySelectorAll('.ro-export-dd.open').forEach(function (el) {
            el.classList.remove('open');
        });
    });

    /**
     * Eksekusi export untuk 1 distributor dengan format pilihan.
     * mode: 'lengkap' (semua kolom analisis) atau 'ringkas' (nama + qty saja,
     * siap dikirim ke distributor).
     */
    window.roDoExport = function (groupIdx, mode) {
        var dd = document.getElementById('roExportDd' + groupIdx);
        if (!dd) return;

        var table   = document.querySelector('.ro-tbl-wrap table[data-group-idx="' + groupIdx + '"]');
        var checked = table ? table.querySelectorAll('.ro-export-chk:checked') : [];

        if (checked.length === 0) {
            alert('Pilih minimal 1 barang (kolom centang "Export") untuk di-export.');
            dd.classList.remove('open');
            return;
        }

        var distId   = dd.getAttribute('data-distributor-id');
        var distNama = dd.getAttribute('data-distributor-nama');

        document.getElementById('roExportDistributorId').value   = distId || '';
        document.getElementById('roExportDistributorNama').value = distId ? '' : distNama;
        document.getElementById('roExportFormat').value           = mode;

        roFillGoodIds(checked);
        dd.classList.remove('open');
        document.getElementById('roExportForm').submit();
    };

    /* ── Export semua (selalu format lengkap, lintas distributor) ────── */
    var exportAllBtn = document.getElementById('roExportAllBtn');
    if (exportAllBtn) {
        exportAllBtn.addEventListener('click', function (e) {
            e.preventDefault();

            var allChecked = document.querySelectorAll('.ro-export-chk:checked');
            if (allChecked.length === 0) {
                alert('Pilih minimal 1 barang (kolom centang "Export") untuk di-export.');
                return;
            }

            document.getElementById('roExportDistributorId').value   = '';
            document.getElementById('roExportDistributorNama').value = '';
            document.getElementById('roExportFormat').value           = 'lengkap';

            roFillGoodIds(allChecked);
            document.getElementById('roExportForm').submit();
        });
    }

    /**
     * Susun teks daftar pesanan dalam format WA dan salin ke clipboard.
     *
     * Format didesain untuk enak dibaca di layar HP:
     * - Tidak pakai spasi alignment / tabel (font WA tidak monospace)
     * - Nama barang di baris sendiri, qty di baris berikutnya dengan indent
     *   dan bold WA (*...*) agar langsung mencolok saat scan cepat
     * - Nomor urut agar distributor mudah menghitung item
     */
    window.roCopyWa = function (groupIdx) {
        var dd = document.getElementById('roExportDd' + groupIdx);
        if (!dd) return;

        var table   = document.querySelector('.ro-tbl-wrap table[data-group-idx="' + groupIdx + '"]');
        var checked = table ? table.querySelectorAll('.ro-export-chk:checked') : [];

        if (checked.length === 0) {
            alert('Pilih minimal 1 barang (kolom centang "Export") untuk di-copy.');
            dd.classList.remove('open');
            return;
        }

        var distNama = dd.getAttribute('data-distributor-nama') || 'Distributor';
        var today    = new Date().toLocaleDateString('id-ID', {
            day: 'numeric', month: 'long', year: 'numeric'
        });

        var lines = [];
        lines.push('📦 *PESANAN BARANG*');
        lines.push('📅 ' + today);
        lines.push('🏢 ' + distNama);
        lines.push('');

        var no = 1;
        checked.forEach(function (chk) {
            var goodId  = chk.getAttribute('data-good-id');
            var row     = chk.closest('tr');
            var nameEl  = row ? row.querySelector('.ro-item-name') : null;
            var qtyEl   = document.getElementById('roQty' + goodId);
            var unitEl  = row ? row.querySelector('.ro-qty-unit') : null;

            var nama = nameEl ? nameEl.textContent.trim() : ('Barang #' + goodId);
            var qty  = qtyEl  ? (parseInt(qtyEl.value, 10) || 0) : 0;
            var unit = unitEl ? unitEl.textContent.trim() : '';

            // Format: "1. Indomie Goreng"
            //         "   → *50 Dus*"
            // Pemisahan nama & qty ke 2 baris supaya tidak terlalu panjang
            // di layar HP — kebanyakan orang WA-nya dipakai di HP 5-6 inci.
            lines.push(no + '. ' + nama);
            lines.push('   \u2192 *' + qty + ' ' + unit + '*');
            no++;
        });

        lines.push('');
        lines.push('_Total: ' + (no - 1) + ' jenis barang_');

        var text = lines.join('\n');

        // Salin ke clipboard — gunakan Clipboard API modern dulu (lebih andal
        // di browser terbaru), fallback ke execCommand untuk browser lama.
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(function () {
                roShowToast('✅ Teks pesanan berhasil disalin!');
            }).catch(function () {
                roFallbackCopy(text);
            });
        } else {
            roFallbackCopy(text);
        }

        dd.classList.remove('open');
    };

    function roFallbackCopy(text) {
        var ta = document.createElement('textarea');
        ta.value = text;
        ta.style.cssText = 'position:fixed;top:-9999px;left:-9999px;opacity:0';
        document.body.appendChild(ta);
        ta.focus();
        ta.select();
        try {
            document.execCommand('copy');
            roShowToast('✅ Teks pesanan berhasil disalin!');
        } catch (e) {
            roShowToast('⚠️ Gagal menyalin. Coba lagi.');
        }
        document.body.removeChild(ta);
    }

    var _toastTimer = null;
    function roShowToast(msg) {
        var el = document.getElementById('roToast');
        if (!el) return;
        el.textContent = msg;
        el.classList.add('show');
        if (_toastTimer) clearTimeout(_toastTimer);
        _toastTimer = setTimeout(function () {
            el.classList.remove('show');
        }, 2800);
    }

    function roFillGoodIds(checkedNodeList) {
        var idsHolder  = document.getElementById('roExportGoodIds');
        var qtyHolder  = document.getElementById('roExportQtyOverrides');
        idsHolder.innerHTML = '';
        qtyHolder.innerHTML = '';

        checkedNodeList.forEach(function (chk) {
            var goodId = chk.getAttribute('data-good-id');

            var idInput   = document.createElement('input');
            idInput.type  = 'hidden';
            idInput.name  = 'good_ids[]';
            idInput.value = goodId;
            idsHolder.appendChild(idInput);

            // Ambil nilai Qty Order TERKINI dari textfield (bisa sudah diubah
            // manual oleh user), supaya yang ter-export sesuai apa yang
            // terlihat di layar, bukan angka rekomendasi awal.
            var qtyInput = document.getElementById('roQty' + goodId);
            if (qtyInput) {
                var qtyOverride   = document.createElement('input');
                qtyOverride.type  = 'hidden';
                qtyOverride.name  = 'qty_overrides[' + goodId + ']';
                qtyOverride.value = qtyInput.value;
                qtyHolder.appendChild(qtyOverride);
            }
        });
    }

    /* ── Modal discontinue — mode SINGLE (1 barang dari kolom Aksi) ──── */
    var ROD_DEFAULT_REASON = 'Sudah lama tidak order';

    window.roOpenDiscModal = function (goodId, name, kat) {
        _discTargets = [{ id: goodId, name: name }];

        document.getElementById('roModalTitle').textContent = '🗑️ Konfirmasi Discontinue';
        document.getElementById('roModalName').textContent  = name;
        document.getElementById('roModalMeta').textContent  = 'Kategori: ' + kat;
        document.getElementById('roModalList').style.display = 'none';

        roResetModalState();
        document.getElementById('roDiscModal').classList.add('open');
        setTimeout(function () {
            var ta = document.getElementById('roDiscReason');
            ta.focus();
            ta.select(); // teks default langsung terseleksi, mudah diganti kalau perlu
        }, 120);
    };

    /* ── Modal discontinue — mode BULK (beberapa barang tercentang) ──── */
    window.roOpenBulkDiscModal = function (groupIdx) {
        var table = document.querySelector('.ro-tbl-wrap table[data-group-idx="' + groupIdx + '"]');
        if (!table) return;

        var checked = table.querySelectorAll('.ro-disc-chk:checked');
        if (checked.length === 0) return;

        _discTargets = Array.prototype.map.call(checked, function (chk) {
            var row = chk.closest('tr');
            var nameEl = row ? row.querySelector('.ro-item-name') : null;
            return {
                id:   chk.getAttribute('data-good-id'),
                name: nameEl ? nameEl.textContent.trim() : ('Barang #' + chk.getAttribute('data-good-id')),
            };
        });

        document.getElementById('roModalTitle').textContent = '🗑️ Konfirmasi Discontinue (' + _discTargets.length + ' barang)';
        document.getElementById('roModalName').textContent  = _discTargets.length + ' barang akan di-discontinue sekaligus:';
        document.getElementById('roModalMeta').textContent  = '';

        var listEl = document.getElementById('roModalList');
        listEl.innerHTML = _discTargets.map(function (t, i) {
            return '<li><span class="num">' + (i + 1) + '.</span> ' + roEscapeHtml(t.name) + '</li>';
        }).join('');
        listEl.style.display = 'block';

        roResetModalState();
        document.getElementById('roDiscModal').classList.add('open');
        setTimeout(function () {
            var ta = document.getElementById('roDiscReason');
            ta.focus();
            ta.select();
        }, 120);
    };

    function roEscapeHtml(str) {
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function roResetModalState() {
        document.getElementById('roDiscReason').value = ROD_DEFAULT_REASON;
        document.getElementById('roDiscErr').style.display = 'none';
        document.getElementById('roModalProgress').style.display = 'none';
        document.getElementById('roModalProgress').textContent = '';
        var okBtn = document.querySelector('.ro-btn-ok');
        var cancelBtn = document.querySelector('.ro-btn-cancel');
        okBtn.disabled = false;
        cancelBtn.disabled = false;
        okBtn.textContent = '🗑️ Ya, Discontinue';
    }

    window.roCloseDiscModal = function () {
        document.getElementById('roDiscModal').classList.remove('open');
        _discTargets = [];
    };

    /* ── Submit: 1 request per barang, dijalankan berurutan ──────────── */
    window.roSubmitDisc = function () {
        var reason = document.getElementById('roDiscReason').value.trim();
        if (reason.length < 5) {
            document.getElementById('roDiscErr').style.display = 'block';
            document.getElementById('roDiscReason').focus();
            return;
        }
        document.getElementById('roDiscErr').style.display = 'none';

        if (_discTargets.length === 0) return;

        var okBtn        = document.querySelector('.ro-btn-ok');
        var cancelBtn    = document.querySelector('.ro-btn-cancel');
        var progressEl   = document.getElementById('roModalProgress');
        var tokenEl      = document.querySelector('#roDiscForm input[name="_token"]');
        var token        = tokenEl ? tokenEl.value : '';
        var total        = _discTargets.length;
        var failedNames  = [];

        okBtn.disabled = true;
        cancelBtn.disabled = true;
        progressEl.style.display = 'block';

        function discontinueOne(index) {
            if (index >= total) {
                // Semua selesai diproses
                if (failedNames.length > 0) {
                    alert(
                        'Selesai, tapi ' + failedNames.length + ' barang gagal di-discontinue:\n- ' +
                        failedNames.join('\n- ')
                    );
                }
                window.location.reload();
                return;
            }

            var target = _discTargets[index];
            progressEl.textContent = total > 1
                ? ('Memproses ' + (index + 1) + ' dari ' + total + ': ' + target.name + ' ...')
                : 'Memproses...';
            okBtn.textContent = total > 1
                ? ('⏳ ' + (index + 1) + '/' + total)
                : '⏳ Memproses...';

            fetch(_discBaseUrl + '/' + target.id + '/discontinue', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN':    token,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept':          'application/json, text/html',
                },
                body: new URLSearchParams({ reason: reason, _token: token }),
            })
            .then(function () {
                discontinueOne(index + 1);
            })
            .catch(function () {
                failedNames.push(target.name);
                discontinueOne(index + 1); // tetap lanjut ke barang berikutnya walau 1 gagal
            });
        }

        discontinueOne(0);
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
