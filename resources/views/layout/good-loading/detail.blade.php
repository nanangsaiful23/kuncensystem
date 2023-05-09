<div class="content-wrapper">
  @include('layout' . '.error')

  <section class="content">
    <div class="row">
      <div class="col-xs-12">
        <div class="box">
          <div class="box-header">
            <h3 class="box-title"> Form Detail Loading</h3>
          </div>

          {!! Form::model($good_loading, array('class' => 'form-horizontal')) !!}
            <div class="box-body">
                <div class="panel-body">
                    <div class="row">
                        <div class="form-group">
                            {!! Form::label('distributor_id', 'Distributor', array('class' => 'col-sm-2 control-label')) !!}
                            <div class="col-sm-4">
                                {!! Form::text('distributor', $good_loading->distributor->name, array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                            </div>

                        </div>
                        <div class="form-group">
                            {!! Form::label('loading_date', 'Tanggal Pembelian', array('class' => 'col-sm-2 control-label')) !!}
                            <div class="col-sm-4">
                                {!! Form::text('loading_date', displayDate($good_loading->loading_date), array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                            </div>
                        </div>
                        <div class="form-group">
                            {!! Form::label('note', 'Catatan', array('class' => 'col-sm-2 left control-label')) !!}
                            <div class="col-sm-4">
                                {!! Form::text('note', null, array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                            </div>
                        </div>
                        <div class="form-group">
                            {!! Form::label('checker', 'PIC Check Barang', array('class' => 'col-sm-2 control-label')) !!}
                            <div class="col-sm-4">
                                {!! Form::text('checker', null, array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                            </div>
                        </div>
                        <div class="form-group">
                            {!! Form::label('total_item_price', 'Total Harga', array('class' => 'col-sm-2 control-label')) !!}
                            <div class="col-sm-4">
                                {!! Form::text('total_item_price', null, array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                            </div>
                        </div>
                    </div>

                    <div class="form-group col-sm-12" style="overflow-x:scroll">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <th>Barcode</th>
                                <th>Nama</th>
                                <th>Expired</th>
                                <th>Jumlah Input</th>
                                <th>Jumlah Real</th>
                                <th>Harga Beli</th>
                                <th>Total Harga</th>
                                <th>Stock</th>
                                <th>Harga Jual</th>
                            </thead>
                            <tbody>
                                @foreach($good_loading->details as $detail)
                                    <tr>
                                        <td>
                                            {{ $detail->good_unit->good->code }}
                                        </td>
                                        <td>
                                            {{ $detail->good_unit->good->name }}
                                        </td>
                                        <td>
                                            {{ $detail->expiry_date }}
                                        </td>
                                        <td>
                                            {{ $detail->quantity. ' @' . $detail->good_unit->unit->name . ' (' . $detail->good_unit->unit->code . ')' }}
                                        </td>
                                        <td>
                                            {{ $detail->real_quantity . ' ' . $detail->good_unit->unit->base }}
                                        </td>
                                        <td style="text-align: right;">
                                            {{ showRupiah($detail->price) }}
                                        </td>
                                        <td style="text-align: right;">
                                            {{ showRupiah($detail->quantity * $detail->price) }}
                                        </td>
                                        <td>
                                            {{ $detail->last_stock }}
                                        </td>
                                        <td style="text-align: right;">
                                            {{ showRupiah($detail->selling_price) }}
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
