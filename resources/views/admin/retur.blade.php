@extends('layout.user', ['role' => 'admin', 'title' => 'Admin'])

@section('content')
  <style type="text/css">
    table, th, td
    {
      border: solid 2px black !important;
    }
  </style>

  <div class="content-wrapper">
    <section class="content">
      <div class="row">
        <div class="col-xs-12">
          <div class="box">
            <div class="box-header">
              <h3 class="box-title">{{ $default['page_name'] }}</h3>
            </div>
            <div class="box-body">
              {!! Form::label('show', 'Show', array('class' => 'col-sm-1 control-label')) !!}
             <div class="col-sm-1">
                {!! Form::select('show', getPaginations(), $pagination, ['class' => 'form-control', 'style'=>'width: 100%', 'id' => 'show', 'onchange' => 'advanceSearch()']) !!}
              </div>
              {!! Form::label('distributor_id', 'Distributor', array('class' => 'col-sm-1 control-label')) !!}
             <div class="col-sm-2">
                {!! Form::select('distributor_id', getDistributorLists(), $distributor_id, ['class' => 'form-control select2', 'style'=>'width: 100%', 'id' => 'distributor_id', 'onchange' => 'advanceSearch()']) !!}
              </div>
              {!! Form::label('status', 'Status', array('class' => 'col-sm-1 control-label')) !!}
             <div class="col-sm-2">
                {!! Form::select('status', ['null' => 'Belum diretur', 'barang' => 'Pengembalian barang', 'uang' => 'Pengembalian uang'], $status, ['class' => 'form-control select2', 'style'=>'width: 100%', 'id' => 'status', 'onchange' => 'advanceSearch()']) !!}
              </div>
            </div>
            <div class="box-body" style="overflow-x:scroll; color: black !important">
              <table id="example1" class="table table-bordered table-striped">
                <thead>
                <tr>
                  <th>Distributor</th>
                  <th>Nama Barang</th>
                  <th>Status</th>
                </tr>
                </thead>
                <tbody id="table-good">
                  @foreach($items as $item)
                    <tr>
                      <td>{{ $item->last_distributor->name }}</td>
                      <td>{{ $item->good->name }}</td>
                      <td>
                        @if($item->returned_date != null)
                          Dikembalikan dalam bentuk {{ $item->returned_type }} pada tanggal {{ displayDate($item->returned_date) }}
                        @else
                          Belum diretur<br>
                          <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#modal-retur-{{$item->id}}">Retur Barang</button>

                          <div class="modal modal-retur fade" id="modal-retur-{{ $item->id }}">
                            <div class="modal-dialog">
                              <div class="modal-content">
                                <div class="modal-header">
                                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span></button>
                                    <h4 class="modal-title">Retur Barang {{ $item->good->name }}</h4>
                                </div>
                                <div class="modal-body">
                                    <p>Anda yakin ingin meretur {{ $item->good->name }}?</p>
                                </div>
                                <div class="modal-footer">
                                  <button type="button" class="btn btn-outline pull-left" data-dismiss="modal">Close</button>
                                  <button type="button" class="btn btn-outline" onclick="event.preventDefault(); document.getElementById('uang-{{ $item->id }}').submit();">Retur Uang</button>
                                  <button type="button" class="btn btn-outline" onclick="event.preventDefault(); document.getElementById('barang-{{ $item->id }}').submit();">Retur Barang</button>
                                </div>
                              </div>
                            </div>
                          </div>

                          <form id="uang-{{$item->id}}" action="{{ url('admin/retur/' . $item->id) }}" method="POST" style="display: none;">
                            {{ csrf_field() }}
                            {{ form::hidden('type', 'uang')}}
                            {{ method_field('PUT') }}
                          </form>

                          <form id="barang-{{$item->id}}" action="{{ url('admin/retur/' . $item->id) }}" method="POST" style="display: none;">
                            {{ csrf_field() }}
                            {{ form::hidden('type', 'barang')}}
                            {{ method_field('PUT') }}
                          </form>
                        @endif
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
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
      var show           = $('#show').val();
      var distributor_id = $('#distributor_id').val();
      var status         = $('#status').val();
      window.location = window.location.origin + '/admin/retur/' + distributor_id + '/' + status + '/' + show;
    }
    </script>
  @endsection
@endsection