<div class="content-wrapper">
  @include('layout' . '.error')

  <section class="content">
    <div class="row">
      <div class="col-xs-12">
        <div class="box">
          <div class="box-header">
            <h3 class="box-title"> Detail Transaksi Lain</h3>
          </div>

          {!! Form::model($other_transaction, array('class' => 'form-horizontal')) !!}
            <div class="box-body">
              @include('layout' . '.other-transaction.form', ['SubmitButtonText' => 'View'])
            </div>
          {!! Form::close() !!}

        </div>
      </div>
    </div>
  </section>
</div>