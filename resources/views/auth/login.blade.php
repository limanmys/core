@extends('layouts.app')

@section('body_class', 'login-page')

@section('body')
    <div class="login-box">
        <div class="login-logo">
            <a><b><img src="/images/limanlogo.png" height="50"><br></b></a>
            <h5>{{env("BRAND_NAME")}}</h5>
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
                @if (session('status'))
                    <div class="alert alert-success">
                        {{ session('status') }}
                    </div>
                @endif
                <form action="{{ route('login')}}" method="post">
                    @csrf
                    <div class="input-group mb-3">
                        <input type="text" name="liman_email_mert" class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}" placeholder="{{__("Email Adresi")}}" value="{{ old('liman_email_mert') }}" required>
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-envelope"></span>
                            </div>
                        </div>
                    </div>
                    <div class="input-group mb-3">
                        <input type="password" name="liman_password_baran" class="form-control {{ $errors->has('password') ? 'is-invalid' : '' }}" placeholder="Parola">
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
        <center><a href="https://liman.havelsan.com.tr" target="_blank"><img src="/images/havelsan-aciklab.png" height="50"></a></center>
    </div>
    <style>
        .login-page, .card-body {
            background: linear-gradient(261deg, #007bff, #343a40);
            background-size: 400% 400%;

            -webkit-animation: AnimationName 0s ease infinite;
            -moz-animation: AnimationName 0s ease infinite;
            animation: AnimationName 0s ease infinite;
        }

        @-webkit-keyframes AnimationName {
            0%{background-position:0% 50%}
            50%{background-position:100% 50%}
            100%{background-position:0% 50%}
        }
        @-moz-keyframes AnimationName {
            0%{background-position:0% 50%}
            50%{background-position:100% 50%}
            100%{background-position:0% 50%}
        }
        @keyframes AnimationName {
            0%{background-position:0% 50%}
            50%{background-position:100% 50%}
            100%{background-position:0% 50%}
        }
        .login-box, .card-body {
            color:white;
        }
    </style>
@stop