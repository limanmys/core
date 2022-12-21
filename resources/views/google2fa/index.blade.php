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
          @if (env("BRANDING") != "")
          <div class="flex items-center justify-center w-full">
            <img src="{{ env('BRANDING') }}" alt="{{ env('BRAND_NAME') }}" style="max-width: 200px;">
          </div>
          @endif
          @if ($errors->count() > 0 )
              <div class="alert alert-danger">
                  {{ __($errors->first()) }}
              </div>
          @endif
          <div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
              {{ __('İki Aşamalı Doğrulama') }}
            </h2>
          </div>
          
          <p style="text-align: center; line-height: 20px">{{ __('Please enter the OTP generated on your Authenticator App. Ensure you submit the current one because it refreshes every 30 seconds.') }}</p>

          <form class="mt-8 space-y-6" action="{{ route('2fa') }}" method="post" autocomplete="off">
            @csrf
            <input type="hidden" name="remember" value="true">
            <div class="rounded-md shadow-sm -space-y-px">
              <div>
                <label for="one_time_password">{{__('Doğrulama Kodu')}}</label>
                <input id="one_time_password" name="one_time_password" type="text" autocomplete="email" required class="appearance-none rounded-md relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm" placeholder="••••••" value="{{ old('one_time_password') }}">
              </div>
            </div>

            <div>
              <button type="submit" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                {{__("Giriş Yap ")}}
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