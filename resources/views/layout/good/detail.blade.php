<style>
    table tr td
    {
        padding: 3px;
    }
</style>

<div class="content-wrapper">
  @include('layout' . '.error')

  <section class="content">
    <div class="row">
      <div class="col-xs-12">
        <div class="box">
              <div class="box-header" style="text-align: center;">
              </div>
            <div class="box-body">
                <div class="col-sm-12">
                    <div class="col-sm-6" style="background-color: #FFFAD7; border-radius: 10px;">
                        <div class="col-sm-8">
                            <table>
                                <tr>
                                    <td colspan="3"><i>Informasi barang</i></td>
                                </tr>
                                <tr>
                                    <td colspan="3"><h2>{{ ucwords($good->name) }}</h2></td>
                                </tr>
                                <tr>
                                    <td>Barcode</td>
                                    <td>:</td>
                                    <td>{{ $good->code }}</td>
                                </tr>
                                <tr>
                                    <td>Kategori</td>
                                    <td>:</td>
                                    <td>{{ $good->category->name }}</td>
                                </tr>
                                <tr>
                                    <td>Jenis Barang</td>
                                    <td>:</td>
                                    <td>{{ $good->getType() }}</td>
                                </tr>
                                <tr>
                                    <td>Brand</td>
                                    <td>:</td>
                                    <td>@if($good->brand != null) {{ $good->brand->name }} @else - @endif</td>
                                </tr>
                                @if(\Auth::user()->role == 'supervisor')
                                    <tr>
                                        <td>Distributor terakhir</td>
                                        <td>:</td>
                                        <td>{{ $good->getDistributor()->name }}</td>
                                    </tr>
                                @endif
                            </table>
                        </div>
                        <div class="col-sm-4">
                            <div style="text-align: center;">
                                @if($good->profilePicture() != null)
                                    <img src="{{ URL::to('image/' . $good->profilePicture()->location) }}" style="height: 200px;"><br>
                                @endif
                            </div>
                            <div style="text-align: center;">
                                <a href="{{ url($role . '/good/' . $good->id . '/photo/create') }}"><i class="fa fa-camera"></i><br>Tambah Foto</a><br>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-5 col-sm-offset-1">
                        <div class="col-sm-12" style="background-color: #FFECC0; border-radius: 10px;">
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
                        @if(\Auth::user()->email == 'admin')
                            <div class="col-sm-12" style="background-color: #FFC29B; border-radius: 10px; margin-top: 10px;">
                                <h4>Harga Beli</h4>
                                <h5>@if($good->getLastBuy() != null) {{ $good->getDistributor()->name . ' (' . $good->getLastBuy()->good_loading->note . ')' }} @endif</h5>
                                <div class="col-sm-12">
                                    @foreach($good->good_units as $unit)
                                        <div class="col-sm-6">
                                            <h4>{{ showRupiah($unit->buy_price) . ' /' . $unit->unit->name}}</h4>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        <div class="col-sm-12" style="background-color: #F4991A; border-radius: 10px; margin-top: 10px;">
                            <h4>Harga Jual</h4>
                            <div class="col-sm-12">
                                @foreach($good->good_units as $unit)
                                    <div class="col-sm-6">
                                        <h5>
                                            {{ showRupiah($unit->selling_price) . ' /' . $unit->unit->name}}
                                            @if(\Auth::user()->email == 'admin')
                                              <br>Untung: {{ showRupiah($unit->selling_price - $unit->buy_price) . ' (' . calculateProfit($unit->buy_price, $unit->selling_price) }}%)
                                            @endif
                                        </h5>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                    <div class="col-sm-12" style="margin-top: 10px">
                      <a href="{{ url($role . '/good/' . $good->id . '/edit') }}" class="btn btn-info btn-flat col-sm-3" target="_blank()">Ubah Data Barang</a>
                      <a href="{{ url($role . '/good/' . $good->id . '/transaction/2018-01-01/' . date('Y-m-d') . '/10') }}" class="btn btn-flat col-sm-3" target="_blank()">Lihat Riwayat Penjualan Barang</a>
                      <a href="{{ url($role . '/good/' . $good->id . '/loading/2018-01-01/' . date('Y-m-d') . '/10') }}" class="btn btn-success btn-flat  col-sm-3" target="_blank()">Lihat Riwayat Loading Barang</a>
                      <a href="{{ url($role . '/good/' . $good->id . '/price/2023-01-01/' . date('Y-m-d') . '/10') }}" class="btn btn-danger btn-flat col-sm-3" target="_blank()">Lihat Riwayat Pricing Barang</a>
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
