<div class="panel-body" style="color: black !important;">
    <div class="row">
        <div class="form-group">
            {!! Form::label('good_id', 'Barang', array('class' => 'col-sm-12')) !!}
            <div class="col-sm-5">
                @if($SubmitButtonText == 'View')
                @else
                    {!! Form::select('good_id', getGoodLists(), null, ['class' => 'form-control select2','required'=>'required', 'style'=>'width:100%', 'id' => 'unit']) !!}
                @endif
            </div>
        </div>

        <div class="form-group">
            {!! Form::label('distributor_id', 'Distributor', array('class' => 'col-sm-12')) !!}
            <div class="col-sm-5">
                @if($SubmitButtonText == 'View')
                @else
                    {!! Form::select('distributor_id', getDistributorLists(), null, ['class' => 'form-control select2','required'=>'required', 'style'=>'width:100%', 'id' => 'unit']) !!}
                @endif
            </div>
        </div>

        <div class="form-group">
            {!! Form::label('quantity', 'Jumlah Barang', array('class' => 'col-sm-12')) !!}
            <div class="col-sm-5">
                @if($SubmitButtonText == 'View')
                    {!! Form::text('quantity', null, array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                @else
                    {!! Form::text('quantity', null, array('class' => 'form-control')) !!}
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
                @endif
            </div>
        </div>
    </div>
</div>

{!! Form::close() !!}

@section('js-addon')
  <script type="text/javascript">
    $(document).ready(function(){
      $('.select2').select2();

    });
</script>
@endsection