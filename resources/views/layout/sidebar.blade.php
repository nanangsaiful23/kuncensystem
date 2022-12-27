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
</style>

<aside class="main-sidebar" style="border-right: 0.5px; border: solid #DDDDDD;">
  <section class="sidebar">
    <ul class="sidebar-menu" data-widget="tree">
      <li class="header">MENU UTAMA</li>
      <li class="treeview {{ (Request::segment(2) == 'good-loading' ) ? 'active' : ''  }}">
        <a href="#">
            <i class="fa fa-truck"></i><span> Loading Barang</span>
          <span class="pull-right-container">
            <i class="fa fa-angle-left pull-right"></i>
          </span>
        </a>
        <ul class="treeview-menu">
            <li class="{{ Request::segment(2) == 'good-loading'&& Request::segment(3) == 'create' ? 'active' : ''  }}"><a href="{{ url('/admin/good-loading/create') }}"><i class="fa fa-circle-o"></i> Input Loading Barang</a></li>
            <li class="{{ Request::segment(2) == 'good-loading'&& Request::segment(3) != 'create' ? 'active' : ''  }}"><a href="{{ url('/admin/good-loading/' . date('Y-m-d') . '/' . date('Y-m-d') . '/all/50') }}"><i class="fa fa-circle-o"></i> List Loading Barang</a></li>
        </ul>
      </li>
      <li class="header">MENU LAIN</li>
      <li class="treeview {{ (Request::segment(2) == 'brand' ) ? 'active' : ''  }}">
        <a href="#">
            <i class="fa fa-html5"></i><span> Brand</span>
          <span class="pull-right-container">
            <i class="fa fa-angle-left pull-right"></i>
          </span>
        </a>
        <ul class="treeview-menu">
            <li class="{{ Request::segment(2) == 'brand' && Request::segment(3) == 'create' ? 'active' : ''  }}"><a href="{{ url('/admin/brand/create') }}"><i class="fa fa-circle-o"></i> Input Brand</a></li>
            <li class="{{ Request::segment(2) == 'brand' && Request::segment(3) != 'create' ? 'active' : ''  }}"><a href="{{ url('/admin/brand/15') }}"><i class="fa fa-circle-o"></i> List Brand</a></li>
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
            <li class="{{ Request::segment(2) == 'category' && Request::segment(3) == 'create' ? 'active' : ''  }}"><a href="{{ url('/admin/category/create') }}"><i class="fa fa-circle-o"></i> Input Kategori</a></li>
            <li class="{{ Request::segment(2) == 'category' && Request::segment(3) != 'create' ? 'active' : ''  }}"><a href="{{ url('/admin/category/15') }}"><i class="fa fa-circle-o"></i> List Kategori</a></li>
        </ul>
      </li>
      <li class="treeview {{ (Request::segment(2) == 'color' ) ? 'active' : ''  }}">
        <a href="#">
            <i class="fa fa-paint-brush"></i><span> Warna</span>
          <span class="pull-right-container">
            <i class="fa fa-angle-left pull-right"></i>
          </span>
        </a>
        <ul class="treeview-menu">
            <li class="{{ Request::segment(2) == 'color' && Request::segment(3) == 'create' ? 'active' : ''  }}"><a href="{{ url('/admin/color/create') }}"><i class="fa fa-circle-o"></i> Input Warna</a></li>
            <li class="{{ Request::segment(2) == 'color' && Request::segment(3) != 'create' ? 'active' : ''  }}"><a href="{{ url('/admin/color/15') }}"><i class="fa fa-circle-o"></i> List Warna</a></li>
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
            <li class="{{ Request::segment(2) == 'distributor' && Request::segment(3) == 'create' ? 'active' : ''  }}"><a href="{{ url('/admin/distributor/create') }}"><i class="fa fa-circle-o"></i> Input Distributor</a></li>
            <li class="{{ Request::segment(2) == 'distributor' && Request::segment(3) != 'create' ? 'active' : ''  }}"><a href="{{ url('/admin/distributor/15') }}"><i class="fa fa-circle-o"></i> List Distributor</a></li>
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
            <li class="{{ Request::segment(2) == 'unit' && Request::segment(3) == 'create' ? 'active' : ''  }}"><a href="{{ url('/admin/unit/create') }}"><i class="fa fa-circle-o"></i> Input Satuan</a></li>
            <li class="{{ Request::segment(2) == 'unit' && Request::segment(3) != 'create' ? 'active' : ''  }}"><a href="{{ url('/admin/unit/10') }}"><i class="fa fa-circle-o"></i> List Satuan</a></li>
        </ul>
      </li>
    </ul>
  </section>
</aside>
