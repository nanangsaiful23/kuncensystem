<style>
  @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap');

  :root {
    /* Selaraskan dengan sidebar merah AdminLTE NTN Mart */
    --brand:        #ff7b54;
    --brand-dark:   #c0392b;
    --brand-light:  #fdf2f2;
    --brand-mid:    #fce4e4;

    --bg-base:      #f5f6fa;
    --bg-card:      #ffffff;

    --debit-bg:     #f0faf4;
    --debit-border: #86d8a8;
    --debit-text:   #1a6e3c;
    --credit-bg:    #fff5f5;
    --credit-border:#f5a3a3;
    --credit-text:  #b91c1c;

    --text-primary:   #1a1d23;
    --text-secondary: #5a5f73;
    --text-muted:     #9499ad;
    --border:         #e2e6f0;

    --shadow-sm: 0 1px 3px rgba(0,0,0,.07);
    --shadow-md: 0 4px 16px rgba(0,0,0,.1);
    --radius:    12px;
    --radius-sm: 7px;

    /* Typography scale — semua naik ~1 step */
    --fs-xs:   0.78rem;   /* 12.5px */
    --fs-sm:   0.875rem;  /* 14px   */
    --fs-base: 0.9375rem; /* 15px   */
    --fs-md:   1rem;      /* 16px   */
    --fs-lg:   1.0625rem; /* 17px — uang & kode akun */
  }

  * { box-sizing: border-box; margin: 0; padding: 0; }

  .journal-wrapper {
    font-family: 'Inter', sans-serif;
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
    margin-bottom: 20px;
  }
  .journal-header__title {
    font-size: 1.35rem;
    font-weight: 700;
    letter-spacing: -.01em;
    color: var(--text-primary);
  }
  .journal-header__title span {
    color: var(--brand);
  }
  .btn-add {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 22px;
    background: var(--brand);
    color: #fff;
    border: none;
    border-radius: var(--radius-sm);
    font-family: inherit;
    font-size: var(--fs-sm);
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: background .18s, transform .12s, box-shadow .18s;
    box-shadow: 0 2px 8px rgba(231,76,60,.35);
  }
  .btn-add:hover {
    background: var(--brand-dark);
    transform: translateY(-1px);
    box-shadow: 0 4px 14px rgba(231,76,60,.42);
    color: #fff;
  }
  .btn-add svg { flex-shrink: 0; }

  /* ── Filter Card ── */
  .filter-card {
    background: var(--bg-card);
    border-radius: var(--radius);
    border: 1px solid var(--border);
    border-top: 3px solid var(--brand);
    box-shadow: var(--shadow-sm);
    padding: 18px 22px;
    margin-bottom: 18px;
  }
  .filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(175px, 1fr));
    gap: 12px 18px;
    align-items: end;
  }
  .filter-group { display: flex; flex-direction: column; gap: 5px; }
  .filter-label {
    font-family: 'Inter', sans-serif;
    font-size: var(--fs-xs);
    font-weight: 700;
    color: var(--text-secondary);
    text-transform: uppercase;
    letter-spacing: .07em;
  }
  .filter-control {
    font-family: 'Inter', sans-serif;
    font-size: var(--fs-sm);
    padding: 8px 12px;
    border: 1.5px solid var(--border);
    border-radius: var(--radius-sm);
    background: var(--bg-base);
    color: var(--text-primary);
    width: 100%;
    transition: border-color .18s, box-shadow .18s;
    appearance: none;
    -webkit-appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%235a5f73' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
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
    border-color: var(--brand);
    box-shadow: 0 0 0 3px rgba(231,76,60,.12);
  }
  .filter-divider {
    height: 1px;
    background: var(--border);
    margin: 14px 0;
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
    padding: 8px 16px;
    background: var(--brand);
    color: #fff;
    border: none;
    border-radius: var(--radius-sm);
    cursor: pointer;
    transition: background .18s;
  }
  .btn-search:hover { background: var(--brand-dark); }
  .btn-clear {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 8px 12px;
    background: var(--brand-light);
    color: var(--brand-dark);
    border: 1.5px solid var(--credit-border);
    border-radius: var(--radius-sm);
    cursor: pointer;
    transition: background .18s;
  }
  .btn-clear:hover { background: var(--brand-mid); }

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
    font-size: var(--fs-sm);
    font-family: 'Inter', sans-serif;
  }
  .journal-table thead tr {
    background: #fafbfd;
    border-bottom: 2px solid var(--border);
  }
  .journal-table th {
    padding: 13px 15px;
    font-weight: 700;
    color: var(--text-secondary);
    text-align: left;
    font-size: var(--fs-xs);
    text-transform: uppercase;
    letter-spacing: .06em;
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
  .journal-table th a:hover { color: var(--brand); }
  
  /* Grouping Columns */
  .th-debit, .td-debit { border-left: 2px solid var(--debit-border) !important; }
  .th-credit, .td-credit { border-left: 2px solid var(--credit-border) !important; }

  .journal-table th.th-debit { background: #ebfaf2; color: var(--debit-text); border-bottom-color: var(--debit-border); }
  .journal-table th.th-credit { background: #fff0f0; color: var(--credit-text); border-bottom-color: var(--credit-border); }

  .journal-table tbody tr {
    border-bottom: 1px solid var(--border);
    transition: background .12s;
  }
  .journal-table tbody tr:hover { background: #fdf8f8; }
  .journal-table tbody tr.highlighted { background: var(--brand-mid) !important; }
  .journal-table tbody tr:last-child { border-bottom: none; }

  .journal-table td {
    padding: 14px 15px;
    color: var(--text-primary);
    vertical-align: middle;
    font-size: var(--fs-sm);
    line-height: 1.45;
  }
  .td-debit { background: var(--debit-bg); }
  .td-credit { background: var(--credit-bg); }

  /* Badge tipe */
  .badge-type {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: var(--fs-xs);
    font-weight: 700;
    background: var(--brand-light);
    color: var(--brand-dark);
    white-space: nowrap;
    border: 1px solid var(--brand-mid);
  }
  .badge-type.loading     { background: #fff8e7; color: #92400e; border-color: #fde68a; }
  .badge-type.transaction { background: #eff6ff; color: #1e40af; border-color: #bfdbfe; }
  .badge-type.penyusutan  { background: #f5f3ff; color: #5b21b6; border-color: #ddd6fe; }
  .badge-type.operasional { background: #ecfdf5; color: #065f46; border-color: #a7f3d0; }

  /* ── Typography data cells — lebih besar & mudah dibaca ── */

  /* ID jurnal */
  .text-id {
    font-family: 'IBM Plex Mono', monospace;
    font-size: var(--fs-sm);
    color: var(--text-secondary);
    font-weight: 500;
    letter-spacing: -.01em;
  }

  /* Tipe ID — angka referensi */
  .text-type-id {
    font-family: 'IBM Plex Mono', monospace;
    font-size: var(--fs-base);
    font-weight: 700;
    color: var(--text-primary);
    letter-spacing: -.01em;
  }

  /* Tanggal */
  .text-date {
    font-size: var(--fs-base);
    font-weight: 600;
    white-space: nowrap;
    color: var(--text-primary);
  }

  /* Nama akun */
  .text-account-name {
    font-size: var(--fs-base);
    line-height: 1.35;
    font-weight: 500;
    color: var(--text-primary);
  }

  /* Kode akun (1141, 1113, dll) */
  .text-account-code {
    font-family: 'IBM Plex Mono', monospace;
    font-size: var(--fs-md);
    font-weight: 700;
    letter-spacing: .02em;
  }

  /* Nominal uang — paling mencolok */
  .amount {
    font-family: 'IBM Plex Mono', monospace;
    font-size: var(--fs-lg);
    font-weight: 700;
    white-space: nowrap;
    display: block;
    text-align: right;
    letter-spacing: -.01em;
  }
  .amount.debit  { color: var(--debit-text); }
  .amount.credit { color: var(--credit-text); }

  .journal-link {
    color: var(--brand);
    text-decoration: none;
    font-weight: 500;
    font-size: var(--fs-sm);
    line-height: 1.45;
    transition: color .15s;
  }
  .journal-link:hover { color: var(--brand-dark); text-decoration: underline; }

  .btn-edit {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 7px;
    background: var(--brand-light);
    color: var(--brand);
    text-decoration: none;
    border: 1px solid var(--brand-mid);
    transition: background .15s, transform .12s, border-color .15s;
  }
  .btn-edit:hover {
    background: var(--brand);
    color: #fff;
    border-color: var(--brand);
    transform: scale(1.08);
  }

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
  .journal-card.highlighted { border-color: var(--brand); box-shadow: 0 0 0 2px rgba(231,76,60,.18); }

  .jcard-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 11px 14px;
    background: var(--brand-light);
    border-bottom: 1px solid var(--brand-mid);
    gap: 8px;
  }
  .jcard-header-left { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
  .jcard-id {
    font-family: 'IBM Plex Mono', monospace;
    font-size: var(--fs-sm);
    color: var(--text-secondary);
    font-weight: 600;
  }
  .jcard-date {
    font-size: var(--fs-sm);
    color: var(--text-primary);
    font-weight: 600;
  }
  .jcard-actions { display: flex; align-items: center; gap: 8px; }

  .jcard-body { padding: 13px 14px; }
  .jcard-name {
    font-size: var(--fs-base);
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
  .jcard-amount-box.debit  { background: var(--debit-bg);  border: 1px solid var(--debit-border); }
  .jcard-amount-box.credit { background: var(--credit-bg); border: 1px solid var(--credit-border); }
  .jcard-amount-label {
    font-size: var(--fs-xs);
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .08em;
    margin-bottom: 3px;
  }
  .jcard-amount-box.debit  .jcard-amount-label { color: var(--debit-text); }
  .jcard-amount-box.credit .jcard-amount-label { color: var(--credit-text); }
  .jcard-amount-code {
    font-family: 'IBM Plex Mono', monospace;
    font-size: var(--fs-md);
    font-weight: 700;
    color: var(--text-secondary);
    margin-bottom: 1px;
  }
  .jcard-amount-name {
    font-size: 20px;
    color: var(--text-primary);
    font-weight: 500;
    margin-bottom: 5px;
  }
  .jcard-amount-val {
    font-family: 'IBM Plex Mono', monospace;
    font-size: 22px;
    font-weight: 700;
    letter-spacing: -.01em;
  }
  .jcard-amount-box.debit  .jcard-amount-val { color: var(--debit-text); }
  .jcard-amount-box.credit .jcard-amount-val { color: var(--credit-text); }

  .jcard-meta { display: flex; flex-wrap: wrap; gap: 6px; }
  .jcard-meta-item {
    font-size: var(--fs-xs);
    color: var(--text-muted);
    background: var(--bg-base);
    padding: 3px 8px;
    border-radius: 20px;
    border: 1px solid var(--border);
  }

  /* ── Pagination ── */
  .pagination-wrapper {
    padding: 14px 20px;
    border-top: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 8px;
    background: #fafbfd;
  }
  .pagination-wrapper .pagination { margin: 0; }
  /* Warnai tombol pagination aktif sesuai brand */
  .pagination-wrapper .pagination > .active > a,
  .pagination-wrapper .pagination > .active > span {
    background-color: var(--brand) !important;
    border-color: var(--brand) !important;
    color: #fff !important;
  }
  .pagination-wrapper .pagination > li > a:hover {
    border-color: var(--brand);
    color: var(--brand);
  }

  /* ── Checkbox ── */
  .custom-check {
    width: 16px; height: 16px;
    cursor: pointer;
    accent-color: var(--brand);
  }

  /* ── Sort icon ── */
  .sort-icon { opacity: .45; }

  /* ── Responsive ── */
  @media (max-width: 900px) {
    .journal-table-wrap { display: none; }
    .journal-cards { display: block; padding: 16px 0; }
    .filter-grid { grid-template-columns: 1fr 1fr; }
  }
  @media (max-width: 520px) {
    .journal-wrapper { padding: 14px 10px 40px; }
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
            <td><span class="badge-type {{ $typeClass }}">{{ $journal->type }}</span></td>
            <td class="text-id">{{ $journal->id }}</td>
            <td class="text-type-id">{{ $journal->type_id }}</td>
            <td class="text-date">{{ displayDate($journal->journal_date) }}</td>
            <td style="max-width:200px;">
              @if($journal->type == 'good_loading')
                <a href="{{ url($role . '/good-loading/' . $journal->type_id . '/detail') }}" class="journal-link" target="_blank">{{ $journal->name }}</a>
              @elseif($journal->type == 'transaction' || $journal->type == 'penyusutan' || $journal->type == 'operasional' || strpos($journal->type, "hutang dagang") !== false)
                <a href="{{ url($role . '/transaction/' . $journal->type_id . '/detail') }}" class="journal-link" target="_blank">{{ $journal->name }}</a>
              @else
                <span class="text-account-name">{{ $journal->name }}</span>
              @endif
            </td>
            <td class="td-debit text-account-code" style="color:var(--debit-text);">{{ $journal->debit_account()->code }}</td>
            <td class="td-debit text-account-name">{{ $journal->debit_account()->name }}</td>
            <td class="td-debit"><span class="amount debit">{{ showRupiah($journal->debit) }}</span></td>
            <td class="td-credit text-account-code" style="color:var(--credit-text);">{{ $journal->credit_account()->code }}</td>
            <td class="td-credit text-account-name">{{ $journal->credit_account()->name }}</td>
            <td class="td-credit"><span class="amount credit">{{ showRupiah($journal->credit) }}</span></td>
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
