<div class="content-wrapper">
  @include('layout' . '.error')

  <section class="content">
    <div class="row">
      <div class="col-xs-12">
        <div class="box">

          {!! Form::model(old(),array('url' => route($role . '.transaction.store'), 'enctype'=>'multipart/form-data', 'method' => 'POST', 'class' => 'form-horizontal', 'id' => 'transaction-form')) !!}
          {{-- one token per opened form: a resubmit (double click, retry after the connection dropped) is not saved twice --}}
          <input type="hidden" name="submit_token" value="{{ \Illuminate\Support\Str::uuid() }}">
            <div class="box-body">
              @include('layout' . '.transaction.form', ['SubmitButtonText' => 'Tambah'])
            </div>
          {!! Form::close() !!}

        </div>
      </div>
    </div>
  </section>
</div>