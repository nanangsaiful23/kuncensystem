@include('layout.transaction.style')

<div class="content-wrapper">
  @include('layout' . '.error')

  <section class="content" style="padding:0">
    <div class="page-scroll">

      <div class="page-actions">
        <h4 style="margin:0 10px 0 0;font-weight:700">{{ $default['page_name'] }} #{{ $transaction->id }}</h4>
        <span style="color:var(--clr-muted);font-size:14px">{{ $transaction->created_at }}</span>
        <span class="sdiv"></span>
        <a class="btn-page" id="act-back" href="{{ url($role . '/transaction/all/all/' . date('Y-m-d') . '/' . date('Y-m-d') . '/20') }}"><i class="fa fa-arrow-left"></i> Daftar <kbd>Esc</kbd></a>
        <a class="btn-page" id="act-print" href="{{ url($role . '/transaction/' . $transaction->id . '/print') }}"><i class="fa fa-print"></i> Cetak <kbd>P</kbd></a>
        @if(isSuperadmin())
          <a class="btn-page" id="act-edit" href="{{ url($role . '/transaction/' . $transaction->id . '/edit') }}"><i class="fa fa-pencil"></i> Ubah <kbd>E</kbd></a>
        @endif
      </div>

      <div class="info-grid">
        <div class="info-card"><div class="k">PIC / Kasir</div><div class="v">{{ $transaction->actor()->name }}</div></div>
        <div class="info-card"><div class="k">Member</div><div class="v">{{ $transaction->member->name }}</div></div>
        <div class="info-card"><div class="k">Tipe</div><div class="v">@if($transaction->type_name() != null){{ $transaction->type_name()->code . ' - ' . $transaction->type_name()->name }}@else - @endif</div></div>
        <div class="info-card"><div class="k">Keterangan</div><div class="v">{{ $transaction->note ?: '-' }}</div></div>
        <div class="info-card"><div class="k">Total Harga</div><div class="v">{{ showRupiah($transaction->total_item_price) }}</div></div>
        <div class="info-card"><div class="k">Total Diskon</div><div class="v">{{ showRupiah($transaction->details->sum('discount_price')) }}</div></div>
        <div class="info-card"><div class="k">Potongan Akhir</div><div class="v">{{ showRupiah($transaction->total_discount_price) }}</div></div>
        <div class="info-card big"><div class="k">Total Akhir</div><div class="v">{{ showRupiah($transaction->total_sum_price) }}</div></div>
        <div class="info-card"><div class="k">Uang Dibayar</div><div class="v">{{ showRupiah($transaction->money_paid) }}</div></div>
        <div class="info-card"><div class="k">Kembalian</div><div class="v">{{ showRupiah($transaction->money_returned) }}</div></div>
        @if(isSuperadmin())
          <div class="info-card"><div class="k">HPP</div><div class="v">{{ showRupiah($transaction->getHpp()) }}</div></div>
          <div class="info-card"><div class="k">Untung</div><div class="v">{{ showRupiah($transaction->getProfit()) }}</div></div>
        @endif
      </div>

      <div class="panel-table" style="min-height:0">
        <div class="panel-table-header">
          <h6><i class="fa fa-list-ul"></i> Daftar Barang</h6>
          <span class="badge-count">{{ count($transaction->detailsWithDeleted()) }} item</span>
        </div>
        <div class="panel-table-scroll" style="overflow-x:auto">
          <table class="tbl-items">
            <thead>
              <tr>
                <th class="col-no">#</th>
                <th>Barcode</th>
                <th class="col-name">Nama Barang</th>
                <th class="col-qty" style="text-align:center">Jml</th>
                @if(isSuperadmin())
                  <th class="col-num" style="text-align:right">Harga Beli</th>
                @endif
                <th class="col-num" style="text-align:right">Harga Jual</th>
                <th class="col-num" style="text-align:right">Total Diskon</th>
                <th class="col-sum" style="text-align:right">Total Akhir</th>
              </tr>
            </thead>
            <tbody>
              <?php $i = 1; ?>
              @foreach($transaction->detailsWithDeleted() as $detail)
                <tr class="{{ $i % 2 ? 'group-odd' : 'group-even' }}@if($detail->type == 'retur') retur-row @endif">
                  <td class="td-no">{{ $i++ }}</td>
                  <td><a href="{{ url($role . '/good/' . $detail->good_unit->good->id . '/detail') }}" target="_blank">{{ $detail->good_unit->good->code }}</a></td>
                  <td><div class="cell-txt nm">{{ $detail->good_unit->good->getFullName() . ' ' . $detail->good_unit->unit->name }}@if($detail->type == 'retur') <small class="low">RETUR</small>@endif</div></td>
                  <td style="text-align:center;font-size:16px;font-weight:700">{{ $detail->quantity }}</td>
                  @if(isSuperadmin())
                    <td style="text-align:right">{{ showRupiah($detail->buy_price) }}</td>
                  @endif
                  <td style="text-align:right">{{ showRupiah($detail->selling_price) }}</td>
                  <td style="text-align:right">{{ showRupiah($detail->discount_price) }}</td>
                  <td style="text-align:right;font-weight:700">@if($detail->type == 'retur') - @endif{{ showRupiah($detail->sum_price) }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </section>
</div>

@section('js-addon')
<script>
/* Esc = daftar, P = cetak, E = ubah (sama gaya dengan halaman tambah) */
document.addEventListener('keydown', function(e){
    if (e.ctrlKey || e.altKey || e.metaKey) return;
    var tag = (e.target.tagName || '').toLowerCase();
    if (tag === 'input' || tag === 'textarea' || tag === 'select') return;
    var id = e.key === 'Escape' ? 'act-back' : (e.key === 'p' || e.key === 'P') ? 'act-print' : (e.key === 'e' || e.key === 'E') ? 'act-edit' : null;
    var a = id && document.getElementById(id);
    if (a) { e.preventDefault(); window.location = a.href; }
});
</script>
@endsection
