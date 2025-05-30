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

<aside class="main-sidebar" style="border-right: 0.5px; border: solid #DDDDDD;">
  <section class="sidebar" id="side-menu">
    <ul class="sidebar-menu" data-widget="tree">
      <li class="header">MENU UTAMA</li>
      <li class="{{ Request::segment(1) == 'search' ? 'active' : ''  }}"><a href="{{ url('/search/beras') }}" target="_blank()"><i class="fa fa-search"></i> CARI BARANG</a></li>
      @if($role == 'admin')
        <li><a href="{{ url('/' . $role . '/cashFlow/' . date('Y-m-d') . '/' . date('Y-m-d') . '/15') }}"><i class="fa fa-dollar"></i> Riwayat Kas di Tangan</a></li>
      @endif
      <li class="treeview {{ (Request::segment(2) == 'good' ) ? 'active' : ''  }}">
        <a href="#">
            <i class="fa fa-cubes"></i><span> Barang</span>
          <span class="pull-right-container">
            <i class="fa fa-angle-left pull-right"></i>
          </span>
        </a>
        <ul class="treeview-menu">
            <li class="{{ Request::segment(2) == 'good' && Request::segment(3) != 'printDisplay' && Request::segment(3) != 'zeroStock' && Request::segment(3) != 'exp' && Request::segment(3) != 'transfer' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/good/all/all/20') }}"><i class="fa fa-circle-o"></i> Daftar Barang</a></li>
            @if(\Auth::user()->role == 'supervisor')
              <li class="{{ Request::segment(2) == 'good' && Request::segment(3) == 'transfer'  ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/good/transfer') }}"><i class="fa fa-circle-o"></i> Transfer Barang</a></li>
            @endif
            <li class="{{ Request::segment(2) == 'good' && Request::segment(3) == 'exp' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/good/exp') }}"><i class="fa fa-circle-o"></i> Daftar Barang Expired</a></li>
            <li class="{{ Request::segment(2) == 'good' && Request::segment(3) == 'printDisplay' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/good/printDisplay') }}"><i class="fa fa-circle-o"></i> Print Harga Barang Display</a></li>
            @if($role == 'admin')
              <li class="{{ Request::segment(2) == 'good' && Request::segment(3) == 'zeroStock' && Request::segment(7) != '-1' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/good/zeroStock/all/all/1/0') }}"><i class="fa fa-circle-o"></i> Stock Habis</a></li>
              <li class="{{ Request::segment(2) == 'good' && Request::segment(3) == 'zeroStock' && Request::segment(7) == '-1' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/good/zeroStock/all/all/all/-1') }}"><i class="fa fa-circle-o"></i> Stock Minus</a></li>
            @endif
            @if(\Auth::user()->role == 'supervisor')
              <li class="{{ Request::segment(2) == 'good' && Request::segment(3) == 'resume'  ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/good/resume/price/desc/20') }}"><i class="fa fa-circle-o"></i> Rangkuman Barang</a></li>
            @endif
        </ul>
      </li>
      @if($role == 'admin')
        <li class="treeview {{ (Request::segment(2) == 'stock-opname' ) ? 'active' : ''  }}">
          <a href="#">
              <i class="fa fa-search-plus"></i><span> Stock Opname Barang</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
              <li class="{{ Request::segment(2) == 'stock-opname' && Request::segment(4) == 'create' && Request::segment(3) != 'internal' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/stock-opname/create') }}"><i class="fa fa-circle-o"></i> Tambah Stock Opname Barang</a></li>
              <li class="{{ Request::segment(2) == 'stock-opname' && Request::segment(4) != 'create' && Request::segment(4) != 'excel' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/stock-opname/' . date('Y-m-d') . '/' . date('Y-m-d') . '/20') }}"><i class="fa fa-circle-o"></i> Daftar Stock Opname Barang</a></li>
          </ul>
        </li>
        <li class="treeview {{ (Request::segment(2) == 'good-loading' ) ? 'active' : ''  }}">
          <a href="#">
              <i class="fa fa-truck"></i><span> Loading Barang</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
              <li class="{{ Request::segment(2) == 'good-loading' && Request::segment(4) == 'create' && Request::segment(3) != 'internal' && Request::segment(3) != 'transaction-internal' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/good-loading/normal/create') }}"><i class="fa fa-circle-o"></i> Tambah Loading Barang</a></li>
              <li class="{{ Request::segment(2) == 'good-loading' && Request::segment(4) == 'create' && Request::segment(3) == 'internal' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/good-loading/internal/create') }}"><i class="fa fa-circle-o"></i> Tambah Loading Barang Internal</a></li>
              @if(\Auth::user()->email == 'admin')
                <li class="{{ Request::segment(2) == 'good-loading' && Request::segment(4) == 'create' && Request::segment(3) == 'transaction-internal' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/good-loading/transaction-internal/create') }}"><i class="fa fa-circle-o"></i> Tambah Loading Barang & Transaksi Internal</a></li>
              @endif
              <li class="{{ Request::segment(2) == 'good-loading' && Request::segment(4) != 'create' && Request::segment(4) != 'excel' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/good-loading/' . date('Y-m-d') . '/' . date('Y-m-d') . '/all/50') }}"><i class="fa fa-circle-o"></i> Daftar Loading Barang</a></li>
              <li class="{{ Request::segment(2) == 'good-loading' && Request::segment(4) == 'excel' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/good-loading/excel') }}"><i class="fa fa-circle-o"></i> Import Data Excel</a></li>
          </ul>
        </li>
      @endif
      <li class="treeview {{ (Request::segment(2) == 'transaction' ) ? 'active' : ''  }}">
        <a href="#">
            <i class="fa fa-money"></i><span> Transaksi Barang</span>
          <span class="pull-right-container">
            <i class="fa fa-angle-left pull-right"></i>
          </span>
        </a>
        <ul class="treeview-menu">
            <li class="{{ Request::segment(2) == 'transaction' && Request::segment(3) == 'create' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/transaction/create') }}"><i class="fa fa-circle-o"></i> Tambah Transaksi</a></li>
            <li class="{{ Request::segment(2) == 'transaction' && Request::segment(3) == 'createTouch' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/transaction/createTouch') }}"><i class="fa fa-circle-o"></i> Tambah Transaksi (touch screen ver)</a></li>
            <!-- <li class="{{ Request::segment(2) == 'transaction' && Request::segment(3) == 'createNew' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/transaction/createNew') }}" target="_blank()"><i class="fa fa-circle-o"></i> Tambah Transaksi (new page)</a></li> -->
            <li class="{{ Request::segment(2) == 'transaction' && Request::segment(3) != 'create' && Request::segment(3) != 'resume' && Request::segment(3) != 'resumeTotal' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/transaction/all/all/' . date('Y-m-d') . '/' . date('Y-m-d') . '/20') }}"><i class="fa fa-circle-o"></i> Daftar Transaksi</a></li>
            <li class="{{ Request::segment(2) == 'transaction' && Request::segment(3) == 'resumeTotal' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/transaction/resumeTotal/' . date('Y-m-d') . '/' . date('Y-m-d')) }}"><i class="fa fa-circle-o"></i> Resume Transaksi Total</a></li>
            @if(\Auth::user()->email == 'admin')
              <li class="{{ Request::segment(2) == 'transaction' && Request::segment(3) == 'resume' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/transaction/resume/normal/all/all/' . date('Y-m-d') . '/' . date('Y-m-d')) }}"><i class="fa fa-circle-o"></i> Resume Transaksi Per Barang</a></li>
            @endif
        </ul>
      </li>
      <li class="treeview {{ (Request::segment(2) == 'internal-transaction' ) ? 'active' : ''  }}">
        <a href="#">
            <i class="fa fa-building-o"></i><span> Transaksi Internal</span>
          <span class="pull-right-container">
            <i class="fa fa-angle-left pull-right"></i>
          </span>
        </a>
        <ul class="treeview-menu">
            <li class="{{ Request::segment(2) == 'internal-transaction' && Request::segment(3) == 'create' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/internal-transaction/create') }}"><i class="fa fa-circle-o"></i> Tambah Transaksi Internal</a></li>
            <li class="{{ Request::segment(2) == 'internal-transaction' && Request::segment(3) != 'create' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/internal-transaction/all/all/' . date('Y-m-d') . '/' . date('Y-m-d') . '/20') }}"><i class="fa fa-circle-o"></i> Daftar Transaksi Internal</a></li>
        </ul>
      </li>
      @if($role == 'admin')
        <li class="{{ Request::segment(2) == 'retur' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/retur/all/null/20') }}"><i class="fa fa-arrow-left"></i> Barang Retur</a></li>
      @endif
      <li class="header">PEMASUKAN LAIN-LAIN</li>
      <li class="treeview {{ (Request::segment(2) == 'other-transaction' ) ? 'active' : ''  }}">
        <a href="#">
            <i class="fa fa-dollar"></i><span> Transaksi Lain</span>
          <span class="pull-right-container">
            <i class="fa fa-angle-left pull-right"></i>
          </span>
        </a>
        <ul class="treeview-menu">
            <li class="{{ Request::segment(2) == 'other-transaction' && Request::segment(3) == 'create' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/other-transaction/create') }}"><i class="fa fa-circle-o"></i> Tambah Transaksi Lain</a></li>
            <li class="{{ Request::segment(2) == 'other-transaction' && Request::segment(3) != 'create' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/other-transaction/' . date('Y-m-d') . '/' . date('Y-m-d') . '/20') }}"><i class="fa fa-circle-o"></i> Daftar Transaksi Lain</a></li>
        </ul>
      </li>
      <li class="header">PENGELUARAN LAIN-LAIN</li>
      <li class="treeview {{ (Request::segment(2) == 'other-payment' ) ? 'active' : ''  }}">
        <a href="#">
            <i class="fa fa-plus"></i><span> Biaya Lain</span>
          <span class="pull-right-container">
            <i class="fa fa-angle-left pull-right"></i>
          </span>
        </a>
        <ul class="treeview-menu">
            <li class="{{ Request::segment(2) == 'other-payment' && Request::segment(3) == 'create' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/other-payment/create') }}"><i class="fa fa-circle-o"></i> Tambah Biaya Lain</a></li>
            <li class="{{ Request::segment(2) == 'other-payment' && Request::segment(3) != 'create' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/other-payment/' . date('Y-m-d') . '/' . date('Y-m-d') . '/20') }}"><i class="fa fa-circle-o"></i> Daftar Biaya Lain</a></li>
        </ul>
      </li>
      <li class="header">MENU LAIN</li>
      <li class="treeview {{ (Request::segment(2) == 'member' ) ? 'active' : ''  }}">
        <a href="#">
            <i class="fa fa-users"></i><span> Member</span>
          <span class="pull-right-container">
            <i class="fa fa-angle-left pull-right"></i>
          </span>
        </a>
        <ul class="treeview-menu">
          @if(\Auth::user()->email == 'admin')
            <li class="{{ Request::segment(2) == 'member' && Request::segment(3) == 'create' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/member/create') }}"><i class="fa fa-circle-o"></i> Tambah Member</a></li>
          @endif
          <li class="{{ Request::segment(2) == 'member' && Request::segment(3) != 'create' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/member/2020-01-01/' . date('Y-m-d') . '/name/asc/15') }}"><i class="fa fa-circle-o"></i> Daftar Member</a></li>
        </ul>
      </li>
      @if($role == 'admin')
        <li class="treeview {{ (Request::segment(2) == 'delivery-fee' ) ? 'active' : ''  }}">
          <a href="#">
              <i class="fa fa-car"></i><span> Delivery Fee</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
              <li class="{{ Request::segment(2) == 'delivery-fee' && Request::segment(3) == 'create' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/delivery-fee/create') }}"><i class="fa fa-circle-o"></i> Tambah Delivery Fee</a></li>
              <li class="{{ Request::segment(2) == 'delivery-fee' && Request::segment(3) != 'create' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/delivery-fee/15') }}"><i class="fa fa-circle-o"></i> Daftar Delivery Fee</a></li>
          </ul>
        </li>
        @if(\Auth::user()->role == 'supervisor')
          <li class="treeview {{ (Request::segment(2) == 'brand' ) ? 'active' : ''  }}">
            <a href="#">
                <i class="fa fa-html5"></i><span> Brand</span>
              <span class="pull-right-container">
                <i class="fa fa-angle-left pull-right"></i>
              </span>
            </a>
            <ul class="treeview-menu">
                <li class="{{ Request::segment(2) == 'brand' && Request::segment(3) == 'create' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/brand/create') }}"><i class="fa fa-circle-o"></i> Tambah Brand</a></li>
                <li class="{{ Request::segment(2) == 'brand' && Request::segment(3) != 'create' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/brand/15') }}"><i class="fa fa-circle-o"></i> Daftar Brand</a></li>
            </ul>
          </li>
          <li class="treeview {{ (Request::segment(2) == 'distributor' ) ? 'active' : ''  }}">
            <a href="#">
                <i class="fa fa-truck"></i><span> Distributor</span>
              <span class="pull-right-container">
                <i class="fa fa-angle-left pull-right"></i>
              </span>
            </a>
            <ul class="treeview-menu">
                <li class="{{ Request::segment(2) == 'distributor' && Request::segment(3) == 'create' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/distributor/create') }}"><i class="fa fa-circle-o"></i> Tambah Distributor</a></li>
                <li class="{{ Request::segment(2) == 'distributor' && Request::segment(3) != 'create' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/distributor/15') }}"><i class="fa fa-circle-o"></i> Daftar Distributor</a></li>
            </ul>
          </li>
          <li class="treeview {{ (Request::segment(2) == 'category' ) ? 'active' : ''  }}">
            <a href="#">
                <i class="fa fa-shopping-cart"></i><span> Kategori</span>
              <span class="pull-right-container">
                <i class="fa fa-angle-left pull-right"></i>
              </span>
            </a>
            <ul class="treeview-menu">
                <li class="{{ Request::segment(2) == 'category' && Request::segment(3) == 'create' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/category/create') }}"><i class="fa fa-circle-o"></i> Tambah Kategori</a></li>
                <li class="{{ Request::segment(2) == 'category' && Request::segment(3) != 'create' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/category/15') }}"><i class="fa fa-circle-o"></i> Daftar Kategori</a></li>
            </ul>
          </li>
          <li class="treeview {{ Request::segment(2) == 'unit' ? 'active' : ''  }}">
            <a href="#">
              <i class="fa fa-shopping-basket"></i><span> Satuan</span>
              <span class="pull-right-container">
                <i class="fa fa-angle-left pull-right"></i>
              </span>
            </a>
            <ul class="treeview-menu">
                <li class="{{ Request::segment(2) == 'unit' && Request::segment(3) == 'create' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/unit/create') }}"><i class="fa fa-circle-o"></i> Tambah Satuan</a></li>
                <li class="{{ Request::segment(2) == 'unit' && Request::segment(3) != 'create' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/unit/all') }}"><i class="fa fa-circle-o"></i> Daftar Satuan</a></li>
            </ul>
          </li>
          <li class="treeview {{ Request::segment(2) == 'voucher' ? 'active' : ''  }}">
            <a href="#">
              <i class="fa fa-money"></i><span> Voucher</span>
              <span class="pull-right-container">
                <i class="fa fa-angle-left pull-right"></i>
              </span>
            </a>
            <ul class="treeview-menu">
                <li class="{{ Request::segment(2) == 'voucher' && Request::segment(3) == 'create' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/voucher/create') }}"><i class="fa fa-circle-o"></i> Tambah Voucher</a></li>
                <li class="{{ Request::segment(2) == 'voucher' && Request::segment(3) != 'create' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/voucher/all') }}"><i class="fa fa-circle-o"></i> Daftar Voucher</a></li>
            </ul>
          </li>
        @endif
      @endif
      @if(\Auth::user()->email == 'admin')
        <li class="header">LAPORAN KEUANGAN</li>
        <li class="treeview {{ (Request::segment(2) == 'account' ) ? 'active' : ''  }}">
          <a href="#">
              <i class="fa fa-book"></i><span> Akun</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
              <li class="{{ Request::segment(2) == 'account' && Request::segment(3) == 'create' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/account/create') }}"><i class="fa fa-circle-o"></i> Tambah Akun</a></li>
              <li class="{{ Request::segment(2) == 'account' && Request::segment(3) != 'create' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/account/15') }}"><i class="fa fa-circle-o"></i> Daftar Akun</a></li>
          </ul>
        </li>
        <li><a href="{{ url('/' . $role . '/journal/all/all/' . date('Y-m-d') . '/' . date('Y-m-d') . '/id/asc/15') }}"><i class="fa fa-calculator"></i> Jurnal</a></li>
        <li class="{{ Request::segment(2) == 'profit' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/profit') }}"><i class="fa fa-arrow-circle-up"></i> Laba Rugi</a></li>
        <li class="{{ Request::segment(2) == 'scale' ? 'active' : ''  }}"><a href="{{ url('/' . $role . '/scale/2020-01-01/' . date('Y-m-d')) }}"><i class="fa fa-balance-scale"></i> Neraca</a></li>
      @endif
    </ul>
  </section>
</aside>
