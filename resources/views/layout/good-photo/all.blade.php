<div class="content-wrapper">

  @include('layout' . '.alert-message', ['type' => $default['type'], 'data' => $default['data'], 'color' => $default['color']])

  <section class="content">
    <div class="row">
      <div class="col-xs-12">
        <div class="box">
          <div class="box-header">
            <h3 class="box-title">Daftar Gambar <a href="{{ url($role . '/good/' . $good->id . '/detail') }}">{{ $good->name }} ({{ $good->code }})</a></h3>
          </div>
          <div class="box-body" style="overflow-x:scroll">
            @foreach($good->good_photos as $photo)
              <div class="col-sm-4">
                <img src="{{ URL::to('image/' . $photo->location) }}" style="height: 300px;"><br>

                <button type="button" class="no-btn" data-toggle="modal" data-target="#modal-danger-{{$photo->id}}"><i class="fa fa-times red" aria-hidden="true"></i> Hapus foto</button>

                @include('layout' . '.delete-modal', ['id' => $photo->id, 'data' => 'foto ' . $good->name, 'formName' => 'delete-form-' . $photo->id])

                <form id="delete-form-{{$photo->id}}" action="{{ url($role . '/good/' . $good->id . '/photo/' . $photo->id . '/delete') }}" method="POST" style="display: none;">
                  {{ csrf_field() }}
                  {{ method_field('DELETE') }}
                </form>
              </div>
            @endforeach
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