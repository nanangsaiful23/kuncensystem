<div class="panel-body">
    <div class="row">
        <div class="col-sm-12">
            <div class="form-group">
                <div class="col-sm-8">
                    <input name="is_profile_picture" type="checkbox" value="1" checked="checked"> Jadikan gambar utama
                </div>
            </div>
        </div>

        <div class="col-sm-12">
            <div class="form-group">
                {!! Form::label('file', 'File', array('class' => 'col-sm-1 control-label')) !!}
                <div class="col-sm-3">
                    {!! Form::file('file', NULL, array('class' => 'form-control')) !!}
                </div>
            </div>
        </div>
    </div>
</div>

{{ csrf_field() }}

<hr>
@if($SubmitButtonText == 'Edit')
    {!! Form::submit($SubmitButtonText, ['class' => 'btn btn-warning btn-flat btn-block form-control'])  !!}
@elseif($SubmitButtonText == 'Tambah')
    {!! Form::submit($SubmitButtonText, ['class' => 'btn btn-success btn-flat btn-block form-control'])  !!}
@elseif($SubmitButtonText == 'View')
@endif

{!! Form::close() !!}

@section('js-addon')
@endsection