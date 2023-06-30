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

        <div class="form-group" id='payment'>
            {!! Form::label('payment', 'Jenis Pembayaran', array('class' => 'col-sm-12')) !!}
            <div class="col-sm-5">
                @if($SubmitButtonText == 'View')
                    {!! Form::text('payment', null, array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                @else
                    <select class="form-control select2" style="width: 100%;" name="payment">
                        <div>
                            <option value="cash">Tunai/Cash</option>
                            <option value="transfer">Transfer</option>
                        </div>
                    </select>
                @endif
            </div>
        </div>

        <div class="form-group" id="buy_price_div">
            {!! Form::label('buy_price', 'Harga Beli Pulsa/Token', array('class' => 'col-sm-12')) !!}
            <div class="col-sm-5">
                @if($SubmitButtonText == 'View')
                    {!! Form::text('buy_price', null, array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                @else
                    {!! Form::text('buy_price', null, array('class' => 'form-control', 'onkeyup' => 'formatNumber("buy_price")')) !!}
                @endif
            </div>
        </div>

        <div class="form-group">
            {!! Form::label('money', 'Harga Jual/Nominal', array('class' => 'col-sm-12')) !!}
            <div class="col-sm-5">
                @if($SubmitButtonText == 'View')
                    {!! Form::text('money', null, array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                @else
                    {!! Form::text('money', null, array('class' => 'form-control', 'onkeyup' => 'formatNumber("money")')) !!}
                @endif
            </div>
        </div>

        <div class="form-group" id="no_token">
            {!! Form::label('no_token', 'No Token Listrik/Nomor HP', array('class' => 'col-sm-12')) !!}
            <div class="col-sm-5">
                @if($SubmitButtonText == 'View')
                    {!! Form::text('no_token', null, array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                @else
                    {!! Form::text('no_token', null, array('class' => 'form-control')) !!}
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
                $("#payment").hide();
                $("#buy_price_div").hide();
                $("#no_token").hide();
            @endif
        });

        function showother_payment()
        {
            selectBox = document.getElementById("type");
            if(selectBox.options[selectBox.selectedIndex].value == 'piutang_transaction')
            {
                $("#member").show();
                $("#payment").show();
                $("#buy_price_div").hide();
                $("#no_token").hide();
            }
            else if(selectBox.options[selectBox.selectedIndex].value == 'pulsa_transaction')
            {
                $("#member").hide();
                $("#payment").show();
                $("#buy_price_div").show();
                $("#no_token").show();
            }
            else
            {
                $("#member").hide();
                $("#payment").hide();
                $("#buy_price_div").hide();
                $("#no_token").hide();
            }
        }


        function formatNumber(name)
        {
            num = document.getElementById(name).value;
            num = num.toString().replace(/,/g,'');
            document.getElementById(name).value = num.toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,');
        }
    </script>
@endsection