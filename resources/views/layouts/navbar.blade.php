<nav class="main-header navbar navbar-expand navbar-dark" style="height:58.86px;border:0px;">
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-toggle="tooltip" title="{{ __('Menüyü gizle') }}"
                data-widget="pushmenu" href="#" onclick="$('[role=tooltip]').remove(); collapseNav()"
                id="collapseMenu"><i class="fas fa-bars"></i></a>
        </li>
</ul>
@if (request()->request->get('server') != null)
    <ul class="mx-auto order-0 navbar-nav text-white d-md-block d-sm-none">
        <li style="font-weight:bolder;font-size:20px;cursor:pointer;" data-toggle="tooltip"
            data-original-title="{{ server()->ip_address }}"
            onclick="window.location.href = '{{ route('server_one', [
                'server_id' => server()->id,
            ]) }}'">
            {{ server()->name }}</li>
    </ul>
@endif
<!-- Right navbar links -->
<ul class="navbar-nav @if (request()->request->get('server') == null) ml-auto @endif">
    <li class="nav-item dropdown btn-group">
        <a class="nav-link" data-toggle="dropdown" href="#">
            <i class="fa-solid fa-globe"></i>
        </a>
        <div class="dropdown-menu dropdown-menu-right p-0" style="min-width: 150px !important;">
                @foreach (getLanguageNames() as $short => $long)
                <a href="{{ route('set_locale', ['locale' => $short]) }}" class="dropdown-item">
                    {{ strtoupper($short) }} {{ $long }}
                </a>
                @endforeach
        </div>
    </li>

    <li class="btn-group nav-item dropdown">
        <a class="nav-link" data-toggle="dropdown" href="#">
            <i class="fa fa-user mr-1"></i>
            <span class="d-none d-sm-inline-block"
                title="{{ user()->name, 20 }}">{{ str_limit(user()->name, 20) }}</span>
        </a>
        <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right animate slideIn">
            <div class="card widget-user-2" style="margin-bottom: 0px;">
                <div class="widget-user-header">
                    <h3 class="widget-user-username" style="margin-left: 0px; font-weight: 600;" title="{{ user()->name, 20 }}">
                        {{ str_limit(user()->name, 20) }}</h3>
                    <h5 class="widget-user-desc" style="margin-left: 0px;font-size: 13px;">
                        {{ __('Son Giriş Tarihi: ') . \Carbon\Carbon::parse(user()->last_login_at)->isoFormat('LLL') }}
                    </h5>
                    <h5 class="widget-user-desc" style="margin-left: 0px;font-size: 13px;">
                        {{ __('Giriş Yapılan Son IP: ') . user()->last_login_ip }}</h5>
                    <h5 class="widget-user-desc" style="margin-left: 0px;font-size: 13px;">
                        {{ __('Bağlı Liman: ') . getLimanHostname() }}</h5>
                    <h5 class="widget-user-desc" style="margin-left: 0px;font-size: 11px;">{{ __('Liman ID: ') }} <span
                            id="liman-id">{{ getLimanId() }}</span> <i data-toggle="tooltip"
                            data-original-title="{{ __('Liman ID Kopyala') }}" id="copy-liman-id"
                            class="far fa-copy fa-lg ml-1" style="cursor: pointer;" onclick="copyToClipboard('liman-id')"></i></h5>
                </div>
                <div class="row" style="border-top: 1px solid rgba(0, 0, 0, 0.05); margin: 0">
                    <div class="col-md-12">
                        <a class="notif-action dropdown-item text-center btn-link d-block" style="color: #ff4444 !important; background: #fff !important" 
                            href="{{ route('logout') }}"
                            onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        {{ __('Çıkış Yap') }} <span class="fas fa-sign-out-alt" style="font-size: 14px;"></span>
                        </a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST"
                            style="display: none;">
                            @csrf
                        </form>
                    </div>
                </div>
                
                
            </div>
        </div>
    </li>
</ul>
</nav>
