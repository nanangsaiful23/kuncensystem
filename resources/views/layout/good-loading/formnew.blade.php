<style type="text/css">
    *, *::before, *::after { box-sizing: border-box; }

    body, html { height: 100%; overflow: hidden; }

    :root {
        --clr-bg:         #F0F4F8;
        --clr-surface:    #FFFFFF;
        --clr-border:     #D1DCE8;
        --clr-primary:    #2563EB;
        --clr-primary-dk: #1D4ED8;
        --clr-success:    #16A34A;
        --clr-warning:    #D97706;
        --clr-danger:     #DC2626;
        --clr-text:       #1E293B;
        --clr-muted:      #64748B;
        --clr-input-bg:   #FAFCFF;
    }

    /* ── Shell ── */
    .loading-shell {
        display: flex;
        flex-direction: column;
        height: calc(100vh - 50px);
        padding: 4px 10px;
        gap: 4px;
        background: var(--clr-bg);
    }

    /* ── Search bar ── */
    .search-bar {
        display: flex;
        align-items: center;
        gap: 8px;
        background: var(--clr-surface);
        border: 1px solid var(--clr-border);
        border-radius: 7px;
        padding: 4px 10px;
        flex-shrink: 0;
    }
    .search-bar .sg { display:flex; align-items:center; gap:5px; flex:1; }
    .search-bar label { font-size:11px; font-weight:600; color:var(--clr-muted); text-transform:uppercase; letter-spacing:.5px; white-space:nowrap; margin:0; }
    .search-bar .iw {
        display:flex; align-items:center; flex:1;
        border:1.5px solid var(--clr-border); border-radius:5px;
        overflow:hidden; background:var(--clr-input-bg); transition:border-color .15s;
    }
    .search-bar .iw:focus-within { border-color:var(--clr-primary); box-shadow:0 0 0 3px rgba(37,99,235,.1); }
    .search-bar .iw input { border:none; background:transparent; padding:4px 7px; font-size:13px; color:var(--clr-text); width:100%; outline:none; }
    .search-bar .iw .si { padding:0 7px; color:var(--clr-muted); cursor:pointer; font-size:13px; }
    .search-bar .sdiv { width:1px; height:24px; background:var(--clr-border); flex-shrink:0; }

    /* ── Body ── */
    .loading-body { display:flex; gap:8px; flex:1; min-height:0; }

    /* ── Table panel ── */
    .panel-table {
        flex:1; display:flex; flex-direction:column;
        background:var(--clr-surface); border:1px solid var(--clr-border);
        border-radius:8px; overflow:hidden; min-width:0;
    }
    .panel-table-header {
        display:flex; align-items:center; justify-content:space-between;
        padding:4px 10px; border-bottom:1px solid var(--clr-border);
        background:#F8FAFC; flex-shrink:0;
    }
    .panel-table-header h6 { margin:0; font-size:11px; font-weight:700; color:var(--clr-muted); text-transform:uppercase; letter-spacing:.5px; }
    .badge-count { background:var(--clr-primary); color:#fff; border-radius:20px; padding:1px 8px; font-size:11px; font-weight:600; }
    .panel-table-scroll { overflow-y:auto; overflow-x:auto; flex:1; }

    /* ── Table ── */
    .tbl-items { width:100%; border-collapse:collapse; font-size:12px; }
    .tbl-items thead th {
        position:sticky; top:0; z-index:2;
        background:#EFF6FF; color:var(--clr-primary-dk);
        font-weight:700; font-size:10.5px; text-transform:uppercase; letter-spacing:.4px;
        padding:4px 5px; border-bottom:2px solid var(--clr-border); white-space:nowrap;
    }
    .tbl-items tbody tr { border-bottom:1px solid #EEF2F7; transition:background .1s; }
    .tbl-items tbody tr:hover { background:#F0F7FF; }
    .tbl-items tbody td { padding:2px 3px !important; vertical-align:middle; }

    /* merged cells (nama + action) */
    .tbl-items td.td-merged {
        vertical-align:middle !important;
        background:#FAFCFF;
        border-left:3px solid var(--clr-primary);
    }
    /* row-group coloring: alternate per good */
    .tbl-items tbody tr.group-odd  { background:#FAFEFF; }
    .tbl-items tbody tr.group-even { background:#FFFAF5; }
    .tbl-items tbody tr.group-odd:hover  { background:#E8F4FF; }
    .tbl-items tbody tr.group-even:hover { background:#FFF3E0; }

    /* compact inputs */
    .tbl-items .fc {
        display:block; width:100%; padding:2px 5px;
        font-size:12px; color:var(--clr-text);
        background:var(--clr-input-bg); border:1px solid var(--clr-border);
        border-radius:4px; resize:none; transition:border-color .15s; font-family:inherit;
    }
    .tbl-items .fc:focus { outline:none; border-color:var(--clr-primary); box-shadow:0 0 0 2px rgba(37,99,235,.1); }
    .tbl-items .fc[readonly], .tbl-items .fc:disabled { background:#F1F5F9 !important; color:var(--clr-muted); cursor:default; }
    .tbl-items input.fc  { height:26px; }
    .tbl-items select.fc { height:26px; cursor:pointer; }
    .tbl-items textarea.fc { height:26px; line-height:1.3; overflow:hidden; }

    /* column widths */
    .col-no      { width:24px; text-align:center; font-weight:700; color:var(--clr-muted); font-size:11px; }
    .col-name    { min-width:130px; max-width:180px; }
    .col-barcode { min-width:75px; max-width:100px; }
    .col-exp     { min-width:95px; }
    .col-qty     { min-width:50px; max-width:70px; }
    .col-unit    { min-width:85px; max-width:120px; }
    .col-stock   { min-width:55px; max-width:70px; }
    .col-price   { min-width:80px; max-width:110px; }
    .col-act     { width:30px; }

    /* name display inside merged cell */
    .name-display {
        font-size:12px; font-weight:600; color:var(--clr-text);
        word-break:break-word; white-space:pre-wrap; line-height:1.35;
        padding:2px 4px;
    }
    .name-display small { display:block; font-size:10px; color:var(--clr-muted); font-weight:400; }

    /* delete btn */
    .btn-delete {
        background:none; border:none; cursor:pointer; color:var(--clr-danger);
        font-size:14px; padding:2px 5px; border-radius:4px; transition:background .15s; line-height:1;
    }
    .btn-delete:hover { background:#FEE2E2; }

    /* ── Sidebar ── */
    .panel-sidebar { width:220px; flex-shrink:0; display:flex; flex-direction:column; gap:6px; }

    .sidebar-card {
        background:var(--clr-surface); border:1px solid var(--clr-border);
        border-radius:8px; padding:8px 10px; flex-shrink:0;
    }
    .sidebar-card .card-title { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:var(--clr-muted); margin:0 0 6px; }

    .field-row { margin-bottom:5px; }
    .field-row:last-child { margin-bottom:0; }
    .field-row label { display:block; font-size:11px; font-weight:600; color:var(--clr-text); margin-bottom:2px; }
    .field-row .form-control,
    .field-row select,
    .field-row textarea,
    .field-row input[type="text"] {
        width:100%; padding:3px 7px; font-size:12px; color:var(--clr-text);
        background:var(--clr-input-bg); border:1.5px solid var(--clr-border);
        border-radius:5px; transition:border-color .15s; font-family:inherit; display:block;
    }
    .field-row .form-control:focus,
    .field-row select:focus,
    .field-row textarea:focus,
    .field-row input[type="text"]:focus {
        outline:none; border-color:var(--clr-primary); box-shadow:0 0 0 3px rgba(37,99,235,.1);
    }
    .field-row textarea { resize:none; height:44px; }
    .field-row .form-control[readonly] { background:#F1F5F9; color:var(--clr-muted); }

    .total-price-display {
        background:#EFF6FF; border:1.5px solid #BFDBFE;
        border-radius:6px; padding:4px 8px; text-align:right;
    }
    .total-price-display .lbl { font-size:9px; font-weight:600; color:var(--clr-muted); text-transform:uppercase; }
    .total-price-display .val { font-size:15px; font-weight:700; color:var(--clr-primary-dk); }

    .btn-submit {
        width:100%; padding:7px; font-size:13px; font-weight:700;
        border:none; border-radius:6px; cursor:pointer;
        transition:filter .15s, transform .1s; letter-spacing:.2px;
    }
    .btn-submit:hover { filter:brightness(1.07); }
    .btn-submit:active { transform:scale(.98); }
    .btn-submit.tambah { background:var(--clr-success); color:#fff; }
    .btn-submit.edit   { background:var(--clr-warning); color:#fff; }

    .btn-add-good {
        width:100%; padding:5px 8px; font-size:12px; font-weight:600;
        border:1.5px dashed var(--clr-success); color:var(--clr-success);
        background:transparent; border-radius:5px; cursor:pointer;
        transition:background .15s; margin-bottom:4px;
    }
    .btn-add-good:hover { background:#F0FDF4; }

    /* select2 */
    .select2-container--default .select2-selection--single { height:26px !important; border-color:var(--clr-border) !important; border-radius:5px !important; }
    .select2-container--default .select2-selection--single .select2-selection__rendered { line-height:24px !important; font-size:12px; padding-left:6px; }
    .select2-container--default .select2-selection--single .select2-selection__arrow { height:24px !important; }

    /* scrollbar */
    .panel-table-scroll::-webkit-scrollbar { width:4px; height:4px; }
    .panel-table-scroll::-webkit-scrollbar-track { background:#F1F5F9; }
    .panel-table-scroll::-webkit-scrollbar-thumb { background:#CBD5E1; border-radius:99px; }

    /* modal result */
    .modal-result-item {
        display:block; width:100%; padding:7px 10px; font-size:13px; color:var(--clr-text);
        cursor:pointer; border-radius:4px; border:none; background:transparent;
        text-align:left; word-break:break-word; transition:background .12s; font-family:inherit; line-height:1.4;
    }
    .modal-result-item:nth-child(odd)  { background:#FFF7ED; }
    .modal-result-item:nth-child(even) { background:#FDF2F8; }
    .modal-result-item:hover { background:#DBEAFE !important; color:var(--clr-primary-dk); }

    @media (max-width:1024px) { .panel-sidebar { width:195px; } }
    @media (max-width:768px) {
        body, html { overflow:auto; }
        .loading-shell { height:auto; }
        .loading-body  { flex-direction:column; }
        .panel-sidebar { width:100%; }
        .panel-table-scroll { max-height:50vh; }
    }
</style>

<?php $distributors = getDistributors(); ?>

<div class="loading-shell">

    {{-- ══ SEARCH BAR ══ --}}
    <div class="search-bar">
        <i class="fa fa-search" style="color:var(--clr-muted);font-size:13px;"></i>
        <div class="sg">
            <label for="all_barcode">Barcode</label>
            <div class="iw">
                <input type="text" name="all_barcode" id="all_barcode" placeholder="Scan / ketik barcode..." oninput="searchByBarcode()">
                <span class="si"><i class="fa fa-barcode"></i></span>
            </div>
        </div>
        <div class="sdiv"></div>
        <div class="sg">
            <label for="search_good">Keyword</label>
            <div class="iw">
                <input type="text" name="search_good" id="search_good" placeholder="Nama barang...">
                <span class="si" onclick="ajaxFunction()" title="Cari"><i class="fa fa-search"></i></span>
            </div>
        </div>
    </div>

    {{-- ══ BODY ══ --}}
    <div class="loading-body">

        {{-- LEFT: table --}}
        <div class="panel-table">
            <div class="panel-table-header">
                <h6><i class="fa fa-list-ul"></i> Daftar Barang</h6>
                <span class="badge-count" id="badge-item-count">0 item</span>
            </div>
            <div class="panel-table-scroll">
                <table class="tbl-items">
                    <thead>
                        <tr>
                            <th class="col-no">#</th>
                            <th class="col-name">Nama Barang</th>
                            <th class="col-barcode">Barcode</th>
                            <th class="col-exp">Expired</th>
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
                            {{-- NO: merged per group --}}
                            <td class="col-no td-merged" id="td-no-{{ $i }}" rowspan="1"></td>
                            {{-- NAMA: merged per group --}}
                            <td class="col-name td-merged" id="td-name-{{ $i }}" rowspan="1">
                                <div class="name-display" id="name_display-{{ $i }}"></div>
                                {!! Form::text('names[]', null, ['id' => 'name-'.$i, 'style' => 'display:none']) !!}
                                {!! Form::text('name_temps[]', null, ['id' => 'name_temp-'.$i, 'style' => 'display:none']) !!}
                            </td>
                            <td class="col-barcode">
                                <textarea name="barcodes[]" class="fc" id="barcode-{{ $i }}" rows="1"></textarea>
                            </td>
                            <td class="col-exp">
                                <input type="text" class="fc" name="exp_dates[]" id="exp-{{ $i }}" placeholder="yyyy-mm-dd">
                            </td>
                            <td class="col-qty">
                                <input type="text" name="quantities[]" class="fc" id="quantity-{{ $i }}"
                                    onchange="editPrice('{{ $i }}')"
                                    onkeypress="editPrice('{{ $i }}')"
                                    onkeyup="editPrice('{{ $i }}')">
                            </td>
                            <td class="col-unit">
                                @if($SubmitButtonText == 'View')
                                    {!! Form::text('unit', null, ['class' => 'fc', 'readonly' => 'readonly']) !!}
                                @else
                                    {!! Form::select('units[]', getUnits(), null, [
                                        'class'    => 'fc select2',
                                        'required' => 'required',
                                        'style'    => 'width:100%',
                                        'id'       => 'unit-'.$i,
                                        'onchange' => 'changePriceByUnit('.$i.')'
                                    ]) !!}
                                @endif
                            </td>
                            <td class="col-stock">
                                {!! Form::text('old_stocks[]', null, ['class' => 'fc', 'readonly' => 'readonly', 'id' => 'old_stock-'.$i]) !!}
                            </td>
                            <td class="col-stock">
                                {!! Form::text('new_stocks[]', null, ['class' => 'fc', 'readonly' => 'readonly', 'id' => 'new_stock-'.$i]) !!}
                            </td>
                            @if(\Auth::user()->role == 'supervisor')
                            <td class="col-price">
                                <input type="text" name="prices[]" class="fc" id="price-{{ $i }}"
                                    onchange="editBuyPrice('{{ $i }}')" onkeyup="editBuyPrice('{{ $i }}')">
                            </td>
                            <td class="col-price">
                                {!! Form::text('total_prices[]', null, ['class' => 'fc', 'readonly' => 'readonly', 'id' => 'total_price-'.$i]) !!}
                            </td>
                            <td class="col-price">
                                <input type="text" name="sell_prices[]" class="fc" id="sell_price-{{ $i }}">
                            </td>
                            @endif
                            {{-- ACTION: merged per group --}}
                            <td class="col-act td-merged" id="td-act-{{ $i }}" rowspan="1" style="text-align:center;vertical-align:middle;">
                                <button type="button" class="btn-delete" id="delete-{{ $i }}"
                                        onclick="deleteGroup('{{ $i }}')" title="Hapus semua satuan">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- RIGHT: sidebar --}}
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
                        {!! Form::text('distributor', null, ['class' => 'form-control', 'readonly' => 'readonly']) !!}
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
                    {!! Form::select('payment', getLoadingPaymentType(), null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'payment']) !!}
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
                    {!! Form::text('total_item_price', null, ['class' => 'form-control', 'readonly' => 'readonly', 'id' => 'total_item_price', 'style' => 'display:none']) !!}
                </div>
                @endif
                 @if($SubmitButtonText == 'Edit')
                {!! Form::submit('Edit', ['class' => 'btn-submit edit']) !!}
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

{{-- Modal keyword --}}
<div class="modal modal-primary fade" id="modal_search">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title"><i class="fa fa-list"></i> Hasil Pencarian — klik nama untuk pilih</h4>
            </div>
            <div class="modal-body" style="max-height:55vh;overflow-y:auto;padding:8px;">
                <div id="result_good"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

@if(\Auth::user()->role == 'supervisor')
<div class="modal fade" id="modal-good">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title"><i class="fa fa-plus"></i> Barang Baru</h4>
            </div>
            <div class="modal-body">
                <div class="col-sm-12">
                    <div class="form-group">
                        {!! Form::label('category_id', 'Kategori', ['class' => 'col-sm-4 control-label']) !!}
                        <div class="col-sm-8">{!! Form::select('category_id', getCategories(), null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'category_id']) !!}</div>
                    </div>
                    <div class="form-group">
                        {!! Form::label('type_id', 'Jenis Barang', ['class' => 'col-sm-4 control-label']) !!}
                        <div class="col-sm-8">{!! Form::select('type_id', getGoodTypes(), null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'type_id']) !!}</div>
                    </div>
                    <div class="form-group">
                        {!! Form::label('code', 'Barcode', ['class' => 'col-sm-4 control-label']) !!}
                        <div class="col-sm-8">{!! Form::text('code', null, ['class' => 'form-control', 'id' => 'code']) !!}</div>
                    </div>
                    <div class="form-group">
                        {!! Form::label('name', 'Nama Barang', ['class' => 'col-sm-4 control-label']) !!}
                        <div class="col-sm-8">{!! Form::text('name', null, ['class' => 'form-control', 'required' => 'required', 'id' => 'name']) !!}</div>
                    </div>
                    <div class="form-group">
                        {!! Form::label('brand_id', 'Brand / Merek', ['class' => 'col-sm-4 control-label']) !!}
                        <div class="col-sm-8">{!! Form::select('brand_id', getBrands(), null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'brand_id']) !!}</div>
                    </div>
                    <div class="form-group">
                        {!! Form::label('unit_id', 'Satuan', ['class' => 'col-sm-4 control-label']) !!}
                        <div class="col-sm-8">{!! Form::select('unit_id', getUnits(), null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'unit_id']) !!}</div>
                    </div>
                    <div class="form-group">
                        {!! Form::label('price', 'Harga Beli', ['class' => 'col-sm-4 control-label']) !!}
                        <div class="col-sm-8">{!! Form::text('price', null, ['class' => 'form-control', 'required' => 'required', 'id' => 'price', 'onkeyup' => 'formatNumber("price")']) !!}</div>
                    </div>
                    <div class="form-group">
                        {!! Form::label('selling_price', 'Harga Jual', ['class' => 'col-sm-4 control-label']) !!}
                        <div class="col-sm-8">{!! Form::text('selling_price', null, ['class' => 'form-control', 'required' => 'required', 'id' => 'selling_price', 'onkeyup' => 'formatNumber("selling_price")']) !!}</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-success" onclick="event.preventDefault(); addNewGood();">
                    <i class="fa fa-plus"></i> Tambah
                </button>
            </div>
        </div>
    </div>
</div>
@endif

{!! Form::close() !!}

@section('js-addon')
<script>
// ─── State ───────────────────────────────────────────────────────────────────
// next_idx  : always the next fresh row index to be created
// blank_idx : the current empty input row at the TOP of tbody
var next_idx        = 2;    // row-data-1 already exists in HTML (blank)
var blank_idx       = 1;    // tracks which row is the current blank
var total_real_item = 0;    // unique goods
var total_good      = 0;    // display counter (#1, #2 …)
var group_map       = {};   // good_id → { rows:[], name, barcode }
var row_group       = {};   // rowIdx  → good_id
var group_order     = 0;
var group_parity    = {};   // good_id → 'odd'|'even'

// ─── Init ─────────────────────────────────────────────────────────────────────
$(document).ready(function () {
    $('.select2').select2();
    $('#all_barcode').focus();
    $('#loading_date').datepicker({ autoclose:true, format:'yyyy-mm-dd', todayHighlight:true });
    $('#exp-1').datepicker({ autoclose:true, format:'yyyy-mm-dd', todayHighlight:true });
    $('#search_good').keyup(function(e){ if(e.keyCode==13) ajaxFunction(); });
});

// ─── Badge ────────────────────────────────────────────────────────────────────
function updateBadge() {
    document.getElementById('badge-item-count').textContent = total_real_item + ' item';
}

// ─── Rebuild rowspan & visual merge for a group ───────────────────────────────
// NOTE: HTML rowspan only works when sibling <tr> elements follow each other.
// We achieve the "merge" look by HIDING the td-no / td-name / td-act on non-first
// rows and giving the first row's cells a rowspan equal to the group size.
// The first row of a group is always rows[0] (earliest idx = bottom-most in DOM
// since we prepend; but visually it is the top of the group because siblings follow).
function rebuildGroupMerge(good_id) {
    if (!group_map[good_id]) return;
    var g      = group_map[good_id];
    var rows   = g.rows;               // ordered by insertion; rows[0] = top of group
    if (!rows.length) return;

    var firstIdx = rows[0];
    var count    = rows.length;
    var parity   = group_parity[good_id] || 'odd';

    for (var ri = 0; ri < rows.length; ri++) {
        var idx = rows[ri];
        // colour stripe
        $('#row-data-' + idx).removeClass('group-odd group-even').addClass('group-' + parity);

        var $no   = $('#td-no-'   + idx);
        var $name = $('#td-name-' + idx);
        var $act  = $('#td-act-'  + idx);

        if (ri === 0) {
            // First row: show merged cells with correct rowspan
            $no.attr('rowspan',   count).css('display', '');
            $name.attr('rowspan', count).css('display', '');
            $act.attr('rowspan',  count).css('display', '');
        } else {
            // Subsequent rows: hide merged cells (browser uses first row's rowspan)
            $no.hide();
            $name.hide();
            $act.hide();
        }
    }

    // Update name text in first row's merged cell
    var nameEl = document.getElementById('name_display-' + firstIdx);
    if (nameEl) {
        nameEl.innerHTML = escHtml(g.name)
            + (count > 1 ? '<small>' + count + ' satuan</small>' : '');
    }

    // Update no counter
    var noEl = document.getElementById('td-no-' + firstIdx);
    if (noEl) noEl.textContent = total_good + 1;
}

// Simple HTML escape
function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

// ─── Create a NEW numbered data row, append AFTER the blank row ───────────────
// Returns the new row's index.
function _createDataRow() {
    var ni   = next_idx++;
    var html = _buildRowHtml(ni);
    // blank_idx row is always at the TOP (prepended); data rows go AFTER it
    $('#row-data-' + blank_idx).after(html);
    // init plugins on the new row
    $('#unit-' + ni).select2();
    $('#exp-'  + ni).datepicker({ autoclose:true, format:'yyyy-mm-dd', todayHighlight:true });
    return ni;
}

// ─── Write good data into a specific row ──────────────────────────────────────
function _populateRow(rowIdx, good) {
    var gps = good.getPcsSellingPrice;
    var el;

    el = document.getElementById('name-'      + rowIdx); if(el) el.value = good.id;
    el = document.getElementById('name_temp-' + rowIdx); if(el) el.value = good.name;
    el = document.getElementById('barcode-'   + rowIdx); if(el) el.value = good.code;
    el = document.getElementById('old_stock-' + rowIdx); if(el) el.value = good.old_stock;
    el = document.getElementById('new_stock-' + rowIdx); if(el) el.value = parseInt(good.old_stock) + 1;
    el = document.getElementById('base_qty-'  + rowIdx); if(el) el.value = gps.unit_qty;
    el = document.getElementById('quantity-'  + rowIdx); if(el) el.value = 1;
    el = document.getElementById('price-'     + rowIdx); if(el) el.value = parseFloat(gps.base_buy_price) * parseFloat(gps.unit_qty);
    el = document.getElementById('sell_price-'+ rowIdx); if(el) el.value = gps.selling_price;

    var $unit = $('#unit-' + rowIdx);
    if ($unit.length) { $unit.val(gps.unit_id).trigger('change'); }

    editPrice(rowIdx);
}

// ─── Main entry point: called once per satuan ─────────────────────────────────
// idx   : 0-based index within current good's units array
// total : (r.length - 1)  — last index
function fillItem(good, _unused, idx, total) {
    if (!good || good.length === 0) { alert('Barang tidak ditemukan'); return; }

    var good_id = String(good.id);
    var isFirst = (idx === 0);
    var isLast  = (idx === total);

    // ── Init group on first satuan ──
    if (isFirst) {
        // Check: is this good already in the list? If yes, skip re-adding
        if (group_map[good_id]) {
            alert('Barang "' + good.name + '" sudah ada dalam daftar.');
            document.getElementById('all_barcode').value = '';
            return;
        }
        group_parity[good_id] = (group_order % 2 === 0) ? 'odd' : 'even';
        group_order++;
        group_map[good_id] = { rows: [], name: good.name, barcode: good.code };
        total_real_item++;
        updateBadge();
    }

    // ── Get or create the row for this satuan ──
    var rowIdx;
    if (isFirst) {
        // Reuse the existing blank row at the top
        rowIdx = blank_idx;
    } else {
        // Create a new row inserted after the blank
        rowIdx = _createDataRow();
    }

    group_map[good_id].rows.push(rowIdx);
    row_group[rowIdx] = good_id;

    // ── Fill data ──
    _populateRow(rowIdx, good);

    // ── After last satuan: finalize ──
    if (isLast) {
        // Rebuild the merged cells for this group
        rebuildGroupMerge(good_id);
        total_good++;

        // Now create a NEW blank row at the very top
        var newBlank = next_idx++;
        var blankHtml = _buildRowHtml(newBlank);
        $('#table-transaction').prepend(blankHtml);
        $('#unit-' + newBlank).select2();
        $('#exp-'  + newBlank).datepicker({ autoclose:true, format:'yyyy-mm-dd', todayHighlight:true });
        blank_idx = newBlank;

        document.getElementById('all_barcode').value = '';
        if (parseFloat(good.old_stock) < 0) {
            alert('Silahkan lakukan stock opname karena stock barang minus');
        }
    }
}

// ─── Unit options HTML string — built once by PHP ─────────────────────────────
var _unitOptsHtml = '<option value="">Pilih satuan</option>'
@foreach(getUnits() as $uid => $uname)
    + '<option value="{{ $uid }}">{{ addslashes($uname) }}</option>'
@endforeach
;

var _supShow = @if(\Auth::user()->role != 'supervisor') '"style=\'display:none\'"' @else '""' @endif;

// ─── Build row HTML ───────────────────────────────────────────────────────────
function _buildRowHtml(i) {
    var supShow = _supShow;
    var opts    = _unitOptsHtml;

    return '<tr id="row-data-'+i+'" class="group-odd">'
        + '<input type="hidden" name="base_qtys[]" id="base_qty-'+i+'">'
        + '<td class="col-no td-merged" id="td-no-'+i+'" rowspan="1"></td>'
        + '<td class="col-name td-merged" id="td-name-'+i+'" rowspan="1">'
        +   '<div class="name-display" id="name_display-'+i+'"></div>'
        +   '<input type="hidden" name="names[]" id="name-'+i+'">'
        +   '<input type="hidden" name="name_temps[]" id="name_temp-'+i+'">'
        + '</td>'
        + '<td class="col-barcode"><textarea name="barcodes[]" class="fc" id="barcode-'+i+'" rows="1"></textarea></td>'
        + '<td class="col-exp"><input type="text" class="fc" name="exp_dates[]" id="exp-'+i+'" placeholder="yyyy-mm-dd"></td>'
        + '<td class="col-qty"><input type="text" name="quantities[]" class="fc" id="quantity-'+i+'"'
        +   ' onchange="editPrice('+i+')" onkeypress="editPrice('+i+')" onkeyup="editPrice('+i+')"></td>'
        + '<td class="col-unit"><select class="fc select2" name="units[]" id="unit-'+i+'" style="width:100%"'
        +   ' onchange="changePriceByUnit('+i+')">'+opts+'</select></td>'
        + '<td class="col-stock"><input type="text" class="fc" readonly name="old_stocks[]" id="old_stock-'+i+'"></td>'
        + '<td class="col-stock"><input type="text" class="fc" readonly name="new_stocks[]" id="new_stock-'+i+'"></td>'
        + '<td class="col-price" '+supShow+'><input type="text" name="prices[]" class="fc" id="price-'+i+'"'
        +   ' onchange="editBuyPrice('+i+')" onkeyup="editBuyPrice('+i+')"></td>'
        + '<td class="col-price" '+supShow+'><input type="text" class="fc" readonly name="total_prices[]" id="total_price-'+i+'"></td>'
        + '<td class="col-price" '+supShow+'><input type="text" name="sell_prices[]" class="fc" id="sell_price-'+i+'"></td>'
        + '<td class="col-act td-merged" id="td-act-'+i+'" rowspan="1" style="text-align:center;vertical-align:middle;">'
        +   '<button type="button" class="btn-delete" onclick="deleteGroup('+i+')" title="Hapus"><i class="fa fa-trash"></i></button>'
        + '</td>'
        + '</tr>';
}

// ─── Delete entire group ──────────────────────────────────────────────────────
function deleteGroup(anyRowIdx) {
    anyRowIdx = parseInt(anyRowIdx);
    var good_id = row_group[anyRowIdx];
    if (!good_id) return; // blank row, ignore

    var goodName = group_map[good_id] ? group_map[good_id].name : 'barang ini';
    if (!confirm('Hapus semua satuan "' + goodName + '"?')) return;

    var rows = group_map[good_id].rows.slice(); // copy
    for (var i = 0; i < rows.length; i++) {
        $('#row-data-' + rows[i]).remove();
        delete row_group[rows[i]];
    }
    delete group_map[good_id];
    delete group_parity[good_id];

    total_real_item = Math.max(0, total_real_item - 1);
    total_good      = Math.max(0, total_good - 1);
    updateBadge();
    changeTotal();

    // Re-number remaining groups
    var num = 1;
    for (var gid in group_map) {
        var gr = group_map[gid];
        if (!gr.rows.length) continue;
        var noEl = document.getElementById('td-no-' + gr.rows[0]);
        if (noEl) noEl.textContent = num;
        num++;
    }
}

// ─── Barcode search ───────────────────────────────────────────────────────────
function searchByBarcode() {
    var val = $('#all_barcode').val().trim();
    if (!val) return;
    $.ajax({
        url: "{!! url($role . '/good/searchByBarcode/') !!}/" + val,
        success: function(r) { searchItemByName(r.good.id); },
        error:   function()  {}
    });
}

function searchItemByName(id) {
    $.ajax({
        url: "{!! url($role . '/good/searchById/') !!}/" + id,
        success: function(result) {
            var r = result.units;
            for (var i = 0; i < r.length; i++) {
                var gps  = { unit_id: r[i].unit_id, unit_qty: r[i].unit_qty, base_qty: r[i].good_base_qty, base_buy_price: r[i].good_base_buy_price, buy_price: r[i].buy_price, selling_price: r[i].selling_price };
                var good = { id: r[i].good_id, name: r[i].name, code: r[i].code, getPcsSellingPrice: gps, old_stock: r[i].stock };
                fillItem(good, -1, i, r.length - 1);
            }
            $('#modal_search').modal('hide');
            $('#search_good').val('');
            $('#result_good').html('');
        },
        error: function() {}
    });
}

// ─── Price helpers ────────────────────────────────────────────────────────────
function editPrice(index) {
    index = parseInt(index);
    var pEl = document.getElementById('price-'      + index);
    var qEl = document.getElementById('quantity-'   + index);
    var tEl = document.getElementById('total_price-'+ index);
    var oEl = document.getElementById('old_stock-'  + index);
    var nEl = document.getElementById('new_stock-'  + index);
    if (!pEl || !qEl || !tEl) return;
    var price = parseFloat(unFormatNumber(pEl.value || '0')) || 0;
    var qty   = parseFloat(qEl.value || '0') || 0;
    tEl.value = price * qty;
    if (oEl && nEl) nEl.value = (parseInt(oEl.value) || 0) + qty;
    formatNumber('total_price-' + index);
    changeTotal();
}

function editBuyPrice(index) {
    index = parseInt(index);
    var bqty   = parseFloat(document.getElementById('base_qty-' + index).value) || 1;
    var bprice = parseFloat((document.getElementById('price-' + index).value || '0').replace(/,/g,'')) / bqty;
    var good_id = row_group[index];
    if (good_id && group_map[good_id]) {
        var rows = group_map[good_id].rows;
        for (var i = 0; i < rows.length; i++) {
            if (parseInt(rows[i]) !== index) {
                var bq = parseFloat(document.getElementById('base_qty-' + rows[i]).value) || 1;
                var newP = bq * bprice;
                var pEl  = document.getElementById('price-'      + rows[i]);
                var tEl  = document.getElementById('total_price-'+ rows[i]);
                var qEl  = document.getElementById('quantity-'   + rows[i]);
                if (pEl) pEl.value = newP;
                if (tEl && qEl) tEl.value = newP * (parseFloat(qEl.value) || 0);
            }
        }
    }
    editPrice(index);
}

function changeTotal() {
    var total = 0;
    // sum all known data rows (from row_group keys)
    for (var ri in row_group) {
        var el = document.getElementById('total_price-' + ri);
        if (el) total += parseFloat((el.value || '0').replace(/,/g,'')) || 0;
    }
    var el2 = document.getElementById('total_item_price');
    if (el2) { el2.value = total; formatNumber('total_item_price'); }
    var disp = document.getElementById('total_price_display');
    if (disp) disp.textContent = 'Rp ' + total.toString().replace(/(\d)(?=(\d{3})+(?!\d))/g,'$1,');
}

function changePriceByUnit(index) {
    $.ajax({
        url: "{!! url($role . '/good/getPriceUnit/') !!}/" + $('#name-' + index).val() + '/' + $('#unit-' + index).val(),
        success: function(result) {
            var gu = result.good_unit;
            document.getElementById('price-'       + index).value = gu ? gu.buy_price    : '0';
            document.getElementById('sell_price-'  + index).value = gu ? gu.selling_price : '0';
            document.getElementById('total_price-' + index).value =
                (gu ? gu.buy_price : 0) * (document.getElementById('quantity-' + index).value || 0);
            changeTotal();
        }
    });
}

// ─── Keyword search ───────────────────────────────────────────────────────────
function ajaxFunction() {
    $('#modal_search').modal('show');
    $.ajax({
        url: "{!! url($role . '/good/searchByKeyword/') !!}/" + $('#search_good').val(),
        success: function(result) {
            var html = '';
            var r    = result.goods;
            for (var i = 0; i < r.length; i++) {
                html += '<button type="button" class="modal-result-item" onclick="searchItemByName(\'' + r[i].id + '\')">' + r[i].name + '</button>';
            }
            $('#result_good').html(html || '<p style="color:var(--clr-muted);padding:12px;">Tidak ada hasil.</p>');
        },
        error: function() {}
    });
}

// ─── Add new good ─────────────────────────────────────────────────────────────
function addNewGood() {
    var ok = true;
    if (!$('#category_id').val())    { ok=false; alert('Silahkan pilih kategori'); }
    if (!$('#name').val())           { ok=false; alert('Silahkan isi nama'); }
    if (!$('#unit_id').val())        { ok=false; alert('Silahkan pilih satuan'); }
    if (!$('#price').val())          { ok=false; alert('Silahkan isi harga beli'); }
    if (!$('#selling_price').val())  { ok=false; alert('Silahkan isi harga jual'); }
    if (ok && parseInt($('#price').val()) >= parseInt($('#selling_price').val())) {
        alert('Perhatian: Harga beli lebih besar/sama dengan harga jual');
    }
    if (!ok) return;
    $.ajax({
        url: "{!! url($role . '/good/store/') !!}", type:'POST',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        data: {
            role: '{{ $role }}', category_id: $('#category_id').val(), type_id: $('#type_id').val(),
            code: $('#code').val(), name: $('#name').val(), brand_id: $('#brand_id').val(),
            unit_id: $('#unit_id').val(), price: $('#price').val(), selling_price: $('#selling_price').val()
        },
        success: function(result) {
            var g    = result.good;
            var gps  = { unit_id: g.unit_id, unit_qty: 1, base_qty: 1, base_buy_price: parseFloat(g.price), buy_price: parseFloat(g.price), selling_price: parseFloat(g.selling_price) };
            var good = { id: String(g.id), name: g.name, code: g.code, getPcsSellingPrice: gps, old_stock: 0 };
            fillItem(good, -1, 0, 0); // single satuan → idx=0, total=0
            $('#code,#name,#price,#selling_price').val('');
            $('#modal-good').modal('hide');
        },
        error: function() { alert('Harga beli dan harga jual harus berupa angka & tidak boleh kosong'); }
    });
}

// ─── Submit ───────────────────────────────────────────────────────────────────
function submitForm() {
    var ok = true;
    if ($('#distributor_name').val()=='' && $('#all_distributor').val()=='null') { ok=false; alert('Silahkan isi distributor'); }
    if ($('#loading_date').val()=='')  { ok=false; alert('Silahkan isi tanggal pembelian'); }
    if (total_real_item === 0)        { ok=false; alert('Silahkan pilih barang'); }
    if ($('#payment').val()=='0000')  { alert('Silahkan cek kembali jenis pembayaran'); }
    if (ok) document.getElementById('loading-form').submit();
}

// ─── Format ───────────────────────────────────────────────────────────────────
function formatNumber(name) {
    var el = document.getElementById(name);
    if (!el) return;
    var num = el.value.toString().replace(/,/g,'');
    el.value = num.replace(/(\d)(?=(\d{3})+(?!\d))/g,'$1,');
}
function unFormatNumber(num) { return (num||'').toString().replace(/,/g,''); }
</script>
@endsection