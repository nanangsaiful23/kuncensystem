<div class="content-wrapper">
  @include('layout' . '.error')

  <section class="content" style="background-color: yellow">
    <div class="row">
      <div class="col-xs-12">
        <div class="box">
          <div class="box-header">
            <h3 class="box-title"> Form Input Transaksi INTERNAL</h3>
          </div>

          {!! Form::model(old(),array('url' => route($role . '.internal-transaction.store'), 'enctype'=>'multipart/form-data', 'method' => 'POST', 'class' => 'form-horizontal', 'id' => 'transaction-form')) !!}
            <div class="box-body">
              @include('layout' . '.internal-transaction.form', ['SubmitButtonText' => 'Tambah'])
            </div>
          {!! Form::close() !!}

        </div>
      </div>
    </div>
  </section>
</div>