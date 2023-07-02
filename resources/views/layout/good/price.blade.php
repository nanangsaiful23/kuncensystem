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
            <a href="{{ url($role . '/good/' . $good->id . '/editPrice') }}" class="btn btn-warning" target="_blank()">Ubah Harga Jual</a>
            <table id="example1" class="table table-bordered table-striped">
              <thead>
              <tr>
                @if(\Auth::user()->email == 'admin')
                  <th>Created at</th>
                @endif
                <th>PIC</th>
                <th>Satuan</th>
                <th>Harga Jual Lama</th>
                <th>Harga Jual Baru</th>
                <th>Alasan</th>
              </tr>
              </thead>
              <tbody id="table-good">
                @foreach($prices as $price)
                  <tr>
                    @if(\Auth::user()->email == 'admin')
                      <td>{{ $price->created_at }}</td>
                    @endif
                    <td>{{ $price->actor()->name }}</td>
                    <td>{{ $price->good_unit->unit->code }}</td>
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
