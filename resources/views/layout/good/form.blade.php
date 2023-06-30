<div class="panel-body" style="color: black !important;">
    <div class="row">
        <div class="form-group">
            {!! Form::label('category_id', 'Kategori', array('class' => 'col-sm-12')) !!}
            <div class="col-sm-5">
                @if($SubmitButtonText == 'View')
                    {!! Form::text('category', null, array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                @else
                    {!! Form::select('category_id', getCategories(), $good->category_id, ['class' => 'form-control select2',
                    'style'=>'width: 100%', 'id' => 'brand_id']) !!}
                @endif
            </div>
        </div>

        <div class="form-group">
            {!! Form::label('brand_id', 'Brand', array('class' => 'col-sm-12')) !!}
            <div class="col-sm-5">
                @if($SubmitButtonText == 'View')
                    {!! Form::text('brand', null, array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                @else
                    {!! Form::select('brand_id', getBrands(), $good->brand_id, ['class' => 'form-control select2',
                    'style'=>'width: 100%', 'id' => 'brand_id']) !!}
                @endif
            </div>
        </div>

        <div class="form-group">
            {!! Form::label('code', 'Kode/Barcode Barang', array('class' => 'col-sm-12')) !!}
            <div class="col-sm-5">
                @if($SubmitButtonText == 'View')
                    {!! Form::text('code', null, array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                @else
                    {!! Form::text('code', null, array('class' => 'form-control')) !!}
                @endif
            </div>
        </div>

        <div class="form-group">
            {!! Form::label('name', 'Nama Barang', array('class' => 'col-sm-12')) !!}
            <div class="col-sm-5">
                @if($SubmitButtonText == 'View')
                    {!! Form::text('name', null, array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                @else
                    {!! Form::text('name', null, array('class' => 'form-control')) !!}
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
                    <a href="{{ url($role . '/good/' . $good->id . '/edit') }}" class="btn form-control">Ubah Data Barang</a>
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