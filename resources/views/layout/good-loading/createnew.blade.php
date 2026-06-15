<div class="content-wrapper">
  @include('layout' . '.error')

  <section class="content">
    <div class="row">
      <div class="col-xs-12">
        <div class="box">
            <h5 > {{ $default['page_name'] }}</h5>
          {!! Form::model(old(),array('url' => route($role . '.good-loading.store', $type), 'enctype'=>'multipart/form-data', 'method' => 'POST', 'class' => 'form-horizontal', 'id' => 'loading-form', 'onkeydown' => 'return event.key !== "Enter";')) !!}
            <div class="box-body">
              @include('layout' . '.good-loading.formnew', ['SubmitButtonText' => 'Tambah'])
            </div>
          {!! Form::close() !!}

        </div>
      </div>
    </div>
  </section>
</div>