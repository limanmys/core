<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __("Liman Merkezi Yönetim Sistemi") }}</title>
    <meta name="server_id" content="{{ request('server_id') ? request('server_id') : '' }}">
    <meta name="extension_id" content="{{ request('extension_id') ? request('extension_id') : '' }}">
    <link rel="stylesheet" href="/wizard/build.css">
    <script src="/wizard/jquery.js"></script>
    <script src="/wizard/app.js"></script>
</head>

<body class="antialiased">
    <div class="wrapper min-h-screen flex flex-col items-center justify-center w-6/12 mx-auto">
        <img class="mb-5 h-20 z-50 w-auto filter invert brightness-100 select-none" draggable="false"
            src="/images/limanlogo.svg" alt="Liman MYS" style="max-height: 50px;">
        <div class="w-full bg-gray-50 rounded z-50 py-10 shadow-sm text-gray-800">
            <div class="grid grid-cols-6 gap-4">
                @include("wizard.nav")
                <div class="col-span-4 px-5 relative h-full">
                    @yield("content")

                    @if (!isset($noButtons))
                    <div class="absolute bottom-0 flex justify-between w-full">
                        <div class="flex justify-between" style="width: calc(100% - 40px)">
                            @if ($step != 1)
                            <div class="w-1/2">
                                <button
                                    onclick="location.href='{{ route('wizard', $step - 1) }}'"
                                    class="w-32 focus:outline-none py-2 px-5 rounded shadow-sm text-center text-gray-600 bg-white hover:bg-gray-100 font-medium border">{{ __("Geri") }}</button>
                            </div>
                            @endif

                            <div class="@if ($step != 1) w-1/2 @else w-full @endif text-right">
                                @isset($skip)
                                <button
                                    id="skip"
                                    onclick="location.href='{{ route('wizard', $step + 1) }}'"
                                    style="width: 80px;"
                                    class="focus:outline-none border border-transparent py-2 rounded text-center font-medium">{{ __("Atla") }}</button>
                                @endisset
                                
                                <button
                                    id="next"
                                    @if (!isset($onclick)) onclick="location.href='{{ route('wizard', $step + 1) }}'" @else onclick="{{ $onclick }}" @endif
                                    class="w-32 focus:outline-none border border-transparent py-2 px-5 rounded shadow-sm text-center text-white bg-blue-500 hover:bg-blue-600 font-medium">{{ __("İleri") }}</button>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            @if (isset($progress))
            <div class="h-4 bg-gray-400 rounded mt-10 -mb-3 mx-5">
                <div class="{{ $progressClass }} h-full text-center text-xs text-white bg-green-300 rounded">
                    {{ $progress }}%
                </div>
            </div>
            @endif
        </div>
    </div>
</body>

</html>