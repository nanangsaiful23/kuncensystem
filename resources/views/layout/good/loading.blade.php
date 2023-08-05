<div class="content-wrapper">

  @include('layout' . '.alert-message', ['type' => $default['type'], 'data' => $default['data'], 'color' => $default['color']])

  <section class="content">
    <div class="row">
      <div class="col-xs-12">
        <div class="box">
          <div class="box-header">
            <h3 class="box-title">{{ $default['page_name'] . ' ' . $good->name }}</h3>
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
            <h4>Total loading: {{ $loadings->sum('real_quantity') }}</h4><br>
          </div>
          <div class="box-body" style="overflow-x:scroll">
            <table id="example1" class="table table-bordered table-striped">
              <thead>
              <tr>
                <th>Created at</th>
                <th>Tanggal loading</th>
                <th>Note</th>
                <th>Nama Distributor</th>
                <th>Expired</th>
                <th>Jumlah Input</th>
                <th>Jumlah Real</th>
                <th>Harga Beli</th>
                <th>Total Harga</th>
                <th>Stock Sebelumnya</th>
                <th>Harga Jual</th>
                @if(\Auth::user()->email == 'admin')
                  <th>Laba</th>
                @endif
              </tr>
              </thead>
              <tbody id="table-good">
                @foreach($loadings as $good_loading)
                  <tr>
                    <td><a href="{{ url($role . '/good-loading/' . $good_loading->good_loading->id . '/detail') }}" class="btn" target="_blank">{{ $good_loading->created_at }}</a></td>
                    <td>{{ displayDate($good_loading->good_loading->loading_date) }}</td>
                    <td>{{ $good_loading->good_loading->note }}</td>
                    <td>{{ $good_loading->good_loading->distributor->name }}</td>
                    <td>{{ $good_loading->expiry_date }}</td>
                    <td>{{ $good_loading->quantity. ' ' . $good_loading->good_unit->unit->code }}</td>
                    <td>{{ $good_loading->real_quantity }}</td>
                    <td style="text-align: right;">{{ showRupiah($good_loading->price) }}</td>
                    <td style="text-align: right;">{{ showRupiah($good_loading->quantity * $good_loading->price) }}</td>
                    <td>{{ $good_loading->last_stock }}</td>
                    <td style="text-align: right;">{{ showRupiah($good_loading->selling_price) }}</td>
                    @if(\Auth::user()->email == 'admin')
                      <td>{{ showRupiah(roundMoney($good_loading->selling_price) - $good_loading->price) }}
                        <br>{{ calculateProfit($good_loading->price, $good_loading->selling_price) }}%
                      </td>
                    @endif
                  </tr>
                @endforeach
              </tbody>
              <div id="renderField">
                @if($pagination != 'all')
                  {{ $loadings->render() }}
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
      window.location = window.location.origin + '/{{ $role }}/good/{{ $good->id }}/loading/' + $("#datepicker").val() + '/' + $("#datepicker2").val() +'/{{ $pagination }}';
    }

    function advanceSearch()
    {
      var show        = $('#show').val();
      var distributor = $('#distributor').val();
      window.location = window.location.origin + '/{{ $role }}/good/{{ $good->id }}/loading/{{ $start_date }}/{{ $end_date }}/' + show;
    }
  </script>
@endsection
