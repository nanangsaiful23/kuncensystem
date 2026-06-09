@extends('layout.user', ['role' => 'admin', 'title' => 'Laporan Keuangan'])

@section('content')
@php
    $startDate       = $startDate       ?? date('Y-m-01');
    $endDate         = $endDate         ?? date('Y-m-d');
    $tab             = $tab             ?? 'labarugi';
    $allAccounts     = $allAccounts     ?? collect();
    $accountsGrouped = $accountsGrouped ?? [];
    $generalJournal  = $generalJournal  ?? collect();
    $ledger          = $ledger          ?? collect();
    $profitLoss      = $profitLoss      ?? [];
    $balanceSheet    = $balanceSheet    ?? [];
    $cashFlow        = $cashFlow        ?? collect();

    $pl = array_merge([
        'pendapatan'           => collect(),
        'beban'                => collect(),
        'pendapatan_lain'      => collect(),
        'total_pendapatan'     => 0,
        'hpp'                  => 0,
        'laba_kotor'           => 0,
        'beban_usaha'          => collect(),
        'total_beban_usaha'    => 0,
        'total_pendapatan_lain'=> 0,
        'laba_bersih'          => 0,
    ], $profitLoss);

    $bs = array_merge([
        'aktiva'        => collect(),
        'pasiva'        => collect(),
        'ekuitas'       => collect(),
        'totalAktiva'   => 0,
        'totalPasiva'   => 0,
        'totalEkuitas'  => 0,
        'upTo'          => $endDate,
    ], $balanceSheet);
@endphp

<style>
.fr-wrap { padding:1rem 1.25rem 2rem; font-family:'Segoe UI',system-ui,sans-serif; }
.fr-wrap *,.fr-wrap *::before,.fr-wrap *::after { box-sizing:border-box; }
.fr-wrap {
    --ink:#0f172a; --ink2:#475569; --ink3:#94a3b8;
    --surf:#f8fafc; --card:#fff; --bdr:#e2e8f0; --bdr2:#f1f5f9;
    --blue:#2563eb; --blue-bg:#eff6ff; --blue-b:#bfdbfe;
    --green:#16a34a; --green-bg:#f0fdf4; --green-b:#bbf7d0;
    --red:#dc2626; --red-bg:#fef2f2; --red-b:#fecaca;
    --amber:#d97706; --amber-bg:#fffbeb; --amber-b:#fde68a;
}

/* Header */
.fr-head { display:flex; justify-content:space-between; align-items:flex-start;
           flex-wrap:wrap; gap:.75rem; margin-bottom:1rem; }
.fr-head h1 { font-size:1.75rem; font-weight:800; color:var(--ink); margin:0; }
.fr-head p  { font-size:1.3rem; color:var(--ink2); margin:.15rem 0 0; }
.fr-badge   { background:var(--blue-bg); border:1px solid var(--blue-b); color:var(--blue);
              font-size:1.25rem; font-weight:600; padding:.3rem .75rem;
              border-radius:9999px; white-space:nowrap; }

/* Filter */
.fr-filter { background:var(--card); border:1px solid var(--bdr); border-radius:.6rem;
             padding:.75rem 1rem; margin-bottom:.875rem;
             display:flex; align-items:flex-end; gap:.6rem; flex-wrap:wrap; }
.fr-fg { display:flex; flex-direction:column; gap:.2rem; } 
.fr-fg label { font-size:1.15rem; font-weight:700; color:var(--ink3);
               text-transform:uppercase; letter-spacing:.08em; }
.fr-fg input { padding:.38rem .65rem; border:1px solid var(--bdr); border-radius:.4rem; 
               font-size:1.375rem; color:var(--ink); background:var(--surf); min-width:130px; }
.fr-fg input:focus { outline:none; border-color:var(--blue); }
.fr-btn-p { padding:.4rem 1rem; background:var(--blue); color:#fff; border:none; 
            border-radius:.4rem; font-size:1.375rem; font-weight:600;
            cursor:pointer; align-self:flex-end; }

/* Tabs */
.fr-tabs { display:flex; gap:.3rem; flex-wrap:wrap; margin-bottom:1rem; }
.fr-tab  { padding:.4rem .9rem; border-radius:.45rem; font-size:1.3375rem; font-weight:600;
           cursor:pointer; border:2px solid var(--bdr); background:var(--card);
           color:var(--ink2); text-decoration:none; transition:.15s; }
.fr-tab:hover,.fr-tab.active { background:var(--blue); color:#fff; border-color:var(--blue); }

/* Card */
.fr-card { background:var(--card); border:1px solid var(--bdr);
           border-radius:.6rem; overflow:hidden; margin-bottom:1rem; }
.fr-card-hd { padding:.65rem 1rem; border-bottom:1px solid var(--bdr); 
              display:flex; align-items:center; justify-content:space-between; }
.fr-card-hd h2 { font-size:1.4rem; font-weight:700; color:var(--ink); margin:0; }
.fr-card-body { padding:1rem; }

/* Grid 2 kolom */
.fr-grid2 { display:grid; grid-template-columns:repeat(auto-fill,minmax(340px,1fr));
            gap:1rem; margin-bottom:1rem; }

/* Tabel keuangan */
.fr-tbl-wrap { overflow-x:auto; max-height:540px; overflow-y:auto; }
.fr-tbl-wrap table { width:100%; border-collapse:collapse; font-size:1.4rem; }
.fr-tbl-wrap thead th { position:sticky; top:0; z-index:2; background:#f1f5f9;
.    padding:.55rem .8rem; text-align:left; font-weight:700; color:var(--ink2); 
    border-bottom:2px solid var(--bdr); white-space:nowrap; font-size:1.35rem; }
.fr-tbl-wrap tbody td { padding:.55rem .8rem; border-bottom:1px solid var(--bdr2); 
                        color:var(--ink); vertical-align:middle; font-size:1.4rem; }
.fr-tbl-wrap tbody tr:nth-child(even) td { background:#f8fafc; }
.fr-tbl-wrap tbody tr:nth-child(odd)  td { background:#fff; }
.fr-tbl-wrap tbody tr:hover td { background:#eff6ff !important; }
.fr-tbl-wrap tbody tr:last-child td { border-bottom:none; }
.tr { text-align:right; } .tc { text-align:center; }
tfoot td { font-weight:700; background:#f1f5f9 !important; 
           padding:.55rem .8rem; border-top:2px solid var(--bdr); font-size:1.4rem; }

/* Laba Rugi spesifik */
.pl-section { margin-bottom:1.25rem; }
.pl-section-title { font-size:1.3rem; font-weight:800; color:var(--ink3);
                    text-transform:uppercase; letter-spacing:.08em;
                    padding:.4rem 0 .3rem; border-bottom:2px solid var(--bdr);
                    margin-bottom:.5rem; display:flex; justify-content:space-between; }
.pl-row { display:flex; align-items:center; justify-content:space-between;
          padding:.45rem .5rem; border-radius:.35rem; margin-bottom:.2rem; }
.pl-row:hover { background:var(--surf); }
.pl-row .pr-left  { display:flex; align-items:center; gap:.5rem; font-size:1.4rem; }
.pl-row .pr-right { font-size:1.4rem; font-weight:600; text-align:right; white-space:nowrap; }
.pl-dot  { width:10px; height:10px; border-radius:50%; flex-shrink:0; }
.pl-sub  { font-size:1.3rem; color:var(--ink3); margin-left:1.25rem; }
.pl-total-row { display:flex; justify-content:space-between; align-items:center;
                padding:.55rem .75rem; border-radius:.45rem; font-weight:700; 
                font-size:1.4375rem; margin-top:.5rem; }
.pl-green  { background:var(--green-bg); color:var(--green); }
.pl-red    { background:var(--red-bg);   color:var(--red); }
.pl-blue   { background:var(--blue-bg);  color:var(--blue); }
.pl-amber  { background:var(--amber-bg); color:var(--amber); }
.pl-divider{ border:none; border-top:1px dashed var(--bdr); margin:.5rem 0; }

/* Neraca */
.bs-section { margin-bottom:1rem; }
.bs-title   { font-size:1.3rem; font-weight:800; color:var(--ink3);
              text-transform:uppercase; letter-spacing:.08em;
              padding:.35rem 0; border-bottom:2px solid var(--bdr); margin-bottom:.4rem; }
.bs-row     { display:flex; justify-content:space-between; align-items:center;
              padding:.4rem .5rem; border-radius:.3rem; font-size:1.4rem; }
.bs-row:hover { background:var(--surf); }
.bs-row .bl { display:flex; align-items:center; gap:.5rem; }
.bs-row .br { font-weight:600; white-space:nowrap; }
.bs-total   { display:flex; justify-content:space-between; padding:.5rem .75rem;
              border-radius:.4rem; font-weight:800; font-size:1.4375rem; margin-top:.4rem; }
.bs-aktiva-total  { background:var(--blue-bg);  color:var(--blue); }
.bs-pasiva-total  { background:var(--amber-bg); color:var(--amber); }
.bs-ekuitas-total { background:var(--green-bg); color:var(--green); }
.bs-check-ok  { background:var(--green-bg); color:var(--green); }
.bs-check-err { background:var(--red-bg);   color:var(--red); }

/* Kas */
.kas-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); gap:.75rem; }
.kas-card { background:var(--card); border:1px solid var(--bdr); border-radius:.6rem; 
            padding:.875rem 1rem; border-left:4px solid var(--blue); }
.kas-card .kc-label { font-size:1.2rem; font-weight:700; color:var(--ink3);
                      text-transform:uppercase; letter-spacing:.07em; margin-bottom:.35rem; }
.kas-card .kc-code  { font-size:1.25rem; color:var(--ink3); margin-bottom:.15rem; }
.kas-card .kc-saldo { font-size:1.6rem; font-weight:800; margin-bottom:.5rem; }
.kas-card .kc-row   { display:flex; justify-content:space-between; 
                      font-size:1.3rem; color:var(--ink2); margin-bottom:.15rem; }
.kas-card .kc-pos   { color:var(--green); font-weight:600; }
.kas-card .kc-neg   { color:var(--red);   font-weight:600; }

/* Akun badge */
.acc-dot  { width:9px; height:9px; border-radius:50%; display:inline-block;
            flex-shrink:0; margin-right:.35rem; }
.code-tag { font-family:monospace; font-size:1.25rem; background:var(--surf);
            border:1px solid var(--bdr); border-radius:.3rem;
            padding:.1rem .4rem; color:var(--ink2); }

/* Info box */
.fr-info  { background:var(--blue-bg); border:1px solid var(--blue-b); border-radius:.5rem;
            padding:.6rem .875rem; font-size:1.3rem; color:var(--blue);
            margin-bottom:.875rem; }

/* Empty */
.fr-empty { text-align:center; padding:2rem; color:var(--ink3); font-size:1.4rem; }

@media(max-width:640px) {
    .fr-filter { flex-direction:column; }
    .fr-grid2  { grid-template-columns:1fr; }
    .kas-grid  { grid-template-columns:1fr; }
}
</style>

<div class="content-wrapper">
<section class="content">
<div class="fr-wrap">

{{-- Header --}}
<div class="fr-head">
    <div>
        <h1>💰 Laporan Keuangan</h1>
        <p>Laba Rugi · Neraca · Buku Besar · Jurnal · Kas — data dinamis dari {{ $allAccounts->count() }} akun aktif</p>
    </div>
    <span class="fr-badge" style="font-size:1.25rem;">
        {{ \Carbon\Carbon::parse($startDate)->isoFormat('D MMM Y') }} →
        {{ \Carbon\Carbon::parse($endDate)->isoFormat('D MMM Y') }}
    </span>
</div>

{{-- Filter --}}
<form method="GET" action="{{ route('admin.reports.financial') }}" id="frForm">
    <input type="hidden" name="tab" value="{{ $tab }}">
    <div class="fr-filter">
        <div class="fr-fg">
            <label>Dari Tanggal</label>
            <input type="date" name="start_date" value="{{ $startDate }}">
        </div>
        <div class="fr-fg">
            <label>Sampai Tanggal</label>
            <input type="date" name="end_date" value="{{ $endDate }}">
        </div>
        <button type="submit" class="fr-btn-p">🔍 Terapkan</button>
    </div>
</form>

{{-- Tabs --}}
<div class="fr-tabs">
    @foreach([
        'labarugi'   => '📊 Laba Rugi',
        'neraca'     => '⚖️ Neraca',
        'buku_besar' => '📒 Buku Besar',
        'jurnal'     => '📋 Jurnal Umum',
        'kas'        => '💵 Kas',
    ] as $key => $label)
    <a href="{{ route('admin.reports.financial', array_merge(request()->query(), ['tab'=>$key])) }}" style="font-size:1.3375rem;"
       class="fr-tab {{ $tab===$key?'active':'' }}">{{ $label }}</a>
    @endforeach
</div>

{{-- ════════════════════════════════════════════════════════════ --}}
{{-- TAB: LABA RUGI                                              --}}
{{-- ════════════════════════════════════════════════════════════ --}}
@if($tab === 'labarugi')
<div class="fr-info">
    ℹ️ Pendapatan dihitung dari semua akun <strong>4xxx</strong> (Penjualan/Pendapatan),
    HPP dari akun <strong>5101</strong>, Beban dari akun <strong>5xxx lainnya</strong>, 
    dan Pendapatan Lain-lain dari akun <strong>6xxx</strong>.
    Akun baru yang ditambahkan akan otomatis masuk ke perhitungan ini.
</div>

<div class="fr-grid2">
    {{-- Kolom kiri: Pendapatan & HPP --}}
    <div class="fr-card">
        <div class="fr-card-hd">
            <h2 style="font-size:1.4rem;">💰 Pendapatan & Laba Kotor</h2>
            <span style="font-size:.8rem;color:var(--ink3)">{{ $startDate }} – {{ $endDate }}</span>
        </div>
        <div class="fr-card-body">

            {{-- Pendapatan --}}
            <div class="pl-section">
                <div class="pl-section-title">
                    <span>Pendapatan (4xxx)</span>
                    <span>Rp {{ number_format($pl['total_pendapatan'],0,',','.') }}</span>
                </div>
                @forelse($pl['pendapatan'] as $acc)
                <div class="pl-row">
                    <div class="pr-left">
                        <span class="pl-dot" style="background:{{ $acc->color ?? '#94a3b8' }}"></span>
                        <div style="font-size:1.4rem;">
                            <div>{{ $acc->name }}</div>
                            <div class="pl-sub"><span class="code-tag">{{ $acc->code }}</span></div>
                        </div>
                    </div>
                    <div class="pr-right" style="color:var(--green)">
                        Rp {{ number_format($acc->total,0,',','.') }}
                    </div>
                </div>
                @empty
                <div class="fr-empty">Tidak ada pendapatan di periode ini.</div>
                @endforelse
            </div>

            <hr class="pl-divider">

            {{-- HPP --}}
            <div class="pl-section">
                <div class="pl-section-title">
                    <span>HPP — Akun 5101</span>
                    <span style="color:var(--red)">(Rp {{ number_format($pl['hpp'],0,',','.') }})</span>
                </div>
                @foreach($pl['beban']->where('code','5101') as $acc)
                <div class="pl-row">
                    <div class="pr-left">
                        <span class="pl-dot" style="background:{{ $acc->color ?? '#E2852E' }}"></span>
                        <div style="font-size:1.4rem;">
                            <div>{{ $acc->name }}</div>
                            <div class="pl-sub"><span class="code-tag">{{ $acc->code }}</span></div>
                        </div>
                    </div>
                    <div class="pr-right" style="color:var(--red)">
                        (Rp {{ number_format($acc->total,0,',','.') }})
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Laba Kotor --}}
            <div class="pl-total-row {{ $pl['laba_kotor'] >= 0 ? 'pl-green' : 'pl-red' }}">
                <span style="font-size:1.4375rem;">Laba Kotor</span>
                <span style="font-size:1.4375rem;">Rp {{ number_format($pl['laba_kotor'],0,',','.') }}</span>
            </div>
        </div>
    </div>

    {{-- Kolom kanan: Beban & Laba Bersih --}}
    <div class="fr-card">
        <div class="fr-card-hd" style="font-size:1.4rem;">
            <h2 style="font-size:1.4rem;">📉 Beban Usaha & Laba Bersih</h2>
        </div>
        <div class="fr-card-body">

            {{-- Beban Usaha (5xxx selain 5101) --}}
            <div class="pl-section">
                <div class="pl-section-title">
                    <span>Beban Usaha (5xxx, kecuali HPP)</span>
                    <span style="color:var(--red)">(Rp {{ number_format($pl['total_beban_usaha'],0,',','.') }})</span>
                </div>
                @forelse($pl['beban_usaha'] as $acc)
                <div class="pl-row">
                    <div class="pr-left">
                        <span class="pl-dot" style="background:{{ $acc->color ?? '#94a3b8' }}"></span>
                        <div style="font-size:1.4rem;">
                            <div>{{ $acc->name }}</div>
                            <div class="pl-sub"><span class="code-tag">{{ $acc->code }}</span></div>
                        </div>
                    </div>
                    <div class="pr-right" style="color:var(--red)">
                        (Rp {{ number_format($acc->total,0,',','.') }})
                    </div>
                </div>
                @empty
                <div class="fr-empty">Tidak ada beban di periode ini.</div>
                @endforelse
            </div>

            @if($pl['pendapatan_lain']->count() > 0)
            <hr class="pl-divider">
            <div class="pl-section">
                <div class="pl-section-title">
                    <span>Pendapatan Lain-lain (6xxx)</span>
                    <span style="color:var(--green)">Rp {{ number_format($pl['total_pendapatan_lain'],0,',','.') }}</span>
                </div>
                @foreach($pl['pendapatan_lain'] as $acc)
                <div class="pl-row">
                    <div class="pr-left">
                        <span class="pl-dot" style="background:{{ $acc->color ?? '#1B3C53' }}"></span>
                        <div style="font-size:1.4rem;">
                            <div>{{ $acc->name }}</div>
                            <div class="pl-sub"><span class="code-tag">{{ $acc->code }}</span></div>
                        </div>
                    </div>
                    <div class="pr-right" style="color:var(--green)">
                        Rp {{ number_format($acc->total,0,',','.') }}
                    </div>
                </div>
                @endforeach
            </div>
            @endif

            {{-- Laba Bersih --}}
            <div class="pl-total-row {{ $pl['laba_bersih'] >= 0 ? 'pl-green' : 'pl-red' }}"
                 style="font-size:1.5rem;margin-top:.75rem">
                <span style="font-size:1.5rem;">🏆 Laba Bersih</span>
                <span style="font-size:1.5rem;">Rp {{ number_format($pl['laba_bersih'],0,',','.') }}</span>
            </div>

            {{-- Ringkasan cepat --}}
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem;margin-top:.75rem">
                <div class="pl-total-row pl-blue" style="font-size:1.3rem;">
                    <span style="font-size:1.3rem;">Total Pendapatan</span>
                    <span style="font-size:1.3rem;">Rp {{ number_format($pl['total_pendapatan'],0,',','.') }}</span>
                </div>
                <div class="pl-total-row pl-amber" style="font-size:1.3rem;">
                    <span style="font-size:1.3rem;">Total Beban</span>
                    <span style="font-size:1.3rem;">Rp {{ number_format($pl['total_beban_usaha'] + $pl['hpp'],0,',','.') }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

{{-- ════════════════════════════════════════════════════════════ --}}
{{-- TAB: NERACA                                                 --}}
{{-- ════════════════════════════════════════════════════════════ --}}
@if($tab === 'neraca')
<div class="fr-info">
    ℹ️ Neraca dihitung dari saldo kumulatif jurnal per akun sampai tanggal <strong>{{ $bs['upTo'] }}</strong>.
    Akun <strong>1xxx = Aktiva</strong>, <strong>2xxx = Kewajiban</strong>, <strong>3xxx = Ekuitas</strong>. 
</div>

@php
    $totalPasivaEkuitas = $bs['totalPasiva'] + $bs['totalEkuitas'];
    $selisih = $bs['totalAktiva'] - $totalPasivaEkuitas;
@endphp

{{-- Check balance --}}
<div class="pl-total-row {{ abs($selisih) < 1 ? 'bs-check-ok' : 'bs-check-err' }}"
     style="margin-bottom:1rem;font-size:1.375rem;">
    <span style="font-size:1.375rem;">{{ abs($selisih) < 1 ? '✅ Neraca Seimbang' : '❌ Neraca Tidak Seimbang' }}</span>
    <span style="font-size:1.375rem;">Selisih: Rp {{ number_format(abs($selisih),0,',','.') }}</span>
</div>

<div class="fr-grid2">
    {{-- Aktiva --}}
    <div class="fr-card">
        <div class="fr-card-hd"><h2>🏦 Aktiva (1xxx)</h2></div>
        <div class="fr-card-body">
            <div class="bs-section">
                @forelse($bs['aktiva'] as $acc)
                <div class="bs-row">
                    <div class="bl">
                        <span class="pl-dot" style="background:{{ $acc->color ?? '#94a3b8' }}"></span>
                        <div style="font-size:1.4rem;">
                            <div style="font-size:.9rem">{{ $acc->nama }}</div>
                            <div><span class="code-tag">{{ $acc->kode }}</span></div>
                        </div>
                    </div>
                    <div class="br">Rp {{ number_format($acc->saldo,0,',','.') }}</div>
                </div>
                @empty
                <div class="fr-empty">Tidak ada data aktiva.</div>
                @endforelse
            </div>
            <div class="bs-total bs-aktiva-total">
                <span style="font-size:1.4375rem;">Total Aktiva</span>
                <span style="font-size:1.4375rem;">Rp {{ number_format($bs['totalAktiva'],0,',','.') }}</span>
            </div>
        </div>
    </div>

    {{-- Pasiva + Ekuitas --}}
    <div class="fr-card">
        <div class="fr-card-hd"><h2>📋 Kewajiban & Ekuitas</h2></div>
        <div class="fr-card-body">

            @if($bs['pasiva']->count() > 0)
            <div class="bs-section">
                <div class="bs-title">Kewajiban (2xxx)</div>
                @foreach($bs['pasiva'] as $acc)
                <div class="bs-row">
                    <div class="bl">
                        <span class="pl-dot" style="background:{{ $acc->color ?? '#94a3b8' }}"></span>
                        <div style="font-size:1.4rem;">
                            <div style="font-size:.9rem">{{ $acc->nama }}</div>
                            <div><span class="code-tag">{{ $acc->kode }}</span></div>
                        </div>
                    </div>
                    <div class="br">Rp {{ number_format($acc->saldo,0,',','.') }}</div>
                </div>
                @endforeach
                <div class="bs-total bs-pasiva-total" style="font-size:.875rem;margin-top:.35rem">
                    <span style="font-size:1.375rem;">Total Kewajiban</span>
                    <span style="font-size:1.375rem;">Rp {{ number_format($bs['totalPasiva'],0,',','.') }}</span>
                </div>
            </div>
            @endif

            @if($bs['ekuitas']->count() > 0)
            <div class="bs-section" style="margin-top:.75rem">
                <div class="bs-title">Ekuitas (3xxx)</div>
                @foreach($bs['ekuitas'] as $acc)
                <div class="bs-row">
                    <div class="bl">
                        <span class="pl-dot" style="background:{{ $acc->color ?? '#94a3b8' }}"></span>
                        <div style="font-size:1.4rem;">
                            <div style="font-size:.9rem">{{ $acc->nama }}</div>
                            <div><span class="code-tag">{{ $acc->kode }}</span></div>
                        </div>
                    </div>
                    <div class="br">Rp {{ number_format($acc->saldo,0,',','.') }}</div>
                </div>
                @endforeach
                <div class="bs-total bs-ekuitas-total" style="font-size:.875rem;margin-top:.35rem">
                    <span style="font-size:1.375rem;">Total Ekuitas</span>
                    <span style="font-size:1.375rem;">Rp {{ number_format($bs['totalEkuitas'],0,',','.') }}</span>
                </div>
            </div>
            @endif

            <div class="bs-total bs-pasiva-total"
                 style="margin-top:.75rem;font-size:1.5rem;font-weight:800;">
                <span style="font-size:1.5rem;">Total Kewajiban + Ekuitas</span>
                <span style="font-size:1.5rem;">Rp {{ number_format($totalPasivaEkuitas,0,',','.') }}</span>
            </div>
        </div>
    </div>
</div>
@endif

{{-- ════════════════════════════════════════════════════════════ --}}
{{-- TAB: BUKU BESAR                                             --}}
{{-- ════════════════════════════════════════════════════════════ --}}
@if($tab === 'buku_besar')
<div class="fr-info">
    ℹ️ Saldo per akun dalam periode ini. Semua akun yang memiliki transaksi jurnal tampil otomatis.
    Saldo positif = debit lebih besar; negatif = kredit lebih besar. 
</div>
<div class="fr-card">
    <div class="fr-card-hd">
        <h2>📒 Buku Besar per Akun</h2>
        <span style="font-size:.8rem;color:var(--ink3)">{{ $ledger->count() }} akun aktif</span>
    </div>
    <div class="fr-tbl-wrap">
        <table>
            <thead><tr>
                <th>Kode</th>
                <th>Nama Akun</th>
                <th>Kelompok</th>
                <th>Tipe</th>
                <th class="tr">Total Debit</th>
                <th class="tr">Total Kredit</th>
                <th class="tr">Saldo</th>
            </tr></thead>
            <tbody>
            @forelse($ledger as $row)
            <tr>
                <td><span class="code-tag">{{ $row->kode }}</span></td>
                <td>
                    <span class="acc-dot" style="background:{{ $row->color ?? '#94a3b8' }}"></span> 
                    {{ $row->nama }}
                </td>
                <td style="font-size:1.3rem;color:var(--ink2)">{{ $row->kelompok }}</td>
                <td>
                    @if(strtolower($row->tipe)==='debet')
                        <span style="font-size:.75rem;background:var(--blue-bg);color:var(--blue);padding:.15rem .5rem;border-radius:9999px;font-weight:600">Debet</span>
                    @else
                        <span style="font-size:.75rem;background:var(--amber-bg);color:var(--amber);padding:.15rem .5rem;border-radius:9999px;font-weight:600">Kredit</span>
                    @endif
                </td>
                <td class="tr">Rp {{ number_format($row->total_debit,0,',','.') }}</td>
                <td class="tr">Rp {{ number_format($row->total_kredit,0,',','.') }}</td>
                <td class="tr" style="font-weight:700;color:{{ $row->saldo >= 0 ? 'var(--green)' : 'var(--red)' }}"> 
                    Rp {{ number_format(abs($row->saldo),0,',','.') }}
                    <span style="font-size:.75rem;font-weight:400;color:var(--ink3)">
                        {{ $row->saldo >= 0 ? 'D' : 'K' }}
                    </span>
                </td>
            </tr>
            @empty
            <tr><td colspan="7"><div class="fr-empty">Tidak ada data buku besar di periode ini.</div></td></tr>
            @endforelse
            </tbody>
            @if($ledger->count() > 0)
            <tfoot>
                <tr>
                    <td colspan="4">TOTAL</td>
                    <td class="tr">Rp {{ number_format($ledger->sum('total_debit'),0,',','.') }}</td>
                    <td class="tr">Rp {{ number_format($ledger->sum('total_kredit'),0,',','.') }}</td>
                    <td></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>
@endif

{{-- ════════════════════════════════════════════════════════════ --}}
{{-- TAB: JURNAL UMUM                                            --}}
{{-- ════════════════════════════════════════════════════════════ --}}
@if($tab === 'jurnal')
<div class="fr-card">
    <div class="fr-card-hd">
        <h2>📋 Jurnal Umum</h2>
        <span style="font-size:.8rem;color:var(--ink3)">{{ $generalJournal->count() }} entri</span>
    </div>
    <div class="fr-tbl-wrap">
        <table>
            <thead><tr>
                <th>Tanggal</th>
                <th>Keterangan</th>
                <th>Tipe</th>
                <th>Akun Debit</th>
                <th class="tr">Debit (Rp)</th>
                <th>Akun Kredit</th>
                <th class="tr">Kredit (Rp)</th>
            </tr></thead>
            <tbody>
            @forelse($generalJournal as $j)
            <tr>
                <td style="white-space:nowrap">
                    {{ \Carbon\Carbon::parse($j->journal_date)->isoFormat('D MMM Y') }}
                </td>
                <td style="max-width:250px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                    title="{{ $j->keterangan }}">{{ $j->keterangan }}</td>
                <td><span style="font-size:.75rem;background:var(--blue-bg);color:var(--blue);padding:.15rem .5rem;border-radius:9999px;font-weight:600">{{ $j->tipe }}</span></td>
                <td style="font-size:1.4rem;">
                    <span class="acc-dot" style="background:{{ $j->color_debit ?? '#94a3b8' }}"></span>
                    <span class="code-tag">{{ $j->kode_debit }}</span>
                    {{ $j->akun_debit }}
                </td>
                <td class="tr" style="font-weight:600;color:var(--blue)">
                    {{ number_format($j->debit,0,',','.') }}
                </td>
                <td style="font-size:1.4rem;">
                    <span class="acc-dot" style="background:{{ $j->color_kredit ?? '#94a3b8' }}"></span>
                    <span class="code-tag">{{ $j->kode_kredit }}</span>
                    {{ $j->akun_kredit }}
                </td>
                <td class="tr" style="font-weight:600;color:var(--amber)">
                    {{ number_format($j->credit,0,',','.') }}
                </td>
            </tr>
            @empty
            <tr><td colspan="7"><div class="fr-empty">Tidak ada jurnal di periode ini.</div></td></tr>
            @endforelse
            </tbody>
            @if($generalJournal->count() > 0)
            <tfoot>
                <tr>
                    <td colspan="4">TOTAL</td>
                    <td class="tr">Rp {{ number_format($generalJournal->sum('debit'),0,',','.') }}</td>
                    <td></td>
                    <td class="tr">Rp {{ number_format($generalJournal->sum('credit'),0,',','.') }}</td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>
@endif

{{-- ════════════════════════════════════════════════════════════ --}}
{{-- TAB: KAS                                                    --}}
{{-- ════════════════════════════════════════════════════════════ --}}
@if($tab === 'kas')
<div class="fr-info">
    ℹ️ Semua akun kas (kode <strong>111x</strong>) ditampilkan otomatis.
    Akun kas baru yang ditambahkan akan muncul di sini tanpa perlu konfigurasi. 
</div>
@php $totalSaldoAkhir = 0; foreach($cashFlow as $k) $totalSaldoAkhir += $k->saldo_akhir; @endphp
<div class="fr-card" style="margin-bottom:.875rem">
    <div class="fr-card-hd">
        <h2>💵 Total Kas Keseluruhan</h2>
        <span style="font-size:1.5rem;font-weight:800;color:{{ $totalSaldoAkhir >= 0 ? 'var(--green)' : 'var(--red)' }}">
            Rp {{ number_format($totalSaldoAkhir,0,',','.') }} 
        </span>
    </div>
</div>
<div class="kas-grid">
    @forelse($cashFlow as $kas)
    <div class="kas-card" style="border-left-color:{{ $kas->color }}">
        <div class="kc-label">{{ $kas->nama }}</div>
        <div class="kc-code" style="font-size:1.25rem;"><span class="code-tag">{{ $kas->kode }}</span></div>
        <div class="kc-saldo" style="font-size:1.6rem;color:{{ $kas->saldo_akhir >= 0 ? 'var(--green)' : 'var(--red)' }}">
            Rp {{ number_format($kas->saldo_akhir,0,',','.') }} 
        </div>
        <div class="kc-row">
            <span style="font-size:1.3rem;">Saldo Awal</span>
            <span style="font-size:1.3rem;">Rp {{ number_format($kas->saldo_awal,0,',','.') }}</span>
        </div>
        <div class="kc-row">
            <span style="font-size:1.3rem;">Masuk</span>
            <span class="kc-pos" style="font-size:1.3rem;">+ Rp {{ number_format($kas->masuk,0,',','.') }}</span>
        </div>
        <div class="kc-row">
            <span style="font-size:1.3rem;">Keluar</span>
            <span class="kc-neg" style="font-size:1.3rem;">− Rp {{ number_format($kas->keluar,0,',','.') }}</span>
        </div>
    </div>
    @empty
    <div class="fr-empty" style="grid-column:1/-1">Tidak ada akun kas ditemukan.</div>
    @endforelse
</div>
@endif

</div>{{-- end fr-wrap --}}
</section>
</div>{{-- end content-wrapper --}}
@endsection