<div class="content-wrapper">

  @include('layout' . '.alert-message', ['type' => $default['type'], 'data' => $default['data'], 'color' => $default['color']])

  <section class="content">
    <div class="row">
      <div class="col-xs-12">
        <div class="box">
          <div class="box-header">
            <h3 class="box-title">{{ $default['page_name'] }}</h3>
            <!-- @include('layout.search-form') -->
          </div>
          <div class="box-body">
            {!! Form::label('show', 'Show', array('class' => 'col-sm-1 control-label')) !!}
           <div class="col-sm-1">
              {!! Form::select('show', getPaginations(), $pagination, ['class' => 'form-control', 'style'=>'width: 100%', 'id' => 'show', 'onchange' => 'advanceSearch()']) !!}
            </div>
            @if($role == 'admin')
              {!! Form::label('user_id', 'PIC', array('class' => 'col-sm-1 control-label')) !!}
              <div class="col-sm-2">
                {!! Form::select('user_id', getUsers(), $role_user . '/' . $role_id, ['class' => 'form-control select2', 'style'=>'width: 100%', 'id' => 'user_id', 'onchange' => 'advanceSearch()']) !!}
              </div>
            @endif
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
          <div class="box-body" style="font-size: 16px">
            <table>
              <tr>
                <td>Total transaksi</td>
                <td>:</td>
                <td style="text-align: right;">{{ showRupiah($all_normal->sum('total_item_price') + $all_retur->sum('total_item_price')) }}</td>
              </tr>
              <tr>
                <td>Total potongan</td>
                <td>:</td>
                <td style="text-align: right;">{{ showRupiah($all_normal->sum('total_discount_price') + $all_retur->sum('total_discount_price')) }}</td>
              </tr>
              <tr>
                <td>Total transaksi setelah potongan</td>
                <td>:</td>
                <td style="text-align: right;">{{ showRupiah($all_normal->sum('total_sum_price') + $all_retur->sum('total_sum_price')) }}</td>
              </tr>
              @if(\Auth::user()->email == 'admin')
                <tr>
                  <td>HPP</td>
                  <td>:</td>
                  <td style="text-align: right;">{{ showRupiah($hpp_normal->sum('total') + $hpp_retur_normal->sum('total') - $hpp_retur->sum('total')) }}</td>
                </tr>
                <tr>
                  <td>Untung</td>
                  <td>:</td>
                  <td style="text-align: right;">{{ showRupiah($all_normal->sum('total_sum_price') + $all_retur->sum('total_sum_price') - ($hpp_normal->sum('total') + $hpp_retur_normal->sum('total') - $hpp_retur->sum('total'))) }}</td>
                </tr>
              @endif
            </table>
          </div>

          @include('layout.transaction.all-form', ['name' => 'Lunas', 'transactions' => $transactions['cash'], 'color' => '#E5F9DB', 'total_sum_price' => $transactions['cash']->sum('total_sum_price'), 'total_discount_price' => $transactions['cash']->sum('total_discount_price'), 'discount_price' => $transactions['cash']->sum('discount_price')])

          @if(sizeof($transactions['credit']) > 0)
           @include('layout.transaction.all-form', ['name' => 'Hutang', 'transactions' => $transactions['credit'], 'color' => '#FFD0D0', 'total_sum_price' => $transactions['credit']->sum('total_sum_price'), 'total_discount_price' => $transactions['credit']->sum('total_discount_price'), 'discount_price' => $transactions['credit']->sum('discount_price')])
          @endif
          @if(sizeof($transactions['transfer']) > 0)
           @include('layout.transaction.all-form', ['name' => 'Transfer', 'transactions' => $transactions['transfer'], 'color' => '#9AC5F4', 'total_sum_price' => $transactions['transfer']->sum('total_sum_price'), 'total_discount_price' => $transactions['transfer']->sum('total_discount_price'), 'discount_price' => $transactions['transfer']->sum('discount_price')])
          @endif
          @if(sizeof($transactions['credit_transfer']) > 0)
           @include('layout.transaction.all-form', ['name' => 'Hutang Transfer', 'transactions' => $transactions['credit_transfer'], 'color' => '#EA906C', 'total_sum_price' => $transactions['credit_transfer']->sum('total_sum_price'), 'total_discount_price' => $transactions['credit_transfer']->sum('total_discount_price'), 'discount_price' => $transactions['credit_transfer']->sum('discount_price')])
          @endif
          @if(sizeof($transactions['retur']) > 0)
           @include('layout.transaction.all-form', ['name' => 'Retur', 'transactions' => $transactions['retur'], 'color' => '#FFF3E2', 'total_sum_price' => $transactions['retur']->sum('total_sum_price'), 'total_discount_price' => $transactions['retur']->sum('total_discount_price'), 'discount_price' => $transactions['retur']->sum('discount_price')])
          @endif
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

    function changeDate()
    {
      window.location = window.location.origin + '/{{ $role }}/transaction/{{ $role_user }}/{{ $role_id }}/' + $("#datepicker").val() + '/' + $("#datepicker2").val() + '/{{ $pagination }}';
    }

    function advanceSearch()
    {
      var show        = $('#show').val();
      var user_id     = $('#user_id').val();

      @if($role == 'cashier')
        user_id = 'all/all';
      @endif
      window.location = window.location.origin + '/{{ $role }}/transaction/' + user_id + '/{{ $start_date }}/{{ $end_date }}/' + show;
    }

    function highlight(id)
    {
      if($("#" + id).prop('checked') == true)
        $('#div-' + id).css('background-color', "{{ config('app.app_color') }}");
      else
        $('#div-' + id).css('background-color', "white");
    }
  </script>
@endsection
