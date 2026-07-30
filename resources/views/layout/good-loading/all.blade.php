<div class="content-wrapper">

  @include('layout' . '.alert-message', ['type' => $default['type'], 'data' => $default['data'], 'color' => $default['color']])

  @php
      $totalLoading   = $good_loadings->sum('total_item_price');
      $totalCash      = $cash->sum('total_item_price');
      $jumlahLoading  = method_exists($good_loadings, 'total') ? $good_loadings->total() : $good_loadings->count();
      $rataRata       = $jumlahLoading > 0 ? ($totalLoading / $jumlahLoading) : 0;
      $jumlahInternal = 0;
      foreach ($good_loadings as $gl) { if ($gl->type == 'internal') $jumlahInternal++; }
  @endphp

  <style>
  .gl-wrap { padding: 1rem 1.25rem 2rem; font-family: 'Segoe UI', system-ui, sans-serif; }
  .gl-wrap *, .gl-wrap *::before, .gl-wrap *::after { box-sizing: border-box; }
  .gl-wrap {
      --ink:#0f172a; --ink2:#475569; --ink3:#94a3b8;
      --surf:#f8fafc; --card:#fff; --bdr:#e2e8f0; --bdr2:#f1f5f9;
      --blue:#2563eb; --blue-bg:#eff6ff; --blue-b:#bfdbfe;
      --green:#16a34a; --green-bg:#f0fdf4; --green-b:#bbf7d0;
      --orange:#d97706; --orange-bg:#fffbeb; --orange-b:#fde68a;
      --purple:#6d28d9; --purple-bg:#f5f3ff; --purple-b:#ddd6fe;
      --red:#dc2626; --red-bg:#fef2f2; --red-b:#fecaca;
  }

  /* Header */
  .gl-head { display:flex; justify-content:space-between; align-items:flex-start;
             flex-wrap:wrap; gap:.75rem; margin-bottom:1rem; }
  .gl-head h1 { font-size:1.25rem; font-weight:800; color:var(--ink); margin:0; }
  .gl-head p  { font-size:1.2rem; color:var(--ink2); margin:.15rem 0 0; }
  .gl-badge   { background:var(--blue-bg); border:1px solid var(--blue-b); color:var(--blue);
                font-size:.95rm; font-weight:600; padding:.3rem .75rem;
                border-radius:9999px; white-space:nowrap; align-self:flex-start; }

  /* KPI */
  .gl-kpi-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(180px,1fr));
                 gap:.625rem; margin-bottom:.875rem; }
  .gl-kpi { background:var(--card); border:1px solid var(--bdr); border-radius:.6rem;
            padding:.75rem .875rem; border-left:4px solid var(--blue); }
  .gl-kpi .kl { font-size:.65rem; font-weight:700; color:var(--ink3);
                text-transform:uppercase; letter-spacing:.07em; margin-bottom:.3rem; }
  .gl-kpi .kv { font-size:1.25rem; font-weight:800; color:var(--ink); line-height:1; }
  .gl-kpi .ks { font-size:.7rem; color:var(--ink3); margin-top:.25rem; }
  .gl-kpi.k-green { border-left-color:var(--green); } .gl-kpi.k-green .kv { color:var(--green); }
  .gl-kpi.k-orange { border-left-color:var(--orange); } .gl-kpi.k-orange .kv { color:var(--orange); }
  .gl-kpi.k-purple { border-left-color:var(--purple); } .gl-kpi.k-purple .kv { color:var(--purple); }

  /* Filter card (server-side: tanggal, distributor, show) */
  .gl-filter { background:var(--card); border:1px solid var(--bdr); border-radius:.6rem;
               padding:.85rem 1rem; margin-bottom:.75rem; }
  .gl-filter-row { display:flex; align-items:flex-end; gap:.6rem; flex-wrap:wrap; }
  .gl-fg { display:flex; flex-direction:column; gap:.2rem; }
  .gl-fg label { font-size:.65rem; font-weight:700; color:var(--ink3);
                 text-transform:uppercase; letter-spacing:.08em; }
  .gl-fg input, .gl-fg select { padding:.38rem .65rem; border:1px solid var(--bdr);
      border-radius:.4rem; font-size:1.2rem; color:var(--ink); background:var(--surf);
      min-width:130px; line-height:1.4; }
  .gl-fg input:focus, .gl-fg select:focus { outline:none; border-color:var(--blue); }
  .gl-presets { display:flex; gap:.35rem; flex-wrap:wrap; margin-top:.6rem; }
  .gl-preset-btn { padding:.28rem .7rem; background:var(--surf); border:1px solid var(--bdr);
                   color:var(--ink2); border-radius:9999px; font-size:.95rm; font-weight:600;
                   cursor:pointer; }
  .gl-preset-btn:hover { border-color:var(--blue); color:var(--blue); background:var(--blue-bg); }

  /* Quick filter (client side) */
  .gl-quick { background:var(--card); border:1px solid var(--bdr); border-radius:.6rem;
              padding:.75rem 1rem; margin-bottom:.875rem;
              display:flex; align-items:flex-end; gap:.6rem; flex-wrap:wrap; }
  .gl-quick .gl-fg input, .gl-quick .gl-fg select { min-width:150px; }
  .gl-pills { display:flex; gap:.35rem; flex-wrap:wrap; }
  .gl-pill { padding:.3rem .8rem; border-radius:9999px; font-size:.75rem; font-weight:600;
             cursor:pointer; border:2px solid var(--bdr); background:var(--surf); color:var(--ink2);
             white-space:nowrap; }
  .gl-pill.active { background:var(--blue); border-color:var(--blue); color:#fff; }
  .gl-pill.p-internal.active { background:var(--purple); border-color:var(--purple); }
  .gl-pill.p-external.active { background:var(--green); border-color:var(--green); }

  /* Table card */
  .gl-tbl-card { background:var(--card); border:1px solid var(--bdr);
                 border-radius:.6rem; overflow:hidden; margin-bottom:1rem; }
  .gl-tbl-head { padding:.625rem 1rem; border-bottom:1px solid var(--bdr);
                 display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:.5rem;}
  .gl-tbl-head h2 { font-size:1.25rem; font-weight:700; color:var(--ink); margin:0; }
  .gl-tbl-cnt { font-size:1.2rem; color:var(--ink3); }
  .gl-tbl-wrap { overflow-x:auto; }
  .gl-tbl-wrap table { width:100%; border-collapse:collapse; font-size:1.1rem; min-width:1050px; }
  .gl-tbl-wrap thead th { position:sticky; top:0; z-index:2;
      background:var(--surf); padding:.6rem .75rem; text-align:left;
      font-weight:700; color:var(--ink2); border-bottom:2px solid var(--bdr); white-space:nowrap; }
  .gl-tbl-wrap tbody td { padding:.6rem .75rem; border-bottom:1px solid var(--bdr2);
                          color:var(--ink); vertical-align:middle; white-space:nowrap; }
  .gl-tbl-wrap tbody tr:nth-child(even) td { background:#f8fafc; }
  .gl-tbl-wrap tbody tr:nth-child(odd) td { background:#fff; }
  .gl-tbl-wrap tbody tr:hover td { background:#eff6ff !important; }
  .gl-tbl-wrap tbody tr.gl-internal:nth-child(even) td { background:#fdfaff; }
  .gl-tbl-wrap tbody tr.gl-internal:nth-child(odd) td { background:#fbf7ff; }
  .gl-tbl-wrap tbody tr.gl-internal:hover td { background:#f5f3ff !important; }
  .gl-tbl-wrap tbody tr.gl-hide { display:none; }
  .tr { text-align:right; } .tc { text-align:center; }

  .gl-badge-tag { display:inline-flex; align-items:center; gap:.2rem; padding:.18rem .55rem;
                  border-radius:9999px; font-size:.95rm; font-weight:700; white-space:nowrap; }
  .b-purple { background:var(--purple-bg); color:var(--purple); border:1px solid var(--purple-b); }
  .b-green  { background:var(--green-bg);  color:var(--green);  border:1px solid var(--green-b); }
  .b-blue   { background:var(--blue-bg);   color:var(--blue);   border:1px solid var(--blue-b); }
  .b-gry    { background:#f1f5f9; color:#475569; border:1px solid #e2e8f0; }

  .gl-icon-link { color:var(--blue); font-size:1rem; text-decoration:none; }
  .gl-icon-link:hover { color:var(--purple); }
  .gl-icon-del { color:var(--red); cursor:pointer; background:none; border:none; font-size:1rem; }
  .gl-icon-del:hover { color:#991b1b; }

  .gl-empty { text-align:center; padding:2.5rem 1rem; color:var(--ink3); }
  .gl-empty .ei { font-size:2rem; margin-bottom:.5rem; }

  .gl-pagi-wrap { padding:.75rem 1rem; border-top:1px solid var(--bdr); background:var(--surf); }
  .gl-pagi-wrap .pagination { margin:0; }

  @media(max-width:768px) {
      .gl-kpi-grid { grid-template-columns:repeat(2,1fr); }
      .gl-filter-row, .gl-quick { flex-direction:column; align-items:stretch; }
      .gl-fg input, .gl-fg select, .gl-quick .gl-fg input, .gl-quick .gl-fg select { min-width:unset; width:100%; }
  }
  </style>

  <section class="content">
    <div class="gl-wrap">

      {{-- Header --}}
      <div class="gl-head">
        <div>
          <h1>📦 Daftar Loading Barang</h1>
          <p>Riwayat pembelian / loading barang dari distributor</p>
        </div>
        <span class="gl-badge">
          {{ displayDate($start_date) }} → {{ displayDate($end_date) }}
        </span>
      </div>

      {{-- KPI --}}
      <div class="gl-kpi-grid">
        <div class="gl-kpi k-green">
          <div class="kl">Total Loading</div>
          <div class="kv">{{ showRupiah($totalLoading) }}</div>
          <div class="ks">Periode terpilih</div>
        </div>
        <div class="gl-kpi k-orange">
          <div class="kl">Loading Kas di Tangan</div>
          <div class="kv">{{ showRupiah($totalCash) }}</div>
          <div class="ks">Dibayar tunai langsung</div>
        </div>
        <div class="gl-kpi">
          <div class="kl">Jumlah Loading</div>
          <div class="kv">{{ number_format($jumlahLoading) }}</div>
          <div class="ks">Transaksi loading</div>
        </div>
        <div class="gl-kpi k-purple">
          <div class="kl">Rata-rata / Loading</div>
          <div class="kv" style="font-size:1.05rem">{{ showRupiah($rataRata) }}</div>
          <div class="ks">{{ $jumlahInternal }} bersifat internal (halaman ini)</div>
        </div>
      </div>

      {{-- Filter server-side --}}
      <div class="gl-filter">
        <div class="gl-filter-row">
          <div class="gl-fg">
            <label>Tampilkan</label>
            {!! Form::select('show', getPaginations(), $pagination, ['class' => 'form-control', 'id' => 'show', 'onchange' => 'advanceSearch()']) !!}
          </div>
          <div class="gl-fg">
            <label>Tanggal Awal</label>
            <input type="text" class="form-control" id="datepicker" name="start_date" value="{{ $start_date }}" onchange="changeDate()" placeholder="yyyy-mm-dd">
          </div>
          <div class="gl-fg">
            <label>Tanggal Akhir</label>
            <input type="text" class="form-control" id="datepicker2" name="end_date" value="{{ $end_date }}" onchange="changeDate()" placeholder="yyyy-mm-dd">
          </div>
          <div class="gl-fg" style="min-width:220px">
            <label>Distributor</label>
            {!! Form::select('distributor', getDistributorLoading($distributor_id, $start_date, $end_date), $distributor_id, ['class' => 'form-control select2', 'style'=>'width:100%', 'id' => 'distributor', 'onchange' => 'advanceSearch()']) !!}
          </div>
        </div>
        <div class="gl-presets">
          <span class="gl-preset-btn" onclick="setRange(7)">7 Hari Terakhir</span>
          <span class="gl-preset-btn" onclick="setRange(30)">30 Hari Terakhir</span>
          <span class="gl-preset-btn" onclick="setRange(90)">90 Hari Terakhir</span>
          <span class="gl-preset-btn" onclick="setThisMonth()">Bulan Ini</span>
        </div>
      </div>

      {{-- Quick filter client-side --}}
      <div class="gl-quick">
        <div class="gl-fg" style="min-width:220px">
          <label>Cari (PIC / Distributor / Catatan / ID)</label>
          <input type="text" id="glSearch" placeholder="Ketik untuk mencari..." oninput="glApplyFilter()">
        </div>
        <div class="gl-fg">
          <label>Payment</label>
          <select id="glPayment" onchange="glApplyFilter()">
            <option value="">Semua Payment</option>
          </select>
        </div>
        <div class="gl-fg">
          <label>Jenis</label>
          <div class="gl-pills">
            <span class="gl-pill active" data-type="all" onclick="glSetType('all', this)">Semua</span>
            <span class="gl-pill p-internal" data-type="internal" onclick="glSetType('internal', this)">Internal</span>
            <span class="gl-pill p-external" data-type="external" onclick="glSetType('external', this)">Eksternal</span>
          </div>
        </div>
      </div>

      {{-- Tabel --}}
      <div class="gl-tbl-card">
        <div class="gl-tbl-head">
          <h2>📋 Detail Loading</h2>
          <div class="gl-tbl-cnt" id="glCounter">Menampilkan semua baris</div>
        </div>
        <div class="gl-tbl-wrap">
          <table id="example1">
            <thead>
              <tr>
                <th>Dibuat</th>
                <th>PIC</th>
                <th>Payment</th>
                <th>Jenis</th>
                <th>ID</th>
                <th>Tanggal</th>
                <th>Distributor</th>
                <th class="tr">Total Loading</th>
                <th>Catatan</th>
                <th>User</th>
                <th class="tc">Detail</th>
                <th class="tc">Print</th>
                @if(\Auth::user()->email == 'admin')
                  <th class="tc">Edit</th>
                  <th class="tc">Hapus</th>
                @endif
              </tr>
            </thead>
            <tbody id="table-good">
              @forelse($good_loadings as $good_loading)
                @php
                    $isInternal = $good_loading->type == 'internal';
                    $distNama   = $good_loading->getDistributor()->name;
                    $paymentTxt = $good_loading->paymentObj();
                    $picNama    = $good_loading->actor()->name;
                    $searchStr  = strtolower($picNama . ' ' . $distNama . ' ' . $good_loading->note . ' ' . $good_loading->id . ' ' . $good_loading->type . ' ' . $paymentTxt);
                @endphp
                <tr class="{{ $isInternal ? 'gl-internal' : '' }}"
                    data-type="{{ $isInternal ? 'internal' : 'external' }}"
                    data-payment="{{ $paymentTxt }}"
                    data-search="{{ $searchStr }}">
                  <td>{{ $good_loading->created_at }}</td>
                  <td>{{ $picNama }}</td>
                  <td><span class="gl-badge-tag b-blue">{{ $paymentTxt }}</span></td>
                  <td>
                    @if($isInternal)
                      <span class="gl-badge-tag b-purple">Internal</span>
                    @else
                      <span class="gl-badge-tag b-green">Eksternal</span>
                    @endif
                  </td>
                  <td>{{ $good_loading->id }}</td>
                  <td>{{ displayDate($good_loading->loading_date) }}</td>
                  <td>{{ $distNama }}</td>
                  <td class="tr" style="font-weight:700">{{ showRupiah($good_loading->total_item_price) }}</td>
                  <td style="max-width:220px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap" title="{{ $good_loading->note }}">{{ $good_loading->note }}</td>
                  <td>{{ $picNama }}</td>
                  <td class="tc">
                    <a class="gl-icon-link" href="{{ url($role . '/good-loading/' . $good_loading->id . '/detail') }}" title="Detail">
                      <i class="fa fa-hand-o-right" aria-hidden="true"></i>
                    </a>
                  </td>
                  <td class="tc">
                    <a class="gl-icon-link" href="{{ url($role . '/good-loading/' . $good_loading->id . '/print') }}" title="Print">
                      <i class="fa fa-print" aria-hidden="true"></i>
                    </a>
                  </td>
                  @if(\Auth::user()->email == 'admin')
                    <td class="tc">
                      <a class="gl-icon-link" href="{{ url($role . '/good-loading/' . $good_loading->id . '/edit') }}" title="Edit">
                        <i class="fa fa-file" aria-hidden="true"></i>
                      </a>
                    </td>
                    <td class="tc">
                      <button type="button" class="gl-icon-del" data-toggle="modal" data-target="#modal-danger-{{ $good_loading->id }}" title="Hapus">
                        <i class="fa fa-times" aria-hidden="true"></i>
                      </button>

                      @include('layout' . '.delete-modal', ['id' => $good_loading->id, 'data' => $good_loading->created_at . ' ' . $distNama, 'formName' => 'delete-form-' . $good_loading->id])

                      <form id="delete-form-{{ $good_loading->id }}" action="{{ url($role . '/good-loading/' . $good_loading->id . '/delete') }}" method="POST" style="display: none;">
                        {{ csrf_field() }}
                        {{ method_field('DELETE') }}
                      </form>
                    </td>
                  @endif
                </tr>
              @empty
                <tr>
                  <td colspan="14">
                    <div class="gl-empty"><div class="ei">📭</div><p>Tidak ada data loading pada periode/filter ini.</p></div>
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        @if($pagination != 'all')
        <div class="gl-pagi-wrap" id="renderField">
          {{ $good_loadings->render() }}
        </div>
        @endif
      </div>

    </div>
  </section>
</div>

@section('js-addon')
  <script type="text/javascript">
    $(document).ready(function(){
        $('.select2').select2();
      $('#datepicker').datepicker({
        autoclose: true,
        format: 'yyyy-mm-dd'
      })

      $('#datepicker2').datepicker({
        autoclose: true,
        format: 'yyyy-mm-dd'
      })

      $("#search-input").keyup( function(e){
        if(e.keyCode == 13)
        {
          ajaxFunction();
        }
      });

      $("#search-btn").click(function(){
          ajaxFunction();
      });

      glBuildPaymentOptions();
      glApplyFilter();
    });

    function changeDate()
    {
        var distributor = $('#distributor').val();
      window.location = window.location.origin + '/{{ $role }}/good-loading/' + $("#datepicker").val() + '/' + $("#datepicker2").val() +'/'+distributor +'/{{ $pagination }}';
    }

    function advanceSearch()
    {
      var show        = $('#show').val();
      var distributor = $('#distributor').val();
      window.location = window.location.origin + '/{{ $role }}/good-loading/{{ $start_date }}/{{ $end_date }}/'+distributor+'/' + show;
    }

    /* ── Preset tanggal cepat ─────────────────────────────────────── */
    function pad(n) { return n < 10 ? '0' + n : '' + n; }
    function fmt(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }

    function setRange(days)
    {
        var end   = new Date();
        var start = new Date();
        start.setDate(end.getDate() - days);
        $('#datepicker').val(fmt(start));
        $('#datepicker2').val(fmt(end));
        changeDate();
    }

    function setThisMonth()
    {
        var now   = new Date();
        var start = new Date(now.getFullYear(), now.getMonth(), 1);
        $('#datepicker').val(fmt(start));
        $('#datepicker2').val(fmt(now));
        changeDate();
    }

    /* ── Quick filter client-side (hanya mempengaruhi baris yang sedang tampil) ── */
    var glCurrentType = 'all';

    function glBuildPaymentOptions()
    {
        var payments = [];
        $('#table-good tr[data-payment]').each(function(){
            var p = $(this).attr('data-payment');
            if (p && payments.indexOf(p) === -1) payments.push(p);
        });
        payments.sort();
        var sel = $('#glPayment');
        payments.forEach(function(p){
            sel.append('<option value="' + p + '">' + p + '</option>');
        });
    }

    function glSetType(type, el)
    {
        glCurrentType = type;
        $('.gl-pill').removeClass('active');
        $(el).addClass('active');
        glApplyFilter();
    }

    function glApplyFilter()
    {
        var keyword = ($('#glSearch').val() || '').toLowerCase().trim();
        var payment = $('#glPayment').val() || '';
        var total = 0, shown = 0;

        $('#table-good tr[data-search]').each(function(){
            var row = $(this);
            total++;

            var matchKeyword = !keyword || (row.attr('data-search') || '').indexOf(keyword) !== -1;
            var matchType    = (glCurrentType === 'all') || (row.attr('data-type') === glCurrentType);
            var matchPayment = !payment || (row.attr('data-payment') === payment);

            if (matchKeyword && matchType && matchPayment) {
                row.removeClass('gl-hide');
                shown++;
            } else {
                row.addClass('gl-hide');
            }
        });

        if (total > 0) {
            $('#glCounter').text('Menampilkan ' + shown + ' dari ' + total + ' baris (halaman ini)');
        }
    }
  </script>
@endsection