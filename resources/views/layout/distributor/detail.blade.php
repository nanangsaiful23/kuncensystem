<style>
  table tr td
  {
    padding: 5px;
  }
</style>

<div class="content-wrapper">
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
            </div>
            <div class="col-sm-8">
              <table>
                <tr>
                  <td><b>Total Aset</b></td>
                  <td>: (+)</td>
                  <td style="text-align: right;"><b>{{ showRupiah($distributor->getAsset()) }}</b></td>
                  <td><a href="{{ url($role . '/distributor/' . $distributor->id . '/detail/aset') }}"><i class="fa fa-hand-o-right tosca" aria-hidden="true"></i> Detail Aset</a></td>
                </tr>
                <tr>
                  <td>Total Hutang Dagang</td>
                  <td>: (-)</td>
                  <td style="text-align: right;">{{ showRupiah($distributor->utang_dagang) }}</td>
                  <td><a href="{{ url($role . '/distributor/' . $distributor->id . '/detail/utang_dagang') }}"><i class="fa fa-hand-o-right tosca" aria-hidden="true"></i> Detail Hutang Dagang</a></td>
                </tr>
                <tr>
                  <td>Total Titipan Uang</td>
                  <td>: (-)</td>
                  <td style="text-align: right;">{{ showRupiah($distributor->titip_uang) }}</td>
                  <td><a href="{{ url($role . '/distributor/' . $distributor->id . '/detail/titip_uang') }}"><i class="fa fa-hand-o-right tosca" aria-hidden="true"></i> Detail Titipan Uang</a></td>
                </tr>
                <tr>
                  <td>Total Pembayaran (Transaksi Internal)</td>
                  <td>: (+)</td>
                  <td style="text-align: right;">{{ showRupiah($distributor->pembayaran_internal) }}</td>
                  <td><a href="{{ url($role . '/distributor/' . $distributor->id . '/detail/pembayaran_internal') }}"><i class="fa fa-hand-o-right tosca" aria-hidden="true"></i> Detail Transaksi Internal</a></td>
                </tr>
                <tr>
                  <td>Total Piutang Dagang</td>
                  <td>: (+)</td>
                  <td style="text-align: right;">{{ showRupiah($distributor->piutang_dagang) }}</td>
                  <td><a href="{{ url($role . '/distributor/' . $distributor->id . '/detail/piutang_dagang') }}"><i class="fa fa-hand-o-right tosca" aria-hidden="true"></i> Detail Piutang Dagang</a></td>
                </tr>
                <tr>
                  <td>Total Piutang Dagang (Loading Barang)</td>
                  <td>: (+)</td>
                  <td style="text-align: right;">{{ showRupiah($distributor->piutang_dagang_loading) }}</td>
                  <td><a href="{{ url($role . '/distributor/' . $distributor->id . '/detail/piutang_dagang_loading') }}"><i class="fa fa-hand-o-right tosca" aria-hidden="true"></i> Detail Piutang Dagang (Loading Barang)</a></td>
                </tr>
                <tr>
                  <td>Total Pembayaran Langsung</td>
                  <td>: (+)</td>
                  <td style="text-align: right;">{{ showRupiah($distributor->pembayaran) }}</td>
                  <td><a href="{{ url($role . '/distributor/' . $distributor->id . '/detail/pembayaran') }}"><i class="fa fa-hand-o-right tosca" aria-hidden="true"></i> Detail Pembayaran</a></td>
                </tr>
                <tr>
                  <td><b>Total Utang</b></td>
                  <td>: </td>
                  <td style="text-align: right;"><b>{{ showRupiah($distributor->pembayaran_internal + $distributor->piutang_dagang + $distributor->pembayaran - $distributor->utang_dagang - $distributor->titip_uang) }}</b></td>
                </tr>
                <tr>
                  <td><b>Total Untung</b></td>
                  <td>: </td>
                  <td style="text-align: right;"><b>{{ showRupiah($distributor->untung) }}</b></td>
                  <td><a href="{{ url($role . '/distributor/' . $distributor->id . '/detail/untung') }}"><i class="fa fa-hand-o-right tosca" aria-hidden="true"></i> Detail Untung</a></td>
                </tr>
                <tr>
                  <td><b>Total Rugi</b></td>
                  <td>: </td>
                  <td style="text-align: right;"><b>{{ showRupiah($distributor->rugi) }}</b></td>
                  <td><a href="{{ url($role . '/distributor/' . $distributor->id . '/detail/rugi') }}"><i class="fa fa-hand-o-right tosca" aria-hidden="true"></i> Detail Rugi</a></td>
                </tr>
              </table>              
            </div>
            <div class="col-sm-12">
              @if($type == 'aset')
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
                <table class="table table-bordered table-striped">
                  <thead>
                  <tr>
                    <th width="5%">Highlight</th>
                    <th width="10%">Tanggal</th>
                    <th width="10%">Tipe</th>
                    <th width="15%">Nama</th>
                    <th width="15%">Qty</th>
                    <th width="15%">Satuan</th>
                    <th width="15%">Harga Beli</th>
                    @if($type == 'untung')
                      <th width="15%">Harga Jual</th>
                    @endif
                    <th width="15%">Sum</th>
                  </tr>
                  </thead>
                  <tbody id="table-good">
                    @foreach($items as $item)
                      <tr id="div-untung-{{ $item->id }}">
                        <td><input type="checkbox" name="hard-cashes[]" id="untung-{{ $item->id }}" onclick="highlight('untung-{{ $item->id }}')"></td>
                        <td>{{ displayDate($item->created_at) }}</td>
                        <td>{{ $item->type }}</td>
                        <td><a href="{{ url($role . '/transaction/' . $item->id . '/detail') }}" style="color: blue" target="_blank()">{{ $item->name }}</a></td>
                        <td>{{ $item->quantity }}</td>
                        <td>{{ $item->code }}</td>
                        <td>{{ showRupiah($item->buy_price) }}</td>
                        @if($type == 'untung')
                          <td>{{ showRupiah($item->selling_price) }}</td>
                          <td>{{ showRupiah(($item->selling_price - $item->buy_price) * $item->quantity) }}</td>
                        @else
                          <td>{{ showRupiah($item->buy_price * $item->quantity) }}</td>
                        @endif
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