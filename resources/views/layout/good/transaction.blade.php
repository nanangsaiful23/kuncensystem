<div class="content-wrapper">

  @include('layout' . '.alert-message', ['type' => $default['type'], 'data' => $default['data'], 'color' => $default['color']])

  <section class="content">
    <div class="row">
      <div class="col-xs-12">
        <div class="box">
          <div class="box-header">
            <h3 class="box-title">{{ $default['page_name'] . ' ' . $good->name }}</h3>
            <!-- @include('layout.search-form') -->
          </div>
          <div class="box-body">
            {!! Form::label('show', 'Show', array('class' => 'col-sm-1 control-label')) !!}
           <div class="col-sm-1">
              {!! Form::select('show', getPaginations(), $pagination, ['class' => 'form-control', 'style'=>'width: 100%', 'id' => 'show', 'onchange' => 'advanceSearch()']) !!}
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
          <div class="box-body" style="overflow-x:scroll">
            <h4>Total penjualan: {{ $transactions->sum('real_quantity') }}</h4><br>
          </div>
          <div class="box-body" style="overflow-x:scroll">
            <table id="example1" class="table table-bordered table-striped">
              <thead>
              <tr>
                <th>Created at</th>
                <th>ID</th>
                <th>Tipe</th>
                <th>PIC</th>
                <th>Jumlah</th>
                <th>Unit</th>
                <th>Jumlah Real</th>
                <th>Stock Terakhir</th>
                @if(\Auth::user()->email == 'admin')
                  <th>Harga Beli</th>
                @endif
                <th>Harga Jual</th>
                <th>Harga Jual Total</th>
                <th>Total Diskon</th>
                <th>Harga Jual Setelah Diskon</th>
                <th>Total Akhir</th>
              </tr>
              </thead>
              <tbody id="table-good">
                @foreach($transactions as $transaction)
                  <tr>
                    <td>{{ $transaction->created_at }}</td>
                    <td><a href="{{ url($role . '/transaction/' . $transaction->transaction->id . '/detail') }}" class="btn" target="_blank">{{ $transaction->transaction->id }}</a></td>
                    <td>{{ $transaction->type }}</td>
                    <td>{{ $transaction->transaction->actor()->name }}</td>
                    <td>{{ $transaction->quantity }}</td>
                    <td>{{ $transaction->good_unit->unit->name }}</td>
                    <td>{{ $transaction->real_quantity }}</td>
                    <td>{{ $transaction->last_stock }}</td>
                    @if(\Auth::user()->email == 'admin')
                      <td style="text-align: right;">{{ showRupiah($transaction->buy_price) }}</td>
                    @endif
                    <td style="text-align: right;">{{ showRupiah($transaction->selling_price) }}</td>
                    <td style="text-align: right;">{{ showRupiah($transaction->quantity * $transaction->selling_price) }}</td>
                    <td style="text-align: right;">{{ showRupiah($transaction->discount_price) }}</td>
                    <td style="text-align: right;">{{ showRupiah($transaction->sum_price / $transaction->quantity) }}</td>
                    <td style="text-align: right;">{{ showRupiah($transaction->sum_price) }}</td>
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
        var distributor = $('#distributor').val();
      window.location = window.location.origin + '/{{ $role }}/good/{{ $good->id }}/transaction/' + $("#datepicker").val() + '/' + $("#datepicker2").val() +'/{{ $pagination }}';
    }

    function advanceSearch()
    {
      var show        = $('#show').val();
      var distributor = $('#distributor').val();
      window.location = window.location.origin + '/{{ $role }}/good/{{ $good->id }}/transaction/{{ $start_date }}/{{ $end_date }}/' + show;
    }
  </script>
@endsection
