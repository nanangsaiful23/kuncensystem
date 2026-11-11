@extends('layout.user', ['role' => 'admin', 'title' => 'Ringkasan Pemilik'])

@section('content')
@php
    $rp = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');
    $num = fn ($v, $d = 0) => number_format((float) $v, $d, ',', '.');
    $change = function ($now, $before) {
        if (! $before || abs($before) < 1) return null;
        return round(($now - $before) / abs($before) * 100, 1);
    };
    $dayOfMonth = $date->day;
@endphp
<style>
    .os { font-size:15px; }
    .os .box-title { font-size:18px; font-weight:700; }
    .os .hint { color:#666; font-size:14px; margin:4px 0 10px; }
    .os .cards { display:grid; grid-template-columns:repeat(auto-fill, minmax(230px, 1fr)); gap:12px; margin-bottom:16px; }
    .os .card { background:#fff; border:1px solid #d2d6de; border-top:4px solid #3c8dbc; border-radius:4px; padding:12px 14px; }
    .os .card.green { border-top-color:#00a65a; } .os .card.red { border-top-color:#dd4b39; } .os .card.orange { border-top-color:#f39c12; }
    .os .card .lbl { font-size:14px; color:#555; font-weight:600; }
    .os .card .val { font-size:24px; font-weight:700; margin:4px 0; }
    .os .card .sub { font-size:14px; color:#666; }
    .os .up { color:#00a65a; font-weight:700; } .os .down { color:#dd4b39; font-weight:700; }
    .os table.table { font-size:15px; }
    .os table.table th { background:#f4f4f4; white-space:nowrap; }
    .os table.table td.n, .os table.table th.n { text-align:right; white-space:nowrap; }
    .os .neg { color:#dd4b39; }
    .os .filter { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
    .os .filter input { font-size:15px; padding:4px 8px; }
</style>

<div class="content-wrapper os">
  <section class="content">

    <div class="box">
      <div class="box-header">
        <h3 class="box-title">Ringkasan Pemilik</h3>
        <form method="GET" action="{{ route('admin.reports.owner') }}" class="filter" style="margin-top:8px">
          <label for="date" style="margin:0">Sampai tanggal</label>
          <input type="date" id="date" name="date" value="{{ $date->toDateString() }}" max="{{ date('Y-m-d') }}">
          <button type="submit" class="btn btn-primary btn-sm">Tampilkan</button>
        </form>
        <p class="hint">Uang (penjualan, laba, kas, piutang, utang, persediaan) dari jurnal, sama dengan Neraca dan Laba Rugi. Barang dari stok dan penjualan 30 hari terakhir.</p>
      </div>
      <div class="box-body">
        <div class="cards">
          @php $c = $change($now->penjualan, $prev->penjualan ?? null); @endphp
          <div class="card">
            <div class="lbl">Penjualan {{ $now->label }} (s/d tgl {{ $dayOfMonth }})</div>
            <div class="val">{{ $rp($now->penjualan) }}</div>
            <div class="sub">bulan lalu {{ $rp($prev->penjualan ?? 0) }}
              @if($c !== null) <span class="{{ $c >= 0 ? 'up' : 'down' }}">{{ $c >= 0 ? '▲' : '▼' }} {{ $num(abs($c), 1) }}%</span> @endif
            </div>
          </div>
          <div class="card green">
            <div class="lbl">Laba kotor (margin)</div>
            <div class="val">{{ $rp($now->laba_kotor) }}</div>
            <div class="sub">margin {{ $num($now->margin, 1) }}% &middot; bulan lalu {{ $num($prev->margin ?? 0, 1) }}%</div>
          </div>
          <div class="card {{ $now->laba_bersih >= 0 ? 'green' : 'red' }}">
            <div class="lbl">Laba bersih (setelah beban)</div>
            <div class="val {{ $now->laba_bersih < 0 ? 'neg' : '' }}">{{ $rp($now->laba_bersih) }}</div>
            <div class="sub">beban {{ $rp($now->beban) }} &middot; bulan lalu laba {{ $rp($prev->laba_bersih ?? 0) }}</div>
          </div>
          <div class="card orange">
            <div class="lbl">Nilai persediaan</div>
            <div class="val">{{ $rp($stockValue) }}</div>
            <div class="sub">
              @if($stockDays) cukup ± <b>{{ $num($stockDays) }} hari</b> penjualan @endif
              &middot; di jurnal {{ $rp($now->persediaan) }}
            </div>
          </div>
          <div class="card">
            <div class="lbl">Kas (semua akun kas)</div>
            <div class="val {{ $now->kas < 0 ? 'neg' : '' }}">{{ $rp($now->kas) }}</div>
          </div>
          <div class="card">
            <div class="lbl">Piutang / Utang dagang</div>
            <div class="val" style="font-size:20px">{{ $rp($now->piutang) }} / {{ $rp($now->utang_dagang) }}</div>
            <div class="sub">uang di pelanggan / uang ke distributor</div>
          </div>
          <div class="card red">
            <div class="lbl">Modal mengendap</div>
            <div class="val">{{ $rp($stuckValue) }}</div>
            <div class="sub">{{ $stuck->count() }} barang ada stok tapi tidak laku 60 hari</div>
          </div>
          <div class="card {{ $toBuy->count() ? 'red' : 'green' }}">
            <div class="lbl">Perlu dibeli</div>
            <div class="val">{{ $toBuy->count() }} barang</div>
            <div class="sub">stok habis dalam kurang dari 7 hari</div>
          </div>
        </div>
      </div>
    </div>

    <div class="box">
      <div class="box-header"><h3 class="box-title">12 bulan terakhir</h3>
        <p class="hint">Laba kotor = penjualan - HPP. Laba bersih = laba kotor - beban + pendapatan lain. Saldo kas, persediaan, piutang dan utang pada akhir bulan.</p></div>
      <div class="box-body table-responsive">
        <table class="table table-bordered table-striped">
          <thead><tr>
            <th>Bulan</th><th class="n">Penjualan</th><th class="n">Laba kotor</th><th class="n">Margin</th><th class="n">Beban</th>
            <th class="n">Laba bersih</th><th class="n">Kas</th><th class="n">Persediaan</th><th class="n">Piutang</th><th class="n">Utang dagang</th>
          </tr></thead>
          <tbody>
          @foreach($trend->reverse() as $m)
            <tr>
              <td>{{ $m->label }}</td>
              <td class="n">{{ $num($m->penjualan) }}</td>
              <td class="n">{{ $num($m->laba_kotor) }}</td>
              <td class="n">{{ $num($m->margin, 1) }}%</td>
              <td class="n">{{ $num($m->beban) }}</td>
              <td class="n {{ $m->laba_bersih < 0 ? 'neg' : '' }}"><b>{{ $num($m->laba_bersih) }}</b></td>
              <td class="n {{ $m->kas < 0 ? 'neg' : '' }}">{{ $num($m->kas) }}</td>
              <td class="n">{{ $num($m->persediaan) }}</td>
              <td class="n">{{ $num($m->piutang) }}</td>
              <td class="n">{{ $num($m->utang_dagang) }}</td>
            </tr>
          @endforeach
          </tbody>
          <tfoot><tr>
            <th>12 bulan</th><th class="n">{{ $num($trend->sum('penjualan')) }}</th><th class="n">{{ $num($trend->sum('laba_kotor')) }}</th>
            <th class="n">{{ $trend->sum('penjualan') > 0 ? $num($trend->sum('laba_kotor') / $trend->sum('penjualan') * 100, 1) : 0 }}%</th>
            <th class="n">{{ $num($trend->sum('beban')) }}</th><th class="n">{{ $num($trend->sum('laba_bersih')) }}</th>
            <th colspan="4"></th>
          </tr></tfoot>
        </table>
      </div>
    </div>

    <div class="box">
      <div class="box-header"><h3 class="box-title">Perlu dibeli ({{ $toBuy->count() }})</h3>
        <p class="hint">Stok habis dalam kurang dari 7 hari dengan kecepatan jual 30 hari terakhir. Saran beli = kebutuhan 14 hari dikurangi stok.</p></div>
      <div class="box-body table-responsive">
        <table class="table table-bordered table-striped">
          <thead><tr><th>Barang</th><th>Distributor terakhir</th><th class="n">Stok</th><th class="n">Terjual / hari</th><th class="n">Habis dalam</th><th class="n">Saran beli</th></tr></thead>
          <tbody>
          @forelse($toBuy->take(40) as $g)
            <tr>
              <td>{{ $g->name }}</td><td>{{ $g->distributor }}</td>
              <td class="n {{ $g->stock < 0 ? 'neg' : '' }}">{{ $num($g->stock, 1) }} {{ $g->satuan }}</td>
              <td class="n">{{ $num($g->per_day, 2) }}</td>
              <td class="n"><b>{{ $g->days_left <= 0 ? 'habis' : $num($g->days_left, 1) . ' hari' }}</b></td>
              <td class="n">{{ $num(max(0, ceil($g->per_day * 14 - max($g->stock, 0)))) }} {{ $g->satuan }}</td>
            </tr>
          @empty
            <tr><td colspan="6">Tidak ada barang yang akan habis dalam 7 hari.</td></tr>
          @endforelse
          </tbody>
        </table>
        @if($toBuy->count() > 40)<p class="hint">Ditampilkan 40 dari {{ $toBuy->count() }} barang.</p>@endif
      </div>
    </div>

    <div class="row">
      <div class="col-md-6">
        <div class="box">
          <div class="box-header"><h3 class="box-title">Paling menguntungkan (30 hari)</h3>
            <p class="hint">Laba kotor = penjualan setelah diskon - harga beli.</p></div>
          <div class="box-body table-responsive">
            <table class="table table-bordered table-striped">
              <thead><tr><th>Barang</th><th class="n">Terjual</th><th class="n">Laba</th><th class="n">Margin</th></tr></thead>
              <tbody>
              @foreach($earners->take(20) as $g)
                <tr><td>{{ $g->name }}</td><td class="n">{{ $num($g->sold, 1) }} {{ $g->satuan }}</td>
                  <td class="n">{{ $num($g->laba) }}</td><td class="n">{{ $g->omzet > 0 ? $num($g->laba / $g->omzet * 100, 1) : 0 }}%</td></tr>
              @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="box">
          <div class="box-header"><h3 class="box-title">Modal mengendap ({{ $rp($stuckValue) }})</h3>
            <p class="hint">Ada stok, tapi tidak terjual sama sekali dalam 60 hari. Pertimbangkan promo, retur ke distributor, atau berhenti beli.</p></div>
          <div class="box-body table-responsive">
            <table class="table table-bordered table-striped">
              <thead><tr><th>Barang</th><th class="n">Stok</th><th class="n">Nilai</th><th>Terakhir laku</th></tr></thead>
              <tbody>
              @foreach($stuck->take(20) as $g)
                <tr><td>{{ $g->name }}</td><td class="n">{{ $num($g->stock, 1) }} {{ $g->satuan }}</td>
                  <td class="n">{{ $num($g->value) }}</td><td>{{ $g->last_sale ? \Carbon\Carbon::parse($g->last_sale)->format('d-m-Y') : 'belum pernah' }}</td></tr>
              @endforeach
              </tbody>
            </table>
            @if($stuck->count() > 20)<p class="hint">Ditampilkan 20 dari {{ $stuck->count() }} barang, urut dari nilai terbesar.</p>@endif
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-6">
        <div class="box">
          <div class="box-header"><h3 class="box-title">Pembelian per distributor (90 hari)</h3></div>
          <div class="box-body table-responsive">
            <table class="table table-bordered table-striped">
              <thead><tr><th>Distributor</th><th class="n">Loading</th><th class="n">Total</th><th>Terakhir</th></tr></thead>
              <tbody>
              @foreach($purchases as $p)
                <tr><td>{{ $p->name }}</td><td class="n">{{ $p->loadings }}</td><td class="n">{{ $num($p->total) }}</td><td>{{ \Carbon\Carbon::parse($p->last_date)->format('d-m-Y') }}</td></tr>
              @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="box">
          <div class="box-header"><h3 class="box-title">Stok minus ({{ $negative->count() }})</h3>
            <p class="hint">Stok tidak mungkin minus: biasanya loading belum diinput atau salah satuan. Perbaiki lewat loading atau stock opname.</p></div>
          <div class="box-body table-responsive">
            <table class="table table-bordered table-striped">
              <thead><tr><th>Barang</th><th class="n">Stok</th><th>Terakhir loading</th></tr></thead>
              <tbody>
              @forelse($negative->take(15) as $g)
                <tr><td>{{ $g->name }}</td><td class="n neg">{{ $num($g->stock, 1) }} {{ $g->satuan }}</td>
                  <td>{{ $g->last_loading ? \Carbon\Carbon::parse($g->last_loading)->format('d-m-Y') : '-' }}</td></tr>
              @empty
                <tr><td colspan="3">Tidak ada.</td></tr>
              @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

  </section>
</div>
@endsection
