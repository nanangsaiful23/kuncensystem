
@extends('layout.user', ['role' => 'admin', 'title' => 'Admin'])

@section('content')

{{-- Safety net --}}
@php
    $goods      = $goods      ?? collect();
    $summary    = $summary    ?? [];
    $categories = $categories ?? collect();
    $startDate  = $startDate  ?? date('Y-m-d', strtotime('-90 days'));
    $endDate    = $endDate    ?? date('Y-m-d');
    $kategori   = $kategori   ?? null;
    $status     = $status     ?? 'all';
    $sortBy     = $sortBy     ?? 'total_omzet';
    $sortDir    = $sortDir    ?? 'desc';
    $days       = $days       ?? 90;

    $s = array_merge([
        'total' => 0, 'fastCount' => 0, 'slowCount' => 0, 'deadCount' => 0,
        'totalOmzet' => 0, 'totalLaba' => 0, 'totalNilaiStok' => 0,
        'reorderCount' => 0, 'discontinueCount' => 0, 'reviewCount' => 0,
    ], $summary);
@endphp


@section('content')
<style>
/* ── Tokens ───────────────────────────────────────────────────── */
:root {
    --ink:      #0f172a;
    --ink-2:    #475569;
    --ink-3:    #94a3b8;
    --surface:  #f8fafc;
    --card:     #ffffff;
    --border:   #e2e8f0;
    --border-2: #f1f5f9;

    --fast:     #16a34a;
    --fast-bg:  #f0fdf4;
    --fast-b:   #bbf7d0;
    --slow:     #d97706;
    --slow-bg:  #fffbeb;
    --slow-b:   #fde68a;
    --dead:     #dc2626;
    --dead-bg:  #fef2f2;
    --dead-b:   #fecaca;
    --blue:     #2563eb;
    --blue-bg:  #eff6ff;
    --blue-b:   #bfdbfe;
}

/* ── Base ─────────────────────────────────────────────────────── */
* { box-sizing: border-box; margin: 0; padding: 0; }
body { background: var(--surface); color: var(--ink); font-family: 'Segoe UI', system-ui, sans-serif; }

.page { padding: 1.5rem; max-width: 1600px; margin: 0 auto; }

/* ── Page Header ──────────────────────────────────────────────── */
.page-head { display: flex; align-items: flex-start; justify-content: space-between;
             margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem; }
.page-head h1 { font-size: 1.5rem; font-weight: 800; color: var(--ink); letter-spacing: -.02em; }
.page-head p  { font-size: .875rem; color: var(--ink-2); margin-top: .2rem; }
.period-badge { background: var(--blue-bg); border: 1px solid var(--blue-b);
                color: var(--blue); font-size: .75rem; font-weight: 600;
                padding: .35rem .8rem; border-radius: 9999px; white-space: nowrap; }

/* ── Filter Bar ───────────────────────────────────────────────── */
.filter-bar { background: var(--card); border: 1px solid var(--border);
              border-radius: .75rem; padding: 1rem 1.25rem;
              margin-bottom: 1.25rem; display: flex;
              align-items: flex-end; gap: .75rem; flex-wrap: wrap; }
.fg { display: flex; flex-direction: column; gap: .3rem; }
.fg label { font-size: .7rem; font-weight: 700; color: var(--ink-3);
            text-transform: uppercase; letter-spacing: .08em; }
.fg input, .fg select {
    padding: .45rem .8rem; border: 1px solid var(--border);
    border-radius: .5rem; font-size: .875rem; color: var(--ink);
    background: var(--surface); min-width: 130px;
    transition: border-color .15s;
}
.fg input:focus, .fg select:focus { outline: none; border-color: var(--blue); }
.btn-primary { padding: .5rem 1.25rem; background: var(--blue); color: #fff;
               border: none; border-radius: .5rem; font-size: .875rem;
               font-weight: 600; cursor: pointer; align-self: flex-end; transition: .15s; }
.btn-primary:hover { background: #1d4ed8; }
.btn-reset { padding: .5rem .9rem; background: var(--surface); color: var(--ink-2);
             border: 1px solid var(--border); border-radius: .5rem; font-size: .875rem;
             cursor: pointer; align-self: flex-end; transition: .15s; }
.btn-reset:hover { border-color: var(--ink-2); }

/* ── Status Pill Filters ──────────────────────────────────────── */
.pill-row { display: flex; gap: .5rem; flex-wrap: wrap; margin-bottom: 1.25rem; }
.pill { padding: .4rem 1rem; border-radius: 9999px; font-size: .8125rem;
        font-weight: 600; cursor: pointer; border: 2px solid transparent;
        transition: all .15s; text-decoration: none; display: inline-block; }
.pill-all    { background: var(--ink);      color: #fff; }
.pill-fast   { background: var(--fast-bg);  color: var(--fast);  border-color: var(--fast-b); }
.pill-slow   { background: var(--slow-bg);  color: var(--slow);  border-color: var(--slow-b); }
.pill-dead   { background: var(--dead-bg);  color: var(--dead);  border-color: var(--dead-b); }
.pill.active { box-shadow: 0 0 0 2px var(--blue); }
.pill-all.active { background: var(--blue); }

/* ── KPI Cards ────────────────────────────────────────────────── */
.kpi-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: .875rem; margin-bottom: 1.25rem; }
.kpi { background: var(--card); border: 1px solid var(--border);
       border-radius: .75rem; padding: 1rem 1.1rem; }
.kpi .k-label { font-size: .7rem; font-weight: 700; color: var(--ink-3);
                text-transform: uppercase; letter-spacing: .07em; margin-bottom: .4rem; }
.kpi .k-val   { font-size: 1.5rem; font-weight: 800; color: var(--ink); line-height: 1; }
.kpi .k-sub   { font-size: .75rem; color: var(--ink-3); margin-top: .25rem; }
.kpi.fast-card  { border-left: 4px solid var(--fast); }
.kpi.slow-card  { border-left: 4px solid var(--slow); }
.kpi.dead-card  { border-left: 4px solid var(--dead); }
.kpi.blue-card  { border-left: 4px solid var(--blue); }
.kpi.fast-card  .k-val { color: var(--fast); }
.kpi.slow-card  .k-val { color: var(--slow); }
.kpi.dead-card  .k-val { color: var(--dead); }
.kpi.blue-card  .k-val { color: var(--blue); }

/* ── Action Summary Cards ─────────────────────────────────────── */
.action-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
               gap: .875rem; margin-bottom: 1.5rem; }
.action-card { background: var(--card); border: 1px solid var(--border);
               border-radius: .75rem; padding: 1rem 1.1rem;
               display: flex; align-items: center; gap: .875rem; }
.action-icon { width: 2.5rem; height: 2.5rem; border-radius: .6rem;
               display: flex; align-items: center; justify-content: center;
               font-size: 1.25rem; flex-shrink: 0; }
.action-card .a-label { font-size: .75rem; font-weight: 700; color: var(--ink-3);
                        text-transform: uppercase; letter-spacing: .06em; }
.action-card .a-val   { font-size: 1.375rem; font-weight: 800; color: var(--ink); }
.ac-reorder  .action-icon { background: #ffe4e6; }
.ac-disc     .action-icon { background: var(--dead-bg); }
.ac-review   .action-icon { background: var(--slow-bg); }
.ac-stok     .action-icon { background: var(--blue-bg); }

/* ── Table Card ───────────────────────────────────────────────── */
.tbl-card { background: var(--card); border: 1px solid var(--border);
            border-radius: .75rem; overflow: hidden; }
.tbl-card-head { padding: .875rem 1.25rem; border-bottom: 1px solid var(--border);
                 display: flex; align-items: center; justify-content: space-between;
                 flex-wrap: wrap; gap: .5rem; }
.tbl-card-head h2 { font-size: .9375rem; font-weight: 700; }
.tbl-count { font-size: .8125rem; color: var(--ink-3); }
.tbl-wrap { overflow-x: auto; max-height: 520px; overflow-y: auto; }
.tbl-wrap thead th { position: sticky; top: 0; z-index: 2; }
table { width: 100%; border-collapse: collapse; font-size: .8rem; }
thead th { background: var(--surface); padding: .6rem .875rem; text-align: left;
           font-weight: 700; color: var(--ink-2); border-bottom: 2px solid var(--border);
           white-space: nowrap; }
thead th.sortable { cursor: pointer; user-select: none; }
thead th.sortable:hover { color: var(--blue); }
thead th.sorted { color: var(--blue); }
tbody td { padding: .55rem .875rem; border-bottom: 1px solid var(--border-2);
           color: var(--ink); vertical-align: middle; }
tbody tr:last-child td { border-bottom: none; }
tbody tr:hover td { background: #f8fafc; }
.tr { text-align: right; }
.tc { text-align: center; }

/* ── Badges ───────────────────────────────────────────────────── */
.badge { display: inline-flex; align-items: center; gap: .25rem;
         padding: .22rem .65rem; border-radius: 9999px;
         font-size: .7rem; font-weight: 700; white-space: nowrap; }
.b-fast  { background: var(--fast-bg);  color: var(--fast);  border: 1px solid var(--fast-b); }
.b-slow  { background: var(--slow-bg);  color: var(--slow);  border: 1px solid var(--slow-b); }
.b-dead  { background: var(--dead-bg);  color: var(--dead);  border: 1px solid var(--dead-b); }
.b-blue  { background: var(--blue-bg);  color: var(--blue);  border: 1px solid var(--blue-b); }
.b-green { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
.b-red   { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
.b-gray  { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }

/* ── Rekomendasi badge warna berdasarkan action ───────────────── */
.rec-maintain     { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
.rec-reorder      { background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa; }
.rec-reorder_urgent{ background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; font-weight: 800; }
.rec-monitor      { background: #fffbeb; color: #92400e; border: 1px solid #fde68a; }
.rec-reduce_order { background: #fff7ed; color: #92400e; border: 1px solid #fed7aa; }
.rec-review       { background: #fff7ed; color: #92400e; border: 1px solid #fde68a; }
.rec-clearance    { background: #fef2f2; color: #9f1239; border: 1px solid #fecaca; }
.rec-discontinue  { background: #1e293b; color: #f8fafc; border: 1px solid #334155; }

/* ── Days-of-stock bar ────────────────────────────────────────── */
.dos-wrap { display: flex; align-items: center; gap: .4rem; min-width: 90px; }
.dos-bar  { flex: 1; height: 5px; background: var(--border); border-radius: 9999px; overflow: hidden; }
.dos-fill { height: 5px; border-radius: 9999px; }
.dos-text { font-size: .75rem; color: var(--ink-2); min-width: 32px; text-align: right; }

/* ── Stok badge warna berdasarkan level ──────────────────────── */
.stok-ok   { color: var(--fast); font-weight: 600; }
.stok-warn { color: var(--slow); font-weight: 600; }
.stok-crit { color: var(--dead); font-weight: 600; }
.stok-zero { color: var(--ink-3); }

/* ── Progress bar untuk distribusi ───────────────────────────── */
.dist-row { display: flex; align-items: center; gap: .5rem; font-size: .8125rem; margin-bottom: .35rem; }
.dist-label { min-width: 100px; color: var(--ink-2); }
.dist-bar-wrap { flex: 1; height: 8px; background: var(--border); border-radius: 9999px; overflow: hidden; }
.dist-bar-fill { height: 8px; border-radius: 9999px; }
.dist-count { min-width: 32px; text-align: right; font-weight: 600; color: var(--ink); }

/* ── Sortable arrows ──────────────────────────────────────────── */
.sort-arrow { font-size: .65rem; margin-left: .2rem; opacity: .5; }
.sort-arrow.active { opacity: 1; }

/* ── Empty state ──────────────────────────────────────────────── */
.empty { text-align: center; padding: 3rem 1rem; color: var(--ink-3); }
.empty .empty-icon { font-size: 2.5rem; margin-bottom: .75rem; }
.empty p { font-size: .9375rem; }

/* ── Tooltip ──────────────────────────────────────────────────── */
[data-tip] { position: relative; cursor: help; }
[data-tip]:hover::after {
    content: attr(data-tip);
    position: absolute; bottom: 125%; left: 50%; transform: translateX(-50%);
    background: var(--ink); color: #fff; font-size: .7rem; font-weight: 500;
    padding: .3rem .6rem; border-radius: .35rem; white-space: nowrap;
    z-index: 9999; pointer-events: none;
}

/* ── Responsive ───────────────────────────────────────────────── */
@media (max-width: 768px) {
    .kpi-grid   { grid-template-columns: repeat(3, 1fr); }
    .action-grid{ grid-template-columns: repeat(2, 1fr); }
    .filter-bar { flex-direction: column; }
    .page-head  { flex-direction: column; }
}
@media (max-width: 480px) {
    .kpi-grid   { grid-template-columns: repeat(2, 1fr); }
}
</style>
<div class="content-wrapper report-wrapper">
    <section class="content">
    {{-- ── Page Header ─────────────────────────────────────────────── --}}
    <div class="page-head">
        <div>
            <h1>📊 Analisis Pergerakan Barang</h1>
            <p>Identifikasi fast moving, slow moving, dan dead stock untuk keputusan inventory</p>
        </div>
        <span class="period-badge">
            {{ \Carbon\Carbon::parse($startDate)->isoFormat('D MMM Y') }}
            →
            {{ \Carbon\Carbon::parse($endDate)->isoFormat('D MMM Y') }}
            ({{ $days }} hari)
        </span>
    </div>

    {{-- ── Filter Bar ──────────────────────────────────────────────── --}}
    <form method="GET" action="{{ route('admin.reports.movement') }}" id="filterForm">
        <div class="filter-bar">
            <div class="fg">
                <label>Dari Tanggal</label>
                <input type="date" name="start_date" value="{{ $startDate }}">
            </div>
            <div class="fg">
                <label>Sampai Tanggal</label>
                <input type="date" name="end_date" value="{{ $endDate }}">
            </div>
            <div class="fg">
                <label>Kategori</label>
                <select name="kategori">
                    <option value="">Semua Kategori</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ $kategori == $cat->id ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="fg">
                <label>Urutkan</label>
                <select name="sort_by">
                    <option value="total_omzet"     {{ $sortBy === 'total_omzet'     ? 'selected' : '' }}>Omzet</option>
                    <option value="total_transaksi" {{ $sortBy === 'total_transaksi' ? 'selected' : '' }}>Jml Transaksi</option>
                    <option value="total_qty"       {{ $sortBy === 'total_qty'       ? 'selected' : '' }}>Qty Terjual</option>
                    <option value="days_of_stock"   {{ $sortBy === 'days_of_stock'   ? 'selected' : '' }}>Hari Stok</option>
                    <option value="days_since_trx"  {{ $sortBy === 'days_since_trx'  ? 'selected' : '' }}>Hari Sejak Jual</option>
                    <option value="stok_sekarang"   {{ $sortBy === 'stok_sekarang'   ? 'selected' : '' }}>Stok</option>
                    <option value="nilai_stok"      {{ $sortBy === 'nilai_stok'      ? 'selected' : '' }}>Nilai Stok</option>
                    <option value="urgency"         {{ $sortBy === 'urgency'         ? 'selected' : '' }}>Urgensi</option>
                </select>
            </div>
            <div class="fg">
                <label>Arah</label>
                <select name="sort_dir">
                    <option value="desc" {{ $sortDir === 'desc' ? 'selected' : '' }}>Terbesar → Terkecil</option>
                    <option value="asc"  {{ $sortDir === 'asc'  ? 'selected' : '' }}>Terkecil → Terbesar</option>
                </select>
            </div>
            <input type="hidden" name="status" id="statusInput" value="{{ $status }}">
            <button type="submit" class="btn-primary">🔍 Terapkan</button>
            <a href="{{ route('admin.reports.movement') }}" class="btn-reset">Reset</a>
        </div>
    </form>

    {{-- ── Status Pill Filter ───────────────────────────────────────── --}}
    <div class="pill-row">
        @php
            $pills = [
                'all'  => ['label' => '🔢 Semua ('   . $s['total']      . ')', 'class' => 'pill-all'],
                'fast' => ['label' => '🚀 Fast ('    . $s['fastCount']  . ')', 'class' => 'pill-fast'],
                'slow' => ['label' => '🐢 Slow ('    . $s['slowCount']  . ')', 'class' => 'pill-slow'],
                'dead' => ['label' => '💀 Dead ('    . $s['deadCount']  . ')', 'class' => 'pill-dead'],
            ];
        @endphp
        @foreach($pills as $key => $pill)
            <a href="javascript:void(0)"
               class="pill {{ $pill['class'] }} {{ $status === $key ? 'active' : '' }}"
               onclick="setStatus('{{ $key }}')">
                {{ $pill['label'] }}
            </a>
        @endforeach
    </div>

    {{-- ── KPI Cards ────────────────────────────────────────────────── --}}
    <div class="kpi-grid">
        <div class="kpi fast-card">
            <div class="k-label">Fast Moving</div>
            <div class="k-val">{{ number_format($s['fastCount']) }}</div>
            <div class="k-sub">
                @if($s['total'] > 0)
                    {{ number_format($s['fastCount'] / $s['total'] * 100, 0) }}% dari total
                @endif
            </div>
        </div>
        <div class="kpi slow-card">
            <div class="k-label">Slow Moving</div>
            <div class="k-val">{{ number_format($s['slowCount']) }}</div>
            <div class="k-sub">
                @if($s['total'] > 0)
                    {{ number_format($s['slowCount'] / $s['total'] * 100, 0) }}% dari total
                @endif
            </div>
        </div>
        <div class="kpi dead-card">
            <div class="k-label">Dead Stock</div>
            <div class="k-val">{{ number_format($s['deadCount']) }}</div>
            <div class="k-sub">
                @if($s['total'] > 0)
                    {{ number_format($s['deadCount'] / $s['total'] * 100, 0) }}% dari total
                @endif
            </div>
        </div>
        <div class="kpi blue-card">
            <div class="k-label">Total Omzet</div>
            <div class="k-val" style="font-size:1.1rem;">Rp {{ number_format($s['totalOmzet'] / 1000000, 1) }}jt</div>
            <div class="k-sub">Laba Rp {{ number_format($s['totalLaba'] / 1000000, 1) }}jt</div>
        </div>
        <div class="kpi">
            <div class="k-label">Nilai Stok</div>
            <div class="k-val" style="font-size:1.1rem;">Rp {{ number_format($s['totalNilaiStok'] / 1000000, 1) }}jt</div>
            <div class="k-sub">HPP barang tersimpan</div>
        </div>
        <div class="kpi">
            <div class="k-label">Total Produk</div>
            <div class="k-val">{{ number_format($s['total']) }}</div>
            <div class="k-sub">ditampilkan</div>
        </div>
    </div>

    {{-- ── Action Summary ───────────────────────────────────────────── --}}
    <div class="action-grid">
        <div class="action-card ac-reorder">
            <div class="action-icon">📦</div>
            <div>
                <div class="a-label">Perlu Reorder</div>
                <div class="a-val">{{ number_format($s['reorderCount']) }} barang</div>
            </div>
        </div>
        <div class="action-card ac-disc">
            <div class="action-icon">🗑️</div>
            <div>
                <div class="a-label">Discontinue</div>
                <div class="a-val">{{ number_format($s['discontinueCount']) }} barang</div>
            </div>
        </div>
        <div class="action-card ac-review">
            <div class="action-icon">⚠️</div>
            <div>
                <div class="a-label">Perlu Review</div>
                <div class="a-val">{{ number_format($s['reviewCount']) }} barang</div>
            </div>
        </div>
        <div class="action-card ac-stok">
            <div class="action-icon">📊</div>
            <div>
                <div class="a-label">Periode Analisis</div>
                <div class="a-val">{{ $days }} hari</div>
            </div>
        </div>
    </div>

    {{-- ── Main Table ───────────────────────────────────────────────── --}}
    <div class="tbl-card">
        <div class="tbl-card-head">
            <h2>📋 Detail Pergerakan Barang</h2>
            <span class="tbl-count">{{ $goods->count() }} barang</span>
        </div>
        <div class="tbl-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Barang</th>
                        <th>Kategori</th>
                        <th>Status</th>
                        <th class="tr" data-tip="Jumlah transaksi dalam periode">Transaksi</th>
                        <th class="tr" data-tip="Total qty terjual (satuan terkecil)">Qty Terjual</th>
                        <th class="tr">Omzet</th>
                        <th class="tr">Laba</th>
                        <th class="tr" data-tip="Margin = (jual-beli)/jual × 100">Margin</th>
                        <th class="tr" data-tip="Stok saat ini dalam satuan terkecil">Stok</th>
                        <th class="tr" data-tip="Estimasi hari stok habis berdasarkan rata-rata penjualan">Hari Stok</th>
                        <th class="tr" data-tip="Hari sejak transaksi terakhir">Sejak Jual</th>
                        <th>Rekomendasi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($goods as $i => $g)
                    @php
                        // Days of stock color
                        if ($g->days_of_stock <= 7)        $dosColor = '#dc2626';
                        elseif ($g->days_of_stock <= 14)   $dosColor = '#d97706';
                        elseif ($g->days_of_stock <= 30)   $dosColor = '#ca8a04';
                        elseif ($g->days_of_stock >= 9999) $dosColor = '#94a3b8';
                        else                               $dosColor = '#16a34a';

                        $dosPct = $g->days_of_stock >= 9999 ? 100
                            : min(100, ($g->days_of_stock / 90) * 100);

                        // Stok class
                        if ($g->stok_sekarang <= 0)       $stokClass = 'stok-zero';
                        elseif ($g->stok_sekarang <= 5)   $stokClass = 'stok-crit';
                        elseif ($g->stok_sekarang <= 20)  $stokClass = 'stok-warn';
                        else                              $stokClass = 'stok-ok';

                        $statusBadge = 'b-' . $g->status_color;
                        if ($g->status_color === 'orange') $statusBadge = 'b-slow';
                    @endphp
                    <tr>
                        <td style="color:var(--ink-3);font-size:.75rem;">{{ $i + 1 }}</td>
                        <td>
                            <div style="font-weight:600;font-size:.8125rem;">{{ $g->nama }}</div>
                            <div style="font-size:.7rem;color:var(--ink-3);">
                                {{ $g->kode ?? '' }}
                                @if($g->merk && $g->merk !== '-') · {{ $g->merk }} @endif
                                · {{ $g->satuan }}
                            </div>
                        </td>
                        <td>
                            <span style="font-size:.75rem;color:var(--ink-2);">{{ $g->kategori }}</span>
                        </td>
                        <td>
                            <span class="badge {{ $statusBadge }}">
                                {{ $g->status === 'fast' ? '🚀' : ($g->status === 'slow' ? '🐢' : '💀') }}
                                {{ $g->status_label }}
                            </span>
                        </td>
                        <td class="tr">
                            <strong>{{ number_format($g->total_transaksi) }}</strong>
                            <div style="font-size:.7rem;color:var(--ink-3);">transaksi</div>
                        </td>
                        <td class="tr">
                            {{ number_format($g->total_qty, 0, ',', '.') }}
                            <div style="font-size:.7rem;color:var(--ink-3);">
                                ~{{ number_format($g->avg_qty_per_day, 1) }}/hari
                            </div>
                        </td>
                        <td class="tr">
                            <span style="font-size:.8125rem;">Rp {{ number_format($g->total_omzet, 0, ',', '.') }}</span>
                        </td>
                        <td class="tr">
                            <span style="font-size:.8125rem;color:{{ $g->total_laba >= 0 ? 'var(--fast)' : 'var(--dead)' }}">
                                Rp {{ number_format($g->total_laba, 0, ',', '.') }}
                            </span>
                        </td>
                        <td class="tr">
                            @if($g->margin_pct >= 20)
                                <span class="badge b-green">{{ $g->margin_pct }}%</span>
                            @elseif($g->margin_pct >= 10)
                                <span class="badge b-blue">{{ $g->margin_pct }}%</span>
                            @elseif($g->margin_pct > 0)
                                <span class="badge b-slow">{{ $g->margin_pct }}%</span>
                            @else
                                <span class="badge b-gray">-</span>
                            @endif
                        </td>
                        <td class="tr">
                            <span class="{{ $stokClass }}">
                                {{ $g->stok_sekarang <= 0 ? 'Habis' : number_format($g->stok_sekarang, 0, ',', '.') }}
                            </span>
                            @if($g->stok_sekarang > 0)
                            <div style="font-size:.7rem;color:var(--ink-3);">
                                Rp {{ number_format($g->nilai_stok / 1000, 0) }}rb
                            </div>
                            @endif
                        </td>
                        <td class="tr">
                            @if($g->stok_sekarang <= 0)
                                <span style="color:var(--ink-3);font-size:.75rem;">—</span>
                            @elseif($g->days_of_stock >= 9999)
                                <span style="color:var(--ink-3);font-size:.75rem;">∞</span>
                            @else
                                <div class="dos-wrap" style="justify-content:flex-end;">
                                    <div class="dos-bar">
                                        <div class="dos-fill" style="width:{{ $dosPct }}%;background:{{ $dosColor }};"></div>
                                    </div>
                                    <span class="dos-text" style="color:{{ $dosColor }};">{{ $g->days_of_stock }}h</span>
                                </div>
                            @endif
                        </td>
                        <td class="tr">
                            @if($g->days_since_trx >= 999)
                                <span class="badge b-gray">Tidak pernah</span>
                            @elseif($g->days_since_trx >= 90)
                                <span class="badge b-dead">{{ $g->days_since_trx }}h lalu</span>
                            @elseif($g->days_since_trx >= 30)
                                <span class="badge b-slow">{{ $g->days_since_trx }}h lalu</span>
                            @else
                                <span class="badge b-fast">{{ $g->days_since_trx }}h lalu</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge rec-{{ $g->recommendation }}">
                                {{ $g->rec_icon }} {{ $g->rec_label }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="13">
                            <div class="empty">
                                <div class="empty-icon">🔍</div>
                                <p>Tidak ada barang yang sesuai filter.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- ── Legend Rekomendasi ──────────────────────────────────────── --}}
        <div style="padding:1rem 1.25rem;border-top:1px solid var(--border);
                    display:flex;flex-wrap:wrap;gap:.5rem;align-items:center;">
            <span style="font-size:.75rem;font-weight:700;color:var(--ink-3);margin-right:.25rem;">LEGENDA:</span>
            <span class="badge rec-maintain">✅ Pertahankan</span>
            <span class="badge rec-reorder">📦 Tambah Stok</span>
            <span class="badge rec-reorder_urgent">🚨 Reorder Segera</span>
            <span class="badge rec-monitor">👁️ Monitor</span>
            <span class="badge rec-reduce_order">📉 Kurangi Order</span>
            <span class="badge rec-review">⚠️ Perlu Review</span>
            <span class="badge rec-clearance">🏷️ Obral/Clearance</span>
            <span class="badge rec-discontinue">🗑️ Discontinue</span>
        </div>
    </div>

    {{-- ── Distribusi Visual ────────────────────────────────────────── --}}
    @if($s['total'] > 0)
    <div style="background:var(--card);border:1px solid var(--border);border-radius:.75rem;
                padding:1.25rem;margin-top:1.25rem;">
        <h2 style="font-size:.9375rem;font-weight:700;margin-bottom:1rem;">📊 Distribusi Status</h2>
        @php
            $rows = [
                ['label' => '🚀 Fast Moving', 'count' => $s['fastCount'], 'color' => '#16a34a'],
                ['label' => '🐢 Slow Moving', 'count' => $s['slowCount'], 'color' => '#d97706'],
                ['label' => '💀 Dead Stock',  'count' => $s['deadCount'], 'color' => '#dc2626'],
            ];
        @endphp
        @foreach($rows as $row)
        @php $pct = $s['total'] > 0 ? round($row['count'] / $s['total'] * 100, 1) : 0; @endphp
        <div class="dist-row">
            <div class="dist-label">{{ $row['label'] }}</div>
            <div class="dist-bar-wrap">
                <div class="dist-bar-fill" style="width:{{ $pct }}%;background:{{ $row['color'] }};"></div>
            </div>
            <div class="dist-count">{{ $row['count'] }}</div>
            <div style="min-width:42px;text-align:right;font-size:.75rem;color:var(--ink-3);">{{ $pct }}%</div>
        </div>
        @endforeach
    </div>
    @endif

</div>
</section>
</div>
<script>
function setStatus(val) {
    document.getElementById('statusInput').value = val;
    document.getElementById('filterForm').submit();
}
</script>
@endsection
