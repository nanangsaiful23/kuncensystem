@extends('layout.user', ['role' => 'admin', 'title' => 'Sidak Stok Toko', 'default' => ['page_name' => 'Sidak Stok Toko']])

@section('content')
@include('admin.sidak.style')

<div class="content-wrapper">
<div class="sdk">
    <h1>Sidak Stok Toko</h1>
    <p class="sdk-sub">Cek stok fisik beberapa barang secara acak, lalu bandingkan dengan stok di sistem. Sidak tidak mengubah stok.</p>

    @if(session('error'))
        <div class="sdk-msg bad">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="sdk-msg bad">{{ $errors->first() }}</div>
    @endif

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

    @if($adaDaftar ?? false)
        <div class="sdk-card">
            <h2>Sidak dead stock</h2>
            <p class="sdk-sub" style="margin-bottom:6px">
                Sistem membuat daftar barang <b>Dead Stock</b> dan <b>Perlu Dicek</b> yang stoknya masih ada,
                dari modal tertahan terbesar. Barang yang sudah dicek 30 hari terakhir dilewati.
                Daftarnya bisa dicek langsung di HP, atau dikirim ke pegawai lewat teks WA.
            </p>
            <form method="POST" action="{{ route('admin.sidak.dead-stock') }}" id="dead-form">
                {{ csrf_field() }}
                <div class="sdk-grid2">
                    <div>
                        <label class="sdk-label" for="dead-category">Kategori</label>
                        <select class="sdk-input" id="dead-category" name="category">
                            <option value="">Semua kategori</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="sdk-label" for="dead-limit">Jumlah barang</label>
                        <select class="sdk-input" id="dead-limit" name="limit">
                            <option value="10">10</option>
                            <option value="20">20</option>
                            <option value="30" selected>30</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                </div>
                <label class="sdk-label" for="dead-by">Dihitung oleh (nama pegawai, boleh kosong)</label>
                <input class="sdk-input" id="dead-by" name="counted_by" maxlength="100" placeholder="kosongkan kalau Anda sendiri yang mengecek">
                <div style="margin-top:12px">
                    <button type="submit" class="sdk-btn sdk-btn-prim"><i class="fa fa-list-ol"></i> Buat Daftar Dead Stock</button>
                </div>
            </form>
        </div>
    @endif

    @if($open->count() > 0)
        <div class="sdk-card">
            <h2>Sidak yang belum selesai</h2>
            @foreach($open as $inspection)
                @php $nTarget = ($adaDaftar ?? false) ? $inspection->targets->count() : 0; @endphp
                <div class="sdk-item">
                    <div class="sdk-item-main">
                        <div class="sdk-item-name">{{ $inspection->created_at->format('d/m/Y H:i') }} &middot; {{ optional($inspection->actor())->name ?? '-' }}</div>
                        <div class="sdk-item-sub">
                            @if($nTarget)
                                Daftar {{ $inspection->details->whereIn('good_id', $inspection->targets->pluck('good_id'))->count() }}/{{ $nTarget }} barang dicek
                            @else
                                {{ $inspection->details->count() }} barang dicek
                            @endif
                            @if($inspection->counted_by) &middot; dihitung {{ $inspection->counted_by }} @endif
                            @if($inspection->note) &middot; {{ $inspection->note }} @endif
                        </div>
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

@section('js-addon')
<script>
(function () {
    var form = document.getElementById('dead-form');
    if (!form) { return; }
    // Menyusun daftar dead stock butuh beberapa detik; cegah tekan dua kali.
    form.addEventListener('submit', function () {
        var btn = form.querySelector('button[type=submit]');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Menyusun daftar…';
    });
})();
</script>
@endsection
