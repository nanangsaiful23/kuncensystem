@extends('layout.user', ['role' => 'admin', 'title' => 'Admin'])

@section('content')
<div class="content-wrapper">
  <section class="content">
    <div class="row">
      <div class="col-xs-12">
        <div class="box">
          <div class="box-header">
            <h3 class="box-title"> {{ $default['page_name'] . ' ' . $account->name }}</h3>
            </div>
            <div class="box-body chart-responsive">

              {!! Form::label('account_code', 'Jenis', array('class' => 'col-sm-1 control-label')) !!}
              <div class="col-sm-3">
                {!! Form::select('account_code', getAccountLists(), $account->code, ['class' => 'form-control select2', 'style'=>'width: 100%', 'id' => 'account_code', 'onchange' => 'advanceSearch()']) !!}
              </div>
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
              <div class="chart" id="line-chart" style="height: 300px;"></div>
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
        @for($i = 0; $i < sizeof($ledgers); $i++)
          {x: '{{ displayDate($ledgers[$i]->created_at) }}', item1: {{ checkNull($ledgers[$i]->initial) }}, item2: {{ checkNull($ledgers[$i]->ongoing) }}, item3: {{ checkNull($ledgers[$i]->current) }}},
        @endfor
      ],
      xkey: 'x',
      ykeys: ['item1', 'item2', 'item3'],
      labels: ['Awal', 'Berjalan', 'Akhir'],
      lineColors: ['#3c8dbc', '#FF6D1F', '#76153C'],
      hideHover: 'auto'
    });

    function advanceSearch()
    {
      window.location = window.location.origin + '/admin/scaleLedger/' + $('#start_date').val() + '/' + $('#end_date').val() + '/' + $('#account_code').val();
    }
  </script>
@endsection
@endsection