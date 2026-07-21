@extends('layout.user', ['role' => 'admin', 'title' => 'Kondisi Toko'])

@section('content')
@php
    $metrics = array_merge([
        'total_omzet' => 0, 'total_laba' => 0, 'total_hpp' => 0, 'total_diskon' => 0,
        'total_transaksi' => 0, 'rata_transaksi' => 0, 'margin_pct' => 0, 'discount_pct' => 0,
        'cash_in' => 0, 'cash_out' => 0, 'cash_net' => 0, 'cash_end' => 0,
        'receivable_total' => 0, 'receivable_ratio' => 0,
        'stock_hpp' => 0, 'stock_potential_profit' => 0, 'stock_to_sales_ratio' => 0,
        'goods_total' => 0, 'fast_count' => 0, 'slow_count' => 0, 'dead_count' => 0,
        'fast_pct' => 0, 'slow_pct' => 0, 'dead_pct' => 0,
        'reorder_count' => 0, 'review_count' => 0, 'discontinue_count' => 0,
    ], $health['metrics'] ?? []);
    $scores = $health['scores'] ?? [];
    $recommendations = $health['recommendations'] ?? collect();
    $criticalGoods = $health['critical_goods'] ?? collect();
    $topReceivables = $health['top_receivables'] ?? collect();
    $lockedStock = $health['locked_stock'] ?? collect();
    $scoreColor = $scores['color'] ?? 'red';
    $recon = array_merge([
        'total_fisik' => 0, 'total_jurnal' => 0, 'selisih' => 0, 'selisih_pct' => 0,
        'jumlah_barang' => 0, 'jumlah_barang_minus' => 0, 'total_nilai_minus' => 0,
        'barang_minus' => collect(), 'status' => 'perlu_cek', 'account_code' => null,
    ], $health['reconciliation'] ?? []);
    $reconColor = $recon['status'] === 'cocok' ? 'green' : ($recon['status'] === 'wajar' ? 'orange' : 'red');
    $reconLabel = $recon['status'] === 'cocok' ? '✅ Cocok' : ($recon['status'] === 'wajar' ? '⚠️ Wajar' : '🚨 Perlu Dicek');
@endphp

<style>
.sh-wrap { padding: 1rem 1.25rem 2rem; background: #f6f7f9; min-height: 100vh; }
.sh-wrap *, .sh-wrap *:before, .sh-wrap *:after { box-sizing: border-box; }
.sh-wrap { --ink:#172033; --muted:#667085; --line:#d9e0ea; --panel:#fff; --green:#16803c; --orange:#c26418; --red:#c92a2a; --blue:#2563eb; }
.sh-head { display:flex; justify-content:space-between; gap:1rem; flex-wrap:wrap; margin-bottom:1rem; }
.sh-head h1 { margin:0; font-size:1.45rem; font-weight:800; color:var(--ink); }
.sh-head p { margin:.25rem 0 0; color:var(--muted); font-size:.875rem; }
.sh-filter { display:flex; align-items:flex-end; gap:.65rem; flex-wrap:wrap; background:var(--panel); border:1px solid var(--line); border-radius:8px; padding:.85rem 1rem; margin-bottom:1rem; }
.sh-filter label { display:block; font-size:.72rem; color:var(--muted); font-weight:700; text-transform:uppercase; letter-spacing:.04em; }
.sh-filter input { height:34px; border:1px solid var(--line); border-radius:6px; padding:0 .6rem; }
.sh-btn { height:34px; border:0; border-radius:6px; padding:0 1rem; background:var(--blue); color:#fff; font-weight:700; }
.sh-grid { display:grid; gap:.85rem; margin-bottom:1rem; }
.sh-grid-kpi { grid-template-columns:repeat(auto-fill,minmax(170px,1fr)); }
.sh-grid-2 { grid-template-columns:repeat(auto-fit,minmax(330px,1fr)); }
.sh-card { background:var(--panel); border:1px solid var(--line); border-radius:8px; overflow:hidden; }
.sh-card-pad { padding:1rem; }
.sh-title { padding:.8rem 1rem; border-bottom:1px solid var(--line); display:flex; justify-content:space-between; gap:.75rem; align-items:center; }
.sh-title h2 { margin:0; font-size:1rem; color:var(--ink); font-weight:800; }
.sh-title small { color:var(--muted); }
.sh-score { display:grid; grid-template-columns:170px 1fr; gap:1rem; align-items:center; }
.sh-score-ring { width:150px; height:150px; border-radius:50%; display:flex; flex-direction:column; align-items:center; justify-content:center; margin:auto; border:12px solid #dbe7ff; }
.sh-score-ring.green { border-color:#ccebd6; color:var(--green); }
.sh-score-ring.orange { border-color:#ffe0c7; color:var(--orange); }
.sh-score-ring.red { border-color:#ffd5d5; color:var(--red); }
.sh-score-num { font-size:2.5rem; font-weight:900; line-height:1; }
.sh-score-label { font-size:.78rem; font-weight:800; color:inherit; }
.sh-score-bars { display:grid; gap:.75rem; }
.sh-bar-row { display:grid; grid-template-columns:110px 1fr 42px; gap:.65rem; align-items:center; font-size:.84rem; color:var(--muted); }
.sh-bar { height:8px; border-radius:999px; overflow:hidden; background:#e9edf3; }
.sh-bar span { display:block; height:8px; background:var(--blue); border-radius:999px; }
.sh-kpi { padding:.9rem 1rem; border-left:4px solid var(--blue); }
.sh-kpi.green { border-color:var(--green); } .sh-kpi.orange { border-color:var(--orange); } .sh-kpi.red { border-color:var(--red); }
.sh-kpi .label { color:var(--muted); font-size:.72rem; font-weight:800; text-transform:uppercase; letter-spacing:.04em; }
.sh-kpi .value { color:var(--ink); font-size:1.25rem; font-weight:900; margin-top:.2rem; }
.sh-kpi .sub { color:var(--muted); font-size:.78rem; margin-top:.15rem; }
.sh-rec { display:grid; gap:.65rem; }
.sh-rec-item { border:1px solid var(--line); border-radius:8px; padding:.85rem; display:grid; grid-template-columns:90px 1fr; gap:.75rem; }
.sh-priority { align-self:start; text-align:center; border-radius:999px; padding:.25rem .55rem; font-size:.72rem; font-weight:900; text-transform:uppercase; }
.sh-priority.high { background:#ffe5e5; color:var(--red); }
.sh-priority.medium { background:#fff0d9; color:var(--orange); }
.sh-priority.low { background:#e6f5ec; color:var(--green); }
.sh-rec-item h3 { margin:0 0 .25rem; font-size:.98rem; font-weight:800; color:var(--ink); }
.sh-rec-item p { margin:0 0 .25rem; color:var(--muted); font-size:.86rem; }
.sh-rec-item strong { color:var(--ink); }
.sh-table-wrap { overflow:auto; max-height:430px; }
.sh-table { width:100%; border-collapse:collapse; font-size:.9rem; min-width:720px; }
.sh-table th { position:sticky; top:0; background:#f3f6fa; color:#4d5a6f; text-align:left; padding:.65rem .75rem; border-bottom:1px solid var(--line); white-space:nowrap; }
.sh-table td { padding:.65rem .75rem; border-bottom:1px solid #edf1f6; color:var(--ink); vertical-align:middle; }
.sh-table tr:hover td { background:#f8fbff; }
.tr { text-align:right; }
.badge-soft { display:inline-block; border-radius:999px; padding:.2rem .55rem; font-size:.78rem; font-weight:800; }
.badge-fast { background:#e8f7ee; color:var(--green); }
.badge-slow { background:#fff4e6; color:var(--orange); }
.badge-dead { background:#ffe8e8; color:var(--red); }
.badge-info { background:#e9f0ff; color:var(--blue); }
.badge-recon-green { background:#e8f7ee; color:var(--green); }
.badge-recon-orange { background:#fff4e6; color:var(--orange); }
.badge-recon-red { background:#ffe8e8; color:var(--red); }
.unit-list { display:grid; gap:.22rem; min-width:190px; }
.unit-row { display:flex; justify-content:space-between; gap:.55rem; padding:.22rem .45rem; border:1px solid #e5ebf2; border-radius:6px; background:#fbfcfe; font-size:.78rem; }
.unit-row strong { color:var(--ink); }
.unit-row span { color:var(--muted); white-space:nowrap; }
.sh-empty { color:var(--muted); text-align:center; padding:2rem 1rem; }
@media (max-width: 760px) {
    .sh-score { grid-template-columns:1fr; }
    .sh-rec-item { grid-template-columns:1fr; }
}
</style>

<div class="content-wrapper sh-wrap">
    <section class="content">
        <div class="sh-head">
            <div>
                <h1>Kondisi Toko</h1>
                <p>Ringkasan kesehatan keuangan, stok, dan rekomendasi keputusan untuk periode terpilih.</p>
            </div>
        </div>

        <form method="GET" action="{{ route('admin.store-health') }}" class="sh-filter">
            <div>
                <label>Dari tanggal</label>
                <input type="date" name="start_date" value="{{ $startDate }}">
            </div>
            <div>
                <label>Sampai tanggal</label>
                <input type="date" name="end_date" value="{{ $endDate }}">
            </div>
            <button class="sh-btn" type="submit">Terapkan</button>
        </form>

        <div class="sh-grid sh-grid-2">
            <div class="sh-card sh-card-pad">
                <div class="sh-score">
                    <div class="sh-score-ring {{ $scoreColor }}">
                        <div class="sh-score-num">{{ $scores['overall'] ?? 0 }}</div>
                        <div class="sh-score-label">{{ $scores['label'] ?? '-' }}</div>
                    </div>
                    <div class="sh-score-bars">
                        <div class="sh-bar-row">
                            <span>Keuangan</span>
                            <div class="sh-bar"><span style="width:{{ $scores['financial'] ?? 0 }}%"></span></div>
                            <strong>{{ $scores['financial'] ?? 0 }}</strong>
                        </div>
                        <div class="sh-bar-row">
                            <span>Stok</span>
                            <div class="sh-bar"><span style="width:{{ $scores['stock'] ?? 0 }}%"></span></div>
                            <strong>{{ $scores['stock'] ?? 0 }}</strong>
                        </div>
                        <div class="sh-bar-row">
                            <span>Keputusan</span>
                            <div class="sh-bar"><span style="width:{{ $scores['decision'] ?? 0 }}%"></span></div>
                            <strong>{{ $scores['decision'] ?? 0 }}</strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="sh-card">
                <div class="sh-title">
                    <h2>Rekomendasi Prioritas</h2>
                    <small>{{ $startDate }} - {{ $endDate }}</small>
                </div>
                <div class="sh-card-pad">
                    <div class="sh-rec">
                        @forelse($recommendations as $rec)
                            <div class="sh-rec-item">
                                <span class="sh-priority {{ $rec['priority'] }}">{{ $rec['priority'] }}</span>
                                <div>
                                    <h3>{{ $rec['area'] }} - {{ $rec['title'] }}</h3>
                                    <p>{{ $rec['reason'] }}</p>
                                    <p><strong>Aksi:</strong> {{ $rec['action'] }}</p>
                                </div>
                            </div>
                        @empty
                            <div class="sh-empty">Belum ada rekomendasi.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="sh-grid sh-grid-kpi">
            <div class="sh-card sh-kpi green">
                <div class="label">Omzet</div>
                <div class="value">Rp {{ number_format($metrics['total_omzet'], 0, ',', '.') }}</div>
                <div class="sub">{{ number_format($metrics['total_transaksi']) }} transaksi</div>
            </div>
            <div class="sh-card sh-kpi {{ $metrics['margin_pct'] < 12 ? 'red' : 'green' }}">
                <div class="label">Margin laba</div>
                <div class="value">{{ number_format($metrics['margin_pct'], 1, ',', '.') }}%</div>
                <div class="sub">Laba Rp {{ number_format($metrics['total_laba'], 0, ',', '.') }}</div>
            </div>
            <div class="sh-card sh-kpi {{ $metrics['cash_net'] < 0 ? 'red' : 'green' }}">
                <div class="label">Arus kas bersih</div>
                <div class="value">Rp {{ number_format($metrics['cash_net'], 0, ',', '.') }}</div>
                <div class="sub">Saldo akhir Rp {{ number_format($metrics['cash_end'], 0, ',', '.') }}</div>
            </div>
            <div class="sh-card sh-kpi {{ $metrics['receivable_ratio'] > 20 ? 'orange' : '' }}">
                <div class="label">Piutang</div>
                <div class="value">Rp {{ number_format($metrics['receivable_total'], 0, ',', '.') }}</div>
                <div class="sub">{{ number_format($metrics['receivable_ratio'], 1, ',', '.') }}% dari omzet</div>
            </div>
            <div class="sh-card sh-kpi">
                <div class="label">Nilai stok HPP</div>
                <div class="value">Rp {{ number_format($metrics['stock_hpp'], 0, ',', '.') }}</div>
                <div class="sub">{{ number_format($metrics['stock_to_sales_ratio'], 1, ',', '.') }}% dari omzet</div>
            </div>
            <div class="sh-card sh-kpi {{ $metrics['reorder_count'] > 0 ? 'orange' : 'green' }}">
                <div class="label">Perlu reorder</div>
                <div class="value">{{ number_format($metrics['reorder_count']) }}</div>
                <div class="sub">Fast {{ $metrics['fast_count'] }}, Slow {{ $metrics['slow_count'] }}, Dead {{ $metrics['dead_count'] }}</div>
            </div>
        </div>

        <div class="sh-grid sh-grid-2">
            <div class="sh-card">
                <div class="sh-title">
                    <h2>Barang Prioritas</h2>
                    <div style="display:flex; align-items:center; gap:10px;">
                        <a href="{{ route('admin.store-health.export', request()->query()) }}" class="sh-btn" style="background:var(--green); text-decoration:none; display:flex; align-items:center; gap:5px; font-size:0.75rem; height:28px;">
                            <span>📥 Export CSV</span>
                        </a>
                        <small>Reorder, clearance, review</small>
                    </div>
                </div>
                <div class="sh-table-wrap">
                    <table class="sh-table">
                        <thead>
                            <tr>
                                <th>Barang</th>
                                <th>Status</th>
                                <th>Rekomendasi</th>
                                <th class="tr">Stok</th>
                                <th>Harga per Satuan</th>
                                <th class="tr">Days Stock</th>
                                <th class="tr">Omzet</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($criticalGoods as $good)
                                <tr>
                                    <td>{{ $good->nama }}<br><small>{{ $good->kategori }} - {{ $good->merk }}</small></td>
                                    <td><span class="badge-soft badge-{{ $good->status }}">{{ $good->status_label }}</span></td>
                                    <td><span class="badge-soft badge-info">{{ $good->rec_label }}</span></td>
                                    <td class="tr">{{ number_format($good->stok_sekarang, 2, ',', '.') }} {{ $good->satuan }}</td>
                                    <td>
                                        <div class="unit-list">
                                            @foreach(($good->unit_breakdown ?? collect())->take(4) as $unit)
                                                <div class="unit-row">
                                                    <strong>{{ $unit->satuan }} @if($unit->unit_qty > 1) ({{ number_format($unit->unit_qty, 0, ',', '.') }}x) @endif</strong>
                                                    <span>B {{ number_format($unit->buy_price, 0, ',', '.') }} / J {{ number_format($unit->selling_price, 0, ',', '.') }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="tr">{{ $good->days_of_stock >= 9999 ? 'Aman' : number_format($good->days_of_stock) . ' hari' }}</td>
                                    <td class="tr">Rp {{ number_format($good->total_omzet, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="sh-empty">Tidak ada data barang prioritas.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="sh-card">
                <div class="sh-title">
                    <h2>Piutang Prioritas</h2>
                    <small>Member dengan sisa piutang terbesar</small>
                </div>
                <div class="sh-table-wrap">
                    <table class="sh-table">
                        <thead>
                            <tr>
                                <th>Member</th>
                                <th>Telepon</th>
                                <th class="tr">Tagihan</th>
                                <th class="tr">Dibayar</th>
                                <th class="tr">Sisa</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topReceivables as $row)
                                <tr>
                                    <td>{{ $row->nama_member }}</td>
                                    <td>{{ $row->telepon }}</td>
                                    <td class="tr">Rp {{ number_format($row->total_tagihan, 0, ',', '.') }}</td>
                                    <td class="tr">Rp {{ number_format($row->total_pembayaran, 0, ',', '.') }}</td>
                                    <td class="tr"><strong>Rp {{ number_format($row->sisa_piutang, 0, ',', '.') }}</strong></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="sh-empty">Tidak ada piutang terbuka.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="sh-card">
            <div class="sh-title">
                <h2>Modal Terikat di Stok</h2>
                <small>Barang dengan nilai HPP stok terbesar</small>
            </div>
            <div class="sh-table-wrap">
                <table class="sh-table">
                    <thead>
                        <tr>
                            <th>Barang</th>
                            <th>Kategori</th>
                            <th class="tr">Stok</th>
                            <th>Harga per Satuan</th>
                            <th class="tr">Nilai HPP</th>
                            <th class="tr">Potensi Laba</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($lockedStock as $row)
                            <tr>
                                <td>{{ $row->nama_barang }}<br><small>{{ $row->kode }}</small></td>
                                <td>{{ $row->kategori }} - {{ $row->merk }}</td>
                                <td class="tr">{{ number_format($row->stok_akhir, 2, ',', '.') }} {{ $row->satuan }}</td>
                                <td>
                                    <div class="unit-list">
                                        @foreach(($row->unit_breakdown ?? collect())->take(5) as $unit)
                                            <div class="unit-row">
                                                <strong>{{ $unit->satuan }} @if($unit->unit_qty > 1) ({{ number_format($unit->unit_qty, 0, ',', '.') }}x) @endif</strong>
                                                <span>B {{ number_format($unit->buy_price, 0, ',', '.') }} / J {{ number_format($unit->selling_price, 0, ',', '.') }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="tr"><strong>Rp {{ number_format($row->nilai_hpp, 0, ',', '.') }}</strong></td>
                                <td class="tr">Rp {{ number_format($row->potensi_laba, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="sh-empty">Tidak ada data valuasi stok.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="sh-card" style="margin-top:1rem">
            <div class="sh-title">
                <h2>🔍 Rekonsiliasi Persediaan (Fisik vs Jurnal)</h2>
                <small>
                    Per {{ $endDate }}
                    <span class="badge-soft badge-recon-{{ $reconColor }}" style="margin-left:.4rem">{{ $reconLabel }}</span>
                </small>
            </div>
            <div class="sh-card-pad">
                @if(!$recon['account_code'])
                <div class="sh-empty" style="padding:1rem 0">
                    Kode akun Persediaan belum diset. Cek <code>PERSEDIAAN_ACCOUNT_CODE</code>
                    di <code>StoreHealthRepository</code> dan sesuaikan dengan kode akun di tabel <code>accounts</code>.
                </div>
                @endif
                <div class="sh-grid sh-grid-kpi" style="margin-bottom:.75rem">
                    <div class="sh-card sh-kpi">
                        <div class="label">Nilai Fisik (Stok x Harga Beli)</div>
                        <div class="value">Rp {{ number_format($recon['total_fisik'], 0, ',', '.') }}</div>
                        <div class="sub">{{ number_format($recon['jumlah_barang']) }} barang</div>
                    </div>
                    <div class="sh-card sh-kpi">
                        <div class="label">Saldo Akun Persediaan (Jurnal)</div>
                        <div class="value">Rp {{ number_format($recon['total_jurnal'], 0, ',', '.') }}</div>
                        <div class="sub">kode akun: {{ $recon['account_code'] ?? '-' }}</div>
                    </div>
                    <div class="sh-card sh-kpi {{ $reconColor }}">
                        <div class="label">Selisih</div>
                        <div class="value">Rp {{ number_format($recon['selisih'], 0, ',', '.') }}</div>
                        <div class="sub">{{ $recon['selisih_pct'] }}% dari saldo jurnal</div>
                    </div>
                    <div class="sh-card sh-kpi {{ $recon['jumlah_barang_minus'] > 0 ? 'red' : 'green' }}">
                        <div class="label">Barang Stok Minus</div>
                        <div class="value">{{ number_format($recon['jumlah_barang_minus']) }}</div>
                        <div class="sub">Rp {{ number_format($recon['total_nilai_minus'], 0, ',', '.') }} distorsi nilai</div>
                    </div>
                </div>

                @if($recon['jumlah_barang_minus'] > 0)
                <div class="sh-table-wrap">
                    <table class="sh-table">
                        <thead>
                            <tr>
                                <th>Barang</th>
                                <th class="tr">Stok</th>
                                <th class="tr">Harga Beli</th>
                                <th class="tr">Nilai (Distorsi)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recon['barang_minus'] as $bm)
                            <tr>
                                <td>{{ $bm->nama }}<br><small>{{ $bm->kode }}</small></td>
                                <td class="tr" style="color:var(--red);font-weight:800">{{ number_format($bm->stok, 0, ',', '.') }}</td>
                                <td class="tr">Rp {{ number_format($bm->harga_beli, 0, ',', '.') }}</td>
                                <td class="tr" style="color:var(--red)">Rp {{ number_format($bm->nilai, 0, ',', '.') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="sh-empty">Tidak ada barang dengan stok minus. 👍</div>
                @endif
            </div>
        </div>
    </section>
</div>
@endsection