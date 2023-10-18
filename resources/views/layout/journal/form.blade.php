<div class="panel-body" style="color: black !important;">
    <div class="row">
        <div class="form-group">
            {!! Form::label('type', 'Tipe Jurnal', array('class' => 'col-sm-12')) !!}
            <div class="col-sm-5">
                @if($SubmitButtonText == 'View')
                    {!! Form::text('type', null, array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                @else
                    {!! Form::select('type', getJournalTypes(), null, ['class' => 'form-control select2',
                    'style'=>'width: 100%']) !!}
                @endif
            </div>
        </div>

        <div class="form-group">
            {!! Form::label('journal_date', 'Tanggal', array('class' => 'col-sm-12')) !!}
            <div class="col-sm-5">
                @if($SubmitButtonText == 'View')
                    {!! Form::text('journal_date', null, array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                @else
                    <div class="input-group date">
                        <input type="text" class="form-control" name="journal_date" id="journal_date" style="word-wrap: break-word;">
                    </div>
                @endif
            </div>
        </div>

        <div class="form-group">
            {!! Form::label('name', 'Nama', array('class' => 'col-sm-12')) !!}
            <div class="col-sm-5">
                @if($SubmitButtonText == 'View')
                    {!! Form::text('name', null, array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                @else
                    {!! Form::text('name', null, array('class' => 'form-control', 'required' => 'required')) !!}
                @endif
            </div>
        </div>

        <div class="form-group">
            {!! Form::label('debit_account_id', 'Akun Debit', array('class' => 'col-sm-12')) !!}
            <div class="col-sm-5">
                @if($SubmitButtonText == 'View')
                    {!! Form::text('debit_account_id', null, array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                @else
                    {!! Form::select('debit_account_id', getAccountJournalLists(), null, ['class' => 'form-control select2',
                    'style'=>'width: 100%']) !!}
                @endif
            </div>
        </div>

        <div class="form-group">
            {!! Form::label('debit', 'Nominal Debit', array('class' => 'col-sm-12')) !!}
            <div class="col-sm-5">
                @if($SubmitButtonText == 'View')
                    {!! Form::text('debit', null, array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                @else
                    {!! Form::text('debit', null, array('class' => 'form-control', 'onkeyup' => 'formatNumber("debit")', 'required' => 'required')) !!}
                @endif
            </div>
        </div>

        <div class="form-group">
            {!! Form::label('credit_account_id', 'Akun Kredit', array('class' => 'col-sm-12')) !!}
            <div class="col-sm-5">
                @if($SubmitButtonText == 'View')
                    {!! Form::text('credit_account_id', null, array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                @else
                    {!! Form::select('credit_account_id', getAccountJournalLists(), null, ['class' => 'form-control select2',
                    'style'=>'width: 100%']) !!}
                @endif
            </div>
        </div>

        <div class="form-group">
            {!! Form::label('credit', 'Nominal Kredit', array('class' => 'col-sm-12')) !!}
            <div class="col-sm-5">
                @if($SubmitButtonText == 'View')
                    {!! Form::text('credit', null, array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                @else
                    {!! Form::text('credit', null, array('class' => 'form-control', 'onkeyup' => 'formatNumber("credit")', 'required' => 'required')) !!}
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
                    <a href="{{ url($role . '/journal/' . $journal->id . '/edit') }}" class="btn form-control">Ubah Data Journal</a>
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
                $('#journal_date').datepicker({
                    autoclose: true,
                    format: 'yyyy-mm-dd',
                    todayHighlight: true
                });
          });

        function formatNumber(name)
        {
            num = document.getElementById(name).value;
            num = num.toString().replace(/,/g,'');
            document.getElementById(name).value = num.toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,');
        }
</script>
@endsection