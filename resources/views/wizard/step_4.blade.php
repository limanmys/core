<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __("Liman Merkezi Yönetim Sistemi") }}</title>
    <meta name="server_id" content="{{request('server_id') ? request('server_id') : ''}}">
    <meta name="extension_id" content="{{request('extension_id') ? request('extension_id') : ''}}">
    <link rel="stylesheet" href="{{ asset('/wizard/build.css') }}">
    <script src="{{ asset('/wizard/jquery.js') }}"></script>
    <script src="{{ asset('/wizard/app.js') }}"></script>
</head>

<body class="antialiased">
    <div class="wrapper min-h-screen flex flex-col items-center justify-center w-4/12 mx-auto">
        <img class="mb-5 h-20 z-50 w-auto filter invert brightness-100 select-none" draggable="false"
            src="{{ asset('/images/limanlogo.svg') }}" alt="Liman MYS" style="max-height: 50px;">
        <div class="w-full bg-gray-50 rounded z-50 py-10 shadow-sm text-gray-800">
            <div class="px-7">
                <svg class="mb-4 h-20 w-20 text-green-500 mx-auto" viewBox="0 0 20 20" fill="currentColor">  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>

                <h2 class="text-2xl mb-4 text-gray-800 text-center font-bold">{{ __("Kurulum başarılı!") }}</h2>

                <div class="text-gray-600 mb-8 text-center">
                    {{ __("Liman'ı kullandığınız için teşekkür ederiz. Oluşturduğunuz kullanıcı hesabı ile Liman MYS'ye giriş yapabilirsiniz. Yeni eklenti mağazamızı kullanarak ücretsiz eklentilerimizi indirip sunucunuzda kullanabilirsiniz!") }}
                </div>

                <button onclick='location.href="{{ route("finish_wizard") }}"' class="w-40 block mx-auto focus:outline-none py-2 px-5 rounded-lg shadow-sm text-center text-gray-600 bg-white hover:bg-gray-100 font-medium border">{{ __("Tamamla") }}</button>
            </div>

            <div class="h-4 bg-gray-400 rounded mt-10 -mb-3 mx-5">
                <div class="w-full h-full text-center text-xs text-white bg-green-300 rounded">
                    100%
                </div>
            </div>
        </div>
    </div>
</body>

</html>
