@extends('layout.user', ['role' => 'admin', 'title' => 'Admin'])

@section('content')
  @include('layout.' . $default['page'] . '.' . $default['section'], ['role' => 'admin'])
@endsection