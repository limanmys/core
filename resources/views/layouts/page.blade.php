@extends('layouts.master')

@section('adminlte_css')
    <link rel="stylesheet"
          href="{{ asset('vendor/adminlte/dist/css/skins/skin-blue.min.css')}} ">
    @stack('css')
    @yield('css')
@stop

@section('body_class', 'skin-blue sidebar-mini sidebar-collapse')

@section('body')
    @if(auth()->user()->status == "1")
        <div class="alert-warning" align="center">
            {{__("Yönetici Hesabı İle Giriş Yaptınız.")}}
        </div>
    @endif
    <div class="wrapper" style="height: auto">
        @auth
            <script>
                window.onload = function () {
                    setInterval(function () {
                        checkNotifications();
                    }, 3000);
                };
                let csrf = document.getElementsByName('csrf-token')[0].getAttribute('content');
            </script>
    @endauth
    <!-- Main Header -->
        <header class="main-header">

            <a href="/" class="logo">

                <span class="logo-mini"><b>L</b></span>

                <span class="logo-lg"><b>Liman</b></span>
            </a>

            <!-- Header Navbar -->
            <nav class="navbar navbar-static-top" role="navigation">
                <!-- Sidebar toggle button-->
                <a href="#" class="sidebar-toggle" data-toggle="push-menu" role="button">
                    <span class="sr-only">{{__("Geri Dön")}}</span>
                </a>

                <!-- Navbar Right Menu -->
                <div class="navbar-custom-menu">
                    <ul class="nav navbar-nav">

                        <!-- Notifications: style can be found in dropdown.less -->
                        <li id="notifications-menu" class="dropdown notifications-menu"
                            style="margin-top:6px">
                            @include('l.notifications')
                        </li>

                        <!-- User Account: style can be found in dropdown.less -->
                        <li class="dropdown user user-menu">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                                <span class="hidden-xs">{{auth()->user()->name}}</span>
                            </a>
                            <ul class="dropdown-menu">
                                <!-- Menu Footer-->
                                <li class="user-footer">
                                    <div class="pull-left">
                                        <a href="#"
                                           class="btn btn-default btn-flat">{{__("Profil")}}</a>
                                    </div>
                                    <div class="pull-right">
                                        <a onclick="request('/cikis',new FormData(),null)"
                                           class="btn btn-default btn-flat">{{__("Çıkış Yap")}}</a>
                                    </div>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </nav>
        </header>

        <aside class="main-sidebar">

            <section class="sidebar">

                <ul class="sidebar-menu" data-widget="tree">
                    <!-- Sidebar Menu -->
                    @if(auth()->user()->favorites && count(auth()->user()->favorites))
                        <li class="header">{{__("Favori Sunucular")}}</li>
                        @foreach(\App\Server::find(auth()->user()->favorites) as $favorite)
                            <li class="treeview">
                                <a href="#">
                                    <i class="fa fa-fw fa-server "></i>
                                    <span>{{$favorite->name}}</span>
                                    <span class="pull-right-container">
                                            <i class="fa fa-angle-left pull-right"></i>
                                        </span>
                                </a>
                                <ul class="treeview-menu">
                                    <li class="">
                                        <a href="/sunucular/{{$favorite->_id}}">
                                            <i class="fa fa-fw fa-info "></i>
                                            <span>{{__("Sunucu Detayları")}}</span>
                                        </a>
                                    </li>

                                    @foreach(\App\Extension::find(array_keys($favorite->extensions)) as $extension)
                                        <li class="">
                                            <a href="/l/{{$extension->_id}}/{{$favorite->city}}/{{$favorite->_id}}">
                                                <i class="fa fa-fw fa-{{$extension->icon}} "></i>
                                                <span>{{$extension->name}}</span>
                                            </a>
                                        </li>
                                    @endforeach
                                    @if($favorite->type == "linux_ssh")
                                        <li class="">
                                            <a onclick="terminal('{{$favorite->_id}}','{{$favorite->name}}')" href="#">
                                                <i class="fa fa-fw fa-info "></i>
                                                <span>{{__("Terminal")}}</span>
                                            </a>
                                        </li>
                                    @endif
                                </ul>

                            </li>
                        @endforeach
                    @endif
                    <li class="header">{{__("Sunucular")}}</li>
                    <li class="">
                        <a href="/sunucular">
                            <i class="fa fa-fw fa-server "></i>
                            <span>{{__("Sunucular")}}</span>
                        </a>
                    </li>
                    @if(count(extensions()))
                        <li class="header">{{__("Eklentiler")}}</li>
                    @endif
                    @foreach(extensions() as $extension)
                        <li class="">
                            <a href="/l/{{$extension->_id}}">
                                <i class="fa fa-fw fa-{{$extension->icon}} "></i>
                                <span>{{__($extension->name)}}</span>
                            </a>
                        </li>
                    @endforeach

                    @if(auth()->user()->isAdmin())
                        <li class="header">{{__("Yönetim Paneli")}}</li>
                        <li class="">
                            <a href="/eklentiler">
                                <i class="fa fa-fw fa-plus "></i>
                                <span>{{__("Eklentiler")}}</span>
                            </a>
                        </li>
                        <li class="">
                            <a href="/betikler">
                                <i class="fa fa-fw fa-subscript "></i>
                                <span>{{__("Betikler")}}</span>
                            </a>
                        </li>
                        <li class="">
                            <a href="/ayarlar">
                                <i class="fa fa-fw fa-plus "></i>
                                <span>{{__("Ayarlar")}}</span>
                            </a>
                        </li>
                        <li class="">
                            <a href="/talepler">
                                <i class="fa fa-fw fa-plus "></i>
                                <span>{{__("Yetki Talepleri")}}</span>
                            </a>
                        </li>
                    @else
                        <li class="header">{{__("Yetki Talebi")}}</li>
                        <li class="">
                            <a href="/taleplerim">
                                <i class="fa fa-fw fa-key "></i>
                                <span>{{__("Taleplerim")}}</span>
                            </a>
                        </li>
                    @endif

                    <li class="header">{{__("Ayarlar")}}</li>
                    <li class="">
                        <a href="/anahtarlar">
                            <i class="fa fa-fw fa-key "></i>
                            <span>{{__("Anahtarlar")}}</span>
                        </a>
                    </li>
                    <li class="">
                        <a href="/widgetlar"
                        >
                            <i class="fa fa-fw fa-key "></i>
                            <span>{{__("Widgetlar")}}</span>
                        </a>
                    </li>

                </ul>
                <!-- /.sidebar-menu -->
            </section>
            <!-- /.sidebar -->
        </aside>

        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">
            <!-- Content Header (Page header) -->
            <section class="content-header">
                @yield('content_header')
            </section>

            <!-- Main content -->
            <section class="content">

                @yield('content')

            </section>
        </div>
        <!-- /.content-wrapper -->

    </div>

    <script>
        function terminal(serverId, name) {
            let elm = $("#terminal");
            $("#terminal .modal-body iframe").attr('src', '/sunucu/terminal?server_id=' + serverId);
            $("#terminal .modal-title").html(name + '{{__(" sunucusu terminali")}}');
            elm.modal('show');
            elm.on('hidden.bs.modal', function () {
                $("#terminal .modal-body iframe").attr('src', '');
            })
        }
    </script>
@stop
@include('l.modal-iframe',[
    "id" => "terminal",
    "url" => '',
    "title" => ""
])

@section('adminlte_js')
    <script src="{{ asset('vendor/adminlte/dist/js/adminlte.min.js') }}"></script>
    @stack('js')

    @yield('js')
@stop
