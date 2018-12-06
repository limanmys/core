<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ __("Liman Sistem Yönetimi") }}</title>

    <!-- Scripts -->
    <script src="{{ asset('js/jquery-3.3.1.min.js') }}"></script>
    <script src="{{asset('js/popper.min.js')}}"></script>
    <script src="{{ asset('js/bootstrap.min.js') }}"></script>

    <!-- Styles -->
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/main.css') }}" rel="stylesheet">
    <link href="{{ asset('css/fa.min.css') }}" rel="stylesheet">

    <style>
        .sidebar {
            color: #FFF;
            background: #2b2b2d;
            width: 320px;
            max-width: 400px;
            height: 100%;
            float: left;
            position: fixed;
            z-index: 1000;
            display: block;
            flex:1;
        }
        #navbar-toggle {
            cursor: pointer;
        }
        #toggleView {
            margin-left: 44px;
        }
        .menu-icon {
            float: right;
        }
        .sidebar-nav {
            display: block;
            float: left;
            width: 100%;
            list-style: none;
            margin: 0;
            padding: 0;
        }
        .sidebar-nav li a {
            font-size: 16px;
            text-decoration: none;
            color: #FFF;
            float: left;
            width: 100%;
            height: 50px;
            line-height: 15px;
            padding: 20px;
            vertical-align: center;
        }
        .sidebar-nav li a:hover {
            background:#121213;
            -webkit-transition: background 0.1s;
            transition: background 0.1s;
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-dark fixed-top bg-dark flex-md-nowrap p-0 shadow">
        <a class="navbar-brand col-sm-3 col-md-2 mr-0" href="/">{{ __("Liman Sistem Yönetimi") }}</a>
        @auth
        <input class="form-control form-control-dark w-80" type="text" placeholder="{{ __("Arama") }}" aria-label="{{ __("Arama") }}">
        @endauth
        <ul class="navbar-nav px-3">
            <li class="nav-item text-nowrap">
                @if (Session::get('locale') == "tr")
                    <a class="nav-link text-white" onclick="language('en')">EN</a>
                @else
                    <a class="nav-link text-white" onclick="language('tr')">TR</a>
                @endif

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
                            <a href="{{route('home')}}">{{ __("Ana Sayfa") }}<i data-toggle="tooltip" data-placement="bottom" title="Ana Sayfa" class="fa fa-home menu-icon" aria-hidden="true"></i></a>
                        </li>
                        @p_server
                            <li>
                                <a href="{{route('servers')}}">{{ __("Sunucular") }}<i data-toggle="tooltip" data-placement="bottom" title="Sunucular" class="fa fa-download menu-icon" aria-hidden="true"></i></a>
                            </li>
                        @endp_server
                        @foreach($extensions as $extension)
                            <li>
                                <a href="/l/{{$extension->name}}">{{ __($extension->name) }}<i data-toggle="tooltip" data-placement="bottom" title="{{$extension->name}}" class="fa fa-cog menu-icon" aria-hidden="true"></i></a>
                            </li>
                        @endforeach
                        <li>
                            <a href="{{route('scripts')}}">{{ __("Betikler") }}<i data-toggle="tooltip" data-placement="bottom" title="Betikler" class="fa fa-cog menu-icon" aria-hidden="true"></i>
                            </a>
                        </li>
                        <li>
                            <a href="{{route('keys')}}">{{ __("SSH Anahtarları") }}<i data-toggle="tooltip" data-placement="bottom" title="SSH Anahtarları" class="fa fa-cog menu-icon" aria-hidden="true"></i>
                            </a>
                        </li>
                        <li>
                            <a href="{{route('extensions_settings')}}">{{ __("Eklentiler") }}<i data-toggle="tooltip" data-placement="bottom" title="Eklentiler" class="fa fa-cog menu-icon" aria-hidden="true"></i>
                            </a>
                        </li>
                        <li>
                            <a href="{{route('settings')}}">{{ __("Sistem Ayarları") }}<i data-toggle="tooltip" data-placement="bottom" title="Sistem Ayarları" class="fa fa-cog menu-icon" aria-hidden="true"></i>
                            </a>
                        </li>
                        <li>
                            <a onclick="navbar(true);" class="text-right"><i class="fa fa-bars menu-icon" aria-hidden="true"></i></a>
                        </li>
                    </ul>
                </div>
            @endauth
            <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-4">
                <br>
                @yield('content')
            </main>
        </div>
    </div>
        <script>
            @auth
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
            @endauth
            $(function () {
                $('[data-toggle="tooltip"]').tooltip();
                $('form').attr('target','#');
            });

            function navbar(flag) {
                if (localStorage.getItem("state") === "expanded") {
                    if(!flag){
                        $('.sidebar').css('margin-left', '0px');
                        $('main').removeClass('col-lg-11').addClass('col-lg-10');
                    }else{
                        $('.sidebar').css('margin-left', '-270px');
                        $('main').removeClass('col-lg-10').addClass('col-lg-11');
                        localStorage.setItem("state", "minimized");
                    }
                } else{
                    if(!flag){
                        $('.sidebar').css('margin-left', '-270px');
                        $('main').removeClass('col-lg-10').addClass('col-lg-11');
                    }else{
                        $('.sidebar').css('margin-left', '0px');
                        $('main').removeClass('col-lg-11').addClass('col-lg-10');
                        localStorage.setItem("state", "expanded");
                    }
                }
            }
            function language(locale){
                $.get("{{route('set_locale')}}", {
                    locale: locale,
                }, function (data, status) {
                    location.reload();
                });
            }
            navbar(false);
        </script>
</body>
</html>
