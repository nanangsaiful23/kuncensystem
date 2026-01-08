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
              {!! Form::model(old(),array('url' => route('admin.storeScaleLedger', [$start_date, $end_date]), 'enctype'=>'multipart/form-data', 'method' => 'POST', 'class' => 'form-horizontal')) !!}
                <div class="col-sm-6">
                  {!! Form::submit("Simpan Ledger Neraca & Update Aset Distributor", ['class' => 'btn form-control'])  !!}<br>
                </div>
                <a href="{{ url('admin/scaleLedger/' . $start_date . '/' . $end_date . '/2101') }}" class="btn">Riwayat Ledger Neraca (query DB)</a>
                <hr>
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
                      {!! Form::hidden('account_ids[]', $activa_debits[$i]->id) !!}
                      {!! Form::hidden('initials[]', $activa_debits[$i]->balance) !!}
                      <?php $ongoing = $activa_debits[$i]->debit - $activa_credits[$i]->credit;
                            $current = $activa_debits[$i]->balance + $ongoing; ?>
                      {!! Form::hidden('ongoings[]', $ongoing) !!}
                      {!! Form::hidden('currents[]', $current) !!}
                      <td>{{ $activa_debits[$i]->code }}</td>
                      <td>{{ $activa_debits[$i]->name }}</td>
                      <td style="text-align: right;">{{ showRupiah($activa_debits[$i]->balance) }}</td>
                      <td style="text-align: right;">{{ showRupiah($ongoing) }}</td>
                      <td style="text-align: right;">{{ showRupiah($current) }}</td>
                    </tr>
                  @endfor
                  <tr style="font-weight: bold;">
                    <td></td>
                    <td></td>
                    {!! Form::hidden('account_ids[]', '-1') !!}
                    <?php $initial = $activa_debits->sum('balance');
                          $ongoing = $activa_debits->sum('debit') - $activa_credits->sum('credit');
                          $current = $activa_debits->sum('balance') + $activa_debits->sum('debit') - $activa_credits->sum('credit'); ?>
                    {!! Form::hidden('initials[]', $initial) !!}
                    {!! Form::hidden('ongoings[]', $ongoing) !!}
                    {!! Form::hidden('currents[]', $current) !!}
                    <td style="text-align: right;">{{ showRupiah($initial) }}</td>
                    <td style="text-align: right;">{{ showRupiah($ongoing) }}</td>
                    <td style="text-align: right;">{{ showRupiah($current) }}</td>
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
                      {!! Form::hidden('account_ids[]', $pasiva_debits[$i]->id) !!}
                      {!! Form::hidden('initials[]', $pasiva_debits[$i]->balance) !!}
                      <?php $ongoing = -1 * ($pasiva_debits[$i]->debit - $pasiva_credits[$i]->credit);
                            $current = $pasiva_debits[$i]->balance + $ongoing; ?>
                      {!! Form::hidden('ongoings[]', $ongoing) !!}
                      {!! Form::hidden('currents[]', $current) !!}
                      <td>{{ $pasiva_debits[$i]->code }}</td>
                      <td>{{ $pasiva_debits[$i]->name }}</td>
                      @if($pasiva_debits[$i]->code == '3002')
                        <td style="text-align: right;">{{ showRupiah($laba[0]) }}</td>
                        <td style="text-align: right;">{{ showRupiah($laba[1]) }}</td>
                        <td style="text-align: right;">{{ showRupiah($laba[2]) }}</td>
                      @else
                          <td style="text-align: right;">{{ showRupiah($pasiva_debits[$i]->balance) }}</td>
                          <td style="text-align: right;">{{ showRupiah($ongoing) }}</td>
                          <td style="text-align: right;">{{ showRupiah($current) }}</td>
                      @endif
                    </tr>
                  @endfor
                  <tr style="font-weight: bold;">
                    <td></td>
                    <td></td>
                    {!! Form::hidden('account_ids[]', '-2') !!}
                    <?php $initial = $pasiva_debits->sum('balance') + $laba[0];
                          $ongoing = -1 * ($pasiva_debits->sum('debit') - ($pasiva_credits->sum('credit') + $laba[1]));
                          $current = $initial + $ongoing; ?>
                    {!! Form::hidden('initials[]', $initial) !!}
                    {!! Form::hidden('ongoings[]', $ongoing) !!}
                    {!! Form::hidden('currents[]', $current) !!}
                    <td style="text-align: right;">{{ showRupiah($initial) }}</td>
                    <td style="text-align: right;">{{ showRupiah($ongoing) }}</td>
                    <td style="text-align: right;">{{ showRupiah($current) }}</td>
                  </tr>
                  <tr>
                    <td colspan="5"></td>
                  </tr>
                </tbody>
              </table>
              {!! Form::close() !!} 
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