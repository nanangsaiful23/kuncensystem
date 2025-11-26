@extends('layout.user', ['role' => 'admin', 'title' => 'Admin'])

@section('content')
<div class="content-wrapper">
  <section class="content">
    <div class="row">
      <div class="col-xs-12">
        <div class="box">
          <div class="box-header">
            <h3 class="box-title"> {{ $default['page_name'] }}</h3>
            </div>
            <div class="box-body chart-responsive">

              {!! Form::label('start_date', 'Tanggal Awal', array('class' => 'col-sm-1 control-label')) !!}
              <div class="col-sm-2">
                <div class="input-group date">
                  <input type="text" class="form-control pull-right" name="start_date" value="{{ $start_date }}" id="start_date" onchange="advanceSearch()">
                </div>
              </div>
              {!! Form::label('end_date', 'Tanggal Akhir', array('class' => 'col-sm-1 control-label')) !!}
              <div class="col-sm-2">
                <div class="input-group date">
                  <input type="text" class="form-control pull-right" name="end_date" value="{{ $end_date }}" id='end_date' onchange="advanceSearch()">
                </div>
              </div>
              <div class="chart" id="line-chart" style="height: 500px;"></div>
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
      $('#start_date').datepicker({
        autoclose: true,
        format: 'yyyy-mm-dd'
      })

      $('#end_date').datepicker({
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

var line = new Morris.Line({
      element: 'line-chart',
      resize: true,
      data: [ 
        @for($i = 0; $i < sizeof($dates); $i++)
            <?php $show = ''; ?>
            @for($j = 0; $j < sizeof($dates[$i]->data); $j++)
              <?php $show .= $dates[$i]->data[$j]->code . ":" . ($dates[$i]->data[$j]->current/1000) . ","; ?>
            @endfor
          {x: '{{ $dates[$i]->date }}', {{ $show }}},
        @endfor
      ],
      xkey: 'x',
      ykeys: [
        @for($j = 0; $j < sizeof($dates[0]->data); $j++)
          "{{ $dates[0]->data[$j]->code }}",
        @endfor
      ],
      xLabelAngle: 60,
      labels: [
          @for($j = 0; $j < sizeof($dates[0]->data); $j++)
            "{{ $dates[0]->data[$j]->name }}",
          @endfor
      ],
      lineColors: [
          @for($j = 0; $j < sizeof($dates[0]->data); $j++)
            "{{ $dates[0]->data[$j]->color }}",
          @endfor],
      hideHover: 'auto',
      parseTime: false
      // ymin: 0,   // Set the minimum Y-axis value
      // ymax: 100000000
    });
    

    function advanceSearch()
    {
      window.location = window.location.origin + '/admin/scaleLedger/' + $('#start_date').val() + '/' + $('#end_date').val() + '/' + $('#account_code').val();
    }
  </script>
@endsection
@endsection