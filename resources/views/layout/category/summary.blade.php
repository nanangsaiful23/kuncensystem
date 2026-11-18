@php
  $fmt = fn ($n) => number_format((float) $n, 0, ',', '.');
  $lihatNilai = isSupervisor();
  $m = $rangkuman['masalah'];
  $jumlahMasalah = $m['kategori_dobel']->count() + $m['jenis_dobel']->count() + $m['jenis_tanpa_kategori']->count()
                 + $m['kategori_tanpa_area'] + ($m['barang_tanpa_kategori'] > 0 ? 1 : 0) + ($m['barang_tidak_cocok']->count() > 0 ? 1 : 0);
@endphp

<style>
  .rk-stats { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 15px; }
  .rk-stat { flex: 1 1 130px; background: #fff; border: 1px solid #ddd; border-radius: 4px; padding: 10px 12px; }
  .rk-stat b { display: block; font-size: 22px; color: #222; }
  .rk-stat small { color: #666; }
  .rk-stat.warn { border-color: #f39c12; background: #fff8e8; }
  .rk-table td, .rk-table th { vertical-align: middle !important; }
  .rk-area td { background: #e8f1fb !important; font-weight: 600; cursor: pointer; }
  .rk-kat td:first-child { padding-left: 24px !important; }
  .rk-kat .rk-toggle { cursor: pointer; }
  .rk-jenis td { background: #fafafa !important; font-size: 12px; }
  .rk-jenis td:first-child { padding-left: 48px !important; }
  .rk-num { text-align: right; white-space: nowrap; }
  .rk-muted { color: #999; }
  .rk-badge { display: inline-block; font-size: 11px; padding: 1px 6px; border-radius: 3px; background: #eee; color: #555; margin-left: 4px; }
  .rk-badge.warn { background: #fcebc5; color: #8a5a00; }
  .rk-links a { margin-right: 8px; white-space: nowrap; }
  .rk-masalah h4 { margin-top: 18px; font-size: 15px; }
</style>

<div class="content-wrapper">

  @include('layout' . '.alert-message', ['type' => $default['type'], 'data' => $default['data'], 'color' => $default['color']])

  <section class="content">
    <div class="row">
      <div class="col-xs-12">
        <div class="box">
          <div class="box-header">
            <h3 class="box-title">{{ $default['page_name'] }}</h3>
            <div class="rk-links" style="margin-top: 8px;">
              <a href="{{ url($role . '/category/create') }}"><i class="fa fa-plus"></i> Tambah Kategori</a>
              <a href="{{ url($role . '/type/create') }}"><i class="fa fa-plus"></i> Tambah Jenis Barang</a>
              <a href="#rk-masalah"><i class="fa fa-exclamation-triangle"></i> Perlu dirapikan ({{ $jumlahMasalah }})</a>
            </div>
          </div>
          <div class="box-body" style="color: black !important;">

            <div class="rk-stats">
              <div class="rk-stat"><b>{{ $rangkuman['jumlah']['area'] }}</b><small>Area</small></div>
              <div class="rk-stat"><b>{{ $rangkuman['jumlah']['kategori'] }}</b><small>Kategori</small></div>
              <div class="rk-stat"><b>{{ $rangkuman['jumlah']['jenis'] }}</b><small>Jenis barang</small></div>
              <div class="rk-stat"><b>{{ $fmt($rangkuman['total']->barang) }}</b><small>Barang aktif</small></div>
              <div class="rk-stat"><b>{{ $fmt($rangkuman['total']->berstok) }}</b><small>Barang berstok</small></div>
              <div class="rk-stat"><b>{{ $fmt($rangkuman['total']->habis) }}</b><small>Stok habis / minus</small></div>
              <div class="rk-stat {{ $rangkuman['total']->tanpa_jenis ? 'warn' : '' }}"><b>{{ $fmt($rangkuman['total']->tanpa_jenis) }}</b><small>Barang belum berjenis</small></div>
              @if($lihatNilai)
                <div class="rk-stat"><b>{{ $fmt($rangkuman['total']->nilai_stok) }}</b><small>Nilai stok (harga beli)</small></div>
              @endif
            </div>

            <p class="rk-muted" style="margin-bottom: 8px;">
              Klik baris Area untuk membuka atau menutup kategorinya, lalu klik <i class="fa fa-caret-right"></i> pada kategori untuk melihat jenis barangnya.
              Barang yang sudah discontinue ({{ $fmt($rangkuman['discontinue']) }}) tidak ikut dihitung.
              <a href="#" id="rk-buka-semua">Buka semua</a> · <a href="#" id="rk-tutup-semua">Tutup semua</a>
            </p>

            <div style="overflow-x: auto;">
            <table class="table table-bordered rk-table">
              <thead>
                <tr>
                  <th>Area / Kategori / Jenis</th>
                  <th class="rk-num">Jenis</th>
                  <th class="rk-num">Barang</th>
                  <th class="rk-num">Berstok</th>
                  <th class="rk-num">Habis</th>
                  <th class="rk-num">Belum berjenis</th>
                  @if($lihatNilai)<th class="rk-num">Nilai stok</th>@endif
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                @foreach($rangkuman['tree'] as $ai => $a)
                  <tr class="rk-area" data-area="{{ $ai }}">
                    <td><i class="fa fa-caret-down rk-icon"></i> {{ $a->area->name }}
                      <span class="rk-badge">{{ $a->kategori->count() }} kategori</span></td>
                    <td class="rk-num">{{ $fmt($a->total->jenis) }}</td>
                    <td class="rk-num">{{ $fmt($a->total->barang) }}</td>
                    <td class="rk-num">{{ $fmt($a->total->berstok) }}</td>
                    <td class="rk-num">{{ $fmt($a->total->habis) }}</td>
                    <td class="rk-num">{{ $fmt($a->total->tanpa_jenis) }}</td>
                    @if($lihatNilai)<td class="rk-num">{{ $fmt($a->total->nilai_stok) }}</td>@endif
                    <td></td>
                  </tr>
                  @foreach($a->kategori as $c)
                    <tr class="rk-kat" data-of-area="{{ $ai }}" data-kat="{{ $c->id }}">
                      <td>
                        <span class="rk-toggle" data-kat="{{ $c->id }}"><i class="fa fa-caret-right rk-icon"></i> {{ $c->name }}</span>
                        <span class="rk-muted">({{ $c->code }})</span>
                        @if($c->habis_pakai)<span class="rk-badge">habis pakai</span>@endif
                        @if($c->jenis->isEmpty())<span class="rk-badge warn">belum ada jenis</span>@endif
                        @if($c->jenis_kosong)<span class="rk-badge">{{ $c->jenis_kosong }} jenis tanpa barang</span>@endif
                      </td>
                      <td class="rk-num">{{ $c->jenis->count() }}</td>
                      <td class="rk-num">{{ $fmt($c->stat->barang) }}</td>
                      <td class="rk-num">{{ $fmt($c->stat->berstok) }}</td>
                      <td class="rk-num">{{ $fmt($c->stat->habis) }}</td>
                      <td class="rk-num">{{ $fmt($c->stat->tanpa_jenis) }}</td>
                      @if($lihatNilai)<td class="rk-num">{{ $fmt($c->stat->nilai_stok) }}</td>@endif
                      <td class="rk-links">
                        <a href="{{ url($role . '/good/' . $c->id . '/all/all/goods.id/desc/20') }}" title="Daftar barang"><i class="fa fa-cubes"></i> Barang</a>
                        <a href="{{ url($role . '/category/' . $c->id . '/edit') }}" title="Ubah kategori"><i class="fa fa-pencil orange"></i> Ubah</a>
                      </td>
                    </tr>
                    @foreach($c->jenis as $t)
                      <tr class="rk-jenis" data-of-kat="{{ $c->id }}" data-of-area="{{ $ai }}" style="display: none;">
                        <td>{{ $t->name }} <span class="rk-muted">({{ $t->code }})</span>
                          @if($t->tipe_kontrol)<span class="rk-badge">{{ str_replace('_', ' ', $t->tipe_kontrol) }}</span>@endif
                          @if($t->min_model || $t->max_model)<span class="rk-badge">model {{ $t->min_model ?? '?' }}–{{ $t->max_model ?? '?' }}</span>@endif
                        </td>
                        <td></td>
                        <td class="rk-num">{{ $fmt($t->stat->barang) }}</td>
                        <td class="rk-num">{{ $fmt($t->stat->berstok) }}</td>
                        <td class="rk-num">{{ $fmt($t->stat->habis) }}</td>
                        <td></td>
                        @if($lihatNilai)<td class="rk-num">{{ $fmt($t->stat->nilai_stok) }}</td>@endif
                        <td class="rk-links">
                          <a href="{{ url($role . '/good/all/' . $t->id . '/all/goods.id/desc/20') }}"><i class="fa fa-cubes"></i> Barang</a>
                          <a href="{{ url($role . '/type/' . $t->id . '/edit') }}"><i class="fa fa-pencil orange"></i> Ubah</a>
                        </td>
                      </tr>
                    @endforeach
                  @endforeach
                @endforeach
              </tbody>
            </table>
            </div>

            <div class="rk-masalah" id="rk-masalah">
              <h3 style="font-size: 18px;"><i class="fa fa-exclamation-triangle" style="color: #f39c12;"></i> Perlu dirapikan</h3>
              @if($jumlahMasalah == 0)
                <p>Tidak ada nama dobel atau data yang tidak konsisten.</p>
              @endif

              @if($m['kategori_dobel']->count())
                <h4>Nama kategori dobel</h4>
                <ul>
                  @foreach($m['kategori_dobel'] as $grup)
                    <li>
                      @foreach($grup as $c)
                        <a href="{{ url($role . '/category/' . $c->id . '/edit') }}">{{ $c->name }} ({{ $c->code }})</a>@if(!$loop->last), @endif
                      @endforeach
                    </li>
                  @endforeach
                </ul>
              @endif

              @if($m['jenis_dobel']->count())
                <h4>Nama jenis dobel dalam kategori yang sama</h4>
                <ul>
                  @foreach($m['jenis_dobel'] as $grup)
                    <li>
                      {{ $m['nama_kategori'][$grup[0]->category_id] ?? 'Tanpa kategori' }}:
                      @foreach($grup as $t)
                        <a href="{{ url($role . '/type/' . $t->id . '/edit') }}">{{ $t->name }} ({{ $t->code }})</a>@if(!$loop->last), @endif
                      @endforeach
                    </li>
                  @endforeach
                </ul>
              @endif

              @if($m['kategori_tanpa_area'])
                <h4>Kategori belum punya Area</h4>
                <p>{{ $m['kategori_tanpa_area'] }} kategori. Kategori ini ada di kelompok "Belum ada Area" pada tabel di atas. Atur Area-nya lewat tombol Ubah.</p>
              @endif

              @if($m['jenis_tanpa_kategori']->count())
                <h4>Jenis barang belum punya kategori induk ({{ $m['jenis_tanpa_kategori']->count() }})</h4>
                <ul>
                  @foreach($m['jenis_tanpa_kategori'] as $t)
                    <li><a href="{{ url($role . '/type/' . $t->id . '/edit') }}">{{ $t->name }} ({{ $t->code }})</a></li>
                  @endforeach
                </ul>
              @endif

              @if($m['barang_tanpa_kategori'])
                <h4>Barang tanpa kategori</h4>
                <p>{{ $fmt($m['barang_tanpa_kategori']) }} barang aktif belum punya kategori atau kategorinya sudah dihapus.</p>
              @endif

              @if($m['barang_tidak_cocok']->count())
                <h4>Kategori barang berbeda dengan kategori induk jenisnya ({{ $m['barang_tidak_cocok']->count() }}{{ $m['barang_tidak_cocok']->count() >= 200 ? '+' : '' }})</h4>
                <table class="table table-bordered table-condensed">
                  <thead><tr><th>Barang</th><th>Kategori barang</th><th>Jenis</th><th>Kategori induk jenis</th></tr></thead>
                  <tbody>
                    @foreach($m['barang_tidak_cocok'] as $g)
                      <tr>
                        <td><a href="{{ url($role . '/good/' . $g->id . '/edit') }}">{{ $g->name }}</a></td>
                        <td>{{ $g->kategori_barang ?? '-' }}</td>
                        <td>{{ $g->jenis }}</td>
                        <td>{{ $g->kategori_jenis }}</td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              @endif

              @if($rangkuman['total']->tanpa_jenis && isSupervisor())
                <p class="rk-muted">{{ $fmt($rangkuman['total']->tanpa_jenis) }} barang belum berjenis. Barang ini bisa dipetakan sekaligus lewat
                  <a href="{{ url('admin/penataan-kategori') }}">Penataan Kategori</a>.</p>
              @endif
            </div>

          </div>
        </div>
      </div>
    </div>
  </section>
</div>

@section('js-addon')
  <script type="text/javascript">
    $(function () {
      function setKat(id, open) {
        $('tr.rk-jenis[data-of-kat="' + id + '"]').toggle(open);
        $('tr.rk-kat[data-kat="' + id + '"] .rk-icon').toggleClass('fa-caret-down', open).toggleClass('fa-caret-right', !open);
      }

      $('.rk-toggle').on('click', function () {
        var id = $(this).data('kat');
        setKat(id, !$('tr.rk-jenis[data-of-kat="' + id + '"]').first().is(':visible'));
      });

      $('tr.rk-area').on('click', function () {
        var ai = $(this).data('area');
        var kats = $('tr.rk-kat[data-of-area="' + ai + '"]');
        var open = !kats.first().is(':visible');
        kats.toggle(open);
        if (!open) $('tr.rk-jenis[data-of-area="' + ai + '"]').hide();
        kats.each(function () { if (!open) setKat($(this).data('kat'), false); });
        $(this).find('.rk-icon').toggleClass('fa-caret-down', open).toggleClass('fa-caret-right', !open);
      });

      $('#rk-buka-semua').on('click', function (e) {
        e.preventDefault();
        $('tr.rk-kat').show();
        $('tr.rk-area .rk-icon').addClass('fa-caret-down').removeClass('fa-caret-right');
        $('tr.rk-kat').each(function () { setKat($(this).data('kat'), true); });
      });

      $('#rk-tutup-semua').on('click', function (e) {
        e.preventDefault();
        $('tr.rk-kat').each(function () { setKat($(this).data('kat'), false); });
      });
    });
  </script>
@endsection
