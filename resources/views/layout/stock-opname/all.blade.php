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
            <h4>Total stock opname: {{ showRupiah($stock_opnames->sum('total')) }}</h4><br>
          </div>
          <div class="box-body" style="overflow-x:scroll">
            <table id="example1" class="table table-bordered table-striped">
              <thead>
              <tr>
                <th>Tanggal</th>
                <th>Checker</th>
                <th>Total</th>
                <th>Catatan</th>
                <th class="center">Detail</th>
              </tr>
              </thead>
              <tbody id="table-good">
                @foreach($stock_opnames as $stock_opname)
                  <tr>
                    <td>{{ $stock_opname->created_at }}</td>
                    <td>{{ $stock_opname->actor()->name }}</td>
                    <td>{{ showRupiah($stock_opname->total) }}</td>
                    <td>{{ $stock_opname->note }}</td>
                    <td class="center"><a href="{{ url($role . '/stock_opname/' . $stock_opname->id . '/detail') }}"><i class="fa fa-hand-o-right tosca" aria-hidden="true"></i></a></td>
                  </tr>
                @endforeach
              </tbody>
              <div id="renderField">
                @if($pagination != 'all')
                  {{ $stock_opnames->render() }}
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
      window.location = window.location.origin + '/{{ $role }}/stock-opname/' + $("#datepicker").val() + '/' + $("#datepicker2").val() + '/{{ $pagination }}';
    }

    function advanceSearch()
    {
      var show        = $('#show').val();
      window.location = window.location.origin + '/{{ $role }}/stock-opname/{{ $start_date }}/{{ $end_date }}/' + show;
    }
  </script>
@endsection
