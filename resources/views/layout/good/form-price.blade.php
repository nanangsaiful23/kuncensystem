<div class="panel-body" style="color: black !important;">
    <div class="row">
        @foreach($good->good_units as $unit)
            {{ Form::hidden('good_unit_ids[]', $unit->id) }}
            {{ Form::hidden('qtys[]', $unit->unit->quantity, array('id' => 'qty-' . $unit->id)) }}
            <div class="form-group">
                {!! Form::label('units[]', 'Satuan', array('class' => 'col-sm-12')) !!}
                <div class="col-sm-5">
                    {!! Form::text('units[]', $unit->unit->name, array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                </div>
            </div>
            
            <div class="form-group">
                {!! Form::label('old_buy_prices[]', 'Harga Beli Lama', array('class' => 'col-sm-12')) !!}
                <div class="col-sm-5" style="background-color: yellow;">
                    {!! Form::text('old_buy_prices[]', showRupiah($unit->buy_price), array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                </div>
            </div>
            
            <div class="form-group">
                {!! Form::label('buy_prices[]', 'Harga Beli Baru', array('class' => 'col-sm-12')) !!}
                <div class="col-sm-5" style="background-color: yellow;">
                    @if($SubmitButtonText == 'View')
                        {!! Form::text('buy_prices[]', null, array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                    @else
                        <input type="text" name="buy_prices[]" class="form-control" id="buy_price-{{ $unit->id}}" required="required" onkeyup="formatNumber('buy_price-{{ $unit->id}}')" onchange="changeBuyPrice('{{ $unit->id}}')">
                    @endif
                </div>
            </div>
            
            <div class="form-group">
                {!! Form::label('old_selling_prices[]', 'Harga Jual Lama', array('class' => 'col-sm-12')) !!}
                <div class="col-sm-5">
                    {!! Form::text('old_selling_prices[]', showRupiah($unit->selling_price), array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                </div>
            </div>
            
            <div class="form-group">
                {!! Form::label('selling_prices[]', 'Harga Jual Baru', array('class' => 'col-sm-12')) !!}
                <div class="col-sm-5">
                    @if($SubmitButtonText == 'View')
                        {!! Form::text('selling_prices[]', null, array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                    @else
                        <input type="text" name="selling_prices[]" class="form-control" id="selling_price-{{ $unit->id}}" required="required" onkeyup="formatNumber('selling_price-{{ $unit->id}}')">
                    @endif
                </div>
            </div>
        @endforeach
            <div class="form-group">
                {!! Form::label('reason', 'Alasan', array('class' => 'col-sm-12')) !!}
                <div class="col-sm-5">
                    @if($SubmitButtonText == 'View')
                        {!! Form::text('reason', null, array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                    @else
                        {!! Form::text('reason', null, array('class' => 'form-control')) !!}
                    @endif
                </div>
            </div>

            <div class="form-group">
                {{ csrf_field() }}

                <div class="col-sm-5">
                    <hr>
                    @if($SubmitButtonText == 'Edit')
                        {!! Form::submit($SubmitButtonText, ['class' => 'btn form-control'])  !!}
                    @elseif($SubmitButtonText == 'Tambah')
                        {!! Form::submit($SubmitButtonText, ['class' => 'btn form-control'])  !!}
                    @endif
                </div>
            </div>
    </div>
</div>

{!! Form::close() !!}

@section('js-addon')
    <script type="text/javascript">
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

        function changeBuyPrice(unit_id)
        {
            price = unFormatNumber($('#buy_price-' + unit_id).val());
            unit  = $('#qty-' + unit_id).val(); 
            base_price = price / unit;
            // console.log($('#buy_price-' + unit_id).val() + " " + $('#qty-' + unit_id).val() + "  " + base_price);

            @foreach($good->good_units as $unit)
                $('#buy_price-{{ $unit->id }}').val(base_price * $('#qty-{{ $unit->id }}').val());
                formatNumber('buy_price-{{ $unit->id }}');
            @endforeach
        }
    </script>
@endsection