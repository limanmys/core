@extends('layouts.app')

@section('content')
    @include('title',[
        "title" => "Tüm Sunucular"
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
                <tr onclick="dummy('{{$server->_id}}')" class="highlight">
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

    <script>
        function dummy(id){
            let main = document.getElementsByTagName('main')[0];
            main.innerHTML = document.getElementsByClassName('loading')[0].innerHTML;
            document.getElementsByClassName('loading_message')[0].innerHTML = "Sunucuyla bağlantı kuruluyor";
            location.href = '/sunucular/' + id ;
        }
    </script>
@endsection