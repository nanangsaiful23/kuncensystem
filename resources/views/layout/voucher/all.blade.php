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
                <th>Kode</th>
                <th>Start Period</th>
                <th>End Period</th>
                <th>Kuota</th>
                <th>Tipe</th>
                <th>Nominal</th>
                <th>Aktif</th>
                <th class="center">Detail</th>
                <th class="center">Ubah</th>
                @if($role == 'admin')
                  <th class="center">Hapus</th>
                @endif
              </tr>
              </thead>
              <tbody id="table-good">
                @foreach($vouchers as $voucher)
                  <tr @if($voucher->is_valid == 1) style='background-color: #DADDB1' @endif>
                    <td>{{ $voucher->code }}</td>
                    <td>{{ displayDate($voucher->start_period) }}</td>
                    <td>{{ displayDate($voucher->end_period) }}</td>
                    <td>{{ $voucher->quota }}</td>
                    <td>{{ $voucher->type }}</td>
                    <td>{{ $voucher->nominal }}</td>
                    <td>@if($voucher->is_valid == 1) Aktif @else Non Aktif @endif</td>
                    <td class="center"><a href="{{ url($role . '/voucher/' . $voucher->id . '/detail') }}"><i class="fa fa-hand-o-right tosca" aria-hidden="true"></i></a></td>
                    <td class="center"><a href="{{ url($role . '/voucher/' . $voucher->id . '/edit') }}"><i class="fa fa-file orange" aria-hidden="true"></i></a></td>
                    @if($role == 'admin')
                      <td class="center">
                        <button type="button" class="no-btn center" data-toggle="modal" data-target="#modal-danger-{{$voucher->id}}"><i class="fa fa-times" aria-hidden="true" style="color: red !important"></i></button>

                        @include('layout' . '.delete-modal', ['id' => $voucher->id, 'data' => $voucher->name, 'formName' => 'delete-form-' . $voucher->id])

                        <form id="delete-form-{{$voucher->id}}" action="{{ url($role . '/voucher/' . $voucher->id . '/delete') }}" method="POST" style="display: none;">
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
                  {{ $vouchers->render() }}
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