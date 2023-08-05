<div class="content-wrapper">

  @include('layout' . '.alert-message', ['type' => $default['type'], 'data' => $default['data'], 'color' => $default['color']])

  <section class="content">
    <div class="row">
      <div class="col-xs-12">
        <div class="box">
          <div class="box-header">
            <h3 class="box-title">{{ $default['page_name'] }}</h3>
          </div>
          <div class="box-body">
            {!! Form::label('start_date', 'Tanggal Awal', array('class' => 'col-sm-1 control-label')) !!}
            <div class="col-sm-2">
              <div class="input-group date">
                <input type="text" class="form-control pull-right" id="datepicker" name="start_date" value="{{ $start_date }}" onchange="changeDate()">
              </div>
            </div>
            {!! Form::label('end_date', 'Tanggal Akhir', array('class' => 'col-sm-1 control-label')) !!}
            <div class="col-sm-2">
              <div class="input-group date">
                <input type="text" class="form-control pull-right" id="datepicker2" name="end_date" value="{{ $end_date }}" onchange="changeDate()">
              </div>
            </div>
          </div>
          <div class="box-body" style="overflow-x:scroll;">
            <h2>Total transaksi akhir: {{ showRupiah($transactions['normal']->sum('total_sum_price') + $transactions['retur']->sum('total_sum_price') + $transactions['not_valid']->sum('total_sum_price') + $transactions['internal']->sum('total_sum_price') + $transactions['other_transaction']->sum('debit') + $transactions['other_payment']->sum('debit')) }}</h2>
            <h3>Total transaksi (normal + retur): {{ showRupiah($transactions['normal']->sum('total_sum_price') + $transactions['retur']->sum('total_sum_price')) }}</h3>
            <h3>Total transaksi double/tidak valid: {{ showRupiah($transactions['not_valid']->sum('total_sum_price')) }}</h3>
            <h3>Total transaksi internal: {{ showRupiah($transactions['internal']->sum('total_sum_price')) }}</h3>
            <h3>Total transaksi lain: {{ showRupiah($transactions['other_transaction']->sum('debit')) }}</h3>
            <h3>Total biaya lain: {{ showRupiah($transactions['other_payment']->sum('debit')) }}</h3>
          </div>
          <div class="box-body" style="overflow-x:scroll;">
            {!! Form::model(old(),array('url' => route($role . '.transaction.storeMoney'), 'enctype'=>'multipart/form-data', 'method' => 'POST', 'class' => 'form-horizontal')) !!}
              <div class="form-group">
                {!! Form::label('money', 'Pengambilan Uang', array('class' => 'col-sm-12')) !!}
                <div class="col-sm-5">
                  {!! Form::text('money', null, array('class' => 'form-control', 'onchange' => 'changeBalance()', 'id' => 'money', 'onkeyup' => 'formatNumber("money")')) !!}
                </div>
              </div>
              <div class="col-sm-5">
                {!! Form::submit('Simpan', ['class' => 'btn btn-success btn-flat btn-block form-control col-sm-5'])  !!}
              </div>
            {!! Form::close() !!}
            <div class="form-group">
              <div class="col-sm-12">
                <h3 id="balance"></h3>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

@section('js-addon')
  <script type="text/javascript">
    $(document).ready(function(){
      $('.select2').select2();
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

    function formatNumber(name)
    {
        num = document.getElementById(name).value;
        num = num.toString().replace(/,/g,'');
        document.getElementById(name).value = num.toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,');
    }

    function unFormatNumber(num)
    {
        return num.replace(/,/g,'');
    }

    function changeDate()
    {
      window.location = window.location.origin + '/admin/transaction/resumeTotal/' + $("#datepicker").val() + '/' + $("#datepicker2").val();
    }

    function changeBalance()
    {
      saldo = unFormatNumber($("#money").val());
      total_money = '{{ $transactions['normal']->sum('total_sum_price') + $transactions['retur']->sum('total_sum_price') }}';
      balance = total_money - saldo;
      $("#balance").html("Sisa uang kasir: " + balance.toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,'));
    }
  </script>
@endsection
