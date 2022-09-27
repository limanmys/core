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
<div class="il-isimleri"></div>
<body class="hold-transition @yield('body_class')">
<script>
    var module = { };
</script>
<script src="{{mix('/js/liman.js')}}"></script>
@if(auth()->check())
<script>
    toastr.options.closeButton = true;
    function isJson(str) {
        try {
            JSON.parse(str);
        } catch (e) {
            return false;
        }
        return true;
    }
    Echo.private('App.User.{{auth()->user()->id}}')
        .notification((notification) => {
            var data = notification['\u0000*\u0000attributes'];
            if(data){
                var errors = [
                    "error" , "health_problem"
                ];
                let notificationTitle = decodeURIComponent(JSON.parse('"' + data["title"].replace(/\"/g, '\\"') + '"'));
                let notificationMsg = decodeURIComponent(JSON.parse('"' + data["message"].replace(/\"/g, '\\"') + '"'));

                if (isJson(notificationTitle)) {
                    let temp = JSON.parse(notificationTitle)
                    notificationTitle = temp[language];
                } else {
                    notificationTitle = data["title"];
                }

                if (isJson(notificationMsg)) {
                    let temp = JSON.parse(notificationMsg)
                    notificationMsg = temp[language];
                } else {
                    notificationMsg = data["message"];
                }

                if(errors.includes(data.type)){
                    toastElement = toastr.error(notificationMsg, notificationTitle, {timeOut: 5000});
                }else if(data.type == "liman_update"){
                    toastElement = toastr.warning(notificationMsg, notificationTitle, {timeOut: 5000})
                }else{
                    toastElement = toastr.success(notificationMsg, notificationTitle, {timeOut: 5000})
                }
                var displayedNots = [];

                if(localStorage.displayedNots){
                    displayedNots = JSON.parse(localStorage.displayedNots);
                } 
                displayedNots.push(data.id);
                localStorage.displayedNots = JSON.stringify(displayedNots);
                
                $(toastElement).click(function(){
                    window.location.href = "/bildirim/" + data.id;
                });
            }
            checkNotifications(data ? data.id : null);
    });

    function dataTablePresets(type){
        if(type == "normal"){
            return {
                bFilter: true,
                "language" : {
                    url : "{{__("/turkce.json")}}"
                }
            };
        }else if(type == "multiple"){
            return {
                bFilter: true,
                select: {
                    style: 'multi',
                    selector: 'td:not(.table-menu)'
                },
                dom: 'Blfrtip',
                buttons: {
                    buttons: [
                        { extend: 'selectAll', className: 'btn btn-xs btn-primary mr-1' },
                        { extend: 'selectNone', className: 'btn btn-xs btn-primary mr-1' }
                    ],
                    dom: {
                        button: { className: 'btn' }
                    }
                },
                language: {
                    url : "{{__("/turkce.json")}}",
                    buttons: {
                        selectAll: "{{ __('Tümünü Seç') }}",
                        selectNone: "{{ __('Tümünü Kaldır') }}"
                    }
                }
            };
        }
    }
</script>
@endif
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
        @if(auth()->check())
            checkNotifications();
        @endif
        initialPresets();
    };

    function publicPath(path, extension_id=null){
        if(extension_id == null){
            extension_id = $("meta[name=extension_id]").attr("content");
        }
        return "{{ route('home') }}/eklenti/"+extension_id+"/public/"+path;
    }

    function initialPresets(){
        $('table').not('.notDataTable').not(".bx--data-table").DataTable({
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
