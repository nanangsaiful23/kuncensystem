@extends('layout.user', ['role' => 'cashier', 'title' => 'Cashier'])

@section('content')

  <div class="content-wrapper">
    <section class="content-header">
      <h1>
        Hi, {{ \Auth::user()->name }}<br>
        Selamat datang di aplikasi {{ config('app.name') }}<br>
        <small>Anda login sebagai Kasir</small>
      </h1>
    </section>
    <div class="row">
      <div class="col-xs-12">
        <div class="box">
          <div class="box-body" style="overflow-x:scroll;">
            <h2>Rincian Transaksi {{ displayDate(date('Y-m-d')) }}</h2>
            <div class="col-sm-4">
              <table style="font-size: 25px; padding: 5px;">
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
            <div class="col-sm-4">
              <table style="font-size: 25px; padding: 5px;">
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
      </div>
      <div class="col-xs-12">
        <div class="box">
          <div class="box-body" style="overflow-x:scroll;">
            <h2>Daftar Harga Naik</h2>
            <h5>(jika harga display sudah diganti, mohon dichecklist)</h5>
            <table class="table table-bordered table-striped">
              <thead>
                <tr>
                  <th>Nama Barang</th>
                  <th>Harga Lama</th>
                  <th>Harga Baru</th>
                  <th>Sudah Diganti</th>
                </tr>
              </thead>
              <tbody>
                @foreach($good_prices as $price)
                  <tr>
                    <td>{{ $price->good_unit->good->name . ' ' . $price->good_unit->unit->name}}</td>
                    <td>{{ showRupiah($price->old_price) }}</td>
                    <td>{{ showRupiah($price->recent_price) }}</td>
                    <td><a href="{{ url('cashier/good-price/' . $price->id . '/checked') }}"><i class="fa fa-check orange" aria-hidden="true"></i></a></td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

@endsection
