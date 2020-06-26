@if(env('LIMAN_RESTRICTED') == true && !user()->isAdmin())
<nav class="main-header navbar navbar-expand navbar-dark" style="margin-left:0px;max-height:60px">
<ul class="navbar-nav"  style="line-height:45px;">
<a href="/" class="brand-link">
        <img src="/images/liman_logo-white.svg" height="30" style="opacity: .8;margin-left: 0.3rem;">
          <span class="right badge badge-success" style="margin-left:10px;">{{getVersion()}}</span>
        </a>
<li class="nav-item d-none d-sm-inline-block">
              <a href="/" class="nav-link">{{__("Ana Sayfa")}}</a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
              <a href="/ayarlar/{{request('extension_id')}}/{{request('server_id')}}" class="nav-link">{{__("Ayarlar")}}</a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
              <a href="mailto:{{env('APP_NOTIFICATION_EMAIL')}}?subject={{env('BRAND_NAME')}} {{extension()->display_name}} {{extension()->version}}" class="nav-link">{{__("Destek Al")}}</a>
            </li>
@else
<nav class="main-header navbar navbar-expand navbar-dark" style="height:58.86px;border:0px;"> <!-- exactly 58.86 :) -->
<ul class="navbar-nav">
          <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" onclick="request('{{route('set_collapse')}}',new FormData(),null)"><i class="fas fa-bars"></i></a>
          </li>
          <li class="nav-item">
            <a class="nav-link" onclick="toggleDarkMode()"><i id="darkModeIcon" class="fas fa-sun"></i></a>
          </li>
@endif
<script>
	if(currentlyDark == true){
		setDarkMode();
	}
</script>
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
                        <div class="widget-user-header bg-warning" style="color:black">
                          <h3 class="widget-user-username" style="margin-left: 0px;">{{user()->name}}</h3>
                          <h5 class="widget-user-desc" style="margin-left: 0px;font-size: 13px;">{{__("Son Giriş Tarihi : ") . user()->last_login_at}}</h5>
                          <h5 class="widget-user-desc" style="margin-left: 0px;font-size: 13px;">{{__("Giriş Yapılan Son Ip : ") . user()->last_login_ip}}</h5>
                          <h5 class="widget-user-desc" style="margin-left: 0px;font-size: 13px;">{{__("Bağlı Liman : ") . getLimanHostname()}}</h5>
                        </div>
                        <div class="card-footer p-0">
                          <ul class="nav flex-column">
                            <li class="nav-item">
                              <a href="{{route('my_profile')}}" class="nav-link text-dark">
                                {{__("Profil")}}
                              </a>
                            </li>
                            <li class="nav-item">
                              <a href="{{route('my_access_tokens')}}" class="nav-link text-dark">
                                {{__("Kişisel Erişim Anahtarları")}}
                              </a>
                            </li>
                            <li class="nav-item">
                              <a onclick="request('/cikis',new FormData(),null)" class="nav-link text-dark">
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

