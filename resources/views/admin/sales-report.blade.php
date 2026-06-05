<style>
    :root {
        --primary:   #2563eb;
        --success:   #16a34a;
        --warning:   #d97706;
        --danger:    #dc2626;
        --muted:     #6b7280;
        --surface:   #f8fafc;
        --border:    #e2e8f0;
    }

    /* ── Layout ─────────────────────────────── */
    .report-wrapper    { background: var(--surface) !important; min-height: 100vh; }
    .report-header     { margin-bottom: 1.5rem; }
    .report-header h1  { font-size: 1.5rem; font-weight: 700; color: #1e293b; margin: 0; }
    .report-header p   { color: var(--muted); margin: .25rem 0 0; font-size: .875rem; }

    /* ── Filter Bar ─────────────────────────── */
    .filter-bar        { background: #fff; border: 1px solid var(--border); border-radius: .75rem;
                         padding: 1rem 1.25rem; margin-bottom: 1.5rem; display: flex;
                         align-items: flex-end; gap: 1rem; flex-wrap: wrap; }
    .filter-group      { display: flex; flex-direction: column; gap: .3rem; }
    .filter-group label{ font-size: .75rem; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: .05em; }
    .filter-group input,
    .filter-group select{ padding: .45rem .75rem; border: 1px solid var(--border); border-radius: .5rem;
                          font-size: .875rem; color: #334155; background: #fff; }
    .btn-filter        { padding: .5rem 1.25rem; background: var(--primary); color: #fff;
                         border: none; border-radius: .5rem; font-size: .875rem; font-weight: 600;
                         cursor: pointer; align-self: flex-end; }
    .btn-filter:hover  { background: #1d4ed8; }

    /* ── Section Tabs ───────────────────────── */
    .tab-nav           { display: flex; gap: .25rem; margin-bottom: 1.25rem; flex-wrap: wrap; }
    .tab-btn           { padding: .45rem 1rem; border: 1px solid var(--border); border-radius: .5rem;
                         font-size: .8125rem; font-weight: 500; background: #fff; color: var(--muted);
                         cursor: pointer; transition: all .15s; }
    .tab-btn.active,
    .tab-btn:hover     { background: var(--primary); color: #fff; border-color: var(--primary); }
    .tab-pane          { display: none; }
    .tab-pane.active   { display: block; }

    /* ── KPI Cards ──────────────────────────── */
    .kpi-grid          { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
                         gap: 1rem; margin-bottom: 1.5rem; }
    .kpi-card          { background: #fff; border: 1px solid var(--border); border-radius: .75rem;
                         padding: 1.1rem 1.25rem; }
    .kpi-card .label   { font-size: .75rem; font-weight: 600; color: var(--muted);
                         text-transform: uppercase; letter-spacing: .05em; margin-bottom: .4rem; }
    .kpi-card .value   { font-size: 1.375rem; font-weight: 700; color: #1e293b; }
    .kpi-card .sub     { font-size: .75rem; color: var(--muted); margin-top: .2rem; }
    .kpi-card.green .value { color: var(--success); }
    .kpi-card.blue  .value { color: var(--primary); }
    .kpi-card.orange .value{ color: var(--warning); }
    .kpi-card.red   .value { color: var(--danger); }

    /* ── Cards ──────────────────────────────── */
    .card              { background: #fff; border: 1px solid var(--border); border-radius: .75rem;
                         margin-bottom: 1.25rem; overflow: hidden; }
    .card-header       { padding: .875rem 1.25rem; border-bottom: 1px solid var(--border);
                         display: flex; align-items: center; justify-content: space-between; }
    .card-title        { font-size: 2rem; font-weight: 700; color: #1e293b; margin: 0; }
    .card-body         { padding: 1.25rem; }

    /* ── Table ──────────────────────────────── */
    .tbl-wrap          { overflow-x: auto; }
    .tbl-scroll-v      { max-height: 400px; overflow-y: auto; border: 1px solid var(--border); border-radius: 0.5rem; }
    /* Membuat header tetap di atas saat scroll vertikal */
    .tbl-scroll-v thead th { position: sticky; top: 0; z-index: 10; box-shadow: 0 1px 0 var(--border); }

    table              { width: 100%; border-collapse: collapse; font-size: 1.5rem; }
    thead th           { background: var(--surface); padding: .6rem .875rem; text-align: left;
                         font-weight: 700; color: #475569; border-bottom: 2px solid var(--border);
                         white-space: nowrap; }
    tbody td           { padding: .55rem .875rem; border-bottom: 1px solid var(--border); color: #334155; }
    tbody tr:last-child td { border-bottom: none; }
    tbody tr:hover td  { background: #f1f5f9; }
    .text-right        { text-align: right; }
    .text-center       { text-align: center; }

    /* ── Badges ─────────────────────────────── */
    .badge             { display: inline-flex; align-items: center; padding: .2rem .6rem;
                         border-radius: 9999px; font-size: .7rem; font-weight: 600; }
    .badge-green       { background: #dcfce7; color: #15803d; }
    .badge-blue        { background: #dbeafe; color: #1d4ed8; }
    .badge-orange      { background: #fef3c7; color: #b45309; }
    .badge-red         { background: #fee2e2; color: #b91c1c; }

    /* ── 2-col Grid ─────────────────────────── */
    .grid-2, .grid-3   { display: grid; gap: 1.25rem; width: 100%; margin-bottom: 1.25rem; }
    .grid-2            { grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); }
    .grid-3            { grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); }
    
    /* Menghilangkan margin bawah card di dalam grid agar tidak double spacing */
    .grid-2 > .card, .grid-3 > .card { margin-bottom: 0; }

    /* ── Progress bars ──────────────────────── */
    .progress-wrap     { width: 100%; background: #e2e8f0; border-radius: 9999px; height: 6px; }
    .progress-bar      { height: 6px; border-radius: 9999px; background: var(--primary); }

    /* ── Laba-Rugi summary ──────────────────── */
    .pl-table tr.total td { font-weight: 700; border-top: 2px solid var(--border); }
    .pl-table tr.sub-total td { font-weight: 600; background: #f8fafc; }
    .pl-table td:last-child   { text-align: right; }

    /* ── Responsive ─────────────────────────── */
    @media (max-width: 640px) {
        .filter-bar  { flex-direction: column; }
        .kpi-grid    { grid-template-columns: repeat(2, 1fr); }
    }
</style>
@extends('layout.user', ['role' => 'admin', 'title' => 'Admin'])

@section('content')

<div class="content-wrapper report-wrapper">
    <section class="content">
    
    {{-- Header --}}
    <div class="report-header">
        <h1>📊 Laporan Penjualan &amp; Keuangan</h1>
        <p>Data terkini per {{ \Carbon\Carbon::now()->isoFormat('dddd, D MMMM Y') }}</p>
    </div>

    {{-- ── Filter Bar ──────────────────────────────────────────────── --}}
    <form method="GET" action="{{ route('admin.reports.sales') }}" id="filterForm">
        <div class="filter-bar">
            <div class="filter-group">
                <label>Dari Tanggal</label>
                <input type="date" name="start_date" value="{{ $startDate }}">
            </div>
            <div class="filter-group">
                <label>Sampai Tanggal</label>
                <input type="date" name="end_date" value="{{ $endDate }}">
            </div>
            <div class="filter-group">
                <label>Tahun (Trend)</label>
                <select name="year">
                    @foreach($availableYears as $y)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn-filter">🔍 Terapkan Filter</button>
        </div>
    </form>

    {{-- ── Tab Navigation ──────────────────────────────────────────── --}}
    <div class="tab-nav">
        <button class="tab-btn active" onclick="showTab('penjualan', this)">📦 Penjualan</button>
        <button class="tab-btn" onclick="showTab('produk', this)">🛒 Produk</button>
        <button class="tab-btn" onclick="showTab('pelanggan', this)">👥 Pelanggan</button>
        <button class="tab-btn" onclick="showTab('pembelian', this)">🚚 Pembelian &amp; Stok</button>
        <button class="tab-btn" onclick="showTab('keuangan', this)">💰 Keuangan</button>
    </div>

    {{-- ================================================================
         TAB 1: PENJUALAN
    ================================================================ --}}
    <div id="tab-penjualan" class="tab-pane active">

        {{-- KPI Cards --}}
        <div class="kpi-grid">
            <div class="kpi-card blue">
                <div class="label">Total Omzet</div>
                <div class="value">Rp {{ number_format($summary['totalOmzet'], 0, ',', '.') }}</div>
                <div class="sub">{{ number_format($summary['totalTransaksi']) }} transaksi</div>
            </div>
            <div class="kpi-card green">
                <div class="label">Laba Kotor</div>
                <div class="value">Rp {{ number_format($summary['totalLaba'], 0, ',', '.') }}</div>
                <div class="sub">
                    Margin
                    @if($summary['totalOmzet'] > 0)
                        {{ number_format($summary['totalLaba'] / $summary['totalOmzet'] * 100, 1) }}%
                    @else 0% @endif
                </div>
            </div>
            <div class="kpi-card orange">
                <div class="label">Total HPP</div>
                <div class="value">Rp {{ number_format($summary['totalHpp'], 0, ',', '.') }}</div>
            </div>
            <div class="kpi-card red">
                <div class="label">Total Diskon</div>
                <div class="value">Rp {{ number_format($summary['totalDiskon'], 0, ',', '.') }}</div>
                <div class="sub">Termasuk voucher</div>
            </div>
            <div class="kpi-card">
                <div class="label">Jml Transaksi</div>
                <div class="value">{{ number_format($summary['totalTransaksi']) }}</div>
            </div>
            <div class="kpi-card">
                <div class="label">Rata-rata / Transaksi</div>
                <div class="value">Rp {{ number_format($summary['rataRata'], 0, ',', '.') }}</div>
            </div>
        </div>

        {{-- Trend Harian --}}
        <div class="card">
            <div class="card-header">
                <span class="card-title">📈 Trend Penjualan Harian</span>
                <span style="font-size:.8125rem;color:var(--muted);">{{ $startDate }} – {{ $endDate }}</span>
            </div>
            <div class="card-body">
                <div class=" tbl-wrap tbl-scroll-v">
                    <table>
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Hari</th>
                                <th class="text-right">Omzet</th>
                                <th class="text-right">Jml Transaksi</th>
                                <th class="text-right">Rata-rata</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($dailyTrend as $row)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($row->tanggal)->isoFormat('D MMM Y') }}</td>
                                <td>{{ \Carbon\Carbon::parse($row->tanggal)->isoFormat('dddd') }}</td>
                                <td class="text-right">Rp {{ number_format($row->omzet, 0, ',', '.') }}</td>
                                <td class="text-right">{{ $row->jumlah_transaksi }}</td>
                                <td class="text-right">Rp {{ number_format($row->jumlah_transaksi > 0 ? $row->omzet / $row->jumlah_transaksi : 0, 0, ',', '.') }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center" style="color:var(--muted);padding:2rem;">Tidak ada data untuk periode ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Trend Bulanan --}}
        <div class="card">
            <div class="card-header">
                <span class="card-title">📅 Trend Penjualan Bulanan — {{ $year }}</span>
            </div>
            <div class="card-body">
                <div class=" tbl-wrap tbl-scroll-v">
                    <table>
                        <thead>
                            <tr>
                                <th>Bulan</th>
                                <th class="text-right">Omzet</th>
                                <th class="text-right">Total Diskon</th>
                                <th class="text-right">Omzet Bersih</th>
                                <th class="text-right">Jml Transaksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $namaBulan = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
                            @endphp
                            @forelse($monthlyTrend as $row)
                            @php $omzetBersih = $row->omzet - $row->total_diskon; @endphp
                            <tr>
                                <td>{{ $namaBulan[$row->bulan - 1] ?? $row->bulan }} {{ $row->tahun }}</td>
                                <td class="text-right">Rp {{ number_format($row->omzet, 0, ',', '.') }}</td>
                                <td class="text-right">Rp {{ number_format($row->total_diskon, 0, ',', '.') }}</td>
                                <td class="text-right">Rp {{ number_format($omzetBersih, 0, ',', '.') }}</td>
                                <td class="text-right">{{ $row->jumlah_transaksi }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center" style="color:var(--muted);padding:2rem;">Tidak ada data.</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr style="font-weight:700;background:#f8fafc;">
                                <td>TOTAL</td>
                                <td class="text-right">Rp {{ number_format($totals['monthly_omzet'], 0, ',', '.') }}</td>
                                <td class="text-right">Rp {{ number_format($totals['monthly_diskon'], 0, ',', '.') }}</td>
                                <td class="text-right">Rp {{ number_format($totals['monthly_bersih'], 0, ',', '.') }}</td>
                                <td class="text-right">{{ number_format($totals['monthly_transaksi']) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        {{-- Metode Pembayaran --}}
        <div class="card">
            <div class="card-header">
                <span class="card-title">💳 Metode Pembayaran</span>
            </div>
            <div class="card-body">
                @php $totalOmzetBayar = $totals['payment_omzet']; @endphp
                <div class=" tbl-wrap tbl-scroll-v">
                    <table>
                        <thead>
                            <tr>
                                <th>Metode</th>
                                <th class="text-right">Jml Transaksi</th>
                                <th class="text-right">Total Omzet</th>
                                <th>Proporsi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($paymentMethods as $pm)
                            @php $pct = $totalOmzetBayar > 0 ? $pm->total_omzet / $totalOmzetBayar * 100 : 0; @endphp
                            <tr>
                                <td><span class="badge badge-blue">{{ $pm->metode_bayar }}</span></td>
                                <td class="text-right">{{ number_format($pm->jumlah_transaksi) }}</td>
                                <td class="text-right">Rp {{ number_format($pm->total_omzet, 0, ',', '.') }}</td>
                                <td style="min-width:120px;">
                                    <div style="display:flex;align-items:center;gap:.5rem;">
                                        <div class="progress-wrap" style="flex:1;">
                                            <div class="progress-bar" style="width:{{ $pct }}%;"></div>
                                        </div>
                                        <span style="font-size:.75rem;color:var(--muted);min-width:36px;">{{ number_format($pct, 1) }}%</span>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center" style="color:var(--muted);">Tidak ada data.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>{{-- end tab-penjualan --}}


    {{-- ================================================================
         TAB 2: PRODUK
    ================================================================ --}}
    <div id="tab-produk" class="tab-pane">

        {{-- Penjelasan logika unit --}}
        <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:.6rem;padding:.875rem 1.1rem;margin-bottom:1.25rem;font-size:.8125rem;color:#1e40af;line-height:1.6;">
            <strong>ℹ️ Catatan Perhitungan:</strong>
            Tabel <em>"Terlaris per Satuan"</em> mengelompokkan data per <strong>produk + satuan</strong>
            (mis. Indomie/Pcs dan Indomie/Dus adalah baris terpisah) agar qty, omzet, dan laba
            dihitung dalam satuan yang konsisten dan tidak campur aduk.
            Tabel <em>"Ringkasan per Produk"</em> menggabungkan semua satuan dengan mengonversi
            qty ke satuan terkecil (<code>qty × units.quantity</code>).
        </div>

        {{-- Top 10 per Satuan (getTopSellingGoods) --}}
        <div class="card">
            <div class="card-header">
                <span class="card-title">🏆 Top Terlaris — per Produk &amp; Satuan</span>
                <span style="font-size:.8rem;color:var(--muted);">Setiap baris = 1 kombinasi produk + satuan</span>
            </div>
            <div class="card-body">
                <div class=" tbl-wrap tbl-scroll-v">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Produk</th>
                                <th>Satuan</th>
                                <th>Kategori</th>
                                <th>Merek</th>
                                <th class="text-right">Qty Terjual<br><small style="font-weight:400">(dlm satuan ini)</small></th>
                                <th class="text-right">Harga Jual Rata²</th>
                                <th class="text-right">Harga Beli Rata²</th>
                                <th class="text-right">Total Omzet</th>
                                <th class="text-right">Total Laba</th>
                                <th class="text-right">Margin</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topGoods as $i => $good)
                            <tr>
                                <td><strong>{{ $i + 1 }}</strong></td>
                                <td>
                                    {{ $good->nama_produk }}
                                    @if($good->kode_produk && $good->kode_produk !== '-')
                                        <br><small style="color:var(--muted)">{{ $good->kode_produk }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-blue">{{ $good->satuan }}</span>
                                    @if($good->unit_qty_konversi > 1)
                                        <br><small style="color:var(--muted)">= {{ $good->unit_qty_konversi }} satuan dasar</small>
                                    @endif
                                </td>
                                <td>{{ $good->kategori }}</td>
                                <td>{{ $good->merk }}</td>
                                <td class="text-right">{{ number_format($good->total_qty, 0, ',', '.') }}</td>
                                <td class="text-right">Rp {{ number_format($good->harga_jual_rata, 0, ',', '.') }}</td>
                                <td class="text-right">Rp {{ number_format($good->harga_beli_rata, 0, ',', '.') }}</td>
                                <td class="text-right">Rp {{ number_format($good->total_omzet, 0, ',', '.') }}</td>
                                <td class="text-right">Rp {{ number_format($good->total_laba, 0, ',', '.') }}</td>
                                <td class="text-right">
                                    <span class="badge {{ $good->margin_pct >= 20 ? 'badge-green' : ($good->margin_pct >= 10 ? 'badge-blue' : 'badge-orange') }}">
                                        {{ number_format($good->margin_pct, 1) }}%
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="11" class="text-center" style="color:var(--muted);padding:2rem;">Tidak ada data.</td></tr>
                            @endforelse
                        </tbody>
                        @if($topGoods->count())
                        <tfoot>
                            <tr style="font-weight:700;background:#f8fafc;">
                                <td colspan="8">TOTAL</td>
                                <td class="text-right">Rp {{ number_format($totals['topgoods_omzet'], 0, ',', '.') }}</td>
                                <td class="text-right">Rp {{ number_format($totals['topgoods_laba'], 0, ',', '.') }}</td>
                                <td class="text-right">{{ number_format($totals['topgoods_margin'], 1) }}%</td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>

        {{-- Ringkasan per Produk (getSalesPerGood) — qty dikonversi ke satuan dasar --}}
        <div class="card">
            <div class="card-header">
                <span class="card-title">📋 Ringkasan per Produk</span>
                <span style="font-size:.8rem;color:var(--muted);">Qty dikonversi ke satuan terkecil — semua unit digabung</span>
            </div>
            <div class="card-body">
                <div class=" tbl-wrap tbl-scroll-v">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Kode</th>
                                <th>Produk</th>
                                <th>Kategori</th>
                                <th>Merek</th>
                                <th class="text-right">Total Qty<br><small style="font-weight:400">(satuan dasar)</small></th>
                                <th class="text-right">Satuan Dasar</th>
                                <th class="text-right">Varian Unit</th>
                                <th class="text-right">Total Omzet</th>
                                <th class="text-right">Total Laba</th>
                                <th class="text-right">Margin</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($salesPerGood as $i => $sg)
                            <tr>
                                <td><strong>{{ $i + 1 }}</strong></td>
                                <td><code style="font-size:.75rem;">{{ $sg->kode_produk }}</code></td>
                                <td>{{ $sg->nama_produk }}</td>
                                <td>{{ $sg->kategori }}</td>
                                <td>{{ $sg->merk }}</td>
                                <td class="text-right">{{ number_format($sg->total_qty_dasar, 0, ',', '.') }}</td>
                                <td class="text-center">
                                    <span class="badge badge-blue">{{ $sg->satuan_dasar }}</span>
                                </td>
                                <td class="text-center">
                                    @if($sg->jumlah_varian_unit > 1)
                                        <span class="badge badge-orange">{{ $sg->jumlah_varian_unit }} unit</span>
                                    @else
                                        <span class="badge badge-green">1 unit</span>
                                    @endif
                                </td>
                                <td class="text-right">Rp {{ number_format($sg->total_omzet, 0, ',', '.') }}</td>
                                <td class="text-right">Rp {{ number_format($sg->total_laba, 0, ',', '.') }}</td>
                                <td class="text-right">
                                    <span class="badge {{ $sg->margin_pct >= 20 ? 'badge-green' : ($sg->margin_pct >= 10 ? 'badge-blue' : 'badge-orange') }}">
                                        {{ number_format($sg->margin_pct, 1) }}%
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="11" class="text-center" style="color:var(--muted);padding:2rem;">Tidak ada data.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="grid-2">
            {{-- Per Kategori --}}
            <div class="card">
                <div class="card-header">
                    <span class="card-title">📂 Penjualan per Kategori</span>
                </div>
                <div class="card-body">
                    @php $totalCat = $totals['cat_omzet']; @endphp
                    <div class=" tbl-wrap tbl-scroll-v">
                        <table>
                            <thead>
                                <tr>
                                    <th>Kategori</th>
                                    <th class="text-right">Omzet</th>
                                    <th class="text-right">%</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($salesByCategory as $cat)
                                @php $pct = $totalCat > 0 ? $cat->total_omzet / $totalCat * 100 : 0; @endphp
                                <tr>
                                    <td>{{ $cat->kategori }}</td>
                                    <td class="text-right">Rp {{ number_format($cat->total_omzet, 0, ',', '.') }}</td>
                                    <td class="text-right">{{ number_format($pct, 1) }}%</td>
                                </tr>
                                @empty
                                <tr><td colspan="3" class="text-center" style="color:var(--muted);">Tidak ada data.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Per Brand --}}
            <div class="card">
                <div class="card-header">
                    <span class="card-title">🏷️ Penjualan per Merek</span>
                </div>
                <div class="card-body">
                    @php $totalBrand = $totals['brand_omzet']; @endphp
                    <div class=" tbl-wrap tbl-scroll-v">
                        <table>
                            <thead>
                                <tr>
                                    <th>Merek</th>
                                    <th class="text-right">Omzet</th>
                                    <th class="text-right">Laba</th>
                                    <th class="text-right">%</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($salesByBrand as $brand)
                                @php $pct = $totalBrand > 0 ? $brand->total_omzet / $totalBrand * 100 : 0; @endphp
                                <tr>
                                    <td>{{ $brand->merk }}</td>
                                    <td class="text-right">Rp {{ number_format($brand->total_omzet, 0, ',', '.') }}</td>
                                    <td class="text-right">Rp {{ number_format($brand->total_laba, 0, ',', '.') }}</td>
                                    <td class="text-right">{{ number_format($pct, 1) }}%</td>
                                </tr>
                                @empty
                                <tr><td colspan="4" class="text-center" style="color:var(--muted);">Tidak ada data.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Voucher --}}
        <div class="card">
            <div class="card-header">
                <span class="card-title">🎟️ Efektivitas Voucher</span>
            </div>
            <div class="card-body">
                <div class=" tbl-wrap tbl-scroll-v">
                    <table>
                        <thead>
                            <tr>
                                <th>Kode Voucher</th>
                                <th class="text-right">Dipakai (x)</th>
                                <th class="text-right">Total Diskon Diberikan</th>
                                <th class="text-right">Total Omzet Terkait</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($voucherReport as $v)
                            <tr>
                                <td><span class="badge badge-orange">{{ $v->kode_voucher }}</span></td>
                                <td class="text-right">{{ $v->jumlah_dipakai }}x</td>
                                <td class="text-right">Rp {{ number_format($v->total_diskon_voucher, 0, ',', '.') }}</td>
                                <td class="text-right">Rp {{ number_format($v->total_omzet_terkait, 0, ',', '.') }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center" style="color:var(--muted);padding:1.5rem;">Tidak ada voucher yang digunakan.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>{{-- end tab-produk --}}


    {{-- ================================================================
         TAB 3: PELANGGAN
    ================================================================ --}}
    <div id="tab-pelanggan" class="tab-pane">

        {{-- Top Member --}}
        <div class="card">
            <div class="card-header">
                <span class="card-title">⭐ 10 Member Paling Aktif</span>
            </div>
            <div class="card-body">
                <div class=" tbl-wrap tbl-scroll-v">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nama Member</th>
                                <th>Nama Toko</th>
                                <th>Telepon</th>
                                <th class="text-right">Jml Transaksi</th>
                                <th class="text-right">Total Omzet</th>
                                <th class="text-right">Total Diskon</th>
                                <th>Transaksi Terakhir</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topMembers as $i => $m)
                            <tr>
                                <td><strong>{{ $i + 1 }}</strong></td>
                                <td>{{ $m->nama_member }}</td>
                                <td>{{ $m->nama_toko ?? '-' }}</td>
                                <td>{{ $m->telepon ?? '-' }}</td>
                                <td class="text-right">{{ number_format($m->jumlah_transaksi) }}</td>
                                <td class="text-right">Rp {{ number_format($m->total_omzet, 0, ',', '.') }}</td>
                                <td class="text-right">Rp {{ number_format($m->total_diskon, 0, ',', '.') }}</td>
                                <td>{{ $m->transaksi_terakhir ? \Carbon\Carbon::parse($m->transaksi_terakhir)->isoFormat('D MMM Y') : '-' }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="8" class="text-center" style="color:var(--muted);padding:2rem;">Tidak ada data member.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Piutang Member --}}
        <div class="card">
            <div class="card-header">
                <span class="card-title">📋 Rekap Piutang Member</span>
            </div>
            <div class="card-body">
                <div class=" tbl-wrap tbl-scroll-v">
                    <table>
                        <thead>
                            <tr>
                                <th>Nama Member</th>
                                <th>Telepon</th>
                                <th class="text-right">Total Tagihan</th>
                                <th class="text-right">Total Dibayar</th>
                                <th class="text-right">Sisa Piutang</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($memberReceivables as $rec)
                            @php $lunas = $rec->sisa_piutang <= 0; @endphp
                            <tr>
                                <td>{{ $rec->nama_member }}</td>
                                <td>{{ $rec->telepon ?? '-' }}</td>
                                <td class="text-right">Rp {{ number_format($rec->total_tagihan, 0, ',', '.') }}</td>
                                <td class="text-right">Rp {{ number_format($rec->total_pembayaran, 0, ',', '.') }}</td>
                                <td class="text-right" style="font-weight:600;color:{{ $lunas ? 'var(--success)' : 'var(--danger)' }};">
                                    Rp {{ number_format(max($rec->sisa_piutang, 0), 0, ',', '.') }}
                                </td>
                                <td>
                                    <span class="badge {{ $lunas ? 'badge-green' : 'badge-red' }}">
                                        {{ $lunas ? 'Lunas' : 'Belum Lunas' }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="text-center" style="color:var(--muted);padding:2rem;">Tidak ada data piutang.</td></tr>
                            @endforelse
                        </tbody>
                        @if($memberReceivables->count() > 0)
                        <tfoot>
                            <tr style="font-weight:700;background:#f8fafc;">
                                <td colspan="2">TOTAL</td>
                                <td class="text-right">Rp {{ number_format($totals['recv_tagihan'], 0, ',', '.') }}</td>
                                <td class="text-right">Rp {{ number_format($totals['recv_dibayar'], 0, ',', '.') }}</td>
                                <td class="text-right" style="color:var(--danger);">Rp {{ number_format(max($totals['recv_sisa'], 0), 0, ',', '.') }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>

    </div>{{-- end tab-pelanggan --}}


    {{-- ================================================================
         TAB 4: PEMBELIAN & STOK
    ================================================================ --}}
    <div id="tab-pembelian" class="tab-pane">

        {{-- Pembelian per Distributor --}}
        <div class="card">
            <div class="card-header">
                <span class="card-title">🚚 Pembelian per Distributor</span>
            </div>
            <div class="card-body">
                <div class=" tbl-wrap tbl-scroll-v">
                    <table>
                        <thead>
                            <tr>
                                <th>Distributor</th>
                                <th>Lokasi</th>
                                <th class="text-right">Jml Loading</th>
                                <th class="text-right">Total Pembelian</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($purchaseByDistributor as $dist)
                            <tr>
                                <td>{{ $dist->nama_distributor }}</td>
                                <td>{{ $dist->lokasi ?? '-' }}</td>
                                <td class="text-right">{{ number_format($dist->jumlah_loading) }}</td>
                                <td class="text-right">Rp {{ number_format($dist->total_pembelian, 0, ',', '.') }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center" style="color:var(--muted);padding:2rem;">Tidak ada data pembelian.</td></tr>
                            @endforelse
                        </tbody>
                        @if($purchaseByDistributor->count() > 0)
                        <tfoot>
                            <tr style="font-weight:700;background:#f8fafc;">
                                <td colspan="2">TOTAL</td>
                                <td class="text-right">{{ number_format($totals['dist_loading']) }}</td>
                                <td class="text-right">Rp {{ number_format($totals['dist_pembelian'], 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>

        {{-- Retur Barang --}}
        <div class="card">
            <div class="card-header">
                <span class="card-title">↩️ Retur Barang ke Distributor</span>
            </div>
            <div class="card-body">
                <div class=" tbl-wrap tbl-scroll-v">
                    <table>
                        <thead>
                            <tr>
                                <th>Tanggal Retur</th>
                                <th>Distributor</th>
                                <th>Barang</th>
                                <th>Satuan</th>
                                <th>Jenis Retur</th>
                                <th class="text-right">Harga Beli</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($returSummary as $r)
                            <tr>
                                <td>{{ $r->returned_date ? \Carbon\Carbon::parse($r->returned_date)->isoFormat('D MMM Y') : '-' }}</td>
                                <td>{{ $r->distributor }}</td>
                                <td>{{ $r->nama_barang }}</td>
                                <td>{{ $r->satuan }}</td>
                                <td><span class="badge badge-orange">{{ $r->jenis_retur }}</span></td>
                                <td class="text-right">Rp {{ number_format($r->harga_beli, 0, ',', '.') }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="text-center" style="color:var(--muted);padding:2rem;">Tidak ada data retur.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Valuasi Stok --}}
        <div class="card">
            <div class="card-header">
                <span class="card-title">📦 Valuasi Stok Saat Ini</span>
                <span style="font-size:.8125rem;color:var(--muted);">
                    HPP: Rp {{ number_format($totals['stock_nilai_hpp'], 0, ',', '.') }}
                    &nbsp;|&nbsp;
                    Nilai Jual: Rp {{ number_format($totals['stock_nilai_jual'], 0, ',', '.') }}
                    &nbsp;|&nbsp;
                    Potensi Laba: Rp {{ number_format($totals['stock_potensi_laba'], 0, ',', '.') }}
                </span>
            </div>
            <div class="card-body">
                <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:.5rem;padding:.7rem 1rem;margin-bottom:1rem;font-size:.8rem;color:#1e40af;">
                    ℹ️ Stok &amp; harga menggunakan <strong>satuan terkecil</strong> (sesuai nilai <code>goods.last_stock</code>).
                    Kolom <em>Stok</em> menunjukkan jumlah dalam satuan tersebut.
                </div>
                <div class=" tbl-wrap tbl-scroll-v">
                    <table>
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Barang</th>
                                <th>Kategori</th>
                                <th>Merek</th>
                                <th>Satuan Dasar</th>
                                <th class="text-right">Stok<br><small style="font-weight:400">(satuan dasar)</small></th>
                                <th class="text-right">Harga Beli/satuan</th>
                                <th class="text-right">Harga Jual/satuan</th>
                                <th class="text-right">Nilai HPP</th>
                                <th class="text-right">Nilai Jual</th>
                                <th class="text-right">Potensi Laba</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($stockValuation as $s)
                            @php
                                $marginStok = ($s->nilai_jual > 0)
                                    ? ($s->potensi_laba / $s->nilai_jual * 100)
                                    : 0;
                            @endphp
                            <tr>
                                <td><code style="font-size:.75rem;">{{ $s->kode }}</code></td>
                                <td>{{ $s->nama_barang }}</td>
                                <td>{{ $s->kategori ?? '-' }}</td>
                                <td>{{ $s->merk ?? '-' }}</td>
                                <td>
                                    <span class="badge badge-blue">{{ $s->satuan }}</span>
                                </td>
                                <td class="text-right">
                                    <span class="{{ $s->stok_akhir <= 0 ? 'badge badge-red' : ($s->stok_akhir <= 5 ? 'badge badge-orange' : '') }}">
                                        {{ number_format($s->stok_akhir, 0, ',', '.') }}
                                    </span>
                                </td>
                                <td class="text-right">Rp {{ number_format($s->harga_beli, 0, ',', '.') }}</td>
                                <td class="text-right">Rp {{ number_format($s->harga_jual, 0, ',', '.') }}</td>
                                <td class="text-right">Rp {{ number_format($s->nilai_hpp, 0, ',', '.') }}</td>
                                <td class="text-right">Rp {{ number_format($s->nilai_jual, 0, ',', '.') }}</td>
                                <td class="text-right">
                                    <span class="badge {{ $marginStok >= 20 ? 'badge-green' : ($marginStok >= 10 ? 'badge-blue' : 'badge-orange') }}">
                                        Rp {{ number_format($s->potensi_laba, 0, ',', '.') }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="11" class="text-center" style="color:var(--muted);padding:2rem;">Tidak ada data stok.</td></tr>
                            @endforelse
                        </tbody>
                        @if($stockValuation->count() > 0)
                        <tfoot>
                            <tr style="font-weight:700;background:#f8fafc;">
                                <td colspan="8">TOTAL</td>
                                <td class="text-right">Rp {{ number_format($totals['stock_nilai_hpp'], 0, ',', '.') }}</td>
                                <td class="text-right">Rp {{ number_format($totals['stock_nilai_jual'], 0, ',', '.') }}</td>
                                <td class="text-right">Rp {{ number_format($totals['stock_potensi_laba'], 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>

    </div>{{-- end tab-pembelian --}}


    {{-- ================================================================
         TAB 5: KEUANGAN
    ================================================================ --}}
    <div id="tab-keuangan" class="tab-pane">

        {{-- Laba Rugi --}}
        <div class="grid-2">
            <div class="card">
                <div class="card-header">
                    <span class="card-title">📊 Ringkasan Laba Rugi</span>
                    <span style="font-size:.8125rem;color:var(--muted);">{{ $startDate }} – {{ $endDate }}</span>
                </div>
                <div class="card-body">
                    <table class="pl-table">
                        <tbody>
                            <tr>
                                <td>Omzet Bruto</td>
                                <td>Rp {{ number_format($profitLoss['omzetBruto'], 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td style="padding-left:1.5rem;color:var(--danger);">(-) Total Diskon</td>
                                <td style="color:var(--danger);">(Rp {{ number_format($profitLoss['totalDiskon'], 0, ',', '.') }})</td>
                            </tr>
                            <tr>
                                <td style="padding-left:1.5rem;color:var(--danger);">(-) Retur Penjualan</td>
                                <td style="color:var(--danger);">(Rp {{ number_format($profitLoss['totalRetur'], 0, ',', '.') }})</td>
                            </tr>
                            <tr class="sub-total">
                                <td>Omzet Bersih</td>
                                <td>Rp {{ number_format($profitLoss['omzetBersih'], 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td style="padding-left:1.5rem;color:var(--danger);">(-) HPP (Harga Pokok Penjualan)</td>
                                <td style="color:var(--danger);">(Rp {{ number_format($profitLoss['totalHpp'], 0, ',', '.') }})</td>
                            </tr>
                            <tr class="total">
                                <td>Laba Kotor</td>
                                <td style="color: {{ $profitLoss['labaKotor'] >= 0 ? 'var(--success)' : 'var(--danger)' }};">
                                    Rp {{ number_format($profitLoss['labaKotor'], 0, ',', '.') }}
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2" style="height:1rem;border:none;"></td>
                            </tr>
                            <tr>
                                <td>Total Pembelian Periode Ini</td>
                                <td>Rp {{ number_format($profitLoss['totalPembelian'], 0, ',', '.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Neraca Saldo --}}
            <div class="card">
                <div class="card-header">
                    <span class="card-title">⚖️ Neraca Saldo</span>
                </div>
                <div class="card-body">
                    <div class=" tbl-wrap tbl-scroll-v">
                        <table>
                            <thead>
                                <tr>
                                    <th>Kode</th>
                                    <th>Akun</th>
                                    <th>Tipe</th>
                                    <th class="text-right">Saldo Awal</th>
                                    <th class="text-right">Mutasi</th>
                                    <th class="text-right">Saldo Akhir</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($trialBalance as $tb)
                                <tr>
                                    <td><code style="font-size:.75rem;">{{ $tb->kode_akun }}</code></td>
                                    <td>{{ $tb->nama_akun }}</td>
                                    <td><span class="badge badge-blue">{{ $tb->tipe_akun }}</span></td>
                                    <td class="text-right">Rp {{ number_format($tb->saldo_awal, 0, ',', '.') }}</td>
                                    <td class="text-right">Rp {{ number_format($tb->mutasi, 0, ',', '.') }}</td>
                                    <td class="text-right" style="font-weight:600;">Rp {{ number_format($tb->saldo_akhir, 0, ',', '.') }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="6" class="text-center" style="color:var(--muted);padding:2rem;">Tidak ada data neraca saldo.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Jurnal Umum --}}
        <div class="card">
            <div class="card-header">
                <span class="card-title">📒 Jurnal Umum</span>
                <span style="font-size:.8125rem;color:var(--muted);">{{ $startDate }} – {{ $endDate }}</span>
            </div>
            <div class="card-body">
                <div class=" tbl-wrap tbl-scroll-v">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Tanggal</th>
                                <th>Keterangan</th>
                                <th>Tipe</th>
                                <th>Akun Debit</th>
                                <th class="text-right">Debit (Rp)</th>
                                <th>Akun Kredit</th>
                                <th class="text-right">Kredit (Rp)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($generalJournal as $j)
                            <tr>
                                <td>{{ $j->id }}</td>
                                <td>{{ \Carbon\Carbon::parse($j->journal_date)->isoFormat('D MMM Y') }}</td>
                                <td>{{ $j->keterangan }}</td>
                                <td><span class="badge badge-blue">{{ $j->tipe }}</span></td>
                                <td>{{ $j->kode_debit }} - {{ $j->akun_debit }}</td>
                                <td class="text-right">Rp {{ number_format($j->debit, 0, ',', '.') }}</td>
                                <td>{{ $j->kode_kredit }} - {{ $j->akun_kredit }}</td>
                                <td class="text-right">Rp {{ number_format($j->credit, 0, ',', '.') }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="8" class="text-center" style="color:var(--muted);padding:2rem;">Tidak ada jurnal untuk periode ini.</td></tr>
                            @endforelse
                        </tbody>
                        @if($generalJournal->count() > 0)
                        <tfoot>
                            <tr style="font-weight:700;background:#f8fafc;">
                                <td colspan="5">TOTAL</td>
                                <td class="text-right">Rp {{ number_format($totals['journal_debit'], 0, ',', '.') }}</td>
                                <td></td>
                                <td class="text-right">Rp {{ number_format($totals['journal_credit'], 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>

    </div>{{-- end tab-keuangan --}}
    </section>
</div>
@section('js-addon')
<script>
function showTab(tabName, btn) {
    // Hide all panes
    document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
    // Deactivate all buttons
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    // Activate selected
    document.getElementById('tab-' + tabName).classList.add('active');
    btn.classList.add('active');
    // Persist active tab in URL hash
    history.replaceState(null, '', '#' + tabName);
}

// Restore tab from URL hash on load
document.addEventListener('DOMContentLoaded', function () {
    const hash = window.location.hash.replace('#', '');
    if (hash) {
        const btn = document.querySelector(`.tab-btn[onclick*="${hash}"]`);
        if (btn) showTab(hash, btn);
    }
});
</script>
@endsection
@endsection
