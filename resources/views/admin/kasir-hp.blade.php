@extends('layout.user', ['role' => 'admin', 'title' => 'Kasir HP', 'default' => ['page_name' => 'Kasir HP']])

@section('content')
<style>
  .kh-wrap { padding: 15px; max-width: 1000px; }
  .kh-card { background: #fff; border: 1px solid #ddd; border-radius: 6px; padding: 15px; margin-bottom: 15px; }
  .kh-table { width: 100%; border-collapse: collapse; }
  .kh-table th, .kh-table td { padding: 8px 10px; border-bottom: 1px solid #eee; text-align: left; vertical-align: top; }
  .kh-table th { background: #f4f4f4; }
  .kh-muted { color: #888; font-size: 12px; }
  .kh-badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 12px; }
  .kh-on { background: #dff0d8; color: #3c763d; }
  .kh-off { background: #f2dede; color: #a94442; }
  .kh-pair { display: flex; gap: 20px; align-items: center; flex-wrap: wrap; }
  .kh-pair-code { font-size: 36px; font-weight: 700; letter-spacing: 6px; font-family: monospace; }
  .kh-items { margin: 4px 0 0; padding-left: 18px; font-size: 12px; }
  .kh-form-inline { display: flex; gap: 8px; flex-wrap: wrap; }
  .kh-form-inline input[type=text] { flex: 1; min-width: 200px; }
  .kh-scroll { overflow-x: auto; }
</style>

<div class="content-wrapper">
<div class="kh-wrap">
  <h3 style="margin-top:0">Kasir HP</h3>
  <p>HP Android yang boleh dipakai sebagai kasir tambahan (aplikasi Kasir HP). HP hanya bisa membuat transaksi penjualan
     dan mengambil data barang, harga jual, dan member. Transaksi yang dibuat saat internet mati dikirim belakangan.</p>

  @if(session('kasir_hp_ok'))
    <div class="alert alert-success">{{ session('kasir_hp_ok') }}</div>
  @endif

  @if(! $tabelAda)
    <div class="alert alert-warning">
      Tabel Kasir HP belum ada dan gagal dibuat otomatis (akun database mungkin tidak boleh membuat/mengubah tabel).
      Jalankan sekali file <code>docs/sql/2026-11-23-kasir-hp-tahap1.sql</code> di phpMyAdmin, lalu buka halaman ini lagi.
    </div>
  @else

  @if($pair = session('kasir_hp_pair'))
    <div class="kh-card" style="border-color:#3c8dbc">
      <b>Kode pasang untuk {{ $pair['device'] }}</b>
      <div class="kh-pair" style="margin-top:10px">
        <div>{!! qrCodeSvg(json_encode(['server' => url('/'), 'pair_code' => $pair['code']]), 180, 2) !!}</div>
        <div>
          <div class="kh-pair-code">{{ $pair['code'] }}</div>
          <div class="kh-muted">Alamat server: <code>{{ url('/') }}</code></div>
          <div class="kh-muted">Berlaku {{ \App\Models\PosDevice::PAIR_MINUTES }} menit dan hanya sekali pakai.
            Di aplikasi Kasir HP pilih "Pasang perangkat", lalu scan QR ini atau ketik kodenya.</div>
        </div>
      </div>
    </div>
  @endif

  <div class="kh-card">
    <h4 style="margin-top:0">Perangkat</h4>
    <div class="kh-scroll">
    <table class="kh-table">
      <thead>
        <tr>
          <th>Kode</th><th>Nama</th><th>Status</th><th>Terakhir terhubung</th><th>Terakhir kirim</th><th>Hari ini</th><th></th>
        </tr>
      </thead>
      <tbody>
        @forelse($devices as $device)
          @php $t = $today[$device->id] ?? null; @endphp
          <tr>
            <td><b>{{ $device->code }}</b></td>
            <td>{{ $device->name }}
              @if(! $device->token_hash)<div class="kh-muted">belum dipasang di HP</div>@endif
            </td>
            <td><span class="kh-badge {{ $device->is_active ? 'kh-on' : 'kh-off' }}">{{ $device->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
            <td>{{ $device->last_seen_at ? $device->last_seen_at->format('d-m-Y H:i') : '-' }}</td>
            <td>{{ $device->last_sync_at ? $device->last_sync_at->format('d-m-Y H:i') : '-' }}</td>
            <td>{{ $t ? $t->jumlah . ' transaksi, ' . showRupiah($t->total) : '-' }}</td>
            <td style="white-space:nowrap">
              @if($device->is_active)
                <form method="POST" action="{{ route('admin.kasir-hp.pair', $device) }}" style="display:inline">
                  {{ csrf_field() }}
                  <button class="btn btn-xs btn-default" title="Untuk HP baru atau HP yang aplikasinya diinstal ulang">Kode pasang</button>
                </form>
              @endif
              <form method="POST" action="{{ route('admin.kasir-hp.toggle', $device) }}" style="display:inline"
                    onsubmit="return {{ $device->is_active ? "confirm('Nonaktifkan " . e($device->name) . "? HP ini langsung tidak bisa mengirim transaksi. Transaksi yang belum terkirim dari HP ini tertahan.')" : 'true' }}">
                {{ csrf_field() }}
                <button class="btn btn-xs {{ $device->is_active ? 'btn-danger' : 'btn-success' }}">{{ $device->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
              </form>
            </td>
          </tr>
        @empty
          <tr><td colspan="7" class="kh-muted">Belum ada perangkat.</td></tr>
        @endforelse
      </tbody>
    </table>
    </div>

    <form method="POST" action="{{ route('admin.kasir-hp.store') }}" class="kh-form-inline" style="margin-top:15px">
      {{ csrf_field() }}
      <input type="text" name="name" class="form-control" placeholder="Nama HP, mis. HP Kasir 1 (Samsung A05)" maxlength="100" required>
      <button class="btn btn-primary"><i class="fa fa-plus"></i> Tambah perangkat</button>
    </form>
    @if($errors->any())
      <div class="text-danger" style="margin-top:6px">{{ $errors->first() }}</div>
    @endif
  </div>

  <div class="kh-card">
    <h4 style="margin-top:0">Transaksi HP ditolak
      @if($openRejects)<span class="kh-badge kh-off">{{ $openRejects }} belum selesai</span>@endif
    </h4>
    <p class="kh-muted">Transaksi dari HP yang tidak bisa disimpan otomatis (mis. lebih dari 7 hari, total tidak cocok, barang sudah dihapus).
      Cek isinya; bila perlu input manual di kasir desktop, lalu tandai selesai.
      @if($tampilSelesai)
        <a href="{{ route('admin.kasir-hp.index') }}">Sembunyikan yang sudah selesai</a>
      @else
        <a href="{{ route('admin.kasir-hp.index', ['selesai' => 1]) }}">Tampilkan juga yang sudah selesai</a>
      @endif
    </p>
    <div class="kh-scroll">
    <table class="kh-table">
      <thead>
        <tr><th>Diterima</th><th>HP / Nota</th><th>Isi</th><th>Alasan</th><th></th></tr>
      </thead>
      <tbody>
        @forelse($rejects as $reject)
          @php $d = $reject->data(); @endphp
          <tr style="{{ $reject->resolved_at ? 'opacity:.55' : '' }}">
            <td style="white-space:nowrap">{{ $reject->created_at->format('d-m-Y H:i') }}</td>
            <td>{{ $reject->device ? $reject->device->code : '-' }}<br><span class="kh-muted">{{ $reject->receipt_no ?: '-' }}</span></td>
            <td>
              <div class="kh-muted">Waktu jual: {{ is_scalar($d['sold_at'] ?? null) ? $d['sold_at'] : '-' }}
                · {{ is_scalar($d['payment'] ?? null) ? $d['payment'] : '-' }}
                · total {{ is_numeric($d['total'] ?? null) ? showRupiah($d['total']) : '-' }}</div>
              @if(is_array($d['items'] ?? null))
                <ul class="kh-items">
                  @foreach($d['items'] as $item)
                    @php
                      $gu = is_array($item) && is_numeric($item['good_unit_id'] ?? null) ? \App\Models\GoodUnit::withTrashed()->find($item['good_unit_id']) : null;
                      $nama = $gu && $gu->good ? $gu->good->name . ' (' . optional($gu->unit)->name . ')' : 'ID satuan ' . (is_array($item) && is_scalar($item['good_unit_id'] ?? null) ? $item['good_unit_id'] : '?');
                    @endphp
                    <li>{{ $nama }} × {{ is_array($item) && is_scalar($item['quantity'] ?? null) ? $item['quantity'] : '?' }}
                        @ {{ is_array($item) && is_numeric($item['price'] ?? null) ? showRupiah($item['price']) : '?' }}</li>
                  @endforeach
                </ul>
              @endif
            </td>
            <td>{{ $reject->reason }}</td>
            <td>
              <form method="POST" action="{{ route('admin.kasir-hp.resolve', $reject) }}">
                {{ csrf_field() }}
                <button class="btn btn-xs {{ $reject->resolved_at ? 'btn-default' : 'btn-success' }}">{{ $reject->resolved_at ? 'Buka lagi' : 'Tandai selesai' }}</button>
              </form>
            </td>
          </tr>
        @empty
          <tr><td colspan="5" class="kh-muted">Tidak ada.</td></tr>
        @endforelse
      </tbody>
    </table>
    </div>
  </div>

  <div class="kh-card kh-muted">
    Untuk pengembang aplikasi HP: API ada di <code>{{ url('/api/pos/v1') }}</code>, keterangan lengkap di
    <code>docs/rancangan-kasir-hp-tahap1.md</code>.
  </div>

  @endif
</div>
</div>
@endsection
