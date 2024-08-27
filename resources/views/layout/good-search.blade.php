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

      .btn-input
      {
        height: 60px !important;
        margin: 5px;
        font-size: 20px;
      }

      /*body
      {
        background-color: {{ config('app.app_color') }} !important;
      }*/
    </style>

    <body class="hold-transition sidebar-mini">
        <div class="wrapper">
            <section class="content">
              <div class="row">
                <div class="col-xs-12">
                  <div class="box">
                    <div class="box-header" style="text-align: center;">
                      <h1 class="box-title" style="font-size: 30px !important;">CARI BARANG {{ config('app.name') }}</h1>
                    </div>
                    <div class="box-body" style="overflow-x:scroll; color: black !important; background-color: {{ config('app.app_color') }} !important;">
                      <div class="col-sm-12">
                        <div class="btn col-sm-6" style="border: solid black 2px; font-size: 20px; background-color:white !important;" onclick="changeView('photo')">Tampilan foto</div>
                        <div class="btn col-sm-6" style="border: solid black 2px; font-size: 20px; background-color:white !important;" onclick="changeView('list')">Tampilan list</div>
                      </div>
                      <div class="col-sm-12" style="margin-top: 20px"> 
                        <div class="input-group">
                          <input type="text" name="q" class="form-control" placeholder="Search..." id="search-input" value="{{ $query }}" style="height: 50px; font-size: 30px; border: solid black 2px;">
                          <span class="input-group-btn">
                            <button type="submit" name="search" id="search-btn" class="btn btn-flat"><i class="fa fa-search" style="font-size: 30px"></i></button>
                          </span>
                          <span class="input-group-btn">
                            <div onclick="clearInput()" class="btn btn-flat" style="background-color: red"><i class="fa fa-times" style="font-size: 30px"></i></div>
                          </span>
                          <span class="input-group-btn">
                            <div class="loader" style="display: none;"></div>
                          </span>
                        </div>
                      </div>
                      <!-- <div class="col-sm-12" style=" background-color:white !important;">
                        <div class="col-sm-1 btn btn-input btn-warning" onclick="changeInput('bed cover')">Bed Cover</div>
                        <div class="col-sm-1 btn btn-input btn-warning" onclick="changeInput('beras')">Beras</div>
                        <div class="col-sm-1 btn btn-input btn-warning" onclick="changeInput('ember')">Ember</div>
                        <div class="col-sm-1 btn btn-input btn-warning" onclick="changeInput('gelas')">Gelas</div>
                        <div class="col-sm-1 btn btn-input btn-warning" onclick="changeInput('gula')">Gula</div>
                        <div class="col-sm-1 btn btn-input btn-warning" onclick="changeInput('kompor')">Kompor</div>
                        <div class="col-sm-1 btn btn-input btn-warning" onclick="changeInput('magic com')">Magic Com</div>
                        <div class="col-sm-1 btn btn-input btn-warning" onclick="changeInput('piring')">Piring</div>
                        <div class="col-sm-1 btn btn-input btn-warning" onclick="changeInput('sprei')">Sprei</div>
                        <div class="col-sm-1 btn btn-input btn-warning" onclick="changeInput('tikar')">Tikar</div>
                      </div> -->
                      <div class="col-sm-12" id="photo-div"> 
                        @foreach($goods as $good)
                          <div class="col-sm-4" style="text-align: center; border: black solid 2px;"> 
                            @foreach($good->good_photos as $photo)
                              <img src="{{ URL::to('image/' . $photo->location) }}" style="width: 200px; display: block; margin: auto;">
                            @endforeach
                            <h3>{{ $good->code }}</h3>
                            <h3>{{ $good->name }}</h3>
                            @foreach($good->good_units as $unit)
                              @if($unit->unit != null)
                                <h3>{{ showRupiah($unit->selling_price) . '/' . $unit->unit->name}}</h3>
                              @else
                                {{ $unit }}
                              @endif
                            @endforeach
                            @if($good->getPcsSellingPrice() == null)
                              <h3>0</h3>
                            @else
                              <h3>{{ $good->getStock() . ' ' . $good->getPcsSellingPrice()->unit->code }}</h3>
                            @endif
                          </div>
                        @endforeach
                      </div>
                      <?php $i = 0 ?>
                      <div class="col-sm-12" id="table-div" style="margin-top: 20px">
                        <table class="table table-bordered table-striped" style="font-size: 28px;">
                          <thead>
                            <tr>
                              <th>Kode</th>
                              <th>Nama</th>
                              <th>Harga Jual</th>
                              <th>Stock</th>
                            </tr>
                          </thead>
                          <tbody>
                            @foreach($goods as $good)
                              <tr @if($i % 2 == 0) style="background-color: #FFD0D0" @endif>
                                <td>{{ $good->code }}</td>
                                <td>{{ $good->name }}</td>
                                <td>
                                  @foreach($good->good_units as $unit)
                              @if($unit->unit != null)
                                <h3>{{ showRupiah($unit->selling_price) . '/' . $unit->unit->name}}</h3>
                              @else
                                {{ $unit }}
                              @endif
                                  @endforeach
                                </td>
                                @if($good->getPcsSellingPrice() == null)
                                  <td>0</td>
                                @else
                                  <td>
                                    {{ $good->getStock() . ' ' . $good->getPcsSellingPrice()->unit->code }}<br>
                                    ({{ ($good->getStock() * $good->getPcsSellingPrice()->unit->quantity) . ' ' . $good->getPcsSellingPrice()->unit->base }})
                                  </td>
                                @endif
                              </tr>
                              <?php $i++ ?>
                            @endforeach
                          </tbody>
                        </table>
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
          $('#photo-div').hide();
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

      function changeInput(keyword)
      {
        window.location = window.location.origin + '/search/' + keyword;
      }

      function changeView(view)
      {
        if(view == 'photo')
        {
          $('#photo-div').show();
          $('#table-div').hide();
        }
        else
        {
          $('#photo-div').hide();
          $('#table-div').show();
        }
      }

      function clearInput()
      {
        document.getElementById("search-input").value = "";
        $("#search-input").focus();
      }
    </script>
</html>