<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{__("Liman Merkezi Sistem Yönetimi")}}</title>

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
<!-- Admin Password Recovery : https://www.youtube.com/watch?v=dQw4w9WgXcQ -->
@if(auth()->check())
<script>
    toastr.options.closeButton = true;
    Echo.private('App.User.{{auth()->user()->id}}')
        .notification((notification) => {
            var data = notification['\u0000*\u0000attributes'];
            if(data){
                var errors = [
                    "error" , "health_problem"
                ];
                if(errors.includes(data.type)){
                    toastElement = toastr.error(data.message, data.title, {timeOut: 5000});
                }else if(data.type == "liman_update"){
                    toastElement = toastr.warning(data.message, data.title, {timeOut: 5000})
                }else{
                    toastElement = toastr.success(data.message, data.title, {timeOut: 5000})
                }
                var displayedNots = [];

                if(localStorage.displayedNots){
                    displayedNots = JSON.parse(localStorage.displayedNots);
                } 
                displayedNots.push(data.id);
                localStorage.displayedNots = JSON.stringify(displayedNots);
                
                $(toastElement).click(function(){
                    partialPageRequest("/bildirim/" + data.id);
                });
            }
            checkNotifications(data ? data.id : null);
    });
    jQuery(function($) {
      var path = window.location.href; // because the 'href' property of the DOM element is the absolute path
      $('nav ul a').each(function() {
        if (this.href === path) {
          $(this).addClass('active');
        }
      });
      $('.list-group a').each(function() {
        if (this.href === path) {
          $(this).addClass('active');
        }
      });
      if(localStorage.nightMode == "on"){
          $('body').addClass('skin-dark');
      }
    });

    function dataTablePresets(type){
        if(type == "normal"){
            return {
                bFilter: true,
                "language" : {
                    url : "/turkce.json"
                }
            };
        }else if(type == "multiple"){
            return {
                bFilter: true,
                select: {
                    style: 'multi'
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
                    url : "/turkce.json",
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
            $(this).closest('.dropdown').find('.dropdown-toggle').html($(this).text() + '<span class="caret"></span>');
        });
        $(".nav.nav-tabs a").on('click',function () {
            window.location.hash = $(this).attr("href");
        });
        initialPresets();
        activeTab();
        var title = $(".breadcrumb-item.active").text();
        if(title !== undefined){
            document.title = title + " / Liman";
        }
        @if(auth()->check())
            checkNotifications();
        @endif

        $('.ext_nav').slice({{getExtensionViewCount()}}, $('.ext_nav').length).hide();
        $('.ext_nav_more_less').click(function(){
            if ($('.ext_nav').length == $('.ext_nav:visible').length) {
                $('.ext_nav_more_less').find('p').text("{{__('...daha fazla')}}");
                $('.ext_nav').slice({{getExtensionViewCount()}}, $('.ext_nav').length).hide();
            }else{
                $('.ext_nav_more_less').find('p').text("{{__('daha az...')}}");
                $('.ext_nav:hidden').show();
            }
        });
    };

    function initialPresets(){
        $('table').not('.notDataTable').DataTable({
            autoFill : true,
            bFilter: true,
            destroy: true,
            "language" : {
                url : "{{asset('turkce.json')}}"
            }
        });
        $('.js-example-basic-multiple,.js-example-basic-single').select2({
            width: 'resolve'
        });
        $(":input").inputmask();
    }
</script>
</html>
