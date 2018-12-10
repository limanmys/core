<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __("Liman Sistem Yönetimi") }}</title>

    <!-- Scripts -->

    <script src="{{asset('js/liman.js')}}"></script>
    <script src="{{ asset('js/bootstrap-native-v4.min.js') }}"></script>

    <!-- Styles -->

    <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/main.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">
</head>
<body>

    <nav class="navbar navbar-dark fixed-top bg-dark flex-md-nowrap p-0 shadow">
        <a class="navbar-brand col-sm-3 col-md-2 mr-0" href="/">{{ __("Liman Sistem Yönetimi") }}</a>
        @auth
        <input class="form-control form-control-dark w-80" type="text" placeholder="{{ __("Arama") }}" aria-label="{{ __("Arama") }}">
        @endauth
        <ul class="navbar-nav px-3">
            <li class="nav-item text-nowrap">
                <form action="#" onsubmit="return request('/locale',this)">
                    @if (Session::get('locale') == "en")
                        <a class="nav-link text-white" onclick="language('tr')">TR</a>
                    @else
                        <a class="nav-link text-white" onclick="language('en')">EN</a>
                    @endif
                </form>
            </li>
        </ul>
        @auth
        <ul class="navbar-nav px-3">
            <li class="nav-item text-nowrap">
                <a class="nav-link text-white" href="#">{{Auth::user()->name}}</a>
            </li>
        </ul>
        @endauth
    </nav>
    <div class="container-fluid">
        <div class="row">
            @auth
                <div class="sidebar">
                    <ul class="sidebar-nav">
                        <li>
                            <a href="{{route('home')}}">{{ __("Ana Sayfa") }}</a>
                        </li>
                        <li>
                            <a href="{{route('servers')}}">{{ __("Sunucular") }}</a>
                        </li>
                        @foreach($extensions as $extension)
                            @p('extension',$extension->_id)
                            <li>
                                <a href="/l/{{$extension->_id}}">{{ __($extension->name) }}</a>
                            </li>
                            @endp
                        @endforeach
                        @p('script')
                        <li>
                            <a href="{{route('scripts')}}">{{ __("Betikler") }}</a>
                        </li>
                        @endp
                        <li>
                            <a href="{{route('keys')}}">{{ __("SSH Anahtarları") }}</a>
                        </li>
                        @p('extension_manager')
                        <li>
                            <a href="{{route('extensions_settings')}}">{{ __("Eklentiler") }}</a>
                        </li>
                        @endp
                        @p('settings')
                        <li>
                            <a href="{{route('settings')}}">{{ __("Sistem Ayarları") }}</a>
                        </li>
                        @endp
                        @if(Auth::user()->isAdmin() == false)
                        <li>
                            <a href="{{route('request_permission')}}">{{ __("Yetki Talebi") }}</a>
                        </li>
                        @endif
                        <li>
                            <a onclick="navbar(true);" class="text-right">Toggle</a>
                        </li>
                    </ul>
                </div>
            @endauth
            <main role="main" class="col-md-9 ml-md-5 col-lg-10 px-4">
                <br>
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
