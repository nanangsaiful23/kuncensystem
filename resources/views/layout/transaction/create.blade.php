<div class="content-wrapper">
  @include('layout' . '.error')

  <section class="content">
    <div class="row">
      <div class="col-xs-12">
        <div class="box">

          {!! Form::model(old(),array('url' => route($role . '.transaction.store'), 'enctype'=>'multipart/form-data', 'method' => 'POST', 'class' => 'form-horizontal', 'id' => 'transaction-form')) !!}
            <div class="box-body">
              @include('layout' . '.transaction.form', ['SubmitButtonText' => 'Tambah'])
            </div>
          {!! Form::close() !!}

        </div>
      </div>
    </div>
  </section>
</div>