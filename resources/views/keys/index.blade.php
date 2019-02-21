@extends('layouts.app')

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{route('home')}}">{{__("Ana Sayfa")}}</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{__("Anahtarlar")}}</li>
        </ol>
    </nav>
    @include('l.modal-button',[
        "text" => "Anahtar Ekle",
        "class" => "btn-success",
        "target_id" => "add_key"
    ])<br><br>

    @include('l.table',[
        "value" => $keys,
        "title" => [
            "Sunucu" , "Kullanıcı" , "*hidden*" , "*hidden*"
        ],
        "display" => [
            "name" , "username", "_id:key_id" , "server_id"
        ],
        "menu" => [
            "Düzenle" => [
                "target" => "edit",
                "icon" => "edit"
            ],
            "Sil" => [
                "target" => "delete",
                "icon" => "delete"
            ]
        ]
    ])

    @include('l.modal',[
        "id"=>"add_key",
        "title" => "SSH Anahtar Ekle",
        "url" => route('key_add'),
        "next" => "reload",
        "inputs" => [
            "Adı" => "name:text",
            "Sunucu Seçin:server_id" => objectToArray($servers,"name","_id"),
            "Kullanıcı Adı" => "username:text",
            "Parola" => "password:password"
        ],
        "submit_text" => "Ekle"
    ])

    @include('l.modal',[
        "id"=>"edit",
        "title" => "Anahtarı Düzenle",
        "url" => route('key_add'),
        "next" => "reload",
        "inputs" => [
            "Adı" => "name:text",
            "Sunucu Seçin:server_id" => objectToArray($servers,"name","_id"),
            "Kullanıcı Adı" => "username:text",
            "Parola" => "password:password"
        ],
        "submit_text" => "Düzenle"
    ])
@endsection