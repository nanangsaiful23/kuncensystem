{{--
    Cek harga beli dan harga jual di browser (loading, ubah loading, ganti harga,
    tambah harga, barang baru). Aturan sama dengan buySellPriceProblem() di
    app/Helpers/main.php:
      - salah (tidak bisa disimpan): harga beli / harga jual kosong atau 0,
        harga jual lebih kecil dari harga beli
      - peringatan (boleh disimpan setelah dikonfirmasi): harga jual sama dengan harga beli
    Butuh numParse / numRound / numFmt dari layout/good-loading/number-js (include lebih dulu).
--}}
<script>
function priceProblem(buy, sell) {
    var b = numRound(buy), s = numRound(sell);
    if (!(b > 0)) return { level: 'error', field: 'buy',  msg: 'harga beli masih kosong atau 0' };
    if (!(s > 0)) return { level: 'error', field: 'sell', msg: 'harga jual masih kosong atau 0' };
    if (s < b)    return { level: 'error', field: 'sell', msg: 'harga jual Rp ' + numFmt(s) + ' lebih kecil dari harga beli Rp ' + numFmt(b) + ' (rugi Rp ' + numFmt(b - s) + ')' };
    if (s === b)  return { level: 'warn',  field: 'sell', msg: 'harga jual sama dengan harga beli (tidak ada untung)' };
    return null;
}

/* Keterangan untung untuk harga yang benar, mis. "Untung Rp 1.500 (12,5%)" */
function priceMarginText(buy, sell) {
    var b = numRound(buy), s = numRound(sell);
    if (!(b > 0) || !(s > b)) return '';
    return 'Untung Rp ' + numFmt(s - b) + ' (' + numFmt((s - b) / b * 100) + '%)';
}

/* Warnai kolom harga: merah = salah, kuning = sama dengan harga beli. */
function markPriceInputs(buyEl, sellEl, problem) {
    var onBuy  = !!(problem && problem.field === 'buy');
    var onSell = !!(problem && problem.field === 'sell');
    $(buyEl).toggleClass('price-bad', onBuy).attr('title', onBuy ? problem.msg : '');
    $(sellEl).toggleClass('price-bad', onSell && problem.level === 'error')
             .toggleClass('price-warn', onSell && problem.level === 'warn')
             .attr('title', onSell ? problem.msg : priceMarginText($(buyEl).val(), $(sellEl).val()));
}

/*
 * Untuk form biasa (Bootstrap: ganti harga, tambah harga): warnai .form-group dan
 * tulis keterangan di bawah kolom (salah, sama, atau untungnya). Selama salah satu
 * kolom masih kosong belum ada keterangan; kolom kosong dicek saat simpan.
 */
function markPriceFormGroups(buyEl, sellEl) {
    var b = $(buyEl).val(), s = $(sellEl).val();
    var filled = $.trim(b) !== '' && $.trim(s) !== '';
    var p = filled ? priceProblem(b, s) : null;
    var onBuy = !!(p && p.field === 'buy'), onSell = !!(p && p.field === 'sell');
    var $bg = $(buyEl).closest('.form-group'), $sg = $(sellEl).closest('.form-group');
    $bg.toggleClass('has-error', onBuy);
    $sg.toggleClass('has-error', onSell && p.level === 'error')
       .toggleClass('has-warning', onSell && p.level === 'warn')
       .toggleClass('has-success', filled && !p);
    priceNote(buyEl, onBuy ? p.msg : '');
    priceNote(sellEl, onSell ? p.msg : priceMarginText(b, s));
}
function priceNote(el, text) {
    var $n = $(el).siblings('.price-note');
    if (!$n.length) $n = $('<span class="help-block price-note" style="margin-bottom:0"></span>').insertAfter(el);
    $n.text(text).toggle(text !== '');
}

/*
 * Cek sebelum simpan. checks: [{ label, buy, sell, buyEl, sellEl }] (kolom yang difokus bila salah).
 * Ada yang salah -> pesan, fokus ke kolomnya, false. Ada peringatan -> konfirmasi.
 */
function confirmPrices(checks) {
    var errors = [], warns = [], firstBad = null, firstWarn = null;
    for (var i = 0; i < checks.length; i++) {
        var c = checks[i], p = priceProblem(c.buy, c.sell);
        if (!p) continue;
        var el = p.field === 'buy' ? (c.buyEl || c.el) : (c.sellEl || c.el);
        if (p.level === 'error') { errors.push(c.label + ': ' + p.msg); firstBad = firstBad || el; }
        else { warns.push(c.label + ': ' + p.msg); firstWarn = firstWarn || el; }
    }
    function list(lines) {
        var max = 12, out = lines.slice(0, max).join('\n');
        return lines.length > max ? out + '\n... dan ' + (lines.length - max) + ' lainnya' : out;
    }
    function focusEl(el) {
        if (!el) return;
        if (el.focus) el.focus();
        if (el.scrollIntoView) el.scrollIntoView({ block: 'nearest' });
    }
    if (errors.length) {
        alert('Harga belum benar, perbaiki dulu (kolom merah):\n\n' + list(errors));
        focusEl(firstBad);
        return false;
    }
    if (warns.length && !confirm('Periksa lagi harganya:\n\n' + list(warns) + '\n\nTetap simpan?')) {
        focusEl(firstWarn);
        return false;
    }
    return true;
}
</script>
