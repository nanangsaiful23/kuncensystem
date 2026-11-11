{{-- Shared look of the loading pages (create and edit) --}}
<style type="text/css">
    *, *::before, *::after { box-sizing: border-box; }
    body, html { height: 100%; overflow: hidden; }
    :root {
        --clr-bg:#F0F4F8; --clr-surface:#FFFFFF; --clr-border:#D1DCE8;
        --clr-primary:#2563EB; --clr-primary-dk:#1D4ED8;
        --clr-success:#16A34A; --clr-warning:#D97706; --clr-danger:#DC2626;
        --clr-text:#1E293B; --clr-muted:#64748B; --clr-input-bg:#FAFCFF;
    }
    .loading-shell { display:flex; flex-direction:column; height:calc(100vh - 50px); padding:4px 10px; gap:4px; background:var(--clr-bg); }
    .search-bar { display:flex; align-items:center; gap:8px; background:var(--clr-surface); border:1px solid var(--clr-border); border-radius:7px; padding:4px 10px; flex-shrink:0; }
    .search-bar .sg { display:flex; align-items:center; gap:5px; flex:1; }
    .search-bar label { font-size:11px; font-weight:600; color:var(--clr-muted); text-transform:uppercase; letter-spacing:.5px; white-space:nowrap; margin:0; }
    .search-bar .iw { display:flex; align-items:center; flex:1; border:1.5px solid var(--clr-border); border-radius:5px; overflow:hidden; background:var(--clr-input-bg); transition:border-color .15s; }
    .search-bar .iw:focus-within { border-color:var(--clr-primary); box-shadow:0 0 0 3px rgba(37,99,235,.1); }
    .search-bar .iw input { border:none; background:transparent; padding:4px 7px; font-size:13px; color:var(--clr-text); width:100%; outline:none; }
    .search-bar .iw .si { padding:0 7px; color:var(--clr-muted); cursor:pointer; font-size:13px; }
    .search-bar .sdiv { width:1px; height:24px; background:var(--clr-border); flex-shrink:0; }
    .loading-body { display:flex; gap:8px; flex:1; min-height:0; }
    .panel-table { flex:1; display:flex; flex-direction:column; background:var(--clr-surface); border:1px solid var(--clr-border); border-radius:8px; overflow:hidden; min-width:0; }
    .panel-table-header { display:flex; align-items:center; justify-content:space-between; padding:4px 10px; border-bottom:1px solid var(--clr-border); background:#F8FAFC; flex-shrink:0; }
    .panel-table-header h6 { margin:0; font-size:11px; font-weight:700; color:var(--clr-muted); text-transform:uppercase; letter-spacing:.5px; }
    .badge-count { background:var(--clr-primary); color:#fff; border-radius:20px; padding:1px 8px; font-size:11px; font-weight:600; }
    .panel-table-scroll { overflow-y:auto; overflow-x:auto; flex:1; }
    .tbl-items { width:100%; border-collapse:collapse; font-size:12px; }
    .tbl-items thead th { position:sticky; top:0; z-index:2; background:#EFF6FF; color:var(--clr-primary-dk); font-weight:700; font-size:10.5px; text-transform:uppercase; letter-spacing:.4px; padding:4px 5px; border-bottom:2px solid var(--clr-border); white-space:nowrap; }
    .tbl-items tbody tr { border-bottom:1px solid #EEF2F7; }
    .tbl-items tbody td { padding:2px 3px !important; vertical-align:middle; }

    /* ---- KUNCI MERGE ---- */
    /* Semua sel yang akan di-rowspan pakai class td-merged */
    .tbl-items td.td-merged { vertical-align:middle !important; }
    /* Accent garis kiri hanya di kolom no */
    .tbl-items td.td-no { border-left:3px solid var(--clr-primary); }
    /* Warna stripe per grup — sel biasa */
    .tbl-items tr.group-odd  td { background:#FAFEFF; }
    .tbl-items tr.group-even td { background:#FFFAF5; }
    /* Warna stripe sel merged sedikit berbeda supaya mudah dibedakan */
    .tbl-items tr.group-odd  td.td-merged { background:#EEF6FF; }
    .tbl-items tr.group-even td.td-merged { background:#FFF5E6; }
    .tbl-items tbody tr:hover td { filter:brightness(.97); }

    .tbl-items .fc { display:block; width:100%; padding:2px 5px; font-size:12px; color:var(--clr-text); background:var(--clr-input-bg); border:1px solid var(--clr-border); border-radius:4px; resize:none; transition:border-color .15s; font-family:inherit; height:26px; }
    .tbl-items .fc:focus { outline:none; border-color:var(--clr-primary); box-shadow:0 0 0 2px rgba(37,99,235,.1); }
    .tbl-items .fc[readonly] { background:#F1F5F9 !important; color:var(--clr-muted); }
    .tbl-items select.fc { cursor:pointer; }

    /* Teks display untuk kolom merged (bukan input) */
    .cell-txt { font-size:12px; color:var(--clr-text); padding:2px 4px; line-height:1.4; word-break:break-word; }
    .cell-txt.sm { font-size:11px; color:var(--clr-muted); text-align:center; }
    .cell-txt small { display:block; font-size:10px; color:var(--clr-muted); margin-top:1px; }

    .col-no      { width:24px; text-align:center; }
    .col-name    { min-width:130px; max-width:170px; }
    .col-barcode { min-width:80px; max-width:110px; }
    .col-exp     { min-width:90px; }
    .col-qty     { min-width:48px; max-width:65px; }
    .col-unit    { min-width:90px; max-width:130px; }
    .col-stock   { min-width:52px; max-width:70px; text-align:center; }
    .col-price   { min-width:80px; max-width:110px; }
    .col-act     { width:28px; text-align:center; }

    .btn-delete { background:none; border:none; cursor:pointer; color:var(--clr-danger); font-size:14px; padding:2px 5px; border-radius:4px; transition:background .15s; }
    .btn-delete:hover { background:#FEE2E2; }

    .panel-sidebar { width:220px; flex-shrink:0; display:flex; flex-direction:column; gap:6px; }
    .sidebar-card { background:var(--clr-surface); border:1px solid var(--clr-border); border-radius:8px; padding:8px 10px; flex-shrink:0; }
    .sidebar-card .card-title { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:var(--clr-muted); margin:0 0 6px; }
    .field-row { margin-bottom:5px; }
    .field-row:last-child { margin-bottom:0; }
    .field-row label { display:block; font-size:11px; font-weight:600; color:var(--clr-text); margin-bottom:2px; }
    .field-row .form-control, .field-row select, .field-row textarea, .field-row input[type="text"] { width:100%; padding:3px 7px; font-size:12px; color:var(--clr-text); background:var(--clr-input-bg); border:1.5px solid var(--clr-border); border-radius:5px; transition:border-color .15s; font-family:inherit; display:block; }
    .field-row .form-control:focus, .field-row select:focus { outline:none; border-color:var(--clr-primary); }
    .field-row textarea { resize:none; height:44px; }
    .field-row .form-control[readonly] { background:#F1F5F9; color:var(--clr-muted); }
    .total-price-display { background:#EFF6FF; border:1.5px solid #BFDBFE; border-radius:6px; padding:4px 8px; text-align:right; }
    .total-price-display .lbl { font-size:9px; font-weight:600; color:var(--clr-muted); text-transform:uppercase; }
    .total-price-display .val { font-size:15px; font-weight:700; color:var(--clr-primary-dk); }
    .btn-submit { width:100%; padding:7px; font-size:13px; font-weight:700; border:none; border-radius:6px; cursor:pointer; transition:filter .15s; }
    .btn-submit:hover { filter:brightness(1.07); }
    .btn-submit.tambah { background:var(--clr-success); color:#fff; }
    .btn-submit.edit   { background:var(--clr-warning); color:#fff; }
    .btn-add-good { width:100%; padding:5px 8px; font-size:12px; font-weight:600; border:1.5px dashed var(--clr-success); color:var(--clr-success); background:transparent; border-radius:5px; cursor:pointer; margin-bottom:4px; }
    .btn-add-good:hover { background:#F0FDF4; }
    .select2-container--default .select2-selection--single { height:26px !important; border-color:var(--clr-border) !important; border-radius:5px !important; }
    .select2-container--default .select2-selection--single .select2-selection__rendered { line-height:24px !important; font-size:12px; padding-left:6px; }
    .select2-container--default .select2-selection--single .select2-selection__arrow { height:24px !important; }
    .panel-table-scroll::-webkit-scrollbar { width:4px; height:4px; }
    .panel-table-scroll::-webkit-scrollbar-thumb { background:#CBD5E1; border-radius:99px; }
    .modal-result-item { display:block; width:100%; padding:7px 10px; font-size:13px; color:var(--clr-text); cursor:pointer; border-radius:4px; border:none; background:transparent; text-align:left; word-break:break-word; font-family:inherit; }
    .modal-result-item:nth-child(odd)  { background:#FFF7ED; }
    .modal-result-item:nth-child(even) { background:#FDF2F8; }
    .modal-result-item:hover { background:#DBEAFE !important; }

    /* ---- PENCARIAN TERPADU ---- */
    .search-bar { position:relative; }
    .search-bar .sg { max-width:640px; }
    .kbd-hint { margin-left:auto; font-size:11px; color:var(--clr-muted); white-space:nowrap; }
    kbd { background:#F1F5F9; border:1px solid #CBD5E1; border-bottom-width:2px; border-radius:3px; padding:0 4px; font-size:10px; color:#334155; font-family:inherit; }
    #sr-busy { display:none; font-size:12px; font-weight:700; color:var(--clr-primary-dk); white-space:nowrap; }
    .sr-panel { display:none; position:absolute; left:0; right:0; top:100%; margin-top:4px; background:#fff; border:1px solid var(--clr-border); border-radius:8px; box-shadow:0 10px 28px rgba(15,23,42,.18); z-index:200; overflow:hidden; }
    .sr-head { display:flex; align-items:center; justify-content:space-between; gap:10px; padding:5px 10px; background:#F8FAFC; border-bottom:1px solid var(--clr-border); font-size:11px; color:var(--clr-muted); flex-wrap:wrap; }
    .sr-list { max-height:min(46vh,380px); overflow-y:auto; }
    .sr-item { display:flex; align-items:center; gap:8px; padding:6px 10px; cursor:pointer; border-bottom:1px solid #EEF2F7; font-size:13px; }
    .sr-item input { width:16px; height:16px; flex:none; margin:0; pointer-events:none; }
    .sr-item .sr-name { flex:1; font-weight:600; color:var(--clr-text); word-break:break-word; }
    .sr-item .sr-code { font-size:11px; color:var(--clr-muted); flex:none; }
    .sr-item .sr-tag { font-size:10px; font-weight:700; color:#64748B; background:#E2E8F0; border-radius:10px; padding:1px 8px; flex:none; }
    .sr-item:hover { background:#F1F5F9; }
    .sr-item.checked { background:#EFF6FF; }
    .sr-item.active { background:#DBEAFE; box-shadow:inset 3px 0 0 var(--clr-primary); }
    .sr-item.disabled { background:#F8FAFC; cursor:not-allowed; }
    .sr-item.disabled .sr-name { color:#94A3B8; font-weight:400; }
    .sr-state { padding:16px; text-align:center; color:var(--clr-muted); font-size:13px; }
    .sr-foot { display:flex; align-items:center; justify-content:space-between; padding:6px 10px; background:#F8FAFC; border-top:1px solid var(--clr-border); }
    .sr-btn { border:1px solid var(--clr-border); background:#fff; color:var(--clr-text); border-radius:5px; padding:4px 12px; font-size:12px; font-weight:600; cursor:pointer; }
    .sr-btn.primary { background:var(--clr-success); border-color:var(--clr-success); color:#fff; }
    .sr-btn.primary.is-empty { background:#94A3B8; border-color:#94A3B8; }
    .sr-btn.loading { background:var(--clr-primary); border-color:var(--clr-primary); cursor:wait; }
    .sr-btn .sr-spin { display:none; }
    .sr-btn.loading .sr-spin { display:inline-block; }
    .sr-btn.loading .sr-ic, .sr-btn.loading .sr-cnt { display:none; }
    .sr-mask { display:none; position:absolute; inset:0; background:rgba(255,255,255,.82); z-index:5; align-items:center; justify-content:center; gap:8px; font-size:14px; font-weight:700; color:var(--clr-primary-dk); }
    .sr-panel.busy .sr-mask { display:flex; }
    .sr-panel.busy .sr-list { pointer-events:none; }

    /* ---- TABEL: jumlah 0 & baris aktif ---- */
    .tbl-items .fc.is-zero { border-color:#F59E0B; background:#FFFBEB; color:#92400E; font-weight:700; }
    .tbl-items .fc:focus { border-color:var(--clr-primary); background:#fff; box-shadow:0 0 0 3px rgba(37,99,235,.25); }
    .tbl-items tbody tr:focus-within td { background:#FEFCE8 !important; }
    .tbl-items td.td-merged { transition:none; }
    .tbl-items tr.flash td { animation:rowFlash 1.4s ease-out; }
    @keyframes rowFlash { from { background:#FEF08A; } to { background:transparent; } }
    .tbl-hint { font-size:10.5px; color:var(--clr-muted); }
    .cell-txt.name-click { cursor:pointer; border-radius:4px; }
    .cell-txt.name-click:hover { background:#DBEAFE; outline:1px dashed #93C5FD; }
    .cell-txt small.renamed-tag { color:var(--clr-warning); font-weight:700; }
    .tbl-items textarea.fc.name-edit { height:auto; min-height:52px; resize:vertical; line-height:1.3; border-color:var(--clr-primary); background:#fff; box-shadow:0 0 0 3px rgba(37,99,235,.25); white-space:normal; }
    .tbl-items select.fc.unit-locked { pointer-events:none; -webkit-appearance:none; -moz-appearance:none; appearance:none; background:#F1F5F9 !important; color:var(--clr-muted); border-style:dashed; cursor:not-allowed; }

    #toast-box { position:fixed; top:58px; right:14px; z-index:2000; display:flex; flex-direction:column; gap:6px; }
    .toast-item { padding:8px 14px; border-radius:6px; color:#fff; font-size:13px; max-width:360px; box-shadow:0 4px 14px rgba(0,0,0,.2); }
    .toast-item.success { background:#16A34A; } .toast-item.warning { background:#D97706; }
    .toast-item.error { background:#DC2626; }   .toast-item.info { background:#2563EB; }
    @media (max-width:900px) { .kbd-hint, .tbl-hint { display:none; } }
    @media (max-width:768px) { body,html{overflow:auto} .loading-shell{height:auto} .loading-body{flex-direction:column} .panel-sidebar{width:100%} .panel-table-scroll{max-height:50vh} }
    /* ---- judul halaman & jenis loading (create dan edit) ---- */
    .page-title { font-size:13px; font-weight:700; color:var(--clr-text); white-space:nowrap; margin:0; }
    .page-title small { font-weight:400; color:var(--clr-muted); margin-left:6px; }
    .type-badge { font-size:11px; font-weight:700; border-radius:20px; padding:2px 10px; white-space:nowrap; }
    .type-badge.internal { background:#FEF9C3; color:#854D0E; border:1px solid #FDE047; }
    .type-badge.transaction-internal { background:#FCE7F3; color:#9D174D; border:1px solid #F9A8D4; }
    .tbl-items td.td-num { text-align:right; }
    .tbl-items .fc.num { text-align:right; }
    .tbl-items .fc.changed { border-color:var(--clr-warning); background:#FFFBEB; }
</style>
