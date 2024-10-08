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
              <h3 class="box-title">Neraca</h3>
              <h4>{{ showRupiah($total) }}</h4>
            </div>
            <div class="box-body">
              {!! Form::label('start_date', 'Tanggal Awal', array('class' => 'col-sm-2 control-label')) !!}
              <div class="col-sm-2">
                <div class="input-group date">
                  <input type="text" class="form-control pull-right" id="datepicker" name="start_date" value="{{ $start_date }}" onchange="changeDate()">
                </div>
              </div>
              {!! Form::label('end_date', 'Tanggal Akhir', array('class' => 'col-sm-2 control-label')) !!}
              <div class="col-sm-2">
                <div class="input-group date">
                  <input type="text" class="form-control pull-right" id="datepicker2" name="end_date" value="{{ $end_date }}" onchange="changeDate()">
                </div>
              </div>
            </div>
            <div class="box-body" style="overflow-x:scroll; color: black !important">
              <table id="example1" class="table table-bordered table-striped">
                <thead>
                <tr>
                  <th width="10%">AKTIVA</th>
                  <th width="30%">Nama</th>
                  <th width="20%">Awal</th>
                  <th width="20%">Berjalan</th>
                  <th width="20%">Akhir</th>
                </tr>
                </thead>
                <tbody id="table-good">
                  @for($i = 0; $i < sizeof($activa_debits); $i++)
                    <tr>
                      <td>{{ $activa_debits[$i]->code }}</td>
                      <td>{{ $activa_debits[$i]->name }}</td>
                      <td style="text-align: right;">{{ showRupiah($activa_debits[$i]->balance) }}</td>
                      <td style="text-align: right;">{{ showRupiah($activa_debits[$i]->debit - $activa_credits[$i]->credit) }}</td>
                      <td style="text-align: right;">{{ showRupiah($activa_debits[$i]->balance + $activa_debits[$i]->debit - $activa_credits[$i]->credit) }}</td>
                    </tr>
                  @endfor
                  <tr style="font-weight: bold;">
                    <td></td>
                    <td></td>
                    <td style="text-align: right;">{{ showRupiah($activa_debits->sum('balance')) }}</td>
                    <td style="text-align: right;">{{ showRupiah($activa_debits->sum('debit') - $activa_credits->sum('credit')) }}</td>
                    <td style="text-align: right;">{{ showRupiah($activa_debits->sum('balance') + $activa_debits->sum('debit') - $activa_credits->sum('credit')) }}</td>
                  </tr>
                  <tr>
                    <td colspan="5"></td>
                  </tr>
                </tbody>
              </table>
              <table id="example2" class="table table-bordered table-striped">
                <thead>
                <tr>
                  <th width="10%">PASIVA</th>
                  <th width="30%">Nama</th>
                  <th width="20%">Awal</th>
                  <th width="20%">Berjalan</th>
                  <th width="20%">Akhir</th>
                </tr>
                </thead>
                <tbody id="table-good">
                  @for($i = 0; $i < sizeof($pasiva_debits); $i++)
                    <tr>
                      <td>{{ $pasiva_debits[$i]->code }}</td>
                      <td>{{ $pasiva_debits[$i]->name }}</td>
                      <td style="text-align: right;">{{ showRupiah($pasiva_debits[$i]->balance) }}</td>
                      <td style="text-align: right;">{{ showRupiah(-1 * ($pasiva_debits[$i]->debit - $pasiva_credits[$i]->credit)) }}</td>
                      <td style="text-align: right;">{{ showRupiah($pasiva_debits[$i]->balance + (-1 * ($pasiva_debits[$i]->debit - $pasiva_credits[$i]->credit))) }}</td>
                    </tr>
                  @endfor
                  <tr style="font-weight: bold;">
                    <td></td>
                    <td></td>
                    <td style="text-align: right;">{{ showRupiah($pasiva_debits->sum('balance')) }}</td>
                    <td style="text-align: right;">{{ showRupiah(-1 * ($pasiva_debits->sum('debit') - $pasiva_credits->sum('credit'))) }}</td>
                    <td style="text-align: right;">{{ showRupiah($pasiva_debits->sum('balance') + (-1 * ($pasiva_debits->sum('debit') - $pasiva_credits->sum('credit')))) }}</td>
                  </tr>
                  <tr>
                    <td colspan="5"></td>
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

      function changeDate()
      {
        window.location = window.location.origin + '/admin/scale/' + $("#datepicker").val() + '/' + $("#datepicker2").val();
      }
    </script>
  @endsection
@endsection