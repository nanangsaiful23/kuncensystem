<style>
  .select2-container--default .select2-selection--multiple .select2-selection__choice 
  {
    background-color: yellow !important;
    color: black !important;
  }
</style>

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
              {!! Form::label('account', 'Jenis', array('class' => 'col-sm-1 control-label')) !!}
              <div class="col-sm-3">
                {!! Form::select('account', getAccountLists(), null, ['class' => 'form-control select2', 'multiple' => 'multiple', 'style'=>'width: 100%', 'id' => 'account', 'onchange' => 'changeGraph()']) !!}
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
    

    function changeGraph() 
    {
      ykeysgraph = [];
      labelsgraph = [];
      colorsgraph = [];
      showall = [];
      dates = <?php echo json_encode($dates); ?>;
      dates = dates.data;
      for(i = 0; i < dates.length; i++)
      {
          show = {};
          date = dates[i].data.data;
          for(j = 0; j < date.length; j++)
          {
            data = date[j].code;
            accounts = $('#account').val();
            if(accounts.includes(data))
            {
              show[data] = (date[j].current / 1000); 

              if(!ykeysgraph.includes(data))
                ykeysgraph.push(data);
              if(!labelsgraph.includes(date[j].name))
                labelsgraph.push(date[j].name);
              if(!colorsgraph.includes(date[j].color))
                colorsgraph.push(date[j].color);
            }
          }
          if(show != '')
          {
            show['x'] = dates[i].date; 
            showall.push(show);
          }
      }

      console.log(showall);
      console.log(ykeysgraph);
      console.log(labelsgraph);
      console.log(colorsgraph);

      var line = new Morris.Line({
        element: 'line-chart',
        resize: true,
        data: showall,
        xkey: 'x',
        ykeys: ykeysgraph,
        xLabelAngle: 60,
        labels: labelsgraph,
        lineColors: colorsgraph,
        hideHover: 'auto',
        parseTime: false
      });

    //   line.options.ykeys.push(ykeysgraph);
    // line.options.labels.push(labelsgraph);
    // line.options.lineColors.push(colorsgraph); // Add a new color
    // line.redraw();
    }
    function advanceSearch()
    {
      window.location = window.location.origin + '/admin/scaleLedger/' + $('#start_date').val() + '/' + $('#end_date').val() + '/' + $('#account_code').val();
    }
  </script>
@endsection
@endsection