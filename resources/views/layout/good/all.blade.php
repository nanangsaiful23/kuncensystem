<style type="text/css">
  .table-striped > tbody > tr:nth-child(2n+1) > td, .table-striped > tbody > tr:nth-child(2n+1) > th {
   background-color: #E1EEDD;
}
</style>

<div class="content-wrapper">

  @include('layout' . '.alert-message', ['type' => $default['type'], 'data' => $default['data'], 'color' => $default['color']])

  <section class="content">
    <div class="row">
      <div class="col-xs-12">
        <div class="box">
          <div class="box-header">
            <h3 class="box-title">Daftar Barang</h3>
          </div>
          <div class="box-body" style="overflow-x:scroll">
            <div class="form-group col-sm-12" style="margin-top: 10px;">

              @include('layout.search-form')

              {!! Form::label('show', 'Show', array('class' => 'col-sm-1 control-label')) !!}
              <div class="col-sm-2">
                {!! Form::select('show', getPaginations(), $pagination, ['class' => 'form-control select2', 'style'=>'width: 100%', 'id' => 'show', 'onchange' => 'advanceSearch()']) !!}
              </div>
              {!! Form::label('category', 'Kategori', array('class' => 'col-sm-1 control-label')) !!}
              <div class="col-sm-3">
                {!! Form::select('category', getCategories(), $category_id, ['class' => 'form-control select2', 'style'=>'width: 100%', 'id' => 'category', 'onchange' => 'advanceSearch()']) !!}
              </div>
            </div>
            @if(\Auth::user()->email == 'admin')
              <div class="form-group col-sm-12" style="margin-top: 10px;">
                {!! Form::label('distributor', 'Distributor', array('class' => 'col-sm-1 control-label')) !!}
                <div class="col-sm-5">
                  {!! Form::select('distributor', getDistributorLists(), $distributor_id, ['class' => 'form-control select2', 'style'=>'width: 100%', 'id' => 'distributor', 'onchange' => 'advanceSearch()']) !!}
                </div>
              </div>
            @endif
          </div>
          <div class="box-body" style="overflow-x:scroll">
              <div class="form-group">
                @if($pagination != 'all')
                  {{ $goods->render() }}
                @endif
              </div>
            <table id="example1" class="table table-bordered table-striped">
              <thead>
                <tr>
                  <th style="width: 5%; text-align: center;">Kategori</th>
                  <th style="width: 45%; text-align: center;">Nama</th>
                  <th style="width: 12%; text-align: center;">Stock</th>
                  <th style="width: 15%; text-align: center;">Harga Jual</th>
                  <th style="width: 15%; text-align: center;">Kode</th>
                  @if(\Auth::user()->role == 'supervisor')
                    <th style="width: 10%; text-align: center;">Harga Beli</th>
                  @endif
                  <th style="width: 5%; text-align: center;">Action</th>
                </tr>
              </thead>
              <tbody>
                @foreach($goods as $good)
                  <tr>
                    <td>{{ $good->category->name }}</td>
                    <td>
                      <h4>{{ $good->name }}</h4>
                      @if($good->brand != null) <h5>Brand: {{ $good->brand->name }}</h5>@endif
                      @if(\Auth::user()->email == 'admin')
                        <i class="fa fa-truck green" aria-hidden="true"></i> {{ $good->getDistributor()->name }} @if($good->getLastBuy() != null) {{ ' (' . $good->getLastBuy()->good_loading->note . ')' }} @endif
                      @endif
                    </td>
                    <td>
                      <i class="fa fa-cubes brown" aria-hidden="true"></i> {{ $good->getStock() . ' ' . $good->getPcsSellingPrice()->unit->code }}<br>
                      <i class="fa fa-money green" aria-hidden="true"></i> {{ ($good->good_transactions()->sum('real_quantity') / $good->getPcsSellingPrice()->unit->quantity) . ' ' . $good->getPcsSellingPrice()->unit->code }}<br>
                      <i class="fa fa-truck pink" aria-hidden="true"></i> {{ ($good->good_loadings()->sum('real_quantity') / $good->getPcsSellingPrice()->unit->quantity) . ' ' . $good->getPcsSellingPrice()->unit->code }}
                      @if($role == 'admin')
                        <br><a href="{{ url($role . '/good/' . $good->id . '/loading/2023-01-01/' . date('Y-m-d') . '/10') }}" class="btn btn-warning" target="_blank()">Riwayat loading</a><br>
                      @endif
                      <br><a href="{{ url($role . '/good/' . $good->id . '/transaction/2023-01-01/' . date('Y-m-d') . '/10') }}" class="btn btn-warning" target="_blank()">Riwayat penjualan</a><br>
                    </td>
                    <td>
                      @foreach($good->good_units as $unit)
                        <b>{{ showRupiah($unit->selling_price) . ' /' . $unit->unit->name}}</b>
                        @if(\Auth::user()->email == 'admin')
                          <br>Untung: {{ showRupiah($unit->selling_price - $unit->buy_price) . ' (' . calculateProfit($unit->buy_price, $unit->selling_price) }}%)<br>
                        @endif
                        @if($role == 'admin')
                          <button type="button" class="no-btn" data-toggle="modal" data-target="#modal-danger-{{ 'unit-' . $unit->id }}"><i class="fa fa-times red" aria-hidden="true"></i> Hapus harga</button>

                          @include('layout' . '.delete-modal', ['id' => 'unit-' . $unit->id, 'data' => 'Harga ' . $good->name . ' ' . $unit->unit->name, 'formName' => 'delete-unit-' . $unit->id])

                          <form id="delete-unit-{{$unit->id}}" action="{{ url($role . '/good/' . $good->id . '/deletePrice/' . $unit->id) }}" method="POST" style="display: none;">
                            {{ csrf_field() }}
                            {{ method_field('DELETE') }}
                          </form><br>
                        @endif
                      @endforeach
                      <br><a href="{{ url($role . '/good/' . $good->id . '/price/2023-01-01/' . date('Y-m-d') . '/10') }}" class="btn btn-warning" target="_blank()">Riwayat harga jual</a><br>
                    </td>
                    <td>{{ $good->code }}</td>
                    @if(\Auth::user()->role == 'supervisor')
                      <td>
                        @foreach($good->good_units as $unit)
                          {{ showRupiah($unit->buy_price) . ' /' . $unit->unit->name}}
                        @endforeach
                      </td>
                    @endif
                    <td>
                      <a href="{{ url($role . '/good/' . $good->id . '/detail') }}" target="_blank()"><i class="fa fa-hand-o-right tosca" aria-hidden="true"></i></a><br>
                      <a href="{{ url($role . '/good/' . $good->id . '/edit') }}" target="_blank()"><i class="fa fa-file orange" aria-hidden="true"></i></a><br>
                      @if($good->getStock() == 0)
                        <button type="button" class="no-btn" data-toggle="modal" data-target="#modal-danger-{{$good->id}}"><i class="fa fa-times red" aria-hidden="true"></i></button>

                        @include('layout' . '.delete-modal', ['id' => $good->id, 'data' => $good->name, 'formName' => 'delete-form-' . $good->id])

                        <form id="delete-form-{{$good->id}}" action="{{ url($role . '/good/' . $good->id . '/delete') }}" method="POST" style="display: none;">
                          {{ csrf_field() }}
                          {{ method_field('DELETE') }}
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

    function ajaxFunction()
    {
      $.ajax({
        url: "{!! url($role . '/good/searchByKeyword/') !!}/" + $("#search-input").val(),
        success: function(result){
          console.log(result);
          var htmlResult = "<thead><tr><th style=\"width: 5%; text-align: center;\">Kategori</th><th style=\"width: 45%; text-align: center;\">Nama</th><th style=\"width: 12%; text-align: center;\">Stock</th><th style=\"width: 15%; text-align: center;\">Harga Jual</th><th style=\"width: 15%; text-align: center;\">Kode</th>@if(\Auth::user()->email == 'admin')<th style=\"width: 10%; text-align: center;\">Harga Beli</th>@endif<th style=\"width: 5%; text-align: center;\">Action</th></tr></thead><tbody>";
          if(result != null)
          {
            var r = result.goods;
            for (var i = 0; i < r.length; i++) {
              htmlResult += "<tr><td>" + r[i].category.name + "</td><td><h4>" + r[i].name + "</h4><h5>Brand:" + r[i].brand_name + "</h5>";

              var username = "{{ \Auth::user()->email }}";
              var role = "{{ $role }}";
              if(username == 'admin')
              {
                htmlResult += "<br><i class='fa fa-truck green' aria-hidden='true'></i> " + r[i].last_loading + "</td>";
              }

              htmlResult += "<td><i class=\"fa fa-cubes brown\" aria-hidden=\"true\"></i> " + r[i].stock + " " + r[i].unit + "<br><i class=\"fa fa-money green\" aria-hidden=\"true\"></i> " + r[i].transaction + " " + r[i].unit + "<br><i class=\"fa fa-truck pink\" aria-hidden=\"true\"></i> " + r[i].loading + " " + r[i].unit + "<br><br><a href=\"" + window.location.origin + "/" + '{{ $role }}' + "/good/" + r[i].id + "/loading/2023-01-01/" + "{{ date('Y-m-d') }}" + "/10\" class=\"btn btn-warning\" target=\"_blank()\">Riwayat loading</a><br><br><a href=\"" + window.location.origin + "/" + '{{ $role }}' + "/good/" + r[i].id + "/transaction/2023-01-01/" + "{{ date('Y-m-d') }}" + "/10\" class=\"btn btn-warning\" target=\"_blank()\">Riwayat penjualan</a></td><td>";

              for (var j = 0; j < r[i].good_units.length; j++) {
                htmlResult += "<b>" + r[i].good_units[j].price + " /" + r[i].good_units[j].unit_name + "<br></b>";

                if(username == 'admin')
                { 
                  htmlResult += "Untung: " + r[i].good_units[j].profit + " (" + r[i].good_units[j].percentage + "%)<br>";
                }

                if(role == 'admin')
                {
                  htmlResult += "<a href=\"" + window.location.origin + "/" + '{{ $role }}' + "/good/" + r[i].id + "/deletePrice/" + r[i].good_units[j].id + "\" onclick=\"event.preventDefault(); document.getElementById('delete-form-unit" + r[i].good_units[j].id + "').submit();\"><i class=\"fa fa-times red\"></i> Hapus harga</a><form id='delete-form-unit" + r[i].good_units[j].id + "' action=\"" + window.location.origin + "/" + '{{ $role }}' + "/good/" + r[i].id + "/deletePrice/" + r[i].good_units[j].id + "\" method=\"POST\" style=\"display: none;\">" + '{{ csrf_field() }}' + '{{ method_field("DELETE") }}' + "</form><br>";
                }
              }
              htmlResult += "<br><a href=\"" + window.location.origin + "/" + '{{ $role }}' + "/good/" + r[i].id + "/price/2023-01-01/" + "{{ date('Y-m-d') }}" + "/10\" class=\"btn btn-warning\" target=\"_blank()\">Riwayat harga jual</a></td>";

              htmlResult += "<td>" + r[i].code + "</td>";

              if(username == 'admin')
              { 
                htmlResult += "<td>";
                for (var j = 0; j < r[i].good_units.length; j++) {
                  htmlResult += r[i].good_units[j].buy_price + " /" + r[i].good_units[j].unit_name + "<br>";
                }
                htmlResult += "</td>";
              }

              htmlResult += "<td><a href=\"" + window.location.origin + "/" + '{{ $role }}' + "/good/" + r[i].id + "/detail\" target=\"_blank()\"><i class=\"fa fa-hand-o-right tosca\" aria-hidden=\"true\"></i></a><br><a href=\"" + window.location.origin + "/" + '{{ $role }}' + "/good/" + r[i].id + "/edit\" target=\"_blank()\"><i class=\"fa fa-pencil-square-o orange\"></i></a><br>";


              if(r[i].stock == '0')
              {
                htmlResult += "<a href=\"" + window.location.origin + "/" + '{{ $role }}' + "/good/" + r[i].id + "/delete\" onclick=\"event.preventDefault(); document.getElementById('delete-form-" + r[i].id + "').submit();\"><i class=\"fa fa-times red\"></i></a><form id='delete-form-" + r[i].id + "' action=\"" + window.location.origin + "/" + '{{ $role }}' + "/good/" + r[i].id + "/delete\" method=\"POST\" style=\"display: none;\">" + '{{ csrf_field() }}' + '{{ method_field("DELETE") }}' + "</form>";
              }
              
              htmlResult += "</td></tr>";
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

    function advanceSearch()
    {
      var username = "{{ \Auth::user()->email }}";

      if(username == 'admin')
        window.location = window.location.origin + '/{{ $role }}/good/' + $('#category').val() + '/' + $('#distributor').val() + '/' + $('#show').val();
      else
        window.location = window.location.origin + '/{{ $role }}/good/' + $('#category').val() + '/all/' + $('#show').val();
    }
  </script>
@endsection
