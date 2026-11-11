{{--
    Numbers in the loading forms. Same rules as parseNumber() in app/Helpers/main.php:
    "12.500" / "12,500" / "12500.00" = 12500, "1.234,5" / "1,234.5" = 1234.5, "2,5" = 2.5.
    Shown Indonesian style (12.500,5); sent to the server as plain numbers (12500.5).
--}}
<script>
function numParse(v) {
    if (typeof v === 'number') return isFinite(v) ? v : 0;
    var s = String(v == null ? '' : v).replace(/[^0-9.,\-]/g, '');
    if (s === '' || s === '-') return 0;
    var neg = s.charAt(0) === '-';
    s = s.replace(/-/g, '');
    var dot = s.lastIndexOf('.'), comma = s.lastIndexOf(',');
    if (dot >= 0 && comma >= 0) {
        var dec = dot > comma ? '.' : ',';
        s = s.split(dec === '.' ? ',' : '.').join('').replace(dec, '.');
    } else if (dot >= 0 || comma >= 0) {
        var sep = dot >= 0 ? '.' : ',';
        var parts = s.split(sep);
        var thousands = parts.length > 2 || (parts[parts.length - 1].length === 3 && parts[0] !== '' && parts[0] !== '0');
        s = thousands ? parts.join('') : parts.join('.');
    }
    var n = parseFloat(s);
    if (!isFinite(n)) return 0;
    return neg ? -n : n;
}
function numRound(n) { return Math.round(numParse(n) * 100) / 100; }
/* 12500.5 -> "12.500,5" */
function numFmt(n) { return numRound(n).toLocaleString('id-ID', { maximumFractionDigits: 2 }); }
/* 12500.5 -> "12500.5" (what the server gets) */
function numPlain(n) { return String(numRound(n)); }
/* Before submit: every number field of the form as a plain number. */
function numNormalizeForm(form) {
    $(form).find('input,textarea').filter(function () {
        return /^(prices|sell_prices|quantities|total_prices)\[\]$|^total_item_price$/.test(this.name || '');
    }).each(function () {
        if (this.value !== '') this.value = numPlain(this.value);
    });
}
</script>
