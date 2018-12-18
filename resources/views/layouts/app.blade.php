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
    <link rel="stylesheet" href="{{ asset('css/fa.min.css') }}">
</head>
<body style="display: block">
<nav class="navbar navbar-dark fixed-top bg-dark flex-md-nowrap p-0 shadow">
    <span class="navbar-brand col-sm-3 col-md-2 mr-0" style="cursor: default"><span
                style="line-height: 30px;">{{ __("Liman Sistem Yönetimi") }}</span><i
                style="cursor: pointer;line-height: 30px;" onclick="navbar(true)"
                class="float-right fas fa-bars"></i></span>
    @auth
        <input class="form-control form-control-dark w-80" type="text" placeholder="{{ __("Arama") }}"
               aria-label="{{ __("Arama") }}">
    @endauth
    <ul class="navbar-nav px-3">
        <li class="nav-item text-nowrap">
            <form action="#" onsubmit="return request('/locale',this,reload)" style="cursor: pointer;">
                @if (Session::get('locale') == "en")
                    <input type="hidden" name="locale" value="tr">
                    <button class="btn btn-link text-white-">TR</button>
                @else
                    <input type="hidden" name="locale" value="en">
                    <button class="btn btn-link text-white">EN</button>
                @endif

            </form>
        </li>
    </ul>
    @auth
        <ul class="navbar-nav px-3">
            <li class="nav-item text-nowrap" style="cursor: pointer;">
                <a class="nav-link text-white" onclick="return request('logout',null,reload)">{{Auth::user()->name}}</a>
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
                        <a href="{{route('home')}}" class="{{ Request::is(route('home')) ? 'selected' : '' }}">
                            <i class="fas fa-home"></i>&nbsp;
                            <span class="sidebar-name">{{ __("Ana Sayfa") }}</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{route('servers')}}">
                            <i class="fas fa-server"></i>&nbsp;
                            <span class="sidebar-name">{{ __("Sunucular") }}</span>
                        </a>
                    </li>
                    @foreach($extensions as $extension)
                        @p('extension',$extension->_id)
                        <li>
                            <a href="/l/{{$extension->_id}}">
                                @if($extension->icon)
                                    <i class="fas fa-{{$extension->icon}}"></i>&nbsp;
                                @else
                                    <i class="fas fa-circle"></i>&nbsp;
                                @endif
                                <span class="sidebar-name">{{ __($extension->name) }}</span>
                            </a>
                        </li>
                        @endp
                    @endforeach
                    @p('script')
                    <li>
                        <a href="{{route('scripts')}}">
                            <i class="fas fa-subscript"></i>&nbsp;
                            <span class="sidebar-name">{{ __("Betikler") }}</span>
                        </a>
                    </li>
                    @endp
                    <li>
                        <a href="{{route('keys')}}">
                            <i class="fas fa-key"></i>&nbsp;
                            <span class="sidebar-name">{{ __("SSH Anahtarları") }}</span>
                        </a>
                    </li>
                    @p('extension_manager')
                    <li>
                        <a href="{{route('extensions_settings')}}">
                            <i class="fas fa-plus"></i>&nbsp;
                            <span class="sidebar-name">{{ __("Eklentiler") }}</span>
                        </a>
                    </li>
                    @endp
                    @p('settings')
                    <li>
                        <a href="{{route('settings')}}">
                            <i class="fas fa-cog"></i>&nbsp;
                            <span class="sidebar-name">{{ __("Sistem Ayarları") }}</span>
                        </a>
                    </li>
                    @endp
                    @if(Auth::user()->isAdmin() == false)
                        <li>
                            <a href="{{route('request_permission')}}">
                                <i class="fas fa-lock"></i>&nbsp;
                                <span class="sidebar-name">{{ __("Yetki Talebi") }}</span>
                            </a>
                        </li>
                    @else
                        <li>
                            <a href="{{route('request_list')}}">
                                <i class="fas fa-lock"></i>&nbsp;
                                <span class="sidebar-name">{{ __("Yetki Talepleri") }}</span>
                            </a>
                        </li>
                    @endif
                </ul>
            </div>
        @endauth
        <main role="main">
            @yield('content')
        </main>
    </div>
    @include('__system__.loading')
</div>
</body>
</html>
