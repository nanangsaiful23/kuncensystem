<div class="panel-body" style="color: black !important;">
    <div class="row">
        <div class="form-group">
            {!! Form::label('name', 'Nama Akun', array('class' => 'col-sm-12')) !!}
            <div class="col-sm-5">
                @if($SubmitButtonText == 'View')
                    {!! Form::text('name', null, array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                @else
                    {!! Form::text('name', null, array('class' => 'form-control')) !!}
                @endif
            </div>
        </div>

        <div class="form-group">
            {!! Form::label('balance', 'Saldo Awal', array('class' => 'col-sm-12')) !!}
            <div class="col-sm-5">
                @if($SubmitButtonText == 'View')
                    {!! Form::text('balance', null, array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                @else
                    {!! Form::text('balance', null, array('class' => 'form-control')) !!}
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
                    <a href="{{ url($role . '/account/' . $account->id . '/edit') }}" class="btn form-control">Ubah Data Akun</a>
                @endif
            </div>
        </div>
    </div>
</div>

{!! Form::close() !!}

@section('js-addon')
@endsection