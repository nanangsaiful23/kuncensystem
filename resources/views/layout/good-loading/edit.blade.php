{{-- Ubah loading: same layout as the create page (layout/good-loading/style) --}}
<?php
    $details = $good_loading->detailsWithDeleted();
    $isSupervisor = isSupervisor();
    // 12500.00 -> "12.500", 2.50 -> "2,5"
    $num = function ($v) {
        $s = number_format((float) $v, 2, ',', '.');
        return rtrim(rtrim($s, '0'), ',');
    };
    $type = $good_loading->type ?? 'normal';
    $parity = [];
?>
<div class="content-wrapper">
  @include('layout' . '.error')

  <section class="content" style="padding:0">
  {!! Form::model($good_loading, array('url' => route($role . '.good-loading.update', $good_loading->id), 'method' => 'PUT', 'class' => 'form-horizontal', 'id' => 'loading-form', 'onkeydown' => 'return event.key !== "Enter" || event.target.tagName === "TEXTAREA";')) !!}
  @include('layout.good-loading.style')

  <div class="loading-shell">

    <div class="search-bar">
        <h5 class="page-title">{{ $default['page_name'] }}
            <small>ID {{ $good_loading->id }} &middot; {{ optional($good_loading->distributor)->name }} &middot; {{ displayDate($good_loading->loading_date) }}</small>
        </h5>
        @if($type === 'internal')
            <span class="type-badge internal">Loading internal</span>
        @elseif($type === 'transaction-internal')
            <span class="type-badge transaction-internal">Loading &amp; transaksi internal</span>
        @endif
        <span class="kbd-hint">Baris yang diubah ditandai kuning &middot; <kbd>Ctrl</kbd>+<kbd>Enter</kbd> simpan</span>
    </div>

    <div class="loading-body">

        <div class="panel-table">
            <div class="panel-table-header">
                <h6><i class="fa fa-list-ul"></i> Daftar Barang</h6>
                <span class="badge-count">{{ $details->count() }} item &middot; qty {{ $num($details->sum('quantity')) }}</span>
            </div>
            <div class="panel-table-scroll">
                <table class="tbl-items">
                    <thead>
                        <tr>
                            <th class="col-no">#</th>
                            <th class="col-name">Nama Barang</th>
                            <th class="col-barcode">Barcode</th>
                            <th class="col-qty">Jml</th>
                            <th class="col-unit">Satuan</th>
                            <th class="col-price"@if(!$isSupervisor) style="display:none"@endif>Harga Beli</th>
                            <th class="col-price"@if(!$isSupervisor) style="display:none"@endif>Total</th>
                            <th class="col-price"@if(!$isSupervisor) style="display:none"@endif>Harga Jual</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($details as $k => $detail)
                        <?php
                            $i = $k + 1;   // row number, the server reads "change" with it
                            $good = $detail->good_unit->good;
                            $parity[$good->id] = $parity[$good->id] ?? (count($parity) % 2 ? 'even' : 'odd');
                        ?>
                        <tr id="row-data-{{ $i }}" class="group-{{ $parity[$good->id] }}" data-good="{{ $good->id }}">
                            <td class="col-no td-no">
                                {{ $i }}
                                <input type="hidden" name="ids[]" value="{{ $detail->id }}">
                                <input type="hidden" name="base_qtys[]" id="base_qty-{{ $i }}" value="{{ $detail->good_unit->unit->quantity }}">
                                <input type="hidden" name="barcodes[]" value="{{ $good->code }}">
                            </td>
                            <td class="col-name">
                                <input type="text" class="fc" name="name_temps[]" id="name_temp-{{ $i }}" value="{{ $good->getFullName() }}" oninput="markChanged({{ $i }})">
                            </td>
                            <td class="col-barcode"><div class="cell-txt sm">{{ $good->code }}</div></td>
                            <td class="col-qty">
                                <input type="text" class="fc num" name="quantities[]" id="quantity-{{ $i }}" value="{{ $num($detail->quantity) }}" oninput="editPrice({{ $i }})">
                            </td>
                            <td class="col-unit"><div class="cell-txt">{{ $detail->good_unit->unit->name }}</div></td>
                            <td class="col-price"@if(!$isSupervisor) style="display:none"@endif>
                                <input type="text" class="fc num" name="prices[]" id="price-{{ $i }}" value="{{ $num($detail->price) }}" oninput="editBuyPrice({{ $i }})" onchange="tidyNumber(this)">
                            </td>
                            <td class="col-price"@if(!$isSupervisor) style="display:none"@endif>
                                <input type="text" class="fc num" name="total_prices[]" id="total_price-{{ $i }}" value="{{ $num((float) $detail->price * (float) $detail->quantity) }}" readonly>
                            </td>
                            <td class="col-price"@if(!$isSupervisor) style="display:none"@endif>
                                <input type="text" class="fc num" name="sell_prices[]" id="sell_price-{{ $i }}" value="{{ $num($detail->selling_price) }}" oninput="markChanged({{ $i }})" onchange="tidyNumber(this)">
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="panel-sidebar">
            <div class="sidebar-card" style="flex:1;overflow-y:auto;">
                <p class="card-title"><i class="fa fa-file-text-o"></i> Detail Pembelian</p>
                <div class="field-row">
                    <label>Distributor</label>
                    {!! Form::select('distributor_id', getDistributorLists(), $good_loading->distributor_id, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'distributor_id']) !!}
                </div>
                <div class="field-row">
                    <label>Tanggal Pembelian</label>
                    <input type="text" class="form-control" required name="loading_date" id="loading_date" value="{{ $good_loading->loading_date }}" placeholder="yyyy-mm-dd">
                </div>
                <div class="field-row">
                    <label>Jenis Pembayaran</label>
                    @if($good_loading->payment == 'cash')
                        {!! Form::text('payment', null, ['class' => 'form-control', 'readonly' => 'readonly']) !!}
                    @else
                        {!! Form::select('payment', getLoadingPaymentType(), $good_loading->payment, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'payment']) !!}
                    @endif
                </div>
                <div class="field-row">
                    <label>PIC Check Barang</label>
                    {!! Form::text('checker', null, ['class' => 'form-control', 'id' => 'checker']) !!}
                </div>
                <div class="field-row">
                    <label>Catatan</label>
                    {!! Form::textarea('note', null, ['class' => 'form-control', 'id' => 'note']) !!}
                </div>
                <div class="total-price-display"@if(!$isSupervisor) style="display:none"@endif>
                    <div class="lbl">Total Harga</div>
                    <div class="val" id="total_price_display">Rp 0</div>
                </div>
                <input type="hidden" name="total_item_price" id="total_item_price">
                <input type="hidden" name="change" id="change">
                <button type="button" class="btn-submit edit" style="margin-top:8px" onclick="submitForm()">
                    <i class="fa fa-check-circle"></i> Simpan Perubahan
                </button>
            </div>
        </div>
    </div>
  </div>
  {!! Form::close() !!}
  </section>
</div>

@section('js-addon')
@include('layout.good-loading.number-js')
<script>
var TOTAL_ROWS = {{ $details->count() }};
var changed = {};   /* row number -> true; sent as "1;4;7" */

$(document).ready(function () {
    $('.select2').select2();
    $('#loading_date').datepicker({ autoclose: true, format: 'yyyy-mm-dd', todayHighlight: true });
    changeTotal();
    $(document).on('keydown', function (e) {
        if (e.ctrlKey && e.key === 'Enter') { e.preventDefault(); submitForm(); }
    });
});

function markChanged(i) {
    changed[i] = true;
    $('#row-data-' + i).find('.fc:not([readonly])').addClass('changed');
}

function tidyNumber(el) { if (el.value !== '') el.value = numFmt(el.value); }

function editPrice(i) {
    markChanged(i);
    var total = numParse($('#price-' + i).val()) * numParse($('#quantity-' + i).val());
    $('#total_price-' + i).val(numFmt(total));
    changeTotal();
}

/* a new buy price for one unit of a good sets the other units of that good too */
function editBuyPrice(i) {
    var good = $('#row-data-' + i).data('good');
    var basePrice = numParse($('#price-' + i).val()) / (numParse($('#base_qty-' + i).val()) || 1);
    $('tr[data-good="' + good + '"]').each(function () {
        var r = parseInt(this.id.split('-')[2], 10);
        if (r !== i) {
            $('#price-' + r).val(numFmt(basePrice * (numParse($('#base_qty-' + r).val()) || 1)));
            editPrice(r);
        }
    });
    editPrice(i);
}

function changeTotal() {
    var t = 0;
    for (var i = 1; i <= TOTAL_ROWS; i++) t += numParse($('#total_price-' + i).val());
    $('#total_item_price').val(numPlain(t));
    $('#total_price_display').text('Rp ' + numFmt(t));
}

function submitForm() {
    if ($('#loading_date').val() === '') { alert('Isi tanggal pembelian'); return; }
    if ($('#payment').val() === '0000') { alert('Cek kembali jenis pembayaran'); }
    if (window._submitting) return;
    window._submitting = true;
    $('#change').val(Object.keys(changed).join(';'));
    numNormalizeForm(document.getElementById('loading-form'));
    $('.btn-submit.edit').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Menyimpan...');
    document.getElementById('loading-form').submit();
}
</script>
@endsection
