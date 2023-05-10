<div class="content-wrapper">
  @include('layout' . '.error')

  <section class="content">
    <div class="row">
      <div class="col-xs-12">
        <div class="box">
          <div class="box-header">
            <h3 class="box-title"> Form Input Akun</h3>
          </div>

          {!! Form::model(old(),array('url' => route($role . '.account.store'), 'enctype'=>'multipart/form-data', 'method' => 'POST', 'class' => 'form-horizontal')) !!}
            <div class="box-body">
              @include('layout' . '.account.form', ['SubmitButtonText' => 'Tambah'])
            </div>
          {!! Form::close() !!}

        </div>
      </div>
    </div>
  </section>
</div>