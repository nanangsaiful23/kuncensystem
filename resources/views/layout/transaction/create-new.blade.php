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
        <!-- <link rel="stylesheet" href="{{asset('assets/dist/css/AdminLTE.min.css')}}"> -->
        <!-- AdminLTE Skins. Choose a skin from the css/skins
           folder instead of downloading all of them to reduce the load. -->
        <link rel="stylesheet" href="{{asset('assets/plugins/iCheck/all.css')}}">
        <link rel="stylesheet" href="{{asset('assets/dist/css/skins/_all-skins.min.css')}}">
        <!-- Morris chart -->
        <link rel="stylesheet" href="{{asset('assets/bower_components/morris.js/morris.css')}}">
        <link rel="stylesheet" href="{{asset('assets/bower_components/bootstrap-daterangepicker/daterangepicker.css')}}">
        <!-- jvectormap -->
        <link rel="stylesheet" href="{{asset('assets/bower_components/jvectormap/jquery-jvectormap.css')}}">
        <!-- Select2 -->
        <link rel="stylesheet" href="{{asset('assets/bower_components/select2/dist/css/select2.min.css')}}">
        <link rel="stylesheet" href="{{asset('assets/plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.min.css')}}">

        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
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

      table, tr, td, td .form-control
      {
        border: none !important;
        border-top: none;
      }

      .select2-container--default .select2-selection--multiple .select2-selection__choice
      {
        background-color: rgb(60, 141, 188) !important;
      }

      .modal-body {
        overflow-y: auto;
        }
    </style>

    <body style=" background-color: #DED0B6; max-height: 100vh !important;">
      @include('layout' . '.error')

      <section class="content">
        <div class="row">
           <div class="modal modal-primary fade" id="modal_video">
            <div class="modal-dialog">
              <div class="modal-content">
                <div class="modal-body">
                  <video id="preview" style="display: none"></video>
                </div>
              </div>
            </div>
          </div>
          
          <div class="alert alert-danger alert-dismissible" id="message" style="display:none">
              <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
              <h4><i class="icon fa fa-warning"></i> Barang kosong</h4>
              <div id="empty-item"></div>
          </div>
          {!! Form::model(old(),array('url' => route($role . '.transaction.store'), 'enctype'=>'multipart/form-data', 'method' => 'POST', 'class' => 'form-horizontal', 'id' => 'transaction-form')) !!}
            <div class="col-sm-12" style="max-height: 100vh !important; position: absolute;">
              <div class="col-sm-5" style="">
                <div class="row col-sm-12" style="height: 55vh; overflow-y: scroll; position: absolute; background-color: white;">
                  <table class="table" style="overflow-x: auto; border: none;">
                      <tbody id="table-transaction">
                          <?php $i = 1; ?>
                          <tr style="background-color: #FFE7C1">
                            <td style="display: none;">
                              {!! Form::textarea('barcodes[]', null, array('class' => 'form-control', 'readonly' => 'readonly', 'id' => 'barcode-'.$i)) !!}
                            </td>
                              <td colspan="6" width="60%">
                                  {!! Form::text('name_temps[]', null, array('class' => 'form-control', 'readonly' => 'readonly', 'id' => 'name_temp-'.$i, 'style' => 'font: 28px; background: none; font-weight: 600')) !!}
                                  {!! Form::text('names[]', null, array('id'=>'name-' . $i, 'style' => 'display:none')) !!}
                              </td>
                              <td colspan="2" width="15%">
                                  {!! Form::text('sums[]', null, array('class' => 'form-control', 'readonly' => 'readonly', 'id' => 'sum-'.$i, 'style' => 'font: 22px bold; text-align: right; background: none; font-weight: 600')) !!}
                              </td>
                              <td width="5%"><i class="fa fa-times red" id="delete-{{ $i }}" onclick="deleteItem('-{{ $i }}')"></i></td>
                          </tr>
                          <tr id="row-data-{{ $i }}">
                            <td width="1%">Price</td>
                            <td width="15%">
                                {!! Form::text('buy_prices[]', null, array('id'=>'buy_price-' . $i, 'style' => 'display:none')) !!}
                                {!! Form::text('prices[]', null, array('class' => 'form-control', 'readonly' => 'readonly', 'id' => 'price-'.$i, 'style' => 'background: none')) !!}
                            </td>
                            <td width="1%">Qty</td>
                            <td width="10%">
                              <input type="text" name="quantities[]" class="form-control" id="quantity-{{ $i }}" onchange="checkDiscount('all_barcode', '{{ $i }}')">
                            </td>
                            <td width="1%">Total</td>
                            <td width="10%">
                                {!! Form::text('total_prices[]', null, array('class' => 'form-control', 'readonly' => 'readonly', 'id' => 'total_price-'.$i, 'style' => 'background: none')) !!}
                            </td>
                            <td width="1%">Disc</td>
                            <td colspan="2" width="20%">
                                @if(\Auth::user()->email == 'admin')
                                    <input type="text" name="discounts[]" class="form-control" id="discount-{{ $i }}" onchange="editPrice('all_barcode', '{{ $i }}')">
                                @else
                                    {!! Form::text('discounts[]', 0, array('class' => 'form-control', 'readonly' => 'readonly', 'id' => 'discount-'.$i)) !!}
                                @endif
                            </td>
                          </tr>
                      </tbody>
                  </table>
                </div>
                <div class="row col-sm-5" style="bottom: 0px; position: fixed; height: 45vh; background-color: #E5E1DA;">
                  <div class="col-sm-4">
                    <table style="margin-top: 30px">
                      <tbody style="text-align: center;">
                        <tr>
                          <td style="font-size: 28px">Voucher</td>
                        </tr>
                        <tr>
                          <td>
                            <input type="text" name="voucher_nominal" class="form-control" id="voucher_nominal">
                            <br>
                            <div onclick="event.preventDefault(); checkVoucher();" class= 'btn btn-success btn-flat btn-block form-control'>Check Voucher</div>
                          </td>
                        </tr>
                        <tr>
                          <td id="voucher_result"></td>
                        </tr>
                        <tr>
                          <td>
                            F2 untuk SCAN barang<br>
                            F4 untuk SEARCH barang<br>
                            F8 untuk BAYAR
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                  <div class="col-sm-8"> 
                    <table style="margin-top: 30px">
                      <tbody style="text-align: right;">
                        <tr>
                          <td width="60%" style="padding-right: 10px;">Total Harga</td>
                          <td>{!! Form::text('total_item_price', null, array('class' => 'form-control', 'readonly' => 'readonly', 'id' => 'total_item_price', 'style' => 'background: none; text-align: right; color: black')) !!}</td>
                        </tr>
                        <tr>
                          <td style="padding-right: 10px;">Total Potongan Harga Per Barang</td>
                          <td>{!! Form::text('total_discount_items_price', null, array('class' => 'form-control', 'readonly' => 'readonly', 'id' => 'total_discount_items_price', 'style' => 'background: none; text-align: right;')) !!}</td>
                        </tr>
                        <tr>
                          <td style="padding-right: 10px;">Potongan Harga Akhir</td>
                          <td><input type="text" name="total_discount_price" class="form-control" id="total_discount_price" onchange="changeTotal()" onkeypress="changeTotal()" required="required" onkeyup="formatNumber('total_discount_price')" style="text-align: right;"></td>
                        </tr>
                        <tr>
                          <td style="font-size: 28px; padding-right: 10px;"><b>Total Akhir</b></td>
                          <td>{!! Form::text('total_sum_price', null, array('class' => 'form-control', 'readonly' => 'readonly', 'id' => 'total_sum_price', 'style' => "font-size: 30px; font-weight: 600; text-align: right; color: black; background: none")) !!}</td>
                        </tr>
                        <tr>
                          <td style="padding-right: 10px;">Bayar</td>
                          <td>
                            <input type="text" name="money_paid" class="form-control" id="money_paid" onchange="changeReturn()" onkeypress="changeReturn()" required="required" onkeyup="formatNumber('money_paid')" style="font-size: 30px; text-align: right;">
                          </td>
                        </tr>
                        <tr>
                          <td style="padding-right: 10px;">Kembali</td>
                          <td>{!! Form::text('money_returned', null, array('class' => 'form-control', 'readonly' => 'readonly', 'id' => 'money_returned', 'style' => 'text-align: right')) !!}</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                  <div class="col-sm-12" style="margin-bottom: 10px; margin-top: 10px;">
                    <div onclick="event.preventDefault(); submitForm(this);" class= 'btn btn-success btn-flat btn-block form-control' style="height: 50px; font-size: 30px;">Proses Transaksi</div>
                  </div>
                  {{ Form::hidden('type', 'normal') }}
                </div>
              </div>
              <div class="col-sm-7" style="background-color: #DED0B6; height: auto;">
                <div class="row col-sm-12" style="height: 20vh; top: 5vh; background-color: #DED0B6;">
                  <div class="col-sm-12" style="height: 40px!important; font-size: 20px;">
                      {!! Form::label('all_barcode', 'Cari barcode', array('class' => 'col-sm-3 control-label')) !!}
                      <div class="col-sm-8">
                          <input type="text" name="all_barcode" class="form-control" id="all_barcode" onchange="searchByBarcode('all_barcode')">
                      </div>
                  </div>
                  <div class="col-sm-12" style="height: 40px!important; font-size: 20px;">
                      {!! Form::label('keyword', 'Cari keyword', array('class' => 'col-sm-3 control-label')) !!}
                      <div class="col-sm-8">
                          <input type="text" name="search_good" class="form-control" id="search_good">
                      </div>
                       <div class="modal modal-primary fade" id="modal_search">
                        <div class="modal-dialog">
                          <div class="modal-content">
                            <div class="modal-header">
                              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span></button>
                                <h4 class="modal-title">Hasil Keyword (klik nama barang)</h4>
                            </div>
                            <div class="modal-body">
                              <div id="result_good"></div>
                            </div>
                            <div class="modal-footer">
                              <button type="button" class="btn btn-outline pull-left" data-dismiss="modal">Close</button>
                            </div>
                          </div>
                        </div>
                      </div>
                  </div>
                </div>
                <div class="row" style="margin-top: 25vh;">
                  <div class="col-sm-12" style="font-size: 15px; overflow-y: auto; height: 500px; padding-bottom: 130px;">
                      @foreach($goods as $good)
                        <div class="col-sm-2" style="text-align: center; background-color: white; margin: 2px; border: black solid 2px; border-radius: 5px; height: 180px; width: 200px; cursor: pointer;" onclick="searchByKeyword('all_barcode', '{{ $good->guid }}')"> 
                          <img src="{{ URL::to('image/' . $good->location) }}" style="width: 120px; display: block; margin: auto; margin-top: 5px; margin-bottom: 3px; border-radius: 5px;">
                          {{ $good->good_name . ' ' . $good->unit_name }}
                        </div>
                      @endforeach
                  </div>
                </div>
                <div class="row" style="bottom: 0px; position: fixed; height: 25vh; background-color: #DED0B6; ">
                  <div class="col-sm-12" style="height: 15vh; background-color: #DED0B6;">
                    <div class="col-sm-6">
                      {!! Form::label('member_id', 'Member', array('class' => 'col-sm-4 control-label')) !!}
                      <div class="col-sm-8">
                          {!! Form::text('member_name', null, array('class' => 'form-control', 'id' => 'member_name')) !!}
                          <select class="form-control select2" style="width: 100%;" name="member_id" id="all_member">
                              <div>
                                  @foreach(getMembers() as $member)
                                  <option value="{{ $member->id }}">
                                      {{ $member->name . ' (' . $member->address . ')'}}</option>
                                  @endforeach
                              </div>
                          </select>
                      </div>
                    </div>
                    <div class="col-sm-6">
                      {!! Form::label('payment', 'Jenis Pembayaran', array('class' => 'col-sm-3 control-label', 'style' => 'text-align: left')) !!}
                      <div class="col-sm-8">
                          <h5>(jika pembayaran hutang, pilih metode cash dan nominal uang isi sesuai dengan uang yang diterima)</h5>
                          <select class="form-control select2" style="width: 100%;" name="payment">
                              <div>
                                  <option value="cash">Cash/Uang</option>
                                  <option value="transfer">Transfer</option>
                              </div>
                          </select>
                      </div>
                    </div>
                  </div>
                  <div class="col-sm-12" style="height: 10vh; background-color: #DED0B6;">
                    <div class="col-sm-6">
                        <div class="col-sm-8 col-sm-offset-4 btn btn-warning" onclick="startCamera()">Scan Barcode Member</div>
                        <div class="col-sm-8 col-sm-offset-4 btn btn-warning" onclick="stopCamera()">Berhenti Scan</div>
                    </div>
                    <div class="col-sm-6">
                      {!! Form::label('note', 'Keterangan', array('class' => 'col-sm-3 control-label')) !!}
                      <div class="col-sm-8">
                          {!! Form::text('note', null, array('class' => 'form-control', 'style' => 'height: 70px')) !!}
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="row" style="background-color: yellow; display: none;">
                <h3>Transaksi Retur</h3>
                <div class="form-group col-sm-5" style="height: 40px!important; font-size: 20px;">
                    {!! Form::label('all_barcode_retur', 'Cari barcode', array('class' => 'col-sm-4 control-label')) !!}
                    <div class="col-sm-8">
                        <input type="text" name="all_barcode_retur" class="form-control" id="all_barcode_retur" onchange="searchByBarcode('all_barcode_retur')">
                    </div>
                </div>
                <div class="form-group col-sm-7" style="height: 40px!important; font-size: 20px;">
                    {!! Form::label('keyword_retur', 'Cari keyword', array('class' => 'col-sm-3 control-label')) !!}
                    <div class="col-sm-8">
                        <input type="text" name="search_good_retur" class="form-control" id="search_good_retur">
                    </div>
                     <div class="modal modal-primary fade" id="modal_search_retur">
                      <div class="modal-dialog">
                        <div class="modal-content">
                          <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                              <span aria-hidden="true">&times;</span></button>
                              <h4 class="modal-title">Hasil Keyword (klik nama barang)</h4>
                          </div>
                          <div class="modal-body">
                            <div id="result_good_retur"></div>
                          </div>
                          <div class="modal-footer">
                            <button type="button" class="btn btn-outline pull-left" data-dismiss="modal">Close</button>
                          </div>
                        </div>
                      </div>
                    </div>
                </div>
                <table class="table table-bordered table-striped" style="overflow-x: auto;">
                    <thead>
                        <th>Barcode</th>
                        <th>Nama</th>
                        <th>Kondisi</th>
                        <th>Jumlah</th>
                        <th>Harga</th>
                        <th>Potongan</th>
                        <th>Total Harga</th>
                        <th>Total Akhir</th>
                        <th>Hapus</th>
                    </thead>
                    <tbody id="table-transaction-retur">
                        <?php $i = 1; ?>
                        <tr id="row-data-retur_s{{ $i }}">
                            <td>
                                {!! Form::textarea('barcodesretur_s[]', null, array('class' => 'form-control', 'readonly' => 'readonly', 'id' => 'barcode-retur_s'.$i, 'style' => 'height: 70px')) !!}
                            </td>
                            <td width="30%">
                                {!! Form::textarea('name_tempsretur_s[]', null, array('class' => 'form-control', 'readonly' => 'readonly', 'id' => 'name_temp-retur_s'.$i, 'style' => 'height: 70px')) !!}
                                {!! Form::text('namesretur_s[]', null, array('id'=>'name-retur_s' . $i, 'style' => 'display:none')) !!}
                            </td>
                            <td>
                                <select class="form-control select2" style="width: 100%;" name="conditionsretur_s[]" id="conditionretur_s{{ $i }}">
                                    <div>
                                        <option value="rusak">Rusak</option>
                                        <option value="not">Tidak Rusak</option>
                                    </div>
                                </select>
                            </td>
                            <td>
                                <input type="text" name="quantitiesretur_s[]" class="form-control" id="quantity-retur_s{{ $i }}" onchange="checkDiscount('all_barcode_retur', '{{ $i }}')">
                            </td>
                            <td>
                                {!! Form::text('buy_pricesretur_s[]', null, array('id'=>'buy_price-retur_s' . $i, 'style' => 'display:none')) !!}
                                {!! Form::text('pricesretur_s[]', null, array('class' => 'form-control', 'readonly' => 'readonly', 'id' => 'price-retur_s'.$i)) !!}
                            </td>
                            <td>
                                @if(\Auth::user()->email == 'admin')
                                    <input type="text" name="discountsretur_s[]" class="form-control" id="discount-retur_s{{ $i }}" onchange="editPrice('all_barcode_retur', '{{ $i }}')">
                                @else
                                    {!! Form::text('discountsretur_s[]', 0, array('class' => 'form-control', 'readonly' => 'readonly', 'id' => 'discount-retur_s'.$i)) !!}
                                @endif
                            </td>
                            <td>
                                {!! Form::text('total_pricesretur_s[]', null, array('class' => 'form-control', 'readonly' => 'readonly', 'id' => 'total_price-retur_s'.$i)) !!}
                            </td>
                            <td>
                                {!! Form::text('sumsretur_s[]', null, array('class' => 'form-control', 'readonly' => 'readonly', 'id' => 'sum-retur_s'.$i)) !!}
                            </td>
                            <td><i class="fa fa-times red" id="delete-retur_s{{ $i }}" onclick="deleteItem('-retur_s{{ $i }}')"></i></td>
                        </tr>
                    </tbody>
                </table>
            </div>
          {!! Form::close() !!}
        </div>
      </section>
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

    <script type="text/javascript" src="https://rawgit.com/schmich/instascan-builds/master/instascan.min.js"></script>

    <script type="text/javascript">
        let scanner = new Instascan.Scanner({ video: document.getElementById('preview') });
        var total_item = 1;
        var total_item_retur = 1;
        $(document).ready (function (){
            $('.select2').select2();
            $("#all_barcode").focus();
            document.getElementById("total_discount_price").value = 0;

            $("#search_good").keyup( function(e){
              if(e.keyCode == 13)
              {
                ajaxFunction("all_barcode");
              }
            });

            $("#search_good_retur").keyup( function(e){
              if(e.keyCode == 13)
              {
                ajaxFunction("all_barcode_retur");
              }
            });
        });

        document.addEventListener('keydown', function(event) {
            if (event.keyCode == 113) {  //F2
                $("#all_barcode").focus();
            }
            else if (event.keyCode == 115) { //F4
                $("#search_good").focus();
            }
            else if (event.keyCode == 119) { //F8
                $("#money_paid").focus();
            }
        }, true);

        $('#modal_search').on('shown.bs.modal', function() {
          $('#search_good').focus();
        })

        function fillItem(name, good)
        {
            var bool = false;
            var type = '';
            var items = total_item;

            if(name == 'all_barcode_retur')
            {
                type = 'retur_s';
                items = total_item_retur;
            }

            if(good.length != 0)
            {
                for (var i = 1; i <= items; i++)
                {
                    if(document.getElementById("barcode-" + type + i))
                    {
                        if(document.getElementById("barcode-" + type + i).value != '' && document.getElementById("barcode-" + type + i).value == good.getPcsSellingPrice.id && document.getElementById("price-" + type + i).value == good.getPcsSellingPrice.selling_price)
                        {
                            temp_total = document.getElementById("quantity-" + type + i).value;
                            temp_total = parseInt(temp_total) + 1;
                            document.getElementById("quantity-" + type + i).value = temp_total;
                            bool = true;

                            editPrice(name, i);
                            break;
                        }
                    }
                }

                if(bool == false)
                {
                    document.getElementById("name-" + type + items).value = good.id;
                    document.getElementById("name_temp-" + type + items).value = good.name + " " + good.getPcsSellingPrice.name;
                    document.getElementById("barcode-" + type + items).value = good.getPcsSellingPrice.id;
                    document.getElementById("quantity-" + type + items).value = 1;

                    document.getElementById("price-" + type + items).value = good.getPcsSellingPrice.selling_price;
                    document.getElementById("discount-" + type + items).value = '0';
                    document.getElementById("buy_price-" + type + items).value = good.getPcsSellingPrice.buy_price;
                    document.getElementById("total_price-" + type + items).value = good.getPcsSellingPrice.selling_price;

                    editPrice(name, items);

                    document.getElementById(name).value = '';
                    $("#" + name).focus();

                }
                document.getElementById(name).value = '';
                $("#" + name).focus();
            }
            else
            {
                alert('Barang tidak ditemukan');
                document.getElementById("barcode-" + type + index).value = '';
                document.getElementById("name-" + type + index).focus();
            }
        }

        function searchByKeyword(name, good_unit_id)
        {
            type = '';
            if(name == 'all_barcode_retur')
            {
                type = '_retur';
            }
            
            $.ajax({
              url: "{!! url($role . '/good/searchByGoodUnit/') !!}/" + good_unit_id,
              success: function(result){
                var good = result.good;
                if(good.stock <= 0)
                {
                    document.getElementById("message").style.display = "block";
                    htmlResult2 = "> " + good.name + " stock: " + good.stock + "<br>";
                    $("#empty-item").append(htmlResult2);
                }
                fillItem(name, result.good);
                $('#modal_search' + type).modal('hide');
                $('#search_good' + type).val('');
                $('#result_good' + type).val('');
            },
              error: function(){
              }
            });
        }

        function searchByBarcode(name) 
        {
            $.ajax({
              url: "{!! url($role . '/good/searchByBarcode/') !!}/" + $("#" + name).val(),
              success: function(result){
                var good = result.good;
                fillItem(name, result.good)},
              error: function(){
              }
            });
        }

        function checkDiscount(name_div, index)
        {
                editPrice(name_div, index);
        }

        function changeTotal()
        {
            total_item_price = parseInt(0);
            total_discount_price = parseInt(0);
            total_sum_price = parseInt(0);
            total_discount_items = parseInt(0);

            for (var i = 1; i <= total_item; i++)
            {
                if(document.getElementById("barcode-" + i))
                {
                    if(document.getElementById("barcode-" + i).value != '')
                    {
                        items = document.getElementById("price-" + i).value * document.getElementById("quantity-" + i).value;

                        total_item_price += parseInt(items);

                        sums = document.getElementById("sum-" + i).value;
                        sums = sums.replace(/,/g,'');

                        total_sum_price += parseInt(sums);

                        discount = document.getElementById("discount-" + i).value;
                        discount = discount.replace(/,/g,'');

                        total_discount_items += parseInt(discount);
                    }
                }
            }

            for (var i = 1; i <= total_item_retur; i++)
            {
                if(document.getElementById("barcode-retur_s" + i))
                {
                    if(document.getElementById("barcode-retur_s" + i).value != '')
                    {
                        items = document.getElementById("price-retur_s" + i).value * document.getElementById("quantity-retur_s" + i).value;

                        total_item_price -= parseInt(items);

                        sums = document.getElementById("sum-retur_s" + i).value;
                        sums = sums.replace(/,/g,'');

                        total_sum_price -= parseInt(sums);

                        discount = document.getElementById("discount-retur_s" + i).value;
                        discount = discount.replace(/,/g,'');

                        total_discount_items += parseInt(discount);
                    }
                }
            }

            discount = document.getElementById("total_discount_price").value;
            discount = discount.replace(/,/g,'');
            total_sum_price = parseInt(total_sum_price) - parseInt(discount);

            document.getElementById("total_item_price").value = total_item_price;
            document.getElementById("total_discount_items_price").value = total_discount_items;
            document.getElementById("total_sum_price").value = total_sum_price - $('#voucher_nominal').val();

            formatNumber("total_item_price");
            formatNumber("total_discount_items_price");
            formatNumber("total_sum_price");

            changeReturn();
        }

        function changeReturn()
        {
            total = document.getElementById("money_paid").value;
            total = total.replace(/,/g,'');

            sum = document.getElementById("total_sum_price").value;
            sum = sum.replace(/,/g,'');
            money_returned = parseInt(total) - parseInt(sum);

            document.getElementById("money_returned").value = money_returned;
            formatNumber("money_returned");
        }

        function submitForm(btn)
        {
            if($('#money_paid').val() != '' && $('#total_discount_price').val() != '')
            {
                if(parseInt(unFormatNumber($('#money_paid').val())) < parseInt(unFormatNumber($('#total_sum_price').val())) && ($('#all_member').val() == '1' && $('#member_name').val() == ''))
                {
                    alert('Jumlah pembayaran kurang dari total belanja. Silahkan pilih member');
                }
                else
                {
                    btn.disabled = true;
                    document.getElementById('transaction-form').submit();
                    // alert('hay');
                }
            }
            else
            {
                alert('Silahkan masukkan jumlah uang dan potongan toko');
            }
        }

        function formatNumber(name)
        {
            num = document.getElementById(name).value;
            num = num.toString().replace(/,/g,'');
            document.getElementById(name).value = num.toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,');
        }

        function unFormatNumber(num)
        {
            return num.replace(/,/g,'');
        }

        function deleteItem(index)
        {
            console.log('masuk delet');
            $("#row-data" + index).remove();
            changeTotal();
        }

        function editPrice(name, index)
        {
            temp1=parseInt(index)+1
            var type = '';
            var items = total_item;
            var td_rusak = '';
            if(name == 'all_barcode_retur')
            {
                type = 'retur_s';
                items = total_item_retur;
                td_rusak = '<td><select class="form-control select2" style="width: 100%;" name="conditionsretur_s[]" id="conditionretur_s' + temp1 + '"><div><option value="rusak">Rusak</option><option value="not">Tidak Rusak</option></div></select></td>';
            }
            console.log(name);

            document.getElementById("total_price-" + type + index).value = (unFormatNumber(document.getElementById("price-" + type + index).value) * unFormatNumber(document.getElementById("quantity-" + type + index).value));

            document.getElementById("sum-" + type + index).value = document.getElementById("total_price-" + type + index).value - unFormatNumber(document.getElementById("discount-" + type + index).value);

            formatNumber("total_price-" + type + index);
            formatNumber("sum-" + type + index);
            formatNumber("discount-" + type + index);

            changeTotal();

            htmlResult = '<tr style="background-color: #FFE7C1">';

            htmlResult += '<td style="display: none"><input type="text" name="barcodes' + type + '[]" class="form-control" id="barcode-' + type + temp1 + '" readonly="readonly"></td><td colspan="6" width="60%"><textarea  class="form-control" readonly="readonly" id="name_temp-' + type + temp1 + '" name="name_temps' + type + '[]" type="text" style="font: 28px; background: none; font-weight: 600"></textarea><input id="name-' + type + temp1 + '" name="names' + type + '[]" type="text" style="display:none"></td><td colspan="2" width="20%"><input class="form-control" readonly="readonly" id="sum-' + type + temp1+'" name="sums' + type + '[]" type="text" style="font: 22px bold; text-align: right; background: none; font-weight: 600"></td><td><i class="fa fa-times red" id="delete-' + type + temp1+'" onclick="deleteItem(\'-' + type + temp1 + '\')"></i></td></tr><tr id="row-data' + "-" + type + temp1 + '">' + td_rusak + '<td width="1%">Price</td><td width="10%"><input id="buy_price-' + type + temp1 + '" name="buy_prices' + type + '[]" type="text" style="display:none"><input class="form-control" readonly="readonly" id="price-' + type +temp1 + '" name="prices' + type + '[]" type="text"></td><td width="1%">Qty</td><td width="10%"><input type="text" name="quantities' + type + '[]" class="form-control" id="quantity-' + type + temp1+'" onchange="checkDiscount(\'' + name + '\', \'' + temp1 + '\')"></td><td width="1%">Total</td><td width="25%"><input class="form-control" readonly="readonly" id="total_price-' + type + temp1+ '" name="total_prices' + type + '[]" type="text"></td><td width="1%">Discount</td><td width="10%"><input type="text" name="discounts' + type + '[]" class="form-control" id="discount-' + type + temp1+'" onchange="editPrice(\'' + name + '\', \'' + type + temp1 + '\')"></td></tr>';
           
            if(index == items)
            {
                if(name == 'all_barcode_retur')
                {
                    total_item_retur += 1;
                    $("#table-transaction-retur").prepend(htmlResult);
                }
                else
                {
                    total_item += 1;
                    $("#table-transaction").prepend(htmlResult);
                }
            }
            document.getElementById(name).value = '';
            $("#" + name).focus();

        }

        function ajaxFunction(name)
        {
            type = '';
            if(name == 'all_barcode_retur')
            {
                type = '_retur';
            }
            
            $('#modal_search' + type).modal('show');   

              $.ajax({
                url: "{!! url($role . '/good/searchByKeywordGoodUnit/') !!}/" + $("#search_good" + type).val(),
                success: function(result){
                    htmlResult = '';

                    htmlResult += "<style type='text/css'>.modal-div:hover { background-color: white; }</style>";
                  var r = result.good_units;

                  for (var i = 0; i < r.length; i++) {
                    if((i%2) == 0) 
                    {
                        color = '#FFF1CE';
                    }
                    else color = "#FDEFF4";
                    htmlResult += "<textarea class='col-sm-12 modal-div' style='display:inline-block; color:black; cursor: pointer; min-height:40px; max-height:80px; background-color:" + color + "; padding: 5px;' onclick='searchByKeyword(\"" + name + "\",\"" + r[i].good_unit_id + "\")'>" + r[i].name + " " + r[i].unit + "</textarea>";
                  }
                  $("#result_good" + type).html(htmlResult);
                  $('.modal-body').css('height',$( window ).height()*0.5);
                },
                error: function(){
                    console.log('error');
                }
              });
        }

        function ajaxButton(keyword)
        {
            name = "all_barcode";
            $('#modal_search').modal('show');   
              $.ajax({
                url: "{!! url($role . '/good/searchByKeywordGoodUnit/') !!}/" + keyword,
                success: function(result){
                    htmlResult = '';

                    htmlResult += "<style type='text/css'>.modal-div:hover { background-color: white; }</style>";
                  var r = result.good_units;

                  for (var i = 0; i < r.length; i++) {
                    if((i%2) == 0) 
                    {
                        color = '#FFF1CE';
                    }
                    else color = "#FDEFF4";
                    htmlResult += "<textarea class='col-sm-12 modal-div' style='display:inline-block; color:black; cursor: pointer; min-height:40px; max-height:80px; background-color:" + color + "; padding: 5px;' onclick='searchByKeyword(\"" + name + "\",\"" + r[i].good_unit_id + "\")'>" + r[i].name + " " + r[i].unit + "</textarea>";
                  }
                  $("#result_good").html(htmlResult);
                  $('.modal-body').css('height',$( window ).height()*0.5);
                },
                error: function(){
                    console.log('error');
                }
              });
        }

        function checkVoucher()
        {
            if(total_item == 1 && total_item_retur == 1)
            {
                alert('Silahkan pilih barang');
            }
            else
            {

              $.ajax({
                url: "{!! url($role . '/voucher/searchByCode/') !!}/" + $('#voucher').val(),
                success: function(result){
                    var r = result.voucher;
                    console.log(result);
                    if(result.voucher != null)
                    {
                        if(r.type == 'discount')
                        {
                            $("#voucher_result").html("Diskon sebesar " + r.nominal + " %");
                            potongan = parseInt(unFormatNumber($('#total_sum_price').val())) * r.nominal / 100;
                            total = parseInt(unFormatNumber($('#total_sum_price').val())) - potongan;
                            $('#voucher_result').css('background-color', '#DADDB1');
                            $('#voucher_result').css('height',$( window ).height()*0.1);
                        }
                        else
                        {
                            $("#voucher_result").html("Potongan sebesar Rp" + r.nominal);
                            potongan = r.nominal;
                            total = parseInt(unFormatNumber($('#total_sum_price').val())) - potongan;
                            $('#voucher_result').css('background-color', '#DADDB1');
                            $('#voucher_result').css('height',$( window ).height()*0.1);
                        }
                        $('#total_sum_price').val(total);
                        $('#voucher_nominal').val(potongan);
                    }
                    else
                    {
                      $("#voucher_result").html(result.message);
                      $('#voucher_result').css('background-color', '#FF6969');
                      $('#voucher_result').css('height',$( window ).height()*0.1);
                      $('#voucher_nominal').val(0);
                    }
                },
                error: function(){
                    console.log('error');
                }
              }); 
            }
        }

        function startCamera()
        {    
            $('#modal_video').modal('show');  
            $('#preview').show();
          scanner.addListener('scan', function (content) {
            $.ajax({
                url: "{!! url($role . '/member/search/') !!}/" + content,
                success: function(result){
                    if(result.member != null)
                    {
                        $("#all_member").val(result.member.id).change(); 
                    }
                    else
                    {
                        alert('Member tidak ditemukan');
                    }
                    scanner.stop();
                    $('#preview').hide();
                },
                error: function(){
                    console.log('error');
                }
              });
          });
          Instascan.Camera.getCameras().then(function (cameras) {
            if (cameras.length > 0) {
              scanner.start(cameras[0]);
            } else {
              console.error('No cameras found.');
            }
          }).catch(function (e) {
            console.error(e);
          });
        }

        function stopCamera()
        {
          // let scanner = new Instascan.Scanner({ video: document.getElementById('preview') });
            scanner.stop();
            $('#preview').hide();
            $('#modal_video').modal('hide');  
        }
    </script>
</html>