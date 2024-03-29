<div class="content-wrapper">

  @include('layout' . '.alert-message', ['type' => $default['type'], 'data' => $default['data'], 'color' => $default['color']])

  <section class="content">
    <div class="row">
      <div class="col-xs-12">
        <div class="box">
          <div class="box-header">
            <h3 class="box-title"><a href="{{ url($role . '/good/' . $good->id . '/detail') }}">{{ $default['page_name'] . ' ' . $good->name }}</a></h3>
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
            @if(\Auth::user()->role == 'supervisor')
              <a href="{{ url($role . '/good/' . $good->id . '/editPrice') }}" class="btn btn-warning" target="_blank()">Ubah Harga Jual</a>
              <a href="{{ url($role . '/good/' . $good->id . '/createPrice') }}" class="btn btn-warning" target="_blank()">Tambah Harga Jual</a>
            @endif
            <table id="example1" class="table table-bordered table-striped">
              <thead>
              <tr>
                <th>Created at</th>
                <th>PIC</th>
                <th>Satuan</th>
                @if(\Auth::user()->role == 'supervisor')
                  <th>Harga Beli Lama</th>
                  <th>Harga Beli Baru</th>
                @endif
                <th>Harga Jual Lama</th>
                <th>Harga Jual Baru</th>
                <th>Alasan</th>
              </tr>
              </thead>
              <tbody id="table-good">
                @foreach($prices as $price)
                  <tr>
                    <td>{{ $price->created_at }}</td>
                    <td>{{ $price->actor()->name }}</td>
                    <td>{{ $price->good_unit->unit->code }}</td>
                    @if(\Auth::user()->role == 'supervisor')
                      <td style="text-align: right;">{{ showRupiah($price->old_buy_price) }}</td>
                      <td style="text-align: right;">{{ showRupiah($price->recent_buy_price) }}</td>
                    @endif
                    <td style="text-align: right;">{{ showRupiah($price->old_price) }}</td>
                    <td style="text-align: right;">{{ showRupiah($price->recent_price) }}</td>
                    <td>{{ $price->reason }}</td>
                  </tr>
                @endforeach
              </tbody>
              <div id="renderField">
                @if($pagination != 'all')
                  {{ $prices->render() }}
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
      window.location = window.location.origin + '/{{ $role }}/good/{{ $good->id }}/price/' + $("#datepicker").val() + '/' + $("#datepicker2").val() +'/{{ $pagination }}';
    }

    function advanceSearch()
    {
      var show        = $('#show').val();
      var distributor = $('#distributor').val();
      window.location = window.location.origin + '/{{ $role }}/good/{{ $good->id }}/price/{{ $start_date }}/{{ $end_date }}/' + show;
    }
  </script>
@endsection
