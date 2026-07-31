<div class="content-wrapper">

  @include('layout' . '.alert-message', ['type' => $default['type'], 'data' => $default['data'], 'color' => $default['color']])

  @php
      $type           = $type ?? '';
      $payment        = $payment ?? '';
      $keyword        = $keyword ?? '';
      $excludeCash    = $excludeCash ?? false;
      $excludeSystem  = $excludeSystem ?? false;
      $paymentOptions = $paymentOptions ?? collect();
      $jumlahLoading  = $totalFiltered ?? 0;
      $shownOnPage    = $shownCount ?? (is_countable($good_loadings) ? count($good_loadings) : 0);
      $totalLoading   = $totalLoadingSum ?? 0;
      $totalCash      = $totalCashSum ?? 0;
      $rataRata       = $jumlahLoading > 0 ? ($totalLoading / $jumlahLoading) : 0;
      $filterAktif    = ($type !== '') || ($payment !== '') || ($keyword !== '') || $excludeCash || $excludeSystem;
  @endphp

  <style>
  .gl-wrap {
      padding: 1.25rem 1.5rem 2.25rem;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
      font-size: 15px;
      color: #1a1d23;
  }
  .gl-wrap *, .gl-wrap *::before, .gl-wrap *::after { box-sizing: border-box; }
  .gl-wrap {
      --ink:#1a1d23; --ink2:#4b5061; --ink3:#8b90a0;
      --surf:#f7f8fa; --card:#fff; --bdr:#e3e6ec; --bdr2:#eef0f4;
      --blue:#2563eb; --blue-bg:#eef3ff; --blue-b:#c3d6ff;
      --green:#178a4c; --green-bg:#eefaf3; --green-b:#bfe9d2;
      --orange:#c2660f; --orange-bg:#fff6ec; --orange-b:#f7d9ab;
      --purple:#6d28d9; --purple-bg:#f5f2ff; --purple-b:#ddd2fb;
      --red:#d1352b; --red-bg:#fdefee; --red-b:#f6c6c2;
  }

  /* Header */
  .gl-head { display:flex; justify-content:space-between; align-items:flex-start;
             flex-wrap:wrap; gap:.85rem; margin-bottom:1.25rem; }
  .gl-head h1 { font-size:1.55rem; font-weight:800; color:var(--ink); margin:0; letter-spacing:-.01em; }
  .gl-head p  { font-size:.95rem; color:var(--ink2); margin:.25rem 0 0; }
  .gl-badge   { background:var(--blue-bg); border:1px solid var(--blue-b); color:var(--blue);
                font-size:.9rem; font-weight:600; padding:.4rem .9rem;
                border-radius:9999px; white-space:nowrap; align-self:flex-start; }

  /* KPI */
  .gl-kpi-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(200px,1fr));
                 gap:.85rem; margin-bottom:1.1rem; }
  .gl-kpi { background:var(--card); border:1px solid var(--bdr); border-radius:.7rem;
            padding:.95rem 1.1rem; border-left:4px solid var(--blue); }
  .gl-kpi .kl { font-size:.8rem; font-weight:700; color:var(--ink3);
                text-transform:uppercase; letter-spacing:.06em; margin-bottom:.4rem; }
  .gl-kpi .kv { font-size:1.55rem; font-weight:800; color:var(--ink); line-height:1.15; }
  .gl-kpi .ks { font-size:.85rem; color:var(--ink3); margin-top:.3rem; }
  .gl-kpi.k-green { border-left-color:var(--green); } .gl-kpi.k-green .kv { color:var(--green); }
  .gl-kpi.k-orange { border-left-color:var(--orange); } .gl-kpi.k-orange .kv { color:var(--orange); }
  .gl-kpi.k-purple { border-left-color:var(--purple); } .gl-kpi.k-purple .kv { color:var(--purple); }

  /* Filter card — SATU grup, semua diproses backend */
  .gl-filter { background:var(--card); border:1px solid var(--bdr); border-radius:.7rem;
               padding:1.1rem 1.25rem; margin-bottom:1.1rem; }
  .gl-filter-row { display:flex; align-items:flex-end; gap:.85rem; flex-wrap:wrap; }
  .gl-fg { display:flex; flex-direction:column; gap:.3rem; }
  .gl-fg label { font-size:.8rem; font-weight:700; color:var(--ink2);
                 text-transform:uppercase; letter-spacing:.05em; }
  .gl-fg input[type=text], .gl-fg select { padding:.55rem .8rem; border:1.5px solid var(--bdr);
      border-radius:.5rem; font-size:.95rem; color:var(--ink); background:var(--surf);
      min-width:150px; line-height:1.4; font-family:inherit; }
  .gl-fg input:focus, .gl-fg select:focus { outline:none; border-color:var(--blue); background:#fff; }
  .gl-btn-prim { padding:.58rem 1.2rem; background:var(--blue); color:#fff; border:none;
                 border-radius:.5rem; font-size:.95rem; font-weight:700; cursor:pointer;
                 align-self:flex-end; white-space:nowrap; }
  .gl-btn-prim:hover { background:#1d4fd1; }
  .gl-btn-reset { padding:.58rem 1rem; background:var(--surf); color:var(--ink2);
                  border:1.5px solid var(--bdr); border-radius:.5rem; font-size:.95rem;
                  cursor:pointer; align-self:flex-end; text-decoration:none;
                  display:inline-block; white-space:nowrap; font-weight:600; }
  .gl-presets { display:flex; gap:.5rem; flex-wrap:wrap; margin-top:.85rem; }
  .gl-preset-btn { padding:.4rem .95rem; background:var(--surf); border:1.5px solid var(--bdr);
                   color:var(--ink2); border-radius:9999px; font-size:.85rem; font-weight:600;
                   cursor:pointer; }
  .gl-preset-btn:hover { border-color:var(--blue); color:var(--blue); background:var(--blue-bg); }
  .gl-divider { height:1px; background:var(--bdr2); margin:1rem 0; }

  .gl-pills { display:flex; gap:.5rem; flex-wrap:wrap; }
  .gl-pill { padding:.42rem 1rem; border-radius:9999px; font-size:.88rem; font-weight:600;
             cursor:pointer; border:2px solid var(--bdr); background:var(--surf); color:var(--ink2);
             white-space:nowrap; }
  .gl-pill.active { background:var(--blue); border-color:var(--blue); color:#fff; }
  .gl-pill.p-internal.active { background:var(--purple); border-color:var(--purple); }
  .gl-pill.p-external.active { background:var(--green); border-color:var(--green); }

  .gl-check-row { display:flex; gap:1.25rem; flex-wrap:wrap; }
  .gl-check { display:flex; align-items:center; gap:.5rem; font-size:.92rem;
              color:var(--ink2); font-weight:600; cursor:pointer; user-select:none; }
  .gl-check input[type=checkbox] { width:1.05rem; height:1.05rem; cursor:pointer; accent-color:var(--blue); }

  .gl-active-chip { display:inline-flex; align-items:center; gap:.35rem; background:var(--blue-bg);
                    border:1px solid var(--blue-b); color:var(--blue); font-size:.85rem;
                    font-weight:600; padding:.3rem .8rem; border-radius:9999px; margin-right:.4rem;
                    margin-bottom:.3rem; }

  /* Table card */
  .gl-tbl-card { background:var(--card); border:1px solid var(--bdr);
                 border-radius:.7rem; overflow:hidden; margin-bottom:1.1rem; }
  .gl-tbl-head { padding:.85rem 1.25rem; border-bottom:1px solid var(--bdr);
                 display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:.6rem;}
  .gl-tbl-head h2 { font-size:1.05rem; font-weight:700; color:var(--ink); margin:0; }
  .gl-tbl-cnt { font-size:.92rem; color:var(--ink3); }
  .gl-tbl-wrap { overflow-x:auto; }
  .gl-tbl-wrap table { width:100%; border-collapse:collapse; font-size:.95rem; min-width:1100px; }
  .gl-tbl-wrap thead th { position:sticky; top:0; z-index:2;
      background:var(--surf); padding:.8rem .9rem; text-align:left;
      font-weight:700; color:var(--ink2); border-bottom:2px solid var(--bdr); white-space:nowrap; }
  .gl-tbl-wrap tbody td { padding:.8rem .9rem; border-bottom:1px solid var(--bdr2);
                          color:var(--ink); vertical-align:middle; white-space:nowrap; }
  .gl-tbl-wrap tbody tr:nth-child(even) td { background:#f8fafc; }
  .gl-tbl-wrap tbody tr:nth-child(odd) td { background:#fff; }
  .gl-tbl-wrap tbody tr:hover td { background:#eff6ff !important; }
  .gl-tbl-wrap tbody tr.gl-internal:nth-child(even) td { background:#fdfaff; }
  .gl-tbl-wrap tbody tr.gl-internal:nth-child(odd) td { background:#fbf7ff; }
  .gl-tbl-wrap tbody tr.gl-internal:hover td { background:#f5f3ff !important; }
  .tr { text-align:right; } .tc { text-align:center; }

  .gl-badge-tag { display:inline-flex; align-items:center; gap:.25rem; padding:.25rem .7rem;
                  border-radius:9999px; font-size:.82rem; font-weight:700; white-space:nowrap; }
  .b-purple { background:var(--purple-bg); color:var(--purple); border:1px solid var(--purple-b); }
  .b-green  { background:var(--green-bg);  color:var(--green);  border:1px solid var(--green-b); }
  .b-blue   { background:var(--blue-bg);   color:var(--blue);   border:1px solid var(--blue-b); }
  .b-gry    { background:#f1f5f9; color:#475569; border:1px solid #e2e8f0; }

  .gl-icon-link { color:var(--blue); font-size:1.1rem; text-decoration:none; }
  .gl-icon-link:hover { color:var(--purple); }
  .gl-icon-del { color:var(--red); cursor:pointer; background:none; border:none; font-size:1.1rem; }
  .gl-icon-del:hover { color:#991b1b; }

  .gl-empty { text-align:center; padding:3rem 1rem; color:var(--ink3); font-size:1rem; }
  .gl-empty .ei { font-size:2.2rem; margin-bottom:.6rem; }

  .gl-pagi-wrap { padding:.85rem 1.25rem; border-top:1px solid var(--bdr); background:var(--surf); font-size:.92rem; }
  .gl-pagi-wrap .pagination { margin:0; }
  .gl-pagi-wrap .pagination > li > a,
  .gl-pagi-wrap .pagination > li > span { font-size:.9rem; }

  @media(max-width:768px) {
      .gl-kpi-grid { grid-template-columns:repeat(2,1fr); }
      .gl-filter-row { flex-direction:column; align-items:stretch; }
      .gl-fg input, .gl-fg select { min-width:unset; width:100%; }
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
          <div class="ks">Sesuai filter aktif</div>
        </div>
        <div class="gl-kpi k-orange">
          <div class="kl">Loading Kas di Tangan</div>
          <div class="kv">{{ showRupiah($totalCash) }}</div>
          <div class="ks">Dibayar tunai langsung</div>
        </div>
        <div class="gl-kpi">
          <div class="kl">Jumlah Loading</div>
          <div class="kv">{{ number_format($jumlahLoading) }}</div>
          <div class="ks">Total hasil filter</div>
        </div>
        <div class="gl-kpi k-purple">
          <div class="kl">Rata-rata / Loading</div>
          <div class="kv" style="font-size:1.25rem">{{ showRupiah($rataRata) }}</div>
          <div class="ks">Dari total hasil filter</div>
        </div>
      </div>

      {{-- Filter — SATU grup, semua parameter diproses backend --}}
      <div class="gl-filter">
        <div class="gl-filter-row">
          <div class="gl-fg">
            <label>Tampilkan</label>
            {!! Form::select('show', getPaginations(), $pagination, ['class' => 'form-control', 'id' => 'show']) !!}
          </div>
          <div class="gl-fg">
            <label>Tanggal Awal</label>
            <input type="text" class="form-control" id="datepicker" value="{{ $start_date }}" placeholder="yyyy-mm-dd">
          </div>
          <div class="gl-fg">
            <label>Tanggal Akhir</label>
            <input type="text" class="form-control" id="datepicker2" value="{{ $end_date }}" placeholder="yyyy-mm-dd">
          </div>
          <div class="gl-fg" style="min-width:230px">
            <label>Distributor</label>
            {!! Form::select('distributor', getDistributorLoading($distributor_id, $start_date, $end_date), $distributor_id, ['class' => 'form-control select2', 'style'=>'width:100%', 'id' => 'distributor']) !!}
          </div>
          <div class="gl-fg">
            <label>Payment</label>
            <select id="glPayment">
              <option value="">Semua Payment</option>
              @foreach($paymentOptions as $po)
                <option value="{{ $po->code }}" {{ $payment === $po->code ? 'selected' : '' }}>{{ $po->name }} ({{ $po->code }})</option>
              @endforeach
            </select>
          </div>
          <div class="gl-fg" style="min-width:230px">
            <label>Cari (ID / PIC / Distributor / Catatan)</label>
            <input type="text" id="glKeyword" value="{{ $keyword }}" placeholder="Ketik lalu Enter / klik Terapkan...">
          </div>
          <button type="button" class="gl-btn-prim" onclick="glTerapkan()">🔍 Terapkan</button>
          <a href="javascript:void(0)" class="gl-btn-reset" onclick="glReset()">Reset</a>
        </div>

        <div class="gl-divider"></div>

        <div class="gl-filter-row" style="align-items:center">
          <div class="gl-fg">
            <label>Jenis</label>
            <div class="gl-pills">
              <span class="gl-pill {{ $type === '' ? 'active' : '' }}" onclick="glSetType('')">Semua</span>
              <span class="gl-pill p-internal {{ $type === 'internal' ? 'active' : '' }}" onclick="glSetType('internal')">Internal</span>
              <span class="gl-pill p-external {{ $type === 'external' ? 'active' : '' }}" onclick="glSetType('external')">Eksternal</span>
            </div>
          </div>

          <div class="gl-fg">
            <label>Kecualikan</label>
            <div class="gl-check-row">
              <label class="gl-check">
                <input type="checkbox" id="glExcludeCash" {{ $excludeCash ? 'checked' : '' }} onchange="glTerapkan()">
                Payment Cash
              </label>
              <label class="gl-check">
                <input type="checkbox" id="glExcludeSystem" {{ $excludeSystem ? 'checked' : '' }} onchange="glTerapkan()">
                Loading By Sistem
              </label>
            </div>
          </div>
        </div>

        @if($filterAktif)
        <div class="gl-filter-row" style="margin-top:.85rem">
          <div>
            @if($type !== '')<span class="gl-active-chip">Jenis: {{ $type === 'internal' ? 'Internal' : 'Eksternal' }}</span>@endif
            @if($payment !== '')<span class="gl-active-chip">Payment: {{ $payment }}</span>@endif
            @if($keyword !== '')<span class="gl-active-chip">"{{ $keyword }}"</span>@endif
            @if($excludeCash)<span class="gl-active-chip">Tanpa Cash</span>@endif
            @if($excludeSystem)<span class="gl-active-chip">Tanpa By Sistem</span>@endif
          </div>
        </div>
        @endif

        <div class="gl-presets">
          <span class="gl-preset-btn" onclick="setRange(7)">7 Hari Terakhir</span>
          <span class="gl-preset-btn" onclick="setRange(30)">30 Hari Terakhir</span>
          <span class="gl-preset-btn" onclick="setRange(90)">90 Hari Terakhir</span>
          <span class="gl-preset-btn" onclick="setThisMonth()">Bulan Ini</span>
        </div>
      </div>

      {{-- Tabel --}}
      <div class="gl-tbl-card">
        <div class="gl-tbl-head">
          <h2>📋 Detail Loading</h2>
          <div class="gl-tbl-cnt">
            Menampilkan <strong>{{ number_format($shownOnPage) }}</strong> dari
            <strong>{{ number_format($jumlahLoading) }}</strong> baris hasil filter
          </div>
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
                @endphp
                <tr class="{{ $isInternal ? 'gl-internal' : '' }}">
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
                    <div class="gl-empty"><div class="ei">📭</div><p>Tidak ada data loading yang cocok dengan filter ini.</p></div>
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        @if($pagination != 'all')
        <div class="gl-pagi-wrap" id="renderField">
          {{ $good_loadings->appends(request()->query())->render() }}
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

      // Semua kontrol filter memicu navigasi ulang ke backend — bukan filter di browser.
      $('#show, #distributor, #glPayment').on('change', glTerapkan);
      $('#datepicker, #datepicker2').on('changeDate', glTerapkan);

      $('#glKeyword').on('keyup', function(e){
        if (e.keyCode == 13) glTerapkan();
      });
    });

    var glCurrentType = '{{ $type }}';

    function pad(n) { return n < 10 ? '0' + n : '' + n; }
    function fmt(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }

    function setRange(days)
    {
        var end   = new Date();
        var start = new Date();
        start.setDate(end.getDate() - days);
        $('#datepicker').val(fmt(start));
        $('#datepicker2').val(fmt(end));
        glTerapkan();
    }

    function setThisMonth()
    {
        var now   = new Date();
        var start = new Date(now.getFullYear(), now.getMonth(), 1);
        $('#datepicker').val(fmt(start));
        $('#datepicker2').val(fmt(now));
        glTerapkan();
    }

    function glSetType(type)
    {
        glCurrentType = type;
        glTerapkan();
    }

    function glReset()
    {
        window.location = window.location.origin + '/{{ $role }}/good-loading/'
            + '{{ date('Y-m-d', strtotime('-90 days')) }}/{{ date('Y-m-d') }}/all/20';
    }

    /* ── Satu pintu keluar untuk SEMUA filter → backend ─────────────────── */
    function glTerapkan()
    {
        var show          = $('#show').val();
        var start         = $('#datepicker').val();
        var end           = $('#datepicker2').val();
        var distributor   = $('#distributor').val();
        var payment       = $('#glPayment').val();
        var keyword       = $('#glKeyword').val();
        var excludeCash   = $('#glExcludeCash').is(':checked');
        var excludeSystem = $('#glExcludeSystem').is(':checked');

        var base = window.location.origin + '/{{ $role }}/good-loading/' + start + '/' + end + '/' + distributor + '/' + show;

        var qs = [];
        if (glCurrentType)  qs.push('type=' + encodeURIComponent(glCurrentType));
        if (payment)        qs.push('payment=' + encodeURIComponent(payment));
        if (keyword)        qs.push('keyword=' + encodeURIComponent(keyword));
        if (excludeCash)    qs.push('exclude_cash=1');
        if (excludeSystem)  qs.push('exclude_system=1');

        if (qs.length) base += '?' + qs.join('&');

        window.location = base;
    }
  </script>
@endsection