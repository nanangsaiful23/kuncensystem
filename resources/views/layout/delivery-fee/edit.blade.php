<div class="content-wrapper">
  @include('layout' . '.error')

  <section class="content">
    <div class="row">
      <div class="col-xs-12">
        <div class="box">
          <div class="box-header">
            <h3 class="box-title"> {{ $default['page_name'] }}</h3>
          </div>

		      {!! Form::model($fee, array('url' => route($role . '.delivery-fee.update', $fee->id), 'method' => 'POST', 'class' => 'form-horizontal')) !!}
            <div class="box-body">
              @include('layout' . '.delivery-fee.form', ['SubmitButtonText' => 'Edit'])
			        {{ method_field('PUT') }}
            </div>
          {!! Form::close() !!}

        </div>
      </div>
    </div>
  </section>
</div>