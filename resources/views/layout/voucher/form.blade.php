<div class="panel-body" style="color: black !important;">
    <div class="row">
        <div class="form-group">
            {!! Form::label('code', 'Kode', array('class' => 'col-sm-12')) !!}
            <div class="col-sm-5">
                @if($SubmitButtonText == 'View')
                    {!! Form::text('code', null, array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                @else
                    {!! Form::text('code', null, array('class' => 'form-control')) !!}
                @endif
            </div>
        </div>

        <div class="form-group">
            {!! Form::label('name', 'Nama', array('class' => 'col-sm-12')) !!}
            <div class="col-sm-5">
                @if($SubmitButtonText == 'View')
                    {!! Form::text('name', null, array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                @else
                    {!! Form::text('name', null, array('class' => 'form-control')) !!}
                @endif
            </div>
        </div>

        <div class="form-group">
            {!! Form::label('start_period', 'Start Period', array('class' => 'col-sm-12')) !!}
            <div class="col-sm-5">
                @if($SubmitButtonText == 'View')
                    {!! Form::text('start_period', null, array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                @else
                    <div class="input-group date">
                        <input type="text" class="form-control" name="start_period" id="start_period" style="word-wrap: break-word;">
                    </div>
                @endif
            </div>
        </div>

        <div class="form-group">
            {!! Form::label('end_period', 'End Period', array('class' => 'col-sm-12')) !!}
            <div class="col-sm-5">
                @if($SubmitButtonText == 'View')
                    {!! Form::text('end_period', null, array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                @else
                    <div class="input-group date">
                        <input type="text" class="form-control" name="end_period" id="end_period" style="word-wrap: break-word;">
                    </div>
                @endif
            </div>
        </div>

        <div class="form-group">
            {!! Form::label('quota', 'Quota', array('class' => 'col-sm-12')) !!}
            <div class="col-sm-3">
                @if($SubmitButtonText == 'View')
                    {!! Form::text('quota', null, array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                @else
                    {!! Form::text('quota', null, array('class' => 'form-control')) !!}
                @endif
            </div>
        </div>

        <div class="form-group">
            {!! Form::label('type', 'Tipe Voucher', array('class' => 'col-sm-12')) !!}
            <div class="col-sm-3">
                @if($SubmitButtonText == 'View')
                    {!! Form::text('type', null, array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                @else
                    <select class="form-control select2" style="width: 100%;" name="type">
                        <div>
                            <option value="discount">Diskon (dalam bentuk persen)</option>
                            <option value="cashback">Potongan (dalam bentuk rupiah)</option>
                        </div>
                    </select>
                @endif
            </div>
        </div>

        <div class="form-group">
            {!! Form::label('nominal', 'Nominal', array('class' => 'col-sm-12')) !!}
            <div class="col-sm-3">
                @if($SubmitButtonText == 'View')
                    {!! Form::text('nominal', null, array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                @else
                    {!! Form::text('nominal', null, array('class' => 'form-control')) !!}
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
                    <a href="{{ url($role . '/voucher/' . $voucher->id . '/edit') }}" class="btn form-control">Ubah Data Voucher</a>
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
                $('#start_period').datepicker({
                    autoclose: true,
                    format: 'yyyy-mm-dd',
                    todayHighlight: true
                });
                $('#end_period').datepicker({
                    autoclose: true,
                    format: 'yyyy-mm-dd',
                    todayHighlight: true
                });
          });
</script>
@endsection