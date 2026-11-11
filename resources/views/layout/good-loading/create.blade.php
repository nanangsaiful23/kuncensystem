{{-- Tambah loading (normal, internal, transaksi internal): one layout, see formnew --}}
<div class="content-wrapper">
  @include('layout' . '.error')

  <section class="content" style="padding:0">
    {!! Form::model(old(), array('url' => route($role . '.good-loading.store', $type), 'enctype' => 'multipart/form-data', 'method' => 'POST', 'class' => 'form-horizontal', 'id' => 'loading-form', 'onkeydown' => 'return event.key !== "Enter";')) !!}
      @include('layout' . '.good-loading.formnew', ['SubmitButtonText' => 'Tambah'])
    {!! Form::close() !!}
  </section>
</div>
