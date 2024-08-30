<div class="content-wrapper">
  @include('layout' . '.error')

  <section class="content">
    <div class="row">
      <div class="col-xs-12">
        <div class="box">
          <div class="box-header">
            <h3 class="box-title"> {{ $default['page_name'] }}</h3>
          </div>

          {!! Form::model($transaction, array('class' => 'form-horizontal', 'id' => 'loading-form')) !!}
            <div class="box-body">
                <div class="panel-body">
                    <div class="row">
                        <div class="form-group">
                            {!! Form::label('actor', 'PIC', array('class' => 'col-sm-4 control-label')) !!}
                            <div class="col-sm-4">
                                {!! Form::text('actor', $transaction->actor()->name, array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                            </div>
                        </div>
                        <div class="form-group">
                            {!! Form::label('member_id', 'Member', array('class' => 'col-sm-4 control-label')) !!}
                            <div class="col-sm-4">
                                {!! Form::select('member_id', getMemberLists(), $transaction->member_id, ['class' => 'form-control select2', 'style'=>'width: 100%', 'id' => 'member_id']) !!}
                            </div>
                        </div>
                        <div class="form-group">
                            {!! Form::label('note', 'Keterangan', array('class' => 'col-sm-4 control-label')) !!}
                            <div class="col-sm-4">
                                {!! Form::text('note', $transaction->note, array('class' => 'form-control')) !!}
                            </div>
                        </div>
                        <div class="form-group">
                            {!! Form::label('total_item_price', 'Total Harga', array('class' => 'col-sm-4 control-label')) !!}
                            <div class="col-sm-4">
                                {!! Form::text('total_item_price', showRupiah($transaction->total_item_price), array('class' => 'form-control', 'readonly' => 'readonly' ,'id' => 'total_item_price')) !!}
                            </div>
                        </div>
                        <div class="form-group">
                            {!! Form::label('total_discount_items_price', 'Total Potongan Harga Per Barang', array('class' => 'col-sm-4 control-label')) !!}
                            <div class="col-sm-4">
                                {!! Form::text('total_discount_items_price', null, array('class' => 'form-control', 'readonly' => 'readonly', 'id' => 'total_discount_items_price')) !!}
                            </div>
                        </div>
                        <div class="form-group">
                            {!! Form::label('total_discount_price', 'Total Potongan Akhir', array('class' => 'col-sm-4 control-label')) !!}
                            <div class="col-sm-4">
                                {!! Form::text('total_discount_price', ($transaction->total_discount_price), array('class' => 'form-control', 'readonly' => 'readonly','id' => 'total_discount_price')) !!}
                            </div>
                        </div>
                        <div class="form-group">
                            {!! Form::label('total_sum_price', 'Total Akhir', array('class' => 'col-sm-4 control-label')) !!}
                            <div class="col-sm-4">
                                {!! Form::text('total_sum_price', showRupiah($transaction->total_sum_price), array('class' => 'form-control', 'readonly' => 'readonly', 'id' => 'total_sum_price')) !!}
                            </div>
                        </div>
                        <div class="form-group">
                            {!! Form::label('money_paid', 'Total Uang', array('class' => 'col-sm-4 control-label')) !!}
                            <div class="col-sm-4">
                                <input type="text" name="money_paid" class="form-control" id="money_paid" onchange="changeReturn()" onkeypress="changeReturn()" required="required" onkeyup="formatNumber('money_paid')" value="{{ $transaction->money_paid }}">
                            </div>
                        </div>
                        <div class="form-group">
                            {!! Form::label('money_returned', 'Kembalian', array('class' => 'col-sm-4 control-label')) !!}
                            <div class="col-sm-4">
                                {!! Form::text('money_returned', showRupiah($transaction->money_returned), array('class' => 'form-control', 'readonly' => 'readonly', 'id' => 'money_returned')) !!}
                            </div>
                        </div>
                    </div>

                    <div class="form-group col-sm-12" style="overflow-x:scroll">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <th>Barcode</th>
                                <th>Nama</th>
                                <th>Satuan</th>
                                <th>Jumlah</th>
                                @if(\Auth::user()->role == 'supervisor')
                                    <th>Harga Beli</th>
                                    <th>Harga Jual</th>
                                    <th>Total Diskon</th>
                                    <th>Total Akhir</th>
                                @endif
                            </thead>
                            <tbody>
                                <?php $i = 1; ?>
                                <input type="hidden" name="change" id="change">
                                @foreach($transaction->details as $detail)
                                    <tr id="row-data-{{ $i }}">
                                        <td>
                                            <input type="hidden" name="ids[]" id="id-{{ $i }}" value="{{ $detail->id }}">
                                            <textarea type="text" name="barcodes[]" class="form-control" id="barcode-{{ $i }}" style="height: 70px" readonly="readonly">{{ $detail->good_unit->good->code }}</textarea>
                                        </td>
                                        <td width="20%">
                                            {!! Form::textarea('name_temps[]', $detail->good_unit->good->name, array('class' => 'form-control', 'id' => 'name_temp-'.$i, 'readonly' => 'readonly', 'style' => 'height: 70px', 'onchange' => 'changeLists("' . $i . '")')) !!}
                                        </td>
                                        <td>
                                            {!! Form::textarea('units[]', $detail->good_unit->unit->name, array('class' => 'form-control', 'id' => 'unit-'.$i, 'style' => 'height: 70px', 'readonly' => 'readonly')) !!}</td>
                                        <td>
                                            <textarea type="text" name="quantities[]" class="form-control" id="quantity-{{ $i }}"
                                                onchange="editPrice('{{ $i }}')" onkeypress="editPrice('{{ $i }}')">{{ $detail->quantity }}</textarea>
                                        </td>
                                        <td @if(\Auth::user()->role != 'supervisor') style="display: none" @endif>
                                            {!! Form::textarea('buy_prices[]', $detail->buy_price, array('class' => 'form-control', 'id' => 'buy-'.$i, 'style' => 'height: 70px', 'readonly' => 'readonly')) !!}
                                        </td>
                                        <td @if(\Auth::user()->role != 'supervisor') style="display: none" @endif>
                                            {!! Form::textarea('selling_prices[]', $detail->selling_price, array('class' => 'form-control', 'id' => 'selling_price-'.$i, 'style' => 'height: 70px', 'readonly' => 'readonly')) !!}
                                        </td>
                                        <td @if(\Auth::user()->role != 'supervisor') style="display: none" @endif>
                                            {!! Form::textarea('discount_prices[]', $detail->discount_price, array('class' => 'form-control', 'readonly' => 'readonly', 'id' => 'discount_price-'.$i, 'style' => 'height: 70px')) !!}
                                        </td>
                                        <td @if(\Auth::user()->role != 'supervisor') style="display: none" @endif>
                                            {!! Form::textarea('sum_prices[]', $detail->sum_price, array('class' => 'form-control', 'readonly' => 'readonly', 'id' => 'sum_price-'.$i, 'style' => 'height: 70px')) !!}
                                        </td>
                                        <!-- <td><i class="fa fa-times red" id="delete-{{ $i }}" onclick="deleteItem('{{ $i }}')"></i></td> -->
                                    </tr>
                                <?php $i++ ?>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{ method_field('PUT') }}

                </div>
                    <div onclick="event.preventDefault(); submitForm();" class='btn btn-success btn-flat btn-block form-control'>{{ $default['page_name'] }}</div>
                    {!! Form::close() !!}
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
            total_discount_price = parseInt(0);
            total_sum_price = parseInt(0);
            total_discount_items = parseInt(0);
              total_item = parseInt('{{ sizeof($transaction->details) }}');

            for (var i = 1; i <= total_item; i++)
            {
                if(document.getElementById("barcode-" + i))
                {
                    if(document.getElementById("barcode-" + i).value != '')
                    {
                        items = document.getElementById("selling_price-" + i).value * document.getElementById("quantity-" + i).value;

                        total_item_price += parseInt(items);

                        sums = document.getElementById("sum_price-" + i).value;
                        sums = sums.replace(/,/g,'');

                        total_sum_price += parseInt(sums);

                        discount = document.getElementById("discount_price-" + i).value;
                        discount = discount.replace(/,/g,'');

                        total_discount_items += parseInt(discount);
                    }
                }
            }

            console.log(total_sum_price);
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

          function submitForm()
          {
              var isi=true;
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
            changeLists(index);
              document.getElementById("sum_price-" + index).value = (unFormatNumber(document.getElementById("selling_price-" + index).value) * unFormatNumber(document.getElementById("quantity-" + index).value)) - unFormatNumber(document.getElementById("discount_price-" + index).value);

              formatNumber("sum_price-" + index);

              changeTotal();
          }

    </script>
@endsection

