<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    @if(auth()->check() && user()->email == "administrator@liman.app")
    <div class="alert alert-danger customAlert">
        <b>{{__("Tam yetkili yönetici hesabı ile giriş yaptınız, sisteme zarar verebilirsiniz.")}}</b>
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