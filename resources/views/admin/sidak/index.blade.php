@extends('layout.user', ['role' => 'admin', 'title' => 'Sidak Stok Toko', 'default' => ['page_name' => 'Sidak Stok Toko']])

@section('content')
@include('admin.sidak.style')

<div class="content-wrapper">
<div class="sdk">
    <h1>Sidak Stok Toko</h1>
    <p class="sdk-sub">Cek stok fisik beberapa barang secara acak, lalu bandingkan dengan stok di sistem. Sidak tidak mengubah stok.</p>

    <div class="sdk-card">
        <h2>Mulai sidak baru</h2>
        <form method="POST" action="{{ route('admin.sidak.store') }}">
            {{ csrf_field() }}
            <label class="sdk-label" for="note">Catatan (boleh kosong)</label>
            <input class="sdk-input" id="note" name="note" maxlength="255" placeholder="contoh: sidak rak minuman">
            <div style="margin-top:12px">
                <button type="submit" class="sdk-btn sdk-btn-prim"><i class="fa fa-barcode"></i> Mulai Sidak</button>
            </div>
        </form>
    </div>

    @if($open->count() > 0)
        <div class="sdk-card">
            <h2>Sidak yang belum selesai</h2>
            @foreach($open as $inspection)
                <div class="sdk-item">
                    <div class="sdk-item-main">
                        <div class="sdk-item-name">{{ $inspection->created_at->format('d/m/Y H:i') }} &middot; {{ optional($inspection->actor())->name ?? '-' }}</div>
                        <div class="sdk-item-sub">{{ $inspection->details->count() }} barang dicek @if($inspection->note) &middot; {{ $inspection->note }} @endif</div>
                    </div>
                    <a class="sdk-btn sdk-btn-sm sdk-btn-prim" href="{{ route('admin.sidak.show', $inspection->id) }}">Lanjutkan</a>
                </div>
            @endforeach
        </div>
    @endif

    <div class="sdk-card">
        <h2>Riwayat sidak</h2>
        <form method="GET" action="{{ route('admin.sidak.index') }}">
            <div class="sdk-grid2">
                <input class="sdk-input" type="date" name="start_date" value="{{ $startDate }}" aria-label="Dari tanggal">
                <input class="sdk-input" type="date" name="end_date" value="{{ $endDate }}" aria-label="Sampai tanggal">
            </div>
            <div style="margin:10px 0 4px">
                <button type="submit" class="sdk-btn sdk-btn-sm" style="width:100%">Tampilkan</button>
            </div>
        </form>

        @php $finished = $inspections->filter(fn ($i) => $i->isFinished()); @endphp
        @forelse($finished as $inspection)
            @php $s = $inspection->summary(); $selisih = $s['minus'] + $s['plus']; @endphp
            <a class="sdk-item" href="{{ route('admin.sidak.show', $inspection->id) }}" style="text-decoration:none">
                <div class="sdk-item-main">
                    <div class="sdk-item-name">{{ $inspection->created_at->format('d/m/Y H:i') }} &middot; {{ optional($inspection->actor())->name ?? '-' }}</div>
                    <div class="sdk-item-sub">
                        {{ $s['total'] }} barang dicek, {{ $s['match'] }} cocok
                        @if($s['minus_value'] != 0) &middot; kurang Rp{{ number_format(abs($s['minus_value']), 0, ',', '.') }} @endif
                        @if($inspection->note) &middot; {{ $inspection->note }} @endif
                    </div>
                </div>
                <span class="sdk-pill {{ $selisih == 0 ? 'ok' : 'bad' }}">{{ $selisih == 0 ? 'Cocok semua' : $selisih . ' selisih' }}</span>
            </a>
        @empty
            <div class="sdk-empty">Belum ada sidak pada rentang tanggal ini.</div>
        @endforelse
    </div>
</div>
</div>
@endsection
