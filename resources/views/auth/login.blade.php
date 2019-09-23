@extends('layouts.app')

@section('body_class', 'login-page')

@section('body')
    <div class="login-box">
        <div class="login-logo">
            <a href="{{ route('login') }}"><b>Liman</b></a>
        </div>
        <!-- /.login-logo -->
        <div class="login-box-body">
                @if ($errors->count() > 0 )
                <div class="alert alert-danger">
                    {{$errors->all()[0]}}
                </div>
            @endif
            @if(session('warning'))
            <div class="alert alert-warning">
                {{session('warning')}}
            </div>
            @endif
            <form action="{{ route('login')}}" method="post">
                @csrf
                <div class="form-group has-feedback {{ $errors->has('email') ? 'has-error' : '' }}">
                    <input type="email" name="email" class="form-control" value="{{ old('email') }}"
                           placeholder="{{__("Email Adresi")}}" required>
                    <span class="glyphicon glyphicon-envelope form-control-feedback"></span>
                    @if ($errors->has('email'))
                        <span class="help-block">
                            <strong>{{__("Giriş Yapılamadı.")}}</strong>
                        </span>
                    @endif
                </div>
                <div class="form-group has-feedback {{ $errors->has('password') ? 'has-error' : '' }}">
                    <input type="password" name="password" class="form-control"
                           placeholder="{{__("Parola")}}" required>
                    <span class="glyphicon glyphicon-lock form-control-feedback"></span>
                    @if ($errors->has('password'))
                        <span class="help-block">
                            <strong>{{__("Giriş Yapılamadı.")}}</strong>
                        </span>
                    @endif
                </div>
                <div class="row">
                    <div class="col-xs-7" style="margin-left:20px;margin-right: 8px;">
                        <div class="checkbox icheck">
                            <label>
                                <input type="checkbox" name="remember"> {{__("Beni Hatırla")}}
                            </label>
                        </div>
                    </div>
                    <div class="col-xs-4">
                        <button type="submit" class="btn btn-primary btn-block btn-flat">{{__("Giriş Yap ")}}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@stop