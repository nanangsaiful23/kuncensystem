<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" type="image/gif" href="{{asset('assets/icon/education.png')}}" />
<title>Tambah Transaksi</title>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&family=IBM+Plex+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
  :root {
    --bg: #f0f2f7; /*0f1117;*/
    --surface: #ffffff; /*#1a1d27;*/
    --surface2: #f5f6fa; /*#22263a;*/
    --surface3: #eaecf4;
    --border: #2e3450;
    --accent: #A47251; /*#00e5a0;*/
    --accent-dim: #FFC193; /*#00e5a020;*/
    --accent2: #4f9eff;
    --accent2-dim: #4f9eff18;
    --danger: #ff4f6e;
    --danger-dim: #ff4f6e18;
    --muted: #000000;
    --warn: #ffc13d;
    --warn-dim: #ffc13d18;
    --text: #000000;
    --text-muted: #7a839a;
    --text-faint: #444c68;
    --mono: 'IBM Plex Mono', monospace;
    --sans: 'IBM Plex Sans', sans-serif;
    --radius: 10px;
    --shadow: 0 4px 24px rgba(0,0,0,0.5);
  }

  * { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    background: var(--bg);
    color: var(--text);
    font-family: var(--sans);
    height: 100vh;
    display: flex;
    flex-direction: column;
    overflow: hidden;
  }

  /* Search bar */
  .search-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
  }
  .input-group {
    display: flex; flex-direction: column; gap: 5px;
  }
  .input-group label {
    font-size: 11px; font-weight: 600; text-transform: uppercase;
    letter-spacing: 0.8px; color: var(--accent);
  }
  .input-wrap {
    position: relative;
    display: flex; align-items: center;
  }
  .input-wrap svg {
    position: absolute; left: 12px; color: var(--muted);
    pointer-events: none; width: 16px; height: 16px;
  }
  input[type="text"] {
    width: 100%; background: var(--surface);
    border: 1px solid var(--border); color: var(--text);
    font-family: 'DM Mono', monospace; font-size: 13px;
    padding: 10px 12px 10px 36px;
    border-radius: var(--radius); outline: none;
    transition: border-color 0.2s, box-shadow 0.2s;
  }
  input[type="text"]:focus {
    border-color: var(--accent);
    background: var(--accent-dim);
    box-shadow: 0 0 0 3px rgba(0,229,160,0.1);
  }
  input[type="text"]::placeholder { color: var(--muted); }

  /* ── TOP BAR ─────────────────────────────── */
  .topbar {
    background: var(--surface);
    border-bottom: 1px solid var(--border);
    padding: 0 20px;
    height: 52px;
    display: flex;
    align-items: center;
    gap: 20px;
    flex-shrink: 0;
  }

  .topbar a { text-decoration: none;}

  .topbar-brand {
    font-family: var(--mono);
    font-weight: 700;
    font-size: 15px;
    color: var(--accent);
    letter-spacing: 1px;
    white-space: nowrap;
  }

  .topbar-sep { width: 1px; height: 24px; background: var(--border); }
  .topbar-hint {
    font-size: 11px;
    color: var(--text-muted);
    font-family: var(--mono);
    display: flex; gap: 16px;
  }
  .hotkey {
    display: inline-flex; align-items: center; gap: 5px;
  }
  .hotkey kbd {
    background: var(--surface3);
    border: 1px solid var(--border);
    border-radius: 4px;
    padding: 1px 6px;
    font-size: 10px;
    font-family: var(--mono);
    color: var(--accent);
  }
  .topbar-clock {
    margin-left: auto;
    font-family: var(--mono);
    font-size: 14px;
    font-weight: 600;
    color: var(--text-muted);
  }

  /* ── MAIN LAYOUT ─────────────────────────── */
  .main {
    display: flex;
    flex: 1;
    overflow: hidden;
    gap: 0;
  }

  /* ── LEFT: ITEMS TABLE ───────────────────── */
  .items-panel {
    flex: 1;
    display: flex;
    flex-direction: column;
    border-right: 1px solid var(--border);
    overflow: hidden;
  }

  .items-header {
    background: var(--surface);
    border-bottom: 1px solid var(--border);
    padding: 10px 16px;
    display: flex;
    align-items: center;
    gap: 10px;
    flex-shrink: 0;
  }
  .items-header h2 {
    font-size: 12px;
    font-weight: 600;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: var(--text-muted);
  }
  .item-count-badge {
    background: var(--accent-dim);
    color: var(--accent);
    border: 1px solid var(--accent);
    border-radius: 20px;
    padding: 1px 9px;
    font-family: var(--mono);
    font-size: 11px;
    font-weight: 700;
  }

  .table-wrap {
    flex: 1;
    overflow-y: auto;
    overflow-x: auto;
  }

  .table-wrap::-webkit-scrollbar { width: 6px; height: 6px; }
  .table-wrap::-webkit-scrollbar-track { background: var(--surface); }
  .table-wrap::-webkit-scrollbar-thumb { background: var(--border); border-radius: 3px; }
  .table-wrap::-webkit-scrollbar-thumb:hover { background: var(--surface3); }

  table {
    width: 100%;
    min-width: 680px;
    border-collapse: collapse;
    table-layout: fixed;
  }

  thead tr {
    background: var(--surface2);
    position: sticky;
    top: 0;
    z-index: 2;
  }

  thead th {
    padding: 10px 10px;
    font-family: var(--mono);
    font-size: 10px;
    font-weight: 600;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    color: var(--text-muted);
    border-bottom: 2px solid var(--border);
    text-align: left;
    white-space: nowrap;
  }
  thead th.right { text-align: right; }
  thead th.center { text-align: center; }

  tbody tr {
    border-bottom: 1px solid var(--border);
    transition: background 0.15s;
  }
  tbody tr:nth-child(even) { background: #F2EAE0; }
  tbody tr:hover { background: var(--surface2); }
  tbody tr.row-highlight { background: #162a23 !important; }

  td {
    padding: 5px 8px;
    vertical-align: middle;
    font-size: 13px;
  }
  td.num {
    font-family: var(--mono);
    color: var(--text-muted);
    text-align: center;
    width: 40px;
  }
  td.name-cell { width: 28%; }
  td.unit-cell { width: 8%; }
  td.price-cell, td.total-cell, td.sum-cell { text-align: right; }

  .td-input {
    background: transparent;
    border: 1px solid transparent;
    border-radius: 6px;
    color: var(--text);
    font-family: var(--sans);
    font-size: 13px;
    font-weight: 600;
    width: 100%;
    padding: 5px 7px;
    transition: all 0.15s;
    outline: none;
  }
  .td-input:focus {
    border-color: var(--accent2);
    background: var(--accent2-dim);
  }
  .td-input[readonly] {
    color: var(--text-muted);
    cursor: default;
  }
  .td-input.qty-input {
    background: var(--accent-dim);
    border-color: #3a5a28;
    color: var(--accent);
    text-align: center;
    font-weight: 700;
    font-family: var(--mono);
    width: 70px;
  }
  .td-input.qty-input:focus {
    border-color: var(--accent-dim);
    background: var(--bg);
  }
  .td-input.disc-input {
    text-align: right;
    width: 100px;
    color: var(--text);
    font-family: var(--mono);
  }
  .td-input.price-ro {
    text-align: right;
    color: var(--text);
    font-family: var(--mono);
  }
  .td-input.sum-ro {
    text-align: right;
    color: var(--accent);
    font-family: var(--mono);
    font-weight: 700;
  }

  .item-name {
    font-weight: 600;
    color: var(--text);
    font-size: 13px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  .unit-badge {
    display: inline-block;
    font-size: 12px;
    font-family: var(--mono);
    background: var(--surface);
    border: 1px solid var(--border);
    padding: 1px 6px;
    border-radius: 4px;
    color: var(--text);
  }

  .delete-btn {
    background: none;
    border: none;
    cursor: pointer;
    color: var(--text-faint);
    font-size: 16px;
    padding: 4px 6px;
    border-radius: 5px;
    transition: all 0.15s;
    line-height: 1;
  }
  .delete-btn:hover {
    color: var(--danger);
    background: var(--danger-dim);
  }

  .empty-row td {
    text-align: center;
    padding: 40px;
    color: var(--text-faint);
    font-size: 13px;
    font-style: italic;
  }

  /* ── RIGHT: CONTROL PANEL ────────────────── */
  .ctrl-panel {
    width: 300px;
    flex-shrink: 0;
    background: var(--surface);
    display: flex;
    flex-direction: column;
    overflow-y: auto;
    overflow-x: hidden;
  }
  .ctrl-panel::-webkit-scrollbar { width: 4px; }
  .ctrl-panel::-webkit-scrollbar-thumb { background: var(--border); }

  .ctrl-section {
    border-bottom: 1px solid var(--border);
    padding: 14px 16px;
  }
  .ctrl-section-label {
    font-size: 10px;
    font-family: var(--mono);
    letter-spacing: 2px;
    text-transform: uppercase;
    color: var(--text-faint);
    margin-bottom: 10px;
  }

  .field-group { margin-bottom: 10px; }
  .field-label {
    font-size: 11px;
    font-weight: 600;
    color: var(--text-muted);
    margin-bottom: 5px;
    display: flex;
    align-items: center;
    gap: 6px;
  }
  .field-label .shortcut {
    font-family: var(--mono);
    font-size: 9px;
    color: var(--text-faint);
  }
  .ctrl-input {
    width: 100%;
    background: var(--surface2);
    border: 1.5px solid var(--border);
    border-radius: 8px;
    color: var(--text);
    font-family: var(--sans);
    font-size: 14px;
    font-weight: 500;
    padding: 9px 12px;
    outline: none;
    transition: all 0.2s;
  }
  .ctrl-input:focus {
    border-color: var(--accent2);
    background: var(--accent2-dim);
    box-shadow: 0 0 0 3px rgba(79,158,255,0.1);
  }
  .ctrl-input.barcode-input:focus {
    border-color: var(--accent);
    background: var(--accent-dim);
    box-shadow: 0 0 0 3px rgba(0,229,160,0.1);
  }

  .action-row {
    display: flex;
    gap: 8px;
    margin-top: 8px;
  }
  .btn {
    flex: 1;
    padding: 8px 12px;
    border: none;
    border-radius: 7px;
    cursor: pointer;
    font-family: var(--sans);
    font-weight: 600;
    font-size: 12px;
    transition: all 0.15s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
  }
  .btn-secondary {
    background: var(--surface3);
    color: var(--text-muted);
    border: 1px solid var(--border);
  }
  .btn-secondary:hover { background: var(--border); color: var(--text); }
  .btn-accent {
    background: var(--accent-dim);
    color: var(--accent);
    border: 1px solid var(--accent);
  }
  .btn-accent:hover { background: var(--accent); color: #000; }
  .btn-warn {
    background: var(--warn-dim);
    color: var(--warn);
    border: 1px solid var(--warn);
  }
  .btn-warn:hover { background: var(--warn); color: #000; }

  /* Alert box */
  .alert-box {
    background: var(--danger-dim);
    border: 1px solid var(--danger);
    border-radius: 8px;
    padding: 10px 12px;
    font-size: 12px;
    color: var(--danger);
    display: none;
    margin-bottom: 10px;
  }

  /* Payment method toggle */
  .payment-toggle {
    display: flex;
    gap: 6px;
  }
  .pay-opt {
    flex: 1;
    padding: 8px;
    border-radius: 7px;
    border: 1.5px solid var(--border);
    background: var(--surface2);
    color: var(--text-muted);
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    text-align: center;
    transition: all 0.15s;
  }
  .pay-opt.active {
    border-color: var(--accent2);
    background: var(--accent2-dim);
    color: var(--accent2);
  }
  .pay-opt input { display: none; }

  /* Checkbox */
  .check-row {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: var(--text-muted);
    cursor: pointer;
  }
  .check-row input[type=checkbox] {
    width: 16px; height: 16px;
    accent-color: var(--accent2);
    cursor: pointer;
  }

  /* ── SUMMARY SECTION ─────────────────────── */
  .summary-section {
    padding: 14px 16px;
    border-bottom: 1px solid var(--border);
  }
  .summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 5px 0;
    font-size: 12px;
  }
  .summary-row .s-label { color: var(--text-muted); }
  .summary-row .s-val {
    font-family: var(--mono);
    font-weight: 600;
    color: var(--text);
  }
  .summary-row.divider { border-top: 1px solid var(--border); margin-top: 5px; padding-top: 10px; }
  .summary-row.total-row .s-label {
    font-size: 13px;
    font-weight: 700;
    color: var(--text);
  }
  .summary-row.total-row .s-val {
    font-size: 18px;
    color: var(--danger);
    font-weight: 700;
  }

  /* Discount + Pay + Change */
  .pay-section {
    padding: 14px 16px;
    border-bottom: 1px solid var(--border);
  }
  .pay-input-row {
    display: flex;
    flex-direction: column;
    gap: 10px;
  }
  .pay-field {
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .pay-field .pay-label {
    font-size: 11px;
    font-weight: 600;
    color: var(--text-muted);
    width: 80px;
    flex-shrink: 0;
  }
  .pay-input {
    flex: 1;
    background: var(--surface2);
    border: 1.5px solid var(--border);
    border-radius: 8px;
    color: var(--text);
    font-family: var(--mono);
    font-size: 15px;
    font-weight: 700;
    padding: 8px 10px;
    outline: none;
    text-align: right;
    transition: all 0.2s;
  }
  .pay-input:focus {
    border-color: var(--warn);
    background: var(--warn-dim);
  }
  .pay-input.total-display {
    background: #1e0a14;
    border-color: var(--danger);
    color: var(--danger);
    cursor: default;
    font-size: 17px;
  }
  .pay-input.change-display {
    background: #0d1e16;
    border-color: var(--accent);
    color: var(--accent);
    cursor: default;
    font-size: 16px;
  }
  .pay-input.disc-input-main {
    border-color: var(--warn);
    color: var(--warn);
  }

  /* Totals bar */
  .totals-bar {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
  }
  .total-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 14px 16px;
  }
  .total-card .tc-label {
    font-size: 11px; font-weight: 600; text-transform: uppercase;
    letter-spacing: 0.7px; color: var(--muted); margin-bottom: 6px;
  }
  .total-card .tc-value {
    font-family: 'DM Mono', monospace; font-size: 18px; font-weight: 500;
  }

  .total-card .tc-value-input{
    font-family: 'DM Mono', monospace; font-size: 18px; font-weight: 500;
    flex: 1;
    background: var(--surface2);
    border: 1.5px solid var(--border);
    border-radius: 8px;
    color: var(--text);
    font-family: var(--mono);
    font-size: 15px;
    font-weight: 700;
    padding: 8px 10px;
    outline: none;
    text-align: right;
    transition: all 0.2s;
    width: 100%;
  }
  .tc-value-input:focus {
    border-color: var(--accent);
    background: var(--accent-dim);
  }
  .total-card.highlight {
    background: rgba(108,99,255,0.1);
    border-color: rgba(108,99,255,0.4);
  }
  .total-card.highlight .tc-value { color: var(--accent); }
  .total-card.bayar .tc-value { color: var(--yellow); }

  /* ── SUBMIT BUTTON ───────────────────────── */
  .submit-area {
    padding: 16px;
    margin-top: auto;
  }
  .btn-submit {
    width: 100%;
    padding: 16px;
    background: linear-gradient(135deg, #00e5a0, #00b87a);
    border: none;
    border-radius: 10px;
    color: #000;
    font-family: var(--sans);
    font-size: 16px;
    font-weight: 800;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
    box-shadow: 0 4px 20px rgba(0,229,160,0.25);
  }
  .btn-submit:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 28px rgba(0,229,160,0.4);
  }
  .btn-submit:active { transform: translateY(0); }
  .btn-submit:disabled {
    opacity: 0.4;
    cursor: not-allowed;
    transform: none;
  }
  .btn-submit .sub-text {
    font-size: 11px;
    font-weight: 500;
    opacity: 0.7;
    font-family: var(--mono);
  }

  /* ── MODAL ───────────────────────────────── */
  .modal-overlay {
    display: none;
    position: fixed; inset: 0;
    background: rgba(0,0,0,0.7);
    z-index: 100;
    align-items: center;
    justify-content: center;
  }
  .modal-overlay.show { display: flex; }
  .modal-box {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 12px;
    width: 480px;
    max-width: 90vw;
    max-height: 70vh;
    display: flex;
    flex-direction: column;
    box-shadow: var(--shadow);
    animation: popIn 0.18s ease;
  }
  @keyframes popIn {
    from { transform: scale(0.93); opacity: 0; }
    to   { transform: scale(1); opacity: 1; }
  }
  .modal-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 14px 18px;
    border-bottom: 1px solid var(--border);
  }
  .modal-head h3 {
    font-size: 14px;
    font-weight: 700;
    color: var(--text);
  }
  .modal-close {
    background: none; border: none; cursor: pointer;
    color: var(--text-muted); font-size: 20px; line-height: 1;
    transition: color 0.15s;
  }
  .modal-close:hover { color: var(--danger); }
  .modal-body {
    overflow-y: auto;
    padding: 12px;
    display: flex;
    flex-direction: column;
    gap: 5px;
    flex: 1;
  }
  .modal-body::-webkit-scrollbar { width: 4px; }
  .modal-body::-webkit-scrollbar-thumb { background: var(--border); }

  .modal-item {
    padding: 10px 12px;
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border: 1px solid var(--border);
    transition: all 0.12s;
  }
  .modal-item:hover { background: var(--surface2); border-color: var(--accent2); }
  .modal-item.out-of-stock { font-size: 14px; opacity: 1; color: var(--text);}
  .modal-item.item-iname { font-size: 14px; font-weight: 600; color: var(--text); }
  .modal-item.item-meta { font-size: 14px; color: var(--text); margin-top: 2px; }
  .stock-chip {
    font-size: 10px; font-family: var(--mono); font-weight: 700;
    padding: 2px 8px; border-radius: 20px;
  }
  .stock-chip.good { background: #FFB090; color: var(--accent); font-size: 13px}
  .stock-chip.low  { background: rgba(255,193,61,0.15); color: #4B2E2B; }
  .stock-chip.empty { background: rgba(255,79,110,0.15); color: var(--danger); }

  /* camera preview */
  #preview {
    width: 100%; border-radius: 8px;
    border: 1px solid var(--border);
    display: none;
    margin-bottom: 8px;
  }

  /* Voucher row */
  .voucher-row {
    display: flex; gap: 6px;
  }
  .voucher-row .ctrl-input { flex: 1; }
  .btn-check-voucher {
    background: var(--surface3);
    border: 1px solid var(--border);
    color: var(--text-muted);
    padding: 9px 12px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.15s;
  }
  .btn-check-voucher:hover {
    border-color: var(--accent2);
    color: var(--accent2);
  }
  .voucher-result {
    font-size: 12px; font-family: var(--mono);
    padding: 7px 10px; border-radius: 6px; display: none;
    margin-top: 5px;
  }
  .voucher-result.ok { background: rgba(0,229,160,0.12); color: var(--accent); border: 1px solid var(--accent); }
  .voucher-result.fail { background: var(--danger-dim); color: var(--danger); border: 1px solid var(--danger); }

  /* ── QUICK DEMO INTERACTIONS ─────────────── */
  .demo-notice {
    background: linear-gradient(135deg, #00e5a0, #00b87a);
    border: 1px solid var(--warn);
    border-radius: 7px;
    padding: 8px 12px;
    font-size: 20px;
    color: var(--text);
    font-family: var(--mono);
    text-align: center;
    margin-bottom: 8px;
  }
</style>
</head>

{!! Form::model(old(),array('url' => route($role . '.transaction.store'), 'enctype'=>'multipart/form-data', 'method' => 'POST', 'id' => 'transaction-form')) !!}
<body>

<!-- TOP BAR -->
<div class="topbar">
  <a href="{{ url('/admin/' ) }}"><span class="topbar-brand">▣ {{ config('app.name') }}</span></a>
  <div class="topbar-sep"></div>
  <div class="topbar-hint">
    <span class="hotkey"><kbd>F2</kbd> Barcode</span>
    <span class="hotkey"><kbd>F4</kbd> Keyword</span>
    <span class="hotkey"><kbd>F8</kbd> Bayar</span>
  </div>
  <div class="topbar-clock" id="clock">--:--:--</div>
</div>

<!-- MAIN -->
<div class="main">

  <!-- ITEMS PANEL -->
  <div class="items-panel" style=" height: 90vh !important; overflow-y: auto;">

    <!-- Search row -->
    <div class="search-row" style="margin-top: 10px; margin-bottom: 10px;">
      <div class="input-group">
        <label>Barcode</label>
        <div class="input-wrap">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="2" height="16"/><rect x="7" y="4" width="1" height="16"/><rect x="10" y="4" width="2" height="16"/><rect x="14" y="4" width="1" height="16"/><rect x="17" y="4" width="2" height="16"/></svg>
          <input type="text" id="all_barcode" placeholder="Scan atau ketik barcode…" autofocus onchange="searchByBarcode('all_barcode')">
        </div>
      </div>
      <div class="input-group">
        <label>Cari Produk</label>
        <div class="input-wrap">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
          <input type="text" id="search_good" placeholder="Nama produk…" name="search_good">
        </div>
      </div>
    </div>

    <div class="items-header">
      <h2>Daftar Barang</h2>
      <span class="item-count-badge" id="item-count-badge">0 item</span>
    </div>
    <div class="table-wrap" id="div-good">
      <table id="main-table">
        <thead>
          <tr>
            <th style="width:40px" class="center">#</th>
            <th style="width:28%">Nama Barang</th>
            <th style="width:8%">Satuan</th>
            <th style="width:7%" class="center">Qty</th>
            <th style="width:10%" class="right">Harga</th>
            <th style="width:8%" class="right">Pot/Brg</th>
            <th style="width:9%" class="right">Tot Pot</th>
            <th style="width:11%" class="right">Total Harga</th>
            <th style="width:12%" class="right">Total Akhir</th>
            <th style="width:36px" class="center">✕</th>
          </tr>
        </thead>
        <tbody id="table-transaction">
          <tr class="empty-row" id="empty-row-msg">
            <td colspan="10">Belum ada barang — scan barcode atau cari dengan keyword</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Totals -->
    <div class="totals-bar">
      <!-- div class="total-card highlight">
        <div class="tc-label">Total Item</div>
        <div class="tc-value" id="div_tot_items">0</div>
      </div> -->
      <div class="total-card">
        <div class="tc-label">Potongan Akhir</div>
          <input class="tc-value-input" id="total_discount_price" name="total_discount_price" placeholder="0" onkeyup="formatNumber('total_discount_price'); changeTotal()" onfocusout="checkIsPromo()">
      </div>
      <div class="total-card bayar">
        <div class="tc-label">Bayar</div>
          <input class="tc-value-input" id="money_paid" name="money_paid" placeholder="0" onkeyup="formatNumber('money_paid'); changeReturn()">
      </div>
    </div>
  </div>

  <!-- CONTROL PANEL -->
  <div class="ctrl-panel">

    <!-- Alert -->
    <div style="padding:14px 16px 0">
      <div class="alert-box" id="message">
        ⚠ Stok kosong: <span id="empty-item"></span>
      </div>
    </div>

    <!-- SCAN / SEARCH -->
    <div class="ctrl-section">
      <div class="field-group">
        <div class="field-label">Member</div>
        <input type="text" class="ctrl-input" id="search_member"
               name="search_member"
               placeholder="Cari nama member...">
        <input type="hidden" name="member_id" id="member_id" value="1">
      </div>

      <!-- <div class="action-row">
        <button class="btn btn-warn" onclick="startCamera()">📷 Scan Member</button>
        <button class="btn btn-secondary" onclick="stopCamera()">✕ Stop</button>
      </div> -->
    </div>

    <!-- MEMBER & NOTE -->
    <div class="ctrl-section">
      <!-- <div class="ctrl-section-label">Member &amp; Keterangan</div> -->

      <div class="field-group">
        <div class="field-label">Keterangan</div>
        <input type="text" class="ctrl-input" name="note" placeholder="Catatan transaksi...">
      </div>

      <div class="field-group">
        <div class="field-label">Pembayaran</div>
        <div class="payment-toggle">
          <label class="pay-opt active" id="opt-cash" onclick="setPayment('cash', this)">
            <input type="radio" name="payment" value="cash" checked> 💵 Cash
          </label>
          <label class="pay-opt" id="opt-transfer" onclick="setPayment('transfer', this)">
            <input type="radio" name="payment" value="transfer"> 🏦 Transfer
          </label>
        </div>
        <div class="payment-toggle" style="margin-top: 8px">
          <label class="pay-opt" id="is_credit" onclick="setPaymentAddon('credit', this)">
            <input type="checkbox" name="is_credit" value="1"> Catat sebagai Hutang
          </label>
          <label class="pay-opt" id="is_promo" onclick="setPaymentAddon('promo', this)">
            <input type="checkbox" name="is_promo" value="1"> Promo
          </label>
        </div>
      </div>

      <!-- <label class="check-row">
        <input type="checkbox" name="is_credit" id="is_credit" value="1">
        Catat sebagai Hutang
      </label>

      <label class="check-row">
        <input type="checkbox" name="is_promo" id="is_promo" value="1">
        Promo
      </label> -->
    </div>

    <!-- VOUCHER -->
    <!-- <div class="ctrl-section">
      <div class="ctrl-section-label">Voucher</div>
      <div class="field-group">
        <div class="voucher-row">
          <input type="text" class="ctrl-input" name="voucher" id="voucher" placeholder="Kode voucher">
          <button class="btn-check-voucher" onclick="event.preventDefault(); checkVoucher()">Cek</button>
        </div>
        <input type="hidden" name="voucher_nominal" id="voucher_nominal" value="0">
        <div class="voucher-result" id="voucher_result"></div>
      </div>
    </div> -->

    <!-- SUMMARY -->
    <div class="summary-section">
      <div class="ctrl-section-label" style="margin-bottom:10px">Ringkasan</div>
      <div class="summary-row">
        <span class="s-label">Subtotal Harga</span>
        <span class="s-val" id="summary-subtotal">Rp 0</span>
      </div>
      <div class="summary-row">
        <span class="s-label">Total Potongan Item</span>
        <span class="s-val" id="summary-disc-items" style="color:var(--text-muted)">– Rp 0</span>
      </div>
      <div class="summary-row">
        <span class="s-label">Total Potongan Akhir</span>
        <span class="s-val" id="summary-disc-tot" style="color:var(--text-muted)">– Rp 0</span>
      </div>
      <div class="summary-row divider total-row">
        <span class="s-label">Total Akhir</span>
        <span class="s-val" id="summary-total">Rp 0</span>
      </div>
      <div class="summary-row divider total-row">
        <span class="s-label">Bayar</span>
        <span class="s-val" id="summary-paid" style="color: var(--accent)">Rp 0</span>
      </div>
    </div>

    <!-- HIDDEN FIELDS -->
    <input type="hidden" id="total_item_price" name="total_item_price">
    <input type="hidden" id="total_discount_items_price" name="total_discount_items_price">
    <input type="hidden" id="total_sum_price" name="total_sum_price">
    <input type="hidden" id="money_returned" name="money_returned">
    <input type="hidden" name="type" value="normal">

    <!-- SUBMIT -->
    <div class="submit-area">
      <!-- <div class="demo-notice" id="div_tot_items">Total Item: </div> -->
      <div class="demo-notice" id="money_returned_display">Kembali: </div>
      <button class="btn-submit" id="div_money_returned" onclick="event.preventDefault(); submitForm(this)">
        <span>PROSES TRANSAKSI</span>
        <!-- <span class="sub-text" id="submit-sub">0 item • Kembali: Rp 0</span> -->
      </button>
    </div>
  </div>
</div>

<!-- MODAL: Keyword Search -->
<div class="modal-overlay" id="modal_search">
  <div class="modal-box">
    <div class="modal-head">
      <h3>🔍 Hasil Pencarian Barang</h3>
      <button class="modal-close" onclick="closeModal('modal_search')">×</button>
    </div>
    <div class="modal-body" id="result_good"></div>
  </div>
</div>

<!-- MODAL: Member Search -->
<div class="modal-overlay" id="modal_member">
  <div class="modal-box">
    <div class="modal-head">
      <h3>👤 Hasil Pencarian Member</h3>
      <button class="modal-close" onclick="closeModal('modal_member')">×</button>
    </div>
    <div class="modal-body" id="result_member"></div>
  </div>
</div>
{!! Form::close() !!}

<script src="{{asset('assets/bower_components/jquery/dist/jquery.min.js')}}"></script>
<!-- jQuery UI 1.11.4 -->
<script src="{{asset('assets/bower_components/jquery-ui/jquery-ui.min.js')}}"></script>
<script>

// ── CLOCK ──────────────────────────────────────────────
function updateClock() {
  const now = new Date();
  document.getElementById('clock').textContent =
    now.toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit', second:'2-digit'});
}
setInterval(updateClock, 1000); updateClock();

// ── STATE ──────────────────────────────────────────────
let total_item = 1;
let total_real_item = 0;
let total_item_retur = 1;

// ── INIT ───────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  document.getElementById('all_barcode').focus();
  document.getElementById("is_promo").checked = false;
  // document.getElementById('total_discount_price').value = '0';
  document.getElementById('transaction-form').addEventListener('keydown', (e) => {
    if (e.key === 'Enter') { e.preventDefault(); }
  });

  document.getElementById('search_good').addEventListener('keyup', e => {
    if (e.key === 'Enter') ajaxFunction('all_barcode');
  });
  document.getElementById('search_member').addEventListener('keyup', e => {
    if (e.key === 'Enter') searchMember();
  });
  // demo: add sample items on load for illustration
  // loadDemoItems();
});

document.addEventListener('keydown', e => {
  if (e.key === 'F2') { e.preventDefault(); document.getElementById('all_barcode').focus(); }
  if (e.key === 'F4') { e.preventDefault(); document.getElementById('search_good').focus(); }
  if (e.key === 'F8') { e.preventDefault(); document.getElementById('money_paid').focus(); }
});

// ── DEMO ITEMS ─────────────────────────────────────────
function loadDemoItems() {
  const samples = [
    { id: 1, name: 'Indomie Goreng Special', unit: 'PCS', barcode: 'BRC001', price: 3500 },
    { id: 2, name: 'Aqua 600ml', unit: 'BTL', barcode: 'BRC002', price: 4000 },
    { id: 3, name: 'Teh Botol Sosro 450ml', unit: 'BTL', barcode: 'BRC003', price: 5500 },
  ];
  samples.forEach(g => {
    fillItemDirect(g);
  });
}

// ── FILL ITEM (direct, used by demo) ──────────────────
function fillItemDirect(good) {
  total_real_item += 1;
  let items = total_item;
  const type = '';

  // Ensure row container exists, add new row HTML
  const newRow = buildRowHtml(type, items, good);
  const tbody = document.getElementById('table-transaction');
  // Remove empty message
  const emptyMsg = document.getElementById('empty-row-msg');
  if (emptyMsg) emptyMsg.remove();

  tbody.insertAdjacentHTML('beforeend', newRow);
  document.getElementById(`quantity-${items}`).value = 1;
  editPrice('all_barcode', items);
  total_item += 1;
  updateItemBadge();

  let scrollDiv = document.getElementById('div-good');
  scrollDiv.scrollTop = scrollDiv.scrollHeight;
}

function buildRowHtml(type, idx, good) {
  const evenBg = (idx % 2 === 0) ? 'background:#F2EAE0' : '';
  return `
  <tr id="row-data-${type}${idx}" style="${evenBg}">
    <td class="num" style="font-size:12px">${idx}</td>
    <td class="name-cell">
      <div class="item-name" title="${good.name}">${good.name}</div>
      <input type="hidden" name="barcodes[]" id="barcode-${type}${idx}" value="${good.barcode || ''}">
      <input type="hidden" name="names[]" id="name-${type}${idx}" value="${good.id || ''}">
      <input type="hidden" name="name_temps[]" id="name_temp-${type}${idx}" value="${good.name}">
      <input type="hidden" name="buy_prices[]" id="buy_price-${type}${idx}" value="${good.buy_price || 0}">
    </td>
    <td class="unit-cell"><span class="unit-badge">${good.unit}</span>
      <input type="hidden" name="satuans[]" id="satuan-${type}${idx}" value="${good.unit}">
      <input type="hidden" name="numbers[]" id="no-${type}${idx}" value="${idx}">
    </td>
    <td style="text-align:center">
      <input type="text" name="quantities[]" class="td-input qty-input" id="quantity-${type}${idx}"
             value="1" onchange="checkDiscount('all_barcode','${idx}')">
    </td>
    <td class="price-cell">
      <input type="text" name="prices[]" class="td-input price-ro" id="price-${type}${idx}"
             value="${good.price}" readonly style="text-align:right; min-width:60px">
    </td>
    <td>
      <input type="text" name="discount_pieces[]" class="td-input disc-input" id="discount_piece-${type}${idx}"
             placeholder="0" onchange="editPrice('all_barcode','${idx}')">
    </td>
    <td>
      <input type="text" name="discounts[]" class="td-input disc-input" id="discount-${type}${idx}"
             onchange="editPrice('all_barcode','${idx}')" style="min-width: 100px">
    </td>
    <td class="total-cell">
      <input type="text" name="total_prices[]" class="td-input price-ro" id="total_price-${type}${idx}"
             readonly style="text-align:right; min-width:85px">
    </td>
    <td class="sum-cell">
      <input type="text" name="sums[]" class="td-input sum-ro" id="sum-${type}${idx}"
             readonly style="text-align:right; min-width:85px">
    </td>
    <td style="text-align:center">
      <button class="delete-btn" onclick="deleteItem('-${type}${idx}')" title="Hapus">✕</button>
    </td>
  </tr>`;
}

// ── FILL ITEM (from AJAX) ─────────────────────────────
function fillItem(name, good) {
  total_real_item += 1;
  let bool = false;
  const type = '';
  let items = total_item;

  if (good && good.length !== 0) {
    for (let i = 1; i <= items; i++) {
      const bc = document.getElementById(`barcode-${type}${i}`);
      const pr = document.getElementById(`price-${type}${i}`);
      if (bc && bc.value !== '' && bc.value == good?.getPcsSellingPrice?.id && pr?.value == good?.getPcsSellingPrice?.selling_price) {
        let q = parseInt(document.getElementById(`quantity-${type}${i}`).value) + 1;
        document.getElementById(`quantity-${type}${i}`).value = q;
        bool = true;
        editPrice(name, i);
        break;
      }
    }
    if (!bool) {
      const g = {
        id: good.id,
        name: good.name,
        unit: good.getPcsSellingPrice?.name || '',
        barcode: good.getPcsSellingPrice?.id || '',
        price: good.getPcsSellingPrice?.selling_price || 0,
        buy_price: good.getPcsSellingPrice?.buy_price || 0,
      };
      const emptyMsg = document.getElementById('empty-row-msg');
      if (emptyMsg) emptyMsg.remove();
      const tbody = document.getElementById('table-transaction');
      tbody.insertAdjacentHTML('beforeend', buildRowHtml(type, items, g));
      document.getElementById(`quantity-${items}`).value = 1;
      editPrice(name, items);
      total_item += 1;
      updateItemBadge();
    }
    document.getElementById(name).value = '';
    document.getElementById(name).focus();
    let scrollDiv = document.getElementById('div-good');
    scrollDiv.scrollTop = scrollDiv.scrollHeight;
  } else {
    alert('Barang tidak ditemukan');
  }
}

// ── EDIT PRICE ─────────────────────────────────────────
function editPrice(name, index) {
  const type = '';
  const disc_piece = parseFloat(unFormatNumber(document.getElementById(`discount_piece-${type}${index}`)?.value || '0')) || 0;
  const qty = parseFloat(unFormatNumber(document.getElementById(`quantity-${type}${index}`)?.value || '1')) || 1;
  const price = parseFloat(unFormatNumber(document.getElementById(`price-${type}${index}`)?.value || '0')) || 0;

  const disc_total = disc_piece * qty;
  const total_price = price * qty;
  const sum = total_price - disc_total;

  document.getElementById(`discount-${type}${index}`).value = disc_total;
  document.getElementById(`total_price-${type}${index}`).value = total_price;
  document.getElementById(`sum-${type}${index}`).value = sum;

  formatNumber(`total_price-${type}${index}`);
  formatNumber(`sum-${type}${index}`);
  formatNumber(`discount-${type}${index}`);

  if(disc_piece != 0&& !document.getElementById('is_promo').checked)
  {
    alert("Apakah promo? Jika ya, silahkan ceklist promo");
  }

  changeTotal();
}

function checkDiscount(name, index) {
  editPrice(name, index);
}

// ── CHANGE TOTAL ───────────────────────────────────────
function changeTotal() {
  let total_item_price = 0, total_sum_price = 0, total_disc_items = 0;

  for (let i = 1; i <= total_item; i++) {
    const bc = document.getElementById(`barcode-${i}`);
    if (bc && bc.value !== '') {
      total_item_price += parseFloat(unFormatNumber(document.getElementById(`price-${i}`)?.value || '0')) * parseFloat(document.getElementById(`quantity-${i}`)?.value || '0');
      total_sum_price += parseFloat(unFormatNumber(document.getElementById(`sum-${i}`)?.value || '0'));
      total_disc_items += parseFloat(unFormatNumber(document.getElementById(`discount-${i}`)?.value || '0'));
    }
  }

  const main_disc = parseFloat(unFormatNumber(document.getElementById('total_discount_price').value || '0')) || 0;
  total_sum_price -= main_disc;
  // const voucher_nom = parseFloat(document.getElementById('voucher_nominal').value || '0') || 0;
  // total_sum_price -= voucher_nom;

  document.getElementById('total_item_price').value = total_item_price;
  document.getElementById('total_discount_items_price').value = total_disc_items;
  document.getElementById('total_sum_price').value = total_sum_price;

  // Format display
  // document.getElementById('total_sums').textContent = 'Rp ' + formatRp(total_sum_price);
  // formatNumber('total_sums');

  // Summary sidebar
  document.getElementById('summary-subtotal').textContent = 'Rp ' + formatRp(total_item_price);
  document.getElementById('summary-disc-items').textContent = '– Rp ' + formatRp(total_disc_items);
  document.getElementById('summary-disc-tot').textContent = '– Rp ' + formatRp(main_disc);
  document.getElementById('summary-total').textContent = 'Rp ' + formatRp(total_sum_price);

  changeReturn();
}

function checkIsPromo(){
  if(document.getElementById('total_discount_price').value != 0 && !document.getElementById('is_promo').checked)
  {
    alert("Apakah promo? Jika ya, silahkan ceklist promo");
  }
}

function changeReturn() {
  const paid = parseFloat(unFormatNumber(document.getElementById('money_paid').value || '0')) || 0;
  const total = parseFloat(unFormatNumber(document.getElementById('total_sum_price').value || '0')) || 0;
  const ret = paid - total;
  const retStr = formatRp(ret);
  document.getElementById('money_returned_display').textContent = "Kembali: " + retStr;
  document.getElementById('money_returned').value = ret;
  document.getElementById('summary-paid').textContent = 'Rp ' + formatRp(paid);
  // document.getElementById('div_tot_items').textContent = total_real_item;
  // document.getElementById('submit-sub').textContent =
  //   `${total_real_item} item • Kembali: Rp ${formatRp(ret)}`;
}

// ── DELETE ─────────────────────────────────────────────
function deleteItem(index) {
  const row = document.getElementById(`row-data${index}`);
  if (row) row.remove();
  total_real_item = Math.max(0, total_real_item - 1);
  if (total_real_item === 0) {
    const tbody = document.getElementById('table-transaction');
    if (!document.getElementById('empty-row-msg')) {
      tbody.insertAdjacentHTML('beforeend',
        `<tr class="empty-row" id="empty-row-msg"><td colspan="10">Belum ada barang — scan barcode atau cari dengan keyword</td></tr>`);
    }
  }
  updateItemBadge();
  changeTotal();
}

function updateItemBadge() {
  document.getElementById('item-count-badge').textContent = total_real_item + ' item';
}

// ── FORMAT ─────────────────────────────────────────────
function formatNumber(id) {
  const el = document.getElementById(id);
  if (!el) return;
  let v = el.value.toString().replace(/,/g, '');
  el.value = v.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,');
}
function unFormatNumber(s) { return (s || '').toString().replace(/,/g, ''); }
function formatRp(n) {
  const num = Math.round(n || 0);
  return num.toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,');
}

// ── PAYMENT ───────────────────────────────────────────
function setPayment(val, el) {
  // document.querySelectorAll('.pay-opt').forEach(o => o.classList.remove('active'));
  if(val == 'cash')
  {
    document.getElementById('opt-transfer').classList.remove('active');
    el.classList.add('active');
  }
  else if(val == 'transfer')
  {
    document.getElementById('opt-cash').classList.remove('active');
    el.classList.add('active');
  }
}

// ── PAYMENT ADDON ──────────────────────────────────────
function setPaymentAddon(val, el) {
  // document.querySelectorAll('.pay-opt').forEach(o => o.classList.remove('active'));
  el.classList.add('active');
}

// ── SUBMIT ─────────────────────────────────────────────
function submitForm(btn) {
  const paid = document.getElementById('money_paid').value;
  if(document.getElementById('total_discount_price').value == '' || document.getElementById('total_discount_price').value == null)
    document.getElementById('total_discount_price').value = 0;
  const disc = document.getElementById('total_discount_price').value;
  if (!paid || !disc) { alert('Silahkan masukkan jumlah uang dan potongan toko'); return; }
  const paidNum = parseInt(unFormatNumber(paid));
  const totalNum = parseInt(unFormatNumber(document.getElementById('total_sum_price').value));
  if (paidNum < totalNum && (document.getElementById('member_id').value == '1' || !document.getElementById('is_credit').checked)) {
    alert('Jumlah pembayaran kurang dari total belanja. Silahkan pilih member dan centang tombol hutang');
    return;
  }
  btn.disabled = true;
  document.getElementById('transaction-form').submit();
  // setTimeout(() => { btn.disabled = false; }, 2000); // demo reset
  // alert('✓ Transaksi berhasil diproses! (Demo)');
}

// ── BARCODE / KEYWORD ──────────────────────────────────
function searchByBarcode(name) {
  $.ajax({
    url: "{!! url($role . '/good/searchByBarcode/') !!}/" + $("#" + name).val(),
    success: function(result){
      var good = result.good;
      if(good != null)
      {
          fillItem(name, result.good)
      }
    }, 
    error: function(){
    }
  });
}
function ajaxFunction(name) {
  openModal('modal_search');
  // AJAX stub: would populate result_good

  $.ajax({
    url: "{!! url($role . '/good/searchByKeywordGoodUnit/') !!}/" + $("#search_good").val(),
    success: function(result){
        htmlResult = '<div style="padding:16px;color:var(--text-muted);font-size:13px;text-align:center">';

        // htmlResult += "<style type='text/css'>.modal-div:hover { background-color: white; }</style>";
      var r = result.good_units;

      for (var i = 0; i < r.length; i++) {

        htmlResult += "<div class='modal-item ";

        if(r[i].stock == 0) 
        {
            htmlResult += "out-of-stock'";
        }
        else if(r[i].stock < 0) 
        {
            htmlResult += "item-meta'";
        }
        else
        {
            htmlResult += "item-iname'";
        }

        htmlResult += " onclick='searchByKeyword(\"" + name + "\",\"" + r[i].good_unit_id + "\")'>";

        if(r[i].status == null)
        {
          htmlResult += '<span class="stock-chip good"></span>';
        }
        else if(r[i].status == '[KOSONG]')
        {
          htmlResult += '<span class="stock-chip empty">[KOSONG]</span>';
        }
        else
        {
          htmlResult += '<span class="stock-chip low">[MINUS]</span>';
        }

        htmlResult += r[i].name + ' <span class="stock-chip good">' + r[i].unit + "</span></div>";
      }

      htmlResult += "</div>";
      $("#result_good").html(htmlResult);
    },
    error: function(){
        console.log('error');
    }
  });
// document.getElementById('result_good').innerHTML =
//     `<div style="padding:16px;color:var(--text-muted);font-size:13px;text-align:center">Menghubungi server...<br><small>(Demo: koneksi AJAX dinonaktifkan)</small></div>`;
  // document.getElementById('result_good').innerHTML =
  //   `Menghubungi server...<br><small>(Demo: koneksi AJAX dinonaktifkan)</small></div>`;
}
function ajaxButton(keyword) { ajaxFunction('all_barcode'); }
function searchByKeyword(name, id) { 

  $.ajax({
    url: "{!! url($role . '/good/searchByGoodUnit/') !!}/" + id,
    success: function(result){
      var good = result.good;
      fillItem(name, result.good);
      closeModal('modal_search'); 
      $('#search_good').val('');
      $('#result_good').val('');
  },
    error: function(){
    }
  });
}
function searchMember() {
  openModal('modal_member');

  $.ajax({
    url: "{!! url($role . '/member/searchByName/') !!}/" + $("#search_member").val(),
    success: function(result){
        htmlResult = '<div style="padding:16px;color:var(--text-muted);font-size:13px;text-align:center">';

      var r = result.members;

      for (var i = 0; i < r.length; i++) {
        htmlResult += "<div class='modal-item item-iname' onclick='setMember(\"" + r[i].id + "\",\"" + r[i].name + "\")'>" + r[i].name + " (" + r[i].address + ")</div>";
        // htmlResult += "<textarea class='col-sm-12 modal-div' style='display:inline-block; color:black; cursor: pointer; min-height:40px; max-height:80px; padding: 5px;' onclick='setMember(\"" + r[i].id + "\",\"" + r[i].name + "\")'>" + r[i].name + " (" + r[i].address + ")</textarea>";
      }
      $("#result_member").html(htmlResult);
      $("#search_member").val('');
      $("#member_id").val('1');
      // $('.modal-body').css('height',$( window ).height()*0.5);
    },
    error: function(){
        console.log('error');
    }
  });
  // document.getElementById('result_member').innerHTML =
    // `<div style="padding:16px;color:var(--text-muted);font-size:13px;text-align:center">Mencari member...<br><small>(Demo)</small></div>`;
}
function setMember(id, name) {
  document.getElementById('member_id').value = id;
  document.getElementById('search_member').value = name;
  closeModal('modal_member');
}

// // ── VOUCHER ────────────────────────────────────────────
// function checkVoucher() {
//   const vr = document.getElementById('voucher_result');
//   vr.style.display = 'block';
//   vr.className = 'voucher-result ok';
//   vr.textContent = '✓ Voucher valid – Diskon 10% (demo)';
// }

// // ── CAMERA ────────────────────────────────────────────
// function startCamera() { document.getElementById('preview').style.display = 'block'; }
// function stopCamera() { document.getElementById('preview').style.display = 'none'; }

// ── MODAL HELPERS ──────────────────────────────────────
function openModal(id) { document.getElementById(id).classList.add('show'); }
function closeModal(id) { document.getElementById(id).classList.remove('show'); }
document.querySelectorAll('.modal-overlay').forEach(m => {
  m.addEventListener('click', e => { if (e.target === m) m.classList.remove('show'); });
});

// // ── INPUT STYLING ──────────────────────────────────────
// function changeBackColor(id) {
//   const el = document.getElementById(id);
//   if (el) { el.style.borderColor = 'var(--accent)'; el.style.boxShadow = '0 0 0 3px rgba(0,229,160,0.12)'; }
// }
// function changeBackNorm(id) {
//   const el = document.getElementById(id);
//   if (el) { el.style.borderColor = ''; el.style.boxShadow = ''; }
// }
</script>
</body>
</html>