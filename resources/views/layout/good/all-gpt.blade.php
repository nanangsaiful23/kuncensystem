<style>
  :root {
    --bg: #f5f4f0;
    --surface: #ffffff;
    --surface2: #f9f8f5;
    --border: #e8e5de;
    --border-strong: #d4cfc4;
    --text-primary: #1a1814;
    --text-secondary: #6b6659;
    --text-muted: #9e9889;
    --accent: #e85d2f;
    --accent-soft: #fdf1ec;
    --green: #2a7a4b;
    --green-soft: #edf6f1;
    --amber: #b45309;
    --amber-soft: #fef3e2;
    --blue: #1d4ed8;
    --blue-soft: #eff6ff;
    --shadow-sm: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
    --shadow-md: 0 4px 12px rgba(0,0,0,0.08), 0 2px 4px rgba(0,0,0,0.04);
    --shadow-lg: 0 8px 24px rgba(0,0,0,0.1), 0 4px 8px rgba(0,0,0,0.06);
    --radius: 12px;
    --radius-sm: 8px;
  }

  * { margin: 0; padding: 0; box-sizing: border-box; }

  body {
    font-family: 'Plus Jakarta Sans', sans-serif;
    background: var(--bg);
    color: var(--text-primary);
    min-height: 100vh;
  }

  /* ─── TOP BAR ─── */
  .topbar {
    background: var(--surface);
    border-bottom: 1px solid var(--border);
    padding: 0 clamp(12px, 3vw, 28px);
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 60px;
    position: sticky;
    top: 0;
    z-index: 100;
    box-shadow: var(--shadow-sm);
    overflow: hidden;
  }
  .topbar-left { display: flex; align-items: center; gap: 12px; min-width: 0; flex: 1; }
  .logo {
    font-weight: 700;
    font-size: 17px;
    letter-spacing: -0.4px;
    color: var(--text-primary);
    text-align: right;
  }
  .logo span { color: var(--accent); }
  .breadcrumb {
    font-size: 13px;
    color: var(--text-muted);
    display: flex;
    align-items: center;
    gap: 6px;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
    min-width: 0;
  }
  .breadcrumb strong { color: var(--text-secondary); font-weight: 500; }

  /* ─── LAYOUT ─── */
  .page-wrapper { max-width: 1400px; margin: 0 auto; padding: clamp(14px, 3vw, 28px) clamp(12px, 3vw, 28px) 60px; }

  /* ─── PAGE HEADER ─── */
  .page-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 24px;
    gap: 16px;
    flex-wrap: wrap;
  }
  .page-title h1 {
    font-size: 26px;
    font-weight: 700;
    letter-spacing: -0.6px;
    color: var(--text-primary);
  }
  .page-title p { font-size: 14px; color: var(--text-secondary); margin-top: 3px; }
  .btn-add {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: var(--accent);
    color: #fff;
    border: none;
    padding: 10px 20px;
    border-radius: var(--radius-sm);
    font-family: inherit;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.15s, transform 0.1s, box-shadow 0.15s;
    box-shadow: 0 2px 8px rgba(232,93,47,0.3);
    white-space: nowrap;
  }
  .btn-add:hover { background: #d04f24; box-shadow: 0 4px 12px rgba(232,93,47,0.35); transform: translateY(-1px); }
  .btn-add:active { transform: translateY(0); }
  .btn-add svg { width: 16px; height: 16px; }

  /* ─── STATS ROW ─── */
  .stats-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
    margin-bottom: 24px;
  }
  .stat-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 16px 20px;
    box-shadow: var(--shadow-sm);
    display: flex;
    align-items: center;
    gap: 14px;
    transition: box-shadow 0.15s, transform 0.15s;
  }
  .stat-card:hover { box-shadow: var(--shadow-md); transform: translateY(-1px); }
  .stat-icon {
    width: 42px; height: 42px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
  }
  .stat-icon svg { width: 20px; height: 20px; }
  .stat-icon.blue { background: var(--blue-soft); color: var(--blue); }
  .stat-icon.green { background: var(--green-soft); color: var(--green); }
  .stat-icon.amber { background: var(--amber-soft); color: var(--amber); }
  .stat-icon.red { background: var(--accent-soft); color: var(--accent); }
  .stat-value { font-size: 22px; font-weight: 700; letter-spacing: -0.5px; }
  .stat-label { font-size: 12px; color: var(--text-secondary); margin-top: 1px; font-weight: 500; }

  /* ─── FILTERS ─── */
  .filters-panel {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 18px 20px;
    margin-bottom: 20px;
    box-shadow: var(--shadow-sm);
    overflow: hidden;
    min-width: 0;
  }
  .filters-top {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    align-items: center;
  }
  /* ── Search Bar ── */
  .search-wrap {
    min-width: min(300px, 100%);
    padding-bottom: 16px;
    flex: 1;
  }

  .search-box {
    display: flex;
    align-items: center;
    background: var(--bg);
    border: 1.5px solid var(--border);
    border-radius: var(--radius);
    padding: 0 6px 0 16px;
    transition: border-color .2s, box-shadow .2s;
  }
  .search-box:focus-within {
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(200,64,26,.1);
    background: #fff;
  }

  .search-icon {
    color: var(--text-muted);
    flex-shrink: 0;
    display: flex;
  }

  #searchInput {
    flex: 1;
    border: none;
    background: transparent;
    font-family: inherit;
    font-size: 15px;
    color: var(--text-primary);
    padding: 13px 10px;
    outline: none;
  }
  #searchInput::placeholder { color: var(--text-muted); }

  .btn-search {
    width: 32px; height: 32px;
    border: none; background: transparent;
    cursor: pointer;
    color: var(--text-muted);
    border-radius: 6px;
    display: flex; align-items: center; justify-content: center;
    transition: background .15s, color .15s;
    flex-shrink: 0;
  }
  .btn-search:hover { background: var(--tag-bg); color: #6FCF97; }

  .btn-clear, .close {
    width: 32px; height: 32px;
    border: none; background: transparent;
    cursor: pointer;
    color: var(--text-muted);
    border-radius: 6px;
    display: flex; align-items: center; justify-content: center;
    transition: background .15s, color .15s;
    flex-shrink: 0;
  }
  .btn-clear:hover, .close:hover { background: var(--tag-bg); color: var(--accent); }

  .close 
  {
    display: fixed;
  }

  .filter-select {
    padding: 9px 32px 9px 12px;
    border: 1.5px solid var(--border);
    border-radius: var(--radius-sm);
    background: var(--bg);
    font-family: inherit;
    font-size: 13.5px;
    color: var(--text-primary);
    cursor: pointer;
    outline: none;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%239e9889' stroke-width='2.5'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 10px center;
    min-width: min(150px, 100%);
    max-width: 100%;
    transition: border-color 0.15s;
  }
  .filter-select:focus { border-color: var(--accent); background-color: white; }

  .filters-bottom {
    display: flex;
    gap: 8px;
    margin-top: 12px;
    flex-wrap: wrap;
    align-items: center;
  }
  .filter-tag {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    background: var(--surface2);
    border: 1px solid var(--border);
    border-radius: 20px;
    font-size: 12.5px;
    color: var(--text-secondary);
    cursor: pointer;
    transition: all 0.15s;
    font-weight: 500;
  }
  .filter-tag:hover { border-color: var(--accent); color: var(--accent); }
  .filter-tag.active { background: var(--accent-soft); border-color: var(--accent); color: var(--accent); }
  .results-meta {
    margin-left: auto;
    font-size: 13px;
    color: var(--text-muted);
    white-space: nowrap;
  }

  /* ─── TABLE TOOLBAR ─── */
  .table-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12px;
    flex-wrap: wrap;
    gap: 10px;
  }
  .sort-controls { display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--text-secondary); }
  .sort-btn {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 5px 11px;
    border: 1.5px solid var(--border);
    border-radius: 20px;
    background: var(--surface);
    font-size: 12.5px;
    font-family: inherit;
    color: var(--text-secondary);
    cursor: pointer;
    transition: all 0.15s;
    font-weight: 500;
  }
  .sort-btn:hover, .sort-btn.active { border-color: var(--accent); color: var(--accent); background: var(--accent-soft); }
  .view-toggle { display: flex; gap: 4px; }
  .view-btn {
    width: 32px; height: 32px;
    display: flex; align-items: center; justify-content: center;
    border: 1.5px solid var(--border);
    border-radius: 6px;
    background: var(--surface);
    cursor: pointer;
    color: var(--text-muted);
    transition: all 0.15s;
  }
  .view-btn:hover, .view-btn.active { border-color: var(--accent); color: var(--accent); background: var(--accent-soft); }
  .view-btn svg { width: 15px; height: 15px; }

  /* ─── PRODUCT LIST ─── */
  .product-list { display: flex; flex-direction: column; gap: 10px; }

  .product-card {
    background: var(--surface);
    border: 1.5px solid var(--border);
    border-radius: var(--radius);
    padding: 18px 22px;
    display: grid;
    grid-template-columns: 36px minmax(0,1fr) auto auto auto auto;
    gap: 16px 20px;
    align-items: center;
    box-shadow: var(--shadow-sm);
    transition: border-color 0.15s, box-shadow 0.15s, transform 0.15s;
    cursor: pointer;
    animation: fadeSlideIn 0.3s ease both;
    min-width: 0;
  }
    border-color: #cfc9be;
    box-shadow: var(--shadow-md);
    transform: translateY(-2px);
  }
  .product-card:active { transform: translateY(0); }

  @keyframes fadeSlideIn {
    from { opacity: 0; transform: translateY(8px); }
    to   { opacity: 1; transform: translateY(0); }
  }
  .product-card:nth-child(1) { animation-delay: 0.04s; }
  .product-card:nth-child(2) { animation-delay: 0.08s; }
  .product-card:nth-child(3) { animation-delay: 0.12s; }
  .product-card:nth-child(4) { animation-delay: 0.16s; }
  .product-card:nth-child(5) { animation-delay: 0.20s; }

  /* checkbox */
  .product-checkbox {
    width: 18px; height: 18px;
    border: 1.5px solid var(--border-strong);
    border-radius: 5px;
    cursor: pointer;
    appearance: none;
    background: var(--surface);
    transition: all 0.12s;
    flex-shrink: 0;
  }
  .product-checkbox:checked {
    background: var(--accent);
    border-color: var(--accent);
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='3'%3E%3Cpolyline points='20 6 9 17 4 12'%3E%3C/polyline%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: center;
  }

  /* product info */
  .product-info { min-width: 0; overflow: hidden; }
  .product-name {
    font-size: 15px;
    font-weight: 600;
    color: var(--text-primary);
    letter-spacing: -0.2px;
    overflow: hidden;
    text-overflow: ellipsis;
    word-break: break-word;
    overflow-wrap: break-word;
    margin-bottom: 5px;
  }
  .product-meta { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; row-gap: 5px; }
  .category-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 9px;
    border-radius: 20px;
    font-size: 11.5px;
    font-weight: 600;
    letter-spacing: 0.1px;
  }
  .category-badge.lemari { background: #ede8ff; color: #5b3fc4; }
  .category-badge.plastik { background: #e0f2fe; color: #0369a1; }
  .category-badge.lainnya { background: #f1f5f9; color: #475569; }
  .distributor-info {
    font-size: 12px;
    color: var(--text-primary);
    display: flex; align-items: center; gap: 4px;
  }
  .distributor-info svg { width: 12px; height: 12px; flex-shrink: 0; }
  .product-code {
    font-family: 'DM Mono', monospace;
    font-size: 11.5px;
    color: var(--text-primary);
    background: var(--surface2);
    border: 1px solid var(--border);
    padding: 2px 7px;
    border-radius: 4px;
  }

  /* stock */
  .stock-col { text-align: center; min-width: 0; }
  .stock-main {
    font-size: 18px;
    font-weight: 700;
    letter-spacing: -0.3px;
  }
  .stock-main.zero { color: var(--text-muted); }
  .stock-main.low { color: var(--amber); }
  .stock-main.good { color: var(--green); }
  .stock-unit { font-size: 11.5px; color: var(--text-primary); font-weight: 500; margin-top: 1px; }
  .stock-breakdown {
    display: flex; gap: 4px; justify-content: center; margin-top: 5px; flex-wrap: wrap;
  }
  .stock-pill {
    display: inline-flex; align-items: center; gap: 3px;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
    background: var(--surface2);
    color: var(--text-primary);
    border: 1px solid var(--border);
  }
  .stock-pill svg { width: 9px; height: 9px; }

  /* price */
  .price-col { text-align: right; min-width: 0; }
  .price-label { font-size: 11px; color: var(--text-muted); font-weight: 500; margin-bottom: 3px; }
  .price-sell {
    font-size: 16px;
    font-weight: 700;
    color: var(--text-primary);
    letter-spacing: -0.3px;
    word-break: break-word;
    overflow-wrap: break-word;
  }
  .price-buy {
    font-size: 12px;
    color: var(--text-secondary);
    margin-top: 2px;
    word-break: break-word;
  }
  .profit-badge {
    display: inline-flex; align-items: center; gap: 3px;
    margin-top: 5px;
    padding: 3px 8px;
    background: var(--green-soft);
    color: var(--green);
    border-radius: 20px;
    font-size: 11.5px;
    font-weight: 700;
  }
  .profit-badge svg { width: 11px; height: 11px; }

  /* last activity */
  .activity-col { min-width: 0; text-align: center; }
  .activity-date { font-size: 12px; color: var(--text-secondary); font-weight: 500; }
  .activity-label { font-size: 11px; color: var(--text-muted); margin-top: 2px; }
  .never-tag {
    display: inline-block;
    padding: 2px 8px;
    background: var(--surface2);
    border: 1px solid var(--border);
    border-radius: 4px;
    font-size: 11.5px;
    color: var(--text-primary);
    font-weight: 500;
  }

  /* actions */
  .actions-col {
    display: flex;
    flex-direction: column;
    gap: 5px;
    align-items: flex-end;
  }
  .action-row { display: flex; gap: 5px; }
  .action-btn {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 6px 11px;
    border-radius: 6px;
    border: 1.5px solid transparent;
    font-family: inherit;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s;
    white-space: nowrap;
  }
  .action-btn svg { width: 13px; height: 13px; }
  .action-btn.primary {
    background: var(--accent-soft);
    color: var(--accent);
    border-color: #f5c4b0;
  }
  .action-btn.primary:hover { background: var(--accent); color: white; border-color: var(--accent); }
  .action-btn.ghost {
    background: var(--surface2);
    color: var(--text-secondary);
    border-color: var(--border);
  }
  .action-btn.ghost:hover { background: var(--surface); border-color: var(--border-strong); color: var(--text-primary); box-shadow: var(--shadow-sm); }
  .action-btn.danger {
    background: transparent;
    color: #dc2626;
    border-color: transparent;
  }
  .action-btn.danger:hover { background: #fee2e2; border-color: #fca5a5; }
  .action-btn.icon-only { padding: 6px; }

  /* ─── PAGINATION ─── */
  .pagination-wrap {
    margin-top: 28px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 14px;
  }
  .pagination-info { font-size: 13.5px; color: var(--text-secondary); }
  .pagination-info strong { color: var(--text-primary); }
  nav .pagination { display: flex !important; gap: 5px; align-items: center; }
  .page-item {
    width: 34px; height: 34px;
    display: flex; align-items: center; justify-content: center;
    border: 1.5px solid var(--border);
    border-radius: 8px;
    background: var(--surface);
    font-family: 'DM Mono', monospace;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    color: var(--text-secondary);
    transition: all 0.15s;
  }
  .page-item:hover { border-color: var(--accent); color: var(--accent); }
  .page-item.active { background: var(--accent); border-color: var(--accent); color: white; font-weight: 700; }
  .page-item.nav { color: var(--text-muted); }
  .page-item svg { width: 14px; height: 14px; }
  .page-item a { color: var(--text-muted); text-decoration: none;}
  .page-dots {
    color: var(--text-muted);
    font-size: 14px;
    padding: 0 4px;
  }

  /* ─── EMPTY STATE ─── */
  .empty-state {
    text-align: center;
    padding: 60px 20px;
    color: var(--text-muted);
  }
  .empty-state svg { width: 18%; height: 18%; margin-bottom: 14px; opacity: 0.3; }
  .empty-state p { font-size: 15px; color: var(--text-secondary); }

  /* ══════════════════════════════════════
     RESPONSIVE — semua breakpoint
  ══════════════════════════════════════ */

  /* ── Tablet lebar (≤ 1100px) ── */
  @media (max-width: 1100px) {
    .product-card {
      /* Kurangi jadi 5 kolom: buang activity-col ke baris bawah */
      grid-template-columns: 36px 1fr auto auto auto;
      grid-template-rows: auto auto;
    }
    /* Pindah activity ke bawah info, span penuh */
    .activity-col {
      grid-column: 2 / -1;
      text-align: left;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .activity-col .never-tag { display: inline-flex; gap: 12px; }
  }

  /* ── Tablet (≤ 900px) ── */
  @media (max-width: 900px) {
    .page-wrapper { padding: 16px 14px 48px; }

    .stats-row { grid-template-columns: repeat(2, 1fr); gap: 10px; }

    /* Filter area */
    .filters-top    { flex-direction: column; align-items: stretch; }
    .search-wrap    { min-width: unset; width: 100%; }
    .filters-bottom { flex-direction: column; align-items: stretch; gap: 8px; }
    .filter-select  { min-width: unset; width: 100%; }

    /* Sort controls — scroll horizontal, tidak bertumpuk */
    .sort-controls { flex-wrap: nowrap; overflow-x: auto; padding-bottom: 4px; -webkit-overflow-scrolling: touch; }
    .sort-controls::-webkit-scrollbar { height: 3px; }
    .sort-btn      { white-space: nowrap; flex-shrink: 0; }

    /* Product card: 2 kolom utama (checkbox+info | actions) */
    .product-card {
      grid-template-columns: 36px 1fr;
      grid-template-rows: auto auto auto auto;
      gap: 10px 12px;
      padding: 14px 16px;
    }
    /* Urutan manual via grid-area */
    .product-card > input[type="checkbox"] { grid-column: 1; grid-row: 1; align-self: start; margin-top: 3px; }
    .product-info    { grid-column: 2; grid-row: 1; }
    .stock-col       { grid-column: 1 / -1; grid-row: 2; text-align: left; display: flex; align-items: center; gap: 12px; }
    .stock-col .stock-unit { display: inline; }
    .stock-breakdown { margin-top: 0; }
    .price-col       { grid-column: 1 / -1; grid-row: 3; text-align: left; min-width: unset; }
    .activity-col    { grid-column: 1 / -1; grid-row: 4; text-align: left; min-width: unset; }
    .actions-col     { grid-column: 1 / -1; grid-row: 5; align-items: flex-start; flex-direction: row; flex-wrap: wrap; gap: 6px; }
    .action-row      { flex-wrap: wrap; gap: 6px; }
  }

  /* ── Mobile (≤ 600px) ── */
  @media (max-width: 600px) {
    .page-wrapper { padding: 12px 10px 48px; }

    /* Stats: 1 kolom */
    .stats-row { grid-template-columns: 1fr 1fr; gap: 8px; }
    .stat-card { padding: 12px 14px; gap: 10px; }
    .stat-value { font-size: 18px; }

    /* Filter */
    .filters-panel { padding: 12px 14px; }
    #searchInput { font-size: 14px; }

    /* Sort scroll-horizontal, tidak wrap */
    .table-toolbar { flex-direction: column; align-items: flex-start; gap: 8px; }

    /* Product card: full-width single flow */
    .product-card {
      grid-template-columns: 28px 1fr;
      gap: 8px 10px;
      padding: 12px 14px;
    }

    /* Harga jual — bisa wrap */
    .price-sell { font-size: 14px; white-space: normal; word-break: break-word; }
    .price-buy  { white-space: normal; }

    /* action buttons kecil */
    .action-btn { font-size: 11.5px; padding: 5px 9px; }

    /* never-tag block */
    .activity-col .never-tag { display: block; }

    /* Pagination */
    .pagination-wrap { flex-direction: column; align-items: flex-start; gap: 10px; }
    nav .pagination  { flex-wrap: wrap; }
  }

  /* ── Mobile kecil (≤ 400px) ── */
  @media (max-width: 400px) {
    .stats-row { grid-template-columns: 1fr; }
    .stat-card { padding: 10px 12px; }
    .action-btn { font-size: 11px; padding: 5px 8px; }
    .product-card { padding: 10px 12px; }
  }

  /* ─── TOOLTIP ─── */
  [data-tip] { position: relative; }
  [data-tip]:hover::after {
    content: attr(data-tip);
    position: absolute;
    bottom: calc(100% + 6px);
    left: 50%;
    transform: translateX(-50%);
    background: var(--text-primary);
    color: white;
    padding: 4px 9px;
    border-radius: 5px;
    font-size: 11.5px;
    font-weight: 500;
    white-space: nowrap;
    pointer-events: none;
    z-index: 200;
  }

  .modal {
    display: none; /* Hidden by default */
    position: fixed;  /* Stay in place */
    z-index: 1; /* Sit on top */
    /*padding-top: 20px;*/
    left: 0;
    top: 0;
    width: 100%; 
    height: 100%; 
    overflow: auto; /* Enable scroll if needed */
    background-color: rgb(0,0,0); /* Fallback color */
    background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
    border: 1.5px solid var(--border);
    border-radius: var(--radius);
  }

  /* Modal Content */
  .modal-content {
    /*background-color: #fefefe;*/
    margin: auto;
    /*padding: 20px;*/
    border: 1px solid #888;
    width: 100%;
    border: 1.5px solid var(--border);
    border-radius: var(--radius);
  }

  .modal-title
  {
    text-align: center;
  }

  .modal-body
  {
    text-align: center;
  }

  .modal-backdrop
  {
    z-index: -1;
  }

  .alert-delete
  {
    background-color: #dc2626;
  }

  /* ─── SCROLLBAR ─── */
  ::-webkit-scrollbar { width: 7px; height: 7px; }
  ::-webkit-scrollbar-track { background: var(--bg); }
  ::-webkit-scrollbar-thumb { background: var(--border-strong); border-radius: 10px; }
  ::-webkit-scrollbar-thumb:hover { background: var(--text-muted); }
</style>

<div class="content-wrapper">

  @include('layout' . '.alert-message', ['type' => $default['type'], 'data' => $default['data'], 'color' => $default['color']])

  <section class="content">
    <div class="row">
      <div class="filters-panel">
        <div class="filters-top">

          <div class="search-wrap">
            <div class="search-box">
              <span class="search-icon">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
              </span>
              <input id="searchInput" placeholder="Cari nama atau kode barang…" autocomplete="off" spellcheck="false">
              <button class="btn-search" id="btnSearch" title="Cari barang" onclick="ajaxFunction()">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
              </button>
              <button class="btn-clear" id="btnClear" title="Hapus pencarian" onclick="clearInput()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                  <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
              </button>
            </div>
          </div>
        </div>
        <div class="filters-bottom">

          {!! Form::select('category', getCategories(), $category_id, ['class' => 'filter-select select2', 'id' => 'category', 'onchange' => 'advanceSearch()']) !!}
          {!! Form::select('type', getGoodTypes(), $type_id, ['class' => 'filter-select select2', 'id' => 'type', 'onchange' => 'advanceSearch()']) !!}
          @if(\Auth::user()->email == 'admin')
            {!! Form::select('distributor', getDistributorLists(), $distributor_id, ['class' => 'filter-select select2', 'id' => 'distributor', 'onchange' => 'advanceSearch()']) !!}
          @endif
        </div>

        <!-- TABLE TOOLBAR -->
        <div class="table-toolbar">
          <div class="sort-controls" style="overflow-x: auto">
            <span>Urutkan:</span>
            @foreach(getGoodSort() as $key => $value)
              <button class="sort-btn @if($sort == $key) active @endif" onclick="advanceSearch('{{ $key }}')">{{ $value }} @if($order == 'asc') ↑ @else ↓ @endif</button>
            @endforeach
          </div>
          <div style="display:flex;align-items:center;gap:10px;">
            {!! Form::select('show', getPaginations(), $pagination, ['class' => 'filter-select', 'style'=>'min-width:unset;width:auto;', 'id' => 'show', 'onchange' => 'advanceSearch()']) !!}
          </div>
        </div>

        <!-- PRODUCT LIST -->
        <div class="product-list" id="productList">

          @foreach($goods as $good)
            <div class="product-card">
              <input type="checkbox" class="product-checkbox">
              <div class="product-info">
                <div class="product-name">[{{ $good->getType() }}] {{ $good->name }}</div>
                <div class="product-meta">
                  <span class="category-badge" style="background-color: @if($good->category->color == null) #ede8ff @else {{ $good->category->color }} @endif">
                    <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>
                    {{ $good->category->name }}
                  </span>
                  @if($good->brand != null) 
                    <span class="category-badge plastik">
                      <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>
                      Brand: {{ $good->brand->name }}
                    </span>
                  @endif
                  <span class="product-code">{{ $good->code }}</span>
                  @if(\Auth::user()->email == 'admin')
                    <span class="distributor-info">
                      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                      {{ $good->getDistributor()->name }} @if($good->getLastBuy() != null) {{ ' (' . $good->getLastBuy()->good_loading->note . ')' }} @endif
                    </span>
                  @endif
                </div>
              </div>
              <div class="stock-col">
                <div class="stock-main @if($good->last_stock == 0) zero @elseif($good->last_stock <= 10) low @else in @endif">{{ $good->last_stock }}</div>
                <div class="stock-unit">{{ $good->base_unit()->unit->code }}</div>
                <div class="stock-breakdown">
                  <span class="stock-pill" data-tip="Loading">
                    <!-- <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><rect x="2" y="7" width="20" height="13" rx="2"/><path d="M16 21V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v16"/></svg>  -->
                    <a href="{{ url($role . '/good/' . $good->id . '/loading/2023-01-01/' . date('Y-m-d') . '/10') }}" target="_blank()">L: {{ checkNull($good->total_loading) }}</a>
                  </span>
                  <span class="stock-pill" data-tip="Transaksi">
                    <!-- <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg> -->
                    <a href="{{ url($role . '/good/' . $good->id . '/transaction/2023-01-01/' . date('Y-m-d') . '/10') }}" target="_blank()">T: {{ $good->total_transaction }}</a>
                  </span>
                </div>
              </div>
              <div class="price-col">
                <!-- <div class="price-label">HARGA JUAL</div> -->
                @foreach($good->good_units as $unit)
                  <div class="price-sell">{{ showRupiah($unit->selling_price) . ' / ' . $unit->unit->name }}</div>
                  @if(\Auth::user()->email == 'admin')
                    <div class="price-buy">Beli: {{ showRupiah($unit->buy_price) }}</div>
                    <div class="profit-badge">
                      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                      {{ showRupiah($unit->selling_price - $unit->buy_price) . ' (' . calculateProfit($unit->buy_price, $unit->selling_price) }}%)
                    </div>
                    
                      <div class="price-buy">
                        <button type="button" class="action-btn danger" data-toggle="modal" data-target="#modal-danger-unit-{{$unit->id}}">Hapus harga</button>
                      </div>

                      @include('layout' . '.delete-modal', ['id' => 'unit-' . $unit->id, 'data' => 'Harga ' . $good->name . ' ' . $unit->unit->name, 'formName' => 'delete-unit-' . $unit->id])

                      <form id="delete-unit-{{$unit->id}}" action="{{ url($role . '/good/' . $good->id . '/deletePrice/' . $unit->id) }}" method="POST" style="display: none;">
                        {{ csrf_field() }}
                        {{ method_field('DELETE') }}
                      </form><br>
                  @endif
                @endforeach   
                <div class="price-buy">
                  <a href="{{ url($role . '/good/' . $good->id . '/price/2023-01-01/' . date('Y-m-d') . '/10') }}" target="_blank()">
                    <button class="action-btn primary" style="font-size:11.5px;">Riwayat harga jual</button>
                  </a>    
                </div>   
              </div>
              <div class="activity-col">
                <span class="never-tag">
                  @if($good->last_loading == null)
                    Belum pernah loading
                  @else
                    Load: {{ displayDate($good->last_loading) }}
                  @endif
                  <br>
                  @if($good->last_transaction == null)
                    Belum ada transaksi
                  @else
                    Trx: {{ displayDate($good->last_transaction) }}
                  @endif
                </span>
              </div>
              <div class="actions-col">
                <div class="action-row">
                  <a href="{{ url($role . '/good/' . $good->id . '/edit') }}" target="_blank()">
                    <button class="action-btn primary">
                      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                      Edit
                    </button>
                  </a>
                  <a href="{{ url($role . '/good/' . $good->id . '/detail') }}" target="_blank()">
                    <button class="action-btn ghost">
                      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                      Detail
                    </button>
                  </a>
                  @if($good->last_stock == 0 && $role == 'admin')
                    <button class="action-btn danger icon-only" data-tip="Hapus" data-toggle="modal" data-target="#modal-danger-{{$good->id}}">
                      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                    </button>
                    @include('layout' . '.delete-modal', ['id' => $good->id, 'data' => $good->name, 'formName' => 'delete-form-' . $good->id])

                    <form id="delete-form-{{$good->id}}" action="{{ url($role . '/good/' . $good->id . '/delete') }}" method="POST" style="display: none;">
                      {{ csrf_field() }}
                      {{ method_field('DELETE') }}
                    </form>
                  @endif
                </div>
              </div>
            </div>
          @endforeach
          <!-- CARD 1 -->

        </div><!-- /product-list -->
        <!-- PAGINATION -->
        <div class="pagination-wrap">
          <div class="pagination-info">
            Halaman <strong>{{ $goods->currentPage() }}</strong> dari <strong>{{ $goods->lastPage() }}</strong> · Total <strong>{{ $goods->total() }}</strong> produk
          </div>

          @if($pagination != 'all')
            {{ $goods->render() }}
          @endif
        </div>
      </div>
    </div>
  </section>
</div>

@section('js-addon')
  <script type="text/javascript">
    $(document).ready(function(){
        $('.select2').select2();
        $("#searchInput").keyup( function(e){
          if(e.keyCode == 13)
          {
            ajaxFunction();
          }
        });

        $("#btnSearch").click(function(){
            ajaxFunction();
        });
    });

  function ajaxFunction()
  {
    $.ajax({
      url: "{!! url($role . '/good/searchByKeyword/') !!}/" + $("#searchInput").val(),
      success: function(result){
        if(result != null)
        {
          var r = result.goods;
          var username = "{{ \Auth::user()->email }}";
          var role = "{{ $role }}";
          var htmlResult = '';
          for (var i = 0; i < r.length; i++) 
          {
            htmlResult += '<div class="product-card"><input type="checkbox" class="product-checkbox"><div class="product-info"><div class="product-name">[ ' + r[i].good_type + '] ' + r[i].name + '</div><div class="product-meta"><span class="category-badge" style="background-color: ';

            if(r[i].category.color == null)
              htmlResult += '#ede8ff';
            else
              htmlResult += r[i].category.color;

            htmlResult += '"><svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>' + r[i].category.name + '</span>';

            if(r[i].brand_name != null)
            {
              htmlResult += '<span class="category-badge plastik"><svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>Brand: ' + r[i].brand_name + '</span>';
            }
            
            htmlResult += '<span class="product-code">' + r[i].code + '</span>';

            if(username == 'admin')
            {
              htmlResult += '<span class="distributor-info"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>' + r[i].last_loading + '</span>';
            }
                
            htmlResult += '</div></div><div class="stock-col"><div class="stock-main ';

            if(r[i].stock == 0)
              htmlResult += 'zero';
            else if(r[i].stock <= 10)
              htmlResult += 'low';
            else
              htmlResult += 'in';
            
            htmlResult += '">' + r[i].stock + '</div><div class="stock-unit">' + r[i].unit + '</div><div class="stock-breakdown"><a href=\"' + window.location.origin + "/" + role + "/good/" + r[i].id + "/loading/2023-01-01/" + "{{ date('Y-m-d') }}" + "/10\" target=\"_blank()\"><span class=\"stock-pill\" data-tip=\"Loading\">L: " + r[i].loading + '</span></a><a href=\"' + window.location.origin + "/" + role + "/good/" + r[i].id + "/transaction/2023-01-01/" + "{{ date('Y-m-d') }}" + "/10\" target=\"_blank()\"><span class=\"stock-pill\" data-tip=\"Transaksi\">T: " + r[i].transaction + '</span></a></div></div><div class="price-col">';

            for (var j = 0; j < r[i].good_units.length; j++) {
              htmlResult += '<div class="price-sell">' + r[i].good_units[j].price + " /" + r[i].good_units[j].unit_name + '</div>';

              if(username == 'admin')
              {
                htmlResult += "<div class='price-buy'>Beli: " + r[i].good_units[j].buy_price + '</div><div class="profit-badge"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>' + r[i].good_units[j].profit + ' (' + r[i].good_units[j].percentage + "%)</div><div class=\"price-buy\"><button type=\"button\" class=\"action-btn danger\" onclick=\"event.preventDefault(); document.getElementById('delete-unit-" + r[i].good_units[j].id + "').submit();\">Hapus harga</button></div>";

                // htmlResult += "<div class=\"modal modal-danger fade\" id=\"#modal-danger-unit-" + r[i].good_units[j].id + "\"><div class=\"modal-dialog\"><div class=\"modal-content\"><div class=\"modal-header\"><button type=\"button\" class=\"close\" data-dismiss=\"modal\" aria-label=\"Close\"><span aria-hidden=\"true\">&times;</span></button><h4 class=\"modal-title\">Hapus harga " + r[i].name + ' ' + r[i].good_units[j].unit_name + "</h4></div><div class=\"modal-body\"><p>Anda yakin ingin menghapus harga " + r[i].name + ' ' + r[i].good_units[j].unit_name + "?</p></div><div class=\"modal-footer\"><button type=\"button\" class=\"btn btn-outline pull-left\" data-dismiss=\"modal\">Close</button><button type=\"button\" class=\"btn btn-outline\" onclick=\"event.preventDefault(); document.getElementById('delete-unit-" + r[i].good_units[j].id + "').submit();\">Hapus</button></div></div></div></div>";

                htmlResult += '<form id="delete-unit-' + r[i].good_units[j].id + '" action=\"' + window.location.origin + "/" + role + "/good/" + r[i].id + '/deletePrice/' + r[i].good_units[j].id + '\" method="POST" style="display: none;">{{ csrf_field() }}<input type="hidden" name="_method" value="DELETE"></form><br>';
              }
            }

            htmlResult += '<div class="price-buy"><a href=\"' + window.location.origin + "/" + role + "/good/" + r[i].id + "/price/2023-01-01/" + "{{ date('Y-m-d') }}" + '/10\" target=\"_blank()\"><button class="action-btn primary" style="font-size:11.5px;">Riwayat harga jual</button></a></div></div><div class="activity-col"><span class="never-tag">';

            if(r[i].last_loading_date == null)
              htmlResult += 'Belum pernah loading';
            else
              htmlResult += 'Load: ' + r[i].last_loading_date;
            
            htmlResult += '<br>';

            if(r[i].last_transaction_date == null)
              htmlResult += 'Belum ada transaksi';
            else
              htmlResult += 'Trx: ' + r[i].last_transaction_date;

            htmlResult += "</span></div><div class='actions-col'><div class='action-row'><a href=\"" + window.location.origin + "/" + role + "/good/" + r[i].id +"/edit\" target=\"_blank()\"><button class='action-btn primary'><svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'><path d='M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7'/><path d='M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z'/></svg>Edit</button></a><a href=\"" + window.location.origin + "/" + role + "/good/" + r[i].id + "/detail\" target=\"_blank()\"><button class='action-btn ghost'><svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'><path d='M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z'/><circle cx='12' cy='12' r='3'/></svg>Detail</button></a>";

            if(role == 'admin' && r[i].stock == 0)
            {
              htmlResult += "<button class='action-btn danger icon-only' data-tip='Hapus' data-toggle='modal' data-target='#modal-danger-" + r[i].id + "'><svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'><polyline points='3 6 5 6 21 6'/><path d='M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6'/><path d='M10 11v6M14 11v6'/><path d='M9 6V4h6v2'/></svg></button>";

              htmlResult += "<div class=\"modal modal-danger fade\" id=\"modal-danger-" + r[i].id + "\"><div class=\"modal-dialog\"><div class=\"modal-content\"><div class=\"modal-header\"><button type=\"button\" class=\"close\" data-dismiss=\"modal\" aria-label=\"Close\"><span aria-hidden=\"true\">&times;</span></button><h4 class=\"modal-title\">Hapus barang " + r[i].name + "</h4></div><div class=\"modal-body\"><p>Anda yakin ingin menghapus barang " + r[i].name + "?</p></div><div class=\"modal-footer\"><button type=\"button\" class=\"btn btn-outline pull-left\" data-dismiss=\"modal\">Close</button><button type=\"button\" class=\"btn btn-outline\" onclick=\"event.preventDefault(); document.getElementById('delete-" + r[i].id + "').submit();\">Hapus</button></div></div></div></div>";

              htmlResult += '<form id="delete-' + r[i].id + '" action=\"' + window.location.origin + "/" + role + "/good/" + r[i].id + '/delete\" method="POST" style="display: none;">{{ csrf_field() }}<input type="hidden" name="_method" value="DELETE"></form><br>';
            }
            htmlResult += "</div></div></div>";
          }

          if(htmlResult == '')
          {
            htmlResult = '<div class="empty-state" id="emptyState"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="8" y1="11" x2="14" y2="11"/></svg><p>Produk tidak ditemukan.<br>Coba kata kunci lain.</p></div>';
          }
        }        

        $("#productList").html(htmlResult);
      },
      error: function(){
          console.log('error');
      }
    });
  }

  function advanceSearch(key_sort = null)
  {
    if(key_sort == null)
      key_sort = '{{ $sort }}';

    key_ord = '{{ $order }}';

    if(key_sort == '{{ $sort }}')
      if('{{ $order }}' == 'asc')
        key_ord = 'desc';
      else
        key_ord = 'asc';

    var username = "{{ \Auth::user()->email }}";

    if(username == 'admin')
      window.location = window.location.origin + '/{{ $role }}/good/' + $('#category').val() + '/' + $('#type').val() + '/' + $('#distributor').val() + '/' + key_sort + '/' + key_ord + '/' + $('#show').val();
    else
      window.location = window.location.origin + '/{{ $role }}/good/' + $('#category').val() + '/' + $('#type').val() + '/all/goods.id/desc/' + $('#show').val();
  }

  function clearInput()
  {
    document.getElementById("searchInput").value = "";
    $("#searchInput").focus();
  }
  </script>
@endsection