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
              <div class="col-sm-5">
                <canvas id="pieChart" style="height:300px"></canvas>
              </div>
              <div class="col-sm-7">
                <table class="table table-bordered table-striped" style="height: 500px; display: block; overflow: auto;">
                  <thead>
                    <th>Kategori</th>
                    <th>Qty</th>
                    <th>Persentase</th>
                    <th>Total</th>
                    <th>Untung</th>
                  </thead>
                  <tbody>
                    @foreach($result as $data)
                      <tr style="background-color: {{ $data->color }}">
                        <td>{{ $data->name }}</td>
                        <td style="text-align: right;">{{ $data->qty }}</td>
                        <td style="text-align: right;">{{ round($data->qty / $result->sum('qty') * 100, 2) }}%</td>
                        <td style="text-align: right;">{{ showRupiah($data->total_price) }}</td>
                        <td style="text-align: right;">{{ showRupiah($data->profit) }}</td>
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
            var pieData = [];
            for(j = 0; j < result.length; j++)
            {
              show = {};
              show['value'] = result[j].qty;
              if(result[j].color == null)
              {
                show['color'] = '#FF0000';
                show['highlight'] = '#FF0000';
              }
              else
              {
                show['color'] = result[j].color;
                show['highlight'] = result[j].color;
              }
              show['label'] = result[j].name;

              pieData.push(show);
            }

            console.log(pieData);

            var pieChartCanvas = $('#pieChart').get(0).getContext('2d')
            var pieChart       = new Chart(pieChartCanvas)
    
            var pieOptions     = {
              //Boolean - Whether we should show a stroke on each segment
              segmentShowStroke    : true,
              //String - The colour of each segment stroke
              segmentStrokeColor   : '#fff',
              //Number - The width of each segment stroke
              segmentStrokeWidth   : 2,
              //Number - The percentage of the chart that we cut out of the middle
              percentageInnerCutout: 50, // This is 0 for Pie charts
              //Number - Amount of animation steps
              animationSteps       : 100,
              //String - Animation easing effect
              animationEasing      : 'easeOutBounce',
              //Boolean - Whether we animate the rotation of the Doughnut
              animateRotate        : true,
              //Boolean - Whether we animate scaling the Doughnut from the centre
              animateScale         : false,
              //Boolean - whether to make the chart responsive to window resizing
              responsive           : true,
              // Boolean - whether to maintain the starting aspect ratio or not when responsive, if set to false, will take up entire container
              maintainAspectRatio  : true,
              //String - A legend template
              legendTemplate       : '<ul class="<%=name.toLowerCase()%>-legend"><% for (var i=0; i<segments.length; i++){%><li><span style="background-color:<%=segments[i].fillColor%>"></span><%if(segments[i].label){%><%=segments[i].label%><%}%></li><%}%></ul>'
            }
            //Create pie or douhnut chart
            // You can switch between pie and douhnut using the method below.
            pieChart.Doughnut(pieData, pieOptions)
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