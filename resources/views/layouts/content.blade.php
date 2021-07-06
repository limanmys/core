<!-- Content Wrapper. Contains page content -->
@if(!request('partialRequest'))
<div class="content-wrapper">
@endif
    @if(auth()->check() && user()->email == "administrator@liman.dev")
    <div class="alert alert-danger customAlert">
        <i class="fas fa-heart-broken mr-1"></i>{{__("Tam yetkili ana yönetici hesabı ile giriş yaptınız, sisteme zarar verebilirsiniz.")}} <a href="/ayarlar#users">Yeni bir hesap oluşturup</a>, yetkilendirmeleri ayarlamanızı tavsiye ederiz.
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
        <div class="container-fluid @if(auth()->check() && user()->email != 'administrator@liman.dev') pt-4 @endif">
            @yield('content')
        </div>
    </section>
@if(!request('partialRequest'))
</div>
@endif
<!-- /.content-wrapper -->