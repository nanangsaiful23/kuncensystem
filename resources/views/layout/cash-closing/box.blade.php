{{--
  Kotak Tutup Kasir di Resume Transaksi Total (admin & kasir).
  Data: $tk dari CashClosingControllerBase::tutupKasirViewData(). Logika: App\Services\TutupKasir.
--}}
@if($tk['boleh'] ?? false)
<style>
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
  .tk-laci { max-width: 760px; }
  .tk-laci td { vertical-align: middle; }
  .tk-laci input.tk-nama { min-width: 140px; }
  .tk-laci input.tk-uang { font-size: 18px; height: 40px; text-align: right; min-width: 150px; }
  .tk-total { font-size: 18px; }
  .tk-live { font-size: 18px; margin: 4px 0 14px; }
  .tk-pas { color: #3c763d; }
  .tk-kurang { color: #a94442; }
  .tk-lebih { color: #31708f; }
  .tk-badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 12px; }
  .tk-ok { background: #dff0d8; color: #3c763d; }
  .tk-warn { background: #fcf8e3; color: #8a6d3b; }
  .tk-sub { margin: 18px 0 8px; font-weight: 600; }
  .tk-perangkat { margin-top: 18px; border-top: 1px solid #eee; padding-top: 10px; }
  .tk-perangkat summary { cursor: pointer; font-weight: 600; }
  .tk-laci-list { display: flex; flex-wrap: wrap; gap: 12px; }
  .tk-laci-card { flex: 1 1 280px; max-width: 440px; border: 1px solid #ddd; border-radius: 6px; padding: 12px; background: #fcfcfc; }
  .tk-laci-head { display: flex; gap: 8px; align-items: center; font-size: 16px; margin-bottom: 6px; }
  .tk-mini { width: 100%; margin-bottom: 8px; }
  .tk-mini td { padding: 3px 0; vertical-align: top; }
  .tk-mini .num { text-align: right; white-space: nowrap; }
  .tk-mini .tk-perk td { border-top: 1px solid #ddd; font-weight: 700; padding-top: 6px; }
  .tk-label { display: block; margin: 6px 0 3px; }
  .tk-laci-card input.tk-uang, .tk-laci-card input.tk-tarik { font-size: 18px; height: 40px; text-align: right; }
  .tk-laci-selisih, .tk-laci-sisa { margin: 4px 0; min-height: 20px; }
  .tk-totals { max-width: 560px; margin: 14px 0; font-size: 15px; }
  .tk-totals td { padding: 6px 10px; }
</style>

@php
  $tkSelisih = function ($d) {
      if ($d == 0) return ['Pas', 'tk-pas'];
      return $d < 0 ? ['Kurang ' . showRupiah(abs($d)), 'tk-kurang'] : ['Lebih ' . showRupiah($d), 'tk-lebih'];
  };
@endphp

<div class="box tk-card" id="tutup-kasir">
  <div class="box-header">
    <h3 class="box-title">
      Tutup Kasir @if(! empty($tk['tanggal'])) &middot; {{ displayDate($tk['tanggal']) }} @endif
    </h3>
  </div>
  <div class="box-body">

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

  @if(! $tk['tabelAda'])
    <div class="alert alert-warning" style="margin:0">
      Tabel tutup kasir belum ada dan gagal dibuat otomatis (akun database mungkin tidak boleh membuat tabel).
      Jalankan sekali file <code>docs/sql/2026-11-24-tutup-kasir.sql</code> di phpMyAdmin, lalu buka halaman ini lagi.
    </div>

  @elseif($tk['tanggal'] === null)
    <p>Untuk tutup kasir, pilih <b>tanggal awal dan tanggal akhir yang sama</b>. Kalau kemarin lupa, pilih tanggal kemarin.</p>

  @elseif($tk['status'] === 'closed')
    @php $c = $tk['closing']; [$teks, $kelas] = $tkSelisih($c->difference); @endphp
    <p><b>Kasir tanggal ini sudah ditutup.</b></p>
    <table class="tk-table" style="max-width:520px">
      <tr><td>Saldo sistem</td><td class="num">{{ showRupiah($c->system_balance) }}</td></tr>
      <tr><td>Uang fisik{{ $c->drawers ? ' (semua laci)' : ' di laci' }}</td><td class="num">{{ showRupiah($c->physical_cash) }}</td></tr>
      <tr><td>Selisih</td><td class="num {{ $kelas }}"><b>{{ $teks }}</b></td></tr>
      <tr><td>Diserahkan ke owner</td><td class="num">{{ showRupiah($c->draw_amount) }}</td></tr>
      <tr><td>Sisa di laci</td><td class="num">{{ showRupiah($c->remaining()) }}</td></tr>
      @if($c->note)
        <tr><td>Keterangan</td><td>{{ $c->note }}</td></tr>
      @endif
      <tr><td>Ditutup oleh</td><td>{{ $c->closerName() }}, {{ $c->created_at->format('d/m/Y H:i') }}</td></tr>
    </table>
    @if($c->drawers)
      <div class="tk-sub">Rincian per laci</div>
      <div class="tk-scroll">
      <table class="tk-table">
        <thead>
          <tr>
            <th>Laci / kasir</th><th class="num">Sisa kemarin</th><th class="num">Uang masuk</th><th class="num">Perkiraan</th>
            <th class="num">Uang fisik</th><th class="num">Selisih laci</th><th class="num">Ditarik</th><th class="num">Sisa di laci</th>
          </tr>
        </thead>
        <tbody>
          @foreach($c->drawers as $l)
            @php
              $masuk = array_key_exists('panduan', $l) ? $l['panduan'] : ($l['penjualan_cash'] ?? null);
              $perk = $l['perkiraan'] ?? null;
              [$teksLaci, $kelasLaci] = $perk !== null ? $tkSelisih(round($l['uang'] - $perk, 2)) : ['–', ''];
            @endphp
            <tr>
              <td style="white-space:nowrap">{{ $l['nama'] }}</td>
              <td class="num">{{ isset($l['sisa_kemarin']) ? showRupiah($l['sisa_kemarin']) : '–' }}</td>
              <td class="num">
                {{ $masuk !== null ? showRupiah($masuk) : '–' }}
                @if(! empty($l['lain_cash']) || ! empty($l['biaya_cash']))
                  <div class="tk-muted" style="white-space:normal; max-width:200px; margin-left:auto">penjualan {{ showRupiah($l['penjualan_cash']) }}, lain +{{ showRupiah($l['lain_cash']) }}, biaya &minus;{{ showRupiah($l['biaya_cash']) }}</div>
                @endif
              </td>
              <td class="num">{{ $perk !== null ? showRupiah($perk) : '–' }}</td>
              <td class="num">{{ showRupiah($l['uang']) }}</td>
              <td class="num {{ $kelasLaci }}">{{ $teksLaci }}</td>
              <td class="num">{{ array_key_exists('tarik', $l) ? showRupiah($l['tarik']) : '–' }}</td>
              <td class="num">{{ array_key_exists('tarik', $l) ? showRupiah($l['uang'] - $l['tarik']) : '–' }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
      </div>
    @endif
    @if(! empty($tk['cek']))
      @php $ck = $tk['cek']; @endphp
      <div class="tk-sub" id="cek-kecocokan">Cek kecocokan</div>
      @if($ck->problem_count === 0)
        <p class="tk-pas" style="margin:0"><b>Semua cocok</b>
          <span class="tk-muted">({{ implode(', ', array_column($ck->results, 'label')) }})</span></p>
      @elseif($tk['role'] === 'admin')
        @include('layout.cash-closing.cek-hasil', ['c' => $ck])
        <p class="tk-muted" style="margin:0">
          @if($ck->resolved_at)
            Ditandai sudah {{ $ck->resolution }} oleh {{ $ck->resolverName() }}, {{ $ck->resolved_at->format('d/m/Y H:i') }}.
          @else
            Tampil di <a href="{{ url('admin') }}#cek-kecocokan">dashboard admin</a> sampai ditandai sudah dibaca / sudah ditangani.
          @endif
        </p>
      @else
        <p style="margin:0">{{ $ck->problem_count }} tidak cocok ({{ implode(', ', array_column($ck->masalah(), 'label')) }}), sudah dilaporkan ke admin.</p>
      @endif
    @endif
    @if($tk['bolehBatal'] && $tk['last'] && $tk['last']->id === $c->id)
      <form method="POST" action="{{ url('admin/cash-closing/' . $c->id . '/cancel') }}" style="margin-top:12px"
            onsubmit="return confirm('Batalkan tutup kasir {{ displayDate($tk['tanggal']) }}? Jurnal selisih dan penarikannya ikut dihapus.')">
        @csrf
        <button type="submit" class="btn btn-danger btn-flat">Batalkan tutup kasir ini</button>
      </form>
    @endif

  @elseif($tk['status'] === 'future')
    <p style="margin:0">Tanggal ini belum terjadi.</p>

  @elseif($tk['status'] === 'blocked')
    <p style="margin:0">Sudah ada tutup kasir tanggal <b>{{ displayDate($tk['last']->closing_date) }}</b>, sesudah tanggal ini.
       Tutup kasir hanya bisa diisi untuk tanggal sesudah tutup kasir terakhir, supaya saldo hari-hari berikutnya tetap benar.</p>

  @else
    @if($tk['lampau'])
      <div class="alert alert-warning">
        <b>Tanggal lampau.</b> Dipakai kalau tutup kasir tanggal ini lupa diinput. Saldo sistem dihitung sampai akhir
        {{ displayDate($tk['tanggal']) }}, dan jurnalnya memakai tanggal itu. Isi uang fisik sesuai hitungan pada tanggal itu.
      </div>
    @endif
    <p class="tk-muted">
      Tutup kasir terakhir: {{ $tk['last'] ? displayDate($tk['last']->closing_date) : 'belum ada' }}.
      Kalau ada tanggal yang terlewat dan ingin diisi, isi dulu tanggal yang lebih awal.
    </p>

    @php
      // Laci dari TutupKasir::daftarLaci(): nama, sisa kemarin, dan panduan dari sistem; uang fisik wajib diisi.
      // Admin boleh menambah laci. Setelah gagal simpan, isian lama dipakai lagi (dicocokkan per kunci laci).
      $tkAdmin = $role === 'admin';
      $tkPutus = collect($tk['devices'])->where('stale', true)->count();
      $tkLama = []; $tkTambahanLama = [];
      if (is_array(old('laci_uang'))) {
          foreach (old('laci_uang') as $i => $u) {
              $k = (string) old('laci_kasir.' . $i);
              $isi = ['nama' => old('laci_nama.' . $i), 'uang' => $u, 'tarik' => old('laci_tarik.' . $i)];
              if ($k === '') { $tkTambahanLama[] = $isi; } else { $tkLama[$k] = $isi; }
          }
      }
      $tkRows = [];
      foreach ($tk['laci'] as $l) {
          $tkRows[] = $l + ['uang' => $tkLama[$l['key']]['uang'] ?? '', 'tarik' => $tkLama[$l['key']]['tarik'] ?? '', 'tetap' => true];
      }
      if ($tkAdmin) {
          foreach ($tkTambahanLama as $t) {
              $tkRows[] = ['key' => '', 'nama' => $t['nama'], 'sisa' => null, 'p' => null, 'perkiraan' => null,
                           'uang' => $t['uang'], 'tarik' => $t['tarik'], 'tetap' => false];
          }
      }
      $tkSisaTakDiketahui = collect($tk['laci'])->contains(fn ($l) => $l['sisa'] === null);
    @endphp

    @if($tkPutus)
      <div class="alert alert-warning" style="padding:8px 12px">
        {{ $tkPutus }} perangkat Kasir HP/PC tidak terhubung. Kalau dipakai hari ini, transaksinya mungkin belum masuk
        ke server dan selisihnya bisa palsu.
        <a href="#tk-perangkat" onclick="document.getElementById('tk-perangkat').open = true">Lihat perangkat</a>
      </div>
    @endif

    <form method="POST" action="{{ url($role . '/cash-closing') }}" id="tk-form">
      @csrf
      <input type="hidden" name="closing_date" value="{{ $tk['tanggal'] }}">
      <input type="hidden" name="system_balance_seen" value="{{ number_format($tk['saldo'], 2, '.', '') }}">

      <div class="tk-muted">Saldo Kas di Tangan menurut sistem{{ $tk['lampau'] ? ' (sampai akhir ' . displayDate($tk['tanggal']) . ')' : '' }}, untuk semua laci</div>
      <div class="tk-saldo" id="tk-saldo" data-saldo="{{ $tk['saldo'] }}">{{ showRupiah($tk['saldo']) }}</div>

      <div class="tk-sub">Hitung tiap laci <span class="tk-muted">(uang fisik wajib diisi, isi 0 kalau lacinya kosong)</span></div>
      @if($tkSisaTakDiketahui)
        <p class="tk-muted">Sisa kemarin belum diketahui karena belum ada tutup kasir dengan penarikan per laci.
           Setelah tutup kasir ini, sisa tiap laci otomatis muncul di tutup kasir berikutnya.</p>
      @endif

      <div class="tk-laci-list" id="tk-laci-body">
        @foreach($tkRows as $r)
          <div class="tk-laci-card" data-perkiraan="{{ $r['perkiraan'] ?? '' }}">
            <input type="hidden" name="laci_kasir[]" value="{{ $r['key'] }}">
            <div class="tk-laci-head">
              @if($r['tetap'])
                <input type="hidden" class="tk-nama" name="laci_nama[]" value="{{ $r['nama'] }}">
                <b>{{ $r['nama'] }}</b>
              @else
                <input type="text" class="form-control tk-nama" name="laci_nama[]" value="{{ $r['nama'] }}" maxlength="60" placeholder="Nama laci">
                <button type="button" class="btn btn-default btn-flat tk-hapus" title="Hapus laci tambahan">&times;</button>
              @endif
            </div>
            <table class="tk-mini">
              <tr><td>Sisa kemarin</td><td class="num">{{ $r['sisa'] !== null ? showRupiah($r['sisa']) : '–' }}</td></tr>
              <tr>
                <td>Uang masuk hari ini
                  @if($r['p'])
                    <div class="tk-muted">penjualan {{ showRupiah($r['p']['penjualan']) }} ({{ $r['p']['jumlah'] }} transaksi)</div>
                    @if($r['p']['lain'])<div class="tk-muted">+ transaksi lain {{ showRupiah($r['p']['lain']) }}</div>@endif
                    @if($r['p']['biaya'])<div class="tk-muted">&minus; biaya lain {{ showRupiah($r['p']['biaya']) }}</div>@endif
                  @endif
                </td>
                <td class="num">{{ $r['p'] ? showRupiah($r['p']['total']) : ($r['tetap'] ? showRupiah(0) : '–') }}</td>
              </tr>
              <tr class="tk-perk"><td>Perkiraan isi laci</td><td class="num">{{ $r['perkiraan'] !== null ? showRupiah($r['perkiraan']) : '–' }}</td></tr>
            </table>

            <label class="tk-label">Uang fisik di laci</label>
            <input type="text" inputmode="numeric" autocomplete="off" class="form-control tk-uang" name="laci_uang[]"
                   value="{{ $r['uang'] }}" placeholder="0" {{ $r['tetap'] ? 'required' : '' }}>
            <div class="tk-laci-selisih">&nbsp;</div>

            <label class="tk-label">Ditarik ke owner</label>
            <input type="text" inputmode="numeric" autocomplete="off" class="form-control tk-tarik" name="laci_tarik[]"
                   value="{{ $r['tarik'] }}" placeholder="0">
            <div class="tk-laci-sisa">&nbsp;</div>
          </div>
        @endforeach
      </div>
      @if($tkAdmin)
        <button type="button" class="btn btn-default btn-flat" id="tk-tambah" style="margin-top:8px">+ Tambah laci</button>
      @endif

      <table class="tk-table tk-totals">
        <tr><td>Total uang fisik semua laci</td><td class="num"><b id="tk-total">Rp0.00</b></td></tr>
        <tr><td>Selisih dengan saldo sistem <span class="tk-muted">(resmi, dijurnal)</span></td><td class="num" id="tk-selisih">&ndash;</td></tr>
        <tr><td>Total ditarik ke owner <span class="tk-muted">(satu jurnal ke Kas di Nanang)</span></td><td class="num"><b id="tk-total-tarik">Rp0.00</b></td></tr>
        <tr><td>Sisa di semua laci</td><td class="num" id="tk-sisa">&ndash;</td></tr>
      </table>

      @if($tk['tanpaKasir']['lain'] || $tk['tanpaKasir']['biaya'])
        <div class="alert alert-warning" style="padding:8px 12px">
          Di tanggal ini ada
          {{ collect([
              $tk['tanpaKasir']['lain'] ? 'transaksi lain cash ' . showRupiah($tk['tanpaKasir']['lain']) : null,
              $tk['tanpaKasir']['biaya'] ? 'biaya lain cash ' . showRupiah($tk['tanpaKasir']['biaya']) : null,
          ])->filter()->implode(' dan ') }}
          yang tidak tercatat kasirnya (diinput sebelum fitur ini ada), jadi tidak masuk perkiraan per laci.
          Totalnya tetap ikut di saldo Kas di Tangan.
        </div>
      @endif

      <div class="tk-field" style="max-width:600px">
        <label for="note">Keterangan <span class="tk-muted">(isi alasannya kalau ada selisih)</span></label>
        <textarea class="form-control" id="note" name="note" rows="2" maxlength="1000">{{ old('note') }}</textarea>
      </div>

      <button type="submit" class="btn btn-success btn-flat" style="min-width:220px; font-size:16px">Simpan Tutup Kasir</button>

      <p class="tk-muted" style="margin-top:12px">Perkiraan isi laci = sisa kemarin + uang masuk hari ini (penjualan cash + transaksi lain
         cash &minus; biaya lain dari kas yang diinput kasir itu). Selisih per laci hanya panduan untuk mencari laci yang kurang;
         selisih resmi yang dijurnal dihitung dari <b>total semua laci</b> dibanding saldo Kas di Tangan.</p>
    </form>

    @if(count($tk['devices']))
      <details id="tk-perangkat" class="tk-perangkat">
        <summary>
          Kasir HP/PC: {{ count($tk['devices']) }} perangkat,
          @if($tkPutus)<span class="tk-badge tk-warn">{{ $tkPutus }} tidak terhubung</span>@else<span class="tk-badge tk-ok">semua terhubung</span>@endif
          <span class="tk-muted">(klik untuk lihat/sembunyikan)</span>
        </summary>
        <p class="tk-muted" style="margin-top:8px">Transaksi yang dibuat saat internet mati baru masuk ke server setelah perangkatnya terhubung.
           Kalau ada yang belum masuk, saldo sistem jadi lebih kecil dari uang fisik dan selisihnya palsu.</p>
        <div class="tk-scroll">
        <table class="tk-table">
          <thead><tr><th>Perangkat</th><th>Terakhir terhubung</th><th>Transaksi masuk hari ini</th><th>Status</th></tr></thead>
          <tbody>
            @foreach($tk['devices'] as $d)
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
      </details>
    @endif

    @if($tkAdmin)
      <template id="tk-laci-baru">
        <div class="tk-laci-card" data-perkiraan="">
          <input type="hidden" name="laci_kasir[]" value="">
          <div class="tk-laci-head">
            <input type="text" class="form-control tk-nama" name="laci_nama[]" value="" maxlength="60" placeholder="Nama laci">
            <button type="button" class="btn btn-default btn-flat tk-hapus" title="Hapus laci tambahan">&times;</button>
          </div>
          <label class="tk-label">Uang fisik di laci</label>
          <input type="text" inputmode="numeric" autocomplete="off" class="form-control tk-uang" name="laci_uang[]" value="" placeholder="0">
          <div class="tk-laci-selisih">&nbsp;</div>
          <label class="tk-label">Ditarik ke owner</label>
          <input type="text" inputmode="numeric" autocomplete="off" class="form-control tk-tarik" name="laci_tarik[]" value="" placeholder="0">
          <div class="tk-laci-sisa">&nbsp;</div>
        </div>
      </template>
    @endif

    <script>
      (function () {
        var saldo = parseFloat(document.getElementById('tk-saldo').dataset.saldo) || 0;
        var list = document.getElementById('tk-laci-body');
        var outTotal = document.getElementById('tk-total');
        var outTarik = document.getElementById('tk-total-tarik');
        var outSelisih = document.getElementById('tk-selisih');
        var outSisa = document.getElementById('tk-sisa');
        var tambah = document.getElementById('tk-tambah');

        function angka(el) { var s = el.value.replace(/[^0-9]/g, ''); return s === '' ? null : parseInt(s, 10); }
        function rupiah(n) { return 'Rp' + n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
        function rapikan(el) { var n = angka(el); el.value = n === null ? '' : n.toLocaleString('en-US'); }
        function kartu() { return Array.prototype.slice.call(list.querySelectorAll('.tk-laci-card')); }
        function teksSelisih(d) {
          if (d === 0) return '<span class="tk-pas"><b>Pas</b></span>';
          return d < 0 ? '<span class="tk-kurang"><b>Kurang ' + rupiah(-d) + '</b></span>'
                       : '<span class="tk-lebih"><b>Lebih ' + rupiah(d) + '</b></span>';
        }

        function hitung() {
          var totalUang = null, totalTarik = 0, lebihTarik = false;
          kartu().forEach(function (c) {
            var u = angka(c.querySelector('.tk-uang')), t = angka(c.querySelector('.tk-tarik')) || 0;
            var perk = c.dataset.perkiraan === '' ? null : parseFloat(c.dataset.perkiraan);
            var outLaci = c.querySelector('.tk-laci-selisih'), outSisaLaci = c.querySelector('.tk-laci-sisa');
            if (u === null) { outLaci.innerHTML = '&nbsp;'; outSisaLaci.innerHTML = '&nbsp;'; }
            else {
              totalUang = (totalUang || 0) + u;
              outLaci.innerHTML = perk === null ? '&nbsp;'
                : 'Dibanding perkiraan: ' + teksSelisih(Math.round((u - perk) * 100) / 100);
              if (t > u) { lebihTarik = true; outSisaLaci.innerHTML = '<span class="tk-kurang">Penarikan melebihi uang di laci</span>'; }
              else outSisaLaci.innerHTML = 'Sisa di laci: <b>' + rupiah(u - t) + '</b>';
            }
            totalTarik += t;
          });
          outTotal.textContent = rupiah(totalUang || 0);
          outTarik.textContent = rupiah(totalTarik);
          if (totalUang === null) { outSelisih.innerHTML = '&ndash;'; outSisa.innerHTML = '&ndash;'; return lebihTarik; }
          outSelisih.innerHTML = teksSelisih(Math.round((totalUang - saldo) * 100) / 100);
          outSisa.innerHTML = rupiah(totalUang - totalTarik);
          return lebihTarik;
        }

        list.addEventListener('input', function (e) {
          if (e.target.classList.contains('tk-uang') || e.target.classList.contains('tk-tarik')) { rapikan(e.target); hitung(); }
        });
        list.addEventListener('click', function (e) {
          var btn = e.target.closest('.tk-hapus');
          if (btn) { btn.closest('.tk-laci-card').remove(); hitung(); }
        });
        if (tambah) {
          tambah.addEventListener('click', function () {
            var c = document.getElementById('tk-laci-baru').content.firstElementChild.cloneNode(true);
            c.querySelector('.tk-nama').value = 'Laci tambahan';
            list.appendChild(c);
            c.querySelector('.tk-uang').focus();
          });
        }
        list.querySelectorAll('.tk-uang, .tk-tarik').forEach(rapikan);
        hitung();

        document.getElementById('tk-form').addEventListener('submit', function (e) {
          if (hitung()) { e.preventDefault(); alert('Ada penarikan yang melebihi uang di lacinya.'); return; }
          var rincian = '', totalUang = 0, totalTarik = 0, ada = false;
          kartu().forEach(function (c) {
            var u = angka(c.querySelector('.tk-uang')), t = angka(c.querySelector('.tk-tarik')) || 0;
            if (u === null) return;
            ada = true; totalUang += u; totalTarik += t;
            rincian += '\n- ' + (c.querySelector('.tk-nama').value || 'Laci') + ': uang ' + rupiah(u) + ', ditarik ' + rupiah(t);
          });
          if (!ada) { e.preventDefault(); alert('Isi uang fisik di laci.'); return; }
          var d = Math.round((totalUang - saldo) * 100) / 100;
          var pesan = 'Simpan tutup kasir {{ displayDate($tk['tanggal']) }}?\n' + rincian
            + '\n\nTotal uang fisik: ' + rupiah(totalUang)
            + '\nSelisih dengan saldo sistem: ' + (d === 0 ? 'pas' : (d < 0 ? 'kurang ' : 'lebih ') + rupiah(Math.abs(d)))
            + '\nTotal ditarik ke owner: ' + rupiah(totalTarik)
            + '\nSisa di semua laci: ' + rupiah(totalUang - totalTarik);
          if (!confirm(pesan)) e.preventDefault();
        });
      })();
    </script>
  @endif

  @if($tk['tabelAda'] && $tk['tanggal'] === null)
    @if($tk['closings']->isEmpty())
      <p class="tk-muted" style="margin:0">Belum ada tutup kasir di periode ini.</p>
    @else
      <div class="tk-sub">Tutup kasir di periode ini</div>
      <div class="tk-scroll">
      <table class="tk-table">
        <thead>
          <tr>
            <th>Tanggal</th><th class="num">Saldo sistem</th><th class="num">Uang fisik</th><th class="num">Selisih</th>
            <th class="num">Ke owner</th><th class="num">Sisa di laci</th><th>Per laci</th><th>Keterangan</th><th>Ditutup oleh</th>
          </tr>
        </thead>
        <tbody>
          @foreach($tk['closings'] as $c)
            @php [$teks, $kelas] = $tkSelisih($c->difference); @endphp
            <tr>
              <td style="white-space:nowrap"><a href="{{ url($role . '/transaction/resumeTotal/' . $c->closing_date->format('Y-m-d') . '/' . $c->closing_date->format('Y-m-d')) }}">{{ $c->closing_date->format('d/m/Y') }}</a></td>
              <td class="num">{{ showRupiah($c->system_balance) }}</td>
              <td class="num">{{ showRupiah($c->physical_cash) }}</td>
              <td class="num {{ $kelas }}">{{ $teks }}</td>
              <td class="num">{{ showRupiah($c->draw_amount) }}</td>
              <td class="num">{{ showRupiah($c->remaining()) }}</td>
              <td style="white-space:nowrap">
                @foreach($c->drawers ?? [] as $l)
                  <div>{{ $l['nama'] }}: {{ showRupiah($l['uang']) }}@if(! empty($l['tarik'])) <span class="tk-muted">(ditarik {{ showRupiah($l['tarik']) }})</span>@endif</div>
                @endforeach
              </td>
              <td>{{ $c->note }}</td>
              <td>{{ $c->closerName() }} <span class="tk-muted">{{ $c->created_at->format('d/m H:i') }}</span></td>
            </tr>
          @endforeach
        </tbody>
      </table>
      </div>
    @endif
  @endif

  </div>
</div>
@endif
