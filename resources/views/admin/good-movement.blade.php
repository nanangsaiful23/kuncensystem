@extends('layout.user', ['role' => 'admin', 'title' => 'Analisis Pergerakan Barang'])

@section('content')
@php
    $categories         = $categories         ?? collect();
    $negativeStockCount = $negativeStockCount ?? 0;
    $negativeStockGoods = $negativeStockGoods ?? collect();
    $status       = $status       ?? 'all';
    $adaKolomBaru = $adaKolomBaru ?? false;
    $seasons      = $seasons      ?? collect();
    $obralSelesai = $obralSelesai ?? 0;
    $bolehSidak    = $bolehSidak    ?? false;
    $hargaObralAda = $hargaObralAda ?? false;
    $lihatModal    = $lihatModal    ?? false;
@endphp

<style>
/* ── Halaman (warna & bentuk mengikuti sistem: oranye #FF7B54, latar #EEEDEB, kotak putih) ──
   Ukuran pakai px: Bootstrap memasang html { font-size:10px }.
   CSS global layout memaksa semua <span> hitam & tipis, jadi di sini dikembalikan ke induknya. */
.gm2 { padding: 14px 18px 40px; font-size: 14px; font-weight: 400; color: #1f1f1f;
       --acc: #FF7B54; --acc-bg: #FFF1EC; --t1: #1f1f1f; --t2: #555; --t3: #6b6b6b; --bdr: #e3e1dc; }
.gm2 *, .gm2 *::before, .gm2 *::after { box-sizing: border-box; }
.gm2 span { color: inherit; font-weight: inherit; }
.gm2 h1 { font-size: 19px; font-weight: 600; margin: 0; color: var(--t1); }
.gm2 .box { background: #fff; border-radius: 10px; box-shadow: 0 1px 1px rgba(0,0,0,.1); padding: 12px 14px; margin-bottom: 12px; }
.gm2 .muted { color: var(--t3); font-size: 12px; }
.gm2 .gb { display: inline-flex; align-items: center; gap: 6px; height: 34px; padding: 0 12px; border: 1px solid var(--acc); background: #fff;
           color: var(--t1); border-radius: 6px; font-size: 13px; font-weight: 400; cursor: pointer; white-space: nowrap; text-decoration: none; line-height: 1; }
.gm2 .gb:hover { background: var(--acc); color: #fff; text-decoration: none; }
.gm2 .gb.sm { height: 28px; padding: 0 9px; font-size: 12px; }
.gm2 .gb.ghost { border-color: var(--bdr); }
.gm2 .gb.ghost:hover { background: #f3f2ef; color: var(--t1); }
.gm2 .inp { height: 34px; padding: 0 10px; border: 1px solid var(--bdr); border-radius: 6px; background: #fff; color: var(--t1); font-size: 13px; }
.gm2 .inp:focus { outline: none; border-color: var(--acc); }
.gm2 .inp:disabled { background: #f6f5f2; color: var(--t3); }

/* Pemberitahuan */
.gm2 .al { padding: 9px 14px; border-radius: 10px; margin-bottom: 10px; font-size: 13px; }
.gm2 .al-ok   { background: #EAF3DE; border: 1px solid #C0DD97; color: #27500A; }
.gm2 .al-err  { background: #FCEBEB; border: 1px solid #F7C1C1; color: #791F1F; }
.gm2 .al-info { background: #E1F5EE; border: 1px solid #9FE1CB; color: #085041; }
.gm2 .warn { background: #FFF8E6; border: 1px solid #F3D58A; border-radius: 10px; padding: 8px 14px; margin-bottom: 12px; color: #7A4B00; font-size: 13px; }
.gm2 .warn summary { cursor: pointer; font-weight: 500; }
.gm2 .warn table { width: 100%; border-collapse: collapse; margin-top: 8px; }
.gm2 .warn th, .gm2 .warn td { text-align: left; padding: 5px 6px; border-bottom: 1px solid #F3D58A; }
.gm2 .warn a { color: #7A4B00; text-decoration: underline; }

/* Judul + filter dalam satu baris */
.gm2 .top { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.gm2 .top h1 { margin-right: auto; }
.gm2 .custom { display: none; gap: 8px; align-items: center; width: 100%; justify-content: flex-end; font-size: 12px; color: var(--t2); }
.gm2 .custom.on { display: flex; }

/* Kotak status (= filter utama) */
.gm2 .sec-h { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; margin: 12px 0 8px; padding-top: 10px; border-top: 1px solid var(--bdr); }
.gm2 .sec-h b { font-weight: 600; font-size: 14px; }
.gm2 .sec-h .modal-t { margin-left: auto; font-size: 13px; color: var(--t2); }
.gm2 .sec-h .modal-t b { color: #B42318; font-size: 15px; }
.gm2 .chips { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 6px; }
.gm2 .chip { text-align: left; border: 1px solid var(--bdr); background: #fff; border-radius: 8px; padding: 6px 10px; cursor: pointer; color: var(--t1); min-width: 0; font-weight: 400; }
.gm2 .chip:hover { border-color: #c9c6bf; }
.gm2 .chip.on { border-color: var(--acc); background: var(--acc-bg); box-shadow: inset 0 0 0 1px var(--acc); }
.gm2 .chip .cl { font-size: 13px; display: flex; align-items: center; gap: 6px; white-space: nowrap; }
.gm2 .chip .cl .pct { margin-left: auto; font-size: 12px; color: var(--t3); }
.gm2 .chip .cn { font-size: 12px; color: var(--t3); white-space: nowrap; margin-top: 2px; }
.gm2 .chip .cn b { font-size: 16px; font-weight: 600; color: var(--t1); margin-right: 6px; }
.gm2 .chip.zero { opacity: .7; }
.gm2 .dot { width: 9px; height: 9px; border-radius: 2px; flex-shrink: 0; display: inline-block; }

/* Tabel */
.gm2 .tbar { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 8px; }
.gm2 .tbar .sum { margin-right: auto; font-size: 13px; color: var(--t2); }
.gm2 .tbar .sum b { color: var(--t1); font-weight: 600; }
/* Kerangka AdminLTE (.wrapper overflow) membuat sticky terhadap jendela tidak jalan,
   jadi tabel diberi area scroll sendiri setinggi layar: judul kolom selalu terlihat. */
.gm2 .twrap { overflow: auto; max-height: calc(100vh - 110px); border-bottom: 1px solid #efeeea; }
.gm2 table.t { width: 100%; min-width: 960px; border-collapse: separate; border-spacing: 0; font-size: 13px; }
.gm2 .t thead th { position: sticky; top: 0; z-index: 2; background: #F7F6F3; color: var(--t2); font-weight: 500; font-size: 12px; text-align: left;
                   padding: 7px; border-bottom: 1px solid var(--bdr); vertical-align: bottom; line-height: 1.5; }
.gm2 .t th .sk { cursor: pointer; border-radius: 4px; padding: 0 2px; white-space: nowrap; }
.gm2 .t th .sk:hover { background: #ecebe7; color: var(--t1); }
.gm2 .t th .sk.on { color: #C2410C; font-weight: 600; }
.gm2 .t td { padding: 8px 7px; border-bottom: 1px solid #efeeea; vertical-align: top; }
.gm2 .t tbody tr:hover td { background: #FFF8F5; }
.gm2 .t .r { text-align: right; }
.gm2 .t .c { text-align: center; }
.gm2 .t .num { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
.gm2 .nm { font-weight: 500; line-height: 1.35; }
.gm2 .sub { font-size: 12px; color: var(--t3); margin-top: 2px; line-height: 1.4; }
.gm2 .tag { display: inline-block; font-size: 12px; padding: 0 7px; border-radius: 999px; white-space: nowrap; cursor: pointer; }
.gm2 .tag.m { background: #E1F5EE; color: #085041; }
.gm2 .tag.k { color: #0F6E56; border: 1px dashed #1D9E75; }
.gm2 .set-musim { color: var(--t3); text-decoration: underline dotted; cursor: pointer; visibility: hidden; }
.gm2 .t tbody tr:hover .set-musim { visibility: visible; }
.gm2 .bd { display: inline-block; font-size: 12px; padding: 1px 8px; border-radius: 999px; font-weight: 500; }
.gm2 .ok { color: #2F6B0C; } .gm2 .wr { color: #9A5B00; } .gm2 .bad { color: #B42318; } .gm2 .na { color: #8a8984; }
.gm2 .cek { display: inline-block; font-size: 12px; padding: 0 6px; border-radius: 999px; margin-top: 3px; white-space: nowrap; background: #EAF3DE; color: #27500A; }
.gm2 .cek.lama { background: #f1f0ed; color: #5f5e5a; }
.gm2 .aksi { display: flex; flex-wrap: wrap; gap: 4px; margin-top: 5px; }
.gm2 .aksi form { margin: 0; }
.gm2 .empty { text-align: center; padding: 30px; color: var(--t3); }
.gm2 .pagi { display: flex; justify-content: space-between; align-items: center; padding-top: 10px; flex-wrap: wrap; gap: 8px; font-size: 13px; color: var(--t2); }
.gm2 .pg { display: flex; gap: 4px; flex-wrap: wrap; }
.gm2 .pg button { min-width: 32px; height: 32px; border: 1px solid var(--bdr); background: #fff; color: var(--t1); border-radius: 6px; cursor: pointer; font-size: 13px; }
.gm2 .pg button.on { background: var(--acc); border-color: var(--acc); color: #fff; }
.gm2 .pg button:disabled { opacity: .4; cursor: default; }
.gm2 .pg .gap { padding: 0 4px; align-self: center; }

/* Bar aksi untuk barang yang dicentang: melayang di bawah layar (di bawah jendela modal) */
.gm2 .bulk { position: fixed; bottom: 14px; left: 50%; transform: translateX(-50%); z-index: 1000; width: max-content; max-width: calc(100vw - 40px);
             display: none; align-items: center; gap: 8px; flex-wrap: wrap; background: #fff;
             border: 1px solid var(--acc); box-shadow: 0 6px 24px rgba(0,0,0,.18); border-radius: 10px; padding: 9px 12px; }
.gm2 .bulk.on { display: flex; }
.gm2 .bulk .info { margin-right: auto; font-size: 13px; }
.gm2 .bulk .info b { font-weight: 600; }

@media (max-width: 1100px) { .gm2 .chips { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
@media (max-width: 700px)  { .gm2 .chips { grid-template-columns: repeat(2, minmax(0, 1fr)); } .gm2 .top h1 { width: 100%; } }

/* ── Jendela (modal) — tetap seperti sebelumnya ─────────────────── */
.gm-modal-overlay {
    --ink:    #0f172a; --ink2: #475569; --ink3: #94a3b8;
    --surf:   #f8fafc; --card: #fff;   --bdr:  #e2e8f0; --bdr2: #f1f5f9;
    --fast:   #16a34a; --fast-bg:#f0fdf4; --fast-b:#bbf7d0;
    --slow:   #d97706; --slow-bg:#fffbeb; --slow-b:#fde68a;
    --dead:   #dc2626; --dead-bg:#fef2f2; --dead-b:#fecaca;
    --disc:   #6d28d9; --disc-bg:#f5f3ff; --disc-b:#ddd6fe;
    --blue:   #2563eb; --blue-bg:#eff6ff; --blue-b:#bfdbfe;
}
.gm-modal-overlay .tr { text-align:right; }
.gm-modal-overlay .gm-sub { font-size:1.2rem; color:var(--ink3); margin-top:.15rem; white-space:normal; line-height:1.35; }
.gm-modal-sel { width:100%; padding:.45rem .6rem; border:1px solid var(--bdr); border-radius:.45rem;
                font-size:1.4rem; margin-bottom:.75rem; background:#fff; }
.gm-modal-radio { display:flex; flex-direction:column; gap:.35rem; margin-bottom:.75rem; font-size:1.4rem; }
.gm-modal-radio label { font-weight:500; display:flex; gap:.4rem; align-items:flex-start; }
.gm-modal-info { background:#f0fdfa; border:1px solid #99f6e4; color:#115e59; border-radius:.45rem;
                 padding:.6rem .875rem; font-size:1.3rem; margin-bottom:.875rem; line-height:1.55; }
.gm-modal-obral .gm-modal-warn { background:#fdf2f8; border-color:#fbcfe8; color:#9d174d; }
.gm-modal-obral .gm-btn-ok { background:#be185d; }
.gm-modal.gm-modal-wide { max-width:880px; max-height:92vh; display:flex; flex-direction:column; }
.gm-modal-wide .gm-modal-bd { overflow-y:auto; }
.gm-inp { padding:.4rem .55rem; border:1px solid var(--bdr); border-radius:.4rem; font-size:1.4rem; background:#fff; }
.gm-inp:focus { outline:none; border-color:#be185d; }
.gm-ob-ctrl { display:flex; flex-wrap:wrap; gap:.5rem; align-items:flex-end; margin-bottom:.75rem;
              background:var(--surf); border:1px solid var(--bdr); border-radius:.5rem; padding:.6rem .75rem; }
.gm-ob-ctrl label { display:flex; flex-direction:column; font-size:1.2rem; font-weight:700; color:var(--ink2); gap:.2rem; }
.gm-ob-ctrl .gm-inp { width:110px; }
.gm-ob-ctrl button { padding:.45rem .9rem; border:none; border-radius:.4rem; background:#be185d; color:#fff;
                     font-size:1.4rem; font-weight:700; cursor:pointer; }
.gm-ob-tbl { width:100%; border-collapse:collapse; font-size:1.3rem; margin-bottom:.75rem; }
.gm-ob-tbl th { text-align:left; font-size:1.2rem; color:var(--ink2); border-bottom:2px solid var(--bdr); padding:.35rem .4rem; white-space:nowrap; }
.gm-ob-tbl td { border-bottom:1px solid var(--bdr); padding:.35rem .4rem; vertical-align:middle; }
.gm-ob-tbl tr.gm-ob-first td { border-top:2px solid var(--bdr); }
.gm-ob-tbl .tr { text-align:right; white-space:nowrap; }
.gm-ob-tbl .gm-inp { width:110px; text-align:right; }
.gm-ob-tbl .gm-inp.bad { border-color:var(--dead); background:#fef2f2; }
.gm-ob-name { font-weight:700; color:var(--ink); }
.gm-ob-msg { font-size:1.2rem; line-height:1.3; }
.gm-ob-msg.bad { color:var(--dead); font-weight:700; }
.gm-ob-msg.warn { color:#b45309; }
.gm-ob-msg.ok { color:#15803d; }
.gm-ob-alert { border-radius:.45rem; padding:.55rem .8rem; font-size:1.3rem; margin-bottom:.75rem; line-height:1.5; }
.gm-ob-alert.warn { background:#fffbeb; border:1px solid #fde68a; color:#92400e; }
.gm-ob-alert.bad  { background:#fef2f2; border:1px solid #fecaca; color:#991b1b; }
.gm-wa-ta { width:100%; min-height:300px; font-family:inherit; font-size:1.4rem; line-height:1.45; padding:.6rem .75rem;
            border:1px solid var(--bdr); border-radius:.45rem; }
.gm-tabs { display:flex; gap:.4rem; margin-bottom:.6rem; }
.gm-tabs button { flex:1; padding:.5rem .6rem; border:1px solid var(--bdr); border-radius:.45rem; background:#fff;
                  font-size:1.4rem; font-weight:700; color:var(--ink2); cursor:pointer; }
.gm-tabs button.on { background:#be185d; border-color:#be185d; color:#fff; }
.gm-modal-inp { width:100%; padding:.5rem .7rem; border:1px solid var(--bdr); border-radius:.45rem; font-size:1.4rem; margin-bottom:.75rem; }
.gm-modal-overlay { display:none; position:fixed; inset:0;
                    background:rgba(15,23,42,.5); z-index:99999;
                    align-items:center; justify-content:center; padding:1rem; }
.gm-modal-overlay.open { display:flex; }
.gm-modal { background:#fff; border-radius:.75rem; width:100%; max-width:430px;
            box-shadow:0 20px 60px rgba(0,0,0,.25); overflow:hidden; }
.gm-modal-hd { padding:.875rem 1.1rem; border-bottom:1px solid var(--bdr);
               display:flex; align-items:center; justify-content:space-between; }
.gm-modal-hd h3 { font-size:1.4rem; font-weight:700; color:var(--ink); margin:0; }
.gm-modal-close { background:none; border:none; font-size:1.4rem; cursor:pointer;
                  color:var(--ink3); padding:.1rem .3rem; border-radius:.3rem; }
.gm-modal-close:hover { color:var(--ink); background:var(--surf); }
.gm-modal-bd { padding:1.1rem; }
.gm-modal-name { font-weight:700; font-size:1.4rem; color:var(--ink); margin-bottom:.15rem; }
.gm-modal-meta { font-size:1.4rem; color:var(--ink2); margin-bottom:.875rem; }
.gm-modal-warn { background:var(--disc-bg); border:1px solid var(--disc-b);
                 border-radius:.45rem; padding:.6rem .875rem; font-size:1.4rem;
                 color:var(--disc); margin-bottom:.875rem; line-height:1.55; }
.gm-modal-lbl  { font-size:1.4rem; font-weight:700; color:var(--ink2);
                 margin-bottom:.3rem; display:block; }
.gm-modal-ta   { width:100%; padding:.55rem .75rem; border:1px solid var(--bdr);
                 border-radius:.45rem; font-size:1.4rem; resize:vertical;
                 min-height:85px; font-family:inherit; color:var(--ink); }
.gm-modal-ta:focus { outline:none; border-color:var(--disc); }
.gm-modal-err  { font-size:1.4rem; color:var(--dead); margin-top:.3rem; display:none; }
.gm-modal-ft   { padding:.75rem 1.1rem; border-top:1px solid var(--bdr);
                 display:flex; justify-content:flex-end; gap:.5rem; }
.gm-btn-cancel { padding:.4rem .9rem; background:var(--surf); color:var(--ink2);
                 border:1px solid var(--bdr); border-radius:.4rem;
                 font-size:1.4rem; cursor:pointer; }
.gm-btn-ok     { padding:.4rem 1rem; background:var(--disc); color:#fff; border:none;
                 border-radius:.4rem; font-size:1.4rem; font-weight:700; cursor:pointer; }
.gm-btn-ok:hover { background:#5b21b6; }
.gm-modal-overlay span { color:inherit; }
</style>

<div class="content-wrapper">
<section class="content">
<div class="gm2">

{{-- Pemberitahuan --}}
@if(session('success'))
    <div class="al al-ok">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="al al-err">{{ session('error') }}</div>
@endif
@if($errors->any())
    <div class="al al-err">
        @if(count($errors->all()) === 1)
            {{ $errors->first() }}
        @else
            Belum disimpan:
            <ul style="margin:.3rem 0 0 1.2rem;padding:0">
                @foreach($errors->all() as $msg)<li>{{ $msg }}</li>@endforeach
            </ul>
        @endif
    </div>
@endif
@if($obralSelesai > 0)
    <div class="al al-info">{{ $obralSelesai }} barang obral sudah habis stoknya dan otomatis di-discontinue ("Habis obral"). Lihat di kotak Discontinued.</div>
@endif
@if(!$adaKolomBaru)
    <div class="al al-err">Kolom obral & musim belum bisa dibuat otomatis. Jalankan file <code>docs/sql/2026-11-27-obral-dan-musim.sql</code> di phpMyAdmin, lalu buka ulang halaman ini.</div>
@endif

{{-- Stok minus: masalah kerapian DATA (membuat nilai stok, hari stok, dan reorder
     ikut salah). Dibereskan lewat Stock Opname, bukan diedit manual. --}}
@if($negativeStockCount > 0)
<details class="warn">
    <summary>{{ $negativeStockCount }} barang stok minus: perhitungan pergerakan dan reorder bisa salah. Klik untuk melihat daftarnya.</summary>
    <table>
        <thead><tr><th>Barang</th><th>Kategori</th><th>Merk</th><th style="text-align:right">Stok sistem</th><th>Jadi minus karena</th></tr></thead>
        <tbody>
        @foreach($negativeStockGoods as $ng)
            <tr>
                <td>{{ $ng->nama }} <span style="color:#8a6a2a">({{ $ng->kode }})</span></td>
                <td>{{ $ng->kategori ?? '-' }}</td>
                <td>{{ $ng->merk ?? '-' }}</td>
                <td style="text-align:right;color:#B42318;font-weight:600">{{ number_format($ng->stok) }}</td>
                <td>
                    @if($ng->penyebab_url)
                        <a href="{{ url($ng->penyebab_url) }}">{{ $ng->penyebab }}</a>
                    @else
                        {{ $ng->penyebab ?? '-' }}
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <div style="margin-top:8px">Perbaiki lewat menu <b>Stock Opname</b> (hitung fisik ulang), jangan diedit langsung di database, supaya riwayat penyesuaiannya tetap tercatat.</div>
</details>
@endif

{{-- Judul + filter + kotak status --}}
<section class="box">
    <div class="top">
        <h1>Analisis Pergerakan Barang</h1>
        <select class="inp" id="gmPeriode" style="width:270px" title="Periode penjualan yang dinilai"></select>
        <select class="inp" id="gmKat" style="width:170px">
            <option value="">Semua kategori</option>
            @foreach($categories as $cat)
                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
            @endforeach
        </select>
        <input class="inp" id="gmCari" placeholder="Cari nama atau kode barang" style="width:190px">
        <button class="gb ghost" type="button" id="gmReset">Reset</button>
        <form class="custom" id="gmCustom" method="GET" action="{{ route('admin.reports.movement') }}">
            Dari <input type="date" class="inp" name="start_date" value="{{ $startDate }}">
            sampai <input type="date" class="inp" name="end_date" value="{{ $endDate }}">
            <button class="gb" type="submit">Terapkan</button>
        </form>
    </div>
    <div class="sec-h">
        <b>Status barang</b>
        <span class="muted">Klik untuk menyaring tabel. Arahkan kursor ke kotak untuk penjelasan.</span>
        <span class="modal-t" id="gmModalTertahan" title="Nilai stok (stok × harga beli) barang Dead Stock + Slow Moving"></span>
        @if($adaKolomBaru)
        <a class="gb sm" href="{{ route('admin.seasons.index') }}">Atur musim</a>
        @endif
    </div>
    <div class="chips" id="gmChips"></div>
</section>

{{-- Tabel --}}
<section class="box">
    <div class="tbar">
        <div class="sum" id="gmSum"></div>
        <select class="inp" id="gmSaran" style="width:230px" title="Saring berdasarkan saran tindakan"></select>
        <select class="inp" id="gmSort" title="Urutkan"></select>
        <button class="gb ghost" type="button" id="gmDir"></button>
    </div>
    <div class="twrap">
        <table class="t"><thead id="gmHead"></thead><tbody id="gmBody"></tbody></table>
    </div>
    <div class="pagi">
        <span><span id="gmPagiInfo"></span> &nbsp;
            <select class="inp" id="gmPerPage" title="Barang per halaman">
                <option value="25">25 / halaman</option>
                <option value="50" selected>50 / halaman</option>
                <option value="100">100 / halaman</option>
                <option value="200">200 / halaman</option>
                <option value="99999">Semua</option>
            </select>
        </span>
        <div class="pg" id="gmPagi"></div>
    </div>

    {{-- Muncul saat ada barang dicentang --}}
    <div class="bulk" id="gmBulkBar">
        <span class="info" id="gmBulkCount"></span>
        @if($bolehSidak)
        <button type="button" class="gb sm" id="gmBulkCekBtn" onclick="gmOpenCek()"
            title="Buat daftar cek stok fisik (Sidak). Bisa dicek langsung di HP atau dikirim ke pegawai lewat WA.">Tugas Cek Stok (<span id="gmBulkCekN">0</span>)</button>
        @endif
        @if($adaKolomBaru)
        <button type="button" class="gb sm" id="gmBulkObralBtn" onclick="gmOpenBulkObral()">Obral (<span id="gmBulkObralN">0</span>)</button>
        <button type="button" class="gb sm" id="gmBulkLabelBtn" onclick="gmOpenLabel()">Label Obral (<span id="gmBulkLabelN">0</span>)</button>
        <button type="button" class="gb sm" id="gmBulkWaBtn" onclick="gmOpenWa()">Teks WA Obral (<span id="gmBulkWaN">0</span>)</button>
        @endif
        <button type="button" class="gb sm" id="gmBulkDiscBtn" onclick="gmOpenBulkModal()">Discontinue (<span id="gmBulkDiscN">0</span>)</button>
        <button type="button" class="gb sm ghost" onclick="gmClearAll()">Batal Pilih</button>
    </div>
</section>

</div>{{-- end gm2 --}}
</section>
</div>{{-- end content-wrapper --}}

{{-- ═══════════════════════════════════════════════════════════════════ --}}
{{-- MODAL DISCONTINUE — di luar content-wrapper agar z-index benar     --}}
{{-- ═══════════════════════════════════════════════════════════════════ --}}
<div class="gm-modal-overlay" id="gmDiscModal">
    <div class="gm-modal">
        <div class="gm-modal-hd">
            <h3>🗑️ Konfirmasi Discontinue</h3>
            <button class="gm-modal-close" type="button" onclick="gmCloseModal()">✕</button>
        </div>
        <div class="gm-modal-bd">
            <div class="gm-modal-name" id="gmModalName">—</div>
            <div class="gm-modal-meta" id="gmModalMeta">—</div>
            <div class="gm-modal-warn">
                ⚠️ Barang yang di-discontinue <strong>tidak akan muncul lagi</strong> dalam
                laporan pergerakan. Riwayat transaksi tetap tersimpan dan barang dapat
                diaktifkan kembali kapan saja dari tab <em>Disc.</em>
            </div>
            <label class="gm-modal-lbl" for="gmDiscReason">
                Alasan Discontinue <span style="color:var(--dead)">*</span>
            </label>
            <textarea class="gm-modal-ta" id="gmDiscReason"
                placeholder="Contoh: Tidak laku selama 6 bulan, digantikan produk baru..."></textarea>
            <div class="gm-modal-err" id="gmDiscErr">Alasan wajib diisi minimal 5 karakter.</div>
        </div>
        <div class="gm-modal-ft">
            <button type="button" class="gm-btn-cancel" onclick="gmCloseModal()">Batal</button>
            <button type="button" class="gm-btn-ok" onclick="gmSubmitDisc()">🗑️ Ya, Discontinue</button>
        </div>
    </div>
</div>

{{-- Form tersembunyi untuk POST discontinue --}}
<form method="POST" id="gmDiscForm" style="display:none">
    @csrf
    <input type="hidden" name="reason" id="gmDiscFormReason">
</form>

{{-- ═══════════════════════════════════════════════════════════════════ --}}
{{-- MODAL BULK DISCONTINUE — checklist banyak barang sekaligus         --}}
{{-- ═══════════════════════════════════════════════════════════════════ --}}
<div class="gm-modal-overlay" id="gmBulkModal">
    <div class="gm-modal">
        <div class="gm-modal-hd">
            <h3>🗑️ Discontinue Sekaligus</h3>
            <button class="gm-modal-close" type="button" onclick="gmCloseBulkModal()">✕</button>
        </div>
        <div class="gm-modal-bd">
            <div class="gm-modal-name" id="gmBulkCountModal">0 barang dipilih</div>
            <div class="gm-modal-warn">
                ⚠️ Hanya barang berstatus Dead Stock / Kosong yang ikut. Barang itu tidak
                punya riwayat penjualan yang bagus dalam 12 bulan terakhir maupun periode
                filter saat ini. Semuanya akan di-discontinue dengan <strong>satu alasan yang sama</strong> di bawah ini.
                Bisa diaktifkan kembali kapan saja dari tab <em>Disc.</em>
            </div>
            <label class="gm-modal-lbl" for="gmBulkReason">
                Alasan Discontinue (berlaku untuk semua barang terpilih) <span style="color:var(--dead)">*</span>
            </label>
            <textarea class="gm-modal-ta" id="gmBulkReason"
                placeholder="Contoh: Beres-beres data lama — tidak ada penjualan >12 bulan, stok habis..."></textarea>
            <div class="gm-modal-err" id="gmBulkErr">Alasan wajib diisi minimal 5 karakter.</div>
        </div>
        <div class="gm-modal-ft">
            <button type="button" class="gm-btn-cancel" onclick="gmCloseBulkModal()">Batal</button>
            <button type="button" class="gm-btn-ok" onclick="gmSubmitBulk()">🗑️ Ya, Discontinue Semua</button>
        </div>
    </div>
</div>

{{-- Form tersembunyi untuk POST bulk-discontinue --}}
<form method="POST" id="gmBulkForm" action="{{ route('admin.reports.movement.bulk-discontinue') }}" style="display:none">
    @csrf
    <input type="hidden" name="reason" id="gmBulkFormReason">
    <div id="gmBulkFormIds"></div>
</form>

@if($adaKolomBaru)
{{-- ═══════════════════════════════════════════════════════════════════ --}}
{{-- MODAL OBRAL — satu barang atau banyak sekaligus                    --}}
{{-- ═══════════════════════════════════════════════════════════════════ --}}
<div class="gm-modal-overlay gm-modal-obral" id="gmObralModal">
    <div class="gm-modal{{ $hargaObralAda ? ' gm-modal-wide' : '' }}">
        <div class="gm-modal-hd">
            <h3>🏷️ {{ $hargaObralAda ? 'Obral & Harga Obral' : 'Tandai Obral' }}</h3>
            <button class="gm-modal-close" type="button" onclick="gmCloseObral()">✕</button>
        </div>
        <div class="gm-modal-bd">
            <div class="gm-modal-name" id="gmObralName">—</div>
            <div class="gm-modal-warn">
                Barang obral <strong>tidak dinilai fast/slow/dead</strong> dan <strong>tidak pernah disarankan order</strong>
                (di sini maupun di Rekomendasi Reorder), walaupun selama obral lakunya ramai.
                @if($hargaObralAda)
                Harga jual diganti <strong>harga obral</strong> di bawah ini (tercatat di riwayat harga; Kasir HP/PC ikut berubah saat sinkron).
                Begitu stoknya habis, barang <strong>otomatis di-discontinue</strong> dan <strong>harganya kembali normal</strong>.
                Batal obral juga mengembalikan harga normal.
                @else
                Begitu stoknya habis, barang <strong>otomatis di-discontinue</strong> dengan alasan "Habis obral".
                Harga obral tetap diatur seperti biasa (ubah harga jual / diskon di kasir).
                @endif
            </div>
            @if($hargaObralAda)
            <div id="gmObUnchecked" class="gm-ob-alert warn" style="display:none"></div>
            <div class="gm-ob-ctrl">
                <label>Diskon (%)
                    <input type="number" class="gm-inp" id="gmObDisc" min="1" max="90" step="1" value="30"></label>
                <label>Pembulatan
                    <select class="gm-inp" id="gmObRound">
                        <option value="100">ke Rp100</option>
                        <option value="500" selected>ke Rp500</option>
                        <option value="1000">ke Rp1.000</option>
                        <option value="1">tanpa</option>
                    </select></label>
                <button type="button" onclick="gmObApply()">Terapkan ke semua</button>
                <span class="gm-sub" style="flex-basis:100%">
                    Dibulatkan ke bawah, tapi <strong>tidak pernah di bawah modal</strong> (paling rendah sama dengan modal).
                    Tiap harga masih bisa diubah sendiri. Kosongkan kalau satuan itu tidak diobral.
                    @unless($lihatModal) Harga modal tidak ditampilkan untuk akun ini; harga di bawah modal ditolak saat disimpan. @endunless
                </span>
            </div>
            <div id="gmObLoading" class="gm-sub" style="margin-bottom:.75rem">Memuat harga…</div>
            <table class="gm-ob-tbl" id="gmObTbl" style="display:none">
                <thead><tr>
                    <th>Barang</th><th>Satuan</th>
                    @if($lihatModal)<th class="tr">Modal</th>@endif
                    <th class="tr">Harga normal</th><th class="tr">Harga obral</th><th>Keterangan</th>
                </tr></thead>
                <tbody id="gmObBody"></tbody>
            </table>
            <div id="gmObErr" class="gm-ob-alert bad" style="display:none"></div>
            @endif
            <label class="gm-modal-lbl" for="gmObralReason">Keterangan obral <span style="color:var(--dead)">*</span></label>
            <textarea class="gm-modal-ta" id="gmObralReason"
                placeholder="Contoh: Dead stock 6 bulan, dihabiskan dengan harga obral"></textarea>
            <div class="gm-modal-err" id="gmObralErr">Keterangan wajib diisi minimal 5 karakter.</div>
        </div>
        <div class="gm-modal-ft">
            <button type="button" class="gm-btn-cancel" onclick="gmCloseObral()">Batal</button>
            <button type="button" class="gm-btn-ok" id="gmObralOk" onclick="gmSubmitObral()">🏷️ Ya, Obral</button>
        </div>
    </div>
</div>
<form method="POST" id="gmObralForm" action="{{ route('admin.reports.movement.clearance') }}" style="display:none">
    @csrf
    <input type="hidden" name="reason" id="gmObralFormReason">
    <div id="gmObralFormIds"></div>
    <div id="gmObralFormPrices"></div>
</form>

{{-- ═══════════════════════════════════════════════════════════════════ --}}
{{-- MODAL LABEL OBRAL — cetak label rak harga coret                     --}}
{{-- ═══════════════════════════════════════════════════════════════════ --}}
<div class="gm-modal-overlay gm-modal-obral" id="gmLabelModal">
    <div class="gm-modal">
        <div class="gm-modal-hd">
            <h3>🖨️ Cetak Label Obral</h3>
            <button class="gm-modal-close" type="button" onclick="gmCloseLabel()">✕</button>
        </div>
        <div class="gm-modal-bd">
            <div class="gm-modal-name" id="gmLabelName">—</div>
            <div class="gm-modal-info">
                Label rak dengan <strong>harga normal dicoret</strong>, harga obral besar, hemat Rp &amp; persen,
                dan tulisan "selama stok ada". Kertas A4 mendatar, sama seperti Print Harga Display.
            </div>
            <label class="gm-modal-lbl" for="gmLabelLayout">Jumlah label per halaman</label>
            <select class="gm-modal-sel" id="gmLabelLayout">
                <option value="2">2 (besar)</option>
                <option value="4">4</option>
                <option value="9" selected>9</option>
                <option value="16">16 (kecil)</option>
            </select>
            <label class="gm-modal-lbl" for="gmLabelQty">Jumlah cetak per barang</label>
            <input type="number" class="gm-modal-inp" id="gmLabelQty" min="1" max="10" value="1">
        </div>
        <div class="gm-modal-ft">
            <button type="button" class="gm-btn-cancel" onclick="gmCloseLabel()">Batal</button>
            <button type="button" class="gm-btn-ok" onclick="gmSubmitLabel()">🖨️ Cetak</button>
        </div>
    </div>
</div>
<form method="POST" id="gmLabelForm" action="{{ route('admin.reports.movement.clearance-label') }}" target="_blank" style="display:none">
    @csrf
    <input type="hidden" name="layout" id="gmLabelFormLayout">
    <div id="gmLabelFormIds"></div>
</form>

{{-- ═══════════════════════════════════════════════════════════════════ --}}
{{-- MODAL TEKS WA OBRAL — untuk pelanggan / untuk pegawai               --}}
{{-- ═══════════════════════════════════════════════════════════════════ --}}
<div class="gm-modal-overlay gm-modal-obral" id="gmWaModal">
    <div class="gm-modal gm-modal-wide" style="max-width:600px">
        <div class="gm-modal-hd">
            <h3>📋 Teks WA Obral</h3>
            <button class="gm-modal-close" type="button" onclick="gmCloseWa()">✕</button>
        </div>
        <div class="gm-modal-bd">
            <div class="gm-tabs">
                <button type="button" id="gmWaTabCust" class="on" onclick="gmWaTab('pelanggan')">Untuk pelanggan</button>
                <button type="button" id="gmWaTabStaff" onclick="gmWaTab('pegawai')">Untuk pegawai</button>
            </div>
            <div class="gm-sub" id="gmWaHint" style="margin-bottom:.5rem"></div>
            <textarea class="gm-wa-ta" id="gmWaText">Memuat…</textarea>
        </div>
        <div class="gm-modal-ft">
            <button type="button" class="gm-btn-cancel" onclick="gmCloseWa()">Tutup</button>
            <button type="button" class="gm-btn-ok" id="gmWaCopyBtn" onclick="gmCopyWa()">📋 Copy Teks</button>
        </div>
    </div>
</div>
@endif

@if($bolehSidak)
{{-- ═══════════════════════════════════════════════════════════════════ --}}
{{-- MODAL TUGAS CEK STOK — buat sesi Sidak berisi daftar barang         --}}
{{-- ═══════════════════════════════════════════════════════════════════ --}}
<div class="gm-modal-overlay" id="gmCekModal">
    <div class="gm-modal">
        <div class="gm-modal-hd">
            <h3>🔎 Tugas Cek Stok</h3>
            <button class="gm-modal-close" type="button" onclick="gmCloseCek()">✕</button>
        </div>
        <div class="gm-modal-bd">
            <div class="gm-modal-name" id="gmCekName">—</div>
            <div class="gm-modal-info" style="background:#eff6ff;border-color:#bfdbfe;color:#1e3a8a">
                Dibuat <strong>satu sesi Sidak</strong> berisi daftar ini (urut seperti tabel). Di halaman sidak:
                cek langsung di HP, atau <strong>Copy teks WA</strong> untuk pegawai lalu ketik angka balasannya di daftar yang sama.
                Setelah selesai, <strong>Jadikan Stock Opname</strong> mengoreksi stok, dan barang yang fisiknya 0 bisa langsung di-discontinue.
            </div>
            <label class="gm-modal-lbl" for="gmCekNote">Catatan</label>
            <input class="gm-modal-inp" id="gmCekNote" maxlength="255"
                value="{{ $status === 'dead' ? 'Cek fisik dead stock' : ($status === 'at_risk' ? 'Cek fisik barang Perlu Dicek' : 'Cek fisik barang') }}">
            <label class="gm-modal-lbl" for="gmCekBy">Dihitung oleh (nama pegawai, boleh kosong)</label>
            <input class="gm-modal-inp" id="gmCekBy" maxlength="100" placeholder="contoh: Rina">
            <div class="gm-modal-err" id="gmCekErr"></div>
        </div>
        <div class="gm-modal-ft">
            <button type="button" class="gm-btn-cancel" onclick="gmCloseCek()">Batal</button>
            <button type="button" class="gm-btn-ok" style="background:#2563eb" onclick="gmSubmitCek()">🔎 Buat Tugas</button>
        </div>
    </div>
</div>
<form method="POST" id="gmCekForm" action="{{ route('admin.sidak.store') }}" style="display:none">
    @csrf
    <input type="hidden" name="note" id="gmCekFormNote">
    <input type="hidden" name="counted_by" id="gmCekFormBy">
    <div id="gmCekFormIds"></div>
</form>
@endif

@if($adaKolomBaru)

{{-- ═══════════════════════════════════════════════════════════════════ --}}
{{-- MODAL MUSIM — tandai barang / jenisnya sebagai musiman              --}}
{{-- ═══════════════════════════════════════════════════════════════════ --}}
<div class="gm-modal-overlay" id="gmSeasonModal">
    <div class="gm-modal">
        <div class="gm-modal-hd">
            <h3>🗓️ Atur Musim</h3>
            <button class="gm-modal-close" type="button" onclick="gmCloseSeason()">✕</button>
        </div>
        <form method="POST" id="gmSeasonForm">
            @csrf
            <div class="gm-modal-bd">
                <div class="gm-modal-name" id="gmSeasonName">—</div>
                <div class="gm-modal-meta" id="gmSeasonMeta">—</div>
                @if($seasons->isEmpty())
                <div class="gm-modal-info">
                    Belum ada musim. Buat dulu (mis. "Ramadan & Lebaran", "Tahun Ajaran Baru") di
                    <a href="{{ route('admin.seasons.index') }}">halaman Atur Musim</a>.
                </div>
                @else
                <div class="gm-modal-info">
                    Barang musiman di luar musimnya tidak dicap Slow/Dead dan tidak disarankan order.
                    Menjelang musim, muncul saran <em>Siapkan Stok Musim</em> sebanyak penjualan musim lalu.
                </div>
                <label class="gm-modal-lbl">Berlaku untuk</label>
                <div class="gm-modal-radio">
                    <label id="gmSeasonScopeJenisWrap"><input type="radio" name="scope" value="jenis" id="gmSeasonScopeJenis" onchange="gmSeasonScopeChanged()">
                        <span>Seluruh jenis <strong id="gmSeasonTipe">—</strong> (barang lain di jenis ini ikut)</span></label>
                    <label><input type="radio" name="scope" value="barang" id="gmSeasonScopeBarang" onchange="gmSeasonScopeChanged()">
                        <span>Barang ini saja</span></label>
                </div>
                <label class="gm-modal-lbl" for="gmSeasonSelect">Musim</label>
                <select name="season_id" id="gmSeasonSelect" class="gm-modal-sel">
                    <option value="" id="gmSeasonOptIkut">— Ikut jenisnya —</option>
                    <option value="0">Bukan musiman</option>
                    @foreach($seasons as $sea)
                    <option value="{{ $sea->id }}">{{ $sea->name }}</option>
                    @endforeach
                </select>
                <div style="font-size:1.3rem"><a href="{{ route('admin.seasons.index') }}">Kelola daftar & tanggal musim →</a></div>
                @endif
            </div>
            <div class="gm-modal-ft">
                <button type="button" class="gm-btn-cancel" onclick="gmCloseSeason()">Batal</button>
                @if($seasons->isNotEmpty())
                <button type="submit" class="gm-btn-ok" style="background:#0f766e">🗓️ Simpan</button>
                @endif
            </div>
        </form>
    </div>
</div>
@endif

<script>
(function () {
    /* ══════════════════════════════════════════════════════════════════
       Data semua barang dikirim sekali. Saring status / saran / kategori /
       cari, urutkan, dan halaman dikerjakan di sini tanpa memuat ulang.
       ══════════════════════════════════════════════════════════════════ */
    var GM       = @json($gmData);
    var goods    = GM.goods, disc = GM.disc;
    var _goodId  = null;
    var _baseUrl = '{{ url("admin/reports/movement") }}';
    var _csrf    = '{{ csrf_token() }}';

    var ST = {
        all:     { l: 'Semua', c: '#5f5e5a', tip: 'Semua barang aktif (tanpa discontinued).' },
        fast:    { l: 'Fast Moving', c: '#3B6D11', bg: '#EAF3DE', fg: '#27500A', tip: 'Minimal ' + GM.minFast + ' transaksi dalam periode ini dan terakhir laku 30 hari terakhir.' },
        slow:    { l: 'Slow Moving', c: '#D08A1E', bg: '#FAEEDA', fg: '#633806', tip: 'Masih laku, tapi jarang.' },
        dead:    { l: 'Dead Stock', c: '#E24B4A', bg: '#FCEBEB', fg: '#791F1F', tip: 'Stok masih ada, tidak laku 90 hari atau lebih, dan riwayat 12 bulan juga sepi.' },
        at_risk: { l: 'Perlu Dicek', c: '#7F77DD', bg: '#EEEDFE', fg: '#3C3489', tip: 'Tidak laku di periode ini, TAPI 12 bulan terakhir rata-rata laku minimal 1x/bulan. Cek dulu stoknya, jangan langsung di-discontinue.' },
        kosong:  { l: 'Kosong', c: '#9a988f', bg: '#F1EFE8', fg: '#444441', tip: 'Stok habis dan tidak laku. Tidak menahan modal, jadi bukan dead stock.' },
        baru:    { l: 'Baru Masuk', c: '#378ADD', bg: '#E6F1FB', fg: '#0C447C', tip: 'Baru di-loading kurang dari 30 hari dan belum laku sesudahnya. Belum bisa dinilai.' },
        musiman: { l: 'Musiman', c: '#1D9E75', bg: '#E1F5EE', fg: '#085041', tip: 'Barang musiman yang sedang di luar musim atau musimnya segera tiba. Tidak dinilai Slow/Dead.' },
        obral:   { l: 'Obral', c: '#D4537E', bg: '#FBEAF0', fg: '#72243E', tip: 'Sedang dihabiskan: tidak disarankan order, otomatis di-discontinue saat stok habis.' },
        kandidat_musim: { l: 'Kandidat Musiman', c: '#1D9E75', dash: true, tip: 'Belum ditandai musim, tapi sebagian besar penjualan 12 bulan terakhir terjadi di bulan tertentu. Klik tanda "pola musim?" di barangnya untuk menandai.' },
        discontinued: { l: 'Discontinued', c: '#534AB7', tip: 'Barang tidak aktif. Tidak dihitung dalam laporan.' }
    };
    var CHIP_KEYS = ['all', 'fast', 'slow', 'dead', 'at_risk', 'kosong', 'baru', 'musiman', 'obral', 'kandidat_musim', 'discontinued'];
    var REC = {
        reorder_urgent: ['#FCEBEB', '#791F1F'], reorder: ['#FFF1EC', '#8A3A12'], season_prep: ['#E1F5EE', '#085041'],
        maintain: ['#EAF3DE', '#27500A'], monitor: ['#F1EFE8', '#444441'], reduce_order: ['#FAEEDA', '#633806'],
        review: ['#FAEEDA', '#633806'], clearance: ['#FBEAF0', '#72243E'], discontinue: ['#EEEDFE', '#3C3489'],
        restock_check: ['#EEEDFE', '#3C3489'], review_stuck: ['#EEEDFE', '#3C3489'], wait: ['#E6F1FB', '#0C447C'],
        season_hold: ['#E1F5EE', '#085041'], clearance_run: ['#FBEAF0', '#72243E']
    };
    var REC_LABEL = Object.assign({
        season_hold: 'Simpan, jangan order', season_prep: 'Siapkan Stok Musim', clearance_run: 'Habiskan, jangan order'
    }, GM.recLabels || {});
    // Kelompok saran = 4 kartu aksi di tampilan lama
    var GROUPS = [
        ['g:reorder', 'Perlu reorder', function (g) { return g.rec === 'reorder' || g.rec === 'reorder_urgent'; }],
        ['g:season', 'Siapkan stok musim', function (g) { return g.rec === 'season_prep'; }],
        ['g:review', 'Perlu review', function (g) { return g.rec === 'review' || g.rec === 'clearance'; }],
        ['g:disc', 'Kandidat discontinue', function (g) { return g.rec === 'discontinue'; }]
    ];
    // Nama urutan (sama dengan parameter sort_by) → kunci data
    var SORTK = { total_omzet: 'om', nilai_stok: 'nv', total_laba: 'lb', total_transaksi: 'trx', total_qty: 'qty',
                  days_of_stock: 'dos', days_since_trx: 'dst', stok_sekarang: 'stok', urgency: 'urg', nama: 'nama' };
    var SORTS = [
        ['total_omzet', 'Urut: omzet'], ['nilai_stok', 'Urut: nilai stok'], ['total_laba', 'Urut: laba'],
        ['total_transaksi', 'Urut: transaksi'], ['total_qty', 'Urut: qty terjual'], ['days_of_stock', 'Urut: stok cukup untuk'],
        ['days_since_trx', 'Urut: terakhir laku'], ['stok_sekarang', 'Urut: stok'], ['urgency', 'Urut: urgensi saran'], ['nama', 'Urut: nama barang']
    ];
    // Urutan bawaan saat kotak status diklik: yang menahan modal diurutkan dari nilai stok terbesar.
    var SORT_PER_STATUS = { dead: 'nilai_stok', slow: 'nilai_stok', at_risk: 'nilai_stok', musiman: 'nilai_stok',
                            obral: 'nilai_stok', baru: 'nilai_stok', kosong: 'days_since_trx',
                            all: 'total_omzet', fast: 'total_omzet', kandidat_musim: 'total_qty' };

    var st = {
        status: CHIP_KEYS.indexOf(GM.init.status) >= 0 ? GM.init.status : 'all',
        saran: GM.init.saran || '', kat: GM.init.kat || '', q: GM.init.q || '',
        sort: SORTK[GM.init.sort] ? GM.init.sort : 'total_omzet', dir: GM.init.dir === 'asc' ? 'asc' : 'desc',
        page: 1, per: 50
    };
    var picked = {};      // id barang yang dicentang
    var _rows  = [];      // baris hasil saring + urut saat ini

    /* ── Format ───────────────────────────────────────────────── */
    var $  = function (id) { return document.getElementById(id); };
    var nf = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 });
    var esc = function (s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; };
    var rp = function (v) { return new Intl.NumberFormat('id-ID').format(Math.round(v || 0)); };
    var jt = function (v) {
        v = v || 0;
        if (!v) return 'Rp 0';
        if (Math.abs(v) >= 1e9) return 'Rp ' + (v / 1e9).toLocaleString('id-ID', { maximumFractionDigits: 2 }) + ' M';
        if (Math.abs(v) >= 1e6) return 'Rp ' + (v / 1e6).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' jt';
        return 'Rp ' + nf.format(Math.round(v / 1000)) + ' rb';
    };
    // Lama waktu dalam hari / bulan / tahun (bukan singkatan "h" yang mirip jam)
    var lama = function (d) {
        if (d < 60) return d + ' hari';
        if (d < 730) return Math.round(d / 30.4) + ' bulan';
        return (d / 365).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' tahun';
    };
    var pendek = function (d) { return lama(d).replace(' hari', ' hr').replace(' bulan', ' bln').replace(' tahun', ' thn'); };
    var tgl = function (s, tahun) {
        if (!s) return '—';
        var d = new Date(String(s).replace(' ', 'T'));
        return d.toLocaleDateString('id-ID', tahun === false ? { day: 'numeric', month: 'short' } : { day: 'numeric', month: 'short', year: 'numeric' });
    };

    /* ── Periode (hanya ini yang memuat ulang halaman) ────────── */
    (function () {
        var sel = $('gmPeriode');
        var hariAntara = function (a, b) { return Math.round((new Date(b) - new Date(a)) / 864e5); };
        // Tanggal lokal (toISOString memakai UTC dan bisa mundur sehari di WIB)
        var mundur = function (n) {
            var d = new Date(GM.today + 'T00:00:00');
            d.setDate(d.getDate() - n);
            return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
        };
        var pilih = (GM.end === GM.today && [30, 90, 180].indexOf(hariAntara(GM.start, GM.end)) >= 0) ? String(hariAntara(GM.start, GM.end)) : 'custom';
        // Yang terpilih menampilkan tanggalnya; pilihan lain menampilkan lamanya.
        var rentang = tgl(GM.start, false) + ' – ' + tgl(GM.end) + ' · ' + GM.days + ' hari';
        [['30', 'Periode 30 hari terakhir'], ['90', 'Periode 90 hari terakhir'], ['180', 'Periode 180 hari terakhir'], ['custom', 'Pilih tanggal sendiri…']].forEach(function (p) {
            var o = document.createElement('option');
            o.value = p[0];
            o.textContent = p[0] === pilih ? rentang : p[1];
            sel.appendChild(o);
        });
        sel.value = pilih;
        $('gmCustom').classList.toggle('on', pilih === 'custom');
        sel.addEventListener('change', function () {
            if (this.value === 'custom') { $('gmCustom').classList.add('on'); return; }
            var p = new URLSearchParams(location.search);
            p.set('start_date', mundur(+this.value));
            p.set('end_date', GM.today);
            location.search = p.toString();
        });
        // tanggal sendiri: bawa juga saringan yang sedang dipakai
        $('gmCustom').addEventListener('submit', function () {
            var f = this;
            new URLSearchParams(location.search).forEach(function (v, k) {
                if (k === 'start_date' || k === 'end_date') return;
                var h = document.createElement('input'); h.type = 'hidden'; h.name = k; h.value = v; f.appendChild(h);
            });
        });
    })();

    /* ── Saringan ─────────────────────────────────────────────── */
    function baseMatch(g) {
        if (st.kat && String(g.kid) !== st.kat) return false;
        if (st.q) {
            var q = st.q.toLowerCase();
            if (String(g.nama || '').toLowerCase().indexOf(q) < 0 && String(g.kode || '').toLowerCase().indexOf(q) < 0) return false;
        }
        return true;
    }
    function statusMatch(g, s) {
        if (s === 'all') return true;
        if (s === 'kandidat_musim') return !!g.kand;
        return g.status === s;
    }
    function saranFn(k) {
        if (!k) return function () { return true; };
        var grp = GROUPS.filter(function (x) { return x[0] === k; })[0];
        if (grp) return grp[2];
        return function (g) { return g.rec === k; };
    }
    function saranLabel(k) {
        var grp = GROUPS.filter(function (x) { return x[0] === k; })[0];
        return grp ? grp[1] : (REC_LABEL[k] || k);
    }

    /* ── Gambar ulang semua bagian ────────────────────────────── */
    function render() {
        var isDisc   = st.status === 'discontinued';
        var base     = goods.filter(baseMatch);
        var baseDisc = disc.filter(baseMatch);
        var forChips = base.filter(saranFn(st.saran));
        var rows     = isDisc ? baseDisc.slice() : forChips.filter(function (g) { return statusMatch(g, st.status); });

        // Kotak status: jumlah · persen · nilai stok (ikut kategori, cari, dan saran)
        var tot = forChips.length, agg = {};
        CHIP_KEYS.forEach(function (k) { agg[k] = { n: 0, v: 0 }; });
        forChips.forEach(function (g) {
            agg.all.n++; agg.all.v += g.nv || 0;
            if (agg[g.status]) { agg[g.status].n++; agg[g.status].v += g.nv || 0; }
            if (g.kand) { agg.kandidat_musim.n++; agg.kandidat_musim.v += g.nv || 0; }
        });
        agg.discontinued.n = baseDisc.length;
        $('gmChips').innerHTML = CHIP_KEYS.map(function (k) {
            var s = ST[k], a = agg[k];
            var pct = (k !== 'all' && k !== 'discontinued' && k !== 'kandidat_musim' && tot) ? Math.round(a.n / tot * 100) + '%' : '';
            var note = k === 'kosong' ? 'stok habis' : k === 'discontinued' ? 'tidak dihitung' : (a.v > 0 ? jt(a.v) : '');
            var dot = k === 'all' ? '' : '<span class="dot" style="background:' + (s.dash ? '#fff;border:1.5px dashed ' + s.c : s.c) + '"></span>';
            return '<button type="button" class="chip' + (st.status === k ? ' on' : '') + (a.n === 0 ? ' zero' : '') + '" data-k="' + k + '" title="' + esc(s.tip) + '">' +
                '<div class="cl">' + dot + esc(s.l) + (pct ? '<span class="pct">' + pct + '</span>' : '') + '</div>' +
                '<div class="cn"><b>' + nf.format(a.n) + '</b>' + note + '</div></button>';
        }).join('');
        $('gmModalTertahan').innerHTML = 'Modal tertahan (dead + slow) <b>' + jt(agg.dead.v + agg.slow.v) + '</b>';

        // Saran: jumlah ikut kategori, cari, dan status
        var forSaran = isDisc ? [] : base.filter(function (g) { return statusMatch(g, st.status); });
        var hitung = function (f) { return forSaran.filter(f).length; };
        $('gmSaran').innerHTML = '<option value="">Saran: semua</option><optgroup label="Kelompok">' +
            GROUPS.map(function (x) { return '<option value="' + x[0] + '">' + x[1] + ' (' + nf.format(hitung(x[2])) + ')</option>'; }).join('') +
            '</optgroup><optgroup label="Per label saran">' +
            Object.keys(REC).filter(function (k) { return REC[k]; }).map(function (k) {
                return '<option value="' + k + '">' + esc(REC_LABEL[k] || k) + ' (' + nf.format(hitung(saranFn(k))) + ')</option>';
            }).join('') + '</optgroup>';
        $('gmSaran').value = st.saran;
        $('gmSaran').disabled = isDisc;

        // Satu baris ringkasan (jumlah & nilai stok sudah ada di kotak status)
        var om = 0, lb = 0;
        if (!isDisc) rows.forEach(function (g) { om += g.om || 0; lb += g.lb || 0; });
        var apa = [ST[st.status].l];
        if (st.saran && !isDisc) apa.push(saranLabel(st.saran).toLowerCase());
        if (st.kat) apa.push($('gmKat').selectedOptions[0] ? $('gmKat').selectedOptions[0].textContent : '');
        if (st.q) apa.push('"' + st.q + '"');
        $('gmSum').innerHTML = '<b>' + esc(apa.join(' · ')) + '</b>' + (isDisc ? ' · tidak dihitung dalam ringkasan' :
            ' · omzet ' + GM.days + ' hari <b>' + jt(om) + '</b> · laba <b>' + jt(lb) + '</b>' +
            (om > 0 ? ' (margin ' + (lb / om * 100).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + '%)' : ''));

        // Urutkan
        var key = SORTK[st.sort], m = st.dir === 'asc' ? 1 : -1;
        if (!isDisc) rows.sort(function (a, b) {
            if (key === 'nama') return m * String(a.nama || '').localeCompare(String(b.nama || ''));
            return m * ((a[key] || 0) - (b[key] || 0));
        });
        $('gmSort').value = st.sort;
        $('gmSort').disabled = isDisc;
        $('gmDir').disabled = isDisc;
        $('gmDir').textContent = st.sort === 'nama' ? (st.dir === 'asc' ? '↑ A–Z' : '↓ Z–A') : (st.dir === 'desc' ? '↓ Terbesar' : '↑ Terkecil');
        _rows = rows;

        // Halaman
        var per   = st.per >= 99999 ? Math.max(rows.length, 1) : st.per;
        var pages = Math.max(1, Math.ceil(rows.length / per));
        if (st.page > pages) st.page = pages;
        var from = (st.page - 1) * per, view = rows.slice(from, from + per);
        if (isDisc) renderDisc(view, from); else renderMain(view, from);

        $('gmPagiInfo').textContent = rows.length ? nf.format(from + 1) + '–' + nf.format(from + view.length) + ' dari ' + nf.format(rows.length) + ' barang' : '';
        var pb = [], add = function (lbl, p, dis, on) {
            pb.push('<button type="button" data-p="' + p + '"' + (dis ? ' disabled' : '') + (on ? ' class="on"' : '') + '>' + lbl + '</button>');
        };
        if (pages > 1) {
            add('‹', st.page - 1, st.page <= 1);
            var tampil = {};
            [1, 2, st.page - 1, st.page, st.page + 1, pages - 1, pages].forEach(function (p) { if (p >= 1 && p <= pages) tampil[p] = 1; });
            var akhir = 0;
            Object.keys(tampil).map(Number).sort(function (a, b) { return a - b; }).forEach(function (p) {
                if (p - akhir > 1) pb.push('<span class="gap">…</span>');
                add(p, p, false, p === st.page);
                akhir = p;
            });
            add('›', st.page + 1, st.page >= pages);
        }
        $('gmPagi').innerHTML = pb.join('');
        gmUpdateBulkBar();
        syncUrl();
    }

    function sk(key, label) {
        var on = st.sort === key;
        return '<span class="sk' + (on ? ' on' : '') + '" data-s="' + key + '" title="Klik untuk mengurutkan">' + label + (on ? (st.dir === 'desc' ? ' ▼' : ' ▲') : '') + '</span>';
    }

    function renderMain(view, from) {
        // Kolom Status hanya perlu kalau isinya campuran (Semua / Kandidat musiman)
        var showStatus = st.status === 'all' || st.status === 'kandidat_musim';
        $('gmHead').innerHTML = '<tr>' +
            '<th class="c" style="width:30px"><input type="checkbox" id="gmChkAll" title="Centang semua barang di halaman ini"></th>' +
            '<th style="width:38px">#</th>' +
            '<th>' + sk('nama', 'Barang') + '</th>' +
            (showStatus ? '<th style="width:100px">Status</th>' : '') +
            '<th class="r" style="width:102px">' + sk('total_omzet', 'Omzet (Rp)') + '<br>' + sk('total_transaksi', 'trx') + ' · ' + sk('total_qty', 'qty') + '</th>' +
            '<th class="r" style="width:90px">' + sk('total_laba', 'Laba (Rp)') + '<br>margin</th>' +
            '<th class="r" style="width:90px">' + sk('stok_sekarang', 'Stok') + '<br>' + sk('nilai_stok', 'nilai (Rp)') + '</th>' +
            '<th class="r" style="width:80px">' + sk('days_of_stock', 'Stok cukup') + '<br>untuk</th>' +
            '<th class="r" style="width:94px">' + sk('days_since_trx', 'Terakhir') + '<br>laku</th>' +
            '<th style="width:140px">' + sk('urgency', 'Saran') + '<br>dan aksi</th></tr>';
        var cols = showStatus ? 10 : 9;
        if (!view.length) {
            $('gmBody').innerHTML = '<tr><td colspan="' + cols + '" class="empty">Tidak ada barang yang cocok dengan saringan ini.</td></tr>';
            return;
        }
        $('gmBody').innerHTML = view.map(function (g, i) {
            var s = ST[g.status] || ST.slow;
            var meta = [g.kat, g.kode, g.sat, g.merk].filter(function (x) { return x && x !== '-'; }).map(esc).join(' · ');
            var musim = '';
            @if($adaKolomBaru)
            if (g.mus) musim = ' <span class="tag m" data-act="musim" data-id="' + g.id + '" title="' + esc((g.mus_info || '') + ' · diatur lewat ' + (g.mus_src === 'barang' ? 'barang ini' : 'jenis ' + (g.tipe || ''))) + '">musim ' + esc(g.mus) + '</span>';
            else if (g.kand) musim = ' <span class="tag k" data-act="musim" data-id="' + g.id + '" title="' + g.kand.persen + '% penjualan 12 bulan terakhir terjadi di ' + esc(g.kand.label) + '. Klik untuk tandai musiman.">pola musim? ' + g.kand.persen + '% di ' + esc(g.kand.label) + '</span>';
            else musim = ' <span class="set-musim" data-act="musim" data-id="' + g.id + '">· atur musim</span>';
            @endif
            var info = '';
            if (g.status === 'obral') {
                info = '<div class="sub">Obral sejak ' + tgl(g.ob_mulai) + ' (' + (g.ob_hari || 0) + ' hari) oleh ' + esc(g.ob_oleh || '-') +
                    ' · terjual ' + nf.format(g.ob_jual || 0) + ' dari ' + nf.format(g.ob_awal || 0) +
                    (g.ob_alasan ? ' · <em>' + esc(String(g.ob_alasan).slice(0, 60)) + '</em>' : '') + '</div>';
            } else if (g.status === 'musiman') {
                info = '<div class="sub">' + esc(g.mus) + ': ' + esc(g.mus_info) + '</div>';
            } else if (g.status === 'baru') {
                info = '<div class="sub">Loading ' + g.dsl + ' hari lalu</div>';
            }
            var trx = g.status === 'at_risk'
                ? '<span style="color:#3C3489" title="Transaksi 12 bulan terakhir, di luar periode">' + nf.format(g.trx_lt || 0) + ' trx / 12 bln</span>'
                : nf.format(g.trx || 0) + ' trx · ' + (g.qpd || 0).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + '/hr';
            var mg = g.mg || 0, mgc = mg >= 20 ? 'ok' : mg >= 10 ? '' : mg > 0 ? 'wr' : 'na';
            var stok = g.stok || 0;
            var stokTxt = stok <= 0 ? '<span class="na">Habis</span>'
                : '<span class="' + (stok <= 5 ? 'bad' : stok <= 20 ? 'wr' : 'ok') + '" style="font-weight:600">' + nf.format(stok) + '</span>';
            var cek = g.dicek != null
                ? '<div><span class="cek' + (g.dicek > {{ \App\Models\GoodMovementRepository::CHECKED_FRESH_DAYS }} ? ' lama' : '') + '" title="Stok fisik terakhir dihitung (Sidak / Stock Opname) ' + tgl(g.dicek_at) + '">dicek ' + (g.dicek === 0 ? 'hari ini' : pendek(g.dicek)) + '</span></div>'
                : '';
            var dos = stok <= 0 ? '<span class="na">—</span>'
                : g.dos >= 9999 ? '<span class="na" title="Tidak ada penjualan di periode ini">tidak laku</span>'
                : '<span class="' + (g.dos <= 7 ? 'bad' : g.dos <= 30 ? 'wr' : 'ok') + '" title="' + g.dos + ' hari">' + lama(g.dos) + '</span>';
            var dst = g.dst >= 999 ? '<span class="na">belum pernah</span>'
                : '<span class="' + (g.dst >= 90 ? 'bad' : g.dst >= 30 ? 'wr' : 'ok') + '" title="' + g.dst + ' hari lalu">' + lama(g.dst) + ' lalu</span>';
            var rc = REC[g.rec] || ['#F1EFE8', '#444441'];
            var recSub = '';
            if (g.rec === 'season_prep') {
                recSub = '<div class="sub">Musim lalu terjual ' + nf.format(g.mus_lalu || 0) + ' ' + esc(g.sat) +
                    ((g.mus_lalu || 0) > stok ? ' — kurang ±' + nf.format((g.mus_lalu || 0) - Math.max(0, stok)) : '') + '</div>';
            }
            var aksi = [];
            if (g.co) aksi.push('<button class="gb sm" type="button" data-act="obral" data-id="' + g.id + '">Obral</button>');
            if (g.cd) aksi.push('<button class="gb sm" type="button" data-act="disc" data-id="' + g.id + '" title="Discontinue">Disc.</button>');
            if (g.io) aksi.push('<form method="POST" action="' + _baseUrl + '/' + g.id + '/batal-obral" onsubmit="return confirm(\'Batalkan obral barang ini? Harga jual kembali normal dan barang bisa disarankan order lagi.\')">' +
                '<input type="hidden" name="_token" value="' + _csrf + '"><button class="gb sm" type="submit">Batal Obral</button></form>');
            if (g.status === 'at_risk') aksi.push('<span class="sub" style="color:#3C3489" title="Riwayat 12 bulan: ' + (g.trx_lt || 0) + ' transaksi. Jangan discontinue sebelum dicek manual.">Jangan dihapus</span>');
            var bisaCentang = g.co || g.cd || g.cc || g.io;
            return '<tr>' +
                '<td class="c">' + (bisaCentang ? '<input type="checkbox" class="gm-pick" data-id="' + g.id + '"' + (picked[g.id] ? ' checked' : '') + '>' : '') + '</td>' +
                '<td class="muted">' + (from + i + 1) + '</td>' +
                '<td><div class="nm">' + esc(g.nama) + '</div><div class="sub">' + meta + musim + '</div>' + info + '</td>' +
                (showStatus ? '<td><span class="bd" style="background:' + s.bg + ';color:' + s.fg + '">' + esc(g.sl || s.l) + '</span></td>' : '') +
                '<td class="num">' + (g.om ? rp(g.om) : '<span class="na">0</span>') + '<div class="sub">' + trx + '</div></td>' +
                '<td class="num"><span class="' + ((g.lb || 0) > 0 ? 'ok' : (g.lb || 0) < 0 ? 'bad' : 'na') + '">' + rp(g.lb) + '</span>' +
                    '<div class="sub ' + mgc + '">' + (mg > 0 ? mg.toLocaleString('id-ID') + '%' : '–') + '</div></td>' +
                '<td class="num">' + stokTxt + (stok > 0 ? '<div class="sub">' + rp(g.nv) + '</div>' : '') + cek + '</td>' +
                '<td class="num">' + dos + '</td>' +
                '<td class="num">' + dst + '</td>' +
                '<td><span class="bd" style="background:' + rc[0] + ';color:' + rc[1] + '">' + esc(REC_LABEL[g.rec] || g.rec) + '</span>' + recSub +
                    (aksi.length ? '<div class="aksi">' + aksi.join('') + '</div>' : '') + '</td></tr>';
        }).join('');
    }

    function renderDisc(view, from) {
        $('gmHead').innerHTML = '<tr><th style="width:38px">#</th><th>Barang</th><th class="r" style="width:80px">Stok</th>' +
            '<th>Alasan discontinue</th><th style="width:120px">Tanggal</th><th style="width:130px">Oleh</th><th class="r" style="width:110px">Aksi</th></tr>';
        if (!view.length) {
            $('gmBody').innerHTML = '<tr><td colspan="7" class="empty">Tidak ada barang discontinued.</td></tr>';
            return;
        }
        $('gmBody').innerHTML = view.map(function (g, i) {
            var meta = [g.kat, g.kode, g.merk].filter(function (x) { return x && x !== '-'; }).map(esc).join(' · ');
            return '<tr><td class="muted">' + (from + i + 1) + '</td>' +
                '<td><div class="nm">' + esc(g.nama) + '</div><div class="sub">' + meta + '</div></td>' +
                '<td class="num">' + (g.stok > 0 ? '<span class="wr" style="font-weight:600">' + nf.format(g.stok) + '</span>' : '<span class="na">Kosong</span>') + '</td>' +
                '<td>' + esc(g.alasan || '—') + '</td><td>' + tgl(g.tgl) + '</td><td>' + esc(g.oleh) + '</td>' +
                '<td class="r"><form method="POST" action="' + _baseUrl + '/' + g.id + '/restore" onsubmit="return confirm(\'Aktifkan kembali barang ini?\')" style="margin:0">' +
                '<input type="hidden" name="_token" value="' + _csrf + '"><button class="gb sm" type="submit">Aktifkan</button></form></td></tr>';
        }).join('');
    }

    /* ── Simpan saringan di alamat halaman (tetap sama saat dibuka ulang / kembali dari aksi) ── */
    function syncUrl() {
        var p = new URLSearchParams(location.search);
        var set = function (k, v, bawaan) { if (v && v !== bawaan) p.set(k, v); else p.delete(k); };
        set('status', st.status, 'all');
        set('saran', st.saran, '');
        set('kategori', st.kat, '');
        set('q', st.q, '');
        set('sort_by', st.sort, 'total_omzet');
        set('sort_dir', st.dir, 'desc');
        var qs = p.toString();
        history.replaceState(null, '', location.pathname + (qs ? '?' + qs : ''));
    }

    /* ── Centang & bar aksi ───────────────────────────────────── */
    var FLAG = { 'data-can-obral': 'co', 'data-can-disc': 'cd', 'data-can-cek': 'cc', 'data-is-obral': 'io' };
    // Urut seperti tabel (dipakai juga untuk urutan daftar Tugas Cek Stok)
    function gmCheckedIds(attr) {
        var f = FLAG[attr];
        return _rows.filter(function (g) { return picked[g.id] && g[f]; }).map(function (g) { return g.id; });
    }
    function gmUpdateBulkBar() {
        var bar = $('gmBulkBar');
        if (!bar) return;
        var dipilih = _rows.filter(function (g) { return picked[g.id]; });
        var nilai = dipilih.reduce(function (t, g) { return t + (g.nv || 0); }, 0);
        bar.classList.toggle('on', dipilih.length > 0);
        $('gmBulkCount').innerHTML = '<b>' + dipilih.length + '</b> barang dipilih · nilai stok <b>' + jt(nilai) + '</b>';
        var counts = {
            Obral: gmCheckedIds('data-can-obral').length, Disc: gmCheckedIds('data-can-disc').length,
            Cek: gmCheckedIds('data-can-cek').length, Label: gmCheckedIds('data-is-obral').length, Wa: gmCheckedIds('data-is-obral').length
        };
        Object.keys(counts).forEach(function (k) {
            var n = $('gmBulk' + k + 'N'), b = $('gmBulk' + k + 'Btn');
            if (n) n.textContent = counts[k];
            if (b) b.style.display = counts[k] === 0 ? 'none' : '';
        });
        var semua = document.querySelectorAll('.gm-pick'), on = document.querySelectorAll('.gm-pick:checked');
        if ($('gmChkAll')) $('gmChkAll').checked = semua.length > 0 && semua.length === on.length;
    }
    window.gmUpdateBulkBar = gmUpdateBulkBar;
    window.gmClearAll = function () {
        picked = {};
        document.querySelectorAll('.gm-pick').forEach(function (c) { c.checked = false; });
        gmUpdateBulkBar();
    };
    function byId(id) {
        id = +id;
        for (var i = 0; i < goods.length; i++) if (goods[i].id === id) return goods[i];
        return null;
    }

    /* ── Kejadian ─────────────────────────────────────────────── */
    var ubahSaringan = function () { st.page = 1; picked = {}; render(); };
    document.querySelector('.gm2').addEventListener('click', function (e) {
        var t = e.target.closest('[data-k],[data-s],[data-p],[data-act]');
        if (!t) return;
        if (t.dataset.k !== undefined) {
            st.status = t.dataset.k;
            if (SORT_PER_STATUS[st.status]) { st.sort = SORT_PER_STATUS[st.status]; st.dir = 'desc'; }
            ubahSaringan();
        } else if (t.dataset.s !== undefined) {
            if (st.sort === t.dataset.s) st.dir = st.dir === 'desc' ? 'asc' : 'desc';
            else { st.sort = t.dataset.s; st.dir = t.dataset.s === 'nama' ? 'asc' : 'desc'; }
            st.page = 1; render();
        } else if (t.dataset.p !== undefined) {
            st.page = +t.dataset.p; render();
            var tbl = document.querySelector('.gm2 .tbar');
            if (tbl) tbl.scrollIntoView({ block: 'start' });
        } else if (t.dataset.act) {
            var g = byId(t.dataset.id);
            if (!g) return;
            if (t.dataset.act === 'obral') gmOpenObral([g.id], g.nama);
            else if (t.dataset.act === 'disc') gmOpenModal(g.id, g.nama, g.kat);
            else if (t.dataset.act === 'musim') gmOpenSeason(g.id, g.nama, g.tipe || '', g.jenis ? 1 : 0, g.sg === undefined ? null : g.sg, g.sid || null);
        }
    });
    document.querySelector('.gm2').addEventListener('change', function (e) {
        if (e.target.classList.contains('gm-pick')) {
            if (e.target.checked) picked[e.target.dataset.id] = 1; else delete picked[e.target.dataset.id];
            gmUpdateBulkBar();
        } else if (e.target.id === 'gmChkAll') {
            document.querySelectorAll('.gm-pick').forEach(function (c) {
                c.checked = e.target.checked;
                if (c.checked) picked[c.dataset.id] = 1; else delete picked[c.dataset.id];
            });
            gmUpdateBulkBar();
        }
    });
    $('gmKat').addEventListener('change', function () { st.kat = this.value; ubahSaringan(); });
    $('gmSaran').addEventListener('change', function () { st.saran = this.value; ubahSaringan(); });
    var tunda;
    $('gmCari').addEventListener('input', function () {
        var v = this.value;
        clearTimeout(tunda);
        tunda = setTimeout(function () { st.q = v.trim(); ubahSaringan(); }, 200);
    });
    $('gmSort').addEventListener('change', function () { st.sort = this.value; st.dir = this.value === 'nama' ? 'asc' : 'desc'; st.page = 1; render(); });
    $('gmDir').addEventListener('click', function () { st.dir = st.dir === 'desc' ? 'asc' : 'desc'; st.page = 1; render(); });
    $('gmPerPage').addEventListener('change', function () { st.per = +this.value; st.page = 1; render(); });
    $('gmReset').addEventListener('click', function () {
        st.status = 'all'; st.saran = ''; st.kat = ''; st.q = ''; st.sort = 'total_omzet'; st.dir = 'desc';
        $('gmKat').value = ''; $('gmCari').value = '';
        ubahSaringan();
    });
    SORTS.forEach(function (s) { var o = document.createElement('option'); o.value = s[0]; o.textContent = s[1]; $('gmSort').appendChild(o); });
    $('gmKat').value = st.kat;
    if ($('gmKat').value !== st.kat) st.kat = '';
    $('gmCari').value = st.q;

    /* ── Modal discontinue ──────────────────────────────────────── */
    window.gmOpenModal = function (goodId, name, kat) {
        _goodId = goodId;
        document.getElementById('gmModalName').textContent = name;
        document.getElementById('gmModalMeta').textContent = 'Kategori: ' + kat;
        document.getElementById('gmDiscReason').value = '';
        document.getElementById('gmDiscErr').style.display = 'none';
        document.getElementById('gmDiscModal').classList.add('open');
        setTimeout(function () { document.getElementById('gmDiscReason').focus(); }, 120);
    };

    window.gmCloseModal = function () {
        document.getElementById('gmDiscModal').classList.remove('open');
        _goodId = null;
    };

    window.gmSubmitDisc = function () {
        var reason = document.getElementById('gmDiscReason').value.trim();
        if (reason.length < 5) {
            document.getElementById('gmDiscErr').style.display = 'block';
            document.getElementById('gmDiscReason').focus();
            return;
        }
        document.getElementById('gmDiscErr').style.display = 'none';
        var form = document.getElementById('gmDiscForm');
        form.action = _baseUrl + '/' + _goodId + '/discontinue';
        document.getElementById('gmDiscFormReason').value = reason;
        form.submit();
    };

    window.gmOpenBulkModal = function () {
        var checked = gmCheckedIds('data-can-disc');
        if (checked.length === 0) return;
        document.getElementById('gmBulkCountModal').textContent = checked.length + ' barang akan di-discontinue';
        document.getElementById('gmBulkReason').value = '';
        document.getElementById('gmBulkErr').style.display = 'none';
        document.getElementById('gmBulkModal').classList.add('open');
        setTimeout(function () { document.getElementById('gmBulkReason').focus(); }, 120);
    };

    window.gmCloseBulkModal = function () {
        document.getElementById('gmBulkModal').classList.remove('open');
    };

    window.gmSubmitBulk = function () {
        var reason = document.getElementById('gmBulkReason').value.trim();
        if (reason.length < 5) {
            document.getElementById('gmBulkErr').style.display = 'block';
            document.getElementById('gmBulkReason').focus();
            return;
        }
        var checked = gmCheckedIds('data-can-disc');
        if (checked.length === 0) { gmCloseBulkModal(); return; }

        document.getElementById('gmBulkErr').style.display = 'none';
        document.getElementById('gmBulkFormReason').value = reason;
        gmFillIds('gmBulkFormIds', checked);

        document.getElementById('gmBulkForm').submit();
    };

    function gmFillIds(wrapId, ids, name) {
        var idsWrap = document.getElementById(wrapId);
        idsWrap.innerHTML = '';
        ids.forEach(function (id) {
            var inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = name || 'ids[]';
            inp.value = id;
            idsWrap.appendChild(inp);
        });
    }

    /* ── Bantuan umum ───────────────────────────────────────────── */
    var _hargaObral = @json($hargaObralAda);
    var _lihatModal = @json($lihatModal);
    var _freshDays  = {{ \App\Models\GoodMovementRepository::CHECKED_FRESH_DAYS }};

    function gmEsc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }
    function gmNum(n) { return Number(n).toLocaleString('id-ID', { maximumFractionDigits: 2 }); }
    function gmRp(n)  { return 'Rp' + Math.round(Number(n)).toLocaleString('id-ID'); }

    /** Data barang + satuan + harga dari server (urut sesuai ids). */
    function gmFetchGoods(ids) {
        var qs = ids.map(function (id) { return 'ids[]=' + encodeURIComponent(id); }).join('&');
        return fetch(_baseUrl + '/obral/barang?' + qs, {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (res) {
            if (!res.ok) throw new Error('Gagal memuat data barang (' + res.status + ').');
            return res.json();
        }).then(function (data) { return data.goods || []; });
    }

    function gmCopyText(text, btn) {
        function done() {
            if (!btn) return;
            var old = btn.textContent;
            btn.textContent = '✅ Tersalin';
            setTimeout(function () { btn.textContent = old; }, 1800);
        }
        function fallback() {
            var ta = document.createElement('textarea');
            ta.value = text; ta.style.position = 'fixed'; ta.style.opacity = '0';
            document.body.appendChild(ta); ta.select();
            try { document.execCommand('copy'); done(); } catch (e) { alert('Gagal menyalin. Salin manual dari kotak teks.'); }
            document.body.removeChild(ta);
        }
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(done).catch(fallback);
        } else {
            fallback();
        }
    }

    /* ── Obral + harga obral ────────────────────────────────────── */
    var _obralIds = [];
    var _obGoods  = [];
    window.gmOpenObral = function (ids, name) {
        if (!document.getElementById('gmObralModal') || !ids.length) return;
        _obralIds = ids;
        document.getElementById('gmObralName').textContent = name;
        document.getElementById('gmObralReason').value = '';
        document.getElementById('gmObralErr').style.display = 'none';
        document.getElementById('gmObralModal').classList.add('open');
        if (!_hargaObral) {
            setTimeout(function () { document.getElementById('gmObralReason').focus(); }, 120);
            return;
        }
        _obGoods = [];
        document.getElementById('gmObErr').style.display = 'none';
        document.getElementById('gmObUnchecked').style.display = 'none';
        document.getElementById('gmObTbl').style.display = 'none';
        document.getElementById('gmObBody').innerHTML = '';
        var loading = document.getElementById('gmObLoading');
        loading.style.display = '';
        loading.textContent = 'Memuat harga…';
        gmFetchGoods(ids).then(function (goods) {
            _obGoods = goods;
            loading.style.display = 'none';
            gmObRender();
            setTimeout(function () { document.getElementById('gmObDisc').focus(); }, 60);
        }).catch(function (e) { loading.textContent = e.message; });
    };
    window.gmOpenBulkObral = function () {
        var ids = gmCheckedIds('data-can-obral');
        gmOpenObral(ids, ids.length + ' barang akan diobral');
    };
    window.gmCloseObral = function () {
        var m = document.getElementById('gmObralModal');
        if (m) m.classList.remove('open');
    };

    function gmObRender() {
        var body = document.getElementById('gmObBody');
        var html = '';
        _obGoods.forEach(function (g) {
            g.units.forEach(function (u, k) {
                var normal = u.normal_price !== null ? u.normal_price : u.selling_price;
                html += '<tr class="' + (k === 0 ? 'gm-ob-first' : '') + '">';
                if (k === 0) {
                    html += '<td rowspan="' + g.units.length + '"><div class="gm-ob-name">' + gmEsc(g.nama) + '</div>' +
                        '<div class="gm-sub">sisa ' + gmNum(g.stok) + ' ' + gmEsc(g.satuan) + ' · ' + gmEsc(g.kategori) + '</div></td>';
                }
                html += '<td>' + gmEsc(String(u.unit).toUpperCase()) + (u.isi > 1 ? '<div class="gm-sub">isi ' + gmNum(u.isi) + '</div>' : '') + '</td>';
                if (_lihatModal) html += '<td class="tr">' + (u.buy_price > 0 ? gmRp(u.buy_price) : '<span style="color:var(--dead)">kosong</span>') + '</td>';
                html += '<td class="tr">' + gmRp(normal) + '</td>' +
                    '<td class="tr"><input type="number" class="gm-inp gm-ob-inp" min="0" step="any" inputmode="decimal"' +
                        ' data-gu="' + u.good_unit_id + '" data-good="' + g.good_id + '" data-normal="' + normal + '"' +
                        ' data-buy="' + (u.buy_price === null ? '' : u.buy_price) + '" data-isi="' + u.isi + '" data-unit="' + gmEsc(u.unit) + '"' +
                        ' placeholder="tidak diubah"></td>' +
                    '<td><div class="gm-ob-msg" id="gmObMsg' + u.good_unit_id + '"></div></td>';
                html += '</tr>';
            });
        });
        body.innerHTML = html || '<tr><td colspan="6" class="gm-sub">Barang tidak ditemukan.</td></tr>';
        document.getElementById('gmObTbl').style.display = '';
        body.querySelectorAll('.gm-ob-inp').forEach(function (inp) { inp.addEventListener('input', gmObCheck); });

        // Barang yang belum dicek fisik: sebaiknya dicek dulu supaya yang diobral memang ada di rak.
        var belum = _obGoods.filter(function (g) { return g.dicek_hari === null || g.dicek_hari > _freshDays; });
        var box = document.getElementById('gmObUnchecked');
        if (belum.length) {
            var names = belum.slice(0, 5).map(function (g) { return gmEsc(g.nama); }).join(', ') + (belum.length > 5 ? ', …' : '');
            box.innerHTML = '⚠️ <strong>' + belum.length + ' barang belum dicek fisik ' + _freshDays + ' hari terakhir</strong>: ' + names +
                '. Sebaiknya buat <strong>Tugas Cek Stok</strong> dulu supaya barang yang diobral memang ada di rak.';
            box.style.display = '';
        }
        gmObCheck();
    }

    /** Harga obral dari diskon %: dibulatkan ke bawah, tidak di bawah modal, tidak di atas harga normal. */
    function gmObSuggest(normal, buy, disc, step) {
        var target = normal * (1 - disc / 100);
        var val = Math.floor(target / step) * step;
        if (buy > 0 && val < buy) {
            val = Math.ceil(buy / step) * step;
            if (val > normal) val = buy;
        }
        if (val >= normal || val <= 0) return '';
        return Math.round(val * 100) / 100;
    }

    window.gmObApply = function () {
        var disc = parseFloat(document.getElementById('gmObDisc').value);
        var step = parseFloat(document.getElementById('gmObRound').value) || 1;
        if (!(disc > 0 && disc < 100)) { document.getElementById('gmObDisc').focus(); return; }
        document.querySelectorAll('#gmObBody .gm-ob-inp').forEach(function (inp) {
            var buy = inp.getAttribute('data-buy') === '' ? 0 : parseFloat(inp.getAttribute('data-buy'));
            inp.value = gmObSuggest(parseFloat(inp.getAttribute('data-normal')), buy, disc, step);
        });
        gmObCheck();
    };

    /** Periksa semua harga obral; kembalikan jumlah kesalahan. */
    function gmObCheck() {
        var inputs = Array.prototype.slice.call(document.querySelectorAll('#gmObBody .gm-ob-inp'));
        var errors = 0;
        var perGood = {};
        inputs.forEach(function (inp) {
            var raw = inp.value.trim();
            var normal = parseFloat(inp.getAttribute('data-normal'));
            var buy = inp.getAttribute('data-buy') === '' ? null : parseFloat(inp.getAttribute('data-buy'));
            var msg = document.getElementById('gmObMsg' + inp.getAttribute('data-gu'));
            var v = raw === '' ? null : parseFloat(raw);
            var cls = '', text = '';
            if (v === null || Math.abs(v - normal) < 0.005) {
                text = 'harga tetap'; cls = '';
            } else if (!(v > 0)) {
                text = 'harga harus lebih dari 0'; cls = 'bad';
            } else if (buy !== null && buy <= 0) {
                text = 'harga beli masih kosong'; cls = 'bad';
            } else if (buy !== null && v < buy - 0.005) {
                text = 'di bawah modal ' + gmRp(buy) + ' — paling rendah sama dengan modal'; cls = 'bad';
            } else if (v > normal) {
                text = 'lebih tinggi dari harga normal'; cls = 'bad';
            } else {
                text = 'hemat ' + gmRp(normal - v) + ' (-' + Math.round((normal - v) / normal * 100) + '%)' +
                       (buy !== null && Math.abs(v - buy) < 0.005 ? ' · = modal' : '');
                cls = 'ok';
            }
            if (cls === 'bad') errors++;
            inp.classList.toggle('bad', cls === 'bad');
            msg.className = 'gm-ob-msg ' + cls;
            msg.textContent = text;
            var gid = inp.getAttribute('data-good');
            (perGood[gid] = perGood[gid] || []).push({ inp: inp, msg: msg, isi: parseFloat(inp.getAttribute('data-isi')) || 1,
                price: v === null ? normal : v, changed: v !== null && Math.abs(v - normal) >= 0.005, bad: cls === 'bad' });
        });

        // Satuan besar per isinya lebih mahal dari satuan kecil yang diobral:
        // pembeli yang ambil satuan besar malah bayar lebih mahal.
        Object.keys(perGood).forEach(function (gid) {
            var list = perGood[gid].slice().sort(function (a, b) { return a.isi - b.isi; });
            if (list.length < 2 || !list.some(function (x) { return x.changed; })) return;
            var small = list[0], perSmall = small.price / small.isi;
            list.slice(1).forEach(function (big) {
                var per = big.price / big.isi;
                if (!big.bad && per > perSmall + 0.5) {
                    big.msg.className = 'gm-ob-msg warn';
                    big.msg.textContent = '⚠ per ' + small.inp.getAttribute('data-unit') + ' jadi ' + gmRp(per) +
                        ', lebih mahal dari ' + small.inp.getAttribute('data-unit') + ' (' + gmRp(perSmall) + '). Samakan diskonnya.';
                }
            });
        });

        var errBox = document.getElementById('gmObErr');
        if (errBox) {
            errBox.style.display = errors ? '' : 'none';
            errBox.textContent = errors ? errors + ' harga obral belum benar (lihat tanda merah). Perbaiki atau kosongkan dulu.' : '';
        }
        return errors;
    }

    window.gmSubmitObral = function () {
        var reason = document.getElementById('gmObralReason').value.trim();
        if (reason.length < 5) {
            document.getElementById('gmObralErr').style.display = 'block';
            document.getElementById('gmObralReason').focus();
            return;
        }
        var wrap = document.getElementById('gmObralFormPrices');
        if (wrap) {
            if (_hargaObral && !_obGoods.length) return; // harga belum termuat
            if (_hargaObral && gmObCheck() > 0) return;
            wrap.innerHTML = '';
            document.querySelectorAll('#gmObBody .gm-ob-inp').forEach(function (inp) {
                var raw = inp.value.trim();
                if (raw === '' || Math.abs(parseFloat(raw) - parseFloat(inp.getAttribute('data-normal'))) < 0.005) return;
                var h = document.createElement('input');
                h.type = 'hidden';
                h.name = 'prices[' + inp.getAttribute('data-gu') + ']';
                h.value = raw;
                wrap.appendChild(h);
            });
        }
        document.getElementById('gmObralOk').disabled = true;
        document.getElementById('gmObralFormReason').value = reason;
        gmFillIds('gmObralFormIds', _obralIds);
        document.getElementById('gmObralForm').submit();
    };

    /* ── Label obral ────────────────────────────────────────────── */
    window.gmOpenLabel = function () {
        var ids = gmCheckedIds('data-is-obral');
        if (!ids.length) return;
        document.getElementById('gmLabelName').textContent = ids.length + ' barang obral';
        document.getElementById('gmLabelModal').classList.add('open');
    };
    window.gmCloseLabel = function () {
        var m = document.getElementById('gmLabelModal');
        if (m) m.classList.remove('open');
    };
    window.gmSubmitLabel = function () {
        var ids = gmCheckedIds('data-is-obral');
        var qty = Math.max(1, Math.min(10, parseInt(document.getElementById('gmLabelQty').value, 10) || 1));
        document.getElementById('gmLabelFormLayout').value = document.getElementById('gmLabelLayout').value;
        var wrap = document.getElementById('gmLabelFormIds');
        gmFillIds('gmLabelFormIds', ids);
        ids.forEach(function (id) {
            var h = document.createElement('input');
            h.type = 'hidden'; h.name = 'quantities[' + id + ']'; h.value = qty;
            wrap.appendChild(h);
        });
        document.getElementById('gmLabelForm').submit();
        gmCloseLabel();
    };

    /* ── Teks WA obral ──────────────────────────────────────────── */
    var _waGoods = [];
    var _waMode  = 'pelanggan';

    /** Satuan yang diobral (punya harga normal lebih tinggi); kalau tidak ada, harga satuan terkecil. */
    function gmWaPrices(g) {
        var diskon = g.units.filter(function (u) { return u.normal_price !== null && u.normal_price > u.selling_price; });
        return diskon.length ? diskon : g.units.slice(0, 1);
    }
    function gmWaLine(u) {
        var unit = '/' + String(u.unit).toLowerCase();
        if (u.normal_price !== null && u.normal_price > u.selling_price) {
            return '~' + gmRp(u.normal_price) + '~ → *' + gmRp(u.selling_price) + '* ' + unit +
                   ' (hemat ' + Math.round((u.normal_price - u.selling_price) / u.normal_price * 100) + '%)';
        }
        return '*' + gmRp(u.selling_price) + '* ' + unit;
    }
    function gmWaBuild() {
        var today = new Date().toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
        var lines = [];
        if (_waMode === 'pelanggan') {
            lines.push('🏷️ *OBRAL – SELAMA STOK ADA* 🏷️', '');
            _waGoods.forEach(function (g) {
                lines.push('• *' + g.nama + '*');
                gmWaPrices(g).forEach(function (u) { lines.push('   ' + gmWaLine(u)); });
            });
            lines.push('', 'Stok terbatas, siapa cepat dia dapat! 🛒');
        } else {
            lines.push('🏷️ *TUGAS OBRAL*', '📅 ' + today, '',
                'Pindahkan ke rak/keranjang obral dan pasang label obral (harga lama dicoret):', '');
            _waGoods.forEach(function (g, i) {
                lines.push((i + 1) + '. ' + g.nama + ' (' + g.kategori + ')');
                lines.push('   sisa ' + gmNum(g.stok) + ' ' + g.satuan + ' · ' + gmWaPrices(g).map(gmWaLine).join(' · '));
            });
            lines.push('', '_Total: ' + _waGoods.length + ' barang_');
        }
        return lines.join('\n');
    }
    window.gmWaTab = function (mode) {
        _waMode = mode;
        document.getElementById('gmWaTabCust').classList.toggle('on', mode === 'pelanggan');
        document.getElementById('gmWaTabStaff').classList.toggle('on', mode === 'pegawai');
        document.getElementById('gmWaHint').textContent = mode === 'pelanggan'
            ? 'Untuk status / grup WA pelanggan: harga normal dicoret dan harga obral, tanpa stok dan tanpa modal. Teks bisa diedit dulu.'
            : 'Untuk pegawai: daftar barang yang dipindah ke rak obral, sisa stok, dan harga baru. Teks bisa diedit dulu.';
        if (_waGoods.length) document.getElementById('gmWaText').value = gmWaBuild();
    };
    window.gmOpenWa = function () {
        var ids = gmCheckedIds('data-is-obral');
        if (!ids.length) return;
        _waGoods = [];
        document.getElementById('gmWaText').value = 'Memuat…';
        document.getElementById('gmWaModal').classList.add('open');
        gmWaTab(_waMode);
        gmFetchGoods(ids).then(function (goods) {
            _waGoods = goods;
            document.getElementById('gmWaText').value = gmWaBuild();
        }).catch(function (e) { document.getElementById('gmWaText').value = e.message; });
    };
    window.gmCloseWa = function () {
        var m = document.getElementById('gmWaModal');
        if (m) m.classList.remove('open');
    };
    window.gmCopyWa = function () {
        if (!_waGoods.length) return;
        gmCopyText(document.getElementById('gmWaText').value, document.getElementById('gmWaCopyBtn'));
    };

    /* ── Tugas cek stok ─────────────────────────────────────────── */
    var _maxCek = {{ \App\Http\Controllers\Admin\StockInspectionController::MAX_TARGETS }};
    window.gmOpenCek = function () {
        var ids = gmCheckedIds('data-can-cek');
        if (!ids.length) return;
        document.getElementById('gmCekName').textContent = ids.length + ' barang akan dicek stok fisiknya';
        var err = document.getElementById('gmCekErr');
        err.style.display = ids.length > _maxCek ? 'block' : 'none';
        err.textContent = 'Paling banyak ' + _maxCek + ' barang dalam satu tugas. Kurangi centangnya.';
        document.getElementById('gmCekModal').classList.add('open');
    };
    window.gmCloseCek = function () {
        var m = document.getElementById('gmCekModal');
        if (m) m.classList.remove('open');
    };
    window.gmSubmitCek = function () {
        var ids = gmCheckedIds('data-can-cek');
        if (!ids.length || ids.length > _maxCek) return;
        document.getElementById('gmCekFormNote').value = document.getElementById('gmCekNote').value.trim();
        document.getElementById('gmCekFormBy').value = document.getElementById('gmCekBy').value.trim();
        gmFillIds('gmCekFormIds', ids, 'good_ids[]');
        document.getElementById('gmCekForm').submit();
    };

    /* ── Musim ──────────────────────────────────────────────────── */
    window.gmOpenSeason = function (goodId, name, tipe, punyaJenis, musimBarang, musimJenis) {
        var m = document.getElementById('gmSeasonModal');
        if (!m) return;
        document.getElementById('gmSeasonForm').action = _baseUrl + '/' + goodId + '/musim';
        document.getElementById('gmSeasonName').textContent = name;
        document.getElementById('gmSeasonMeta').textContent = punyaJenis ? ('Jenis: ' + tipe) : 'Barang ini belum punya jenis';
        var tipeEl = document.getElementById('gmSeasonTipe');
        if (tipeEl) {
            tipeEl.textContent = tipe || '-';
            document.getElementById('gmSeasonScopeJenisWrap').style.display = punyaJenis ? '' : 'none';
            // Bawaan: per jenis, kecuali barang ini sudah punya pengaturan sendiri.
            var perBarang = !punyaJenis || (musimBarang !== null && musimBarang !== '');
            document.getElementById(perBarang ? 'gmSeasonScopeBarang' : 'gmSeasonScopeJenis').checked = true;
            gmSeasonScopeChanged();
            document.getElementById('gmSeasonSelect').value = perBarang
                ? (musimBarang !== null ? String(musimBarang) : '')
                : (musimJenis ? String(musimJenis) : '');
        }
        m.classList.add('open');
    };
    window.gmSeasonScopeChanged = function () {
        var opt = document.getElementById('gmSeasonOptIkut');
        var jenis = document.getElementById('gmSeasonScopeJenis');
        if (!opt || !jenis) return;
        // "Ikut jenisnya" hanya masuk akal untuk pengaturan per barang.
        opt.textContent = jenis.checked ? '— Pilih musim —' : '— Ikut jenisnya —';
    };
    window.gmCloseSeason = function () {
        var m = document.getElementById('gmSeasonModal');
        if (m) m.classList.remove('open');
    };

    /* ── Event listeners ────────────────────────────────────────── */
    document.getElementById('gmDiscModal').addEventListener('click', function (e) {
        if (e.target === this) gmCloseModal();
    });
    ['gmBulkModal', 'gmObralModal', 'gmSeasonModal', 'gmLabelModal', 'gmWaModal', 'gmCekModal'].forEach(function (id) {
        var el = document.getElementById(id);
        if (el) el.addEventListener('click', function (e) {
            if (e.target === this) this.classList.remove('open');
        });
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { gmCloseModal(); gmCloseBulkModal(); gmCloseObral(); gmCloseSeason(); gmCloseLabel(); gmCloseWa(); gmCloseCek(); }
    });

    /* ── Mulai ──────────────────────────────────────────────────── */
    // Catatan bawaan Tugas Cek Stok mengikuti kotak status yang sedang dipilih.
    var _bukaCek = window.gmOpenCek;
    if (_bukaCek) window.gmOpenCek = function () {
        var note = $('gmCekNote');
        if (note) note.value = st.status === 'dead' ? 'Cek fisik dead stock' : (st.status === 'at_risk' ? 'Cek fisik barang Perlu Dicek' : 'Cek fisik barang');
        _bukaCek();
    };
    render();
}());
</script>
@endsection
