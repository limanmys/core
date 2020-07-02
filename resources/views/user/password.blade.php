<?php
if (user()->forceChange != true) {
    header('Location: ' . route('home'));
    die();
} ?>

@extends('layouts.app')

@section('body_class', 'login-page')
<div style="position: absolute;top: 0px;right:0px;cursor:pointer;">
    <a onclick="request('/cikis',new FormData(),null)" class="btn btn-default btn-flat">{{__("Çıkış Yap")}}</a>
</div>
@section('body')
    <div class="login-box">
        <div class="login-logo">
            <a><b><img src="/images/limanlogo.png" height="50"><br></b></a>
            <h5>{{env("BRAND_NAME")}}</h5>
        </div>
        @if ($errors->count() > 0 )
        <div class="alert alert-danger">
            {{$errors->all()[0]}}
        </div>
    @endif
        <div class="card">
            <div class="card-body login-card-body">
                <div class="alert alert-info alert-dismissible">
                    {{__("Lütfen devam etmeden önce parolanızı değiştirin.")}}
                </div>
                @if(session('warning'))
                <div class="alert alert-warning">
                    {{session('warning')}}
                </div>
                @endif
                <form action="{{ route('password_change_save')}}" method="post">
                    @csrf
                    <div class="form-group has-feedback {{ $errors->has('password') ? 'has-error' : '' }}">
                        <input type="password" name="old_password" class="form-control"
                            placeholder="{{__("Mevcut Parola")}}" required>
                        <span class="glyphicon glyphicon-lock form-control-feedback"></span>
                    </div>
                    <div class="form-group has-feedback {{ $errors->has('password') ? 'has-error' : '' }}">
                        <input type="password" name="password" class="form-control"
                            placeholder="{{__("Yeni Parola")}}" required>
                        <span class="glyphicon glyphicon-lock form-control-feedback"></span>
                    </div>
                    <div class="form-group has-feedback {{ $errors->has('password') ? 'has-error' : '' }}">
                        <input type="password" name="password_confirmation" class="form-control"
                            placeholder="{{__("Yeni Parola Tekrar")}}" required>
                        <span class="glyphicon glyphicon-lock form-control-feedback"></span>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <button type="submit" class="btn btn-block btn-primary">{{__("Şifreyi Güncelle ")}}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
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