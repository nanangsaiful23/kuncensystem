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
                <th>No</th>
                <th>Kode</th>
                <th>Nama</th>
                <th>Base Qty</th>
                <th>Base Price</th>
                <th>Grosir Qty</th>
                <th>Grosir Price</th>
                <th class="center">Ganti Harga</th>
              </tr>
              </thead>
              <tbody id="table-good">
                <?php $i = 1; ?>
                @foreach($goods as $good)
                  <tr>
                    <td>{{ $i++ }}</td>
                    <td>{{ $good->code }}</td>
                    <td>{{ $good->name }}</td>
                    <td>{{ $good->base_qty }}</td>
                    <td>{{ $good->base_price }}</td>
                    <td>{{ $good->quantity }}</td>
                    <td>{{ $good->selling_price }}</td>
                    <td class="center"><a href="{{ url($role . '/good/' . $good->id . '/editPrice') }}"><i class="fa fa-hand-o-right tosca" aria-hidden="true"></i></a></td>
                  </tr>
                @endforeach
              </tbody>
              <div id="renderField">
                {{ $goods->render() }}
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