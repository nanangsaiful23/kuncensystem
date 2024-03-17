@extends('cashier.layout.auth')

@section('content')
<div class="container" style="margin-top: 50px;">
    <div class="row">
        <div class="col-sm-8 col-md-6 col-md-offset-3">
            <a href="{{ url('/admin/login') }}"><img src="{{asset('assets/icon/logo.png')}}" alt="about" style="display: block;width: 80%;margin-left: auto;margin-right: auto;display: block;"></a>
        </div>
        <div class="col-md-8 col-md-offset-2" style="margin-top: 30px;">
            <div class="panel panel-default" style=" background-color: #FFFFFF;">
                <div class="panel-heading" style="background-color: #FFFFFF; text-align: center;"><h4>HALAMAN KASIR</h4></div>
                <div class="panel-body">
                    <form class="form-horizontal" role="form" method="POST" action="{{ url('/cashier/login') }}">
                        {{ csrf_field() }}

                        <div class="form-group{{ $errors->has('email') ? ' has-error' : '' }}">
                            <label for="email" class="col-md-4 control-label">Username</label>

                            <div class="col-md-5">
                                <input id="email" type="text" class="form-control" name="email" value="{{ old('email') }}" autofocus>

                                @if ($errors->has('email'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('email') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="form-group{{ $errors->has('password') ? ' has-error' : '' }}">
                            <label for="password" class="col-md-4 control-label">Password</label>

                            <div class="col-md-5">
                                <input id="password" type="password" class="form-control" name="password">

                                @if ($errors->has('password'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('password') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="col-md-12 col-xs-12">
                                <button type="submit" class="btn btn-xs btn-primary" style="display: block;margin-left: auto;margin-right: auto;width: 30%; font-size: 20px;border-color: #7868E6; background-color: white; color: black;">
                                    LOGIN
                                </button>
                            </div>
                            <div class="col-md-12 col-xs-12">
                                <a class="btn btn-link" href="{{ url('/cashier/password/reset') }}" style="display: block;margin-left: auto;margin-right: auto;">
                                    Saya lupa password
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
