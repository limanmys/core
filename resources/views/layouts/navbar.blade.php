@if(env('LIMAN_RESTRICTED') == true && !user()->isAdmin())
<nav class="main-header navbar navbar-expand navbar-dark" style="margin-left:0px;height:58.86px;border:0px;">
<ul class="navbar-nav"  style="line-height:60px;">
        <a href="/" class="brand-link">
            <img src="/images/limanlogo.svg" height="30" style="opacity: .8;cursor:pointer;" title="Versiyon {{getVersion()}}">
        </a>
<li class="nav-item d-none d-sm-inline-block">
              <a href="/" class="nav-link" style="padding-top: 0px;">{{__("Ana Sayfa")}}</a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
              <a href="/ayarlar/{{request('extension_id')}}/{{request('server_id')}}" class="nav-link" style="padding-top: 0px;">{{__("Ayarlar")}}</a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
              <a href="mailto:{{env('APP_NOTIFICATION_EMAIL')}}?subject={{env('BRAND_NAME')}} {{extension()->display_name}} {{extension()->version}}" class="nav-link" style="padding-top: 0px;">{{__("Destek Al")}}</a>
            </li>
@else
<nav class="main-header navbar navbar-expand navbar-dark" style="height:58.86px;border:0px;"> <!-- exactly 58.86 :) -->
    <ul class="navbar-nav">
          <li class="nav-item">
            <a class="nav-link" data-toggle="tooltip" title="{{ __('Menüyü gizle') }}" data-widget="pushmenu" href="#" onclick="collapseNav()"><i class="fas fa-bars"></i></a>
          </li>
          <li class="nav-item">
            <a class="nav-link" data-toggle="tooltip" title="{{ __('Karanlık mod') }}" onclick="toggleDarkMode()"><i id="darkModeIcon" class="fas fa-sun"></i></a>
          </li>
@endif
          <script>
            if(typeof currentlyDark != "undefined" && currentlyDark == true){
              setDarkMode();
            }

            function collapseNav(){
              request('{{route('set_collapse')}}',new FormData(),null);
            }
          </script>
          <li class="nav-item d-none d-md-block">
            <a href="/takip" class="nav-link" data-toggle="tooltip" title="{{ __('Sunucu Takibi') }}">
              <i class="nav-icon fas fa-grip-horizontal"></i>
            </a>
          </li>
          <li class="nav-item d-none d-md-block">
                <a href="/bilesenler" class="nav-link" data-toggle="tooltip" @if(request()->getRequestUri() == '/bilesenler')class="active"@endif title='{{__("Bileşenler")}}'>
                      <i class="nav-icon fas fa-chart-pie"></i>
                </a>
          </li>
        </ul>
        @if(request('server') != null)
        <ul class="mx-auto order-0 navbar-nav text-white d-md-block d-sm-none">
                <li style="font-weight:bolder;font-size:20px;cursor:pointer;" data-toggle="tooltip" data-original-title="{{server()->ip_address}}" onclick="window.location.href = '{{route('server_one',[
                    "server_id" => server()->id,
                ])}}'">{{server()->name}}</li>
            </ul>
        @endif
        <!-- Right navbar links -->
        <ul class="navbar-nav @if(request('server') == null) ml-auto @endif">
          
          <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#">
              @if (session('locale') === "tr")
                TR
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
                    TR Türkçe
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
                    <i class="fa fa-user mr-1"></i>
                    <span class="d-none d-sm-inline-block" title="{{ user()->name, 20 }}">{{ str_limit(user()->name, 20)}}</span>
                </a>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                    <div class="card card-widget widget-user-2" style="margin-bottom: 0px;">
                        <div class="widget-user-header bg-secondary" style="color:white">
                          <h3 class="widget-user-username" style="margin-left: 0px;" title="{{ user()->name, 20 }}">{{ str_limit(user()->name, 20)}}</h3>
                          <h5 class="widget-user-desc" style="margin-left: 0px;font-size: 13px;">{{__("Son Giriş Tarihi : ") . \Carbon\Carbon::parse(user()->last_login_at)->isoFormat('LL')}}</h5>
                          <h5 class="widget-user-desc" style="margin-left: 0px;font-size: 13px;">{{__("Giriş Yapılan Son Ip : ") . user()->last_login_ip}}</h5>
                          <h5 class="widget-user-desc" style="margin-left: 0px;font-size: 13px;">{{__("Bağlı Liman : ") . getLimanHostname()}}</h5>
                          <h5 class="widget-user-desc" style="margin-left: 0px;font-size: 11px;">{{__("Liman ID: ") . getLimanId()}}</h5>
                        </div>
                        <div class="card-footer p-0">
                          <ul class="nav flex-column" style="cursor:pointer;">
                          @if (auth()->user()->isAdmin())
                            <li class="nav-item">
                              <a href="/talepler" class="nav-link text-dark">
                              <i class="nav-icon fas fa-plus mr-1"></i>
                              {{__("Yetki Talepleri")}}
                              @if(\App\Models\LimanRequest::where('status',0)->count())
                              <span class="badge badge-info right">{{\App\Models\LimanRequest::where('status',0)->count()}}</span>
                              @endif
                              </a>
                            </li>
                          @else 
                          <li class="nav-item">
                            <a href="/taleplerim" class="nav-link text-dark">
                              <i class="nav-icon fas fa-key mr-1"></i>
                              {{__("Yetki Talebi")}}
                            </a>
                          </li>
                          @endif
                            <li class="nav-item">
                              <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="nav-link text-dark">
                                {{__("Çıkış Yap")}}	&nbsp;<i class="fas fa-sign-out-alt"></i>
                              </a>
                              <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                  @csrf
                              </form>
                            </li>
                          </ul>
                        </div>
                    </div>
                </div>
            </li>
        </ul>
      </nav>

