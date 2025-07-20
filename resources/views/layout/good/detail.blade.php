<div class="content-wrapper">
  @include('layout' . '.error')

  <section class="content">
    <div class="row">
      <div class="col-xs-12">
        <div class="box">
            <h4>{{ $good->category->name }}</h4>
              <div class="box-header" style="text-align: center;">
                <h1>{{ $good->name }}</h1>
                <h4>{{ $good->code }}</h4>
                <h5>@if($good->brand != null){{ $good->brand->name }}@endif</h5>
                @if(\Auth::user()->role == 'supervisor')<h4>Distributor terakhir: {{ $good->getDistributor()->name }}</h4>@endif
              </div>
            <div class="box-body">
                <div style="text-align: center;">
                    @if($good->profilePicture() != null)<img src="{{ URL::to($role . '/image/' . $good->profilePicture()->location) }}" style="height: 200px;"><br>@endif
                    <a href="{{ url($role . '/good/' . $good->id . '/photo/create') }}"><i class="fa fa-camera"></i><br>Tambah Foto</a><br>
                </div>
                <hr>
                <div class="panel-body">
                    <div class="row" style="text-align: center; background-color: #FFFAD7;">
                        <h4>Stock Barang</h4>
                        <div class="col-sm-12">
                            <div class="col-sm-4">
                                <h4>Total Loading</h4> 
                                <h3>{{ $good->total_loading . ' ' . $good->base_unit()->unit->code }}</h3>
                            </div>
                            <div class="col-sm-4">
                                <h4>Total Terjual</h4>
                                <h3>{{ $good->total_transaction . ' ' . $good->base_unit()->unit->code }}</h3>
                            </div>
                            <div class="col-sm-4">
                                <h4>Sisa Barang</h4>
                                <h3>{{ $good->last_stock . ' ' . $good->base_unit()->unit->code }}</h3>
                            </div>
                        </div>
                    </div>
                    <hr>
                    @if(\Auth::user()->email == 'admin')
                        <div class="row" style="text-align: center; background-color: #A1C2F1;">
                            <h4>Harga Beli</h4>
                            <h3>@if($good->getLastBuy() != null) {{ $good->getDistributor()->name . ' (' . $good->getLastBuy()->good_loading->note . ')' }} @endif</h3>
                            <div class="col-sm-12">
                                @foreach($good->good_units as $unit)
                                    <div class="col-sm-4">
                                        <h3>{{ showRupiah($unit->buy_price) . ' /' . $unit->unit->name}}</h3>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <hr>
                    @endif
                    <div class="row" style="text-align: center; background-color: #FDCEDF;">
                        <h4>Harga Jual</h4>
                        <div class="col-sm-12">
                            @foreach($good->good_units as $unit)
                                <div class="col-sm-4">
                                    <h3>
                                        {{ showRupiah($unit->selling_price) . ' /' . $unit->unit->name}}
                                        @if(\Auth::user()->email == 'admin')
                                          <br>Untung: {{ showRupiah($unit->selling_price - $unit->buy_price) . ' (' . calculateProfit($unit->buy_price, $unit->selling_price) }}%)
                                        @endif
                                    </h3>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                      <a href="{{ url($role . '/good/' . $good->id . '/edit') }}" class="btn btn-info btn-flat btn-block form-control" target="_blank()">Ubah Data Barang</a>
                      <a href="{{ url($role . '/good/' . $good->id . '/transaction/2018-01-01/' . date('Y-m-d') . '/10') }}" class="btn btn-flat btn-block form-control back-pink white" target="_blank()">Lihat Riwayat Penjualan Barang</a>
                      <a href="{{ url($role . '/good/' . $good->id . '/loading/2018-01-01/' . date('Y-m-d') . '/10') }}" class="btn btn-success btn-flat btn-block form-control" target="_blank()">Lihat Riwayat Loading Barang</a>
                      <a href="{{ url($role . '/good/' . $good->id . '/price/2023-01-01/' . date('Y-m-d') . '/10') }}" class="btn btn-danger btn-flat btn-block form-control" target="_blank()">Lihat Riwayat Pricing Barang</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
    </section>
</div>

<style type="text/css">
    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background-color: rgb(60, 141, 188) !important;
    }
</style>
@section('js-addon')
@endsection
