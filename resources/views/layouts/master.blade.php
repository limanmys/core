<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{__("Liman Sistem Yönetimi")}}</title>

    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <link rel="stylesheet" href="{{mix('/css/liman.css')}}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="server_id" content="{{request('server_id') ? request('server_id') : ''}}">
    <meta name="extension_id" content="{{request('extension_id') ? request('extension_id') : ''}}">
</head>
<body class="hold-transition @yield('body_class')">
  <div class="il-isimleri"></div>

<script src="{{mix('/js/liman.js')}}"></script>

@yield('body')
</body>
<script>
    window.onload = function () {
        $(".nav.nav-tabs a").on('click',function () {
            window.location.hash = $(this).attr("href");
        });
        activeTab();
        $('table').not('.notDataTable').DataTable({
            autoFill : true,
            bFilter: true,
            destroy: true,
            "language" : {
                url : "{{asset('turkce.json')}}"
            }
        });
        let title = $(".breadcrumb-item.active").html();
        if(title !== undefined){
            document.title = title + " / Liman";
        }
        @if(auth()->check())
        setInterval(function () {
            checkNotifications();
        }, 3000);
        @endif

        $('.ext_nav').slice({{env('NAV_EXTENSION_HIDE_COUNT', 10)}}, $('.ext_nav').length).hide();
        $('.ext_nav_more_less').click(function(){
            if ($('.ext_nav').length == $('.ext_nav:visible').length) {
                $('.ext_nav_more_less').find('span').text("{{__('...daha fazla')}}");
                $('.ext_nav').slice({{env('NAV_EXTENSION_HIDE_COUNT', 10)}}, $('.ext_nav').length).hide();
            }else{
                $('.ext_nav_more_less').find('span').text("{{__('daha az...')}}");
                $('.ext_nav:hidden').show();
            }
        });
    };

    function activeTab(){
        let element = $('a[href="'+ window.location.hash +'"]');
        if(element){
            element.tab('show');
            if(element.attr("onclick")){
                element.click();
            }
        }
    }

    function terminal(serverId, name) {
        let elm = $("#terminal");
        $("#terminal .modal-body iframe").attr('src', '/sunucu/terminal?server_id=' + serverId);
        $("#terminal .modal-title").html(name + '{{__(" sunucusu terminali")}}');
        elm.modal('show');
        elm.on('hidden.bs.modal', function () {
            $("#terminal .modal-body iframe").attr('src', '');
        })
    }


    window.onbeforeunload = function () {
        Swal.fire({
            position: 'center',
            type: 'info',
            title: '{{__("Yükleniyor...")}}',
            showConfirmButton: false,
            allowOutsideClick : false,
        });
    };
</script>
</html>
