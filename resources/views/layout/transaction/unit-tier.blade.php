{{--
  Harga satuan besar otomatis (dipakai form transaksi desktop dan HP).

  Barang dengan beberapa satuan, mis. PCS 3.500 dan PAK5 15.000 (3.000 per pcs).
  Kasir memilih PCS dan mengisi jumlah 6: baris tetap terlihat "PCS x 6", tetapi
  totalnya dihitung 1 PAK5 + 1 PCS = 18.500 (bukan 6 x 3.500 = 21.000).
  Saat transaksi dikirim, baris itu dipecah menjadi satu baris per satuan
  (PAK5 x 1 dan PCS x 1), sama seperti kalau kasir memilih kedua satuan sendiri:
  stok, HPP, dan struk tetap per satuan.

  Aturan:
  - hanya baris jual (bukan retur);
  - satuan besar dipakai hanya bila harga per isinya lebih murah;
  - tidak pernah memecah ke satuan yang lebih kecil dari pilihan kasir;
  - potongan baris (jika ada) tetap berlaku di atas harga ini.

  Butuh dari form: num, qtyNum, r2, fmt, esc, $id.
--}}
<style>
    .tier-note { display:block; margin-top:2px; font-size:12px; font-weight:600; color:#047857; }
    .tier-note:empty { display:none; }
    .tier-note b { font-weight:700; }
</style>
<script>
var TIER = { goods:{} };      /* good_id -> { name, units:[{ id, unit, qty, price, buy_price }] } */

function tierUnit(u){
    return { id:String(u.good_unit_id), unit:u.unit, qty:num(u.unit_qty), price:num(u.selling_price), buy_price:u.buy_price };
}

/* hasil cari nama barang: satu baris per satuan, semua satuan barang ikut */
function tierRememberList(list){
    var fresh = {};
    for (var i = 0; i < list.length; i++) {
        var u = list[i];
        if (u.unit_qty === undefined || u.unit_qty === null) continue;      /* server belum diperbarui: fitur diam */
        if (!fresh[u.good_id]) { fresh[u.good_id] = true; TIER.goods[u.good_id] = { name:u.name, units:[] }; }
        TIER.goods[u.good_id].units.push(tierUnit(u));
    }
}

/* hasil scan barcode: satuan ada di good.sale_units */
function tierRememberGood(g){
    if (!g || !g.sale_units) return;
    TIER.goods[g.id] = { name:g.name, units:$.map(g.sale_units, tierUnit) };
}

/* Rencana harga satuan besar untuk satu baris jual, atau null bila tidak ada yang lebih murah.
   { lines:[{ u, q }], total, saved } - lines dari satuan terbesar ke satuan pilihan kasir. */
function tierPlan(n){
    var s = String(n), g = TIER.goods[$id('name-' + s).value];
    if (!g) return null;
    var own = $id('barcode-' + s).value, price = num($id('price-' + s).value), q = qtyNum($id('quantity-' + s).value);
    var mine = null, bigger = [];
    $.each(g.units, function(i, u){ if (u.id === own) mine = $.extend({}, u, { price:price }); });
    if (!mine || !(mine.qty > 0) || !(price > 0) || !(q > 0)) return null;

    /* satuan lebih besar, per isinya makin murah (satu satuan per isi, yang termurah) */
    var cand = $.grep(g.units, function(u){ return u.id !== own && u.qty > mine.qty && u.price > 0; });
    cand.sort(function(a, b){ return a.qty - b.qty || a.price - b.price; });
    var perIsi = price / mine.qty;
    $.each(cand, function(i, u){
        if (bigger.length && bigger[bigger.length - 1].qty === u.qty) return;
        if (u.price / u.qty < perIsi - 1e-9) { bigger.push(u); perIsi = u.price / u.qty; }
    });
    if (!bigger.length) return null;

    var rest = q * mine.qty, lines = [], total = 0;
    for (var i = bigger.length - 1; i >= 0; i--) {
        var c = Math.floor(rest / bigger[i].qty + 1e-9);
        if (c > 0) { lines.push({ u:bigger[i], q:c }); rest -= c * bigger[i].qty; }
    }
    if (!lines.length) return null;
    var left = Math.round(rest / mine.qty * 10000) / 10000;       /* sisa tetap di satuan pilihan kasir */
    if (left > 0) lines.push({ u:mine, q:left });

    $.each(lines, function(i, l){ total += r2(l.q * l.u.price); });
    var saved = r2(r2(q * price) - total);
    if (saved <= 0) return null;
    return { lines:lines, total:r2(total), saved:saved };
}

function tierText(plan){
    return $.map(plan.lines, function(l){ return fmt(l.q) + ' ' + l.u.unit; }).join(' + ');
}

/* keterangan di baris: "Harga grosir: 1 PAK5 + 1 PCS (hemat 2,500)" */
function tierNote(s, plan){
    var el = $id('tier-' + s);
    if (!el) return;
    el.innerHTML = plan ? 'Harga grosir: <b>' + esc(tierText(plan)) + '</b> (hemat ' + esc(fmt(plan.saved)) + ')' : '';
}

/* Total baris jual setelah harga satuan besar; dipanggil dari calcRow */
function tierGross(k, n, normal){
    if (k) return normal;
    var plan = tierPlan(n);
    tierNote(k + n, plan);
    return plan ? plan.total : normal;
}

/* Sebelum dikirim: baris dengan harga grosir diganti satu baris per satuan.
   Input asli dilepas namanya (tidak terkirim), pengganti ditaruh di tempat yang
   sama supaya urutan barang di struk tidak berubah. */
function tierExpand(){
    $('#table-transaction').children('[data-n]').each(function(){
        var n = $(this).data('n'), s = String(n), plan = tierPlan(n);
        if (!plan) return;
        var good_id = $id('name-' + s).value, g = TIER.goods[good_id], d = num($id('discount-' + s).value);
        var big = 0;                                      /* potongan baris ditaruh di baris bernilai terbesar */
        $.each(plan.lines, function(i, l){ if (l.q * l.u.price > plan.lines[big].q * plan.lines[big].u.price) big = i; });

        $.each(['barcode', 'name', 'name_temp', 'buy_price', 'total_price', 'price', 'quantity', 'discount', 'sum'], function(i, f){
            var el = $id(f + '-' + s);
            if (el) el.removeAttribute('name');
        });

        var h = '';
        function hid(name, v){ h += '<input type="hidden" name="' + name + '[]" value="' + esc(v) + '">'; }
        $.each(plan.lines, function(i, l){
            var tot = r2(l.q * l.u.price), disc = i === big ? d : 0;
            hid('barcodes', l.u.id);
            hid('names', good_id);
            hid('name_temps', g.name + ' ' + l.u.unit);
            hid('buy_prices', l.u.buy_price);
            hid('total_prices', plain(tot));
            hid('prices', plain(l.u.price));
            hid('quantities', plain(l.q));
            hid('discounts', plain(disc));
            hid('sums', plain(tot - disc));
        });
        $($id('barcode-' + s).parentNode).append(h);
    });
}
</script>
