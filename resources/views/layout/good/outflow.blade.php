{{--
    Rincian stok keluar per penyebab untuk satu barang.
    $row  = baris dari \App\Services\StockOutflow::forDisplay()
    $unit = kode satuan dasar barang
--}}
@php
    $outflowColors = ['terjual' => '#00a65a', 'retur' => '#605ca8', 'expired' => '#dd4b39', 'rusak' => '#d81b60', 'hilang' => '#f39c12', 'lainnya' => '#777'];
@endphp
<div style="font-size: 12px; line-height: 1.5; margin-top: 4px;">
    @foreach(\App\Services\StockOutflow::LABELS as $reason => $label)
        @if(($row[$reason] ?? 0) != 0)
            <span style="color: {{ $outflowColors[$reason] }}; white-space: nowrap;">&#9679; {{ $label }}: <b>{{ $row[$reason] + 0 }}</b> {{ $unit }}</span><br>
        @endif
    @endforeach
</div>
