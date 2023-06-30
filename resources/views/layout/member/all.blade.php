<div class="content-wrapper">

  @include('layout' . '.alert-message', ['type' => $default['type'], 'data' => $default['data'], 'color' => $default['color']])

  <section class="content">
    <div class="row">
      <div class="col-xs-12">
        <div class="box">
          <div class="box-header">
            <h3 class="box-title">List Member</h3>
            @include('layout.search-form')
          </div>
          <div class="box-body" style="overflow-x:scroll; color: black !important">
            <table id="example1" class="table table-bordered table-striped">
              <thead>
              <tr>
                <th>Nama</th>
                <th>Alamat</th>
                <th>Riwayat Transaksi</th>
                <th>Riwayat Pembayaran</th>
                <th>Sisa Hutang</th>
                <th class="center">Detail</th>
                <th class="center">Ubah</th>
                @if($role == 'admin')
                  <th class="center">Hapus</th>
                @endif
              </tr>
              </thead>
              <tbody id="table-good">
                @foreach($members as $member)
                  <tr>
                    <td>{{ $member->name }}</td>
                    <td>{{ $member->address }}</td>
                    <td class="center">Total Transaksi: {{ showRupiah($member->totalTransaction()->sum('total_sum_price')) }}<br><a href="{{ url($role . '/member/' . $member->id . '/transaction/2019-01-01/' . date('Y-m-d') . '/all') }}"><i class="fa fa-hand-o-right pink" aria-hidden="true"></i> detail</a></td>
                    <td class="center">Total pembayaran: {{ showRupiah($member->totalPayment()->sum('money')) }}<br><a href="{{ url($role . '/member/' . $member->id . '/payment/2019-01-01/' . date('Y-m-d') . '/all') }}"><i class="fa fa-hand-o-right green" aria-hidden="true"></i> detail</a></td>
                    <td>{{ showRupiah($member->totalTransaction()->sum('total_sum_price') - $member->totalPayment()->sum('money')) }}</td>
                    <td class="center"><a href="{{ url($role . '/member/' . $member->id . '/detail') }}"><i class="fa fa-hand-o-right tosca" aria-hidden="true"></i></a></td>
                    <td class="center"><a href="{{ url($role . '/member/' . $member->id . '/edit') }}"><i class="fa fa-file orange" aria-hidden="true"></i></a></td>
                    @if($role == 'admin')
                      <td class="center">
                        <button type="button" class="no-btn center" data-toggle="modal" data-target="#modal-danger-{{$member->id}}"><i class="fa fa-times" aria-hidden="true" style="color: red !important"></i></button>

                        @include('layout' . '.delete-modal', ['id' => $member->id, 'data' => $member->name, 'formName' => 'delete-form-' . $member->id])

                        <form id="delete-form-{{$member->id}}" action="{{ url($role . '/member/' . $member->id . '/delete') }}" method="POST" style="display: none;">
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
                  {{ $members->render() }}
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