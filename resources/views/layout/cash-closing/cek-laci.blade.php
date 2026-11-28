{{--
  Cek otomatis: jumlah perkiraan semua laci = saldo sistem (App\Services\TutupKasir::cekJumlahLaci()).
  Data: $cl (hasil cekJumlahLaci), $tkAdmin (bool, tautan hanya untuk admin),
  $daftarLuar (bool, tampilkan daftar jurnal di luar laci; false kalau sudah tampil di kartu "belum dialokasikan").
--}}
@php $daftarLuar = $daftarLuar ?? true; @endphp
@if($cl && ($cl['bisaDicek'] || $daftarLuar))
  @php
    $clTanda = fn ($n) => ($n < 0 ? '&minus;' : '+') . e(showRupiah(abs($n)));
    $clAdaLoading = collect($cl['luar'])->contains(fn ($i) => in_array($i['jenis'], \App\Services\TutupKasir::JURNAL_LOADING, true));
  @endphp
  @if($cl['bisaDicek'] && $cl['cocok'])
    <p class="tk-muted tk-cek-laci-ok">Cek jumlah laci: jumlah perkiraan semua laci sama dengan saldo sistem ({{ showRupiah($cl['saldo']) }}).</p>
  @elseif($cl['bisaDicek'] || $cl['luar'])
    <div class="alert alert-warning tk-cek-laci" style="padding:8px 12px">
      @if($cl['bisaDicek'])
        <b>Jumlah perkiraan semua laci tidak sama dengan saldo sistem.</b>
        Perkiraan semua laci {{ showRupiah($cl['jumlah']) }}, saldo sistem {{ showRupiah($cl['saldo']) }}
        (beda {{ showRupiah(abs($cl['selisih'])) }}). Selisih per laci jadi tidak tepat.
      @else
        Uang Kas di Tangan berikut tidak masuk laci mana pun, jadi tidak ikut perkiraan per laci. Totalnya tetap ikut di saldo sistem.
      @endif
      @if($cl['luar'] && ! $daftarLuar)
        Sumber bedanya ada di kotak "belum dialokasikan ke laci" di atas.
      @elseif($cl['luar'])
        <div style="margin-top:6px">{{ $cl['bisaDicek'] ? 'Sumber bedanya, jurnal Kas di Tangan (1111) yang tidak masuk laci mana pun:' : '' }}</div>
        <ul style="margin:4px 0 0 18px; padding:0">
          @foreach($cl['luar'] as $i)
            <li>
              <b>{!! $clTanda($i['nominal']) !!}</b>
              @if($tkAdmin && ! empty($i['url']))
                <a href="{{ url($i['url']) }}">{{ $i['teks'] }}</a>
              @else
                {{ $i['teks'] }}
              @endif
            </li>
          @endforeach
        </ul>
      @endif
      @if($cl['bisaDicek'] && abs($cl['belumKetemu']) >= 0.01)
        <div style="margin-top:4px">Sisa beda {!! $clTanda($cl['belumKetemu']) !!} belum ketemu sumbernya.</div>
      @endif
      @if($clAdaLoading)
        <div style="margin-top:4px">Loading barang yang dibayar dari Kas di Tangan: pilih lacinya di halaman Ubah Loading, lalu perkiraan laci itu ikut berkurang.</div>
      @endif
    </div>
  @endif
@endif
