<div class="content-wrapper">
  @include('layout' . '.error')

  <section class="content">
    <div class="row">
      <div class="col-xs-12">
        <div class="box">
          <div class="box-header">
            <h3 class="box-title"> Form Edit Biaya Lain</h3>
          </div>

		      {!! Form::model($other_payment, array('url' => route($role . '.other-payment.update', $other_payment->id), 'method' => 'POST', 'class' => 'form-horizontal')) !!}
            <div class="box-body">
              @include('layout' . '.other-payment.form', ['SubmitButtonText' => 'Edit'])
			        {{ method_field('PUT') }}
            </div>
          {!! Form::close() !!}

        </div>
      </div>
    </div>
  </section>
</div>