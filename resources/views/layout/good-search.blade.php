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

      table, th, td
      {
        border: solid black 2px !important;
      }
    </style>

    <body class="hold-transition sidebar-mini" style="background-color: white !important">
        <div class="wrapper">
            <section class="content">
              <div class="row">
                <div class="col-xs-12">
                  <div class="box">
                    <div class="box-header" style="text-align: center;">
                      <h1 class="box-title" style="font-size: 30px !important;">CARI BARANG</h1>
                    </div>
                    <div class="box-body" style="overflow-x:scroll; color: black !important">
                      <div class="col-sm-12"> 
                        <div class="input-group">
                          <input type="text" name="q" class="form-control" placeholder="Search..." id="search-input" value="{{ $query }}" style="height: 50px; font-size: 30px; border: solid black 2px;">
                          <span class="input-group-btn">
                            <button type="submit" name="search" id="search-btn" class="btn btn-flat"><i class="fa fa-search" style="font-size: 30px"></i>
                            </button>
                          </span>
                          <span class="input-group-btn">
                            <div class="loader" style="display: none;"></div>
                          </span>
                        </div>
                      </div>
                      <div class="col-sm-12"> 
                        @foreach($goods as $good)
                          <div class="col-sm-4" style="text-align: center; border: black solid 2px;"> 
                            @foreach($good->good_photos as $photo)
                              <img src="{{ URL::to('image/' . $photo->location) }}" style="width: 200px; display: block; margin: auto;">
                            @endforeach
                            <h3>{{ $good->code }}</h3>
                            <h3>{{ $good->name }}</h3>
                            @foreach($good->good_units as $unit)
                              <h3>{{ showRupiah($unit->selling_price) . '/' . $unit->unit->name}}</h3>
                            @endforeach
                            <h3>{{ $good->getStock() . ' ' . $good->getPcsSellingPrice()->unit->code }}</h3>
                          </div>
                        @endforeach
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </section>
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

    <script type="text/javascript">
      $(document).ready(function(){
          $('.select2').select2();
          $("#search-input").keyup( function(e){
            if(e.keyCode == 13)
            {
              search();
            }
          });

          $("#search-btn").click(function(){
              search();
          });
      });
      function search()
      {
        window.location = window.location.origin + '/search/' + $('#search-input').val();
      }
    </script>
</html>