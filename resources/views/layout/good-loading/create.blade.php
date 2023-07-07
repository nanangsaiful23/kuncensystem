<div class="content-wrapper">
  @include('layout' . '.error')

  <section class="content">
    <div class="row">
      <div class="col-xs-12">
        <div class="box">
          <div class="box-header">
            <h3 class="box-title"> Form Input Loading</h3>
            <h5>Jika membuat barang baru (yang belum ada di sistem) silahkan buat barang baru di bagian paling bawah</h5>
            <h5>Jika mmebuat harga satuan baru, pilih barang dari daftar kemudian pilih satuannya</h5>
          </div>

          {!! Form::model(old(),array('url' => route($role . '.good-loading.store'), 'enctype'=>'multipart/form-data', 'method' => 'POST', 'class' => 'form-horizontal', 'id' => 'loading-form')) !!}
            <div class="box-body">
              @include('layout' . '.good-loading.form', ['SubmitButtonText' => 'Tambah'])
            </div>
          {!! Form::close() !!}

        </div>
      </div>
    </div>
  </section>
</div>