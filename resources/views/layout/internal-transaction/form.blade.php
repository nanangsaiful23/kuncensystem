<style type="text/css">
  .select2-container--default .select2-selection--multiple .select2-selection__choice
  {
    background-color: rgb(60, 141, 188) !important;
  }

  .modal-body {
    overflow-y: auto;
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
                <input type="text" name="all_barcode" class="form-control" id="all_barcode" onchange="searchByBarcode()">
            </div>
        </div>
        <div class="form-group col-sm-7" style="height: 40px!important; font-size: 20px;">
            {!! Form::label('keyword', 'Cari keyword', array('class' => 'col-sm-3 control-label')) !!}
            <div class="col-sm-8">
                <input type="text" name="search_good" class="form-control" id="search_good">
            </div>
             <div class="modal modal-primary fade" id="modal-search">
              <div class="modal-dialog">
                <div class="modal-content">
                  <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                      <span aria-hidden="true">&times;</span></button>
                      <h4 class="modal-title">Hasil Keyword (klik nama barang)</h4>
                  </div>
                  <div class="modal-body">
                    <div id="result-good"></div>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-outline pull-left" data-dismiss="modal">Close</button>
                  </div>
                </div>
              </div>
            </div>
        </div>
        <div class="form-group col-sm-5">
            {!! Form::label('type', 'Jenis Transaksi', array('class' => 'col-sm-4 control-label', 'style' => 'text-align: left')) !!}
            <div class="col-sm-8">
                <select class="form-control select2" style="width: 100%;" name="type" id="type">
                    <div>
                        <option value="0000">0000 - Sistem Error</option>
                        <option value="5215">5215 - Biaya Penyusutan Barang</option>
                        <option value="5220">5220 - Biaya Perlengkapan Kantor</option>
                        <option value="2101">2101 - Utang Dagang</option>
                    </div>
                </select>
            </div>
        </div>
        <div class="form-group col-sm-7">
            {!! Form::label('note', 'Keterangan', array('class' => 'col-sm-3 control-label')) !!}
            <div class="col-sm-8">
                @if($SubmitButtonText == 'View')
                    {!! Form::text('note', null, array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                @else
                    {!! Form::text('note', null, array('class' => 'form-control', 'style' => 'height: 70px')) !!}
                @endif
            </div>
        </div>
        <div class="form-group col-sm-5">
            {!! Form::label('distributor_id', 'Distributor', array('class' => 'col-sm-4 control-label')) !!}
            <div class="col-sm-8">
                @if($SubmitButtonText == 'View')
                    {!! Form::text('distributor', null, array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                @else
                    <select class="form-control select2" style="width: 100%;" name="distributor_id" id="distributor_id">
                        <div>
                            <option value="null">Silahkan pilih distributor</option>
                            @foreach(getDistributors() as $distributor)
                            <option value="{{ $distributor->id }}">
                                {{ $distributor->name }}</option>
                            @endforeach
                        </div>
                    </select>
                @endif
            </div>
        </div>
        <table class="table table-bordered table-striped" style="overflow-x: auto;">
            <thead>
                <th>Barcode</th>
                <th>Nama</th>
                <th>Stock Lama</th>
                <th>Jumlah</th>
                <th>Stock Baru</th>
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
                        {!! Form::text('old_stocks[]', null, array('class' => 'form-control', 'readonly' => 'readonly', 'id' => 'old_stock-'.$i)) !!}
                    </td>
                    <td>
                        <input type="text" name="quantities[]" class="form-control" id="quantity-{{ $i }}" onchange="checkDiscount('{{ $i }}')" onkeypress="checkDiscount('{{ $i }}')">
                    </td>
                    <td>
                        {!! Form::text('new_stocks[]', null, array('class' => 'form-control', 'readonly' => 'readonly', 'id' => 'new_stock-'.$i)) !!}
                    </td>
                    <td>
                        {!! Form::text('buy_prices[]', null, array('id'=>'buy_price-' . $i, 'style' => 'display:none')) !!}
                        {!! Form::text('prices[]', null, array('class' => 'form-control', 'readonly' => 'readonly', 'id' => 'price-'.$i)) !!}
                    </td>
                    <td>
                        @if(\Auth::user()->email == 'admin')
                            <input type="text" name="discounts[]" class="form-control" id="discount-{{ $i }}" onchange="editPrice('{{ $i }}')" onkeypress="editPrice('{{ $i }}')">
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
                    <td><i class="fa fa-times red" id="delete-{{ $i }}" onclick="deleteItem('{{ $i }}')"></i></td>
                </tr>
            </tbody>
        </table>
        <div class="form-group">
            {!! Form::label('total_item_price', 'Total Harga', array('class' => 'col-sm-3 control-label')) !!}
            <div class="col-sm-3">
                {!! Form::text('total_item_price', null, array('class' => 'form-control', 'readonly' => 'readonly', 'id' => 'total_item_price')) !!}
            </div>
        </div>
        <!-- <div class="form-group">
            {!! Form::label('total_promo_price', 'Total Promo', array('class' => 'col-sm-3 control-label')) !!}
            <div class="col-sm-3">
                {!! Form::text('total_promo_price', null, array('class' => 'form-control', 'readonly' => 'readonly', 'id' => 'total_promo_price')) !!}
            </div>
        </div> -->
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
                <input type="text" name="money_paid" class="form-control" id="money_paid" readonly="readonly" required="required" style="font-size: 40px; height: 40px;">
            </div>
        </div>
        <div class="form-group">
            {!! Form::label('money_returned', 'Kembali', array('class' => 'col-sm-3 control-label')) !!}
            <div class="col-sm-3">
                {!! Form::text('money_returned', null, array('class' => 'form-control', 'readonly' => 'readonly', 'id' => 'money_returned')) !!}
            </div>
        </div>
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
        $(document).ready (function (){
            $('.select2').select2();
            $("#all_barcode").focus();
            document.getElementById("total_discount_price").value = 0;

            $("#search_good").keyup( function(e){
              if(e.keyCode == 13)
              {
                ajaxFunction();
              }
            });
        });

        $('#modal-search').on('shown.bs.modal', function() {
          $('#search_good').focus();
        })

        function fillItem(good)
        {
            console.log(good);
            var bool = false;

            if(good.length != 0)
            {
                for (var i = 1; i <= total_item; i++)
                {
                    if(document.getElementById("barcode-" + i))
                    {
                        if(document.getElementById("barcode-" + i).value != '' && document.getElementById("barcode-" + i).value == good.getPcsSellingPrice.id && document.getElementById("price-" + i).value == good.getPcsSellingPrice.selling_price)
                        {
                            temp_total = document.getElementById("quantity-" + i).value;
                            temp_total = parseInt(temp_total) + 1;
                            document.getElementById("quantity-" + i).value = temp_total;
                            bool = true;

                            editPrice(i);
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

                    editPrice(total_item);

                    document.getElementById("all_barcode").value = '';
                    $("#all_barcode").focus();

                }
                document.getElementById("all_barcode").value = '';
                $("#all_barcode").focus();
            }
            else
            {
                alert('Barang tidak ditemukan');
                document.getElementById("barcode-" + index).value = '';
                document.getElementById("name-" + index).focus();
            }
        }

        function enterPressed(barcode)
        {
            if(event.key === 'Enter') {
                searchByBarcodeKeyword(barcode);    
            }
        }

        function searchByKeyword(good_unit_id)
        {
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
                fillItem(result.good);
                $('#modal-search').modal('hide');
                $('#search_good').val('');
                $('#result-good').val('');
            },
              error: function(){
              }
            });
        }

        function searchByBarcode()
        {
            $.ajax({
              url: "{!! url($role . '/good/searchByBarcode/') !!}/" + $("#all_barcode").val(),
              success: function(result){
                var good = result.good;
                fillItem(result.good)},
              error: function(){
              }
            });
        }

        function checkDiscount(index)
        {
            editPrice(index);
        }

        function changeFocus(index)
        {
            $("#barcode-" + index).focus();
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

            document.getElementById("money_returned").value = money_returned;
            formatNumber("money_returned");
        }

        function submitForm(btn)
        {
            // console.log($('#distributor_id').val());
            if($('#money_paid').val() != '' && $('#total_discount_price').val() != '')
            {
                if($('#type').val() == '2101' && $('#distributor_id').val() == 'null')
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
            $("#row-data-" + index).remove();
            changeTotal();
        }

        function editPrice(index)
        {
            document.getElementById("new_stock-" + index).value = parseInt(document.getElementById("old_stock-" + index).value) - parseInt(document.getElementById("quantity-" + index).value);

            document.getElementById("total_price-" + index).value = (unFormatNumber(document.getElementById("price-" + index).value) * unFormatNumber(document.getElementById("quantity-" + index).value));

            document.getElementById("sum-" + index).value = document.getElementById("total_price-" + index).value  - unFormatNumber(document.getElementById("discount-" + index).value);

            formatNumber("total_price-" + index);
            formatNumber("sum-" + index);
            formatNumber("discount-" + index);

            changeTotal();
            temp1=parseInt(index)+1

            @if(\Auth::user()->email == 'admin')
                htmlResult = '<tr id="row-data-' + temp1+ '"><td><input type="text" name="barcodes[]" class="form-control" id="barcode-' + temp1+ '" onchange="searchName(' + temp1+ ')"></td><td width="30%"><textarea  class="form-control" readonly="readonly" id="name_temp-' + temp1+ '" name="name_temps[]" type="text" style="height: 70px"></textarea><input id="name-' + temp1 + '" name="names[]" type="text" style="display:none"></td><td><input class="form-control" readonly="readonly" id="old_stock-' + temp1+'" name="old_stocks[]" type="text"></td><td><input type="text" name="quantities[]" class="form-control" id="quantity-' + temp1+'" onkeypress="editPrice(' + temp1+')" onchange="editPrice(' + temp1+ ')"></td><td><input class="form-control" readonly="readonly" id="new_stock-' + temp1+'" name="new_stocks[]" type="text"></td><td><input id="buy_price-' + temp1 + '" name="buy_prices[]" type="text" style="display:none"><input class="form-control" readonly="readonly" id="price-' +temp1+ '" name="prices[]" type="text"></td><td><input type="text" name="discounts[]" class="form-control" id="discount-' + temp1+'" onkeypress="editPrice(' + temp1+')" onchange="editPrice(' + temp1+ ')"></td><td><input class="form-control" readonly="readonly" id="total_price-' + temp1+ '" name="total_prices[]" type="text"></td><td><input class="form-control" readonly="readonly" id="sum-' + temp1+'" name="sums[]" type="text"></td><td><i class="fa fa-times red" id="delete-' + temp1+'" onclick="deleteItem('
                + temp1+ ')"></i></td></tr>';
            @else
                htmlResult = '<tr id="row-data-' + temp1+ '"><td><input type="text" name="barcodes[]" class="form-control" id="barcode-' + temp1+ '" onchange="searchName(' + temp1+ ')"></td><td width="30%"><textarea  class="form-control" readonly="readonly" id="name_temp-' + temp1+ '" name="name_temps[]" type="text" style="height: 70px"></textarea><input id="name-' + temp1 + '" name="names[]" type="text" style="display:none"></td><td><input class="form-control" readonly="readonly" id="old_stock-' + temp1+'" name="old_stocks[]" type="text"></td><td><input type="text" name="quantities[]" class="form-control" id="quantity-' + temp1+'" onkeypress="editPrice(' + temp1+')" onchange="editPrice(' + temp1+ ')"></td><td><input class="form-control" readonly="readonly" id="new_stock-' + temp1+'" name="new_stocks[]" type="text"></td><td><input id="buy_price-' + temp1 + '" name="buy_prices[]" type="text" style="display:none"><input class="form-control" readonly="readonly" id="price-' +temp1+ '" name="prices[]" type="text"></td><td><input type="text" name="discounts[]" class="form-control" id="discount-' + temp1+'" readonly="readonly" value="0"></td><td><input class="form-control" readonly="readonly" id="total_price-' + temp1+ '" name="total_prices[]" type="text"></td><td><input class="form-control" readonly="readonly" id="sum-' + temp1+'" name="sums[]" type="text"></td><td><i class="fa fa-times red" id="delete-' + temp1+'" onclick="deleteItem('
                + temp1+ ')"></i></td></tr>';
            @endif
            htmlResult += "<script>$('#type-" + temp1 + "').select2();<\/script>";
           
            if(index == total_item)
            {
                total_item += 1;
                $("#table-transaction").prepend(htmlResult);
                // $("#table-transaction").append(s);
            }
            document.getElementById("all_barcode").value = '';
            $("#all_barcode").focus();

        }

        function ajaxFunction()
        {
            $('#modal-search').modal('show');
          $.ajax({
            url: "{!! url($role . '/good/searchByKeywordGoodUnit/') !!}/" + $("#search_good").val(),
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
                htmlResult += "<a class='col-sm-12 modal-div' onclick='searchByKeyword(\"" + r[i].good_unit_id + "\")' style='color:black; cursor: pointer; height:40px; background-color:" + color + "; padding: 5px;'>" + r[i].name + " " + r[i].unit + "</a>";
              }
              $("#result-good").html(htmlResult);
              $('.modal-body').css('height',$( window ).height()*0.5);
            },
            error: function(){
                console.log('error');
            }
          });
        }
    </script>
@endsection
