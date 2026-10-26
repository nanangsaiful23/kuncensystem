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
    .tbl-items .fc.is-zero { border-color:var(--clr-danger); background:#FEF2F2; color:var(--clr-danger); font-weight:700; }
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
</style>

<?php $distributors = getDistributors(); $canEditUnit = (\Auth::user()->email == 'admin'); $canEditName = true; if (!isset($SubmitButtonText)) { $SubmitButtonText = 'Tambah'; } ?>

<div class="loading-shell">

    {{-- SEARCH BAR: barcode + nama barang digabung --}}
    <div class="search-bar" id="sr-wrap">
        <i class="fa fa-search" style="color:var(--clr-muted);font-size:13px;"></i>
        <div class="sg">
            <label for="all_search">Cari Barang</label>
            <div class="iw">
                <input type="text" id="all_search" autocomplete="off" placeholder="Scan barcode atau ketik nama barang..."
                       oninput="onSearchInput()" onkeydown="onSearchKeydown(event)">
                <span class="si" onclick="focusSearch()" title="Fokus ke pencarian (F2)"><i class="fa fa-barcode"></i></span>
            </div>
        </div>
        <span id="sr-busy"><i class="fa fa-spinner fa-spin"></i> Menambahkan barang...</span>
        <span class="kbd-hint"><kbd>F2</kbd> cari &middot; <kbd>F3</kbd> / <kbd>&darr;</kbd> ke tabel &middot; <kbd>Ctrl</kbd>+<kbd>Enter</kbd> simpan</span>

        <div class="sr-panel" id="sr-panel">
            <div class="sr-mask"><i class="fa fa-spinner fa-spin"></i> Menambahkan barang ke tabel...</div>
            <div class="sr-head">
                <span id="sr-info"></span>
                <span><kbd>&uarr;</kbd><kbd>&darr;</kbd> pilih &middot; <kbd>Insert</kbd> / <kbd>Shift</kbd>+<kbd>&uarr;&darr;</kbd> centang &middot; <kbd>Enter</kbd> tambahkan &middot; <kbd>Esc</kbd> tutup</span>
            </div>
            <div class="sr-list" id="sr-list"></div>
            <div class="sr-foot">
                <button type="button" class="sr-btn" onclick="srCheckAll()">Centang semua</button>
                <button type="button" class="sr-btn primary is-empty" id="sr-add" onclick="srAddChecked()">
                    <i class="fa fa-plus sr-ic"></i><i class="fa fa-spinner fa-spin sr-spin"></i>
                    <span class="sr-lbl">Tambahkan</span><span class="sr-cnt"> (<span id="sr-count">0</span>)</span>
                </button>
            </div>
        </div>
    </div>

    <div class="loading-body">

        {{-- TABEL --}}
        <div class="panel-table">
            <div class="panel-table-header">
                <h6><i class="fa fa-list-ul"></i> Daftar Barang</h6>
                <span class="tbl-hint"><kbd>Enter</kbd> kolom berikut &middot; <kbd>&uarr;</kbd><kbd>&darr;</kbd> baris &middot; <kbd>&larr;</kbd><kbd>&rarr;</kbd> kolom &middot; <kbd>Esc</kbd> cari &middot; <kbd>Alt</kbd>+<kbd>Del</kbd> hapus &middot; klik nama / <kbd>F4</kbd> ubah nama</span>
                <span class="badge-count" id="badge-item-count">0 item</span>
            </div>
            <div class="panel-table-scroll">
                <table class="tbl-items">
                    <thead>
                        <tr>
                            <th class="col-no">#</th>
                            <th class="col-name">Nama Barang</th>
                            <th class="col-barcode">Barcode</th>
                            <th class="col-exp">Tgl Expired</th>
                            <th class="col-qty">Jml</th>
                            <th class="col-unit">Satuan</th>
                            <th class="col-stock">Stok Lama</th>
                            <th class="col-stock">Stok Baru</th>
                            @if(\Auth::user()->role == 'supervisor')
                            <th class="col-price">Harga Beli</th>
                            <th class="col-price">Total</th>
                            <th class="col-price">Harga Jual</th>
                            @endif
                            <th class="col-act"></th>
                        </tr>
                    </thead>
                    <tbody id="table-transaction">
                        <?php $i = 1; ?>
                        <tr id="row-data-{{ $i }}" class="group-odd">
                            <input type="hidden" name="base_qtys[]" id="base_qty-{{ $i }}">
                            <td class="col-no td-merged td-no"   id="td-no-{{ $i }}"        rowspan="1"></td>
                            <td class="col-name td-merged"        id="td-name-{{ $i }}"      rowspan="1">
                                <div class="cell-txt{{ $canEditName ? ' name-click' : '' }}" id="name_display-{{ $i }}"@if($canEditName) title="Klik untuk mengubah nama"@endif></div>
                                <input type="hidden" name="names[]"      id="name-{{ $i }}">
                                <input type="hidden" name="name_temps[]" id="name_temp-{{ $i }}">
                                <input type="hidden" name="name_changed[]" id="name_changed-{{ $i }}" value="0">
                            </td>
                            <td class="col-barcode td-merged"     id="td-barcode-{{ $i }}"   rowspan="1">
                                <div class="cell-txt sm" id="barcode_display-{{ $i }}"></div>
                                <input type="hidden" name="barcodes[]" id="barcode-{{ $i }}">
                            </td>
                            <td class="col-exp td-merged"         id="td-exp-{{ $i }}"       rowspan="1">
                                <input type="text" class="fc" name="exp_dates[]" id="exp-{{ $i }}" placeholder="yyyy-mm-dd">
                            </td>
                            <td class="col-qty">
                                <input type="text" class="fc" name="quantities[]" id="quantity-{{ $i }}"
                                    onchange="editPrice('{{ $i }}')" onkeypress="editPrice('{{ $i }}')" onkeyup="editPrice('{{ $i }}')">
                            </td>
                            <td class="col-unit">
                                @if($SubmitButtonText == 'View')
                                    {!! Form::text('unit', null, ['class'=>'fc','readonly'=>'readonly']) !!}
                                @else
                                    {!! Form::select('units[]', getUnits(), null, ['class'=>'fc'.($canEditUnit ? '' : ' unit-locked'),'tabindex'=>($canEditUnit ? null : -1),'style'=>'width:100%','id'=>'unit-'.$i,'onchange'=>'changePriceByUnit('.$i.')']) !!}
                                @endif
                            </td>
                            <td class="col-stock td-merged"       id="td-old-stock-{{ $i }}" rowspan="1">
                                <div class="cell-txt sm" id="old_stock_display-{{ $i }}">—</div>
                                <input type="hidden" name="old_stocks[]" id="old_stock-{{ $i }}">
                            </td>
                            <td class="col-stock td-merged"       id="td-new-stock-{{ $i }}" rowspan="1">
                                <div class="cell-txt sm" id="new_stock_display-{{ $i }}">—</div>
                                <input type="hidden" name="new_stocks[]" id="new_stock-{{ $i }}">
                            </td>
                            @if(\Auth::user()->role == 'supervisor')
                            <td class="col-price"><input type="text" class="fc" name="prices[]"       id="price-{{ $i }}"       onchange="editBuyPrice('{{ $i }}')" onkeyup="editBuyPrice('{{ $i }}')"></td>
                            <td class="col-price"><input type="text" class="fc" name="total_prices[]" id="total_price-{{ $i }}" readonly></td>
                            <td class="col-price"><input type="text" class="fc" name="sell_prices[]"  id="sell_price-{{ $i }}"></td>
                            @endif
                            <td class="col-act td-merged"         id="td-act-{{ $i }}"       rowspan="1">
                                <button type="button" class="btn-delete" onclick="deleteGroup('{{ $i }}')" title="Hapus"><i class="fa fa-trash"></i></button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- SIDEBAR --}}
        <div class="panel-sidebar">
            @if(\Auth::user()->role == 'supervisor')
            <button type="button" class="btn-add-good" data-toggle="modal" data-target="#modal-good">
                <i class="fa fa-plus-circle"></i> Tambah Barang Baru
            </button>
            @endif

            <div class="sidebar-card" style="flex:1;overflow-y:auto;">
                <p class="card-title"><i class="fa fa-file-text-o"></i> Detail Pembelian</p>
                <div class="field-row">
                    <label>Distributor</label>
                    @if($SubmitButtonText == 'View')
                        {!! Form::text('distributor', null, ['class'=>'form-control','readonly'=>'readonly']) !!}
                    @else
                        <input type="text" name="distributor_name" id="distributor_name" style="display:none;">
                        <select class="form-control select2" style="width:100%;" name="distributor_id" id="all_distributor">
                            <option value="null">Pilih distributor...</option>
                            @foreach($distributors as $d)
                                <option value="{{ $d->id }}">{{ $d->name }}</option>
                            @endforeach
                        </select>
                    @endif
                </div>
                <div class="field-row">
                    <label>Tanggal Pembelian</label>
                    <div class="input-group date">
                        <input type="text" class="form-control" required name="loading_date" id="loading_date" placeholder="yyyy-mm-dd">
                    </div>
                </div>
                <div class="field-row">
                    <label>Jenis Pembayaran</label>
                    {!! Form::select('payment', getLoadingPaymentType(), null, ['class'=>'form-control select2','style'=>'width:100%','id'=>'payment']) !!}
                </div>
                <div class="field-row">
                    <label>PIC Check Barang</label>
                    <input type="text" name="checker" class="form-control" id="checker" placeholder="Nama checker...">
                </div>
                <div class="field-row">
                    <label>Catatan</label>
                    <textarea name="note" class="form-control" id="note" placeholder="Opsional..."></textarea>
                </div>
                @if(\Auth::user()->role == 'supervisor')
                <div class="total-price-display">
                    <div class="lbl">Total Harga</div>
                    <div class="val" id="total_price_display">Rp 0</div>
                    <input type="hidden" name="total_item_price" id="total_item_price">
                </div>
                @endif
                 @if($SubmitButtonText == 'Edit')
                {!! Form::submit('Edit', ['class'=>'btn-submit edit']) !!}
            @elseif($SubmitButtonText == 'Tambah')
                <button type="button" class="btn-submit tambah" onclick="event.preventDefault(); submitForm();">
                    <i class="fa fa-check-circle"></i> {{ $default['page_name'] }}
                </button>
            @endif
            </div>

            {{ csrf_field() }}

           
        </div>
    </div>
</div>

<div id="toast-box"></div>

@if(\Auth::user()->role == 'supervisor')
<div class="modal fade" id="modal-good">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            <h4 class="modal-title"><i class="fa fa-plus"></i> Barang Baru</h4>
        </div>
        <div class="modal-body"><div class="col-sm-12">
            <div class="form-group"><label class="col-sm-4 control-label">Kategori</label><div class="col-sm-8">{!! Form::select('category_id', getCategories(), null, ['class'=>'form-control select2','style'=>'width:100%','id'=>'category_id']) !!}</div></div>
            <div class="form-group"><label class="col-sm-4 control-label">Jenis Barang</label><div class="col-sm-8">{!! Form::select('type_id', getGoodTypes(), null, ['class'=>'form-control select2','style'=>'width:100%','id'=>'type_id']) !!}</div></div>
            <div class="form-group"><label class="col-sm-4 control-label">Barcode</label><div class="col-sm-8">{!! Form::text('code', null, ['class'=>'form-control','id'=>'code']) !!}</div></div>
            <div class="form-group"><label class="col-sm-4 control-label">Nama Barang</label><div class="col-sm-8">{!! Form::text('name', null, ['class'=>'form-control','required'=>'required','id'=>'name']) !!}</div></div>
            <div class="form-group"><label class="col-sm-4 control-label">Brand / Merek</label><div class="col-sm-8">{!! Form::select('brand_id', getBrands(), null, ['class'=>'form-control select2','style'=>'width:100%','id'=>'brand_id']) !!}</div></div>
            <div class="form-group"><label class="col-sm-4 control-label">Satuan</label><div class="col-sm-8">{!! Form::select('unit_id', getUnits(), null, ['class'=>'form-control select2','style'=>'width:100%','id'=>'unit_id']) !!}</div></div>
            <div class="form-group"><label class="col-sm-4 control-label">Harga Beli</label><div class="col-sm-8">{!! Form::text('price', null, ['class'=>'form-control','required'=>'required','id'=>'price','onkeyup'=>'formatNumber("price")']) !!}</div></div>
            <div class="form-group"><label class="col-sm-4 control-label">Harga Jual</label><div class="col-sm-8">{!! Form::text('selling_price', null, ['class'=>'form-control','required'=>'required','id'=>'selling_price','onkeyup'=>'formatNumber("selling_price")']) !!}</div></div>
        </div></div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
            <button type="button" class="btn btn-success" onclick="event.preventDefault(); addNewGood();"><i class="fa fa-plus"></i> Tambah</button>
        </div>
    </div></div>
</div>
@endif

{!! Form::close() !!}

@section('js-addon')
<script>
/* ═══════ STATE ═══════════════════════════════════════════════════════════ */
var next_idx        = 2;
var blank_idx       = 1;
var total_real_item = 0;
var total_good      = 0;
var group_map       = {};   /* good_id → { rows[], name, barcode, old_stock } */
var row_group       = {};   /* rowIdx  → good_id */
var group_order     = 0;
var group_parity    = {};   /* good_id → 'odd'|'even' */

/* ═══════ INIT ════════════════════════════════════════════════════════════ */
$(document).ready(function () {
    $('.select2').select2();
    $('#all_search').focus();
    $('#loading_date').datepicker({ autoclose:true, format:'yyyy-mm-dd', todayHighlight:true });
    $('#exp-1').datepicker({ autoclose:true, format:'yyyy-mm-dd', todayHighlight:true });
});

/* ═══════ BADGE ══════════════════════════════════════════════════════════ */
function updateBadge(){
    document.getElementById('badge-item-count').textContent = total_real_item + ' item';
}

/* ═══════════════════════════════════════════════════════════════════════
   REBUILD MERGE
   Kolom yang di-merge (rowspan) per grup barang:
     td-no, td-name, td-barcode, td-exp, td-old-stock, td-new-stock, td-act
   Cara kerja:
     - Baris PERTAMA grup → rowspan = jumlah baris, display tampil
     - Baris ke-2 dst    → td tersebut di-hide (display:none)
   ═══════════════════════════════════════════════════════════════════════ */
var MERGED_COLS = ['td-no-','td-name-','td-barcode-','td-exp-','td-old-stock-','td-new-stock-','td-act-'];

function rebuildGroupMerge(good_id) {
    if (!group_map[good_id]) return;
    var g        = group_map[good_id];
    var rows     = g.rows;
    if (!rows.length) return;
    var firstIdx = rows[0];
    var count    = rows.length;
    var parity   = group_parity[good_id] || 'odd';

    for (var ri = 0; ri < rows.length; ri++) {
        var idx = rows[ri];
        /* Stripe warna baris */
        $('#row-data-'+idx).removeClass('group-odd group-even').addClass('group-'+parity);

        for (var ci = 0; ci < MERGED_COLS.length; ci++) {
            var $cell = $('#'+MERGED_COLS[ci]+idx);
            if (ri === 0) {
                $cell.attr('rowspan', count).show();
            } else {
                $cell.hide();
            }
        }
    }

    /* Isi konten sel merged di baris pertama */
    $('#td-no-'+firstIdx).text(total_good + 1);
    renderNameCell(good_id);
    $('#barcode_display-'+firstIdx).text(g.barcode || '');
    $('#old_stock_display-'+firstIdx).text(g.old_stock !== '' ? g.old_stock : '—');
    refreshNewStock(good_id);
}

/* Hitung stok baru = stok lama + total qty semua satuan */
function refreshNewStock(good_id) {
    if (!group_map[good_id]) return;
    var rows  = group_map[good_id].rows;
    var first = rows[0];
    var old   = parseInt(group_map[good_id].old_stock) || 0;
    var qty   = 0;
    for (var i = 0; i < rows.length; i++) {
        qty += parseFloat($('#quantity-'+rows[i]).val()) || 0;
    }
    var nv = old + qty;
    $('#new_stock-'+first).val(nv);
    $('#new_stock_display-'+first).text(nv);
}

/* ═══════ BUILD ROW HTML ═════════════════════════════════════════════════ */
var _uopts = '<option value="">Pilih satuan</option>'
    @foreach(getUnits() as $uid => $uname)
    + '<option value="{{ $uid }}">{{ addslashes($uname) }}</option>'
    @endforeach
;
var _sup = @if(\Auth::user()->role == 'supervisor') true @else false @endif;
var _canEditUnit = @if(\Auth::user()->email == 'admin') true @else false @endif;   /* hanya admin yang boleh ubah satuan */
var _canEditName = @if(isset($canEditName) && $canEditName) true @else false @endif;   /* ubah nama barang (klik nama) */

function buildRow(i) {
    var ps = _sup ? '' : ' style="display:none"';
    return '<tr id="row-data-'+i+'" class="group-odd">'
    +'<input type="hidden" name="base_qtys[]" id="base_qty-'+i+'">'
    /* MERGED */
    +'<td class="col-no td-merged td-no"   id="td-no-'+i+'"        rowspan="1"></td>'
    +'<td class="col-name td-merged"        id="td-name-'+i+'"      rowspan="1">'
    +  '<div class="cell-txt'+(_canEditName?' name-click':'')+'" id="name_display-'+i+'"'+(_canEditName?' title="Klik untuk mengubah nama"':'')+'></div>'
    +  '<input type="hidden" name="names[]"      id="name-'+i+'">'
    +  '<input type="hidden" name="name_temps[]" id="name_temp-'+i+'">'
    +  '<input type="hidden" name="name_changed[]" id="name_changed-'+i+'" value="0">'
    +'</td>'
    +'<td class="col-barcode td-merged"     id="td-barcode-'+i+'"   rowspan="1">'
    +  '<div class="cell-txt sm" id="barcode_display-'+i+'"></div>'
    +  '<input type="hidden" name="barcodes[]" id="barcode-'+i+'">'
    +'</td>'
    +'<td class="col-exp td-merged"         id="td-exp-'+i+'"       rowspan="1">'
    +  '<input type="text" class="fc" name="exp_dates[]" id="exp-'+i+'" placeholder="yyyy-mm-dd">'
    +'</td>'
    /* PER SATUAN */
    +'<td class="col-qty"><input type="text" class="fc" name="quantities[]" id="quantity-'+i+'"'
    +' onchange="editPrice('+i+')" onkeypress="editPrice('+i+')" onkeyup="editPrice('+i+')"></td>'
    +'<td class="col-unit"><select class="fc'+(_canEditUnit?'':' unit-locked')+'" name="units[]" id="unit-'+i+'" style="width:100%"'+(_canEditUnit?'':' tabindex="-1"')+''
    +' onchange="changePriceByUnit('+i+')">'+_uopts+'</select></td>'
    /* MERGED stok */
    +'<td class="col-stock td-merged" id="td-old-stock-'+i+'" rowspan="1">'
    +  '<div class="cell-txt sm" id="old_stock_display-'+i+'">—</div>'
    +  '<input type="hidden" name="old_stocks[]" id="old_stock-'+i+'">'
    +'</td>'
    +'<td class="col-stock td-merged" id="td-new-stock-'+i+'" rowspan="1">'
    +  '<div class="cell-txt sm" id="new_stock_display-'+i+'">—</div>'
    +  '<input type="hidden" name="new_stocks[]" id="new_stock-'+i+'">'
    +'</td>'
    /* Harga */
    +'<td class="col-price"'+ps+'><input type="text" class="fc" name="prices[]"       id="price-'+i+'"       onchange="editBuyPrice('+i+')" onkeyup="editBuyPrice('+i+')"></td>'
    +'<td class="col-price"'+ps+'><input type="text" class="fc" name="total_prices[]" id="total_price-'+i+'" readonly></td>'
    +'<td class="col-price"'+ps+'><input type="text" class="fc" name="sell_prices[]"  id="sell_price-'+i+'"></td>'
    /* MERGED action */
    +'<td class="col-act td-merged" id="td-act-'+i+'" rowspan="1">'
    +  '<button type="button" class="btn-delete" onclick="deleteGroup('+i+')" title="Hapus"><i class="fa fa-trash"></i></button>'
    +'</td>'
    +'</tr>';
}

/* ═══════ FILL ITEM — dipanggil sekali per satuan ════════════════════════ */
function fillItem(good, _u, idx, total) {
    if (!good || good.length===0) { alert('Barang tidak ditemukan'); return; }
    var gid    = String(good.id);
    var isFirst = idx===0;
    var isLast  = idx===total;

    if (isFirst) {
        if (group_map[gid]) {
            notify('Barang "'+good.name+'" sudah ada.', 'warning');
            return;
        }
        group_parity[gid] = (group_order%2===0)?'odd':'even';
        group_order++;
        group_map[gid] = { rows:[], name:good.name, barcode:good.code, old_stock:good.old_stock };
        total_real_item++; updateBadge();
    }

    var ri;
    if (isFirst) {
        ri = blank_idx;
    } else {
        ri = next_idx++;
        $('#row-data-'+blank_idx).after(buildRow(ri));
        $('#exp-'+ri).datepicker({autoclose:true,format:'yyyy-mm-dd',todayHighlight:true});
    }

    group_map[gid].rows.push(ri);
    row_group[ri] = gid;
    populateRow(ri, good);

    if (isLast) {
        rebuildGroupMerge(gid);
        total_good++;
        var ni = next_idx++;
        $('#table-transaction').prepend(buildRow(ni));
        $('#exp-'+ni).datepicker({autoclose:true,format:'yyyy-mm-dd',todayHighlight:true});
        blank_idx = ni;
    }
}

function populateRow(ri, good) {
    var gps = good.getPcsSellingPrice;
    $('#name-'+ri).val(good.id);
    $('#name_temp-'+ri).val(good.name);
    $('#barcode-'+ri).val(good.code);
    $('#old_stock-'+ri).val(good.old_stock);
    $('#new_stock-'+ri).val(parseInt(good.old_stock)+0);
    $('#base_qty-'+ri).val(gps.unit_qty);
    $('#quantity-'+ri).val(0);   /* default 0: harus diisi manual agar tidak salah loading */
    $('#price-'+ri).val(parseFloat(gps.base_buy_price)*parseFloat(gps.unit_qty));
    $('#sell_price-'+ri).val(gps.selling_price);
    $('#unit-'+ri).val(gps.unit_id).trigger('change');
    editPrice(ri);
}

/* ═══════ DELETE GROUP ═══════════════════════════════════════════════════ */
function deleteGroup(anyRowIdx) {
    anyRowIdx = parseInt(anyRowIdx);
    var gid = row_group[anyRowIdx];
    if (!gid) return;
    if (!confirm('Hapus "'+group_map[gid].name+'" beserta semua satuannya?')) return;
    var rows = group_map[gid].rows.slice();
    for (var i=0;i<rows.length;i++) { $('#row-data-'+rows[i]).remove(); delete row_group[rows[i]]; }
    delete group_map[gid]; delete group_parity[gid];
    total_real_item=Math.max(0,total_real_item-1);
    total_good=Math.max(0,total_good-1);
    updateBadge(); changeTotal();
    var n=1;
    for (var g in group_map) {
        if (group_map[g].rows.length) $('#td-no-'+group_map[g].rows[0]).text(n++);
    }
    total_good=n-1;
}

/* ═══════ PENCARIAN TERPADU (barcode + nama barang) ═══════════════════════
   - Angka saja (>= 6 digit) dianggap barcode: dicoba langsung. Cocok -> langsung masuk tabel
     (fokus tetap di kolom cari agar bisa scan terus). Tidak cocok -> dicari sebagai keyword.
   - Selain itu dianggap keyword: hasil tampil di panel dan bisa dicentang beberapa sekaligus.
   - Jumlah barang yang baru ditambahkan = 0, harus diisi manual (menghindari salah loading).
   ═══════════════════════════════════════════════════════════════════════ */
var URL_BARCODE = "{!! url($role.'/good/searchByBarcode/') !!}/";
var URL_KEYWORD = "{!! url($role.'/good/searchByKeyword/') !!}/";
var URL_BYID    = "{!! url($role.'/good/searchById/') !!}/";

var sr = { timer:null, seq:0, items:[], active:-1, open:false, query:'', adding:false, queue:[] };

function focusSearch(){ $('#all_search').focus().select(); }
function isBarcodeLike(v){ return /^\d{6,}$/.test(v); }

function notify(msg, type){
    var $t = $('<div class="toast-item ' + (type || 'info') + '"></div>').text(msg);
    $('#toast-box').append($t);
    setTimeout(function(){ $t.fadeOut(300, function(){ $t.remove(); }); }, 3800);
}

function onSearchInput(){
    clearTimeout(sr.timer);
    var v = $.trim($('#all_search').val());
    if (!v) { sr.seq++; closeResults(); return; }
    sr.timer = setTimeout(function(){ runSearch(v, false); }, isBarcodeLike(v) ? 150 : 300);
}

function runSearch(v, viaEnter){
    clearTimeout(sr.timer);
    var seq = ++sr.seq;
    if (isBarcodeLike(v)) {
        $.ajax({
            url: URL_BARCODE + encodeURIComponent(v),
            success: function(r){
                if (seq !== sr.seq) return;
                if (r && r.good && r.good.id) addGoods([String(r.good.id)], { focusQty:false });
                else keywordSearch(v, seq, viaEnter);
            },
            error: function(){ if (seq === sr.seq) keywordSearch(v, seq, viaEnter); }
        });
    } else {
        keywordSearch(v, seq, viaEnter);
    }
}

function keywordSearch(v, seq, viaEnter){
    showPanel();
    $('#sr-info').text('');
    $('#sr-list').html('<div class="sr-state"><i class="fa fa-spinner fa-spin"></i> Mencari...</div>');
    $.ajax({
        url: URL_KEYWORD + encodeURIComponent(v),
        success: function(res){ if (seq === sr.seq) renderResults(res.goods || [], v, viaEnter); },
        error: function(){
            if (seq !== sr.seq) return;
            $('#sr-list').html('<div class="sr-state" style="color:#DC2626">Gagal mengambil data.</div>');
        }
    });
}

function renderResults(list, v, viaEnter){
    sr.query  = v;
    sr.items  = [];
    for (var i = 0; i < list.length; i++) {
        var id = String(list[i].id);
        sr.items.push({ id:id, name:list[i].name, code:list[i].code || '', exists:!!group_map[id], checked:false });
    }

    if (!sr.items.length) {
        sr.active = -1;
        $('#sr-list').html('<div class="sr-state">Tidak ada hasil untuk "' + esc(v) + '".</div>');
        $('#sr-info').text('0 hasil');
        updateSrFoot();
        return;
    }

    // Enter + hanya 1 hasil yang bisa ditambah -> langsung masuk tabel
    var avail = $.grep(sr.items, function(it){ return !it.exists; });
    if (viaEnter && sr.items.length === 1 && avail.length === 1) {
        addGoods([avail[0].id], { focusQty:true, fromPanel:true });
        return;
    }

    sr.active = -1;
    for (var k = 0; k < sr.items.length; k++) { if (!sr.items[k].exists) { sr.active = k; break; } }
    paintResults();
}

function paintResults(){
    var scroll = $('#sr-list').scrollTop();
    var h = '';
    for (var i = 0; i < sr.items.length; i++) {
        var it = sr.items[i];
        h += '<div class="sr-item' + (it.exists ? ' disabled' : '') + (it.checked ? ' checked' : '') + (i === sr.active ? ' active' : '') + '" data-i="' + i + '">'
           +   '<input type="checkbox" tabindex="-1"' + (it.checked ? ' checked' : '') + (it.exists ? ' disabled' : '') + '>'
           +   '<span class="sr-name">' + esc(it.name) + '</span>'
           +   (it.code ? '<span class="sr-code">' + esc(it.code) + '</span>' : '')
           +   (it.exists ? '<span class="sr-tag">Sudah ada</span>' : '')
           + '</div>';
    }
    $('#sr-list').html(h).scrollTop(scroll);
    $('#sr-info').text(sr.items.length + ' hasil');
    updateSrFoot();

    var el = $('#sr-list .sr-item.active')[0];
    if (el && el.scrollIntoView) { el.scrollIntoView({ block:'nearest' }); }
}

function updateSrFoot(){
    var n = checkedIds().length;
    $('#sr-count').text(n);
    $('#sr-add').toggleClass('is-empty', n === 0);
}

function checkedIds(){
    var ids = [];
    for (var i = 0; i < sr.items.length; i++) { if (sr.items[i].checked && !sr.items[i].exists) ids.push(sr.items[i].id); }
    return ids;
}

function toggleCheck(i, force){
    if (sr.adding) return;
    var it = sr.items[i];
    if (!it || it.exists) return;
    it.checked = (force === undefined) ? !it.checked : force;
    $('#sr-list .sr-item').eq(i).toggleClass('checked', it.checked).find('input').prop('checked', it.checked);
    updateSrFoot();
}

function setActive(i){
    sr.active = i;
    var $it = $('#sr-list .sr-item').removeClass('active').eq(i).addClass('active');
    if ($it[0] && $it[0].scrollIntoView) $it[0].scrollIntoView({ block:'nearest' });
}

function moveActive(d){
    var i = sr.active;
    while (true) {
        i += d;
        if (i < 0 || i >= sr.items.length) return;
        if (!sr.items[i].exists) { setActive(i); return; }
    }
}

function srCheckAll(){
    if (sr.adding) return;
    for (var i = 0; i < sr.items.length; i++) { if (!sr.items[i].exists) sr.items[i].checked = true; }
    paintResults();
}

function srAddChecked(){
    if (sr.adding) return;                       // cegah klik ganda
    var ids = checkedIds();
    if (!ids.length) {
        var it = sr.items[sr.active];
        if (it && !it.exists) ids = [it.id];
    }
    if (ids.length) addGoods(ids, { focusQty:true, fromPanel:true });
}

function showPanel(){ sr.open = true; $('#sr-panel').show(); }
function closeResults(){ sr.open = false; sr.items = []; sr.active = -1; sr.query = ''; $('#sr-panel').hide(); }

function handleSearchEnter(){
    clearTimeout(sr.timer);
    if (sr.adding && sr.open) return;
    var v = $.trim($('#all_search').val());
    if (!v) return;
    if (!sr.open || sr.query !== v) { runSearch(v, true); return; }

    var ids = checkedIds();
    if (!ids.length) {
        var it = sr.items[sr.active];
        if (!it) return;
        if (it.exists) { notify('"' + it.name + '" sudah ada di tabel', 'warning'); flashGroup(it.id); return; }
        ids = [it.id];
    }
    addGoods(ids, { focusQty:true, fromPanel:true });
}

function onSearchKeydown(e){
    var k = e.keyCode;
    if (k === 13) { e.preventDefault(); handleSearchEnter(); return; }

    var val = $.trim($('#all_search').val());

    if (!sr.open) {
        if (k === 40 || k === 38) {
            e.preventDefault();
            if (e.repeat) return;                 // tombol ditahan tidak boleh terus berpindah
            if (k === 40) {
                if (val) runSearch(val, false);   // ada teks -> cari
                else focusFirstRow();             // kosong -> turun ke tabel
            }
        } else if (k === 27) { $('#all_search').val(''); }
        return;
    }

    if (k === 40 || k === 38) {
        e.preventDefault();
        if (sr.adding) return;
        if (e.shiftKey) toggleCheck(sr.active, true);
        moveActive(k === 40 ? 1 : -1);
    } else if (k === 45) {                       // Insert
        e.preventDefault();
        toggleCheck(sr.active);
    } else if (k === 27) {                       // Esc
        e.preventDefault();
        if (!sr.adding) closeResults();
    }
}

/* ═══════ TAMBAH KE TABEL (dengan antrian + indikator loading) ═══════════ */
function setBusy(on, fromPanel){
    $('#sr-busy').toggle(!!on);
    if (fromPanel !== undefined) {
        $('#sr-panel').toggleClass('busy', !!(on && fromPanel));
        $('#sr-add').toggleClass('loading', !!(on && fromPanel)).prop('disabled', !!(on && fromPanel));
        $('#sr-add .sr-lbl').text(on && fromPanel ? 'Menambahkan...' : 'Tambahkan');
    }
}

function addGoods(ids, opts){
    opts = opts || {};
    if (!ids || !ids.length) return;
    if (opts.fromPanel && sr.adding) return;     // klik/Enter ganda dari panel diabaikan

    sr.queue.push({ ids:ids, opts:opts });
    $('#all_search').val('');                    // siap untuk scan berikutnya
    if (!sr.adding) processQueue();
    else setBusy(true);
}

function processQueue(){
    var job = sr.queue.shift();
    if (!job) { sr.adding = false; setBusy(false, false); return; }

    sr.adding = true;
    sr.seq++;                                    // batalkan pencarian yang masih berjalan
    setBusy(true, !!job.opts.fromPanel);

    var calls = [];
    $.each(job.ids, function(_, id){
        var d = $.Deferred();
        $.ajax({ url: URL_BYID + id })
            .done(function(res){ d.resolve(res); })
            .fail(function(){ d.resolve(null); });
        calls.push(d.promise());
    });

    $.when.apply($, calls).done(function(){
        var results = (calls.length === 1) ? [arguments[0]] : Array.prototype.slice.call(arguments);
        var added = [], dup = [], minus = [], failed = 0;

        try {
            for (var n = 0; n < results.length; n++) {
                var res = results[n];
                if (!res || !res.units || !res.units.length) { failed++; continue; }
                var r = res.units, gid = String(r[0].good_id);

                if (group_map[gid]) { dup.push(r[0].name); flashGroup(gid); continue; }

                for (var i = 0; i < r.length; i++) {
                    var gps = { unit_id:r[i].unit_id, unit_qty:r[i].unit_qty, base_buy_price:r[i].good_base_buy_price, selling_price:r[i].selling_price };
                    fillItem({ id:r[i].good_id, name:r[i].name, code:r[i].code, getPcsSellingPrice:gps, old_stock:r[i].stock }, -1, i, r.length - 1);
                }
                added.push(gid);
                if (parseFloat(r[0].stock) < 0) minus.push(r[0].name);
            }
        } catch (err) {
            notify('Terjadi kesalahan saat menambahkan barang', 'error');
            if (window.console) console.error(err);
        }

        closeResults();

        if (added.length) notify(added.length + ' barang ditambahkan. Tekan panah bawah untuk mengisi jumlah (default 0).', 'success');
        if (dup.length)   notify('Sudah ada: ' + dup.join(', '), 'warning');
        if (minus.length) notify('Stok minus, lakukan stock opname: ' + minus.join(', '), 'warning');
        if (failed)       notify(failed + ' barang gagal diambil', 'error');

        if (sr.queue.length) { processQueue(); return; }   // masih ada antrian (mis. scan beruntun)

        sr.adding = false;
        setBusy(false, false);

        focusSearch();                                // fokus tetap di cari -> bisa langsung cari/scan lagi; tekan panah bawah untuk ke tabel
    });
}

function focusQtyOfGroup(gid){
    var g = group_map[String(gid)];
    if (!g || !g.rows.length) { focusSearch(); return; }
    var el = document.getElementById('quantity-' + g.rows[0]);
    if (el) { el.focus(); if (el.scrollIntoView) el.scrollIntoView({ block:'nearest' }); }
}

function flashGroup(gid){
    var g = group_map[String(gid)];
    if (!g) return;
    var $rows = $();
    for (var i = 0; i < g.rows.length; i++) { $rows = $rows.add($('#row-data-' + g.rows[i])); }
    $rows.addClass('flash');
    setTimeout(function(){ $rows.removeClass('flash'); }, 1500);
    if ($rows[0] && $rows[0].scrollIntoView) $rows[0].scrollIntoView({ block:'nearest' });
}

/* ═══════ KEYBOARD DI TABEL ═══════════════════════════════════════════════
   Enter / Shift+Enter  : kolom berikutnya / sebelumnya (Jumlah -> Harga Beli -> Harga Jual -> baris berikutnya)
   Panah atas/bawah     : baris atas / bawah di kolom yang sama
   Panah kiri/kanan     : kolom sebelah (Jumlah, Satuan, Harga Beli, Harga Jual) saat isi kolom terblok / kursor di tepi
   Esc                  : kembali ke kolom cari
   Alt+Delete           : hapus barang di baris ini
   ═══════════════════════════════════════════════════════════════════════ */
var FLOW_TYPES = ['quantity', 'price', 'sell_price'];
var ROW_TYPES  = ['quantity', 'unit', 'price', 'sell_price'];

function dataRows(){
    return $('#table-transaction tr').filter(function(){
        return /^row-data-\d+$/.test(this.id) && this.id !== 'row-data-' + blank_idx;
    });
}

function ctl($tr, type){
    var el = $tr.find('[id^="' + type + '-"]')[0];
    if (!el || el.readOnly || $(el).hasClass('unit-locked') || !$(el).is(':visible')) return null;
    return el;
}

function focusCtl(el){
    if (!el) return false;
    el.focus();
    if (el.scrollIntoView) el.scrollIntoView({ block:'nearest' });
    return true;
}

function focusFirstRow(){
    var $rows = dataRows();
    if (!$rows.length) { notify('Belum ada barang di tabel', 'info'); return; }
    focusCtl(ctl($rows.eq(0), 'quantity'));
}

function visibleTypes($tr, types){
    var out = [];
    for (var i = 0; i < types.length; i++) { if (ctl($tr, types[i])) out.push(types[i]); }
    return out;
}

function moveFlow(idx, type, dir){
    var $tr = $('#row-data-' + idx);
    var flow = visibleTypes($tr, FLOW_TYPES);
    var cur  = flow.indexOf(type === 'unit' ? 'quantity' : type);
    var $rows = dataRows(), ri = $rows.index($tr);

    if (dir > 0) {
        if (cur + 1 < flow.length) { focusCtl(ctl($tr, flow[cur + 1])); return; }
        // akhir baris: lanjut ke baris berikutnya, jika sudah paling bawah kembali ke kolom cari
        if (ri + 1 >= $rows.length) { focusSearch(); return; }
        focusCtl(ctl($rows.eq(ri + 1), 'quantity'));
    } else {
        if (cur - 1 >= 0) { focusCtl(ctl($tr, flow[cur - 1])); return; }
        var $prev = $rows.eq(ri - 1);
        if (ri - 1 < 0 || !$prev.length) { focusSearch(); return; }
        var pf = visibleTypes($prev, FLOW_TYPES);
        focusCtl(ctl($prev, pf[pf.length - 1] || 'quantity'));
    }
}

function moveVertical(idx, type, d){
    var $rows = dataRows(), ri = $rows.index($('#row-data-' + idx)) + d;
    if (ri < 0) { focusSearch(); return; }
    if (ri >= $rows.length) return;
    focusCtl(ctl($rows.eq(ri), type));
}

function moveHorizontal(idx, type, d){
    var $tr = $('#row-data-' + idx);
    var types = visibleTypes($tr, ROW_TYPES), p = types.indexOf(type) + d;
    if (p >= 0 && p < types.length) focusCtl(ctl($tr, types[p]));
}

function handleTableKey(e, t, type, idx){
    var k = e.keyCode;

    if (k === 27) { e.preventDefault(); focusSearch(); return; }                                // Esc
    if (e.altKey && k === 46) { e.preventDefault(); deleteGroup(idx); return; }                // Alt+Delete
    if (type === 'exp') return;                                                                 // tanggal: ketik manual / Tab

    if (e.repeat && (k === 13 || (k >= 37 && k <= 40))) { e.preventDefault(); return; }         // tombol ditahan: 1 langkah saja

    if (k === 13) { e.preventDefault(); e.stopPropagation(); moveFlow(idx, type, e.shiftKey ? -1 : 1); return; }

    if (t.tagName === 'SELECT') return;                                                         // panah di dropdown = pilih opsi

    if (k === 40 || k === 38) { e.preventDefault(); moveVertical(idx, type, k === 40 ? 1 : -1); return; }

    if (k === 39 || k === 37) {
        var len = t.value.length, s = t.selectionStart, en = t.selectionEnd;
        var all = (s === 0 && en === len);
        if (k === 39 && (all || (s === len && en === len))) { e.preventDefault(); moveHorizontal(idx, type, 1); }
        if (k === 37 && (all || (s === 0 && en === 0)))     { e.preventDefault(); moveHorizontal(idx, type, -1); }
    }
}

function keyboardSubmit(){
    if (total_real_item === 0) { notify('Belum ada barang di tabel', 'warning'); focusSearch(); return; }
    if (!confirm('Simpan data ini?')) return;
    if ($('.btn-submit.tambah').length) submitForm();
    else if ($('.btn-submit.edit').length) $('.btn-submit.edit').click();
}

/* Jumlah 0 tidak boleh disimpan */
function validateZeroQty(){
    var bad = null;
    $('#table-transaction tr').each(function(){
        var m = /^row-data-(\d+)$/.exec(this.id);
        if (!m) return;
        var idx = parseInt(m[1], 10);
        if (!row_group[idx]) return;
        if ((parseFloat($('#quantity-' + idx).val()) || 0) <= 0) { bad = idx; return false; }
    });
    if (bad === null) return true;
    alert('Jumlah "' + group_map[row_group[bad]].name + '" masih 0. Isi jumlah barang yang diterima.');
    var el = document.getElementById('quantity-' + bad);
    if (el) { el.focus(); if (el.scrollIntoView) el.scrollIntoView({ block:'center' }); }
    return false;
}

$(document).ready(function(){
    // Keyboard tabel (fase capture agar tidak terblokir script lain)
    document.addEventListener('keydown', function(e){
        var t = e.target;
        if (!t || !t.closest || !t.closest('#table-transaction')) return;
        var tag = t.tagName;
        if (tag !== 'INPUT' && tag !== 'SELECT') return;
        var m = /^(quantity|unit|price|sell_price|exp)-(\d+)$/.exec(t.id);
        if (!m) return;
        handleTableKey(e, t, m[1], parseInt(m[2], 10));
    }, true);

    // Isi kolom otomatis terblok saat fokus (klik maupun keyboard)
    $(document).on('focus', '#table-transaction input.fc', function(){
        if (/^exp-/.test(this.id)) return;
        var el = this;
        el._sel = true;
        el.select();
        setTimeout(function(){ el.select(); }, 0);
    });
    $(document).on('mouseup', '#table-transaction input.fc', function(e){
        if (this._sel) { this._sel = false; e.preventDefault(); }
    });

    // klik di luar panel = tutup
    $(document).on('mousedown', function(e){
        if (!$(e.target).closest('#sr-wrap').length && !sr.adding) closeResults();
    });
    // klik di panel tidak boleh memindahkan fokus dari kolom cari
    $('#sr-panel').on('mousedown', function(e){ e.preventDefault(); });
    $('#sr-list').on('click', '.sr-item', function(){ if (sr.adding) return; var i = parseInt($(this).data('i'), 10); sr.active = i; toggleCheck(i); });
    $('#sr-list').on('dblclick', '.sr-item', function(){
        if (sr.adding) return;
        var it = sr.items[parseInt($(this).data('i'), 10)];
        if (it && !it.exists) addGoods([it.id], { focusQty:true, fromPanel:true });
    });

    // Shortcut global
    $(document).on('keydown', function(e){
        var tag = (e.target.tagName || '').toLowerCase();
        var typing = tag === 'input' || tag === 'textarea' || tag === 'select' || e.target.isContentEditable;
        if (e.keyCode === 113) { e.preventDefault(); focusSearch(); }                       // F2 -> cari
        else if (e.keyCode === 114) { e.preventDefault(); focusFirstRow(); }                // F3 -> tabel
        else if (e.key === '/' && !typing) { e.preventDefault(); focusSearch(); }           // /
        else if (e.ctrlKey && e.keyCode === 13) { e.preventDefault(); keyboardSubmit(); }   // Ctrl+Enter
    });

    // Mode Edit: tolak simpan bila ada jumlah 0
    if ($('.btn-submit.edit').length) {
        $('#loading-form').on('submit', function(e){ if (!validateZeroQty()) e.preventDefault(); });
    }
});


/* ═══════ UBAH NAMA BARANG ════════════════════════════════════════════════
   Klik nama barang (atau F4 saat fokus di baris) -> kolom nama bisa diedit.
   Enter = simpan lalu terkunci lagi. Esc / klik di luar = batal.
   Shift+Enter = baris baru. Terkunci kembali sampai nama diklik lagi.
   ═══════════════════════════════════════════════════════════════════════ */
var URL_RENAME = null;   // Isi bila sudah ada endpoint penyimpanan nama ke master barang, mis. "{!! url($role.'/good/updateName') !!}"
                         // (POST: good_id, name). Bila null, nama baru dikirim saat form disimpan lewat name_temps[] + name_changed[] (=1).
var nameEdit = { gid:null, saving:false };

function renderNameCell(gid){
    var g = group_map[String(gid)];
    if (!g || !g.rows.length) return;
    var count = g.rows.length;
    $('#name_display-' + g.rows[0]).html(
        esc(g.name)
        + (count > 1 ? '<small>' + count + ' satuan</small>' : '')
        + (g.renamed ? '<small class="renamed-tag">nama diubah</small>' : '')
    );
}

function startNameEdit(gid){
    if (!_canEditName || nameEdit.gid || sr.adding) return;
    var g = group_map[String(gid)];
    if (!g) return;

    var $div = $('#name_display-' + g.rows[0]);
    var $ta  = $('<textarea class="fc name-edit" rows="2" maxlength="255"></textarea>').val(g.name);
    nameEdit.gid = String(gid);
    nameEdit.saving = false;
    $div.hide().after($ta);

    $ta.on('keydown', function(e){
        if (e.keyCode === 13 && !e.shiftKey) { e.preventDefault(); e.stopPropagation(); commitNameEdit(); }
        else if (e.keyCode === 27) { e.preventDefault(); e.stopPropagation(); endNameEdit(true); }
    }).on('blur', function(){
        setTimeout(function(){
            if (nameEdit.gid && !nameEdit.saving) {
                endNameEdit(false);
                notify('Perubahan nama dibatalkan. Tekan Enter untuk menyimpan.', 'info');
            }
        }, 120);
    });
    $ta.focus().select();
}

function endNameEdit(refocus){
    var gid = nameEdit.gid;
    if (!gid) return;
    var g = group_map[gid];
    nameEdit.gid = null;
    nameEdit.saving = false;
    if (g) {
        var $div = $('#name_display-' + g.rows[0]);
        $div.next('textarea.name-edit').remove();
        $div.show();
        if (refocus) focusQtyOfGroup(gid);
    }
}

function applyRename(gid, name, savedToServer){
    var g = group_map[String(gid)];
    if (!g) return;
    g.name = name;
    g.renamed = true;
    for (var i = 0; i < g.rows.length; i++) {
        $('#name_temp-' + g.rows[i]).val(name);
        $('#name_changed-' + g.rows[i]).val(savedToServer ? 0 : 1);   // 1 = backend perlu memperbarui nama master saat simpan
    }
    renderNameCell(gid);
    notify('Nama diubah menjadi "' + name + '"', 'success');
}

function commitNameEdit(){
    var gid = nameEdit.gid, g = group_map[gid];
    if (!g || nameEdit.saving) return;
    var $ta = $('#name_display-' + g.rows[0]).next('textarea.name-edit');
    var val = $.trim($ta.val().replace(/\s+/g, ' '));

    if (!val) { notify('Nama barang tidak boleh kosong', 'warning'); $ta.focus(); return; }
    if (val === g.name) { endNameEdit(true); return; }

    if (!URL_RENAME) { applyRename(gid, val, false); endNameEdit(true); return; }

    nameEdit.saving = true;
    $ta.prop('disabled', true);
    $.ajax({
        url: URL_RENAME, type: 'POST',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        data: { good_id: gid, name: val, role: '{{ $role }}' },
        success: function(res){
            var nm = (res && res.good && res.good.name) ? res.good.name : val;
            applyRename(gid, nm, true);
            endNameEdit(true);
        },
        error: function(){
            nameEdit.saving = false;
            $ta.prop('disabled', false).focus();
            notify('Gagal menyimpan nama barang', 'error');
        }
    });
}

$(document).ready(function(){
    $(document).on('click', '.name-click', function(){
        var idx = parseInt(this.id.split('-')[1], 10);
        var gid = row_group[idx];
        if (gid) startNameEdit(gid);
    });

    // F4 saat fokus di salah satu kolom baris
    document.addEventListener('keydown', function(e){
        if (e.keyCode !== 115) return;
        var t = e.target;
        if (!t || !t.closest || !t.closest('#table-transaction')) return;
        var m = /-(\d+)$/.exec(t.id || '');
        if (!m) return;
        var gid = row_group[parseInt(m[1], 10)];
        if (gid) { e.preventDefault(); startNameEdit(gid); }
    }, true);
});


/* ═══════ PRICE ══════════════════════════════════════════════════════════ */
function editPrice(i){
    i=parseInt(i);
    var p=parseFloat(unf($('#price-'+i).val()))||0;
    var q=parseFloat($('#quantity-'+i).val())||0;
    $('#total_price-'+i).val(fmt(p*q));
    if(row_group[i]) $('#quantity-'+i).toggleClass('is-zero', q<=0);
    changeTotal();
    var gid=row_group[i]; if(gid) refreshNewStock(gid);
}

function editBuyPrice(i){
    i=parseInt(i);
    var bq=parseFloat($('#base_qty-'+i).val())||1;
    var bp=parseFloat(unf($('#price-'+i).val()))/bq;
    var gid=row_group[i];
    if(gid&&group_map[gid]){
        group_map[gid].rows.forEach(function(ri){
            if(parseInt(ri)!==i){
                var b=parseFloat($('#base_qty-'+ri).val())||1;
                var np=b*bp;
                $('#price-'+ri).val(np);
                $('#total_price-'+ri).val(fmt(np*(parseFloat($('#quantity-'+ri).val())||0)));
            }
        });
    }
    editPrice(i);
}

function changeTotal(){
    var t=0;
    for(var ri in row_group){ var el=document.getElementById('total_price-'+ri); if(el) t+=parseFloat(unf(el.value))||0; }
    $('#total_item_price').val(t);
    var d=document.getElementById('total_price_display');
    if(d) d.textContent='Rp '+t.toLocaleString('id-ID');
}

function changePriceByUnit(i){
    $.ajax({ url:"{!! url($role.'/good/getPriceUnit/') !!}/"+$('#name-'+i).val()+'/'+$('#unit-'+i).val(),
        success:function(res){
            var gu=res.good_unit;
            $('#price-'+i).val(gu?gu.buy_price:'0');
            $('#sell_price-'+i).val(gu?gu.selling_price:'0');
            $('#total_price-'+i).val(fmt((gu?gu.buy_price:0)*(parseFloat($('#quantity-'+i).val())||0)));
            changeTotal();
        }
    });
}

/* ═══════ ADD NEW GOOD ═══════════════════════════════════════════════════ */
function addNewGood(){
    var ok=true;
    if(!$('#category_id').val()){ok=false;alert('Pilih kategori');}
    if(!$('#name').val()){ok=false;alert('Isi nama barang');}
    if(!$('#unit_id').val()){ok=false;alert('Pilih satuan');}
    if(!$('#price').val()){ok=false;alert('Isi harga beli');}
    if(!$('#selling_price').val()){ok=false;alert('Isi harga jual');}
    if(!ok) return;
    $.ajax({ url:"{!! url($role.'/good/store/') !!}", type:'POST',
        headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')},
        data:{role:'{{ $role }}',category_id:$('#category_id').val(),type_id:$('#type_id').val(),code:$('#code').val(),name:$('#name').val(),brand_id:$('#brand_id').val(),unit_id:$('#unit_id').val(),price:$('#price').val(),selling_price:$('#selling_price').val()},
        success:function(res){
            var g=res.good;
            var gps={unit_id:g.unit_id,unit_qty:1,base_buy_price:parseFloat(g.price),selling_price:parseFloat(g.selling_price)};
            fillItem({id:String(g.id),name:g.name,code:g.code,getPcsSellingPrice:gps,old_stock:0},-1,0,0);
            $('#code,#name,#price,#selling_price').val('');
            $('#modal-good').modal('hide');
            setTimeout(function(){ focusSearch(); }, 400);
        }, error:function(){ alert('Harga harus berupa angka.'); }
    });
}

/* ═══════ SUBMIT ═════════════════════════════════════════════════════════ */
function submitForm(){
    var ok=true;
    if($('#distributor_name').val()===''&&$('#all_distributor').val()==='null'){ok=false;alert('Pilih distributor');}
    if($('#loading_date').val()===''){ok=false;alert('Isi tanggal pembelian');}
    if(total_real_item===0){ok=false;alert('Pilih minimal satu barang');}
    if($('#payment').val()==='0000'){alert('Cek jenis pembayaran');}
    if(ok && !validateZeroQty()) ok=false;
    if(ok){
        if(window._submitting) return;
        window._submitting=true;
        $('.btn-submit.tambah').prop('disabled',true).html('<i class="fa fa-spinner fa-spin"></i> Menyimpan...');
        document.getElementById('loading-form').submit();
    }
}

/* ═══════ UTILS ══════════════════════════════════════════════════════════ */
function esc(s){ return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }
function unf(v){ return (v||'').toString().replace(/\./g,'').replace(/,/g,'.'); }
function fmt(n){ return parseFloat(n||0).toLocaleString('id-ID'); }
function formatNumber(id){ var el=document.getElementById(id); if(el){ var n=parseFloat(unf(el.value))||0; el.value=fmt(n); } }
function unFormatNumber(v){ return unf(v); }
</script>
@endsection