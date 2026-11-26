{{--
  Kotak Cek Kecocokan di dashboard admin (App\Services\CekKecocokan).
  Data dari Admin\MainController@index: $cek ['belum' => hasil tidak cocok yang belum
  ditandai, 'terakhir' => hasil cek terakhir, 'boleh' => hak kas.cek_kecocokan]
  dan $last_cash_flow (null = kemarin belum tutup kasir).
--}}
@php
  $ckKemarin = date('Y-m-d', strtotime('-1 day'));
  $ckBelumTutup = $last_cash_flow == null;
  $ckTerakhir = $cek['terakhir'];
@endphp
@if($ckBelumTutup || $cek['belum']->isNotEmpty() || $ckTerakhir || session('cek_ok') || $errors->has('cek'))
<style>
  .ck-box .box-body { padding-top: 0; }
  .ck-item { border-left: 4px solid #dd4b39; background: #fff7f6; padding: 10px 12px; margin-bottom: 10px; border-radius: 4px; }
  .ck-item.ck-warn { border-left-color: #f39c12; background: #fffaf0; }
  .ck-item.ck-selesai { border-left-color: #bbb; background: #f7f7f7; color: #777; }
  .ck-baris-ok { color: #3c763d; font-weight: 600; }
  .ck-actions { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; margin-top: 8px; }
  .ck-actions form { display: inline-flex; flex-wrap: wrap; gap: 6px; align-items: center; margin: 0; }
  .ck-actions input[type=text] { width: 240px; }
</style>
<section class="col-lg-12" id="cek-kecocokan">
  <div class="box ck-box">
    <div class="box-header">
      <h2 class="box-title">Cek Kecocokan</h2>
    </div>
    <div class="box-body">
      @if(session('cek_ok'))
        <div class="alert alert-success">{{ session('cek_ok') }}</div>
      @endif
      @if($errors->has('cek'))
        <div class="alert alert-danger">{{ $errors->first('cek') }}</div>
      @endif

      @if($ckBelumTutup)
        <div class="ck-item ck-warn">
          <b>Kemarin ({{ displayDate($ckKemarin) }}) belum tutup kasir.</b>
          <a href="{{ url('admin/transaction/resumeTotal/' . $ckKemarin . '/' . $ckKemarin) }}#tutup-kasir">Isi tutup kasir kemarin</a>
        </div>
      @endif

      @foreach($cek['belum'] as $c)
        <div class="ck-item">
          <b>Tutup kasir {{ displayDate($c->check_date) }}: {{ $c->problem_count }} tidak cocok</b>
          <span class="ck-muted">&middot; dicek {{ $c->updated_at->format('d/m/Y H:i') }}</span>
          @include('layout.cash-closing.cek-hasil', ['c' => $c])

          @if($cek['boleh'])
            @php $ckStok = count(collect($c->results)->firstWhere('key', 'stok')['good_ids'] ?? []); @endphp
            <div class="ck-actions">
              <form method="POST" action="{{ url('admin/reconciliation/' . $c->id . '/mark') }}">
                @csrf
                <input type="text" name="note" class="form-control input-sm" maxlength="1000" placeholder="Catatan (boleh kosong)">
                <button type="submit" name="resolution" value="dibaca" class="btn btn-default btn-sm">Sudah dibaca</button>
                <button type="submit" name="resolution" value="ditangani" class="btn btn-success btn-sm">Sudah ditangani</button>
              </form>
              <form method="POST" action="{{ url('admin/reconciliation/' . $c->id . '/recheck') }}">
                @csrf
                <button type="submit" class="btn btn-default btn-sm" title="Jalankan lagi ceknya setelah data diperbaiki">Cek ulang</button>
              </form>
              @if($ckStok)
                <form method="POST" action="{{ url('admin/reconciliation/' . $c->id . '/sync-stock') }}"
                      onsubmit="return confirm('Samakan stok tercatat {{ $ckStok }} barang dengan hitungan dari riwayat loading & transaksi?')">
                  @csrf
                  <button type="submit" class="btn btn-warning btn-sm">Samakan stok {{ $ckStok }} barang</button>
                </form>
              @endif
            </div>
          @endif
        </div>
      @endforeach

      @if($cek['belum']->isEmpty() && $ckTerakhir)
        @if($ckTerakhir->problem_count === 0)
          <div class="ck-baris-ok"><i class="fa fa-check"></i> Tutup kasir {{ displayDate($ckTerakhir->check_date) }}: semua cocok.</div>
        @else
          <div class="ck-item ck-selesai">
            Tutup kasir {{ displayDate($ckTerakhir->check_date) }}: {{ $ckTerakhir->problem_count }} tidak cocok,
            ditandai sudah {{ $ckTerakhir->resolution }} oleh {{ $ckTerakhir->resolverName() }}
            ({{ optional($ckTerakhir->resolved_at)->format('d/m/Y H:i') }})@if($ckTerakhir->resolve_note): "{{ $ckTerakhir->resolve_note }}"@endif.
          </div>
        @endif
      @endif
    </div>
  </div>
</section>
@endif
