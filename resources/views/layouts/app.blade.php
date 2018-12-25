@auth
    @php($notifications = notifications())
@endauth
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
<nav class="navbar navbar-dark fixed-top bg-dark flex-md-nowrap p-0">
    <span class="navbar-brand col-sm-3 col-md-2 mr-0" style="cursor: default">
        <span style="line-height: 30px;cursor:default;"><b>{{ __("Liman Sistem Yönetimi") }}</b></span></span>
    @auth
        <input class="form-control form-control-dark w-80" type="text" placeholder="{{ __("Arama") }}"
               aria-label="{{ __("Arama") }}" onkeyup="search();" id="search_input">
    <ul class="dropdown" style="list-style: none;margin-bottom:0px">
        <span class="px-3 text-white" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="cursor: pointer;">
            @if(count($notifications) > 0)
                <i class="fas fa-bell bell"></i>
            @else
                <i class="far fa-bell"></i>
            @endif
        </span>
        <ul id="notificationDiv" class="dropdown-menu shadow-lg border-dark" style="width: 400px;margin-left: -200px;">
            @include('__system__.notifications',$notifications)
        </ul>
    </ul>
    @endauth
    <ul class="navbar-nav">
        <li class="nav-item text-nowrap">
            <form action="#" onsubmit="return request('{{route('set_locale')}}',this,reload)" style="cursor: pointer;">
                @if (Session::get('locale') == "en")
                    <input type="hidden" name="locale" value="tr">
                    <button class="btn btn-link text-white-">
                        English
                    </button>
                @else
                    <input type="hidden" name="locale" value="en">
                    <button class="btn btn-link text-white">
                        Türkçe
                    </button>
                @endif

            </form>
        </li>
    </ul>
    @auth
        <ul class="navbar-nav px-2">
            <li class="nav-item text-nowrap" style="cursor: pointer;">
                <a class="nav-link text-white" onclick="return request('{{route('logout')}}',null,reload)"><i class="fas fa-sign-out-alt"></i></a>
            </li>
        </ul>
    @endauth
</nav>
<div class="container-fluid">
    <div class="row">
        @auth
            <div class="sidebar" onmouseover="navbar(true)" onmouseout="navbar(false)">
                <ul class="sidebar-nav">
                    <li>
                        <a href="{{route('home')}}">
                            <i class="fas fa-home"></i>&nbsp;
                            <span class="sidebar-name" style="visibility: hidden;">{{ __("Ana Sayfa") }}</span>
                            <span class="badge badge-info badge-pill" id="home_notifications">2</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{route('servers')}}">
                            <i class="fas fa-server"></i>&nbsp;
                            <span class="sidebar-name" style="visibility: hidden;">{{ __("Sunucular") }}</span>
                            <span class="badge badge-info badge-pill" id="server_notifications">4</span>
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
                                <span class="sidebar-name" style="visibility: hidden;">{{ __($extension->name) }}</span>
                                    <span class="badge badge-info badge-pill" id="{{strtolower($extension->name)}}_notifications">5</span>
                            </a>
                        </li>
                        @endp
                    @endforeach
                    @p('script')
                    <li>
                        <a href="{{route('scripts')}}">
                            <i class="fas fa-subscript"></i>&nbsp;
                            <span class="sidebar-name" style="visibility: hidden;">{{ __("Betikler") }}</span>
                            <span class="badge badge-info badge-pill" id="script_notifications">4</span>
                        </a>
                    </li>
                    @endp
                    <li>
                        <a href="{{route('keys')}}">
                            <i class="fas fa-key"></i>&nbsp;
                            <span class="sidebar-name" style="visibility: hidden;">{{ __("SSH Anahtarları") }}</span>
                            <span class="badge badge-info badge-pill" id="key_notifications">3</span>
                        </a>
                    </li>
                    @p('extension_manager')
                    <li>
                        <a href="{{route('extensions_settings')}}">
                            <i class="fas fa-plus"></i>&nbsp;
                            <span class="sidebar-name" style="visibility: hidden;">{{ __("Eklentiler") }}</span>
                            <span class="badge badge-info badge-pill" id="extensions_notifications">2</span>
                        </a>
                    </li>
                    @endp
                    @p('settings')
                    <li>
                        <a href="{{route('settings')}}">
                            <i class="fas fa-cog"></i>&nbsp;
                            <span class="sidebar-name" style="visibility: hidden;">{{ __("Sistem Ayarları") }}</span>
                            <span class="badge badge-info badge-pill" id="settings_notifications">1</span>
                        </a>
                    </li>
                    @endp
                    @if(Auth::user()->isAdmin() == false)
                        <li>
                            <a href="{{route('request_permission')}}">
                                <i class="fas fa-lock"></i>&nbsp;
                                <span class="sidebar-name" style="visibility: hidden;">{{ __("Yetki Talebi") }}</span>
                                <span class="badge badge-info badge-pill" id="request_notifications">2</span>
                            </a>
                        </li>
                    @else
                        <li>
                            <a href="{{route('request_list')}}">
                                <i class="fas fa-lock"></i>&nbsp;
                                <span class="sidebar-name" style="visibility: hidden;">{{ __("Yetki Talepleri") }}</span>
                                <span class="badge badge-info badge-pill" id="request_list_notifications">3</span>
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
<div class="winter-is-coming">

    <div class="snow snow--near"></div>
    <div class="snow snow--near snow--alt"></div>

    <div class="snow snow--mid"></div>
    <div class="snow snow--mid snow--alt"></div>

    <div class="snow snow--far"></div>
    <div class="snow snow--far snow--alt"></div>
</div>
</body>
</html>
