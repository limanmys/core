<?php
if (user()->forceChange != true) {
    header('Location: ' . route('home'));
    die();
} ?>
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">

<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>{{__("Liman Merkezi Yönetim Sistemi")}}</title>

  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="{{mix('/css/liman.css')}}">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
</head>

<body>
  <script>
      var module = { };
  </script>
  <script src="{{mix('/js/liman.js')}}"></script>
  <div class="container-fluid">
    <div class="row">
      <div class="col-4 auth-bg">
      <div class="flex items-center justify-center" style="height: 100vh; flex-direction: column;">
          <a href="https://liman.havelsan.com.tr">
              <img class="mx-auto h-12 w-auto" style="padding: 6px;" src="{{ asset('images/limanlogo.svg') }}" alt="Liman MYS">
            </a>
            <h2 class="text-center text-2xl font-extrabold" style="width: 300px;">
              <hr style="border-color: rgba(255,255,255,0.2)">
            </h2>
            <h6 style="color: #fff; font-weight: 600;">{{env("BRAND_NAME")}}</h6>
        </div>
      </div>
      <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8 col-8">
        <div style="position: absolute;top: 0px;right:0px;cursor:pointer;">
            <a onclick="request('/cikis',new FormData(),null)" style="padding:15px; display: block;">{{__("Çıkış Yap")}}</a>
        </div>
        <div class="max-w-md w-full space-y-8">
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
          <div>
            <h2 class="mt-6 text-center text-2xl font-extrabold text-gray-900">
                Lütfen devam etmeden önce parolanızı değiştirin.
                <hr>
                Please change your password before proceeding.
            </h2>
          </div>
          <form class="mt-8 space-y-6" action="{{ route('password_change_save')}}" method="post" autocomplete="off">
            @csrf
            <input type="hidden" name="remember" value="true">
            <div class="rounded-md shadow-sm -space-y-px">
              <div>
                <input type="password" name="old_password" autocomplete="off" class="appearance-none shadow-sm rounded relative block px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 sm:text-sm" placeholder="{{__("Mevcut Parola / Current Password")}}" required style="width: 100%;">
              </div>
            </div>
            <div class="rounded-md shadow-sm -space-y-px">
              <div>
                <input type="password" name="password" autocomplete="off" class="appearance-none shadow-sm rounded relative block px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 sm:text-sm" placeholder="{{__("Yeni Parola / New Password")}}" required style="width: 100%;">
              </div>
            </div>
            <div class="rounded-md shadow-sm -space-y-px">
              <div>
                <input type="password" name="password_confirmation" autocomplete="off" class="appearance-none shadow-sm rounded relative block px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 sm:text-sm" placeholder="{{__("Yeni Parola Tekrar / Password Confirmation")}}" required style="width: 100%;">
              </div>
            </div>
            <div>
              <button type="submit" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                  <svg class="h-5 w-5 text-indigo-500 group-hover:text-indigo-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                  </svg>
                </span>
                {{__("Şifreyi Güncelle / Update Password")}}
              </button>
            </div>

            <div class="flex align-items-center justify-center">
              <a href="https://aciklab.org" target="_blank">
                <img src="{{ asset('images/aciklab-dikey.svg') }}" alt="HAVELSAN Açıklab"
                   style="max-width: 120px;">
              </a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</body>
</html>