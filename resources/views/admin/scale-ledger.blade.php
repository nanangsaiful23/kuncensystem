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
              <!-- {!! Form::label('account', 'Jenis', array('class' => 'col-sm-1 control-label')) !!}
              <div class="col-sm-3">
                {!! Form::select('account', getAccountLists(), null, ['class' => 'form-control select2', 'multiple' => 'multiple', 'style'=>'width: 100%', 'id' => 'account', 'onchange' => 'changeGraph()']) !!}
              </div> -->
              <div class="chart" id="bar-chart" style="height: 500px;"></div>
            </div>
            <div class="box-body">
              <table class="table table-bordered table-striped">
                <thead>
                  <th>Tanggal</th>
                  <th>Penjualan</th>
                  <th>Pengeluaran</th>
                  <th>Untung</th>
                </thead>
                <tbody>
                  @foreach($dates as $date)
                    <tr>
                      <td>{{ displayDate($date->date) }}</td>
                      <td>{{ showRupiah($date->dataplus->sum('debit')) }}</td>
                      <td>{{ showRupiah($date->data->sum('debit')) }}</td>
                      <td>{{ showRupiah($date->profit) }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
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

        changeGraph();
    });
    

    function changeGraph() 
    {
      $.ajax({
        url: "{!! url('/admin/getScaleLedger/' . $start_date . '/' . $end_date . '/profit') !!}",
        success: function(result){
          dates = result.data;
            ykeysgraph = [];
            labelsgraph = [];
            colorsgraph = [];
            showall = [];
            for(i = 0; i < dates.length; i++)
            {
              show = {};
              date = dates[i].data;
              
              for(j = 0; j < date.length; j++)
              {
                data = date[j].code;
                show[data] = date[j].debit; 

                if(!ykeysgraph.includes(data))
                  ykeysgraph.push(data);
                if(!labelsgraph.includes(date[j].name))
                  labelsgraph.push(date[j].name);
                if(!colorsgraph.includes(date[j].color))
                  colorsgraph.push(date[j].color);
              }

              date = dates[i].dataplus;
              for(j = 0; j < date.length; j++)
              {
                data = date[j].code;
                show[data] = date[j].debit; 

                if(!ykeysgraph.includes(data))
                  ykeysgraph.push(data);
                if(!labelsgraph.includes(date[j].name))
                  labelsgraph.push(date[j].name);
                if(!colorsgraph.includes(date[j].color))
                  colorsgraph.push(date[j].color);
              }

              show['untung'] = dates[i].profit;
              if(!ykeysgraph.includes('untung'))
                ykeysgraph.push('untung');
              if(!labelsgraph.includes('untung'))
                labelsgraph.push('untung');
              if(!colorsgraph.includes('#05339C'))
                colorsgraph.push('#05339C');

              if(show != '')
              {
                show['x'] = dates[i].date; 
                showall.push(show);
              }
            }

            console.log(showall);

            var line = new Morris.Bar({
              element: 'bar-chart',
              resize: true,
              data: showall,
              xkey: 'x',
              ykeys: ykeysgraph,
              xLabelAngle: 60,
              labels: labelsgraph,
              barColors: colorsgraph,
              hideHover: 'auto',
              parseTime: false
            });

          line.redraw();
        },
        error: function(){
            console.log('error');
        }
      });

    }
    function advanceSearch()
    {
      window.location = window.location.origin + '/admin/scaleLedger/' + $('#start_date').val() + '/' + $('#end_date').val() + '/' + $('#account_code').val();
    }
  </script>
@endsection
@endsection