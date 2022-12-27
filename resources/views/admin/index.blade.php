@extends('layout.user', ['role' => 'admin', 'title' => 'Admin'])

@section('content')

  <div class="content-wrapper">
    <section class="content-header">
      <h1>
        Hi, {{ \Auth::user()->name }}<br>
        Selamat datang di aplikasi {{ config('app.name') }}<br>
        <small>Anda login sebagai Admin</small>
      </h1>
    </section>
  </div>

@endsection
