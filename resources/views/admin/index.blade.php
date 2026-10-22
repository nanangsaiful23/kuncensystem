@extends('layout.user', ['role' => 'admin', 'title' => 'Admin'])

@section('content')

<style type="text/css">
  .box
  {
    border: solid 2px white;
    border-radius: 10px;
  }

  /* ===== Print harga naik ===== */
  .gp-toolbar
  {
    display: flex; flex-wrap: wrap; align-items: center;
    padding: 10px 12px; margin-bottom: 10px;
    background: #f4f8fb; border: 1px solid #e3e8ef; border-radius: 6px;
  }
  .gp-toolbar > * { margin: 3px 12px 3px 0; }
  .gp-toolbar label { margin-bottom: 0; font-weight: 600; cursor: pointer; }
  .gp-toolbar .gp-spacer { flex: 1 1 auto; margin: 0; }
  .gp-check, #gp-check-all { width: 18px; height: 18px; cursor: pointer; }
  tr.gp-selected > td { background: #fff7cc !important; }
  #gp-table td, #gp-table th { vertical-align: middle !important; }
</style>

  <div class="content-wrapper" style="background-color: #EEEDEB">
    <section class="content-header" style="background-color: white !important">
      <h1>
        <br>
      </h1>
    </section>
    <div class="row" style="margin-top: 10px">
      @if($last_cash_flow == null)
        <section class="col-lg-12" style="height: 60px !important;">

          <!-- Chat box -->
          <div class="box">
            <div class="box-header">
              <h2 class="box-title">KEMAREN BELUM PENARIKAN UANG</h2>
            </div>
          </div>

        </section>
      @endif
      <section class="col-lg-7 connectedSortable">

        <!-- Chat box -->
        <div class="box">
          <div class="box-header">
            <h2 class="box-title">Rincian Transaksi {{ displayDate(date('Y-m-d')) }}</h2>
          </div>
          <div class="box-body chat">
            <div class="col-sm-6">
              <table style="font-size: 20px;">
                <tr>
                  <td>Barang</td>
                  <td>:</td>
                  <td style="text-align: right;">{{ showRupiah($transactions['cash']->sum('total_sum_price') + $transactions['credit']->sum('total_sum_price') + $transactions['transfer']->sum('total_sum_price') + $transactions['credit_transfer']->sum('total_sum_price') + $transactions['retur']->sum('total_sum_price')) }}</td>
                </tr>
                <tr>
                  <td>Lain-lain</td>
                  <td>:</td>
                  <td style="text-align: right;">{{ showRupiah($other_transactions->sum('debit')) }}</td>
                </tr>
                <tr style="font-weight: bold;">
                  <td>Total</td>                  
                  <td>:</td>
                  <td style="text-align: right;">{{ showRupiah($transactions['cash']->sum('total_sum_price') + $transactions['credit']->sum('total_sum_price') + $transactions['transfer']->sum('total_sum_price') + $transactions['credit_transfer']->sum('total_sum_price') + $transactions['retur']->sum('total_sum_price') + $other_transactions->sum('debit')) }}</td>
                </tr>
              </table>
            </div>
            <div class="col-sm-6">
              <table style="font-size: 20px;">
                <tr>
                  <td>Uang cash</td>
                  <td>:</td>
                  <td style="text-align: right;">{{ showRupiah($transactions['cash']->sum('total_sum_price') + $transactions['credit']->sum('money_paid') + $transactions['retur']->sum('total_sum_price') + $other_transactions->sum('debit')) }}</td>
                </tr>
                <tr>
                  <td>Uang transfer</td>
                  <td>:</td>
                  <td style="text-align: right;">{{ showRupiah($transactions['transfer']->sum('total_sum_price') + ($transactions['credit_transfer']->sum('money_paid'))) }}</td>
                </tr>
              </table>
            </div>
          </div>
        </div>

      </section>
      <section class="col-lg-4 connectedSortable">

        <!-- Chat box -->
        <div class="box">
          <div class="box-header">
            <h2 class="box-title">Hi, {{ \Auth::user()->name }}</h2>
          </div>
          <div class="box-body chat">
            Selamat datang di aplikasi {{ config('app.name') }}<br>
            <small>Anda login sebagai Admin</small>
          </div>
        </div>

      </section>
      <section class="col-lg-7 connectedSortable">

        <!-- Chat box -->
        <div class="box">
          <div class="box-header">
            <h2 class="box-title">Daftar Harga Naik</h2>
          <h5>(jika harga display sudah diganti, mohon dichecklist)</h5>
          </div>
          <div class="box-body chat">
          <div class="gp-toolbar">
            <label><input type="radio" name="gp_type" value="rack" checked> Display Rak</label>
            <label><input type="radio" name="gp_type" value="list"> Display List</label>
            <span id="gp-layout-wrap">
              <select id="gp_layout" class="form-control input-sm" style="width:auto; display:inline-block;" title="Ukuran label per halaman A4">
                <option value="2">2 label / halaman</option>
                <option value="4">4 label / halaman</option>
                <option value="9" selected>9 label / halaman</option>
                <option value="16">16 label / halaman</option>
                <option value="25">25 label / halaman</option>
              </select>
            </span>
            <span class="gp-spacer"></span>
            <span class="text-muted" id="gp-info">Belum ada yang dipilih</span>
            <button type="button" class="btn btn-primary btn-sm" id="gp-print" disabled><i class="fa fa-print"></i> Print terpilih (<span id="gp-count">0</span>)</button>
          </div>
          <table class="table table-bordered table-striped" id="gp-table">
            <thead>
              <tr>
                <th style="width:40px; text-align:center;"><input type="checkbox" id="gp-check-all" title="Pilih semua"></th>
                <th>Nama Barang</th>
                <th>Harga Lama</th>
                <th>Harga Baru</th>
                <th>Tanggal Perubahan</th>
                <th>Sudah Diganti</th>
              </tr>
            </thead>
            <tbody>
              @foreach($good_prices as $price)
                <tr class="gp-row"
                    data-price-id="{{ $price->id }}"
                    data-good-unit-id="{{ $price->good_unit->id }}"
                    data-good-id="{{ $price->good_unit->good->id }}"
                    data-name="{{ $price->good_unit->good->getFullName() }}">
                  <td style="text-align:center;"><input type="checkbox" class="gp-check"></td>
                  <td>{{ $price->good_unit->good->getFullName() . ' ' . $price->good_unit->unit->name}}</td>
                  <td>{{ showRupiah($price->old_price) }}</td>
                  <td>{{ showRupiah($price->recent_price) }}</td>
                  <td>{{ displayDate($price->created_at) }}</td>
                  <td><a href="{{ url('admin/good-price/' . $price->id . '/checked') }}"><i class="fa fa-check orange" aria-hidden="true"></i></a></td>
                </tr>
              @endforeach
              <tr class="gp-empty" @if(count($good_prices) > 0) style="display:none;" @endif>
                <td colspan="6" class="text-center text-muted">Tidak ada perubahan harga yang perlu diganti.</td>
              </tr>
            </tbody>
          </table>
          </div>
        </div>

      </section>
      <section class="col-lg-4 connectedSortable">

        <!-- Chat box -->
        <div class="box">
          <div class="box-header">
            <h2 class="box-title">Keuangan</h2>
          </div>
          <div class="box-body chat">
            <h4>Kas di tangan: {{ showRupiah($cash_account->balance + $cash_in->sum('debit') - $cash_out->sum('credit')) }}<br>
            Neraca: {{ showRupiah($total) }}</h4>
          </div>
        </div>

      </section>
    </div>
  </div>

  {{-- Form tersembunyi: dikirim ke halaman print di tab baru --}}
  <form id="gp-form" method="POST" action="{{ route('admin.print-display') }}" target="_blank" style="display:none;">
    {{ csrf_field() }}
  </form>

  {{-- Konfirmasi setelah print --}}
  <div class="modal fade" id="gp-modal" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title"><i class="fa fa-print"></i> Label sudah dicetak?</h4>
        </div>
        <div class="modal-body">
          Halaman print dibuka di tab baru. Setelah label berhasil dicetak, barang yang dipilih
          (<strong id="gp-modal-count">0</strong>) akan ditandai <em>sudah diganti</em> dan hilang dari daftar.
          <div id="gp-modal-error" class="text-red" style="margin-top:10px; display:none;"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default pull-left" id="gp-modal-no">Belum, simpan di daftar</button>
          <button type="button" class="btn btn-success" id="gp-modal-yes"><i class="fa fa-check"></i> Sudah, hilangkan dari daftar</button>
        </div>
      </div>
    </div>
  </div>

@endsection

@section('js-addon')
  <script type="text/javascript">
    (function () {
      var CHECKED_URL = "{!! url('admin/good-price') !!}";   // + /{id}/checked  (route yang sama dengan ikon centang)
      var pendingRows = [];

      function selectedRows() { return $('#gp-table .gp-row').filter(function () { return $(this).find('.gp-check').is(':checked'); }); }

      function refresh() {
        var $sel = selectedRows();
        var all = $('#gp-table .gp-row').length;

        // barang yang sama (beda satuan) hanya dihitung 1 karena print otomatis mencakup semua satuan
        var goods = {};
        $sel.each(function () { goods[$(this).data('good-id')] = true; });
        var n = Object.keys(goods).length;

        $('#gp-count').text(n);
        $('#gp-print').prop('disabled', n === 0);
        $('#gp-table .gp-row').each(function () { $(this).toggleClass('gp-selected', $(this).find('.gp-check').is(':checked')); });
        $('#gp-check-all').prop('checked', all > 0 && $sel.length === all).prop('disabled', all === 0);
        $('.gp-empty').toggle(all === 0);

        var info = 'Belum ada yang dipilih';
        if (n > 0) {
          info = n + ' barang dipilih';
          if ($('input[name="gp_type"]:checked').val() === 'rack') {
            var per = parseInt($('#gp_layout').val(), 10);
            info += ' (' + Math.ceil(n / per) + ' halaman A4)';
          }
        }
        $('#gp-info').text(info);
      }

      function syncType() {
        $('#gp-layout-wrap').toggle($('input[name="gp_type"]:checked').val() === 'rack');
        refresh();
      }

      function submitPrint() {
        var $sel = selectedRows();
        if (!$sel.length) return;

        var $form = $('#gp-form');
        $form.find('input:not([name="_token"])').remove();

        var type = $('input[name="gp_type"]:checked').val();
        $form.append($('<input type="hidden" name="type">').val(type));
        if (type === 'rack') { $form.append($('<input type="hidden" name="layout">').val($('#gp_layout').val())); }

        // 1 barang = 1 id (print otomatis mencakup semua satuan)
        var seen = {};
        $sel.each(function () {
          var gid = $(this).data('good-id');
          if (seen[gid]) return;
          seen[gid] = true;
          $form.append($('<input type="hidden" name="ids[]">').val($(this).data('good-unit-id')));
          $form.append($('<input type="hidden" name="names[]">').val($(this).data('name')));
          $form.append($('<input type="hidden" name="quantities[]">').val(1));
        });

        pendingRows = $sel.toArray();
        $form[0].submit();                         // membuka halaman print di tab baru

        $('#gp-modal-count').text(Object.keys(seen).length + ' barang');
        $('#gp-modal-error').hide().empty();
        $('#gp-modal-yes').prop('disabled', false).html('<i class="fa fa-check"></i> Sudah, hilangkan dari daftar');
        $('#gp-modal').modal('show');
      }

      function markDone() {
        var failed = 0, remaining = pendingRows.length;
        $('#gp-modal-yes').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Memproses...');

        $.each(pendingRows, function (_, row) {
          var $r = $(row);
          $.ajax({ url: CHECKED_URL + '/' + $r.data('price-id') + '/checked', type: 'GET', cache: false })
            .done(function () { $r.remove(); })
            .fail(function () { failed++; })
            .always(function () {
              remaining--;
              if (remaining > 0) return;
              refresh();
              if (failed === 0) {
                $('#gp-modal').modal('hide');
              } else {
                $('#gp-modal-error').text(failed + ' barang gagal ditandai. Silakan klik ikon centang manual pada barang tersebut.').show();
                $('#gp-modal-yes').prop('disabled', false).html('<i class="fa fa-check"></i> Coba lagi');
                pendingRows = $.grep(pendingRows, function (el) { return $.contains(document, el); });
              }
            });
        });
      }

      $(document).ready(function () {
        $('#gp-table').on('change', '.gp-check', refresh);
        $('#gp-table').on('click', '.gp-row td', function (e) {
          // klik di baris = centang/uncentang (kecuali klik pada link/ikon/checkbox itu sendiri)
          if ($(e.target).closest('a, input, button').length) return;
          var $c = $(this).closest('tr').find('.gp-check');
          $c.prop('checked', !$c.is(':checked')); refresh();
        });
        $('#gp-check-all').on('change', function () {
          $('#gp-table .gp-check').prop('checked', $(this).is(':checked')); refresh();
        });
        $('input[name="gp_type"]').on('change', syncType);
        $('#gp_layout').on('change', refresh);
        $('#gp-print').on('click', submitPrint);
        $('#gp-modal-yes').on('click', markDone);
        $('#gp-modal-no').on('click', function () { $('#gp-modal').modal('hide'); });
        syncType();
      });
    })();
  </script>
@endsection