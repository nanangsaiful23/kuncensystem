<div class="content-wrapper">
  @include('layout' . '.error')

  <section class="content">
    <div class="row">
      <div class="col-xs-12">
        <div class="box">
          <div class="box-header">
            <h3 class="box-title"> {{ $default['page_name'] }}</h3>
          </div>

          {!! Form::model($good_loading, array('class' => 'form-horizontal', 'id' => 'loading-form')) !!}
            <div class="box-body">
                <div class="panel-body">
                    <div class="row">
                        <div class="form-group">
                            {!! Form::label('distributor_id', 'Distributor', array('class' => 'col-sm-2 control-label')) !!}
                            <div class="col-sm-4">
                                {!! Form::select('distributor_id', getDistributorLists(), $good_loading->distributor_id, ['class' => 'form-control select2', 'style'=>'width: 100%', 'id' => 'distributor_id']) !!}
                            </div>
                        </div>
                        <div class="form-group">
                            {!! Form::label('loading_date', 'Tanggal Pembelian', array('class' => 'col-sm-2 control-label')) !!}
                            <div class="col-sm-4">
                                {!! Form::text('loading_date', $good_loading->loading_date, array('class' => 'form-control')) !!}
                            </div>
                        </div>
                        <div class="form-group">
                            {!! Form::label('payment', 'Jenis Pembayaran', array('class' => 'col-sm-2 left control-label')) !!}
                            <div class="col-sm-4">
                                {!! Form::select('payment', getLoadingPaymentType(), $good_loading->payment, ['class' => 'form-control select2', 'style'=>'width: 100%', 'id' => 'payment']) !!}
                            </div>
                        </div>
                        <div class="form-group">
                            {!! Form::label('note', 'Catatan', array('class' => 'col-sm-2 left control-label')) !!}
                            <div class="col-sm-4">
                                {!! Form::text('note', null, array('class' => 'form-control')) !!}
                            </div>
                        </div>
                        <div class="form-group">
                            {!! Form::label('checker', 'PIC Check Barang', array('class' => 'col-sm-2 control-label')) !!}
                            <div class="col-sm-4">
                                {!! Form::text('checker', null, array('class' => 'form-control')) !!}
                            </div>
                        </div>
                    </div>

                    <div class="form-group col-sm-12" style="overflow-x:scroll">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <th>No</th>
                                <th>Barcode</th>
                                <th>Nama</th>
                                <th>Satuan</th>
                                <th>Jumlah Input</th>
                                @if(\Auth::user()->role == 'supervisor')
                                    <th>Harga Beli</th>
                                    <th>Total Harga</th>
                                    <th>Harga Jual</th>
                                @endif
                                <!-- <th>Hapus</th> -->
                            </thead>
                            <tbody>
                                <?php $i = 1 ?>
                                <input type="hidden" name="change" id="change">
                                @foreach($good_loading->detailsWithDeleted() as $detail)
                                    <tr id="row-data-{{ $i }}">
                                        <td>{{ $i++ }}</td>
                                        <td>
                                            <input type="hidden" name="ids[]" id="id-{{ $i }}" value="{{ $detail->id }}">
                                            <textarea type="text" name="barcodes[]" class="form-control" id="barcode-{{ $i }}" style="height: 70px">{{ $detail->good_unit->good->code }}</textarea>
                                        </td>
                                        <td width="20%">
                                            {!! Form::textarea('name_temps[]', $detail->good_unit->good->name, array('class' => 'form-control', 'id' => 'name_temp-'.$i, 'style' => 'height: 70px', 'onchange' => 'changeLists(' . $i . ')')) !!}
                                        </td>
                                        <td>
                                            {!! Form::textarea('units[]', $detail->good_unit->unit->name, array('class' => 'form-control', 'id' => 'unit-'.$i, 'style' => 'height: 70px', 'readonly' => 'readonly')) !!}</td>
                                        <td>
                                            <textarea type="text" name="quantities[]" class="form-control" id="quantity-{{ $i }}"
                                                onchange="editPrice('{{ $i }}')" onkeypress="editPrice('{{ $i }}')">{{ $detail->quantity }}</textarea>
                                        </td>
                                        <td @if(\Auth::user()->role != 'supervisor') style="display: none" @endif>
                                             <textarea type="text" name="prices[]" class="form-control" id="price-{{ $i }}" onchange="editBuyPrice('{{ $i }}')">{{ $detail->price }}</textarea>
                                        </td>
                                        <td @if(\Auth::user()->role != 'supervisor') style="display: none" @endif>
                                            {!! Form::textarea('total_prices[]', $detail->price * $detail->quantity, array('class' => 'form-control', 'readonly' => 'readonly', 'id' => 'total_price-'.$i, 'style' => 'height: 70px')) !!}
                                        </td>
                                        <td @if(\Auth::user()->role != 'supervisor') style="display: none" @endif>
                                            {!! Form::textarea('sell_prices[]', $detail->selling_price, array('class' => 'form-control', 'id' => 'sell_price-'.$i, 'style' => 'height: 70px')) !!}
                                        </td>
                                        <!-- <td><i class="fa fa-times red" id="delete-{{ $i }}" onclick="deleteItem('{{ $i }}')"></i></td> -->
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="form-group" @if(\Auth::user()->role != 'supervisor') style="display: none" @endif>
                        {!! Form::label('total_item_price', 'Total Harga', array('class' => 'col-sm-3 control-label')) !!}
                        <div class="col-sm-3">
                            {!! Form::text('total_item_price', null, array('class' => 'form-control', 'readonly' => 'readonly', 'id'
                            => 'total_item_price')) !!}
                        </div>
                    </div>
                    {{ method_field('PUT') }}
                    <div onclick="event.preventDefault(); submitForm();" class='btn btn-success btn-flat btn-block form-control'>{{ $default['page_name'] }}</div>
                    {!! Form::close() !!}
                    Total item = {{ $i-1 }}<br>
                    Total qty = {{ $good_loading->detailsWithDeleted()->sum('quantity') }}

                </div>
            </div>
        </div>
    </div>
</div>
    </section>
</div>

<style type="text/css">
    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background-color: rgb(60, 141, 188) !important;
    }
</style>
@section('js-addon')
    <script type="text/javascript">
        
          $(document).ready (function (){
              $('.select2').select2();
                $('#loading_date').datepicker({
                    autoclose: true,
                    format: 'yyyy-mm-dd',
                    todayHighlight: true
                });
          });

          function changeLists($id)
          {
            change = $("#change").val();
            if(change.includes(';' + $id + ';') == false)
            {
                change += $id + ';';
            }
            $("#change").val(change);
          }

          function changeFocus(index)
          {
              $("#barcode-" + index).focus();
          }

          function changeTotal()
          {
              total_item_price = parseInt(0);
              total_item = parseInt('{{ sizeof($good_loading->detailsWithDeleted()) }}');
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
              if($("#payment").val() == "0000")
              {
                alert('Silahkan cek kembali jenis pembayaran')
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

          function editBuyPrice(index)
          {
            //change all buy price
            changeLists(index);
            if(document.getElementById("base_qty-" + index) != null)
            {
                good_id = document.getElementById("barcode-" + index).value;
                base_buy_price = document.getElementById("price-" + index).value / document.getElementById("base_qty-" + index).value;
                for(i = 1; i <= total_item; i++)
                {
                    if(document.getElementById("barcode-" + i).value == good_id && i != index)
                    {
                        document.getElementById("price-" + i).value = document.getElementById("base_qty-" + i).value * base_buy_price;
                    }
                }
            }

            editPrice(index);
          }

          function editPrice(index)
          {
            changeLists(index);
              document.getElementById("total_price-" + index).value = unFormatNumber(document.getElementById("price-" + index).value) * unFormatNumber(document.getElementById("quantity-" + index).value);

              formatNumber("total_price-" + index);

              changeTotal();
          }

    </script>
@endsection

