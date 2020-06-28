<?php
    $servers = extension()->servers(request('city'));
    if($servers->count() == 1){
        $url = route('extension_server',[
            "extension_id" => extension()->id,
            "city" => request('city'),
            "server_id" => $servers->first()->id
        ]);
        header("Location: $url", true);
        exit();
    }
?>

@extends('layouts.app')

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{route('home')}}">{{__("Ana Sayfa")}}</a></li>
            <li class="breadcrumb-item"><a href="/l/{{extension()->id}}">{{__(extension()->display_name)}} {{ __('Sunucuları') }}</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{cities(request('city'))}}</li>
        </ol>
    </nav>
    @if(count($servers) == 0)
    <div class="alert alert-warning" role="alert">
            Bu eklentiyi kullanan hiçbir sunucu yok, hemen <a href="{{route('servers')}}">sunucular</a> sayfasına gidip mevcut sunucularınızdan birini bu eklentiyi kullanması için ayarlayabilirsiniz.
        </div>
    @else
    
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{__(extension()->display_name)}} {{ __('Sunucuları') }}</h3>
        </div>
        <div class="card-body">
            @include('errors')
            @include('table',[
                "value" => $servers,
                "title" => [
                    "Sunucu Adı" , "İp Adresi" , "Sunucu Tipi" , "Kontrol Portu", "*hidden*" ,"*hidden*"
                ],
                "display" => [
                    "name" , "ip_address", "type" , "control_port", "city:city", "id:server_id"
                ],
                "onclick" => "details"
            ])
        </div>
    </div>
    <script>
        function details(element) {
            let server_id = element.querySelector('#server_id').innerHTML;
            window.location.href = window.location.href + "/" + server_id
        }
    </script>
    @endif
@endsection