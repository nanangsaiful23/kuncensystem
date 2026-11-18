@extends('layout.user', ['role' => 'admin', 'title' => 'Penataan Kategori', 'default' => ['page_name' => 'Penataan Kategori']])

@section('content')
@include('admin.sidak.style')
<style>
.pk-table { width: 100%; border-collapse: collapse; font-size: 14px; }
.pk-table th, .pk-table td { padding: 6px 8px; border-top: 1px solid var(--bdr); text-align: right; }
.pk-table th:first-child, .pk-table td:first-child { text-align: left; }
.pk-table th { font-size: 12px; color: var(--ink2); font-weight: 700; border-top: 0; }
.pk-scroll { overflow-x: auto; }
.pk-step { display: flex; gap: 10px; align-items: flex-start; }
.pk-num { flex: none; width: 28px; height: 28px; border-radius: 50%; background: var(--blue); color: #fff;
          font-weight: 700; display: flex; align-items: center; justify-content: center; }
.pk-check { display: flex; gap: 8px; align-items: flex-start; margin: 10px 0; font-size: 15px; color: var(--ink); }
.pk-check input { width: 20px; height: 20px; margin-top: 2px; flex: none; }
.sdk { max-width: 900px; }
</style>

<div class="content-wrapper">
<div class="sdk">
    <h1>Penataan Kategori</h1>
    <p class="sdk-sub">Tahap 3: pindahkan barang ke kategori dan jenis barang baru. Usulan tidak mengubah apa pun; database hanya berubah saat CSV yang sudah diperiksa diunggah.</p>

    @if(session('pesan'))
        <div class="sdk-msg info">{{ session('pesan') }}</div>
    @endif
    @if($errors->any())
        <div class="sdk-msg bad">{{ $errors->first() }}</div>
    @endif
    @if($ruleErrors)
        <div class="sdk-msg bad">Ada aturan yang bermasalah: {{ implode(' ', $ruleErrors) }}</div>
    @endif

    @if($hasil = session('hasil'))
        <div class="sdk-card">
            <h2>Hasil penerapan</h2>
            <div class="sdk-stats" style="grid-template-columns: repeat(3, 1fr)">
                <div class="sdk-stat ok"><b>{{ $hasil['diubah'] }}</b><small>barang dipindah</small></div>
                <div class="sdk-stat"><b>{{ $hasil['nama_diubah'] }}</b><small>nama diubah</small></div>
                <div class="sdk-stat {{ count($hasil['dilewati']) ? 'warn' : '' }}"><b>{{ count($hasil['dilewati']) }}</b><small>baris dilewati</small></div>
            </div>
            <div class="sdk-item-sub">Backup sebelum penerapan: <b>{{ $hasil['backup'] }}</b></div>
            @if($hasil['dilewati'])
                <details style="margin-top:8px">
                    <summary>Lihat baris yang dilewati</summary>
                    <ul style="font-size:13px;color:var(--ink2);margin-top:6px">
                        @foreach(array_slice($hasil['dilewati'], 0, 300) as $s)
                            <li>{{ $s }}</li>
                        @endforeach
                    </ul>
                </details>
            @endif
        </div>
    @endif

    <div class="sdk-stats" style="grid-template-columns: repeat(2, 1fr)">
        <div class="sdk-stat ok"><b>{{ number_format($jumlahPemetaan, 0, ',', '.') }}</b><small>barang punya usulan</small></div>
        <div class="sdk-stat warn"><b>{{ number_format($jumlahTidak, 0, ',', '.') }}</b><small>barang tidak terpetakan</small></div>
    </div>

    <div class="sdk-card">
        <div class="pk-step">
            <div class="pk-num">1</div>
            <div style="flex:1">
                <h2>Unduh usulan</h2>
                <p class="sdk-item-sub">Urutan: barang berstok dulu, Peralatan Rumah Tangga dulu. Buka di Excel, periksa kolom <b>jenis baru</b> dan <b>nama baru</b>. Baris yang salah boleh dikoreksi (isi nama jenis yang benar) atau dihapus. Jangan ubah kolom id, nama, kategori_lama_id, dan jenis_lama_id.
                    Di CSV <b>tidak terpetakan</b>, isi kolom <b>jenis baru</b> untuk barang yang sudah Anda putuskan (boleh juga <b>nama baru</b>); baris yang dibiarkan kosong diabaikan. File itu bisa diunggah di langkah 2 dengan cara yang sama.</p>
                <div class="sdk-grid2">
                    <a class="sdk-btn sdk-btn-prim" href="{{ route('admin.penataan-kategori.pemetaan') }}"><i class="fa fa-download"></i> Usulan pemetaan</a>
                    <a class="sdk-btn" href="{{ route('admin.penataan-kategori.tidak-terpetakan') }}"><i class="fa fa-download"></i> Tidak terpetakan</a>
                </div>
            </div>
        </div>
    </div>

    <div class="sdk-card">
        <div class="pk-step">
            <div class="pk-num">2</div>
            <div style="flex:1">
                <h2>Terapkan CSV yang sudah diperiksa</h2>
                <p class="sdk-item-sub">Sebelum menulis, kolom nama/jenis/kategori semua barang otomatis disalin ke tabel backup. Baris yang barangnya sudah berubah sejak CSV diunduh akan dilewati.</p>
                <form method="POST" action="{{ route('admin.penataan-kategori.terapkan') }}" enctype="multipart/form-data"
                      onsubmit="return confirm('Terapkan CSV ini ke data barang?')">
                    {{ csrf_field() }}
                    <input class="sdk-input" type="file" name="file" accept=".csv,text/csv" required>
                    <label class="pk-check"><input type="checkbox" name="sudah_backup" value="1" required>
                        <span>Saya sudah membuat export database lewat phpMyAdmin hari ini.</span></label>
                    <button type="submit" class="sdk-btn sdk-btn-ok"><i class="fa fa-check"></i> Terapkan</button>
                </form>
            </div>
        </div>
    </div>

    <div class="sdk-card">
        <h2>Ringkasan per kategori lama</h2>
        <div class="pk-scroll">
            <table class="pk-table">
                <tr><th>Kategori</th><th>Barang</th><th>Berstok</th><th>Berubah</th><th>Tidak terpetakan</th><th>Sudah sesuai</th></tr>
                @foreach($ringkasan as $r)
                    <tr>
                        <td>{{ $r['kategori'] }}</td>
                        <td>{{ $r['barang'] }}</td>
                        <td>{{ $r['berstok'] }}</td>
                        <td>{{ $r['terpetakan'] }}</td>
                        <td style="{{ $r['tidak_terpetakan'] ? 'color:var(--warn);font-weight:700' : '' }}">{{ $r['tidak_terpetakan'] }}</td>
                        <td>{{ $r['tetap'] }}</td>
                    </tr>
                @endforeach
            </table>
        </div>
    </div>

    <div class="sdk-card">
        <h2>Pulihkan dari backup</h2>
        @if($backups)
            <p class="sdk-item-sub">Mengembalikan nama, jenis, dan kategori semua barang ke kondisi saat backup dibuat.</p>
            <form method="POST" action="{{ route('admin.penataan-kategori.pulihkan') }}" onsubmit="return confirm('Kembalikan data barang ke backup ini?')">
                {{ csrf_field() }}
                <select class="sdk-input" name="backup">
                    @foreach($backups as $b)
                        <option value="{{ $b }}">{{ $b }}</option>
                    @endforeach
                </select>
                <label class="pk-check"><input type="checkbox" name="yakin_pulihkan" value="1" required>
                    <span>Saya yakin ingin memulihkan.</span></label>
                <button type="submit" class="sdk-btn sdk-btn-bad"><i class="fa fa-undo"></i> Pulihkan</button>
            </form>
        @else
            <div class="sdk-empty">Belum ada backup. Backup dibuat otomatis setiap kali CSV diterapkan.</div>
        @endif
    </div>
</div>
</div>
@endsection
