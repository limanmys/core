<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    @if(auth()->check() && user()->email == "administrator@liman.app")
    <div class="alert alert-warning"
        style="padding: 10px 25px 10px 15px !important; font-size: 12px; border-radius: 0px; text-align: center; text-shadow: 3px 2px 3px rgba(255,255,255,.2); border: 0px; -webkit-box-shadow: 0px 0px 15px -2px rgba(0,0,0,0.75); -moz-box-shadow: 0px 0px 15px -2px rgba(0,0,0,0.75); box-shadow: 0px 0px 15px -2px rgba(0,0,0,0.75);">
        {{__("Tam yetkili yönetici hesabı ile giriş yaptınız, sisteme zarar verebilirsiniz.")}}
    </div>
    @endif
    <!-- Content Header (Page header) -->
    @if (trim($__env->yieldContent('content_header')))
    <div class="content-header">
        @yield('content_header')
    </div>
    @endif

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            @yield('content')
        </div>
    </section>
</div>
<!-- /.content-wrapper -->