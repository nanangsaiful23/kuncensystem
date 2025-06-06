<div class="panel-body" style="color: black !important;">
    <div class="row">
        <div class="form-group">
            {!! Form::label('name', 'Nama Member', array('class' => 'col-sm-12')) !!}
            <div class="col-sm-5">
                @if($SubmitButtonText == 'View')
                    {!! Form::text('name', null, array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                @else
                    {!! Form::text('name', null, array('class' => 'form-control')) !!}
                @endif
            </div>
        </div>

        <div class="form-group">
            {!! Form::label('address', 'Alamat', array('class' => 'col-sm-12')) !!}
            <div class="col-sm-5">
                @if($SubmitButtonText == 'View')
                    {!! Form::text('address', null, array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                @else
                    {!! Form::text('address', null, array('class' => 'form-control')) !!}
                @endif
            </div>
        </div>

        <div class="form-group">
            {!! Form::label('phone_number', 'No Telephone', array('class' => 'col-sm-12')) !!}
            <div class="col-sm-5">
                @if($SubmitButtonText == 'View')
                    {!! Form::text('phone_number', null, array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                @else
                    {!! Form::text('phone_number', null, array('class' => 'form-control')) !!}
                @endif
            </div>
        </div>

        @if(\Auth::user()->email == 'admin')
            <div class="form-group">
                {!! Form::label('store_name', 'Nama Toko', array('class' => 'col-sm-12')) !!}
                <div class="col-sm-5">
                    @if($SubmitButtonText == 'View')
                        {!! Form::text('store_name', null, array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                    @else
                        {!! Form::text('store_name', null, array('class' => 'form-control')) !!}
                    @endif
                </div>
            </div>

            <div class="form-group">
                {!! Form::label('store_address', 'Alamat Toko', array('class' => 'col-sm-12')) !!}
                <div class="col-sm-5">
                    @if($SubmitButtonText == 'View')
                        {!! Form::text('store_address', null, array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                    @else
                        {!! Form::text('store_address', null, array('class' => 'form-control')) !!}
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
                        <a href="{{ url($role . '/member/' . $member->id . '/edit') }}" class="btn form-control">Ubah Data Member</a>
                        <a href="{{ url($role . '/member/' . $member->id . '/showQrCode') }}" target="_blank()" class="btn btn-warning form-control" style="margin-top: 20px">QR Code</a>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>

{!! Form::close() !!}

@section('js-addon')
@endsection