@extends('layout.user', ['role' => 'admin', 'title' => 'Admin'])

@section('content')
  <style type="text/css">
    table, th, td
    {
      border: solid 2px black !important;
    }
  </style>

  <div class="content-wrapper">
    <section class="content">
      <div class="row">
        <div class="col-xs-12">
          <div class="box">
            <div class="box-header">
              <h3 class="box-title">Laba Rugi</h3>
            </div>
            <div class="box-body" style="overflow-x:scroll; color: black !important">
              <table id="example1" class="table table-bordered table-striped">
                <thead>
                <tr>
                  <th width="10%">Kode</th>
                  <th>Nama</th>
                  <th>Awal</th>
                  <th>Berjalan</th>
                  <th>Akhir</th>
                </tr>
                </thead>
                <tbody id="table-good">
                  <tr>
                    <td>4101</td>
                    <td>Penjualan</td>
                    <td style="text-align: right;">{{ showRupiah($penjualan_account->balance) }}</td>
                    <td style="text-align: right;">{{ showRupiah($penjualan->sum('credit')) }}</td>
                    <td style="text-align: right;">{{ showRupiah($penjualan_account->balance + $penjualan->sum('credit')) }}</td>
                  </tr>
                  <tr>
                    <td>5101</td>
                    <td>Harga Penjualan Pokok</td>
                    <td style="text-align: right;">{{ showRupiah($hpp_account->balance) }}</td>
                    <td style="text-align: right;">{{ showRupiah($hpp->sum('debit')) }}</td>
                    <td style="text-align: right;">{{ showRupiah($hpp_account->balance + $hpp->sum('debit')) }}</td>
                  </tr>
                  <tr style="font-weight: bold;">
                    <td></td>
                    <td>Laba (rugi) kotor</td>
                    <td style="text-align: right;">{{ showRupiah($penjualan_account->balance - $hpp_account->balance) }}</td>
                    <td style="text-align: right;">{{ showRupiah($penjualan->sum('credit') - $hpp->sum('debit')) }}</td>
                    <td style="text-align: right;">{{ showRupiah(($penjualan_account->balance + $penjualan->sum('credit')) - ($hpp_account->balance + $hpp->sum('debit'))) }}</td>
                  </tr>
                  <tr>
                    <td colspan="5"></td>
                  </tr>
                  @foreach($payments as $payment)
                    <tr>
                      <td>{{ $payment->code }}</td>
                      <td>{{ $payment->name }}</td>
                      <td style="text-align: right;">{{ showRupiah($payment->balance) }}</td>
                      <td style="text-align: right;">{{ showRupiah($payment->debit) }}</td>
                      <td style="text-align: right;">{{ showRupiah($payment->balance + $payment->debit) }}</td>
                    </tr>
                  @endforeach
                  <tr style="font-weight: bold;">
                    <td></td>
                    <td>Jumlah Biaya Usaha</td>
                    <td style="text-align: right;">{{ showRupiah($payments->sum('balance')) }}</td>
                    <td style="text-align: right;">{{ showRupiah($payments->sum('debit')) }}</td>
                    <td style="text-align: right;">{{ showRupiah($payments->sum('balance') + $payments->sum('debit')) }}</td>
                  </tr>
                  <tr>
                    <td colspan="5"></td>
                  </tr>
                  <tr style="font-weight: bold;">
                    <td></td>
                    <td>Laba Bersih Usaha</td>
                    <td style="text-align: right;">{{ showRupiah(($penjualan_account->balance - $hpp_account->balance) - $payments->sum('balance')) }}</td>
                    <td style="text-align: right;">{{ showRupiah(($penjualan->sum('credit') - $hpp->sum('debit')) - $payments->sum('debit')) }}</td>
                    <td style="text-align: right;">{{ showRupiah((($penjualan_account->balance + $penjualan->sum('credit')) - ($hpp_account->balance + $hpp->sum('debit'))) - ($payments->sum('balance') + $payments->sum('debit'))) }}</td>
                  </tr>
                  <tr>
                    <td colspan="5"></td>
                  </tr>
                  <tr style="font-weight: bold;">
                    <td>6101</td>
                    <td>Pendapatan lain-lain</td>
                    <td style="text-align: right;">{{ showRupiah($other_incomes[0]->balance) }}</td>
                    <td style="text-align: right;">{{ showRupiah($other_incomes[0]->credit) }}</td>
                    <td style="text-align: right;">{{ showRupiah($other_incomes[0]->balance + $other_incomes[0]->credit) }}</td>
                  </tr>
                  <tr style="font-weight: bold;">
                    <td>6102</td>
                    <td>Biaya lain-lain</td>
                    <td style="text-align: right;">{{ showRupiah($other_outcomes[0]->balance) }}</td>
                    <td style="text-align: right;">{{ showRupiah($other_outcomes[0]->debit) }}</td>
                    <td style="text-align: right;">{{ showRupiah($other_outcomes[0]->balance + $other_outcomes[0]->debit) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>

  @section('js-addon')
    <script type="text/javascript">
      $(document).ready(function(){
        $('#datepicker').datepicker({
          autoclose: true,
          format: 'yyyy-mm-dd'
        })

        $('#datepicker2').datepicker({
          autoclose: true,
          format: 'yyyy-mm-dd'
        })
        
          $("#search-input").keyup( function(e){
            if(e.keyCode == 13)
            {
              ajaxFunction();
            }
          });

          $("#search-btn").click(function(){
              ajaxFunction();
          });

      });
    </script>
  @endsection
@endsection