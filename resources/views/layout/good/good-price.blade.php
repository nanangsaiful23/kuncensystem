<div class="content-wrapper">
  @include('layout' . '.error')

  <section class="content">
    <div class="row">
      <div class="col-xs-12">
        <div class="box">
          <div class="box-header">
            <h3 class="box-title"> {{ $default['page_name'] . ' ' . $good->name }}</h3>
          </div>

		      {!! Form::model($good, array('url' => route($role . '.good.update-price', $good->id), 'method' => 'POST', 'class' => 'form-horizontal')) !!}
            <div class="box-body">
              @include('layout' . '.good.form-price', ['SubmitButtonText' => 'Edit'])
			        {{ method_field('PUT') }}
            </div>
          {!! Form::close() !!}

        </div>
      </div>
    </div>
  </section>
</div>