<style type="text/css">
    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background-color: rgb(60, 141, 188) !important;
    }

    table tr td .form-control, .form-control[disabled], .form-control[readonly], fieldset[disabled] .form-control
    {
        /*font-size: 17px;*/
        /*font-weight: 700;*/
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

<div class="panel-body" @if($type == 'internal') style="background-color: yellow" @elseif($type == 'transaction-internal') style="background-color: #EECAD5" @endif>
    <?php $distributors = getDistributors() ?>
    <div class="row" style="margin-top: -40px">
        <div class="col-sm-12">
            <div class="col-sm-9" style="width: 80% !important">
                <div class="row">
                    <div class="form-group col-sm-12" style="overflow-x:scroll">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <th>No</th>
                                <th>Barcode</th>
                                <th>Nama</th>
                                <th>Expired</th>
                                <th>Jumlah</th>
                                <th>Satuan</th>
                                <th>Stock Lama</th>
                                <th>Stock Baru</th>
                                @if(\Auth::user()->role == 'supervisor')
                                    <th>Harga Beli</th>
                                    <th>Total Harga</th>
                                    <th>Harga Jual</th>
                                @endif
                                <th>Hapus</th>
                            </thead>
                            <tbody id="table-transaction">
                                <?php $i = 1; ?>
                                <tr id="row-data-{{ $i }}">
                                    <input type="hidden" name="base_qtys[]" id="base_qty-{{ $i}}">
                                    <td id="count-good-{{ $i }}"></td>
                                    <td width="5%">
                                        <textarea type="text" name="barcodes[]" class="form-control" id="barcode-{{ $i }}" style="height: 70px"></textarea>
                                    </td>
                                    <td width="20%">
                                        {!! Form::textarea('name_temps[]', null, array('class' => 'form-control', 'id' => 'name_temp-'.$i, 'style' => 'height: 70px')) !!}
                                        {!! Form::text('names[]', null, array('id'=>'name-' . $i, 'style' => 'display:none')) !!}
                                    </td>
                                    <td>
                                        <div class="input-group date">
                                            <input type="text" class="form-control" name="exp_dates[]" id="exp-{{$i}}" style="word-wrap: break-word; height: 70px;">
                                        </div>
                                    </td>
                                    <td>
                                        <textarea type="text" name="quantities[]" class="form-control" id="quantity-{{ $i }}"
                                            onchange="editPrice('{{ $i }}')" onkeypress="editPrice('{{ $i }}')" onkeyup="editPrice('{{ $i }}')"></textarea>
                                    </td>
                                    <td>
                                        @if($SubmitButtonText == 'View')
                                            {!! Form::text('unit', null, array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                                        @else
                                            {!! Form::select('units[]', getUnits(), null, ['class' => 'form-control select2','required'=>'required', 'style'=>'width:100%', 'id' => 'unit-' . $i, 'onchange' => 'changePriceByUnit(' . $i . ')']) !!}
                                        @endif
                                    </td>
                                    <td>
                                        {!! Form::textarea('old_stocks[]', null, array('class' => 'form-control', 'readonly' =>
                                        'readonly', 'id' => 'old_stock-'.$i, 'style' => 'height: 70px')) !!}
                                    </td>
                                    <td>
                                        {!! Form::textarea('new_stocks[]', null, array('class' => 'form-control', 'readonly' =>
                                        'readonly', 'id' => 'new_stock-'.$i, 'style' => 'height: 70px')) !!}
                                    </td>
                                    <td @if(\Auth::user()->role != 'supervisor') style="display: none" @endif>
                                         <textarea type="text" name="prices[]" class="form-control" id="price-{{ $i }}" onchange="editBuyPrice('{{ $i }}')" onkeyup="editBuyPrice('{{ $i }}')"></textarea>
                                    </td>
                                    <td @if(\Auth::user()->role != 'supervisor') style="display: none" @endif>
                                        {!! Form::textarea('total_prices[]', null, array('class' => 'form-control', 'readonly' => 'readonly', 'id' => 'total_price-'.$i, 'style' => 'height: 70px')) !!}
                                    </td>
                                    <td @if(\Auth::user()->role != 'supervisor') style="display: none" @endif>
                                        {!! Form::textarea('sell_prices[]', null, array('class' => 'form-control', 'id' => 'sell_price-'.$i, 'style' => 'height: 70px')) !!}
                                    </td>
                                    <td><i class="fa fa-times red" id="delete-{{ $i }}" onclick="deleteItem('{{ $i }}')"></i></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{ csrf_field() }}

                <hr>
                @if($SubmitButtonText == 'Edit')
                {!! Form::submit($SubmitButtonText, ['class' => 'btn btn-warning btn-flat btn-block form-control']) !!}
                @elseif($SubmitButtonText == 'Tambah')
                    <div onclick="event.preventDefault(); submitForm();" class='btn btn-success btn-flat btn-block form-control'>{{ $default['page_name'] }}</div>
                @elseif($SubmitButtonText == 'View')
                @endif
            </div>
            <div class="col-sm-3" style="width: 20% !important">
                <div class="form-group col-sm-12" style="margin-top: -40px">
                    {!! Form::label('all_barcode', 'Barcode', array('class' => 'col-sm-1 left control-label')) !!}
                    <div class="col-sm-12 input-group">
                        <input type="text" name="all_barcode" class="form-control" id="all_barcode" onchange="searchByBarcode()">
                        <span class="input-group-btn">
                            <button type="submit" name="search" id="search-btn" class="btn btn-flat" onclick="event.preventDefault(); searchByBarcode();"><i class="fa fa-search"></i>
                        </button>
                    </div>
                </div>
                <div class="form-group col-sm-12" style="margin-top: -10px;">
                    {!! Form::label('keyword', 'Keyword', array('class' => 'col-sm-1 left control-label')) !!}
                    <div class="col-sm-12 input-group">
                        <input type="text" name="search_good" class="form-control" id="search_good">
                        <span class="input-group-btn">
                            <button type="submit" name="search" id="search-btn" class="btn btn-flat" onclick="event.preventDefault(); ajaxFunction();"><i class="fa fa-search"></i>
                        </button>
                    </span>
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
                @if(\Auth::user()->role == 'supervisor')
                    <div class="form-group col-sm-12" style="margin-top: -10px;">
                        <button type="button" class="btn btn-success col-sm-12" data-toggle="modal" data-target="#modal-good">Tambah barang baru</button>
                    </div>
                @endif
                <div class="form-group col-sm-12" style="margin-top: -10px;">
                    {!! Form::label('distributor_id', 'Distributor', array('class' => 'col-sm-1 left control-label')) !!}
                    <div class="col-sm-12">
                        @if($SubmitButtonText == 'View')
                            {!! Form::text('distributor', null, array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                        @else
                            <input type="text" name="distributor_name" class="form-control" id="distributor_name" style="display: none;">
                            <select class="form-control select2" style="width: 100%;" name="distributor_id" id="all_distributor">
                                <!-- <div> -->
                                    <option value="null">Silahkan pilih distributor</option>
                                    @foreach($distributors as $distributor)
                                    <option value="{{ $distributor->id }}">
                                        {{ $distributor->name }}</option>
                                    @endforeach
                                <!-- </div> -->
                            </select>
                        @endif
                    </div>
                </div>
                <div class="form-group col-sm-12" style="margin-top: -10px;">
                    {!! Form::label('loading_date', 'Tanggal Pembelian', array('class' => 'col-sm-12 control-label', 'style' => 'text-align: left')) !!}
                    <div class="col-sm-12">
                        <div class="input-group date">
                            <input type="text" class="form-control pull-right" required="required" name="loading_date" id="loading_date">
                        </div>
                    </div>
                </div>
               <div class="form-group col-sm-12" style="margin-top: -10px;">
                    {!! Form::label('note', 'Catatan', array('class' => 'col-sm-1 left control-label')) !!}
                    <div class="col-sm-12">
                        <textarea type="text" name="note" class="form-control" id="note"></textarea>
                    </div>
                </div>
               <div class="form-group col-sm-12" style="margin-top: -10px;">
                    {!! Form::label('checker', 'PIC Check Barang', array('class' => 'col-sm-12 control-label', 'style' => 'text-align: left')) !!}
                    <div class="col-sm-12">
                        <input type="text" name="checker" class="form-control" id="checker">
                    </div>
                </div>
               <div class="form-group col-sm-12" style="margin-top: -10px;">
                    {!! Form::label('payment', 'Jenis Pembayaran', array('class' => 'col-sm-12 control-label', 'style' => 'text-align: left')) !!}
                    <div class="col-sm-12">
                        {!! Form::select('payment', getLoadingPaymentType(), null, ['class' => 'form-control select2', 'style'=>'width: 100%', 'id' => 'payment']) !!}
                    </div>
                </div>
                <div class="form-group col-sm-12" style="margin-top: -10px;" @if(\Auth::user()->role != 'supervisor') style="display: none" @endif>
                    {!! Form::label('total_item_price', 'Total Harga', array('class' => 'col-sm-12 control-label', 'style' => 'text-align: left')) !!}
                    <div class="col-sm-12">
                        {!! Form::text('total_item_price', null, array('class' => 'form-control', 'readonly' => 'readonly', 'id'
                        => 'total_item_price')) !!}
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(\Auth::user()->role == 'supervisor')
        <div class="modal fade" id="modal-good">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span></button>
                  <h4 class="modal-title">Barang Baru</h4>
              </div>
              <div class="modal-body">
                <div class="col-sm-12">
                    <div class="form-group">
                        {!! Form::label('category_id', 'Kategori', array('class' => 'col-sm-4 control-label')) !!}
                        <div class="col-sm-8">
                            {!! Form::select('category_id', getCategories(), null, ['class' => 'form-control select2',
                            'style'=>'width: 100%', 'id' => 'category_id']) !!}
                        </div>
                    </div>
                    <div class="form-group">
                        {!! Form::label('type_id', 'Jenis Barang', array('class' => 'col-sm-4 control-label')) !!}
                        <div class="col-sm-8">
                            {!! Form::select('type_id', getGoodTypes(), null, ['class' => 'form-control select2',
                            'style'=>'width: 100%', 'id' => 'type_id']) !!}
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
                            {!! Form::text('price', null, array('class' => 'form-control','required'=>'required', 'id' => 'price', 'onkeyup' => 'formatNumber("price")')) !!}
                        </div>
                    </div>

                    <div class="form-group">
                        {!! Form::label('selling_price', 'Harga Jual', array('class' => 'col-sm-4 control-label')) !!}
                        <div class="col-sm-8">
                            {!! Form::text('selling_price', null, array('class' => 'form-control','required'=>'required', 'id' => 'selling_price', 'onkeyup' => 'formatNumber("selling_price")')) !!}
                        </div>
                    </div>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-outline pull-left" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-outline" onclick="event.preventDefault(); addNewGood();">Tambah Barang Baru</button>
              </div>
            </div>
          </div>
        </div>
    @endif
</div>

{!! Form::close() !!}

@section('js-addon')
<script type="text/javascript">
    var total_item = 1;
    var total_real_item=0;
    var total_good = 0;
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

            $("#search_good").keyup( function(e){
              if(e.keyCode == 13)
              {
                ajaxFunction();
              }
            });
          });

          function fillItem(good,index,idx,total)
          {
              var bool = false;

              if(good.length != 0)
              {
                  document.getElementById("name-" + total_item).value = good.id;
                  document.getElementById("name_temp-" + total_item).value = good.name;
                  document.getElementById("barcode-" + total_item).value = good.code;
                  document.getElementById("old_stock-" + total_item).value = good.old_stock;
                  document.getElementById("new_stock-" + total_item).value = parseInt(good.old_stock) + 1;
                  $("#unit-" + total_item).val(good.getPcsSellingPrice.unit_id).change();
                  $("#price-" + total_item).val(good.getPcsSellingPrice.base_buy_price * good.getPcsSellingPrice.unit_qty);
                  $("#sell_price-" + total_item).val(good.getPcsSellingPrice.selling_price);
                  document.getElementById("base_qty-" + total_item).value = good.getPcsSellingPrice.unit_qty;
                  document.getElementById("quantity-" + total_item).value = 1;

                if(idx != total)
                {
                    document.getElementById("name-" + total_item).style.display = 'none';
                    document.getElementById("name_temp-" + total_item).style.display = 'none';
                    document.getElementById("barcode-" + total_item).style.display = 'none';
                    document.getElementById("old_stock-" + total_item).style.display = 'none';
                    document.getElementById("new_stock-" + total_item).style.display = 'none';
                    document.getElementById("exp-" + total_item).style.display = 'none';
                    document.getElementById("delete-" + total_item).style.display = 'none';
                }
                else
                {
                  document.getElementById("count-good-" + total_item).innerHTML = total_good + 1;
                }

                  if(good.getPcsSellingPrice.unit_qty != good.getPcsSellingPrice.base_qty)
                  {
                    // document.getElementById('price-' + total_item).readOnly = true;
                  }
                  editPrice(total_item);
                  total_real_item += 1;
                  document.getElementById("all_barcode").value = '';
                  if(good.old_stock < 0)
                  {
                    alert('Silahkan lakukan stock opname karena stock barang minus');
                  }
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
              }
              if($("#unit_id").val()==""){
                isi=false;
                alert("silahkan pilih satuan");
              }
              if($("#price").val()==""){
                isi=false;
                alert("silahkan isi harga beli");
              }
              if($("#selling_price").val()==""){
                isi=false;
                alert("silahkan isi harga jual");
              }
              if(isi == true && parseInt($("#price").val()) >= parseInt($("#selling_price").val())){
                isi=true;
                alert("Perhatian: Harga beli lebih besar/sama dengan harga jual");
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
                    $("#old_stock-" + total_item).val(0);

                    total_item += 1;
                    total_real_item+=1;
                    total_good += 1;
                    htmlResult = '<tr id="row-data-' + total_item+ '"><input type="hidden" name="base_qtys[]" id="base_qty-' + total_item + '"><td id="count-good-' + total_item + '"></td><td width="5%"><textarea type="text" name="barcodes[]" class="form-control" id="barcode-' + total_item+ '" onchange="searchName(' + total_item+ ')"></textarea></td><td width="20%"><textarea  class="form-control" id="name_temp-' + total_item+ '" name="name_temps[]" type="text" style="height: 70px"></textarea><textarea id="name-' + total_item + '" name="names[]" type="text" style="display:none"></textarea></td><td><input class="form-control" id="exp-' + total_item + '" name="exp_dates[]" type="text"></td><td><textarea type="text" name="quantities[]" class="form-control" id="quantity-' + total_item +'" onkeypress="editPrice(' + total_item +')" onchange="editPrice(' + total_item + ')"></textarea></td><td><select class="form-control select2" id="unit-' + total_item + '" name="units[]" onchange="changePriceByUnit(' + total_item + ')">@foreach(getUnitAsObjects() as $unit)<option value="{{ $unit->id }}">{{ $unit->name }}</option>@endforeach</select></td><td><textarea class="form-control" readonly="readonly" id="old_stock-' + total_item+ '" name="old_stocks[]" type="text"></textarea></td><td><textarea class="form-control" readonly="readonly" id="new_stock-' + total_item+ '" name="new_stocks[]" type="text"></textarea></td><td @if(\Auth::user()->role != 'supervisor') style="display: none" @endif><textarea class="form-control" id="price-' + total_item + '" name="prices[]" type="text" onchange="editBuyPrice(' + total_item + ')"></textarea></td><td @if(\Auth::user()->role != 'supervisor') style="display: none" @endif><textarea class="form-control" readonly="readonly" id="total_price-' + total_item +'" name="total_prices[]" type="text"></textarea></td><td @if(\Auth::user()->role != 'supervisor') style="display: none" @endif><textarea class="form-control" id="sell_price-' + total_item+ '" name="sell_prices[]" type="text"></textarea></td><td><i class="fa fa-times red" id="delete-' + total_item +'" onclick="deleteItem(' + total_item + ')"></i></td></tr>';
                    htmlResult += "<script>$('#unit-" + total_item + "').select2();<\/script>";
                    $("#table-transaction").prepend(htmlResult);

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
                    $('#modal-good').modal('hide');
                },
                error: function(){
                    alert("Harga beli dan harga jual harus berupa angka & tidak boleh kosong");
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
                  
                  searchItemByName(good.id);
                },
                error: function(){
                }
              });
          }


          function searchItemByName(id)
          {
              $.ajax({
                url: "{!! url($role . '/good/searchById/') !!}/" + id,
                success: function(result){
                    var index=-1;
                    var r = result.units;

                    for (var i = 0; i < r.length; i++) {
                        const getPcsSellingPrice = {unit_id: r[i].unit_id, unit_qty: r[i].unit_qty, base_qty: r[i].good_base_qty, base_buy_price: r[i].good_base_buy_price, buy_price: r[i].buy_price, selling_price: r[i].selling_price};
                        const good = {id: r[i].good_id, name: r[i].name, code: r[i].code, getPcsSellingPrice: getPcsSellingPrice, old_stock: r[i].stock};

                        fillItem(good,index,i,r.length-1);
                        $('#modal_search').modal('hide');
                        $('#search_good').val('');
                        $('#result_good').val('');
                    }
                    total_good += 1;
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
              if ($("#distributor_name").val() == "" && $("#all_distributor").val() == "null"){
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
              if($("#payment").val() == "0000")
              {
                alert('Silahkan cek kembali jenis pembayaran')
              }
              // for(i = 1; i <= total_real_item; i++)
              // {
              //   if($("#old_stock-" + i).val() < 0)
              //   {
              //       alert('Silahkan lakukan stock opname, ada barang yang minus');
              //       isi=false;
              //   }
              // }
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
            dele = $('#barcode-' + index).val();
            count_good = 1;
            count_all = total_item;
            for (i = 0; i < count_all; i++) 
            {
                if($('#barcode-' + i).val() == dele)
                {
                    $("#row-data-" + i).remove();
                    total_real_item-=1;
                }
                else if($("#barcode-" + i).is(":visible"))
                {
                    console.log(document.getElementById("count-good-" + i).innerHTML);
                    document.getElementById("count-good-" + i).innerHTML = count_good;
                    count_good++;
                }
            }
              total_good-=1;
              changeTotal();
          }

          function editBuyPrice(index)
          {
            //change all buy price
            good_id = document.getElementById("barcode-" + index).value;
            base_buy_price = document.getElementById("price-" + index).value / document.getElementById("base_qty-" + index).value;
            for(i = 0; i < total_item; i++)
            {
                if($('#barcode-' + i).val() == good_id && i != index)
                {
                    document.getElementById("price-" + i).value = document.getElementById("base_qty-" + i).value * base_buy_price;
                    document.getElementById("total_price-" + i).value = document.getElementById("price-" + i).value * document.getElementById("quantity-" + i).value;
                }
            }

            editPrice(index);
          }

          function editPrice(index)
          {
              document.getElementById("total_price-" + index).value = unFormatNumber(document.getElementById("price-" + index).value) * unFormatNumber(document.getElementById("quantity-" + index).value);
              document.getElementById("new_stock-" + index).value = parseInt(document.getElementById("old_stock-" + index).value) + parseInt(document.getElementById("quantity-" + index).value);

              formatNumber("total_price-" + index);

            //   total_qty = 0;
            //   for(i = 1; i <= total_item; i++)
            // {
            //     if(document.getElementById("quantity-" + i).value != null || document.getElementById("quantity-" + i).value != ' ')
            //     {
            //         console.log(document.getElementById("quantity-" + i).value);
            //         total_qty += parseInt(document.getElementById("quantity-" + i).value);
            //     }
            // }

            // document.getElementById("total-item").innerHTML = "Total item: " + total_qty;

              changeTotal();
              temp1=parseInt(index)+1
              htmlResult = '<tr id="row-data-' + temp1+ '"><input type="hidden" name="base_qtys[]" id="base_qty-' + temp1 + '">';
              htmlResult += '<td id="count-good-' + temp1 + '"></td><td width="5%"><textarea type="text" name="barcodes[]" class="form-control" id="barcode-' + temp1+ '" onchange="searchName(' + temp1+ ')"></textarea></td><td width="20%"><textarea  class="form-control" id="name_temp-' + temp1+ '" name="name_temps[]" type="text" style="height: 70px"></textarea><textarea id="name-' + temp1 + '" name="names[]" type="text" style="display:none"></textarea></td><td><input class="form-control"  id="exp-' +temp1+ '" name="exp_dates[]" type="text"></td>';

              htmlResult += '<td><textarea type="text" name="quantities[]" class="form-control" id="quantity-' + temp1+'" onkeypress="editPrice(' + temp1+')" onchange="editPrice(' + temp1+ ')" onkeyup="editPrice(' + temp1+ ')"></textarea></td><td><select class="form-control select2" id="unit-' + temp1 + '" name="units[]" onchange="changePriceByUnit(' + temp1 + ')">@foreach(getUnitAsObjects() as $unit)<option value="{{ $unit->id }}">{{ $unit->name }}</option>@endforeach</select></td><td><textarea class="form-control" readonly="readonly" id="old_stock-' + temp1+ '" name="old_stocks[]" type="text"></textarea></td><td><textarea class="form-control" readonly="readonly" id="new_stock-' + temp1+ '" name="new_stocks[]" type="text"></textarea></td><td @if(\Auth::user()->role != 'supervisor') style="display: none" @endif><textarea type="text" name="prices[]" class="form-control" id="price-' + temp1+'" onchange="editBuyPrice(' + temp1+ ')" onkeyup="editBuyPrice(' + temp1+ ')"></textarea></td><td @if(\Auth::user()->role != 'supervisor') style="display: none" @endif><textarea class="form-control" readonly="readonly" id="total_price-' + temp1+ '" name="total_prices[]" type="text"></textarea></td><td @if(\Auth::user()->role != 'supervisor') style="display: none" @endif><textarea class="form-control" id="sell_price-' + temp1+ '" name="sell_prices[]" type="text"></textarea></td><td><i class="fa fa-times red" id="delete-' + temp1+'" onclick="deleteItem('
              + temp1+ ')"></i></td></tr>';
              
              htmlResult += "<script>$('#unit-" + temp1 + "').select2();$('#exp-"+temp1+"').datepicker({autoclose: true,format: 'yyyy-mm-dd',todayHighlight: true});<\/script>";
              if(index == total_item)
              {
                  total_item += 1;
                  $("#table-transaction").prepend(htmlResult);
                  // $("#table-transaction").append(s);
              }
              document.getElementById("all_barcode").value = '';
              // $("#all_barcode").focus();

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

        function ajaxFunction()
        {   
            $('#modal_search').modal('show');   

              $.ajax({
                url: "{!! url($role . '/good/searchByKeyword/') !!}/" + $("#search_good").val(),
                success: function(result){
                    htmlResult = '';

                    htmlResult += "<style type='text/css'>.modal-div:hover { background-color: white; }</style>";
                  var r = result.goods;

                  for (var i = 0; i < r.length; i++) {
                    if((i%2) == 0) 
                    {
                        color = '#FFF1CE';
                    }
                    else color = "#FDEFF4";
                    htmlResult += "<textarea class='col-sm-12 modal-div' style='display:inline-block; color:black; cursor: pointer; min-height:40px; max-height:80px; background-color:" + color + "; padding: 5px;' onclick='searchItemByName(\"" + r[i].id + "\")'>" + r[i].name + "</textarea>";
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
