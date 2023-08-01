<div class="content-wrapper">

  @include('layout' . '.alert-message', ['type' => $default['type'], 'data' => $default['data'], 'color' => $default['color']])

  <section class="content">
    <div class="row">
      <div class="col-xs-12">
        <div class="box">
          <div class="box-header">
            <h3 class="box-title">{{ $default['page_name'] }}</h3>
          </div>
          <div class="box-body" style="overflow-x:scroll">
            <table id="example1" class="table table-bordered table-striped">
              <thead>
              <tr>
                <th>Nama Barang</th>
                <th>Exp</th>
              </tr>
              </thead>
              <tbody id="table-good">
                @foreach($loadings as $loading)
                  <tr>
                    <td>{{ $loading->good_unit->good->name }}</td>
                    <td>{{ displayDate($loading->expiry_date) }}</td>
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
