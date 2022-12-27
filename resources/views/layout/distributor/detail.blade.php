<div class="content-wrapper">
  @include('layout' . '.error')

  <section class="content">
    <div class="row">
      <div class="col-xs-12">
        <div class="box">
          <div class="box-header">
            <h3 class="box-title"> Detail Distributor</h3>
          </div>

          {!! Form::model($distributor, array('class' => 'form-horizontal')) !!}
            <div class="box-body">
              @include('layout' . '.distributor.form', ['SubmitButtonText' => 'View'])
            </div>
          {!! Form::close() !!}

        </div>
      </div>
    </div>
  </section>
</div>