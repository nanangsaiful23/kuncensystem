<style>
  @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap');

  :root {
    --brand:        #e74c3c;
    --brand-dark:   #c0392b;
    --brand-light:  #fdf2f2;
    --brand-mid:    #fce4e4;

    --profit-bg:     #f0faf4;
    --profit-border: #86d8a8;
    --profit-text:   #1a6e3c;
    --loss-bg:       #fff5f5;
    --loss-border:   #f5a3a3;
    --loss-text:     #b91c1c;

    --sales-bg:     #eff6ff;
    --sales-border: #93c5fd;
    --sales-text:   #1e40af;

    --bg-base:       #f5f6fa;
    --bg-card:       #ffffff;
    --text-primary:  #1a1d23;
    --text-secondary:#5a5f73;
    --text-muted:    #9499ad;
    --border:        #e2e6f0;

    --shadow-sm: 0 1px 3px rgba(0,0,0,.07);
    --shadow-md: 0 6px 20px rgba(0,0,0,.1);
    --radius:    12px;
    --radius-sm: 8px;

    --fs-xs:   0.72rem;
    --fs-sm:   0.875rem;
    --fs-base: 0.9375rem;
    --fs-md:   1rem;
    --fs-lg:   1.125rem;
    --fs-xl:   1.35rem;
  }

  * { box-sizing: border-box; }

  .ledger-wrapper {
    font-family: 'Inter', sans-serif;
    background: var(--bg-base);
    min-height: calc(100vh - 50px);
    padding: 20px;
    color: var(--text-primary);
  }

  /* ── Page title ── */
  .ledger-page-title {
    font-size: 1.3rem;
    font-weight: 700;
    color: var(--text-primary);
    margin-bottom: 18px;
  }
  .ledger-page-title span { color: var(--brand); }

  /* ── Generic card shell ── */
  .l-card {
    background: var(--bg-card);
    border-radius: var(--radius);
    border: 1px solid var(--border);
    border-top: 3px solid var(--brand);
    box-shadow: var(--shadow-sm);
    overflow: hidden;
    margin-bottom: 18px;
  }
  .l-card__head {
    padding: 13px 20px;
    border-bottom: 1px solid var(--border);
    background: #fafbfd;
    display: flex;
    align-items: center;
    gap: 10px;
  }
  .l-card__icon {
    width: 30px; height: 30px;
    border-radius: 7px;
    background: var(--brand-light);
    display: flex; align-items: center; justify-content: center;
    color: var(--brand); flex-shrink: 0;
  }
  .l-card__title {
    font-size: var(--fs-base);
    font-weight: 700;
    color: var(--text-primary);
  }

  /* ── Filter bar ── */
  .filter-bar {
    padding: 14px 20px 16px;
    display: flex;
    flex-wrap: wrap;
    gap: 14px 20px;
    align-items: flex-end;
    border-bottom: 1px solid var(--border);
  }
  .filter-group { display: flex; flex-direction: column; gap: 5px; }
  .filter-label {
    font-size: var(--fs-xs);
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .07em;
    color: var(--text-secondary);
  }
  .filter-input {
    font-family: 'Inter', sans-serif;
    font-size: var(--fs-sm);
    padding: 8px 12px;
    border: 1.5px solid var(--border);
    border-radius: var(--radius-sm);
    background: var(--bg-base);
    color: var(--text-primary);
    min-width: 150px;
    transition: border-color .18s, box-shadow .18s;
  }
  .filter-input:focus {
    outline: none;
    border-color: var(--brand);
    box-shadow: 0 0 0 3px rgba(231,76,60,.12);
  }

  /* ── Chart ── */
  .chart-body { padding: 16px 20px 20px; }
  #bar-chart { height: 380px; width: 100%; }

  /* ════════════════════════════════
     SUMMARY STRIP — 3 KPI boxes
  ════════════════════════════════ */
  .summary-strip {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0;
    border-bottom: 1px solid var(--border);
  }
  .kpi-box {
    padding: 16px 20px;
    border-right: 1px solid var(--border);
  }
  .kpi-box:last-child { border-right: none; }
  .kpi-box__label {
    font-size: var(--fs-xs);
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .07em;
    color: var(--text-muted);
    margin-bottom: 5px;
    display: flex;
    align-items: center;
    gap: 5px;
  }
  .kpi-box__dot {
    width: 7px; height: 7px;
    border-radius: 50%;
    display: inline-block;
    flex-shrink: 0;
  }
  .kpi-box__val {
    font-family: 'IBM Plex Mono', monospace;
    font-size: var(--fs-xl);
    font-weight: 700;
    letter-spacing: -.02em;
    line-height: 1.15;
  }
  .kpi-box__sub {
    font-size: var(--fs-xs);
    color: var(--text-muted);
    margin-top: 2px;
  }
  /* profit KPI colours */
  .kpi-box.profit .kpi-box__dot { background: var(--profit-text); }
  .kpi-box.profit .kpi-box__val { color: var(--profit-text); }
  .kpi-box.profit.neg .kpi-box__dot { background: var(--loss-text); }
  .kpi-box.profit.neg .kpi-box__val { color: var(--loss-text); }
  .kpi-box.sales  .kpi-box__dot { background: var(--sales-text); }
  .kpi-box.sales  .kpi-box__val { color: var(--sales-text); }
  .kpi-box.expense .kpi-box__dot { background: var(--brand); }
  .kpi-box.expense .kpi-box__val { color: var(--brand-dark); }

  /* ════════════════════════════════
     ACCORDION
  ════════════════════════════════ */
  .acc-list { padding: 12px 16px 16px; }

  .acc-item {
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    margin-bottom: 8px;
    overflow: hidden;
    transition: box-shadow .2s;
  }
  .acc-item:last-child { margin-bottom: 0; }
  .acc-item.is-open { box-shadow: var(--shadow-md); border-color: var(--brand-mid); }
  .acc-item.is-profit { border-left: 4px solid var(--profit-border); }
  .acc-item.is-loss   { border-left: 4px solid var(--loss-border); }

  /* Accordion trigger row */
  .acc-trigger {
    display: grid;
    grid-template-columns: 1fr auto auto auto 28px;
    align-items: center;
    gap: 8px 16px;
    padding: 14px 16px;
    cursor: pointer;
    background: #fff;
    transition: background .15s;
    user-select: none;
  }
  .acc-item.is-open .acc-trigger { background: #fafbfd; }
  .acc-trigger:hover { background: #fafbfd; }

  .acc-period {
    font-weight: 700;
    font-size: var(--fs-base);
    color: var(--text-primary);
    white-space: nowrap;
  }

  .acc-kpi {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 1px;
  }
  .acc-kpi__label {
    font-size: var(--fs-xs);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: var(--text-muted);
  }
  .acc-kpi__val {
    font-family: 'IBM Plex Mono', monospace;
    font-size: var(--fs-md);
    font-weight: 700;
    white-space: nowrap;
  }
  .acc-kpi__val.sales   { color: var(--sales-text); }
  .acc-kpi__val.expense { color: var(--brand-dark); }
  .acc-kpi__val.profit  { color: var(--profit-text); }
  .acc-kpi__val.loss    { color: var(--loss-text); }

  /* Profit badge pill */
  .profit-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 12px;
    border-radius: 20px;
    font-family: 'IBM Plex Mono', monospace;
    font-size: var(--fs-base);
    font-weight: 700;
    white-space: nowrap;
  }
  .profit-pill.pos { background: var(--profit-bg); color: var(--profit-text); border: 1.5px solid var(--profit-border); }
  .profit-pill.neg { background: var(--loss-bg);   color: var(--loss-text);   border: 1.5px solid var(--loss-border); }

  /* Chevron */
  .acc-chevron {
    width: 20px; height: 20px;
    display: flex; align-items: center; justify-content: center;
    color: var(--text-muted);
    transition: transform .22s ease, color .15s;
    flex-shrink: 0;
  }
  .acc-item.is-open .acc-chevron { transform: rotate(180deg); color: var(--brand); }

  /* Accordion body */
  .acc-body {
    display: none;
    border-top: 1px solid var(--border);
    background: #fafbfd;
    padding: 16px;
  }
  .acc-item.is-open .acc-body { display: block; }

  .acc-body__title {
    font-size: var(--fs-xs);
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: var(--brand-dark);
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 6px;
  }
  .acc-body__title::after {
    content: '';
    flex: 1;
    height: 1px;
    background: var(--brand-mid);
  }

  /* Expense rows inside body */
  .exp-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 11px 14px;
    border-radius: 7px;
    background: #fff;
    border: 1px solid var(--border);
    margin-bottom: 5px;
    transition: background .12s;
  }
  .exp-row:last-of-type { margin-bottom: 0; }
  .exp-row:hover { background: var(--brand-light); border-color: var(--brand-mid); }

  .exp-row__num {
    width: 26px; height: 26px;
    border-radius: 50%;
    background: var(--brand-light);
    color: var(--brand);
    font-size: .8rem;
    font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    border: 1.5px solid var(--brand-mid);
  }
  .exp-row__name {
    flex: 1;
    font-size: 1rem;
    font-weight: 500;
    color: var(--text-primary);
    min-width: 0;
  }
  .exp-row__val {
    font-family: 'IBM Plex Mono', monospace;
    font-size: 1.05rem;
    font-weight: 600;
    color: var(--brand-dark);
    white-space: nowrap;
  }

  /* Total bar at bottom of expense list */
  .exp-total {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 12px;
    border-radius: 6px;
    background: var(--brand-mid);
    border: 1.5px solid var(--brand);
    margin-top: 10px;
  }
  .exp-total__label {
    font-size: var(--fs-xs);
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: var(--brand-dark);
  }
  .exp-total__val {
    font-family: 'IBM Plex Mono', monospace;
    font-size: var(--fs-lg);
    font-weight: 700;
    color: var(--brand-dark);
  }

  /* ── Expense row drill-down ── */
  .exp-row { cursor: pointer; }
  .exp-row.is-active { background: var(--brand-light); border-color: var(--brand); }
  .exp-row__chevron {
    color: var(--text-muted); font-size: .8rem; margin-left: 4px;
    transition: transform .18s;
  }
  .exp-row.is-active .exp-row__chevron { transform: rotate(180deg); color: var(--brand); }

  .exp-detail {
    margin: 4px 0 5px 14px;
    padding: 10px 14px 6px;
    border-left: 3px solid var(--brand-mid);
    background: #fcfcfd;
    border-radius: 0 7px 7px 0;
  }

  /* Dua kolom: kiri positif, kanan minus/koreksi */
  .exp-detail-columns {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0 20px;
  }
  .exp-detail-col-head {
    display: flex; align-items: center; justify-content: space-between;
    font-size: .8rem; font-weight: 800; text-transform: uppercase;
    letter-spacing: .05em; padding: 2px 4px 8px; margin-bottom: 4px;
    border-bottom: 2px solid var(--border);
  }
  .exp-detail-col-head .cnt { font-weight: 600; text-transform: none; letter-spacing: 0; opacity: .7; }
  .exp-detail-col-head.positif { color: var(--profit-text); border-bottom-color: var(--profit-border); }
  .exp-detail-col-head.minus   { color: var(--loss-text);   border-bottom-color: var(--loss-border); }
  .exp-detail-col + .exp-detail-col { border-left: 1px dashed var(--border); padding-left: 20px; }

  .exp-detail-row {
    display: flex; align-items: center; gap: 10px;
    padding: 9px 4px; font-size: .9375rem;
    border-bottom: 1px dashed var(--border);
    line-height: 1.4;
    cursor: pointer;
    border-radius: 5px;
    transition: background .12s;
    text-decoration: none;
    color: inherit;
  }
  .exp-detail-row:hover, .exp-detail-row:focus { background: var(--brand-light); text-decoration: none; }
  .exp-detail-row:hover .exp-detail-name { color: var(--brand-dark); text-decoration: underline; }
  .exp-detail-row:last-child { border-bottom: none; }
  .exp-detail-date { color: var(--text-muted); width: 74px; flex-shrink: 0; font-weight: 500; }
  .exp-detail-name { flex: 1; color: var(--text-primary); min-width: 0; }
  .exp-detail-val { font-family: 'IBM Plex Mono', monospace; font-size: .95rem; font-weight: 600; color: var(--brand-dark); white-space: nowrap; }
  .exp-detail-val.neg { color: var(--loss-text); }
  .exp-detail-arrow { color: var(--text-muted); font-size: .85rem; flex-shrink: 0; }
  .exp-detail-row:hover .exp-detail-arrow { color: var(--brand); }
  .exp-detail-loading, .exp-detail-empty {
    font-size: .9375rem; color: var(--text-muted); font-style: italic; padding: 10px 4px;
  }
  .exp-detail-col-empty {
    font-size: .84rem; color: var(--text-muted); font-style: italic; padding: 10px 4px;
  }

  @media (max-width: 700px) {
    .exp-detail-columns { grid-template-columns: 1fr; }
    .exp-detail-col + .exp-detail-col {
      border-left: none; padding-left: 0;
      margin-top: 12px; border-top: 1px dashed var(--border); padding-top: 10px;
    }
  }

  /* Empty state */
  .exp-empty {
    text-align: center;
    padding: 16px;
    color: var(--text-muted);
    font-size: var(--fs-sm);
    font-style: italic;
  }

  /* ── Pagination ── */
  .pagination-wrapper {
    padding: 13px 20px;
    border-top: 1px solid var(--border);
    background: #fafbfd;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 8px;
  }
  .pagination-wrapper .pagination { margin: 0; }
  .pagination-wrapper .pagination > .active > a,
  .pagination-wrapper .pagination > .active > span {
    background-color: var(--brand) !important;
    border-color: var(--brand) !important;
    color: #fff !important;
  }
  .pagination-wrapper .pagination > li > a:hover {
    border-color: var(--brand); color: var(--brand);
  }
  .pagination-info {
    font-size: var(--fs-xs);
    color: var(--text-muted);
  }

  /* ── Responsive ── */
  @media (max-width: 768px) {
    .summary-strip { grid-template-columns: 1fr 1fr; }
    .kpi-box:nth-child(2) { border-right: none; }
    .kpi-box:nth-child(3) { border-top: 1px solid var(--border); grid-column: 1/-1; }
    .acc-trigger {
      grid-template-columns: 1fr auto 28px;
      grid-template-rows: auto auto;
    }
    .acc-period { grid-column: 1; grid-row: 1; }
    .profit-pill-wrap { grid-column: 2; grid-row: 1; }
    .acc-kpi-sales { grid-column: 1; grid-row: 2; align-items: flex-start; }
    .acc-kpi-expense { grid-column: 2; grid-row: 2; }
    .acc-chevron { grid-column: 3; grid-row: 1; }
    .filter-bar { flex-direction: column; }
    .filter-input { min-width: unset; width: 100%; }
  }
  @media (max-width: 480px) {
    .ledger-wrapper { padding: 12px 10px 40px; }
    .summary-strip { grid-template-columns: 1fr; }
    .kpi-box { border-right: none; border-bottom: 1px solid var(--border); }
    .kpi-box:last-child { border-bottom: none; }
    .kpi-box:nth-child(2) { border-right: none; }
    .kpi-box:nth-child(3) { border-top: none; grid-column: auto; }
    .acc-list { padding: 10px; }
    .kpi-box__val { font-size: var(--fs-lg); }
  }
</style>

@extends('layout.user', ['role' => 'admin', 'title' => 'Admin'])

@section('content')
<div class="content-wrapper ledger-wrapper">
  <section class="content">

    <h1 class="ledger-page-title">Grafik <span>Ledger Neraca</span></h1>

    {{-- ── CHART CARD ── --}}
    <div class="l-card">
      <div class="l-card__head">
        <div class="l-card__icon">
          <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2zm0 0V9a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v10m-6 0a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2m0 0V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2z"/></svg>
        </div>
        <span class="l-card__title">Perbandingan Penjualan & Pengeluaran per Bulan</span>
      </div>

      <div class="filter-bar">
        <div class="filter-group">
          <label class="filter-label">Tanggal Awal</label>
          <input type="text" class="filter-input" name="start_date" value="{{ $start_date }}" id="start_date" onchange="advanceSearch()" placeholder="yyyy-mm-dd">
        </div>
        <div class="filter-group">
          <label class="filter-label">Tanggal Akhir</label>
          <input type="text" class="filter-input" name="end_date" value="{{ $end_date }}" id="end_date" onchange="advanceSearch()" placeholder="yyyy-mm-dd">
        </div>
      </div>

      <div class="chart-body">
        <div id="bar-chart"></div>
      </div>
    </div>

    {{-- ── ACCORDION CARD ── --}}
    <div class="l-card">
      <div class="l-card__head">
        <div class="l-card__icon">
          <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
        </div>
        <span class="l-card__title">Rincian per Periode</span>
      </div>

      {{-- KPI summary strip --}}
      @php
        $totalSales   = $dates->sum(fn($d) => $d->dataplus->sum('debit'));
        $totalExpense = $dates->sum(fn($d) => $d->data->sum('debit'));
        $totalProfit  = $dates->sum('profit');
      @endphp
      <div class="summary-strip">
        <div class="kpi-box profit {{ $totalProfit < 0 ? 'neg' : '' }}">
          <div class="kpi-box__label">
            <span class="kpi-box__dot"></span> Total Untung / Rugi
          </div>
          <div class="kpi-box__val">{{ showRupiah($totalProfit) }}</div>
          <div class="kpi-box__sub">Akumulasi periode ini</div>
        </div>
        <div class="kpi-box sales">
          <div class="kpi-box__label">
            <span class="kpi-box__dot"></span> Total Penjualan
          </div>
          <div class="kpi-box__val">{{ showRupiah($totalSales) }}</div>
          <div class="kpi-box__sub">Seluruh periode</div>
        </div>
        <div class="kpi-box expense">
          <div class="kpi-box__label">
            <span class="kpi-box__dot"></span> Total Pengeluaran
          </div>
          <div class="kpi-box__val">{{ showRupiah($totalExpense) }}</div>
          <div class="kpi-box__sub">Seluruh periode</div>
        </div>
      </div>

      {{-- Accordion list --}}
      <div class="acc-list">
        @foreach($dates as $idx => $date)
        @php
          $profit  = $date->profit;
          $isLoss  = $profit < 0;
          $sales   = $date->dataplus->sum('debit');
          $expense = $date->data->sum('debit');
        @endphp
        <div class="acc-item {{ $isLoss ? 'is-loss' : 'is-profit' }}" id="acc-{{ $loop->index }}">
          <div class="acc-trigger" onclick="toggleAcc({{ $loop->index }})">

            {{-- Period --}}
            <span class="acc-period">{{ date('F Y', strtotime($date->date)) }}</span>

            {{-- Penjualan --}}
            <div class="acc-kpi acc-kpi-sales">
              <span class="acc-kpi__label">Penjualan</span>
              <span class="acc-kpi__val sales">{{ showRupiah($sales) }}</span>
            </div>

            {{-- Pengeluaran --}}
            <div class="acc-kpi acc-kpi-expense">
              <span class="acc-kpi__label">Pengeluaran</span>
              <span class="acc-kpi__val expense">{{ showRupiah($expense) }}</span>
            </div>

            {{-- Profit pill --}}
            <div class="profit-pill-wrap">
              <span class="profit-pill {{ $isLoss ? 'neg' : 'pos' }}">
                @if($isLoss)
                  <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19V5m0 14-7-7m7 7 7-7"/></svg>
                @else
                  <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m0-14 7 7m-7-7-7 7"/></svg>
                @endif
                {{ showRupiah($profit) }}
              </span>
            </div>

            {{-- Chevron --}}
            <span class="acc-chevron">
              <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
            </span>
          </div>

          {{-- Expandable detail --}}
          <div class="acc-body">
            <div class="acc-body__title">Rincian Pengeluaran</div>
            @if($date->data->count() > 0)
              @foreach($date->data as $i => $data)
              @php $expTarget = 'exp-detail-' . $date->year . '-' . $date->month . '-' . $data->account_id; @endphp
              <div class="exp-row"
                   onclick="toggleExpDetail(this)"
                   data-account-id="{{ $data->account_id }}"
                   data-start="{{ $date->period_start }}"
                   data-end="{{ $date->period_end }}"
                   data-target="{{ $expTarget }}">
                <span class="exp-row__num">{{ $i + 1 }}</span>
                <span class="exp-row__name">{{ $data->name }}</span>
                <span class="exp-row__val">{{ showRupiah($data->debit) }}</span>
                <span class="exp-row__chevron">▾</span>
              </div>
              <div class="exp-detail" id="{{ $expTarget }}" style="display:none"></div>
              @endforeach
              <div class="exp-total">
                <span class="exp-total__label">Total Pengeluaran</span>
                <span class="exp-total__val">{{ showRupiah($expense) }}</span>
              </div>
            @else
              <div class="exp-empty">Tidak ada pengeluaran tercatat bulan ini.</div>
            @endif
          </div>
        </div>
        @endforeach
      </div>

      {{-- Pagination --}}
      @if(method_exists($dates, 'render'))
      <div class="pagination-wrapper">
        <span class="pagination-info">Menampilkan data per periode</span>
        {{ $dates->render() }}
      </div>
      @endif
    </div>

  </section>
</div>

@section('js-addon')
<script type="text/javascript">
  $(document).ready(function(){
    $('#start_date, #end_date').datepicker({ autoclose: true, format: 'yyyy-mm-dd' });
    changeGraph();
  });

  /* Accordion toggle */
  function toggleAcc(idx) {
    var item = document.getElementById('acc-' + idx);
    item.classList.toggle('is-open');
  }

  /* Expense row drill-down toggle */
  function toggleExpDetail(rowEl) {
    var targetId = rowEl.dataset.target;
    var target = document.getElementById(targetId);
    var willOpen = target.style.display !== 'block';

    // Tutup detail lain yang sedang terbuka
    document.querySelectorAll('.exp-detail').forEach(function (el) { el.style.display = 'none'; });
    document.querySelectorAll('.exp-row.is-active').forEach(function (el) { el.classList.remove('is-active'); });

    if (!willOpen) return;

    rowEl.classList.add('is-active');
    target.style.display = 'block';

    if (target.dataset.loaded === '1') return;

    target.innerHTML = '<div class="exp-detail-loading">Memuat detail...</div>';

    var url = "{{ url('/admin/scaleLedger/detail') }}/" + rowEl.dataset.accountId
            + "/" + rowEl.dataset.start + "/" + rowEl.dataset.end;

    fetch(url)
      .then(function (res) { return res.json(); })
      .then(function (json) {
        target.dataset.loaded = '1';

        var positif = json.positif || [];
        var minus   = json.minus   || [];

        if (positif.length === 0 && minus.length === 0) {
          target.innerHTML = '<div class="exp-detail-empty">Tidak ada rincian transaksi pada periode ini.</div>';
          return;
        }

        var html = '<div class="exp-detail-columns">'
                 +   '<div class="exp-detail-col">'
                 +     '<div class="exp-detail-col-head positif"><span>Positif</span><span class="cnt">' + positif.length + '</span></div>'
                 +     renderExpDetailRows(positif)
                 +   '</div>'
                 +   '<div class="exp-detail-col">'
                 +     '<div class="exp-detail-col-head minus"><span>Minus</span><span class="cnt">' + minus.length + '</span></div>'
                 +     renderExpDetailRows(minus)
                 +   '</div>'
                 + '</div>';

        target.innerHTML = html;
      })
      .catch(function () {
        target.innerHTML = '<div class="exp-detail-empty">Gagal memuat detail. Coba lagi.</div>';
      });
  }

  /* Render satu kolom (positif atau minus) sebagai daftar link ke halaman jurnal */
  function renderExpDetailRows(items) {
    if (!items || items.length === 0) {
      return '<div class="exp-detail-col-empty">Tidak ada.</div>';
    }
    var journalBaseUrl = "{{ url('/admin/journal') }}";
    var html = '';
    items.forEach(function (r) {
      var valClass = r.nominal < 0 ? 'exp-detail-val neg' : 'exp-detail-val';
      html += '<a class="exp-detail-row" href="' + journalBaseUrl + '/' + r.id + '/edit">'
            +   '<span class="exp-detail-date">' + r.tanggal + '</span>'
            +   '<span class="exp-detail-name">' + r.keterangan + '</span>'
            +   '<span class="' + valClass + '">' + formatRupiahJs(r.nominal) + '</span>'
            +   '<span class="exp-detail-arrow">→</span>'
            + '</a>';
    });
    return html;
  }

  function formatRupiahJs(n) {
    var sign = n < 0 ? '-' : '';
    return sign + 'Rp ' + Math.abs(Math.round(n)).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
  }

  /* Bar chart */
  function changeGraph() {
    $.ajax({
      url: "{!! url('/admin/getScaleLedger/' . $start_date . '/' . $end_date . '/profit') !!}",
      success: function(result) {
        var dates      = result.data;
        var ykeys      = [], labels = [], colors = [], showall = [];

        for (var i = 0; i < dates.length; i++) {
          var show = {};

          [dates[i].data, dates[i].dataplus].forEach(function(arr) {
            for (var j = 0; j < arr.length; j++) {
              var code = arr[j].code;
              show[code] = arr[j].debit;
              if (!ykeys.includes(code))       ykeys.push(code);
              if (!labels.includes(arr[j].name))  labels.push(arr[j].name);
              if (!colors.includes(arr[j].color)) colors.push(arr[j].color);
            }
          });

          show['untung'] = dates[i].profit;
          if (!ykeys.includes('untung'))   ykeys.push('untung');
          if (!labels.includes('Untung'))  labels.push('Untung');
          if (!colors.includes('#e74c3c')) colors.push('#e74c3c');

          show['x'] = dates[i].date;
          showall.push(show);
        }

        var bar = new Morris.Bar({
          element:      'bar-chart',
          resize:       true,
          data:         showall,
          xkey:         'x',
          ykeys:        ykeys,
          labels:       labels,
          barColors:    colors,
          xLabelAngle:  50,
          hideHover:    'auto',
          parseTime:    false,
          gridLineColor:'#e2e6f0',
          labelColor:   '#5a5f73',
          barSizeRatio: 0.55
        });
        bar.redraw();
      },
      error: function() { console.log('changeGraph error'); }
    });
  }

  function advanceSearch() {
    window.location = window.location.origin + '/admin/scaleLedger/'
      + $('#start_date').val() + '/' + $('#end_date').val();
  }
</script>
@endsection
@endsection