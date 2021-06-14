@include('layouts.navbar')
      <!-- Main Sidebar Container -->
      <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <!-- Brand Logo -->
        <a href="/" class="brand-link">
            <img id="limanLogo" src="/images/limanlogo_hq.png" height="20" style="opacity: .8;cursor:pointer;" title="Versiyon {{getVersion() . ' Build : ' . getVersionCode()}}">
        </a>
        <!-- Sidebar -->
        <div class="sidebar">  
          <!-- Sidebar Menu -->
          <nav>
            <ul id="liman-sidebar" class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                @if(count($SERVERS))
                <li class="nav-header">{{__("Sunucular")}}</li>
                @endif
                @foreach ($USER_FAVORITES as $server)
                <li class="nav-item has-treeview @if(request('server_id') == $server->id) menu-open @endif">
                    <a href="#" class="nav-link @if(request('server_id') == $server->id) active @endif">
                        <i class="fab {{ $server->isLinux() ? 'fa-linux' : 'fa-windows' }} nav-icon" style="font-weight: 400"></i>
                        <p>
                            {{$server->name}}
                            <i class="right fas fa-angle-right"></i>
                            <i class="fas fa-thumbtack right mr-3 mt-1" style="font-size: 14px; transform: none!important"></i>
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
                            <a href='/l/{{$extension->id}}/{{$server->city}}/{{$server->id}}' class="nav-link @if(request('extension_id') == $extension->id) active @endif">
                                <i class="nav-icon {{ empty($extension->icon) ? 'fab fa-etsy' : 'fas fa-'.$extension->icon}}"></i>
                                <p>{{__($extension->display_name)}}</p>
                            </a>
                        </li>
                        @endforeach
                    </ul>
                @endforeach
                @foreach ($SERVERS as $server)
                <li class="nav-item has-treeview @if(request('server_id') == $server->id) menu-open @endif">
                    <a href="#" class="nav-link @if(request('server_id') == $server->id) active @endif">
                        <i class="fab {{ $server->isLinux() ? 'fa-linux' : 'fa-windows' }} nav-icon" style="font-weight: 400"></i>
                        <p>
                            {{$server->name}}
                            <i class="right fas fa-angle-right"></i>
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
                            <a href='/l/{{$extension->id}}/{{$server->city}}/{{$server->id}}' class="nav-link @if(request('extension_id') == $extension->id) active @endif">
                                <i class="nav-icon {{ empty($extension->icon) ? 'fab fa-etsy' : 'fas fa-'.$extension->icon}}"></i>
                                <p>{{__($extension->display_name)}}</p>
                            </a>
                        </li>
                        @endforeach
                    </ul>
                @endforeach
                @if(count($SERVERS) > 1) 
                <li class="nav-item">
                <a href='/sunucular' class="nav-link">
                    <i class="nav-icon fas fa-ellipsis-h"></i>
                    <p>{{__("Tüm sunucuları gör")}}</p>
                </a>
                 </li>
                @else
                <li class="nav-item">
                <a href='/sunucular' class="nav-link">
                    <i class="nav-icon fas fa-plus"></i>
                    <p>{{__("Sunucu ekle")}}</p>
                </a>
                </li>

                <li class="nav-item">
                    <p style="color: white; padding: 10px 20px; font-weight: 600;">
                    Liman kullanmaya başlamak için<br> yukarıdan sunucu ekleyin.
                    </p>
                </li>
                @endif
            </ul>
          </nav>
          
          <!-- /.sidebar-menu -->
        </div>
        <div class="sidebar-bottom">
            <div class="container">
                <div class="row">
                    @if(auth()->user()->isAdmin())
                    <div class="col">
                        <a href="/ayarlar" data-toggle="tooltip" @if(request()->getRequestUri() == '/ayarlar')class="active"@endif title='{{__("Sistem Ayarları")}}'>
                            <i class="nav-icon fas fa-cog"></i>
                            
                        </a>
                    </div>
                    @endif
                    <div class="col">
                        <a href="/profil" data-toggle="tooltip" @if(request()->getRequestUri() == '/profil')class="active"@endif title='{{__("Profil")}}'>
                            <i class="nav-icon fas fa-user"></i>
                        </a>
                    </div>
                    <div class="col">
                        <a href="/kasa" data-toggle="tooltip" @if(request()->getRequestUri() == '/kasa')class="active"@endif title='{{__("Kasa")}}'>
                            <i class="nav-icon fas fa-wallet"></i>
                        </a>
                    </div>
                    <div class="col">
                        <a href="/profil/anahtarlarim" data-toggle="tooltip" @if(request()->getRequestUri() == '/profil/anahtarlarim')class="active"@endif title='{{__("Erişim Anahtarları")}}'>
                            <i class="nav-icon fas fa-user-secret"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.sidebar -->
      </aside>