@extends('layouts.app')

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{route('home')}}">{{__("Ana Sayfa")}}</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{__("Sunucular")}}</li>
        </ol>
    </nav>
    @include('l.modal-button',[
        "class" => "btn-success",
        "target_id" => "add_server",
        "text" => "Server Ekle"
    ])<br><br>

    @include('l.table',[
        "value" => servers(),
        "title" => [
            "Sunucu Adı" , "İp Adresi" , "*hidden*" , "Kontrol Portu", "*hidden*" ,"*hidden*"
        ],
        "display" => [
            "name" , "ip_address", "type:type" , "control_port", "city:city", "_id:server_id"
        ],
        "menu" => [
            "Düzenle" => [
                "target" => "edit",
                "icon" => "fa-edit"
            ],
            "Yetki Ver" => [
                "target" => "give_permission",
                "icon" => "fa-unlock"
            ],
            "Sil" => [
                "target" => "delete",
                "icon" => "fa-trash"
            ]
        ],
        "onclick" => "details"
    ])

    @include('l.modal',[
        "id"=>"add_server",
        "title" => "Sunucu Ekle",
        "url" => route('server_add'),
        "selects" => [
            "Linux Sunucusu:linux" => [
                "Linux:linux" => "type:hidden"
            ],
            "Linux Sunucusu (SSH):linux_ssh" => [
                "SSH Kullanıcı Adı" => "username:text",
                "SSH Parola" => "password:password",
                "SSH Portu" => "port:number",
                "Linux SSH:linux_ssh" => "type:hidden"
            ],
            "Windows Sunucusu:windows" => [
                "Windows:windows" => "type:hidden"
            ],
            "Windows Sunucusu (PowerShell):windows_powershell" => [
                "Uzak Masaüstu Hesabı" => "username:text",
                "Uzak Masaüstü Parolası" => "password:password",
                "Windows Powershell:windows_powershell" => "type:hidden"
            ]
        ],
        "inputs" => [
            "Adı" => "name:text",
            "İp Adresi" => "ip_address:text",
            "Sunucu Durumu Kontrol Portu" => "control_port:number",
            "Şehir:city" => cities(),

        ],
        "submit_text" => "Ekle"
    ])

    <script>
        function details(element) {
            let server_id = element.querySelector('#server_id').innerHTML;
            window.location.href = "/sunucular/" + server_id
        }
    </script>

    @include('l.modal',[
       "id"=>"delete",
       "title" =>"Sunucuyu Sil",
       "url" => route('server_remove'),
       "text" => "Sunucuyu silmek istediğinize emin misiniz? Bu işlem geri alınamayacaktır.",
       "next" => "reload",
       "inputs" => [
           "Sunucu Id:'null'" => "server_id:hidden"
       ],
       "submit_text" => "Sunucuyu Sil"
   ])

    @include('l.modal',[
        "id"=>"edit",
        "title" => "Sunucuyu Düzenle",
        "url" => route('server_update'),
        "next" => "updateTable",
        "inputs" => [
            "Sunucu Adı" => "name:text",
            "Kontrol Portu" => "control_port:number",
            "Sunucu Id:''" => "server_id:hidden",
            "Şehir:city" => cities(),
        ],
        "submit_text" => "Düzenle"
    ])

    @include('l.modal',[
        "id"=>"give_permission",
        "title" => "Kullanıcıya Yetki Ver",
        "url" => route('server_grant_permission'),
        "next" => "function(){return true;}",
        "inputs" => [
            "Kullanıcı Emaili" => "email:text",
            "Sunucu Id:a" => "server_id:hidden"
        ],
        "text" => "Güvenlik sebebiyle kullanıcı listesi sunulmamaktadır.",
        "submit_text" => "Yetkilendir"
    ])

@endsection