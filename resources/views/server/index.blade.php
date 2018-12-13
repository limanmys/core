@extends('layouts.app')

@section('content')

    @include('title',[
        "title" => "Sunucular"
    ])
    @include('modal-button',[
        "class" => "btn-success",
        "target_id" => "add_server",
        "text" => "Server Ekle"
    ])<br><br>
    @if(isset($servers))
        <table class="table">
            <thead>
            <tr>
                <th scope="col">{{__("Sunucu Adı")}}</th>
                <th scope="col">{{__("İp Adresi")}}</th>
                <th scope="col">{{__("Port")}}</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($servers as $server)
                <tr onclick="location.href = '/sunucular/{{$server->_id}}';" class="highlight">
                    <td>{{$server->name}}</td>
                    <td>{{$server->ip_address}}</td>
                    <td>{{$server->port}}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @else
        <h3>{{__("Sunucunuz Bulunmuyor.")}}</h3>
    @endif
    @include('modal',[
        "id"=>"add_server",
        "title" => "Sunucu Ekle",
        "url" => route('server_add'),
        "next" => "redirect",
        "inputs" => [
            "Adı" => "name:text",
            "İp Adresi" => "ip_address:text",
            "Bağlantı Portu" => "port:number",
            "Anahtar Kullanıcı Adı" => "username:text",
            "Anahtar Parola" => "password:password"
        ],
        "submit_text" => "Ekle"
    ])
@endsection