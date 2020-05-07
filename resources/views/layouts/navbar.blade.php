<nav class="main-header navbar navbar-expand navbar-light">
        <!-- Left navbar links -->
        <ul class="navbar-nav">
          <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" onclick="request('{{route('set_collapse')}}',new FormData(),null)"><i class="fas fa-bars"></i></a>
          </li>
          @if(config('liman.liman_restricted') == true && !user()->isAdmin())
          <li class="nav-item d-none d-sm-inline-block">
            <a href="/" class="nav-link">{{__("Ana Sayfa")}}</a>
          </li>
          <li class="nav-item d-none d-sm-inline-block">
            <a href="/ayarlar/{{request('extension_id')}}/{{request('server_id')}}" class="nav-link">{{__("Ayarlar")}}</a>
          </li>
          @endif
        </ul>
        <!-- Right navbar links -->
        <ul class="navbar-nav ml-auto">
          <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#">
              @if (session('locale') === "tr")
                <i class="flag-icon flag-icon-tr"></i>
              @else
                EN
              @endif
            </a>
            <div class="dropdown-menu dropdown-menu-right p-0">
                @if (session('locale') === "tr")
                  <a href="{{route('set_locale', ['locale' => 'en'])}}" class="dropdown-item active">
                    EN English
                  </a>
                @elseif (session('locale') === "en")
                  <a href="{{route('set_locale', ['locale' => 'tr'])}}" class="dropdown-item active">
                    <i class="flag-icon flag-icon-tr mr-2"></i> Türkçe
                  </a>
                @endif
            </div>
          </li>
          <!-- Notifications Dropdown Menu -->
          @if(user()->isAdmin())
            <li id="adminNotifications" class="nav-item dropdown">
              @include('notifications',["notifications" => adminNotifications(),"id" =>
              "adminNotifications","systemNotification" => true])
            </li>
          @endif
          <li id="userNotifications" class="nav-item dropdown">
            @include('notifications',["notifications" => notifications()])
          </li>
          <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#">
                    <i class="fa fa-user"></i>
                    <span class="d-none d-sm-inline-block">{{user()->name}}</span>
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

