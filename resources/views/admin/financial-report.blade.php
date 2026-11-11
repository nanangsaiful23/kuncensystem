@extends('layout.user', ['role' => 'admin', 'title' => 'Laporan Keuangan'])

@section('content')
@php
    $startDate       = $startDate       ?? date('Y-m-01');
    $endDate         = $endDate         ?? date('Y-m-d');
    $tab             = $tab             ?? 'labarugi';
    $allAccounts     = $allAccounts     ?? collect();
    $accountsGrouped = $accountsGrouped ?? [];
    $profitLoss      = $profitLoss      ?? [];
    $cashFlow        = $cashFlow        ?? collect();
    $monthlyBalance  = $monthlyBalance  ?? [];

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
.fr-head p  { font-size:1.4rem; color:var(--ink2); margin:.15rem 0 0; }
.fr-badge   { background:var(--blue-bg); border:1px solid var(--blue-b); color:var(--blue);
              font-size:1.4rem; font-weight:600; padding:.3rem .75rem;
              border-radius:9999px; white-space:nowrap; }

/* Filter */
.fr-filter { background:var(--card); border:1px solid var(--bdr); border-radius:.6rem;
             padding:.75rem 1rem; margin-bottom:.875rem;
             display:flex; align-items:flex-end; gap:.6rem; flex-wrap:wrap; }
.fr-fg { display:flex; flex-direction:column; gap:.2rem; } 
.fr-fg label { font-size:1.4rem; font-weight:700; color:var(--ink3);
               text-transform:uppercase; letter-spacing:.08em; }
.fr-fg input { padding:.38rem .65rem; border:1px solid var(--bdr); border-radius:.4rem; 
               font-size:1.4rem; color:var(--ink); background:var(--surf); min-width:130px; }
.fr-fg input:focus { outline:none; border-color:var(--blue); }
.fr-btn-p { padding:.4rem 1rem; background:var(--blue); color:#fff; border:none; 
            border-radius:.4rem; font-size:1.4rem; font-weight:600;
            cursor:pointer; align-self:flex-end; }

/* Tabs */
.fr-tabs { display:flex; gap:.3rem; flex-wrap:wrap; margin-bottom:1rem; }
.fr-tab  { padding:.4rem .9rem; border-radius:.45rem; font-size:1.4rem; font-weight:600;
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
    border-bottom:2px solid var(--bdr); white-space:nowrap; font-size:1.4rem; }
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
.pl-section-title { font-size:1.4rem; font-weight:800; color:var(--ink3);
                    text-transform:uppercase; letter-spacing:.08em;
                    padding:.4rem 0 .3rem; border-bottom:2px solid var(--bdr);
                    margin-bottom:.5rem; display:flex; justify-content:space-between; }
.pl-row { display:flex; align-items:center; justify-content:space-between;
          padding:.45rem .5rem; border-radius:.35rem; margin-bottom:.2rem; }
.pl-row:hover { background:var(--surf); }
.pl-row .pr-left  { display:flex; align-items:center; gap:.5rem; font-size:1.4rem; }
.pl-row .pr-right { font-size:1.4rem; font-weight:600; text-align:right; white-space:nowrap; }
.pl-dot  { width:10px; height:10px; border-radius:50%; flex-shrink:0; }
.pl-sub  { font-size:1.4rem; color:var(--ink3); margin-left:1.25rem; }
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
.bs-title   { font-size:1.4rem; font-weight:800; color:var(--ink3);
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
.kas-card .kc-label { font-size:1.4rem; font-weight:700; color:var(--ink3);
                      text-transform:uppercase; letter-spacing:.07em; margin-bottom:.35rem; }
.kas-card .kc-code  { font-size:1.4rem; color:var(--ink3); margin-bottom:.15rem; }
.kas-card .kc-saldo { font-size:1.6rem; font-weight:800; margin-bottom:.5rem; }
.kas-card .kc-row   { display:flex; justify-content:space-between; 
                      font-size:1.4rem; color:var(--ink2); margin-bottom:.15rem; }
.kas-card .kc-pos   { color:var(--green); font-weight:600; }
.kas-card .kc-neg   { color:var(--red);   font-weight:600; }

/* Akun badge */
.acc-dot  { width:9px; height:9px; border-radius:50%; display:inline-block;
            flex-shrink:0; margin-right:.35rem; }
.code-tag { font-family:monospace; font-size:1.4rem; background:var(--surf);
            border:1px solid var(--bdr); border-radius:.3rem;
            padding:.1rem .4rem; color:var(--ink2); }

/* Info box */
.fr-info  { background:var(--blue-bg); border:1px solid var(--blue-b); border-radius:.5rem;
            padding:.6rem .875rem; font-size:1.4rem; color:var(--blue);
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
        <p>Laba Rugi · Neraca Bulanan · Kas — data dinamis dari {{ $allAccounts->count() }} akun aktif</p>
    </div>
    <span class="fr-badge" style="font-size:1.4rem;">
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
        'neraca_bulanan' => '📅 Neraca Bulanan',
        'kas'        => '💵 Kas',
    ] as $key => $label)
    <a href="{{ route('admin.reports.financial', array_merge(request()->query(), ['tab'=>$key])) }}" style="font-size:1.4rem;"
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
            <span style="font-size:1.4rem;color:var(--ink3)">{{ $startDate }} – {{ $endDate }}</span>
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
                <div class="pl-total-row pl-blue" style="font-size:1.4rem;">
                    <span style="font-size:1.4rem;">Total Pendapatan</span>
                    <span style="font-size:1.4rem;">Rp {{ number_format($pl['total_pendapatan'],0,',','.') }}</span>
                </div>
                <div class="pl-total-row pl-amber" style="font-size:1.4rem;">
                    <span style="font-size:1.4rem;">Total Beban</span>
                    <span style="font-size:1.4rem;">Rp {{ number_format($pl['total_beban_usaha'] + $pl['hpp'],0,',','.') }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

{{-- ════════════════════════════════════════════════════════════ --}}
{{-- TAB: NERACA BULANAN                                         --}}
{{-- ════════════════════════════════════════════════════════════ --}}
@if($tab === 'neraca_bulanan' && $monthlyBalance)
@php
    $mb = $monthlyBalance;
    $fmtMb = function ($v) {
        if ($v === null) return '<span style="color:var(--ink3)">–</span>';
        $s = number_format(abs($v), 0, ',', '.');
        return $v < 0 ? '<span style="color:var(--red)">−' . $s . '</span>' : $s;
    };
    $sectionTitles = ['aktiva' => 'Aktiva (1xxx)', 'kewajiban' => 'Kewajiban (2xxx)', 'ekuitas' => 'Ekuitas (3xxx + laba)', 'lainnya' => 'Akun lain (perlu dicek)'];
    $canUnlock = \App\Services\PeriodLock::userCanUnlock();
    $confirmText = $mb['closed']
        ? 'Buka kembali tahun ' . $mb['year'] . '? Tahun sesudahnya yang sudah ditutup juga ikut dibuka.'
        : 'Tutup buku tahun ' . $mb['year'] . '? Laba rugi dipindah ke Laba Ditahan dan data sampai 31 Desember ' . $mb['year'] . ' dikunci.';
@endphp
<style>
.mb-tbl td:first-child, .mb-tbl th:first-child { position:sticky; left:0; z-index:1; min-width:230px; }
.mb-tbl thead th:first-child { z-index:3; }
.mb-tbl td { white-space:nowrap; }
.mb-tbl tr.mb-sec td { background:#e2e8f0 !important; font-weight:800; color:var(--ink2); text-transform:uppercase; font-size:1.4rem; }
.mb-tbl tr.mb-total td { font-weight:800; background:#f1f5f9 !important; }
.mb-tbl tr.mb-computed td:first-child { font-style:italic; }
.mb-bar { display:flex; flex-wrap:wrap; gap:.6rem; align-items:center; margin-bottom:1rem; }
.mb-bar select { padding:.38rem .65rem; border:1px solid var(--bdr); border-radius:.4rem; font-size:1.4rem; }
.mb-badge { padding:.3rem .75rem; border-radius:9999px; font-size:1.4rem; font-weight:700; }
.mb-btn-d { padding:.4rem 1rem; background:var(--red); color:#fff; border:none; border-radius:.4rem; font-weight:700; cursor:pointer; font-size:1.4rem; }
</style>

<div class="fr-info">
    ℹ️ Saldo akhir tiap bulan tahun <strong>{{ $mb['year'] }}</strong>, dihitung dari saldo awal akun + semua jurnal sampai akhir bulan itu.
    Laba dimasukkan ke Ekuitas, jadi Aktiva harus sama dengan Kewajiban + Ekuitas. Nilai minus ditampilkan merah.
</div>

@if(session('closing_message'))
<div class="pl-total-row bs-check-ok" style="margin-bottom:1rem;font-size:1.4rem;">{{ session('closing_message') }}</div>
@endif

<div class="mb-bar">
    <form method="GET" action="{{ route('admin.reports.financial') }}">
        <input type="hidden" name="tab" value="neraca_bulanan">
        <select name="year" onchange="this.form.submit()" aria-label="Tahun">
            @foreach($mb['years'] as $y)
                <option value="{{ $y }}" @selected($y == $mb['year'])>{{ $y }}</option>
            @endforeach
        </select>
    </form>

    @if($mb['closed'])
        <span class="mb-badge pl-green">🔒 Sudah tutup buku</span>
    @elseif($mb['canClose'])
        <span class="mb-badge pl-amber">Belum tutup buku</span>
    @else
        <span class="mb-badge pl-blue">Tahun berjalan</span>
    @endif
    @if($mb['lockedUntil'])
        <span style="font-size:1.4rem;color:var(--ink2)">Data terkunci sampai {{ displayDate($mb['lockedUntil']) }}</span>
    @endif

    @if($canUnlock && $mb['canClose'])
    <form method="POST" action="{{ route('admin.reports.financial.close-year') }}" style="margin-left:auto"
          onsubmit="return confirm(@js($confirmText))">
        @csrf
        <input type="hidden" name="year" value="{{ $mb['year'] }}">
        @if($mb['closed'])
            <input type="hidden" name="action" value="reopen">
            <button type="submit" class="fr-btn-p">🔓 Buka kembali {{ $mb['year'] }}</button>
        @else
            <button type="submit" class="mb-btn-d">🔒 Tutup buku {{ $mb['year'] }}</button>
        @endif
    </form>
    @endif
</div>

<div class="fr-card">
    <div class="fr-tbl-wrap" style="max-height:none">
        <table class="mb-tbl">
            <thead>
                <tr>
                    <th>Akun</th>
                    @foreach($mb['months'] as $m)
                        <th class="tr">{{ $m['label'] }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
            @foreach($mb['sections'] as $key => $rows)
                @continue($key === 'lainnya' && empty($rows))
                <tr class="mb-sec"><td colspan="13">{{ $sectionTitles[$key] }}</td></tr>
                @foreach($rows as $row)
                <tr class="{{ !empty($row->computed) ? 'mb-computed' : '' }}">
                    <td>@if($row->kode)<span class="code-tag">{{ $row->kode }}</span> @endif{{ $row->nama }}</td>
                    @foreach($row->values as $v)
                        <td class="tr">{!! $fmtMb($v) !!}</td>
                    @endforeach
                </tr>
                @endforeach
                <tr class="mb-total">
                    <td>Total {{ $sectionTitles[$key] }}</td>
                    @foreach($mb['totals'][$key] as $v)
                        <td class="tr">{!! $fmtMb($v) !!}</td>
                    @endforeach
                </tr>
            @endforeach
                <tr class="mb-total">
                    <td>Selisih (Aktiva + Akun lain − Kewajiban − Ekuitas)</td>
                    @foreach($mb['difference'] as $v)
                        <td class="tr" style="{{ $v !== null && abs($v) >= 1 ? 'color:var(--red)' : 'color:var(--green)' }}">{!! $v === null ? $fmtMb(null) : (abs($v) < 1 ? '✓' : $fmtMb($v)) !!}</td>
                    @endforeach
                </tr>
            </tbody>
        </table>
    </div>
</div>

@if(abs($mb['openingImbalance']) >= 1)
<div class="fr-info" style="margin-top:1rem;background:var(--amber-bg);border-color:var(--amber-b);color:var(--amber)">
    ⚠️ Saldo awal akun (kolom saldo di menu Akun) tidak seimbang: selisih Rp {{ number_format(abs($mb['openingImbalance']), 0, ',', '.') }}
    ({{ $mb['openingImbalance'] > 0 ? 'debit lebih besar' : 'kredit lebih besar' }}). Selisih neraca di atas berasal dari sini;
    jurnalnya sendiri seimbang. Rapikan saldo awal akun supaya selisihnya nol.
</div>
@endif
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
        <div class="kc-code" style="font-size:1.4rem;"><span class="code-tag">{{ $kas->kode }}</span></div>
        <div class="kc-saldo" style="font-size:1.6rem;color:{{ $kas->saldo_akhir >= 0 ? 'var(--green)' : 'var(--red)' }}">
            Rp {{ number_format($kas->saldo_akhir,0,',','.') }} 
        </div>
        <div class="kc-row">
            <span style="font-size:1.4rem;">Saldo Awal</span>
            <span style="font-size:1.4rem;">Rp {{ number_format($kas->saldo_awal,0,',','.') }}</span>
        </div>
        <div class="kc-row">
            <span style="font-size:1.4rem;">Masuk</span>
            <span class="kc-pos" style="font-size:1.4rem;">+ Rp {{ number_format($kas->masuk,0,',','.') }}</span>
        </div>
        <div class="kc-row">
            <span style="font-size:1.4rem;">Keluar</span>
            <span class="kc-neg" style="font-size:1.4rem;">− Rp {{ number_format($kas->keluar,0,',','.') }}</span>
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