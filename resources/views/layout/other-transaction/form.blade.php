<div class="panel-body" style="color: black !important;">
    <div class="row">
        <div class="form-group">
            {!! Form::label('type', 'Tipe Transaksi', array('class' => 'col-sm-12')) !!}
            <div class="col-sm-5">
                @if($SubmitButtonText == 'View')
                    {!! Form::text('type', null, array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                @else
                    <select class="form-control select2" style="width: 100%;" name="type" onchange="showother_payment()" id="type">
                        <div>
                            <option value="box_transaction">Penjualan Kardus</option>
                            <option value="piutang_transaction">Pembayaran Piutang</option>
                            <option value="pulsa_transaction">Penjualan Pulsa/Token Listrik</option>
                        </div>
                    </select>
                @endif
            </div>
        </div>

        <div class="form-group" id='member'>
            {!! Form::label('member_id', 'Member', array('class' => 'col-sm-12')) !!}
            <div class="col-sm-5">
                @if($SubmitButtonText == 'View')
                    {!! Form::text('member', null, array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                @else
                    <select class="form-control select2" style="width: 100%;" name="member_id" id="all_member">
                        <div>
                            <option value="null">Non Member</option>
                            @foreach(getMembers() as $member)
                            <option value="{{ $member->id }}">
                                {{ $member->name . ' (' . $member->address . ')'}}</option>
                            @endforeach
                        </div>
                    </select>
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
                    <a href="{{ url($role . '/other-transaction/' . $other_transaction->id . '/edit') }}" class="btn form-control">Ubah Data Transaksi Lain</a>
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
            @if($SubmitButtonText != 'View')
                $("#member").hide();
            @endif
        });

        function showother_payment()
        {
            selectBox = document.getElementById("type");
            if(selectBox.options[selectBox.selectedIndex].value == 'piutang_transaction')
                $("#member").show();
            else
            {
                $("#member").hide();
            }
        }
    </script>
@endsection