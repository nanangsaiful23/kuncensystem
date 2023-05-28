<style type="text/css">
  th
  {
    text-align: center;
  }
</style>

<div class="content-wrapper">

  @include('layout' . '.alert-message', ['type' => $default['type'], 'data' => $default['data'], 'color' => $default['color']])

  <section class="content">
    <div class="row">
      <div class="col-xs-12">
        <div class="box">
          <div class="box-header">
            <h3 class="box-title">{{ $default['page_name'] }}</h3>
          </div>
          <div class="box-body" style="overflow-x:scroll">
            <div class="form-group col-sm-12" style="margin-top: 10px;">
              {!! Form::label('location', 'Lokasi', array('class' => 'col-sm-1 control-label')) !!}
              <div class="col-sm-3">
                {!! Form::select('location', getDistributorLocations(), $location, ['class' => 'form-control select2', 'style'=>'width: 100%', 'id' => 'location', 'onchange' => 'advanceSearch()']) !!}
              </div>
              {!! Form::label('category_id', 'Kategori', array('class' => 'col-sm-1 control-label')) !!}
              <div class="col-sm-5">
                {!! Form::select('category_id', getCategories(), $category_id, ['class' => 'form-control select2', 'style'=>'width: 100%', 'id' => 'category_id', 'onchange' => 'advanceSearch()']) !!}
              </div>
            </div>
            <div class="form-group col-sm-12" style="margin-top: 10px;">
              {!! Form::label('stock', 'Stock', array('class' => 'col-sm-1 control-label')) !!}
              <div class="col-sm-3">
                {!! Form::select('stock', ['0' => '0', '3' => '3', '5' => '5', '10' => '10'], $stock, ['class' => 'form-control select2', 'style'=>'width: 100%', 'id' => 'stock', 'onchange' => 'advanceSearch()']) !!}
              </div>
              {!! Form::label('distributor', 'Distributor', array('class' => 'col-sm-1 control-label')) !!}
              <div class="col-sm-5">
                {!! Form::select('distributor_id', getDistributorLists(), $distributor_id, ['class' => 'form-control select2', 'style'=>'width: 100%', 'id' => 'distributor_id', 'onchange' => 'advanceSearch()']) !!}
              </div>
            </div>
          </div>
          <div class="box-body" style="overflow-x:scroll; color: black !important">
            {!! Form::model(old(),array('url' => route($role . '.zeroStock.export'), 'enctype'=>'multipart/form-data', 'method' => 'POST', 'class' => 'form-horizontal')) !!}
              {!! Form::submit('EXPORT', ['class' => 'btn form-control'])  !!}
            <table id="example1" class="table table-bordered table-striped">
              <thead>
              <tr>
                <th>Distributor Terakhir</th>
                <th>Nama</th>
                <th>Loading Terakhir</th>
                <th>Harga Beli Terakhir</th>
                <th>Stock</th>
                <th>Export</th>
                <th>Hapus Barang</th>
              </tr>
              </thead>
              <tbody id="table-good">
                @foreach($goods as $good)
                  <tr>
                    <td>{{ $good->obj->getLastBuy()->good_loading->distributor->name }}</td>
                    <td>{{ $good->obj->name }}</td>
                    <td style="text-align: center;">{{ displayDate($good->obj->getLastBuy()->good_loading->loading_date) }}</td>
                    <td style="text-align: right;">{{ showRupiah($good->obj->getLastBuy()->price) }}</td>
                    <td style="text-align: center;">{{ $good->obj->getStock() }}</td>
                    <td style="text-align: center;">
                      <input type="checkbox" name="exports[]" value="{{ $good->obj->id }}" checked="checked">
                    </td>
                  </form>
                    <td style="text-align: center;">
                      @if($good->obj->getStock() == 0)
                        <button type="button" class="no-btn" data-toggle="modal" data-target="#modal-danger-{{$good->id}}"><i class="fa fa-times red" aria-hidden="true"></i></button>

                        @include('layout' . '.delete-modal', ['id' => $good->obj->id, 'data' => $good->obj->name, 'formName' => 'delete-form-' . $good->obj->id])

                        <form id="delete-form-{{$good->obj->id}}" action="{{ url($role . '/good/' . $good->obj->id . '/delete') }}" method="POST" style="display: none;">
                          {{ csrf_field() }}
                          {{ method_field('DELETE') }}
                        </form>
                      @endif
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
            {!! Form::close() !!}
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

@section('js-addon')
  <script type="text/javascript">
    $(document).ready(function(){
        $('.select2').select2();
        $("#search-input").keyup( function(e){
          if(e.keyCode == 13)
          {
            ajaxFunction();
          }
        });

        $("#search-btn").click(function(){
            ajaxFunction();
        });

    });

    function advanceSearch()
    {
      window.location = window.location.origin + '/{{ $role }}/good/zeroStock/' + $('#category_id').val() + '/' + $('#location').val() + '/' + $('#distributor_id').val() + '/' + $('#stock').val();
    }
  </script>
@endsection