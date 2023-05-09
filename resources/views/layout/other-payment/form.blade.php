<div class="panel-body" style="color: black !important;">
    <div class="row">
        <div class="form-group">
            {!! Form::label('debit_account_id', 'Jenis Biaya', array('class' => 'col-sm-12')) !!}
            <div class="col-sm-5">
                @if($SubmitButtonText == 'View')
                    {!! Form::text('debit_account_id', null, array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                @else
                    {!! Form::select('debit_account_id', getOtherPayment(), null, ['class' => 'form-control select2',
                    'style'=>'width: 100%']) !!}
                @endif
            </div>
        </div>

        <div class="form-group">
            {!! Form::label('money', 'Jumlah Uang', array('class' => 'col-sm-12')) !!}
            <div class="col-sm-5">
                @if($SubmitButtonText == 'View')
                    {!! Form::text('money', null, array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                @else
                    {!! Form::text('money', null, array('class' => 'form-control')) !!}
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

    </script>
@endsection