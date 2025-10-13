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
                    <td style="text-align: right;">{{ showRupiah($penjualan_credit->sum('credit') - $penjualan_debit->sum('debit')) }}</td>
                    <td style="text-align: right;">{{ showRupiah($penjualan_account->balance + $penjualan_credit->sum('credit') - $penjualan_debit->sum('debit')) }}</td>
                  </tr>
                  <tr>
                    <td>5101</td>
                    <td>Harga Penjualan Pokok</td>
                    <td style="text-align: right;">{{ showRupiah($hpp_account->balance) }}</td>
                    <td style="text-align: right;">{{ showRupiah($hpp_debit->sum('debit') - $hpp_credit->sum('credit')) }}</td>
                    <td style="text-align: right;">{{ showRupiah($hpp_account->balance + $hpp_debit->sum('debit') - $hpp_credit->sum('credit')) }}</td>
                  </tr>
                  <tr style="font-weight: bold;">
                    <td></td>
                    <td>Laba (rugi) kotor</td>
                    <td style="text-align: right;">{{ showRupiah($penjualan_account->balance - $hpp_account->balance) }}</td>
                    <td style="text-align: right;">{{ showRupiah(($penjualan_credit->sum('credit') - $penjualan_debit->sum('debit')) - ($hpp_debit->sum('debit') - $hpp_credit->sum('credit'))) }}</td>
                    <td style="text-align: right;">{{ showRupiah($penjualan_account->balance + $hpp_account->balance + ($penjualan_credit->sum('credit') - $penjualan_debit->sum('debit')) - ($hpp_debit->sum('debit') - $hpp_credit->sum('credit'))) }}</td>
                  </tr>
                  <tr>
                    <td colspan="5"></td>
                  </tr>
                  @for($i = 0; $i < sizeof($payment_ins); $i++)
                    <tr>
                      <td>{{ $payment_ins[$i]->code }}</td>
                      <td>{{ $payment_ins[$i]->name }}</td>
                      <td style="text-align: right;">{{ showRupiah($payment_ins[$i]->balance) }}</td>
                      <td style="text-align: right;">{{ showRupiah($payment_ins[$i]->debit - $payment_outs[$i]->credit) }}</td>
                      <td style="text-align: right;">{{ showRupiah($payment_ins[$i]->balance + $payment_ins[$i]->debit - $payment_outs[$i]->credit) }}</td>
                    </tr>
                  @endfor
                  <tr style="font-weight: bold;">
                    <td></td>
                    <td>Jumlah Biaya Usaha</td>
                    <td style="text-align: right;">{{ showRupiah($payment_ins->sum('balance')) }}</td>
                    <td style="text-align: right;">{{ showRupiah($payment_ins->sum('debit') - $payment_outs->sum('credit')) }}</td>
                    <td style="text-align: right;">{{ showRupiah($payment_ins->sum('balance') + $payment_ins->sum('debit') - $payment_outs->sum('credit')) }}</td>
                  </tr>
                  <tr>
                    <td colspan="5"></td>
                  </tr>
                  <tr>
                    <td colspan="5"></td>
                  </tr>
                  @for($i = 0; $i < sizeof($other_debits); $i++)
                    <tr style="font-weight: bold;">
                      <td>{{ $other_debits[$i]->code }}</td>
                      <td>{{ $other_debits[$i]->name }}</td>
                      <td style="text-align: right;">{{ showRupiah($other_debits[$i]->balance) }}</td>
                    @if($other_debits[$i]->code == '6101')
                      <td style="text-align: right;">{{ showRupiah($other_credits[$i]->sum('credit') - $other_debits[$i]->sum('debit')) }}</td>
                      <td style="text-align: right;">{{ showRupiah($other_debits[$i]->balance + $other_credits[$i]->sum('credit') - $other_debits[$i]->sum('debit')) }}</td>
                    @else
                      <td style="text-align: right;">{{ showRupiah($other_debits[$i]->sum('debit') - $other_credits[$i]->sum('credit')) }}</td>
                      <td style="text-align: right;">{{ showRupiah($other_debits[$i]->balance + $other_debits[$i]->sum('debit') - $other_credits[$i]->sum('credit')) }}</td>
                    @endif
                    </tr>
                  @endfor
                  <tr style="font-weight: bold; background-color: yellow;">
                    <td>-</td>
                    <td>Total Akhir</td>
                    <td style="text-align: right;">{{ showRupiah(($penjualan_account->balance - $hpp_account->balance) - $payment_ins->sum('balance') + $other_income_debits[0]->balance - $other_outcome_debits[0]->balance) }}</td>
                    <td style="text-align: right;">{{ showRupiah(($penjualan_credit->sum('credit') - $penjualan_debit->sum('debit')) - ($hpp_debit->sum('debit') - $hpp_credit->sum('credit')) - ($payment_ins->sum('debit') - $payment_outs->sum('credit')) + ($other_income_credits->sum('credit') - $other_income_debits->sum('debit')) - ($other_outcome_debits->sum('debit') - $other_outcome_credits->sum('credit'))) }}</td>
                    <td style="text-align: right;">{{ showRupiah(($penjualan_account->balance + $hpp_account->balance + ($penjualan_credit->sum('credit') - $penjualan_debit->sum('debit')) - ($hpp_debit->sum('debit') - $hpp_credit->sum('credit')) - ($payment_ins->sum('balance') + $payment_ins->sum('debit') - $payment_outs->sum('credit'))) + ($other_income_debits[0]->balance + $other_income_credits->sum('credit') - $other_income_debits->sum('debit')) - ($other_outcome_debits[0]->balance + $other_outcome_debits->sum('debit') - $other_outcome_credits->sum('credit'))) }}</td>
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