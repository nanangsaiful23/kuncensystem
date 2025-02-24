<style type="text/css">
  .select2-container--default .select2-selection--multiple .select2-selection__choice
  {
    background-color: rgb(60, 141, 188) !important;
  }

  .modal-body {
    overflow-y: auto;
    }

    .modal-content {
        /*width: 1500px;
        margin-left: -500px;*/
    }

    table tr td .form-control, .form-control[disabled], .form-control[readonly], fieldset[disabled] .form-control
    {
        font-size: 13px;
        background-color: white !important;
    }

    input:disabled
    {
        background-color: white !important;
    }

    table tr td
    {
        padding: 0px !important;
    }
</style>
<script type="text/javascript" src="https://rawgit.com/schmich/instascan-builds/master/instascan.min.js"></script>

<div class="panel-body" style="margin-top: -30px">
    <div class="row">
        <div class="form-group col-sm-9">
            <table class="table table-bordered table-striped" style="overflow-x: auto;">
                <thead>
                    <th style="display: none;">Barcode</th>
                    <th>No</th>
                    <th>Nama</th>
                    <th>Qty</th>
                    <th>Harga</th>
                    <th>Potongan</th>
                    <th>Total Harga</th>
                    <th>Total Akhir</th>
                    <th>X</th>
                </thead>
                <tbody id="table-transaction">
                    <?php $i = 1; ?>
                    <tr id="row-data-{{ $i }}" @if($i % 2 == 0) style="background-color: #FDDBBB" @endif>
                        <td style="display: none;">
                            {!! Form::text('barcodes[]', null, array('class' => 'form-control', 'readonly' => 'readonly', 'id' => 'barcode-'.$i)) !!}
                        </td>
                        <td width="5%">
                            <input type="text" name="numbers[]" class="form-control" id="no-{{ $i }}" value="{{ $i }}">
                        </td>
                        <td width="50%">
                            {!! Form::text('name_temps[]', null, array('class' => 'form-control', 'readonly' => 'readonly', 'id' => 'name_temp-'.$i)) !!}
                            {!! Form::text('names[]', null, array('id'=>'name-' . $i, 'style' => 'display:none')) !!}
                        </td>
                        <td width="5%">
                            <input type="text" name="quantities[]" class="form-control" id="quantity-{{ $i }}" onchange="checkDiscount('all_barcode', '{{ $i }}')">
                        </td>
                        <td width="9%">
                            {!! Form::text('buy_prices[]', null, array('id'=>'buy_price-' . $i, 'style' => 'display:none')) !!}
                            {!! Form::text('prices[]', null, array('class' => 'form-control', 'readonly' => 'readonly', 'id' => 'price-'.$i, 'style' => 'text-align: right')) !!}
                        </td>
                        <td width="8%">
                            @if(\Auth::user()->email == 'admin')
                                <input type="text" name="discounts[]" class="form-control" id="discount-{{ $i }}" onchange="editPrice('all_barcode', '{{ $i }}')" style="text-align: right;">
                            @else
                                {!! Form::text('discounts[]', 0, array('class' => 'form-control', 'readonly' => 'readonly', 'id' => 'discount-'.$i)) !!}
                            @endif
                        </td>
                        <td width="9%">
                            {!! Form::text('total_prices[]', null, array('class' => 'form-control', 'readonly' => 'readonly', 'id' => 'total_price-'.$i, 'style' => 'text-align: right')) !!}
                        </td>
                        <td width="9%">
                            {!! Form::text('sums[]', null, array('class' => 'form-control', 'readonly' => 'readonly', 'id' => 'sum-'.$i, 'style' => 'text-align: right')) !!}
                        </td>
                        <td width="3%"><i class="fa fa-times red" id="delete-{{ $i }}" onclick="deleteItem('-{{ $i }}')"></i></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="form-group col-sm-3">
            <video id="preview" style="display: none; margin-top: 0px;"></video>
            <div class="alert alert-danger alert-dismissible" id="message" style="display:none">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <h4><i class="icon fa fa-warning"></i> Barang kosong</h4>
                <div id="empty-item"></div>
            </div>
            <div class="form-group col-sm-12" style="height: 40px!important; font-size: 20px;">
                {!! Form::label('all_barcode', 'Barcode', array('class' => 'col-sm-4 control-label')) !!}
                <div class="col-sm-8">
                    <input type="text" name="all_barcode" class="form-control" id="all_barcode" onchange="searchByBarcode('all_barcode')" style="background-color: #DCE4C9">
                </div>
            </div>
            <div class="form-group col-sm-12" style="height: 40px!important; font-size: 20px; margin-top: -10px;">
                {!! Form::label('keyword', 'Keyword', array('class' => 'col-sm-4 control-label')) !!}
                <div class="col-sm-8">
                    <input type="text" name="search_good" class="form-control" id="search_good" style="background-color: #F0C1E1">
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
            <div class="form-group col-sm-12" style="margin-top: -10px;">
                {!! Form::label('member_id', 'Member', array('class' => 'col-sm-4 control-label')) !!}
                <div class="col-sm-8">
                    @if($SubmitButtonText == 'View')
                        {!! Form::text('member', null, array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                    @else
                        {!! Form::text('member_name', null, array('class' => 'form-control', 'id' => 'member_name')) !!}
                        <select class="form-control select2" style="width: 100%;" name="member_id" id="all_member">
                            <div>
                                @foreach(getMembers() as $member)
                                <option value="{{ $member->id }}">
                                    {{ $member->name . ' (' . $member->address . ')'}}</option>
                                @endforeach
                            </div>
                        </select>
                    @endif
                </div>
            </div>
            <div class="form-group col-sm-12" style="margin-top: -10px;">
                <div class="col-sm-8 col-sm-offset-4 btn btn-warning" onclick="startCamera()">Scan Barcode Member</div>
                <div class="col-sm-8 col-sm-offset-4 btn btn-warning" onclick="stopCamera()">Berhenti Scan</div>
            </div>
            <div class="form-group col-sm-12">
                {!! Form::label('note', 'Keterangan', array('class' => 'col-sm-4 control-label')) !!}
                <div class="col-sm-8">
                    @if($SubmitButtonText == 'View')
                        {!! Form::text('note', null, array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                    @else
                        {!! Form::text('note', null, array('class' => 'form-control', 'style' => 'height: 50px')) !!}
                    @endif
                </div>
            </div>
            <div class="form-group col-sm-12" style="margin-top: -10px;">
                {!! Form::label('payment', 'Pembayaran', array('class' => 'col-sm-4 control-label', 'style' => 'text-align: left')) !!}
                <div class="col-sm-8">
                    <select class="form-control select2" style="width: 100%;" name="payment">
                        <div>
                            <option value="cash">Cash/Uang</option>
                            <option value="transfer">Transfer</option>
                        </div>
                    </select>
                </div>
            </div>
            <div class="form-group" style="display: none;">
                {!! Form::label('total_item_price', 'Total Harga', array('class' => 'col-sm-4 control-label')) !!}
                <div class="col-sm-8">
                    {!! Form::text('total_item_price', null, array('class' => 'form-control', 'readonly' => 'readonly', 'id' => 'total_item_price')) !!}
                </div>
            </div>
            <div class="form-group" style="display: none;">
                {!! Form::label('total_discount_items_price', 'Total Potongan Harga Per Barang', array('class' => 'col-sm-4 control-label')) !!}
                <div class="col-sm-8">
                    {!! Form::text('total_discount_items_price', null, array('class' => 'form-control', 'readonly' => 'readonly', 'id' => 'total_discount_items_price')) !!}
                </div>
            </div>
            <div class="form-group" style="margin-top: -10px;">
                {!! Form::label('total_discount_price', 'Potongan Harga Akhir', array('class' => 'col-sm-4 control-label')) !!}
                <div class="col-sm-8">
                    <input type="text" name="total_discount_price" class="form-control" id="total_discount_price" onchange="changeTotal()" onkeypress="changeTotal()" required="required" onkeyup="formatNumber('total_discount_price')">
                </div>
            </div>
            <div class="form-group" style="display: none">
                {!! Form::label('voucher', 'Potongan Voucher', array('class' => 'col-sm-4 control-label')) !!}
                <div class="col-sm-3">
                    <input type="text" name="voucher_nominal" class="form-control" id="voucher_nominal">
                </div>
                <div class="col-sm-2">
                    <input type="text" name="voucher" class="form-control" id="voucher"><br>
                    <div onclick="event.preventDefault(); checkVoucher();" class= 'btn btn-success btn-flat btn-block form-control'>Check Voucher</div>
                </div>
                <div class="col-sm-2">
                </div>
                <div class="col-sm-3" id="voucher_result">
                </div>
            </div>
            <div class="form-group" style="margin-top: -10px;">
                {!! Form::label('total_sum_price', 'Total Akhir', array('class' => 'col-sm-4 control-label')) !!}
                <div class="col-sm-8">
                    {!! Form::text('total_sum_price', null, array('class' => 'form-control', 'readonly' => 'readonly', 'id' => 'total_sum_price', 'style' => 'background-color: #9EDF9C; font-weight: bold')) !!}
                </div>
            </div>
            <div class="form-group" style="margin-top: -10px;">
                {!! Form::label('money_paid', 'Bayar', array('class' => 'col-sm-4 control-label')) !!}
                <div class="col-sm-8">
                    <input type="text" name="money_paid" class="form-control" id="money_paid" onchange="changeReturn()" onkeypress="changeReturn()" required="required" onkeyup="formatNumber('money_paid')" style="background-color: #FAB12F">
                </div>
            </div>
            <div class="form-group" style="margin-top: -10px;">
                {!! Form::label('money_returned', 'Kembali', array('class' => 'col-sm-4 control-label')) !!}
                <div class="col-sm-8">
                    {!! Form::text('money_returned', null, array('class' => 'form-control', 'readonly' => 'readonly', 'id' => 'money_returned')) !!}
                </div>
            </div>  
            <div class="form-group" style="margin-top: -10px;">
                <div id="div_total_item"></div>
                Total qty:
            </div>  
        </div>
        {{ Form::hidden('type', 'normal') }}
    </div>

    <div class="row" style="margin-top: -40px">
        <hr>
        @if($SubmitButtonText == 'Edit')
            {!! Form::submit($SubmitButtonText, ['class' => 'btn btn-warning btn-flat btn-block form-control',])  !!}
        @elseif($SubmitButtonText == 'Tambah')
            <div onclick="event.preventDefault(); submitForm(this);" class= 'btn btn-success btn-flat btn-block form-control' style="height: 40px; font-size: 25px; background-color: #C5D3E8">Proses Transaksi</div>
        @elseif($SubmitButtonText == 'View')
        @endif
        <hr>
    </div>

    <div class="row" style="background-color: yellow;">
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
        <table class="table table-bordered table-striped" style="overflow-x: auto; overflow-y: auto">
            <thead>
                <th style="display: none;">Barcode</th>
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
                    <td style="display: none;">
                        {!! Form::text('barcodesretur_s[]', null, array('class' => 'form-control', 'readonly' => 'readonly', 'id' => 'barcode-retur_s'.$i, 'style' => 'height: 70px')) !!}
                    </td>
                    <td width="30%">
                        {!! Form::text('name_tempsretur_s[]', null, array('class' => 'form-control', 'readonly' => 'readonly', 'id' => 'name_temp-retur_s'.$i, 'style' => 'height: 70px')) !!}
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
</div>

{{ csrf_field() }}

{!! Form::close() !!}

@section('js-addon')
    <script type="text/javascript">
        let scanner = new Instascan.Scanner({ video: document.getElementById('preview') });
        var total_item = 1;
        var total_item_retur = 1;
        $(document).ready (function (){
            $('.select2').select2();
            $("#all_barcode").focus();
            $("#row-data-" + total_item).hide();
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

                    $("#row-data-" + items).show();
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
                    // document.getElementById("message").style.display = "block";
                    htmlResult2 = "> " + good.name + " stock: " + good.stock + "<br>";
                    alert(good.name + " stock: " + good.stock);
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
            console.log(name_div + ' ' + index);
            // type = '';
            // if(name_div == 'all_barcode_retur')
            // {
            //     type = 'retur_s';
            // }
            // console.log(type + ' ' + index);
            // good_id = document.getElementById("name-" + type + index).value;
            // name = document.getElementById("name_temp-" + type + index).value;
            // quantity = document.getElementById("quantity-" + type + index).value;
            // price = document.getElementById("price-" + type + index).value;
            // $.ajax({
            //   url: "{!! url($role . '/good/checkDiscount/') !!}/" + good_id + '/' + quantity + '/' + price,
            //   success: function(result){
            //     var discount = result.discount;

            //     document.getElementById("discount-" + type + index).value = discount;

            //     if(discount != '0')
            //     {
            //         document.getElementById("row-data-" + type + index).style.background = 'green';
            //     }

            //     if(result.stock < quantity)
            //     {
            //         document.getElementById("message").style.display = "block";
            //         htmlResult2 = "> " + name + " stock: " + result.stock + "<br>";
            //         $("#empty-item").append(htmlResult2);
            //     }

                editPrice(name_div, index);
            //   },
            //   error: function(){
            //   }
            // });
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

            color = '';
            if(temp1 % 2 == 0)
                color = 'background-color: #FDDBBB !important;';

            htmlResult = '<tr id="row-data' + "-" + type + temp1 + '" style="' + color + '"><td style="display: none;"><input type="text" name="barcodes' + type + '[]" class="form-control" id="barcode-' + type + temp1 + '" readonly="readonly"></td><td><input type="text" name="numbers' + type + '[]" class="form-control" id="no-' + type + temp1 + '" value="' + temp1 + '"></td><td width="30%"><input type="text" class="form-control" readonly="readonly" id="name_temp-' + type + temp1 + '" name="name_temps' + type + '[]" type="text" style="' + color + '"></text><input id="name-' + type + temp1 + '" name="names' + type + '[]" type="text" style="display:none"></td>' + td_rusak + '<td><input type="text" name="quantities' + type + '[]" class="form-control" id="quantity-' + type + temp1+'" onchange="checkDiscount(\'' + name + '\', \'' + temp1 + '\')"></td><td><input id="buy_price-' + type + temp1 + '" name="buy_prices' + type + '[]" type="text" style="display:none"><input class="form-control" readonly="readonly" id="price-' + type +temp1 + '" name="prices' + type + '[]" type="text" style="text-align: right; ' + color + '"></td>';

            @if(\Auth::user()->email == 'admin')
                htmlResult += '<td><input type="text" name="discounts' + type + '[]" class="form-control" id="discount-' + type + temp1+'" onchange="editPrice(\'' + name + '\', \'' + type + temp1 + '\')" style="text-align: right;"></td>';
            @else
                htmlResult += '<td><input type="text" name="discounts' + type + '[]" class="form-control" id="discount-' + type + temp1 +'" readonly="readonly" value="0" style="text-align: right;"></td>';
            @endif

            htmlResult += '<td><input class="form-control" readonly="readonly" id="total_price-' + type + temp1+ '" name="total_prices' + type + '[]" type="text" style="text-align: right; ' + color + '"></td><td><input class="form-control" readonly="readonly" id="sum-' + type + temp1+'" name="sums' + type + '[]" type="text" style="text-align: right; ' + color + '"></td><td><i class="fa fa-times red" id="delete-' + type + temp1+'" onclick="deleteItem(\'-' + type + temp1 + '\')"></i></td></tr>';

            htmlResult += "<script>$('#type-" + type + temp1 + "').select2();<\/script>";
           
            // document.getElementById("div_total_item").value = "Total item: " + total_item;
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

                $("#row-data-" + total_item).hide();
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
            $('#preview').show();
          scanner.addListener('scan', function (content) {
            console.log(content);
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
        }
    </script>
@endsection
