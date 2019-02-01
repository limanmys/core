@extends('layouts.app')

@section('content_header')
    <h1>{{extension()->name}} {{ __('Sunucuları') }}</h1>
@stop

@section('content')

    <button class="btn btn-success" onclick="history.back();">{{ __('Geri Dön') }}</button><br><br>
    @include('l.table',[
        "value" => \App\Extension::servers(request('city')),
        "title" => [
            "Sunucu Adı" , "İp Adresi" , "Sunucu Tipi" , "Kontrol Portu", "*hidden*" ,"*hidden*"
        ],
        "display" => [
            "name" , "ip_address", "type" , "control_port", "city:city", "_id:server_id"
        ],
        "onclick" => "details"
    ])

    <script>
        function details(element) {
            let server_id = element.querySelector('#server_id').innerHTML;
            window.location.href = window.location.href + "/" + server_id
        }
    </script>
@endsection