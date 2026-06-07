@extends('layout.user', ['role' => 'admin', 'title' => 'Analisis Pergerakan Barang'])

@section('content')
@php
    $goods             = $goods             ?? collect();
    $summary           = $summary           ?? [];
    $categories        = $categories        ?? collect();
    $discontinuedCount = $discontinuedCount ?? 0;
    $startDate         = $startDate         ?? date('Y-m-d', strtotime('-90 days'));
    $endDate           = $endDate           ?? date('Y-m-d');
    $kategori          = $kategori          ?? null;
    $status            = $status            ?? 'all';
    $sortBy            = $sortBy            ?? 'total_omzet';
    $sortDir           = $sortDir           ?? 'desc';
    $days              = $days              ?? 90;
    $s = array_merge([
        'total'=>0,'fastCount'=>0,'slowCount'=>0,'deadCount'=>0,
        'totalOmzet'=>0,'totalLaba'=>0,'totalNilaiStok'=>0,
        'reorderCount'=>0,'discontinueCount'=>0,'reviewCount'=>0,
    ], $summary);
    $isDiscTab = ($status === 'discontinued');
@endphp

<style>
/* Scoped ke .gm-wrap agar tidak nabrak navbar/layout global */
.gm-wrap { padding: 1rem 1.25rem 2rem; font-family: 'Segoe UI', system-ui, sans-serif; }
.gm-wrap *, .gm-wrap *::before, .gm-wrap *::after { box-sizing: border-box; }

/* Tokens */
.gm-wrap {
    --ink:    #0f172a; --ink2: #475569; --ink3: #94a3b8;
    --surf:   #f8fafc; --card: #fff;   --bdr:  #e2e8f0; --bdr2: #f1f5f9;
    --fast:   #16a34a; --fast-bg:#f0fdf4; --fast-b:#bbf7d0;
    --slow:   #d97706; --slow-bg:#fffbeb; --slow-b:#fde68a;
    --dead:   #dc2626; --dead-bg:#fef2f2; --dead-b:#fecaca;
    --disc:   #6d28d9; --disc-bg:#f5f3ff; --disc-b:#ddd6fe;
    --blue:   #2563eb; --blue-bg:#eff6ff; --blue-b:#bfdbfe;
}

/* Header */
.gm-head { display:flex; justify-content:space-between; align-items:flex-start;
           flex-wrap:wrap; gap:.75rem; margin-bottom:1rem; }
.gm-head h1 { font-size:1.25rem; font-weight:800; color:var(--ink); margin:0; }
.gm-head p  { font-size:.8rem; color:var(--ink2); margin:.15rem 0 0; }
.gm-badge   { background:var(--blue-bg); border:1px solid var(--blue-b); color:var(--blue);
              font-size:.72rem; font-weight:600; padding:.3rem .75rem;
              border-radius:9999px; white-space:nowrap; align-self:flex-start; }

/* Alert */
.gm-alert { padding:.65rem 1rem; border-radius:.5rem; margin-bottom:.875rem;
            font-size:.8125rem; font-weight:500; }
.gm-alert-ok  { background:var(--fast-bg); border:1px solid var(--fast-b); color:#15803d; }
.gm-alert-err { background:var(--dead-bg); border:1px solid var(--dead-b); color:#b91c1c; }

/* Filter bar */
.gm-filter { background:var(--card); border:1px solid var(--bdr); border-radius:.6rem;
             padding:.75rem 1rem; margin-bottom:.875rem;
             display:flex; align-items:flex-end; gap:.6rem; flex-wrap:wrap; }
.gm-fg { display:flex; flex-direction:column; gap:.2rem; }
.gm-fg label { font-size:.65rem; font-weight:700; color:var(--ink3);
               text-transform:uppercase; letter-spacing:.08em; }
.gm-fg input, .gm-fg select { padding:.38rem .65rem; border:1px solid var(--bdr);
    border-radius:.4rem; font-size:.8rem; color:var(--ink); background:var(--surf);
    min-width:110px; line-height:1.4; }
.gm-fg input:focus,.gm-fg select:focus { outline:none; border-color:var(--blue); }
.gm-btn-prim { padding:.4rem 1rem; background:var(--blue); color:#fff; border:none;
               border-radius:.4rem; font-size:.8rem; font-weight:600; cursor:pointer;
               align-self:flex-end; white-space:nowrap; }
.gm-btn-reset { padding:.4rem .75rem; background:var(--surf); color:var(--ink2);
                border:1px solid var(--bdr); border-radius:.4rem; font-size:.8rem;
                cursor:pointer; align-self:flex-end; text-decoration:none;
                display:inline-block; white-space:nowrap; }

/* Pills */
.gm-pills { display:flex; gap:.4rem; flex-wrap:wrap; margin-bottom:.875rem; }
.gm-pill  { padding:.3rem .8rem; border-radius:9999px; font-size:.775rem; font-weight:600;
            cursor:pointer; border:2px solid transparent; text-decoration:none;
            display:inline-block; transition:box-shadow .15s; }
.gm-pill-all  { background:var(--ink);     color:#fff; }
.gm-pill-fast { background:var(--fast-bg); color:var(--fast); border-color:var(--fast-b); }
.gm-pill-slow { background:var(--slow-bg); color:var(--slow); border-color:var(--slow-b); }
.gm-pill-dead { background:var(--dead-bg); color:var(--dead); border-color:var(--dead-b); }
.gm-pill-disc { background:var(--disc-bg); color:var(--disc); border-color:var(--disc-b); }
.gm-pill.active { box-shadow:0 0 0 2.5px var(--blue); }
.gm-pill-all.active { background:var(--blue); }

/* KPI grid */
.gm-kpi-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(140px,1fr));
               gap:.625rem; margin-bottom:.875rem; }
.gm-kpi { background:var(--card); border:1px solid var(--bdr);
          border-radius:.6rem; padding:.75rem .875rem; }
.gm-kpi .kl { font-size:.65rem; font-weight:700; color:var(--ink3);
              text-transform:uppercase; letter-spacing:.07em; margin-bottom:.3rem; }
.gm-kpi .kv { font-size:1.35rem; font-weight:800; color:var(--ink); line-height:1; }
.gm-kpi .ks { font-size:.7rem; color:var(--ink3); margin-top:.2rem; }
.gm-kpi.k-fast { border-left:4px solid var(--fast); } .gm-kpi.k-fast .kv{color:var(--fast);}
.gm-kpi.k-slow { border-left:4px solid var(--slow); } .gm-kpi.k-slow .kv{color:var(--slow);}
.gm-kpi.k-dead { border-left:4px solid var(--dead); } .gm-kpi.k-dead .kv{color:var(--dead);}
.gm-kpi.k-disc { border-left:4px solid var(--disc); } .gm-kpi.k-disc .kv{color:var(--disc);}
.gm-kpi.k-blue { border-left:4px solid var(--blue); } .gm-kpi.k-blue .kv{color:var(--blue);}

/* Action cards */
.gm-ac-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(175px,1fr));
              gap:.625rem; margin-bottom:1rem; }
.gm-ac { background:var(--card); border:1px solid var(--bdr); border-radius:.6rem;
         padding:.75rem .875rem; display:flex; align-items:center; gap:.625rem; }
.gm-ac-ico { width:2rem; height:2rem; border-radius:.4rem; display:flex;
             align-items:center; justify-content:center; font-size:1rem; flex-shrink:0; }
.gm-ac .al { font-size:.65rem; font-weight:700; color:var(--ink3); text-transform:uppercase; }
.gm-ac .av { font-size:1.1rem; font-weight:800; color:var(--ink); }
.gm-ac-r .gm-ac-ico { background:#ffe4e6; }
.gm-ac-d .gm-ac-ico { background:var(--disc-bg); }
.gm-ac-v .gm-ac-ico { background:var(--slow-bg); }
.gm-ac-s .gm-ac-ico { background:var(--blue-bg); }

/* Table card */
.gm-tbl-card { background:var(--card); border:1px solid var(--bdr);
               border-radius:.6rem; overflow:hidden; margin-bottom:1rem; }
.gm-tbl-head { padding:.625rem 1rem; border-bottom:1px solid var(--bdr);
               display:flex; align-items:center; justify-content:space-between; }
.gm-tbl-head h2 { font-size:.875rem; font-weight:700; color:var(--ink); margin:0; }
.gm-tbl-cnt { font-size:.775rem; color:var(--ink3); }
.gm-tbl-wrap { overflow-x:auto; max-height:540px; overflow-y:auto; }
.gm-tbl-wrap table { width:100%; border-collapse:collapse; font-size:1.375rem;
                     table-layout:fixed; }
.gm-tbl-wrap thead th { position:sticky; top:0; z-index:2;
    background:var(--surf); padding:.55rem .75rem; text-align:left;
    font-weight:700; color:var(--ink2); border-bottom:2px solid var(--bdr);
    white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.gm-tbl-wrap tbody td { padding:.6rem .75rem; border-bottom:1px solid var(--bdr2);
                        color:var(--ink); vertical-align:middle;
                        overflow:hidden; text-overflow:ellipsis; }
.gm-tbl-wrap tbody tr:last-child td { border-bottom:none; }
.gm-tbl-wrap tbody tr:hover td { background:#f8fafc; }
.gm-tbl-wrap tbody tr.row-dead td { background:#fff9f9; }
.tr { text-align:right; } .tc { text-align:center; }

/* Column widths — fixed agar tidak melebar */
.col-no   { width:40px; }
.col-nama { width:220px; }
.col-kat  { width:110px; }
.col-stat { width:120px; }
.col-trx  { width:80px; }
.col-qty  { width:100px; }
.col-omz  { width:125px; }
.col-laba { width:125px; }
.col-mgn  { width:72px; }
.col-stok { width:88px; }
.col-dos  { width:100px; }
.col-sjl  { width:88px; }
.col-rek  { width:130px; }
.col-aksi { width:105px; }

/* Badges */
.gm-badge-tag { display:inline-flex; align-items:center; gap:.2rem; padding:.18rem .55rem;
                border-radius:9999px; font-size:1.275rem; font-weight:700; white-space:nowrap; }
.b-fast { background:var(--fast-bg); color:var(--fast); border:1px solid var(--fast-b); }
.b-slow { background:var(--slow-bg); color:var(--slow); border:1px solid var(--slow-b); }
.b-dead { background:var(--dead-bg); color:var(--dead); border:1px solid var(--dead-b); }
.b-disc { background:var(--disc-bg); color:var(--disc); border:1px solid var(--disc-b); }
.b-blue { background:var(--blue-bg); color:var(--blue); border:1px solid var(--blue-b); }
.b-grn  { background:#f0fdf4; color:#15803d; border:1px solid #bbf7d0; }
.b-org  { background:var(--slow-bg); color:var(--slow); border:1px solid var(--slow-b); }
.b-gry  { background:#f1f5f9; color:#475569; border:1px solid #e2e8f0; }

.rec-maintain      { background:#f0fdf4; color:#15803d;  border:1px solid #bbf7d0; }
.rec-reorder       { background:#fff7ed; color:#c2410c;  border:1px solid #fed7aa; }
.rec-reorder_urgent{ background:var(--dead-bg); color:#b91c1c; border:1px solid var(--dead-b); font-weight:800; }
.rec-monitor       { background:#fffbeb; color:#92400e;  border:1px solid #fde68a; }
.rec-reduce_order  { background:#fff7ed; color:#92400e;  border:1px solid #fed7aa; }
.rec-review        { background:#fff7ed; color:#92400e;  border:1px solid #fde68a; }
.rec-clearance     { background:var(--dead-bg); color:#9f1239; border:1px solid var(--dead-b); }
.rec-discontinue   { background:var(--disc-bg); color:var(--disc); border:1px solid var(--disc-b); }

/* Stok color */
.s-ok   { color:var(--fast); font-weight:600; }
.s-warn { color:var(--slow); font-weight:600; }
.s-crit { color:var(--dead); font-weight:600; }
.s-zero { color:var(--ink3); }

/* DoS bar */
.dos-w { display:flex; align-items:center; gap:.25rem; }
.dos-b { flex:1; height:4px; background:var(--bdr); border-radius:9999px; overflow:hidden; min-width:30px; }
.dos-f { height:4px; border-radius:9999px; }
.dos-t { font-size:1.275rem; white-space:nowrap; }

/* Action buttons */
.btn-disc { padding:.22rem .6rem; background:var(--disc-bg); color:var(--disc);
            border:1px solid var(--disc-b); border-radius:.35rem; font-size:1.275rem;
            font-weight:700; cursor:pointer; white-space:nowrap; display:inline-block; }
.btn-disc:hover { background:var(--disc); color:#fff; }
.btn-restore { padding:.22rem .6rem; background:var(--fast-bg); color:#15803d;
               border:1px solid var(--fast-b); border-radius:.35rem; font-size:1.275rem;
               font-weight:700; cursor:pointer; white-space:nowrap; }
.btn-restore:hover { background:#15803d; color:#fff; }

/* Legend */
.gm-legend { padding:.625rem 1rem; border-top:1px solid var(--bdr);
             display:flex; flex-wrap:wrap; gap:.35rem; align-items:center; }
.gm-legend-lbl { font-size:.68rem; font-weight:700; color:var(--ink3); }

/* Distribusi */
.gm-dist { background:var(--card); border:1px solid var(--bdr); border-radius:.6rem;
           padding:1rem; margin-top:.875rem; }
.gm-dist h2 { font-size:.8375rem; font-weight:700; margin:0 0 .75rem; }
.dist-row  { display:flex; align-items:center; gap:.5rem; font-size:.775rem; margin-bottom:.3rem; }
.dist-lbl  { min-width:105px; color:var(--ink2); }
.dist-bw   { flex:1; height:7px; background:var(--bdr); border-radius:9999px; overflow:hidden; }
.dist-bf   { height:7px; border-radius:9999px; }
.dist-cnt  { min-width:26px; text-align:right; font-weight:600; }
.dist-pct  { min-width:38px; text-align:right; font-size:.7rem; color:var(--ink3); }

/* Empty */
.gm-empty { text-align:center; padding:2.5rem 1rem; color:var(--ink3); }
.gm-empty .ei { font-size:2rem; margin-bottom:.5rem; }

/* Tooltip */
[data-gm-tip] { position:relative; cursor:help; }
[data-gm-tip]:hover::after { content:attr(data-gm-tip); position:absolute; bottom:130%;
    left:50%; transform:translateX(-50%); background:var(--ink); color:#fff;
    font-size:.66rem; padding:.22rem .5rem; border-radius:.3rem;
    white-space:nowrap; z-index:9999; pointer-events:none; }

/* ── MODAL ─────────────────────────────────────────────────────── */
.gm-modal-overlay { display:none; position:fixed; inset:0;
                    background:rgba(15,23,42,.5); z-index:99999;
                    align-items:center; justify-content:center; padding:1rem; }
.gm-modal-overlay.open { display:flex; }
.gm-modal { background:#fff; border-radius:.75rem; width:100%; max-width:430px;
            box-shadow:0 20px 60px rgba(0,0,0,.25); overflow:hidden; }
.gm-modal-hd { padding:.875rem 1.1rem; border-bottom:1px solid var(--bdr);
               display:flex; align-items:center; justify-content:space-between; }
.gm-modal-hd h3 { font-size:.9375rem; font-weight:700; color:var(--ink); margin:0; }
.gm-modal-close { background:none; border:none; font-size:1.125rem; cursor:pointer;
                  color:var(--ink3); padding:.1rem .3rem; border-radius:.3rem; }
.gm-modal-close:hover { color:var(--ink); background:var(--surf); }
.gm-modal-bd { padding:1.1rem; }
.gm-modal-name { font-weight:700; font-size:.9rem; color:var(--ink); margin-bottom:.15rem; }
.gm-modal-meta { font-size:.775rem; color:var(--ink2); margin-bottom:.875rem; }
.gm-modal-warn { background:var(--disc-bg); border:1px solid var(--disc-b);
                 border-radius:.45rem; padding:.6rem .875rem; font-size:.775rem;
                 color:var(--disc); margin-bottom:.875rem; line-height:1.55; }
.gm-modal-lbl  { font-size:.775rem; font-weight:700; color:var(--ink2);
                 margin-bottom:.3rem; display:block; }
.gm-modal-ta   { width:100%; padding:.55rem .75rem; border:1px solid var(--bdr);
                 border-radius:.45rem; font-size:.8125rem; resize:vertical;
                 min-height:85px; font-family:inherit; color:var(--ink); }
.gm-modal-ta:focus { outline:none; border-color:var(--disc); }
.gm-modal-err  { font-size:.72rem; color:var(--dead); margin-top:.3rem; display:none; }
.gm-modal-ft   { padding:.75rem 1.1rem; border-top:1px solid var(--bdr);
                 display:flex; justify-content:flex-end; gap:.5rem; }
.gm-btn-cancel { padding:.4rem .9rem; background:var(--surf); color:var(--ink2);
                 border:1px solid var(--bdr); border-radius:.4rem;
                 font-size:.8125rem; cursor:pointer; }
.gm-btn-ok     { padding:.4rem 1rem; background:var(--disc); color:#fff; border:none;
                 border-radius:.4rem; font-size:.8125rem; font-weight:700; cursor:pointer; }
.gm-btn-ok:hover { background:#5b21b6; }

@media(max-width:768px) {
    .gm-kpi-grid { grid-template-columns:repeat(3,1fr); }
    .gm-ac-grid  { grid-template-columns:repeat(2,1fr); }
    .gm-filter   { flex-direction:column; }
}
@media(max-width:480px) { .gm-kpi-grid { grid-template-columns:repeat(2,1fr); } }
</style>

<div class="content-wrapper">
<section class="content">
<div class="gm-wrap">

{{-- Alerts --}}
@if(session('success'))
    <div class="gm-alert gm-alert-ok">✅ {{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="gm-alert gm-alert-err">❌ {{ session('error') }}</div>
@endif

{{-- Header --}}
<div class="gm-head">
    <div>
        <h1>📊 Analisis Pergerakan Barang</h1>
        <p>Fast moving · Slow moving · Dead stock · Discontinue</p>
    </div>
    @if(!$isDiscTab)
    <span class="gm-badge">
        {{ \Carbon\Carbon::parse($startDate)->isoFormat('D MMM Y') }} →
        {{ \Carbon\Carbon::parse($endDate)->isoFormat('D MMM Y') }}
        · {{ $days }} hari
    </span>
    @endif
</div>

{{-- Filter --}}
<form method="GET" action="{{ route('admin.reports.movement') }}" id="gmFilterForm">
    <div class="gm-filter">
        @if(!$isDiscTab)
        <div class="gm-fg">
            <label>Dari</label>
            <input type="date" name="start_date" value="{{ $startDate }}">
        </div>
        <div class="gm-fg">
            <label>Sampai</label>
            <input type="date" name="end_date" value="{{ $endDate }}">
        </div>
        @endif
        <div class="gm-fg">
            <label>Kategori</label>
            <select name="kategori">
                <option value="">Semua Kategori</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ $kategori == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>
        @if(!$isDiscTab)
        <div class="gm-fg">
            <label>Urutkan</label>
            <select name="sort_by">
                <option value="total_omzet"     {{ $sortBy==='total_omzet'?'selected':'' }}>Omzet</option>
                <option value="total_transaksi" {{ $sortBy==='total_transaksi'?'selected':'' }}>Transaksi</option>
                <option value="total_qty"       {{ $sortBy==='total_qty'?'selected':'' }}>Qty</option>
                <option value="days_of_stock"   {{ $sortBy==='days_of_stock'?'selected':'' }}>Hari Stok</option>
                <option value="days_since_trx"  {{ $sortBy==='days_since_trx'?'selected':'' }}>Sejak Jual</option>
                <option value="stok_sekarang"   {{ $sortBy==='stok_sekarang'?'selected':'' }}>Stok</option>
                <option value="urgency"         {{ $sortBy==='urgency'?'selected':'' }}>Urgensi</option>
            </select>
        </div>
        <div class="gm-fg">
            <label>Arah</label>
            <select name="sort_dir">
                <option value="desc" {{ $sortDir==='desc'?'selected':'' }}>↓ Terbesar</option>
                <option value="asc"  {{ $sortDir==='asc'?'selected':'' }}>↑ Terkecil</option>
            </select>
        </div>
        @endif
        <input type="hidden" name="status" id="gmStatusInput" value="{{ $status }}">
        <button type="submit" class="gm-btn-prim">🔍 Terapkan</button>
        <a href="{{ route('admin.reports.movement') }}" class="gm-btn-reset">Reset</a>
    </div>
</form>

{{-- Pills --}}
<div class="gm-pills">
    @php $pills = [
        'all'          => ['l'=>'🔢 Semua ('.$s['total'].')',          'c'=>'gm-pill-all'],
        'fast'         => ['l'=>'🚀 Fast ('.$s['fastCount'].')',        'c'=>'gm-pill-fast'],
        'slow'         => ['l'=>'🐢 Slow ('.$s['slowCount'].')',        'c'=>'gm-pill-slow'],
        'dead'         => ['l'=>'💀 Dead ('.$s['deadCount'].')',        'c'=>'gm-pill-dead'],
        'discontinued' => ['l'=>'🗑️ Disc. ('.$discontinuedCount.')',    'c'=>'gm-pill-disc'],
    ]; @endphp
    @foreach($pills as $k => $p)
        <a href="javascript:void(0)"
           class="gm-pill {{ $p['c'] }} {{ $status===$k?'active':'' }}"
           onclick="gmSetStatus('{{ $k }}')">{{ $p['l'] }}</a>
    @endforeach
</div>

{{-- KPI --}}
@if(!$isDiscTab)
<div class="gm-kpi-grid">
    <div class="gm-kpi k-fast">
        <div class="kl">Fast Moving</div>
        <div class="kv">{{ number_format($s['fastCount']) }}</div>
        <div class="ks">{{ $s['total']>0?number_format($s['fastCount']/$s['total']*100,0).'%':'-' }}</div>
    </div>
    <div class="gm-kpi k-slow">
        <div class="kl">Slow Moving</div>
        <div class="kv">{{ number_format($s['slowCount']) }}</div>
        <div class="ks">{{ $s['total']>0?number_format($s['slowCount']/$s['total']*100,0).'%':'-' }}</div>
    </div>
    <div class="gm-kpi k-dead">
        <div class="kl">Dead Stock</div>
        <div class="kv">{{ number_format($s['deadCount']) }}</div>
        <div class="ks">{{ $s['total']>0?number_format($s['deadCount']/$s['total']*100,0).'%':'-' }}</div>
    </div>
    <div class="gm-kpi k-disc">
        <div class="kl">Discontinued</div>
        <div class="kv">{{ number_format($discontinuedCount) }}</div>
        <div class="ks">tidak dihitung</div>
    </div>
    <div class="gm-kpi k-blue">
        <div class="kl">Total Omzet</div>
        <div class="kv" style="font-size:1rem">Rp {{ number_format($s['totalOmzet']/1000000,1) }}jt</div>
        <div class="ks">Laba Rp {{ number_format($s['totalLaba']/1000000,1) }}jt</div>
    </div>
    <div class="gm-kpi">
        <div class="kl">Nilai Stok</div>
        <div class="kv" style="font-size:1rem">Rp {{ number_format($s['totalNilaiStok']/1000000,1) }}jt</div>
        <div class="ks">HPP tersimpan</div>
    </div>
</div>
<div class="gm-ac-grid">
    <div class="gm-ac gm-ac-r"><div class="gm-ac-ico">📦</div><div><div class="al">Perlu Reorder</div><div class="av">{{ $s['reorderCount'] }}</div></div></div>
    <div class="gm-ac gm-ac-d"><div class="gm-ac-ico">🗑️</div><div><div class="al">Kandidat Disc.</div><div class="av">{{ $s['discontinueCount'] }}</div></div></div>
    <div class="gm-ac gm-ac-v"><div class="gm-ac-ico">⚠️</div><div><div class="al">Perlu Review</div><div class="av">{{ $s['reviewCount'] }}</div></div></div>
    <div class="gm-ac gm-ac-s"><div class="gm-ac-ico">📊</div><div><div class="al">Periode</div><div class="av">{{ $days }} hr</div></div></div>
</div>
@else
<div class="gm-kpi-grid" style="grid-template-columns:repeat(auto-fill,minmax(200px,1fr))">
    <div class="gm-kpi k-disc">
        <div class="kl">Total Discontinued</div>
        <div class="kv">{{ number_format($goods->count()) }}</div>
        <div class="ks">barang tidak aktif</div>
    </div>
</div>
@endif

{{-- ── TABEL ─────────────────────────────────────────────────────── --}}
<div class="gm-tbl-card">
    <div class="gm-tbl-head">
        <h2>{{ $isDiscTab ? '🗑️ Barang Discontinued' : '📋 Detail Pergerakan Barang' }}</h2>
        <span class="gm-tbl-cnt">{{ $goods->count() }} barang</span>
    </div>
    <div class="gm-tbl-wrap">

    @if($isDiscTab)
    {{-- Tabel Discontinued --}}
    <table>
        <colgroup>
            <col class="col-no"><col style="width:210px"><col style="width:100px">
            <col style="width:70px"><col style="width:auto"><col style="width:100px">
            <col style="width:90px"><col style="width:105px">
        </colgroup>
        <thead><tr>
            <th>#</th><th>Barang</th><th>Kategori</th>
            <th class="tr">Stok</th><th>Alasan</th>
            <th>Tanggal</th><th>Oleh</th><th class="tc">Aksi</th>
        </tr></thead>
        <tbody>
        @forelse($goods as $i => $g)
        <tr>
            <td style="color:var(--ink3)">{{ $i+1 }}</td>
            <td>
                <div style="font-weight:600;font-size:1.3rem;white-space:normal">{{ $g->nama }}</div>
                <div style="font-size:1.18rem;color:var(--ink3)">{{ $g->kode ?? '' }}@if($g->merk&&$g->merk!=='-') · {{ $g->merk }}@endif</div>
            </td>
            <td style="font-size:1.25rem;color:var(--ink2)">{{ $g->kategori }}</td>
            <td class="tr">
                <span class="{{ $g->stok_sekarang>0?'s-warn':'s-zero' }}">
                    {{ $g->stok_sekarang>0 ? number_format($g->stok_sekarang,0,',','.') : 'Kosong' }}
                </span>
            </td>
            <td style="font-size:1.25rem;color:var(--ink2);white-space:normal">
                {{ $g->discontinued_reason !== '-' ? $g->discontinued_reason : '—' }}
            </td>
            <td style="font-size:1.22rem;color:var(--ink2);white-space:nowrap">
                {{ $g->discontinued_at ? \Carbon\Carbon::parse($g->discontinued_at)->isoFormat('D MMM Y') : '—' }}
            </td>
            <td style="font-size:1.22rem;color:var(--ink2)">{{ $g->discontinued_by }}</td>
            <td class="tc">
                <form method="POST" action="{{ route('admin.reports.movement.restore', $g->good_id) }}"
                      onsubmit="return confirm('Aktifkan kembali barang ini?')">
                    @csrf
                    <button type="submit" class="btn-restore">♻️ Aktifkan</button>
                </form>
            </td>
        </tr>
        @empty
        <tr><td colspan="8"><div class="gm-empty"><div class="ei">🎉</div><p>Tidak ada barang discontinued.</p></div></td></tr>
        @endforelse
        </tbody>
    </table>

    @else
    {{-- Tabel Movement Normal --}}
    <table>
        <colgroup>
            <col class="col-no"><col class="col-nama"><col class="col-kat">
            <col class="col-stat"><col class="col-trx"><col class="col-qty">
            <col class="col-omz"><col class="col-laba"><col class="col-mgn">
            <col class="col-stok"><col class="col-dos"><col class="col-sjl">
            <col class="col-rek"><col class="col-aksi">
        </colgroup>
        <thead><tr>
            <th class="col-no">#</th>
            <th class="col-nama">Barang</th>
            <th class="col-kat">Kategori</th>
            <th class="col-stat">Status</th>
            <th class="col-trx tr" data-gm-tip="Jml transaksi periode ini">Trx</th>
            <th class="col-qty tr" data-gm-tip="Qty terjual (satuan terkecil)">Qty</th>
            <th class="col-omz tr">Omzet</th>
            <th class="col-laba tr">Laba</th>
            <th class="col-mgn tr" data-gm-tip="(jual-beli)/jual × 100">Mgn</th>
            <th class="col-stok tr" data-gm-tip="Stok saat ini (satuan terkecil)">Stok</th>
            <th class="col-dos tr" data-gm-tip="Estimasi hari stok habis">Hari Stok</th>
            <th class="col-sjl tr" data-gm-tip="Hari sejak transaksi terakhir">Sjk Jual</th>
            <th class="col-rek">Rekomendasi</th>
            <th class="col-aksi tc">Aksi</th>
        </tr></thead>
        <tbody>
        @forelse($goods as $i => $g)
        @php
            if ($g->days_of_stock<=7)        $dc='#dc2626';
            elseif($g->days_of_stock<=14)    $dc='#d97706';
            elseif($g->days_of_stock<=30)    $dc='#ca8a04';
            elseif($g->days_of_stock>=9999)  $dc='#94a3b8';
            else                             $dc='#16a34a';
            $dp = $g->days_of_stock>=9999 ? 100 : min(100,($g->days_of_stock/90)*100);

            if($g->stok_sekarang<=0)      $sc='s-zero';
            elseif($g->stok_sekarang<=5)  $sc='s-crit';
            elseif($g->stok_sekarang<=20) $sc='s-warn';
            else                          $sc='s-ok';

            $sb = $g->status_color==='orange'?'b-slow':($g->status_color==='red'?'b-dead':'b-fast');
            $isDead = ($g->status==='dead');
        @endphp
        <tr class="{{ $isDead?'row-dead':'' }}">
            <td style="color:var(--ink3)">{{ $i+1 }}</td>
            <td>
                <div style="font-weight:600;font-size:1.375rem;white-space:normal;line-height:1.35">{{ $g->nama }}</div>
                <div style="font-size:1.275rem;color:var(--ink3)">
                    {{ $g->kode??'' }}@if($g->merk&&$g->merk!=='-') · {{ $g->merk }}@endif · {{ $g->satuan }}
                </div>
            </td>
            <td style="font-size:1.22rem;color:var(--ink2)">{{ $g->kategori }}</td>
            <td>
                <span class="gm-badge-tag {{ $sb }}">
                    {{ $g->status==='fast'?'🚀':($g->status==='slow'?'🐢':'💀') }}
                    {{ $g->status_label }}
                </span>
            </td>
            <td class="tr">
                <strong>{{ number_format($g->total_transaksi) }}</strong>
                <div style="font-size:1.15rem;color:var(--ink3)">~{{ number_format($g->avg_qty_per_day,1) }}/hr</div>
            </td>
            <td class="tr" style="font-size:1.375rem">{{ number_format($g->total_qty,0,',','.') }}</td>
            <td class="tr" style="font-size:1.375rem">Rp {{ number_format($g->total_omzet,0,',','.') }}</td>
            <td class="tr" style="font-size:1.375rem;color:{{ $g->total_laba>=0?'var(--fast)':'var(--dead)' }}">
                Rp {{ number_format($g->total_laba,0,',','.') }}
            </td>
            <td class="tr">
                @if($g->margin_pct>=20)<span class="gm-badge-tag b-grn">{{ $g->margin_pct }}%</span>
                @elseif($g->margin_pct>=10)<span class="gm-badge-tag b-blue">{{ $g->margin_pct }}%</span>
                @elseif($g->margin_pct>0)<span class="gm-badge-tag b-org">{{ $g->margin_pct }}%</span>
                @else<span class="gm-badge-tag b-gry">-</span>@endif
            </td>
            <td class="tr">
                <span class="{{ $sc }}">{{ $g->stok_sekarang<=0?'Habis':number_format($g->stok_sekarang,0,',','.') }}</span>
                @if($g->stok_sekarang>0)<div style="font-size:1.15rem;color:var(--ink3)">Rp{{ number_format($g->nilai_stok/1000,0) }}rb</div>@endif
            </td>
            <td class="tr">
                @if($g->stok_sekarang<=0)<span style="color:var(--ink3)">—</span>
                @elseif($g->days_of_stock>=9999)<span style="color:var(--ink3)">∞</span>
                @else
                <div class="dos-w">
                    <div class="dos-b"><div class="dos-f" style="width:{{ $dp }}%;background:{{ $dc }}"></div></div>
                    <span class="dos-t" style="color:{{ $dc }}">{{ $g->days_of_stock }}h</span>
                </div>
                @endif
            </td>
            <td class="tr">
                @if($g->days_since_trx>=999)<span class="gm-badge-tag b-gry">Tdk pernah</span>
                @elseif($g->days_since_trx>=90)<span class="gm-badge-tag b-dead">{{ $g->days_since_trx }}h</span>
                @elseif($g->days_since_trx>=30)<span class="gm-badge-tag b-slow">{{ $g->days_since_trx }}h</span>
                @else<span class="gm-badge-tag b-fast">{{ $g->days_since_trx }}h</span>@endif
            </td>
            <td><span class="gm-badge-tag rec-{{ $g->recommendation }}">{{ $g->rec_icon }} {{ $g->rec_label }}</span></td>
            <td class="tc">
                @if($isDead)
                {{-- ✅ Tombol hanya untuk dead stock --}}
                <button type="button" class="btn-disc"
                    onclick="gmOpenModal(
                        {{ $g->good_id }},
                        '{{ addslashes($g->nama) }}',
                        '{{ addslashes($g->kategori) }}'
                    )">🗑️ Disc.</button>
                @else
                <span style="color:var(--ink3);font-size:.7rem">—</span>
                @endif
            </td>
        </tr>
        @empty
        <tr><td colspan="14"><div class="gm-empty"><div class="ei">🔍</div><p>Tidak ada barang sesuai filter.</p></div></td></tr>
        @endforelse
        </tbody>
    </table>
    @endif

    </div>{{-- end tbl-wrap --}}

    {{-- Legend --}}
    @if(!$isDiscTab)
    <div class="gm-legend">
        <span class="gm-legend-lbl">LEGENDA:</span>
        <span class="gm-badge-tag rec-maintain">✅ Pertahankan</span>
        <span class="gm-badge-tag rec-reorder">📦 Tambah Stok</span>
        <span class="gm-badge-tag rec-reorder_urgent">🚨 Reorder Segera</span>
        <span class="gm-badge-tag rec-monitor">👁️ Monitor</span>
        <span class="gm-badge-tag rec-reduce_order">📉 Kurangi Order</span>
        <span class="gm-badge-tag rec-review">⚠️ Review</span>
        <span class="gm-badge-tag rec-clearance">🏷️ Obral</span>
        <span class="gm-badge-tag rec-discontinue">🗑️ Discontinue</span>
    </div>
    @endif
</div>{{-- end tbl-card --}}

{{-- Distribusi --}}
@if(!$isDiscTab && $s['total']>0)
<div class="gm-dist">
    <h2>📊 Distribusi Status</h2>
    @php $distRows=[['l'=>'🚀 Fast Moving','n'=>$s['fastCount'],'c'=>'#16a34a'],['l'=>'🐢 Slow Moving','n'=>$s['slowCount'],'c'=>'#d97706'],['l'=>'💀 Dead Stock','n'=>$s['deadCount'],'c'=>'#dc2626']]; @endphp
    @foreach($distRows as $dr)
    @php $pct=$s['total']>0?round($dr['n']/$s['total']*100,1):0; @endphp
    <div class="dist-row">
        <div class="dist-lbl">{{ $dr['l'] }}</div>
        <div class="dist-bw"><div class="dist-bf" style="width:{{ $pct }}%;background:{{ $dr['c'] }}"></div></div>
        <div class="dist-cnt">{{ $dr['n'] }}</div>
        <div class="dist-pct">{{ $pct }}%</div>
    </div>
    @endforeach
</div>
@endif

</div>{{-- end gm-wrap --}}
</section>
</div>{{-- end content-wrapper --}}

{{-- ═══════════════════════════════════════════════════════════════════ --}}
{{-- MODAL DISCONTINUE — di luar content-wrapper agar z-index benar     --}}
{{-- ═══════════════════════════════════════════════════════════════════ --}}
<div class="gm-modal-overlay" id="gmDiscModal">
    <div class="gm-modal">
        <div class="gm-modal-hd">
            <h3>🗑️ Konfirmasi Discontinue</h3>
            <button class="gm-modal-close" type="button" onclick="gmCloseModal()">✕</button>
        </div>
        <div class="gm-modal-bd">
            <div class="gm-modal-name" id="gmModalName">—</div>
            <div class="gm-modal-meta" id="gmModalMeta">—</div>
            <div class="gm-modal-warn">
                ⚠️ Barang yang di-discontinue <strong>tidak akan muncul lagi</strong> dalam
                laporan pergerakan. Riwayat transaksi tetap tersimpan dan barang dapat
                diaktifkan kembali kapan saja dari tab <em>Disc.</em>
            </div>
            <label class="gm-modal-lbl" for="gmDiscReason">
                Alasan Discontinue <span style="color:var(--dead)">*</span>
            </label>
            <textarea class="gm-modal-ta" id="gmDiscReason"
                placeholder="Contoh: Tidak laku selama 6 bulan, digantikan produk baru..."></textarea>
            <div class="gm-modal-err" id="gmDiscErr">Alasan wajib diisi minimal 5 karakter.</div>
        </div>
        <div class="gm-modal-ft">
            <button type="button" class="gm-btn-cancel" onclick="gmCloseModal()">Batal</button>
            <button type="button" class="gm-btn-ok" onclick="gmSubmitDisc()">🗑️ Ya, Discontinue</button>
        </div>
    </div>
</div>

{{-- Form tersembunyi untuk POST discontinue --}}
<form method="POST" id="gmDiscForm" style="display:none">
    @csrf
    <input type="hidden" name="reason" id="gmDiscFormReason">
</form>

<script>
(function () {
    var _goodId  = null;
    var _baseUrl = '{{ url("admin/reports/movement") }}';

    window.gmOpenModal = function (goodId, name, kat) {
        _goodId = goodId;
        document.getElementById('gmModalName').textContent = name;
        document.getElementById('gmModalMeta').textContent = 'Kategori: ' + kat;
        document.getElementById('gmDiscReason').value = '';
        document.getElementById('gmDiscErr').style.display = 'none';
        document.getElementById('gmDiscModal').classList.add('open');
        setTimeout(function () { document.getElementById('gmDiscReason').focus(); }, 120);
    };

    window.gmCloseModal = function () {
        document.getElementById('gmDiscModal').classList.remove('open');
        _goodId = null;
    };

    window.gmSubmitDisc = function () {
        var reason = document.getElementById('gmDiscReason').value.trim();
        if (reason.length < 5) {
            document.getElementById('gmDiscErr').style.display = 'block';
            document.getElementById('gmDiscReason').focus();
            return;
        }
        document.getElementById('gmDiscErr').style.display = 'none';
        var form = document.getElementById('gmDiscForm');
        form.action = _baseUrl + '/' + _goodId + '/discontinue';
        document.getElementById('gmDiscFormReason').value = reason;
        form.submit();
    };

    window.gmSetStatus = function (val) {
        document.getElementById('gmStatusInput').value = val;
        document.getElementById('gmFilterForm').submit();
    };

    /* Tutup saat klik overlay */
    document.getElementById('gmDiscModal').addEventListener('click', function (e) {
        if (e.target === this) gmCloseModal();
    });

    /* Tutup dengan ESC */
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') gmCloseModal();
    });
}());
</script>
@endsection