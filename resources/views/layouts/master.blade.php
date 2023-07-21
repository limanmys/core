<!DOCTYPE html>
<html lang="{{ session('locale') }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{__("Liman Merkezi Yönetim Sistemi")}}</title>

    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="{{mix('/css/liman.css')}}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="server_id" content="{{request('server_id') ? request('server_id') : ''}}">
    <meta name="extension_id" content="{{request('extension_id') ? request('extension_id') : ''}}">
</head>
<body class="hold-transition @yield('body_class')">
<script>
    var module = { };
</script>
<script src="{{mix('/js/liman.js')}}"></script>

@yield('body')

</body>
<script>
    window.onload = function () {
        $(".dropdown-menu").on('click', 'a.dropdown-item', function(){
            $(this).closest('.dropdown').find('.dropdown-toggle').html($(this).html() + '<span class="caret"></span>');
        });
        $(".nav.nav-tabs a").on('click',function () {
            window.location.hash = $(this).attr("href");
        });
        navigateButtons();
        activeTab();
        var title = $(".breadcrumb-item.active").text();
        if(title != ""){
            document.title = title + " / Liman";
        }
        initialPresets();
    };

    function publicPath(path, extension_id=null){
        if(extension_id == null){
            extension_id = $("meta[name=extension_id]").attr("content");
        }
        return "{{ route('home') }}/eklenti/"+extension_id+"/public/"+path;
    }

    function initialPresets(){
        $('table').not('.notDataTable').not(".bx--data-table").not(".n-data-table-table").DataTable({
            autoFill : true,
            bFilter: true,
            destroy: true,
            "language" : {
                url : "{{asset(__('/turkce.json'))}}"
            }
        });
        $('.js-example-basic-multiple,.js-example-basic-single,.select2').select2({
            width: 'resolve',
            theme: 'bootstrap4',
        });
        $(":input").inputmask();
    }

    function navigateButtons(){
        jQuery(function($) {
            var path = window.location.origin + window.location.pathname;
            $('nav ul a').each(function() {
                if (this.href === path) {
                    $(this).addClass('active');
                }else if(!$(this).hasClass("extension-link")){
                    $(this).removeClass('active')
                }
            });
            $('.list-group a').each(function() {
                if (this.href === path) {
                    $(this).addClass('active');
                }else{
                    $(this).removeClass('active')
                }
            });
            if(localStorage.nightMode === "on"){
                $('body').addClass('skin-dark');
            }
        });
    }
</script>
</html>
