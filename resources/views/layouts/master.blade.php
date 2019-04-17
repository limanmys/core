<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{__("Liman Sistem Yönetimi")}}</title>

    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <link rel="stylesheet" href="{{asset('/css/liman.css')}}">
    <link rel="stylesheet" href="{{ asset('vendor/adminlte/dist/css/skins/skin-blue.min.css')}} ">
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="hold-transition @yield('body_class')">
<script src="{{asset('js/libraries.js')}}"></script>
<script src="{{asset('/js/liman.js')}}"></script>

@yield('body')
</body>
<script>
    window.onload = function () {
        Swal.close();
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
    };

</script>
</html>
