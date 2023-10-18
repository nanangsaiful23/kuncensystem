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
        </div>
    </div>

    <h4>Barang</h4>
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
                <th>Jumlah</th>
                <th style="width: 20%">Satuan</th>
                <th>Hapus</th>
            </thead>
            <tbody id="table-transaction">
                <?php $i = 1; ?>
                <tr id="row-data-{{ $i }}">
                    <input type="hidden" name="base_qtys[]" id="base_qty-{{ $i}}">
                    <td>
                        <textarea type="text" name="barcodes[]" class="form-control" id="barcode-{{ $i }}" style="height: 70px"></textarea>
                    </td>
                    <td width="20%">
                        {!! Form::textarea('name_temps[]', null, array('class' => 'form-control', 'readonly' => 'readonly', 'id' => 'name_temp-'.$i, 'style' => 'height: 70px')) !!}
                        {!! Form::text('names[]', null, array('id'=>'name-' . $i, 'style' => 'display:none')) !!}
                    </td>
                    <td>
                        <textarea type="text" name="quantities[]" class="form-control" id="quantity-{{ $i }}"
                            onchange="editPrice('{{ $i }}')" onkeypress="editPrice('{{ $i }}')"></textarea>
                    </td>
                    <td>
                        @if($SubmitButtonText == 'View')
                            {!! Form::text('unit', null, array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                        @else
                            {!! Form::select('units[]', getUnits(), null, ['class' => 'form-control select2','required'=>'required', 'style'=>'width:100%', 'id' => 'unit-' . $i]) !!}
                        @endif
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
    <div onclick="event.preventDefault(); submitForm();" class='btn btn-success btn-flat btn-block form-control'>Proses Retur</div>
    @elseif($SubmitButtonText == 'View')
    @endif
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
          });

          function fillItem(good,index)
          {
              var bool = false;

              if(good.length != 0)
              {
                  document.getElementById("name-" + total_item).value = good.id;
                  document.getElementById("name_temp-" + total_item).value = good.name;
                  document.getElementById("barcode-" + total_item).value = good.code;
                  $("#unit-" + total_item).val(good.getPcsSellingPrice.unit_id).change();
                  document.getElementById("base_qty-" + total_item).value = good.getPcsSellingPrice.unit_qty;
                  document.getElementById("quantity-" + total_item).value = 1;

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
            console.log("{!! url($role . '/good/searchById/') !!}/" + $("#all_name").val());
              $.ajax({
                url: "{!! url($role . '/good/searchById/') !!}/" + $("#all_name").val(),
                success: function(result){
                    var index=-1;
                    var r = result.units;

                    for (var i = 0; i < r.length; i++) {
                        const getPcsSellingPrice = {unit_id: r[i].unit_id, unit_qty: r[i].unit_qty, base_qty: r[i].good_base_qty, base_buy_price: r[i].good_base_buy_price, buy_price: r[i].buy_price, selling_price: r[i].selling_price};
                        const good = {id: r[i].good_id, name: r[i].name, code: r[i].code, getPcsSellingPrice: getPcsSellingPrice, old_stock: r[i].stock};
                        console.log(good);
                        fillItem(good,index);
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

          function submitForm()
          {
              var isi=true;
              if ($("#distributor_name").val() == "" && $("#all_distributor").val() == "null"){
                isi=false;
                alert("silahkan isi distributor");
              }
              if(total_real_item == 0 )
              {
                  alert('Silahkan pilih barang');
                  isi=false;
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
                  document.getElementById('retur-form').submit();
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
              temp1=parseInt(index)+1
              htmlResult = '<tr id="row-data-' + temp1+ '"><input type="hidden" name="base_qtys[]" id="base_qty-' + temp1 + '"><td><textarea type="text" name="barcodes[]" class="form-control" id="barcode-' + temp1+ '" onchange="searchName(' + temp1+ ')"></textarea></td><td width="20%"><textarea  class="form-control" readonly="readonly" id="name_temp-' + temp1+ '" name="name_temps[]" type="text" style="height: 70px"></textarea><textarea id="name-' + temp1 + '" name="names[]" type="text" style="display:none"></textarea></td><td><textarea type="text" name="quantities[]" class="form-control" id="quantity-' + temp1+'"></textarea></td><td style="width: 20%"><select class="form-control select2" id="unit-' + temp1 + '" name="units[]">@foreach(getUnitAsObjects() as $unit)<option value="{{ $unit->id }}">{{ $unit->name }}</option>@endforeach</select></td><td><i class="fa fa-times red" id="delete-' + temp1+'" onclick="deleteItem('
              + temp1+ ')"></i></td></tr>';
              htmlResult += "<script>$('#unit-" + temp1 + "').select2();<\/script>";
              if(index == total_item)
              {
                  total_item += 1;
                  $("#table-transaction").prepend(htmlResult);
                  // $("#table-transaction").append(s);
              }
              document.getElementById("all_barcode").value = '';
              // $("#all_barcode").focus();

          }
</script>
@endsection
