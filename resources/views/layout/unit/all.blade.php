<div class="content-wrapper">

  @include('layout' . '.alert-message', ['type' => $default['type'], 'data' => $default['data'], 'color' => $default['color']])

  <section class="content">
    <div class="row">
      <div class="col-xs-12">
        <div class="box">
          <div class="box-header">
            <h3 class="box-title">List Satuan</h3>
            @include('layout.search-form')
          </div>
          <div class="box-body" style="overflow-x:scroll; color: black !important">
            <table id="example1" class="table table-bordered table-striped">
              <thead>
              <tr>
                <th>Kode</th>
                <th>Nama</th>
                <th>Eng Name</th>
                <th>Kuantitas</th>
                <th>Unit</th>
                <th class="center">Detail</th>
                <th class="center">Ubah</th>
                @if($role == 'admin')
                  <th class="center">Hapus</th>
                @endif
              </tr>
              </thead>
              <tbody id="table-good">
                @foreach($units as $unit)
                  <tr>
                    <td>{{ $unit->code }}</td>
                    <td>{{ $unit->name }}</td>
                    <td>{{ $unit->eng_name }}</td>
                    <td>{{ $unit->quantity }}</td>
                    <td>{{ $unit->base }}</td>
                    <td class="center"><a href="{{ url($role . '/unit/' . $unit->id . '/detail') }}"><i class="fa fa-hand-o-right tosca" aria-hidden="true"></i></a></td>
                    <td class="center"><a href="{{ url($role . '/unit/' . $unit->id . '/edit') }}"><i class="fa fa-file orange" aria-hidden="true"></i></a></td>
                    @if($role == 'admin')
                      <td class="center">
                        <button type="button" class="no-btn center" data-toggle="modal" data-target="#modal-danger-{{$unit->id}}"><i class="fa fa-times" aria-hidden="true" style="color: red !important"></i></button>

                        @include('layout' . '.delete-modal', ['id' => $unit->id, 'data' => $unit->name, 'formName' => 'delete-form-' . $unit->id])

                        <form id="delete-form-{{$unit->id}}" action="{{ url($role . '/unit/' . $unit->id . '/delete') }}" method="POST" style="display: none;">
                          {{ csrf_field() }}
                          {{ method_field('DELETE') }}
                        </form>
                      </td>
                    @endif
                  </tr>
                @endforeach
              </tbody>
              <div id="renderField">
                @if($pagination != 'all')
                  {{ $units->render() }}
                @endif
              </div>
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
  </script>
@endsection