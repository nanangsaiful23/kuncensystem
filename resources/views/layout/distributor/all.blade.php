<div class="content-wrapper">

  @include('layout' . '.alert-message', ['type' => $default['type'], 'data' => $default['data'], 'color' => $default['color']])

  <section class="content">
    <div class="row">
      <div class="col-xs-12">
        <div class="box">
          <div class="box-header">
            <h3 class="box-title">List Distributor</h3>
            @include('layout.search-form')
          </div>
          <div class="box-body" style="overflow-x:scroll; color: black !important">
            <h3>Total seluruh aset: {{ showRupiah($total) }}</h3>
            <table id="example1" class="table table-bordered table-striped">
              <thead>
              <tr>
                <th>ID</th>
                <th>Nama</th>
                <th>Lokasi</th>
                @if(\Auth::user()->email == 'admin')
                  <th>Total Aset</th>
                  <th>Total Untung</th>
                  <th>Total Rugi (Penyusutan & SO)</th>
                  <th>Persentase Rugi</th>
                  <!-- <th>Total Hutang Dagang</th>
                  <th>Pembayaran Hutang dengan Barang</th>
                  <th>Pembayaran Hutang dengan Uang</th>
                  <th>Sisa Hutang</th> -->
                  <th class="center">Pembayaran Hutang</th>
                @endif
                <th class="center">Detail</th>
                <th class="center">Ubah</th>
                @if($role == 'admin')
                  <th class="center">Hapus</th>
                @endif
              </tr>
              </thead>
              <tbody id="table-good">
                @foreach($distributors as $distributor)
                  <tr>
                    <td>{{ $distributor->id }}</td>
                    <td>{{ $distributor->name }}</td>
                    <td>{{ $distributor->location }}</td>
                    @if(\Auth::user()->email == 'admin')
                      <td>{{ showRupiah($distributor->total_aset) }}</td>
                      <?php $a = $distributor->totalUntung('all')[0]->total; $b = $distributor->totalRugi('all')[0]->total; ?>
                      <td>{{ showRupiah($a) }}</td>
                      <td>{{ showRupiah($b) }}</td>
                      <td>@if($a == 0) 0% @else {{ round($b/$a * 100, 2) . '%' }} @endif</td>
                      <td class="center"><a href="{{ url($role . '/distributor/' . $distributor->id . '/creditPayment') }}"><i class="fa fa-dollar tosca" aria-hidden="true"></i></a></td>
                    @endif
                    <td class="center"><a href="{{ url($role . '/distributor/' . $distributor->id . '/detail/aset') }}"><i class="fa fa-hand-o-right tosca" aria-hidden="true"></i></a></td>
                    <td class="center"><a href="{{ url($role . '/distributor/' . $distributor->id . '/edit') }}"><i class="fa fa-file orange" aria-hidden="true"></i></a></td>
                    @if($role == 'admin')
                      <td class="center">
                        <button type="button" class="no-btn center" data-toggle="modal" data-target="#modal-danger-{{$distributor->id}}"><i class="fa fa-times" aria-hidden="true" style="color: red !important"></i></button>

                        @include('layout' . '.delete-modal', ['id' => $distributor->id, 'data' => $distributor->name, 'formName' => 'delete-form-' . $distributor->id])

                        <form id="delete-form-{{$distributor->id}}" action="{{ url($role . '/distributor/' . $distributor->id . '/delete') }}" method="POST" style="display: none;">
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
                  {{ $distributors->render() }}
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

    function ajaxFunction()
    {
      $.ajax({
        url: "{!! url($role . '/distributor/search/') !!}/" + $("#search-input").val(),
        success: function(result){
          console.log(result);
          var htmlResult = "<thead><tr><th>ID</th><th>Nama</th><th>Lokasi</th><th>Total Aset</th><th>Total Untung</th><th>Total Rugi (Penyusutan & SO)</th><th>Persentase Rugi</th><th class='center'>Pembayaran Hutang</th><th class='center'>Detail</th><th class='center'>Ubah</th>@if($role == 'admin')<th class='center'>Hapus</th>@endif</tr></thead><tbody>";
          if(result != null)
          {
            var r = result.distributors;
            for (var i = 0; i < r.length; i++) {
              htmlResult += "<tr><td>" + r[i].id + "</td><td>" + r[i].name + "</td><td>" + r[i].location + "</td><td>" + r[i].aset + "</td><td>" + r[i].untung + "</td><td>" + r[i].rugi + "</td><td>" + r[i].percentage + "</td><td class='center'><a href=\"" + window.location.origin + "/" + '{{ $role }}' + "/distributor/" + r[i].id + "/creditPayment\"><i class=\"fa fa-dollar brown\" aria-hidden=\"true\"></i></a></td><td class='center'><a href=\"" + window.location.origin + "/" + '{{ $role }}' + "/distributor/" + r[i].id + "/detail/aset\"><i class=\"fa fa-hand-o-right brown\" aria-hidden=\"true\"></i></a></td><td class='center'><a href=\"" + window.location.origin + "/" + '{{ $role }}' + "/distributor/" + r[i].id + "/edit\"><i class=\"fa fa-file brown\" aria-hidden=\"true\"></i></a></td><td><a href=\"" + window.location.origin + "/" + '{{ $role }}' + "/distributor/" + r[i].id + "/delete\" onclick=\"event.preventDefault(); document.getElementById('delete-form-" + r[i].id + "').submit();\"><i class=\"fa fa-times red\"></i></a><form id='delete-form-" + r[i].id + "' action=\"" + window.location.origin + "/" + '{{ $role }}' + "/distributor/" + r[i].id + "/delete\" method=\"POST\" style=\"display: none;\">" + '{{ csrf_field() }}' + '{{ method_field("DELETE") }}' + "</form></td></tr>";
            }
          }

          htmlResult += "</tbody>";

          $("#example1").html(htmlResult);
        },
        error: function(){
            console.log('error');
        }
      });
    }
  </script>
@endsection