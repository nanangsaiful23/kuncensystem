<style type="text/css">
    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background-color: rgb(60, 141, 188) !important;
    }
</style>

<div class="panel-body">
    <?php $goods = getGoods() ?>
    <div class="row">
       <div class="col-sm-7">
            {!! Form::label('note', 'Catatan', array('class' => 'col-sm-1 left control-label')) !!}
            <div class="col-sm-12">
                <input type="text" name="note" class="form-control" id="note">
            </div>
            {!! Form::label('checker', 'PIC Check Barang', array('class' => 'col-sm-12 control-label', 'style' => 'text-align: left')) !!}
            <div class="col-sm-12">
                <input type="text" name="checker" class="form-control" id="checker" required="required">
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
        <div class="form-group col-sm-12" style="overflow-x:scroll">
            <table class="table table-bordered table-striped">
                <thead>
                    <th width="15%">Barcode</th>
                    <th width="45%">Nama</th>
                    <th>Satuan</th>
                    <th width="10%">Stock Lama</th>
                    <th width="10%">Stock Baru</th>
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
                            {!! Form::text('unit_names[]', null, array('class' => 'form-control', 'readonly' => 'readonly', 'id' => 'unit_name-'.$i)) !!}
                            <input type="hidden" name="base_qtys[]" id="base_qty-{{ $i}}">
                            <input type="hidden" name="units[]" id="unit-{{ $i}}">
                        </td>
                        <td>
                            {!! Form::textarea('old_stocks[]', null, array('class' => 'form-control', 'readonly' =>
                            'readonly', 'id' => 'old_stock-'.$i, 'style' => 'height: 70px')) !!}
                        </td>
                        <td>
                            {!! Form::textarea('new_stocks[]', null, array('class' => 'form-control', 'id' => 'new_stock-'.$i, 'style' => 'height: 70px')) !!}
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
    <div onclick="event.preventDefault(); submitForm();" class='btn btn-success btn-flat btn-block form-control'>Proses Stock Opname</div>
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

            $("#search_good").keyup( function(e){
              if(e.keyCode == 13)
              {
                ajaxFunction();
              }
            });
          });

          function fillItem(good,index)
          {
              var bool = false;

              if(good.length != 0 && good.getPcsSellingPrice.unit_qty == good.getPcsSellingPrice.base_qty)
              {
                  document.getElementById("name-" + total_item).value = good.id;
                  document.getElementById("name_temp-" + total_item).value = good.name;
                  document.getElementById("barcode-" + total_item).value = good.code;
                  document.getElementById("unit-" + total_item).value = good.getPcsSellingPrice.unit_id;
                  document.getElementById("unit_name-" + total_item).value = good.getPcsSellingPrice.unit;
                  document.getElementById("base_qty-" + total_item).value = good.getPcsSellingPrice.unit_qty;
                  document.getElementById("old_stock-" + total_item).value = good.old_stock;

                  editPrice(total_item);
                  total_real_item += 1;
                  document.getElementById("all_barcode").value = '';
              }
              else
              {
                  // alert('Barang tidak ditemukan');
              }
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
                        const getPcsSellingPrice = {unit_id: r[i].unit_id, unit_qty: r[i].unit_qty, base_qty: r[i].good_base_qty, base_buy_price: r[i].good_base_buy_price, buy_price: r[i].buy_price, selling_price: r[i].selling_price, unit: r[i].unit};
                        const good = {id: r[i].good_id, name: r[i].name, code: r[i].code, getPcsSellingPrice: getPcsSellingPrice, old_stock: r[i].stock};

                        fillItem(good,index);
                        $('#modal_search').modal('hide');
                        $('#search_good').val('');
                        $('#result_good').val('');
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

              temp1=parseInt(index)+1
              htmlResult = '<tr id="row-data-' + temp1+ '"><td><textarea type="text" name="barcodes[]" class="form-control" id="barcode-' + temp1+ '" onchange="searchName(' + temp1+ ')"></textarea></td><td width="20%"><textarea  class="form-control" readonly="readonly" id="name_temp-' + temp1+ '" name="name_temps[]" type="text" style="height: 70px"></textarea><textarea id="name-' + temp1 + '" name="names[]" type="text" style="display:none"></textarea></td><td><textarea  class="form-control" readonly="readonly" id="unit_name-' + temp1 + '" name="unit_names[]" type="text"></textarea><input type="hidden" name="base_qtys[]" id="base_qty-' + temp1 + '"><input type="hidden" name="units[]" id="unit-' + temp1 + '"></td><td><textarea class="form-control" readonly="readonly" id="old_stock-' + temp1+ '" name="old_stocks[]" type="text"></textarea></td><td><textarea class="form-control" id="new_stock-' + temp1+ '" name="new_stocks[]" type="text"></textarea></td><td><i class="fa fa-times red" id="delete-' + temp1+'" onclick="deleteItem('
              + temp1+ ')"></i></td></tr>';
              htmlResult += "<script>$('#exp-"+temp1+"').datepicker({autoclose: true,format: 'yyyy-mm-dd',todayHighlight: true});<\/script>";
              if(index == total_item)
              {
                  total_item += 1;
                  $("#table-transaction").prepend(htmlResult);
                  // $("#table-transaction").append(s);
              }
              document.getElementById("all_barcode").value = '';
              // $("#all_barcode").focus();

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
