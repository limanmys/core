@if(request()->wantsJson())
    @php(respond(__($exception->getMessage()),254))
@else
    @php($rand = str_random(16))
    @include('extension_pages.server',[
        "viewName" => "",
        "view" => "<div id='$rand'>Talebiniz yoğunluktan dolayı kuyruğa alındı, lütfen bekleyin...</div>",
        "timestamp" => 0
    ])
    <script>
        $(function () {
            observeAPIRequest('{{$exception->getMessage()}}',null,'#{{$rand}}');
        });
    </script>
@endif