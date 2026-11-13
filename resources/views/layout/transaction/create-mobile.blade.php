{{-- Tambah transaksi versi HP: halaman sendiri, form & field sama dengan versi desktop (create + form) --}}
<div class="content-wrapper">
  @include('layout' . '.error')

  <section class="content" style="padding:0">
    {!! Form::model(old(), array('url' => route($role . '.transaction.store'), 'enctype'=>'multipart/form-data', 'method' => 'POST', 'id' => 'transaction-form')) !!}
      {{-- one token per opened form: a resubmit (double click, retry after the connection dropped) is not saved twice --}}
      <input type="hidden" name="submit_token" value="{{ \Illuminate\Support\Str::uuid() }}">
      @include('layout' . '.transaction.form-mobile')
    {!! Form::close() !!}
  </section>
</div>
