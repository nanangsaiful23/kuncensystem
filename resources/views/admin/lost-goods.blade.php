@extends('layout.user', ['role' => 'admin', 'title' => 'Barang Sering Hilang', 'default' => $default])

@section('content')
@use('App\Services\BarangSeringHilang')
@use('App\Services\StockOpnameService', 'SO')
@php
    $s = $ringkasan;
    $query = request()->except('export');
@endphp

<style>
.lg { padding: 16px 18px 40px; max-width: 1400px; margin: 0 auto; font-size: 15px;
      --ink:#0f172a; --ink2:#475569; --ink3:#94a3b8; --bdr:#e2e8f0; --card:#fff;
      --bad:#b91c1c; --bad-bg:#fef2f2; --ok:#15803d; --ok-bg:#f0fdf4; --warn:#b45309; --warn-bg:#fffbeb; --blue:#2563eb; }
.lg, .lg * { box-sizing: border-box; }
.lg h1 { font-size: 24px; font-weight: 700; margin: 0 0 4px; color: var(--ink); }
.lg .sub { color: var(--ink2); margin: 0 0 14px; max-width: 900px; }
.lg-card { background: var(--card); border: 1px solid var(--bdr); border-radius: 12px; padding: 14px; margin-bottom: 14px; }
.lg-filter { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 10px; align-items: end; }
.lg-filter label { display: block; font-size: 12px; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: var(--ink3); margin-bottom: 4px; }
.lg-filter input, .lg-filter select { width: 100%; min-height: 40px; padding: 6px 10px; border: 1px solid var(--bdr); border-radius: 8px; background: #fff; color: var(--ink); }
.lg-btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px; min-height: 40px; padding: 6px 14px;
          border-radius: 8px; border: 1px solid var(--bdr); background: #fff; color: var(--ink) !important; font-weight: 600; text-decoration: none !important; cursor: pointer; }
.lg-btn.prim { background: var(--blue); border-color: var(--blue); color: #fff !important; }
.lg-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 10px; margin-bottom: 14px; }
.lg-stat { background: var(--card); border: 1px solid var(--bdr); border-radius: 12px; padding: 10px 12px; }
.lg-stat small { display: block; font-size: 12px; color: var(--ink2); }
.lg-stat b { display: block; font-size: 22px; color: var(--ink); font-variant-numeric: tabular-nums; }
.lg-stat.bad b { color: var(--bad); } .lg-stat.ok b { color: var(--ok); } .lg-stat.warn b { color: var(--warn); }
.lg-table { width: 100%; border-collapse: collapse; font-size: 14px; }
.lg-table th { text-align: left; font-size: 12px; text-transform: uppercase; letter-spacing: .04em; color: var(--ink2);
               border-bottom: 2px solid var(--bdr); padding: 8px 6px; white-space: nowrap; position: sticky; top: 0; background: #fff; }
.lg-table td { border-bottom: 1px solid var(--bdr); padding: 8px 6px; vertical-align: top; }
.lg-table td.n { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
.lg-table tr.berulang td { background: var(--warn-bg); }
.lg-name { font-weight: 600; color: var(--ink); }
.lg-meta { font-size: 12px; color: var(--ink2); }
.lg-pill { display: inline-block; font-weight: 700; font-size: 12px; padding: 2px 8px; border-radius: 999px; white-space: nowrap; }
.lg-pill.bad { background: var(--bad-bg); color: var(--bad); }
.lg-pill.warn { background: var(--warn-bg); color: var(--warn); }
.lg-bad { color: var(--bad); font-weight: 600; }
.lg-ok { color: var(--ok); }
.lg-empty { text-align: center; color: var(--ink2); padding: 20px 0; }
.lg-scroll { overflow-x: auto; }
</style>

<div class="content-wrapper">
<div class="lg">
    <h1>Barang Sering Hilang</h1>
    <p class="sub">
        Hasil semua stock opname dalam periode, dikumpulkan per barang. <b>Kali kurang</b> = berapa stock opname yang
        menemukan barang ini kurang dari stok sistem. Barang yang kurang 2 kali atau lebih diberi warna kuning: itu pola,
        bukan kebetulan. Nilai rupiah memakai harga beli saat stock opname.
    </p>

    <form method="GET" action="{{ route('admin.reports.lost-goods') }}" class="lg-card lg-filter">
        <div>
            <label for="dari">Dari</label>
            <input type="date" id="dari" name="dari" value="{{ $dari }}">
        </div>
        <div>
            <label for="sampai">Sampai</label>
            <input type="date" id="sampai" name="sampai" value="{{ $sampai }}">
        </div>
        <div>
            <label for="kategori">Kategori</label>
            <select id="kategori" name="kategori">
                <option value="">Semua kategori</option>
                @foreach($categories as $c)
                    <option value="{{ $c->id }}" @selected($kategori == $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="distributor">Distributor</label>
            <select id="distributor" name="distributor">
                <option value="">Semua distributor</option>
                @foreach($distributors as $d)
                    <option value="{{ $d->id }}" @selected($distributor == $d->id)>{{ $d->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="min_kurang">Minimal kali kurang</label>
            <select id="min_kurang" name="min_kurang">
                @foreach([1, 2, 3, 5] as $n)
                    <option value="{{ $n }}" @selected($minKurang == $n)>{{ $n }}x atau lebih</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="urut">Urutkan</label>
            <select id="urut" name="urut">
                @foreach(BarangSeringHilang::URUTAN as $key => $label)
                    <option value="{{ $key }}" @selected($urut === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div style="display:flex; gap:8px">
            <button type="submit" class="lg-btn prim"><i class="fa fa-search"></i> Tampilkan</button>
            <a class="lg-btn" href="{{ route('admin.reports.lost-goods', $query + ['export' => 'csv']) }}"><i class="fa fa-download"></i> CSV</a>
        </div>
    </form>

    <div class="lg-stats">
        <div class="lg-stat"><small>Stock opname di periode</small><b>{{ $s['jumlah_so'] }}</b></div>
        <div class="lg-stat bad"><small>Total rugi (barang kurang)</small><b>{{ SO::rupiah($s['rp_kurang']) }}</b></div>
        <div class="lg-stat ok"><small>Total barang lebih</small><b>{{ SO::rupiah($s['rp_lebih']) }}</b></div>
        <div class="lg-stat"><small>Barang pernah kurang</small><b>{{ $s['barang_kurang'] }}</b></div>
        <div class="lg-stat warn"><small>Kurang 2x atau lebih</small><b>{{ $s['berulang'] }}</b></div>
    </div>

    <div class="lg-card lg-scroll">
        @if($rows->isEmpty())
            <div class="lg-empty">Tidak ada barang yang kurang di stock opname pada periode & filter ini.</div>
        @else
            <table class="lg-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Barang</th>
                        <th>Kali kurang</th>
                        <th class="n">Qty kurang</th>
                        <th class="n">Rugi</th>
                        <th class="n">Lebih</th>
                        <th class="n">Bersih</th>
                        <th>Terakhir kurang</th>
                        <th class="n">Stok sekarang</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $i => $row)
                        <tr class="{{ $row->kali_kurang >= 2 ? 'berulang' : '' }}">
                            <td>{{ $i + 1 }}</td>
                            <td>
                                <div class="lg-name">
                                    <a href="{{ url('admin/good/' . $row->id . '/loading/2023-01-01/' . date('Y-m-d') . '/10') }}" target="_blank">{{ $row->nama }}</a>
                                    @if($row->deleted_at)<span class="lg-pill bad">dihapus</span>@endif
                                </div>
                                <div class="lg-meta">{{ $row->code }} · {{ $row->kategori ?: '-' }} · {{ $row->distributor ?: '-' }}</div>
                            </td>
                            <td>
                                <span class="lg-pill {{ $row->kali_kurang >= 2 ? 'warn' : 'bad' }}">{{ $row->kali_kurang }} dari {{ $row->kali_dicek }} SO</span>
                                <div class="lg-meta">{{ $row->persen }}% kurang @if($row->kali_lebih) · {{ $row->kali_lebih }}x lebih @endif</div>
                            </td>
                            <td class="n">{{ SO::angka($row->qty_kurang) }} {{ $row->satuan }}</td>
                            <td class="n lg-bad">{{ SO::rupiah($row->rp_kurang) }}</td>
                            <td class="n lg-ok">{{ $row->rp_lebih > 0 ? SO::rupiah($row->rp_lebih) : '-' }}</td>
                            <td class="n {{ $row->rp_bersih < 0 ? 'lg-bad' : 'lg-ok' }}">{{ $row->rp_bersih < 0 ? '-' : '' }}{{ SO::rupiah(abs($row->rp_bersih)) }}</td>
                            <td>
                                @if($row->terakhir_kurang)
                                    <a href="{{ url('admin/stock-opname/' . $row->so_terakhir_kurang . '/detail') }}" target="_blank">{{ \Carbon\Carbon::parse($row->terakhir_kurang)->format('d-m-Y') }}</a>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="n">{{ SO::angka((float) $row->last_stock) }} {{ $row->satuan }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
</div>
@endsection
