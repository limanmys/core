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
                <hr style="border-color: rgba(255,255,255,0.2) !important">
            </h2>
            <h6 style="color: #fff; font-weight: 600;">{{env("BRAND_NAME")}}</h6>
        </div>
      </div>
      <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8 col-8">
        <div class="max-w-md w-full space-y-8">
          <div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
              {{ __('Set up Google Authenticator') }}
            </h2>
          </div>
          <form class="mt-8 space-y-6" style="line-height: 22px" action="{{ route('set_google_secret') }}" method="post" autocomplete="off">
            @csrf
            <input type="hidden" id="_secret" name="_secret" value="{{ $secret }}" />
            <p style="text-align: center">{{ __('Set up your two factor authentication by scanning the barcode below. Alternatively, you can use the code') }} <strong>{{ $secret }}</strong></p>
            <div style="display: flex; justify-content: center;">
                {!! $QR_Image !!}
            </div>
            <p style="text-align: center">{{ __('You must set up your Google Authenticator app before continuing. You will be unable to login otherwise') }}</p>
            
            <div>
              <button type="submit" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                {{__("Complete Registration")}}
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