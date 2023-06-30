<style type="text/css">
    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background-color: rgb(60, 141, 188) !important;
    }
</style>

<div class="panel-body">
    <?php $goods = getGoods() ?>
    <?php $distributors = getDistributors() ?>
    <div class="row">
        <div class="col-sm-5">
            <div class="form-group col-sm-12">
                {!! Form::label('distributor_id', 'Distributor', array('class' => 'col-sm-4 control-label')) !!}
                <div class="col-sm-8">
                    @if($SubmitButtonText == 'View')
                        {!! Form::text('distributor', null, array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                    @else
                        <input type="text" name="distributor_name" class="form-control" id="distributor_name">
                        <select class="form-control select2" style="width: 100%;" name="distributor_id" id="all_distributor">
                            <div>
                                <option value="null">Silahkan pilih distributor</option>
                                @foreach($distributors as $distributor)
                                <option value="{{ $distributor->id }}">
                                    {{ $distributor->name }}</option>
                                @endforeach
                            </div>
                        </select>
                    @endif
                </div>
            </div>
            <div class="form-group col-sm-12">
                {!! Form::label('loading_date', 'Tanggal Pembelian', array('class' => 'col-sm-4 control-label')) !!}
                <div class="col-sm-8">
                    <div class="input-group date">
                        <input type="text" class="form-control pull-right" required="required" name="loading_date" id="loading_date">
                    </div>
                </div>
            </div>
        </div>
       <div class="col-sm-7">
            {!! Form::label('note', 'Catatan', array('class' => 'col-sm-1 left control-label')) !!}
            <div class="col-sm-12">
                <input type="text" name="note" class="form-control" id="note">
            </div>
            {!! Form::label('checker', 'PIC Check Barang', array('class' => 'col-sm-12 control-label', 'style' => 'text-align: left')) !!}
            <div class="col-sm-12">
                <input type="text" name="checker" class="form-control" id="checker">
            </div>
            {!! Form::label('payment', 'Jenis Pembayaran', array('class' => 'col-sm-12 control-label', 'style' => 'text-align: left')) !!}
            <div class="col-sm-12">
                <select class="form-control select2" style="width: 100%;" name="payment">
                    <div>
                        @foreach(getAccounts() as $account)
                            <option value="{{ $account->code }}">{{ $account->code . ' - ' . $account->name }}</option>
                        @endforeach
                    </div>
                </select>
            </div>
       </div>
    </div>

    <h4>Barang Lama</h4>
    <div class="row">
        <div class="form-group col-sm-5">
            {!! Form::label('all_barcode', 'Cari barcode', array('class' => 'col-sm-4 control-label')) !!}
            <div class="col-sm-8">
                <input type="text" name="all_barcode" class="form-control" id="all_barcode"
                    onchange="searchByBarcode()">
            </div>
        </div>
        <div class="form-group col-sm-7">
            {!! Form::label('all_name', 'Cari nama barang', array('class' => 'col-sm-3 control-label')) !!}
            <div class="col-sm-9">
                <select class="form-control select2" style="width: 100%;" name="items" id="all_name"
                    onchange="searchItemByName()">
                    <div>
                        <option value="null">Silahkan pilih barang</option>
                        @foreach($goods as $good)
                        <option value="{{ $good->id }}">{{ $good->name . ' ' }}</option>
                        @endforeach
                    </div>
                </select>
            </div>
        </div>
        <div class="form-group col-sm-12" style="overflow-x:scroll">

        <table class="table table-bordered table-striped">
            <thead>
                <th>Barcode</th>
                <th>Nama</th>
                <th>Expired</th>
                <th>Jumlah</th>
                <th>Satuan</th>
                <th>Harga Beli</th>
                <th>Total Harga</th>
                <th>Stock</th>
                <th>Harga Jual</th>
                <th>Hapus</th>
            </thead>
            <tbody id="table-transaction">
                <?php $i = 1; ?>
                <tr id="row-data-{{ $i }}">
                    <td>
                        <textarea type="text" name="barcodes[]" class="form-control" id="barcode-{{ $i }}" style="height: 70px"></textarea>
                    </td>
                    <td width="20%">
                        {!! Form::textarea('name_temps[]', null, array('class' => 'form-control', 'readonly' => 'readonly', 'id' => 'name_temp-'.$i, 'style' => 'height: 70px')) !!}
                        {!! Form::text('names[]', null, array('id'=>'name-' . $i, 'style' => 'display:none')) !!}
                    </td>
                    <td>
                        <div class="input-group date">
                            <input type="text" class="form-control" name="exp_dates[]" id="exp-{{$i}}" style="word-wrap: break-word; height: 70px;"> 
                        </div>
                    </td>
                    <td>
                        <textarea type="text" name="quantities[]" class="form-control" id="quantity-{{ $i }}"
                            onchange="editPrice('{{ $i }}')" onkeypress="editPrice('{{ $i }}')"></textarea>
                    </td>
                    <td>
                        @if($SubmitButtonText == 'View')
                            {!! Form::text('unit', null, array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                        @else
                            {!! Form::select('units[]', getUnits(), null, ['class' => 'form-control select2','required'=>'required', 'style'=>'width:100%', 'id' => 'unit-' . $i, 'onchange' => 'changePriceByUnit(' . $i . ')']) !!}
                        @endif
                    </td>
                    <td>
                         <textarea type="text" name="prices[]" class="form-control" id="price-{{ $i }}"
                            onchange="editPrice('{{ $i }}')" onkeypress="editPrice('{{ $i }}')"></textarea>
                    </td>
                    <td>
                        {!! Form::textarea('total_prices[]', null, array('class' => 'form-control', 'readonly' =>
                        'readonly', 'id' => 'total_price-'.$i, 'style' => 'height: 70px')) !!}
                    </td>
                    <td>
                        {!! Form::textarea('stocks[]', null, array('class' => 'form-control', 'readonly' =>
                        'readonly', 'id' => 'stock-'.$i, 'style' => 'height: 70px')) !!}
                    </td>
                    <td>
                        {!! Form::textarea('sell_prices[]', null, array('class' => 'form-control', 'id' => 'sell_price-'.$i, 'style' => 'height: 70px')) !!}
                    </td>
                    <td><i class="fa fa-times red" id="delete-{{ $i }}" onclick="deleteItem('{{ $i }}')"></i></td>
                </tr>
            </tbody>
        </table>
        </div>
        <div class="form-group">
            {!! Form::label('total_item_price', 'Total Harga', array('class' => 'col-sm-3 control-label')) !!}
            <div class="col-sm-3">
                {!! Form::text('total_item_price', null, array('class' => 'form-control', 'readonly' => 'readonly', 'id'
                => 'total_item_price')) !!}
            </div>
        </div>
    </div>

    {{ csrf_field() }}

    <hr>
    @if($SubmitButtonText == 'Edit')
    {!! Form::submit($SubmitButtonText, ['class' => 'btn btn-warning btn-flat btn-block form-control']) !!}
    @elseif($SubmitButtonText == 'Tambah')
    <div onclick="event.preventDefault(); submitForm();" class='btn btn-success btn-flat btn-block form-control'>Proses
        Loading</div>
    @elseif($SubmitButtonText == 'View')
    @endif

    <h4>Barang Baru</h4>
    <div class="row">
        <div class="col-sm-5">
            <div class="form-group">
                {!! Form::label('category_id', 'Kategori', array('class' => 'col-sm-4 control-label')) !!}
                <div class="col-sm-8">
                    {!! Form::select('category_id', getCategories(), null, ['class' => 'form-control select2',
                    'style'=>'width: 100%', 'id' => 'category_id']) !!}
                </div>
            </div>
            <div class="form-group">
                {!! Form::label('code', 'Barcode Barang', array('class' => 'col-sm-4 control-label')) !!}
                <div class="col-sm-8">
                    {!! Form::text('code', null, array('class' => 'form-control', 'id' => 'code')) !!}
                    {{-- <input name="generate" type="checkbox" checked="checked" id="generate"> Generate code --}}
                </div>
            </div>

            <div class="form-group">
                {!! Form::label('name', 'Nama Barang', array('class' => 'col-sm-4 control-label')) !!}
                <div class="col-sm-8">
                    {!! Form::text('name', null, array('class' => 'form-control','required'=>'required', 'id' => 'name')) !!}
                </div>
            </div>

            <div class="form-group">
                {!! Form::label('brand_id', 'Brand/Merek', array('class' => 'col-sm-4 control-label')) !!}
                <div class="col-sm-8">
                    {!! Form::select('brand_id', getBrands(), null, ['class' => 'form-control select2',
                    'style'=>'width: 100%', 'id' => 'brand_id']) !!}
                </div>
            </div>

            <div class="form-group">
                {!! Form::label('unit_id', 'Satuan', array('class' => 'col-sm-4 control-label')) !!}
                <div class="col-sm-8">
                    {!! Form::select('unit_id', getUnits(), null, ['class' => 'form-control select2',
                    'style'=>'width: 100%', 'id' => 'unit_id']) !!}
                </div>
            </div>

            <div class="form-group">
                {!! Form::label('price', 'Harga Beli', array('class' => 'col-sm-4 control-label')) !!}
                <div class="col-sm-8">
                    {!! Form::text('price', null, array('class' => 'form-control','required'=>'required', 'id' => 'price')) !!}
                </div>
            </div>

            <div class="form-group">
                {!! Form::label('selling_price', 'Harga Jual', array('class' => 'col-sm-4 control-label')) !!}
                <div class="col-sm-8">
                    {!! Form::text('selling_price', null, array('class' => 'form-control','required'=>'required', 'id' => 'selling_price')) !!}
                </div>
            </div>
        </div>
    </div>
    <div onclick="event.preventDefault(); addNewGood()" class='btn btn-success btn-flat btn-block form-control'>Tambah Barang Baru</div>
</div>

{!! Form::close() !!}

@section('js-addon')
<script type="text/javascript">
    var total_item = 1;
    var total_real_item=0;
          $(document).ready (function (){
              $('.select2').select2();
              $("#all_barcode").focus();
                $('#loading_date').datepicker({
                    autoclose: true,
                    format: 'yyyy-mm-dd',
                    todayHighlight: true
                });
             $('#expiry_date').datepicker({
                autoclose: true,
                format: 'yyyy-mm-dd',
                todayHighlight: true
            });
            $('#exp-'+total_item).datepicker({
                autoclose: true,
                format: 'yyyy-mm-dd',
                todayHighlight: true
            });
          });

          function fillItem(good,index)
          {
              var bool = false;

              if(good.length != 0)
              {
                if (index==-1)
                {
                  if(bool == false)
                  {
                      document.getElementById("name-" + total_item).value = good.id;
                      document.getElementById("name_temp-" + total_item).value = good.name;
                      document.getElementById("barcode-" + total_item).value = good.code;
                      $("#unit-" + total_item).val(good.getPcsSellingPrice.unit_id).change();
                      $("#price-" + total_item).val(good.getPcsSellingPrice.buy_price);
                      $("#sell_price-" + total_item).val(good.getPcsSellingPrice.selling_price);
                      document.getElementById("quantity-" + total_item).value = 1;

                      editPrice(total_item);
                    total_real_item+=1;
                      document.getElementById("all_barcode").value = '';
                      $("#all_barcode").focus();

                  }
                }
                else
                {
                      document.getElementById("name-" + index).value = good.id;
                      document.getElementById("name_temp-" + index).value = good.name;
                      document.getElementById("barcode-" + index).value = good.code;
                      $("#unit-" + index).val(good.getPcsSellingPrice.unit_id).change();
                      $("#price-" + index).val(good.getPcsSellingPrice.buy_price);
                      $("#sell_price-" + index).val(good.getPcsSellingPrice.selling_price);
                      document.getElementById("quantity-" + index).value = 1;

                      editPrice(index);
                      total_real_item+=1;
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

          function addNewGood()
          {
              var isi=true;
              if($("#category_id").val()==""){
                isi=false;
                alert("silahkan pilih kategori");
              }
              if($("#name").val()==""){
                isi=false;
                alert("silahkan isi nama");
                console.log("ini sampai");
              }
              if(isi){
              $.ajax({
                url: "{!! url($role . '/good/store/') !!}",
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: {
                    role: '{{ $role }}',
                    category_id: $("#category_id").val(),
                    code: $("#code").val(),
                    name: $("#name").val(),
                    brand_id: $("#brand_id").val(),
                    unit_id: $("#unit_id").val(),
                    price: $("#price").val(),
                    selling_price: $("#selling_price").val(),
                },
                success: function(result){
                    console.log(result);
                    $("#name-" + total_item).val(result.good.id);
                    $("#barcode-" + total_item).val(result.good.code);
                    $("#name_temp-" + total_item).val(result.good.name);
                    $("#quantity-" + total_item).val("1");
                    $("#unit-" + total_item).val(result.good.unit_id).change();
                    $("#price-" + total_item).val(result.good.price);
                    $("#sell_price-" + total_item).val(result.good.selling_price);

                    total_item += 1;
                    total_real_item+=1;
                    htmlResult = '<tr id="row-data-' + total_item+ '"><td><textarea type="text" name="barcodes[]" class="form-control" id="barcode-' + total_item+ '" onchange="searchName(' + total_item+ ')"></textarea></td><td width="20%"><textarea  class="form-control" readonly="readonly" id="name_temp-' + total_item+ '" name="name_temps[]" type="text" style="height: 70px"></textarea><textarea id="name-' + total_item + '" name="names[]" type="text" style="display:none"></textarea></td><td><input class="form-control" id="exp-' + total_item + '" name="exp_dates[]" type="text"></td><td><textarea type="text" name="quantities[]" class="form-control" id="quantity-' + total_item +'" onkeypress="editPrice(' + total_item +')" onchange="editPrice(' + total_item + ')"></textarea></td><td><select class="form-control select2" id="unit-' + total_item + '" name="units[]" onchange="changePriceByUnit(' + total_item + ')">@foreach(getUnitAsObjects() as $unit)<option value="{{ $unit->id }}">{{ $unit->name }}</option>@endforeach</select></td><td><textarea class="form-control" id="price-' + total_item + '" name="prices[]" type="text" onkeypress="editPrice(' + total_item +')" onchange="editPrice(' + total_item + ')"></textarea></td><td><textarea class="form-control" readonly="readonly" id="total_price-' + total_item +'" name="total_prices[]" type="text"></textarea></td><td><textarea class="form-control" readonly="readonly" id="stock-' + total_item+ '" name="stocks[]" type="text"></textarea></td><td><textarea class="form-control" id="sell_price-' + total_item+ '" name="sell_prices[]" type="text"></textarea></td><td><i class="fa fa-times red" id="delete-' + total_item +'" onclick="deleteItem(' + total_item + ')"></i></td></tr>';
                    htmlResult += "<script>$('#unit-" + total_item + "').select2();<\/script>";
                    $("#table-transaction").append(htmlResult);

                    // $("#good_category_id").val("");
                    $("#code").val("");
                    // $("#generate").val("");
                    $("#name").val("");
                    $("#name_from_distributor").val("");
                    // $("#color_id").val(null);
                    // $("#unit").val(null);
                    $("#price").val("");
                    $("#selling_price").val("");
                    $("#expiry_date").val("");
                },
                error: function(){
                    // console.log('error');
                }
              });
              };
          }

          function searchByBarcode()
          {

              $.ajax({
                url: "{!! url($role . '/good/searchByBarcode/') !!}/" + $("#all_barcode").val(),
                success: function(result){
                  var good = result.good;
                  var index=-1;

                  fillItem(result.good,index)},
                error: function(){
                }
              });
          }


          function searchItemByName()
          {

              $.ajax({
                url: "{!! url($role . '/good/searchByKeywordGoodUnit/') !!}/" + $("#all_name").val(),
                success: function(result){
                    var index=-1;
                    var r = result.good_units;

                    for (var i = 0; i < r.length; i++) {
                        const getPcsSellingPrice = {unit_id: r[i].unit_id, buy_price: r[i].buy_price, selling_price: r[i].selling_price};
                        const good = {id: r[i].good_id, name: r[i].name, code: r[i].code, getPcsSellingPrice: getPcsSellingPrice};
                        
                        fillItem(good,index);
                    }
                },
                error: function(){
                }
              });
          }

          function checkQuantity(index)
          {
              $.ajax({
                url: "{!! url($role . '/good/checkQuantity/') !!}/" + $("#name-" + index).val() + '/' + $("#quantity-" + index).val(),
                success: function(result){
                  var status = result.status;

                  if(status == 'ok')
                  {
                      // changePrice(index);
                  }
                  else
                  {
                      alert('Barang tidak mencukupi');
                  }
                },
                error: function(){
                }
              });
          }

          function changeFocus(index)
          {
              $("#barcode-" + index).focus();
          }

          function changeTotal()
          {
              total_item_price = parseInt(0);
              total_promo_price = parseInt(0);
              for (var i = 1; i <= total_item; i++)
              {
                  if(document.getElementById("barcode-" + i))
                  {
                      if(document.getElementById("barcode-" + i).value != '')
                      {
                          money = document.getElementById("total_price-" + i).value;
                          money = money.replace(/,/g,'');
                          console.log('money: ' + money);
                          total_item_price += parseInt(money);

                      }
                  }
              }

              document.getElementById("total_item_price").value = total_item_price;

              formatNumber("total_item_price");

          }

          function changeTotalSum()
          {
              if(document.getElementById("total_discount_price").value == '')
              {
                  // alert("Silahkan isi potongan harga");
              }
              else
              {
                  changeTotal();

                  total = document.getElementById("total_sum_price").value;
                  total = total.replace(/,/g,'');

                  discount = document.getElementById("total_discount_price").value;
                  discount = discount.replace(/,/g,'');
                  total_sum_price = parseInt(total) - parseInt(discount);

                  document.getElementById("total_sum_price").value = total_sum_price;
                  formatNumber("total_sum_price");

                  changeReturn();
              }
          }

          function submitForm()
          {
              var isi=true;
              if ( $("#distributor").val()==""){
                isi=false;
                alert("silahkan isi distributor");
              }
              if ( $("#loading_date").val()==""){
                isi=false;
                alert("silahkan isi tanggal pembelian");
              }
              if(total_real_item == 0 )
              {
                  alert('Silahkan pilih barang');
                  isi=false;
              }
              if(isi)
              {
                  document.getElementById('loading-form').submit();
                  // alert('hay');
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
              total_real_item-=1;
              changeTotal();
          }
          function editPrice(index)
          {
              document.getElementById("total_price-" + index).value = unFormatNumber( document.getElementById("price-" + index).value) * unFormatNumber( document.getElementById("quantity-" + index).value);

              formatNumber("total_price-" + index);

              changeTotal();
              temp1=parseInt(index)+1
              htmlResult = '<tr id="row-data-' + temp1+ '"><td><textarea type="text" name="barcodes[]" class="form-control" id="barcode-' + temp1+ '" onchange="searchName(' + temp1+ ')"></textarea></td><td width="20%"><textarea  class="form-control" readonly="readonly" id="name_temp-' + temp1+ '" name="name_temps[]" type="text" style="height: 70px"></textarea><textarea id="name-' + temp1 + '" name="names[]" type="text" style="display:none"></textarea></td><td><input class="form-control"  id="exp-' +temp1+ '" name="exp_dates[]" type="text"></td><td><textarea type="text" name="quantities[]" class="form-control" id="quantity-' + temp1+'" onkeypress="editPrice(' + temp1+')" onchange="editPrice(' + temp1+ ')"></textarea></td><td><select class="form-control select2" id="unit-' + temp1 + '" name="units[]" onchange="changePriceByUnit(' + temp1 + ')">@foreach(getUnitAsObjects() as $unit)<option value="{{ $unit->id }}">{{ $unit->name }}</option>@endforeach</select></td><td><textarea type="text" name="prices[]" class="form-control" id="price-' + temp1+'" onkeypress="editPrice(' + temp1+')" onchange="editPrice(' + temp1+ ')"></textarea></td><td><textarea class="form-control" readonly="readonly" id="total_price-' + temp1+ '" name="total_prices[]" type="text"></textarea></td><td><textarea class="form-control" readonly="readonly" id="stock-' + temp1+ '" name="stocks[]" type="text"></textarea></td><td><textarea class="form-control" id="sell_price-' + temp1+ '" name="sell_prices[]" type="text"></textarea></td><td><i class="fa fa-times red" id="delete-' + temp1+'" onclick="deleteItem('
              + temp1+ ')"></i></td></tr>';
              htmlResult += "<script>$('#unit-" + temp1 + "').select2();$('#exp-"+temp1+"').datepicker({autoclose: true,format: 'yyyy-mm-dd',todayHighlight: true});<\/script>";
              if(index == total_item)
              {
                  total_item += 1;
                  $("#table-transaction").append(htmlResult);
                  // $("#table-transaction").append(s);
              }
              document.getElementById("all_barcode").value = '';
              $("#all_barcode").focus();

          }

        function changePriceByUnit(index)
        {
          $.ajax({
            url: "{!! url($role . '/good/getPriceUnit/') !!}/" + $("#name-" + index).val() + '/' + $("#unit-" + index).val(),
            success: function(result){
              var good_unit = result.good_unit;

              if(good_unit != null)
              {
                  document.getElementById("price-" + index).value = good_unit.buy_price;
                  document.getElementById("sell_price-" + index).value = good_unit.selling_price;
              }
              else
              {
                  document.getElementById("price-" + index).value = '0';
                  document.getElementById("sell_price-" + index).value = '0';
              }

              document.getElementById("total_price-" + index).value = document.getElementById("price-" + index).value * document.getElementById("quantity-" + index).value;

              changeTotal();
            },
            error: function(){
            }
          });
        }
</script>
@endsection
