<style type="text/css">
  :root {
    --pd-blue: #3c8dbc;
    --pd-blue-dark: #2c6f98;
    --pd-red: #d9534f;
    --pd-line: #e3e8ef;
    --pd-soft: #f4f8fb;
  }

  /* ===== Langkah ===== */
  .pd-step { margin-bottom: 22px; }
  .pd-step-title {
    display: flex; align-items: center;
    font-size: 16px; font-weight: 700; margin: 0 0 10px;
  }
  .pd-step-no {
    display: inline-flex; align-items: center; justify-content: center;
    width: 26px; height: 26px; margin-right: 8px;
    border-radius: 50%; background: var(--pd-blue); color: #fff; font-size: 14px;
  }

  /* ===== Pilihan jenis display ===== */
  .pd-types { display: flex; flex-wrap: wrap; margin: 0 -6px; }
  .pd-type { flex: 1 1 240px; margin: 0 6px 10px; cursor: pointer; }
  .pd-type input { position: absolute; opacity: 0; pointer-events: none; }
  .pd-type-body {
    display: flex; align-items: center; padding: 12px 14px;
    border: 2px solid var(--pd-line); border-radius: 6px; background: #fff;
    transition: border-color .15s, background .15s;
  }
  .pd-type:hover .pd-type-body { border-color: #b8cfe0; }
  .pd-type input:checked + .pd-type-body { border-color: var(--pd-blue); background: var(--pd-soft); }
  .pd-type input:focus + .pd-type-body { box-shadow: 0 0 0 3px rgba(60,141,188,.25); }
  .pd-type-icon { font-size: 26px; color: var(--pd-blue); margin-right: 12px; width: 32px; text-align: center; }
  .pd-type-name { font-weight: 700; font-size: 15px; display: block; }
  .pd-type-hint { font-size: 12px; color: #777; display: block; }
  .pd-type-check { margin-left: auto; color: var(--pd-blue); font-size: 20px; visibility: hidden; }
  .pd-type input:checked + .pd-type-body .pd-type-check { visibility: visible; }

  /* ===== Cari barang ===== */
  .pd-search .form-control { height: 44px; font-size: 16px; }
  .pd-search .btn { height: 44px; font-size: 15px; }
  .pd-search-hint { font-size: 12px; color: #777; margin-top: 6px; }

  /* ===== Daftar terpilih ===== */
  .pd-list-head {
    display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap;
    margin-bottom: 8px;
  }
  .pd-table { margin-bottom: 0; }
  .pd-table > thead > tr > th { background: var(--pd-soft); border-bottom: 2px solid var(--pd-line); font-size: 12px; text-transform: uppercase; letter-spacing: .04em; color: #555; }
  .pd-table > tbody > tr > td { vertical-align: middle; }
  .pd-table .pd-name { font-weight: 700; font-size: 15px; }
  .pd-table .pd-unit { margin-left: 6px; }
  .pd-new { animation: pdFlash 1.6s ease-out; }
  @keyframes pdFlash { from { background: #fff6bf; } to { background: transparent; } }

  .pd-qty { display: inline-flex; align-items: stretch; }
  .pd-qty .btn { width: 34px; padding: 0; font-size: 16px; font-weight: 700; }
  .pd-qty input { width: 56px; text-align: center; border-radius: 0; margin: 0 -1px; position: relative; z-index: 1; font-weight: 700; }
  .pd-remove { color: var(--pd-red); cursor: pointer; font-size: 18px; padding: 4px 8px; background: none; border: 0; }
  .pd-remove:hover { color: #a94442; }

  .pd-empty { text-align: center; color: #888; padding: 28px 10px; border: 2px dashed var(--pd-line); border-radius: 6px; }
  .pd-empty i { font-size: 30px; display: block; margin-bottom: 6px; color: #b8c4d0; }

  /* ===== Ringkasan ===== */
  .pd-summary { border: 1px solid var(--pd-line); border-radius: 6px; padding: 12px 14px; background: var(--pd-soft); }
  .pd-summary-row { display: flex; justify-content: space-between; flex-wrap: wrap; font-size: 15px; }
  .pd-summary .progress { height: 10px; margin: 8px 0 6px; }
  .pd-summary-note { font-size: 12px; color: #666; }
  .pd-summary.is-over { border-color: #f0ad4e; background: #fff9ee; }
  .pd-summary.is-over .pd-summary-note { color: #8a6d3b; font-weight: 700; }

  .pd-error { display: none; margin-bottom: 10px; }
  .pd-print-btn { height: 48px; font-size: 17px; font-weight: 700; }

  /* ===== Modal hasil pencarian ===== */
  .pd-results { max-height: 52vh; overflow-y: auto; margin: 0 -15px; }
  .pd-result {
    display: flex; align-items: center; margin: 0; padding: 10px 15px;
    border-bottom: 1px solid var(--pd-line); cursor: pointer; font-weight: 400;
  }
  .pd-result:hover { background: var(--pd-soft); }
  .pd-result input { margin: 0 12px 0 0; width: 18px; height: 18px; flex: none; }
  .pd-result-name { flex: 1; font-weight: 700; color: #222; word-break: break-word; }
  .pd-result .label, .pd-result .pd-stock { margin-left: 8px; flex: none; }
  .pd-result.is-selected { background: #f1f1f1; cursor: not-allowed; }
  .pd-result.is-selected .pd-result-name { color: #999; font-weight: 400; }
  .pd-units { display: flex; flex-wrap: wrap; justify-content: flex-end; flex: none; max-width: 55%; }
  .pd-unit-chip {
    display: inline-flex; align-items: center; margin: 2px 0 2px 6px; padding: 2px 8px;
    border: 1px solid #c9d8e5; border-radius: 12px; background: #fff; font-size: 12px; white-space: nowrap;
  }
  .pd-unit-chip b { color: var(--pd-blue-dark); margin-right: 6px; }
  .pd-unit-chip.pos  { background: #e6f1dd; }
  .pd-unit-chip.zero { background: #eceeef; }
  .pd-unit-chip.neg  { background: #f3e6d8; }
  .pd-stock { font-size: 12px; padding: 2px 8px; border-radius: 10px; color: #222; }
  .pd-stock-pos  { background: #d6e8c9; }
  .pd-stock-zero { background: #e2e4e6; }
  .pd-stock-neg  { background: #ecdccb; }
  .pd-results-bar { display: flex; align-items: center; justify-content: space-between; padding: 0 0 10px; }
  .pd-results-bar label { margin: 0; font-weight: 700; cursor: pointer; }
  .pd-results-bar input { margin-right: 6px; }
  .pd-state { text-align: center; padding: 30px 10px; color: #777; }
</style>

<div class="content-wrapper">

  @include('layout' . '.alert-message', ['type' => $default['type'], 'data' => $default['data'], 'color' => $default['color']])

  <section class="content">
    <div class="row">
      <div class="col-xs-12">
        <div class="box">
          <div class="box-header with-border">
            <h3 class="box-title">{{ $default['page_name'] }}</h3>
          </div>

          {!! Form::model(old(), array('url' => route($role . '.print-display'), 'method' => 'POST', 'id' => 'print-form')) !!}
          <div class="box-body">

            <div class="pd-error alert alert-danger" id="pd-error"></div>

            {{-- LANGKAH 1 --}}
            <div class="pd-step">
              <h4 class="pd-step-title"><span class="pd-step-no">1</span> Pilih jenis display</h4>
              <div class="pd-types">
                <label class="pd-type">
                  <input type="radio" name="type" value="rack">
                  <span class="pd-type-body">
                    <i class="fa fa-th-large pd-type-icon"></i>
                    <span>
                      <span class="pd-type-name">Display Rak</span>
                      <span class="pd-type-hint">Label harga besar, 1 barang per kotak</span>
                    </span>
                    <i class="fa fa-check-circle pd-type-check"></i>
                  </span>
                </label>
                <label class="pd-type">
                  <input type="radio" name="type" value="list">
                  <span class="pd-type-body">
                    <i class="fa fa-list-ul pd-type-icon"></i>
                    <span>
                      <span class="pd-type-name">Display List</span>
                      <span class="pd-type-hint">Daftar harga, dipasang di tengah rak</span>
                    </span>
                    <i class="fa fa-check-circle pd-type-check"></i>
                  </span>
                </label>
              </div>
            </div>

            {{-- LANGKAH 2 --}}
            <div class="pd-step">
              <h4 class="pd-step-title"><span class="pd-step-no">2</span> Cari dan tambah barang</h4>
              <div class="pd-search">
                <div class="input-group">
                  <input type="text" id="search_good" class="form-control" placeholder="Ketik nama barang, misalnya: mie" autocomplete="off">
                  <span class="input-group-btn">
                    <button type="button" class="btn btn-primary btn-flat" id="btn-search"><i class="fa fa-search"></i> Cari</button>
                  </span>
                </div>
                <div class="pd-search-hint">Tekan Enter untuk mencari. Hasil pencarian bisa dipilih beberapa sekaligus. Barang dengan beberapa satuan cukup dipilih sekali, semua satuannya otomatis ikut dicetak.</div>
              </div>
            </div>

            {{-- LANGKAH 3 --}}
            <div class="pd-step">
              <h4 class="pd-step-title"><span class="pd-step-no">3</span> Atur jumlah cetak</h4>

              <div class="pd-list-head">
                <span id="pd-count-label" class="text-muted">Belum ada barang dipilih</span>
                <button type="button" class="btn btn-default btn-sm btn-flat" id="btn-clear" style="display:none;"><i class="fa fa-trash"></i> Kosongkan</button>
              </div>

              <div id="pd-empty" class="pd-empty">
                <i class="fa fa-shopping-basket"></i>
                Belum ada barang. Cari barang di atas lalu centang yang ingin dicetak.
              </div>

              <div class="table-responsive" id="pd-table-wrap" style="display:none;">
                <table class="table table-hover pd-table">
                  <thead>
                    <tr>
                      <th style="width:40px;">#</th>
                      <th>Barang</th>
                      <th style="width:170px; text-align:center;">Jumlah</th>
                      <th style="width:50px;"></th>
                    </tr>
                  </thead>
                  <tbody id="pd-rows"></tbody>
                </table>
              </div>
            </div>

            <div class="pd-summary" id="pd-summary">
              <div class="pd-summary-row">
                <span>Jumlah barang yang akan dicetak: <strong id="pd-total">0</strong></span>
                <span id="pd-limit-text" class="text-muted"></span>
              </div>
              <div class="progress" id="pd-progress-wrap" style="display:none;">
                <div class="progress-bar progress-bar-success" id="pd-progress" style="width:0%"></div>
              </div>
              <div class="pd-summary-note" id="pd-note">Pilih jenis display untuk melihat batas per halaman.</div>
            </div>

          </div>
          <div class="box-footer">
            <button type="button" class="btn btn-success btn-flat btn-block pd-print-btn" id="btn-print"><i class="fa fa-print"></i> Print</button>
          </div>
          {!! Form::close() !!}
        </div>
      </div>
    </div>
  </section>
</div>

{{-- Modal hasil pencarian (di luar form agar checkbox tidak ikut terkirim) --}}
<div class="modal fade" id="modal_search" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">Hasil pencarian: <strong id="pd-modal-keyword"></strong></h4>
      </div>
      <div class="modal-body">
        <div class="pd-results-bar" id="pd-results-bar" style="display:none;">
          <label><input type="checkbox" id="pd-check-all"> Pilih semua yang belum dipilih</label>
          <span class="text-muted" id="pd-results-info"></span>
        </div>
        <div class="pd-results" id="result_good"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-primary" id="btn-add-selected" disabled><i class="fa fa-plus"></i> Tambahkan ke daftar <span id="pd-add-count"></span></button>
      </div>
    </div>
  </div>
</div>

@section('js-addon')
  <script type="text/javascript">
    // Batas jumlah label per halaman (ubah di sini bila ukuran halaman cetak berubah)
    var PAGE_LIMIT = { rack: 24, list: 17 };

    var items = [];        // barang terpilih: {id, name, unit, qty}
    var resultMap = {};    // hasil pencarian terakhir, berdasarkan good_unit_id

    function esc(s) {
      return String(s === null || s === undefined ? '' : s)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function findItem(id) {
      for (var i = 0; i < items.length; i++) { if (String(items[i].id) === String(id)) return items[i]; }
      return null;
    }

    // Satu barang bisa punya beberapa satuan (beberapa baris good_unit). Dikelompokkan menjadi 1 barang.
    function groupResults(list) {
      var map = {}, order = [];
      for (var i = 0; i < list.length; i++) {
        var r = list[i];
        var key = (r.good_id !== undefined && r.good_id !== null)
          ? 'g' + r.good_id
          : 'n' + String(r.name).toLowerCase().replace(/\s+/g, ' ').replace(/^\s+|\s+$/g, '');
        if (!map[key]) {
          map[key] = { key: key, name: r.name, id: String(r.good_unit_id), units: [] };
          order.push(key);
        }
        map[key].units.push({ id: String(r.good_unit_id), unit: r.unit, stock: r.stock });
      }
      var out = [];
      for (var k = 0; k < order.length; k++) out.push(map[order[k]]);
      return out;
    }

    // Apakah barang (kelompok) ini sudah ada di daftar terpilih?
    function findGroupItem(group) {
      for (var i = 0; i < items.length; i++) {
        var it = items[i];
        if (it.key && it.key === group.key) return it;
        for (var u = 0; u < group.units.length; u++) {
          if (String(it.id) === group.units[u].id) return it;
        }
      }
      return null;
    }

    function showError(msg) {
      $('#pd-error').html('<i class="fa fa-exclamation-triangle"></i> ' + esc(msg)).show();
      $('html, body').animate({ scrollTop: $('#pd-error').offset().top - 80 }, 200);
    }
    function hideError() { $('#pd-error').hide(); }

    /* ================= PENCARIAN ================= */

    function doSearch() {
      var keyword = $.trim($('#search_good').val());
      if (keyword === '') { $('#search_good').focus(); return; }

      $('#pd-modal-keyword').text(keyword);
      $('#pd-results-bar').hide();
      $('#result_good').html('<div class="pd-state"><i class="fa fa-spinner fa-spin"></i> Mencari...</div>');
      $('#pd-check-all').prop('checked', false);
      updateAddButton();
      $('#modal_search').modal('show');

      $.ajax({
        url: "{!! url($role . '/good/searchByKeywordGoodUnit/') !!}/" + encodeURIComponent(keyword),
        success: function (result) {
          renderResults(result.good_units || [], keyword);
        },
        error: function () {
          $('#result_good').html('<div class="pd-state text-red"><i class="fa fa-exclamation-circle"></i> Gagal mengambil data. Coba lagi.</div>');
        }
      });
    }

    function renderResults(list, keyword) {
      resultMap = {};
      var groups = groupResults(list);

      if (!groups.length) {
        $('#result_good').html('<div class="pd-state"><i class="fa fa-search"></i><br>Tidak ada barang untuk "' + esc(keyword) + '".</div>');
        $('#pd-results-bar').hide();
        updateAddButton();
        return;
      }

      var html = '';
      for (var i = 0; i < groups.length; i++) {
        var g = groups[i];
        resultMap[g.key] = g;
        var already = !!findGroupItem(g);

        var chips = '';
        for (var u = 0; u < g.units.length; u++) {
          var st = parseFloat(g.units[u].stock);
          var cls = st > 0 ? 'pos' : (st < 0 ? 'neg' : 'zero');
          chips += '<span class="pd-unit-chip ' + cls + '"><b>' + esc(g.units[u].unit) + '</b>Stok ' + esc(g.units[u].stock) + '</span>';
        }

        html += '<label class="pd-result' + (already ? ' is-selected' : '') + '">' +
                  '<input type="checkbox" class="pd-chk" data-key="' + esc(g.key) + '"' + (already ? ' checked disabled' : '') + '>' +
                  '<span class="pd-result-name">' + esc(g.name) + '</span>' +
                  (already ? '<span class="label label-default">Sudah dipilih</span>' : '') +
                  '<span class="pd-units">' + chips + '</span>' +
                '</label>';
      }
      $('#result_good').html(html);

      var available = $('#result_good .pd-chk:not(:disabled)').length;
      $('#pd-results-info').text(groups.length + ' barang' + (available < groups.length ? ' (' + (groups.length - available) + ' sudah dipilih)' : ''));
      $('#pd-results-bar').show();
      $('#pd-check-all').prop('disabled', available === 0).prop('checked', false);
      updateAddButton();
    }

    function updateAddButton() {
      var n = $('#result_good .pd-chk:checked:not(:disabled)').length;
      $('#btn-add-selected').prop('disabled', n === 0);
      $('#pd-add-count').text(n > 0 ? '(' + n + ')' : '');

      var available = $('#result_good .pd-chk:not(:disabled)').length;
      $('#pd-check-all').prop('checked', available > 0 && n === available);
    }

    function addSelected() {
      var added = [];
      $('#result_good .pd-chk:checked:not(:disabled)').each(function () {
        var g = resultMap[String($(this).data('key'))];
        if (!g || findGroupItem(g)) return;      // barang yang sudah dipilih tidak bisa ditambah lagi

        var units = [];
        for (var u = 0; u < g.units.length; u++) units.push(g.units[u].unit);
        // Cukup kirim 1 id; sistem otomatis mencetak semua satuan barang tersebut
        added.push({ id: g.id, key: g.key, name: g.name, units: units, qty: 1 });
      });

      if (!added.length) return;

      // barang terbaru tampil paling atas
      for (var i = added.length - 1; i >= 0; i--) { items.unshift(added[i]); }
      renderItems(added);
      hideError();

      $('#modal_search').modal('hide');
      $('#search_good').val('');
    }

    /* ================= DAFTAR TERPILIH ================= */

    function unitLabels(it) {
      var out = '';
      var us = it.units || [];
      for (var u = 0; u < us.length; u++) out += '<span class="label label-primary pd-unit">' + esc(us[u]) + '</span>';
      return out;
    }

    function renderItems(newItems) {
      var isNew = {};
      if (newItems) { for (var n = 0; n < newItems.length; n++) isNew[newItems[n].id] = true; }

      var html = '';
      for (var i = 0; i < items.length; i++) {
        var it = items[i];
        html += '<tr data-id="' + esc(it.id) + '"' + (isNew[it.id] ? ' class="pd-new"' : '') + '>' +
                  '<td class="text-muted">' + (i + 1) + '</td>' +
                  '<td>' +
                    '<input type="hidden" name="ids[]" value="' + esc(it.id) + '">' +
                    '<input type="hidden" name="names[]" value="' + esc(it.name) + '">' +
                    '<span class="pd-name">' + esc(it.name) + '</span>' +
                    unitLabels(it) +
                  '</td>' +
                  '<td align="center">' +
                    '<div class="pd-qty">' +
                      '<button type="button" class="btn btn-default pd-minus" tabindex="-1">&minus;</button>' +
                      '<input type="number" min="1" step="1" name="quantities[]" class="form-control pd-qty-input" value="' + esc(it.qty) + '">' +
                      '<button type="button" class="btn btn-default pd-plus" tabindex="-1">+</button>' +
                    '</div>' +
                  '</td>' +
                  '<td><button type="button" class="pd-remove" title="Hapus"><i class="fa fa-times"></i></button></td>' +
                '</tr>';
      }
      $('#pd-rows').html(html);

      var has = items.length > 0;
      $('#pd-empty').toggle(!has);
      $('#pd-table-wrap').toggle(has);
      $('#btn-clear').toggle(has);
      $('#pd-count-label').text(has ? items.length + ' barang dipilih' : 'Belum ada barang dipilih');

      updateSummary();
    }

    function getQty(id) {
      var it = findItem(id);
      return it ? it.qty : 0;
    }

    function setQty(id, value) {
      var it = findItem(id);
      if (!it) return;
      var q = parseInt(value, 10);
      it.qty = (isNaN(q) || q < 1) ? 1 : q;
    }

    function updateSummary() {
      var total = 0;
      for (var i = 0; i < items.length; i++) total += items[i].qty;
      $('#pd-total').text(total);

      var type = $('input[name="type"]:checked').val();
      var limit = type ? PAGE_LIMIT[type] : null;
      var $sum = $('#pd-summary');

      if (!limit) {
        $('#pd-progress-wrap').hide();
        $('#pd-limit-text').text('');
        $('#pd-note').text('Pilih jenis display untuk melihat batas per halaman.');
        $sum.removeClass('is-over');
        return;
      }

      var pct = Math.min(100, Math.round(total / limit * 100));
      var over = total > limit;
      var pages = Math.max(1, Math.ceil(total / limit));

      $('#pd-limit-text').text('Maks. ' + limit + ' per halaman');
      $('#pd-progress-wrap').show();
      $('#pd-progress')
        .css('width', pct + '%')
        .removeClass('progress-bar-success progress-bar-warning progress-bar-danger')
        .addClass(over ? 'progress-bar-danger' : (pct >= 80 ? 'progress-bar-warning' : 'progress-bar-success'));

      if (over) {
        $('#pd-note').html('<i class="fa fa-exclamation-triangle"></i> Melebihi 1 halaman (' + limit + '). Perkiraan ' + pages + ' halaman.');
      } else {
        $('#pd-note').text('Muat dalam 1 halaman (' + total + ' dari ' + limit + ').');
      }
      $sum.toggleClass('is-over', over);
    }

    /* ================= KIRIM ================= */

    function submitForm() {
      hideError();

      if (!$('input[name="type"]:checked').length) {
        showError('Pilih jenis display terlebih dahulu (Display Rak atau Display List).');
        return;
      }
      if (!items.length) {
        showError('Belum ada barang yang dipilih. Cari barang lalu tambahkan ke daftar.');
        return;
      }
      for (var i = 0; i < items.length; i++) {
        if (!items[i].qty || items[i].qty < 1) {
          showError('Jumlah cetak minimal 1 untuk setiap barang.');
          return;
        }
      }

      $('#btn-print').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Memproses...');
      document.getElementById('print-form').submit();
    }

    /* ================= EVENT ================= */

    $(document).ready(function () {

      // Enter di kolom mana pun tidak boleh mengirim form
      $('#print-form').on('keydown', 'input', function (e) {
        if (e.keyCode === 13) e.preventDefault();
      });

      $('#search_good').on('keydown', function (e) {
        if (e.keyCode === 13) { e.preventDefault(); doSearch(); }
      });
      $('#btn-search').on('click', doSearch);

      // Modal hasil
      $('#result_good').on('change', '.pd-chk', updateAddButton);
      $('#pd-check-all').on('change', function () {
        $('#result_good .pd-chk:not(:disabled)').prop('checked', $(this).is(':checked'));
        updateAddButton();
      });
      $('#btn-add-selected').on('click', addSelected);
      $('#modal_search').on('shown.bs.modal', function () { $('#result_good').scrollTop(0); });
      $('#modal_search').on('hidden.bs.modal', function () { $('#search_good').focus(); });

      // Daftar terpilih
      $('#pd-rows').on('click', '.pd-plus, .pd-minus', function () {
        var $tr = $(this).closest('tr');
        var $in = $tr.find('.pd-qty-input');
        var q = (parseInt($in.val(), 10) || 1) + ($(this).hasClass('pd-plus') ? 1 : -1);
        if (q < 1) q = 1;
        $in.val(q);
        setQty($tr.data('id'), q);
        updateSummary();
      });
      $('#pd-rows').on('input', '.pd-qty-input', function () {
        var q = parseInt($(this).val(), 10);
        if (!isNaN(q) && q >= 1) { setQty($(this).closest('tr').data('id'), q); updateSummary(); }
      });
      $('#pd-rows').on('change blur', '.pd-qty-input', function () {
        var $tr = $(this).closest('tr');
        setQty($tr.data('id'), $(this).val());
        $(this).val(getQty($tr.data('id')));
        updateSummary();
      });
      $('#pd-rows').on('focus', '.pd-qty-input', function () { this.select(); });

      $('#pd-rows').on('click', '.pd-remove', function () {
        var id = String($(this).closest('tr').data('id'));
        items = $.grep(items, function (it) { return String(it.id) !== id; });
        renderItems();
      });

      $('#btn-clear').on('click', function () {
        if (confirm('Kosongkan semua barang yang sudah dipilih?')) { items = []; renderItems(); }
      });

      $('input[name="type"]').on('change', function () { hideError(); updateSummary(); });
      $('#btn-print').on('click', submitForm);

      // Pulihkan pilihan bila halaman dimuat ulang setelah gagal validasi
      var oldIds = {!! json_encode(old('ids', [])) !!};
      var oldNames = {!! json_encode(old('names', [])) !!};
      var oldQty = {!! json_encode(old('quantities', [])) !!};
      var oldType = {!! json_encode(old('type')) !!};
      for (var i = 0; i < oldIds.length; i++) {
        if (oldIds[i] && !findItem(oldIds[i])) {
          items.push({ id: String(oldIds[i]), key: '', name: oldNames[i] || '', units: [], qty: Math.max(1, parseInt(oldQty[i], 10) || 1) });
        }
      }
      if (oldType) { $('input[name="type"][value="' + oldType + '"]').prop('checked', true); }

      renderItems();
      $('#search_good').focus();
    });
  </script>
@endsection