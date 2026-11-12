@include('layout.transaction.style')

<?php $canDiscount = isSuperadmin(); ?>

<div class="loading-shell">

    {{-- CARI: barcode + nama barang digabung (sama seperti loading) --}}
    <div class="search-bar" id="sr-wrap">
        <h5 class="page-title">{{ $default['page_name'] }}</h5>
        <span class="mode-switch" title="Ganti Jual / Retur (F6)">
            <button type="button" id="mode-jual" class="on-jual" onclick="setMode('')">Jual</button>
            <button type="button" id="mode-retur" onclick="setMode('retur_s')">Retur</button>
        </span>
        <span class="sdiv"></span>
        <i class="fa fa-search" style="color:var(--clr-muted);font-size:14px;"></i>
        <div class="sg">
            <label for="all_search">Cari Barang</label>
            <div class="iw">
                <input type="text" id="all_search" autocomplete="off" placeholder="Scan barcode lalu Enter, atau ketik nama barang..."
                       oninput="onSearchInput()" onkeydown="onSearchKeydown(event)">
                <span class="si" onclick="focusSearch()" title="Fokus ke pencarian (F2)"><i class="fa fa-barcode"></i></span>
            </div>
        </div>
        <span id="sr-busy"><i class="fa fa-spinner fa-spin"></i> Mencari...</span>
        <span class="kbd-hint"><kbd>F2</kbd> cari &middot; <kbd>F3</kbd> / <kbd>&darr;</kbd> ke tabel &middot; <kbd>F6</kbd> jual/retur &middot; <kbd>F8</kbd> bayar &middot; <kbd>Ctrl</kbd>+<kbd>Enter</kbd> proses</span>

        <div class="sr-panel" id="sr-panel">
            <div class="sr-head">
                <span id="sr-info"></span>
                <span><kbd>&uarr;</kbd><kbd>&darr;</kbd> pilih &middot; <kbd>Insert</kbd> / <kbd>Shift</kbd>+<kbd>&uarr;&darr;</kbd> centang &middot; <kbd>Enter</kbd> tambahkan &middot; <kbd>Esc</kbd> tutup</span>
            </div>
            <div class="sr-list" id="sr-list"></div>
            <div class="sr-foot">
                <button type="button" class="sr-btn" onclick="srCheckAll()">Centang semua</button>
                <button type="button" class="sr-btn primary is-empty" id="sr-add" onclick="srAddChecked()">
                    <i class="fa fa-plus"></i> <span class="sr-lbl">Tambahkan</span><span class="sr-cnt"> (<span id="sr-count">0</span>)</span>
                </button>
            </div>
        </div>
    </div>

    <div class="loading-body">

        {{-- TABEL --}}
        <div class="panel-table">
            <div class="panel-table-header">
                <h6><i class="fa fa-list-ul"></i> Daftar Barang</h6>
                <span class="tbl-hint"><kbd>Enter</kbd> kolom berikut &middot; <kbd>&uarr;</kbd><kbd>&darr;</kbd> baris &middot; <kbd>&larr;</kbd><kbd>&rarr;</kbd> kolom &middot; <kbd>Esc</kbd> cari &middot; <kbd>Alt</kbd>+<kbd>Del</kbd> hapus</span>
                <span class="badge-count" id="badge-item-count">0 item</span>
            </div>
            <div class="panel-table-scroll" id="table-scroll">
                <table class="tbl-items">
                    <thead>
                        <tr>
                            <th class="col-no">#</th>
                            <th class="col-name">Nama Barang</th>
                            <th class="col-num">Harga</th>
                            <th class="col-qty">Jml</th>
                            <th class="col-disc">Potongan</th>
                            <th class="col-sum">Total</th>
                            <th class="col-act"></th>
                        </tr>
                    </thead>
                    <tbody id="table-transaction"></tbody>
                </table>
                <div id="empty-hint"><i class="fa fa-barcode"></i> Belum ada barang. Scan barcode atau ketik nama barang di kolom cari.</div>

                <div id="retur-wrap" style="display:none">
                    <div class="retur-head"><i class="fa fa-undo"></i> RETUR (barang dikembalikan pembeli, mengurangi total)</div>
                    <table class="tbl-items">
                        <thead>
                            <tr>
                                <th class="col-no">#</th>
                                <th class="col-name">Nama Barang</th>
                                <th class="col-cond">Kondisi</th>
                                <th class="col-num">Harga</th>
                                <th class="col-qty">Jml</th>
                                <th class="col-disc">Potongan</th>
                                <th class="col-sum">Total</th>
                                <th class="col-act"></th>
                            </tr>
                        </thead>
                        <tbody id="table-retur"></tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- SIDEBAR PEMBAYARAN --}}
        <div class="panel-sidebar pos">
            <div class="pos-total">
                <div class="lbl">Total Akhir</div>
                <input type="text" name="total_sum_price" id="total_sum_price" readonly tabindex="-1" value="0">
            </div>

            <div class="sidebar-card">
                <div class="pos-sum-line"><span>Total harga</span><span id="show_item_price">0</span></div>
                <div class="pos-sum-line"><span>Potongan per barang</span><span id="show_discount_items">0</span></div>
                <input type="hidden" name="total_item_price" id="total_item_price" value="0">
                <input type="hidden" name="total_discount_items_price" id="total_discount_items_price" value="0">
                <input type="hidden" name="voucher_nominal" value="0">
                {{ Form::hidden('type', 'normal') }}
                <div class="field-row" style="margin-top:6px">
                    <label for="total_discount_price">Potongan harga akhir</label>
                    <input type="text" name="total_discount_price" class="form-control" id="total_discount_price" value="0" autocomplete="off"
                           style="text-align:right" oninput="recalc()" onblur="fmtField('total_discount_price')">
                </div>
            </div>

            <div class="sidebar-card pos-pay">
                <div class="field-row">
                    <label for="money_paid">Bayar <kbd>F8</kbd></label>
                    <input type="text" name="money_paid" class="form-control" id="money_paid" autocomplete="off" inputmode="numeric"
                           oninput="recalc()" onblur="fmtField('money_paid')">
                </div>
                <div class="pay-sugg" id="pay-sugg"></div>
                <div class="field-row">
                    <label>Kembali</label>
                    <div class="pos-change" id="show_change">0</div>
                    <input type="hidden" name="money_returned" id="money_returned" value="0">
                </div>
            </div>

            <div class="sidebar-card" style="flex:1;overflow-y:auto;">
                <div class="field-row">
                    <label>Member</label>
                    <div class="mem-wrap">
                        <input type="text" id="member_q" class="form-control" autocomplete="off" placeholder="Umum. Ketik nama member, Enter" onkeydown="onMemberKey(event)" oninput="onMemberInput()">
                        <input type="hidden" name="member_id" id="member_id" value="1">
                        <div class="mem-list" id="mem-list"></div>
                    </div>
                    <span class="mem-tag" id="mem-tag">Umum</span>
                    <button type="button" class="sr-btn" style="margin-left:4px" onclick="startCamera()">Scan kartu</button>
                    <button type="button" class="sr-btn" onclick="stopCamera()">Stop</button>
                    <video id="preview" style="display:none;width:100%;margin-top:4px"></video>
                </div>
                <div class="field-row">
                    <label>Jenis Pembayaran</label>
                    <select class="form-control" name="payment" id="payment">
                        <option value="cash">Cash/Uang</option>
                        <option value="transfer">Transfer</option>
                    </select>
                    <small style="color:var(--clr-muted);font-size:11px">Hutang: pilih cash, isi uang sesuai yang diterima, dan pilih member.</small>
                </div>
                <div class="field-row">
                    <label><input type="checkbox" name="is_promo" value="1" style="width:auto;height:auto;display:inline-block"> Promo</label>
                </div>
                <div class="field-row">
                    <label>Keterangan</label>
                    <textarea name="note" class="form-control" id="note" placeholder="Opsional..."></textarea>
                </div>
            </div>

            <button type="button" class="btn-submit tambah big" id="btn-process" onclick="event.preventDefault(); submitForm();">
                <i class="fa fa-check-circle"></i> Proses Transaksi
            </button>
            {{ csrf_field() }}
        </div>
    </div>
</div>

<div id="toast-box"></div>

@section('js-addon')
<script>
var CAN_DISCOUNT = @if($canDiscount) true @else false @endif;
var URL_BARCODE  = "{!! url($role.'/good/searchByBarcode/') !!}/";
var URL_KEYWORD  = "{!! url($role.'/good/searchByKeywordGoodUnit/') !!}/";
var URL_MEMBERS  = "{!! url($role.'/member/searchByName/') !!}/";
var URL_MEMBER_CODE = "{!! url($role.'/member/search/') !!}/";

var mode = '';                    /* '' = jual, 'retur_s' = retur */
var counter = { '': 0, 'retur_s': 0 };
var sr = { timer:null, seq:0, items:[], active:-1, open:false, query:'' };

/* ═══════ ANGKA ═══════════════════════════════════════════════════════════ */
function num(v){ var n = parseFloat(String(v == null ? '' : v).replace(/,/g, '')); return isFinite(n) ? n : 0; }
function qtyNum(v){ var n = parseFloat(String(v == null ? '' : v).trim().replace(',', '.')); return isFinite(n) ? n : 0; }
function r2(n){ return Math.round(n * 100) / 100; }
function fmt(n){ return r2(n).toLocaleString('en-US', { maximumFractionDigits:2 }); }
function plain(n){ return String(r2(n)); }
function esc(s){ return String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }
function $id(i){ return document.getElementById(i); }
function fmtField(id){ var el = $id(id); if (el && el.value !== '') { el.value = fmt(num(el.value)); } recalc(); }

function notify(msg, type){
    var $t = $('<div class="toast-item ' + (type || 'info') + '"></div>').text(msg);
    $('#toast-box').append($t);
    setTimeout(function(){ $t.fadeOut(300, function(){ $t.remove(); }); }, 4200);
}

/* ═══════ INIT ════════════════════════════════════════════════════════════ */
$(document).ready(function(){
    $('#all_search').focus();
    $('#transaction-form').on('keydown', function(e){
        /* Enter tidak boleh mengirim form secara tidak sengaja */
        if (e.key === 'Enter' && e.target.tagName === 'INPUT') e.preventDefault();
    });
    recalc();
});

/* ═══════ JUAL / RETUR ════════════════════════════════════════════════════ */
function setMode(k){
    mode = k;
    $('#mode-jual').toggleClass('on-jual', k === '');
    $('#mode-retur').toggleClass('on-retur', k === 'retur_s');
    $('#all_search').attr('placeholder', k === 'retur_s'
        ? 'MODE RETUR: barang yang dicari masuk daftar retur...'
        : 'Scan barcode lalu Enter, atau ketik nama barang...');
    if (k === 'retur_s') $('#retur-wrap').show();
    focusSearch();
}

/* ═══════ BARIS ═══════════════════════════════════════════════════════════ */
function tbodyOf(k){ return $(k === 'retur_s' ? '#table-retur' : '#table-transaction'); }

function rowHtml(k, n, it){
    var s = k + n;
    var cond = k ? '<td class="col-cond"><select class="fc" name="conditionsretur_s[]" id="condition-' + s + '"><option value="rusak">Rusak</option><option value="not">Tidak Rusak</option></select></td>' : '';
    var stockTxt = '';
    if (!k && it.stock !== undefined && it.stock !== null && it.stock !== '') {
        var st = parseFloat(it.stock);
        stockTxt = ' &middot; <span class="' + (st <= 0 ? 'low' : '') + '">stok ' + esc(fmt(st)) + '</span>';
    }
    return '<tr id="row-' + s + '" class="group-odd' + (k ? ' retur-row' : '') + '" data-k="' + k + '" data-n="' + n + '">'
    + '<td class="col-no td-no" id="no-' + s + '"></td>'
    + '<td class="col-name"><div class="cell-txt nm">' + esc(it.name + ' ' + it.unit) + '<small>' + esc(it.code || '') + stockTxt + '</small></div>'
    +   '<input type="hidden" name="barcodes' + k + '[]" id="barcode-' + s + '" value="' + esc(it.good_unit_id) + '">'
    +   '<input type="hidden" name="names' + k + '[]" id="name-' + s + '" value="' + esc(it.good_id) + '">'
    +   '<input type="hidden" name="name_temps' + k + '[]" id="name_temp-' + s + '" value="' + esc(it.name + ' ' + it.unit) + '">'
    +   '<input type="hidden" name="buy_prices' + k + '[]" id="buy_price-' + s + '" value="' + esc(it.buy_price) + '">'
    +   '<input type="hidden" name="total_prices' + k + '[]" id="total_price-' + s + '">'
    + '</td>'
    + cond
    + '<td class="col-num"><input type="text" class="fc num" name="prices' + k + '[]" id="price-' + s + '" value="' + esc(fmt(num(it.price))) + '" readonly tabindex="-1"></td>'
    + '<td class="col-qty"><input type="text" class="fc qty" name="quantities' + k + '[]" id="quantity-' + s + '" value="1" autocomplete="off" inputmode="decimal" oninput="calcRow(\'' + k + '\',' + n + ')"></td>'
    + '<td class="col-disc"><input type="text" class="fc num" name="discounts' + k + '[]" id="discount-' + s + '" value="0" autocomplete="off"' + (CAN_DISCOUNT ? ' oninput="calcRow(\'' + k + '\',' + n + ')"' : ' readonly tabindex="-1"') + '></td>'
    + '<td class="col-sum"><input type="text" class="fc num sum" name="sums' + k + '[]" id="sum-' + s + '" readonly tabindex="-1"></td>'
    + '<td class="col-act"><button type="button" class="btn-delete" tabindex="-1" onclick="deleteRow(\'' + k + '\',' + n + ')" title="Hapus (Alt+Del)"><i class="fa fa-trash"></i></button></td>'
    + '</tr>';
}

function addRow(it){
    var k = mode;
    /* barang yang sama (satuan & harga sama) di tabel yang sama: jumlah ditambah 1 */
    var dup = null;
    tbodyOf(k).find('tr').each(function(){
        var n = $(this).data('n'), s = k + n;
        if ($id('barcode-' + s).value === String(it.good_unit_id) && num($id('price-' + s).value) === num(it.price)) { dup = n; return false; }
    });
    if (dup !== null) {
        var q = $id('quantity-' + k + dup);
        q.value = r2(qtyNum(q.value) + 1);
        calcRow(k, dup);
        flashRow(k, dup);
        notify('"' + it.name + ' ' + it.unit + '" sudah ada, jumlah jadi ' + q.value, 'info');
        return;
    }

    var n = ++counter[k];
    tbodyOf(k).prepend(rowHtml(k, n, it));      /* barang terbaru di paling atas */
    if (k === 'retur_s') $('#retur-wrap').show();
    calcRow(k, n);
    renumber(k);
    flashRow(k, n);
    if (!k && it.stock !== undefined && it.stock !== null && it.stock !== '' && parseFloat(it.stock) <= 0) {
        notify('Stok ' + it.name + ' ' + it.unit + ': ' + fmt(parseFloat(it.stock)) + ' (habis / minus)', 'warning');
    }
}

function renumber(k){
    var $rows = tbodyOf(k).find('tr'), total = $rows.length;
    $rows.each(function(i){
        var no = total - i;
        $('#no-' + k + $(this).data('n')).text(no);
        $(this).removeClass('group-odd group-even').addClass(no % 2 ? 'group-odd' : 'group-even');   /* ganjil putih, genap biru muda */
    });
    updateBadge();
}

function updateBadge(){
    var a = $('#table-transaction tr').length, b = $('#table-retur tr').length;
    $('#badge-item-count').text(a + ' item' + (b ? ' + ' + b + ' retur' : ''));
    $('#empty-hint').toggle(a === 0 && b === 0);
    if (b === 0 && mode !== 'retur_s') $('#retur-wrap').hide();
}

function flashRow(k, n){
    var $tr = $('#row-' + k + n);
    $tr.addClass('flash');
    setTimeout(function(){ $tr.removeClass('flash'); }, 1500);
    if ($tr[0] && $tr[0].scrollIntoView) $tr[0].scrollIntoView({ block:'nearest' });
}

function deleteRow(k, n){
    var $tr = $('#row-' + k + n);
    if (!$tr.length) return;
    var name = $id('name_temp-' + k + n).value;
    if (!confirm('Hapus "' + name + '" dari daftar?')) return;
    var $rows = dataRows(), ri = $rows.index($tr[0]);
    $tr.remove();
    renumber(k);
    recalc();
    var $left = dataRows();
    if ($left.length) focusCtl(ctl($left.eq(Math.min(ri, $left.length - 1)), 'quantity')); else focusSearch();
}

function calcRow(k, n){
    var s = k + n;
    var p = num($id('price-' + s).value), q = qtyNum($id('quantity-' + s).value), d = num($id('discount-' + s).value);
    var tot = r2(p * q);
    $id('total_price-' + s).value = fmt(tot);
    $id('sum-' + s).value = fmt(tot - d);
    $('#quantity-' + s).toggleClass('is-zero', q <= 0);
    recalc();
}

function recalc(){
    var item = 0, sum = 0, disc = 0;
    $('#table-transaction tr, #table-retur tr').each(function(){
        var k = String($(this).data('k')), n = $(this).data('n'), s = k + n;
        var p = num($id('price-' + s).value), q = qtyNum($id('quantity-' + s).value), d = num($id('discount-' + s).value);
        var sg = k ? -1 : 1;
        item += sg * r2(p * q);
        sum  += sg * (r2(p * q) - d);
        disc += d;
    });
    var fd = num($('#total_discount_price').val());
    var total = r2(sum - fd);

    $('#total_item_price').val(fmt(item));
    $('#total_discount_items_price').val(fmt(disc));
    $('#total_sum_price').val(fmt(total));
    $('#show_item_price').text(fmt(item));
    $('#show_discount_items').text(fmt(disc));

    paintSuggestions(total);

    var paidTxt = $.trim($('#money_paid').val());
    var back = paidTxt === '' ? 0 : r2(num(paidTxt) - total);
    $('#money_returned').val(fmt(back));
    $('#show_change').text((back < 0 ? 'Kurang ' : '') + fmt(Math.abs(back))).toggleClass('short', back < 0);
}

/* ═══════ SARAN NILAI BAYAR: uang pas, lalu pembulatan ke atas (2rb, 5rb, 10rb, 20rb, 50rb, 100rb) ═══ */
var pay_options = [];
function paintSuggestions(total){
    var opts = [];
    if (total > 0) {
        opts.push(total);
        [1000, 2000, 5000, 10000, 20000, 50000, 100000].forEach(function(d){
            var v = Math.ceil(total / d) * d;
            if (v > total && opts.indexOf(v) === -1) opts.push(v);
        });
        opts = opts.slice(0, 6);
    }
    pay_options = opts;
    var h = '';
    for (var i = 0; i < opts.length; i++) {
        h += '<button type="button" class="pay-chip' + (i === 0 ? ' exact' : '') + '" data-i="' + i + '" tabindex="-1">'
           + (i === 0 ? 'Uang pas ' : '') + esc(fmt(opts[i])) + '<kbd>Alt+' + (i + 1) + '</kbd></button>';
    }
    $('#pay-sugg').html(h);
}
function pickPay(i){
    if (pay_options[i] === undefined) return;
    $('#money_paid').val(fmt(pay_options[i]));
    recalc();
    $('#money_paid').focus().select();
}
$(document).on('mousedown', '.pay-chip', function(e){ e.preventDefault(); });
$(document).on('click', '.pay-chip', function(){ pickPay(parseInt($(this).data('i'), 10)); });
document.addEventListener('keydown', function(e){
    if (e.altKey && !e.ctrlKey && e.keyCode >= 49 && e.keyCode <= 54) { e.preventDefault(); pickPay(e.keyCode - 49); }
}, true);

/* ═══════ PENCARIAN TERPADU ═══════════════════════════════════════════════
   - Angka saja (>= 6 digit) dianggap barcode: HANYA diproses saat Enter (scanner mengirim Enter),
     cocok -> langsung masuk tabel, fokus tetap di kolom cari agar bisa scan terus.
   - Selain itu keyword: hasil tampil di panel (nama, harga, stok), bisa dicentang beberapa sekaligus.
   ═══════════════════════════════════════════════════════════════════════ */
function focusSearch(){ $('#all_search').focus().select(); }
function isBarcodeLike(v){ return /^\d{6,}$/.test(v); }

function onSearchInput(){
    clearTimeout(sr.timer);
    var v = $.trim($('#all_search').val());
    if (!v) { sr.seq++; abortSearch(); closeResults(); return; }
    if (isBarcodeLike(v)) { closeResults(); return; }          /* tunggu Enter dari scanner */
    if (v.length < MIN_KEYWORD) {                               /* kurang dari 3 huruf: belum cari, batalkan pencarian lama */
        sr.seq++; abortSearch();
        showHint('Ketik minimal ' + MIN_KEYWORD + ' huruf untuk mencari...');
        return;
    }
    sr.timer = setTimeout(function(){ keywordSearch(v, false); }, 350);
}

var MIN_KEYWORD = 3;
function abortSearch(){ if (sr.xhr && sr.xhr.abort) { try { sr.xhr.abort(); } catch (e) {} } sr.xhr = null; }
function showHint(msg){
    sr.open = true; sr.query = ''; sr.items = []; sr.active = -1;
    $('#sr-panel').show();
    $('#sr-info').text('');
    $('#sr-list').html('<div class="sr-state">' + esc(msg) + '</div>');
    updateSrFoot();
}

function handleSearchEnter(){
    clearTimeout(sr.timer);
    var v = $.trim($('#all_search').val());
    if (!v) return;

    if (isBarcodeLike(v)) { barcodeSearch(v); return; }

    if (v.length < MIN_KEYWORD) { showHint('Ketik minimal ' + MIN_KEYWORD + ' huruf untuk mencari...'); return; }

    if (!sr.open || sr.query !== v) { keywordSearch(v, true); return; }

    var ids = checkedItems();
    if (!ids.length) { if (sr.active >= 0 && sr.items[sr.active]) ids = [sr.items[sr.active]]; }
    if (ids.length) addPicked(ids);
}

function barcodeSearch(v){
    $('#all_search').val('');                                 /* siap untuk scan berikutnya */
    closeResults();
    $.ajax({
        url: URL_BARCODE + encodeURIComponent(v),
        success: function(r){
            var g = r && r.good;
            if (g && g.getPcsSellingPrice) {
                var u = g.getPcsSellingPrice;
                addRow({ good_id:g.id, good_unit_id:u.id, name:g.name, unit:u.name, code:g.code, price:u.selling_price, buy_price:u.buy_price, stock:g.stock });
                focusSearch();
            } else {
                notify('Barcode ' + v + ' tidak ditemukan', 'error');
                $('#all_search').val(v);
                keywordSearch(v, false);
            }
        },
        error: function(){ notify('Gagal mengambil data barcode ' + v, 'error'); $('#all_search').val(v).select(); }
    });
}

function keywordSearch(v, viaEnter){
    var seq = ++sr.seq;
    sr.open = true; sr.query = v;
    $('#sr-panel').show();
    $('#sr-info').text('');
    $('#sr-list').html('<div class="sr-state"><i class="fa fa-spinner fa-spin"></i> Mencari...</div>');
    abortSearch();                                              /* permintaan lama yang belum selesai dibatalkan */
    sr.xhr = $.ajax({
        url: URL_KEYWORD + encodeURIComponent(v),
        success: function(res){ if (seq === sr.seq) renderResults(res.good_units || [], v, viaEnter); },
        error: function(xhr, st){ if (st === 'abort') return; if (seq === sr.seq) $('#sr-list').html('<div class="sr-state" style="color:#DC2626">Gagal mengambil data.</div>'); }
    });
}

function renderResults(list, v, viaEnter){
    sr.items = [];
    for (var i = 0; i < list.length; i++) {
        var u = list[i];
        sr.items.push({ good_id:u.good_id, good_unit_id:u.good_unit_id, name:u.name, unit:u.unit, code:u.code || '',
                        price:u.selling_price, buy_price:u.buy_price, stock:u.stock, checked:false });
    }
    if (!sr.items.length) {
        sr.active = -1;
        $('#sr-list').html('<div class="sr-state">Tidak ada hasil untuk "' + esc(v) + '".</div>');
        $('#sr-info').text('0 hasil');
        updateSrFoot();
        return;
    }
    if (viaEnter && sr.items.length === 1) { addPicked([sr.items[0]]); return; }   /* Enter + 1 hasil -> langsung masuk */
    sr.active = 0;
    paintResults();
}

function paintResults(){
    var scroll = $('#sr-list').scrollTop(), h = '';
    for (var i = 0; i < sr.items.length; i++) {
        var it = sr.items[i], st = parseFloat(it.stock);
        var sc = st < 0 ? 'minus' : (st === 0 ? 'zero' : '');
        h += '<div class="sr-item' + (it.checked ? ' checked' : '') + (i === sr.active ? ' active' : '') + '" data-i="' + i + '">'
           +   '<input type="checkbox" tabindex="-1"' + (it.checked ? ' checked' : '') + '>'
           +   '<span class="sr-name">' + esc(it.name + ' ' + it.unit) + '</span>'
           +   (it.code ? '<span class="sr-code">' + esc(it.code) + '</span>' : '')
           +   '<span class="sr-price">' + esc(fmt(num(it.price))) + '</span>'
           +   '<span class="sr-stock ' + sc + '">' + (st < 0 ? 'minus ' : (st === 0 ? 'kosong' : 'stok ')) + (st === 0 ? '' : esc(fmt(st))) + '</span>'
           + '</div>';
    }
    $('#sr-list').html(h).scrollTop(scroll);
    $('#sr-info').text(sr.items.length + ' hasil');
    updateSrFoot();
    var el = $('#sr-list .sr-item.active')[0];
    if (el && el.scrollIntoView) el.scrollIntoView({ block:'nearest' });
}

function checkedItems(){ return $.grep(sr.items, function(it){ return it.checked; }); }
function updateSrFoot(){ var n = checkedItems().length; $('#sr-count').text(n); $('#sr-add').toggleClass('is-empty', n === 0); }

function toggleCheck(i, force){
    var it = sr.items[i]; if (!it) return;
    it.checked = (force === undefined) ? !it.checked : force;
    $('#sr-list .sr-item').eq(i).toggleClass('checked', it.checked).find('input').prop('checked', it.checked);
    updateSrFoot();
}
function setActive(i){
    sr.active = i;
    var $it = $('#sr-list .sr-item').removeClass('active').eq(i).addClass('active');
    if ($it[0] && $it[0].scrollIntoView) $it[0].scrollIntoView({ block:'nearest' });
}
function moveActive(d){ var i = sr.active + d; if (i >= 0 && i < sr.items.length) setActive(i); }
function srCheckAll(){ for (var i = 0; i < sr.items.length; i++) sr.items[i].checked = true; paintResults(); }
function srAddChecked(){
    var ids = checkedItems();
    if (!ids.length && sr.items[sr.active]) ids = [sr.items[sr.active]];
    if (ids.length) addPicked(ids);
}
function closeResults(){ sr.open = false; sr.items = []; sr.active = -1; sr.query = ''; $('#sr-panel').hide(); }

function addPicked(items){
    clearTimeout(sr.timer);
    sr.seq++;
    $('#all_search').val('');
    closeResults();
    for (var i = 0; i < items.length; i++) addRow(items[i]);
    focusSearch();
}

function onSearchKeydown(e){
    var k = e.keyCode;
    if (k === 13) { e.preventDefault(); handleSearchEnter(); return; }
    var val = $.trim($('#all_search').val());

    if (!sr.open) {
        if (k === 40 || k === 38) {
            e.preventDefault();
            if (e.repeat) return;
            if (k === 40) {
                if (val && !isBarcodeLike(val)) { if (val.length >= MIN_KEYWORD) keywordSearch(val, false); else showHint('Ketik minimal ' + MIN_KEYWORD + ' huruf untuk mencari...'); }
                else focusFirstRow();
            }
        } else if (k === 27) { $('#all_search').val(''); }
        return;
    }
    if (k === 40 || k === 38) {
        e.preventDefault();
        if (e.shiftKey) toggleCheck(sr.active, true);
        moveActive(k === 40 ? 1 : -1);
    } else if (k === 45) { e.preventDefault(); toggleCheck(sr.active); }
    else if (k === 27) { e.preventDefault(); closeResults(); }
}

/* ═══════ KEYBOARD DI TABEL (sama seperti loading) ════════════════════════
   Enter / Shift+Enter : kolom berikutnya / sebelumnya (Jumlah -> Potongan -> baris berikutnya -> kolom cari)
   Panah atas/bawah    : baris atas / bawah di kolom yang sama
   Panah kiri/kanan    : kolom sebelah saat isi kolom terblok / kursor di tepi
   Esc                 : kembali ke kolom cari       Alt+Delete : hapus baris ini
   ═══════════════════════════════════════════════════════════════════════ */
var FLOW_TYPES = ['quantity', 'discount'];
var ROW_TYPES  = ['condition', 'quantity', 'discount'];

function dataRows(){ return $('#table-transaction tr, #table-retur tr'); }

function ctl($tr, type){
    var el = $tr.find('[id^="' + type + '-"]')[0];
    if (!el || el.readOnly) return null;
    return el;
}
function focusCtl(el){ if (!el) return false; el.focus(); if (el.scrollIntoView) el.scrollIntoView({ block:'nearest' }); return true; }
function focusFirstRow(){
    var $rows = dataRows();
    if (!$rows.length) { notify('Belum ada barang di tabel', 'info'); return; }
    focusCtl(ctl($rows.eq(0), 'quantity'));
}
function visibleTypes($tr, types){ var out = []; for (var i = 0; i < types.length; i++) { if (ctl($tr, types[i])) out.push(types[i]); } return out; }

function moveFlow(idx, type, dir){
    var $tr = $('#row-' + idx), flow = visibleTypes($tr, FLOW_TYPES);
    var cur = flow.indexOf(type === 'condition' ? 'quantity' : type);
    var $rows = dataRows(), ri = $rows.index($tr);
    if (dir > 0) {
        if (cur + 1 < flow.length) { focusCtl(ctl($tr, flow[cur + 1])); return; }
        if (ri + 1 >= $rows.length) { focusSearch(); return; }
        focusCtl(ctl($rows.eq(ri + 1), 'quantity'));
    } else {
        if (cur - 1 >= 0) { focusCtl(ctl($tr, flow[cur - 1])); return; }
        if (ri - 1 < 0) { focusSearch(); return; }
        var $prev = $rows.eq(ri - 1), pf = visibleTypes($prev, FLOW_TYPES);
        focusCtl(ctl($prev, pf[pf.length - 1] || 'quantity'));
    }
}
function moveVertical(idx, type, d){
    var $rows = dataRows(), ri = $rows.index($('#row-' + idx)) + d;
    if (ri < 0) { focusSearch(); return; }
    if (ri >= $rows.length) return;
    focusCtl(ctl($rows.eq(ri), type));
}
function moveHorizontal(idx, type, d){
    var $tr = $('#row-' + idx), types = visibleTypes($tr, ROW_TYPES), p = types.indexOf(type) + d;
    if (p >= 0 && p < types.length) focusCtl(ctl($tr, types[p]));
}

function handleTableKey(e, t, type, idx){
    var k = e.keyCode, m = /^(retur_s)?(\d+)$/.exec(idx), kind = m[1] || '', n = parseInt(m[2], 10);

    if (k === 27) { e.preventDefault(); focusSearch(); return; }
    if (e.altKey && k === 46) { e.preventDefault(); deleteRow(kind, n); return; }
    if (e.repeat && (k === 13 || (k >= 37 && k <= 40))) { e.preventDefault(); return; }

    if (k === 13) {
        e.preventDefault(); e.stopPropagation();
        if (type === 'quantity' && qtyNum(t.value) <= 0) { notify('Jumlah harus lebih dari 0', 'warning'); return; }
        if (type === 'discount') fmtField(t.id);
        moveFlow(idx, type, e.shiftKey ? -1 : 1);
        return;
    }
    if (t.tagName === 'SELECT') {
        if (k === 39 || k === 37) { e.preventDefault(); moveHorizontal(idx, type, k === 39 ? 1 : -1); }
        return;
    }
    if (k === 40 || k === 38) { e.preventDefault(); moveVertical(idx, type, k === 40 ? 1 : -1); return; }
    if (k === 39 || k === 37) {
        var len = t.value.length, s = t.selectionStart, en = t.selectionEnd, all = (s === 0 && en === len);
        if (k === 39 && (all || (s === len && en === len))) { e.preventDefault(); moveHorizontal(idx, type, 1); }
        if (k === 37 && (all || (s === 0 && en === 0)))     { e.preventDefault(); moveHorizontal(idx, type, -1); }
    }
}

$(document).ready(function(){
    document.addEventListener('keydown', function(e){
        var t = e.target;
        if (!t || !t.closest || !t.closest('#table-transaction, #table-retur')) return;
        if (t.tagName !== 'INPUT' && t.tagName !== 'SELECT') return;
        var m = /^(quantity|discount|condition)-((?:retur_s)?\d+)$/.exec(t.id);
        if (!m) return;
        handleTableKey(e, t, m[1], m[2]);
    }, true);

    /* isi kolom otomatis terblok saat fokus: ketik langsung menimpa (mencegah jumlah tertempel "12" jadi "112") */
    $(document).on('focus', '#table-transaction input.fc, #table-retur input.fc', function(){
        var el = this; el._sel = true; el.select();
        setTimeout(function(){ el.select(); }, 0);
    });
    $(document).on('mouseup', '#table-transaction input.fc, #table-retur input.fc', function(e){
        if (this._sel) { this._sel = false; e.preventDefault(); }
    });
    $(document).on('blur', '#table-transaction input[id^="discount-"], #table-retur input[id^="discount-"]', function(){ if (this.value !== '') this.value = fmt(num(this.value)); });
    $(document).on('blur', '#table-transaction input[id^="quantity-"], #table-retur input[id^="quantity-"]', function(){
        var q = qtyNum(this.value); this.value = q > 0 ? String(r2(q)) : this.value;
    });

    /* klik di luar panel = tutup; klik di panel tidak memindahkan fokus dari kolom cari */
    $(document).on('mousedown', function(e){ if (!$(e.target).closest('#sr-wrap').length) closeResults(); });
    $('#sr-panel').on('mousedown', function(e){ e.preventDefault(); });
    $('#sr-list').on('click', '.sr-item', function(){ var i = parseInt($(this).data('i'), 10); sr.active = i; toggleCheck(i); });
    $('#sr-list').on('dblclick', '.sr-item', function(){ var it = sr.items[parseInt($(this).data('i'), 10)]; if (it) addPicked([it]); });

    /* pembayaran: Enter proses, Esc kembali ke cari */
    $('#money_paid').on('keydown', function(e){
        if (e.keyCode === 13 && e.ctrlKey) { return; }                 /* Ctrl+Enter ditangani shortcut global */
        if (e.keyCode === 13) { e.preventDefault(); fmtField('money_paid'); submitForm(); }
        else if (e.keyCode === 27) { e.preventDefault(); focusSearch(); }
    });
    $('#total_discount_price').on('keydown', function(e){
        if (e.keyCode === 13) { e.preventDefault(); fmtField('total_discount_price'); $('#money_paid').focus(); }
        else if (e.keyCode === 27) { e.preventDefault(); focusSearch(); }
    });

    /* shortcut global */
    document.addEventListener('keydown', function(e){
        var tag = (e.target.tagName || '').toLowerCase();
        var typing = tag === 'input' || tag === 'textarea' || tag === 'select' || e.target.isContentEditable;
        if (e.keyCode === 113 || e.keyCode === 115) { e.preventDefault(); focusSearch(); }          // F2 / F4 -> cari
        else if (e.keyCode === 114) { e.preventDefault(); focusFirstRow(); }                         // F3 -> tabel
        else if (e.keyCode === 117) { e.preventDefault(); setMode(mode === '' ? 'retur_s' : ''); }   // F6 -> jual/retur
        else if (e.keyCode === 119) { e.preventDefault(); $('#money_paid').focus().select(); }       // F8 -> bayar
        else if (e.key === '/' && !typing) { e.preventDefault(); focusSearch(); }
        else if (e.ctrlKey && e.keyCode === 13) { e.preventDefault(); submitForm(); }                // Ctrl+Enter -> proses
    }, true);
});

/* ═══════ MEMBER ══════════════════════════════════════════════════════════ */
var mem = { items:[], active:-1, open:false };
function onMemberInput(){ setMember('1', '', true); }
function setMember(id, name, keepText){
    $('#member_id').val(id);
    $('#mem-tag').text(id === '1' ? 'Umum' : name).toggleClass('picked', id !== '1');
    if (!keepText) $('#member_q').val(id === '1' ? '' : name);
    closeMembers();
}
function closeMembers(){ mem.open = false; mem.items = []; mem.active = -1; $('#mem-list').hide().empty(); }
function paintMembers(){
    var h = '';
    for (var i = 0; i < mem.items.length; i++) {
        var m = mem.items[i];
        h += '<div class="mem-item' + (i === mem.active ? ' active' : '') + '" data-i="' + i + '">' + esc(m.name) + (m.address ? '<small>' + esc(m.address) + '</small>' : '') + '</div>';
    }
    $('#mem-list').html(h || '<div class="mem-item">Member tidak ditemukan</div>').show();
    mem.open = true;
}
function onMemberKey(e){
    var k = e.keyCode;
    if (k === 13) {
        e.preventDefault();
        if (mem.open && mem.items[mem.active]) { var m = mem.items[mem.active]; setMember(String(m.id), m.name); $('#money_paid').focus(); return; }
        var q = $.trim($('#member_q').val());
        if (!q) { setMember('1', ''); return; }
        $.ajax({ url: URL_MEMBERS + encodeURIComponent(q), success: function(r){ mem.items = r.members || []; mem.active = mem.items.length ? 0 : -1; paintMembers(); } });
    } else if (mem.open && (k === 40 || k === 38)) {
        e.preventDefault();
        var i = mem.active + (k === 40 ? 1 : -1);
        if (i >= 0 && i < mem.items.length) { mem.active = i; paintMembers(); }
    } else if (k === 27) { e.preventDefault(); if (mem.open) closeMembers(); else focusSearch(); }
}
$(document).on('mousedown', '.mem-item', function(e){
    e.preventDefault();
    var m = mem.items[parseInt($(this).data('i'), 10)];
    if (m) setMember(String(m.id), m.name);
});

/* scan kartu member (kamera) - script dimuat saat dipakai */
var scanner = null;
function loadScanner(cb){
    if (window.Instascan) { cb(); return; }
    var s = document.createElement('script');
    s.src = 'https://rawgit.com/schmich/instascan-builds/master/instascan.min.js';
    s.onload = cb;
    s.onerror = function(){ notify('Scanner kamera tidak tersedia. Ketik nama member saja.', 'warning'); };
    document.head.appendChild(s);
}
function startCamera(){
    loadScanner(function(){
        try {
            if (!scanner) {
                scanner = new Instascan.Scanner({ video: $id('preview') });
                scanner.addListener('scan', function(content){
                    $.ajax({ url: URL_MEMBER_CODE + encodeURIComponent(content), success: function(r){
                        if (r.member) { setMember(String(r.member.id), r.member.name); } else { notify('Member tidak ditemukan', 'warning'); }
                        stopCamera();
                    } });
                });
            }
            $('#preview').show();
            Instascan.Camera.getCameras().then(function(c){ if (c.length) scanner.start(c[0]); else notify('Kamera tidak ditemukan', 'warning'); });
        } catch (err) { notify('Kamera tidak bisa dijalankan', 'error'); }
    });
}
function stopCamera(){ try { if (scanner) scanner.stop(); } catch (err) {} $('#preview').hide(); }

/* ═══════ PROSES ══════════════════════════════════════════════════════════ */
function submitForm(){
    if (window._submitting) return;
    var $rows = dataRows();
    if (!$('#table-transaction tr').length && !$('#table-retur tr').length) { notify('Pilih minimal satu barang', 'warning'); focusSearch(); return; }

    var bad = null;
    $rows.each(function(){
        var k = String($(this).data('k')), n = $(this).data('n');
        if (qtyNum($id('quantity-' + k + n).value) <= 0) { bad = $id('quantity-' + k + n); return false; }
    });
    if (bad) { notify('Jumlah harus lebih dari 0', 'error'); focusCtl(bad); return; }

    var paidTxt = $.trim($('#money_paid').val());
    if (paidTxt === '') { notify('Isi jumlah uang yang dibayar', 'warning'); $('#money_paid').focus(); return; }
    if ($.trim($('#total_discount_price').val()) === '') $('#total_discount_price').val('0');

    var total = num($('#total_sum_price').val());
    if (num(paidTxt) < total && $('#member_id').val() === '1') {
        notify('Uang kurang dari total belanja. Pilih member (hutang) atau perbaiki jumlah bayar.', 'error');
        $('#member_q').focus();
        return;
    }

    window._submitting = true;
    $('#btn-process').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Memproses...');

    /* semua angka dikirim sebagai angka biasa tanpa pemisah ribuan */
    $('#transaction-form').find('input').filter(function(){
        return /^(prices|quantities|discounts|sums|total_prices|buy_prices)(retur_s)?\[\]$|^(total_item_price|total_discount_items_price|total_discount_price|total_sum_price|money_paid|money_returned)$/.test(this.name || '');
    }).each(function(){
        if (this.value !== '') this.value = /^quantities/.test(this.name) ? plain(qtyNum(this.value)) : plain(num(this.value));
    });
    $id('transaction-form').submit();
}
</script>
@endsection
