<div class="content-wrapper">
  @include('layout' . '.error')

  <section class="content">
    <div class="row">
      <div class="col-xs-12">
        <div class="box">
          <div class="box-header">
            <h3 class="box-title"> {{ $default['page_name'] }}</h3>
          </div>

          {!! Form::model($stock_opname, array('class' => 'form-horizontal')) !!}
            <div class="box-body">
                <div class="panel-body">
                    <div class="row">
                        <div class="form-group">
                            {!! Form::label('created_at', 'Tanggal', array('class' => 'col-sm-2 control-label')) !!}
                            <div class="col-sm-4">
                                {!! Form::text('created_at', displayDate($stock_opname->created_at), array('class' => 'form-control', 'readonly' => 'readonly')) !!}
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
                            {!! Form::label('total', 'Total', array('class' => 'col-sm-2 control-label')) !!}
                            <div class="col-sm-4">
                                {!! Form::text('total', showRupiah($stock_opname->total), array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                            </div>
                        </div>
                    </div>

                    <div class="form-group col-sm-12" style="overflow-x:scroll">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <th>Barcode</th>
                                <th>Nama</th>
                                <th>Satuan</th>
                                <th>Stock Lama</th>
                                <th>Stock Baru</th>
                                <th>Total</th>
                            </thead>
                            <tbody>
                                @foreach($stock_opname->stock_opname_details() as $detail)
                                    <tr @if($detail->good_unit->good->deleted_at != null) style="background-color: red" @endif>
                                        <td>
                                            <a href="{{ url($role . '/good/' . $detail->good_unit->good->id . '/loading/2023-01-01/' . date('Y-m-d') . '/10') }}" class="btn" target="_blank()">
                                            {{ $detail->good_unit->good->code }}</a>
                                        </td>
                                        <td>
                                            {{ $detail->good_unit->good->name }}
                                        </td>
                                        <td>
                                            {{ $detail->good_unit->unit->name }}
                                        </td>
                                        <td>
                                            {{ $detail->old_stock }}
                                        </td>
                                        <td>
                                            {{ $detail->new_stock }}
                                        </td>
                                        <td style="text-align: right;">
                                            {{ showRupiah($detail->total) }}
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
