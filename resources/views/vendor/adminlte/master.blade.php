<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>@yield('title_prefix', config('adminlte.title_prefix', ''))
        @yield('title', config('adminlte.title', 'AdminLTE 2'))
        @yield('title_postfix', config('adminlte.title_postfix', ''))</title>
    <!-- Tell the browser to be responsive to screen width -->
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <link rel="stylesheet" href="{{asset('/css/liman.css')}}">
    {{--<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.2/css/all.css" integrity="sha384-fnmOCqbTlWIlj8LyTjo7mOUStjsKC4pOpQbqyi7RrhN7udi9RwhKkMHpvLbHG9Sr" crossorigin="anonymous">--}}


    <!-- Bootstrap 3.3.7 -->
    {{--<link rel="stylesheet" href="{{ asset('vendor/adminlte/vendor/bootstrap/dist/css/bootstrap.min.css') }}">--}}
    <!-- Font Awesome -->
    {{--<link rel="stylesheet" href="{{ asset('vendor/adminlte/vendor/font-awesome/css/font-awesome.min.css') }}">--}}

    <!-- Theme style -->
    {{--<link rel="stylesheet" href="{{ asset('/css/adminlte.min.css') }}">--}}

    {{--@yield('adminlte_css')--}}

</head>
<body class="hold-transition @yield('body_class')">
<script src="{{asset('js/libraries.js')}}"></script>
<script src="{{asset('/js/liman.js')}}"></script>
<script>
    $(document).ready(function() {
        $('table').not('.notDataTable').DataTable({
            autoFill : true,
            bFilter: true,
            "language" : {
                url : "{{asset('turkce.json')}}"
            }
        });
    } );
</script>
{{--<script src="{{ asset('vendor/adminlte/vendor/jquery/dist/jquery.min.js') }}"></script>--}}
{{--<script src="{{ asset('vendor/adminlte/vendor/jquery/dist/jquery.slimscroll.min.js') }}"></script>--}}
{{--<script src="{{asset('js/jquery.js')}}"></script>--}}
{{--<script src="{{asset('js/datatables.js')}}"></script>--}}

{{--<script src="{{asset('js/other.js')}}"></script>--}}
{{--<script src="{{ asset('vendor/adminlte/vendor/bootstrap/dist/js/bootstrap.min.js') }}"></script>--}}
{{--<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-contextmenu/2.7.1/jquery.contextMenu.min.css">--}}
{{--<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-contextmenu/2.7.1/jquery.contextMenu.min.js"></script>--}}
{{--<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-contextmenu/2.7.1/jquery.ui.position.js"></script>--}}
{{--<link rel="stylesheet" href="https://cdn.datatables.net/select/1.3.0/css/select.dataTables.min.css">--}}

{{--@if(config('adminlte.plugins.datatables'))--}}
    {{--<!-- DataTables with bootstrap 3 renderer -->--}}
    {{--<script src="//cdn.datatables.net/v/bs/dt-1.10.18/datatables.min.js"></script>--}}
    {{--<link rel="stylesheet" href="//cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css">--}}
{{--@endif--}}
{{--<script src="https://cdn.datatables.net/select/1.3.0/js/dataTables.select.min.js"></script>--}}
    {{--<script src="//cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.0/Chart.bundle.min.js"></script>--}}

{{--<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/jstree/3.3.7/themes/default/style.min.css" />--}}
{{--<script src="//cdnjs.cloudflare.com/ajax/libs/jstree/3.3.7/jstree.min.js"></script>--}}
{{--<script src="https://cdn.jsdelivr.net/npm/sweetalert2@8"></script>--}}

@yield('body')
{{--@yield('adminlte_js')--}}

</body>
</html>
