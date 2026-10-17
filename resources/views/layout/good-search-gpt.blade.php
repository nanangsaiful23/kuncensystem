<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" type="image/gif" href="{{asset('assets/icon/education.png')}}" />
<title>@if(isset($default['page_name'])){{ $default['page_name'] }}@endif</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=Playfair+Display:wght@600&display=swap" rel="stylesheet">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  :root {
    --bg: #f7f5f2;
    --surface: #ffffff;
    --border: #e8e3dc;
    --text-primary: #1a1714;
    --text-secondary: #7a7268;
    --text-muted: #b0a99f;
    --accent: #c8401a;
    --accent-soft: #fdf0ec;
    --tag-bg: #f0ede8;
    --green: #2d6a4f;
    --green-soft: #eaf4ef;
    --wa: #25D366;
    --wa-dark: #1da851;
    --shadow-sm: 0 1px 3px rgba(0,0,0,.06);
    --shadow-md: 0 4px 16px rgba(0,0,0,.08);
    --radius: 12px;
    --radius-sm: 8px;
  }

  body {
    font-family: 'DM Sans', sans-serif;
    background: var(--bg);
    color: var(--text-primary);
    min-height: 100vh;
    padding: 0 0 60px;
  }

  /* ── Header ── */
  header {
    background: var(--surface);
    border-bottom: 1px solid var(--border);
    padding: 20px 24px 0;
    position: sticky;
    top: 0;
    z-index: 100;
    box-shadow: var(--shadow-sm);
  }

  .header-top {
    display: flex;
    align-items: center;
    gap: 12px;
    max-width: 860px;
    margin: 0 auto;
  }

  .logo-mark {
    width: 38px;
    height: 38px;
    border-color: var(--accent);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }
  .logo-mark svg { width: 20px; height: 20px; fill: #fff; }

  .logo-text h1 {
    font-family: 'Playfair Display', serif;
    font-size: 18px;
    color: var(--text-primary);
    line-height: 1;
  }
  .logo-text span {
    font-size: 11px;
    color: var(--text-muted);
    letter-spacing: .06em;
    text-transform: uppercase;
  }

  /* ── Search Bar ── */
  .search-wrap {
    max-width: 860px;
    margin: 18px auto 0;
    padding-bottom: 16px;
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

  .btn-clear {
    width: 32px; height: 32px;
    border: none; background: transparent;
    cursor: pointer;
    color: var(--text-muted);
    border-radius: 6px;
    display: flex; align-items: center; justify-content: center;
    transition: background .15s, color .15s;
    flex-shrink: 0;
  }
  .btn-clear:hover { background: var(--tag-bg); color: var(--accent); }

  /* ── Stats bar ── */
  .stats-bar {
    max-width: 860px;
    margin: 0 auto;
    padding: 8px 0 14px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    flex-wrap: wrap;
  }

  .result-count {
    font-size: 13px;
    color: var(--text-secondary);
  }
  .result-count strong { color: var(--text-primary); font-weight: 600; }

  .filter-chips {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
  }
  .chip {
    font-size: 12px;
    padding: 4px 10px;
    border-radius: 20px;
    border: 1px solid var(--border);
    background: var(--surface);
    color: var(--text-secondary);
    cursor: pointer;
    transition: all .15s;
    white-space: nowrap;
  }
  .chip.active, .chip:hover {
    background: var(--accent);
    border-color: var(--accent);
    color: #fff;
  }

  /* ── Alat salin WhatsApp ── */
  .wa-tools {
    display: flex;
    align-items: center;
    gap: 6px 18px;
    flex-wrap: wrap;
    padding: 0 2px 14px;
    font-size: 13px;
    color: var(--text-secondary);
  }
  .wa-tools label {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    user-select: none;
  }
  .wa-tools input[type=checkbox] { accent-color: var(--green); width: 16px; height: 16px; }

  /* ── Main content ── */
  main {
    max-width: 860px;
    margin: 0 auto;
    padding: 0 16px;
  }

  /* ── Product Card ── */
  .product-grid {
    display: grid;
    gap: 12px;
  }

  .product-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 18px 20px;
    display: grid;
    grid-template-columns: auto 1fr auto;
    gap: 16px;
    align-items: start;
    transition: box-shadow .2s, transform .15s, border-color .15s;
    text-decoration: none;
    color: inherit;
    animation: fadeUp .25s ease both;
  }
  .product-card:hover {
    box-shadow: var(--shadow-md);
    transform: translateY(-1px);
  }
  .product-card.selected {
    border-color: var(--green);
    box-shadow: 0 0 0 2px var(--green);
  }

  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(8px); }
    to   { opacity: 1; transform: translateY(0); }
  }

  .card-left {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 10px;
  }

  .card-check {
    width: 24px;
    height: 24px;
    accent-color: var(--green);
    cursor: pointer;
  }

  .card-code {
    font-size: 14px;
    font-weight: 600;
    letter-spacing: .07em;
    text-transform: uppercase;
    color: var(--text-primary);
    background: var(--tag-bg);
    padding: 4px 8px;
    border-radius: 6px;
    white-space: nowrap;
    margin-top: 2px;
  }

  .card-body { min-width: 0; }

  .card-name {
    font-size: 18px;
    font-weight: 600;
    color: var(--text-primary);
    line-height: 1.4;
    margin-bottom: 6px;
  }
  .card-name mark {
    background: #fde68a;
    color: inherit;
    border-radius: 2px;
    padding: 0 1px;
  }

  .card-variants {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
  }
  .variant-tag {
    font-size: 14px;
    color: var(--text-primary);
    background: var(--tag-bg);
    padding: 3px 8px;
    border-radius: 20px;
  }

  /* Prices */
  .card-prices {
    display: flex;
    flex-direction: column;
    gap: 4px;
    align-items: flex-end;
    min-width: 130px;
  }

  .price-row {
    display: flex;
    align-items: center;
    gap: 6px;
    white-space: nowrap;
  }
  .price-unit {
    font-size: 14px;
    color: var(--text-primary);
    background: var(--tag-bg);
    padding: 2px 6px;
    border-radius: 4px;
    font-weight: 500;
  }
  .price-val {
    font-size: 16px;
    font-weight: 600;
    color: var(--text-primary);
  }

  /* Tombol salin per barang */
  .card-actions {
    margin-top: 12px;
    display: flex;
    justify-content: flex-end;
  }
  .btn-copy {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    border: none;
    background: var(--wa);
    color: #fff;
    font-family: inherit;
    font-size: 14px;
    font-weight: 600;
    padding: 8px 14px;
    border-radius: 20px;
    cursor: pointer;
    transition: background .15s;
  }
  .btn-copy:hover { background: var(--wa-dark); }
  .btn-copy svg { width: 16px; height: 16px; }

  /* Stock badge */
  .stock-badge {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 2px;
    margin-top: 10px;
  }
  .stock-label {
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: .07em;
    color: var(--text-primary);
    font-weight: 600;
  }
  .stock-val {
    font-size: 16px;
    font-weight: 700;
  }
  .stock-val.in  { color: var(--green); }
  .stock-val.low { color: #c97a00; }
  .stock-val.out { color: var(--accent); }

  .stock-bar {
    width: 60px;
    height: 4px;
    background: var(--border);
    border-radius: 2px;
    overflow: hidden;
    margin-top: 4px;
  }
  .stock-fill {
    height: 100%;
    border-radius: 2px;
    transition: width .4s;
  }
  .stock-fill.in  { background: var(--green); }
  .stock-fill.low { background: #e8a000; }
  .stock-fill.out { background: var(--border); }

  /* ── Divider ── */
  .card-divider {
    border: none;
    border-top: 1px solid var(--border);
    margin: 4px 0 8px;
  }

  /* ── Empty state ── */
  .empty {
    text-align: center;
    padding: 60px 24px;
    color: var(--text-muted);
  }
  .empty svg { width: 48px; height: 48px; margin-bottom: 12px; opacity: .4; }
  .empty p { font-size: 15px; }

  /* ── Bar salin terpilih ── */
  #waBar {
    position: fixed;
    left: 0; right: 0; bottom: 0;
    z-index: 200;
    background: var(--surface);
    border-top: 1px solid var(--border);
    box-shadow: 0 -4px 16px rgba(0,0,0,.08);
    padding: 12px 16px calc(12px + env(safe-area-inset-bottom, 0px));
    display: none;
    justify-content: center;
    gap: 10px;
    flex-wrap: wrap;
  }
  #waBar .btn-copy { font-size: 15px; padding: 10px 18px; }
  .btn-cancel {
    border: 1px solid var(--border);
    background: var(--surface);
    color: var(--text-secondary);
    font-family: inherit;
    font-size: 15px;
    padding: 10px 18px;
    border-radius: 20px;
    cursor: pointer;
  }
  .btn-cancel:hover { background: var(--tag-bg); }

  /* ── Toast ── */
  #waToast {
    position: fixed;
    top: 18px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 300;
    background: var(--wa);
    color: #fff;
    padding: 12px 24px;
    border-radius: 24px;
    font-size: 15px;
    font-weight: 500;
    box-shadow: var(--shadow-md);
    display: none;
  }

  /* ── Responsive ── */
  @media (max-width: 600px) {
    header { padding: 14px 16px 0; }
    main { padding: 0 12px; }

    .product-card {
      grid-template-columns: 1fr;
      gap: 10px;
      padding: 14px 16px;
    }
    .card-left { flex-direction: row; align-items: center; }
    .card-code { width: fit-content; }
    .card-prices { flex-direction: row; flex-wrap: wrap; align-items: center; gap: 8px; }
    .price-row { flex-direction: row; }
    .card-actions { justify-content: flex-start; }
    .stock-badge { flex-direction: row; align-items: center; margin-top: 0; }
    .stock-bar { width: 40px; }
  }
</style>
</head>
<body>

<header>
  <div class="header-top">
    <div class="logo-mark">
      <img src="{{asset('assets/icon/education.png')}}" style="width: 130%">
      <!-- <svg viewBox="0 0 24 24"><path d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2 9m12-9l2 9M9 21h6"/></svg> -->
    </div>
    <div class="logo-text">
      <h1>{{ config('app.name') }}</h1>
      <span>Katalog Produk</span>
    </div>
  </div>

  <div class="search-wrap">
    <div class="search-box">
      <span class="search-icon">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
      </span>
      <input id="searchInput" placeholder="Cari nama atau kode barang…" autocomplete="off" spellcheck="false" value="{{ $query }}">
      <button class="btn-search" id="btnSearch" title="Cari barang" onclick="changeInput()">
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
</header>

<main>
  <div class="stats-bar">
    <div class="result-count" id="resultCount">Menampilkan <strong id="countNum">{{ sizeof($goods) }}</strong> produk</div>
    <div class="filter-chips">
      <button class="chip" onclick="search('beras')">Beras</button>
      <button class="chip" onclick="search('minyak')">Minyak</button>
      <button class="chip" onclick="search('gula')">Gula</button>
      <button class="chip" onclick="search('tepung')">Tepung</button>
    </div>
  </div>

  @if(sizeof($goods) > 0)
    <!-- Alat salin ke WhatsApp -->
    <div class="wa-tools">
      <label><input type="checkbox" id="checkAll"> Pilih semua</label>
      <label><input type="checkbox" id="optStock"> Sertakan stok</label>
      <label><input type="checkbox" id="optCode"> Sertakan kode barang</label>
    </div>

    <div class="product-grid" id="productGrid">
      <?php $i = 0 ?>
      @foreach($goods as $good)
        @php
          // Data untuk teks WhatsApp (satu barang bisa punya beberapa satuan/harga)
          $waUnits = [];
          foreach ($good->good_units as $waUnit) {
              if ($waUnit->unit != null) {
                  $waUnits[] = [
                      'unit'  => $waUnit->unit->name,
                      'price' => showRupiah($waUnit->selling_price),
                      'qty'   => $waUnit->unit->quantity,
                      'base'  => $waUnit->unit->base,
                  ];
              }
          }

          $waPcs = $good->getPcsSellingPrice();
          $waStock = null;
          if ($waPcs != null) {
              $waStock = $good->getStock() . ' ' . $waPcs->unit->code
                       . ' (' . ($good->getStock() * $waPcs->unit->quantity) . ' ' . $waPcs->unit->base . ')';
          }

          $waData = [
              'name'  => $good->getFullName(),
              'code'  => $good->code,
              'units' => $waUnits,
              'stock' => $waStock,
          ];
        @endphp
        <div class="product-card" data-wa="{{ json_encode($waData) }}" @if($i % 2 == 0) style="background-color: {{ config('app.app_color') }}" @endif>
          <div class="card-left">
            <input type="checkbox" class="card-check" title="Pilih barang ini">
            <div class="card-code">{{ $good->code }}</div>
          </div>
          <div class="card-body">
            <div class="card-name">{{ $good->getFullName() }}</div>
            <!-- <div class="card-variants">
              ${p.detail.split(",").map(v => `<span class="variant-tag">${highlight(v.trim(), q)}</span>`).join("")}
            </div> -->
            <hr class="card-divider">
            <div class="card-prices">
              @foreach($good->good_units as $unit)
                @if($unit->unit != null)
                  <div class="price-row">
                    <span class="price-unit">{{ $unit->unit->name }}</span>
                    <span class="price-val">{{ showRupiah($unit->selling_price) }}</span>
                  </div>
                @else
                  {{ $unit }}
                @endif
              @endforeach
            </div>
            <div class="card-actions">
              <button type="button" class="btn-copy" onclick="copyOne(this)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                Salin untuk WA
              </button>
            </div>
          </div>
          <div>
            <div class="stock-badge">
              <span class="stock-label">Stok</span>
              <?php
                if($waPcs != null)
                {
                  $real = $good->getStock() * $waPcs->unit->quantity;
                }
                else
                {
                  $real = 0;
                }

                if($real <= 0)
                {
                  $cls = 'out';
                  $pct = 0;
                }
                elseif($real <= 10)
                {
                  $cls = 'low';
                  $pct = 50;
                }
                else
                {
                  $cls = 'in';
                  $pct = 100;
                }
              ?>
              <span class="stock-val {{ $cls }}">
                @if($waPcs != null)
                  {{ $good->getStock() . ' ' . $waPcs->unit->code . ' (' . $real . ' ' . $waPcs->unit->base . ')' }}
                @else
                  0
                @endif
              </span>
              <div class="stock-bar"><div class="stock-fill {{ $cls }}" style="width:{{ $pct }}%"></div></div>
            </div>
          </div>
        </div>
        <?php $i++ ?>
      @endforeach
    </div>
  @else
    <div class="empty" id="emptyState">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
        <line x1="8" y1="11" x2="14" y2="11"/>
      </svg>
      <p>Produk tidak ditemukan.<br>Coba kata kunci lain.</p>
    </div>
  @endif
</main>

<!-- Bar aksi untuk salin banyak barang -->
<div id="waBar">
  <button type="button" class="btn-copy" onclick="copySelected()">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
    Salin <span id="waCount">0</span> barang terpilih
  </button>
  <button type="button" class="btn-cancel" onclick="uncheckAll()">Batal pilih</button>
</div>

<div id="waToast"></div>

</body>

<script src="{{asset('assets/bower_components/jquery/dist/jquery.min.js')}}"></script>
<!-- jQuery UI 1.11.4 -->
<script src="{{asset('assets/bower_components/jquery-ui/jquery-ui.min.js')}}"></script>
<script>
  $(document).ready(function(){
      $("#searchInput").keyup( function(e){
        if(e.keyCode == 13)
        {
          changeInput();
        }
      });

      // Pilih semua
      $("#checkAll").change(function(){
          $(".card-check").prop('checked', $(this).is(':checked'));
          updateSelection();
      });

      // Pilih per barang
      $(".card-check").change(function(){
          updateSelection();
      });
  });

  function changeInput()
  {
    window.location = window.location.origin + '/search/' + document.getElementById("searchInput").value;
  }

  function search(keyword) 
  {
    window.location = window.location.origin + '/search/' + keyword;
  }

  function clearInput()
  {
    document.getElementById("searchInput").value = "";
    $("#searchInput").focus();
  }

  /* ====== Fitur salin ke WhatsApp ====== */

  // Jaga-jaga jika showRupiah() tidak memakai "Rp"
  function formatPrice(price)
  {
    var text = String(price).trim();
    return /^rp/i.test(text) ? text : 'Rp ' + text;
  }

  // Susun teks untuk satu barang (format WhatsApp: *tebal*)
  function buildGoodText(data)
  {
    var includeStock = $('#optStock').is(':checked');
    var includeCode  = $('#optCode').is(':checked');

    var lines = [];
    lines.push('*' + data.name + '*' + (includeCode ? ' (' + data.code + ')' : ''));

    if (data.units.length === 0)
    {
      lines.push('- Harga belum tersedia');
    }

    data.units.forEach(function(u){
      var label = '' + u.unit;
      // contoh: "1 Dus (isi 12 Pcs)"
      if (Number(u.qty) > 1 && u.base)
      {
        label += ' (isi ' + u.qty + ' ' + u.base + ')';
      }
      lines.push('- ' + label + ' : ' + formatPrice(u.price));
    });

    if (includeStock && data.stock)
    {
      lines.push('Stok: ' + data.stock);
    }

    return lines.join('\n');
  }

  function getCardData(card)
  {
    return JSON.parse($(card).attr('data-wa'));
  }

  // Salin satu barang (tombol di dalam kartu)
  function copyOne(btn)
  {
    var card = $(btn).closest('.product-card');
    copyToClipboard(buildGoodText(getCardData(card)), 'Teks barang disalin');
  }

  // Salin semua barang yang dicentang
  function copySelected()
  {
    var texts = [];
    $('.card-check:checked').each(function(){
      texts.push(buildGoodText(getCardData($(this).closest('.product-card'))));
    });

    if (texts.length === 0)
    {
      showToast('Belum ada barang yang dipilih', true);
      return;
    }

    // Pisahkan antar barang dengan baris kosong
    copyToClipboard(texts.join('\n\n'), texts.length + ' barang disalin');
  }

  function uncheckAll()
  {
    $('#checkAll').prop('checked', false);
    $('.card-check').prop('checked', false);
    updateSelection();
  }

  function updateSelection()
  {
    var checked = $('.card-check:checked').length;
    var total   = $('.card-check').length;

    $('#waCount').text(checked);
    $('#waBar').css('display', checked > 0 ? 'flex' : 'none');
    $('#checkAll').prop('checked', total > 0 && checked === total);

    $('.product-card').each(function(){
      $(this).toggleClass('selected', $(this).find('.card-check').is(':checked'));
    });

    // Beri ruang di bawah supaya bar tidak menutupi kartu terakhir
    $('body').css('padding-bottom', checked > 0 ? '110px' : '60px');
  }

  function copyToClipboard(text, successMessage)
  {
    if (navigator.clipboard && window.isSecureContext)
    {
      navigator.clipboard.writeText(text).then(function(){
        showToast(successMessage);
      }).catch(function(){
        fallbackCopy(text, successMessage);
      });
    }
    else
    {
      fallbackCopy(text, successMessage);
    }
  }

  // Cadangan untuk halaman tanpa HTTPS / browser lama
  function fallbackCopy(text, successMessage)
  {
    var textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.focus();
    textarea.select();

    try
    {
      var ok = document.execCommand('copy');
      showToast(ok ? successMessage : 'Gagal menyalin', !ok);
    }
    catch (err)
    {
      showToast('Gagal menyalin', true);
    }

    document.body.removeChild(textarea);
  }

  function showToast(message, isError)
  {
    $('#waToast')
      .stop(true, true)
      .text(message)
      .css('background', isError ? '#c8401a' : '#25D366')
      .fadeIn(150)
      .delay(1500)
      .fadeOut(300);
  }
</script>
</html>