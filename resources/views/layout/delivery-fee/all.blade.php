<div class="content-wrapper">

  @include('layout' . '.alert-message', ['type' => $default['type'], 'data' => $default['data'], 'color' => $default['color']])

  <section class="content">
    <div class="row">
      <div class="col-xs-12">
        <div class="box">
          <div class="box-header">
            <h3 class="box-title"> {{ $default['page_name'] }}</h3>
            @include('layout.search-form')
          </div>
          <div class="box-body" style="overflow-x:scroll; color: black !important">
            <table id="example1" class="table table-bordered table-striped">
              <thead>
              <tr>
                <th>Lokasi</th>
                <th>Kecamatan</th>
                <th>Desa</th>
                <th>RT/RW</th>
                <th>Biaya</th>
                <th>Tanggal Input Biaya</th>
                <th class="center" width="5%">Detail</th>
                <th class="center" width="5%">Ubah</th>
                @if($role == 'admin')
                  <th class="center" width="5%">Hapus</th>
                @endif
              </tr>
              </thead>
              <tbody id="table-good">
                @foreach($fees as $fee)
                  <tr>
                    <td>{{ $fee->location }}</td>
                    <td>{{ $fee->kecamatan }}</td>
                    <td>{{ $fee->desa }}</td>
                    <td>{{ $fee->rt_rw }}</td>
                    <td>{{ showRupiah($fee->fee) }}</td>
                    <td>{{ displayDate($fee->date_fee) }}</td>
                    <td class="center"><a href="{{ url($role . '/delivery-fee/' . $fee->id . '/detail') }}"><i class="fa fa-hand-o-right tosca" aria-hidden="true"></i></a></td>
                    <td class="center"><a href="{{ url($role . '/delivery-fee/' . $fee->id . '/edit') }}"><i class="fa fa-file orange" aria-hidden="true"></i></a></td>
                    @if($role == 'admin')
                      <td class="center">
                        <button type="button" class="no-btn center" data-toggle="modal" data-target="#modal-danger-{{$fee->id}}"><i class="fa fa-times" aria-hidden="true" style="color: red !important"></i></button>

                        @include('layout' . '.delete-modal', ['id' => $fee->id, 'data' => $fee->name, 'formName' => 'delete-form-' . $fee->id])

                        <form id="delete-form-{{$fee->id}}" action="{{ url($role . '/delivery-fee/' . $fee->id . '/delete') }}" method="POST" style="display: none;">
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
                  {{ $fees->render() }}
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