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
        font-size: 17px;
        font-weight: 700;
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

<div class="panel-body" style="margin-top: -30px;">
    <div class="row">
        <div class="form-group col-sm-9" style=" height: 65vh !important; overflow-y: auto;" id="div-good">
            <table class="table table-bordered table-striped">
                <thead>
                    <th style="display: none;">Barcode</th>
                    <th>No</th>
                    <th>Nama</th>
                    <th>Stock Lama</th>
                    <th>Jumlah</th>
                    <th>Stock Baru</th>
                    @if(\Auth::user()->role == 'supervisor')
                        <th>Harga</th>
                        <th>Potongan</th>
                        <th>Total Harga</th>
                        <th>Total Akhir</th>
                    @endif
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
                        <td width="38%">
                            {!! Form::text('name_temps[]', null, array('class' => 'form-control', 'readonly' => 'readonly', 'id' => 'name_temp-'.$i)) !!}
                            {!! Form::text('names[]', null, array('id'=>'name-' . $i, 'style' => 'display:none')) !!}
                        </td>
                        <td width="8%">
                            {!! Form::text('old_stocks[]', null, array('class' => 'form-control', 'readonly' => 'readonly', 'id' => 'old_stock-'.$i)) !!}
                        </td>
                        <td width="5%">
                            <input type="text" name="quantities[]" class="form-control" id="quantity-{{ $i }}" onchange="checkDiscount('{{ $i }}')" onkeypress="checkDiscount('{{ $i }}')">
                        </td>
                        <td width="9%">
                            {!! Form::text('new_stocks[]', null, array('class' => 'form-control', 'readonly' => 'readonly', 'id' => 'new_stock-'.$i)) !!}
                        </td>
                        <td width="9%" @if(\Auth::user()->role != 'supervisor') style="display: none" @endif>
                            {!! Form::text('buy_prices[]', null, array('id'=>'buy_price-' . $i, 'style' => 'display:none')) !!}
                            {!! Form::text('prices[]', null, array('class' => 'form-control', 'readonly' => 'readonly', 'id' => 'price-'.$i)) !!}
                        </td>
                        <td width="9%" @if(\Auth::user()->role != 'supervisor') style="display: none" @endif>
                            @if(\Auth::user()->email == 'admin')
                                <input type="text" name="discounts[]" class="form-control" id="discount-{{ $i }}" onchange="editPrice('all_barcode', '{{ $i }}')" onkeypress="editPrice('all_barcode', '{{ $i }}')">
                            @else
                                {!! Form::text('discounts[]', 0, array('class' => 'form-control', 'readonly' => 'readonly', 'id' => 'discount-'.$i)) !!}
                            @endif
                        </td>
                        <td width="9%" @if(\Auth::user()->role != 'supervisor') style="display: none" @endif>
                            {!! Form::text('total_prices[]', null, array('class' => 'form-control', 'readonly' => 'readonly', 'id' => 'total_price-'.$i)) !!}
                        </td>
                        <td width="9%" @if(\Auth::user()->role != 'supervisor') style="display: none" @endif>
                            {!! Form::text('sums[]', null, array('class' => 'form-control', 'readonly' => 'readonly', 'id' => 'sum-'.$i)) !!}
                        </td>
                        <td width="3%"><i class="fa fa-times red" id="delete-{{ $i }}" onclick="deleteItem('{{ $i }}')"></i></td>
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
                {!! Form::label('all_barcode', 'Barcode', array('class' => 'col-sm-12 control-label', 'style' => ' text-align: left')) !!}
                <div class="col-sm-12">
                    <input type="text" name="all_barcode" class="form-control" id="all_barcode" onchange="searchByBarcode('all_barcode')" onfocus="changeBackColor('all_barcode')" onfocusout="changeBackNorm('all_barcode')">
                </div>
            </div>
            <div class="form-group col-sm-12" style="height: 40px!important; font-size: 20px; margin-top: 6px;">
                {!! Form::label('keyword', 'Keyword', array('class' => 'col-sm-12 control-label', 'style' => ' text-align: left')) !!}
                <div class="col-sm-12">
                    <input type="text" name="search_good" class="form-control" id="search_good" onfocus="changeBackColor('search_good')" onfocusout="changeBackNorm('search_good')">
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
            <div class="form-group col-sm-12" style="margin-top: 6px;">
                {!! Form::label('type', 'Jenis Transaksi', array('class' => 'col-sm-12 control-label', 'style' => 'text-align: left')) !!}
                <div class="col-sm-12">
                    <select class="form-control select2" style="width: 100%;" name="type" id="type">
                        <!-- <div> -->
                            <option value="5215">5215 - Biaya Penyusutan Barang</option>
                            <option value="5220">5220 - Biaya Perlengkapan Kantor</option>
                            <option value="2101">2101 - Utang Dagang</option>
                            <option value="3001">3001 - Modal Pemilik</option>
                            <option value="1131">1131 - Piutang Dagang</option>
                            <option value="5225">5225 - Biaya Dapur & Pasar</option>
                        <!-- </div> -->
                    </select>
                </div>
            </div>
            <div class="form-group col-sm-12" style=" margin-top: 6px;">
                {!! Form::label('note', 'Keterangan', array('class' => 'col-sm-12 control-label', 'style' => ' text-align: left')) !!}
                <div class="col-sm-12">
                    @if($SubmitButtonText == 'View')
                        {!! Form::text('note', null, array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                    @else
                        {!! Form::text('note', null, array('class' => 'form-control', 'style' => 'height: 50px')) !!}
                    @endif
                </div>
            </div>
            <div class="form-group col-sm-12" style="margin-top: 6px;">
                {!! Form::label('distributor_id', 'Distributor', array('class' => 'col-sm-12 control-label')) !!}
                <div class="col-sm-12">
                    @if($SubmitButtonText == 'View')
                        {!! Form::text('distributor', null, array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                    @else
                        <select class="form-control select2" style="width: 100%;" name="distributor_id" id="distributor_id">
                            <!-- <div> -->
                                <option value="null">Silahkan pilih distributor</option>
                                @foreach(getDistributors() as $distributor)
                                <option value="{{ $distributor->id }}">
                                    {{ $distributor->name }}</option>
                                @endforeach
                            <!-- </div> -->
                        </select>
                    @endif
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
        </div>
    </div>

    <div class="row" style="margin-top: -40px">
        <div  class="col-sm-6">
            <div class="col-sm-4">
                <div class="form-group">
                    {!! Form::label('total_discount_price', 'Potongan Akhir', array('class' => 'col-sm-12 control-label', 'style' => 'font-size: 18px; text-align: left')) !!}
                    <div class="col-sm-12" style="margin-top: 2px">
                        <input type="text" name="total_discount_price" class="form-control" id="total_discount_price" onchange="changeTotal()" onkeypress="changeTotal()" required="required" onkeyup="formatNumber('total_discount_price'); changeTotal()" style="height: 50px; font-size: 25px;">
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    {!! Form::label('total_sum_price', 'Total Akhir', array('class' => 'col-sm-12 control-label', 'style' => 'font-size: 20px; text-align: left')) !!}
                    <div class="col-sm-12">
                        {!! Form::text('total_sum_price', null, array('class' => 'form-control', 'readonly' => 'readonly', 'id' => 'total_sum_price', 'style' => 'height: 50px; background-color: #BF092F !important; font-weight: bold; font-size: 25px; color: white')) !!}
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    {!! Form::label('money_paid', 'Bayar', array('class' => 'col-sm-12 control-label', 'style' => 'font-size: 20px; text-align: left')) !!}
                    <div class="col-sm-12">
                        <input type="text" name="money_paid" class="form-control" id="money_paid" onchange="changeReturn()" onkeypress="changeReturn()" required="required" onkeyup="formatNumber('money_paid'); changeReturn()" style="height: 50px; background-color: #FFE100; font-size: 25px;">
                    </div>
                </div>
            </div>
        </div>
       <!--  <div class="col-sm-2">
            <div class="form-group">
                {!! Form::label('money_returned', 'Kembali', array('class' => 'col-sm-12 control-label', 'style' => 'font-size: 20px; text-align: left')) !!}
                <div class="col-sm-12">
                    {!! Form::text('money_returned', null, array('class' => 'form-control', 'readonly' => 'readonly', 'id' => 'money_returned', 'style' => 'height: 50px; font-weight: bold; font-size: 25px')) !!}
                </div>
            </div>  
        </div> -->
        @if($SubmitButtonText == 'Edit')
            {!! Form::submit($SubmitButtonText, ['class' => 'btn btn-warning btn-flat btn-block form-control',])  !!}
        @elseif($SubmitButtonText == 'Tambah')
            <div onclick="event.preventDefault(); submitForm(this);" class='btn btn-success col-sm-6' style="height: 80px; font-size: 30px; background-color: #08CB00" id="div_money_returned">Proses Transaksi
            </div>
            {!! Form::hidden('money_returned', null, array('id' => 'money_returned')) !!}
        @elseif($SubmitButtonText == 'View')
        @endif
        <hr>
    </div>
</div>

{{ csrf_field() }}

{!! Form::close() !!}

@section('js-addon')
    <script type="text/javascript">
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
                        if(document.getElementById("barcode-" + type + i).value != '' && document.getElementById("barcode-" + type + i).value == good.getPcsSellingPrice.id && document.getElementById("price-" + type + i).value == good.getPcsSellingPrice.buy_price)
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
                    document.getElementById("name-" + total_item).value = good.id;
                    document.getElementById("name_temp-" + total_item).value = good.name + " " + good.getPcsSellingPrice.name;
                    document.getElementById("barcode-" + total_item).value = good.getPcsSellingPrice.id;
                    document.getElementById("quantity-" + total_item).value = 1;
                    document.getElementById("old_stock-" + total_item).value = good.stock;
                    document.getElementById("new_stock-" + total_item).value = good.stock - 1;
                    document.getElementById("price-" + total_item).value = good.getPcsSellingPrice.buy_price;
                    document.getElementById("discount-" + total_item).value = '0';
                    document.getElementById("buy_price-" + total_item).value = good.getPcsSellingPrice.buy_price;
                    document.getElementById("total_price-" + total_item).value = good.getPcsSellingPrice.buy_price;

                    $("#row-data-" + items).show();
                    editPrice(name, items);

                    document.getElementById(name).value = '';
                    $("#" + name).focus();

                }
                document.getElementById(name).value = '';
                $("#" + name).focus();

                let scrollableDiv = document.getElementById('div-good');
                scrollableDiv.scrollTop = scrollableDiv.scrollHeight;
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
                console.log(result);
                var good = result.good;
                if(good != null)
                {
                    fillItem(name, result.good)
                }
              }, 
              error: function(){
              }
            });
        }

        function checkDiscount(index)
        {
            editPrice('all_barcode', index);
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

            discount = document.getElementById("total_discount_price").value;
            discount = discount.replace(/,/g,'');
            total_sum_price = parseInt(total_sum_price) - parseInt(discount);

            document.getElementById("total_item_price").value = total_item_price;
            document.getElementById("total_discount_items_price").value = total_discount_items;
            document.getElementById("total_sum_price").value = total_sum_price;
            document.getElementById("money_paid").value = total_sum_price;
            document.getElementById("money_returned").value = 0;

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
            money_returned = money_returned.toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,');

            document.getElementById("div_money_returned").innerHTML = "PROSES => Kembali: " + money_returned;
            document.getElementById("money_returned").value = money_returned;
        }

        function submitForm(btn)
        {
            if($('#money_paid').val() != '' && $('#total_discount_price').val() != '')
            {
                if(parseInt(unFormatNumber($('#money_paid').val())) < parseInt(unFormatNumber($('#total_sum_price').val())) && ($('#member_id').val() == '1'))
                {
                    alert('Jumlah pembayaran kurang dari total belanja. Silahkan pilih member dan centang tombol hutang');
                }
                else if($('#type').val() == '2101' && $('#distributor_id').val() == 'null')
                {
                    alert('Silahkan pilih distributor');
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
            console.log(index);
            console.log(name);
            console.log(document.getElementById("old_stock-" + index).value);
            document.getElementById("new_stock-" + index).value = parseInt(document.getElementById("old_stock-" + index).value) - parseInt(document.getElementById("quantity-" + index).value);

            document.getElementById("total_price-" + index).value = (unFormatNumber(document.getElementById("price-" + index).value) * unFormatNumber(document.getElementById("quantity-" + index).value));

            document.getElementById("sum-" + index).value = document.getElementById("total_price-" + index).value  - unFormatNumber(document.getElementById("discount-" + index).value);

            formatNumber("total_price-" + index);
            formatNumber("sum-" + index);
            formatNumber("discount-" + index);

            changeTotal();
            temp1=parseInt(index)+1
            var type = '';
            var items = total_item;

            color = '';
            if(temp1 % 2 == 0)
                color = 'background-color: #E7F2EF !important;';

            htmlResult = '<tr id="row-data' + "-" + type + temp1 + '" style="' + color + '"><td style="display: none;"><input type="text" name="barcodes' + type + '[]" class="form-control" id="barcode-' + type + temp1 + '" readonly="readonly"></td><td><input type="text" name="numbers' + type + '[]" class="form-control" id="no-' + type + temp1 + '" value="' + temp1 + '"></td><td width="25%"><input type="text" class="form-control" readonly="readonly" id="name_temp-' + type + temp1 + '" name="name_temps' + type + '[]" type="text" style="' + color + '"><input id="name-' + type + temp1 + '" name="names' + type + '[]" type="text" style="display:none"></td><td><input class="form-control" readonly="readonly" id="old_stock-' + temp1+'" name="old_stocks[]" type="text"></td><td><input type="text" name="quantities' + type + '[]" class="form-control" id="quantity-' + type + temp1+'" onchange="checkDiscount(\'' + temp1 + '\')" style="background-color: yelow !important"></td><td><input class="form-control" readonly="readonly" id="new_stock-' + temp1+'" name="new_stocks[]" type="text"></td><td><input id="buy_price-' + type + temp1 + '" name="buy_prices' + type + '[]" type="text" style="display:none"><input class="form-control" readonly="readonly" id="price-' + type +temp1 + '" name="prices' + type + '[]" type="text" style="text-align: right; ' + color + '"></td>';

            @if(\Auth::user()->email == 'admin')
                htmlResult += '<td><input type="text" name="discounts' + type + '[]" class="form-control" id="discount-' + type + temp1+'" onchange="editPrice(\'' + name + '\', \'' + type + temp1 + '\')" onkeypress="editPrice(\'' + name + '\', \'' + type + temp1 + '\')" style="text-align: right;"></td>';
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
                    $("#table-transaction-retur").append(htmlResult);
                }
                else
                {
                    total_item += 1;
                    $("#table-transaction").append(htmlResult);
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
                    if(r[i].stock == 0) 
                    {
                        color = '#D1D3D4';
                    }
                    else if(r[i].stock < 0) 
                    {
                        color = '#D9C4B0';
                    }
                    else
                    {
                        color = '#9EBC8A';
                    }
                    if(r[i].status == null)
                    {
                        r[i].status = '';
                    }
                    htmlResult += "<textarea class='col-sm-12 modal-div' style='display:inline-block; color:black; cursor: pointer; min-height:40px; max-height:80px; background-color:" + color + "; padding: 5px;' onclick='searchByKeyword(\"" + name + "\",\"" + r[i].good_unit_id + "\")'>" + r[i].status + ' ' + r[i].name + " " + r[i].unit + "</textarea>";
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

        function changeBackColor(id)
        {
            $("#" + id).css( "border-color", "#1A2A4F" );
            $("#" + id).css( "background-color", "#C2E2FA" );
            $("#" + id).css( "border-width", "4px" );
        }

        function changeBackNorm(id)
        {
            $("#" + id).css( "background-color", "#EEEEEE" );
            $("#" + id).css( "border-width", "1px" );
        }
    </script>
@endsection
