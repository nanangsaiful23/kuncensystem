<div class="content-wrapper">

  @include('layout' . '.alert-message', ['type' => $default['type'], 'data' => $default['data'], 'color' => $default['color']])

  <section class="content">
    <div class="row">
      <div class="col-xs-12">
        <div class="box">
          <div class="box-header">
            <h3 class="box-title">{{ $default['page_name'] . ': ' . $member->name . ' (' . $member->address . ')' }}</h3>
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
          <div class="box-body" style="overflow-x:scroll; background-color: #E5F9DB">
            <h3>Transaksi Lunas</h3><br>
            <h4>Total transaksi lunas: {{ showRupiah($transactions['cash']->sum('total_sum_price')) }}</h4>
            <h4>Total potongan: {{ showRupiah($transactions['cash']->sum('total_discount_price')) }}</h4><br>
          </div>
          <div class="box-body" style="overflow-x:scroll; background-color: #E5F9DB">
            <table id="example1" class="table table-bordered table-striped">
              <thead>
              <tr>
                <th>Waktu</th>
                @if(\Auth::user()->email == 'admin')
                  <th>Kasir</th>
                @endif
                <th>Total Belanja</th>
                <th>Total Diskon</th>
                <th>Potongan Akhir</th>
                <th>Total Akhir</th>
                <th>Uang Dibayar</th>
                <th>Kembalian</th>
                <th class="center">Detail</th>
              </tr>
              </thead>
              <tbody id="table-good">
                @foreach($transactions['cash'] as $transaction)
                  <tr>
                    <td>{{ $transaction->created_at }}</td>
                    @if(\Auth::user()->email == 'admin')
                      <td>{{ $transaction->actor()->name }}</td>
                    @endif
                    <td>{{ showRupiah($transaction->total_item_price) }}</td>
                    <td>{{ showRupiah(checkNull($transaction->details->sum('discount_price'))) }}</td>
                    <td>{{ showRupiah($transaction->total_discount_price) }}</td>
                    <td>{{ showRupiah($transaction->total_sum_price) }}</td>
                    <td>{{ showRupiah($transaction->money_paid) }}</td>
                    <td>{{ showRupiah($transaction->money_returned) }}</td>
                    <td class="center"><a href="{{ url($role . '/transaction/' . $transaction->id . '/detail') }}"><i class="fa fa-hand-o-right tosca" aria-hidden="true"></i></a></td>
                  </tr>
                @endforeach
              </tbody>
              <div id="renderField">
                @if($pagination != 'all')
                  {{ $transactions['cash']->render() }}
                @endif
              </div>
            </table>
          </div>
          <div class="box-body" style="overflow-x:scroll; background-color: #FFD3A3">
            <h3>Transaksi Hutang</h3><br>
            <h4>Total transaksi hutang: {{ showRupiah($transactions['credit']->sum('total_sum_price')) }}</h4>
            <h4>Total potongan: {{ showRupiah($transactions['credit']->sum('total_discount_price')) }}</h4><br>
          </div>
          <div class="box-body" style="overflow-x:scroll; background-color: #FFD3A3">
            <table id="example1" class="table table-bordered table-striped">
              <thead>
              <tr>
                <th>Waktu</th>
                @if(\Auth::user()->email == 'admin')
                  <th>Kasir</th>
                @endif
                <th>Total Belanja</th>
                <th>Total Diskon</th>
                <th>Potongan Akhir</th>
                <th>Total Akhir</th>
                <th>Uang Dibayar</th>
                <th>Kembalian</th>
                <th class="center">Detail</th>
              </tr>
              </thead>
              <tbody id="table-good">
                @foreach($transactions['credit'] as $transaction)
                  <tr>
                    <td>{{ $transaction->created_at }}</td>
                    @if(\Auth::user()->email == 'admin')
                      <td>{{ $transaction->actor()->name }}</td>
                    @endif
                    <td>{{ showRupiah($transaction->total_item_price) }}</td>
                    <td>{{ showRupiah(checkNull($transaction->details->sum('discount_price'))) }}</td>
                    <td>{{ showRupiah($transaction->total_discount_price) }}</td>
                    <td>{{ showRupiah($transaction->total_sum_price) }}</td>
                    <td>{{ showRupiah($transaction->money_paid) }}</td>
                    <td>{{ showRupiah($transaction->money_returned) }}</td>
                    <td class="center"><a href="{{ url($role . '/transaction/' . $transaction->id . '/detail') }}"><i class="fa fa-hand-o-right tosca" aria-hidden="true"></i></a></td>
                  </tr>
                @endforeach
              </tbody>
              <div id="renderField">
                @if($pagination != 'all')
                  {{ $transactions['credit']->render() }}
                @endif
              </div>
            </table>
          </div>
          <div class="box-body" style="overflow-x:scroll; background-color: #ECC9EE">
            <h3>Transaksi Transfer</h3><br>
            <h4>Total transaksi transfer: {{ showRupiah($transactions['transfer']->sum('total_sum_price')) }}</h4>
            <h4>Total potongan: {{ showRupiah($transactions['transfer']->sum('total_discount_price')) }}</h4><br>
          </div>
          <div class="box-body" style="overflow-x:scroll; background-color: #ECC9EE">
            <table id="example1" class="table table-bordered table-striped">
              <thead>
              <tr>
                <th>Waktu</th>
                @if(\Auth::user()->email == 'admin')
                  <th>Kasir</th>
                @endif
                <th>Total Belanja</th>
                <th>Total Diskon</th>
                <th>Potongan Akhir</th>
                <th>Total Akhir</th>
                <th>Uang Dibayar</th>
                <th>Kembalian</th>
                <th class="center">Detail</th>
              </tr>
              </thead>
              <tbody id="table-good">
                @foreach($transactions['transfer'] as $transaction)
                  <tr>
                    <td>{{ $transaction->created_at }}</td>
                    @if(\Auth::user()->email == 'admin')
                      <td>{{ $transaction->actor()->name }}</td>
                    @endif
                    <td>{{ showRupiah($transaction->total_item_price) }}</td>
                    <td>{{ showRupiah(checkNull($transaction->details->sum('discount_price'))) }}</td>
                    <td>{{ showRupiah($transaction->total_discount_price) }}</td>
                    <td>{{ showRupiah($transaction->total_sum_price) }}</td>
                    <td>{{ showRupiah($transaction->money_paid) }}</td>
                    <td>{{ showRupiah($transaction->money_returned) }}</td>
                    <td class="center"><a href="{{ url($role . '/transaction/' . $transaction->id . '/detail') }}"><i class="fa fa-hand-o-right tosca" aria-hidden="true"></i></a></td>
                  </tr>
                @endforeach
              </tbody>
              <div id="renderField">
                @if($pagination != 'all')
                  {{ $transactions['transfer']->render() }}
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