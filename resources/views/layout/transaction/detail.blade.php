<div class="content-wrapper">
  @include('layout' . '.error')

  <section class="content">
    <div class="row">
      <div class="col-xs-12">
        <div class="box">
          <div class="box-header">
            <h3 class="box-title"> Form Detail Transaksi {{ $transaction->created_at }}</h3>
          </div>

          {!! Form::model($transaction, array('class' => 'form-horizontal')) !!}
            <div class="box-body">
                <div class="panel-body">
                    <div class="row">
                        <div class="form-group">
                            {!! Form::label('actor', 'PIC', array('class' => 'col-sm-2 control-label')) !!}
                            <div class="col-sm-4">
                                {!! Form::text('actor', $transaction->actor()->name, array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                            </div>
                        </div>
                        <div class="form-group">
                            {!! Form::label('total_item_price', 'Total Harga', array('class' => 'col-sm-2 control-label')) !!}
                            <div class="col-sm-4">
                                {!! Form::text('total_item_price', showRupiah($transaction->total_item_price), array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                            </div>
                        </div>
                        <div class="form-group">
                            {!! Form::label('total_discount_price', 'Total Diskon', array('class' => 'col-sm-2 control-label')) !!}
                            <div class="col-sm-4">
                                {!! Form::text('total_discount_price', showRupiah($transaction->details->sum('discount_price')), array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                            </div>
                        </div>
                        <div class="form-group">
                            {!! Form::label('total_discount_price', 'Total Potongan Akhir', array('class' => 'col-sm-2 control-label')) !!}
                            <div class="col-sm-4">
                                {!! Form::text('total_discount_price', showRupiah($transaction->total_discount_price), array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                            </div>
                        </div>
                        <div class="form-group">
                            {!! Form::label('total_sum_price', 'Total Akhir', array('class' => 'col-sm-2 control-label')) !!}
                            <div class="col-sm-4">
                                {!! Form::text('total_sum_price', showRupiah($transaction->total_sum_price), array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                            </div>
                        </div>
                        <div class="form-group">
                            {!! Form::label('money_paid', 'Total Uang', array('class' => 'col-sm-2 control-label')) !!}
                            <div class="col-sm-4">
                                {!! Form::text('money_paid', showRupiah($transaction->money_paid), array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                            </div>
                        </div>
                        <div class="form-group">
                            {!! Form::label('money_returned', 'Kembalian', array('class' => 'col-sm-2 control-label')) !!}
                            <div class="col-sm-4">
                                {!! Form::text('money_returned', showRupiah($transaction->money_returned), array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                            </div>
                        </div>
                    </div>

                    <div class="form-group col-sm-12" style="overflow-x:scroll">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <th>Barcode</th>
                                <th>Nama</th>
                                <th>Jumlah</th>
                                @if(\Auth::user()->email == 'admin')
                                    <th>Harga Beli</th> 
                                @endif
                                <th>Harga Jual</th>
                                <th>Total Diskon</th>
                                <th>Total Akhir</th>
                            </thead>
                            <tbody>
                                @foreach($transaction->details as $detail)
                                    <tr @if($detail->type == 'retur') style="background-color: yellow" @endif>
                                        <td>
                                            {{ $detail->good_unit->good->code }}
                                        </td>
                                        <td>
                                            {{ $detail->good_unit->good->name . ' ' . $detail->good_unit->unit->name }}
                                        </td>
                                        <td>
                                            {{ $detail->quantity }}
                                        </td>
                                        @if(\Auth::user()->email == 'admin')
                                            <td style="text-align: right;">
                                                {{ showRupiah($detail->buy_price) }}
                                            </td>
                                        @endif
                                        <td style="text-align: right;">
                                            {{ showRupiah($detail->selling_price) }}
                                        </td>
                                        <td style="text-align: right;">
                                            {{ showRupiah($detail->discount_price) }}
                                        </td>
                                        <td style="text-align: right;">
                                            @if($detail->type == 'retur') - @endif {{ showRupiah($detail->sum_price) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {!! Form::close() !!}

                </div>
            </div>
        </div>
    </div>
</div>
    </section>
</div>

<style type="text/css">
    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background-color: rgb(60, 141, 188) !important;
    }
</style>
@section('js-addon')
@endsection
