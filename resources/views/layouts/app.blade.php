@if(request('partialRequest'))
    @include('layouts.content')
    @php(die())
@else
    @extends('layouts.master')

    @section('body_class', 'sidebar-mini layout-fixed ' . ((session()->has('collapse')) ? 'sidebar-collapse' : ''))

    @section('body')
        <div class="wrapper">
            @auth
                @include('layouts.header')
            @endauth
            @include('layouts.content')
        </div>
        
        <script>
        function partialPageRequest(url){
            var form = new FormData();
            var newUrl = url + "?partialRequest=true";
            form.append('partialRequest',true);
            request(newUrl,form, function(success){
                $(".content-wrapper").html(success);
                initialPresets();
                history.pushState({}, null, url);
            },function(error){
                var json = JSON.parse(error);
                showSwal(json.message,'error',2000);
            },"GET");
        }
    
    </script>
    @stop
@endif