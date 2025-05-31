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
            {!! Form::label('start_date', 'Tanggal Awal', array('class' => 'col-sm-1 control-label')) !!}
            <div class="col-sm-2">
              <div class="input-group date">
                <input type="text" class="form-control pull-right" id="datepicker" name="start_date" value="{{ $start_date }}" onchange="changeDate()">
              </div>
            </div>
            {!! Form::label('end_date', 'Tanggal Akhir', array('class' => 'col-sm-1 control-label')) !!}
            <div class="col-sm-2">
              <div class="input-group date">
                <input type="text" class="form-control pull-right" id="datepicker2" name="end_date" value="{{ $end_date }}" onchange="changeDate()">
              </div>
            </div>
            <table id="example1" class="table table-bordered table-striped">
              <thead>
              <tr>
                @if(\Auth::user()->email == 'admin')
                  <th>ID</th>
                @endif
                <th><a href="{{ url($role . '/member/' . $start_date . '/' . $end_date . '/name/asc/15') }}"><i class="fa fa-sort-alpha-asc" aria-hidden="true"></i></a> <a href="{{ url($role . '/member/' . $start_date . '/' . $end_date . '/name/desc/15') }}"><i class="fa fa-sort-alpha-desc" aria-hidden="true"></i></a> Nama</th>
                <th><a href="{{ url($role . '/member/' . $start_date . '/' . $end_date . '/address/asc/15') }}"><i class="fa fa-sort-alpha-asc" aria-hidden="true"></i></a> <a href="{{ url($role . '/member/' . $start_date . '/' . $end_date . '/address/desc/15') }}"><i class="fa fa-sort-alpha-desc" aria-hidden="true"></i></a> Alamat</th>
                <th><a href="{{ url($role . '/member/' . $start_date . '/' . $end_date . '/phone_number/asc/15') }}"><i class="fa fa-sort-numeric-asc" aria-hidden="true"></i></a> <a href="{{ url($role . '/member/' . $start_date . '/' . $end_date . '/phone_number/desc/15') }}"><i class="fa fa-sort-numeric-desc" aria-hidden="true"></i></a> No WA</th>
                <th><a href="{{ url($role . '/member/' . $start_date . '/' . $end_date . '/last_transaction/asc/15') }}"><i class="fa fa-sort-numeric-asc" aria-hidden="true"></i></a> <a href="{{ url($role . '/member/' . $start_date . '/' . $end_date . '/last_transaction/desc/15') }}"><i class="fa fa-sort-numeric-desc" aria-hidden="true"></i></a> Pembelian Terakhir</th>
                @if(\Auth::user()->email == 'admin')
                  <th><a href="{{ url($role . '/member/' . $start_date . '/' . $end_date . '/total_transaction/asc/15') }}"><i class="fa fa-sort-numeric-asc" aria-hidden="true"></i></a> <a href="{{ url($role . '/member/' . $start_date . '/' . $end_date . '/total_transaction/desc/15') }}"><i class="fa fa-sort-numeric-desc" aria-hidden="true"></i></a> Total Transaksi</th>
                  <th><a href="{{ url($role . '/member/' . $start_date . '/' . $end_date . '/total_sum_price/asc/15') }}"><i class="fa fa-sort-numeric-asc" aria-hidden="true"></i></a> <a href="{{ url($role . '/member/' . $start_date . '/' . $end_date . '/total_sum_price/desc/15') }}"><i class="fa fa-sort-numeric-desc" aria-hidden="true"></i></a> Riwayat Transaksi</th>
                  <th>Riwayat Pembayaran</th>
                  <th>Sisa Hutang</th>
                @endif
                <th class="center">Detail</th>
                @if(\Auth::user()->email == 'admin')
                  <th class="center">Ubah</th>
                  <th class="center">Hapus</th>
                @endif
              </tr>
              </thead>
              <tbody id="table-good">
                @foreach($members as $member)
                  <tr>
                    @if(\Auth::user()->email == 'admin')
                      <td>
                        {{ $member->id }}<br>
                        <a href="{{ url($role . '/member/' . $member->id . '/showQrCode') }}" target="_blank()" class="btn btn-warning">QR Code</a>
                      </td>
                    @endif
                    <td>{{ $member->name }}</td>
                    <td>{{ $member->address }}</td>
                    <td>{{ $member->phone_number }}</td>
                    <td>@if($member->lastTransaction() == null) - @else {{ displayDate($member->lastTransaction()->created_at) }} @endif</td>
                    @if(\Auth::user()->email == 'admin')
                      <td class="center">Jumlah transaksi: {{ $member->total_transaction }}<br><a href="{{ url($role . '/member/' . $member->id . '/transaction/2019-01-01/' . date('Y-m-d') . '/all') }}"><i class="fa fa-hand-o-right pink" aria-hidden="true"></i> detail</a></td>
                      <td class="center">Total transaksi: {{ showRupiah($member->total_sum_price) }}<br><a href="{{ url($role . '/member/' . $member->id . '/transaction/2019-01-01/' . date('Y-m-d') . '/all') }}"><i class="fa fa-hand-o-right pink" aria-hidden="true"></i> detail</a></td>
                      <td class="center">Total pembayaran: {{ showRupiah($member->totalPayment()->sum('money') + $member->totalTransactionCash()->sum('total_sum_price')) }}<br><a href="{{ url($role . '/member/' . $member->id . '/payment/2019-01-01/' . date('Y-m-d') . '/all') }}"><i class="fa fa-hand-o-right green" aria-hidden="true"></i> detail</a></td>
                      <td>{{ showRupiah($member->totalTransactionNormal()->sum('total_sum_price') - ($member->totalPayment()->sum('money') + $member->totalTransactionCash()->sum('total_sum_price'))) }}</td>
                    @endif
                    <td class="center"><a href="{{ url($role . '/member/' . $member->id . '/detail') }}"><i class="fa fa-hand-o-right tosca" aria-hidden="true"></i></a></td>
                    @if(\Auth::user()->email == 'admin')
                      <td class="center"><a href="{{ url($role . '/member/' . $member->id . '/edit') }}"><i class="fa fa-file orange" aria-hidden="true"></i></a></td>
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
        $('#datepicker').datepicker({
          autoclose: true,
          format: 'yyyy-mm-dd'
        })

        $('#datepicker2').datepicker({
          autoclose: true,
          format: 'yyyy-mm-dd'
        })
        
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

    function changeDate()
    {
      window.location = window.location.origin + '/{{ $role }}/member/' + $("#datepicker").val() + '/' + $("#datepicker2").val() + '/{{ $sort }}/{{ $order }}/{{ $pagination }}';
    }

    function ajaxFunction()
    {
      $.ajax({
        url: "{!! url($role . '/member/searchByName/') !!}/" + $("#search-input").val(),
        success: function(result){
          var htmlResult = '<thead><tr>@if(\Auth::user()->email == "admin")<th>ID</th>@endif<th>Nama</th><th>Alamat</th><th>No Telephone</th>@if(\Auth::user()->email == "admin")<th>Riwayat Transaksi</th><th>Riwayat Pembayaran</th><th>Sisa Hutang</th>@endif<th class="center">Detail</th>@if(\Auth::user()->email == "admin")<th class="center">Ubah</th><th class="center">Hapus</th>@endif</tr></thead><tbody>';
          if(result != null)
          {
            var r = result.members;
            for (var i = 0; i < r.length; i++) {
              htmlResult += "<tr>@if(\Auth::user()->email == 'admin')<td>" + r[i].id + "</td>@endif<td>" + r[i].name + "</td><td>" + r[i].address + "</td><td>" + r[i].phone_number + "</td>@if(\Auth::user()->email == 'admin')<td class=\"center\">Total transaksi: " + r[i].transaction + "<br><a href=\"" + window.location.origin + "/{{ $role }}/member/" + r[i].id + "/transaction/2019-01-01/{{ date('Y-m-d')}}/all\" <i class=\"fa fa-hand-o-right pink\" aria-hidden=\"true\"></i> detail</a></td><td class=\"center\">Total pembayaran: " + r[i].payment + "<br><a href=\"" + window.location.origin + "/{{ $role }}/member/" + r[i].id + "/payment/2019-01-01/{{ date('Y-m-d')}}/all\" <i class=\"fa fa-hand-o-right pink\" aria-hidden=\"true\"></i> detail</a></td><td>" + r[i].credit + "</td>@endif<td class=\"center\"><a href=\"" + window.location.origin + "/{{ $role }}/member/" + r[i].id + "/detail\" <i class=\"fa fa-hand-o-right pink\" aria-hidden=\"true\"></i></a></td>@if(\Auth::user()->email == 'admin')<td class=\"center\"><a href=\"" + window.location.origin + "/{{ $role }}/member/" + r[i].id + "/edit\" <i class=\"fa fa-file pink\" aria-hidden=\"true\"></i></a></td><td><a href=\"" + window.location.origin + "/" + '{{ $role }}' + "/member/" + r[i].id + "/delete\" onclick=\"event.preventDefault(); document.getElementById('delete-form-" + r[i].id + "').submit();\"><i class=\"fa fa-times red\"></i></a><form id='delete-form-" + r[i].id + "' action=\"" + window.location.origin + "/" + '{{ $role }}' + "/member/" + r[i].id + "/delete\" method=\"POST\" style=\"display: none;\">" + '{{ csrf_field() }}' + '{{ method_field("DELETE") }}' + "</form></td>@endif";

              
              htmlResult += "</tr>";
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