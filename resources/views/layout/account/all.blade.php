<div class="content-wrapper">

  @include('layout' . '.alert-message', ['type' => $default['type'], 'data' => $default['data'], 'color' => $default['color']])

  <section class="content">
    <div class="row">
      <div class="col-xs-12">
        <div class="box">
          <div class="box-header">
            <h3 class="box-title">List Akun</h3>
            @include('layout.search-form')
          </div>
          <div class="box-body">
            {!! Form::label('show', 'Show', array('class' => 'col-sm-1 control-label')) !!}
           <div class="col-sm-1">
              {!! Form::select('show', getPaginations(), $pagination, ['class' => 'form-control', 'style'=>'width: 100%', 'id' => 'show', 'onchange' => 'advanceSearch()']) !!}
            </div>
          </div>
          <div class="box-body" style="overflow-x:scroll; color: black !important">
            <table id="example1" class="table table-bordered table-striped">
              <thead>
              <tr>
                <th>Kode</th>
                <th>Nama</th>
                <th>Debet/Kredit</th>
                <th>Grup</th>
                <th>Tipe</th>
                <th>Saldo Awal</th>
                <th class="center">Detail</th>
                <th class="center">Ubah</th>
                @if($role == 'admin')
                  <th class="center">Hapus</th>
                @endif
              </tr>
              </thead>
              <tbody id="table-good">
                @foreach($accounts as $account)
                  <tr>
                    <td>{{ $account->code }}</td>
                    <td>{{ $account->name }}</td>
                    <td>{{ $account->type }}</td>
                    <td>{{ $account->group }}</td>
                    <td>{{ $account->activa }}</td>
                    <td style="text-align: right;">{{ showRupiah($account->balance) }}</td>
                    <td class="center"><a href="{{ url($role . '/account/' . $account->id . '/detail') }}"><i class="fa fa-hand-o-right tosca" aria-hidden="true"></i></a></td>
                    <td class="center"><a href="{{ url($role . '/account/' . $account->id . '/edit') }}"><i class="fa fa-file orange" aria-hidden="true"></i></a></td>
                    @if($role == 'admin')
                      <td class="center">
                        <button type="button" class="no-btn center" data-toggle="modal" data-target="#modal-danger-{{$account->id}}"><i class="fa fa-times" aria-hidden="true" style="color: red !important"></i></button>

                        @include('layout' . '.delete-modal', ['id' => $account->id, 'data' => $account->name, 'formName' => 'delete-form-' . $account->id])

                        <form id="delete-form-{{$account->id}}" action="{{ url($role . '/account/' . $account->id . '/delete') }}" method="POST" style="display: none;">
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
                  {{ $accounts->render() }}
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

    function advanceSearch()
    {
      var show        = $('#show').val();
      window.location = window.location.origin + '/{{ $role }}/account/' + show;
    }
  </script>
@endsection