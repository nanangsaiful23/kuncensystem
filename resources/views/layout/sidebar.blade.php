<style>
  .sidebar, li, span, i, a
  {
    color:  black;
    /*border-radius: 0px 10px 10px 0px;*/
  }

  .treeview-menu li, .treeview-menu li a {
    word-wrap: break-word;
    white-space: normal;
  }

  .header
  {
    background-color: #DDDDDD;
    font-weight: 500;
  }

  .treeview-menu .active a
  {
    background-color: #89CFFD;
  }

  .main-sidebar
  {

    position: fixed;
    width: 230px;
    overflow-y: scroll;
    top: 0;
    bottom: 0;
  }
</style>

{{-- Akses menu per role: config/menu_akses.php + halaman Hak Akses Menu (canMenu). --}}
<aside class="main-sidebar" style="border-right: 0.5px; border: solid #DDDDDD;">
  <section class="sidebar" id="side-menu">
    <ul class="sidebar-menu" data-widget="tree">
      <li class="header">MENU UTAMA</li>
      @if(\App\Services\MenuAkses::lihatSebagai())
        <li style="background:#fff3cd; padding:8px 12px; font-size:12px; white-space:normal">
          Melihat sebagai <b>{{ \App\Services\MenuAkses::roles()[\App\Services\MenuAkses::lihatSebagai()] }}</b>.
          <a href="{{ route('admin.menu-akses.lihat-sebagai', 'selesai') }}" style="display:inline; padding:0; color:#0056b3; text-decoration:underline">Kembali ke superadmin</a>
        </li>
      @endif
      @if(canMenu('cari_barang'))
        <li class="{{ Request::segment(1) == 'search' ? 'active' : ''  }}"><a href="{{ url('/search/beras') }}" target="_blank()"><i class="fa fa-search"></i> CARI BARANG</a></li>
      @endif
      @if(canMenu('kas.riwayat'))
        <li><a href="{{ url('/' . $role . '/cashFlow/' . date('Y-m-d') . '/' . date('Y-m-d') . '/15') }}"><i class="fa fa-dollar"></i> Riwayat Kas di Tangan</a></li>
      @endif
      @if(canInduk('Barang') || isSupervisor())
      <li class="treeview {{ (Request::segment(2) == 'good' ) ? 'active' : ''  }}">
        <a href="#">
            <i class="fa fa-cubes"></i><span> Barang</span>
          <span class="pull-right-container">
            <i class="fa fa-angle-left pull-right"></i>
          </span>
        </a>
        <ul class="treeview-menu">
            @if(canMenu('barang.daftar'))
              <li class="{{ Request::segment(2) == 'good' && Request::segment(3) != 'printDisplay' && Request::segment(3) != 'zeroStock' && Request::segment(3) != 'exp' && Request::segment(3) != 'transfer' && Request::segment(3) != 'wholesalePrice' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/good/all/all/all/goods.id/desc/20') }}"><i class="fa fa-circle-o"></i> Daftar Barang</a></li>
            @endif
            @if(isSupervisor())
              <li class="{{ Request::segment(2) == 'good' && Request::segment(3) == 'transfer'  ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/good/transfer') }}"><i class="fa fa-circle-o"></i> Transfer Barang</a></li>
            @endif
            @if(canMenu('barang.expired'))
              <li class="{{ Request::segment(2) == 'good' && Request::segment(3) == 'exp' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/good/exp') }}"><i class="fa fa-circle-o"></i> Daftar Barang Expired</a></li>
            @endif
            @if(canMenu('barang.print_display'))
              <li class="{{ Request::segment(2) == 'good' && Request::segment(3) == 'printDisplay' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/good/printDisplay') }}"><i class="fa fa-circle-o"></i> Print Harga Barang Display</a></li>
            @endif
            @if(canMenu('barang.stok_habis'))
              <li class="{{ Request::segment(2) == 'good' && Request::segment(3) == 'zeroStock' && Request::segment(7) != '0' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/good/zeroStock/all/all/all/1/0') }}"><i class="fa fa-circle-o"></i> Stock Habis</a></li>
            @endif
            @if(canMenu('barang.stok_minus'))
              <li class="{{ Request::segment(2) == 'good' && Request::segment(3) == 'zeroStock' && Request::segment(7) == '-1' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/good/zeroStock/all/all/all/all/-1') }}"><i class="fa fa-circle-o"></i> Stock Minus</a></li>
            @endif
            @if(canMenu('barang.grosir'))
              <li class="{{ Request::segment(2) == 'good' && Request::segment(3) == 'wholesalePrice' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/good/wholesalePrice') }}"><i class="fa fa-circle-o"></i> Harga Grosir Tidak Sesuai</a></li>
            @endif
            @if(canMenu('barang.rangkuman'))
              <li class="{{ Request::segment(2) == 'good' && Request::segment(3) == 'resume'  ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/good/resume/price/desc/20') }}"><i class="fa fa-circle-o"></i> Rangkuman Barang</a></li>
            @endif
            @if(canMenu('barang.ganti_nama'))
              <li class="{{ Request::segment(2) == 'good' && Request::segment(3) == 'changeNameImport'  ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/good/changeNameImport') }}"><i class="fa fa-circle-o"></i> Ganti Nama Barang</a></li>
            @endif
        </ul>
      </li>
      @endif
      @if(canMenu('sidak'))
        <li class="{{ Request::segment(2) == 'sidak-stok' ? 'active' : ''  }}"><a href="{{ url('/admin/sidak-stok') }}"><i class="fa fa-barcode"></i> <span>Sidak Stok Toko</span></a></li>
      @endif
      @if(canInduk('Stock Opname Barang'))
        <li class="treeview {{ (Request::segment(2) == 'stock-opname' ) ? 'active' : ''  }}">
          <a href="#">
              <i class="fa fa-search-plus"></i><span> Stock Opname Barang</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
              @if(canMenu('stock_opname.tambah'))
                <li class="{{ Request::segment(2) == 'stock-opname' && Request::segment(4) == 'create' && Request::segment(3) != 'internal' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/stock-opname/create') }}"><i class="fa fa-circle-o"></i> Tambah Stock Opname Barang</a></li>
              @endif
              @if(canMenu('stock_opname.daftar'))
                <li class="{{ Request::segment(2) == 'stock-opname' && Request::segment(4) != 'create' && Request::segment(4) != 'excel' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/stock-opname/' . date('Y-m-d') . '/' . date('Y-m-d') . '/20') }}"><i class="fa fa-circle-o"></i> Daftar Stock Opname Barang</a></li>
              @endif
          </ul>
        </li>
      @endif
      @if(canInduk('Loading Barang'))
        <li class="treeview {{ (Request::segment(2) == 'good-loading' ) ? 'active' : ''  }}">
          <a href="#">
              <i class="fa fa-truck"></i><span> Loading Barang</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
              @if(canMenu('loading.tambah'))
                <li class="{{ Request::segment(2) == 'good-loading' && in_array(Request::segment(4), ['create', 'createnew']) && Request::segment(3) != 'internal' && Request::segment(3) != 'transaction-internal' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/good-loading/normal/create') }}"><i class="fa fa-circle-o"></i> Tambah Loading Barang</a></li>
              @endif

              @if(canMenu('loading.tambah_internal'))
                <li class="{{ Request::segment(2) == 'good-loading' && Request::segment(4) == 'create' && Request::segment(3) == 'internal' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/good-loading/internal/create') }}"><i class="fa fa-circle-o"></i> Tambah Loading Barang Internal</a></li>
              @endif
              <!-- @if(isSuperadmin())
                <li class="{{ Request::segment(2) == 'good-loading' && Request::segment(4) == 'create' && Request::segment(3) == 'transaction-internal' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/good-loading/transaction-internal/create') }}"><i class="fa fa-circle-o"></i> Tambah Loading Barang & Transaksi Internal</a></li>
              @endif -->
              @if(canMenu('loading.daftar'))
                <li class="{{ Request::segment(2) == 'good-loading' && Request::segment(4) != 'create' && Request::segment(4) != 'createnew' && Request::segment(4) != 'excel' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/good-loading/' . date('Y-m-d') . '/' . date('Y-m-d') . '/all/50') }}"><i class="fa fa-circle-o"></i> Daftar Loading Barang</a></li>
              @endif
              @if(canMenu('loading.import'))
                <li class="{{ Request::segment(2) == 'good-loading' && Request::segment(4) == 'excel' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/good-loading/excel') }}"><i class="fa fa-circle-o"></i> Import Data Excel</a></li>
              @endif
          </ul>
        </li>
      @endif
      @if(canInduk('Transaksi Barang'))
      <li class="treeview {{ (Request::segment(2) == 'transaction' ) ? 'active' : ''  }}">
        <a href="#">
            <i class="fa fa-money"></i><span> Transaksi Barang</span>
          <span class="pull-right-container">
            <i class="fa fa-angle-left pull-right"></i>
          </span>
        </a>
        <ul class="treeview-menu">
            @if(canMenu('transaksi.tambah'))
              <li class="{{ Request::segment(2) == 'transaction' && Request::segment(3) == 'create' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/transaction/create') }}"><i class="fa fa-circle-o"></i> Tambah Transaksi</a></li>
            @endif
            @if(canMenu('transaksi.tambah_gpt'))
              <li class="{{ Request::segment(2) == 'transaction' && Request::segment(3) == 'createGpt' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/transaction/createGpt') }}" target="_blank()"><i class="fa fa-circle-o"></i> Tambah Transaksi (new page)</a></li>
            @endif
            @if(canMenu('transaksi.tambah_hp'))
              <li class="{{ Request::segment(2) == 'transaction' && Request::segment(3) == 'createMobile' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/transaction/createMobile') }}"><i class="fa fa-mobile"></i> Tambah Transaksi (HP)</a></li>
            @endif
          @if(canMenu('transaksi.daftar'))
            <li class="{{ Request::segment(2) == 'transaction' && Request::segment(3) != 'create'&& Request::segment(3) != 'createGpt' && Request::segment(3) != 'createTouch' && Request::segment(3) != 'createMobile' && Request::segment(3) != 'resume' && Request::segment(3) != 'resumeTotal' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/transaction/all/all/' . date('Y-m-d') . '/' . date('Y-m-d') . '/20') }}"><i class="fa fa-circle-o"></i> Daftar Transaksi</a></li>
          @endif
            @if(canMenu('transaksi.resume_total'))
              <li class="{{ Request::segment(2) == 'transaction' && Request::segment(3) == 'resumeTotal' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/transaction/resumeTotal/' . date('Y-m-d') . '/' . date('Y-m-d')) }}"><i class="fa fa-circle-o"></i> Resume Transaksi Total</a></li>
            @endif
            @if(canMenu('transaksi.resume_barang'))
              <li class="{{ Request::segment(2) == 'transaction' && Request::segment(3) == 'resume' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/transaction/resume/normal/all/all/' . date('Y-m-d') . '/' . date('Y-m-d') . '/100') }}"><i class="fa fa-circle-o"></i> Resume Transaksi Per Barang</a></li>
            @endif
        </ul>
      </li>
      @endif
      @if(canInduk('Transaksi Internal'))
        <li class="treeview {{ (Request::segment(2) == 'internal-transaction' ) ? 'active' : ''  }}">
          <a href="#">
              <i class="fa fa-building-o"></i><span> Transaksi Internal</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
              @if(canMenu('transaksi_internal.tambah'))
                <li class="{{ Request::segment(2) == 'internal-transaction' && Request::segment(3) == 'create' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/internal-transaction/create') }}"><i class="fa fa-circle-o"></i> Tambah Transaksi Internal</a></li>
              @endif
              @if(canMenu('transaksi_internal.daftar'))
                <li class="{{ Request::segment(2) == 'internal-transaction' && Request::segment(3) != 'create' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/internal-transaction/all/all/' . date('Y-m-d') . '/' . date('Y-m-d') . '/20') }}"><i class="fa fa-circle-o"></i> Daftar Transaksi Internal</a></li>
              @endif
          </ul>
        </li>
      @endif
      @if(canMenu('retur'))
        <li class="{{ Request::segment(2) == 'retur' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/retur/all/null/20') }}"><i class="fa fa-arrow-left"></i> Barang Retur</a></li>
      @endif
      @if(canInduk('Transaksi Lain (pemasukan)'))
        <li class="header">PEMASUKAN LAIN-LAIN</li>
        <li class="treeview {{ (Request::segment(2) == 'other-transaction' ) ? 'active' : ''  }}">
          <a href="#">
              <i class="fa fa-dollar"></i><span> Transaksi Lain</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
              @if(canMenu('transaksi_lain.tambah'))
                <li class="{{ Request::segment(2) == 'other-transaction' && Request::segment(3) == 'create' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/other-transaction/create') }}"><i class="fa fa-circle-o"></i> Tambah Transaksi Lain</a></li>
              @endif
              @if(canMenu('transaksi_lain.daftar'))
                <li class="{{ Request::segment(2) == 'other-transaction' && Request::segment(3) != 'create' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/other-transaction/' . date('Y-m-d') . '/' . date('Y-m-d') . '/20') }}"><i class="fa fa-circle-o"></i> Daftar Transaksi Lain</a></li>
              @endif
          </ul>
        </li>
      @endif
      @if(canInduk('Biaya Lain (pengeluaran)'))
        <li class="header">PENGELUARAN LAIN-LAIN</li>
        <li class="treeview {{ (Request::segment(2) == 'other-payment' ) ? 'active' : ''  }}">
          <a href="#">
              <i class="fa fa-plus"></i><span> Biaya Lain</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
              @if(canMenu('biaya_lain.tambah'))
                <li class="{{ Request::segment(2) == 'other-payment' && Request::segment(3) == 'create' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/other-payment/create') }}"><i class="fa fa-circle-o"></i> Tambah Biaya Lain</a></li>
              @endif
              @if(canMenu('biaya_lain.daftar'))
                <li class="{{ Request::segment(2) == 'other-payment' && Request::segment(3) != 'create' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/other-payment/' . date('Y-m-d') . '/' . date('Y-m-d') . '/20') }}"><i class="fa fa-circle-o"></i> Daftar Biaya Lain</a></li>
              @endif
          </ul>
        </li>
      @endif
      <li class="header">MENU LAIN</li>
      @if(canInduk('Member'))
      <li class="treeview {{ (Request::segment(2) == 'member' ) ? 'active' : ''  }}">
        <a href="#">
            <i class="fa fa-users"></i><span> Member</span>
          <span class="pull-right-container">
            <i class="fa fa-angle-left pull-right"></i>
          </span>
        </a>
        <ul class="treeview-menu">
          @if(canMenu('member.tambah'))
            <li class="{{ Request::segment(2) == 'member' && Request::segment(3) == 'create' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/member/create') }}"><i class="fa fa-circle-o"></i> Tambah Member</a></li>
          @endif
          @if(canMenu('member.daftar'))
            <li class="{{ Request::segment(2) == 'member' && Request::segment(3) != 'create' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/member/2020-01-01/' . date('Y-m-d') . '/name/asc/15') }}"><i class="fa fa-circle-o"></i> Daftar Member</a></li>
          @endif
        </ul>
      </li>
      @endif
      @if(canInduk('Delivery Fee'))
        <li class="treeview {{ (Request::segment(2) == 'delivery-fee' ) ? 'active' : ''  }}">
          <a href="#">
              <i class="fa fa-car"></i><span> Delivery Fee</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
              @if(canMenu('delivery_fee.tambah'))
                <li class="{{ Request::segment(2) == 'delivery-fee' && Request::segment(3) == 'create' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/delivery-fee/create') }}"><i class="fa fa-circle-o"></i> Tambah Delivery Fee</a></li>
              @endif
              @if(canMenu('delivery_fee.daftar'))
                <li class="{{ Request::segment(2) == 'delivery-fee' && Request::segment(3) != 'create' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/delivery-fee/15') }}"><i class="fa fa-circle-o"></i> Daftar Delivery Fee</a></li>
              @endif
          </ul>
        </li>
      @endif
      @if(canInduk('Brand'))
        <li class="treeview {{ (Request::segment(2) == 'brand' ) ? 'active' : ''  }}">
          <a href="#">
              <i class="fa fa-html5"></i><span> Brand</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
              @if(canMenu('brand.tambah'))
                <li class="{{ Request::segment(2) == 'brand' && Request::segment(3) == 'create' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/brand/create') }}"><i class="fa fa-circle-o"></i> Tambah Brand</a></li>
              @endif
              @if(canMenu('brand.daftar'))
                <li class="{{ Request::segment(2) == 'brand' && Request::segment(3) != 'create' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/brand/15') }}"><i class="fa fa-circle-o"></i> Daftar Brand</a></li>
              @endif
          </ul>
        </li>
      @endif
      @if(canInduk('Distributor'))
        <li class="treeview {{ (Request::segment(2) == 'distributor' ) ? 'active' : ''  }}">
          <a href="#">
              <i class="fa fa-truck"></i><span> Distributor</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
              @if(canMenu('distributor.tambah'))
                <li class="{{ Request::segment(2) == 'distributor' && Request::segment(3) == 'create' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/distributor/create') }}"><i class="fa fa-circle-o"></i> Tambah Distributor</a></li>
              @endif
              @if(canMenu('distributor.daftar'))
                <li class="{{ Request::segment(2) == 'distributor' && Request::segment(3) != 'create' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/distributor/total_aset/desc/20') }}"><i class="fa fa-circle-o"></i> Daftar Distributor</a></li>
              @endif
          </ul>
        </li>
      @endif
      @if(canInduk('Kategori & Jenis'))
        @php $segKategori = Request::segment(2); $segKategori3 = Request::segment(3); @endphp
        <li class="treeview {{ in_array($segKategori, ['category', 'type', 'penataan-kategori']) ? 'active' : ''  }}">
          <a href="#">
              <i class="fa fa-sitemap"></i><span> Kategori &amp; Jenis</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
              @if(canMenu('kategori.rangkuman'))
                <li class="{{ $segKategori == 'category' && $segKategori3 == 'rangkuman' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/category/rangkuman') }}"><i class="fa fa-pie-chart"></i> Rangkuman Kategori</a></li>
              @endif
              @if(canMenu('kategori.daftar'))
                <li class="{{ $segKategori == 'category' && !in_array($segKategori3, ['create', 'rangkuman']) ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/category/15') }}"><i class="fa fa-circle-o"></i> Daftar Kategori</a></li>
              @endif
              @if(canMenu('kategori.tambah'))
                <li class="{{ $segKategori == 'category' && $segKategori3 == 'create' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/category/create') }}"><i class="fa fa-circle-o"></i> Tambah Kategori</a></li>
              @endif
              @if(canMenu('jenis.daftar'))
                <li class="{{ $segKategori == 'type' && $segKategori3 != 'create' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/type/name/asc/30') }}"><i class="fa fa-circle-o"></i> Daftar Jenis Barang</a></li>
              @endif
              @if(canMenu('jenis.tambah'))
                <li class="{{ $segKategori == 'type' && $segKategori3 == 'create' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/type/create') }}"><i class="fa fa-circle-o"></i> Tambah Jenis Barang</a></li>
              @endif
            @if(canMenu('kategori.penataan'))
              <li class="{{ $segKategori == 'penataan-kategori' ? 'active' : ''  }}"><a href="{{ url('/admin/penataan-kategori') }}"><i class="fa fa-random"></i> Penataan Kategori</a></li>
            @endif
          </ul>
        </li>
      @endif
      @if(canInduk('Satuan'))
        <li class="treeview {{ Request::segment(2) == 'unit' ? 'active' : ''  }}">
          <a href="#">
            <i class="fa fa-shopping-basket"></i><span> Satuan</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
              @if(canMenu('satuan.tambah'))
                <li class="{{ Request::segment(2) == 'unit' && Request::segment(3) == 'create' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/unit/create') }}"><i class="fa fa-circle-o"></i> Tambah Satuan</a></li>
              @endif
              @if(canMenu('satuan.daftar'))
                <li class="{{ Request::segment(2) == 'unit' && Request::segment(3) != 'create' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/unit/all') }}"><i class="fa fa-circle-o"></i> Daftar Satuan</a></li>
              @endif
          </ul>
        </li>
      @endif
      @if(canInduk('Voucher'))
        <li class="treeview {{ Request::segment(2) == 'voucher' ? 'active' : ''  }}">
          <a href="#">
            <i class="fa fa-money"></i><span> Voucher</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
              @if(canMenu('voucher.tambah'))
                <li class="{{ Request::segment(2) == 'voucher' && Request::segment(3) == 'create' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/voucher/create') }}"><i class="fa fa-circle-o"></i> Tambah Voucher</a></li>
              @endif
              @if(canMenu('voucher.daftar'))
                <li class="{{ Request::segment(2) == 'voucher' && Request::segment(3) != 'create' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/voucher/all') }}"><i class="fa fa-circle-o"></i> Daftar Voucher</a></li>
              @endif
          </ul>
        </li>
      @endif
      @php
        $adaLaporan = collect(['Laporan Financial', 'Akun', 'Grafik Penjualan'])->contains(fn ($i) => canInduk($i))
            || collect(['laporan.pemilik', 'laporan.penjualan', 'laporan.perputaran', 'laporan.reorder'])->contains(fn ($k) => canMenu($k));
      @endphp
      @if($adaLaporan)
        <li class="header">LAPORAN </li>
      @endif
      @if(canInduk('Laporan Financial'))
        <li class="treeview {{ (Request::segment(2) == 'scale' || Request::segment(2) == 'scaleLedger' || Request::segment(2) == 'journal' || Request::is('admin/reports/financial*')) ? 'active' : ''  }}">
          <a href="#">
              <i class="fa fa-balance-scale"></i><span> laporan financial</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
            @if(canMenu('laporan.jurnal'))
              <li><a href="{{ url('/' . $role . '/journal/all/all/' . date('Y-m-d') . '/' . date('Y-m-d') . '/id/asc/15') }}"><i class="fa fa-calculator"></i> Jurnal</a></li>
            @endif
            @if(canMenu('laporan.keuangan'))
              <li class="{{ Request::is('admin/reports/financial*') ? 'active' : '' }}"><a href="{{ route('admin.reports.financial') }}"><i class="fa fa-calculator"></i> Laba Rugi, Neraca Bulanan &amp; Kas</a></li>
            @endif
            @if(canMenu('laporan.neraca'))
              <li class="{{ Request::segment(2) == 'scale' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/scale/2020-01-01/' . date('Y-m-d')) }}"><i class="fa fa-circle-o"></i> Detail Neraca</a></li>
            @endif
            @if(canMenu('laporan.ledger'))
              <li class="{{ Request::segment(2) == 'scaleLedger' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/scaleLedger/2020-01-01/' . date('Y-m-d')) }}"><i class="fa fa-circle-o"></i> Grafik Ledger Neraca</a></li>
            @endif
          </ul>
        </li>
      @endif
      @if(canInduk('Akun'))
        <li class="treeview {{ (Request::segment(2) == 'account' ) ? 'active' : ''  }}">
          <a href="#">
              <i class="fa fa-book"></i><span> Akun</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
              @if(canMenu('akun.tambah'))
                <li class="{{ Request::segment(2) == 'account' && Request::segment(3) == 'create' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/account/create') }}"><i class="fa fa-circle-o"></i> Tambah Akun</a></li>
              @endif
              @if(canMenu('akun.daftar'))
                <li class="{{ Request::segment(2) == 'account' && Request::segment(3) != 'create' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/account/15') }}"><i class="fa fa-circle-o"></i> Daftar Akun</a></li>
              @endif
          </ul>
        </li>
      @endif
      @if(canInduk('Grafik Penjualan'))
        <li class="treeview {{ (Request::segment(2) == 'salesGraph' ) ? 'active' : ''  }}">
          <a href="#">
              <i class="fa fa-bar-chart"></i><span> Grafik Penjualan</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
              @if(canMenu('grafik.bulan'))
                <li class="{{ Request::segment(2) == 'salesGraph' && Request::segment(3) == 'month' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/salesGraph/month/2020-01-01/' . date('Y-m-d')) }}"><i class="fa fa-circle-o"></i> Per Bulan</a></li>
              @endif
              @if(canMenu('grafik.kategori'))
                <li class="{{ Request::segment(2) == 'salesGraph' && Request::segment(3) == 'category' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/salesGraph/category/2020-01-01/' . date('Y-m-d')) }}"><i class="fa fa-circle-o"></i> Per Kategori</a></li>
              @endif
          </ul>
        </li>
      @endif
      @if(canMenu('laporan.pemilik'))
        <li class="{{ Request::is('admin/reports/owner*') ? 'active' : '' }}"><a href="{{ route('admin.reports.owner') }}"><i class="fa fa-dashboard"></i> Ringkasan Pemilik</a></li>
      @endif
      @if(canMenu('laporan.penjualan'))
        <li class="{{ Request::is('admin/reports/sales*') ? 'active' : '' }}"><a href="{{ route('admin.reports.sales') }}"><i class="fa fa-shopping-cart"></i> Laporan Penjualan</a></li>
      @endif
      @if(canMenu('laporan.perputaran'))
        <li class="{{ Request::is('admin/reports/movement*') ? 'active' : '' }}"><a href="{{ route('admin.reports.movement') }}"><i class="fa fa-refresh"></i> Perputaran Barang</a></li>
      @endif
      @if(canMenu('laporan.reorder'))
        <li class="{{ Request::is('admin/reports/reorder*') ? 'active' : '' }}"><a href="{{ route('admin.reports.reorder') }}"><i class="fa fa-truck"></i> Rekomendasi Reorder</a></li>
      @endif
      @if(isSuperadmin())
        <li class="header">PENGATURAN</li>
        <li class="{{ Request::is('admin/hak-akses*') ? 'active' : '' }}"><a href="{{ route('admin.menu-akses.index') }}"><i class="fa fa-lock"></i> Hak Akses Menu</a></li>
      @endif
    </ul>
  </section>
</aside>
