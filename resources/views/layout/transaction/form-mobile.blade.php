{{-- Form transaksi untuk layar HP. Nama field sama persis dengan form desktop (form.blade.php),
     jadi penyimpanan memakai storeTransactionBase yang sama. --}}
<?php $canDiscount = isSuperadmin(); ?>

<style type="text/css">
    .mtx { --ink:#0f172a; --ink2:#475569; --ink3:#94a3b8; --bdr:#e2e8f0; --bg:#f1f5f9;
           --blue:#2563eb; --blue-bg:#eff6ff; --ok:#16a34a; --ok-bg:#f0fdf4;
           --bad:#dc2626; --bad-bg:#fef2f2; --warn:#d97706; --warn-bg:#fffbeb;
           max-width:640px; margin:0 auto; padding:0 0 96px; font-size:16px; color:var(--ink); }
    .mtx, .mtx * { box-sizing:border-box; }
    .mtx i.fa { color:inherit !important; }
    .content-wrapper { background:var(--bg, #f1f5f9) !important; }
    .main-footer { display:none; }

    /* ---- bagian atas: jual/retur, cari, scan ---- */
    .mtx-top { position:sticky; top:0; z-index:900; background:#fff; border-bottom:1px solid var(--bdr); padding:10px 12px; }
    .mtx-mode { display:grid; grid-template-columns:1fr 1fr; border:1.5px solid var(--bdr); border-radius:10px; overflow:hidden; margin-bottom:8px; }
    .mtx-mode button { border:0; background:#fff; min-height:40px; font-size:15px; font-weight:700; color:var(--ink2); }
    .mtx-mode button.on-jual { background:var(--blue); color:#fff; }
    .mtx-mode button.on-retur { background:#f59e0b; color:#fff; }
    .mtx-search { display:flex; gap:8px; }
    .mtx-input { width:100%; min-height:48px; padding:10px 12px; border:1.5px solid var(--bdr); border-radius:10px;
                 font-size:16px; color:var(--ink); background:#fff; -webkit-appearance:none; appearance:none; }
    .mtx-input:focus { outline:none; border-color:var(--blue); }
    select.mtx-input { -webkit-appearance:menulist; appearance:menulist; }
    .mtx-search .mtx-input { flex:1; min-width:0; }
    .mtx-btn { display:inline-flex; align-items:center; justify-content:center; gap:8px; min-height:48px; padding:10px 14px;
               border-radius:10px; border:1.5px solid var(--bdr); background:#fff; color:var(--ink); font-size:16px; font-weight:700; }
    .mtx-btn:disabled { opacity:.55; }
    .mtx-btn.prim { background:var(--blue); border-color:var(--blue); color:#fff; }
    .mtx-btn.ok { background:var(--ok); border-color:var(--ok); color:#fff; }
    .mtx-btn.block { width:100%; }
    .mtx-btn.icon { flex:none; width:52px; padding:0; font-size:20px; }
    .mtx-btn.icon.on { background:var(--bad); border-color:var(--bad); color:#fff; }

    #scan-box { display:none; margin-top:8px; }
    #mtx-reader { width:100%; border-radius:10px; overflow:hidden; background:#000; }
    #mtx-reader video { width:100% !important; max-height:34vh; object-fit:cover; display:block; }
    .mtx-note { font-size:13px; color:var(--ink2); text-align:center; margin:6px 0 0; }

    .mtx-results { display:none; margin-top:8px; border:1px solid var(--bdr); border-radius:10px; overflow-y:auto; max-height:46vh; background:#fff; }
    .mtx-res { display:flex; gap:10px; align-items:center; width:100%; text-align:left; padding:10px 12px; border:0;
               border-top:1px solid var(--bdr); background:#fff; color:var(--ink); }
    .mtx-res:first-child { border-top:0; }
    .mtx-res:active { background:var(--blue-bg); }
    .mtx-res-main { flex:1; min-width:0; }
    .mtx-res-name { font-size:15px; font-weight:600; word-break:break-word; }
    .mtx-res-sub { font-size:12px; color:var(--ink2); }
    .mtx-res-sub .zero { color:var(--ink3); font-weight:700; } .mtx-res-sub .minus { color:var(--bad); font-weight:700; }
    .mtx-res-price { flex:none; font-size:15px; font-weight:700; }
    .mtx-state { padding:14px 12px; text-align:center; color:var(--ink2); font-size:14px; }

    /* ---- daftar barang ---- */
    .mtx-list { padding:10px 12px 0; }
    .mtx-head { display:flex; justify-content:space-between; align-items:center; font-size:13px; font-weight:700;
                color:var(--ink2); text-transform:uppercase; letter-spacing:.04em; margin:4px 2px 8px; }
    .mtx-head.retur { color:#92400e; margin-top:14px; }
    .mtx-empty { text-align:center; color:var(--ink2); padding:36px 12px; font-size:15px; }
    .mtx-empty i { font-size:34px; display:block; margin-bottom:8px; color:var(--ink3) !important; }
    .mtx-item { background:#fff; border:1px solid var(--bdr); border-radius:12px; padding:10px 12px; margin-bottom:8px; }
    .mtx-item.retur { background:#fffbeb; border-color:#fde68a; }
    .mtx-item.flash { border-color:var(--blue); box-shadow:0 0 0 2px #bfdbfe; }
    .mtx-item-top { display:flex; gap:8px; align-items:flex-start; }
    .mtx-item-main { flex:1; min-width:0; }
    .mtx-item-name { font-size:15px; font-weight:700; line-height:1.3; word-break:break-word; }
    .mtx-item-sub { font-size:12px; color:var(--ink2); margin-top:2px; }
    .mtx-item-sub .low { color:var(--bad); font-weight:700; }
    .mtx-del { flex:none; width:40px; height:40px; margin:-6px -8px 0 0; border:0; background:transparent; color:var(--ink3); font-size:18px; }
    .mtx-item-bot { display:flex; gap:8px; align-items:center; margin-top:8px; }
    .mtx-step { display:flex; flex:none; border:1.5px solid var(--bdr); border-radius:10px; overflow:hidden; background:#fff; }
    .mtx-step button { width:42px; height:42px; border:0; background:#f8fafc; font-size:22px; font-weight:700; color:var(--ink); line-height:1; }
    .mtx-step input { width:58px; height:42px; border:0; border-left:1.5px solid var(--bdr); border-right:1.5px solid var(--bdr);
                      text-align:center; font-size:17px; font-weight:700; color:var(--ink); padding:0 2px; border-radius:0; background:#fff; }
    .mtx-step input.is-zero { background:var(--bad-bg); color:var(--bad); }
    .mtx-disc { flex:1; min-width:0; }
    .mtx-disc label { display:block; font-size:10px; font-weight:700; color:var(--ink3); text-transform:uppercase; margin:0; }
    .mtx-disc input { width:100%; height:28px; border:0; border-bottom:1.5px solid var(--bdr); font-size:16px; text-align:right; padding:0 2px; border-radius:0; background:transparent; }
    .mtx-item-sum { margin-left:auto; flex:none; font-size:17px; font-weight:800; text-align:right; }
    .mtx-item.retur .mtx-item-sum { color:#92400e; }
    .mtx-cond { margin-top:8px; }
    .mtx-cond select { min-height:40px; padding:6px 10px; font-size:15px; }

    /* ---- bar bawah ---- */
    .mtx-bar { position:fixed; left:0; right:0; bottom:0; z-index:950; background:#fff; border-top:1px solid var(--bdr);
               box-shadow:0 -4px 14px rgba(15,23,42,.08); padding:8px 12px calc(8px + env(safe-area-inset-bottom)); }
    .mtx-bar-in { max-width:640px; margin:0 auto; display:flex; gap:10px; align-items:center; }
    .mtx-bar-total { flex:1; min-width:0; }
    .mtx-bar-total small { display:block; font-size:12px; color:var(--ink2); font-weight:600; }
    .mtx-bar-total b { display:block; font-size:24px; font-weight:800; color:#1e3a8a; line-height:1.15; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .mtx-bar .mtx-btn { flex:none; min-width:128px; min-height:52px; font-size:18px; }

    /* ---- lembar pembayaran ---- */
    .mtx-sheet { display:none; position:fixed; left:0; top:0; right:0; bottom:0; z-index:2000; background:var(--bg); overflow-y:auto; -webkit-overflow-scrolling:touch; }
    .mtx-sheet.open { display:block; }
    .mtx-sheet-in { max-width:640px; margin:0 auto; padding:0 12px 24px; }
    .mtx-sheet-head { position:sticky; top:0; z-index:5; background:var(--bg); display:flex; align-items:center; gap:8px; padding:8px 0; }
    .mtx-sheet-head h4 { margin:0; font-size:18px; font-weight:700; }
    .mtx-card { background:#fff; border:1px solid var(--bdr); border-radius:12px; padding:12px; margin-bottom:10px; }
    .mtx-label { display:block; font-size:12px; font-weight:700; letter-spacing:.05em; text-transform:uppercase; color:var(--ink3); margin:10px 0 4px; }
    .mtx-label:first-child { margin-top:0; }
    .mtx-total { background:var(--blue-bg); border:2px solid #bfdbfe; border-radius:12px; padding:8px 12px; text-align:right; margin-bottom:10px; }
    .mtx-total small { font-size:12px; font-weight:700; color:var(--ink2); text-transform:uppercase; }
    .mtx-total b { display:block; font-size:34px; font-weight:800; color:#1e3a8a; line-height:1.15; }
    .mtx-line { display:flex; justify-content:space-between; font-size:14px; color:var(--ink2); padding:2px 0; }
    .mtx-money { font-size:26px !important; font-weight:700; text-align:right; min-height:56px !important; }
    .mtx-right { text-align:right; }
    .mtx-chips { display:flex; flex-wrap:wrap; gap:6px; margin-top:8px; }
    .mtx-chip { border:1.5px solid var(--bdr); background:#fff; border-radius:10px; min-height:42px; padding:6px 12px; font-size:15px; font-weight:700; color:var(--ink); }
    .mtx-chip.exact { border-color:var(--ok); color:var(--ok); background:var(--ok-bg); }
    .mtx-change { font-size:26px; font-weight:800; text-align:right; padding:8px 12px; border-radius:10px; background:var(--ok-bg); color:var(--ok); }
    .mtx-change.short { background:var(--bad-bg); color:var(--bad); }
    .mtx-tag { display:inline-block; font-size:13px; font-weight:700; border-radius:20px; padding:2px 12px; background:#e2e8f0; color:#475569; margin-top:6px; }
    .mtx-tag.picked { background:#dcfce7; color:#166534; }
    .mtx-mem-list { display:none; border:1px solid var(--bdr); border-radius:10px; margin-top:6px; overflow:hidden; }
    .mtx-mem-list button { display:block; width:100%; text-align:left; padding:12px; border:0; border-top:1px solid var(--bdr); background:#fff; font-size:15px; color:var(--ink); }
    .mtx-mem-list button:first-child { border-top:0; }
    .mtx-mem-list small { display:block; color:var(--ink2); }
    .mtx-check { display:flex; align-items:center; gap:10px; min-height:44px; font-size:16px; font-weight:600; margin:8px 0 0; }
    .mtx-check input { width:22px; height:22px; margin:0; }

    #toast-box { position:fixed; left:12px; right:12px; top:12px; z-index:3000; display:flex; flex-direction:column; gap:6px; pointer-events:none; }
    .toast-item { border-radius:10px; padding:10px 14px; font-size:15px; font-weight:600; color:#fff; background:#334155; box-shadow:0 6px 18px rgba(15,23,42,.25); }
    .toast-item.error { background:var(--bad, #dc2626); } .toast-item.warning { background:#d97706; } .toast-item.info { background:#2563eb; }
</style>

<div class="mtx">

    {{-- CARI / SCAN --}}
    <div class="mtx-top" id="sr-wrap">
        <div class="mtx-mode">
            <button type="button" id="mode-jual" class="on-jual" onclick="setMode('')">Jual</button>
            <button type="button" id="mode-retur" onclick="setMode('retur_s')">Retur</button>
        </div>
        <div class="mtx-search">
            <input type="text" class="mtx-input" id="all_search" autocomplete="off" enterkeyhint="search"
                   placeholder="Ketik nama barang / barcode" oninput="onSearchInput()">
            <button type="button" class="mtx-btn icon" id="btn-scan" title="Scan barcode dengan kamera"><i class="fa fa-barcode"></i></button>
        </div>

        <div id="scan-box">
            <select class="mtx-input" id="camera-select" style="display:none;margin-bottom:8px"></select>
            <div id="mtx-reader"></div>
            <p class="mtx-note">Arahkan kamera ke barcode. Kamera tetap menyala untuk scan barang berikutnya.</p>
        </div>

        <div class="mtx-results" id="sr-list"></div>
    </div>

    {{-- DAFTAR BARANG --}}
    <div class="mtx-list">
        <div class="mtx-head"><span><i class="fa fa-list-ul"></i> Daftar Barang</span><span id="badge-item-count">0 item</span></div>
        <div id="empty-hint" class="mtx-empty"><i class="fa fa-barcode"></i>Belum ada barang.<br>Scan barcode atau ketik nama barang di atas.</div>
        <div id="table-transaction"></div>

        <div id="retur-wrap" style="display:none">
            <div class="mtx-head retur"><span><i class="fa fa-undo"></i> Retur (mengurangi total)</span></div>
            <div id="table-retur"></div>
        </div>
    </div>

    {{-- BAR BAWAH --}}
    <div class="mtx-bar">
        <div class="mtx-bar-in">
            <div class="mtx-bar-total">
                <small id="bar-count">0 item</small>
                <b id="bar-total">0</b>
            </div>
            <button type="button" class="mtx-btn prim" onclick="openPay()">Bayar <i class="fa fa-chevron-right"></i></button>
        </div>
    </div>

    {{-- PEMBAYARAN --}}
    <div class="mtx-sheet" id="pay-sheet">
        <div class="mtx-sheet-in">
            <div class="mtx-sheet-head">
                <button type="button" class="mtx-btn" onclick="closePay()"><i class="fa fa-chevron-left"></i> Barang</button>
                <h4>Pembayaran</h4>
            </div>

            <div class="mtx-total">
                <small>Total Akhir</small>
                <b id="show_total">0</b>
                <input type="hidden" name="total_sum_price" id="total_sum_price" value="0">
            </div>

            <div class="mtx-card">
                <div class="mtx-line"><span>Total harga</span><span id="show_item_price">0</span></div>
                <div class="mtx-line"><span>Potongan per barang</span><span id="show_discount_items">0</span></div>
                <input type="hidden" name="total_item_price" id="total_item_price" value="0">
                <input type="hidden" name="total_discount_items_price" id="total_discount_items_price" value="0">
                <input type="hidden" name="voucher_nominal" value="0">
                {{ Form::hidden('type', 'normal') }}
                <label class="mtx-label" for="total_discount_price">Potongan harga akhir</label>
                <input type="text" name="total_discount_price" class="mtx-input mtx-right" id="total_discount_price" value="0" autocomplete="off"
                       inputmode="numeric" oninput="recalc()" onfocus="this.select()" onblur="fmtField('total_discount_price')">
            </div>

            <div class="mtx-card">
                <label class="mtx-label" for="money_paid">Bayar</label>
                <input type="text" name="money_paid" class="mtx-input mtx-money" id="money_paid" autocomplete="off" inputmode="numeric"
                       placeholder="0" oninput="recalc()" onfocus="this.select()" onblur="fmtField('money_paid')">
                <div class="mtx-chips" id="pay-sugg"></div>
                <label class="mtx-label">Kembali</label>
                <div class="mtx-change" id="show_change">0</div>
                <input type="hidden" name="money_returned" id="money_returned" value="0">
            </div>

            <div class="mtx-card">
                <label class="mtx-label" for="member_q">Member</label>
                <div class="mtx-search">
                    <input type="text" id="member_q" class="mtx-input" autocomplete="off" enterkeyhint="search" placeholder="Umum. Ketik nama member" oninput="onMemberInput()">
                    <button type="button" class="mtx-btn" onclick="searchMember()">Cari</button>
                </div>
                <input type="hidden" name="member_id" id="member_id" value="1">
                <div class="mtx-mem-list" id="mem-list"></div>
                <span class="mtx-tag" id="mem-tag">Umum</span>

                <label class="mtx-label" for="payment">Jenis Pembayaran</label>
                <select class="mtx-input" name="payment" id="payment">
                    <option value="cash">Cash/Uang</option>
                    <option value="transfer">Transfer</option>
                </select>
                <p class="mtx-note" style="text-align:left">Hutang: pilih cash, isi uang sesuai yang diterima, dan pilih member.</p>

                <label class="mtx-check"><input type="checkbox" name="is_promo" value="1"> Promo</label>

                <label class="mtx-label" for="note">Keterangan</label>
                <textarea name="note" class="mtx-input" id="note" rows="2" placeholder="Opsional..."></textarea>
            </div>

            <button type="button" class="mtx-btn ok block" id="btn-process" style="min-height:58px;font-size:19px" onclick="submitForm()">
                <i class="fa fa-check-circle"></i> Proses Transaksi
            </button>
            {{ csrf_field() }}
        </div>
    </div>
</div>

<div id="toast-box"></div>

@section('js-addon')
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
var CAN_DISCOUNT = @if($canDiscount) true @else false @endif;
var URL_BARCODE  = "{!! url($role.'/good/searchByBarcode/') !!}/";
var URL_KEYWORD  = "{!! url($role.'/good/searchByKeywordGoodUnit/') !!}/";
var URL_MEMBERS  = "{!! url($role.'/member/searchByName/') !!}/";

var mode = '';                    /* '' = jual, 'retur_s' = retur */
var counter = { '': 0, 'retur_s': 0 };
var sr = { timer:null, seq:0, items:[], xhr:null };
var MIN_KEYWORD = 3;

/* ═══════ ANGKA (sama dengan form desktop) ═══════ */
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
    setTimeout(function(){ $t.fadeOut(300, function(){ $t.remove(); }); }, 3500);
}

/* ═══════ JUAL / RETUR ═══════ */
function setMode(k){
    mode = k;
    $('#mode-jual').toggleClass('on-jual', k === '');
    $('#mode-retur').toggleClass('on-retur', k === 'retur_s');
    $('#all_search').attr('placeholder', k === 'retur_s' ? 'MODE RETUR: cari barang yang diretur' : 'Ketik nama barang / barcode');
    updateBadge();
}

/* ═══════ BARIS ═══════ */
function listOf(k){ return $(k === 'retur_s' ? '#table-retur' : '#table-transaction'); }
function dataRows(){ return $('#table-transaction .mtx-item, #table-retur .mtx-item'); }

function rowHtml(k, n, it){
    var s = k + n, a = "'" + k + "'," + n;
    var stockTxt = '';
    if (!k && it.stock !== undefined && it.stock !== null && it.stock !== '') {
        var st = parseFloat(it.stock);
        stockTxt = ' &middot; <span class="' + (st <= 0 ? 'low' : '') + '">stok ' + esc(fmt(st)) + '</span>';
    }
    var cond = k ? '<div class="mtx-cond"><select class="mtx-input" name="conditionsretur_s[]" id="condition-' + s + '"><option value="rusak">Rusak</option><option value="not">Tidak Rusak</option></select></div>' : '';
    var disc = CAN_DISCOUNT
        ? '<div class="mtx-disc"><label for="discount-' + s + '">Potongan</label><input type="text" name="discounts' + k + '[]" id="discount-' + s + '" value="0" autocomplete="off" inputmode="numeric" onfocus="this.select()" oninput="calcRow(' + a + ')" onblur="if(this.value!==\'\')this.value=fmt(num(this.value))"></div>'
        : '<input type="hidden" name="discounts' + k + '[]" id="discount-' + s + '" value="0">';

    return '<div class="mtx-item' + (k ? ' retur' : '') + '" id="row-' + s + '" data-k="' + k + '" data-n="' + n + '">'
    + '<div class="mtx-item-top">'
    +   '<div class="mtx-item-main"><div class="mtx-item-name">' + esc(it.name + ' ' + it.unit) + '</div>'
    +   '<div class="mtx-item-sub">@ ' + esc(fmt(num(it.price))) + (it.code ? ' &middot; ' + esc(it.code) : '') + stockTxt + '</div></div>'
    +   '<button type="button" class="mtx-del" onclick="deleteRow(' + a + ')" title="Hapus"><i class="fa fa-trash"></i></button>'
    + '</div>'
    + cond
    + '<div class="mtx-item-bot">'
    +   '<div class="mtx-step">'
    +     '<button type="button" onclick="stepQty(' + a + ',-1)">&minus;</button>'
    +     '<input type="text" name="quantities' + k + '[]" id="quantity-' + s + '" value="1" autocomplete="off" inputmode="decimal" onfocus="this.select()" oninput="calcRow(' + a + ')">'
    +     '<button type="button" onclick="stepQty(' + a + ',1)">+</button>'
    +   '</div>'
    +   disc
    +   '<div class="mtx-item-sum" id="sumtxt-' + s + '"></div>'
    + '</div>'
    + '<input type="hidden" name="barcodes' + k + '[]" id="barcode-' + s + '" value="' + esc(it.good_unit_id) + '">'
    + '<input type="hidden" name="names' + k + '[]" id="name-' + s + '" value="' + esc(it.good_id) + '">'
    + '<input type="hidden" name="name_temps' + k + '[]" id="name_temp-' + s + '" value="' + esc(it.name + ' ' + it.unit) + '">'
    + '<input type="hidden" name="buy_prices' + k + '[]" id="buy_price-' + s + '" value="' + esc(it.buy_price) + '">'
    + '<input type="hidden" name="prices' + k + '[]" id="price-' + s + '" value="' + esc(fmt(num(it.price))) + '">'
    + '<input type="hidden" name="total_prices' + k + '[]" id="total_price-' + s + '">'
    + '<input type="hidden" name="sums' + k + '[]" id="sum-' + s + '">'
    + '</div>';
}

function addRow(it){
    var k = mode;
    /* barang yang sama (satuan & harga sama) di daftar yang sama: jumlah ditambah 1 */
    var dup = null;
    listOf(k).find('.mtx-item').each(function(){
        var n = $(this).data('n'), s = k + n;
        if ($id('barcode-' + s).value === String(it.good_unit_id) && num($id('price-' + s).value) === num(it.price)) { dup = n; return false; }
    });
    if (dup !== null) {
        var q = $id('quantity-' + k + dup);
        q.value = r2(qtyNum(q.value) + 1);
        calcRow(k, dup);
        flashRow(k, dup);
        notify(it.name + ' ' + it.unit + ': jumlah jadi ' + q.value, 'info');
        return;
    }

    var n = ++counter[k];
    listOf(k).prepend(rowHtml(k, n, it));       /* barang terbaru di paling atas */
    calcRow(k, n);
    updateBadge();
    flashRow(k, n);
    if (!k && it.stock !== undefined && it.stock !== null && it.stock !== '' && parseFloat(it.stock) <= 0) {
        notify('Stok ' + it.name + ' ' + it.unit + ': ' + fmt(parseFloat(it.stock)) + ' (habis / minus)', 'warning');
    }
}

function updateBadge(){
    var a = $('#table-transaction .mtx-item').length, b = $('#table-retur .mtx-item').length;
    var txt = a + ' item' + (b ? ' + ' + b + ' retur' : '');
    $('#badge-item-count, #bar-count').text(txt);
    $('#empty-hint').toggle(a === 0 && b === 0);
    $('#retur-wrap').toggle(b > 0);
}

function flashRow(k, n){
    var $r = $('#row-' + k + n);
    $r.addClass('flash');
    setTimeout(function(){ $r.removeClass('flash'); }, 1500);
}

function deleteRow(k, n){
    var $r = $('#row-' + k + n);
    if (!$r.length) return false;
    if (!confirm('Hapus "' + $id('name_temp-' + k + n).value + '" dari daftar?')) return false;
    $r.remove();
    updateBadge();
    recalc();
    return true;
}

function stepQty(k, n, d){
    var el = $id('quantity-' + k + n), q = r2(qtyNum(el.value) + d);
    if (q <= 0) { deleteRow(k, n); return; }    /* kurang dari 1 = tanya hapus */
    el.value = q;
    calcRow(k, n);
}

function calcRow(k, n){
    var s = k + n;
    var p = num($id('price-' + s).value), q = qtyNum($id('quantity-' + s).value), d = num($id('discount-' + s).value);
    var tot = r2(p * q);
    $id('total_price-' + s).value = fmt(tot);
    $id('sum-' + s).value = fmt(tot - d);
    $id('sumtxt-' + s).textContent = (k ? '- ' : '') + fmt(tot - d);
    $('#quantity-' + s).toggleClass('is-zero', q <= 0);
    recalc();
}

function recalc(){
    var item = 0, sum = 0, disc = 0;
    dataRows().each(function(){
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
    $('#show_total, #bar-total').text(fmt(total));

    paintSuggestions(total);

    var paidTxt = $.trim($('#money_paid').val());
    var back = paidTxt === '' ? 0 : r2(num(paidTxt) - total);
    $('#money_returned').val(fmt(back));
    $('#show_change').text((back < 0 ? 'Kurang ' : '') + fmt(Math.abs(back))).toggleClass('short', back < 0);
}

/* ═══════ SARAN NILAI BAYAR: uang pas, lalu pembulatan ke atas ═══════ */
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
    if (opts.join('|') === pay_options.join('|')) return;      /* tidak berubah: jangan gambar ulang saat mengetik */
    pay_options = opts;
    var h = '';
    for (var i = 0; i < opts.length; i++) {
        h += '<button type="button" class="mtx-chip' + (i === 0 ? ' exact' : '') + '" data-i="' + i + '">' + (i === 0 ? 'Uang pas ' : '') + esc(fmt(opts[i])) + '</button>';
    }
    $('#pay-sugg').html(h);
}
$(document).on('click', '.mtx-chip', function(){
    var v = pay_options[parseInt($(this).data('i'), 10)];
    if (v === undefined) return;
    $('#money_paid').val(fmt(v));
    recalc();
});

/* ═══════ PENCARIAN ═══════
   Angka saja (>= 6 digit) dianggap barcode: diproses saat Enter / tombol cari di keyboard.
   Selain itu keyword: hasil tampil di bawah kolom cari, ketuk untuk menambahkan. */
function isBarcodeLike(v){ return /^\d{6,}$/.test(v); }
function abortSearch(){ if (sr.xhr && sr.xhr.abort) { try { sr.xhr.abort(); } catch (e) {} } sr.xhr = null; }
function closeResults(){ sr.items = []; $('#sr-list').hide().empty(); }
function showState(html){ $('#sr-list').html('<div class="mtx-state">' + html + '</div>').show(); }

function onSearchInput(){
    clearTimeout(sr.timer);
    var v = $.trim($('#all_search').val());
    if (!v) { sr.seq++; abortSearch(); closeResults(); return; }
    if (isBarcodeLike(v)) { showState('Barcode: tekan Enter / Cari di keyboard'); return; }
    if (v.length < MIN_KEYWORD) { sr.seq++; abortSearch(); showState('Ketik minimal ' + MIN_KEYWORD + ' huruf...'); return; }
    sr.timer = setTimeout(function(){ keywordSearch(v); }, 400);
}

function handleSearchEnter(){
    clearTimeout(sr.timer);
    var v = $.trim($('#all_search').val());
    if (!v) return;
    if (isBarcodeLike(v)) { barcodeSearch(v, true); return; }
    if (v.length < MIN_KEYWORD) { showState('Ketik minimal ' + MIN_KEYWORD + ' huruf...'); return; }
    keywordSearch(v);
}

function barcodeSearch(v, typed){
    if (typed) $('#all_search').val('');
    closeResults();
    $.ajax({
        url: URL_BARCODE + encodeURIComponent(v),
        success: function(r){
            var g = r && r.good;
            if (g && g.getPcsSellingPrice) {
                var u = g.getPcsSellingPrice;
                addRow({ good_id:g.id, good_unit_id:u.id, name:g.name, unit:u.name, code:g.code, price:u.selling_price, buy_price:u.buy_price, stock:g.stock });
            } else {
                notify('Barcode ' + v + ' tidak ditemukan', 'error');
            }
        },
        error: function(){ notify('Gagal mengambil data barcode ' + v, 'error'); }
    });
}

function keywordSearch(v){
    var seq = ++sr.seq;
    showState('<i class="fa fa-spinner fa-spin"></i> Mencari...');
    abortSearch();
    sr.xhr = $.ajax({
        url: URL_KEYWORD + encodeURIComponent(v),
        success: function(res){ if (seq === sr.seq) renderResults(res.good_units || [], v); },
        error: function(xhr, st){ if (st === 'abort') return; if (seq === sr.seq) showState('<span style="color:#dc2626">Gagal mengambil data.</span>'); }
    });
}

function renderResults(list, v){
    sr.items = [];
    var h = '';
    for (var i = 0; i < list.length; i++) {
        var u = list[i], st = parseFloat(u.stock);
        sr.items.push({ good_id:u.good_id, good_unit_id:u.good_unit_id, name:u.name, unit:u.unit, code:u.code || '', price:u.selling_price, buy_price:u.buy_price, stock:u.stock });
        var stock = st < 0 ? '<span class="minus">minus ' + esc(fmt(st)) + '</span>' : (st === 0 ? '<span class="zero">kosong</span>' : 'stok ' + esc(fmt(st)));
        h += '<button type="button" class="mtx-res" data-i="' + i + '">'
           +   '<span class="mtx-res-main"><span class="mtx-res-name">' + esc(u.name + ' ' + u.unit) + '</span>'
           +   '<br><span class="mtx-res-sub">' + (u.code ? esc(u.code) + ' &middot; ' : '') + stock + '</span></span>'
           +   '<span class="mtx-res-price">' + esc(fmt(num(u.selling_price))) + '</span>'
           + '</button>';
    }
    if (!sr.items.length) { showState('Tidak ada hasil untuk "' + esc(v) + '".'); return; }
    $('#sr-list').html(h).scrollTop(0).show();
}

$(document).ready(function(){
    $('#transaction-form').on('keydown', function(e){
        /* Enter tidak boleh mengirim form secara tidak sengaja */
        if (e.key === 'Enter' && e.target.tagName === 'INPUT') e.preventDefault();
    });
    $('#all_search').on('keydown', function(e){ if (e.keyCode === 13) { e.preventDefault(); handleSearchEnter(); } });
    $('#member_q').on('keydown', function(e){ if (e.keyCode === 13) { e.preventDefault(); searchMember(); } });
    $('#money_paid, #total_discount_price').on('keydown', function(e){ if (e.keyCode === 13) { e.preventDefault(); this.blur(); } });

    /* ketuk hasil = langsung masuk daftar */
    $('#sr-list').on('click', '.mtx-res', function(){
        var it = sr.items[parseInt($(this).data('i'), 10)];
        if (!it) return;
        clearTimeout(sr.timer); sr.seq++;
        $('#all_search').val('').blur();
        closeResults();
        addRow(it);
    });
    $(document).on('click', function(e){ if (!$(e.target).closest('#sr-wrap').length) closeResults(); });

    $(document).on('blur', '.mtx-step input', function(){ var q = qtyNum(this.value); if (q > 0) this.value = String(r2(q)); });

    updateBadge();
    recalc();
});

/* ═══════ LEMBAR PEMBAYARAN ═══════ */
function openPay(){
    if (!dataRows().length) { notify('Pilih minimal satu barang', 'warning'); return; }
    stopScan();
    closeResults();
    recalc();
    $('#pay-sheet').addClass('open').scrollTop(0);
    $('body').css('overflow', 'hidden');
}
function closePay(){
    $('#pay-sheet').removeClass('open');
    $('body').css('overflow', '');
}

/* ═══════ MEMBER ═══════ */
var mem = { items:[] };
function setMember(id, name, keepText){
    $('#member_id').val(id);
    $('#mem-tag').text(id === '1' ? 'Umum' : name).toggleClass('picked', id !== '1');
    if (!keepText) $('#member_q').val(id === '1' ? '' : name);
    $('#mem-list').hide().empty();
}
function onMemberInput(){ setMember('1', '', true); }      /* teks diubah = kembali ke Umum sampai member dipilih lagi */
function searchMember(){
    var q = $.trim($('#member_q').val());
    if (!q) { setMember('1', ''); return; }
    $('#mem-list').html('<div class="mtx-state"><i class="fa fa-spinner fa-spin"></i> Mencari...</div>').show();
    $.ajax({
        url: URL_MEMBERS + encodeURIComponent(q),
        success: function(r){
            mem.items = r.members || [];
            var h = '';
            for (var i = 0; i < mem.items.length; i++) {
                var m = mem.items[i];
                h += '<button type="button" data-i="' + i + '">' + esc(m.name) + (m.address ? '<small>' + esc(m.address) + '</small>' : '') + '</button>';
            }
            $('#mem-list').html(h || '<div class="mtx-state">Member tidak ditemukan</div>').show();
        },
        error: function(){ $('#mem-list').html('<div class="mtx-state" style="color:#dc2626">Gagal mengambil data.</div>'); }
    });
}
$(document).on('click', '#mem-list button', function(){
    var m = mem.items[parseInt($(this).data('i'), 10)];
    if (m) setMember(String(m.id), m.name);
});

/* ═══════ SCAN BARCODE DENGAN KAMERA HP (html5-qrcode, sama dengan Sidak Stok) ═══════
   Kamera tetap menyala setelah satu barang terbaca, supaya bisa scan terus. */
var scanner = null, scanning = false, cameras = null;
var lastScan = { code:'', at:0 };
var CAM_KEY = 'transaksi-camera';

function savedCamera(){ try { return localStorage.getItem(CAM_KEY); } catch (e) { return null; } }
function saveCamera(id){ try { localStorage.setItem(CAM_KEY, id); } catch (e) {} }
function isBack(label){ return /back|rear|belakang|environment/i.test(label || ''); }

function pickCamera(list){
    var saved = savedCamera();
    for (var i = 0; i < list.length; i++) { if (list[i].id === saved) return saved; }
    var back = list.filter(function(c){ return isBack(c.label); });
    var main = back.filter(function(c){ return /\b0\b/.test(c.label); })[0];   /* lensa utama biasanya "camera 0" */
    if (main) return main.id;
    if (back.length) return back[0].id;
    return list.length ? list[list.length - 1].id : null;
}

function fillCameraSelect(selected){
    var sel = $id('camera-select');
    sel.innerHTML = cameras.map(function(c, i){
        return '<option value="' + esc(c.id) + '">' + esc(c.label || ('Kamera ' + (i + 1))) + (isBack(c.label) ? ' (belakang)' : '') + '</option>';
    }).join('');
    sel.value = selected;
    sel.style.display = cameras.length > 1 ? 'block' : 'none';
}

function scanUi(on){
    $('#scan-box').toggle(on);
    $('#btn-scan').toggleClass('on', on).html(on ? '<i class="fa fa-times"></i>' : '<i class="fa fa-barcode"></i>');
}

function stopScan(){
    scanUi(false);
    if (scanner && scanning) { scanning = false; return scanner.stop().catch(function(){}); }
    return Promise.resolve();
}

function cameraError(err){
    scanUi(false);
    var denied = String(err).indexOf('NotAllowed') !== -1 || String(err).indexOf('Permission') !== -1;
    notify(denied ? 'Izin kamera ditolak. Izinkan kamera untuk situs ini, atau ketik barcode.' : 'Kamera tidak bisa dibuka. Ketik barcode secara manual.', 'error');
}

function onScan(code){
    if (!scanning) return;
    var now = Date.now();
    if (code === lastScan.code && now - lastScan.at < 2500) return;   /* barcode yang sama masih di depan kamera */
    lastScan = { code:code, at:now };
    if (navigator.vibrate) navigator.vibrate(80);
    barcodeSearch(code, false);
}

function runCamera(cameraId){
    if (!scanner) {
        var F = Html5QrcodeSupportedFormats;
        scanner = new Html5Qrcode('mtx-reader', {
            formatsToSupport: [F.EAN_13, F.EAN_8, F.UPC_A, F.UPC_E, F.CODE_128, F.CODE_39, F.CODE_93, F.ITF, F.CODABAR, F.QR_CODE],
            experimentalFeatures: { useBarCodeDetectorIfSupported: true },
            verbose: false
        });
    }
    var video = { width:{ ideal:1920 }, height:{ ideal:1080 } };
    if (cameraId) video.deviceId = { exact: cameraId }; else video.facingMode = { ideal:'environment' };

    return scanner.start(
        cameraId ? cameraId : { facingMode:'environment' },
        { fps:15, videoConstraints: video },
        onScan,
        function(){}
    ).then(function(){
        scanning = true;
        try {
            var caps = scanner.getRunningTrackCapabilities(), adv = {};
            if (caps.focusMode && caps.focusMode.indexOf('continuous') !== -1) adv.focusMode = 'continuous';
            if (caps.zoom && caps.zoom.max >= 2) adv.zoom = Math.max(caps.zoom.min || 1, 2);
            if (Object.keys(adv).length) scanner.applyVideoConstraints({ advanced:[adv] }).catch(function(){});
        } catch (e) {}
    });
}

function startScan(){
    if (typeof Html5Qrcode === 'undefined') { notify('Pemindai barcode gagal dimuat (cek internet). Ketik barcode secara manual.', 'error'); return; }
    if (!window.isSecureContext || !navigator.mediaDevices) { notify('Kamera hanya bisa dipakai lewat alamat https. Ketik barcode secara manual.', 'error'); return; }
    closeResults();
    scanUi(true);
    var ready = cameras ? Promise.resolve(cameras) : Html5Qrcode.getCameras().then(function(list){ cameras = list || []; return cameras; });
    ready.then(function(list){
        var id = pickCamera(list);
        if (list.length) fillCameraSelect(id);
        return runCamera(id);
    }).catch(cameraError);
}

$id('btn-scan').addEventListener('click', function(){ if ($('#scan-box').is(':visible')) stopScan(); else startScan(); });
$id('camera-select').addEventListener('change', function(){
    var id = this.value;
    saveCamera(id);
    var stop = (scanner && scanning) ? (scanning = false, scanner.stop().catch(function(){})) : Promise.resolve();
    stop.then(function(){ return runCamera(id); }).catch(cameraError);
});

/* ═══════ PROSES ═══════ */
function submitForm(){
    if (window._submitting) return;
    var $rows = dataRows();
    if (!$rows.length) { notify('Pilih minimal satu barang', 'warning'); closePay(); return; }

    var bad = null;
    $rows.each(function(){
        var k = String($(this).data('k')), n = $(this).data('n');
        if (qtyNum($id('quantity-' + k + n).value) <= 0) { bad = $id('quantity-' + k + n); return false; }
    });
    if (bad) { notify('Jumlah harus lebih dari 0', 'error'); closePay(); bad.focus(); return; }

    var paidTxt = $.trim($('#money_paid').val());
    if (paidTxt === '') { notify('Isi jumlah uang yang dibayar', 'warning'); $('#money_paid').focus(); return; }
    if ($.trim($('#total_discount_price').val()) === '') $('#total_discount_price').val('0');
    recalc();

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
