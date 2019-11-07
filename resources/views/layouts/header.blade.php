   <!-- Navbar -->
   <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <!-- Left navbar links -->
        <ul class="navbar-nav">
          <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#"><i class="fas fa-bars"></i></a>
          </li>
          <li class="nav-item d-none d-sm-inline-block">
            <a href="index3.html" class="nav-link">Home</a>
          </li>
          <li class="nav-item d-none d-sm-inline-block">
            <a href="#" class="nav-link">Contact</a>
          </li>
        </ul>
    
        <!-- SEARCH FORM -->
        <form class="form-inline ml-3">
          <div class="input-group input-group-sm">
            <input class="form-control form-control-navbar" type="search" placeholder="Search" aria-label="Search">
            <div class="input-group-append">
              <button class="btn btn-navbar" type="submit">
                <i class="fas fa-search"></i>
              </button>
            </div>
          </div>
        </form>
    
        <!-- Right navbar links -->
        <ul class="navbar-nav ml-auto">
          <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#">
                {{__("Dil")}}
            </a>
            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                @if (session('locale') === "tr")
                <a class="dropdown-item" href="javascript:void(0)" style="cursor: not-allowed;"><b>Türkçe</b></a>
                <a class="dropdown-item" href="{{route('set_locale', ['locale' => 'en'])}}">English</a>
                @elseif (session('locale') === "en")
                <a class="dropdown-item" href="{{route('set_locale', ['locale' => 'tr'])}}">Türkçe</a>
                <a class="dropdown-item" href="javascript:void(0)" style="cursor: not-allowed;"><b>English</b></a>
                @endif
            </div>
          </li>
          <li class="nav-item">
                <span class="nav-link">
                    {{__("Versiyon : ") . env('APP_VERSION')}}
                </span>
          </li>
          <!-- Notifications Dropdown Menu -->
          <li id="adminNotifications" class="nav-item dropdown">
            @include('l.notifications',["notifications" => adminNotifications(),"id" =>
            "adminNotifications","systemNotification" => true])
          </li>
          <li id="userNotifications" class="nav-item dropdown">
            @include('l.notifications',["notifications" => notifications()])
          </li>
          <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#">
                    <i class="fa fa-user"></i>
                    <span class="hidden-xs">{{user()->name}}</span>
                </a>

                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                    <div class="card card-widget widget-user-2" style="margin-bottom: 0px;">
                        <div class="widget-user-header bg-warning">
                          <h3 class="widget-user-username" style="margin-left: 0px;">{{user()->name}}</h3>
                          <h5 class="widget-user-desc" style="margin-left: 0px;font-size: 13px;">{{__("Son Giriş Tarihi : ") . user()->last_login_at}}</h5>
                          <h5 class="widget-user-desc" style="margin-left: 0px;font-size: 13px;">{{__("Giriş Yapılan Son Ip : ") . user()->last_login_ip}}</h5>
                        </div>
                        <div class="card-footer p-0">
                          <ul class="nav flex-column">
                            <li class="nav-item">
                              <a href="{{route('my_profile')}}" class="nav-link">
                                {{__("Profil")}}
                              </a>
                            </li>
                            <li class="nav-item">
                              <a onclick="request('/cikis',new FormData(),null)" class="nav-link">
                                {{__("Çıkış Yap")}}
                              </a>
                            </li>
                          </ul>
                        </div>
                    </div>
                </div>
            </li>
        </ul>
      </nav>
      <!-- /.navbar -->
    
      <!-- Main Sidebar Container -->
      <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <!-- Brand Logo -->
        <a href="/" class="brand-link">
          <img src="/images/liman_logo_white.png" alt="AdminLTE Logo" class="brand-image"
               style="opacity: .8">
          <span class="brand-text font-weight-light">liman</span>
        </a>
    
        <!-- Sidebar -->
        <div class="sidebar">  
          <!-- Sidebar Menu -->
          <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
              <!-- Add icons to the links using the .nav-icon class
                   with font-awesome or any other icon font library -->
                @if(count($USER_FAVORITES))
                <li class="nav-header">{{__("Favori Sunucular")}}</li>
                @endif
                @foreach ($USER_FAVORITES as $server)
                    <li class="nav-item has-treeview @if(request('server_id') == $server->id) menu-open @endif">
                    <a href="#" class="nav-link">
                        <i class="fa fa-server nav-icon"></i>
                        <p>
                            {{$server->name}}
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview" @if(request('server_id') == $server->id) style="display: block;" @endif>
                        <li class="nav-item">
                            <a href="/sunucular/{{$server->id}}" class="nav-link">
                                <i class="fa fa-info nav-icon"></i>
                                <p>{{__("Sunucu Detayları")}}</p>
                            </a>
                        </li>
                        @foreach ($server->extensions() as $extension)
                        <li class="nav-item">
                            <a href="/l/{{$extension->id}}/{{$server->city}}/{{$server->id}}" class="nav-link">
                                <i class="fa fa-{{$extension->icon}} nav-icon"></i>
                                <p>{{__($extension->name)}}</p>
                            </a>
                        </li>
                        @endforeach
                    </ul>
                @endforeach
              <li class="nav-header">{{__("Sunucular")}}</li>
              <li class="nav-item">
                <a href="/sunucular" class="nav-link">
                    <i class="nav-icon fas fa-server"></i>
                    <p>{{__("Sunucular")}}</p>
                </a>
              </li>
              @if(count(extensions()))
                <li class="nav-header">{{__("Eklentiler")}}</li>
                @foreach(extensions() as $extension)
                    <li class="nav-item ext_nav" @if($loop->iteration > env('NAV_EXTENSION_HIDE_COUNT', 10))style="display:none;"@endif>
                        <a href="/l/{{$extension->id}}" class="nav-link">
                            <i class="nav-icon fas fa-{{$extension->icon}}"></i>
                            <p>{{__($extension->name)}}</p>
                        </a>
                    </li>
                    @if(count(extensions()) > env('NAV_EXTENSION_HIDE_COUNT', 10))
                    <li class="nav-item ext_nav_more_less">
                        <a href="javascript:void(0)" class="nav-link">
                            <p>{{__('...daha fazla')}}</p>
                        </a>
                    </li>
                    @endif
                @endforeach
              @endif
              @if(auth()->user()->isAdmin())
                <li class="nav-header">{{__("Yönetim Paneli")}}</li>
                <li class="nav-item">
                    <a href="/eklentiler" class="nav-link">
                        <i class="nav-icon fas fa-plus"></i>
                        <p>{{__("Eklentiler")}}</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/ayarlar" class="nav-link">
                        <i class="nav-icon fas fa-cog"></i>
                        <p>{{__("Ayarlar")}}</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/talepler" class="nav-link">
                        <i class="nav-icon fas fa-plus"></i>
                        <p>{{__("Yetki Talepleri")}}</p>
                        @if(\App\LimanRequest::where('status',0)->count())
                            <span class="badge badge-info right">{{\App\LimanRequest::where('status',0)->count()}}</span>
                        @endif
                    </a>
                </li>
              @else
                <li class="nav-header">{{__("Yetki Talebi")}}</li>
                <li class="nav-item">
                    <a href="/taleplerim" class="nav-link">
                        <i class="nav-icon fas fa-key"></i>
                        <p>{{__("Taleplerim")}}</p>
                    </a>
                </li>
              @endif
              <li class="nav-header">{{__("Ayarlar")}}</li>
              <li class="nav-item">
                  <a href="/kasa" class="nav-link">
                      <i class="nav-icon fas fa-key"></i>
                      <p>{{__("Kasa")}}</p>
                  </a>
              </li>
              <li class="nav-item">
                  <a href="/widgetlar" class="nav-link">
                      <i class="nav-icon fas fa-chart-pie"></i>
                      <p>{{__("Widgetlar")}}</p>
                  </a>
              </li>
            </ul>
          </nav>
          <!-- /.sidebar-menu -->
        </div>
        <!-- /.sidebar -->
      </aside>