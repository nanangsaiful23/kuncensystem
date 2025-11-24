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
                <th width="20%">Tanggal</th>
                <th width="40%">Nama</th>
                <th>Nominal</th>
              </tr>
              </thead>
              <tbody id="table-good">
                <?php $old = '-'; ?>
                @foreach($distributor->getLedgers($pagination) as $ledger)
                  <tr>
                    @if($old != $ledger->created_at)
                      <td>{{ displayDate($ledger->created_at) }}</td>
                    @else
                      <td></td>
                    @endif
                    <td>{{ $ledger->name }}</td>
                    <td>{{ showRupiah($ledger->nominal) }}</td>
                    <?php $old = $ledger->created_at; ?>
                  </tr>
                @endforeach
              </tbody>
              <div id="renderField">
                @if($pagination != 'all')
                  {{ $distributor->getLedgers($pagination)->render() }}
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