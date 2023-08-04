<div class="panel-body" style="color: black !important;">
    <div class="row">
        <div class="form-group">
            {!! Form::label('debit_account_id', 'Jenis Biaya', array('class' => 'col-sm-12')) !!}
            <div class="col-sm-5">
                @if($SubmitButtonText == 'View')
                    {!! Form::text('debit_account_id', null, array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                @else
                    @if(\Auth::user()->email == 'admin')
                        {!! Form::select('debit_account_id', getOtherPayment(), null, ['class' => 'form-control select2', 'style'=>'width: 100%']) !!}
                    @else
                        <select class="form-control select2" style="width: 100%;" name="debit_account_id">
                            <div>
                                <option value="5220">Biaya Operasional Toko</option>
                            </div>
                        </select>
                    @endif
                @endif
            </div>
        </div>

        <div class="form-group">
            {!! Form::label('payment', 'Jenis Pembayaran', array('class' => 'col-sm-12')) !!}
            <div class="col-sm-5">
                @if($SubmitButtonText == 'View')
                    {!! Form::text('payment', null, array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                @else
                    <select class="form-control select2" style="width: 100%;" name="payment">
                        <div>
                            <option value="cash">Tunai/Cash</option>
                            <option value="transfer">Transfer</option>
                        </div>
                    </select>
                @endif
            </div>
        </div>

        <div class="form-group">
            {!! Form::label('notes', 'Keterangan', array('class' => 'col-sm-12')) !!}
            <div class="col-sm-5">
                @if($SubmitButtonText == 'View')
                    {!! Form::text('notes', null, array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                @else
                    {!! Form::text('notes', null, array('class' => 'form-control')) !!}
                @endif
            </div>
        </div>

        <div class="form-group">
            {!! Form::label('money', 'Jumlah Uang', array('class' => 'col-sm-12')) !!}
            <div class="col-sm-5">
                @if($SubmitButtonText == 'View')
                    {!! Form::text('money', null, array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                @else
                    {!! Form::text('money', null, array('class' => 'form-control', 'onkeyup' => 'formatNumber("money")')) !!}
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
                @elseif($SubmitButtonText == 'View')
                    <a href="{{ url($role . '/other-payment/' . $other_payment->id . '/edit') }}" class="btn form-control">Ubah Data Transaksi Lain</a>
                @endif
            </div>
        </div>
    </div>
</div>

{!! Form::close() !!}

@section('js-addon')
    <script type="text/javascript">
        $(document).ready (function (){
            $('.select2').select2();
        });

        function formatNumber(name)
        {
            num = document.getElementById(name).value;
            num = num.toString().replace(/,/g,'');
            document.getElementById(name).value = num.toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,');
        }
    </script>
@endsection