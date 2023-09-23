<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">

        <link rel="icon" type="image/gif" href="{{asset('assets/icon/education.png')}}" />
        <title>@if(isset($default['page_name'])){{ $default['page_name'] }}@endif</title>
        <!-- Tell the browser to be responsive to screen width -->
        <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
        <!-- Bootstrap 3.3.7 -->
        <link rel="stylesheet" href="{{asset('assets/bower_components/bootstrap/dist/css/bootstrap.min.css')}}">
        <!-- Font Awesome -->
        <link rel="stylesheet" href="{{asset('assets/bower_components/font-awesome/css/font-awesome.min.css')}}">
        <!-- Ionicons -->
        <link rel="stylesheet" href="{{asset('assets/bower_components/Ionicons/css/ionicons.min.css')}}">
        <!-- Theme style -->
        <link rel="stylesheet" href="{{asset('assets/dist/css/AdminLTE.min.css')}}">
        <!-- AdminLTE Skins. Choose a skin from the css/skins
           folder instead of downloading all of them to reduce the load. -->
        <link rel="stylesheet" href="{{asset('assets/plugins/iCheck/all.css')}}">
        <link rel="stylesheet" href="{{asset('assets/dist/css/skins/_all-skins.min.css')}}">
        <!-- Morris chart -->
        <link rel="stylesheet" href="{{asset('assets/bower_components/morris.js/morris.css')}}">
        <!-- jvectormap -->
        <link rel="stylesheet" href="{{asset('assets/bower_components/jvectormap/jquery-jvectormap.css')}}">
        <!-- Date Picker -->
        <link rel="stylesheet" href="{{asset('assets/bower_components/bootstrap-datepicker/dist/css/bootstrap-datepicker.min.css')}}">
        <!-- Daterange picker -->
        <link rel="stylesheet" href="{{asset('assets/bower_components/bootstrap-daterangepicker/daterangepicker.css')}}">
        <!-- Select2 -->
        <link rel="stylesheet" href="{{asset('assets/bower_components/select2/dist/css/select2.min.css')}}">
        <!-- bootstrap wysihtml5 - text editor -->
        <link rel="stylesheet" href="{{asset('assets/plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.min.css')}}">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cookie&family=Cormorant&family=Dosis&family=Noto+Sans+Arabic&family=Crimson+Text&family=Dancing+Script&family=Inter:wght@300;400;500;600">
    </head>

    <style type="text/css">
      body, span, h1, h2, h3
      {
        font-family: inter !important;
        font-weight: 300;
        color: black;
/*        color: #FF7B54;*/
      }

      .box 
      {
        border: none;
      }

      .box-title, th
      {
        font-weight: 500;
      }

      label
      {
        font-weight: 400;
      }

      .content-wrapper 
      {
        background-color: white;
      }

      .no-btn
      {
          border: 0;
          background-color: transparent;
          color: #FF7B54;
      }

      .btn
      {
        border-color: #FF7B54; 
        background-color: white; 
        color: black;
      }

      .btn:hover
      {
        background-color: #FF7B54; 
        color: white;
      }

      td .fa
      {
        color: #FF7B54;
      }
    </style>

    <body class="hold-transition sidebar-mini" style="background-color: {{ config('app.app_color') }} !important">
        <div class="wrapper">

            <header class="main-header" style="background-color: white !important;border-bottom: 0.5px; border: solid #DDDDDD;">
                <a href="{{ url('/admin/' ) }}" class="logo">
                <span class="logo-mini"><img src="{{asset('assets/icon/education.png')}}" class="user-image" alt="User Image" style="width: 80%"></span>
                <span class="logo-lg" style="font-family: dosis !important;"><img src="{{asset('assets/icon/education.png')}}" class="user-image" alt="User Image" style="width: 15%;"> {{ config('app.name') }}</span>
              </a>
              
              <nav class="navbar navbar-static-top" style="background-color: #FFFFFF !important">
                <a href="#" class="sidebar-toggle" data-toggle="push-menu" role="button">
                  <span class="sr-only">Toggle navigation</span>
                </a>

                <div class="navbar-custom-menu">
                  <ul class="nav navbar-nav">
                    <li class="dropdown user user-menu">
                        <a href="{{ url('/profile') }}">
                        <img src="{{asset('assets/icon/programmer.png')}}" class="user-image" alt="User Image">
                        <span class="hidden-xs">{{ \Auth::user()->name }} | {{ $role }} </span>
                      </a>
                    </li>
                    <li class="dropdown user user-menu">
                      <a href=@if($role != 'admin') "{{ url('/' . $role . '/logout') }}" @else "{{ url('/logout') }}" @endif onclick="event.preventDefault(); document.getElementById('logout-form').submit();"><i class="fa fa-power-off" aria-hidden="true" style="color: red"></i></a>

                      <form id="logout-form" action="{{ url('/' . $role . '/logout') }}" method="POST" style="display: none;">
                          {{ csrf_field() }}
                      </form>
                    </li>
                  </ul>
                </div>
              </nav>
            </header>

            @include('layout.sidebar')

            @yield('content')

            <footer class="main-footer">
                <div class="pull-right hidden-xs">
                  <b>Copyright &copy; 2022 #jadi_aplikasi by NTN Group</b>
                </div>
                <strong>Template by <a href="https://adminlte.io">Almsaeed Studio</a>.</strong> 
            </footer>
        </div>
    </body>

    <script src="{{asset('assets/bower_components/jquery/dist/jquery.min.js')}}"></script>
    <!-- jQuery UI 1.11.4 -->
    <script src="{{asset('assets/bower_components/jquery-ui/jquery-ui.min.js')}}"></script>
    <!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
    <script>
      $.widget.bridge('uibutton', $.ui.button);
    </script>
    <!-- Bootstrap 3.3.7 -->
    <script src="{{asset('assets/bower_components/bootstrap/dist/js/bootstrap.min.js')}}"></script>
    <!-- Select2 -->
    <script src="{{asset('assets/bower_components/select2/dist/js/select2.full.min.js')}}"></script>
    <!-- Morris.js charts -->
    <script src="{{asset('assets/bower_components/raphael/raphael.min.js')}}"></script>
    <script src="{{asset('assets/bower_components/morris.js/morris.min.js')}}"></script>
    <!-- Sparkline -->
    <script src="{{asset('assets/bower_components/jquery-sparkline/dist/jquery.sparkline.min.js')}}"></script>
    <!-- jvectormap -->
    <script src="{{asset('assets/plugins/jvectormap/jquery-jvectormap-1.2.2.min.js')}}"></script>
    <script src="{{asset('assets/plugins/jvectormap/jquery-jvectormap-world-mill-en.js')}}"></script>
    <!-- jQuery Knob Chart -->
    <script src="{{asset('assets/bower_components/jquery-knob/dist/jquery.knob.min.js')}}"></script>
    <!-- daterangepicker -->
    <script src="{{asset('assets/bower_components/moment/min/moment.min.js')}}"></script>
    <script src="{{asset('assets/bower_components/bootstrap-daterangepicker/daterangepicker.js')}}"></script>
    <!-- datepicker -->
    <script src="{{asset('assets/bower_components/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js')}}"></script>
    <!-- Bootstrap WYSIHTML5 -->
    <script src="{{asset('assets/plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.all.min.js')}}"></script>
    <!-- Slimscroll -->
    <script src="{{asset('assets/bower_components/jquery-slimscroll/jquery.slimscroll.min.js')}}"></script>
    <!-- FastClick -->
    <script src="{{asset('assets/bower_components/fastclick/lib/fastclick.js')}}"></script>
    <!-- AdminLTE App -->
    <script src="{{asset('assets/dist/js/adminlte.min.js')}}"></script>
    <!-- AdminLTE dashboard demo (This is only for demo purposes) -->
    <script src="{{asset('assets/dist/js/pages/dashboard.js')}}"></script>
    <!-- AdminLTE for demo purposes -->
    <script src="{{asset('assets/dist/js/demo.js')}}"></script>

    @yield('js-addon')
</html>