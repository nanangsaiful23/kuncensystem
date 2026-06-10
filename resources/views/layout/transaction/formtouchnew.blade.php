{{--
  POS / Kasir — Touchscreen-first redesign
  Prioritas: laptop/desktop touchscreen → tablet → mobile
--}}
<style>
/* ═══════════════════════════════════════════════════
   TOKEN — selaras dengan sistem NTN Mart (merah)
═══════════════════════════════════════════════════ */
:root {
  --brand:        #e74c3c;
  --brand-dark:   #c0392b;
  --brand-light:  #fdf2f2;
  --green:        #08CB00;
  --green-dark:   #069b00;
  --yellow:       #FFE100;
  --yellow-dark:  #cdb400;
  --focus-bg:     #C2E2FA;
  --focus-border: #1A2A4F;
  --bg:           #f5f6fa;
  --surface:      #ffffff;
  --border:       #e2e6f0;
  --border-strong:#c8cfe0;
  --text:         #1a1d23;
  --text-mid:     #5a5f73;
  --text-muted:   #9499ad;
  --radius:       10px;
  --radius-sm:    7px;
  --touch:        52px;   /* minimum tap target */
  --font-mono:    'IBM Plex Mono', 'Courier New', monospace;
}

/* ── Scope semua style ke .pos-root ── */
.pos-root { font-family: 'Inter','Segoe UI',sans-serif; color: var(--text); }
.pos-root * { box-sizing: border-box; }

/* ═══════════════════════════════════════════════════
   LAYOUT UTAMA
   [  tabel barang  |  sidebar  ]
   [      sticky bottom bar     ]
═══════════════════════════════════════════════════ */
.pos-layout {
  display: flex;
  flex-direction: column;
  height: calc(100vh - 108px); /* sesuaikan dengan topbar + breadcrumb AdminLTE */
  min-height: 480px;
}
.pos-body {
  display: grid;
  grid-template-columns: 1fr 310px;
  gap: 10px;
  flex: 1;
  overflow: hidden;
  padding: 10px 10px 0;
}

/* ═══════════════════════════════════════════════════
   PANEL KIRI — Tabel Barang
═══════════════════════════════════════════════════ */
.pos-table-panel {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  box-shadow: 0 1px 4px rgba(0,0,0,.06);
}
.pos-table-panel__head {
  padding: 8px 14px;
  background: #f8faff;
  border-bottom: 2px solid var(--border);
  font-size: 12px;
  font-weight: 700;
  color: var(--text-muted);
  text-transform: uppercase;
  letter-spacing: .06em;
  display: flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
}
.pos-table-scroll {
  overflow-y: auto;
  overflow-x: auto;
  flex: 1;
  -webkit-overflow-scrolling: touch;
  scroll-behavior: smooth;
}
.pos-table-scroll::-webkit-scrollbar { width: 5px; height: 5px; }
.pos-table-scroll::-webkit-scrollbar-thumb { background: var(--border-strong); border-radius: 6px; }

/* Tabel item */
#pos-items-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 14px;
}
#pos-items-table thead {
  position: sticky;
  top: 0;
  z-index: 3;
}
#pos-items-table thead tr { background: #f0f3fb; }
#pos-items-table thead th {
  padding: 9px 8px;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .05em;
  color: var(--text-mid);
  border-bottom: 2px solid var(--border);
  white-space: nowrap;
  text-align: left;
}
#pos-items-table thead th.right { text-align: right; }
#pos-items-table tbody tr { border-bottom: 1px solid #e2e8f0; transition: background .1s; }
#pos-items-table tbody tr:nth-child(even) { background: #f8fafc; }
#pos-items-table tbody tr:hover { background: #fef8f8; }
#pos-items-table td { padding: 2px 4px !important; vertical-align: middle; }

/* ── Input dalam tabel ── */
#pos-items-table .form-control,
#pos-items-table input[type="text"] {
  height: 38px !important;
  min-height: 38px;
  font-size: 15px !important;
  font-weight: 600 !important;
  border: 1.5px solid var(--border) !important;
  border-radius: var(--radius-sm) !important;
  background: #fff !important;
  padding: 4px 8px !important;
  width: 100%;
  transition: border-color .15s, background .15s, box-shadow .15s;
  color: var(--text) !important;
}
#pos-items-table .form-control:focus,
#pos-items-table input[type="text"]:focus {
  border-color: var(--focus-border) !important;
  background: var(--focus-bg) !important;
  box-shadow: 0 0 0 3px rgba(26,42,79,.12) !important;
  outline: none !important;
  z-index: 1;
  position: relative;
}
#pos-items-table input[readonly],
#pos-items-table input[disabled] { background: #f4f5f8 !important; }

/* Input qty — kuning, centre, lebih tebal */
.qty-input {
  background: var(--yellow) !important;
  font-weight: 800 !important;
  font-size: 17px !important;
  text-align: center !important;
  border-color: var(--yellow-dark) !important;
  min-width: 58px;
}
.qty-input:focus {
  background: #fffac4 !important;
  border-color: var(--focus-border) !important;
}
.right-input { text-align: right !important; }

/* Nomor baris */
.no-input { text-align: center !important; min-width: 40px; color: var(--text-muted) !important; font-size: 13px !important; }

/* Tombol hapus baris — besar, touch-friendly */
.btn-del {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 34px;
  height: 34px;
  border-radius: 8px;
  background: #fee2e2;
  color: var(--brand);
  border: 1.5px solid #fca5a5;
  cursor: pointer;
  font-size: 17px;
  line-height: 1;
  transition: background .15s, color .15s, transform .1s;
  touch-action: manipulation;
  flex-shrink: 0;
}
.btn-del:hover  { background: var(--brand); color: #fff; }
.btn-del:active { transform: scale(.92); }

/* ═══════════════════════════════════════════════════
   PANEL KANAN — Sidebar kontrol
═══════════════════════════════════════════════════ */
.pos-sidebar {
  display: flex;
  flex-direction: column;
  gap: 8px;
  overflow-y: auto;
  -webkit-overflow-scrolling: touch;
}
.pos-sidebar::-webkit-scrollbar { width: 3px; }
.pos-sidebar::-webkit-scrollbar-thumb { background: var(--border); border-radius: 6px; }

/* Card sidebar */
.sc {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 10px 12px;
  box-shadow: 0 1px 4px rgba(0,0,0,.05);
}
.sc-label {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .07em;
  color: var(--text-muted);
  margin-bottom: 6px;
  display: flex;
  align-items: center;
  gap: 6px;
}
.sc-label .kbd {
  font-size: 10px;
  background: #e5e7eb;
  border: 1px solid #d1d5db;
  border-radius: 4px;
  padding: 1px 5px;
  color: var(--text-mid);
  font-family: var(--font-mono);
}

/* Input sidebar — besar */
.pos-inp {
  display: block;
  width: 100%;
  height: var(--touch);
  font-size: 16px;
  font-weight: 600;
  padding: 0 14px;
  border: 2px solid var(--border);
  border-radius: var(--radius-sm);
  background: #f6f7fa;
  color: var(--text);
  font-family: inherit;
  transition: border-color .15s, background .15s, box-shadow .15s;
}
.pos-inp:focus {
  outline: none;
  border-color: var(--focus-border);
  background: var(--focus-bg);
  box-shadow: 0 0 0 3px rgba(26,42,79,.12);
}
.pos-inp[readonly], .pos-inp[disabled] { background: #eef0f5; }

/* Select sidebar */
.pos-sel {
  display: block;
  width: 100%;
  height: var(--touch);
  font-size: 16px;
  font-weight: 600;
  padding: 0 38px 0 14px;
  border: 2px solid var(--border);
  border-radius: var(--radius-sm);
  background: #f6f7fa;
  color: var(--text);
  appearance: none;
  cursor: pointer;
  font-family: inherit;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2.5'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 12px center;
  transition: border-color .15s, background .15s;
}
.pos-sel:focus { outline: none; border-color: var(--focus-border); background-color: var(--focus-bg); }

/* Tombol sidebar */
.pos-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
  width: 100%;
  height: var(--touch);
  border: none;
  border-radius: var(--radius-sm);
  font-family: inherit;
  font-size: 15px;
  font-weight: 700;
  cursor: pointer;
  touch-action: manipulation;
  transition: opacity .15s, transform .1s;
}
.pos-btn:active { transform: scale(.97); }
.pos-btn svg { width: 17px; height: 17px; flex-shrink: 0; }
.pos-btn-warn { background: #FFC107; color: #1a1a1a; box-shadow: 0 2px 6px rgba(255,193,7,.3); }
.pos-btn-warn:hover { opacity: .9; }
.pos-btn-ghost { background: transparent; border: 2px solid var(--border); color: var(--text-muted); width: 52px; }
.pos-btn-ghost:hover { border-color: var(--brand); color: var(--brand); }

/* Alert stok */
#message { border-radius: var(--radius-sm); font-size: 14px; margin: 0; }

/* Checkbox besar — jadi tombol toggle */
.pos-toggle-group { display: flex; gap: 6px; }
.pos-toggle {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 10px 10px;
  border-radius: var(--radius-sm);
  border: 2px solid var(--border);
  cursor: pointer;
  font-size: 15px;
  font-weight: 700;
  color: var(--text-mid);
  transition: all .15s;
  user-select: none;
  touch-action: manipulation;
}
.pos-toggle:has(input:checked) { border-color: var(--brand); background: var(--brand-light); color: var(--brand); }
.pos-toggle input { width: 18px; height: 18px; accent-color: var(--brand); cursor: pointer; }

/* ═══════════════════════════════════════════════════
   STICKY BOTTOM BAR
═══════════════════════════════════════════════════ */
.pos-up {
  flex-shrink: 0;
  background: var(--surface);
  border-bottom: 1px solid var(--border);
  padding: 6px 16px;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 15px;
  align-items: center;
}
.pos-up .sc-label { margin-bottom: 0; white-space: nowrap; flex-shrink: 0; min-width: 100px; }
.pos-up-group { display: flex; align-items: center; gap: 10px; }
.pos-up .pos-inp, 
.pos-up .pos-btn { 
  height: 40px !important; 
  font-size: 15px;
}
.pos-up .pos-inp { flex: 1; }
.pos-up-row { display: flex; gap: 8px; width: 100%; flex: 1; }
.pos-bottom {
  flex-shrink: 0;
  background: var(--surface);
  border-top: 2px solid var(--border);
  padding: 8px 12px;
  display: grid; /* Menggunakan grid untuk tata letak kolom yang fleksibel */
  grid-template-columns: 0.8fr 1fr 1fr 1.2fr; /* Disesuaikan: 'Potongan Akhir' lebih pendek (0.8fr), 'Proses Transaksi' lebih panjang (1.2fr) */
  gap: 10px;
  align-items: end;
}
.pb-field { display: flex; flex-direction: column; gap: 4px; }
.pb-label {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .07em;
  color: var(--text-muted);
  display: flex;
  align-items: center;
  gap: 5px;
}
.pb-label .kbd {
  font-size: 10px;
  background: #e5e7eb;
  border: 1px solid #d1d5db;
  border-radius: 4px;
  padding: 1px 5px;
  font-family: var(--font-mono);
  color: var(--text-mid);
}

/* Input bottom — besar, mono */
.pb-input {
  height: 62px;
  font-size: 24px;
  font-weight: 700;
  padding: 0 14px;
  border-radius: var(--radius-sm);
  border: 2px solid var(--border);
  background: #fff;
  color: var(--text);
  font-family: var(--font-mono);
  letter-spacing: -.01em;
  width: 100%;
  transition: border-color .15s, box-shadow .15s;
}
.pb-input:focus {
  outline: none;
  border-color: var(--focus-border);
  box-shadow: 0 0 0 3px rgba(26,42,79,.12);
  background: var(--focus-bg);
}
.pb-input[readonly] { background: #f0f2f5; }
.pb-input.is-total {
  background: var(--brand) !important;
  color: #fff;
  border-color: var(--brand-dark);
}
.pb-input.is-bayar {
  background: var(--yellow) !important;
  border-color: var(--yellow-dark);
}

/* Tombol PROSES */
#div_money_returned {
  min-width: 190px;
  height: 82px !important;
  display: flex !important;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  font-size: 16px !important;
  font-weight: 700;
  line-height: 1.1;
  border-radius: var(--radius);
  background: var(--green) !important;
  color: #fff;
  cursor: pointer;
  text-align: center;
  padding: 0;
  margin: 0;
  touch-action: manipulation;
  transition: background .15s, transform .1s, box-shadow .15s;
  box-shadow: 0 3px 12px rgba(8,203,0,.3);
  white-space: pre-line;
  border: none;
}
#div_money_returned:hover  { background: var(--green-dark) !important; box-shadow: 0 4px 16px rgba(8,203,0,.38); transform: translateY(-1px); }
#div_money_returned:active { transform: scale(.97); }
.proses-main { font-size: 14px; font-weight: 600; opacity: .8; }
.proses-sub  { font-size: 26px; font-weight: 800; margin-top: 0; }

/* ═══════════════════════════════════════════════════
   MODAL — lebih besar & touchscreen-friendly
═══════════════════════════════════════════════════ */
.pos-root .modal-dialog { max-width: 620px; margin: 30px auto; }
.pos-root .modal-title  { font-size: 17px; font-weight: 700; }
.pos-root .modal-body   { overflow-y: auto; max-height: 56vh; padding: 10px; }
.modal-item {
  display: block !important;
  width: 100%;
  min-height: 54px !important;
  font-size: 16px;
  font-weight: 600;
  padding: 10px 14px;
  cursor: pointer;
  border-radius: 8px;
  border: 1.5px solid transparent;
  resize: none;
  margin-bottom: 6px;
  line-height: 1.4;
  transition: border-color .15s, transform .1s;
  font-family: inherit;
  color: var(--text) !important;
  text-align: left;
}
.modal-item:hover { border-color: var(--brand) !important; transform: translateX(3px); }
.pos-root .modal-footer .btn { height: 44px; font-size: 15px; font-weight: 600; padding: 0 20px; border-radius: var(--radius-sm); }

/* ═══════════════════════════════════════════════════
   RESPONSIF
═══════════════════════════════════════════════════ */

/* Laptop kecil / touchscreen 13–14" */
@media (max-width: 1280px) {
  .pos-body { grid-template-columns: 1fr 285px; }
  .pos-bottom { grid-template-columns: 1fr 1fr 1fr auto; }
  #div_money_returned { min-width: 160px; }
}

/* Tablet landscape / kecil */
@media (max-width: 1024px) {
  .pos-layout { height: auto; min-height: unset; }
  .pos-body {
    grid-template-columns: 1fr;
    overflow: visible;
    padding: 10px;
  }
  .pos-table-panel { min-height: 280px; max-height: 48vh; }
  .pos-sidebar { flex-direction: row; flex-wrap: wrap; overflow: visible; }
  .sc { flex: 1 1 calc(50% - 4px); min-width: 170px; }
  .pos-bottom { grid-template-columns: 1fr 1fr; gap: 8px; }
  #div_money_returned { grid-column: 1 / -1; width: 100%; min-width: unset; height: 68px !important; }
  .proses-main { font-size: 17px; }
}

/* Mobile */
@media (max-width: 640px) {
  .pos-up { grid-template-columns: 1fr; gap: 12px; padding: 10px 12px; }
  .pos-body { padding: 8px; gap: 8px; }
  .pos-bottom { padding: 8px; gap: 7px; }
  .pb-input { height: 54px; font-size: 20px; }
  .sc { flex: 1 1 100%; }
  #pos-items-table .form-control,
  #pos-items-table input[type="text"] { height: 46px !important; font-size: 14px !important; }
  .pos-root .modal-dialog { margin: 8px; max-width: unset; }
}
@media (max-width: 400px) {
  .pb-input { height: 50px; font-size: 18px; }
  #div_money_returned { height: 60px !important; }
  .proses-main { font-size: 15px; }
}

/* Sembunyikan fa-times lama (diganti btn-del) */
.fa-times.red { display: none !important; }
</style>

<script type="text/javascript" src="https://rawgit.com/schmich/instascan-builds/master/instascan.min.js"></script>

<div class="pos-root">
<div class="pos-layout">
    {{-- Barcode & Keyword --}}
    <div class="pos-up">
        {{-- Group: Barcode --}}
        <div class="pos-up-group">
            <div class="sc-label">
                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><rect x="3" y="4" width="2" height="16"/><rect x="7" y="4" width="1" height="16"/><rect x="10" y="4" width="2" height="16"/><rect x="14" y="4" width="1" height="16"/><rect x="17" y="4" width="2" height="16"/></svg>
                Barcode <span class="kbd">F2</span>
            </div>
            <input type="text" name="all_barcode" class="pos-inp" id="all_barcode"
                onchange="searchByBarcode('all_barcode')"
                onfocus="changeBackColor('all_barcode')"
                onfocusout="changeBackNorm('all_barcode')"
                placeholder="Scan / ketik barcode…"
                autocomplete="off">
        </div>

        {{-- Group: Cari Nama Barang --}}
        <div class="pos-up-group">
            <div class="sc-label">
                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="m21 21-4.35-4.35"/></svg>
                Cari Barang <span class="kbd">F4</span>
            </div>
            <div class="pos-up-row">
                <input type="text" name="search_good" class="pos-inp" id="search_good"
                    onfocus="changeBackColor('search_good')"
                    onfocusout="changeBackNorm('search_good')"
                    placeholder="Nama barang…"
                    autocomplete="off">
                <button type="button" class="pos-btn pos-btn-warn" onclick="ajaxFunction('all_barcode')" style="width: auto; padding: 0 16px; white-space: nowrap;">
                    <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="m21 21-4.35-4.35"/></svg>
                    Cari
                </button>
            </div>
        </div>

        {{-- Modal hasil barang --}}
        <div class="modal modal-primary fade" id="modal_search">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title">Hasil Pencarian — ketuk untuk pilih</h4>
              </div>
              <div class="modal-body"><div id="result_good"></div></div>
              <div class="modal-footer">
                <button type="button" class="btn btn-default btn-flat" data-dismiss="modal">Tutup</button>
              </div>
            </div>
          </div>
        </div>
      </div>

  {{-- ══════════════════════════════════════════
       BODY: tabel kiri + sidebar kanan
  ══════════════════════════════════════════ --}}
  <div class="pos-body">

    {{-- ── Panel kiri: Tabel barang ── --}}
    <div class="pos-table-panel">
     
      <div class="pos-table-panel__head">
        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2"/></svg>
        Daftar Barang Transaksi
      </div>
      <div class="pos-table-scroll" id="div-good">
        <table id="pos-items-table">
          <thead>
            <tr>
              <th style="display:none;">Barcode</th>
              <th style="width:40px;">#</th>
              <th>Nama Barang</th>
              <th style="width:72px;">Satuan</th>
              <th style="width:62px;">Qty</th>
              <th class="right" style="width:115px;">Harga</th>
              <th class="right" style="width:90px;">Pot/Brg</th>
              <th class="right" style="width:90px;">Tot Pot</th>
              <th class="right" style="width:115px;">Total Harga</th>
              <th class="right" style="width:115px;">Total Akhir</th>
              <th style="width:46px;"></th>
            </tr>
          </thead>
          <tbody id="table-transaction">
            <?php $i = 1; ?>
            <tr id="row-data-{{ $i }}" @if($i % 2 == 0) style="background:#f8fafc" @endif>
              <td style="display:none;">
                {!! Form::text('barcodes[]', null, ['class'=>'form-control','readonly'=>'readonly','id'=>'barcode-'.$i]) !!}
              </td>
              <td><input type="text" name="numbers[]" class="form-control no-input" id="no-{{ $i }}" value="{{ $i }}" readonly></td>
              <td>
                {!! Form::text('name_temps[]', null, ['class'=>'form-control','readonly'=>'readonly','id'=>'name_temp-'.$i]) !!}
                {!! Form::text('names[]', null, ['id'=>'name-'.$i,'style'=>'display:none']) !!}
              </td>
              <td>{!! Form::text('satuans[]', null, ['class'=>'form-control','readonly'=>'readonly','id'=>'satuan-'.$i]) !!}</td>
              <td>
                <input type="text" name="quantities[]" class="form-control qty-input" id="quantity-{{ $i }}"
                  onchange="checkDiscount('all_barcode','{{ $i }}')">
              </td>
              <td>
                {!! Form::text('buy_prices[]', null, ['id'=>'buy_price-'.$i,'style'=>'display:none']) !!}
                {!! Form::text('prices[]', null, ['class'=>'form-control right-input','readonly'=>'readonly','id'=>'price-'.$i]) !!}
              </td>
              <td>
                <input type="text" name="discount_pieces[]" class="form-control right-input" id="discount_piece-{{ $i }}"
                  onchange="editPrice('all_barcode','{{ $i }}')"
                  onkeypress="editPrice('all_barcode','{{ $i }}')">
              </td>
              <td>
                <input type="text" name="discounts[]" class="form-control right-input" id="discount-{{ $i }}"
                  onchange="editPrice('all_barcode','{{ $i }}')"
                  onkeypress="editPrice('all_barcode','{{ $i }}')">
              </td>
              <td>{!! Form::text('total_prices[]', null, ['class'=>'form-control right-input','readonly'=>'readonly','id'=>'total_price-'.$i]) !!}</td>
              <td>{!! Form::text('sums[]', null, ['class'=>'form-control right-input','readonly'=>'readonly','id'=>'sum-'.$i]) !!}</td>
              <td>
                <button type="button" class="btn-del" id="delete-{{ $i }}" onclick="deleteItem('-{{ $i }}')" title="Hapus baris">
                  <i class="fa fa-times"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>{{-- /pos-table-panel --}}

    {{-- ── Panel kanan: Sidebar ── --}}
    <div class="pos-sidebar">

      {{-- Video scanner --}}
      <video id="preview" style="display:none;width:100%;border-radius:var(--radius);"></video>

      {{-- Alert stok kosong --}}
      <div class="alert alert-danger alert-dismissible" id="message" style="display:none;">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <strong><i class="fa fa-warning"></i> Stok Habis</strong>
        <div id="empty-item" style="font-size:13px;margin-top:3px;"></div>
      </div>

     
      {{-- Member --}}
      <div class="sc">
        <div class="sc-label">
          <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          Member
        </div>
        @if($SubmitButtonText == 'View')
          {!! Form::text('member', null, ['class'=>'pos-inp','readonly'=>'readonly']) !!}
        @else
          <input type="text" name="search_member" class="pos-inp" id="search_member"
            onfocus="changeBackColor('search_member')"
            onfocusout="changeBackNorm('search_member')"
            placeholder="Ketik nama member + Enter…"
            autocomplete="off">
          {!! Form::hidden('member_id', '1', ['id'=>'member_id']) !!}

          {{-- Modal member --}}
          <div class="modal modal-primary fade" id="modal_member">
            <div class="modal-dialog">
              <div class="modal-content">
                <div class="modal-header">
                  <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                  <h4 class="modal-title">Hasil Pencarian Member — ketuk untuk pilih</h4>
                </div>
                <div class="modal-body"><div id="result_member"></div></div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-default btn-flat" data-dismiss="modal">Tutup</button>
                </div>
              </div>
            </div>
          </div>

          {{-- Tombol scan barcode member --}}
          <div style="display:flex;gap:6px;margin-top:8px;">
            <button type="button" class="pos-btn pos-btn-warn" onclick="startCamera()" style="flex:1;">
              <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
              Scan Barcode
            </button>
            <button type="button" class="pos-btn pos-btn-ghost" onclick="stopCamera()" title="Stop kamera">
              <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path stroke-linecap="round" d="m15 9-6 6M9 9l6 6"/></svg>
            </button>
          </div>
        @endif
      </div>

      {{-- Keterangan + Pembayaran + Checkbox --}}
      <div class="sc">
        <div class="sc-label">Keterangan</div>
        @if($SubmitButtonText == 'View')
          {!! Form::text('note', null, ['class'=>'pos-inp','readonly'=>'readonly']) !!}
        @else
          {!! Form::text('note', null, ['class'=>'pos-inp','placeholder'=>'Catatan (opsional)…']) !!}
        @endif

        <div class="sc-label" style="margin-top:10px;">Metode Bayar</div>
        <select class="pos-sel" name="payment">
          <option value="cash">💵  Cash / Tunai</option>
          <option value="transfer">🏦  Transfer</option>
        </select>

        <div style="margin-top:10px;">
          <div class="pos-toggle-group">
            <label class="pos-toggle">
              <input type="checkbox" name="is_credit" id="is_credit" value="1"> Hutang
            </label>
            <label class="pos-toggle">
              <input type="checkbox" name="is_promo" id="is_promo" value="1"> Promo
            </label>
          </div>
        </div>
      </div>

      {{-- Hidden fields --}}
      <div style="display:none;">
        {!! Form::text('total_item_price', null, ['readonly'=>'readonly','id'=>'total_item_price']) !!}
        {!! Form::text('total_discount_items_price', null, ['readonly'=>'readonly','id'=>'total_discount_items_price']) !!}
        <input type="text" name="voucher_nominal" id="voucher_nominal" value="0">
        <input type="text" name="voucher" id="voucher">
      </div>

    </div>{{-- /pos-sidebar --}}
  </div>{{-- /pos-body --}}

  {{-- ══════════════════════════════════════════
       STICKY BOTTOM BAR
  ══════════════════════════════════════════ --}}
  <div class="pos-bottom">

    {{-- Potongan Akhir --}}
    <div class="pb-field">
      <div class="pb-label">Potongan Akhir</div>
      <input type="text" name="total_discount_price" class="pb-input" id="total_discount_price"
        onchange="changeTotal()" onkeypress="changeTotal()" required
        onkeyup="formatNumber('total_discount_price'); changeTotal()"
        placeholder="0">
    </div>

    {{-- Total Akhir --}}
    <div class="pb-field">
      <div class="pb-label">Total Akhir</div>
      {!! Form::text('total_sum_price', null, ['class'=>'pb-input is-total','readonly'=>'readonly','id'=>'total_sum_price','placeholder'=>'0']) !!}
    </div>

    {{-- Bayar --}}
    <div class="pb-field">
      <div class="pb-label">Bayar <span class="kbd">F8</span></div>
      <input type="text" name="money_paid" class="pb-input is-bayar" id="money_paid"
        onchange="changeReturn()" onkeypress="changeReturn()" required
        onkeyup="formatNumber('money_paid'); changeReturn()"
        placeholder="0"
        autocomplete="off">
    </div>

    {{-- Tombol Proses --}}
    @if($SubmitButtonText == 'Edit')
      {!! Form::submit($SubmitButtonText, ['class'=>'btn btn-warning btn-flat','style'=>'height:82px;font-size:18px;font-weight:700;min-width:160px;border-radius:var(--radius);']) !!}
    @elseif($SubmitButtonText == 'Tambah')
    <div class="pb-field">
      <div class="pb-label">✓ Proses Transaksi </div>
       <div onclick="event.preventDefault(); submitForm(this);" id="div_money_returned">
        
        <div class="proses-sub" id="proses-sub-text">Kembali: — · 0 item</div>
      </div>
    
    </div>
      <!-- <div onclick="event.preventDefault(); submitForm(this);" id="div_money_returned">
        <div class="proses-main">✓ Proses Transaksi</div>
        <div class="proses-sub" id="proses-sub-text">Kembali: — · 0 item</div>
      </div> -->
      {!! Form::hidden('money_returned', null, ['id'=>'money_returned']) !!}
    @endif

  </div>{{-- /pos-bottom --}}

</div>{{-- /pos-layout --}}
</div>{{-- /pos-root --}}

{{ Form::hidden('type', 'normal') }}
{!! Form::close() !!}

@section('js-addon')
<script type="text/javascript">
  let scanner = new Instascan.Scanner({ video: document.getElementById('preview') });
  var total_item        = 1;
  var total_real_item   = 0;
  var total_item_retur  = 1;

  $(document).ready(function() {
    $('.select2').select2();
    $('#all_barcode').focus();
    $('#row-data-' + total_item).hide();
    document.getElementById('total_discount_price').value = 0;
    document.getElementById('is_credit').value = 0;

    $('#search_good').keyup(function(e)  { if (e.keyCode == 13) ajaxFunction('all_barcode'); });
    $('#search_good_retur').keyup(function(e) { if (e.keyCode == 13) ajaxFunction('all_barcode_retur'); });
    $('#search_member').keyup(function(e) { if (e.keyCode == 13) searchMember(); });
  });

  document.addEventListener('keydown', function(e) {
    if      (e.keyCode == 113) $('#all_barcode').focus();   // F2
    else if (e.keyCode == 115) $('#search_good').focus();   // F4
    else if (e.keyCode == 119) $('#money_paid').focus();    // F8
  }, true);

  $('#modal_search').on('shown.bs.modal', function() { $('#search_good').focus(); });

  /* ── Isi baris item ── */
  function fillItem(name, good) {
    total_real_item++;
    var bool  = false;
    var type  = (name == 'all_barcode_retur') ? 'retur_s' : '';
    var items = (name == 'all_barcode_retur') ? total_item_retur : total_item;

    if (good.length == 0) { alert('Barang tidak ditemukan'); return; }

    for (var i = 1; i <= items; i++) {
      var bc = document.getElementById('barcode-' + type + i);
      if (bc && bc.value != '' && bc.value == good.getPcsSellingPrice.id &&
          document.getElementById('price-' + type + i).value == good.getPcsSellingPrice.selling_price) {
        document.getElementById('quantity-' + type + i).value = parseInt(document.getElementById('quantity-' + type + i).value) + 1;
        editPrice(name, i);
        bool = true; break;
      }
    }
    if (!bool) {
      document.getElementById('name-' + type + items).value         = good.id;
      document.getElementById('name_temp-' + type + items).value    = good.name;
      document.getElementById('satuan-' + type + items).value       = good.getPcsSellingPrice.name;
      document.getElementById('barcode-' + type + items).value      = good.getPcsSellingPrice.id;
      document.getElementById('quantity-' + type + items).value     = 1;
      document.getElementById('price-' + type + items).value        = good.getPcsSellingPrice.selling_price;
      document.getElementById('discount_piece-' + type + items).value = '0';
      document.getElementById('discount-' + type + items).value     = '0';
      document.getElementById('buy_price-' + type + items).value    = good.getPcsSellingPrice.buy_price;
      document.getElementById('total_price-' + type + items).value  = good.getPcsSellingPrice.selling_price;
      $('#row-data-' + items).show();
      editPrice(name, items);
    }
    document.getElementById(name).value = '';
    $('#' + name).focus();
    var sd = document.getElementById('div-good');
    sd.scrollTop = sd.scrollHeight;
  }

  function searchByKeyword(name, good_unit_id) {
    var type = (name == 'all_barcode_retur') ? '_retur' : '';
    $.ajax({
      url: "{!! url($role . '/good/searchByGoodUnit/') !!}/" + good_unit_id,
      success: function(result) {
        if (result.good.stock <= 0) {
          alert(result.good.name + ' stok: ' + result.good.stock);
          $('#empty-item').append('> ' + result.good.name + ' stok: ' + result.good.stock + '<br>');
        }
        fillItem(name, result.good);
        $('#modal_search' + type).modal('hide');
        $('#search_good' + type).val('');
      }
    });
  }

  function searchByBarcode(name) {
    $.ajax({
      url: "{!! url($role . '/good/searchByBarcode/') !!}/" + $('#' + name).val(),
      success: function(result) { if (result.good != null) fillItem(name, result.good); }
    });
  }

  function checkDiscount(name_div, index) { editPrice(name_div, index); }

  function changeTotal() {
    var tip = 0, tsum = 0, tdisc = 0;
    for (var i = 1; i <= total_item; i++) {
      var bc = document.getElementById('barcode-' + i);
      if (bc && bc.value != '') {
        tip   += parseInt(document.getElementById('price-' + i).value || 0) * parseInt(document.getElementById('quantity-' + i).value || 0);
        tsum  += parseInt((document.getElementById('sum-' + i).value || '0').replace(/,/g,''));
        tdisc += parseInt((document.getElementById('discount-' + i).value || '0').replace(/,/g,''));
      }
    }
    for (var i = 1; i <= total_item_retur; i++) {
      var bc2 = document.getElementById('barcode-retur_s' + i);
      if (bc2 && bc2.value != '') {
        tip   -= parseInt(document.getElementById('price-retur_s' + i).value || 0) * parseInt(document.getElementById('quantity-retur_s' + i).value || 0);
        tsum  -= parseInt((document.getElementById('sum-retur_s' + i).value || '0').replace(/,/g,''));
        tdisc += parseInt((document.getElementById('discount-retur_s' + i).value || '0').replace(/,/g,''));
      }
    }
    var disc    = parseInt((document.getElementById('total_discount_price').value || '0').replace(/,/g,'')) || 0;
    var voucher = parseInt($('#voucher_nominal').val() || 0) || 0;
    tsum = tsum - disc - voucher;

    document.getElementById('total_item_price').value           = tip;
    document.getElementById('total_discount_items_price').value = tdisc;
    document.getElementById('total_sum_price').value            = tsum;
    formatNumber('total_item_price');
    formatNumber('total_discount_items_price');
    formatNumber('total_sum_price');
    changeReturn();
  }

  function changeReturn() {
    var paid = parseInt((document.getElementById('money_paid').value || '0').replace(/,/g,'')) || 0;
    var sum  = parseInt((document.getElementById('total_sum_price').value || '0').replace(/,/g,'')) || 0;
    var ret  = (paid - sum).toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,');
    var subEl = document.getElementById('proses-sub-text');
    if (subEl) subEl.textContent = 'Kembali: ' + ret + ' · ' + total_real_item + ' item';
    document.getElementById('money_returned').value = ret;
  }

  function submitForm(btn) {
    if ($('#money_paid').val() == '' || $('#total_discount_price').val() == '') {
      alert('Silahkan masukkan jumlah uang dan potongan toko'); return;
    }
    if (parseInt(unFormatNumber($('#money_paid').val())) < parseInt(unFormatNumber($('#total_sum_price').val()))
        && ($('#member_id').val() == '1' || !$('#is_credit').is(':checked'))) {
      alert('Jumlah pembayaran kurang. Pilih member dan centang Hutang jika perlu.'); return;
    }
    btn.style.pointerEvents = 'none';
    btn.style.opacity = '.7';
    document.getElementById('transaction-form').submit();
  }

  function formatNumber(name) {
    var el  = document.getElementById(name);
    var num = (el.value || '').toString().replace(/,/g,'');
    el.value = num.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,');
  }
  function unFormatNumber(num) { return (num||'').replace(/,/g,''); }

  function deleteItem(index) {
    $('#row-data' + index).remove();
    total_real_item--;
    changeTotal();
  }

  function editPrice(name, index) {
    var temp1  = parseInt(index) + 1;
    var type   = (name == 'all_barcode_retur') ? 'retur_s' : '';
    var items  = (name == 'all_barcode_retur') ? total_item_retur : total_item;
    var td_rusak = '';
    if (name == 'all_barcode_retur') {
      td_rusak = '<td><select class="form-control" name="conditionsretur_s[]" id="conditionretur_s' + temp1 + '" style="height:38px;font-size:15px;font-weight:600;"><option value="rusak">Rusak</option><option value="not">Tidak Rusak</option></select></td>';
    }

    var dp  = parseFloat(unFormatNumber(document.getElementById('discount_piece-' + type + index).value) || 0);
    var qty = parseFloat(unFormatNumber(document.getElementById('quantity-' + type + index).value) || 0);
    var pr  = parseFloat(unFormatNumber(document.getElementById('price-' + type + index).value) || 0);
    document.getElementById('discount-' + type + index).value     = dp * qty;
    document.getElementById('total_price-' + type + index).value  = pr * qty;
    document.getElementById('sum-' + type + index).value          = (pr * qty) - (dp * qty);
    formatNumber('total_price-' + type + index);
    formatNumber('sum-' + type + index);
    formatNumber('discount-' + type + index);
    changeTotal();

    var rowBg = (temp1 % 2 == 0) ? 'background:#f8fafc;' : '';
    var inpStyle = 'height:38px!important;font-size:15px!important;font-weight:600!important;border:1.5px solid #e2e6f0!important;border-radius:7px!important;';

    var h = '<tr id="row-data-' + type + temp1 + '" style="' + rowBg + '">'
      + '<td style="display:none;"><input type="text" name="barcodes' + type + '[]" class="form-control" id="barcode-' + type + temp1 + '" readonly style="' + inpStyle + '"></td>'
      + '<td><input type="text" name="numbers' + type + '[]" class="form-control no-input" id="no-' + type + temp1 + '" value="' + temp1 + '" readonly style="' + inpStyle + 'text-align:center;min-width:40px;"></td>'
      + '<td><input type="text" class="form-control" readonly id="name_temp-' + type + temp1 + '" name="name_temps' + type + '[]" style="' + inpStyle + '">'
      + '<input id="name-' + type + temp1 + '" name="names' + type + '[]" type="text" style="display:none;"></td>'
      + '<td><input type="text" class="form-control" readonly id="satuan-' + type + temp1 + '" name="satuans-' + type + '[]" style="' + inpStyle + '"></td>'
      + td_rusak
      + '<td><input type="text" name="quantities' + type + '[]" class="form-control qty-input" id="quantity-' + type + temp1 + '" onchange="checkDiscount(\'' + name + '\',\'' + temp1 + '\')" style="' + inpStyle + 'background:var(--yellow)!important;text-align:center;min-width:58px;"></td>'
      + '<td><input id="buy_price-' + type + temp1 + '" name="buy_prices' + type + '[]" type="text" style="display:none;">'
      + '<input class="form-control right-input" readonly id="price-' + type + temp1 + '" name="prices' + type + '[]" type="text" style="' + inpStyle + 'text-align:right;"></td>';

    @if(\Auth::user()->email == 'admin')
      h += '<td><input type="text" name="discount_pieces' + type + '[]" class="form-control right-input" id="discount_piece-' + type + temp1 + '" onchange="editPrice(\'' + name + '\',\'' + type + temp1 + '\')" onkeypress="editPrice(\'' + name + '\',\'' + type + temp1 + '\')" style="' + inpStyle + 'text-align:right;"></td>'
        + '<td><input type="text" name="discounts' + type + '[]" class="form-control right-input" id="discount-' + type + temp1 + '" onchange="editPrice(\'' + name + '\',\'' + type + temp1 + '\')" onkeypress="editPrice(\'' + name + '\',\'' + type + temp1 + '\')" style="' + inpStyle + 'text-align:right;"></td>';
    @else
      h += '<td><input type="text" name="discount_pieces' + type + '[]" class="form-control right-input" id="discount_piece-' + type + temp1 + '" readonly value="0" style="' + inpStyle + 'text-align:right;"></td>'
        + '<td><input type="text" name="discounts' + type + '[]" class="form-control right-input" id="discount-' + type + temp1 + '" readonly value="0" style="' + inpStyle + 'text-align:right;"></td>';
    @endif

    h += '<td><input class="form-control right-input" readonly id="total_price-' + type + temp1 + '" name="total_prices' + type + '[]" type="text" style="' + inpStyle + 'text-align:right;"></td>'
      + '<td><input class="form-control right-input" readonly id="sum-' + type + temp1 + '" name="sums' + type + '[]" type="text" style="' + inpStyle + 'text-align:right;"></td>'
      + '<td><button type="button" class="btn-del" id="delete-' + type + temp1 + '" onclick="deleteItem(\'-' + type + temp1 + '\')" title="Hapus"><i class="fa fa-times"></i></button></td>'
      + '</tr>';

    if (index == items) {
      if (name == 'all_barcode_retur') { total_item_retur++; $('#table-transaction-retur').append(h); }
      else { total_item++; $('#table-transaction').append(h); }
      $('#row-data-' + total_item).hide();
    }
    document.getElementById(name).value = '';
    $('#' + name).focus();
  }

  function ajaxFunction(name) {
    var type = (name == 'all_barcode_retur') ? '_retur' : '';
    $('#modal_search' + type).modal('show');
    $.ajax({
      url: "{!! url($role . '/good/searchByKeywordGoodUnit/') !!}/" + $('#search_good' + type).val(),
      success: function(result) {
        var h = '', r = result.good_units;
        for (var i = 0; i < r.length; i++) {
          var bg = r[i].stock == 0 ? '#D1D3D4' : r[i].stock < 0 ? '#D9C4B0' : '#9EBC8A';
          var st = r[i].status || '';
          h += "<button type='button' class='modal-item' style='background:" + bg + ";' onclick='searchByKeyword(\"" + name + "\",\"" + r[i].good_unit_id + "\")'>" + st + ' ' + r[i].name + ' ' + r[i].unit + '</button>';
        }
        $('#result_good' + type).html(h);
        $('.modal-body').css('height', $(window).height() * 0.52);
      }
    });
  }

  function searchMember() {
    $('#modal_member').modal('show');
    $.ajax({
      url: "{!! url($role . '/member/searchByName/') !!}/" + $('#search_member').val(),
      success: function(result) {
        var h = '', r = result.members;
        for (var i = 0; i < r.length; i++) {
          h += "<button type='button' class='modal-item' style='background:#f0f4ff;' onclick='setMember(\"" + r[i].id + "\",\"" + r[i].name + "\")'>" + r[i].name + ' (' + r[i].address + ')</button>';
        }
        $('#result_member').html(h);
        $('#search_member').val('');
        $('#member_id').val('1');
        $('.modal-body').css('height', $(window).height() * 0.52);
      }
    });
  }

  function setMember(id, name) {
    $('#member_id').val(id);
    $('#search_member').val(name);
    $('#modal_member').modal('hide');
  }

  function ajaxButton(keyword) {
    var name = 'all_barcode';
    $('#modal_search').modal('show');
    $.ajax({
      url: "{!! url($role . '/good/searchByKeywordGoodUnit/') !!}/" + keyword,
      success: function(result) {
        var h = '', r = result.good_units;
        for (var i = 0; i < r.length; i++) {
          var bg = (i % 2 == 0) ? '#FFF1CE' : '#FDEFF4';
          h += "<button type='button' class='modal-item' style='background:" + bg + ";' onclick='searchByKeyword(\"" + name + "\",\"" + r[i].good_unit_id + "\")'>" + r[i].name + ' ' + r[i].unit + '</button>';
        }
        $('#result_good').html(h);
        $('.modal-body').css('height', $(window).height() * 0.52);
      }
    });
  }

  function checkVoucher() {
    if (total_item == 1 && total_item_retur == 1) { alert('Silahkan pilih barang'); return; }
    $.ajax({
      url: "{!! url($role . '/voucher/searchByCode/') !!}/" + $('#voucher').val(),
      success: function(result) {
        var r = result.voucher;
        if (r != null) {
          var pot = r.type == 'discount'
            ? parseInt(unFormatNumber($('#total_sum_price').val())) * r.nominal / 100
            : r.nominal;
          var msg = r.type == 'discount' ? 'Diskon ' + r.nominal + '%' : 'Potongan Rp' + r.nominal;
          $('#voucher_result').html(msg).css({'background':'#DADDB1','height':$(window).height()*0.1});
          $('#total_sum_price').val(parseInt(unFormatNumber($('#total_sum_price').val())) - pot);
          $('#voucher_nominal').val(pot);
        } else {
          $('#voucher_result').html(result.message).css({'background':'#FF6969','height':$(window).height()*0.1});
          $('#voucher_nominal').val(0);
        }
      }
    });
  }

  function startCamera() {
    $('#preview').show();
    scanner.addListener('scan', function(content) {
      $.ajax({
        url: "{!! url($role . '/member/search/') !!}/" + content,
        success: function(result) {
          if (result.member != null) $('#all_member').val(result.member.id).change();
          else alert('Member tidak ditemukan');
          scanner.stop(); $('#preview').hide();
        }
      });
    });
    Instascan.Camera.getCameras().then(function(cameras) {
      if (cameras.length > 0) scanner.start(cameras[0]);
      else console.error('No cameras found.');
    }).catch(function(e) { console.error(e); });
  }
  function stopCamera() { scanner.stop(); $('#preview').hide(); }

  function changeBackColor(id) {
    $('#' + id).css({ 'border-color': '#1A2A4F', 'background-color': '#C2E2FA', 'border-width': '3px' });
  }
  function changeBackNorm(id) {
    $('#' + id).css({ 'background-color': '#f6f7fa', 'border-width': '2px', 'border-color': '#e2e6f0' });
  }
</script>
@endsection