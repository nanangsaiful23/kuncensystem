{{-- Look of the transaction pages (create, edit, detail): the loading look, with table text as big as the Neraca page (14px) --}}
@include('layout.good-loading.style')
<style type="text/css">
    /* ---- ukuran font tabel = Neraca (14px), supaya mudah dibaca kasir ---- */
    .tbl-items { font-size:14px; }
    .tbl-items thead th { font-size:12px; padding:6px 8px; }
    .tbl-items tbody td { padding:4px 6px !important; }
    .tbl-items .fc { font-size:14px; height:34px; padding:3px 8px; }
    .tbl-items .fc.num { text-align:right; }
    .tbl-items .fc.qty { font-size:16px; font-weight:700; text-align:center; }
    .tbl-items .fc.sum { font-weight:700; background:transparent; border-color:transparent; }
    .tbl-items .fc[readonly] { background:#F1F5F9 !important; color:var(--clr-text); }
    .tbl-items .fc.sum[readonly] { background:transparent !important; }
    .cell-txt { font-size:14px; line-height:1.35; }
    .cell-txt.nm { font-weight:600; }
    .cell-txt small { font-size:12px; }
    .cell-txt small.low { color:var(--clr-danger); font-weight:700; }
    .tbl-items td.td-no { font-weight:700; color:var(--clr-muted); text-align:center; }
    .col-no { width:42px; }
    .col-name { min-width:220px; max-width:none; }
    .col-cond { width:130px; }
    .col-num { width:120px; }
    .col-qty { width:92px; min-width:92px; max-width:92px; }
    .col-disc { width:110px; }
    .col-sum { width:140px; }
    .col-act { width:44px; }
    .btn-delete { font-size:16px; padding:5px 8px; }

    .search-bar .iw input { font-size:15px; padding:6px 8px; }
    .search-bar label { font-size:12px; }
    .sr-item { font-size:14px; padding:7px 10px; }
    .sr-item .sr-code { font-size:12px; }
    .sr-item .sr-price { font-weight:700; color:var(--clr-text); flex:none; min-width:90px; text-align:right; }
    .sr-item .sr-stock { font-size:12px; font-weight:700; flex:none; min-width:70px; text-align:right; color:var(--clr-success); }
    .sr-item .sr-stock.zero { color:#64748B; }
    .sr-item .sr-stock.minus { color:var(--clr-danger); }
    /* satu baris per barang: nama di atas, satuan + harga di bawah (yang biru = yang masuk saat Enter) */
    .sr-item .sr-main { flex:1; min-width:0; }
    .sr-item .sr-line { display:flex; align-items:center; gap:8px; }
    .sr-item .sr-units { display:flex; flex-wrap:wrap; gap:4px; margin-top:3px; }
    .sr-item .sr-unit { font-size:12px; color:var(--clr-muted); background:#fff; border:1px solid var(--clr-border); border-radius:12px; padding:1px 9px; cursor:pointer; white-space:nowrap; }
    .sr-item .sr-unit b { color:var(--clr-text); font-weight:700; }
    .sr-item .sr-unit:hover { border-color:var(--clr-primary); }
    .sr-item .sr-unit.on { background:var(--clr-primary); border-color:var(--clr-primary); color:#fff; }
    .sr-item .sr-unit.on b { color:#fff; }
    .kbd-hint, .tbl-hint { font-size:12px; }
    kbd { font-size:11px; }

    /* ---- jual / retur ---- */
    .mode-switch { display:inline-flex; border:1.5px solid var(--clr-border); border-radius:6px; overflow:hidden; flex:none; }
    .mode-switch button { border:none; background:#fff; padding:5px 12px; font-size:13px; font-weight:700; color:var(--clr-muted); cursor:pointer; }
    .mode-switch button.on-jual { background:var(--clr-primary); color:#fff; }
    .mode-switch button.on-retur { background:#F59E0B; color:#fff; }
    .retur-head { background:#FEF3C7; color:#92400E; font-weight:700; font-size:13px; padding:6px 10px; border-top:2px solid #F59E0B; border-bottom:1px solid #FDE68A; }
    #empty-hint { padding:28px 10px; text-align:center; color:var(--clr-muted); font-size:15px; }

    /* ---- sidebar kasir ---- */
    .panel-sidebar.pos { width:340px; overflow-y:auto; }
    .field-row label { font-size:13px; }
    .field-row .form-control, .field-row select, .field-row textarea, .field-row input[type="text"] { font-size:15px; padding:5px 8px; height:36px; }
    .field-row textarea { height:52px; }
    .pos-total { background:#EFF6FF; border:2px solid #BFDBFE; border-radius:8px; padding:6px 10px; text-align:right; }
    .pos-total .lbl { font-size:12px; font-weight:700; color:var(--clr-muted); text-transform:uppercase; }
    .pos-total input { width:100%; border:none; background:transparent; text-align:right; font-size:32px; font-weight:800; color:var(--clr-primary-dk); outline:none; }
    .pos-pay input { font-size:26px !important; font-weight:700; text-align:right; height:48px !important; }
    .pay-sugg { display:flex; flex-wrap:wrap; gap:4px; margin:-1px 0 6px; }
    .pay-chip { border:1.5px solid var(--clr-border); background:#fff; border-radius:6px; padding:2px 8px; font-size:14px; line-height:20px; font-weight:700; color:var(--clr-text); cursor:pointer; }
    .pay-chip kbd { margin-left:5px; font-size:10px; padding:0 3px; opacity:.7; }
    .pay-chip.exact { border-color:var(--clr-success); color:var(--clr-success); background:#F0FDF4; }
    .pay-chip:hover { background:#DBEAFE; }
    .pos-change { font-size:22px; font-weight:800; text-align:right; padding:4px 8px; border-radius:6px; background:#F0FDF4; color:var(--clr-success); min-height:40px; }
    .pos-change.short { background:#FEF2F2; color:var(--clr-danger); }
    .pos-sum-line { display:flex; justify-content:space-between; font-size:13px; color:var(--clr-muted); padding:1px 2px; }
    .btn-submit.big { font-size:18px; padding:12px; }
    /* tombol Proses selalu di paling bawah panel kanan, terpisah dari isian */
    .panel-sidebar.pos #btn-process { margin-top:auto; flex-shrink:0; }
    /* label di samping isian (hemat tinggi, supaya panel kanan muat tanpa gulir di laptop kecil) */
    .field-row.inline-row { display:flex; align-items:center; gap:8px; }
    .field-row.inline-row > label { flex:none; margin:0; }
    .field-row.inline-row > .form-control, .field-row.inline-row > .pos-change { flex:1; min-width:0; width:auto; }

    /* ---- kiri: tabel + baris info transaksi di bawahnya ---- */
    .pos-body { gap:14px; }
    .pos-left { flex:1; min-width:0; display:flex; flex-direction:column; gap:6px; }
    .pos-left .panel-table { min-height:0; }
    .pos-info { position:relative; flex-shrink:0; display:flex; flex-wrap:wrap; align-items:flex-start; gap:6px 12px;
                background:var(--clr-surface); border:1px solid var(--clr-border); border-radius:8px; padding:7px 12px 6px; }
    .info-field { min-width:0; }
    .info-field.f-member { flex:2 1 250px; }
    .info-field.f-pay    { flex:.5 1 130px; }
    .info-field.f-promo  { flex:0 0 auto; }
    .info-field.f-note   { flex:1 1 150px; }
    .info-field > label { display:flex; align-items:center; gap:6px; font-size:13px; font-weight:600; color:var(--clr-text); margin:0 0 3px; height:20px; white-space:nowrap; }
    .info-field .form-control { width:100%; height:36px; font-size:15px; padding:5px 8px; color:var(--clr-text); background:var(--clr-input-bg);
                                border:1.5px solid var(--clr-border); border-radius:5px; font-family:inherit; display:block; box-shadow:none; }
    .info-field .form-control:focus { outline:none; border-color:var(--clr-primary); box-shadow:0 0 0 3px rgba(37,99,235,.12); }
    .info-field textarea.form-control { resize:none; line-height:1.5; overflow-y:auto; }
    .info-inline { display:flex; gap:6px; }
    .info-inline .mem-wrap { flex:1; min-width:0; }
    .cam-btn { flex:none; height:36px; border:1.5px solid var(--clr-border); background:#fff; color:var(--clr-text); border-radius:5px; padding:0 10px; font-size:13px; font-weight:600; cursor:pointer; white-space:nowrap; }
    .cam-btn:hover { background:#F1F5F9; }
    .cam-btn.on { background:#FEF2F2; border-color:var(--clr-danger); color:var(--clr-danger); }
    .cam-pop { display:none; position:absolute; left:12px; bottom:100%; margin-bottom:6px; z-index:310; width:300px; max-width:calc(100% - 24px);
               background:#0F172A; border:1px solid var(--clr-border); border-radius:8px; padding:4px; box-shadow:0 10px 28px rgba(15,23,42,.3); }
    .cam-pop video { display:block; width:100%; border-radius:5px; }
    .info-field > label.promo-box { display:flex; align-items:center; gap:7px; height:36px; padding:0 12px; border:1.5px solid var(--clr-border); border-radius:5px;
                 background:var(--clr-input-bg); font-size:15px; font-weight:600; cursor:pointer; margin:0; user-select:none; }
    .promo-box input { width:18px; height:18px; margin:0; cursor:pointer; flex:none; }
    .info-field > label.promo-box:has(input:checked) { background:#FEF3C7; border-color:#F59E0B; color:#92400E; }
    .info-hint { flex:1 1 100%; font-size:12px; color:var(--clr-muted); line-height:1.3; }

    .mem-wrap { position:relative; }
    /* daftar member membuka ke atas, karena baris info ada di bawah layar */
    .mem-list { display:none; position:absolute; left:0; right:0; bottom:100%; margin-bottom:4px; z-index:300; background:#fff; border:1px solid var(--clr-border); border-radius:6px; box-shadow:0 -8px 22px rgba(15,23,42,.2); max-height:260px; overflow-y:auto; }
    .mem-item { padding:7px 10px; font-size:14px; cursor:pointer; border-bottom:1px solid #EEF2F7; }
    .mem-item small { color:var(--clr-muted); display:block; }
    .mem-item.active, .mem-item:hover { background:#DBEAFE; }
    .mem-tag { font-size:12px; font-weight:700; border-radius:20px; padding:1px 10px; background:#E2E8F0; color:#475569; display:inline-block; max-width:220px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .mem-tag.picked { background:#DCFCE7; color:#166534; }

    /* ---- edit & detail (halaman baca) ---- */
    .page-scroll { height:calc(100vh - 50px); overflow-y:auto; background:var(--clr-bg); padding:8px 12px 20px; }
    .info-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(230px,1fr)); gap:8px; margin-bottom:8px; }
    .info-card { background:var(--clr-surface); border:1px solid var(--clr-border); border-radius:8px; padding:6px 12px; }
    .info-card .k { font-size:12px; font-weight:700; color:var(--clr-muted); text-transform:uppercase; letter-spacing:.4px; }
    .info-card .v { font-size:16px; font-weight:600; color:var(--clr-text); word-break:break-word; }
    .info-card input.form-control { font-size:16px; height:36px; }
    .info-card.big .v { font-size:22px; font-weight:800; color:var(--clr-primary-dk); }
    .page-actions { display:flex; gap:8px; align-items:center; margin-bottom:8px; flex-wrap:wrap; }
    .page-actions .btn-page { border:1px solid var(--clr-border); background:#fff; border-radius:6px; padding:6px 14px; font-size:14px; font-weight:600; color:var(--clr-text); text-decoration:none; }
    /* nomor ganjil putih, genap biru muda: mudah dibaca ke samping */
    .tbl-items tbody tr.group-odd td  { background:#FFFFFF; }
    .tbl-items tbody tr.group-even td { background:#DCEBFA; }
    .tbl-items tbody tr { border-bottom:1px solid #C9D6E4; }
    .tbl-items tr.group-even .fc[readonly] { background:#EAF2FB !important; }
    .tbl-items tr.group-even .fc.sum[readonly], .tbl-items tr.group-odd .fc.sum[readonly] { background:transparent !important; }
    .tbl-items tr.retur-row td { background:#FEF9C3 !important; }
    /* layar pendek (laptop kecil / zoom besar): panel kanan lebih rapat supaya tombol Proses tetap kelihatan */
    @media (min-width:769px) and (max-height:600px) {
        .panel-sidebar.pos { gap:4px; }
        .panel-sidebar.pos .sidebar-card { padding:6px 10px; }
        .pos-total { padding:3px 10px; }
        .pos-total input { font-size:26px; }
        .pos-pay input { font-size:22px !important; height:40px !important; }
        .pos-change { font-size:20px; min-height:34px; padding:2px 8px; }
        .btn-submit.big { padding:9px; }
    }
    @media (max-width:768px) { .panel-sidebar.pos { width:100%; } .page-scroll { height:auto; } .pos-left { width:100%; } .pos-body { gap:8px; } }
</style>
