@extends('layout.user', ['role' => 'admin', 'title' => 'Admin'])

@section('content')

<style type="text/css">
  .box
  {
    border: solid 2px white;
    border-radius: 10px;
  }
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
          <table class="table table-bordered table-striped">
            <thead>
              <tr>
                <th>Nama Barang</th>
                <th>Harga Lama</th>
                <th>Harga Baru</th>
                <th>Tanggal Perubahan</th>
                <th>Sudah Diganti</th>
              </tr>
            </thead>
            <tbody>
              @foreach($good_prices as $price)
                <tr>
                  <td>{{ $price->good_unit->good->name . ' ' . $price->good_unit->unit->name}}</td>
                  <td>{{ showRupiah($price->old_price) }}</td>
                  <td>{{ showRupiah($price->recent_price) }}</td>
                  <td>{{ displayDate($price->created_at) }}</td>
                  <td><a href="{{ url('admin/good-price/' . $price->id . '/checked') }}"><i class="fa fa-check orange" aria-hidden="true"></i></a></td>
                </tr>
              @endforeach
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

@endsection
