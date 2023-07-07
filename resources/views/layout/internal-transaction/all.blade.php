<div class="content-wrapper">

  @include('layout' . '.alert-message', ['type' => $default['type'], 'data' => $default['data'], 'color' => $default['color']])

  <section class="content">
    <div class="row">
      <div class="col-xs-12">
        <div class="box">
          <div class="box-header">
            <h3 class="box-title">Daftar transaksi</h3>
            <!-- @include('layout.search-form') -->
          </div>
          <div class="box-body">
            {!! Form::label('show', 'Show', array('class' => 'col-sm-1 control-label')) !!}
           <div class="col-sm-1">
              {!! Form::select('show', getPaginations(), $pagination, ['class' => 'form-control', 'style'=>'width: 100%', 'id' => 'show', 'onchange' => 'advanceSearch()']) !!}
            </div>
            {!! Form::label('user_id', 'PIC', array('class' => 'col-sm-1 control-label')) !!}
           <div class="col-sm-2">
              {!! Form::select('user_id', getUsers(), $role_user . '/' . $role_id, ['class' => 'form-control select2', 'style'=>'width: 100%', 'id' => 'user_id', 'onchange' => 'advanceSearch()']) !!}
            </div>
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
          <div class="box-body" style="overflow-x:scroll; background-color: #E5F9DB">
            <h3>Transaksi</h3><br>
            <h4>Total transaksi: {{ showRupiah($transactions->sum('total_sum_price')) }}</h4>
            <h4>Total potongan: {{ showRupiah($transactions->sum('total_discount_price')) }}</h4><br>
          </div>
          <div class="box-body" style="overflow-x:scroll; background-color: #E5F9DB">
            <table id="example1" class="table table-bordered table-striped">
              <thead>
              <tr>
                <th>Tipe</th>
                <th>Waktu</th>
                @if(\Auth::user()->email == 'admin')
                  <th>Kasir</th>
                @endif
                <th>Total Belanja</th>
                <th>Total Diskon</th>
                <th>Potongan Akhir</th>
                <th>Total Akhir</th>
                <th>Uang Dibayar</th>
                <th>Kembalian</th>
                <th class="center">Detail</th>
              </tr>
              </thead>
              <tbody id="table-good">
                @foreach($transactions as $transaction)
                  <tr>
                    <td>{{ $transaction->type_name()->code . ' - ' . $transaction->type_name()->name }}</td>
                    <td>{{ $transaction->created_at }}</td>
                    @if(\Auth::user()->email == 'admin')
                      <td>{{ $transaction->actor()->name }}</td>
                    @endif
                    <td>{{ showRupiah($transaction->total_item_price) }}</td>
                    <td>{{ showRupiah(checkNull($transaction->details->sum('discount_price'))) }}</td>
                    <td>{{ showRupiah($transaction->total_discount_price) }}</td>
                    <td>{{ showRupiah($transaction->total_sum_price) }}</td>
                    <td>{{ showRupiah($transaction->money_paid) }}</td>
                    <td>{{ showRupiah($transaction->money_returned) }}</td>
                    <td class="center"><a href="{{ url($role . '/transaction/' . $transaction->id . '/detail') }}"><i class="fa fa-hand-o-right tosca" aria-hidden="true"></i></a></td>
                  </tr>
                @endforeach
              </tbody>
              <div id="renderField">
                @if($pagination != 'all')
                  {{ $transactions->render() }}
                @endif
              </div>
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
      window.location = window.location.origin + '/{{ $role }}/internal-transaction/{{ $role_user }}/{{ $role_id }}/' + $("#datepicker").val() + '/' + $("#datepicker2").val() + '/{{ $pagination }}';
    }

    function advanceSearch()
    {
      var show        = $('#show').val();
      var user_id     = $('#user_id').val();
      window.location = window.location.origin + '/{{ $role }}/internal-transaction/' + user_id + '/{{ $start_date }}/{{ $end_date }}/' + show;
    }
  </script>
@endsection
