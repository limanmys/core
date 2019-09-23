<?php
    if(user()->forceChange != true){
        header('Location: '.route('home'));
        die();
    }
?>
@extends('layouts.app')

@section('body_class', 'login-page')
<div style="position: absolute;top: 0px;right:0px;">
    <a onclick="request('/cikis',new FormData(),null)" class="btn btn-default btn-flat">{{__("Çıkış Yap")}}</a>
</div>
@section('body')
    <div class="login-box">
        <div class="login-logo">
            <b>Liman</b>
        </div>
        @if ($errors->count() > 0 )
        <div class="alert alert-danger">
            {{$errors->all()[0]}}
        </div>
    @endif
        <div class="login-box-body">
                <h5>{{__("Lütfen devam etmeden önce parolanızı değiştirin.")}}</h5>
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
                    <div class="col-xs-4">
                        <button type="submit" class="btn btn-primary">{{__("Şifreyi Güncelle ")}}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@stop