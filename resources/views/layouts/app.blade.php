<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ __("Liman Sistem Yönetimi") }}</title>

    <!-- Scripts -->
    <script src="{{ asset('js/jquery-3.3.1.min.js') }}"></script>
    <script src="{{ asset('js/bootstrap.min.js') }}"></script>

    <!-- Styles -->
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/main.css') }}">
    <link rel="stylesheet" href="{{ asset('css/fa.min.css') }}">
</head>
<body>
@auth
    <nav class="navbar navbar-dark fixed-top bg-dark flex-md-nowrap p-0 shadow">

        <a class="navbar-brand col-sm-3 col-md-2 mr-0" href="#">{{ __("Liman Sistem Yönetimi") }}</a>

        <input class="form-control form-control-dark w-100" type="text" placeholder="{{ __("Arama") }}" aria-label="{{ __("Arama") }}">
        <ul class="navbar-nav px-3">
            <li class="nav-item text-nowrap">
                <a class="nav-link text-white" href="#">{{ __("Çıkış Yap") }}</a>
            </li>
        </ul>

    </nav>
@endauth
    <div class="container-fluid">
        <div class="row">
            @auth
            <nav class="col-md-2 d-none d-md-block bg-dark sidebar">
                <div class="sidebar-sticky">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link text-white" href="{{route('home')}}">
                                <i class="fas fa-anchor"></i>
                                <span>{{ __("Ana Sayfa") }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="{{route('servers')}}">
                                <i class="fas fa-anchor"></i>
                                <span>{{ __("Sunucular") }}</span>
                            </a>
                        </li>
                        @foreach($extensions as $extension)
                            <li class="nav-item">
                                <a class="nav-link text-white" href="/l/{{$extension->name}}">
                                    <i class="fas fa-anchor"></i>
                                    <span>{{$extension->name}}</span>
                                </a>
                            </li>
                        @endforeach

                        <li class="nav-item">
                            <a class="nav-link text-white" href="{{route('scripts')}}">
                                <i class="fas fa-anchor"></i>
                                <span>{{ __("Betikler") }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="{{route('keys')}}">
                                <i class="fas fa-anchor"></i>
                                <span>{{ __("SSH Anahtarları") }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="{{route('extensions_settings')}}">
                                <i class="fas fa-anchor"></i>
                                <span>{{ __("Eklentiler") }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="{{route('users')}}">
                                <i class="fas fa-anchor"></i>
                                <span>{{ __("Liman Kullanıcıları") }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="{{route('settings')}}">
                                <i class="fas fa-anchor"></i>
                                <span>{{ __("Sistem Ayarları") }}</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>
            @endauth
            <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-4">
                <br>
                @yield('content')
            </main>
        </div>
    </div>
    @auth
        <script>
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
        </script>
    @endauth
</body>
</html>
