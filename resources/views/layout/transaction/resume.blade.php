<div class="content-wrapper">

  @include('layout' . '.alert-message', ['type' => $default['type'], 'data' => $default['data'], 'color' => $default['color']])

  <section class="content">
    <div class="row">
      <div class="col-xs-12">
        <div class="box">
          <div class="box-header">
            <h3 class="box-title">{{ $default['page_name'] }}</h3>
          </div>
          <div class="box-body">
            {!! Form::label('category', 'Kategori', array('class' => 'col-sm-1 control-label')) !!}
            <div class="col-sm-2">
              {!! Form::select('category', getCategories(), $category_id, ['class' => 'form-control select2', 'style'=>'width: 100%', 'id' => 'category', 'onchange' => 'advanceSearch()']) !!}
            </div>
            {!! Form::label('distributor', 'Distributor', array('class' => 'col-sm-1 control-label')) !!}
            <div class="col-sm-2">
              {!! Form::select('distributor', getDistributorLists(), $distributor_id, ['class' => 'form-control select2', 'style'=>'width: 100%', 'id' => 'distributor', 'onchange' => 'advanceSearch()']) !!}
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
          <div class="box-body" style="overflow-x:scroll; color: black !important">
            <h3>Total transaksi: {{ showRupiah($total->sum('sum_price')) }}</h3>
            <table id="example1" class="table table-bordered table-striped">
              <thead>
              <tr>
                <th>Kode</th>
                <th>Nama</th>
                <th>Jumlah</th>
                <th>Unit</th>
                <th>Harga Beli</th>
                <th>Harga Jual</th>
                <th>Untung</th>
                <th>Total Penjualan</th>
                <th>Total Untung</th>
              </tr>
              </thead>
              <tbody id="table-good">
                @foreach($transaction_details as $detail)
                  <?php $profit = calculateProfit($detail->buy_price, roundMoney($detail->selling_price)); ?>
                  <tr style="background-color: @if($detail->buy_price >= $detail->selling_price || $detail->buy_price == 0) #D21312 @elseif($profit <= 10) #FAE392 @elseif($profit <= 20) #C3EDC0 @elseif($profit <= 30) #F29727 @else #FF6D60 @endif">
                    <td>{{ $detail->code }}</td>
                    <td>{{ $detail->name }}</td>
                    <td>{{ $detail->quantity }}</td>
                    <td>{{ $detail->unit_name }}</td>
                    <td>{{ showRupiah($detail->buy_price) }}</td>
                    <td>{{ showRupiah($detail->selling_price) }}</td>
                    <td>{{ showRupiah($detail->selling_price - $detail->buy_price) }}<br>{{ $profit }}%</td>
                    <td>{{ showRupiah($detail->selling_price * $detail->quantity) }}</td>
                    <td>{{ showRupiah(($detail->selling_price - $detail->buy_price) * $detail->quantity) }}</td>
                  </tr>
                @endforeach
              </tbody>
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
      window.location = window.location.origin + '/{{ $role }}/transaction/resume/{{ $category_id }}/{{ $distributor_id }}/' + $("#datepicker").val() + '/' + $("#datepicker2").val();
    }

    function advanceSearch()
    {

      window.location = window.location.origin + '/{{ $role }}/transaction/resume/' + $('#category').val() + '/' + $('#distributor').val() + '/{{ $start_date}}/{{ $end_date }}';
    }
  </script>
@endsection