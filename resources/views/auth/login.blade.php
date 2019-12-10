@extends('layouts.app')

@section('body_class', 'login-page')

@section('body')
    <div class="login-box">
        <div class="login-logo">
            <a href="{{ route('login') }}"><b>Liman MYS</b></a>
        </div>
        <div class="card">
            <div class="card-body login-card-body">
                @if ($errors->count() > 0 )
                    <div class="alert alert-danger">
                        {{$errors->first()}}
                    </div>
                @endif
                @if(session('warning'))
                    <div class="alert alert-warning">
                        {{session('warning')}}
                    </div>
                @endif
                <form action="{{ route('login')}}" method="post">
                    @csrf
                    <div class="input-group mb-3">
                        <input type="text" name="liman_email_mert" class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}" placeholder="{{__("Email Adresi ve Ldap Kullanıcı Adı")}}" value="{{ old('liman_email_mert') }}" required>
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-envelope"></span>
                            </div>
                        </div>
                    </div>
                    <div class="input-group mb-3">
                        <input type="password" name="liman_password_baran" class="form-control {{ $errors->has('password') ? 'is-invalid' : '' }}" placeholder="Password">
                        <div class="input-group-append">
                            <div class="input-group-text">
                            <span class="fas fa-lock"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-8">
                            <div class="icheck-primary">
                            <input type="checkbox" id="remember" name="remember">
                            <label for="remember">
                                {{__("Beni Hatırla")}}
                            </label>
                            </div>
                        </div>
                        <div class="col-4">
                            <button type="submit" class="btn btn-primary btn-block">{{__("Giriş Yap ")}}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@stop