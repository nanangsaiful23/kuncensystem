<div class="content-wrapper">
  @include('layout' . '.error')

  <section class="content">
    <div class="row">
      <div class="col-xs-12">
        <div class="box">
          <div class="box-header">
            <h3 class="box-title">{{ $default['page_name'] }}</h3>
          </div>

          {!! Form::model($distributor, array('class' => 'form-horizontal')) !!}
            <div class="box-body">
              @include('layout' . '.distributor.form', ['SubmitButtonText' => 'View'])
            </div>
          {!! Form::close() !!}
          <div class="col-sm-12 btn btn-warning" onclick="changeView('asset')">Asset</div>
          <div class="col-sm-6 btn btn-warning" onclick="changeView('income')">Pengambilan</div>
          <div class="col-sm-6 btn btn-warning" onclick="changeView('outcome')">Pembayaran</div>
          <div id='asset' style="display: none; margin-top: 20px;">
            <h3 style="margin-bottom: 30px;">Total Asset: {{ showRupiah($distributor->getAsset()) }}</h3>
            <table class="table table-bordered table-striped">
              <thead>
              <tr>
                <th width="5%">No</th>
                <th width="15%">Nama</th>
                <th width="10%">Loading</th>
                <th width="10%">Terjual</th>
                <th width="10%">Stock</th>
                <th width="10%">Stock Uang</th>
                <th width="15%">Loading Terakhir</th>
                <th width="15%">Penjualan Terakhir</th>
              </tr>
              </thead>
              <tbody>
                <?php $i = 1 ?>
                @foreach($distributor->detailAsset2() as $item)
                  <tr>
                    <td>{{ $i++ }}</td>
                    <td><a href="{{ url($role . '/good/' . $item->id . '/detail') }}" style="color: blue" target="_blank()">{{ $item->name }}</a></td>
                    <td>{{ $item->good_loadings()->sum('real_quantity') . ' ' . $item->getPcsSellingPrice()->unit->code }}</td>
                    <td>{{ $item->good_transactions()->sum('real_quantity') . ' ' . $item->getPcsSellingPrice()->unit->code }}</td>
                    <td>{{ $item->getStock() }}</td>
                    <td style="text-align: right;">{{ showRupiah($item->getStock() * $item->getPcsSellingPrice()->buy_price) }}</td>
                    <td>{{ displayDate($item->getLastBuy()) }}</td>
                    <td>{{ displayDate($item->getLastTransaction()) }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
            {{ $distributor->detailAsset2()->render() }}
          </div>
          <div id='income' style="display: none; margin-top: 20px;">
            <h3 style="margin-bottom: 30px;">Total Pengambilan: {{ showRupiah($distributor->totalHutangDagangLoading()->sum('total_item_price')) }}</h3>
            <div class="col-sm-6 btn btn-warning" onclick="changeView('hutang')">Daftar Hutang Dagang dari Loading Barang</div>
            <div class="col-sm-6 btn btn-warning" onclick="changeView('titip')">Daftar Titipan Uang</div>
          </div>
            <div class="box-body" style="margin-top: 20px;" id="hutang-history" style="display: none;">
              <h3>Daftar Hutang Dagang dari Loading Barang</h3>
              <h5>Total: {{ showRupiah($distributor->totalHutangDagangLoading()->sum('total_item_price')) }}</h5>
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
                  @foreach($distributor->totalHutangDagangLoading() as $item)
                    <tr id="div-hutang-{{ $item->id }}">
                      <td><input type="checkbox" name="hutangs[]" id="hutang-{{ $item->id }}" onclick="highlight('hutang-{{ $item->id }}')"></td>
                      <td>{{ displayDate($item->loading_date) }}</td>
                      <td><a href="{{ url($role . '/good-loading/' . $item->id . '/detail') }}" style="color: blue" target="_blank()">Loading tanggal {{ displayDate($item->loading_date) . ' (' . $item->note . ')' }}</a></td>
                      <td>{{ showRupiah($item->total_item_price) }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
            <div class="box-body" style="margin-top: 20px;" id="titip-history" style="display: none;">
              <h3>Daftar Titipan Uang</h3>
              <h5>Total: {{ showRupiah($distributor->titipUang()->sum('debit')) }}</h5>
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
                  @foreach($distributor->titipUang() as $item)
                    <tr id="div-titip-{{ $item->id }}">
                      <td><input type="checkbox" name="titips[]" id="titip-{{ $item->id }}" onclick="highlight('titip-{{ $item->id }}')"></td>
                      <td>{{ displayDate($item->journal_date) }}</td>
                      <td><a href="{{ url($role . '/journal/' . $item->id . '/detail') }}" style="color: blue" target="_blank()">{{ $item->name }}</a></td>
                      <td>{{ showRupiah($item->debit) }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          <div id='outcome' style="display: none; margin-top: 20px;">
            <h3 style="margin-bottom: 30px;">Total Pembayaran: {{ showRupiah($distributor->totalHutangDagangInternal()->sum('debit') + $distributor->totalPiutangDagangInternal()->sum('debit') + $distributor->totalPiutangDagangLoading()->sum('total_item_price') + $distributor->totalOutcome()->sum('debit')) }}</h3>
            <div class="col-sm-6 btn btn-warning" onclick="changeView('hutang-internal')">Pembayaran Hutang Dagang dari Transaksi Internal</div>
            <div class="col-sm-6 btn btn-warning" onclick="changeView('piutang-internal')">Daftar Piutang Dagang</div>
            <div class="col-sm-6 btn btn-warning" onclick="changeView('piutang')">Daftar Piutang Dagang dari Loading</div>
            <div class="col-sm-6 btn btn-warning" onclick="changeView('hard-cash')">Daftar Pembayaran Langsung</div>
          </div>
            <div class="box-body" style="margin-top: 20px;" id="hutang-internal-history" style="display: none;">
              <h3>Pembayaran Hutang Dagang dari Transaksi Internal</h3>
              <h5>Total: {{ showRupiah($distributor->totalHutangDagangInternal()->sum('debit')) }}</h5>
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
                  @foreach($distributor->totalHutangDagangInternal() as $item)
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
                </tbody>
              </table>
            </div>
            <div class="box-body" style="margin-top: 20px;" id="piutang-internal-history" style="display: none;">
              <h3>Daftar Piutang Dagang</h3>
              <h5>Total: {{ showRupiah($distributor->totalPiutangDagangInternal()->sum('debit')) }}</h5>
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
                  @foreach($distributor->totalPiutangDagangInternal() as $item)
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
                </tbody>
              </table>
            </div>
            <div class="box-body" style="margin-top: 20px;" id="piutang-history" style="display: none;">
              <h3>Daftar Piutang Dagang dari Loading Barang</h3>
              <h5>Total: {{ showRupiah($distributor->totalPiutangDagangLoading()->sum('total_item_price')) }}</h5>
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
                  @foreach($distributor->totalPiutangDagangLoading() as $item)
                    <tr id="div-piutang-{{ $item->id }}">
                      <td><input type="checkbox" name="piutangs[]" id="piutang-{{ $item->id }}" onclick="highlight('piutang-{{ $item->id }}')"></td>
                      <td>{{ displayDate($item->loading_date) }}</td>
                      <td><a href="{{ url($role . '/good-loading/' . $item->id . '/detail') }}" style="color: blue" target="_blank()">Loading tanggal {{ displayDate($item->loading_date) . ' (' . $item->note . ')' }}</a></td>
                      <td>{{ showRupiah($item->total_item_price) }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
            <div class="box-body" style="margin-top: 20px;" id="hard-cash-history" style="display: none;">
              <h3>Daftar Pembayaran Langsung</h3>
              <h5>Total: {{ showRupiah($distributor->totalOutcome()->sum('debit')) }}</h5>
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
                  @foreach($distributor->totalOutcome() as $item)
                    <tr id="div-hard-cash-{{ $item->id }}">
                      <td><input type="checkbox" name="hard-cashes[]" id="hard-cash-{{ $item->id }}" onclick="highlight('hard-cash-{{ $item->id }}')"></td>
                      <td>{{ displayDate($item->journal_date) }}</td>
                      <td><a href="{{ url($role . '/journal/' . $item->id . '/detail') }}" style="color: blue" target="_blank()">{{ $item->name }}</a></td>
                      <td>{{ showRupiah($item->debit) }}</td>
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
        $('#hutang-history').hide();
        $('#hutang-internal-history').hide();
        $('#piutang-internal-history').hide();
        $('#piutang-history').hide();
        $('#hard-cash-history').hide();
        $('#titip-history').hide();
        $('#income').hide();
    });

    function changeView(name)
    {
      if(name == 'asset')
      {
        $('#asset').show();
        $('#income').hide();
        $('#outcome').hide();

        // document.getElementById("asset").style.overflowX = "auto";
      }
      else if(name == 'income')
      {
        $('#asset').hide();
        $('#outcome').hide();
        $('#income').show();
        changeView('hutang');
      }
      else if(name == 'outcome')
      {
        $('#outcome').show();
        $('#income').hide();
        $('#asset').hide();
        changeView('hutang-internal');
      }
      else if(name == 'hutang')
      {
        $('#hutang-history').show();
        $('#hutang-internal-history').hide();
        $('#piutang-history').hide();
        $('#piutang-internal-history').hide();
        $('#hard-cash-history').hide();
        $('#titip-history').hide();
      }
      else if(name == 'hutang-internal')
      {
        $('#piutang-history').hide();
        $('#hutang-history').hide();
        $('#hutang-internal-history').show();
        $('#piutang-internal-history').hide();
        $('#hard-cash-history').hide();
        $('#titip-history').hide();
      }
      else if(name == 'piutang-internal')
      {
        $('#piutang-history').hide();
        $('#hutang-history').hide();
        $('#hutang-internal-history').hide();
        $('#piutang-internal-history').show();
        $('#hard-cash-history').hide();
        $('#titip-history').hide();
      }
      else if(name == 'hard-cash')
      {
        $('#piutang-history').hide();
        $('#hutang-history').hide();
        $('#hutang-internal-history').hide();
        $('#piutang-internal-history').hide();
        $('#hard-cash-history').show();
        $('#titip-history').hide();
      }
      else if(name == 'titip')
      {
        $('#piutang-history').hide();
        $('#hutang-history').hide();
        $('#hutang-internal-history').hide();
        $('#piutang-internal-history').hide();
        $('#hard-cash-history').hide();
        $('#titip-history').show();
      }
      else 
      {
        $('#piutang-history').show();
        $('#hutang-history').hide();
        $('#hutang-internal-history').hide();
        $('#piutang-internal-history').hide();
        $('#hard-cash-history').hide();
        $('#titip-history').hide();
      }
    }

    function highlight(id)
    {
      if($("#" + id).prop('checked') == true)
        $('#div-' + id).css('background-color', "{{ config('app.app_color') }}");
      else
        $('#div-' + id).css('background-color', "white");
    }
  </script>
@endsection