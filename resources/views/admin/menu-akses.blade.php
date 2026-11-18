@extends('layout.user', ['role' => 'admin', 'title' => 'Hak Akses Menu', 'default' => ['page_name' => 'Hak Akses Menu']])

@section('content')
<style>
  .ha-wrap { padding: 15px; max-width: 900px; }
  .ha-card { background: #fff; border: 1px solid #ddd; border-radius: 6px; padding: 15px; margin-bottom: 15px; }
  .ha-table { width: 100%; border-collapse: collapse; }
  .ha-table th, .ha-table td { padding: 8px 10px; border-bottom: 1px solid #eee; text-align: left; }
  .ha-table th.ha-col, .ha-table td.ha-col { text-align: center; width: 120px; }
  .ha-table tr.ha-grup td { background: #f4f4f4; font-weight: 600; }
  .ha-table input[type=checkbox] { width: 20px; height: 20px; cursor: pointer; }
  .ha-catatan { color: #888; font-size: 12px; }
  .ha-semua { font-size: 12px; font-weight: normal; }
  .ha-tombol { position: sticky; bottom: 0; background: #fff; padding: 12px 0; border-top: 1px solid #ddd; }
</style>

<div class="content-wrapper">
<div class="ha-wrap">
  <h3 style="margin-top:0">Hak Akses Menu</h3>
  <p>Centang menu yang boleh dibuka setiap role. <b>Superadmin selalu bisa membuka semua menu.</b>
     Menu yang tidak dicentang hilang dari sidebar, dan URL-nya ditolak kalau dibuka langsung.</p>

  @if(session('menu_akses_ok'))
    <div class="alert alert-success">{{ session('menu_akses_ok') }}</div>
  @endif
  @if(session('menu_akses_error'))
    <div class="alert alert-danger">{{ session('menu_akses_error') }}</div>
  @endif
  @if(! $tabelAda)
    <div class="alert alert-warning">
      Tabel <code>menu_permissions</code> belum ada, jadi pengaturan belum bisa disimpan (saat ini semua memakai aturan bawaan).
      Jalankan sekali file <code>docs/sql/2026-11-18-tambah-hak-akses-menu.sql</code> di phpMyAdmin.
    </div>
  @endif

  <form method="POST" action="{{ route('admin.menu-akses.update') }}">
    {{ csrf_field() }}
    <div class="ha-card">
      <table class="ha-table">
        <thead>
          <tr>
            <th>Menu</th>
            @foreach($roles as $roleKey => $roleNama)
              <th class="ha-col">
                {{ $roleNama }}<br>
                <label class="ha-semua"><input type="checkbox" class="ha-pilih-semua" data-role="{{ $roleKey }}" style="width:14px;height:14px"> semua</label>
              </th>
            @endforeach
            <th class="ha-col">Superadmin</th>
          </tr>
        </thead>
        <tbody>
          @foreach($grup as $namaGrup => $menus)
            <tr class="ha-grup"><td colspan="{{ count($roles) + 2 }}">{{ $namaGrup }}</td></tr>
            @foreach($menus as $key => $menu)
              <tr>
                <td>
                  {{ $menu['label'] }}
                  @if(empty($menu['paths']))
                    <div class="ha-catatan">hanya disembunyikan dari sidebar</div>
                  @endif
                </td>
                @foreach($roles as $roleKey => $roleNama)
                  <td class="ha-col">
                    <input type="checkbox" name="akses[{{ $roleKey }}][{{ $key }}]" value="1"
                           data-role="{{ $roleKey }}" aria-label="{{ $menu['label'] }} untuk {{ $roleNama }}"
                           {{ $menu['boleh'][$roleKey] ? 'checked' : '' }}>
                  </td>
                @endforeach
                <td class="ha-col"><i class="fa fa-check" style="color:#00a65a" title="selalu boleh"></i></td>
              </tr>
            @endforeach
          @endforeach
        </tbody>
      </table>

      <div class="ha-tombol">
        <button type="submit" class="btn btn-primary" {{ $tabelAda ? '' : 'disabled' }}><i class="fa fa-save"></i> Simpan</button>
        <button type="submit" name="aksi" value="bawaan" class="btn btn-default" {{ $tabelAda ? '' : 'disabled' }}
                onclick="return confirm('Kembalikan semua hak akses ke aturan bawaan?')">Kembalikan ke bawaan</button>
      </div>
    </div>
  </form>
</div>
</div>

<script>
  document.querySelectorAll('.ha-pilih-semua').forEach(function (semua) {
    var role = semua.dataset.role;
    var kotak = document.querySelectorAll('input[name^="akses[' + role + ']"]');
    var segarkan = function () {
      semua.checked = Array.prototype.every.call(kotak, function (k) { return k.checked; });
    };
    segarkan();
    kotak.forEach(function (k) { k.addEventListener('change', segarkan); });
    semua.addEventListener('change', function () {
      kotak.forEach(function (k) { k.checked = semua.checked; });
    });
  });
</script>
@endsection
