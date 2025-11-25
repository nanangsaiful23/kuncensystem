<style>
  table tr td
  {
    padding: 5px;
  }
</style>

<div class="content-wrapper">

  @include('layout' . '.alert-message', ['type' => $default['type'], 'data' => $default['data'], 'color' => $default['color']])
  
  @include('layout' . '.error')

  <section class="content">
    <div class="row">
      <div class="col-sm-12">
        <div class="box">
          <div class="box-header">
            <h3 class="box-title">{{ $default['page_name'] }}</h3>
          </div>
          <div class="box-body">
            <div class="col-sm-4">
              {!! Form::model($distributor, array('class' => 'form-horizontal')) !!}
                @include('layout' . '.distributor.form', ['SubmitButtonText' => 'View'])
              {!! Form::close() !!}
              <a href="{{ url($role . '/distributor/' . $distributor->id . '/ledger/Total%20Aset/2020-01-01/' . date('Y-m-d')) }}" class="btn">Riwayat Ledger Distributor</a>  
            </div>
            <div class="col-sm-8">
              {!! Form::model(old(),array('url' => route($role . '.distributor.storeLedger', $distributor->id), 'enctype'=>'multipart/form-data', 'method' => 'POST', 'class' => 'form-horizontal')) !!}
              <table>
                <tr>
                  <?php $asset = $distributor->getAsset(); ?>
                  {!! Form::hidden('names[]', "Total Aset") !!}
                  {!! Form::hidden('nominals[]', $asset) !!}
                  <td><b>Total Aset</b></td>
                  <td>: (+)</td>
                  <td style="text-align: right;"><b>{{ showRupiah($asset) }}</b></td>
                  <td><a href="{{ url($role . '/distributor/' . $distributor->id . '/detail/aset') }}"><i class="fa fa-hand-o-right tosca" aria-hidden="true"></i> Detail Aset</a></td>
                </tr>
                <tr>
                  {!! Form::hidden('names[]', "Total Hutang Dagang") !!}
                  {!! Form::hidden('nominals[]', $distributor->utang_dagang) !!}
                  <td>Total Hutang Dagang</td>
                  <td>: (-)</td>
                  <td style="text-align: right;">{{ showRupiah($distributor->utang_dagang) }}</td>
                  <td><a href="{{ url($role . '/distributor/' . $distributor->id . '/detail/utang_dagang') }}"><i class="fa fa-hand-o-right tosca" aria-hidden="true"></i> Detail Hutang Dagang</a></td>
                </tr>
                <tr>
                  {!! Form::hidden('names[]', "Total Titipan Uang") !!}
                  {!! Form::hidden('nominals[]', $distributor->titip_uang) !!}
                  <td>Total Titipan Uang</td>
                  <td>: (-)</td>
                  <td style="text-align: right;">{{ showRupiah($distributor->titip_uang) }}</td>
                  <td><a href="{{ url($role . '/distributor/' . $distributor->id . '/detail/titip_uang') }}"><i class="fa fa-hand-o-right tosca" aria-hidden="true"></i> Detail Titipan Uang</a></td>
                </tr>
                <tr>
                  {!! Form::hidden('names[]', "Total Pembayaran (Transaksi Internal)") !!}
                  {!! Form::hidden('nominals[]', $distributor->pembayaran_internal) !!}
                  <td>Total Pembayaran (Transaksi Internal)</td>
                  <td>: (+)</td>
                  <td style="text-align: right;">{{ showRupiah($distributor->pembayaran_internal) }}</td>
                  <td><a href="{{ url($role . '/distributor/' . $distributor->id . '/detail/pembayaran_internal') }}"><i class="fa fa-hand-o-right tosca" aria-hidden="true"></i> Detail Transaksi Internal</a></td>
                </tr>
                <tr>
                  {!! Form::hidden('names[]', "Total Piutang Dagang") !!}
                  {!! Form::hidden('nominals[]', $distributor->piutang_dagang) !!}
                  <td>Total Piutang Dagang</td>
                  <td>: (+)</td>
                  <td style="text-align: right;">{{ showRupiah($distributor->piutang_dagang) }}</td>
                  <td><a href="{{ url($role . '/distributor/' . $distributor->id . '/detail/piutang_dagang') }}"><i class="fa fa-hand-o-right tosca" aria-hidden="true"></i> Detail Piutang Dagang</a></td>
                </tr>
                <tr>
                  {!! Form::hidden('names[]', "Total Piutang Dagang (Loading Barang)") !!}
                  {!! Form::hidden('nominals[]', $distributor->piutang_dagang_loading) !!}
                  <td>Total Piutang Dagang (Loading Barang)</td>
                  <td>: (+)</td>
                  <td style="text-align: right;">{{ showRupiah($distributor->piutang_dagang_loading) }}</td>
                  <td><a href="{{ url($role . '/distributor/' . $distributor->id . '/detail/piutang_dagang_loading') }}"><i class="fa fa-hand-o-right tosca" aria-hidden="true"></i> Detail Piutang Dagang (Loading Barang)</a></td>
                </tr>
                <tr>
                  {!! Form::hidden('names[]', "Total Pembayaran Langsung") !!}
                  {!! Form::hidden('nominals[]', $distributor->pembayaran) !!}
                  <td>Total Pembayaran Langsung</td>
                  <td>: (+)</td>
                  <td style="text-align: right;">{{ showRupiah($distributor->pembayaran) }}</td>
                  <td><a href="{{ url($role . '/distributor/' . $distributor->id . '/detail/pembayaran') }}"><i class="fa fa-hand-o-right tosca" aria-hidden="true"></i> Detail Pembayaran</a></td>
                </tr>
                <tr>
                  <?php $total = $distributor->pembayaran_internal + $distributor->piutang_dagang + $distributor->pembayaran - $distributor->utang_dagang - $distributor->titip_uang; ?>
                  {!! Form::hidden('names[]', "Total Utang") !!}
                  {!! Form::hidden('nominals[]', $total) !!}
                  <td><b>Total Utang</b></td>
                  <td>: </td>
                  <td style="text-align: right;"><b>{{ showRupiah($total) }}</b></td>
                </tr>
                <tr>
                  {!! Form::hidden('names[]', "Total Untung") !!}
                  {!! Form::hidden('nominals[]', $distributor->untung) !!}
                  <td><b>Total Untung</b></td>
                  <td>: </td>
                  <td style="text-align: right;"><b>{{ showRupiah($distributor->untung) }}</b></td>
                  <td><a href="{{ url($role . '/distributor/' . $distributor->id . '/detail/untung') }}"><i class="fa fa-hand-o-right tosca" aria-hidden="true"></i> Detail Untung</a></td>
                </tr>
                <tr>
                  {!! Form::hidden('names[]', "Total Rugi") !!}
                  {!! Form::hidden('nominals[]', $distributor->rugi) !!}
                  <td><b>Total Rugi</b></td>
                  <td>: </td>
                  <td style="text-align: right;"><b>{{ showRupiah($distributor->rugi) }}</b></td>
                  <td><a href="{{ url($role . '/distributor/' . $distributor->id . '/detail/rugi') }}"><i class="fa fa-hand-o-right tosca" aria-hidden="true"></i> Detail Rugi</a></td>
                </tr>
              </table>   
                {!! Form::submit("Simpan Ledger Distributor", ['class' => 'btn form-control'])  !!}
              {!! Form::close() !!}         
            </div>
          </div>
          <div class="box-body" style="border: 2px solid black">
            <div class="col-sm-12">
              @if($type == 'aset')
                <h3>Detail Aset</h3>
                <table class="table table-bordered table-striped">
                  <thead>
                  <tr>
                    <th width="5%">No</th>
                    <th width="15%">Nama</th>
                    <th width="10%">Loading</th>
                    <th width="10%">Terjual</th>
                    <th width="10%">Stock</th>
                    <th width="10%">Satuan</th>
                    <th width="10%">Stock Uang</th>
                    <th width="15%">Loading Terakhir</th>
                    <th width="15%">Penjualan Terakhir</th>
                  </tr>
                  </thead>
                  <tbody>
                    <?php $i = 1 ?>
                    @foreach($items as $item)
                      <tr>
                        <td>{{ $i++ }}</td>
                        <td><a href="{{ url($role . '/good/' . $item->id . '/detail') }}" style="color: blue" target="_blank()">{{ $item->name }}</a></td>
                        <td>{{ $item->total_loading }}</td>
                        <td>{{ $item->total_transaction }}</td>
                        <td>{{ $item->last_stock }}</td>
                        <td>{{ $item->base_unit()->unit->code }}</td>
                        <td style="text-align: right;">{{ showRupiah($item->total) }}</td>
                        <td>{{ displayDate($item->last_loading) }}</td>
                        <td>{{ displayDate($item->last_transaction) }}</td>
                      </tr>
                    @endforeach
                    {!! $items->render() !!}
                  </tbody>
                </table>
              @elseif($type == 'utang_dagang')
                <h3>Detail Hutang Dagang</h3>
                <table class="table table-bordered table-striped">
                  <thead>
                  <tr>
                    <th width="5%">Highlight</th>
                    <th width="10%">Tanggal</th>
                    <th width="15%">Nama</th>
                    <th width="15%">Nominal</th>
                  </tr>
                  </thead>
                  <tbody id="table-good">
                    @foreach($items as $item)
                      <tr id="div-hutang-{{ $item->id }}">
                        <td><input type="checkbox" name="hutangs[]" id="hutang-{{ $item->id }}" onclick="highlight('hutang-{{ $item->id }}')"></td>
                        <td>{{ displayDate($item->loading_date) }}</td>
                        <td><a href="{{ url($role . '/good-loading/' . $item->id . '/detail') }}" style="color: blue" target="_blank()">Loading tanggal {{ displayDate($item->loading_date) . ' (' . $item->note . ')' }}</a></td>
                        <td>{{ showRupiah($item->total_item_price) }}</td>
                      </tr>
                    @endforeach
                    {!! $items->render() !!}
                  </tbody>
                </table>
              @elseif($type == 'titip_uang')
                <h3>Detail Titipan Uang</h3>
                <table class="table table-bordered table-striped">
                  <thead>
                  <tr>
                    <th width="5%">Highlight</th>
                    <th width="10%">Tanggal</th>
                    <th width="15%">Nama</th>
                    <th width="15%">Nominal</th>
                  </tr>
                  </thead>
                  <tbody id="table-good">
                    @foreach($items as $item)
                      <tr id="div-titip-{{ $item->id }}">
                        <td><input type="checkbox" name="titips[]" id="titip-{{ $item->id }}" onclick="highlight('titip-{{ $item->id }}')"></td>
                        <td>{{ displayDate($item->journal_date) }}</td>
                        <td><a href="{{ url($role . '/journal/' . $item->id . '/detail') }}" style="color: blue" target="_blank()">{{ $item->name }}</a></td>
                        <td>{{ showRupiah($item->debit) }}</td>
                      </tr>
                    @endforeach
                    {!! $items->render() !!}
                  </tbody>
                </table>
              @elseif($type == 'pembayaran_internal')
              <h3>Detail Pembayaran Hutang dari Transaksi Internal</h3>
                <table class="table table-bordered table-striped">
                  <thead>
                  <tr>
                    <th width="5%">Highlight</th>
                    <th width="10%">Tanggal</th>
                    <th width="15%">Nama</th>
                    <th width="15%">Nominal</th>
                  </tr>
                  </thead>
                  <tbody id="table-good">
                    @foreach($items as $item)
                      <tr id="div-hutang-internal-{{ $item->id }}">
                        <?php 
                          $subs = explode(" ", $item->name);
                          $id   = $subs[sizeof($subs) - 1];
                        ?>
                        <td><input type="checkbox" name="hutang-internals[]" id="hutang-internal-{{ $item->id }}" onclick="highlight('hutang-internal-{{ $item->id }}')"></td>
                        <td>{{ displayDate($item->created_at) }}</td>
                        <td><a href="{{ url($role . '/transaction/' . substr($id, 0, -1) . '/detail') }}" style="color: blue" target="_blank()">{{ $item->name }}</a></td>
                        <td>{{ showRupiah($item->debit) }}</td>
                      </tr>
                    @endforeach
                    {!! $items->render() !!}
                  </tbody>
                </table>
              @elseif($type == 'piutang_dagang')
              <h3>Detail Piutang Dagang dari Transaksi Internal</h3>
                <table class="table table-bordered table-striped">
                  <thead>
                  <tr>
                    <th width="5%">Highlight</th>
                    <th width="10%">Tanggal</th>
                    <th width="15%">Nama</th>
                    <th width="15%">Nominal</th>
                  </tr>
                  </thead>
                  <tbody id="table-good">
                    @foreach($items as $item)
                      <tr id="div-piutang-internal-{{ $item->id }}">
                        <?php 
                          $subs = explode(" ", $item->name);
                          $id   = $subs[sizeof($subs) - 1];
                        ?>
                        <td><input type="checkbox" name="piutang-internals[]" id="piutang-internal-{{ $item->id }}" onclick="highlight('piutang-internal-{{ $item->id }}')"></td>
                        <td>{{ displayDate($item->created_at) }}</td>
                        <td><a href="{{ url($role . '/transaction/' . substr($id, 0, -1) . '/detail') }}" style="color: blue" target="_blank()">{{ $item->name }}</a></td>
                        <td>{{ showRupiah($item->debit) }}</td>
                      </tr>
                    @endforeach
                    {!! $items->render() !!}
                  </tbody>
                </table>
              @elseif($type == 'piutan_dagang_loading')
              <h3>Detail Piutang Dagang dari Loading Barang</h3>
                <table class="table table-bordered table-striped">
                  <thead>
                  <tr>
                    <th width="5%">Highlight</th>
                    <th width="10%">Tanggal</th>
                    <th width="15%">Nama</th>
                    <th width="15%">Nominal</th>
                  </tr>
                  </thead>
                  <tbody id="table-good">
                    @foreach($items as $item)
                      <tr id="div-piutang-{{ $item->id }}">
                        <td><input type="checkbox" name="piutangs[]" id="piutang-{{ $item->id }}" onclick="highlight('piutang-{{ $item->id }}')"></td>
                        <td>{{ displayDate($item->loading_date) }}</td>
                        <td><a href="{{ url($role . '/good-loading/' . $item->id . '/detail') }}" style="color: blue" target="_blank()">Loading tanggal {{ displayDate($item->loading_date) . ' (' . $item->note . ')' }}</a></td>
                        <td>{{ showRupiah($item->total_item_price) }}</td>
                      </tr>
                    @endforeach
                    {!! $items->render() !!}
                  </tbody>
                </table>
              @elseif($type == 'pembayaran')
              <h3>Detail Pembayaran Tunai</h3>
                <table class="table table-bordered table-striped">
                  <thead>
                  <tr>
                    <th width="5%">Highlight</th>
                    <th width="10%">Tanggal</th>
                    <th width="15%">Nama</th>
                    <th width="15%">Nominal</th>
                  </tr>
                  </thead>
                  <tbody id="table-good">
                    @foreach($items as $item)
                      <tr id="div-hard-cash-{{ $item->id }}">
                        <td><input type="checkbox" name="hard-cashes[]" id="hard-cash-{{ $item->id }}" onclick="highlight('hard-cash-{{ $item->id }}')"></td>
                        <td>{{ displayDate($item->journal_date) }}</td>
                        <td><a href="{{ url($role . '/journal/' . $item->id . '/detail') }}" style="color: blue" target="_blank()">{{ $item->name }}</a></td>
                        <td>{{ showRupiah($item->debit) }}</td>
                      </tr>
                    @endforeach
                    {!! $items->render() !!}
                  </tbody>
                </table>
              @elseif($type == 'untung' || $type == 'rugi')
                @if($type == 'untung')
                  <h3>Detail Untung</h3>
                @else
                  <h3>Detail Rugi</h3>
                @endif
                <table class="table table-bordered table-striped">
                  <thead>
                  <tr>
                    <th width="5%">Highlight</th>
                    <th width="10%">Tanggal</th>
                    <th width="10%">Tipe</th>
                    <th width="15%">Nama</th>
                    <th width="15%">Qty</th>
                    <th width="15%">Harga Beli</th>
                    <th width="15%">Total</th>
                  </tr>
                  </thead>
                  <tbody id="table-good">
                    @foreach($items as $item)
                      <tr id="div-untung-{{ $item->id }}">
                        <td><input type="checkbox" name="hard-cashes[]" id="untung-{{ $item->id }}" onclick="highlight('untung-{{ $item->id }}')"></td>
                        <td>{{ displayDate($item->created_at) }}</td>
                        <td>{{ $item->type }}</td>
                        <td>{{ $item->name }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>{{ showRupiah($item->buy_price) }}</td>
                        <td>{{ showRupiah($item->total) }}</td>
                      </tr>
                    @endforeach
                    {!! $items->render() !!}
                  </tbody>
                </table>
              @endif
              
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
    });

    function highlight(id)
    {
      if($("#" + id).prop('checked') == true)
        $('#div-' + id).css('background-color', "{{ config('app.app_color') }}");
      else
        $('#div-' + id).css('background-color', "white");
    }
  </script>
@endsection