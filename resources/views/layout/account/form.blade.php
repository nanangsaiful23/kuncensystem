<div class="panel-body" style="color: black !important;">
    <div class="row">
        <div class="form-group">
            {!! Form::label('code', 'Kode Akun', array('class' => 'col-sm-12')) !!}
            <div class="col-sm-5">
                @if($SubmitButtonText == 'View')
                    {!! Form::text('code', null, array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                @else
                    {!! Form::text('code', null, array('class' => 'form-control')) !!}
                @endif
            </div>
        </div>

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
            {!! Form::label('type', 'Tipe Akun', array('class' => 'col-sm-12')) !!}
            <div class="col-sm-5">
                @if($SubmitButtonText == 'View')
                    {!! Form::text('type', null, array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                @else
                    <select class="form-control select2" style="width: 100%;" name="type">
                        <div>
                            <option value="Debet">Debet</option>
                            <option value="Kredit">Kredit</option>
                        </div>
                    </select>
                @endif
            </div>
        </div>

        <div class="form-group">
            {!! Form::label('group', 'Grup Akun', array('class' => 'col-sm-12')) !!}
            <div class="col-sm-5">
                @if($SubmitButtonText == 'View')
                    {!! Form::text('group', null, array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                @else
                    <select class="form-control select2" style="width: 100%;" name="group">
                        <div>
                            <option value="Neraca">Neraca</option>
                            <option value="Laba Rugi">Laba Rugi</option>
                        </div>
                    </select>
                @endif
            </div>
        </div>

        <div class="form-group">
            {!! Form::label('activa', 'Tipe Akun', array('class' => 'col-sm-12')) !!}
            <div class="col-sm-5">
                @if($SubmitButtonText == 'View')
                    {!! Form::text('activa', null, array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                @else
                    <select class="form-control select2" style="width: 100%;" name="activa">
                        <div>
                            <option value="Aktiva">Aktiva</option>
                            <option value="Pasiva">Pasiva</option>
                        </div>
                    </select>
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