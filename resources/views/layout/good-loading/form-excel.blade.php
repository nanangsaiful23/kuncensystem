<div class="panel-body" style="color: black !important;">
    <div class="row">
        <div class="form-group">
            {!! Form::label('file', 'File', array('class' => 'col-sm-12')) !!}
            <div class="col-sm-5">
                <input type="file" name="file">
            </div>
        </div>

        <div class="form-group">
            {!! Form::label('distributor_id', 'Distributor', array('class' => 'col-sm-12')) !!}
            <div class="col-sm-5">
                @if($SubmitButtonText == 'View')
                    {!! Form::text('distributor', null, array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                @else
                    <input type="text" name="distributor_name" class="form-control" id="distributor_name">
                    <select class="form-control select2" style="width: 100%;" name="distributor_id" id="all_distributor">
                        <div>
                            <option value="null">Silahkan pilih distributor</option>
                            @foreach(getDistributors() as $distributor)
                            <option value="{{ $distributor->id }}">
                                {{ $distributor->name }}</option>
                            @endforeach
                        </div>
                    </select>
                @endif
            </div>
        </div>

        <div class="form-group">
            {!! Form::label('total_item_price', 'Total Harga', array('class' => 'col-sm-12')) !!}
            <div class="col-sm-5">
                {!! Form::text('total_item_price', null, array('class' => 'form-control')) !!}
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
                @endif
            </div>
        </div>
    </div>
</div>

{!! Form::close() !!}

@section('js-addon')
@endsection