@extends('layout.user', ['role' => 'admin', 'title' => 'Analisis Pergerakan Barang'])

@section('content')
@php
    $goods             = $goods             ?? collect();
    $summary           = $summary           ?? [];
    $categories        = $categories        ?? collect();
    $discontinuedCount  = $discontinuedCount  ?? 0;
    $negativeStockCount = $negativeStockCount ?? 0;
    $negativeStockGoods = $negativeStockGoods ?? collect();
    $startDate         = $startDate         ?? date('Y-m-d', strtotime('-90 days'));
    $endDate           = $endDate           ?? date('Y-m-d');
    $kategori          = $kategori          ?? null;
    $status            = $status            ?? 'all';
    $sortBy            = $sortBy            ?? 'total_omzet';
    $sortDir           = $sortDir           ?? 'desc';
    $days              = $days              ?? 90;
    $s = array_merge([
        'total'=>0,'fastCount'=>0,'slowCount'=>0,'deadCount'=>0,'atRiskCount'=>0,
        'totalOmzet'=>0,'totalLaba'=>0,'totalNilaiStok'=>0,
        'reorderCount'=>0,'discontinueCount'=>0,'reviewCount'=>0,
    ], $summary);
    $isDiscTab = ($status === 'discontinued');
    $adaKolomBaru = $adaKolomBaru ?? false;
    $seasons      = $seasons      ?? collect();
    $obralSelesai = $obralSelesai ?? 0;
    $s = array_merge([
        'kosongCount'=>0,'baruCount'=>0,'musimanCount'=>0,'obralCount'=>0,'kandidatMusimCount'=>0,
        'nilaiDead'=>0,'nilaiSlow'=>0,'nilaiMusiman'=>0,'nilaiObral'=>0,'modalTertahan'=>0,
        'seasonPrepCount'=>0,'minFast'=>0,
    ], $s);
    $rp = fn ($v) => 'Rp ' . number_format($v / 1000000, 1, ',', '.') . 'jt';
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
.gm-head h1 { font-size:1.4rem; font-weight:800; color:var(--ink); margin:0; }
.gm-head p  { font-size:1.4rem; color:var(--ink2); margin:.15rem 0 0; }
.gm-badge   { background:var(--blue-bg); border:1px solid var(--blue-b); color:var(--blue);
              font-size:1.4rem; font-weight:600; padding:.3rem .75rem;
              border-radius:9999px; white-space:nowrap; align-self:flex-start; }

/* Alert */
.gm-alert { padding:.65rem 1rem; border-radius:.5rem; margin-bottom:.875rem;
            font-size:1.4rem; font-weight:500; }
.gm-alert-ok  { background:var(--fast-bg); border:1px solid var(--fast-b); color:#15803d; }
.gm-alert-err { background:var(--dead-bg); border:1px solid var(--dead-b); color:#b91c1c; }

/* Filter bar */
.gm-filter { background:var(--card); border:1px solid var(--bdr); border-radius:.6rem;
             padding:.75rem 1rem; margin-bottom:.875rem;
             display:flex; align-items:flex-end; gap:.6rem; flex-wrap:wrap; }
.gm-fg { display:flex; flex-direction:column; gap:.2rem; }
.gm-fg label { font-size:1.4rem; font-weight:700; color:var(--ink3);
               text-transform:uppercase; letter-spacing:.08em; }
.gm-fg input, .gm-fg select { padding:.38rem .65rem; border:1px solid var(--bdr);
    border-radius:.4rem; font-size:1.4rem; color:var(--ink); background:var(--surf);
    min-width:110px; line-height:1.4; }
.gm-fg input:focus,.gm-fg select:focus { outline:none; border-color:var(--blue); }
.gm-btn-prim { padding:.4rem 1rem; background:var(--blue); color:#fff; border:none;
               border-radius:.4rem; font-size:1.4rem; font-weight:600; cursor:pointer;
               align-self:flex-end; white-space:nowrap; }
.gm-btn-reset { padding:.4rem .75rem; background:var(--surf); color:var(--ink2);
                border:1px solid var(--bdr); border-radius:.4rem; font-size:1.4rem;
                cursor:pointer; align-self:flex-end; text-decoration:none;
                display:inline-block; white-space:nowrap; }

/* Pills */
.gm-pills { display:flex; gap:.4rem; flex-wrap:wrap; margin-bottom:.875rem; }
.gm-pill  { padding:.3rem .8rem; border-radius:9999px; font-size:1.4rem; font-weight:600;
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
.gm-kpi .kl { font-size:1.4rem; font-weight:700; color:var(--ink3);
              text-transform:uppercase; letter-spacing:.07em; margin-bottom:.3rem; }
.gm-kpi .kv { font-size:1.4rem; font-weight:800; color:var(--ink); line-height:1; }
.gm-kpi .ks { font-size:1.4rem; color:var(--ink3); margin-top:.2rem; }
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
             align-items:center; justify-content:center; font-size:1.4rem; flex-shrink:0; }
.gm-ac .al { font-size:1.4rem; font-weight:700; color:var(--ink3); text-transform:uppercase; }
.gm-ac .av { font-size:1.4rem; font-weight:800; color:var(--ink); }
.gm-ac-r .gm-ac-ico { background:#ffe4e6; }
.gm-ac-d .gm-ac-ico { background:var(--disc-bg); }
.gm-ac-v .gm-ac-ico { background:var(--slow-bg); }
.gm-ac-s .gm-ac-ico { background:var(--blue-bg); }

/* Table card */
.gm-tbl-card { background:var(--card); border:1px solid var(--bdr);
               border-radius:.6rem; overflow:hidden; margin-bottom:1rem; }
.gm-tbl-head { padding:.625rem 1rem; border-bottom:1px solid var(--bdr);
               display:flex; align-items:center; justify-content:space-between; }
.gm-tbl-head h2 { font-size:1.4rem; font-weight:700; color:var(--ink); margin:0; }
.gm-tbl-cnt { font-size:1.4rem; color:var(--ink3); }
.gm-tbl-wrap { overflow-x:auto; max-height:580px; overflow-y:auto; }
.gm-tbl-wrap table { width:100%; border-collapse:collapse; font-size:1.4rem;
                     table-layout:auto; min-width:900px; }
.gm-tbl-wrap thead th { position:sticky; top:0; z-index:2;
    background:var(--surf); padding:.65rem .8rem; text-align:left;
    font-weight:700; color:var(--ink2); border-bottom:2px solid var(--bdr);
    white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.gm-tbl-wrap tbody td { padding:.7rem .8rem; border-bottom:1px solid var(--bdr2);
                        color:var(--ink); vertical-align:middle;
                        overflow:hidden; text-overflow:ellipsis; }
.gm-tbl-wrap tbody tr:last-child td { border-bottom:none; }
.gm-tbl-wrap tbody tr:nth-child(even) td { background:#f8fafc; }
.gm-tbl-wrap tbody tr:nth-child(odd) td { background:#fff; }
.gm-tbl-wrap tbody tr:hover td { background:#eff6ff !important; }
.gm-tbl-wrap tbody tr.row-dead:nth-child(even) td { background:#fff5f5; }
.gm-tbl-wrap tbody tr.row-dead:nth-child(odd) td { background:#fff9f9; }
.gm-tbl-wrap tbody tr.row-dead:hover td { background:#fee2e2 !important; }
.tr { text-align:right; } .tc { text-align:center; }

/* Column widths — fixed agar tidak melebar */


/* Badges */
.gm-badge-tag { display:inline-flex; align-items:center; gap:.2rem; padding:.18rem .55rem;
                border-radius:9999px; font-size:1.4rem; font-weight:700; white-space:nowrap; }
.b-fast { background:var(--fast-bg); color:var(--fast); border:1px solid var(--fast-b); }
.b-slow { background:var(--slow-bg); color:var(--slow); border:1px solid var(--slow-b); }
.b-dead { background:var(--dead-bg); color:var(--dead); border:1px solid var(--dead-b); }
.b-disc { background:var(--disc-bg); color:var(--disc); border:1px solid var(--disc-b); }
.b-blue { background:var(--blue-bg); color:var(--blue); border:1px solid var(--blue-b); }
.b-grn  { background:#f0fdf4; color:#15803d; border:1px solid #bbf7d0; }
.b-org  { background:var(--slow-bg); color:var(--slow); border:1px solid var(--slow-b); }
.b-gry  { background:#f1f5f9; color:#475569; border:1px solid #e2e8f0; }
.b-risk { background:#faf5ff; color:#7e22ce; border:1px solid #e9d5ff; }

.rec-maintain      { background:#f0fdf4; color:#15803d;  border:1px solid #bbf7d0; }
.rec-reorder       { background:#fff7ed; color:#c2410c;  border:1px solid #fed7aa; }
.rec-reorder_urgent{ background:var(--dead-bg); color:#b91c1c; border:1px solid var(--dead-b); font-weight:800; }
.rec-monitor       { background:#fffbeb; color:#92400e;  border:1px solid #fde68a; }
.rec-reduce_order  { background:#fff7ed; color:#92400e;  border:1px solid #fed7aa; }
.rec-review        { background:#fff7ed; color:#92400e;  border:1px solid #fde68a; }
.rec-clearance     { background:var(--dead-bg); color:#9f1239; border:1px solid var(--dead-b); }
.rec-discontinue   { background:var(--disc-bg); color:var(--disc); border:1px solid var(--disc-b); }
.rec-restock_check { background:#faf5ff; color:#7e22ce;  border:1px solid #e9d5ff; }
.rec-review_stuck  { background:#faf5ff; color:#7e22ce;  border:1px solid #e9d5ff; }

/* Pill "at_risk" (ungu) */
.gm-pill-risk { background:#faf5ff; color:#7e22ce; border-color:#e9d5ff; }
.gm-pill-kosong { background:#f1f5f9; color:#475569; border-color:#cbd5e1; }
.gm-pill-baru   { background:var(--blue-bg); color:var(--blue); border-color:var(--blue-b); }
.gm-pill-musim  { background:#f0fdfa; color:#0f766e; border-color:#99f6e4; }
.gm-pill-obral  { background:#fdf2f8; color:#be185d; border-color:#fbcfe8; }
.gm-pill-kand   { background:#fff; color:#0f766e; border:2px dashed #5eead4; }

/* Badge status baru */
.b-gray2 { background:#f1f5f9; color:#475569; border:1px solid #cbd5e1; }
.b-teal  { background:#f0fdfa; color:#0f766e; border:1px solid #99f6e4; }
.b-pink  { background:#fdf2f8; color:#be185d; border:1px solid #fbcfe8; }
.b-new   { background:var(--blue-bg); color:var(--blue); border:1px solid var(--blue-b); }
.rec-wait          { background:var(--blue-bg); color:var(--blue); border:1px solid var(--blue-b); }
.rec-season_hold   { background:#f0fdfa; color:#0f766e; border:1px solid #99f6e4; }
.rec-season_prep   { background:#ccfbf1; color:#115e59; border:1px solid #5eead4; font-weight:800; }
.rec-clearance_run { background:#fdf2f8; color:#be185d; border:1px solid #fbcfe8; }

/* Tanda musim & kandidat musiman di kolom barang */
.gm-season-tag { display:inline-block; margin-top:.2rem; padding:.05rem .45rem; border-radius:9999px;
                 background:#f0fdfa; color:#0f766e; border:1px solid #99f6e4; font-size:1.2rem;
                 cursor:pointer; white-space:nowrap; }
.gm-cand-tag   { display:inline-block; margin-top:.2rem; padding:.05rem .45rem; border-radius:9999px;
                 background:#fff; color:#0f766e; border:1px dashed #14b8a6; font-size:1.2rem;
                 cursor:pointer; white-space:nowrap; }
.gm-season-set { font-size:1.2rem; color:var(--ink3); cursor:pointer; text-decoration:underline dotted; }
.gm-season-set:hover, .gm-season-tag:hover, .gm-cand-tag:hover { filter:brightness(.95); color:#0f766e; }
.gm-sub { font-size:1.2rem; color:var(--ink3); margin-top:.15rem; white-space:normal; line-height:1.35; }

/* Tombol obral */
.btn-obral { padding:.22rem .6rem; background:#fdf2f8; color:#be185d; border:1px solid #fbcfe8;
             border-radius:.35rem; font-size:1.4rem; font-weight:700; cursor:pointer; white-space:nowrap;
             display:inline-block; margin-bottom:.2rem; }
.btn-obral:hover { background:#be185d; color:#fff; }
.btn-unobral { padding:.22rem .6rem; background:#fff; color:#be185d; border:1px solid #fbcfe8;
               border-radius:.35rem; font-size:1.3rem; font-weight:700; cursor:pointer; white-space:nowrap; }
.gm-head-actions { display:flex; gap:.5rem; align-items:flex-start; flex-wrap:wrap; }
.gm-link-btn { background:#f0fdfa; border:1px solid #99f6e4; color:#0f766e; font-size:1.4rem; font-weight:700;
               padding:.3rem .75rem; border-radius:9999px; text-decoration:none; white-space:nowrap; }
.gm-link-btn:hover { background:#0f766e; color:#fff; text-decoration:none; }
.gm-alert-info { background:#f0fdfa; border:1px solid #99f6e4; color:#115e59; }
.gm-bulkbar .gm-bulk-obral { background:#be185d; color:#fff; }
.gm-bulkbar button:disabled { opacity:.45; cursor:not-allowed; }
.gm-modal-sel { width:100%; padding:.45rem .6rem; border:1px solid var(--bdr); border-radius:.45rem;
                font-size:1.4rem; margin-bottom:.75rem; background:#fff; }
.gm-modal-radio { display:flex; flex-direction:column; gap:.35rem; margin-bottom:.75rem; font-size:1.4rem; }
.gm-modal-radio label { font-weight:500; display:flex; gap:.4rem; align-items:flex-start; }
.gm-modal-info { background:#f0fdfa; border:1px solid #99f6e4; color:#115e59; border-radius:.45rem;
                 padding:.6rem .875rem; font-size:1.3rem; margin-bottom:.875rem; line-height:1.55; }
.gm-modal-obral .gm-modal-warn { background:#fdf2f8; border-color:#fbcfe8; color:#9d174d; }
.gm-modal-obral .gm-btn-ok { background:#be185d; }

/* Warning stok minus */
.gm-negstock { background:#fffbeb; border:1px solid #fde68a; border-radius:.6rem;
               padding:.75rem 1rem; margin-bottom:.875rem; }
.gm-negstock summary { cursor:pointer; font-size:1.4rem; font-weight:700; color:#92400e; }
.gm-negstock table { width:100%; border-collapse:collapse; margin-top:.6rem; font-size:1.4rem; }
.gm-negstock th, .gm-negstock td { text-align:left; padding:.35rem .5rem; border-bottom:1px solid #fde68a; }

/* Checklist bulk-discontinue */
.gm-chk-col { width:34px; text-align:center; }
.gm-bulkbar { position:sticky; bottom:0; z-index:5; display:none; align-items:center;
              gap:.75rem; background:var(--ink); color:#fff; padding:.6rem 1rem;
              border-radius:.5rem; margin-top:.6rem; font-size:1.4rem; }
.gm-bulkbar.show { display:flex; }
.gm-bulkbar button { padding:.35rem .85rem; border:none; border-radius:.4rem;
                     font-size:1.4rem; font-weight:700; cursor:pointer; }
.gm-bulkbar .gm-bulk-go   { background:var(--disc); color:#fff; }
.gm-bulkbar .gm-bulk-clear{ background:transparent; color:#cbd5e1; border:1px solid #475569 !important; }

/* Stok color */
.s-ok   { color:var(--fast); font-weight:600; }
.s-warn { color:var(--slow); font-weight:600; }
.s-crit { color:var(--dead); font-weight:600; }
.s-zero { color:var(--ink3); }

/* DoS bar */
.dos-w { display:flex; align-items:center; gap:.25rem; }
.dos-b { flex:1; height:4px; background:var(--bdr); border-radius:9999px; overflow:hidden; min-width:30px; }
.dos-f { height:4px; border-radius:9999px; }
.dos-t { font-size:1.4rem; white-space:nowrap; }

/* Action buttons */
.btn-disc { padding:.22rem .6rem; background:var(--disc-bg); color:var(--disc);
            border:1px solid var(--disc-b); border-radius:.35rem; font-size:1.4rem;
            font-weight:700; cursor:pointer; white-space:nowrap; display:inline-block; }
.btn-disc:hover { background:var(--disc); color:#fff; }
.btn-restore { padding:.22rem .6rem; background:var(--fast-bg); color:#15803d;
               border:1px solid var(--fast-b); border-radius:.35rem; font-size:1.4rem;
               font-weight:700; cursor:pointer; white-space:nowrap; }
.btn-restore:hover { background:#15803d; color:#fff; }

/* Legend */
.gm-legend { padding:.625rem 1rem; border-top:1px solid var(--bdr);
             display:flex; flex-wrap:wrap; gap:.35rem; align-items:center; }
.gm-legend-lbl { font-size:1.4rem; font-weight:700; color:var(--ink3); }

/* Distribusi */
.gm-dist { background:var(--card); border:1px solid var(--bdr); border-radius:.6rem;
           padding:1rem; margin-top:.875rem; }
.gm-dist h2 { font-size:1.4rem; font-weight:700; margin:0 0 .75rem; }
.dist-row  { display:flex; align-items:center; gap:.5rem; font-size:1.4rem; margin-bottom:.3rem; }
.dist-lbl  { min-width:105px; color:var(--ink2); }
.dist-bw   { flex:1; height:7px; background:var(--bdr); border-radius:9999px; overflow:hidden; }
.dist-bf   { height:7px; border-radius:9999px; }
.dist-cnt  { min-width:26px; text-align:right; font-weight:600; }
.dist-pct  { min-width:38px; text-align:right; font-size:1.4rem; color:var(--ink3); }

/* Empty */
.gm-empty { text-align:center; padding:2.5rem 1rem; color:var(--ink3); }
.gm-empty .ei { font-size:2rem; margin-bottom:.5rem; }

/* Pagination */
.gm-pagi { display:flex; align-items:center; justify-content:space-between;
           flex-wrap:wrap; gap:.5rem; padding:.75rem 1rem;
           border-top:1px solid var(--bdr); background:var(--surf); }
.gm-pagi-info { font-size:1.4rem; color:var(--ink2); }
.gm-pagi-info strong { color:var(--ink); }
.gm-pagi-ctrl { display:flex; align-items:center; gap:.35rem; flex-wrap:wrap; }
.gm-pagi-btn  { min-width:34px; height:34px; padding:0 .6rem; border:1px solid var(--bdr);
                background:#fff; color:var(--ink2); border-radius:.4rem;
                font-size:1.4rem; cursor:pointer; display:inline-flex;
                align-items:center; justify-content:center; transition:.15s; }
.gm-pagi-btn:hover:not(:disabled)  { background:var(--blue-bg); border-color:var(--blue); color:var(--blue); }
.gm-pagi-btn.active { background:var(--blue); border-color:var(--blue); color:#fff; font-weight:700; }
.gm-pagi-btn:disabled { opacity:.4; cursor:not-allowed; }
.gm-pagi-sep  { color:var(--ink3); font-size:1.4rem; padding:0 .15rem; }
.gm-perpage   { display:flex; align-items:center; gap:.4rem; font-size:1.4rem; color:var(--ink2); }
.gm-perpage select { padding:.3rem .5rem; border:1px solid var(--bdr); border-radius:.4rem;
                     font-size:1.4rem; background:#fff; color:var(--ink); cursor:pointer; }

/* Tooltip */
[data-gm-tip] { position:relative; cursor:help; }
[data-gm-tip]:hover::after { content:attr(data-gm-tip); position:absolute; bottom:130%;
    left:50%; transform:translateX(-50%); background:var(--ink); color:#fff;
    font-size:1.4rem; padding:.22rem .5rem; border-radius:.3rem;
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
.gm-modal-hd h3 { font-size:1.4rem; font-weight:700; color:var(--ink); margin:0; }
.gm-modal-close { background:none; border:none; font-size:1.4rem; cursor:pointer;
                  color:var(--ink3); padding:.1rem .3rem; border-radius:.3rem; }
.gm-modal-close:hover { color:var(--ink); background:var(--surf); }
.gm-modal-bd { padding:1.1rem; }
.gm-modal-name { font-weight:700; font-size:1.4rem; color:var(--ink); margin-bottom:.15rem; }
.gm-modal-meta { font-size:1.4rem; color:var(--ink2); margin-bottom:.875rem; }
.gm-modal-warn { background:var(--disc-bg); border:1px solid var(--disc-b);
                 border-radius:.45rem; padding:.6rem .875rem; font-size:1.4rem;
                 color:var(--disc); margin-bottom:.875rem; line-height:1.55; }
.gm-modal-lbl  { font-size:1.4rem; font-weight:700; color:var(--ink2);
                 margin-bottom:.3rem; display:block; }
.gm-modal-ta   { width:100%; padding:.55rem .75rem; border:1px solid var(--bdr);
                 border-radius:.45rem; font-size:1.4rem; resize:vertical;
                 min-height:85px; font-family:inherit; color:var(--ink); }
.gm-modal-ta:focus { outline:none; border-color:var(--disc); }
.gm-modal-err  { font-size:1.4rem; color:var(--dead); margin-top:.3rem; display:none; }
.gm-modal-ft   { padding:.75rem 1.1rem; border-top:1px solid var(--bdr);
                 display:flex; justify-content:flex-end; gap:.5rem; }
.gm-btn-cancel { padding:.4rem .9rem; background:var(--surf); color:var(--ink2);
                 border:1px solid var(--bdr); border-radius:.4rem;
                 font-size:1.4rem; cursor:pointer; }
.gm-btn-ok     { padding:.4rem 1rem; background:var(--disc); color:#fff; border:none;
                 border-radius:.4rem; font-size:1.4rem; font-weight:700; cursor:pointer; }
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
@if($errors->any())
    <div class="gm-alert gm-alert-err">❌ {{ $errors->first() }}</div>
@endif
@if($obralSelesai > 0)
    <div class="gm-alert gm-alert-info">🏷️ {{ $obralSelesai }} barang obral sudah habis stoknya dan otomatis di-discontinue ("Habis obral"). Lihat di tab Disc.</div>
@endif
@if(!$adaKolomBaru)
    <div class="gm-alert gm-alert-err">⚠️ Kolom obral & musim belum bisa dibuat otomatis. Jalankan file <code>docs/sql/2026-11-27-obral-dan-musim.sql</code> di phpMyAdmin, lalu buka ulang halaman ini.</div>
@endif

{{-- Peringatan stok minus — ini masalah kerapian DATA, bukan masalah
     pergerakan barang. Stok minus membuat perhitungan nilai stok, hari
     stok, dan rekomendasi reorder ikut salah, jadi harus dibereskan lewat
     Stock Opname (bukan diedit manual). --}}
@if($negativeStockCount > 0)
<details class="gm-negstock">
    <summary>⚠️ {{ $negativeStockCount }} barang memiliki STOK MINUS — data ini bisa membuat perhitungan pergerakan/reorder salah. Klik untuk lihat & rapikan lewat Stock Opname.</summary>
    <table>
        <thead><tr><th>Barang</th><th>Kategori</th><th>Merk</th><th class="tr">Stok Sistem</th><th>Jadi Minus Karena</th></tr></thead>
        <tbody>
        @foreach($negativeStockGoods as $ng)
            <tr>
                <td>{{ $ng->nama }} <span style="color:#94a3b8">({{ $ng->kode }})</span></td>
                <td>{{ $ng->kategori ?? '-' }}</td>
                <td>{{ $ng->merk ?? '-' }}</td>
                <td class="tr" style="color:#dc2626;font-weight:700">{{ number_format($ng->stok) }}</td>
                <td>
                    @if($ng->penyebab_url)
                        <a href="{{ url($ng->penyebab_url) }}">{{ $ng->penyebab }}</a>
                    @else
                        {{ $ng->penyebab ?? '-' }}
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <p style="font-size:1.4rem;color:#92400e;margin:.6rem 0 0">
        Perbaiki lewat menu <strong>Stock Opname</strong> (hitung fisik ulang), jangan diedit langsung di database —
        supaya riwayat penyesuaiannya tetap tercatat.
    </p>
</details>
@endif

{{-- Header --}}
<div class="gm-head">
    <div>
        <h1>📊 Analisis Pergerakan Barang</h1>
        <p>Fast moving · Slow moving · Dead stock · Musiman · Obral · Discontinue</p>
    </div>
    <div class="gm-head-actions">
        @if($adaKolomBaru)
        <a href="{{ route('admin.seasons.index') }}" class="gm-link-btn">🗓️ Atur Musim</a>
        @endif
        @if(!$isDiscTab)
        <span class="gm-badge" data-gm-tip="Fast Moving = minimal {{ $s['minFast'] }} transaksi dalam periode ini & terakhir laku ≤30 hari">
            {{ \Carbon\Carbon::parse($startDate)->isoFormat('D MMM Y') }} →
            {{ \Carbon\Carbon::parse($endDate)->isoFormat('D MMM Y') }}
            · {{ $days }} hari
        </span>
        @endif
    </div>
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
            <select name="sort_by" id="gmSortBy">
                <option value="total_omzet"     {{ $sortBy==='total_omzet'?'selected':'' }}>Omzet</option>
                <option value="nilai_stok"      {{ $sortBy==='nilai_stok'?'selected':'' }}>Nilai Stok (stok × harga beli)</option>
                <option value="total_laba"      {{ $sortBy==='total_laba'?'selected':'' }}>Laba</option>
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
            <select name="sort_dir" id="gmSortDir">
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
        'at_risk'      => ['l'=>'❓ Perlu Dicek ('.$s['atRiskCount'].')','c'=>'gm-pill-risk'],
        'kosong'       => ['l'=>'⚪ Kosong ('.$s['kosongCount'].')',    'c'=>'gm-pill-kosong'],
        'baru'         => ['l'=>'🆕 Baru Masuk ('.$s['baruCount'].')',  'c'=>'gm-pill-baru'],
        'musiman'      => ['l'=>'🗓️ Musiman ('.$s['musimanCount'].')',  'c'=>'gm-pill-musim'],
        'obral'        => ['l'=>'🏷️ Obral ('.$s['obralCount'].')',      'c'=>'gm-pill-obral'],
        'kandidat_musim' => ['l'=>'📅 Kandidat Musiman ('.$s['kandidatMusimCount'].')', 'c'=>'gm-pill-kand'],
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
    <div class="gm-kpi k-dead" data-gm-tip="Stok masih ada, tidak laku ≥90 hari, dan riwayat 12 bulan juga sepi">
        <div class="kl">Dead Stock</div>
        <div class="kv">{{ number_format($s['deadCount']) }}</div>
        <div class="ks">{{ $rp($s['nilaiDead']) }} tertahan</div>
    </div>
    <div class="gm-kpi" style="border-left:4px solid #7e22ce" data-gm-tip="Tidak laku di periode filter, TAPI 12 bulan terakhir rata-rata laku ≥1x/bulan — cek dulu stoknya, jangan langsung di-discontinue.">
        <div class="kl">❓ Perlu Dicek</div>
        <div class="kv" style="color:#7e22ce">{{ number_format($s['atRiskCount']) }}</div>
        <div class="ks">Riwayat bagus, jangan dihapus dulu</div>
    </div>
    <div class="gm-kpi" style="border-left:4px solid #dc2626" data-gm-tip="Nilai stok (stok × harga beli) barang Dead Stock + Slow Moving">
        <div class="kl">💰 Modal Tertahan</div>
        <div class="kv" style="color:#dc2626">{{ $rp($s['modalTertahan']) }}</div>
        <div class="ks">Dead {{ $rp($s['nilaiDead']) }} · Slow {{ $rp($s['nilaiSlow']) }}</div>
    </div>
    <div class="gm-kpi" style="border-left:4px solid #0f766e" data-gm-tip="Barang musiman yang sedang di luar musim atau musimnya segera tiba — tidak dinilai Slow/Dead">
        <div class="kl">🗓️ Musiman</div>
        <div class="kv" style="color:#0f766e">{{ number_format($s['musimanCount']) }}</div>
        <div class="ks">{{ $rp($s['nilaiMusiman']) }} disimpan</div>
    </div>
    <div class="gm-kpi" style="border-left:4px solid #be185d" data-gm-tip="Sedang dihabiskan: tidak disarankan order, otomatis di-discontinue saat stok habis">
        <div class="kl">🏷️ Obral</div>
        <div class="kv" style="color:#be185d">{{ number_format($s['obralCount']) }}</div>
        <div class="ks">sisa {{ $rp($s['nilaiObral']) }}</div>
    </div>
    <div class="gm-kpi" style="border-left:4px solid #94a3b8" data-gm-tip="Stok habis dan tidak laku — tidak menahan modal, jadi bukan dead stock">
        <div class="kl">⚪ Kosong</div>
        <div class="kv" style="color:#475569">{{ number_format($s['kosongCount']) }}</div>
        <div class="ks">stok habis & tidak laku</div>
    </div>
    <div class="gm-kpi k-blue" data-gm-tip="Baru di-loading kurang dari 30 hari dan belum laku sesudahnya">
        <div class="kl">🆕 Baru Masuk</div>
        <div class="kv">{{ number_format($s['baruCount']) }}</div>
        <div class="ks">belum bisa dinilai</div>
    </div>
    <div class="gm-kpi k-disc">
        <div class="kl">Discontinued</div>
        <div class="kv">{{ number_format($discontinuedCount) }}</div>
        <div class="ks">tidak dihitung</div>
    </div>
    <div class="gm-kpi k-blue">
        <div class="kl">Total Omzet</div>
        <div class="kv" style="font-size:1.4rem">{{ $rp($s['totalOmzet']) }}</div>
        <div class="ks">Laba {{ $rp($s['totalLaba']) }}</div>
    </div>
    <div class="gm-kpi">
        <div class="kl">Nilai Stok</div>
        <div class="kv" style="font-size:1.4rem">{{ $rp($s['totalNilaiStok']) }}</div>
        <div class="ks">stok × harga beli (stok minus = 0)</div>
    </div>
</div>
<div class="gm-ac-grid">
    <div class="gm-ac gm-ac-r"><div class="gm-ac-ico">📦</div><div><div class="al">Perlu Reorder</div><div class="av">{{ $s['reorderCount'] }}</div></div></div>
    <div class="gm-ac gm-ac-s"><div class="gm-ac-ico">🗓️</div><div><div class="al">Siapkan Stok Musim</div><div class="av">{{ $s['seasonPrepCount'] }}</div></div></div>
    <div class="gm-ac gm-ac-d"><div class="gm-ac-ico">🗑️</div><div><div class="al">Kandidat Disc.</div><div class="av">{{ $s['discontinueCount'] }}</div></div></div>
    <div class="gm-ac gm-ac-v"><div class="gm-ac-ico">⚠️</div><div><div class="al">Perlu Review</div><div class="av">{{ $s['reviewCount'] }}</div></div></div>
    <div class="gm-ac gm-ac-s"><div class="gm-ac-ico">📊</div><div><div class="al">Periode</div><div class="av">{{ $days }} hr · fast ≥{{ $s['minFast'] }} trx</div></div></div>
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

    {{-- Header card: judul + per-page selector --}}
    <div class="gm-tbl-head">
        <h2>{{ $isDiscTab ? '🗑️ Barang Discontinued' : '📋 Detail Pergerakan Barang' }}</h2>
        <div class="gm-perpage">
            <span>Tampilkan</span>
            <select id="gmPerPage" onchange="gmChangePerPage(this.value)">
                <option value="25">25</option>
                <option value="50" selected>50</option>
                <option value="100">100</option>
                <option value="200">200</option>
                <option value="99999">Semua</option>
            </select>
            <span>per halaman</span>
        </div>
    </div>

    @if($isDiscTab)
    {{-- ════════════════════════════════════════════════════════════ --}}
    {{-- TABEL DISCONTINUED — 8 kolom                               --}}
    {{-- ════════════════════════════════════════════════════════════ --}}
    <div class="gm-tbl-wrap">
    <table id="gmTblDisc">
        <colgroup>
            <col style="width:38px">   {{-- # --}}
            <col style="width:200px">  {{-- Barang --}}
            <col style="width:110px">  {{-- Kategori --}}
            <col style="width:75px">   {{-- Stok --}}
            <col>                      {{-- Alasan (flex) --}}
            <col style="width:105px">  {{-- Tanggal --}}
            <col style="width:95px">   {{-- Oleh --}}
            <col style="width:95px">   {{-- Aksi --}}
        </colgroup>
        <thead>
            <tr>
                <th>#</th>
                <th>Barang</th>
                <th>Kategori</th>
                <th class="tr">Stok</th>
                <th>Alasan Discontinue</th>
                <th>Tanggal</th>
                <th>Oleh</th>
                <th class="tc">Aksi</th>
            </tr>
        </thead>
        <tbody>
        @forelse($goods as $i => $g)
        <tr>
            <td style="color:var(--ink3)">{{ $i + 1 }}</td>
            <td title="{{ $g->nama }}">
                <div style="font-weight:600">{{ $g->nama }}</div>
                <div style="font-size:1.4rem;color:var(--ink3)">
                    {{ $g->kode ?? '' }}@if($g->merk && $g->merk !== '-') · {{ $g->merk }}@endif
                </div>
            </td>
            <td title="{{ $g->kategori }}">{{ $g->kategori }}</td>
            <td class="tr">
                <span class="{{ $g->stok_sekarang > 0 ? 's-warn' : 's-zero' }}">
                    {{ $g->stok_sekarang > 0 ? number_format($g->stok_sekarang, 0, ',', '.') : 'Kosong' }}
                </span>
            </td>
            <td title="{{ $g->discontinued_reason !== '-' ? $g->discontinued_reason : '' }}">
                {{ $g->discontinued_reason !== '-' ? $g->discontinued_reason : '—' }}
            </td>
            <td style="white-space:nowrap">
                {{ $g->discontinued_at ? \Carbon\Carbon::parse($g->discontinued_at)->isoFormat('D MMM Y') : '—' }}
            </td>
            <td>{{ $g->discontinued_by }}</td>
            <td class="tc">
                <form method="POST"
                      action="{{ route('admin.reports.movement.restore', $g->good_id) }}"
                      onsubmit="return confirm('Aktifkan kembali barang ini?')">
                    @csrf
                    <button type="submit" class="btn-restore">♻️ Aktifkan</button>
                </form>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="8">
                <div class="gm-empty"><div class="ei">🎉</div><p>Tidak ada barang discontinued.</p></div>
            </td>
        </tr>
        @endforelse
        </tbody>
    </table>
    </div>

    @else
    {{-- ════════════════════════════════════════════════════════════ --}}
    {{-- TABEL MOVEMENT — 11 kolom                                  --}}
    {{-- ════════════════════════════════════════════════════════════ --}}
    <div class="gm-tbl-wrap">
    <table id="gmTblMain">
        {{--
            KOLOM (11):
            1. Aksi       90px
            2. #          38px
            3. Barang     190px
            4. Kategori   105px
            5. Status     115px
            6. Trx/Qty     90px
            7. Omzet      120px
            8. Laba/Margin 125px
            9. Stok        90px
           10. Hr Stok/Sjk 125px
           11. Rekomendasi 115px
        --}}
        <colgroup>
            <col style="width:34px">
            <col style="width:90px">
            <col style="width:38px">
            <col style="width:190px">
            <col style="width:105px">
            <col style="width:115px">
            <col style="width:90px">
            <col style="width:120px">
            <col style="width:125px">
            <col style="width:90px">
            <col style="width:125px">
            <col style="width:115px">
        </colgroup>
        <thead>
            <tr>
                <th class="tc gm-chk-col" data-gm-tip="Pilih Dead/Slow/Kosong lalu obral atau discontinue sekaligus">
                    <input type="checkbox" id="gmChkAll" onclick="gmToggleAll(this)">
                </th>
                <th class="tc">Aksi</th>
                <th>#</th>
                <th>Barang</th>
                <th>Kategori</th>
                <th>Status</th>
                <th class="tr">Trx / Qty</th>
                <th class="tr">Omzet</th>
                <th class="tr">Laba / Margin</th>
                <th class="tr">Stok</th>
                <th class="tr">Hr Stok / Sjk Jual</th>
                <th>Rekomendasi</th>
            </tr>
        </thead>
        <tbody id="gmTblBody">
        @forelse($goods as $i => $g)
        @php
            if      ($g->days_of_stock <=    7) $dc = '#dc2626';
            elseif  ($g->days_of_stock <=   14) $dc = '#d97706';
            elseif  ($g->days_of_stock <=   30) $dc = '#ca8a04';
            elseif  ($g->days_of_stock >= 9999) $dc = '#94a3b8';
            else                                $dc = '#16a34a';
            $dp = $g->days_of_stock >= 9999 ? 100 : min(100, ($g->days_of_stock / 90) * 100);

            if      ($g->stok_sekarang <=  0) $sc = 's-zero';
            elseif  ($g->stok_sekarang <=  5) $sc = 's-crit';
            elseif  ($g->stok_sekarang <= 20) $sc = 's-warn';
            else                              $sc = 's-ok';

            $sb = [
                'orange' => 'b-slow', 'red' => 'b-dead', 'purple' => 'b-risk', 'gray' => 'b-gray2',
                'blue' => 'b-new', 'teal' => 'b-teal', 'pink' => 'b-pink',
            ][$g->status_color] ?? 'b-fast';
            $statusIcon = [
                'fast' => '🚀', 'slow' => '🐢', 'at_risk' => '❓', 'dead' => '💀', 'kosong' => '⚪',
                'baru' => '🆕', 'musiman' => '🗓️', 'obral' => '🏷️',
            ][$g->status] ?? '';
            $isDead   = ($g->status === 'dead');
            $isAtRisk = ($g->status === 'at_risk');
            $isObral  = ($g->status === 'obral');
            // Obral: Dead/Slow yang stoknya masih ada. Discontinue: Dead & Kosong.
            $canObral = $adaKolomBaru && in_array($g->status, ['dead', 'slow'], true) && $g->stok_sekarang > 0;
            $canDisc  = in_array($g->status, ['dead', 'kosong'], true);
            $namaJs   = e(json_encode($g->nama));
            $katJs    = e(json_encode($g->kategori));
            $tipeJs   = e(json_encode($g->tipe ?? ''));
        @endphp
        <tr class="gm-row{{ $isDead ? ' row-dead' : '' }}" data-idx="{{ $i }}">

            {{-- 0. Checklist (Dead/Slow untuk obral, Dead/Kosong untuk discontinue — bukan at_risk) --}}
            <td class="tc gm-chk-col">
                @if($canObral || $canDisc)
                <input type="checkbox" class="gm-chk-item" value="{{ $g->good_id }}"
                    data-can-obral="{{ $canObral ? 1 : 0 }}" data-can-disc="{{ $canDisc ? 1 : 0 }}"
                    onchange="gmUpdateBulkBar()">
                @endif
            </td>

            {{-- 1. Aksi --}}
            <td class="tc">
                @if($canObral)
                <button type="button" class="btn-obral"
                    onclick="gmOpenObral([{{ $g->good_id }}], {!! $namaJs !!})">🏷️ Obral</button>
                @endif
                @if($canDisc)
                <button type="button" class="btn-disc"
                    onclick="gmOpenModal({{ $g->good_id }}, {!! $namaJs !!}, {!! $katJs !!})">
                    🗑️ Disc.
                </button>
                @elseif($isObral)
                <form method="POST" action="{{ route('admin.reports.movement.cancel-clearance', $g->good_id) }}"
                      onsubmit="return confirm('Batalkan obral barang ini? Barang akan dinilai seperti biasa lagi dan bisa disarankan order.')">
                    @csrf
                    <button type="submit" class="btn-unobral">↩️ Batal Obral</button>
                </form>
                @elseif($isAtRisk)
                <span class="gm-badge-tag b-risk" style="font-size:1.4rem" data-gm-tip="Riwayat 12 bulan: {{ $g->total_transaksi_lt }} transaksi. Jangan discontinue sebelum dicek manual.">Jgn Hapus</span>
                @elseif(!$canObral)
                <span style="color:var(--ink3)">—</span>
                @endif
            </td>

            {{-- 2. # --}}
            <td style="color:var(--ink3)">{{ $i + 1 }}</td>

            {{-- 3. Barang --}}
            <td title="{{ $g->nama }}">
                <div style="font-weight:600;overflow:hidden;text-overflow:ellipsis">{{ $g->nama }}</div>
                <div style="font-size:1.4rem;color:var(--ink3);overflow:hidden;text-overflow:ellipsis">
                    @if($g->kode){{ $g->kode }} · @endif{{ $g->satuan }}@if($g->merk && $g->merk !== '-') · {{ $g->merk }}@endif
                </div>
                @if($adaKolomBaru)
                    @php $gmSeasonArgs = $g->good_id . ', ' . $namaJs . ', ' . $tipeJs . ', ' . ($g->type_id ? 1 : 0) . ', ' . e(json_encode($g->season_good)) . ', ' . e(json_encode($g->musim_sumber === 'jenis' ? $g->season_id : null)); @endphp
                    @if($g->musim_nama)
                    <span class="gm-season-tag" onclick="gmOpenSeason({!! $gmSeasonArgs !!})"
                          data-gm-tip="{{ $g->musim_info }} · diatur lewat {{ $g->musim_sumber === 'barang' ? 'barang ini' : 'jenis ' . ($g->tipe ?? '') }}">🗓️ {{ $g->musim_nama }}</span>
                    @elseif($g->kandidat_musim)
                    <span class="gm-cand-tag" onclick="gmOpenSeason({!! $gmSeasonArgs !!})"
                          data-gm-tip="{{ $g->kandidat_musim['persen'] }}% penjualan 12 bulan terakhir terjadi di {{ $g->kandidat_musim['label'] }}. Klik untuk tandai musiman.">📅 Pola musiman? {{ $g->kandidat_musim['persen'] }}% {{ $g->kandidat_musim['label'] }}</span>
                    @else
                    <span class="gm-season-set" onclick="gmOpenSeason({!! $gmSeasonArgs !!})">🗓️ atur musim</span>
                    @endif
                @endif
            </td>

            {{-- 4. Kategori --}}
            <td title="{{ $g->kategori }}">{{ $g->kategori }}</td>

            {{-- 5. Status --}}
            <td>
                <span class="gm-badge-tag {{ $sb }}">
                    {{ $statusIcon }}
                    {{ $g->status_label }}
                </span>
                @if($isObral)
                <div class="gm-sub">
                    Sejak {{ $g->obral_mulai ? \Carbon\Carbon::parse($g->obral_mulai)->isoFormat('D MMM Y') : '-' }}
                    ({{ $g->obral_hari }} hr) · {{ $g->obral_oleh }}<br>
                    Terjual {{ number_format($g->obral_terjual, 0, ',', '.') }} dari {{ number_format($g->obral_stok_awal, 0, ',', '.') }}
                    @if($g->obral_alasan)<br><em>{{ \Illuminate\Support\Str::limit($g->obral_alasan, 60) }}</em>@endif
                </div>
                @elseif($g->status === 'musiman')
                <div class="gm-sub">{{ $g->musim_nama }} · {{ $g->musim_info }}</div>
                @elseif($g->status === 'baru')
                <div class="gm-sub">Loading {{ $g->days_since_loading }} hari lalu</div>
                @endif
            </td>

            {{-- 6. Trx / Qty per hari --}}
            <td class="tr">
                <div style="font-weight:700">{{ number_format($g->total_transaksi) }}</div>
                @if($isAtRisk)
                <div style="font-size:1.4rem;color:#7e22ce" data-gm-tip="Transaksi normal 12 bulan terakhir, di luar filter tanggal di atas">{{ number_format($g->total_transaksi_lt) }}x/12bln</div>
                @else
                <div style="font-size:1.4rem;color:var(--ink3)">{{ number_format($g->avg_qty_per_day, 1) }}/hr</div>
                @endif
            </td>

            {{-- 7. Omzet --}}
            <td class="tr">Rp {{ number_format($g->total_omzet, 0, ',', '.') }}</td>

            {{-- 8. Laba / Margin --}}
            <td class="tr">
                <div style="color:{{ $g->total_laba >= 0 ? 'var(--fast)' : 'var(--dead)' }}">
                    Rp {{ number_format($g->total_laba, 0, ',', '.') }}
                </div>
                <div style="margin-top:.1rem">
                    @if($g->margin_pct >= 20)     <span class="gm-badge-tag b-grn">{{ $g->margin_pct }}%</span>
                    @elseif($g->margin_pct >= 10) <span class="gm-badge-tag b-blue">{{ $g->margin_pct }}%</span>
                    @elseif($g->margin_pct > 0)   <span class="gm-badge-tag b-org">{{ $g->margin_pct }}%</span>
                    @else                         <span class="gm-badge-tag b-gry">-</span>
                    @endif
                </div>
            </td>

            {{-- 9. Stok --}}
            <td class="tr">
                <div class="{{ $sc }}">{{ $g->stok_sekarang <= 0 ? 'Habis' : number_format($g->stok_sekarang, 0, ',', '.') }}</div>
                @if($g->stok_sekarang > 0)
                <div style="font-size:1.4rem;color:var(--ink3)">Rp{{ number_format($g->nilai_stok / 1000, 0) }}rb</div>
                @endif
            </td>

            {{-- 10. Hr Stok / Sjk Jual --}}
            <td class="tr">
                @if($g->stok_sekarang <= 0)
                    <span style="color:var(--ink3)">—</span>
                @elseif($g->days_of_stock >= 9999)
                    <span style="color:var(--ink3)">∞</span>
                @else
                    <div style="display:flex;align-items:center;gap:.3rem;justify-content:flex-end">
                        <div style="width:40px;height:5px;background:var(--bdr);border-radius:9999px;overflow:hidden">
                            <div style="height:5px;width:{{ $dp }}%;background:{{ $dc }};border-radius:9999px"></div>
                        </div>
                        <span style="color:{{ $dc }};white-space:nowrap">{{ $g->days_of_stock }}h</span>
                    </div>
                @endif
                <div style="margin-top:.1rem">
                    @if($g->days_since_trx >= 999)    <span class="gm-badge-tag b-gry"  style="font-size:1.4rem">Tdk pernah</span>
                    @elseif($g->days_since_trx >= 90) <span class="gm-badge-tag b-dead" style="font-size:1.4rem">{{ $g->days_since_trx }}h lalu</span>
                    @elseif($g->days_since_trx >= 30) <span class="gm-badge-tag b-slow" style="font-size:1.4rem">{{ $g->days_since_trx }}h lalu</span>
                    @else                             <span class="gm-badge-tag b-fast" style="font-size:1.4rem">{{ $g->days_since_trx }}h lalu</span>
                    @endif
                </div>
            </td>

            {{-- 11. Rekomendasi --}}
            <td>
                <span class="gm-badge-tag rec-{{ $g->recommendation }}">
                    {{ $g->rec_icon }} {{ $g->rec_label }}
                </span>
                @if($g->recommendation === 'season_prep')
                <div class="gm-sub">Musim lalu terjual {{ number_format($g->musim_lalu_qty, 0, ',', '.') }} {{ $g->satuan }}
                    @if($g->musim_lalu_qty > $g->stok_sekarang) — kurang ±{{ number_format($g->musim_lalu_qty - max(0, $g->stok_sekarang), 0, ',', '.') }}@endif
                </div>
                @endif
            </td>

        </tr>
        @empty
        <tr>
            <td colspan="12" style="text-align:center;padding:2.5rem;color:var(--ink3)">
                <div style="font-size:1.5rem;margin-bottom:.5rem">🔍</div>
                <div>Tidak ada barang sesuai filter.</div>
            </td>
        </tr>
        @endforelse
        </tbody>
    </table>
    </div>

    {{-- Legend --}}
    <div class="gm-legend">
        <span class="gm-legend-lbl">LEGENDA:</span>
        <span class="gm-badge-tag rec-maintain">✅ Pertahankan</span>
        <span class="gm-badge-tag rec-reorder">📦 Tambah Stok</span>
        <span class="gm-badge-tag rec-reorder_urgent">🚨 Reorder Segera</span>
        <span class="gm-badge-tag rec-monitor">👁️ Monitor</span>
        <span class="gm-badge-tag rec-reduce_order">📉 Kurangi Order</span>
        <span class="gm-badge-tag rec-review">⚠️ Review</span>
        <span class="gm-badge-tag rec-clearance">🏷️ Obral</span>
        <span class="gm-badge-tag rec-discontinue">🗑️ Disc.</span>
        <span class="gm-badge-tag rec-restock_check">🔎 Cek Stok (Riwayat Bagus)</span>
        <span class="gm-badge-tag rec-review_stuck">❓ Cek Manual (Riwayat Bagus)</span>
        <span class="gm-badge-tag rec-wait">⏳ Baru Masuk</span>
        <span class="gm-badge-tag rec-season_hold">🧊 Musiman, jangan order</span>
        <span class="gm-badge-tag rec-season_prep">🗓️ Siapkan Stok Musim</span>
        <span class="gm-badge-tag rec-clearance_run">🏷️ Sedang Obral</span>
    </div>

    {{-- Toolbar aksi massal: muncul saat ada checkbox dicentang --}}
    <div class="gm-bulkbar" id="gmBulkBar">
        <span id="gmBulkCount">0 barang dipilih</span>
        @if($adaKolomBaru)
        <button type="button" class="gm-bulk-obral" id="gmBulkObralBtn" onclick="gmOpenBulkObral()">🏷️ Obral (<span id="gmBulkObralN">0</span>)</button>
        @endif
        <button type="button" class="gm-bulk-go" id="gmBulkDiscBtn" onclick="gmOpenBulkModal()">🗑️ Discontinue (<span id="gmBulkDiscN">0</span>)</button>
        <button type="button" class="gm-bulk-clear" onclick="gmClearAll()">Batal Pilih</button>
    </div>
    @endif

    {{-- Pagination --}}
    <div class="gm-pagi" id="gmPagiBar">
        <div class="gm-pagi-info" id="gmPagiInfo">—</div>
        <div class="gm-pagi-ctrl" id="gmPagiCtrl"></div>
    </div>

</div>{{-- end tbl-card --}}

{{-- Distribusi --}}
@if(!$isDiscTab && $s['total']>0)
<div class="gm-dist">
    <h2>📊 Distribusi Status</h2>
    @php $distRows=[
        ['l'=>'🚀 Fast Moving','n'=>$s['fastCount'],'c'=>'#16a34a'],
        ['l'=>'🐢 Slow Moving','n'=>$s['slowCount'],'c'=>'#d97706'],
        ['l'=>'💀 Dead Stock','n'=>$s['deadCount'],'c'=>'#dc2626'],
        ['l'=>'❓ Perlu Dicek','n'=>$s['atRiskCount'],'c'=>'#7e22ce'],
        ['l'=>'⚪ Kosong','n'=>$s['kosongCount'],'c'=>'#94a3b8'],
        ['l'=>'🆕 Baru Masuk','n'=>$s['baruCount'],'c'=>'#2563eb'],
        ['l'=>'🗓️ Musiman','n'=>$s['musimanCount'],'c'=>'#0f766e'],
        ['l'=>'🏷️ Obral','n'=>$s['obralCount'],'c'=>'#be185d'],
    ]; @endphp
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

{{-- ═══════════════════════════════════════════════════════════════════ --}}
{{-- MODAL BULK DISCONTINUE — checklist banyak barang sekaligus         --}}
{{-- ═══════════════════════════════════════════════════════════════════ --}}
<div class="gm-modal-overlay" id="gmBulkModal">
    <div class="gm-modal">
        <div class="gm-modal-hd">
            <h3>🗑️ Discontinue Sekaligus</h3>
            <button class="gm-modal-close" type="button" onclick="gmCloseBulkModal()">✕</button>
        </div>
        <div class="gm-modal-bd">
            <div class="gm-modal-name" id="gmBulkCountModal">0 barang dipilih</div>
            <div class="gm-modal-warn">
                ⚠️ Hanya barang berstatus Dead Stock / Kosong yang ikut. Barang itu tidak
                punya riwayat penjualan yang bagus dalam 12 bulan terakhir maupun periode
                filter saat ini. Semuanya akan di-discontinue dengan <strong>satu alasan yang sama</strong> di bawah ini.
                Bisa diaktifkan kembali kapan saja dari tab <em>Disc.</em>
            </div>
            <label class="gm-modal-lbl" for="gmBulkReason">
                Alasan Discontinue (berlaku untuk semua barang terpilih) <span style="color:var(--dead)">*</span>
            </label>
            <textarea class="gm-modal-ta" id="gmBulkReason"
                placeholder="Contoh: Beres-beres data lama — tidak ada penjualan >12 bulan, stok habis..."></textarea>
            <div class="gm-modal-err" id="gmBulkErr">Alasan wajib diisi minimal 5 karakter.</div>
        </div>
        <div class="gm-modal-ft">
            <button type="button" class="gm-btn-cancel" onclick="gmCloseBulkModal()">Batal</button>
            <button type="button" class="gm-btn-ok" onclick="gmSubmitBulk()">🗑️ Ya, Discontinue Semua</button>
        </div>
    </div>
</div>

{{-- Form tersembunyi untuk POST bulk-discontinue --}}
<form method="POST" id="gmBulkForm" action="{{ route('admin.reports.movement.bulk-discontinue') }}" style="display:none">
    @csrf
    <input type="hidden" name="reason" id="gmBulkFormReason">
    <div id="gmBulkFormIds"></div>
</form>

@if($adaKolomBaru)
{{-- ═══════════════════════════════════════════════════════════════════ --}}
{{-- MODAL OBRAL — satu barang atau banyak sekaligus                    --}}
{{-- ═══════════════════════════════════════════════════════════════════ --}}
<div class="gm-modal-overlay gm-modal-obral" id="gmObralModal">
    <div class="gm-modal">
        <div class="gm-modal-hd">
            <h3>🏷️ Tandai Obral</h3>
            <button class="gm-modal-close" type="button" onclick="gmCloseObral()">✕</button>
        </div>
        <div class="gm-modal-bd">
            <div class="gm-modal-name" id="gmObralName">—</div>
            <div class="gm-modal-warn">
                Barang obral <strong>tidak dinilai fast/slow/dead</strong> dan <strong>tidak pernah disarankan order</strong>
                (di sini maupun di Rekomendasi Reorder), walaupun selama obral lakunya ramai.
                Begitu stoknya habis, barang <strong>otomatis di-discontinue</strong> dengan alasan "Habis obral".
                Harga obral tetap diatur seperti biasa (ubah harga jual / diskon di kasir).
            </div>
            <label class="gm-modal-lbl" for="gmObralReason">Keterangan obral <span style="color:var(--dead)">*</span></label>
            <textarea class="gm-modal-ta" id="gmObralReason"
                placeholder="Contoh: Dead stock 6 bulan, dijual harga modal sampai habis"></textarea>
            <div class="gm-modal-err" id="gmObralErr">Keterangan wajib diisi minimal 5 karakter.</div>
        </div>
        <div class="gm-modal-ft">
            <button type="button" class="gm-btn-cancel" onclick="gmCloseObral()">Batal</button>
            <button type="button" class="gm-btn-ok" onclick="gmSubmitObral()">🏷️ Ya, Obral</button>
        </div>
    </div>
</div>
<form method="POST" id="gmObralForm" action="{{ route('admin.reports.movement.clearance') }}" style="display:none">
    @csrf
    <input type="hidden" name="reason" id="gmObralFormReason">
    <div id="gmObralFormIds"></div>
</form>

{{-- ═══════════════════════════════════════════════════════════════════ --}}
{{-- MODAL MUSIM — tandai barang / jenisnya sebagai musiman              --}}
{{-- ═══════════════════════════════════════════════════════════════════ --}}
<div class="gm-modal-overlay" id="gmSeasonModal">
    <div class="gm-modal">
        <div class="gm-modal-hd">
            <h3>🗓️ Atur Musim</h3>
            <button class="gm-modal-close" type="button" onclick="gmCloseSeason()">✕</button>
        </div>
        <form method="POST" id="gmSeasonForm">
            @csrf
            <div class="gm-modal-bd">
                <div class="gm-modal-name" id="gmSeasonName">—</div>
                <div class="gm-modal-meta" id="gmSeasonMeta">—</div>
                @if($seasons->isEmpty())
                <div class="gm-modal-info">
                    Belum ada musim. Buat dulu (mis. "Ramadan & Lebaran", "Tahun Ajaran Baru") di
                    <a href="{{ route('admin.seasons.index') }}">halaman Atur Musim</a>.
                </div>
                @else
                <div class="gm-modal-info">
                    Barang musiman di luar musimnya tidak dicap Slow/Dead dan tidak disarankan order.
                    Menjelang musim, muncul saran <em>Siapkan Stok Musim</em> sebanyak penjualan musim lalu.
                </div>
                <label class="gm-modal-lbl">Berlaku untuk</label>
                <div class="gm-modal-radio">
                    <label id="gmSeasonScopeJenisWrap"><input type="radio" name="scope" value="jenis" id="gmSeasonScopeJenis" onchange="gmSeasonScopeChanged()">
                        <span>Seluruh jenis <strong id="gmSeasonTipe">—</strong> (barang lain di jenis ini ikut)</span></label>
                    <label><input type="radio" name="scope" value="barang" id="gmSeasonScopeBarang" onchange="gmSeasonScopeChanged()">
                        <span>Barang ini saja</span></label>
                </div>
                <label class="gm-modal-lbl" for="gmSeasonSelect">Musim</label>
                <select name="season_id" id="gmSeasonSelect" class="gm-modal-sel">
                    <option value="" id="gmSeasonOptIkut">— Ikut jenisnya —</option>
                    <option value="0">Bukan musiman</option>
                    @foreach($seasons as $sea)
                    <option value="{{ $sea->id }}">{{ $sea->name }}</option>
                    @endforeach
                </select>
                <div style="font-size:1.3rem"><a href="{{ route('admin.seasons.index') }}">Kelola daftar & tanggal musim →</a></div>
                @endif
            </div>
            <div class="gm-modal-ft">
                <button type="button" class="gm-btn-cancel" onclick="gmCloseSeason()">Batal</button>
                @if($seasons->isNotEmpty())
                <button type="submit" class="gm-btn-ok" style="background:#0f766e">🗓️ Simpan</button>
                @endif
            </div>
        </form>
    </div>
</div>
@endif

<script>
(function () {
    /* ── State ─────────────────────────────────────────────────── */
    var _goodId  = null;
    var _baseUrl = '{{ url("admin/reports/movement") }}';
    var _perPage = 50;
    var _curPage = 1;
    var _allRows = [];

    /* ── Inisialisasi pagination ────────────────────────────────── */
    function gmInitPagi() {
        var tbody = document.getElementById('gmTblBody');
        if (!tbody) { hidePagi(); return; }
        _allRows = Array.prototype.slice.call(tbody.querySelectorAll('tr.gm-row'));
        if (_allRows.length === 0) { hidePagi(); return; }

        // Ambil nilai per-page dari select
        var sel = document.getElementById('gmPerPage');
        if (sel) _perPage = parseInt(sel.value) || 50;

        gmRenderPage(1);
    }

    function hidePagi() {
        var bar = document.getElementById('gmPagiBar');
        if (bar) bar.style.display = 'none';
    }

    function gmRenderPage(page) {
        var total    = _allRows.length;
        var perPage  = _perPage >= 99999 ? total : _perPage;
        var pages    = perPage >= total ? 1 : Math.ceil(total / perPage);

        page = Math.max(1, Math.min(page, pages));
        _curPage = page;

        var start = (page - 1) * perPage;
        var end   = Math.min(start + perPage, total);

        // Tampilkan/sembunyikan baris
        for (var i = 0; i < total; i++) {
            _allRows[i].style.display = (i >= start && i < end) ? '' : 'none';
        }

        // Update nomor urut per halaman
        for (var j = start; j < end; j++) {
            var numCell = _allRows[j].querySelector('td:nth-child(3)');
            if (numCell) numCell.textContent = j + 1;
        }

        // Update zebra (karena sebagian baris disembunyikan, nth-child tidak bisa diandalkan)
        var visible = 0;
        for (var k = start; k < end; k++) {
            var tds = _allRows[k].querySelectorAll('td');
            var isDead = _allRows[k].classList.contains('row-dead');
            var bg = (visible % 2 === 0)
                ? (isDead ? '#fff9f9' : '#ffffff')
                : (isDead ? '#fff5f5' : '#f8fafc');
            for (var t = 0; t < tds.length; t++) tds[t].style.background = bg;
            visible++;
        }

        // Info
        var infoEl = document.getElementById('gmPagiInfo');
        if (infoEl) {
            infoEl.innerHTML = 'Menampilkan <strong>' + (start + 1) + '–' + end + '</strong> dari <strong>' + total + '</strong> barang';
        }

        // Tombol halaman
        var ctrlEl = document.getElementById('gmPagiCtrl');
        if (!ctrlEl) return;
        ctrlEl.innerHTML = '';

        if (pages <= 1) { hidePagi(); return; }
        document.getElementById('gmPagiBar').style.display = '';

        // Prev
        var prev = mkBtn('‹', page <= 1, function() { gmRenderPage(_curPage - 1); });
        ctrlEl.appendChild(prev);

        // Halaman
        var range = buildRange(page, pages);
        for (var r = 0; r < range.length; r++) {
            if (range[r] === '…') {
                var sep = document.createElement('span');
                sep.className = 'gm-pagi-sep';
                sep.textContent = '…';
                ctrlEl.appendChild(sep);
            } else {
                ctrlEl.appendChild(mkPageBtn(range[r], page));
            }
        }

        // Next
        var next = mkBtn('›', page >= pages, function() { gmRenderPage(_curPage + 1); });
        ctrlEl.appendChild(next);
    }

    function buildRange(cur, total) {
        if (total <= 7) {
            var a = []; for (var i = 1; i <= total; i++) a.push(i); return a;
        }
        var out = [1];
        if (cur > 3)            out.push('…');
        for (var i = Math.max(2, cur - 1); i <= Math.min(total - 1, cur + 1); i++) out.push(i);
        if (cur < total - 2)    out.push('…');
        out.push(total);
        return out;
    }

    function mkBtn(label, disabled, fn) {
        var b = document.createElement('button');
        b.className = 'gm-pagi-btn';
        b.textContent = label;
        b.disabled = disabled;
        if (!disabled) b.addEventListener('click', fn);
        return b;
    }

    function mkPageBtn(num, cur) {
        var b = document.createElement('button');
        b.className = 'gm-pagi-btn' + (num === cur ? ' active' : '');
        b.textContent = num;
        b.addEventListener('click', function() { gmRenderPage(num); });
        return b;
    }

    /* ── Per-page change ────────────────────────────────────────── */
    window.gmChangePerPage = function(val) {
        _perPage = parseInt(val) || 50;
        gmRenderPage(1);
    };

    /* ── Modal discontinue ──────────────────────────────────────── */
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

    /* ── Checklist bulk-discontinue ─────────────────────────────── */
    // Catatan: tabel ini dipaginasi di sisi browser (baris disembunyikan via
    // display:none), jadi "pilih semua" & hitungan hanya menghitung baris yang
    // SEDANG TAMPIL di halaman saat ini. Kalau mau bulk-discontinue banyak
    // barang, ubah "Tampilkan per halaman" ke "Semua" dulu di atas tabel.
    function gmVisibleCheckItems() {
        var all = document.querySelectorAll('.gm-chk-item');
        var visible = [];
        for (var i = 0; i < all.length; i++) {
            var row = all[i].closest('tr');
            if (row && row.style.display !== 'none') visible.push(all[i]);
        }
        return visible;
    }

    window.gmToggleAll = function (master) {
        var boxes = gmVisibleCheckItems();
        for (var i = 0; i < boxes.length; i++) boxes[i].checked = master.checked;
        gmUpdateBulkBar();
    };

    function gmCheckedIds(attr) {
        var out = [];
        document.querySelectorAll('.gm-chk-item:checked').forEach(function (b) {
            if (b.getAttribute(attr) === '1') out.push(b.value);
        });
        return out;
    }

    window.gmUpdateBulkBar = function () {
        var visible = gmVisibleCheckItems();
        var checked = visible.filter(function (b) { return b.checked; });
        var bar = document.getElementById('gmBulkBar');
        var countEl = document.getElementById('gmBulkCount');
        if (!bar || !countEl) return;
        if (checked.length > 0) {
            bar.classList.add('show');
            countEl.textContent = checked.length + ' barang dipilih';
        } else {
            bar.classList.remove('show');
        }
        var nObral = gmCheckedIds('data-can-obral').length;
        var nDisc  = gmCheckedIds('data-can-disc').length;
        var oN = document.getElementById('gmBulkObralN'), oB = document.getElementById('gmBulkObralBtn');
        var dN = document.getElementById('gmBulkDiscN'),  dB = document.getElementById('gmBulkDiscBtn');
        if (oN) oN.textContent = nObral;
        if (oB) oB.disabled = nObral === 0;
        if (dN) dN.textContent = nDisc;
        if (dB) dB.disabled = nDisc === 0;
        // Sinkronkan checkbox "pilih semua" di header
        var master = document.getElementById('gmChkAll');
        if (master) master.checked = visible.length > 0 && checked.length === visible.length;
    };

    window.gmClearAll = function () {
        var boxes = document.querySelectorAll('.gm-chk-item');
        for (var i = 0; i < boxes.length; i++) boxes[i].checked = false;
        gmUpdateBulkBar();
    };

    window.gmOpenBulkModal = function () {
        var checked = gmCheckedIds('data-can-disc');
        if (checked.length === 0) return;
        document.getElementById('gmBulkCountModal').textContent = checked.length + ' barang akan di-discontinue';
        document.getElementById('gmBulkReason').value = '';
        document.getElementById('gmBulkErr').style.display = 'none';
        document.getElementById('gmBulkModal').classList.add('open');
        setTimeout(function () { document.getElementById('gmBulkReason').focus(); }, 120);
    };

    window.gmCloseBulkModal = function () {
        document.getElementById('gmBulkModal').classList.remove('open');
    };

    window.gmSubmitBulk = function () {
        var reason = document.getElementById('gmBulkReason').value.trim();
        if (reason.length < 5) {
            document.getElementById('gmBulkErr').style.display = 'block';
            document.getElementById('gmBulkReason').focus();
            return;
        }
        var checked = gmCheckedIds('data-can-disc');
        if (checked.length === 0) { gmCloseBulkModal(); return; }

        document.getElementById('gmBulkErr').style.display = 'none';
        document.getElementById('gmBulkFormReason').value = reason;
        gmFillIds('gmBulkFormIds', checked);

        document.getElementById('gmBulkForm').submit();
    };

    function gmFillIds(wrapId, ids) {
        var idsWrap = document.getElementById(wrapId);
        idsWrap.innerHTML = '';
        ids.forEach(function (id) {
            var inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = 'ids[]';
            inp.value = id;
            idsWrap.appendChild(inp);
        });
    }

    /* ── Obral ──────────────────────────────────────────────────── */
    var _obralIds = [];
    window.gmOpenObral = function (ids, name) {
        if (!document.getElementById('gmObralModal') || !ids.length) return;
        _obralIds = ids;
        document.getElementById('gmObralName').textContent = name;
        document.getElementById('gmObralReason').value = '';
        document.getElementById('gmObralErr').style.display = 'none';
        document.getElementById('gmObralModal').classList.add('open');
        setTimeout(function () { document.getElementById('gmObralReason').focus(); }, 120);
    };
    window.gmOpenBulkObral = function () {
        var ids = gmCheckedIds('data-can-obral');
        gmOpenObral(ids, ids.length + ' barang akan diobral');
    };
    window.gmCloseObral = function () {
        var m = document.getElementById('gmObralModal');
        if (m) m.classList.remove('open');
    };
    window.gmSubmitObral = function () {
        var reason = document.getElementById('gmObralReason').value.trim();
        if (reason.length < 5) {
            document.getElementById('gmObralErr').style.display = 'block';
            document.getElementById('gmObralReason').focus();
            return;
        }
        document.getElementById('gmObralFormReason').value = reason;
        gmFillIds('gmObralFormIds', _obralIds);
        document.getElementById('gmObralForm').submit();
    };

    /* ── Musim ──────────────────────────────────────────────────── */
    window.gmOpenSeason = function (goodId, name, tipe, punyaJenis, musimBarang, musimJenis) {
        var m = document.getElementById('gmSeasonModal');
        if (!m) return;
        document.getElementById('gmSeasonForm').action = _baseUrl + '/' + goodId + '/musim';
        document.getElementById('gmSeasonName').textContent = name;
        document.getElementById('gmSeasonMeta').textContent = punyaJenis ? ('Jenis: ' + tipe) : 'Barang ini belum punya jenis';
        var tipeEl = document.getElementById('gmSeasonTipe');
        if (tipeEl) {
            tipeEl.textContent = tipe || '-';
            document.getElementById('gmSeasonScopeJenisWrap').style.display = punyaJenis ? '' : 'none';
            // Bawaan: per jenis, kecuali barang ini sudah punya pengaturan sendiri.
            var perBarang = !punyaJenis || (musimBarang !== null && musimBarang !== '');
            document.getElementById(perBarang ? 'gmSeasonScopeBarang' : 'gmSeasonScopeJenis').checked = true;
            gmSeasonScopeChanged();
            document.getElementById('gmSeasonSelect').value = perBarang
                ? (musimBarang !== null ? String(musimBarang) : '')
                : (musimJenis ? String(musimJenis) : '');
        }
        m.classList.add('open');
    };
    window.gmSeasonScopeChanged = function () {
        var opt = document.getElementById('gmSeasonOptIkut');
        var jenis = document.getElementById('gmSeasonScopeJenis');
        if (!opt || !jenis) return;
        // "Ikut jenisnya" hanya masuk akal untuk pengaturan per barang.
        opt.textContent = jenis.checked ? '— Pilih musim —' : '— Ikut jenisnya —';
    };
    window.gmCloseSeason = function () {
        var m = document.getElementById('gmSeasonModal');
        if (m) m.classList.remove('open');
    };

    /* ── Pill status ────────────────────────────────────────────── */
    // Tab yang isinya modal tertahan diurutkan dari nilai stok terbesar.
    var _sortPerTab = { dead: 'nilai_stok', slow: 'nilai_stok', at_risk: 'nilai_stok', musiman: 'nilai_stok',
                        obral: 'nilai_stok', baru: 'nilai_stok', kosong: 'days_since_trx',
                        all: 'total_omzet', fast: 'total_omzet', kandidat_musim: 'total_qty' };
    window.gmSetStatus = function (val) {
        document.getElementById('gmStatusInput').value = val;
        var sb = document.getElementById('gmSortBy'), sd = document.getElementById('gmSortDir');
        if (sb && _sortPerTab[val]) { sb.value = _sortPerTab[val]; if (sd) sd.value = 'desc'; }
        document.getElementById('gmFilterForm').submit();
    };

    /* ── Event listeners ────────────────────────────────────────── */
    document.getElementById('gmDiscModal').addEventListener('click', function (e) {
        if (e.target === this) gmCloseModal();
    });
    ['gmBulkModal', 'gmObralModal', 'gmSeasonModal'].forEach(function (id) {
        var el = document.getElementById(id);
        if (el) el.addEventListener('click', function (e) {
            if (e.target === this) this.classList.remove('open');
        });
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { gmCloseModal(); gmCloseBulkModal(); gmCloseObral(); gmCloseSeason(); }
    });

    /* ── Boot ───────────────────────────────────────────────────── */
    document.addEventListener('DOMContentLoaded', gmInitPagi);
}());
</script>
@endsection