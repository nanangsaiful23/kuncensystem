<style>
  @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap');

  :root {
    --bg-base: #F4F6FB;
    --bg-card: #FFFFFF;
    --bg-sidebar: #1A1F2E;
    --accent: #3B6FE8;
    --accent-light: #EEF3FD;
    --debit-bg: #EDFAF3;
    --debit-border: #A3E6C4;
    --debit-text: #1A7A4A;
    --credit-bg: #FFF0F0;
    --credit-border: #FFC2C2;
    --credit-text: #C0392B;
    --text-primary: #1C2233;
    --text-secondary: #6B7694;
    --text-muted: #9BA3BB;
    --border: #E4E9F4;
    --shadow-sm: 0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
    --shadow-md: 0 4px 16px rgba(0,0,0,.08);
    --radius: 14px;
    --radius-sm: 8px;
  }

  * { box-sizing: border-box; margin: 0; padding: 0; }

  .journal-wrapper {
    font-family: 'Plus Jakarta Sans', sans-serif;
    background: var(--bg-base);
    min-height: calc(100vh - 50px);
    padding: 20px;
    color: var(--text-primary);
    position: relative;
    z-index: 1;
  }

  /* ── Page Header ── */
  .journal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 24px;
  }
  .journal-header__title {
    font-size: 1.5rem;
    font-weight: 700;
    letter-spacing: -.02em;
    color: var(--text-primary);
  }
  .journal-header__title span {
    color: var(--accent);
  }
  .btn-add {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: var(--accent);
    color: #fff;
    border: none;
    border-radius: var(--radius-sm);
    font-family: inherit;
    font-size: .875rem;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: background .18s, transform .12s, box-shadow .18s;
    box-shadow: 0 2px 8px rgba(59,111,232,.3);
  }
  .btn-add:hover {
    background: #2d5fd4;
    transform: translateY(-1px);
    box-shadow: 0 4px 14px rgba(59,111,232,.38);
    color: #fff;
  }
  .btn-add svg { flex-shrink: 0; }

  /* ── Filter Card ── */
  .filter-card {
    background: var(--bg-card);
    border-radius: var(--radius);
    border: 1px solid var(--border);
    box-shadow: var(--shadow-sm);
    padding: 20px 24px;
    margin-bottom: 20px;
  }
  .filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 14px 20px;
    align-items: end;
  }
  .filter-group { display: flex; flex-direction: column; gap: 5px; }
  .filter-label {
    font-size: .75rem;
    font-weight: 600;
    color: var(--text-secondary);
    text-transform: uppercase;
    letter-spacing: .06em;
  }
  .filter-control {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: .875rem;
    padding: 8px 12px;
    border: 1.5px solid var(--border);
    border-radius: var(--radius-sm);
    background: var(--bg-base);
    color: var(--text-primary);
    width: 100%;
    transition: border-color .18s, box-shadow .18s;
    appearance: none;
    -webkit-appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236B7694' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 10px center;
    padding-right: 28px;
  }
  .filter-control:not(select) {
    background-image: none;
    padding-right: 12px;
  }
  .filter-control:focus {
    outline: none;
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(59,111,232,.12);
  }
  .filter-divider {
    height: 1px;
    background: var(--border);
    margin: 16px 0;
  }
  .filter-search {
    display: flex;
    gap: 8px;
    align-items: center;
  }
  .filter-search .filter-control { flex: 1; }
  .btn-search {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 8px 14px;
    background: var(--accent);
    color: #fff;
    border: none;
    border-radius: var(--radius-sm);
    cursor: pointer;
    transition: background .18s;
  }
  .btn-search:hover { background: #2d5fd4; }
  .btn-clear {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 8px 12px;
    background: #FFF0F0;
    color: var(--credit-text);
    border: 1.5px solid var(--credit-border);
    border-radius: var(--radius-sm);
    cursor: pointer;
    transition: background .18s;
  }
  .btn-clear:hover { background: var(--credit-bg); }

  /* ── Table Card ── */
  .table-card {
    background: var(--bg-card);
    border-radius: var(--radius);
    border: 1px solid var(--border);
    box-shadow: var(--shadow-sm);
    overflow: hidden;
  }

  /* ── Desktop Table ── */
  .journal-table {
    width: 100%;
    border-collapse: collapse;
    font-size: .82rem;
  }
  .journal-table thead tr {
    background: #F8FAFF;
    border-bottom: 2px solid var(--border);
  }
  .journal-table th {
    padding: 12px 14px;
    font-weight: 600;
    color: var(--text-secondary);
    text-align: left;
    font-size: 1rem;
    text-transform: uppercase;
    letter-spacing: .05em;
    white-space: nowrap;
    border-bottom: 2px solid var(--border);
  }
  .journal-table th a {
    color: inherit;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    transition: color .15s;
  }
  .journal-table th a:hover { color: var(--accent); }
  .journal-table th.th-debit { background: #EDFAF3; color: var(--debit-text); }
  .journal-table th.th-credit { background: #FFF0F0; color: var(--credit-text); }

  .journal-table tbody tr {
    border-bottom: 1px solid var(--border);
    transition: background .15s;
  }
  .journal-table tbody tr:hover { background: #F8FAFF; }
  .journal-table tbody tr.highlighted { background: #EEF3FD !important; }
  .journal-table tbody tr:last-child { border-bottom: none; }

  .journal-table td {
    padding: 12px 14px;
    color: var(--text-primary);
    vertical-align: middle;
  }
  .td-debit { background: var(--debit-bg); }
  .td-credit { background: var(--credit-bg); }

  .badge-type {
    display: inline-block;
    padding: 3px 9px;
    border-radius: 20px;
    font-size: .72rem;
    font-weight: 600;
    background: var(--accent-light);
    color: var(--accent);
    white-space: nowrap;
  }
  .badge-type.loading { background: #FFF8E7; color: #B45309; }
  .badge-type.transaction { background: #EDF9FF; color: #0369A1; }
  .badge-type.penyusutan { background: #F3F0FF; color: #6D28D9; }
  .badge-type.operasional { background: #ECFDF5; color: #065F46; }

  .amount {
    font-family: 'JetBrains Mono', monospace;
    font-size: .8rem;
    font-weight: 500;
    white-space: nowrap;
  }
  .amount.debit { color: var(--debit-text); }
  .amount.credit { color: var(--credit-text); }

  .journal-link {
    color: var(--accent);
    text-decoration: none;
    font-weight: 500;
    font-size: .82rem;
    line-height: 1.4;
    transition: color .15s;
  }
  .journal-link:hover { color: #2d5fd4; text-decoration: underline; }

  .btn-edit {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 30px;
    height: 30px;
    border-radius: 6px;
    background: var(--accent-light);
    color: var(--accent);
    text-decoration: none;
    transition: background .15s, transform .12s;
  }
  .btn-edit:hover { background: var(--accent); color: #fff; transform: scale(1.08); }

  .table-scroll { overflow-x: auto; -webkit-overflow-scrolling: touch; }

  /* ── Mobile Cards ── */
  .journal-cards { display: none; }
  .journal-card {
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    margin: 0 16px 12px;
    background: #fff;
    overflow: hidden;
    transition: box-shadow .18s;
  }
  .journal-card:last-child { margin-bottom: 0; }
  .journal-card:hover { box-shadow: var(--shadow-md); }
  .journal-card.highlighted { border-color: var(--accent); box-shadow: 0 0 0 2px rgba(59,111,232,.2); }

  .jcard-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 14px;
    background: #F8FAFF;
    border-bottom: 1px solid var(--border);
    gap: 8px;
  }
  .jcard-header-left { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
  .jcard-id {
    font-family: 'JetBrains Mono', monospace;
    font-size: .8rem;
    color: var(--text-secondary);
    font-weight: 500;
  }
  .jcard-date {
    font-size: .78rem;
    color: var(--text-muted);
  }
  .jcard-actions { display: flex; align-items: center; gap: 8px; }

  .jcard-body { padding: 12px 14px; }
  .jcard-name {
    font-size: .88rem;
    font-weight: 600;
    margin-bottom: 10px;
    line-height: 1.4;
  }
  .jcard-amounts {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
    margin-bottom: 10px;
  }
  .jcard-amount-box {
    border-radius: var(--radius-sm);
    padding: 10px 12px;
  }
  .jcard-amount-box.debit { background: var(--debit-bg); border: 1px solid var(--debit-border); }
  .jcard-amount-box.credit { background: var(--credit-bg); border: 1px solid var(--credit-border); }
  .jcard-amount-label {
    font-size: .7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .07em;
    margin-bottom: 2px;
  }
  .jcard-amount-box.debit .jcard-amount-label { color: var(--debit-text); }
  .jcard-amount-box.credit .jcard-amount-label { color: var(--credit-text); }
  .jcard-amount-code {
    font-family: 'JetBrains Mono', monospace;
    font-size: .72rem;
    color: var(--text-secondary);
    margin-bottom: 2px;
  }
  .jcard-amount-name {
    font-size: .78rem;
    color: var(--text-primary);
    font-weight: 500;
    margin-bottom: 4px;
  }
  .jcard-amount-val {
    font-family: 'JetBrains Mono', monospace;
    font-size: .85rem;
    font-weight: 700;
  }
  .jcard-amount-box.debit .jcard-amount-val { color: var(--debit-text); }
  .jcard-amount-box.credit .jcard-amount-val { color: var(--credit-text); }

  .jcard-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
  }
  .jcard-meta-item {
    font-size: .72rem;
    color: var(--text-muted);
    background: var(--bg-base);
    padding: 3px 8px;
    border-radius: 20px;
    border: 1px solid var(--border);
  }

  /* ── Pagination ── */
  .pagination-wrapper {
    padding: 16px 20px;
    border-top: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 8px;
  }
  .pagination-wrapper .pagination { margin: 0; }

  /* ── Checkbox ── */
  .custom-check {
    width: 16px; height: 16px;
    cursor: pointer;
    accent-color: var(--accent);
  }

  /* ── Sort icon ── */
  .sort-icon { opacity: .5; }

  /* ── Responsive ── */
  @media (max-width: 900px) {
    .journal-table-wrap { display: none; }
    .journal-cards { display: block; padding: 16px 0; }
    .filter-grid { grid-template-columns: 1fr 1fr; }
  }
  @media (max-width: 520px) {
    .journal-wrapper { padding: 16px 12px 40px; }
    .filter-grid { grid-template-columns: 1fr; }
    .journal-header { flex-direction: column; align-items: flex-start; }
    .jcard-amounts { grid-template-columns: 1fr; }
  }
</style>

<div class="content-wrapper journal-wrapper">
  @include('layout' . '.alert-message', ['type' => $default['type'], 'data' => $default['data'], 'color' => $default['color']])

  {{-- Header --}}
  <div class="journal-header">
    <h1 class="journal-header__title">Daftar <span>Jurnal</span></h1>
    <a href="{{ url($role . '/journal/create') }}" class="btn-add">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
      Tambah Jurnal
    </a>
  </div>

  {{-- Filter Card --}}
  <div class="filter-card">
    {{-- Row 1: Search --}}
    <div class="filter-search" style="margin-bottom: 16px;">
      <input type="text" class="filter-control" id="search-input" placeholder="Cari jurnal…" style="max-width: 360px;">
      <button class="btn-search" id="search-btn" title="Cari">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="m21 21-4.35-4.35"/></svg>
      </button>
      <button class="btn-clear" title="Reset">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>

    <div class="filter-divider"></div>

    {{-- Row 2: Dropdowns --}}
    <div class="filter-grid">
      <div class="filter-group">
        <label class="filter-label">Show</label>
        {!! Form::select('show', getPaginations(), $pagination, ['class' => 'filter-control', 'id' => 'show', 'onchange' => 'advanceSearch()']) !!}
      </div>
      <div class="filter-group">
        <label class="filter-label">Tipe</label>
        {!! Form::select('journal_type', getJournalTypes(), $type, ['class' => 'filter-control', 'id' => 'journal_type', 'onchange' => 'advanceSearch()']) !!}
      </div>
      <div class="filter-group">
        <label class="filter-label">Akun</label>
        {!! Form::select('code', getAccountLists(), $code, ['class' => 'filter-control', 'id' => 'code', 'onchange' => 'advanceSearch()']) !!}
      </div>
      <div class="filter-group">
        <label class="filter-label">Sort</label>
        {!! Form::select('sort', ['id' => 'id', 'created_at' => 'created_at', 'updated_at' => 'updated_at', 'debit' => 'nominal'], $sort, ['class' => 'filter-control', 'id' => 'sort', 'onchange' => 'advanceSearch()']) !!}
      </div>
      <div class="filter-group">
        <label class="filter-label">Tanggal Awal</label>
        <input type="text" class="filter-control" id="datepicker" name="start_date" value="{{ $start_date }}" onchange="changeDate()" placeholder="yyyy-mm-dd">
      </div>
      <div class="filter-group">
        <label class="filter-label">Tanggal Akhir</label>
        <input type="text" class="filter-control" id="datepicker2" name="end_date" value="{{ $end_date }}" onchange="changeDate()" placeholder="yyyy-mm-dd">
      </div>
    </div>
  </div>

  {{-- Table Card --}}
  <div class="table-card">

    {{-- Desktop Table --}}
    <div class="journal-table-wrap table-scroll">
      <table class="journal-table" id="example1">
        <thead>
          <tr>
            <th width="36px"></th>
            <th>Tipe</th>
            <th>
              @if($order == 'desc')
                <a href="{{ url($role . '/journal/' . $code . '/' . $type . '/' . $start_date . '/' . $end_date . '/id/asc/' . $pagination) }}">
              @else
                <a href="{{ url($role . '/journal/' . $code . '/' . $type . '/' . $start_date . '/' . $end_date . '/id/desc/' . $pagination) }}">
              @endif
                <svg class="sort-icon" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M7 16V4m0 0L3 8m4-4 4 4M17 8v12m0 0 4-4m-4 4-4-4"/></svg>
                ID
              </a>
            </th>
            <th>Tipe ID</th>
            <th>
              @if($order == 'desc')
                <a href="{{ url($role . '/journal/' . $code . '/' . $type . '/' . $start_date . '/' . $end_date . '/journal_date/asc/' . $pagination) }}">
              @else
                <a href="{{ url($role . '/journal/' . $code . '/' . $type . '/' . $start_date . '/' . $end_date . '/journal_date/desc/' . $pagination) }}">
              @endif
                <svg class="sort-icon" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M7 16V4m0 0L3 8m4-4 4 4M17 8v12m0 0 4-4m-4 4-4-4"/></svg>
                Tanggal
              </a>
            </th>
            <th>Nama</th>
            <th class="th-debit">
              @if($order == 'desc')
                <a href="{{ url($role . '/journal/' . $code . '/' . $type . '/' . $start_date . '/' . $end_date . '/debit_account_id/asc/' . $pagination) }}">
              @else
                <a href="{{ url($role . '/journal/' . $code . '/' . $type . '/' . $start_date . '/' . $end_date . '/debit_account_id/desc/' . $pagination) }}">
              @endif
                <svg class="sort-icon" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M7 16V4m0 0L3 8m4-4 4 4M17 8v12m0 0 4-4m-4 4-4-4"/></svg>
                No Akun (D)
              </a>
            </th>
            <th class="th-debit">Akun Debet</th>
            <th class="th-debit">
              @if($order == 'desc')
                <a href="{{ url($role . '/journal/' . $code . '/' . $type . '/' . $start_date . '/' . $end_date . '/debit/asc/' . $pagination) }}">
              @else
                <a href="{{ url($role . '/journal/' . $code . '/' . $type . '/' . $start_date . '/' . $end_date . '/debit/desc/' . $pagination) }}">
              @endif
                <svg class="sort-icon" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M7 16V4m0 0L3 8m4-4 4 4M17 8v12m0 0 4-4m-4 4-4-4"/></svg>
                Debet
              </a>
            </th>
            <th class="th-credit">
              @if($order == 'desc')
                <a href="{{ url($role . '/journal/' . $code . '/' . $type . '/' . $start_date . '/' . $end_date . '/credit_account_id/asc/' . $pagination) }}">
              @else
                <a href="{{ url($role . '/journal/' . $code . '/' . $type . '/' . $start_date . '/' . $end_date . '/credit_account_id/desc/' . $pagination) }}">
              @endif
                <svg class="sort-icon" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M7 16V4m0 0L3 8m4-4 4 4M17 8v12m0 0 4-4m-4 4-4-4"/></svg>
                No Akun (K)
              </a>
            </th>
            <th class="th-credit">Akun Kredit</th>
            <th class="th-credit">Kredit</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody id="table-good">
          @foreach($journals as $journal)
          @php
            $typeClass = '';
            if ($journal->type == 'good_loading') {
                $typeClass = 'loading';
            } elseif ($journal->type == 'transaction') {
                $typeClass = 'transaction';
            } elseif ($journal->type == 'penyusutan') {
                $typeClass = 'penyusutan';
            } elseif ($journal->type == 'operasional') {
                $typeClass = 'operasional';
            }
          @endphp
          <tr id="div-journal-{{ $journal->id }}">
            <td style="text-align:center;">
              <input type="checkbox" class="custom-check" name="journals[]" id="journal-{{ $journal->id }}" onclick="highlight('journal-{{ $journal->id }}')">
            </td>
            <td><span class="badge-type {{ $typeClass }}" style="font-size:1rem;">{{ $journal->type }}</span></td>
            <td style="font-family:'JetBrains Mono',monospace; font-size:.8rem; color:var(--text-secondary);">{{ $journal->id }}</td>
            <td style="font-size:.82rem; color:var(--text-secondary);">{{ $journal->type_id }}</td>
            <td style="font-size:1rem; white-space:nowrap;">{{ displayDate($journal->journal_date) }}</td>
            <td style="max-width:200px;">
              @if($journal->type == 'good_loading')
                <a href="{{ url($role . '/good-loading/' . $journal->type_id . '/detail') }}" class="journal-link" target="_blank">{{ $journal->name }}</a>
              @elseif($journal->type == 'transaction' || $journal->type == 'penyusutan' || $journal->type == 'operasional' || strpos($journal->type, "hutang dagang") !== false)
                <a href="{{ url($role . '/transaction/' . $journal->type_id . '/detail') }}" class="journal-link" target="_blank">{{ $journal->name }}</a>
              @else
                <span style="font-size:1rem;">{{ $journal->name }}</span>
              @endif
            </td>
            <td class="td-debit" style="font-family:'JetBrains Mono',monospace; font-size:.78rem; font-weight:600; color:var(--debit-text);">{{ $journal->debit_account()->code }}</td>
            <td class="td-debit" style="font-size:1rem;">{{ $journal->debit_account()->name }}</td>
            <td class="td-debit"  ><span class="amount debit" style="font-size:1.5rem;">{{ showRupiah($journal->debit) }}</span></td>
            <td class="td-credit" style="font-family:'JetBrains Mono',monospace; font-size:.78rem; font-weight:600; color:var(--credit-text);">{{ $journal->credit_account()->code }}</td>
            <td class="td-credit" style="font-size:1rem;">{{ $journal->credit_account()->name }}</td>
            <td class="td-credit"  ><span class="amount credit" style="font-size:1.5rem;">{{ showRupiah($journal->credit) }}</span></td>
            <td>
              <a href="{{ url($role . '/journal/' . $journal->id . '/edit') }}" class="btn-edit" title="Edit">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-5m-1.414-9.414a2 2 0 1 1 2.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
              </a>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    {{-- Mobile Cards --}}
    <div class="journal-cards">
      @foreach($journals as $journal)
      @php
        $typeClass = '';
        if ($journal->type == 'good_loading') {
            $typeClass = 'loading';
        } elseif ($journal->type == 'transaction') {
            $typeClass = 'transaction';
        } elseif ($journal->type == 'penyusutan') {
            $typeClass = 'penyusutan';
        } elseif ($journal->type == 'operasional') {
            $typeClass = 'operasional';
        }
      @endphp
      <div class="journal-card" id="card-journal-{{ $journal->id }}">
        <div class="jcard-header">
          <div class="jcard-header-left">
            <input type="checkbox" class="custom-check" id="journal-card-{{ $journal->id }}" onclick="highlightCard('journal-{{ $journal->id }}')">
            <span class="badge-type {{ $typeClass }}">{{ $journal->type }}</span>
            <span class="jcard-id">#{{ $journal->id }}</span>
            <span class="jcard-date">{{ displayDate($journal->journal_date) }}</span>
          </div>
          <div class="jcard-actions">
            <a href="{{ url($role . '/journal/' . $journal->id . '/edit') }}" class="btn-edit" title="Edit">
              <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-5m-1.414-9.414a2 2 0 1 1 2.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            </a>
          </div>
        </div>
        <div class="jcard-body">
          <div class="jcard-name">
            @if($journal->type == 'good_loading')
              <a href="{{ url($role . '/good-loading/' . $journal->type_id . '/detail') }}" class="journal-link" target="_blank">{{ $journal->name }}</a>
            @elseif($journal->type == 'transaction' || $journal->type == 'penyusutan' || $journal->type == 'operasional' || strpos($journal->type, "hutang dagang") !== false)
              <a href="{{ url($role . '/transaction/' . $journal->type_id . '/detail') }}" class="journal-link" target="_blank">{{ $journal->name }}</a>
            @else
              {{ $journal->name }}
            @endif
          </div>
          <div class="jcard-amounts">
            <div class="jcard-amount-box debit">
              <div class="jcard-amount-label">Debet</div>
              <div class="jcard-amount-code">{{ $journal->debit_account()->code }}</div>
              <div class="jcard-amount-name">{{ $journal->debit_account()->name }}</div>
              <div class="jcard-amount-val">{{ showRupiah($journal->debit) }}</div>
            </div>
            <div class="jcard-amount-box credit">
              <div class="jcard-amount-label">Kredit</div>
              <div class="jcard-amount-code">{{ $journal->credit_account()->code }}</div>
              <div class="jcard-amount-name">{{ $journal->credit_account()->name }}</div>
              <div class="jcard-amount-val">{{ showRupiah($journal->credit) }}</div>
            </div>
          </div>
          <div class="jcard-meta">
            <span class="jcard-meta-item">Tipe ID: {{ $journal->type_id }}</span>
            <span class="jcard-meta-item">Dibuat: {{ displayDateTime($journal->created_at) }}</span>
            <span class="jcard-meta-item">Diubah: {{ displayDateTime($journal->updated_at) }}</span>
          </div>
        </div>
      </div>
      @endforeach
    </div>

    {{-- Pagination --}}
    @if($pagination != 'all')
    <div class="pagination-wrapper">
      <span style="font-size:.82rem; color:var(--text-muted);">Menampilkan data jurnal</span>
      {{ $journals->render() }}
    </div>
    @endif
  </div>
</div>

@section('js-addon')
<script>
$(document).ready(function(){
  // Init select2 if available
  if($.fn.select2) {
    $('#journal_type, #code, #sort, #show').select2({ minimumResultsForSearch: 5 });
  }

  // Datepickers
  if($.fn.datepicker) {
    $('#datepicker, #datepicker2').datepicker({ autoclose: true, format: 'yyyy-mm-dd' });
  }

  // Search on enter
  $('#search-input').keyup(function(e){ if(e.keyCode == 13) ajaxFunction(); });
  $('#search-btn').click(function(){ ajaxFunction(); });
});

function changeDate() {
  window.location = window.location.origin + '/{{ $role }}/journal/{{ $code }}/{{ $type }}/'
    + $('#datepicker').val() + '/' + $('#datepicker2').val()
    + '/{{ $sort }}/{{ $order }}/{{ $pagination }}';
}

function advanceSearch() {
  window.location = window.location.origin + '/{{ $role }}/journal/'
    + $('#code').val() + '/' + $('#journal_type').val()
    + '/{{ $start_date }}/{{ $end_date }}/'
    + $('#sort').val() + '/{{ $order }}/' + $('#show').val();
}

function highlight(id) {
  var checked = $('#' + id).prop('checked');
  var row = $('#div-' + id);
  row.toggleClass('highlighted', checked);
}

function highlightCard(id) {
  var card = $('#card-' + id);
  var checkbox = $('#journal-card-' + id.replace('journal-',''));
  card.toggleClass('highlighted', checkbox.prop('checked'));
}
</script>
@endsection
