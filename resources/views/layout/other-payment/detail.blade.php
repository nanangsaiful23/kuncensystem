<div class="content-wrapper">
  @include('layout' . '.error')

  <section class="content">
    <div class="row">
      <div class="col-xs-12">
        <div class="box">
          <div class="box-header">
            <h3 class="box-title"> Detail Biaya Lain</h3>
          </div>

          {!! Form::model($other_payment, array('class' => 'form-horizontal')) !!}
            <div class="box-body">
              @include('layout' . '.other-payment.form', ['SubmitButtonText' => 'View'])
            </div>
          {!! Form::close() !!}

        </div>
      </div>
    </div>
  </section>
</div>