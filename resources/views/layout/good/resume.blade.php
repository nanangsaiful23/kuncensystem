<div class="content-wrapper">
  @include('layout' . '.error')

  <section class="content">
    <div class="row">
      <div class="col-xs-12">
        <div class="box">
          <div class="box-header">
            <h3 class="box-title">{{ $default['page_name'] }}</h3>
          </div>
          <div id='asset' style="display: none; margin-top: 20px;">
            <h3 style="margin-bottom: 30px;">Total Asset: {{ showRupiah($distributor->getAsset()) }}</h3>
            <table class="table table-bordered table-striped">
              <thead>
              <tr>
                <th width="15%">Nama</th>
                <th width="15%">Loading</th>
                <th width="15%">Terjual</th>
                <th width="15%">Stock</th>
                <th width="15%">Stock Uang</th>
              </tr>
              </thead>
              <tbody>
                @foreach($distributor->detailAsset() as $item)
                  <tr>
                    <td><a href="{{ url($role . '/good/' . $item->id . '/detail') }}" style="color: blue" target="_blank()">{{ $item->name }}</a></td>
                    <td>{{ $item->total_loading }}</td>
                    <td>{{ $item->total_transaction }}</td>
                    <td>{{ $item->total_real }}</td>
                    <td>{{ showRupiah($item->total_real * $item->real_price) }}</td>
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
    });

    function highlight(id)
    {
      if($("#" + id).prop('checked') == true)
        $('#div-' + id).css('background-color', "{{ config('app.app_color') }}");
      else
        $('#div-' + id).css('background-color', "white");
    }
  </script>
@endsection