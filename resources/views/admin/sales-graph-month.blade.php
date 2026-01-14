<style>
  .select2-container--default .select2-selection--multiple .select2-selection__choice 
  {
    background-color: yellow !important;
    color: black !important;
  }

  table tr td 
  {
    font-size: 14px;
  }

  table thead th
  {
    font-size: 16px;
    text-align: center;
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
            </div>
            <div class="box-body">
              <div class="chart">
                <canvas id="lineChart" style="height:250px"></canvas>
              </div>
              <div class="col-sm-6">
                <table class="table table-bordered table-striped">
                  <thead>
                    <th>Tanggal</th>
                    <th>Total</th>
                  </thead>
                  <tbody>
                    @foreach($result as $data)
                      <tr>
                        <td>{{ $data->month . '-' . $data->year }}</td>
                        <td style="text-align: right;">{{ showRupiah($data->total) }}</td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
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
        url: "{!! url('/admin/getSalesGraph/category/' . $start_date . '/' . $end_date) !!}",
        success: function(result){

            var lineChartData = {
              labels  : [
                @for($i = sizeof($result) - 1; $i >= 0; $i--)
                  '{{ $result[$i]->month . '-' . $result[$i]->year }}',
                @endfor
              ],
              datasets: [
                {
                  label               : 'Penjualan',
                  fillColor           : 'rgba(210, 214, 222, 1)',
                  strokeColor         : 'rgba(210, 214, 222, 1)',
                  pointColor          : 'rgba(210, 214, 222, 1)',
                  pointStrokeColor    : '#c1c7d1',
                  pointHighlightFill  : '#fff',
                  pointHighlightStroke: 'rgba(220,220,220,1)',
                  data                : [
                    @for($i = sizeof($result) - 1; $i >= 0; $i--)
                      '{{ $result[$i]->total }}',
                    @endfor
                  ]
                },
              ]
            }

            var lineChartOptions = {
              //Boolean - If we should show the scale at all
              showScale               : true,
              //Boolean - Whether grid lines are shown across the chart
              scaleShowGridLines      : false,
              //String - Colour of the grid lines
              scaleGridLineColor      : 'rgba(0,0,0,.05)',
              //Number - Width of the grid lines
              scaleGridLineWidth      : 1,
              //Boolean - Whether to show horizontal lines (except X axis)
              scaleShowHorizontalLines: true,
              //Boolean - Whether to show vertical lines (except Y axis)
              scaleShowVerticalLines  : true,
              //Boolean - Whether the line is curved between points
              bezierCurve             : true,
              //Number - Tension of the bezier curve between points
              bezierCurveTension      : 0.3,
              //Boolean - Whether to show a dot for each point
              pointDot                : false,
              //Number - Radius of each point dot in pixels
              pointDotRadius          : 4,
              //Number - Pixel width of point dot stroke
              pointDotStrokeWidth     : 1,
              //Number - amount extra to add to the radius to cater for hit detection outside the drawn point
              pointHitDetectionRadius : 20,
              //Boolean - Whether to show a stroke for datasets
              datasetStroke           : true,
              //Number - Pixel width of dataset stroke
              datasetStrokeWidth      : 2,
              //Boolean - Whether to fill the dataset with a color
              datasetFill             : true,
              //String - A legend template
              legendTemplate          : '<ul class="<%=name.toLowerCase()%>-legend"><% for (var i=0; i<datasets.length; i++){%><li><span style="background-color:<%=datasets[i].lineColor%>"></span><%if(datasets[i].label){%><%=datasets[i].label%><%}%></li><%}%></ul>',
              //Boolean - whether to maintain the starting aspect ratio or not when responsive, if set to false, will take up entire container
              maintainAspectRatio     : true,
              //Boolean - whether to make the chart responsive to window resizing
              responsive              : true
            }

            var lineChartCanvas          = $('#lineChart').get(0).getContext('2d')
            var lineChart                = new Chart(lineChartCanvas)
            lineChartOptions.datasetFill = false
            lineChart.Line(lineChartData, lineChartOptions)
          },
          error: function(){
              console.log('error');
          }
      });

    }
    function advanceSearch()
    {
      window.location = window.location.origin + '/admin/salesGraph/' + $('#start_date').val() + '/' + $('#end_date').val();
    }
  </script>
@endsection
@endsection