<div class="content-wrapper">

  @include('layout' . '.alert-message', ['type' => $default['type'], 'data' => $default['data'], 'color' => $default['color']])

  <section class="content">
    <div class="row">
      <div class="col-xs-12">
        <div class="box">
          <div class="box-header">
            <h3 class="box-title">{{ $default['page_name'] }}</h3>
          </div>
          <div class="box-body" style="overflow-x:scroll; color: black !important">
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
  </script>
@endsection