<div class="content-wrapper">
  @include('layout' . '.error')

  <section class="content">
    <div class="row">
      <div class="col-xs-12">
        <div class="box">
          <div class="box-header">
            <h3 class="box-title"> Form Tambah Foto Barang <a href="{{ url($role . '/good/' . $good->id . '/detail') }}">{{ $good->name }} ({{ $good->code }})</a></h3>
          </div>

          {!! Form::model(old(),array('url' => route($role . '.good-photo.store', [$good->id]), 'enctype'=>'multipart/form-data', 'method' => 'POST', 'class' => 'form-horizontal')) !!}
            <div class="box-body">
              @include('layout' . '.good-photo.form', ['SubmitButtonText' => 'Tambah'])
            </div>
          {!! Form::close() !!}

        </div>
      </div>
    </div>
  </section>
</div>