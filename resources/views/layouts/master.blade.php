<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{__("Liman Sistem Yönetimi")}}</title>

    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <link rel="stylesheet" href="{{asset('/css/liman.css')}}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="server_id" content="{{request('server_id') ? request('server_id') : ''}}">
    <meta name="extension_id" content="{{request('extension_id') ? request('extension_id') : ''}}">

</head>
<body class="hold-transition @yield('body_class')">
<script src="{{asset('js/libraries.js')}}"></script>
<script src="{{asset('/js/liman.js')}}"></script>

@yield('body')
</body>
<script>
    window.onload = function () {
        $(".nav.nav-tabs a").on('click',function () {
            window.location.hash = $(this).attr("href");
        });
        activeTab();
        $('table').not('.notDataTable').each(function(item){
          let options = {
              autoFill : true,
              bFilter: true,
              destroy: true,
              language: {
                  url : "{{asset('turkce.json')}}"
              }
          };
          if($(this).attr("ajax-url")){
            options.ajax = {
              beforeSend: function (request) {
                let csrf = document.getElementsByName('csrf-token')[0].getAttribute('content');
                request.setRequestHeader('X-CSRF-TOKEN', csrf);
                request.setRequestHeader("Accept", "text/json");
              },
              data: function(d){
                let server_id = $('meta[name=server_id]').attr("content");
                let extension_id = $('meta[name=extension_id]').attr("content");

                if(server_id != "")
                  d.server_id = server_id;
                if(extension_id != "")
                  d.extension_id = extension_id;
              },
              type: "POST",
              url: $(this).attr("ajax-url")
            };
            options.columns = JSON.parse($(this).attr("ajax-columns"));
            options.processing = true;
            options.serverSide = true;
          }
          console.log(options)
          $(this).DataTable(options);
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

</script>
</html>
