@extends('layouts.app')

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{route('home')}}">{{__("Ana Sayfa")}}</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{__("Ayarlar")}}</li>
        </ol>
    </nav>
    @include('l.modal-button',[
        "class" => "btn-success",
        "target_id" => "add_user",
        "text" => "Kullanıcı Ekle"
    ])<br><br>
    @include('l.table',[
        "value" => \App\User::all(),
        "title" => [
            "Sunucu Adı" , "Email" , "*hidden*" ,
        ],
        "display" => [
            "name" , "email", "_id:user_id" ,
        ],
        "menu" => [
            "Yetkileri Düzenle" => [
                "target" => "edit",
                "icon" => "fa-edit"
            ],
            "Parolayı Sıfırla" => [
                "target" => "passwordReset",
                "icon" => "fa-lock"
            ],
            "Sil" => [
                "target" => "delete",
                "icon" => "fa-trash"
            ]
        ],
        "onclick" => "details"
    ])


    @include('l.modal',[
        "id"=>"add_user",
        "title" => "Kullanıcı Ekle",
        "url" => route('user_add'),
        "next" => "nothing",
        "selects" => [
            "Yönetici:administrator" => [
                "-:administrator" => "type:hidden"
            ],
            "Kullanıcı:user" => [
                "-:user" => "type:hidden"
            ]
        ],
        "inputs" => [
            "Adı" => "name:text",
            "E-mail Adresi" => "email:text",
        ],
        "submit_text" => "Ekle"
    ])

    @include('l.modal',[
       "id"=>"delete",
       "title" =>"Kullanıcıyı Sil",
       "url" => route('user_remove'),
       "text" => "Kullanıcıyı silmek istediğinize emin misiniz? Bu işlem geri alınamayacaktır.",
       "next" => "reload",
       "inputs" => [
           "Kullanici Id:'null'" => "user_id:hidden"
       ],
       "submit_text" => "Kullanıcıyı Sil"
   ])

    @include('l.modal',[
       "id"=>"passwordReset",
       "title" =>"Parolayı Sıfırla",
       "url" => route('user_password_reset'),
       "text" => "Parolayı sıfırlamak istediğinize emin misiniz? Bu işlem geri alınamayacaktır.",
       "next" => "nothing",
       "inputs" => [
           "Kullanici Id:'null'" => "user_id:hidden"
       ],
       "submit_text" => "Parolayı Sıfırla"
   ])

@endsection