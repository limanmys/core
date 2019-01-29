@extends('layouts.app')

@section('content_header')
    <h1>{{ __("SSH Anahtarları") }}</h1>
@stop

@section('content')

    @include('modal-button',[
        "text" => "Anahtar Ekle",
        "class" => "btn-success",
        "target_id" => "add_key"
    ])<br><br>

    @include('table',[
        "value_list" => $keys,
        "name_list" => [
            "Sunucu" , "Kullanıcı" , "*hidden*" , "*hidden*"
        ],
        "display" => [
            "name" , "username", "_id:key_id" , "server_id"
        ],
        "menu_items" => [
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

    @include('modal',[
        "id"=>"add_key",
        "title" => "SSH Anahtar Ekle",
        "url" => route('key_add'),
        "next" => "reload",
        "inputs" => [
            "Adı" => "name:text",
            "Sunucu Secin:server_id" => objectToArray($servers,"name","_id"),
            "Kullanıcı Adı" => "username:text",
            "Parola" => "password:password"
        ],
        "submit_text" => "Ekle"
    ])

    @include('modal',[
        "id"=>"edit",
        "title" => "Anahtarı Düzenle",
        "url" => route('key_add'),
        "next" => "reload",
        "inputs" => [
            "Adı" => "name:text",
            "Sunucu Secin:server_id" => objectToArray($servers,"name","_id"),
            "Kullanıcı Adı" => "username:text",
            "Parola" => "password:password"
        ],
        "submit_text" => "Düzenle"
    ])
@endsection