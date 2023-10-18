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
</style>

<div class="panel-body">
    <div class="alert alert-danger alert-dismissible" id="message" style="display:none">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            <h4><i class="icon fa fa-warning"></i> Barang kosong</h4>
            <div id="empty-item"></div>
    </div>
    <div class="row">
        <div class="form-group col-sm-5" style="height: 40px!important; font-size: 20px;">
            {!! Form::label('all_barcode', 'Cari barcode', array('class' => 'col-sm-4 control-label')) !!}
            <div class="col-sm-8">
                <input type="text" name="all_barcode" class="form-control" id="all_barcode" onchange="searchByBarcode('all_barcode')">
            </div>
        </div>
        <div class="form-group col-sm-7" style="height: 40px!important; font-size: 20px;">
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
        <div class="col-sm-12">
            <div class="form-group col-sm-5">
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
            <div class="form-group col-sm-7">
                <div class="col-sm-1 btn btn-warning" onclick="ajaxButton('beras')">Beras</div>
                <div class="col-sm-1 btn btn-warning" onclick="ajaxButton('ember')">Ember</div>
                <div class="col-sm-1 btn btn-warning" onclick="ajaxButton('gelas')">Gelas</div>
                <div class="col-sm-1 btn btn-warning" onclick="ajaxButton('gula')">Gula</div>
                <div class="col-sm-1 btn btn-warning" onclick="ajaxButton('kompor')">Kompor</div>
                <div class="col-sm-1 btn btn-warning" onclick="ajaxButton('piring')">Piring</div>
                <div class="col-sm-1 btn btn-warning" onclick="ajaxButton('sprei')">Sprei</div>
                <div class="col-sm-1 btn btn-warning" onclick="ajaxButton('tikar')">Tikar</div>
            </div>
        </div>
        <div class="form-group col-sm-5">
            {!! Form::label('note', 'Keterangan', array('class' => 'col-sm-4 control-label')) !!}
            <div class="col-sm-8">
                @if($SubmitButtonText == 'View')
                    {!! Form::text('note', null, array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                @else
                    {!! Form::text('note', null, array('class' => 'form-control', 'style' => 'height: 70px')) !!}
                @endif
            </div>
        </div>
        <div class="form-group col-sm-7">
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
        <table class="table table-bordered table-striped" style="overflow-x: auto;">
            <thead>
                <th>Barcode</th>
                <th>Nama</th>
                <th>Jumlah</th>
                <th>Harga</th>
                <th>Potongan</th>
                <th>Total Harga</th>
                <th>Total Akhir</th>
                <th>Hapus</th>
            </thead>
            <tbody id="table-transaction">
                <?php $i = 1; ?>
                <tr id="row-data-{{ $i }}">
                    <td>
                        {!! Form::textarea('barcodes[]', null, array('class' => 'form-control', 'readonly' => 'readonly', 'id' => 'barcode-'.$i, 'style' => 'height: 70px')) !!}
                    </td>
                    <td width="30%">
                        {!! Form::textarea('name_temps[]', null, array('class' => 'form-control', 'readonly' => 'readonly', 'id' => 'name_temp-'.$i, 'style' => 'height: 70px')) !!}
                        {!! Form::text('names[]', null, array('id'=>'name-' . $i, 'style' => 'display:none')) !!}
                    </td>
                    <td>
                        <input type="text" name="quantities[]" class="form-control" id="quantity-{{ $i }}" onchange="checkDiscount('all_barcode', '{{ $i }}')">
                    </td>
                    <td>
                        {!! Form::text('buy_prices[]', null, array('id'=>'buy_price-' . $i, 'style' => 'display:none')) !!}
                        {!! Form::text('prices[]', null, array('class' => 'form-control', 'readonly' => 'readonly', 'id' => 'price-'.$i)) !!}
                    </td>
                    <td>
                        @if(\Auth::user()->email == 'admin')
                            <input type="text" name="discounts[]" class="form-control" id="discount-{{ $i }}" onchange="editPrice('all_barcode', '{{ $i }}')">
                        @else
                            {!! Form::text('discounts[]', 0, array('class' => 'form-control', 'readonly' => 'readonly', 'id' => 'discount-'.$i)) !!}
                        @endif
                    </td>
                    <td>
                        {!! Form::text('total_prices[]', null, array('class' => 'form-control', 'readonly' => 'readonly', 'id' => 'total_price-'.$i)) !!}
                    </td>
                    <td>
                        {!! Form::text('sums[]', null, array('class' => 'form-control', 'readonly' => 'readonly', 'id' => 'sum-'.$i)) !!}
                    </td>
                    <td><i class="fa fa-times red" id="delete-{{ $i }}" onclick="deleteItem('-{{ $i }}')"></i></td>
                </tr>
            </tbody>
        </table>
        <div class="form-group">
            {!! Form::label('total_item_price', 'Total Harga', array('class' => 'col-sm-3 control-label')) !!}
            <div class="col-sm-3">
                {!! Form::text('total_item_price', null, array('class' => 'form-control', 'readonly' => 'readonly', 'id' => 'total_item_price')) !!}
            </div>
        </div>
        <div class="form-group">
            {!! Form::label('total_discount_items_price', 'Total Potongan Harga Per Barang', array('class' => 'col-sm-3 control-label')) !!}
            <div class="col-sm-3">
                {!! Form::text('total_discount_items_price', null, array('class' => 'form-control', 'readonly' => 'readonly', 'id' => 'total_discount_items_price')) !!}
            </div>
        </div>
        <div class="form-group">
            {!! Form::label('total_discount_price', 'Potongan Harga Akhir', array('class' => 'col-sm-3 control-label')) !!}
            <div class="col-sm-3">
                <input type="text" name="total_discount_price" class="form-control" id="total_discount_price" onchange="changeTotal()" onkeypress="changeTotal()" required="required" onkeyup="formatNumber('total_discount_price')">
            </div>
        </div>
        <div class="form-group">
            {!! Form::label('total_sum_price', 'Total Akhir', array('class' => 'col-sm-3 control-label', 'style' => "font-size: 40px; height: 40px;")) !!}
            <div class="col-sm-3">
                {!! Form::text('total_sum_price', null, array('class' => 'form-control', 'readonly' => 'readonly', 'id' => 'total_sum_price', 'style' => "font-size: 40px; height: 40px;")) !!}
            </div>
        </div>
        <div class="form-group">
            {!! Form::label('money_paid', 'Bayar', array('class' => 'col-sm-3 control-label', 'style' => "font-size: 40px; height: 40px;")) !!}
            <div class="col-sm-3">
                <input type="text" name="money_paid" class="form-control" id="money_paid" onchange="changeReturn()" onkeypress="changeReturn()" required="required" onkeyup="formatNumber('money_paid')" style="font-size: 40px; height: 40px;">
            </div>
        </div>
        <div class="form-group">
            {!! Form::label('money_returned', 'Kembali', array('class' => 'col-sm-3 control-label')) !!}
            <div class="col-sm-3">
                {!! Form::text('money_returned', null, array('class' => 'form-control', 'readonly' => 'readonly', 'id' => 'money_returned')) !!}
            </div>
        </div>
        {{ Form::hidden('type', 'normal') }}
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
</div>

{{ csrf_field() }}

<hr>
@if($SubmitButtonText == 'Edit')
    {!! Form::submit($SubmitButtonText, ['class' => 'btn btn-warning btn-flat btn-block form-control',])  !!}
@elseif($SubmitButtonText == 'Tambah')
    <div onclick="event.preventDefault(); submitForm(this);" class= 'btn btn-success btn-flat btn-block form-control' style="height: 80px; font-size: 40px;">Proses Transaksi</div>
@elseif($SubmitButtonText == 'View')
@endif

{!! Form::close() !!}

@section('js-addon')
    <script type="text/javascript">
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
            document.getElementById("total_sum_price").value = total_sum_price;

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

            @if(\Auth::user()->email == 'admin')
                htmlResult = '<tr id="row-data' + "-" + type + temp1 + '"><td><input type="text" name="barcodes' + type + '[]" class="form-control" id="barcode-' + type + temp1 + '" readonly="readonly"></td><td width="30%"><textarea  class="form-control" readonly="readonly" id="name_temp-' + type + temp1 + '" name="name_temps' + type + '[]" type="text" style="height: 70px"></textarea><input id="name-' + type + temp1 + '" name="names' + type + '[]" type="text" style="display:none"></td>' + td_rusak + '<td><input type="text" name="quantities' + type + '[]" class="form-control" id="quantity-' + type + temp1+'" onchange="checkDiscount(\'' + name + '\', \'' + temp1 + '\')"></td><td><input id="buy_price-' + type + temp1 + '" name="buy_prices' + type + '[]" type="text" style="display:none"><input class="form-control" readonly="readonly" id="price-' + type +temp1 + '" name="prices' + type + '[]" type="text"></td><td><input type="text" name="discounts' + type + '[]" class="form-control" id="discount-' + type + temp1+'" onchange="editPrice(\'' + name + '\', \'' + type + temp1 + '\')"></td><td><input class="form-control" readonly="readonly" id="total_price-' + type + temp1+ '" name="total_prices' + type + '[]" type="text"></td><td><input class="form-control" readonly="readonly" id="sum-' + type + temp1+'" name="sums' + type + '[]" type="text"></td><td><i class="fa fa-times red" id="delete-' + type + temp1+'" onclick="deleteItem(\'-' + type + temp1 + '\')"></i></td></tr>';
            @else
                htmlResult = '<tr id="row-data' + "-" + type + temp1 + '"><td><input type="text" name="barcodes' + type + '[]" class="form-control" id="barcode-' + type + temp1+ '" readonly="readonly"></td><td width="30%"><textarea  class="form-control" readonly="readonly" id="name_temp-' + type + temp1 + '" name="name_temps' + type + '[]" type="text" style="height: 70px"></textarea><input id="name-' + type + temp1 + '" name="names' + type + '[]" type="text" style="display:none"></td>' + td_rusak + '<td><input type="text" name="quantities' + type + '[]" class="form-control" id="quantity-' + type + temp1 +'" onchange="checkDiscount(\'' + name + '\', \'' + temp1 + '\')"></td><td><input id="buy_price-' + type + temp1 + '" name="buy_prices' + type + '[]" type="text" style="display:none"><input class="form-control" readonly="readonly" id="price-' + type +temp1 + '" name="prices' + type + '[]" type="text"></td><td><input type="text" name="discounts' + type + '[]" class="form-control" id="discount-' + type + temp1 +'" readonly="readonly" value="0"></td><td><input class="form-control" readonly="readonly" id="total_price-' + type + temp1 + '" name="total_prices' + type + '[]" type="text"></td><td><input class="form-control" readonly="readonly" id="sum-' + type + temp1 +'" name="sums' + type + '[]" type="text"></td><td><i class="fa fa-times red" id="delete-' + type + temp1+ '" onclick="deleteItem(\'-' + type + temp1 + '\')"></i></td></tr>';
            @endif
            htmlResult += "<script>$('#type-" + type + temp1 + "').select2();<\/script>";
           
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
    </script>
@endsection
