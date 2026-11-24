{{-- Tutup Kasir (admin & kasir). Logika: App\Services\TutupKasir. --}}
<style>
  .tk-wrap { padding: 15px; max-width: 1000px; }
  .tk-card { background: #fff; border: 1px solid #ddd; border-radius: 6px; padding: 15px; margin-bottom: 15px; }
  .tk-card h4 { margin-top: 0; }
  .tk-table { width: 100%; border-collapse: collapse; }
  .tk-table th, .tk-table td { padding: 8px 10px; border-bottom: 1px solid #eee; text-align: left; vertical-align: top; }
  .tk-table th { background: #f4f4f4; }
  .tk-table .num { text-align: right; white-space: nowrap; }
  .tk-scroll { overflow-x: auto; }
  .tk-muted { color: #888; font-size: 12px; }
  .tk-saldo { font-size: 26px; font-weight: 700; }
  .tk-field { margin-bottom: 14px; max-width: 420px; }
  .tk-field label { display: block; margin-bottom: 4px; }
  .tk-field input[type=text] { font-size: 22px; height: 46px; text-align: right; }
  .tk-live { font-size: 18px; margin: 4px 0 14px; }
  .tk-pas { color: #3c763d; }
  .tk-kurang { color: #a94442; }
  .tk-lebih { color: #31708f; }
  .tk-badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 12px; }
  .tk-ok { background: #dff0d8; color: #3c763d; }
  .tk-warn { background: #fcf8e3; color: #8a6d3b; }
</style>

@php
  $selisihLabel = function ($d) {
      if ($d == 0) return ['Pas', 'tk-pas'];
      return $d < 0 ? ['Kurang ' . showRupiah(abs($d)), 'tk-kurang'] : ['Lebih ' . showRupiah($d), 'tk-lebih'];
  };
@endphp

<div class="content-wrapper">
<div class="tk-wrap">
  <h3 style="margin-top:0">Tutup Kasir &middot; {{ displayDate(date('Y-m-d')) }}</h3>
  <p>Hitung uang di laci, lalu catat berapa yang diserahkan ke owner. Kalau uang fisik tidak sama dengan saldo
     <b>Kas di Tangan</b> di sistem, selisihnya langsung dijurnal: kurang ke <i>Biaya Uang Hilang</i>, lebih ke
     <i>Pendapatan Lain-lain</i>. Dengan begitu saldo sistem kembali sama dengan uang fisik.</p>

  @if(session('tutup_kasir_ok'))
    <div class="alert alert-success">{{ session('tutup_kasir_ok') }}</div>
  @endif

  @if($errors->any())
    <div class="alert alert-danger">
      @foreach($errors->all() as $error)
        <div>{{ $error }}</div>
      @endforeach
    </div>
  @endif

  @if(! $tabelAda)
    <div class="alert alert-warning">
      Tabel tutup kasir belum ada dan gagal dibuat otomatis (akun database mungkin tidak boleh membuat tabel).
      Jalankan sekali file <code>docs/sql/2026-11-24-tutup-kasir.sql</code> di phpMyAdmin, lalu buka halaman ini lagi.
    </div>
  @else

    @if($today)
      <div class="tk-card" style="border-color:#3c763d">
        <h4>Kasir hari ini sudah ditutup</h4>
        @php [$teks, $kelas] = $selisihLabel($today->difference); @endphp
        <table class="tk-table" style="max-width:520px">
          <tr><td>Saldo sistem saat ditutup</td><td class="num">{{ showRupiah($today->system_balance) }}</td></tr>
          <tr><td>Uang fisik di laci</td><td class="num">{{ showRupiah($today->physical_cash) }}</td></tr>
          <tr><td>Selisih</td><td class="num {{ $kelas }}"><b>{{ $teks }}</b></td></tr>
          <tr><td>Diserahkan ke owner</td><td class="num">{{ showRupiah($today->draw_amount) }}</td></tr>
          <tr><td>Sisa di laci</td><td class="num">{{ showRupiah($today->remaining()) }}</td></tr>
          @if($today->note)
            <tr><td>Keterangan</td><td>{{ $today->note }}</td></tr>
          @endif
          <tr><td>Ditutup oleh</td><td>{{ $today->closerName() }}, {{ $today->created_at->format('H:i') }}</td></tr>
        </table>
        @if($bolehBatal)
          <form method="POST" action="{{ url('admin/cash-closing/' . $today->id . '/cancel') }}" style="margin-top:12px"
                onsubmit="return confirm('Batalkan tutup kasir hari ini? Jurnal selisih dan penarikannya ikut dihapus.')">
            @csrf
            <button type="submit" class="btn btn-danger btn-flat">Batalkan tutup kasir hari ini</button>
          </form>
        @endif
        <p class="tk-muted" style="margin:10px 0 0">Transaksi setelah ini masuk ke tutup kasir besok.</p>
      </div>
    @else

      @if(count($devices))
        <div class="tk-card">
          <h4>1. Cek Kasir HP/PC</h4>
          <p class="tk-muted">Transaksi yang dibuat saat internet mati baru masuk ke server setelah perangkatnya terhubung.
             Kalau ada yang belum masuk, saldo sistem jadi lebih kecil dari uang fisik dan selisihnya palsu.</p>
          <div class="tk-scroll">
          <table class="tk-table">
            <thead><tr><th>Perangkat</th><th>Terakhir terhubung</th><th>Transaksi masuk hari ini</th><th>Status</th></tr></thead>
            <tbody>
              @foreach($devices as $d)
                <tr>
                  <td>{{ $d['name'] }} <span class="tk-muted">({{ $d['code'] }})</span></td>
                  <td>{{ $d['last_seen_at'] ? $d['last_seen_at']->format('d/m H:i') : 'belum pernah' }}</td>
                  <td>{{ $d['today_count'] }}</td>
                  <td>
                    @if($d['stale'])
                      <span class="tk-badge tk-warn">Tidak terhubung</span>
                      <div class="tk-muted">Kalau dipakai hari ini, buka aplikasinya dan nyalakan internet dulu.</div>
                    @else
                      <span class="tk-badge tk-ok">Terhubung</span>
                    @endif
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
          </div>
        </div>
      @endif

      <div class="tk-card">
        <h4>{{ count($devices) ? '2. ' : '' }}Hitung uang di laci</h4>
        <form method="POST" action="{{ url($role . '/cash-closing') }}" id="tk-form">
          @csrf
          <input type="hidden" name="system_balance_seen" value="{{ number_format($saldo, 2, '.', '') }}">

          <div class="tk-muted">Saldo Kas di Tangan menurut sistem</div>
          <div class="tk-saldo" id="tk-saldo" data-saldo="{{ $saldo }}">{{ showRupiah($saldo) }}</div>
          <p class="tk-muted">Termasuk semua transaksi cash, transaksi lain, biaya lain, dan penarikan sampai saat ini.</p>

          <div class="tk-field">
            <label for="physical_cash"><b>Uang fisik di laci</b> (hasil hitungan)</label>
            <input type="text" inputmode="numeric" autocomplete="off" class="form-control" id="physical_cash" name="physical_cash"
                   value="{{ old('physical_cash') }}" required autofocus>
          </div>
          <div class="tk-live" id="tk-selisih">&nbsp;</div>

          <div class="tk-field">
            <label for="draw_amount"><b>Diserahkan ke owner</b> (masuk Kas di Nanang)</label>
            <input type="text" inputmode="numeric" autocomplete="off" class="form-control" id="draw_amount" name="draw_amount"
                   value="{{ old('draw_amount') }}" placeholder="0">
          </div>
          <div class="tk-live" id="tk-sisa">&nbsp;</div>

          <div class="tk-field" style="max-width:600px">
            <label for="note">Keterangan <span class="tk-muted">(isi alasannya kalau ada selisih)</span></label>
            <textarea class="form-control" id="note" name="note" rows="2" maxlength="1000">{{ old('note') }}</textarea>
          </div>

          <button type="submit" class="btn btn-success btn-flat" style="min-width:220px; font-size:16px">Simpan Tutup Kasir</button>
        </form>
      </div>
    @endif

    <div class="tk-card">
      <h4>Riwayat tutup kasir</h4>
      @if($closings->isEmpty())
        <p class="tk-muted" style="margin:0">Belum ada.</p>
      @else
        <div class="tk-scroll">
        <table class="tk-table">
          <thead>
            <tr>
              <th>Tanggal</th><th class="num">Saldo sistem</th><th class="num">Uang fisik</th><th class="num">Selisih</th>
              <th class="num">Ke owner</th><th class="num">Sisa di laci</th><th>Keterangan</th><th>Ditutup oleh</th>
            </tr>
          </thead>
          <tbody>
            @foreach($closings as $c)
              @php [$teks, $kelas] = $selisihLabel($c->difference); @endphp
              <tr>
                <td style="white-space:nowrap">{{ $c->closing_date->format('d/m/Y') }}</td>
                <td class="num">{{ showRupiah($c->system_balance) }}</td>
                <td class="num">{{ showRupiah($c->physical_cash) }}</td>
                <td class="num {{ $kelas }}">{{ $teks }}</td>
                <td class="num">{{ showRupiah($c->draw_amount) }}</td>
                <td class="num">{{ showRupiah($c->remaining()) }}</td>
                <td>{{ $c->note }}</td>
                <td>{{ $c->closerName() }} <span class="tk-muted">{{ $c->created_at->format('H:i') }}</span></td>
              </tr>
            @endforeach
          </tbody>
        </table>
        </div>
        {{ $closings->links() }}
      @endif
    </div>
  @endif
</div>
</div>

@if($tabelAda && ! $today)
<script>
  (function () {
    var saldo = parseFloat(document.getElementById('tk-saldo').dataset.saldo) || 0;
    var fisik = document.getElementById('physical_cash');
    var serah = document.getElementById('draw_amount');
    var outSelisih = document.getElementById('tk-selisih');
    var outSisa = document.getElementById('tk-sisa');

    function angka(el) { var s = el.value.replace(/[^0-9]/g, ''); return s === '' ? null : parseInt(s, 10); }
    function rupiah(n) { return 'Rp' + n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
    function rapikan(el) {
      var n = angka(el);
      el.value = n === null ? '' : n.toLocaleString('en-US');
    }

    function hitung() {
      var f = angka(fisik), s = angka(serah) || 0;
      if (f === null) {
        outSelisih.innerHTML = '&nbsp;';
        outSisa.innerHTML = '&nbsp;';
        return;
      }
      var d = Math.round((f - saldo) * 100) / 100;
      if (d === 0) outSelisih.innerHTML = '<span class="tk-pas"><b>Pas</b>, tidak ada selisih</span>';
      else if (d < 0) outSelisih.innerHTML = '<span class="tk-kurang"><b>Kurang ' + rupiah(-d) + '</b> dari saldo sistem</span>';
      else outSelisih.innerHTML = '<span class="tk-lebih"><b>Lebih ' + rupiah(d) + '</b> dari saldo sistem</span>';

      outSisa.innerHTML = s > f
        ? '<span class="tk-kurang">Melebihi uang fisik di laci</span>'
        : 'Sisa di laci: <b>' + rupiah(f - s) + '</b>';
    }

    [fisik, serah].forEach(function (el) {
      el.addEventListener('input', function () { rapikan(el); hitung(); });
    });
    rapikan(fisik); rapikan(serah); hitung();

    document.getElementById('tk-form').addEventListener('submit', function (e) {
      var f = angka(fisik), s = angka(serah) || 0;
      if (f === null) return;
      var d = Math.round((f - saldo) * 100) / 100;
      var pesan = 'Simpan tutup kasir?\n\nUang fisik: ' + rupiah(f)
        + '\nSelisih: ' + (d === 0 ? 'pas' : (d < 0 ? 'kurang ' : 'lebih ') + rupiah(Math.abs(d)))
        + '\nDiserahkan ke owner: ' + rupiah(s)
        + '\nSisa di laci: ' + rupiah(f - s);
      if (!confirm(pesan)) e.preventDefault();
    });
  })();
</script>
@endif
