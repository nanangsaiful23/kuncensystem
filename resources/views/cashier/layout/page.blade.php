@extends('layout.user', ['role' => 'cashier', 'title' => 'Cashier'])

@section('content')
  @include('layout.' . $default['page'] . '.' . $default['section'], ['role' => 'cashier'])
@endsection